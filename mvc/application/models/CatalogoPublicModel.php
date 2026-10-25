<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @property OrdenModel $OrdenModel
 */
class CatalogoPublicModel extends CI_Model
{
    use ApiClient;

    public $company = array();

    const CARRITO_COMPANY = "to_company";
    const CARRITO_PORTAL_SELLER = "to_portal_seller";
    const CARRITO_TO_CUSTOMER = "to_customer";

    const SOURCE_CATALOGO = "Catalog";
    const SOURCE_APP = "app";
    const SOURCE_MANUAL = "manual";
    const SOURCE_MARKETPLACE = "Marketplace";


    function __construct()
	{
        parent::__construct();
        //$this->company = $this->Helper->get_company_info();
        $this->load->model('OrdenModel');
    }

	public function obtener_categoriasCatalog($companyId,$catlogId, $cart_token = null){

        if (empty($cart_token)) {
            log_message('error', 'CatalogoPublicModel::obtener_categoriasCatalog - cart_token requerido');

            return [];
        }

        $apiCategories = $this->fetchCatalogPublicCategoriesListFromApi((string) $cart_token);
        if ($apiCategories !== null) {
            return $apiCategories;
        }

        log_message('error', 'CatalogoPublicModel::obtener_categoriasCatalog - API catalog-public/categories sin respuesta');

        return [];
	}

    public function obtener_productos_promociones($companyId, $catalogId)
    {
        log_message('debug', 'CatalogoPublicModel::obtener_productos_promociones - sin implementación API en catálogo público');

        return [];
    }

	public function get_ordercategories($compId,$catalog_id, $colum_order = null, $order_by = null, $cart_token = null){

        if (empty($cart_token)) {
            log_message('error', 'CatalogoPublicModel::get_ordercategories - cart_token requerido');

            return [];
        }

        $apiCategories = $this->fetchCatalogPublicCategoriesOrderFromApi((string) $cart_token);
        if ($apiCategories !== null) {
            return $apiCategories;
        }

        log_message('error', 'CatalogoPublicModel::get_ordercategories - API catalog-public/categories-order sin respuesta');

        return [];
	}

    public function quantity_products($request){

        $request = $this->Helper->cast($request);
        $cartToken = $request->cart_token ?? null;
        if (empty($cartToken)) {
            log_message('error', 'CatalogoPublicModel::quantity_products - cart_token requerido');

            return 0;
        }

        $count = $this->fetchCatalogPublicProductCountFromApi((string) $cartToken, $request);
        if ($count !== null) {
            return $count;
        }

        log_message('error', 'CatalogoPublicModel::quantity_products - API catalog-public/products/count sin respuesta');

        return 0;
    }

	public function get_products($request){

		$request = $this->Helper->cast($request);
        $cartToken = $request->cart_token ?? null;
        if (empty($cartToken)) {
            log_message('error', 'CatalogoPublicModel::get_products - cart_token requerido');

            return [];
        }

        $searchRequest = (object) [
            'company_id' => $request->company_id ?? null,
            'catalog_id' => $request->catalog_id ?? null,
            'search' => $request->search ?? null,
            'category_id' => $request->category_id ?? null,
            'short' => null,
        ];
        $apiProducts = $this->fetchCatalogPublicProductsFromApi((string) $cartToken, $searchRequest, 0, 100);
        if ($apiProducts === null) {
            log_message('error', 'CatalogoPublicModel::get_products - API catalog-public/products sin respuesta');

            return [];
        }

        $imageEmpty = catalog_public_no_image_url();
        foreach ($apiProducts as $product) {
            $product = $this->normalizeCatalogPublicProductRow($product);
            $product->completed_name = trim(($product->name ?? '') . ' | ' . ($product->sku ?? ''));
            $product->images = ! empty($product->imagen_js) ? $product->imagen_js : $imageEmpty;
        }

		return $apiProducts;
	}

    public function products_catalog($request,$offset=null,$limit=null, $customer,  $formatNumeric = null, $cart_token = null){

        $request = $this->Helper->cast($request);
        if (empty($cart_token)) {
            log_message('error', 'CatalogoPublicModel::products_catalog - cart_token requerido');

            return [];
        }

        $pack = $this->fetchCatalogPublicProductsPackFromApi(
            (string) $cart_token,
            $request,
            $offset,
            $limit
        );
        if ($pack !== null) {
            return array_map(function ($row) {
                return $this->normalizeCatalogPublicProductRow($row);
            }, $pack['products']);
        }

        log_message('error', 'CatalogoPublicModel::products_catalog - API catalog-public/products sin respuesta');

        return [];
    }

    /**
     * Productos + secciones taxonomía (categoría → grupo → subgrupo).
     *
     * @return array{products: list<object>, sections: list<object>}
     */
    public function products_catalog_grouped($request, $offset = null, $limit = null, $customer = null, $formatNumeric = null, $cart_token = null): array
    {
        $request = $this->Helper->cast($request);
        if (empty($cart_token)) {
            return ['products' => [], 'sections' => []];
        }

        $pack = $this->fetchCatalogPublicProductsPackFromApi(
            (string) $cart_token,
            $request,
            $offset,
            $limit
        );
        if ($pack === null) {
            return ['products' => [], 'sections' => []];
        }

        $products = array_map(function ($row) {
            return $this->normalizeCatalogPublicProductRow($row);
        }, $pack['products']);

        $token = is_string($cart_token) ? $cart_token : null;
        $sections = [];
        foreach ($pack['sections'] as $section) {
            $sections[] = $this->normalizeCatalogPublicSection($section, $token);
        }

        return ['products' => $products, 'sections' => $sections];
    }

    /**
     * @param object|array<string, mixed> $section
     */
    protected function normalizeCatalogPublicSection($section, ?string $token = null): object
    {
        $section = is_object($section) ? $section : (object) $section;
        if ($token === null || $token === '') {
            $token = $this->resolveCatalogPublicTokenForImages();
        }
        $section->category_image = $this->normalizeTaxonomyImageUrl($section->category_image ?? null, $token);
        $groups = [];
        foreach ((array) ($section->groups ?? []) as $group) {
            $group = is_object($group) ? $group : (object) $group;
            $group->group_image = $this->normalizeTaxonomyImageUrl($group->group_image ?? null, $token);
            $subs = [];
            foreach ((array) ($group->subgroups ?? []) as $sub) {
                $sub = is_object($sub) ? $sub : (object) $sub;
                $sub->subgroup_image = $this->normalizeTaxonomyImageUrl($sub->subgroup_image ?? null, $token);
                $subProducts = [];
                foreach ((array) ($sub->products ?? []) as $p) {
                    $subProducts[] = $this->normalizeCatalogPublicProductRow($p);
                }
                $sub->products = $subProducts;
                $subs[] = $sub;
            }
            $groupProducts = [];
            foreach ((array) ($group->products ?? []) as $p) {
                $groupProducts[] = $this->normalizeCatalogPublicProductRow($p);
            }
            $group->subgroups = $subs;
            $group->products = $groupProducts;
            $groups[] = $group;
        }
        $catProducts = [];
        foreach ((array) ($section->products ?? []) as $p) {
            $catProducts[] = $this->normalizeCatalogPublicProductRow($p);
        }
        $section->groups = $groups;
        $section->products = $catProducts;

        return $section;
    }

    /**
     * Asegura URL usable de taxonomía (uuid.ext + token), igual que el sidebar.
     */
    protected function normalizeTaxonomyImageUrl($imageUrl, ?string $token = null): string
    {
        $raw = trim((string) ($imageUrl ?? ''));
        if ($raw === '') {
            return '';
        }
        if (function_exists('catalog_public_image_url')) {
            // Extraer uuid[.ext] si viene URL absoluta de la API.
            if (preg_match('#/api/v2/images/catalog/([^?&#]+)#i', $raw, $m)) {
                $raw = rawurldecode($m[1]);
            }
            $url = catalog_public_image_url($raw, $token);
            $noImage = function_exists('catalog_public_no_image_url') ? catalog_public_no_image_url() : '';
            if ($url === '' || ($noImage !== '' && $url === $noImage)) {
                return '';
            }

            return $url;
        }

        return $raw;
    }

    protected function resolveCatalogPublicTokenForImages(): ?string
    {
        $ci = function_exists('get_instance') ? get_instance() : null;
        if ($ci && isset($ci->input)) {
            $t = $ci->input->get('token');
            if (is_string($t) && $t !== '') {
                return $t;
            }
        }

        return null;
    }

	public function get_categoryname ($array_categories,$categories){
		$categories_name = array();

		foreach($array_categories as $id){
			if(in_array($id, array_column($categories,'cat_id'))){
				$index = array_search($id, array_column($categories,'cat_id'));
				array_push($categories_name,$categories[$index]->category_name);
			}
		}

		return implode(", ",$categories_name);
	}

    public function formatNumerictextFromcatalog($numberToFormat,$company){
        $formatNumeric = $company->format_id
            ? $this->OrdenModel->getDataFromApi("format_numeric/{$company->format_id}",[],false)
            : $this->OrdenModel->getDataFromApi("format_numeric/{$company->format_id}/default",[],false);
        return number_format($numberToFormat, $formatNumeric->digits,$formatNumeric->radixPoint,$formatNumeric->groupSeparator);
    }

	public function format_numerictext_fromcatalog($number_toformat,$company_id){
        $company = $this->OrdenModel->getDataFromApi('company/' . (int) $company_id, [], false);
        if (! is_object($company) || isset($company->error)) {
            $format_numeric = $this->OrdenModel->getDataFromApi('format_numeric/default', [], false);
        } else {
            $format_numeric = ! empty($company->format_id)
                ? $this->OrdenModel->getDataFromApi('format_numeric/' . (int) $company->format_id, [], false)
                : $this->OrdenModel->getDataFromApi('format_numeric/default', [], false);
        }
        if (! is_object($format_numeric) || ! isset($format_numeric->digits)) {
            return number_format((float) $number_toformat, 2, '.', ',');
        }

		return number_format($number_toformat, $format_numeric->digits, $format_numeric->radixPoint, $format_numeric->groupSeparator);
	}

	public function obtener_product_car($campo = "token_car" , $value, $formatNumeric = null){
        if ($campo === 'token_car' && is_string($value) && $value !== '') {
            $apiLines = $this->fetchCatalogPublicLines($value, $formatNumeric);
            if ($apiLines !== null) {
                return $apiLines;
            }
            log_message('error', 'CatalogoPublicModel::obtener_product_car - API catalog-public/lines sin respuesta');
        }

        return [];
	}

    /**
     * Asigna imagen_js en líneas de carrito (MinIO vía API catalog-public).
     *
     * @param array $carproducts Productos del carrito (por referencia)
     * @param int $company_id ID de la empresa (sin uso en MinIO; conservado por compatibilidad)
     * @param string $cart_token Token del carrito
     */
    public function enrich_carproducts_with_images(array &$carproducts, $company_id, $cart_token = '', $api_images_catalog_base = '')
    {
        if (empty($carproducts)) {
            return;
        }

        $token = (string) $cart_token;
        foreach ($carproducts as $carpro) {
            if (! empty($carpro->imagen_js)) {
                $carpro->imagen_js = catalog_public_image_url($carpro->imagen_js, $token);
            } elseif (! empty($carpro->image_url)) {
                $carpro->imagen_js = catalog_public_image_url($carpro->image_url, $token);
            } elseif (! empty($carpro->images)) {
                $carpro->imagen_js = catalog_public_image_url($carpro->images, $token);
            } else {
                $carpro->imagen_js = catalog_public_no_image_url();
            }
        }
    }

    /**
     * Calcula totales del carrito. $order_id_or_token puede ser:
     * - ID numérico de orden (order_id): se usa API order/{id}/total-details.
     * - Token del carrito de catálogo (token_kor): API catalog-public/totals.
     */
	public function calcular_total_ajax($order_id_or_token)
    {
        $is_order_id = is_numeric($order_id_or_token) && (int) $order_id_or_token > 0;
        if ($is_order_id) {
            try {
                $res = $this->OrdenModel->getDataFromApi("order/{$order_id_or_token}/total-details?formated", [], false);
                if (is_array($res) && isset($res['total_orden'])) {
                    return $res;
                }
                if (is_object($res) && isset($res->total_orden)) {
                    return (array) $res;
                }
                if (is_array($res) && isset($res['error'])) {
                    log_message('error', 'calcular_total_ajax API order/total-details error: ' . json_encode($res));
                }
            } catch (\Throwable $e) {
                log_message('error', 'calcular_total_ajax API falló: ' . $e->getMessage());
            }
        }
        // Token de catálogo (no order_id): API catalog-public/totals
        $apiTotals = $this->fetchCatalogPublicTotals((string) $order_id_or_token);
        if ($apiTotals !== null) {
            return $apiTotals;
        }

        log_message('error', 'CatalogoPublicModel::calcular_total_ajax - API catalog-public/totals sin respuesta');

        return $this->catalogPublicDefaultTotals();
    }

    /**
     * @return array<string, mixed>
     */
    protected function catalogPublicDefaultTotals(): array
    {
        return [
            'total_orden' => '0.00',
            'cantidad_productos' => 0,
            'sub_total' => '0.00',
            'discount_total' => '0.00',
            'success' => true,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    /**
     * La API puede devolver URLs absolutas con host interno (laravel.test); el navegador necesita BASE_URL.
     */
    protected function normalizeCatalogPublicImageUrl(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return $url;
        }
        $webBase = rtrim(base_url(), '/');
        $normalized = preg_replace('#^https?://laravel\.test#i', $webBase, $url);

        return is_string($normalized) ? $normalized : $url;
    }

    /**
     * @param array<string, mixed>|object $row
     * @return object
     */
    protected function normalizeCatalogPublicProductRow($row): object
    {
        $item = is_object($row) ? $row : (object) $row;
        if (! empty($item->imagen_js)) {
            $item->imagen_js = $this->normalizeCatalogPublicImageUrl((string) $item->imagen_js);
        }
        if (! empty($item->image_url)) {
            $item->image_url = $this->normalizeCatalogPublicImageUrl((string) $item->image_url);
        }

        return $item;
    }

    protected function fetchCatalogPublicTotals(string $token): ?array
    {
        if ($token === '') {
            return null;
        }
        try {
            $res = $this->getCatalogPublicFromApi('catalog-public/totals', ['token' => $token]);
            if (is_array($res) && ! isset($res['error']) && ! empty($res['success'])) {
                return $res;
            }
        } catch (\Throwable $e) {
            log_message('error', 'fetchCatalogPublicTotals: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * @return array<int, object>|null
     */
    protected function fetchCatalogPublicLines(string $token, $formatNumeric = null): ?array
    {
        if ($token === '') {
            return null;
        }
        try {
            $res = $this->getCatalogPublicFromApi('catalog-public/lines', ['token' => $token]);
            if (! is_array($res) || isset($res['error']) || ! isset($res['data']) || ! is_array($res['data'])) {
                return null;
            }
            $lines = [];
            foreach ($res['data'] as $row) {
                $item = is_array($row) ? (object) $row : $row;
                $item->salepriceDetail_Formatted = $this->OrdenModel->formatNumber($item->detail_sale_price ?? 0, $formatNumeric);
                $item->importeDetail_Formatted = $this->OrdenModel->formatNumber($item->importe ?? 0, $formatNumeric);
                if (! empty($item->image_url) && empty($item->imagen_js)) {
                    $item->imagen_js = $item->image_url;
                }
                $item = $this->normalizeCatalogPublicProductRow($item);
                if (! isset($item->precio_grupo)) {
                    $item->precio_grupo = $item->detail_sale_price ?? null;
                }
                if (is_object($formatNumeric) && isset($formatNumeric->radixPoint, $formatNumeric->groupSeparator)) {
                    $item->qtyFormatted = number_format((int) ($item->qty ?? 0), 0, $formatNumeric->radixPoint, $formatNumeric->groupSeparator);
                } else {
                    $item->qtyFormatted = number_format((int) ($item->qty ?? 0), 0, '.', ',');
                }
                $lines[] = $item;
            }

            return $lines;
        } catch (\Throwable $e) {
            log_message('error', 'fetchCatalogPublicLines: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * @param object|array<string, mixed> $request
     * @return array{products: list<object>, sections: list<object>}|null
     */
    protected function fetchCatalogPublicProductsPackFromApi(string $token, $request, $offset, $limit): ?array
    {
        $req = is_object($request) ? $request : (object) $request;
        $perPage = $limit ? (int) $limit : 28;
        $page = 1;
        if ($limit && $offset !== null) {
            $page = (int) floor(((int) $offset) / max(1, $perPage)) + 1;
        }
        $params = [
            'token' => $token,
            'page' => $page,
            'per_page' => $perPage,
            'grouped' => 1,
        ];
        if (! empty($req->category_id)) {
            $params['cat_id'] = $req->category_id;
            if ($perPage < 500) {
                $params['per_page'] = 2000;
                $params['page'] = 1;
            }
        } elseif (empty($req->search)) {
            // Vista ALL agrupada: más productos por página para ver taxonomía.
            $params['per_page'] = max($perPage, 100);
        }
        if (! empty($req->search)) {
            $params['search'] = $req->search;
        }
        if (isset($req->short) && $req->short !== null && $req->short !== '') {
            $params['short'] = $req->short;
        }
        try {
            $res = $this->getCatalogPublicFromApi('catalog-public/products', $params);
            if (! is_array($res) || isset($res['error']) || ! isset($res['data']) || ! is_array($res['data'])) {
                return null;
            }
            $products = array_map(static fn ($row) => is_object($row) ? $row : (object) $row, $res['data']);
            $sections = [];
            if (! empty($res['sections']) && is_array($res['sections'])) {
                $sections = array_map(static fn ($row) => is_object($row) ? $row : (object) $row, $res['sections']);
            }

            return ['products' => $products, 'sections' => $sections];
        } catch (\Throwable $e) {
            log_message('error', 'fetchCatalogPublicProductsPackFromApi: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * @param object|array<string, mixed> $request
     * @return array<int, object>|null
     */
    protected function fetchCatalogPublicProductsFromApi(string $token, $request, $offset, $limit): ?array
    {
        $pack = $this->fetchCatalogPublicProductsPackFromApi($token, $request, $offset, $limit);
        if ($pack === null) {
            return null;
        }

        return array_map(function ($row) {
            return $this->normalizeCatalogPublicProductRow($row);
        }, $pack['products']);
    }

    /**
     * @return array<int, object>|null
     */
    protected function fetchCatalogPublicCategoriesOrderFromApi(string $token): ?array
    {
        try {
            $res = $this->getCatalogPublicFromApi('catalog-public/categories-order', ['token' => $token]);
            if (! is_array($res) || isset($res['error']) || ! isset($res['data']) || ! is_array($res['data'])) {
                return null;
            }
            return array_map(static fn ($row) => is_object($row) ? $row : (object) $row, $res['data']);
        } catch (\Throwable $e) {
            log_message('error', 'fetchCatalogPublicCategoriesOrderFromApi: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * @return array<int, object>|null
     */
    protected function fetchCatalogPublicCategoriesListFromApi(string $token): ?array
    {
        try {
            $res = $this->getCatalogPublicFromApi('catalog-public/categories', ['token' => $token]);
            if (! is_array($res) || isset($res['error']) || ! isset($res['data']) || ! is_array($res['data'])) {
                return null;
            }
            return array_map(static fn ($row) => is_object($row) ? $row : (object) $row, $res['data']);
        } catch (\Throwable $e) {
            log_message('error', 'fetchCatalogPublicCategoriesListFromApi: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * @param object|array<string, mixed> $request
     */
    protected function fetchCatalogPublicProductCountFromApi(string $token, $request): ?int
    {
        $req = is_object($request) ? $request : (object) $request;
        $params = ['token' => $token];
        if (! empty($req->category_id)) {
            $params['cat_id'] = $req->category_id;
        }
        if (! empty($req->search)) {
            $params['search'] = $req->search;
        }
        if (isset($req->short) && $req->short !== null && $req->short !== '') {
            $params['short'] = $req->short;
        }
        try {
            $res = $this->getCatalogPublicFromApi('catalog-public/products/count', $params);
            if (is_array($res) && ! isset($res['error']) && isset($res['total'])) {
                return (int) $res['total'];
            }
        } catch (\Throwable $e) {
            log_message('error', 'fetchCatalogPublicProductCountFromApi: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>|null
     */
    protected function catalogPublicCartMutation(string $method, string $resource, string $token, array $body = []): ?array
    {
        if ($token === '') {
            return null;
        }
        try {
            $options = ['query' => ['token' => $token]];
            if ($body !== []) {
                $options['json'] = $body;
            }
            $res = $this->apiRequest('catalog-public/' . $resource, strtoupper($method), $options);
            if (is_array($res) && isset($res['error'], $res['message']) && is_array($res['message']) && array_key_exists('success', $res['message'])) {
                return $res['message'];
            }
            if (! is_array($res) || isset($res['error'])) {
                return null;
            }
            if (isset($res['valid']) && $res['valid'] === false) {
                return null;
            }

            return $res;
        } catch (\Throwable $e) {
            log_message('error', 'catalogPublicCartMutation: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function catalogPublicSetLineQty(string $token, int $productId, $qty, ?string $comment = null): ?array
    {
        return $this->catalogPublicCartMutation('PUT', 'lines', $token, array_filter([
            'product_id' => $productId,
            'qty' => $qty !== null ? (int) $qty : null,
            'comment' => $comment,
        ], static fn ($v) => $v !== null));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function catalogPublicAddLineQty(string $token, int $productId): ?array
    {
        return $this->catalogPublicCartMutation('POST', 'lines/add', $token, [
            'product_id' => $productId,
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function catalogPublicRemoveLine(string $token, int $productId): ?array
    {
        return $this->catalogPublicCartMutation('DELETE', 'lines/' . $productId, $token);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function catalogPublicClearCart(string $token): ?array
    {
        return $this->catalogPublicCartMutation('DELETE', 'lines', $token);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function catalogPublicProductDetail(string $token, int $productId): ?array
    {
        if ($token === '' || $productId < 1) {
            return null;
        }
        try {
            $res = $this->getCatalogPublicFromApi('catalog-public/products/' . $productId, ['token' => $token]);
            if (is_array($res) && isset($res['error'], $res['message']) && is_array($res['message']) && array_key_exists('success', $res['message'])) {
                return $res['message'];
            }
            if (! is_array($res) || isset($res['error']) || empty($res['success'])) {
                return null;
            }

            return $res;
        } catch (\Throwable $e) {
            log_message('error', 'catalogPublicProductDetail: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function catalogPublicOrders(string $token, int $page = 1, int $perPage = 10): ?array
    {
        if ($token === '') {
            return null;
        }
        try {
            $res = $this->getCatalogPublicFromApi('catalog-public/orders', [
                'token' => $token,
                'page' => max(1, $page),
                'per_page' => min(50, max(1, $perPage)),
            ]);
            if (is_array($res) && isset($res['error'], $res['message']) && is_array($res['message']) && array_key_exists('success', $res['message'])) {
                return $res['message'];
            }
            if (! is_array($res) || isset($res['error']) || empty($res['success'])) {
                return null;
            }

            return $res;
        } catch (\Throwable $e) {
            log_message('error', 'catalogPublicOrders: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function catalogPublicOrderDetail(string $token, int $orderId): ?array
    {
        if ($token === '' || $orderId < 1) {
            return null;
        }
        try {
            $res = $this->getCatalogPublicFromApi('catalog-public/orders/' . $orderId, ['token' => $token]);
            if (is_array($res) && isset($res['error'], $res['message']) && is_array($res['message']) && array_key_exists('success', $res['message'])) {
                return $res['message'];
            }
            if (! is_array($res) || isset($res['error']) || empty($res['success'])) {
                return null;
            }

            return $res;
        } catch (\Throwable $e) {
            log_message('error', 'catalogPublicOrderDetail: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function catalogPublicResolveMultiCart(string $token): ?array
    {
        if ($token === '') {
            return null;
        }
        try {
            $res = $this->getCatalogPublicFromApi('catalog-public/multi-cart/active-token', ['token' => $token]);
            if (is_array($res) && isset($res['error'], $res['message']) && is_array($res['message']) && array_key_exists('success', $res['message'])) {
                return $res['message'];
            }
            if (! is_array($res) || isset($res['error']) || empty($res['success'])) {
                return null;
            }

            return $res;
        } catch (\Throwable $e) {
            log_message('error', 'catalogPublicResolveMultiCart: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * @param array<string, mixed> $body
     * @return array<string, mixed>|null
     */
    public function catalogPublicPatchSession(string $token, array $body): ?array
    {
        if ($token === '') {
            return null;
        }
        try {
            $res = $this->apiRequest('catalog-public/session', 'PATCH', [
                'query' => ['token' => $token],
                'json' => $body,
            ]);
            if (is_array($res) && isset($res['error'], $res['message']) && is_array($res['message']) && isset($res['message']['valid'])) {
                return $res['message'];
            }
            if (! is_array($res) || isset($res['error'])) {
                return null;
            }

            return $res;
        } catch (\Throwable $e) {
            log_message('error', 'catalogPublicPatchSession: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>|null
     */
    public function catalogPublicPlaceOrder(string $token, array $payload): ?array
    {
        if ($token === '') {
            return null;
        }
        try {
            $res = $this->apiRequest('catalog-public/place-order', 'POST', [
                'query' => ['token' => $token],
                'json' => $payload,
            ]);
            if (! is_array($res)) {
                return null;
            }
            if (isset($res['success'])) {
                return $res;
            }
            if (isset($res['error'])) {
                $messagePayload = $res['message'] ?? null;
                if (is_array($messagePayload) && array_key_exists('success', $messagePayload)) {
                    return $messagePayload;
                }
                if (is_array($messagePayload) && isset($messagePayload['message'])) {
                    return [
                        'success' => false,
                        'error' => $messagePayload['error'] ?? 'api_error',
                        'message' => $messagePayload['message'],
                    ];
                }

                return [
                    'success' => false,
                    'error' => 'api_error',
                    'message' => is_string($messagePayload) ? $messagePayload : 'Error al crear la orden.',
                ];
            }

            return $res;
        } catch (\Throwable $e) {
            log_message('error', 'catalogPublicPlaceOrder: ' . $e->getMessage());
        }

        return null;
    }

    public function newAddress($request, $language_id) {
        $error = $this->langmodel->get_langsinceLogin('error_address', $language_id);
        if (is_object($error)) {
            $error = (array) $error;
        }
        if (! is_array($error)) {
            $error = [];
        }
        $request = $this->Helper->cast($request);

        $data = [];
        $errores = [];
        $wantsNewAddress = $this->isTruthyNewAddressFlag($request->new_address ?? null);

        if ($wantsNewAddress) {
            $delivery = trim((string) ($request->delivery_address ?? ''));
            $zip = trim((string) ($request->zip_code ?? ''));
            $city = trim((string) ($request->city ?? $request->city_id ?? ''));
            $state = trim((string) ($request->state ?? $request->state_id ?? ''));

            if ($delivery === '') $errores[] = ["input"=> "delivery_address","msg_error"=>$error["address"] ?? ''];
            if ($zip === '') $errores[] = ["input"=> "zip_code","msg_error"=>$error["zip_code"] ?? ''];
            if ($city === '') $errores[] = ["input"=> "city","msg_error"=>$error["city"] ?? ''];
            if ($state === '') $errores[] = ["input"=> "state","msg_error"=>$error["state"] ?? ''];
            $data['new_address'] = true;
        }
        if (count($errores) > 0) {
            return $this->Helper->cast([
                "errores" => $errores,
                "msg" => $error['error_datos'] ?? '',
                "is_valid"=> false
            ]);
        }
        $data["delivery_address"] = trim((string) ($request->delivery_address ?? ''));
        $data["zip_code"] = trim((string) ($request->zip_code ?? ''));
        $data["city"] = trim((string) ($request->city ?? $request->city_id ?? ''));
        $data["state"] = trim((string) ($request->state ?? $request->state_id ?? ''));
        $data["shipping_notes"] = trim((string) ($request->shipping_notes ?? ''));
        $data["comment_order"] = trim((string) ($request->comment_order ?? ''));

        if (isset($request->country) && $request->country !== '' && $request->country !== null) {
            $data['country'] = $request->country;
        }
        if (isset($request->zone) && $request->zone !== '' && $request->zone !== null) {
            $data['zone'] = $request->zone;
        }
        return $this->Helper->cast([
            "is_valid" => true,
            "data" => json_encode($data),
        ]);
    }

    /**
     * @param mixed $value
     */
    private function isTruthyNewAddressFlag($value): bool
    {
        if ($value === true || $value === 1 || $value === '1') {
            return true;
        }
        if (is_string($value)) {
            $normalized = strtolower(trim($value));

            return in_array($normalized, ['true', 'yes', 'on'], true);
        }

        return false;
    }

    public function obtenerOrderDetail($sell_with_inventory,$carproducts, $lang_order)
    {
        $errores = [];
        $items = [];

        foreach ($carproducts as $product) {
            $precio_obtenido = $product->precio_grupo;
            $erroresStock = $this->obtenerErroresStock($product, $lang_order);
            if (count($erroresStock)) array_push($errores, $erroresStock);

            $erroresInventario = $this->obtenerErroresInventario($sell_with_inventory, $product, $lang_order);
            if (count($erroresInventario)) array_push($errores, $erroresInventario);

            $itemDetail = array(
                'id_producto' => isset($product->pro_id) ? (int) $product->pro_id : (int) $product->product_id,
                'qty' => $product->qty,
                'sale_price' => $precio_obtenido,
                'comments' => ($product->comment == null) ? '' : $product->comment,
            );
            $items[] = $itemDetail;
        }

        if ( count($errores) > 0 ) {
            return $this->Helper->cast([
                "is_valid" => false,
                "errores" => $errores
            ]);
        }

       return $this->Helper->cast([
        "is_valid" => true,
        "data" => $items
    ]);

    }

    public function obtenerErroresInventario($sell_with_inventory, $product, $lang_order){
        if (!$sell_with_inventory) {
            return [];
        }
        $qty = (int) $product->qty;
        $stock = isset($product->stock) ? (int) $product->stock : 0;
        if ($qty > $stock) {
            $titulo_multiplo = "Stock";
            $mensaje_multiplo = $lang_order['product'] . " " . $product->sku . " " . $lang_order['no_stock'];
            $name = 'qty-' . (isset($product->product_id) ? $product->product_id : $product->pro_id);
            $msg = $titulo_multiplo;
            $content = $mensaje_multiplo;
            return ['name_input' => $name, 'tittle' => $msg, 'content' => $content];
        }
        return [];
    }

    public function obtenerSource($tipoCarrito){

        switch ($tipoCarrito){
            case self::CARRITO_COMPANY:
                return self::SOURCE_MANUAL;
            case self::CARRITO_PORTAL_SELLER:
                return self::SOURCE_CATALOGO;
            case self::CARRITO_TO_CUSTOMER:
                return self::SOURCE_CATALOGO;
        }

    }

    public function obtenerErroresStock($product, $lang_order){

        if ($product->notify_minimum_stock == 1 && $product->minimum_stock > 0) {
            $qty = $product->qty;
            if ($this->Commonmodel->validar_multiplo($qty, $product->minimum_stock) == false) {
                $titulo_multiplo = $lang_order['titulo_cantidad_multiplo'];
                $mensaje_multiplo = sprintf($lang_order['alert_cantidad_multiplo'], intval($product->minimum_stock), intval($product->minimum_stock));
                $name = 'qty-' . $product->product_id;
                $msg = $titulo_multiplo;
                $content = $mensaje_multiplo;
                return ['name_input' => $name, 'tittle' => $msg, 'content' => $content];
            }
        }
        return [];
    }
}

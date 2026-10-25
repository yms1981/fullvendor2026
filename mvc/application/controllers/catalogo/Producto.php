<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Controlador del catálogo público (antiguo backend, adaptado).
 *
 * Requisitos para que funcione:
 * - API disponible: rutas catalog-public (sesión, listado, carrito, pedido, historial).
 * - Ruta: catalogo/producto?token=XXX (token del carrito de catálogo).
 *
 * @property CatalogoPublicModel $CatalogoPublicModel
 * @property Commonmodel $Commonmodel
 */

class Producto extends My_Controller
{
    public ?int $company;
    public ?int $langId;
    public $company_info = array();
    public $format_numeric = array();
    public $lang_order = array();

    /** @var array<string, mixed>|null */
    protected $catalogPublicSession = null;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('CatalogoPublicModel');

        $token_kor = $this->getIncomingCatalogToken();
        $token_kor = ($token_kor !== null && $token_kor !== '') ? $token_kor : '';
        $car_detail = null;
        $status_cart = null;
        if ($token_kor !== '') {
            $session = $this->fetchCatalogPublicSession($token_kor, false);
            if ($session !== null && ! empty($session['valid'])) {
                $this->catalogPublicSession = $session;
                $this->applyCatalogPublicSession($session);
                $car_detail = $this->cartDetailFromApiSession($session);
                $statusCartMeta = $session['status_cart'] ?? null;
                if (is_array($statusCartMeta)) {
                    $status_cart = (object) $statusCartMeta;
                }
            }
        }
        if ($car_detail === null) {
            $this->company_info = null;
        }

        if ($this->company_info && empty($this->lang_order)) {
            $this->lang_order = $this->catalogLang('cmp-orders', $this->company_info);
        }

        if ($car_detail) {
            switch ($car_detail->type_cart) {
                case 'to_customer':

                    // Descartar payloads JSON del panel company (p. ej. "Email enviado...")
                    // para que no aparezcan al abrir el enlace del email.
                    // No re-setear flash: si se vuelve a set_flashdata aquí, el mensaje
                    // (p. ej. "Orden enviada") reaparece en cada visita al catálogo.
                    $pendingMsg = $this->session->flashdata('msg');
                    $pendingSuccess = $this->session->flashdata('success');
                    $isCompanyJsonFlash = false;
                    if (is_string($pendingMsg) && $pendingMsg !== '') {
                        $decodedFlash = json_decode($pendingMsg, true);
                        $isCompanyJsonFlash = json_last_error() === JSON_ERROR_NONE
                            && is_array($decodedFlash)
                            && isset($decodedFlash[0], $decodedFlash[1]);
                    }
                    if ($isCompanyJsonFlash) {
                        $this->session->unset_userdata('msg');
                        $this->session->unset_userdata('success');
                    }
                    // Mensajes legítimos del catálogo: quedan disponibles para el footer
                    // en esta petición y CI los limpia en la siguiente.

                    if ($status_cart && (int) ($status_cart->multiple_cart ?? 0) === 1) {
                        $resolved = $this->CatalogoPublicModel->catalogPublicResolveMultiCart($token_kor);
                        if (
                            is_array($resolved)
                            && ! empty($resolved['success'])
                            && ! empty($resolved['redirect'])
                            && ! empty($resolved['token'])
                            && (string) $resolved['token'] !== $token_kor
                        ) {
                            redirect($this->toCustomerBrowseUrlWithToken((string) $resolved['token']), 'refresh');
                        }
                    }
                    break;
                case 'to_portal_seller':
                    $fecha_actual = strtotime(date("Y-m-d H:i:s"), time());
                    $fecha_entrada = strtotime($car_detail->expired_date);

                    if ($fecha_actual > $fecha_entrada) {
                        redirect(seller_url('catalogo'), 'refresh');
                        return;
                    }
                    $this->sellerlogin();
                    break;
                case 'to_company':
                    $this->comploggedIn();
                    break;
            }
        }
    }

    /**
     * Exige sesión de vendedor (portal seller). Redirige al login de vendedor si no hay sesión.
     */
    protected function sellerlogin(): void
    {
        if (!$this->session->has_userdata('vendorId')) {
            redirect(seller_url(), 'refresh');
        }
    }

    /**
     * Exige sesión de usuario de empresa (portal company). Redirige al login de empresa si no hay sesión.
     */
    protected function comploggedIn(): void
    {
        if (!$this->loggedIn()) {
            $redirectto = urlencode(current_url());
            redirect($this->config->item('base_path') . 'login?redirectto=' . $redirectto, 'refresh');
        }
    }

    public function get_products()
    {

        $token_kor = $this->input->get('token');
        $car = $this->validar_token_entrante($token_kor);

        if ($car['success'] == false) return show_404();

        $car_detail = $this->resolveCatalogCartDetail($token_kor, $car);
        if ($car_detail === null) {
            return show_404();
        }

        return $this->Helper->response_json(

            $this->CatalogoPublicModel->get_products([
                "company_id" => $car_detail->company_id,
                "catalog_id" => $car_detail->catalog_id,
                "cart_token" => $car_detail->token_kor,
                "search" => $this->input->post('search_value'),
                "category_id" => null,
                "short" => null,
            ])

        );
    }

    public function index()
    {
        $short = $this->input->get('short');
        $token_kor = $this->getIncomingCatalogToken();
        $token_kor = ($token_kor !== null && $token_kor !== '') ? $token_kor : '';
        $car = $this->validar_token_entrante($token_kor);

        if ($car['success'] == false) {
            $this->mostrar_vistas_404($car);
            return;
        }

        $session = $this->fetchCatalogPublicSession($token_kor, true);
        if ($session === null || empty($session['valid'])) {
            $session = $car['session'] ?? null;
        }
        if ($session === null || empty($session['valid'])) {
            $this->mostrar_vistas_404(['success' => false, 'reason' => 'not_found', 'requested_url' => current_url()]);
            return;
        }

        $this->catalogPublicSession = $session;
        $this->applyCatalogPublicSession($session);
        $car_detail = $this->cartDetailFromApiSession($session);
        $viewContext = $this->buildCatalogViewContextFromSession($session);

        $page = 1;
        $get_page = $this->input->get('page');

        if ($get_page != null) {
            $page = $this->input->get('page');
        }

        $limit = 28;
        $offset = ($page - 1) * $limit;
        $categorias = $this->obtener_categoriasCatalog($car_detail->company_id, $car_detail->catalog_id, $car_detail->token_kor);
        $info['categories_order'] = $this->CatalogoPublicModel->get_ordercategories(
            $car_detail->company_id,
            $car_detail->catalog_id,
            null,
            null,
            $car_detail->token_kor
        );
        $info['format_numeric'] = $this->format_numeric;
        $category_id = $this->input->get("cat_id") == null ? null : $this->input->get("cat_id");
        $search = $this->input->get("search") == null ? null : $this->input->get("search");
        $request["search"] = $search;
        $request["category_id"] = $category_id;
        $request["company_id"] = $car_detail->company_id;
        $request["catalog_id"] = $car_detail->catalog_id;
        $request["short"] = $short;
        $request["cart_token"] = $car_detail->token_kor;
        $info = array_merge($info, $viewContext);
        $customer = $info['info_customer'];

        // Sin búsqueda: listado agrupado por categoría → grupo → subgrupo.
        $useGrouped = ($search === null || $search === '');
        if ($useGrouped && $category_id !== null && $category_id !== '') {
            $limit = 2000;
            $offset = 0;
        } elseif ($useGrouped) {
            $limit = 100;
            $offset = ($page - 1) * $limit;
        }

        if ($useGrouped) {
            $pack = $this->CatalogoPublicModel->products_catalog_grouped(
                $request,
                $offset,
                $limit,
                $customer,
                $info['format_numeric'],
                $car_detail->token_kor
            );
            $productos_catalogo = $pack['products'];
            $info['product_sections'] = $pack['sections'];
        } else {
            $productos_catalogo = $this->CatalogoPublicModel->products_catalog(
                $request,
                $offset,
                $limit,
                $customer,
                $info['format_numeric'],
                $car_detail->token_kor
            );
            $info['product_sections'] = [];
        }
        $v_imagen = $this->version();
        $info['products'] = $productos_catalogo;
        // Log diagnóstico catalog_body: resumen de imágenes para la lista
        $con_imagen = 0;
        foreach ($productos_catalogo as $p) {
            if ((isset($p->implode_img) && $p->implode_img !== null && $p->implode_img !== '') || (isset($p->images) && $p->images !== null && $p->images !== '')) {
                $con_imagen++;
            }
        }
        log_message('info', 'catalog_body lista: company_id=' . ($car_detail->company_id ?? '') . ' productos=' . count($productos_catalogo) . ' con_datos_imagen=' . $con_imagen . ' sin_datos_imagen=' . (count($productos_catalogo) - $con_imagen) . ' (ver CatalogoPublicModel::products_catalog en log si sin_datos_imagen > 0)');
        $info['categorias'] = $categorias;
        $info['version'] = $v_imagen;
        $info['limit'] = min(count($productos_catalogo), $limit);
        $info['grouped_catalog'] = $useGrouped;
        $comp = $info['info_company'];

        $info['lang_catalogo'] = $this->catalogLang('cmp-catalog', $comp);
        $info['lang_prods'] = $this->catalogLang('cmp-products', $comp);

        // Base URL con token y cat_id para que al paginar se mantenga la categoría seleccionada
        $base_url = base_url('catalogo/producto') . '?token=' . urlencode($car_detail->token_kor);
        if ($category_id !== null && $category_id !== '') {
            $base_url .= '&cat_id=' . (int) $category_id;
        }
        if ($short !== null && $short !== '') {
            $base_url .= '&short=' . (int) $short;
        }
        $qty_total = $this->CatalogoPublicModel->quantity_products($request);
        $info['total_filtro'] = $qty_total;

        // PAGINACION (con categoría completa agrupada no paginar productos)
        if ($useGrouped && $category_id !== null && $category_id !== '') {
            $info['paginate'] = '';
            $info['limit'] = count($productos_catalogo);
        } else {
            $config = $this->obtener_configuracion_paginacion($base_url, $qty_total, $limit, $comp->language_id, $comp->company_id, $info['lang_prods'] ?? null);
            $this->pagination->initialize($config);
            $info['paginate'] = $this->pagination->create_links();
        }
        $cartLines = $this->CatalogoPublicModel->obtener_product_car('token_car', $token_kor, $this->format_numeric);
        $info['product_detail'] = $this->mapCartLinesToProductDetail($cartLines);

        $info["js_vue"] = [
            "stores/stores_vue.js",
            "stores/search_product.js",
        ];

        // to_customer, to_portal_seller, to_company
        switch ($car_detail->type_cart) {
            case 'to_customer':
                $info['show_stock'] = false;
                $info['js'] = ['enviroment_catalogoweb.js', 'get_languagefromcart.js', 'shoplist.js', 'cart-sidebar.js', 'app.js'];
                break;
            case 'to_portal_seller':
                $info['js'] = ['enviroment.js', 'get_language.js', 'shoplist.js', 'cart-sidebar.js', 'createneworder.js', 'app.js'];
                $info['show_stock'] = true;
                break;
            case 'to_company':
                $info['js'] = ['enviroment_portal.js', 'get_language.js', 'shoplist.js', 'cart-sidebar.js', 'app.js'];
                $info['show_stock'] = false;
                break;
        }

        $info['totales'] = $this->CatalogoPublicModel->calcular_total_ajax($token_kor);
        $info['lang_footer'] = $this->catalogLang('cmp-footer', $comp);
        $info['catalog_uri_index'] = $this->catalogPublicUriIndex();
        $info['catalog_uri_carts'] = $this->catalogPublicUriCarts();
        $info = array_merge($info, $this->catalogPortalNavInfo());
        $this->load->view('catalogo/catalog_header', $info);
        $this->load->view('catalogo/catalog_body', $info);
        $this->load->view('catalogo/catalog_footer', $info);
    }

    public function version()
    {
        $ahora = DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));
        $v_imagen = $ahora->format("YmdHisu");
        return $v_imagen;
    }

    public function validar_token_entrante($token_kor)
    {
        $requestedUrl = catalog_public_browse_url((string) ($token_kor ?? ''));
        $session = $this->fetchCatalogPublicSession($token_kor, false);
        if ($session === null) {
            return ['success' => false, 'reason' => 'not_found', 'requested_url' => $requestedUrl];
        }
        if (! empty($session['valid'])) {
            return ['success' => true, 'session' => $session];
        }
        $companyId = (int) ($session['company']['company_id'] ?? 0);
        $languageId = (int) ($session['company']['language_id'] ?? 0);
        $lang_catalog = $companyId > 0
            ? $this->langmodel->get_lang('cmp-catalog', $companyId, $languageId > 0 ? $languageId : null)
            : [];
        $company = $this->companyObjectFromSession($session['company'] ?? null);
        $reason = (string) ($session['reason'] ?? 'not_found');
        if ($reason === 'expired') {
            return [
                'success' => false,
                'reason' => 'expired',
                'lang_catalog' => $lang_catalog,
                'company' => $company,
                'requested_url' => $requestedUrl,
            ];
        }

        return ['success' => false, 'reason' => 'not_found', 'requested_url' => $requestedUrl];
    }

    public function mostrar_vistas_404($info)
    {
        if (!isset($info['requested_url'])) {
            $info['requested_url'] = current_url();
        }
        $this->load->view('catalogo/vistas_404/catalog_header', $info);
        $this->load->view('catalogo/vistas_404/catalog_404', $info);
        $this->load->view('catalogo/vistas_404/catalog_footer');
    }

    public function obtener_categoriasCatalog($companyId, $catlogId, $cartToken = null)
    {
        return $this->CatalogoPublicModel->obtener_categoriasCatalog($companyId, $catlogId, $cartToken);
    }

    public function obtener_ProductosPromociones($companyId, $catlogId)
    {
        return $this->CatalogoPublicModel->obtener_productos_promociones($companyId, $catlogId);
    }

    public function obtener_configuracion_paginacion($base_url, $total_rows, $show_per_page, $langId, $company_id, $lang_prods = null)
    {
        $info['langprods'] = ($lang_prods !== null && is_array($lang_prods)) ? $lang_prods : $this->langmodel->get_lang('cmp-products', $company_id, $langId);
        $config['base_url'] = $base_url;
        $config['num_links'] = 2;
        $config['uri_segment'] = 4;
        $config['total_rows'] = $total_rows;
        $config['per_page'] = $show_per_page;
        $config['full_tag_open'] = '<ul>';
        $config['full_tag_close'] = '</ul>';
        $config['num_tag_open'] = '<li>';
        $config['num_tag_close'] = '</li>';
        $config['first_link'] = $info['langprods']['pagination_first'];
        $config['first_tag_open'] = '<li class="next">';
        $config['first_tag_close'] = '</li>';
        $config['last_link'] = $info['langprods']['pagination_last'];
        $config['last_tag_open'] = '<li class="next">';
        $config['last_tag_close'] = '</li>';
        $config['prev_link'] = $info['langprods']['pagination_prev'];
        $config['prev_tag_open'] = '<li class="next">';
        $config['prev_tag_close'] = '</li>';
        $config['next_link'] = $info['langprods']['pagination_next'];
        $config['next_tag_open'] = '<li class="next">';
        $config['next_tag_close'] = '</li>';
        $config['cur_tag_open'] = '<li class="current"><a href="#">';
        $config['cur_tag_close'] = '</a></li>';
        $config['use_page_numbers'] = true;
        $config['use_page_numbers'] = true;
        $config['page_query_string'] = true;
        $config['query_string_segment'] = 'page';
        $config['reuse_query_string'] = true;

        return $config;
    }

    public function get_product()
    {
        $request = $this->input->post();
        if (! isset($request['token'], $request['product_id'])) {
            echo json_encode('error token');
            return;
        }

        $token = (string) $request['token'];
        $productId = (int) $request['product_id'];
        $validation = $this->validar_token_entrante($token);
        if ($validation['success'] == false) {
            echo json_encode('error token');
            return;
        }

        $session = $validation['session'] ?? null;
        if (! is_array($session) || empty($session['valid'])) {
            echo json_encode('error token');
            return;
        }

        $detail = $this->CatalogoPublicModel->catalogPublicProductDetail($token, $productId);
        if (! is_array($detail) || empty($detail['success']) || empty($detail['product'])) {
            echo json_encode('error product');
            return;
        }

        $this->applyCatalogPublicSession($session);
        $viewContext = $this->buildCatalogViewContextFromSession($session);
        $car = $this->cartDetailFromApiSession($session);

        $companyId = (int) ($car->company_id ?? 0);
        $info = array_merge($viewContext, [
            'lang_pop' => $this->catalogLang('popover-catalog', $viewContext['info_company'] ?? $companyId),
            'version' => DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''))->format('YmdHisu'),
            'cart_token' => $token,
            'producto_conformato' => (object) $detail['product'],
            'precio_obtenido' => (float) ($detail['price_for_customer'] ?? 0),
            'product_detail' => ! empty($detail['line']) ? (object) $detail['line'] : null,
            'imagenes' => array_map(static fn ($row) => is_object($row) ? $row : (object) $row, $detail['images'] ?? []),
        ]);

        if (! empty($detail['format_numeric']) && is_array($detail['format_numeric'])) {
            $info['format_numeric'] = (object) $detail['format_numeric'];
        }

        switch ($car->type_cart ?? '') {
            case 'to_customer':
            case 'to_company':
                $info['show_stock'] = false;
                break;
            case 'to_portal_seller':
                $info['show_stock'] = true;
                break;
            default:
                $info['show_stock'] = false;
                break;
        }

        $carousel = $this->load->view('catalogo/ajax_carousel', $info, true);
        $form_producto = $this->load->view('catalogo/ajax_viewproducto', $info, true);

        echo json_encode(['caraousel' => $carousel, 'producto_view' => $form_producto]);
    }


    public function obtener_imagenes($productId, $compId, $cartToken = null)
    {
        if (! empty($cartToken)) {
            $detail = $this->CatalogoPublicModel->catalogPublicProductDetail((string) $cartToken, (int) $productId);
            if (is_array($detail) && ! empty($detail['images'])) {
                return array_map(static fn ($row) => is_object($row) ? $row : (object) $row, $detail['images']);
            }
        }

        return [];
    }

    /**
     * Proxy de imagen de catálogo (MinIO vía API). Requiere token de carrito válido.
     * URL: catalogo/producto/serveImage/{imageId}?token={cart_token}
     */
    public function serveImage($imageId = null)
    {
        $token = $this->input->get('token');
        if (empty($token) || empty($imageId) || $imageId === 'index') {
            $this->outputNoImage();
            return;
        }
        $imageId = rawurldecode((string) $imageId);
        if (preg_match('/\.(text\/plain|text%2fplain)$/i', $imageId)) {
            $imageId = preg_replace('/\.(text\/plain|text%2fplain)$/i', '', $imageId);
        }
        $session = $this->fetchCatalogPublicSession((string) $token, false);
        if ($session === null || empty($session['valid'])) {
            $this->outputNoImage();
            return;
        }

        $catalogImageUrl = catalog_public_image_url($imageId, (string) $token);
        try {
            $response = $this->getHttpClient()->request('GET', $catalogImageUrl, [
                'timeout' => 15,
                'http_errors' => false,
            ]);
            if ($response->getStatusCode() === 200 && $response->getBody()->getSize() > 0) {
                header('Content-Type: ' . ($response->getHeaderLine('Content-Type') ?: 'image/webp'));
                header('Cache-Control: private, max-age=3600');
                echo $response->getBody()->getContents();
                return;
            }
        } catch (\Throwable $e) {
            log_message('debug', 'serveImage catalog MinIO: ' . $e->getMessage());
        }
        $this->outputNoImage();
    }

    private function outputNoImage()
    {
        try {
            $response = $this->getHttpClient()->request('GET', catalog_public_no_image_url(), [
                'timeout' => 10,
                'http_errors' => false,
            ]);
            if ($response->getStatusCode() === 200 && $response->getBody()->getSize() > 0) {
                header('Content-Type: ' . ($response->getHeaderLine('Content-Type') ?: 'image/png'));
                header('Cache-Control: private, max-age=3600');
                echo $response->getBody()->getContents();
                return;
            }
        } catch (\Throwable $e) {
            log_message('debug', 'outputNoImage: ' . $e->getMessage());
        }
        header('HTTP/1.1 404 Not Found');
        header('Content-Type: image/png');
    }

    public function buscarCustomer($customer_id, $id_company = null)
    {
        if (is_array($this->catalogPublicSession) && ! empty($this->catalogPublicSession['customer'])) {
            $customer = $this->catalogPublicSession['customer'];
            if ((int) ($customer['customer_id'] ?? 0) === (int) $customer_id) {
                return $this->clientePrecioFromSessionCustomer($customer);
            }
        }

        $session = $this->catalogPublicSession;
        if (! is_array($session) && $this->getIncomingCatalogToken()) {
            $session = $this->fetchCatalogPublicSession((string) $this->getIncomingCatalogToken(), false);
        }
        if (is_array($session) && ! empty($session['customer']) && (int) ($session['customer']['customer_id'] ?? 0) === (int) $customer_id) {
            return $this->clientePrecioFromSessionCustomer($session['customer']);
        }

        return $this->Helper->cast([
            'business_name' => '',
            'group_name' => '',
            'percentage_on_price' => '',
            'percent_price_amount' => 0,
        ]);
    }

    // change status cart (vía API; la mutación de líneas ya sincroniza status en el backend)
    public function change_status_abierto($token)
    {
        $car = $this->validar_token_entrante($token);
        if (empty($car['success']) || empty($car['session']['cart']) || ! is_array($car['session']['cart'])) {
            return;
        }

        $cartMeta = $car['session']['cart'];
        $statusCart = $cartMeta['status_cart'] ?? null;
        $newStatus = 2;

        if ($statusCart !== null && (int) $statusCart !== 4) {
            $totals = $this->CatalogoPublicModel->calcular_total_ajax($token);
            $lineCount = (int) ($totals['cantidad_productos'] ?? 0);
            $newStatus = $lineCount > 0 ? 3 : 2;
        }

        $this->CatalogoPublicModel->catalogPublicPatchSession($token, ['status_cart' => $newStatus]);
    }

    /**
     * @return array{valid: bool, car?: array<string, mixed>, session?: array<string, mixed>, lang_order?: array<string, string>}
     */
    protected function catalogCartMutationContext(string $token): array
    {
        $car = $this->validar_token_entrante($token);
        if (empty($car['success'])) {
            return ['valid' => false, 'car' => $car];
        }

        $session = is_array($car['session'] ?? null) ? $car['session'] : [];
        $companyId = (int) ($session['company']['company_id'] ?? 0);
        $languageId = (int) ($session['company']['language_id'] ?? 0);
        $lang_order = $companyId > 0
            ? $this->langmodel->get_lang('cmp-orders', $companyId, $languageId > 0 ? $languageId : null)
            : (! empty($this->lang_order) ? $this->lang_order : []);

        return [
            'valid' => true,
            'car' => $car,
            'session' => $session,
            'lang_order' => $lang_order,
        ];
    }

    protected function jsonCatalogCartTokenInvalid(): void
    {
        $this->output->set_content_type('application/json')->set_output(json_encode([
            'success' => false,
            'mesagge' => 'Invalid cart token',
        ]));
    }

    /**
     * @param array<string, mixed> $car
     */
    protected function jsonCatalogCartExpired(array $car): void
    {
        $msg = isset($car['lang_catalog']['catalog_expired'])
            ? $car['lang_catalog']['catalog_expired']
            : 'The requested address does not exist';
        echo json_encode(['success' => false, 'mesagge' => $msg]);
    }

    /**
     * @param array<string, string> $lang_order
     */
    protected function jsonCatalogCartApiFailure(array $lang_order): void
    {
        $msg = ! empty($lang_order['error_generic'])
            ? $lang_order['error_generic']
            : (! empty($lang_order['complete_qty']) ? $lang_order['complete_qty'] : 'Error');
        echo json_encode(['success' => false, 'mesagge' => $msg]);
    }

    /**
     * @param array<string, mixed>|null $apiResult
     * @param array<string, string> $lang_order
     */
    protected function respondCatalogSetLineQtyResult(?array $apiResult, array $lang_order, string $origen): bool
    {
        if (! is_array($apiResult)) {
            $this->jsonCatalogCartApiFailure($lang_order);

            return true;
        }

        if (empty($apiResult['success'])) {
            if (($apiResult['error'] ?? '') === 'quantity_not_multiple') {
                $minStock = (int) ($apiResult['minimum_stock'] ?? 1);
                echo json_encode([
                    'success' => false,
                    'mesagge' => sprintf($lang_order['alert_cantidad_multiplo'], $minStock, $minStock),
                ]);

                return true;
            }
            if (($apiResult['error'] ?? '') === 'quantity_required') {
                echo json_encode(['success' => false, 'mesagge' => $lang_order['complete_qty']]);

                return true;
            }
            $this->jsonCatalogCartApiFailure($lang_order);

            return true;
        }

        $msg_succes = null;
        if (! empty($apiResult['deleted'])) {
            $msg_succes = $lang_order['producto_eliminado'];
        } elseif ($origen === 'list') {
            $msg_succes = $lang_order['producto_agregado'];
        } elseif ($origen === 'order') {
            $msg_succes = $lang_order['producto_modificado'];
        }

        echo json_encode([
            'success' => true,
            'mesagge' => $msg_succes,
            'detalle' => $apiResult['detalle'] ?? $apiResult['totals'] ?? [],
            'cantidad_actual' => $apiResult['cantidad_actual'] ?? 0,
            'cantidad_actual_formatted' => $apiResult['cantidad_actual_formatted'] ?? '0',
            'importe_total' => $apiResult['importe_total'] ?? '0.00',
            'comment' => $apiResult['comment'] ?? '',
        ]);

        return true;
    }


    // guardar desde tabla


    public function saveqty()
    {
        $request = $this->input->post();
        $token = isset($request['token']) ? (string) $request['token'] : '';
        $ord_qty = array_key_exists('qty', $request) ? $request['qty'] : null;
        $product_id = isset($request['product_id']) ? (int) $request['product_id'] : 0;
        $origen = $request['origen'] ?? '';

        if ($token === '' || $product_id < 1) {
            $this->jsonCatalogCartTokenInvalid();

            return;
        }

        $ctx = $this->catalogCartMutationContext($token);
        if (empty($ctx['valid'])) {
            $this->jsonCatalogCartExpired($ctx['car'] ?? []);

            return;
        }

        $lang_order = $ctx['lang_order'] ?? [];

        if ($ord_qty === null || $ord_qty === '') {
            $this->CatalogoPublicModel->catalogPublicSetLineQty(
                $token,
                $product_id,
                null,
                isset($request['comment']) ? (string) $request['comment'] : null
            );
            echo json_encode(['success' => false, 'mesagge' => $lang_order['complete_qty'] ?? '']);

            return;
        }

        $apiResult = $this->CatalogoPublicModel->catalogPublicSetLineQty(
            $token,
            $product_id,
            (int) $ord_qty,
            isset($request['comment']) ? (string) $request['comment'] : null
        );
        $this->respondCatalogSetLineQtyResult($apiResult, $lang_order, (string) $origen);
    }

    /**
     * Agregar una unidad al carrito por GET (quick add). Parámetros: token (query), id en URL.
     */
    public function quickAddProduct($product_id)
    {
        $token = $this->input->get('token');
        if (empty($token)) {
            echo json_encode(['success' => false, 'mesagge' => 'Token requerido']);
            return;
        }
        $_POST['token'] = $token;
        $_POST['id'] = $product_id;
        $this->add_qty();
    }

    public function add_qty()
    {
        $request = $this->input->post();
        $token = isset($request['token']) ? (string) $request['token'] : (string) $this->input->get('token');
        $product_id = isset($request['id']) ? (int) $request['id'] : 0;

        if ($token === '' || $product_id < 1) {
            $this->jsonCatalogCartTokenInvalid();

            return;
        }

        $ctx = $this->catalogCartMutationContext($token);
        if (empty($ctx['valid'])) {
            $this->jsonCatalogCartExpired($ctx['car'] ?? []);

            return;
        }

        $lang_order = $ctx['lang_order'] ?? [];
        $apiResult = $this->CatalogoPublicModel->catalogPublicAddLineQty($token, $product_id);
        if (is_array($apiResult) && ! empty($apiResult['success'])) {
            $msg_succes = $lang_order['producto_agregado'] ?? 'Product Added!';
            echo json_encode([
                'success' => true,
                'mesagge' => $msg_succes,
                'detalle' => $apiResult['detalle'] ?? $apiResult['totals'] ?? [],
                'cantidad_actual' => $apiResult['cantidad_actual'] ?? ($apiResult['line']['qty'] ?? 0),
            ]);

            return;
        }

        $this->jsonCatalogCartApiFailure($lang_order);
    }

    /**
     * JSON para el sidebar lateral de productos agregados (mini cart).
     */
    public function mini_cart()
    {
        $token_kor = $this->getIncomingCatalogToken();
        $token_kor = ($token_kor !== null && $token_kor !== '') ? $token_kor : '';
        if ($token_kor === '') {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'items' => [], 'detalle' => []]));

            return;
        }

        $car = $this->validar_token_entrante($token_kor);
        if (empty($car['success'])) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'items' => [], 'detalle' => []]));

            return;
        }

        $session = $car['session'] ?? $this->fetchCatalogPublicSession($token_kor, false);
        if ($session === null || empty($session['valid'])) {
            $this->output
                ->set_content_type('application/json')
                ->set_output(json_encode(['success' => false, 'items' => [], 'detalle' => []]));

            return;
        }

        $this->catalogPublicSession = $session;
        $this->applyCatalogPublicSession($session);
        $car_detail = $this->cartDetailFromApiSession($session);
        $items = $this->CatalogoPublicModel->obtener_product_car('token_car', $token_kor, $this->format_numeric);
        if (! is_array($items)) {
            $items = [];
        }
        $this->CatalogoPublicModel->enrich_carproducts_with_images(
            $items,
            (int) ($car_detail->company_id ?? 0),
            (string) $token_kor
        );

        $payloadItems = [];
        foreach ($items as $item) {
            $proId = (int) ($item->pro_id ?? 0);
            if ($proId < 1) {
                $proId = (int) ($item->product_id ?? 0);
            }
            if ($proId < 1) {
                continue;
            }
            $payloadItems[] = [
                'pro_id' => $proId,
                'name' => (string) ($item->name ?? ''),
                'sku' => (string) ($item->sku ?? ''),
                'qty' => (float) ($item->qty ?? 0),
                'qty_label' => (string) ($item->qtyFormatted ?? ($item->qty ?? 0)),
                'minimum_stock' => (float) ($item->minimum_stock ?? 0),
                'price_label' => (string) ($item->salepriceDetail_Formatted ?? '0.00'),
                'total_label' => (string) ($item->importeDetail_Formatted ?? '0.00'),
                'image' => (string) ($item->imagen_js ?? catalog_public_no_image_url()),
            ];
        }

        $totales = $this->CatalogoPublicModel->calcular_total_ajax($token_kor);
        $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'success' => true,
                'items' => $payloadItems,
                'detalle' => $totales,
            ]));
    }

    public function carts()
    {
        $token_kor = $this->getIncomingCatalogToken();
        $token_kor = ($token_kor !== null && $token_kor !== '') ? $token_kor : '';
        $car = $this->validar_token_entrante($token_kor);

        // validamos que el token del carrito
        if ($car['success'] == false) {
            $this->mostrar_vistas_404($car);
            return;
        }

        $session = $car['session'] ?? $this->fetchCatalogPublicSession($token_kor, false);
        if ($session === null || empty($session['valid'])) {
            $this->mostrar_vistas_404(['success' => false, 'reason' => 'not_found', 'requested_url' => current_url()]);
            return;
        }

        $this->catalogPublicSession = $session;
        $this->applyCatalogPublicSession($session);
        $car_detail = $this->cartDetailFromApiSession($session);
        $viewContext = $this->buildCatalogViewContextFromSession($session);

        $v_imagen = $this->version();
        $info['show_proforma'] = false;

        switch ($car_detail->type_cart) {

            case 'to_company':
                $info['show_proforma'] = true;
                break;

            case 'to_portal_seller':
                $info['show_proforma'] = true;
                break;

            case 'to_customer':
                break;
            default:
                break;
        }

        $this->change_status_abierto($car_detail->token_kor);

        $info = array_merge($info, $viewContext);
        $info['version'] = $v_imagen;
        $comp = $info['info_company'];
        $info['lang_catalogo'] = $this->catalogLang('cmp-catalog', $comp);
        $info['totales'] = $this->CatalogoPublicModel->calcular_total_ajax($token_kor);
        $info['carproducts'] = $this->CatalogoPublicModel->obtener_product_car("token_car", $token_kor, $this->format_numeric);
        $this->CatalogoPublicModel->enrich_carproducts_with_images(
            $info['carproducts'],
            (int) $car_detail->company_id,
            (string) $token_kor
        );
        $info['qty_car'] = count($info['carproducts']);
        $lang_order = $this->catalogLang('cmp-orders', $comp);
        $defaultTaxLabel = (isset($lang_order['includes_tax']) && $lang_order['includes_tax'] !== '') ? $lang_order['includes_tax'] : ((isset($comp->language_id) && (int)$comp->language_id === 2) ? 'Incluye impuesto' : 'Includes tax');
        // En place order del catálogo usar siempre la etiqueta según idioma (no la personalizada de la empresa) para que respete "Includes tax" / "Incluye impuesto".
        $info['plusTaxesLabel'] = (!empty($comp->show_plus_taxes_label)) ? $defaultTaxLabel : '';

        $info['lang_footer'] = $this->catalogLang('cmp-footer', $comp);


        switch ($car_detail->type_cart) {
            case 'to_customer':
                $info['js'] = ['enviroment_catalogoweb.js', 'get_languagefromcart.js', 'placeorder.js', 'validate.js', 'check_newaddress.js', 'cleancart.js', 'cart-sidebar.js'];
                break;
            case 'to_portal_seller':
                $info['js'] = ['enviroment.js', 'get_language.js', 'placeorder.js', 'check_newaddress.js', 'cleancart.js', 'cart-sidebar.js'];
                break;
            case 'to_company':
                $info['js'] = ['enviroment_portal.js', 'get_language.js', 'placeorder.js', 'check_newaddress.js', 'cleancart.js', 'cart-sidebar.js'];
                break;
        }

        $info['catalog_uri_index'] = $this->catalogPublicUriIndex();
        $info['catalog_uri_carts'] = $this->catalogPublicUriCarts();
        $info = array_merge($info, $this->catalogPortalNavInfo());
        $this->load->view('catalogo/catalog_header', $info);
        $this->load->view('catalogo/placeorder', $info);
        $this->load->view('catalogo/catalog_footer', $info);

    }

    public function eliminar()
    {
        $request = $this->input->post();
        $token = isset($request['token']) ? (string) $request['token'] : '';
        $product_id = isset($request['id']) ? (int) $request['id'] : 0;

        if ($token === '' || $product_id < 1) {
            $this->jsonCatalogCartTokenInvalid();

            return;
        }

        $ctx = $this->catalogCartMutationContext($token);
        if (empty($ctx['valid'])) {
            $this->jsonCatalogCartExpired($ctx['car'] ?? []);

            return;
        }

        $lang_order = $ctx['lang_order'] ?? [];
        $apiResult = $this->CatalogoPublicModel->catalogPublicRemoveLine($token, $product_id);
        if (is_array($apiResult) && ! empty($apiResult['success'])) {
            $msg = $lang_order['producto_eliminado'] ?? 'Product Deleted!';
            echo json_encode([
                'success' => true,
                'msg' => $msg,
                'detalle' => $apiResult['detalle'] ?? $apiResult['totals'] ?? [],
            ]);

            return;
        }

        $this->jsonCatalogCartApiFailure($lang_order);
    }

    public function get_customer($customer_id)
    {
        return $this->buscarCustomer($customer_id);
    }

    /**
     * @param object $newAddress
     * @param array<string, mixed> $request
     * @return array<string, mixed>
     */
    protected function buildCatalogPublicPlaceOrderPayload($newAddress, $request, $car_detail)
    {
        $decoded = [];
        if (! empty($newAddress->data)) {
            $decoded = is_string($newAddress->data) ? json_decode($newAddress->data, true) : (array) $newAddress->data;
            if (! is_array($decoded)) {
                $decoded = [];
            }
        }
        $internalComments = trim(($decoded['comment_order'] ?? '') . ' ' . ($decoded['shipping_notes'] ?? ''));

        return [
            'proforma' => ! empty($request['proforma']) && ($request['proforma'] === '1' || $request['proforma'] === true),
            'internal_comments' => $internalComments,
            'new_address' => $decoded,
            'notes' => [
                'shipping_notes' => (string) ($decoded['shipping_notes'] ?? ''),
                'comment_order' => (string) ($decoded['comment_order'] ?? ''),
            ],
            'source' => $this->CatalogoPublicModel->obtenerSource($car_detail->type_cart) ?? 'app',
        ];
    }

    /**
     * Respuesta JSON tras crear la orden (emails, flash, redirect).
     */
    protected function finishCatalogPlaceOrderSuccess($car_detail, int $orderIdInsert, $lang_order, $info_company)
    {
        switch ($car_detail->type_cart) {
            case 'to_company':
                $this->session->set_flashdata('success', 'success');
                $this->session->set_flashdata('msg', $lang_order['order_saved'] . '!');
                return $this->Helper->response_json([
                    'success' => true,
                    'msg' => $lang_order['order_saved'],
                    'back' => true,
                    'url' => company_url('orders/view/' . $orderIdInsert),
                ]);

            case 'to_portal_seller':
                $this->session->set_flashdata('success', 'success');
                $this->session->set_flashdata('msg', $lang_order['order_enviada'], 'success');
                $this->session->set_flashdata('seller_catalog_flash', 'catalog_order_success');
                return $this->Helper->response_json([
                    'success' => true,
                    'msg' => $lang_order['order_enviada'],
                    'back' => true,
                    'url' => site_url('sellers/catalog/products'),
                ]);

            case 'to_customer':
            default:
                // Tras place-order el token/carrito queda cerrado: no redirigir a catalogo/producto?token=…
                // (provoca 404). Cerrar ventana/popup o ir al portal de pedidos.
                $isPortal = $this->session->has_userdata('customer_portal_customer_id')
                    && $this->session->has_userdata('customer_portal_company_id');
                $payload = [
                    'success' => true,
                    'msg' => $lang_order['order_enviada'],
                    'back' => false,
                    'close_window' => ! $isPortal,
                    'url' => $isPortal ? site_url('customer/orders') : '',
                ];
                if (! empty($car_detail->return_admin)) {
                    $payload['close_window'] = true;
                    $payload['url'] = company_url('orders/view/' . $orderIdInsert);
                    $payload['opener_redirect'] = company_url('orders/view/' . $orderIdInsert);
                }

                return $this->Helper->response_json($payload);
        }
    }

    public function place_order()
    {
        $request = $this->input->post();
        $contentType = (string) $this->input->get_request_header('Content-Type', true);
        $raw = file_get_contents('php://input');
        if (
            (empty($request) || strpos($contentType, 'application/json') !== false)
            && is_string($raw)
            && $raw !== ''
        ) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $request = $decoded;
            }
        }
        if (! is_array($request)) {
            $request = [];
        }
        // Compatibilidad: algunos formularios envían campos dentro de "sending".
        if (isset($request['sending']) && is_array($request['sending'])) {
            $request = array_merge($request['sending'], $request);
            unset($request['sending']);
        }

        $token = isset($request['token']) ? trim((string) $request['token']) : '';
        if ($token === '') {
            return $this->Helper->response_json(['success' => false, 'mesagge' => 'Token de carrito requerido.']);
        }
        $carValidation = $this->validar_token_entrante($token);
        if ($carValidation['success'] == false) {
            return $this->Helper->response_json([
                'success' => false,
                'mesagge' => isset($carValidation['lang_catalog']['catalog_expired'])
                    ? $carValidation['lang_catalog']['catalog_expired']
                    : 'The requested address does not exist',
            ]);
        }

        $session = $carValidation['session'] ?? $this->catalogPublicSession ?? [];
        $car_detail = $this->resolveCatalogCartDetail($token, $carValidation);
        if (! $car_detail) {
            return $this->Helper->response_json(['success' => false, 'mesagge' => 'Carrito no encontrado.']);
        }

        $info_company = $this->companyObjectFromSession($session['company'] ?? null);
        $lang_order = $this->catalogLang('cmp-orders', $info_company);

        $statusMeta = $session['status_cart'] ?? null;
        $status = is_array($statusMeta) ? (object) $statusMeta : null;
        if ($status !== null && isset($status->nosubmit_order) && (int) $status->nosubmit_order === 1) {
            $this->session->set_flashdata('msg', $lang_order['no_surmitorder_admin']);
            return $this->Helper->response_json([
                'success' => false,
                'reload' => true,
                'mesagge' => $lang_order['no_surmitorder_admin'],
            ]);
        }

        $carproducts = $this->CatalogoPublicModel->obtener_product_car('token_car', $token, $this->format_numeric);
        if (empty($carproducts)) {
            return $this->Helper->response_json([
                'success' => false,
                'mesagge' => $lang_order['complete_qty'] ?? 'Hay errores en los productos del carrito.',
            ]);
        }

        $newAddress = $this->CatalogoPublicModel->newAddress($request, $info_company->language_id);
        if (! $newAddress->is_valid) {
            return $this->Helper->response_json(['success' => false, 'mesagge' => $newAddress->msg, 'errores_list' => $newAddress->errores]);
        }

        if (! empty($info_company->sell_with_inventory)) {
            foreach ($carproducts as $carpro) {
                $proId = isset($carpro->pro_id) ? (int) $carpro->pro_id : (isset($carpro->product_id) ? (int) $carpro->product_id : 0);
                if ($proId <= 0) {
                    continue;
                }
                $productDetail = $this->CatalogoPublicModel->catalogPublicProductDetail($token, $proId);
                if (is_array($productDetail) && isset($productDetail['product']['stock'])) {
                    $carpro->stock = $productDetail['product']['stock'];
                }
            }
        }

        $oderDetail = $this->CatalogoPublicModel->obtenerOrderDetail($info_company->sell_with_inventory,$carproducts, $lang_order);
        if(!$oderDetail->is_valid) {
            $errMsg = is_array($oderDetail->errores) && count($oderDetail->errores) > 0
                ? (isset($oderDetail->errores[0]['content']) ? $oderDetail->errores[0]['content'] : (isset($oderDetail->errores[0]['msg']) ? $oderDetail->errores[0]['msg'] : 'Hay errores en los productos del carrito. Revise cantidades o stock.'))
                : 'Hay errores en los productos del carrito.';
            return $this->Helper->response_json([
                'success' => false,
                'mesagge' => $errMsg,
                'message' => ['errors' => $oderDetail->errores],
                'errores' => $oderDetail->errores
            ]);
        }

        $placePayload = $this->buildCatalogPublicPlaceOrderPayload($newAddress, $request, $car_detail);
        $apiOrder = $this->CatalogoPublicModel->catalogPublicPlaceOrder($token, $placePayload);

        if ($apiOrder === null) {
            return $this->Helper->response_json([
                'success' => false,
                'mesagge' => 'No se pudo crear la orden. Intente de nuevo.',
                'message' => 'No se pudo crear la orden. Intente de nuevo.',
            ]);
        }

        if (empty($apiOrder['success'])) {
            $errorCode = $apiOrder['error'] ?? '';
            if ($errorCode === 'nosubmit') {
                $this->session->set_flashdata('msg', $lang_order['no_surmitorder_admin']);
                return $this->Helper->response_json([
                    'success' => false,
                    'reload' => true,
                    'mesagge' => $lang_order['no_surmitorder_admin'],
                ]);
            }
            if ($errorCode === 'expired') {
                return $this->Helper->response_json([
                    'success' => false,
                    'mesagge' => isset($carValidation['lang_catalog']['catalog_expired'])
                        ? $carValidation['lang_catalog']['catalog_expired']
                        : 'The requested address does not exist',
                ]);
            }
            $errMsg = isset($apiOrder['message']) && is_string($apiOrder['message'])
                ? $apiOrder['message']
                : 'Error al crear la orden.';
            return $this->Helper->response_json([
                'success' => false,
                'mesagge' => $errMsg,
                'message' => $errMsg,
            ]);
        }

        $orderIdInsert = (int) ($apiOrder['order_id'] ?? 0);
        if ($orderIdInsert < 1) {
            return $this->Helper->response_json([
                'success' => false,
                'mesagge' => 'Respuesta inválida al crear la orden.',
                'message' => 'Respuesta inválida al crear la orden.',
            ]);
        }

        log_message('info', 'place_order: orden creada vía catalog-public/place-order order_id=' . $orderIdInsert);

        return $this->finishCatalogPlaceOrderSuccess($car_detail, $orderIdInsert, $lang_order, $info_company);
    }

    public function expire_apertured_cart($token)
    {
        $this->CatalogoPublicModel->catalogPublicPatchSession((string) $token, ['expire' => true]);
    }

    public function create_neworder()
    {
        $request = $this->input->post();
        $token = isset($request['token']) ? (string) $request['token'] : '';
        if ($token === '') {
            echo json_encode(['success' => false]);

            return;
        }

        $car_validation = $this->validar_token_entrante($token);
        $car_detail = $this->resolveCatalogCartDetail($token, $car_validation);
        if (! $car_detail) {
            echo json_encode(['success' => false]);

            return;
        }

        $totals = $this->CatalogoPublicModel->calcular_total_ajax($car_detail->token_kor);
        $qty_products = (int) ($totals['cantidad_productos'] ?? 0);

        if ($qty_products > 0) {
            $this->CatalogoPublicModel->catalogPublicClearCart($car_detail->token_kor);
        }
        echo json_encode(['success' => true]);
    }

    public function vaciarCarrito()
    {
        $request = $this->input->get();
        $token = isset($request['token']) ? trim((string) $request['token']) : '';
        if ($token === '') {
            return $this->Helper->response_json(['success' => false, 'message' => 'Token requerido']);
        }

        $car = $this->validar_token_entrante($token);
        if (empty($car['success'])) {
            return $this->Helper->response_json([
                'success' => false,
                'mesagge' => $car['lang_catalog']['catalog_expired'] ?? 'The requested address does not exist',
            ]);
        }

        $session = $car['session'] ?? [];
        $companyId = (int) ($session['company']['company_id'] ?? 0);
        $languageId = (int) ($session['company']['language_id'] ?? 0);
        $lang_order = $companyId > 0
            ? $this->langmodel->get_lang('cmp-orders', $companyId, $languageId > 0 ? $languageId : null)
            : [];
        $defaultMsg = $languageId === 2 ? 'Carrito vaciado' : 'Cart cleared';
        $msg = ! empty($lang_order['mensaje_carrito_vacio'])
            ? $lang_order['mensaje_carrito_vacio']
            : $defaultMsg;
        $this->session->set_flashdata('success', 'success');
        $this->session->set_flashdata('msg_cart_cleared', $msg);

        $apiResult = $this->CatalogoPublicModel->catalogPublicClearCart($token);
        if (! is_array($apiResult) || empty($apiResult['success'])) {
            return $this->Helper->response_json(['success' => false, 'message' => $defaultMsg]);
        }

        return $this->Helper->response_json(['success' => true]);
    }

    public function historial() {
        $token = trim((string) $this->input->get('token'));
        $page = max(1, (int) ($this->input->get('page') ?: 1));
        $limit = 10;

        if ($token === '' || ! $this->session->has_userdata('vendorId')) {
            return show_404();
        }

        $validation = $this->validar_token_entrante($token);
        if ($validation['success'] == false) {
            $this->mostrar_vistas_404($validation);

            return;
        }

        $session = $validation['session'] ?? $this->catalogPublicSession ?? [];
        if (empty($session['valid'])) {
            return show_404();
        }

        $info = $this->buildCatalogHistorialShell($token, $session);
        $comp = $info['info_company'];
        $langcliente = $this->catalogLang('cmp-cliente', $comp);

        $ordersApi = $this->CatalogoPublicModel->catalogPublicOrders($token, $page, $limit);
        $ordersRows = is_array($ordersApi['data'] ?? null) ? $ordersApi['data'] : [];
        $orders = $this->mapCatalogPublicOrdersForView($ordersRows);

        $UrlHistorial = base_url('catalogo/producto/historial?token=' . rawurlencode($token));
        $UrlDetalleHistorial = base_url('catalogo/producto/detalleHistorial');

        $dataCustomerOrders = [
            'lang_catalogo' => $info['lang_catalogo'],
            'lang_json' => [
                'cmp_cliente' => ['lang' => $langcliente],
                'cmp_catalog' => ['lang' => $info['lang_catalogo']],
            ],
            'orders' => $orders,
            'info_company' => $info['info_company'],
            'mostrarColumnaseller' => false,
            'urlDetalleOrden' => $UrlDetalleHistorial,
            'catalog_token' => $token,
        ];

        $ordersCount = (int) ($ordersApi['total'] ?? count($orders));
        $config = $this->Commonmodel->obtener_configuracion_paginacion($UrlHistorial, $ordersCount, $limit, $comp->language_id, '', 'current');
        $this->pagination->initialize($config);
        $dataCustomerOrders['paginate'] = $this->pagination->create_links();

        $this->load->view('catalogo/catalog_header', $info);
        $this->load->view('catalogo/CustomerOrders', $dataCustomerOrders);
        $this->load->view('catalogo/catalog_footer', $info);
   }

    public function detalleHistorial($orderId = null){

        $token = trim((string) $this->input->get('token'));
        $orderId = (int) $orderId;
        if ($orderId < 1 || $token === '' || ! $this->session->has_userdata('vendorId')) {
            return show_404();
        }

        $validation = $this->validar_token_entrante($token);
        if ($validation['success'] == false) {
            $this->mostrar_vistas_404($validation);

            return;
        }

        $session = $validation['session'] ?? $this->catalogPublicSession ?? [];
        if (empty($session['valid'])) {
            return show_404();
        }

        $orderApi = $this->CatalogoPublicModel->catalogPublicOrderDetail($token, $orderId);
        if (! is_array($orderApi) || empty($orderApi['order'])) {
            return show_404();
        }

        $info = $this->buildCatalogHistorialShell($token, $session);
        $comp = $info['info_company'];
        $lang_order = $this->catalogLang('cmp-orders', $comp);
        $orderData = $this->arrayToViewObject($orderApi['order']);

        $dataOrder = [
            'data' => $orderData,
            'info_company' => $info['info_company'],
            'info_customer' => $info['info_customer'],
            'format_numeric' => $info['format_numeric'],
            'format_date' => $this->Helper->format_date($comp->language_id),
            'moneda' => $this->Helper->currency($comp->currency)->Symbol ?? '$',
            'lang_order' => $lang_order,
            'lang_catalogo' => $info['lang_catalogo'],
            'lang_json' => [
                'cmp_orders' => ['lang' => $lang_order],
                'cmp_catalog' => ['lang' => $info['lang_catalogo']],
            ],
            'seller' => $orderData->seller ?? null,
            'mostrar_asignados' => false,
            'mostrarNombreCliente' => false,
            'mostrarNombreVendedor' => false,
        ];

        $this->load->view('catalogo/catalog_header', $info);
        $this->load->view('catalogo/DetalleOrden', $dataOrder);
        $this->load->view('catalogo/catalog_footer', $info);

    }

    /**
     * @param array<string, mixed> $session
     * @return array<string, mixed>
     */
    protected function buildCatalogHistorialShell(string $token, array $session): array
    {
        $this->catalogPublicSession = $session;
        $this->applyCatalogPublicSession($session);
        $viewContext = $this->buildCatalogViewContextFromSession($session);
        $info = array_merge($viewContext, $this->catalogPortalNavInfo());
        $comp = $info['info_company'];
        $info['version'] = $this->version();
        $info['lang_catalogo'] = $this->catalogLang('cmp-catalog', $comp);
        $info['lang_footer'] = $this->catalogLang('cmp-footer', $comp);
        $info['totales'] = $this->CatalogoPublicModel->calcular_total_ajax($token);
        $info['carproducts'] = $this->CatalogoPublicModel->obtener_product_car('token_car', $token, $this->format_numeric);
        $info['qty_car'] = count($info['carproducts']);
        $info['catalog_uri_index'] = $this->catalogPublicUriIndex();
        $info['catalog_uri_carts'] = $this->catalogPublicUriCarts();
        $info['info_company'] = $this->enrichCatalogCompanyForViews($info['info_company']);

        return $info;
    }

    protected function enrichCatalogCompanyForViews(object $company): object
    {
        $currency = $this->Helper->currency($company->currency ?? '');
        $company->currencies = [(object) ['symbol' => $currency->Symbol ?? '$']];
        if (! isset($company->format_numeric) || ! is_object($company->format_numeric)) {
            $company->format_numeric = $this->format_numeric;
        }

        return $company;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @return array<int, object>
     */
    protected function mapCatalogPublicOrdersForView(array $rows): array
    {
        $orders = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $orders[] = $this->arrayToViewObject($row);
        }

        return $orders;
    }

    /**
     * @param mixed $data
     * @return object|array<mixed>
     */
    protected function arrayToViewObject($data)
    {
        return json_decode(json_encode($data), false);
    }

    /**
     * Token del carrito en la petición (GET). El portal cliente puede sobreescribir y tomarlo de sesión.
     */
    protected function getIncomingCatalogToken(): ?string
    {
        $t = $this->input->get('token');
        if ($t === null || $t === '') {
            return null;
        }

        $t = is_string($t) ? trim($t) : null;
        if ($t !== null && $t !== '' && str_contains($t, ' ')) {
            $t = str_replace(' ', '+', $t);
        }

        return $t;
    }

    protected function isCustomerPortalCatalogController(): bool
    {
        return false;
    }

    /**
     * Oculta la franja "CUSTOMER: …" encima del filtro de categorías en catalog_header
     * (p. ej. iframe del portal cliente, donde es redundante).
     */
    protected function hideCatalogHeaderCustomerLine(): bool
    {
        return false;
    }

    protected function catalogPublicUriIndex(): string
    {
        return 'catalogo/producto';
    }

    protected function catalogPublicUriCarts(): string
    {
        return 'catalogo/producto/carts';
    }

    /**
     * Tras pedido (to_customer): URL del listado; en sesión de portal cliente usa /customer/catalog.
     */
    protected function toCustomerBrowseUrlWithToken(string $token_kor): string
    {
        $enc = rawurlencode($token_kor);
        if ($this->session->has_userdata('customer_portal_customer_id') && $this->session->has_userdata('customer_portal_company_id')) {
            return base_url('customer/catalog') . '?token=' . $enc;
        }

        return base_url('catalogo/producto?token=') . $enc;
    }

    /**
     * @return array<string, mixed>
     */
    protected function catalogPortalNavInfo(): array
    {
        $hideCustomerLine = $this->hideCatalogHeaderCustomerLine();

        if (! $this->isCustomerPortalCatalogController()) {
            return [
                'show_customer_portal_nav' => false,
                'portal_nav_catalog_url' => '',
                'portal_nav_orders_url' => '',
                'portal_nav_logout_url' => '',
                'hide_catalog_header_customer_line' => $hideCustomerLine,
            ];
        }

        return [
            'show_customer_portal_nav' => true,
            'portal_nav_catalog_url' => site_url('customer/catalog'),
            'portal_nav_orders_url' => site_url('customer/orders'),
            'portal_nav_logout_url' => site_url('customer/logout'),
            'hide_catalog_header_customer_line' => $hideCustomerLine,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function fetchCatalogPublicSession(string $token, bool $touch = false): ?array
    {
        if ($token === '') {
            return null;
        }
        $params = ['token' => $token];
        if ($touch) {
            $params['touch'] = '1';
        }
        try {
            $res = $this->getCatalogPublicFromApi('catalog-public/session', $params);
            $normalized = $this->normalizeCatalogPublicSessionResponse($res);
            if ($normalized !== null && ! empty($normalized['valid'])) {
                $this->catalogPublicSession = $normalized;
            }

            return $normalized;
        } catch (\Throwable $e) {
            log_message('error', 'fetchCatalogPublicSession: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * @param mixed $res
     * @return array<string, mixed>|null
     */
    protected function normalizeCatalogPublicSessionResponse($res): ?array
    {
        if (is_object($res)) {
            $res = json_decode(json_encode($res), true);
        }
        if (! is_array($res)) {
            return null;
        }
        if (isset($res['valid'])) {
            return $res;
        }
        if (isset($res['error'], $res['message']) && is_array($res['message']) && isset($res['message']['valid'])) {
            return $res['message'];
        }
        if (isset($res['error'], $res['message']) && is_array($res['message']) && isset($res['message']['reason'])) {
            return [
                'valid' => false,
                'reason' => (string) $res['message']['reason'],
                'company' => $res['message']['company'] ?? null,
                'flags' => $res['message']['flags'] ?? ['expired' => ($res['message']['reason'] ?? '') === 'expired'],
            ];
        }

        return null;
    }

    /**
     * @param array<string, mixed> $session
     */
    protected function applyCatalogPublicSession(array $session): void
    {
        $company = $session['company'] ?? null;
        if (is_array($company)) {
            $this->company_info = $this->companyObjectFromSession($company);
        }
        $format = $session['format_numeric'] ?? null;
        if (is_array($format)) {
            $this->format_numeric = (object) $format;
        }
        if (! empty($this->company_info->company_id)) {
            $this->lang_order = $this->catalogLang('cmp-orders', $this->company_info);
        }
    }

    /**
     * Traducciones del catálogo público usando locale de la sesión API (sin consultar companies en BD).
     *
     * @param object|int|null $company Objeto company de sesión o company_id
     * @return array<string, mixed>
     */
    protected function catalogLang(string $module, $company = null): array
    {
        if (is_object($company)) {
            $companyId = (int) ($company->company_id ?? 0);
            $languageId = (int) ($company->language_id ?? 0);
        } elseif (is_int($company) || (is_string($company) && $company !== '')) {
            $companyId = (int) $company;
            $languageId = (int) ($this->company_info->language_id ?? 0);
        } else {
            $companyId = (int) ($this->company_info->company_id ?? 0);
            $languageId = (int) ($this->company_info->language_id ?? 0);
        }

        if ($languageId > 0) {
            return $this->langmodel->get_lang($module, $companyId, $languageId);
        }

        return $this->langmodel->get_lang($module, $companyId);
    }

    /**
     * @param array<string, mixed> $session
     * @return array<string, mixed>
     */
    protected function buildCatalogViewContextFromSession(array $session): array
    {
        $cart = $this->cartDetailFromApiSession($session);
        $company = $this->companyObjectFromSession($session['company'] ?? null);
        $catalog = $this->objectFromSessionBlock($session['catalog'] ?? null, [
            'catalog_id' => 0,
            'catalog_name' => '',
            'default_catalog' => 0,
            'cabeza_pagina' => '',
            'logo' => '',
            'logo_url' => '',
            'studio' => [],
        ]);
        $seller = ! empty($session['seller']) && is_array($session['seller'])
            ? $this->objectFromSessionBlock($session['seller'])
            : null;
        $customer = $this->objectFromSessionBlock($session['customer'] ?? null, ['customer_id' => 0]);
        $catalogStudio = $this->catalogStudioFromSessionCatalog($catalog);

        return [
            'company' => $cart,
            'info_company' => $company,
            'info_catalog' => $catalog,
            'catalog_studio' => $catalogStudio,
            'info_seller' => $seller,
            'info_customer' => $customer,
            'cliente_precio' => $this->clientePrecioFromSessionCustomer(
                is_array($session['customer'] ?? null) ? $session['customer'] : []
            ),
            'format_numeric' => $this->format_numeric,
        ];
    }

    /**
     * Definición Catalog Studio del catálogo elegido (plantilla, precios, tema, layout).
     */
    protected function catalogStudioFromSessionCatalog(object $catalog): object
    {
        $studio = [];
        if (isset($catalog->studio) && (is_array($catalog->studio) || is_object($catalog->studio))) {
            $studio = (array) $catalog->studio;
        }

        $themeIn = [];
        if (isset($studio['theme']) && (is_array($studio['theme']) || is_object($studio['theme']))) {
            $themeIn = (array) $studio['theme'];
        }

        $layout = (string) ($studio['layout'] ?? 'grid');
        $cols = (int) ($studio['cols'] ?? 3);
        if ($cols < 1) {
            $cols = 3;
        }
        $shopView = (string) ($studio['shop_view'] ?? '');
        if ($shopView === '') {
            if (in_array($layout, ['list', 'table', 'magazine'], true) || $cols <= 1) {
                $shopView = 'grid_list';
            } elseif ($cols >= 4) {
                $shopView = 'grid_4';
            } else {
                $shopView = 'grid_3';
            }
        }

        $cardColClass = 'col-lg-3 col-md-4 col-sm-6';
        if ($shopView === 'grid_list') {
            $cardColClass = 'col-12';
        } elseif ($cols === 2) {
            $cardColClass = 'col-lg-6 col-md-6 col-sm-6';
        } elseif ($cols === 3) {
            $cardColClass = 'col-lg-4 col-md-4 col-sm-6';
        } elseif ($cols >= 4) {
            $cardColClass = 'col-lg-3 col-md-4 col-sm-6';
        }

        $logoUrl = trim((string) ($catalog->logo_url ?? ''));
        if ($logoUrl === '' && ! empty($catalog->logo) && function_exists('company_logo_url')) {
            $logoUrl = (string) company_logo_url((string) $catalog->logo);
        }

        return (object) [
            'template_key' => (string) ($studio['template_key'] ?? 'classic-grid'),
            'show_prices' => ! isset($studio['show_prices']) || ! empty($studio['show_prices']),
            'layout' => $layout,
            'cols' => $cols,
            'shop_view' => $shopView,
            'card_col_class' => $cardColClass,
            'message' => trim((string) ($catalog->cabeza_pagina ?? '')),
            'logo_url' => $logoUrl,
            'theme' => (object) [
                'banner_bg' => (string) ($themeIn['banner_bg'] ?? '#111827'),
                'banner_text' => (string) ($themeIn['banner_text'] ?? '#ffffff'),
                'footer_bg' => (string) ($themeIn['footer_bg'] ?? '#111827'),
                'footer_text' => (string) ($themeIn['footer_text'] ?? '#e5e7eb'),
                'accent' => (string) ($themeIn['accent'] ?? '#b91c1c'),
            ],
        ];
    }

    /**
     * @param array<string, mixed>|null $data
     */
    protected function objectFromSessionBlock($data, array $defaults = []): object
    {
        if (! is_array($data)) {
            return (object) $defaults;
        }

        return (object) array_merge($defaults, $data);
    }

    /**
     * @param array<string, mixed>|null $company
     */
    protected function companyObjectFromSession($company): object
    {
        return $this->objectFromSessionBlock($company, [
            'company_id' => 0,
            'language_id' => 0,
            'format_id' => 0,
            'sell_with_inventory' => 0,
            'email_client_on_salesman_order' => 0,
            'email_on_salesman_order' => 0,
            'name' => '',
            'email' => '',
            'phone_number' => '',
            'image' => null,
            'currency' => '',
            'show_inventory' => 0,
            'show_semaforo_stock' => 0,
            'show_plus_taxes_label' => 0,
        ]);
    }

    /**
     * @param array<string, mixed> $customer
     */
    protected function clientePrecioFromSessionCustomer(array $customer)
    {
        return $this->Helper->cast([
            'business_name' => $customer['business_name'] ?? $customer['name'] ?? '',
            'group_name' => $customer['group_name'] ?? '',
            'percentage_on_price' => $customer['percentage_on_price'] ?? '',
            'percent_price_amount' => $customer['percent_price_amount'] ?? 0,
        ]);
    }

    /**
     * @param array<int, object>|null $lines
     * @return array<int, object>
     */
    protected function mapCartLinesToProductDetail($lines): array
    {
        if (! is_array($lines)) {
            return [];
        }
        $detail = [];
        foreach ($lines as $line) {
            $proId = isset($line->pro_id) ? (int) $line->pro_id : (int) ($line->product_id ?? 0);
            if ($proId < 1) {
                continue;
            }
            $detail[] = (object) [
                'product_id' => $proId,
                'qty' => $line->qty ?? 0,
                'comment' => $line->comment ?? '',
            ];
        }

        return $detail;
    }

    /**
     * @param array<string, mixed> $session
     */
    protected function cartDetailFromApiSession(array $session)
    {
        $cart = $session['cart'] ?? null;
        if (! is_array($cart)) {
            return null;
        }

        return (object) $cart;
    }

    /**
     * @param array<string, mixed> $validation
     */
    protected function resolveCatalogCartDetail(string $token, array $validation)
    {
        if (! empty($validation['session']) && is_array($validation['session'])) {
            return $this->cartDetailFromApiSession($validation['session']);
        }

        return null;
    }

}

<?php

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @property CatalogoPublicModel $CatalogoPublicModel
 * @property CustomerModel $CustomerModel
 * @property Commonmodel $Commonmodel
 */

abstract class GenericCatalog extends MY_Controller
{
    public ?int $company;
    public ?int $langId;
    public $company_info = array();
    public $format_numeric = array();

    protected $catalogUrl;
    public $lang_order = array();

    /** @var array<string, mixed>|null */
    protected $catalogPublicSession = null;

    public $indexJs = ['enviroment_catalogoweb.js', 'get_languagefromcart.js', 'shoplist.js', 'app.js'];
    public $cartJs  = ['enviroment_catalogoweb.js', 'get_languagefromcart.js', 'placeorder.js', 'validate.js', 'check_newaddress.js'];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('CatalogoPublicModel');
        $this->load->model('CustomerModel');
    }

    public function showViewCatalog($nameView = "components/catalog/body", $data = [])
    {
        $this->load->view('components/catalog/header', $data);
        $this->load->view($nameView, $data);
        $this->load->view('components/catalog/footer', $data);
    }

    public function index()
    {
        $token = $this->input->get('token');
        $info['token'] = $token;

        $page = 1;
        $get_page = $this->input->get('page');

        if ($get_page != null) {
            $page = $this->input->get('page');
        }

        $order = $this->getObjectFromApi("order/invitation/{$token}");

        if ($order->errors) {
            $this->mostrar_vistas_404($order->errors);
            return;
        }

        $order_company = $order->company_id ?: $this->company_info->company_id;
        $catalogId = $order->catalog_id;
        $customer = $this->CustomerModel->getCustomer($order->customer_id, true);

        $limit = 28;
        $offset = ($page - 1) * $limit;
        $info['categorias'] = $this->obtener_categoriasCatalog($catalogId);
        $info['format_numeric'] = $this->format_numeric;
        $request["category_id"] = $this->input->get("cat_id") == null ? null : $this->input->get("cat_id");
        $request["search"] = $this->input->get("search") == null ? null : $this->input->get("search");
        $request["company_id"] = $order_company;
        $request["catalog_id"] = $catalogId;
        $request["short"] = $this->input->get('short');
        $info['info_customer'] = $customer;
        $productsPage = $this->getObjectFromApi("catalog/{$catalogId}/products");
        $info['products'] = $productsPage->data;
        $info['company_id'] = $order_company;
        $info['catalog_text'] = $order->catalog_text;

        $info['limit'] = $limit;
        $info['info_catalog'] = $this->getObjectFromApi("catalog/{$catalogId}");
        $info['info_seller'] = $this->getObjectFromApi("vendor/{$order->user_id}"); //change to 'seller_id'?
        $info['info_company'] = $comp = $this->company_info;

        $info['total_filtro'] = $productsPage->total;

        // PAGINACION
        $config = $this->getPaginationConfig($productsPage->total, $productsPage->per_page, $order_company);

        $this->pagination->initialize($config);

        $info['paginate'] = $this->pagination->create_links();
        $info['cliente_precio'] = $customer;
        $info['product_detail'] = $this->getObjectFromApi("order/{$order->order_id}/details");

        $info["js_vue"] = [
            "stores/stores_vue.js",
            "stores/search_product.js",
        ];

        $info['totales'] = $this->CatalogoPublicModel->calcular_total_ajax($token);
        //$info['lang_footer'] = $this->langmodel->get_lang('cmp-footer', $comp->company_id);
        $info['js'] = $this->indexJs;
        $this->showViewCatalog("components/catalog/body", $info);
    }

    public function validarCart($cart){

        if (!$cart || $cart->is_deleted) return ['success' => false];
        $company = $cart->company;
        $lang_catalog = $this->langmodel->get_lang('cmp-catalog', $company->company_id);
        $fecha_actual = strtotime(date("Y-m-d H:i:s"), time());
        $fecha_entrada = strtotime($cart->expired_date);

        if ($fecha_actual > $fecha_entrada) {
            return ['success' => false, 'lang_catalog' => $lang_catalog, 'company' => $company];
        }
        return ['success' => true];
    }
    public function validar_token_entrante($token_kor)
    {
        $session = $this->fetchCatalogPublicSession($token_kor);
        if ($session === null) {
            return ['success' => false];
        }
        if (! empty($session['valid'])) {
            $this->catalogPublicSession = $session;

            return ['success' => true, 'session' => $session];
        }

        $companyId = (int) ($session['company']['company_id'] ?? 0);
        $lang_catalog = $companyId > 0 ? $this->langmodel->get_lang('cmp-catalog', $companyId) : [];
        $company = (object) ($session['company'] ?? []);

        if (($session['reason'] ?? '') === 'expired') {
            return ['success' => false, 'lang_catalog' => $lang_catalog, 'company' => $company];
        }

        return ['success' => false];
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function fetchCatalogPublicSession(string $token): ?array
    {
        if ($token === '') {
            return null;
        }
        try {
            $res = $this->getCatalogPublicFromApi('catalog-public/session', ['token' => $token]);

            return is_array($res) ? $res : null;
        } catch (\Throwable $e) {
            log_message('error', 'GenericCatalog fetchCatalogPublicSession: ' . $e->getMessage());
        }

        return null;
    }

    public function mostrar_vistas_404($info)
    {
        $this->load->view('catalogo/vistas_404/catalog_header', $info);
        $this->load->view('catalogo/vistas_404/catalog_404', $info);
        $this->load->view('catalogo/vistas_404/catalog_footer');
    }

    public function obtener_categoriasCatalog($catalogId)
    {
        return $this->getObjectFromApi("catalog/{$catalogId}/categories");
    }

    public function obtener_ProductosPromociones($companyId, $catlogId)
    {
        return [];
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

    public function buscarCustomer($customer_id, $id_company = null)
    {
        if (is_array($this->catalogPublicSession) && ! empty($this->catalogPublicSession['customer'])) {
            $customer = $this->catalogPublicSession['customer'];
            if ((int) ($customer['customer_id'] ?? 0) === (int) $customer_id) {
                return $this->clientePrecioFromSessionCustomer($customer);
            }
        }

        $token = $this->input->get('token') ?: $this->input->post('token');
        if ($token) {
            $session = $this->fetchCatalogPublicSession((string) $token);
            if (is_array($session) && ! empty($session['customer']) && (int) ($session['customer']['customer_id'] ?? 0) === (int) $customer_id) {
                return $this->clientePrecioFromSessionCustomer($session['customer']);
            }
        }

        $apiCustomer = $this->getObjectFromApi('customer/' . (int) $customer_id . '?withGroup');
        if (is_object($apiCustomer) && ! isset($apiCustomer->error)) {
            return $this->Helper->cast([
                'business_name' => $apiCustomer->business_name ?? '',
                'group_name' => $apiCustomer->group_name ?? ($apiCustomer->customer_group->name ?? ''),
                'percentage_on_price' => $apiCustomer->percentage_on_price ?? '',
                'percent_price_amount' => $apiCustomer->percent_price_amount ?? 0,
            ]);
        }

        return $this->Helper->cast([
            'business_name' => '',
            'group_name' => '',
            'percentage_on_price' => '',
            'percent_price_amount' => 0,
        ]);
    }

    protected function clientePrecioFromSessionCustomer(array $customer)
    {
        return $this->Helper->cast([
            'business_name' => $customer['business_name'] ?? $customer['name'] ?? '',
            'group_name' => $customer['group_name'] ?? '',
            'percentage_on_price' => $customer['percentage_on_price'] ?? '',
            'percent_price_amount' => $customer['percent_price_amount'] ?? 0,
        ]);
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

        $session = $validation['session'] ?? $this->fetchCatalogPublicSession($token);
        if (! is_array($session) || empty($session['valid'])) {
            echo json_encode('error token');
            return;
        }

        $detail = $this->CatalogoPublicModel->catalogPublicProductDetail($token, $productId);
        if (! is_array($detail) || empty($detail['success']) || empty($detail['product'])) {
            echo json_encode('error product');
            return;
        }

        $cart = (object) ($session['cart'] ?? []);
        $companyId = (int) ($session['company']['company_id'] ?? 0);
        $info['lang_pop'] = $this->langmodel->get_lang('popover-catalog', $companyId);

        $ahora = DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));
        $v_imagen = $ahora->format("YmdHisu");

        switch ($cart->type_cart ?? '') {
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

        $info['product_detail'] = ! empty($detail['line']) ? (object) $detail['line'] : null;
        $info['imagenes'] = array_map(static fn ($row) => is_object($row) ? $row : (object) $row, $detail['images'] ?? []);
        $info['company'] = $cart;
        $info['version'] = $v_imagen;
        $info['producto_conformato'] = (object) $detail['product'];
        $info['precio_obtenido'] = (float) ($detail['price_for_customer'] ?? 0);
        $info['info_company'] = (object) ($session['company'] ?? []);
        $formatNumeric = $detail['format_numeric'] ?? $session['format_numeric'] ?? null;
        $info['format_numeric'] = is_array($formatNumeric)
            ? (object) $formatNumeric
            : (object) ['digits' => 2, 'radixPoint' => '.', 'groupSeparator' => ','];

        $modalProduct = $this->load->view('catalogo/modal/modalproduct', $info, true);

        echo json_encode(['modal' => $modalProduct]);
    }

    public function changeStatusCartAbierto($cart)
    {
        if ($cart->status_cart != null && $cart->status_cart != 4) {
            $product_detail = $cart->detail_cars;
            if ($product_detail > 0) {
                $this->put("catalogcart/{$cart->cart_id}", ['status_cart' => 3]);
            } else {
                $this->put("catalogcart/{$cart->cart_id}", ['status_cart' => 2]);
            }
        } else {
            $this->put("catalogcart/{$cart->cart_id}", ['status_cart' => 2]);
        }
    }

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

    public function expire_apertured_cart($token)
    {
        $this->CatalogoPublicModel->catalogPublicPatchSession((string) $token, ['expire' => true]);
    }

    public function create_neworder()
    {
        $request = $this->input->post();
        $order = $this->getObjectFromApi("order/{$request['token']}");

        $this->post("order/{$order->order_id}", [
            'order_status' => 0,
        ]);

        return $this->Helper->response_json(['success' => true]);
    }
}

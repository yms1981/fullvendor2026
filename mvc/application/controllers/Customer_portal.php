<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Portal de acceso para clientes: vistas en application/views/customer/;
 * login vía API v2/customer/login y token Sanctum v2/user/token (mismo criterio que company/MY_Controller::setSessionData).
 *
 * @property CI_Input $input
 * @property CI_Output $output
 * @property CI_Session $session
 * @property Helper $Helper
 * @property Customer_portal_catalog_model $Customer_portal_catalog_model
 */
class Customer_portal extends MY_Controller
{
    private const CUSTOMER_COMPANY_KEY = 'customer_portal_company_id';
    private const CUSTOMER_ID_KEY = 'customer_portal_customer_id';
    private const CUSTOMER_LANG_KEY = 'customer_portal_language_id';
    private const CUSTOMER_CART_TOKEN_KEY = 'customer_portal_session_token';

    /** Máximo de órdenes por página al consumir GET v2/customer/{id}/orders desde el portal. */
    private const PORTAL_ORDERS_API_PER_PAGE = 150;

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Helper');
    }

    public function index(): void
    {
        // Mantener app context compatible con carga de CMS/layout (about-us, etc.).
        $this->setApplication('marketplace');

        if (strtoupper($this->input->server('REQUEST_METHOD') ?? '') === 'POST') {
            $this->portalLoginJson();

            return;
        }

        if ($this->session->has_userdata(self::CUSTOMER_COMPANY_KEY) && $this->session->has_userdata(self::CUSTOMER_ID_KEY)) {
            redirect(site_url('customer/catalog'));

            return;
        }

        $data = $this->basePortalData();
        // Rutas solo path: el fetch usa el mismo origen que la página (evita CORS si BASE_URL es http y el sitio sirve https)
        $data['authenticate_path'] = $this->portalUriPath('customer/login');
        $data['menu_path'] = $this->portalUriPath('customer/catalog');
        $this->load->view('customer/login', $data);
    }

    /**
     * Redirección HTTP a /customer/login (acceso cliente).
     * Nota: la ruta corta /login se define en routes.php (hoy apunta a Auth, no a este método).
     */
    public function root_login(): void
    {
        redirect(site_url('customer/login'), 'location', 302);
    }

    /**
     * URL legada: la entrada del portal es el catálogo (primera pestaña).
     */
    public function menu(): void
    {
        $this->requireCustomerSession();
        redirect(site_url('customer/catalog'));
    }

    /**
     * Pestaña Catálogo: mismo header y pestañas que Órdenes; listado/carrito en iframe (customer/catalog/embed).
     */
    public function catalog(): void
    {
        $this->loadCustomerCatalogView('browse');
    }

    public function catalog_cart(): void
    {
        $this->loadCustomerCatalogView('cart');
    }

    public function catalog_checkout(): void
    {
        $this->loadCustomerCatalogView('checkout');
    }

    public function orders(): void
    {
        $this->requireCustomerSession();
        $customerId = $this->getCustomerId();
        $langId = $this->getPortalLanguageId();

        // API: todas las órdenes del cliente (todos los estados, incluidas aprobadas); sin filtro por estado.
        $orders = $this->fetchPortalOrdersFromApi($customerId, $langId);

        $data = $this->basePortalData();
        $data['orders'] = $orders;
        $data['url_logout'] = site_url('customer/logout');
        $data['url_menu'] = site_url('customer/catalog');
        $data['url_orders'] = site_url('customer/orders');
        $data['url_catalog'] = site_url('customer/catalog');
        $data['active_portal_tab'] = 'orders';
        $data['back_url'] = site_url('customer/catalog');
        $companyId = $this->getCustomerCompanyId();
        $data['portal_company_name'] = $this->getPortalCompanyDisplayName($companyId);
        $custDisplay = $this->getPortalCustomerDisplay($customerId);
        $data['portal_customer_business'] = $custDisplay['business'];
        $data['portal_customer_contact'] = $custDisplay['contact'];
        $data['orders_money'] = $this->portalOrdersMoneyFormat($companyId);
        $this->load->view('customer/orders', $data);
    }

    /**
     * Crea/recupera token de carrito de catálogo para el cliente del portal
     * y redirige a la pantalla de selección de productos.
     */
    public function startOrder(): void
    {
        $this->requireCustomerSession();

        $companyId = $this->getCustomerCompanyId();
        $customerId = $this->getCustomerId();
        if ($companyId <= 0 || $customerId <= 0) {
            redirect(site_url('customer/catalog'));

            return;
        }

        $token = $this->ensureCustomerCatalogToken($companyId, $customerId);
        if ($token === '') {
            redirect(site_url('customer/catalog'));

            return;
        }

        redirect(site_url('customer/catalog'));
    }

    /**
     * Portal cliente — catálogo: solo redirección al listado público de productos (flujo independiente de /customer/orders/new).
     */
    public function catalog_order_browse(): void
    {
        $this->redirectCustomerCatalogOrderFlow('browse');
    }

    /**
     * Portal cliente — catálogo: carrito y datos de envío (vista placeorder del catálogo público).
     */
    public function catalog_order_cart(): void
    {
        $this->redirectCustomerCatalogOrderFlow('cart');
    }

    /**
     * Portal cliente — catálogo: mismo destino que el carrito, ancla al formulario de envío (#form-order).
     */
    public function catalog_order_checkout(): void
    {
        $this->redirectCustomerCatalogOrderFlow('checkout');
    }

    /**
     * @param 'browse'|'cart'|'checkout' $mode
     */
    private function redirectCustomerCatalogOrderFlow(string $mode): void
    {
        $this->requireCustomerSession();
        $companyId = $this->getCustomerCompanyId();
        $customerId = $this->getCustomerId();
        if ($companyId <= 0 || $customerId <= 0) {
            redirect(site_url('customer/catalog'));

            return;
        }

        $token = $this->ensureCustomerCatalogToken($companyId, $customerId);
        if ($token === '') {
            redirect(site_url('customer/catalog'));

            return;
        }

        if ($mode === 'browse') {
            redirect(site_url('customer/catalog'));

            return;
        }
        if ($mode === 'cart') {
            redirect(site_url('customer/catalog/cart'));

            return;
        }
        redirect(site_url('customer/catalog/cart') . '#form-order');
    }

    /**
     * Layout de portal + iframe hacia el HTML del catálogo (embed).
     *
     * @param 'browse'|'cart'|'checkout' $mode
     */
    private function loadCustomerCatalogView(string $mode): void
    {
        $this->requireCustomerSession();
        $companyId = $this->getCustomerCompanyId();
        $customerId = $this->getCustomerId();

        $data = $this->basePortalData();
        $data['url_logout'] = site_url('customer/logout');
        $data['url_menu'] = site_url('customer/catalog');
        $data['url_orders'] = site_url('customer/orders');
        $data['url_catalog'] = site_url('customer/catalog');
        $data['active_portal_tab'] = 'catalog';
        $data['portal_company_name'] = $this->getPortalCompanyDisplayName($companyId);
        $custDisplay = $this->getPortalCustomerDisplay($customerId);
        $data['portal_customer_business'] = $custDisplay['business'];
        $data['portal_customer_contact'] = $custDisplay['contact'];
        $data['catalog_mode'] = $mode;
        $data['catalog_order_available'] = false;
        $data['catalog_embed_browse_url'] = '';
        $data['catalog_embed_cart_url'] = '';
        $data['catalog_embed_checkout_url'] = '';
        $data['catalog_embed_default_url'] = '';

        if ($companyId > 0 && $customerId > 0) {
            $token = $this->ensureCustomerCatalogToken($companyId, $customerId);
            if ($token !== '') {
                $browseUrl = site_url('customer/catalog/embed');
                $cartUrl = site_url('customer/catalog/embed/cart');
                $checkoutUrl = site_url('customer/catalog/embed/cart') . '#form-order';
                $defaultUrl = $browseUrl;
                if ($mode === 'cart') {
                    $defaultUrl = $cartUrl;
                } elseif ($mode === 'checkout') {
                    $defaultUrl = $checkoutUrl;
                }

                $data['catalog_order_available'] = true;
                $data['catalog_embed_browse_url'] = $browseUrl;
                $data['catalog_embed_cart_url'] = $cartUrl;
                $data['catalog_embed_checkout_url'] = $checkoutUrl;
                $data['catalog_embed_default_url'] = $defaultUrl;
            }
        }

        $this->load->view('customer/catalog', $data);
    }

    /**
     * Respuesta cruda `data` de GET v2/customer/{id}/orders (Bearer en sesión).
     * Incluye todas las órdenes del cliente que devuelva la API (sin filtrar por estado).
     *
     * @return list<array<string, mixed>>
     */
    private function fetchPortalCustomerOrdersRawFromApi(int $customerId): array
    {
        if ($customerId <= 0) {
            return [];
        }
        $token = trim((string) ($this->session->userdata('token') ?? ''));
        if ($token === '') {
            return [];
        }

        $url = rtrim($this->getApiV2EntryPoint(), '/') . '/v2/customer/' . rawurlencode((string) $customerId) . '/orders';

        try {
            $client = new \GuzzleHttp\Client(['http_errors' => false, 'timeout' => 20]);
            $response = $client->request('GET', $url, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ],
                'query' => [
                    'per_page' => self::PORTAL_ORDERS_API_PER_PAGE,
                    'page' => 1,
                ],
            ]);
            $status = $response->getStatusCode();
            $body = $response->getBody()->getContents();
            $json = json_decode($body, true);
            if ($status !== 200 || ! is_array($json)) {
                log_message('error', 'Customer_portal fetchPortalCustomerOrdersRawFromApi: HTTP ' . $status . ' body=' . substr($body, 0, 500));

                return [];
            }
            $rows = $json['data'] ?? null;

            return is_array($rows) ? $rows : [];
        } catch (\Throwable $e) {
            log_message('error', 'Customer_portal fetchPortalCustomerOrdersRawFromApi: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * Extrae fecha (Y-m-d) y hora (H:i:s) desde la fila de orden de la API.
     *
     * @param array<string, mixed> $row
     * @return array{0: string, 1: string}
     */
    private function portalOrderRowCreatedParts(array $row): array
    {
        $raw = $row['created'] ?? $row['created_at'] ?? $row['updated'] ?? null;
        if (is_array($raw)) {
            $raw = $raw['date'] ?? $raw['formatted'] ?? null;
        }
        $s = trim((string) ($raw ?? ''));
        if ($s === '') {
            return ['', ''];
        }
        if (preg_match('/^(\d{4}-\d{2}-\d{2})[ T](\d{1,2}:\d{2}:\d{2})/', $s, $m)) {
            return [$m[1], $m[2]];
        }
        if (preg_match('/^(\d{4}-\d{2}-\d{2})$/', $s, $m)) {
            return [$m[1], ''];
        }

        $d = substr($s, 0, 10);
        $t = strlen($s) >= 19 ? substr($s, 11, 8) : '';

        return [$d, $t];
    }

    /**
     * Símbolo y formato numérico de la empresa (misma API que el panel company).
     *
     * @return array{symbol: string, digits: int, radixPoint: string, groupSeparator: string}
     */
    private function portalOrdersMoneyFormat(int $companyId): array
    {
        $defaults = [
            'symbol' => '$',
            'digits' => 2,
            'radixPoint' => '.',
            'groupSeparator' => ',',
        ];
        if ($companyId <= 0) {
            return $defaults;
        }

        try {
            $fmt = $this->getObjectFromApi("company/{$companyId}/format-numeric");
            if (is_object($fmt) && ! isset($fmt->error)) {
                $defaults['digits'] = (int) ($fmt->digits ?? 2);
                $defaults['radixPoint'] = (string) ($fmt->radixPoint ?? '.');
                $defaults['groupSeparator'] = (string) ($fmt->groupSeparator ?? ',');
            } elseif (is_array($fmt) && ! isset($fmt['error'])) {
                $defaults['digits'] = (int) ($fmt['digits'] ?? 2);
                $defaults['radixPoint'] = (string) ($fmt['radixPoint'] ?? $fmt['radix_point'] ?? '.');
                $defaults['groupSeparator'] = (string) ($fmt['groupSeparator'] ?? $fmt['group_separator'] ?? ',');
            }
        } catch (\Throwable $e) {
            log_message('debug', 'Customer_portal portalOrdersMoneyFormat format-numeric: ' . $e->getMessage());
        }

        try {
            $sym = $this->getRawFromApi("company/{$companyId}/currency-symbol", []);
            if (is_string($sym)) {
                $sym = trim($sym, " \t\n\r\0\x0B\"'");
                if ($sym !== '' && strcasecmp($sym, 'null') !== 0) {
                    $defaults['symbol'] = $sym;
                }
            }
        } catch (\Throwable $e) {
            log_message('debug', 'Customer_portal portalOrdersMoneyFormat currency-symbol: ' . $e->getMessage());
        }

        return $defaults;
    }

    /**
     * Clase CSS para badge de estado según cod_status (status_orders).
     */
    private function portalOrderStatusBadgeClass(?int $codStatus): string
    {
        if ($codStatus === null) {
            return 'portal-order-status--neutral';
        }
        if ($codStatus < 0) {
            return 'portal-order-status--muted';
        }

        switch ($codStatus) {
            case 0:
            case 1:
                return 'portal-order-status--new';
            case 2:
            case 16:
                return 'portal-order-status--pending';
            case 3:
            case 9:
            case 10:
                return 'portal-order-status--progress';
            case 4:
            case 8:
            case 11:
            case 12:
                return 'portal-order-status--done';
            case 7:
            case 13:
                return 'portal-order-status--bad';
            case 5:
            case 6:
            case 14:
                return 'portal-order-status--return';
            default:
                return 'portal-order-status--neutral';
        }
    }

    /**
     * Listado de órdenes vía GET v2/customer/{id}/orders (Bearer en sesión).
     *
     * @return list<object> Vacío si no hay token, error HTTP o respuesta inválida.
     */
    private function fetchPortalOrdersFromApi(int $customerId, int $langId): array
    {
        $out = [];
        foreach ($this->fetchPortalCustomerOrdersRawFromApi($customerId) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $sellerRaw = $row['seller_name'] ?? $row['sellerName'] ?? null;
            if ($sellerRaw === null && isset($row['seller']) && ! is_array($row['seller'])) {
                $sellerRaw = $row['seller'];
            }
            if ($sellerRaw === null && isset($row['seller']) && is_array($row['seller'])) {
                $sellerRaw = $row['seller']['user']['name'] ?? $row['seller']['name'] ?? $row['seller']['full_name'] ?? $row['seller']['business_name'] ?? null;
            }
            if ($sellerRaw === null && isset($row['user']) && is_array($row['user'])) {
                $sellerRaw = $row['user']['name'] ?? $row['user']['full_name'] ?? null;
            }
            if ($sellerRaw === null) {
                $sellerRaw = $row['user_name'] ?? $row['seller_user_name'] ?? $row['name_seller'] ?? null;
            }
            $sellerId = isset($row['seller_id']) ? (int) $row['seller_id'] : 0;
            if ($sellerRaw === null && $sellerId > 0) {
                $sellerRaw = '#' . (string) $sellerId;
            }
            $so = $row['status_order'] ?? $row['statusOrder'] ?? null;
            $statusCod = null;
            $statusLabel = '';
            if (is_array($so)) {
                $codRaw = $so['cod_status'] ?? $so['codStatus'] ?? null;
                if (is_numeric($codRaw)) {
                    $statusCod = (int) $codRaw;
                }
                if ($langId === 2) {
                    $statusLabel = (string) ($so['name_status_spanish'] ?? $so['name_status_english'] ?? '');
                } else {
                    $statusLabel = (string) ($so['name_status_english'] ?? $so['name_status_spanish'] ?? '');
                }
            }
            $statusBadgeClass = $this->portalOrderStatusBadgeClass($statusCod);

            $totalRaw = $row['total_amount'] ?? null;
            if ($totalRaw === null || $totalRaw === '') {
                $amount = isset($row['amount']) ? (float) $row['amount'] : 0.0;
                $discountA = isset($row['discount_a']) ? (float) $row['discount_a'] : 0.0;
                $total = ($amount > 0.0 || $discountA > 0.0) ? max(0.0, $amount - $discountA) : 0.0;
            } else {
                $total = (float) $totalRaw;
            }

            $oid = $row['order_id'] ?? $row['orderId'] ?? null;
            $orderNumber = $row['order_number'] ?? $row['orderNumber'] ?? $row['order_no'] ?? $row['orderNo'] ?? null;
            if ($orderNumber === null || trim((string) $orderNumber) === '') {
                $orderNumber = $oid !== null && $oid !== '' ? (string) $oid : null;
            }
            [$createdDate] = $this->portalOrderRowCreatedParts($row);

            $orderSource = trim((string) ($row['source'] ?? $row['order_source'] ?? $row['orderSource'] ?? ''));

            $out[] = (object) [
                'order_id' => $oid,
                'order_number' => $orderNumber,
                'order_source' => $orderSource,
                'created' => (string) ($row['created'] ?? $row['created_at'] ?? ''),
                'created_date' => $createdDate,
                'seller_name' => (string) ($sellerRaw ?? ''),
                'status_label' => $statusLabel,
                'status_badge_class' => $statusBadgeClass,
                'total_amount' => $total,
            ];
        }

        return $out;
    }

    public function logout(): void
    {
        $this->removeUserData(self::CUSTOMER_COMPANY_KEY);
        $this->removeUserData(self::CUSTOMER_ID_KEY);
        $this->removeUserData(self::CUSTOMER_LANG_KEY);
        $this->removeUserData(self::CUSTOMER_CART_TOKEN_KEY);
        $this->removeUserData('customer_portal_user_id');
        $this->removeUserData('role');
        $this->removeUserData('compId');
        $this->removeUserData('token');
        $this->setUserAPIToken('');
        redirect(site_url('customer/login'));
    }

    /**
     * Ruta legada: ya no se usa en el formulario. GET → pantalla de login; POST → mismo JSON que customer/login.
     */
    public function authenticate(): void
    {
        if (strtoupper($this->input->server('REQUEST_METHOD') ?? '') !== 'POST') {
            redirect(site_url('customer/login'));

            return;
        }

        $this->portalLoginJson();
    }

    private function portalLoginJson(): void
    {
        $raw = $this->input->raw_input_stream;
        $payload = json_decode($raw, true);
        if (! is_array($payload)) {
            $payload = [];
        }

        $email = trim((string) ($payload['email'] ?? ''));
        $password = (string) ($payload['password'] ?? '');
        if ($email === '' || $password === '') {
            $this->respondJson(['success' => false, 'code' => 'customer_login_validation_required'], 422);

            return;
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->respondJson(['success' => false, 'code' => 'customer_login_validation_email'], 422);

            return;
        }

        $apiUrl = rtrim($this->getApiV2EntryPoint(), '/') . '/v2/customer/login';

        try {
            $client = new \GuzzleHttp\Client(['http_errors' => false, 'timeout' => 15]);
            $response = $client->request('POST', $apiUrl, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'email' => $email,
                    'password' => $password,
                    'company_id' => $payload['company_id'] ?? null,
                    'customer_id' => $payload['customer_id'] ?? null,
                ],
            ]);
            $status = $response->getStatusCode();
            $body = json_decode($response->getBody()->getContents(), true);
            if (! is_array($body)) {
                $this->respondJson(['success' => false, 'code' => 'customer_login_network_error'], 502);

                return;
            }

            if ($status === 429) {
                $this->respondJson(['success' => false, 'code' => 'customer_login_too_many_attempts'], 429);

                return;
            }

            if ($status >= 500 && empty($body['code'])) {
                $this->respondJson(['success' => false, 'code' => 'customer_login_server_error'], 502);

                return;
            }

            if (! empty($body['success']) && ! empty($body['data'])) {
                $d = $body['data'];
                $isCustomer = ! empty($d['is_customer']) || (($d['user_type'] ?? '') === 'customer');
                if (! $isCustomer) {
                    $this->respondJson(['success' => false, 'code' => 'customer_login_not_customer'], 403);

                    return;
                }
                $this->establishCustomerSession(
                    (int) $d['company_id'],
                    (int) $d['customer_id'],
                    (int) ($d['language_id'] ?? 1),
                    false
                );
                $this->syncPortalSanctumToken($email, $password);
                $this->assignPortalSessionCartToken((int) $d['customer_id'], (int) $d['company_id']);
                $uid = isset($d['user_id']) ? (int) $d['user_id'] : 0;
                if ($uid > 0) {
                    $this->setUserData('customer_portal_user_id', $uid);
                }
                $this->respondJson([
                    'success' => true,
                    'authenticated' => true,
                    'redirect' => $this->portalUriPath('customer/catalog'),
                ]);

                return;
            }

            $this->respondJson($body, $status >= 400 && $status < 600 ? $status : 400);
        } catch (\Throwable $e) {
            log_message('error', 'Customer_portal portalLoginJson: ' . $e->getMessage());
            $this->respondJson(['success' => false, 'code' => 'customer_login_network_error'], 502);
        }
    }

    /**
     * Path URI (p. ej. /customer/login) para peticiones y redirecciones desde el navegador sin esquema/host.
     */
    private function portalUriPath(string $segments): string
    {
        $path = parse_url(site_url($segments), PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            return '/' . ltrim($segments, '/');
        }

        return $path;
    }

    /**
     * Obtiene token Bearer vía GET v2/user/token (igual que ApiClient::getUserToken en login company).
     */
    private function syncPortalSanctumToken(string $email, string $password): void
    {
        $this->setUserAPIToken('');
        $this->removeUserData('token');

        $url = rtrim($this->getApiV2EntryPoint(), '/') . '/v2/user/token';
        try {
            $client = new \GuzzleHttp\Client(['http_errors' => false, 'timeout' => 15]);
            $response = $client->request('GET', $url, [
                'query' => [
                    'email' => $email,
                    'password' => $password,
                ],
            ]);
            if ($response->getStatusCode() !== 200) {
                return;
            }
            $token = trim((string) $response->getBody()->getContents());
            if ($token !== '') {
                $this->setUserAPIToken($token);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Customer_portal syncPortalSanctumToken: ' . $e->getMessage());
        }
    }

    private function establishCustomerSession(int $companyId, int $customerId, int $languageId, bool $withCartToken = true): void
    {
        $this->removeUserData(self::CUSTOMER_COMPANY_KEY);
        $this->removeUserData(self::CUSTOMER_ID_KEY);
        $this->removeUserData(self::CUSTOMER_LANG_KEY);
        $this->removeUserData(self::CUSTOMER_CART_TOKEN_KEY);

        $token_session = $withCartToken
            ? $this->ensurePortalSessionCartViaApi($customerId, $companyId)
            : substr((string) $this->Helper->get_token(), 0, 6);

        $this->flash('loader', true);
        $this->setUserData(self::CUSTOMER_COMPANY_KEY, $companyId);
        $this->setUserData(self::CUSTOMER_ID_KEY, $customerId);
        $this->setUserData(self::CUSTOMER_LANG_KEY, $languageId);
        $this->setUserData(self::CUSTOMER_CART_TOKEN_KEY, $token_session);
        // Contexto de app requerido por el stack existente para CMS y recursos compartidos.
        $this->setApplication('marketplace');
        $this->setUserData('role', 'customer');
        $this->setUserData('compId', $companyId);
    }

    /**
     * Token session_cart vía POST v2/customer/{id}/ensure-portal-session-cart.
     */
    private function ensurePortalSessionCartViaApi(int $customerId, int $companyId): string
    {
        if ($customerId <= 0) {
            return substr((string) $this->Helper->get_token(), 0, 6);
        }

        try {
            $res = $this->post('customer/' . $customerId . '/ensure-portal-session-cart', [], true);
            if (is_array($res) && isset($res['data']['session_token']) && $res['data']['session_token'] !== '') {
                return (string) $res['data']['session_token'];
            }
            if (is_object($res) && isset($res->data->session_token) && $res->data->session_token !== '') {
                return (string) $res->data->session_token;
            }
        } catch (\Throwable $e) {
            log_message('error', 'Customer_portal ensurePortalSessionCartViaApi: ' . $e->getMessage());
        }

        return substr((string) $this->Helper->get_token(), 0, 6);
    }

    private function assignPortalSessionCartToken(int $customerId, int $companyId): void
    {
        $token = $this->ensurePortalSessionCartViaApi($customerId, $companyId);
        if ($token !== '') {
            $this->setUserData(self::CUSTOMER_CART_TOKEN_KEY, $token);
        }
    }

    /**
     * Evita sesión mezclada con el portal vendedor (/sellers).
     */
    private function clearSellerPortalSession(): void
    {
        $this->removeUserData('seller_portal_company_id');
        $this->removeUserData('seller_portal_seller_id');
        $this->removeUserData('seller_portal_language_id');
        $this->removeUserData('seller_portal_user_id');
        $this->removeUserData('seller_portal_lang_id');
    }

    private function requireCustomerSession(): void
    {
        // Usar has_userdata: con company_id/customer_id en 0 el usuario sigue logueado (hasUserData podría abortar sesión).
        if (! $this->session->has_userdata(self::CUSTOMER_COMPANY_KEY) || ! $this->session->has_userdata(self::CUSTOMER_ID_KEY)) {
            redirect(site_url('customer/login'));
        }
    }

    private function getCustomerCompanyId(): int
    {
        // No usar getUserData(): hace session_abort() y rompe el resto del request (p. ej. basePortalData / idioma).
        return (int) ($this->session->userdata(self::CUSTOMER_COMPANY_KEY) ?? 0);
    }

    private function getPortalCompanyDisplayName(int $companyId): string
    {
        if ($companyId > 0) {
            try {
                $company = $this->getObjectFromApi('company/' . $companyId);
                if (is_object($company) && ! isset($company->error)) {
                    $name = trim((string) ($company->name ?? $company->company_name ?? ''));
                    if ($name !== '') {
                        return $name;
                    }
                } elseif (is_array($company) && ! isset($company['error'])) {
                    $name = trim((string) ($company['name'] ?? $company['company_name'] ?? ''));
                    if ($name !== '') {
                        return $name;
                    }
                }
            } catch (\Throwable $e) {
                log_message('debug', 'Customer_portal getPortalCompanyDisplayName: ' . $e->getMessage());
            }
        }

        return 'HOME VALUE LLC';
    }

    /**
     * Razón social y nombre de contacto del cliente para el encabezado del portal (API).
     *
     * @return array{business: string, contact: string}
     */
    private function getPortalCustomerDisplay(int $customerId): array
    {
        $business = '';
        $contact = '';
        if ($customerId <= 0) {
            return ['business' => '', 'contact' => ''];
        }

        try {
            $api = $this->getObjectFromApi('customer/' . $customerId);
            if (is_object($api) && ! isset($api->error)) {
                $business = trim((string) ($api->business_name ?? ''));
                $contact = trim((string) ($api->name ?? ''));
            } elseif (is_array($api) && ! isset($api['error'])) {
                $business = trim((string) ($api['business_name'] ?? ''));
                $contact = trim((string) ($api['name'] ?? ''));
            }
        } catch (\Throwable $e) {
            log_message('debug', 'Customer_portal getPortalCustomerDisplay: ' . $e->getMessage());
        }

        return ['business' => $business, 'contact' => $contact];
    }

    private function ensureCustomerCatalogToken(int $companyId, int $customerId): string
    {
        $this->load->model('Customer_portal_catalog_model');

        return $this->Customer_portal_catalog_model->ensure_catalog_token($companyId, $customerId);
    }

    protected function getCustomerId(): int
    {
        return (int) ($this->session->userdata(self::CUSTOMER_ID_KEY) ?? 0);
    }

    /**
     * @return array<string, mixed>
     */
    private function basePortalData(): array
    {
        $this->applyLanguageQueryToSession();
        $langId = $this->getPortalLanguageId();

        return [
            'pageTexts' => $this->loadPortalLangSection($langId),
            'lang_id' => $langId,
            'html_lang' => $this->portalHtmlLangCode($langId),
            'settings' => $this->getPlatformSettings(),
        ];
    }

    private function respondJson(array $data, int $status = 200): void
    {
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($data));
    }

    private function applyLanguageQueryToSession(): void
    {
        $lang = $this->input->get('lang');
        if (! is_string($lang) || $lang === '') {
            return;
        }
        $lang = strtolower($lang);
        $map = [
            'en' => 1,
            'es' => 2,
            'pt' => 3,
            'it' => 4,
            'ru' => 5,
            'de' => 6,
            'zh' => 7,
        ];
        if (isset($map[$lang])) {
            $this->session->set_userdata('customer_portal_lang_id', $map[$lang]);

            return;
        }
        if (ctype_digit($lang)) {
            $n = (int) $lang;
            if ($n >= 1 && $n <= 7) {
                $this->session->set_userdata('customer_portal_lang_id', $n);
            }
        }
    }

    /**
     * Tras login, el idioma de la UI sigue el language_id del cliente (API); antes, la preferencia de ?lang= en sesión.
     * Alineado con Company_Controller::langJsonFilenameForLanguageId / LanguageSeeder.
     */
    private function getPortalLanguageId(): int
    {
        // No usar "&&" sobre valores: 0 es válido y debe considerarse sesión de portal activa.
        $loggedIn = $this->session->has_userdata(self::CUSTOMER_COMPANY_KEY)
            && $this->session->has_userdata(self::CUSTOMER_ID_KEY);
        if ($loggedIn) {
            $lm = (int) ($this->session->userdata(self::CUSTOMER_LANG_KEY) ?? 0);
            if ($lm >= 1 && $lm <= 7) {
                return $lm;
            }
        }
        $id = (int) ($this->session->userdata('customer_portal_lang_id') ?? 0);
        if ($id >= 1 && $id <= 7) {
            return $id;
        }

        return 1;
    }

    private function langJsonFilenameForLanguageId(int $languageId): string
    {
        static $map = [
            1 => 'en-EN.json',
            2 => 'es-ES.json',
            3 => 'pt-PT.json',
            4 => 'it-IT.json',
            5 => 'ru-RU.json',
            6 => 'de-DE.json',
            7 => 'zh-CN.json',
        ];

        return $map[$languageId] ?? 'en-EN.json';
    }

    private function portalHtmlLangCode(int $languageId): string
    {
        static $map = [
            1 => 'en',
            2 => 'es',
            3 => 'pt',
            4 => 'it',
            5 => 'ru',
            6 => 'de',
            7 => 'zh-CN',
        ];

        return $map[$languageId] ?? 'en';
    }

    /**
     * Rutas absolutas a api/lang/{archivo}.json (mismo criterio que Company_Controller + FCPATH).
     *
     * @return list<string>
     */
    private function candidatePortalLangJsonPaths(string $file): array
    {
        $paths = [];
        $seen = [];

        $add = static function (string $path) use (&$paths, &$seen): void {
            $norm = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
            if (! is_readable($norm) || ! is_file($norm)) {
                return;
            }
            $resolved = realpath($norm);
            $canonical = $resolved !== false ? $resolved : $norm;
            if (isset($seen[$canonical])) {
                return;
            }
            $seen[$canonical] = true;
            $paths[] = $canonical;
        };

        $custom = $this->config->item('customer_portal_lang_path');
        if (is_string($custom) && $custom !== '') {
            $add(rtrim($custom, '/\\') . DIRECTORY_SEPARATOR . $file);
        }

        // Front controller en mvc/: carpeta del proyecto = padre de FCPATH
        if (defined('FCPATH')) {
            $parentOfFront = realpath(rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . '..');
            if ($parentOfFront !== false) {
                $add($parentOfFront . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $file);
            }
            // Si realpath falla (open_basedir), probar ruta literal
            $add(rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $file);
        }

        $appTrim = rtrim(APPPATH, '/\\');
        $repoRoot = dirname(dirname($appTrim));
        $add($repoRoot . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $file);

        if (PHP_VERSION_ID >= 70000) {
            $add(dirname(APPPATH, 2) . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $file);
        }

        // BASEPATH apunta a .../system/ → hermano mvc + ../api/lang (útil si FCPATH es public/ u otra raíz)
        if (defined('BASEPATH')) {
            $mvcDir = dirname(rtrim(BASEPATH, '/\\'));
            $add($mvcDir . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $file);
        }

        // Último recurso: JSON embebidos en MVC (despliegues sin carpeta api junto al servidor web)
        $add(rtrim(APPPATH, '/\\') . DIRECTORY_SEPARATOR . 'language' . DIRECTORY_SEPARATOR . 'customer_portal' . DIRECTORY_SEPARATOR . $file);

        return $paths;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadPortalLangSection(int $languageId): array
    {
        $tryIds = array_values(array_unique([(int) $languageId, 1]));
        foreach ($tryIds as $lid) {
            $section = $this->loadPortalLangSectionFromFile($lid);
            if ($section !== []) {
                return $section;
            }
        }

        log_message('error', 'Customer_portal: cmp-customer-portal no disponible (language_id=' . $languageId . ')');

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function loadPortalLangSectionFromFile(int $languageId): array
    {
        $file = $this->langJsonFilenameForLanguageId($languageId);

        foreach ($this->candidatePortalLangJsonPaths($file) as $path) {
            $raw = @file_get_contents($path);
            if ($raw === false || $raw === '') {
                continue;
            }
            if (strncmp($raw, "\xEF\xBB\xBF", 3) === 0) {
                $raw = substr($raw, 3);
            }
            $data = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                log_message('error', 'Customer_portal json_decode failed for ' . $path . ': ' . json_last_error_msg());

                continue;
            }
            if (is_array($data) && isset($data['cmp-customer-portal']) && is_array($data['cmp-customer-portal'])) {
                return $data['cmp-customer-portal'];
            }
        }

        return [];
    }
}

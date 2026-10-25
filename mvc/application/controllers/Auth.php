<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @property CI_Form_validation $form_validation *
 * @property CI_Session $session
 * @property CI_Input $input
 */
class Auth extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    protected function processLogin(): array
    {
        $data = ['page' => 'login'];

        if ($this->form_validation->run() !== false) {
            $credentials['email'] = $this->input->post('username');
            $credentials['password'] = $this->input->post('password');

            $response = $this->post('login', $credentials);

            if (isset($response['user'])) {
                log_message('debug', "User Loged in: " . json_encode($response['user']));
                $this->clearAdminPlatformSessionKeys();
                $sessionBatch = [
                    'user' => $response['user'],
                    'app_access' => $response['app_access'] ?? [],
                ];
                if (! empty($response['user']['token'])) {
                    $sessionBatch['token'] = $response['user']['token'];
                    $this->userToken = $response['user']['token'];
                }
                $this->setUserDataBatch($sessionBatch);
            } elseif (isset($response['error'])) {
                $errorText = $this->extractLoginErrorMessage($response);
                if ($errorText !== '') {
                    $data['msg'] = $this->getAuthAlert($errorText, 'danger');
                }
            }
        } else {
            $msg = '';
            if (form_error('username')) {
                $msg .= strip_tags(form_error('username'));
            }
            if (form_error('password')) {
                $msg .= strip_tags(form_error('password'));
            }
            $data['msg'] = $this->getAuthAlert($msg, 'danger');
        }

        return $data;
    }

    public function index()
    {
        if (function_exists('fv_sync_session_language_from_request')) {
            fv_sync_session_language_from_request();
        }

        // GET /login: acceso empresa/admin (vista auth/login). Portal cliente: /customer/login.
        $this->form_validation->set_rules('username', 'Email', 'trim|required');
        $this->form_validation->set_rules('password', 'Password', 'trim|required');

        $this->applyApplicationFromRedirectTo();

        $data = ['page' => 'login'];
        if ($this->input->post('username')) {
            $data = $this->processLogin();
            $this->applyApplicationFromRedirectTo();
        }

        $application = $this->getApplication();
        $data['application'] = $application;

        if ($this->loggedIn()) {
            if ($application === null || $application === '') {
                $data['applications'] = $this->getApps();
            } else {
                $selectedCompany = $this->getCompany();
                $companyIdFromSelection = $this->resolveCompanyIdFromSelection($selectedCompany);
                $companyReady = $application === 'admin' || $companyIdFromSelection > 0;

                if ($companyReady) {
                    $userLang = $this->resolveLoginUserLanguageId();
                    if ($application === 'company') {
                        $companyId = $companyIdFromSelection;
                        if ($companyId > 0) {
                            $this->load->library('company_plan_validator');
                            $companyLangId = $this->resolveCompanyLanguageIdFromApi($companyId);
                            $msgs = $this->company_plan_validator->loadLoginLangMessages($companyLangId);
                            $planError = $this->company_plan_validator->validateForLogin($companyId, $msgs);
                            if ($planError !== '') {
                                $this->doLogOut();
                                $safe = nl2br(htmlspecialchars($planError, ENT_QUOTES, 'UTF-8'));
                                $data['msg'] = $this->getAuthAlert($safe, 'danger');
                                if ($this->isLoginAjaxRequest()) {
                                    $this->respondJsonLogin([
                                        'success' => false,
                                        'message' => strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $safe)),
                                    ], 422);
                                }
                            } elseif ($this->isLoginAjaxRequest()) {
                                $this->respondCompanyLoginSuccess($selectedCompany, $companyLangId);
                            } else {
                                $this->redirectToApplication($selectedCompany, $companyLangId);
                            }
                        }
                    } else {
                        $this->redirectToApplication($selectedCompany, $userLang);
                    }
                }

                if (empty($data['msg'])) {
                    $data['companies'] = $this->getCompanies($application);
                }
            }
        }

        // Mostrar mensaje de registro exitoso solo si viene del registro
        if ($this->input->get('registered') == '1') {
            $data['msg'] = $this->getAuthAlert('¡Registro exitoso! Por favor, inicie sesión con sus credenciales.', 'success');
        } else {
            // Mostrar otros mensajes flash solo si no hay mensaje de registro
            $flash = $this->getFlashMessage();
            if (empty($data['msg'] ?? '') && !$this->loggedIn() && empty($data['companies'])) {
                // El panel empresa guarda en `msg` cadenas JSON para SweetAlert (alert_func en footer), no HTML.
                // No mostrarlas en /login: se verían como texto crudo; además no aplican fuera del panel.
                if (! empty($flash) && ! $this->isSwalStyleFlashPayload($flash)) {
                    $data['msg'] = $flash;
                }
            }
        }

        $data['settings'] = $this->getPlatformSettings();
        $currentLangId = $this->resolveLoginCurrentLangId();
        $codeByLang = [1 => 'en', 2 => 'es', 3 => 'pt', 4 => 'it', 5 => 'ru', 6 => 'de', 7 => 'zh'];
        $currentLocale = $codeByLang[$currentLangId] ?? 'en';
        // Server-side cargamos sólo el bloque del idioma actual (1 request al API). Los otros
        // 6 idiomas se piden via AJAX al endpoint público langmdl si el usuario cambia el selector.
        $currentBlock = $this->fetchCompanyLoginSection($currentLocale);
        $data['login_page_texts'] = $currentBlock + $this->companyLoginDefaults();
        $data['login_translations_by_lang'] = [$currentLangId => $data['login_page_texts']];
        $data['login_current_lang_id'] = $currentLangId;
        $data['url_customer_portal'] = site_url('customer');
        $data['url_seller_portal'] = site_url('sellers');

        if ($this->isLoginAjaxRequest() && $this->input->post('username')) {
            if ($this->loggedIn()) {
                if (! empty($data['companies']) || ! empty($data['applications'])) {
                    $this->respondJsonLogin(['success' => false, 'requiresSelection' => true]);
                }
            } elseif (! empty($data['msg'])) {
                $this->respondJsonLogin([
                    'success' => false,
                    'message' => $this->extractPlainMessageFromAuthAlert($data['msg']),
                ], 422);
            } else {
                $this->respondJsonLogin([
                    'success' => false,
                    'message' => $this->companyLoginDefaults()['loading_text'],
                ], 422);
            }
        }

        $this->load->view('auth/login', $data);
    }

    /**
     * Defaults usados cuando la API no respondió o falta una clave.
     *
     * @return array<string, string>
     */
    private function companyLoginDefaults(): array
    {
        return [
            'title' => 'Login Panel',
            'welcomemsg' => 'Hello!',
            'msg1' => 'Sign in to continue',
            'emailph' => 'Email',
            'passph' => 'Password',
            'okbntext' => 'Sign in',
            'checktext' => 'Remember me',
            'forgottext' => 'Forgot your password?',
            'dont_have_account' => "Don't have an account?",
            'sign_up_link' => 'Sign up',
            'language_label' => 'Language',
            'loading_text' => 'Signing in...',
            'entry_customer_site' => 'Customer',
            'entry_seller_site' => 'Seller',
            'entry_portal_nav_label' => 'Customer and seller access',
            'panel_label' => 'Business management panel',
            'hero_title_html' => 'Manage your business <em>in one place</em>',
            'hero_lead' => 'Control products, orders, customers, billing, inventory and much more with FullVendor Company.',
            'feat_products' => 'Product and inventory management',
            'feat_orders' => 'Orders and sales',
            'feat_customers' => 'Customers and companies',
            'feat_reports' => 'Reports and analytics',
            'feat_marketplace' => 'Sell on the Marketplace',
            'feat_settings' => 'Advanced settings',
            'or_continue' => 'or continue with',
            'social_google' => 'Continue with Google',
            'social_microsoft' => 'Continue with Microsoft',
            'social_soon' => 'Coming soon',
            'splash_welcome' => 'Welcome back!',
            'splash_loading' => 'Loading your workspace...',
            'splash_preparing' => 'We are getting everything ready for you',
            'promo_1_title' => 'Smart catalogs',
            'promo_1_text' => 'Organize products and share catalogs with your sales team in seconds.',
            'promo_2_title' => 'Orders under control',
            'promo_2_text' => 'Track orders, customers and sellers from a single panel.',
            'promo_3_title' => 'Inventory & warehouses',
            'promo_3_text' => 'Manage stock across multiple warehouses with real-time updates.',
            'promo_4_title' => 'Insights that matter',
            'promo_4_text' => 'Dashboards and reports to make better business decisions.',
        ];
    }

    /**
     * Llama al endpoint público /api/v2/langmdl/company-login?locale={code} y devuelve el bloque.
     * Si falla, devuelve []. El Langmodel ya entiende sólo en|es; aquí forzamos cualquier locale
     * soportado por la API (pt|it|ru|de|zh) usando getDataFromApi directamente.
     *
     * @return array<string, string>
     */
    private function fetchCompanyLoginSection(string $locale): array
    {
        try {
            $resp = $this->getDataFromApi('langmdl/company-login', ['locale' => $locale]);
        } catch (\Throwable $e) {
            log_message('error', 'Auth.fetchCompanyLoginSection: ' . $e->getMessage());

            return [];
        }
        if ($resp === null || $resp === '') {
            return [];
        }
        $data = is_array($resp) ? $resp : json_decode((string) $resp, true);
        if (! is_array($data)) {
            return [];
        }
        $clean = [];
        foreach ($data as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $clean[(string) $key] = (string) ($value ?? '');
            }
        }

        return $clean;
    }

    /**
     * Determina el language_id inicial al pintar /login.
     * Orden: ?lang= → sesión MVC → 1 (en).
     */
    private function resolveLoginCurrentLangId(): int
    {
        $codeToId = [
            'en' => 1, 'es' => 2, 'pt' => 3, 'it' => 4, 'ru' => 5, 'de' => 6, 'zh' => 7,
        ];

        $get = (string) ($this->input->get('lang') ?? '');
        if ($get !== '') {
            $code = strtolower(substr($get, 0, 2));
            if (isset($codeToId[$code])) {
                return $codeToId[$code];
            }
        }

        foreach (['language_id', 'languageid', 'lang_id'] as $key) {
            $val = $this->session->userdata($key);
            if ($val !== null && $val !== '' && is_numeric($val)) {
                $id = (int) $val;
                if ($id >= 1 && $id <= 7) {
                    return $id;
                }
            }
        }

        return 1;
    }

    /**
     * Textos del login empresa desde api/lang/*.json (bloque company-login), según ?lang= (en|es|pt|it|ru|de|zh).
     *
     * @return array<string, string>
     */
    /**
     * Detecta payloads de flash pensados para SweetAlert (company/footer): ["texto", "success|error", "#hex"].
     * No deben mostrarse en la vista auth/login, que espera HTML de getAuthAlert().
     */
    private function isSwalStyleFlashPayload(string $flash): bool
    {
        $t = trim($flash);
        if ($t === '' || ($t[0] ?? '') !== '[') {
            return false;
        }
        $decoded = json_decode($t, true);
        if (! is_array($decoded) || ! isset($decoded[0], $decoded[1])) {
            return false;
        }
        if (! is_string($decoded[0]) || ! is_string($decoded[1])) {
            return false;
        }

        return in_array($decoded[1], ['success', 'error', 'warning', 'info'], true);
    }

    private function companyLoginPortalTexts(): array
    {
        $supported = ['en', 'es', 'pt', 'it', 'ru', 'de', 'zh'];
        $lang = strtolower(substr((string) $this->input->get('lang'), 0, 2));
        if (! in_array($lang, $supported, true)) {
            $lang = 'en';
        }
        $block = $this->fetchCompanyLoginSection($lang);
        $defaults = [
            'entry_customer_site' => 'Customer site',
            'entry_seller_site' => 'Seller site',
            'entry_portal_nav_label' => 'Customer and seller access',
        ];
        if ($block === []) {
            return $defaults;
        }

        return $block + $defaults;
    }


    public function logout()
    {
        $msg = $this->getAuthAlert("You have successfully logout!", 'success');
        $this->doLogOut();

        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $this->flashMessage($msg);

        redirect(base_url('login'), 'refresh');
    }

    public function selectApplication(): void
    {
        $this->setApplication($this->input->post('application'));
        redirect($this->loginUrlWithRedirectTo(), 'refresh');
    }

    public function selectCompany(): void
    {
        $companyId = $this->input->post('company_id');
        $company = json_decode($this->input->post('company_data'));

        $this->setCompany($company);

        redirect($this->loginUrlWithRedirectTo(), 'refresh');
    }

    /**
     * Si viene ?redirectto= (p. ej. desde /company), fija la aplicación en sesión
     * para completar el login sin volver a pedir credenciales en bucle.
     */
    private function applyApplicationFromRedirectTo(): void
    {
        $redirectto = $this->input->get('redirectto');
        if ($redirectto === null || $redirectto === '') {
            $redirectto = $this->input->post('redirectto');
        }
        if ($redirectto === null || $redirectto === '') {
            return;
        }

        $current = $this->getUserData('application');
        if ($current !== null && $current !== '') {
            return;
        }

        $app = $this->getApplicationFromUrl(urldecode((string) $redirectto));
        $map = [
            'admin' => 'admin',
            'company' => 'company',
            'salesman' => 'salesman',
            'sellers' => 'salesman',
            'marketplace' => 'marketplace',
            'customer' => 'marketplace',
        ];
        if (isset($map[$app])) {
            $this->setApplication($map[$app]);
        }
    }

    /**
     * @return string URL de /login conservando ?redirectto= y ?lang=
     */
    private function loginUrlWithRedirectTo(): string
    {
        $query = [];
        $redirectto = $this->input->get('redirectto');
        if ($redirectto === null || $redirectto === '') {
            $redirectto = $this->input->post('redirectto');
        }
        if ($redirectto !== null && $redirectto !== '') {
            $query['redirectto'] = (string) $redirectto;
        }
        $lang = $this->input->get('lang');
        if ($lang !== null && $lang !== '') {
            $query['lang'] = (string) $lang;
        }

        return base_url('login') . ($query !== [] ? '?' . http_build_query($query) : '');
    }

    /**
     * Idioma del usuario tras login sin llamada API extra (evita 401 que vaciaba la sesión).
     */
    private function resolveLoginUserLanguageId(): int
    {
        if (function_exists('fv_resolve_session_language_id')) {
            $fromRequest = fv_resolve_session_language_id();
            $getLang = (string) ($this->input->get('lang') ?? '');
            $postLang = (string) ($this->input->post('lang') ?? '');
            if ($getLang !== '' || $postLang !== '') {
                return $fromRequest;
            }
        }

        $user = $this->getUserData('user');
        if (is_array($user) && isset($user['language_id'])) {
            $id = (int) $user['language_id'];
            if ($id >= 1 && $id <= 7) {
                return $id;
            }
        }
        if (is_object($user) && isset($user->language_id)) {
            $id = (int) $user->language_id;
            if ($id >= 1 && $id <= 7) {
                return $id;
            }
        }

        return 1;
    }

    /**
     * @param array<string, mixed> $response
     */
    private function extractLoginErrorMessage(array $response): string
    {
        if (! isset($response['message'])) {
            return '';
        }
        $payload = $response['message'];
        if (is_string($payload)) {
            return $payload;
        }
        if (! is_array($payload)) {
            return '';
        }
        if (isset($payload['message']) && is_string($payload['message'])) {
            return $payload['message'];
        }
        if (isset($payload['errors']) && is_array($payload['errors'])) {
            foreach ($payload['errors'] as $fieldErrors) {
                if (is_array($fieldErrors) && isset($fieldErrors[0]) && is_string($fieldErrors[0])) {
                    return $fieldErrors[0];
                }
                if (is_string($fieldErrors)) {
                    return $fieldErrors;
                }
            }
        }

        return '';
    }

    private function isLoginAjaxRequest(): bool
    {
        if ($this->input->is_ajax_request()) {
            return true;
        }
        $xhr = strtolower((string) $this->input->server('HTTP_X_REQUESTED_WITH'));

        return $xhr === 'xmlhttprequest';
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function respondJsonLogin(array $payload, int $status = 200): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        $this->output
            ->set_status_header($status)
            ->set_content_type('application/json', 'utf-8')
            ->set_output(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->output->_display();
        exit;
    }

    private function extractPlainMessageFromAuthAlert(string $html): string
    {
        $text = html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html)), ENT_QUOTES, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', str_replace("\n", ' ', $text)) ?? '');

        return $text !== '' ? $text : $this->companyLoginDefaults()['loading_text'];
    }

    private function respondCompanyLoginSuccess($selectedCompany, int $companyLangId): void
    {
        $result = $this->prepareApplicationRedirect($selectedCompany, $companyLangId);
        if (! $result['sessionReady'] || $result['url'] === null) {
            $this->respondJsonLogin(['success' => false, 'requiresSelection' => true]);
            return;
        }

        $codeByLang = [1 => 'en', 2 => 'es', 3 => 'pt', 4 => 'it', 5 => 'ru', 6 => 'de', 7 => 'zh'];
        $locale = $codeByLang[$companyLangId] ?? 'en';
        $texts = $this->fetchCompanyLoginSection($locale) + $this->companyLoginDefaults();

        $redirect = (string) $result['url'];
        $redirectParts = parse_url($redirect);
        if (is_array($redirectParts) && ! empty($redirectParts['path'])) {
            $redirect = $redirectParts['path']
                . (isset($redirectParts['query']) ? '?' . $redirectParts['query'] : '');
        }

        $this->respondJsonLogin([
            'success' => true,
            'redirect' => $redirect,
            'langId' => $companyLangId,
            'texts' => $texts,
        ]);
    }


    public
    function forgot_password()
    {
        if ($this->hasUserData('marketplace_user') &&
            $this->hasUserData('marketplace_customer_id')) {
            return redirect(marketplace_url(), 'refresh');
        }

        $this->removeUserData('marketplace_user');
        $this->removeUserData('marketplace_customer_id');

        $data = array('page' => 'login');
        $request = $this->input->get();
        $langid = isset($request['len']) && $request['len'] == 'en' ? 1 /*english*/ : 2 /*español*/
        ;
        $langid = !isset($request['len']) ? 1 : $langid;

        $lang_loginseller = $this->langmodel->get_langsinceLogin('comp-login-seller', $langid);


        $data['msg'] = $this->getFlashMessage();


        $data['settings'] = $this->getPlatformSettings();
        $data['langdef'] = $this->langmodel->get_langsinceLogin('company-login', $langid);
        $data['lang_validate'] = $this->langmodel->get_langsinceLogin('validate-user', $langid);
        $data['langid'] = $langid;
        $data['language'] = isset($request['len']) ? $request['len'] : 'en';
        $data['lang_login'] = $lang_loginseller;
        $this->load->view('marketplace/forgot_password', $data);
    }

    /**
     * Generates an alert message with the given text loading a bootstrap alert div
     * @param string $message the message text
     * @param string $alertType success|warning|danger|info
     * @param bool $return Whether to return the view output or leave it to the Output class
     * @return object|string
     */
    protected function getAuthAlert($message, $alertType = 'success', $return = true)
    {
        return $this->load->view('auth/alert_message', [
            'msg' => $message,
            'alertType' => $alertType
        ], $return);
    }

    /**
     * Muestra el formulario de registro o procesa el registro
     */
    public function signup()
    {
        if ($this->loggedIn()) {
            redirect(base_url('login'), 'refresh');
        }

        // Si hay datos POST, procesar el registro
        if ($this->input->server('REQUEST_METHOD') === 'POST' && $this->input->post('company_name')) {
            $this->processRegister();
            return;
        }

        // Si no hay POST, mostrar el formulario
        $data = ['page' => 'signup'];
        $data['settings'] = $this->getPlatformSettings();
        $this->load->view('auth/signup', $data);
    }

    /**
     * Redirige a /signup (ruta oficial para registro)
     */
    public function register()
    {
        redirect(base_url('signup'), 'refresh');
    }

    /**
     * Procesa el registro de una nueva compañía (método privado, llamado desde signup)
     */
    private function processRegister()
    {
        $data = ['page' => 'signup'];
        $data['settings'] = $this->getPlatformSettings();

        // Validación de campos (solo compañía, sin usuario)
        $this->form_validation->set_rules('company_name', 'Company Name', 'trim|required|max_length[255]');
        $this->form_validation->set_rules('company_email', 'Company Email', 'trim|required|valid_email|max_length[255]');
        $this->form_validation->set_rules('language_id', 'Language', 'trim|required|in_list[1,2]');
        $this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[8]');
        $this->form_validation->set_rules('password_confirmation', 'Password Confirmation', 'trim|required|matches[password]');

        if ($this->form_validation->run() === false) {
            $msg = '';
            if (form_error('company_name')) {
                $msg .= strip_tags(form_error('company_name')) . '<br>';
            }
            if (form_error('company_email')) {
                $msg .= strip_tags(form_error('company_email')) . '<br>';
            }
            if (form_error('language_id')) {
                $msg .= strip_tags(form_error('language_id')) . '<br>';
            }
            if (form_error('password')) {
                $msg .= strip_tags(form_error('password')) . '<br>';
            }
            if (form_error('password_confirmation')) {
                $msg .= strip_tags(form_error('password_confirmation')) . '<br>';
            }
            $data['msg'] = $this->getAuthAlert($msg, 'danger');
            $this->load->view('auth/signup', $data);
            return;
        }

        // Preparar datos para la API (solo compañía, sin usuario)
        $registerData = [
            'company' => [
                'name' => $this->input->post('company_name'),
                'email' => $this->input->post('company_email'),
                'phone_number' => $this->input->post('company_phone') ?: null,
                'tax_id' => $this->input->post('company_tax_id') ?: null,
                'website' => $this->input->post('company_website') ?: null,
                'password' => $this->input->post('password'),
                'language_id' => $this->input->post('language_id'),
            ]
        ];

        // Llamar al endpoint de registro público (sin autenticación)
        try {
            // Obtener API_SERVER del .env, intentando múltiples métodos
            $apiServer = null;
            if (isset($_ENV['API_SERVER']) && !empty($_ENV['API_SERVER'])) {
                $apiServer = $_ENV['API_SERVER'];
            } elseif (getenv('API_SERVER') !== false) {
                $apiServer = getenv('API_SERVER');
            } elseif (isset($_SERVER['API_SERVER']) && !empty($_SERVER['API_SERVER'])) {
                $apiServer = $_SERVER['API_SERVER'];
            }
            
            // Si no se encontró, usar valor por defecto o la constante si está definida
            if (empty($apiServer)) {
                $apiServer = defined('API_SERVER') ? API_SERVER : "localhost";
            }
            
            $apiEndpoint = "http://{$apiServer}/api/v2/register";
            
            $http = new \GuzzleHttp\Client();
            $response = $http->request(
                'POST',
                $apiEndpoint,
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $registerData
                ]
            );
            $responseBody = json_decode($response->getBody()->getContents(), true);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $errorResponse = $e->getResponse();
            $responseBody = json_decode($errorResponse->getBody()->getContents(), true);
            if (!$responseBody) {
                $responseBody = ['error' => true, 'message' => ['message' => 'Error de conexión con el servidor']];
            }
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            $errorResponse = $e->getResponse();
            $responseBody = json_decode($errorResponse->getBody()->getContents(), true);
            if (!$responseBody) {
                $responseBody = ['error' => true, 'message' => ['message' => 'Error del servidor']];
            }
        } catch (\Exception $e) {
            log_message('error', 'Error en registro: ' . $e->getMessage());
            $responseBody = ['error' => true, 'message' => ['message' => 'Error al registrar: ' . $e->getMessage()]];
        }
        
        $response = $responseBody;

        // Verificar si hay error en la respuesta
        if (isset($response['error']) || (isset($response['message']) && !isset($response['data']))) {
            $errorMsg = '';
            
            // Extraer mensajes de error de validación
            if (isset($response['message']['errors']) && is_array($response['message']['errors'])) {
                $errors = $response['message']['errors'];
                $errorList = [];
                foreach ($errors as $field => $fieldErrors) {
                    if (is_array($fieldErrors)) {
                        foreach ($fieldErrors as $error) {
                            // Traducir nombres de campos
                            $fieldName = str_replace(['company.', '_'], ['', ' '], $field);
                            $fieldName = str_replace(['name', 'email', 'password', 'language id', 'phone number', 'tax id', 'website'], 
                                ['Nombre de compañía', 'Email', 'Contraseña', 'Idioma', 'Teléfono', 'Tax ID', 'Sitio web'], $fieldName);
                            $errorList[] = '<strong>' . ucfirst($fieldName) . ':</strong> ' . $error;
                        }
                    } else {
                        $fieldName = str_replace(['company.', '_'], ['', ' '], $field);
                        $fieldName = str_replace(['name', 'email', 'password', 'language id', 'phone number', 'tax id', 'website'], 
                            ['Nombre de compañía', 'Email', 'Contraseña', 'Idioma', 'Teléfono', 'Tax ID', 'Sitio web'], $fieldName);
                        $errorList[] = '<strong>' . ucfirst($fieldName) . ':</strong> ' . $fieldErrors;
                    }
                }
                if (!empty($errorList)) {
                    $errorMsg = 'Por favor, corrija los siguientes errores:<br><br>' . implode('<br>', $errorList);
                } else {
                    $errorMsg = $response['message']['message'] ?? 'Por favor, verifique los datos ingresados.';
                }
            } elseif (isset($response['message']['message'])) {
                $errorMsg = $response['message']['message'];
            } elseif (is_string($response['message'])) {
                $errorMsg = $response['message'];
            } else {
                $errorMsg = 'Error al registrar. Por favor, intente nuevamente.';
            }
            
            $data['msg'] = $this->getAuthAlert($errorMsg, 'danger');
            $this->load->view('auth/signup', $data);
            return;
        }

        // Verificar si el registro fue exitoso
        if (!isset($response['data']) && !isset($response['message'])) {
            $data['msg'] = $this->getAuthAlert('Error desconocido al registrar. Por favor, intente nuevamente.', 'danger');
            $this->load->view('auth/signup', $data);
            return;
        }

        // Registro exitoso - redirigir con parámetro para mostrar mensaje
        redirect(base_url('login?registered=1'), 'refresh');
    }

}


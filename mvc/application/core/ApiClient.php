<?php
// Cargar helper de entorno si está disponible
if (file_exists(APPPATH . 'helpers/env_helper.php')) {
    require_once APPPATH . 'helpers/env_helper.php';
    if (function_exists('load_env_file')) {
        load_env_file();
    }
}

// Función helper para obtener el servidor de la API
if (!function_exists('get_api_server')) {
    function get_api_server() {
        // Verificar si hay un override global
        if (isset($GLOBALS['API_SERVER_OVERRIDE'])) {
            return $GLOBALS['API_SERVER_OVERRIDE'];
        }
        
        // Verificar si la constante está definida y no es "laravel.test"
        if (defined('API_SERVER') && API_SERVER !== 'laravel.test') {
            return API_SERVER;
        }
        
        // Obtener API_SERVER del .env, intentando múltiples métodos
        $apiServer = null;
        if (isset($_ENV['API_SERVER']) && !empty($_ENV['API_SERVER'])) {
            $apiServer = $_ENV['API_SERVER'];
        } elseif (getenv('API_SERVER') !== false && getenv('API_SERVER') !== '') {
            $apiServer = getenv('API_SERVER');
        } elseif (isset($_SERVER['API_SERVER']) && !empty($_SERVER['API_SERVER'])) {
            $apiServer = $_SERVER['API_SERVER'];
        }
        
        // Si no se encontró o es "laravel.test", usar "localhost"
        if (empty($apiServer) || $apiServer === 'laravel.test') {
            $apiServer = 'localhost';
        }
        
        // Definir la constante si no está definida o tiene el valor antiguo
        if (!defined('API_SERVER') || (defined('API_SERVER') && API_SERVER === 'laravel.test')) {
            if (!defined('API_SERVER')) {
                define('API_SERVER', $apiServer);
            } else {
                // Guardar en global para override
                $GLOBALS['API_SERVER_OVERRIDE'] = $apiServer;
            }
        }
        
        return $apiServer;
    }
}

// Obtener y definir API_SERVER
$apiServer = get_api_server();
if (!defined('API_SERVER')) {
    define('API_SERVER', $apiServer);
} elseif (API_SERVER === 'laravel.test') {
    // Si ya está definida con el valor antiguo, usar el override
    $GLOBALS['API_SERVER_OVERRIDE'] = $apiServer;
}


trait ApiClient
{
    protected function getApiServer() {
        return get_api_server();
    }

    /**
     * Devuelve el código de idioma corto (en|es|pt|it|ru|de|zh) que debe enviarse a la API
     * en el header Accept-Language para que las traducciones (Fortify auth.failed, etc.)
     * se resuelvan en el idioma del usuario. Orden:
     *   1. Sesión MVC: language_id, languageid, lang_id (1=en, 2=es; resto soportado por código).
     *   2. ?lang= o input «lang» del request (login antes de la sesión).
     *   3. HTTP_ACCEPT_LANGUAGE del navegador.
     *   4. 'en' por defecto.
     */
    protected function resolveApiAcceptLanguage(): string
    {
        $supported = ['en', 'es', 'pt', 'it', 'ru', 'de', 'zh'];
        $CI = function_exists('get_instance') ? get_instance() : null;

        // 1. Sesión (CI_Session::userdata es public; evita problemas de visibilidad
        //    al usar el trait desde MY_Model, MY_Controller, IsCRUD, etc.).
        if (is_object($CI) && isset($CI->session) && is_object($CI->session) && method_exists($CI->session, 'userdata')) {
            foreach (['language_id', 'languageid', 'lang_id'] as $key) {
                try {
                    $value = $CI->session->userdata($key);
                } catch (\Throwable $e) {
                    $value = null;
                }
                if ($value === null || $value === '' || $value === false) {
                    continue;
                }
                if (is_numeric($value)) {
                    $id = (int) $value;
                    $map = [1 => 'en', 2 => 'es', 3 => 'pt', 4 => 'it', 5 => 'ru', 6 => 'de', 7 => 'zh'];

                    return $map[$id] ?? 'en';
                }
                $code = strtolower(substr((string) $value, 0, 2));
                if (in_array($code, $supported, true)) {
                    return $code;
                }
            }
        }

        // 2. Parametro lang del request (login antes de la sesión: /login?lang=en).
        if (is_object($CI) && isset($CI->input) && is_object($CI->input)) {
            $requestLang = method_exists($CI->input, 'get') ? $CI->input->get('lang') : null;
            if (empty($requestLang) && method_exists($CI->input, 'post')) {
                $requestLang = $CI->input->post('lang');
            }
            if (! empty($requestLang)) {
                $code = strtolower(substr((string) $requestLang, 0, 2));
                if (in_array($code, $supported, true)) {
                    return $code;
                }
            }
        }

        // 3. Accept-Language del navegador.
        $browser = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? (string) $_SERVER['HTTP_ACCEPT_LANGUAGE'] : '';
        if ($browser !== '') {
            $first = trim(explode(',', $browser)[0] ?? '');
            $first = trim(explode(';', $first)[0] ?? '');
            $code = strtolower(substr($first, 0, 2));
            if (in_array($code, $supported, true)) {
                return $code;
            }
        }

        return 'en';
    }

    /**
     * Inyecta Accept-Language en el array de headers Guzzle si no está ya presente.
     *
     * @param array<string, string> $headers
     * @return array<string, string>
     */
    protected function withAcceptLanguageHeader(array $headers): array
    {
        $hasHeader = false;
        foreach (array_keys($headers) as $name) {
            if (strcasecmp((string) $name, 'Accept-Language') === 0) {
                $hasHeader = true;
                break;
            }
        }
        if (! $hasHeader) {
            $headers['Accept-Language'] = $this->resolveApiAcceptLanguage();
        }

        return $headers;
    }
    
    protected function getApiV2EntryPoint() {
        // URL directa al proceso Laravel (otro PHP). Evita que Guzzle vuelva a entrar
        // por el ProxyPass del mismo Apache: con la sesión bloqueada se agotan los workers.
        $internal = getenv('API_INTERNAL_BASE_URL');
        if (!is_string($internal) || trim($internal) === '') {
            $internal = $_ENV['API_INTERNAL_BASE_URL'] ?? '';
        }
        if (is_string($internal) && trim($internal) !== '') {
            return rtrim(trim($internal), '/') . '/api';
        }

        // Llamadas server-side (Guzzle): preferir laravel.test dentro de Docker.
        // Si se usa localhost/api, el ProxyPass reentra al mismo Apache del MVC y,
        // con la sesión bloqueada, agota workers → cURL timeout 30s / 0 bytes.
        static $directLaravel = null;
        if ($directLaravel === null) {
            $directLaravel = false;
            $resolved = @gethostbyname('laravel.test');
            if (is_string($resolved) && $resolved !== '' && $resolved !== 'laravel.test') {
                $directLaravel = true;
            }
        }
        if ($directLaravel) {
            return 'http://laravel.test/api';
        }

        $server = $this->getApiServer();
        return "http://".$server."/api";
    }
    
    // Usar método en lugar de propiedad para obtener siempre el valor correcto
    protected function API_V2_ENTRYPOINT() {
        return $this->getApiV2EntryPoint();
    }

    /**
     * Si la API responde 401 pero la sesión MVC aún tiene usuario, Auth redirige otra vez al dashboard → bucle.
     * Cerrar sesión antes de mandar al login deja un estado coherente.
     */
    protected function clearAuthSessionAfterApi401(): void
    {
        if ($this->shouldSkipAuthSessionClearForImageUpload()) {
            return;
        }
        if (method_exists($this, 'doLogOut')) {
            try {
                $this->doLogOut();
            } catch (\Throwable $e) {
                log_message('error', 'clearAuthSessionAfterApi401: ' . $e->getMessage());
            }
        }
    }

    /**
     * Las subidas paralelas no deben cerrar la sesión MVC ante un 401 puntual de la API.
     */
    protected function shouldSkipAuthSessionClearForImageUpload(): bool
    {
        $CI = &get_instance();
        if (! is_object($CI) || ! isset($CI->router) || ! is_object($CI->router)) {
            return false;
        }
        if (! isset($CI->input) || ! is_object($CI->input) || ! method_exists($CI->input, 'is_ajax_request') || ! $CI->input->is_ajax_request()) {
            return false;
        }
        $method = strtolower((string) ($CI->router->method ?? ''));

        return $method === 'uploadimage' || (defined('FV_STATELESS_IMAGE_UPLOAD') && FV_STATELESS_IMAGE_UPLOAD);
    }

    /**
     * Secreto para firmar el refresh del token: MVC_TOKEN_REFRESH_SECRET, o derivado del APP_KEY de Laravel
     * (mismo algoritmo que la API) leyendo api/.env en monorepo o variable LARAVEL_APP_KEY.
     */
    protected function getMvcTokenRefreshSecret(): string
    {
        if (isset($_ENV['MVC_TOKEN_REFRESH_SECRET']) && $_ENV['MVC_TOKEN_REFRESH_SECRET'] !== '') {
            return (string) $_ENV['MVC_TOKEN_REFRESH_SECRET'];
        }
        $g = getenv('MVC_TOKEN_REFRESH_SECRET');
        if ($g !== false && $g !== '') {
            return (string) $g;
        }
        if (isset($_SERVER['MVC_TOKEN_REFRESH_SECRET']) && $_SERVER['MVC_TOKEN_REFRESH_SECRET'] !== '') {
            return (string) $_SERVER['MVC_TOKEN_REFRESH_SECRET'];
        }

        $appKey = $this->readLaravelAppKeyForMvcRefresh();
        if ($appKey !== '') {
            return hash_hmac('sha256', 'fullvendor-mvc-token-refresh', $appKey);
        }

        return '';
    }

    /**
     * Misma clave que usa config('app.key') en Laravel (p. ej. base64:...).
     */
    protected function readLaravelAppKeyForMvcRefresh(): string
    {
        foreach (['LARAVEL_APP_KEY', 'APP_KEY'] as $envName) {
            if (isset($_ENV[$envName]) && $_ENV[$envName] !== '') {
                return (string) $_ENV[$envName];
            }
            $v = getenv($envName);
            if ($v !== false && $v !== '') {
                return (string) $v;
            }
            if (isset($_SERVER[$envName]) && $_SERVER[$envName] !== '') {
                return (string) $_SERVER[$envName];
            }
        }

        if (! defined('APPPATH')) {
            return '';
        }

        $path = dirname(APPPATH, 2) . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . '.env';
        if (! is_readable($path)) {
            return '';
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return '';
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }
            if (preg_match('/^APP_KEY\s*=\s*(.*)$/', $line, $m)) {
                $value = trim($m[1], " \t\"'");

                return $value;
            }
        }

        return '';
    }

    /**
     * Lee un valor de sesión MVC sin invocar getUserData() protected del controlador.
     *
     * @return mixed
     */
    protected function readMvcSessionUserdata(string $key)
    {
        if ($this instanceof CI_Controller && method_exists($this, 'getUserData')) {
            return $this->getUserData($key);
        }

        if (isset($this->session) && is_object($this->session)) {
            return $this->session->userdata($key);
        }

        $CI = function_exists('get_instance') ? get_instance() : null;
        if (is_object($CI) && isset($CI->session) && is_object($CI->session)) {
            return $CI->session->userdata($key);
        }

        return null;
    }

    /**
     * Renueva el token Sanctum "mvc" vía endpoint público firmado; actualiza sesión con setUserAPIToken.
     */
    protected function attemptMvcTokenRefresh(): bool
    {
        $secret = $this->getMvcTokenRefreshSecret();
        if ($secret === '') {
            return false;
        }

        $userId = 0;
        $email = '';
        $user = $this->readMvcSessionUserdata('user');
        if (is_array($user)) {
            if (isset($user['id'])) {
                $userId = (int) $user['id'];
            }
            if (! empty($user['email'])) {
                $email = strtolower(trim((string) $user['email']));
            }
        }
        if ($userId < 1) {
            $uid = $this->readMvcSessionUserdata('userId');
            if ($uid !== null && $uid !== '') {
                $userId = (int) $uid;
            }
        }
        if ($email === '') {
            $em = $this->readMvcSessionUserdata('email');
            if (! empty($em)) {
                $email = strtolower(trim((string) $em));
            }
        }

        if ($userId < 1 && $email === '') {
            return false;
        }

        $ts = time();
        $payload = $userId . '|' . $email . '|' . $ts;
        $sig = hash_hmac('sha256', $payload, $secret);

        try {
            $url = $this->API_V2_ENTRYPOINT() . '/' . $this->apiVersion . '/auth/refresh-mvc-token';
            $resp = $this->getHttpClient()->request('POST', $url, [
                'headers' => $this->withAcceptLanguageHeader([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ]),
                'json' => [
                    'user_id' => $userId,
                    'email' => $email,
                    'ts' => $ts,
                    'sig' => $sig,
                ],
                'http_errors' => false,
            ]);
            $code = $resp->getStatusCode();
            $body = json_decode((string) $resp->getBody()->getContents(), true);
            if ($code === 200 && is_array($body) && ! empty($body['token']) && is_string($body['token'])) {
                $this->setUserAPIToken($body['token']);

                return true;
            }
        } catch (\Throwable $e) {
            log_message('error', 'attemptMvcTokenRefresh: ' . $e->getMessage());
        }

        return false;
    }

    /** @var GuzzleHttp\Client */
    protected $http;

    protected $userToken;

    protected $apiVersion='v2';

    /**
     * Token Sanctum desde la instancia actual, el controlador MVC o la sesión.
     * Los modelos (p. ej. DashboardModel) no tienen getUserData() propio.
     */
    protected function resolveApiTokenFromMvcContext(): string
    {
        if (isset($this->userToken) && $this->userToken !== null && $this->userToken !== '') {
            return (string) $this->userToken;
        }

        if (method_exists($this, 'resolveImageUploadApiToken')) {
            $fromRequest = $this->resolveImageUploadApiToken();
            if ($fromRequest !== '') {
                return $fromRequest;
            }
        }

        if (method_exists($this, 'getUserData')) {
            $token = $this->getUserData('token');
            if ($token !== null && $token !== '') {
                return (string) $token;
            }
        }

        if (isset($this->session) && is_object($this->session)) {
            $token = $this->session->userdata('token');
            if ($token !== null && $token !== '') {
                return (string) $token;
            }
        }

        $CI = function_exists('get_instance') ? get_instance() : null;
        if (! is_object($CI)) {
            return '';
        }

        if (property_exists($CI, 'userToken') && ! empty($CI->userToken)) {
            return (string) $CI->userToken;
        }

        if (isset($CI->session) && is_object($CI->session)) {
            $token = $CI->session->userdata('token');
            if ($token !== null && $token !== '') {
                return (string) $token;
            }

            $user = $CI->session->userdata('user');
            if (is_array($user) && ! empty($user['token'])) {
                return (string) $user['token'];
            }
            if (is_object($user) && ! empty($user->token)) {
                return (string) $user->token;
            }
        }

        return '';
    }

    /**
     * Comprueba sesión MVC sin invocar métodos protected del controlador desde modelos.
     */
    protected function isMvcUserLoggedIn(): bool
    {
        $CI = function_exists('get_instance') ? get_instance() : null;
        if (! is_object($CI)) {
            return false;
        }

        if (method_exists($CI, 'isUserLoggedIn')) {
            return (bool) $CI->isUserLoggedIn();
        }

        if (isset($CI->session) && is_object($CI->session)) {
            $user = $CI->session->userdata('user');

            return $user !== null && $user !== '' && $user !== false;
        }

        return false;
    }

    /**
     * @return mixed
     */
    public function getUserToken($userName=null,$password=null)
    {
        if (! isset($this->userToken) || $this->userToken === null || $this->userToken === '') {
            $resolved = $this->resolveApiTokenFromMvcContext();
            if ($resolved !== '') {
                $this->userToken = $resolved;
            }
        }
        if(!isset($this->userToken) && isset($userName,$password)) {
            try {
                $this->userToken = $this->getHttpClient()->get(
                    $this->API_V2_ENTRYPOINT() . "/{$this->apiVersion}/user/token",
                    [
                        "query" => ['email' => $userName, 'password' => $password],
                        "headers" => $this->withAcceptLanguageHeader(['Accept' => 'application/json']),
                    ]
                )->getBody()->getContents();
                log_message('error',"Got token is: $this->userToken");
            }
            catch (\GuzzleHttp\Exception\GuzzleException $e) {
                $this->userToken = null;
                if(method_exists($this,'flashMessage')) $this->flashMessage($e->getMessage());
                log_message('error',"Didn't get token for $userName pwd: $password " . $e->getMessage());
            }
        }
        return $this->userToken;
    }

    /**
     * @param mixed $userToken
     */
    public function setUserAPIToken($userToken): void
    {
        $this->userToken = $userToken;
        if ($this->shouldSkipAuthSessionClearForImageUpload()) {
            return;
        }
        if (method_exists($this, 'setUserData')) {
            $this->setUserData('token', $userToken);
        } else {
            $CI = &get_instance();
            if (is_object($CI) && method_exists($CI, 'setUserData')) {
                $CI->setUserData('token', $userToken);
            }
        }
    }


    /**
     * @return \GuzzleHttp\Client
     */
    protected function getHttpClient()
    {
        if(!isset($this->http))
            $this->http = new \GuzzleHttp\Client([
                'timeout' => 30.0, // Timeout de 30 segundos para la respuesta
                'connect_timeout' => 10.0, // Timeout de 10 segundos para la conexión
            ]);
        return $this->http;
    }

    /**
     * Sube un archivo a la API de imágenes (MinIO) y devuelve el valor a guardar en companies.image (uuid.ext).
     * En controladores bajo {@see Company_Controller}, injectParams añade company_id al multipart.
     *
     * @return string|null uuid.extension o null si falla
     */
    protected function uploadCompanyImageToMinioApi(int $companyId, string $localPath, string $originalFileName): ?string
    {
        if ($companyId < 1 || $localPath === '' || ! is_readable($localPath)) {
            return null;
        }
        $fileName = basename($originalFileName);
        if ($fileName === '') {
            $fileName = 'image.png';
        }
        $multipart = [
            [
                'name' => 'contents',
                'contents' => fopen($localPath, 'rb'),
                'filename' => $fileName,
            ],
            [
                'name' => 'name',
                'contents' => $fileName,
            ],
            [
                'name' => 'type',
                'contents' => 'company',
            ],
        ];
        if (! ($this instanceof Company_Controller)) {
            $multipart[] = [
                'name' => 'company_id',
                'contents' => (string) $companyId,
            ];
        }

        $resp = $this->apiRequest('images', 'POST', ['multipart' => $multipart], true);
        if (! is_array($resp) || ! empty($resp['error']) || empty($resp['uuid'])) {
            log_message('error', 'uploadCompanyImageToMinioApi failed: ' . json_encode($resp));

            return null;
        }
        $ext = isset($resp['extension']) ? strtolower((string) $resp['extension']) : '';
        if ($ext === '') {
            $ext = 'png';
        }

        return (string) $resp['uuid'] . '.' . $ext;
    }


    /**
     * @param string $resource
     * @param $params
     * @param string $method
     * @param array $options
     * @return array|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function apiRawRequest(string $resource, $params, string $method= 'GET', array $options = []) {
        try {
            $daToken = $this->getUserToken();
            $headers = array_merge([
                'Accept' => '*/*',
                'Authorization' => 'Bearer ' . $daToken,
            ], $options['headers'] ?? []);
            unset($options['headers']);
            $options['headers'] = $this->withAcceptLanguageHeader($headers);

            if (strtoupper($method) === 'GET') {
                $options['query'] = array_merge($options['query'] ?? [], (array) $params);
            } else {
                $options['json'] = $params;
            }

            return $this->getHttpClient()->request(
                strtoupper($method),
                "{$this->API_V2_ENTRYPOINT()}/{$this->apiVersion}/{$resource}",
                $options
            );

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // Errores 4xx
            log_message('error', "ClientException: " . $e->getMessage());
            return ['error' => 'Client error', 'message' => $e->getResponse()->getBody()->getContents()];

        } catch (\GuzzleHttp\Exception\ServerException $e) {
            // Errores 5xx
            log_message('error', "ServerException: " . $e->getMessage());
            return ['error' => 'Server error', 'message' => $e->getResponse()->getBody()->getContents()];

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            log_message('error', "RequestException: " . $e->getMessage());
            return ['error' => 'Request error', 'message' => $e->getMessage()];

        } catch (\Exception $e) {
            log_message('error', "General Exception: " . $e->getMessage());
            return ['error' => 'Unknown error', 'message' => $e->getMessage()];
        }
    }


    /**
     * @param string $resource El recurso o endpoint al que se realiza la solicitud.
     * @param array $options Opciones adicionales para la solicitud.
     * @param string $method El método HTTP (GET, POST, PUT, DELETE, etc.).
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    function apiRequest(string $resource, string $method, array $options = [], bool $associative = true)
    {
        try {
            $mvcTokenRetry = ! empty($options['_mvc_token_retry']);
            unset($options['_mvc_token_retry']);

            $daToken = $this->getUserToken();
            
            // Solo incluir Authorization si hay un token válido (evita "Bearer " vacío)
            $headers = ['Accept' => 'application/json'];
            if (!empty($daToken)) {
                $headers['Authorization'] = 'Bearer ' . $daToken;
            }
            $options['headers'] = $this->withAcceptLanguageHeader($headers);

            log_message('debug', "Token " . ($daToken ?: '(vacío - catálogo público)'));

            if(method_exists($this,'injectParams')) {
                $this->injectParams($options,strtolower($method)==='get'?'query':'json');
            }

            $response = $this->getHttpClient()->request(
                strtoupper($method),
                "{$this->API_V2_ENTRYPOINT()}/{$this->apiVersion}/{$resource}",
                $options
            );
            if ($response->getStatusCode() === 204)
                return ['message' => 'success'];
            if (in_array($response->getStatusCode(), [200, 201], true)) {
                return json_decode($response->getBody()->getContents(), $associative);
            }

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // Errores 4xx
            $responseBody = $e->getResponse()->getBody()->getContents();
            $decodedResponse = json_decode($responseBody, true);
            $statusCode = $e->getResponse()->getStatusCode();
            log_message('error', "ClientException: " . $e->getMessage());
            log_message('error', "Response body: " . $responseBody);

            $msg = $decodedResponse !== null && is_array($decodedResponse) ? ($decodedResponse['message'] ?? null) : null;
            $isUnauthenticated = ($statusCode === 401) || ($msg === 'Unauthenticated.');

            if ($isUnauthenticated) {
                $CI = &get_instance();
                $isLoginPage = isset($CI->router) && is_object($CI->router)
                    && strtolower($CI->router->class ?? '') === 'auth'
                    && in_array(strtolower($CI->router->method ?? 'index'), ['index', 'signup', 'forgot_password', 'register'], true);
                if ($isLoginPage) {
                    $messagePayload = is_array($decodedResponse) ? $decodedResponse : ['message' => $responseBody, 'errors' => []];
                    return ['error' => 'Client error', 'message' => $messagePayload];
                }
                
                // No redirigir a login para rutas del catálogo público (catalogo/producto)
                $isCatalogPublic = isset($CI->router) && is_object($CI->router)
                    && strtolower($CI->router->directory ?? '') === 'catalogo/'
                    && strtolower($CI->router->class ?? '') === 'producto';
                if ($isCatalogPublic) {
                    $messagePayload = is_array($decodedResponse) ? $decodedResponse : ['message' => $responseBody, 'errors' => []];
                    return ['error' => 'Client error', 'message' => $messagePayload];
                }

                // Sin Bearer: el 401 es por petición anónima a ruta protegida, no por token revocado.
                // Cerrar sesión MVC aquí deslogueaba al usuario aunque otra pestaña o el modelo fuese sin token.
                if ($daToken === null || $daToken === '') {
                    $loggedIn = $this->isMvcUserLoggedIn();
                    if ($loggedIn && ! $mvcTokenRetry && $this->attemptMvcTokenRefresh()) {
                        $this->userToken = null;
                        $retryOpts = $options;
                        $retryOpts['_mvc_token_retry'] = true;

                        return $this->apiRequest($resource, $method, $retryOpts, $associative);
                    }
                    $messagePayload = is_array($decodedResponse) ? $decodedResponse : ['message' => $responseBody, 'errors' => []];
                    return ['error' => 'Client error', 'message' => $messagePayload];
                }

                if (! $mvcTokenRetry && ! empty($daToken) && $this->attemptMvcTokenRefresh()) {
                    $this->userToken = null;
                    $retryOpts = $options;
                    $retryOpts['_mvc_token_retry'] = true;

                    return $this->apiRequest($resource, $method, $retryOpts, $associative);
                }

                $this->clearAuthSessionAfterApi401();

                $loginUrl = function_exists('platform_login_url')
                    ? platform_login_url()
                    : (function_exists('base_url') ? base_url('login') : (rtrim($CI->config->base_url(), '/') . '/login'));
                $isAjax = isset($CI->input) && is_object($CI->input) && method_exists($CI->input, 'is_ajax_request') && $CI->input->is_ajax_request();
                if ($isAjax) {
                    header('Content-Type: application/json');
                    http_response_code(401);
                    echo json_encode(['message' => 'Unauthenticated.', 'redirect' => $loginUrl]);
                    exit;
                }
                if (function_exists('redirect')) {
                    redirect($loginUrl, 'refresh');
                } else {
                    header('Location: ' . $loginUrl);
                    exit;
                }
            }

            // Devolver siempre array en 'message' para que el código que lo use no falle con stdClass
            $messagePayload = (is_array($decodedResponse) ? $decodedResponse : []) + ['message' => $responseBody, 'errors' => []];
            return ['error' => 'Client error', 'message' => $messagePayload];

        } catch (\GuzzleHttp\Exception\ServerException $e) {
            $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : '';
            $decodedResponse = json_decode($responseBody, true);
            log_message('error', 'ServerException: '.$e->getMessage());
            log_message('error', 'Response body: '.$responseBody);

            if (is_array($decodedResponse)) {
                return array_merge(['error' => 'Server error'], $decodedResponse);
            }

            return ['error' => 'Server error', 'message' => $responseBody !== '' ? $responseBody : $e->getMessage()];

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $err = $e->getMessage();
            log_message('error', "RequestException: " . $err);
            if (empty($options['_http_retry']) && preg_match('/SSL|certificate|cURL error 60|cURL error 35/i', $err)) {
                $httpsBase = $this->API_V2_ENTRYPOINT();
                if (stripos($httpsBase, 'https://') === 0) {
                    $retryOptions = $options;
                    unset($retryOptions['_http_retry']);
                    $options['_http_retry'] = true;
                    try {
                        $response = $this->getHttpClient()->request(
                            strtoupper($method),
                            'http://' . substr($httpsBase, 8) . '/' . $this->apiVersion . '/' . $resource,
                            $retryOptions
                        );
                        if ($response->getStatusCode() === 204) {
                            return ['message' => 'success'];
                        }
                        if (in_array($response->getStatusCode(), [200, 201], true)) {
                            return json_decode($response->getBody()->getContents(), $associative);
                        }
                    } catch (\Throwable $retry) {
                        log_message('error', 'RequestException http retry: ' . $retry->getMessage());
                    }
                }
            }
            return ['error' => 'Request error', 'message' => $err];

        } catch (\Exception $e) {
            log_message('error', "General Exception: " . $e->getMessage());
            return ['error' => 'Unknown error', 'message' => $e->getMessage()];
        }
    }

    function apitextRequest(string $resource, string $method, array $options = [])
    {
        try {
            $mvcTokenRetry = ! empty($options['_mvc_token_retry']);
            unset($options['_mvc_token_retry']);

            $daToken = $this->getUserToken();
            
            // Solo incluir Authorization si hay un token válido (evita "Bearer " vacío)
            $headers = ['Accept' => 'application/json'];
            if (!empty($daToken)) {
                $headers['Authorization'] = 'Bearer ' . $daToken;
            }
            $options['headers'] = $this->withAcceptLanguageHeader($headers);

            log_message('debug', "Token " . ($daToken ?: '(vacío - catálogo público)'));

            if(method_exists($this,'injectParams')) {
                $this->injectParams($options,strtolower($method)==='get'?'query':'json');
            }

            $response = $this->getHttpClient()->request(
                strtoupper($method),
                "{$this->API_V2_ENTRYPOINT()}/{$this->apiVersion}/{$resource}",
                $options
            );
            if($response->getStatusCode() === 204)
                return ['message' => 'success'];
            if($response->getStatusCode() === 200)
                return $response->getBody()->getContents();

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            // Errores 4xx
            $responseBody = $e->getResponse()->getBody()->getContents();
            $decodedResponse = json_decode($responseBody, true);
            $statusCode = $e->getResponse()->getStatusCode();
            log_message('error', "ClientException: " . $e->getMessage());
            log_message('error', "Response body: " . $responseBody);
            $isUnauthenticated = ($statusCode === 401)
                || ($decodedResponse !== null && isset($decodedResponse['message']) && $decodedResponse['message'] === 'Unauthenticated.');
            if ($isUnauthenticated) {
                $CI = &get_instance();
                $isLoginPage = isset($CI->router) && is_object($CI->router)
                    && strtolower($CI->router->class ?? '') === 'auth'
                    && in_array(strtolower($CI->router->method ?? 'index'), ['index', 'signup', 'forgot_password', 'register'], true);
                if ($isLoginPage) {
                    return $decodedResponse !== null ? $decodedResponse : ['message' => $responseBody];
                }
                
                // No redirigir a login para rutas del catálogo público (catalogo/producto)
                $isCatalogPublic = isset($CI->router) && is_object($CI->router)
                    && strtolower($CI->router->directory ?? '') === 'catalogo/'
                    && strtolower($CI->router->class ?? '') === 'producto';
                if ($isCatalogPublic) {
                    return $decodedResponse !== null ? $decodedResponse : ['message' => $responseBody];
                }

                if ($daToken === null || $daToken === '') {
                    $loggedIn = $this->isMvcUserLoggedIn();
                    if ($loggedIn && ! $mvcTokenRetry && $this->attemptMvcTokenRefresh()) {
                        $this->userToken = null;
                        $retryOpts = $options;
                        $retryOpts['_mvc_token_retry'] = true;

                        return $this->apitextRequest($resource, $method, $retryOpts);
                    }
                    return $decodedResponse !== null ? $decodedResponse : ['message' => $responseBody];
                }

                if (! $mvcTokenRetry && ! empty($daToken) && $this->attemptMvcTokenRefresh()) {
                    $this->userToken = null;
                    $retryOpts = $options;
                    $retryOpts['_mvc_token_retry'] = true;

                    return $this->apitextRequest($resource, $method, $retryOpts);
                }

                $this->clearAuthSessionAfterApi401();

                $loginUrl = function_exists('platform_login_url')
                    ? platform_login_url()
                    : (function_exists('base_url') ? base_url('login') : (rtrim($CI->config->base_url(), '/') . '/login'));
                $isAjax = isset($CI->input) && is_object($CI->input) && method_exists($CI->input, 'is_ajax_request') && $CI->input->is_ajax_request();
                if ($isAjax) {
                    header('Content-Type: application/json');
                    http_response_code(401);
                    echo json_encode(['message' => 'Unauthenticated.', 'redirect' => $loginUrl]);
                    exit;
                }
                if (function_exists('redirect')) {
                    redirect($loginUrl, 'refresh');
                } else {
                    header('Location: ' . $loginUrl);
                    exit;
                }
            }
            return ['error' => 'Client error', 'message' => $decodedResponse !== null ? $decodedResponse : ['message' => $responseBody, 'errors' => []]];

        } catch (\GuzzleHttp\Exception\ServerException $e) {
            $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : '';
            $decodedResponse = json_decode($responseBody, true);
            log_message('error', 'ServerException: '.$e->getMessage());
            log_message('error', 'Response body: '.$responseBody);

            if (is_array($decodedResponse)) {
                return array_merge(['error' => 'Server error'], $decodedResponse);
            }

            return ['error' => 'Server error', 'message' => $responseBody !== '' ? $responseBody : $e->getMessage()];

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            log_message('error', "RequestException: " . $e->getMessage());
            return ['error' => 'Request error', 'message' => json_decode($e->getMessage(), true)];

        } catch (\Exception $e) {
            log_message('error', "General Exception: " . $e->getMessage());
            return ['error' => 'Unknown error', 'message' => json_decode($e->getMessage(), true)];
        }
    }

    public function getObjectFromApi($resource,$params = []) {
        return $this->getDataFromApi($resource,$params,false);
    }

    /**
     * GET de rutas catalog-public como array (getObjectFromApi devuelve stdClass).
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>|null
     */
    protected function getCatalogPublicFromApi(string $resource, array $params = []): ?array
    {
        $res = $this->getDataFromApi($resource, $params, true);

        return is_array($res) ? $res : null;
    }

    /**
     * @param string $resource
     * @param array $params
     * @param true $associativeResponse
     * @return array|object
     * @throws \GuzzleHttp\Exception\GuzzleException}
     */
    public function getDataFromApi($resource, $params = [], $associativeResponse = true)
    {
        $options = [
            'query'=>$params
        ];
        return $this->apiRequest($resource,'GET',$options, $associativeResponse);
    }

    public function getRawFromApi($resource, $params = [])
    {
        $options = [
            'query'=>$params
        ];
        return $this->apitextRequest($resource,'GET',$options);
    }

    /**
     * @param string $resource
     * @param array $params
     * @param bool $associativeResponse
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException }
     */
    function post($resource, $params = [],$associativeResponse=true)
    {
        $options = [
            'json'=>$params
        ];
        return $this->apiRequest($resource,'POST',$options, $associativeResponse);
    }

    public function multipartPost($resource, $params = [], $associativeResponse = true)
    {
        $multipart = [];

        foreach ($params as $name => $value) {
            if ($value instanceof \CURLFile) {
                $multipart[] = [
                    'name'     => $name,
                    'contents' => fopen($value->getFilename(), 'r'),
                    'filename' => $value->getPostFilename(),
                ];
            } else {
                $multipart[] = [
                    'name'     => $name,
                    'contents' => $value,
                ];
            }
        }

        $options = [
            'multipart' => $multipart,
            // Subidas (normalización Imagick) pueden superar el timeout global de 30s.
            'timeout' => 120.0,
            'connect_timeout' => 15.0,
        ];
        
        // Llamar a injectParams ANTES de apiRequest para que agregue company_id
        if(method_exists($this,'injectParams')) {
            $this->injectParams($options, 'multipart');
        }

        return $this->apiRequest($resource, 'POST', $options, $associativeResponse);
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    function put($resource, $params = [], $associativeResponse=true)
    {
        $options = [
            'json'=>$params
        ];
        return $this->apiRequest($resource,'PUT',$options, $associativeResponse);
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    function patch($resource, $params = [], $associativeResponse=true)
    {
        $options = [
            'json'=>$params
        ];
        return $this->apiRequest($resource,'PATCH',$options, $associativeResponse);
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    function apiDelete($resource, $params = [], $associativeResponse=true)
    {
        $options = [
            'json'=>$params
        ];
        return $this->apiRequest($resource,'DELETE',$options, $associativeResponse);
    }

    function hasError($data): bool {
        if (is_array($data)) {
            return isset($data['error']);
        } elseif (is_object($data)) {
            return isset($data->error);
        }
        return false;
    }

    /**
     * @param mixed $resp
     */
    protected function resolveApiErrorMessage($resp): string
    {
        if (! is_array($resp)) {
            return '';
        }

        $message = $resp['message'] ?? null;
        if (is_string($message) && trim($message) !== '') {
            return trim($message);
        }
        if (is_array($message)) {
            if (isset($message['message']) && is_string($message['message']) && trim($message['message']) !== '') {
                return trim($message['message']);
            }
            if (isset($message['errors']) && is_array($message['errors'])) {
                $parts = [];
                foreach ($message['errors'] as $field => $errors) {
                    if (is_array($errors)) {
                        foreach ($errors as $err) {
                            $parts[] = (string) $field . ': ' . (string) $err;
                        }
                    } else {
                        $parts[] = (string) $field . ': ' . (string) $errors;
                    }
                }
                if ($parts !== []) {
                    return implode(' ', $parts);
                }
            }
            $encoded = json_encode($message, JSON_UNESCAPED_UNICODE);

            return is_string($encoded) ? $encoded : '';
        }

        if (isset($resp['error']) && is_string($resp['error'])) {
            return trim($resp['error']);
        }

        return '';
    }

    /**
     * Filas de un listado API: con `all=true` suele venir un array JSON plano;
     * con paginación, `{ "data": [ ... ] }`.
     *
     * @param mixed $resp
     * @return list<array<string, mixed>>
     */
    protected function extractApiListRows($resp): array
    {
        if (! is_array($resp) || $this->hasError($resp)) {
            return [];
        }
        if (isset($resp['data']) && is_array($resp['data'])) {
            return $resp['data'];
        }
        if ($resp !== [] && array_keys($resp) === range(0, count($resp) - 1)) {
            return $resp;
        }

        return [];
    }
}

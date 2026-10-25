<?php
defined('BASEPATH') or exit('No direct script access allowed');

spl_autoload_register(function ($class) {
    if (file_exists(APPPATH . "core/$class.php")) {
        require_once APPPATH . "core/$class.php";
    }
}
);


/**
 * @property CI_Session $session
 * @property CI_URI $uri
 * @property Helper $Helper
 * @property Mymodel $mymodel
 * @property CI_Email $email
 * @property Usermodel $usermodel
 * @property HistoryStatusOrderModel $HistoryStatusOrderModel
 * @property OrdenModel $OrdenModel
 * @property Langmodel $langmodel
 * @property HelperLanguage $HelperLanguage
 * @property CompanyModel $CompanyModel
 * @property Commonmodel $Commonmodel
 * @property CI_Pagination $pagination
 * @property CI_Benchmark $benchmark
 */
class MY_Controller extends CI_Controller
{

    use ApiClient;
    use HasAuth;

    public $favicon;
    public $logo;
    public $cms;
    public $msg;
    /** @var array */
    protected $jsFiles;

    public function __construct()
    {
        parent::__construct();
        $this->benchmark->mark('my-controller-construct');
        $this->jsFiles = []; // Inicializar como array vacío
        $this->getHttpClient();
        $this->lang->load('date', 'english');
        $this->ensureLangmodelLoaded();
    }

    protected function ensureLangmodelLoaded(): void
    {
        if (! isset($this->langmodel)) {
            $this->load->model('langmodel');
        }
    }

    /**
     * Branding global de la plataforma (favicon, logo, título) vía API pública.
     */
    public function getPlatformSettings(): object
    {
        $cached = $this->cachedApiValue('platform_branding', 300, function () {
            return $this->getObjectFromApi('setting/public/branding');
        });
        $settings = $cached;
        if (is_object($settings) && ! isset($settings->error)) {
            return $settings;
        }

        return (object) [
            'favicon' => null,
            'logo' => null,
            'title' => '',
            'facebook' => null,
            'twitter' => null,
            'linkedin' => null,
            'instagram' => null,
            'address' => null,
            'phone' => null,
            'email' => null,
        ];
    }

    public function getApplicationFromUrl($url = null)
    {
        $urlParts = explode('/', str_ireplace(array('http://', 'https://'), '', $url));
        $appPath = $urlParts[1];
        return $appPath;
    }

    /**
     * Ajusta redirectto al mismo esquema (http/https) que base_url de esta petición.
     * Evita perder la cookie de sesión (secure) al saltar de HTTPS a HTTP en el redirect.
     *
     * @param string|null $url URL absoluta o relativa guardada en ?redirectto=
     * @param string $fallback URL si $url es inválida o de otro host
     */
    protected function normalizeInternalRedirectUrl(?string $url, string $fallback): string
    {
        if ($url === null || trim($url) === '') {
            return $fallback;
        }

        $decoded = urldecode(trim($url));
        $base = rtrim((string) $this->config->item('base_url'), '/');
        $baseParts = parse_url($base);
        if ($baseParts === false || empty($baseParts['host'])) {
            return $fallback;
        }

        $targetParts = parse_url($decoded);
        if ($targetParts === false || empty($targetParts['host'])) {
            if ($decoded !== '' && $decoded[0] === '/') {
                return $base . $decoded;
            }

            return $fallback;
        }

        if (strcasecmp((string) $targetParts['host'], (string) $baseParts['host']) !== 0) {
            return $fallback;
        }

        $scheme = isset($baseParts['scheme']) ? strtolower((string) $baseParts['scheme']) : 'http';
        $out = $scheme . '://' . $targetParts['host'];
        if (! empty($targetParts['port'])) {
            $out .= ':' . $targetParts['port'];
        }
        $out .= $targetParts['path'] ?? '/';
        if (! empty($targetParts['query'])) {
            $out .= '?' . $targetParts['query'];
        }
        if (! empty($targetParts['fragment'])) {
            $out .= '#' . $targetParts['fragment'];
        }

        return $out;
    }

	/**
	 * Sesión del panel admin (/admin): Fortify + tabla admin en API (no el mismo objeto que Auth /login).
	 */
	protected function isAdminPlatformLoggedIn(): bool
	{
		if (! $this->hasUserData('userId') || ! $this->hasUserData('admin')) {
			return false;
		}

		$adminUserId = (int) $this->getUserData('userId');
		if ($adminUserId < 1) {
			return false;
		}

		// Evita mezclar sesión unificada (/login) con flags admin antiguos y token de otro usuario.
		$user = $this->getUserData('user');
		if (is_array($user) && isset($user['id']) && (int) $user['id'] !== $adminUserId) {
			return false;
		}

		$appAccess = $this->getUserData('app_access');
		if (is_array($appAccess) && array_key_exists('admin', $appAccess) && empty($appAccess['admin']['access'])) {
			return false;
		}

		return true;
	}

	/**
	 * Limpia claves de sesión del panel admin al iniciar sesión unificada (/login).
	 */
	protected function clearAdminPlatformSessionKeys(): void
	{
		foreach (['admin', 'userId', 'abilities'] as $key) {
			if ($this->hasUserData($key)) {
				$this->removeUserData($key);
			}
		}
	}

	/**
	 * Limpia claves de sesión del login unificado al entrar por /admin/login.
	 */
	protected function clearUnifiedLoginSessionKeys(): void
	{
		foreach ([
			'user',
			'app_access',
			'compId',
			'role',
			'application',
			'company',
			'company_seller',
			'marketplace_user',
			'marketplace_customer_id',
			'customer_id',
			'sellerId',
			'user_logueado',
			'company_panel_language_id',
			'company_panel_language_comp_id',
		] as $key) {
			if ($this->hasUserData($key)) {
				$this->removeUserData($key);
			}
		}
	}

	/**
	 * La petición actual es un controlador bajo application/controllers/admin/
	 * (evita mandar al login unificado /login).
	 */
	protected function currentRequestIsAdminArea(): bool
	{
		$dir = rtrim((string) $this->router->directory, '/');
		if ($dir !== '' && strcasecmp($dir, 'admin') === 0) {
			return true;
		}
		$path = trim((string) $this->uri->uri_string(), '/');

		return $path === 'admin' || strpos($path, 'admin/') === 0;
	}

	public function checkLoggedInOrLoginWithRedirect()
	{
		if ($this->currentRequestIsAdminArea()) {
			if (function_exists('admin_sync_session_language_from_request')) {
				admin_sync_session_language_from_request();
			}
			if ($this->isAdminPlatformLoggedIn()) {
				return;
			}
			$loginClass = strtolower((string) ($this->router->class ?? ''));
			if (in_array($loginClass, ['login', 'forgetpassword'], true)) {
				return;
			}
			$redirectto = rawurlencode($this->normalizeInternalRedirectUrl(current_url(), admin_url('dashboard')));
			redirect(admin_url('login') . '?redirectto=' . $redirectto, 'refresh');
		}

		if ($this->isDeferredAuthImageUploadAjaxRequest()) {
			$this->bootstrapDeferredImageUploadAuth();
			return;
		}

		if (! $this->loggedIn()) {
			$this->setApplication($this->getApplicationFromUrl(current_url()));

			if (isset($this->input) && is_object($this->input) && method_exists($this->input, 'is_ajax_request') && $this->input->is_ajax_request()) {
				header('Content-Type: application/json; charset=utf-8');
				http_response_code(401);
				echo json_encode(['error' => 'unauthenticated', 'message' => 'Unauthenticated.']);
				exit;
			}

			$redirectto = rawurlencode($this->normalizeInternalRedirectUrl(current_url(), base_url('login')));
			redirect(base_url('login') . '?redirectto=' . $redirectto, 'refresh');
		}
	}

	/**
	 * Subidas AJAX de imágenes: la autenticación se valida en uploadImage() con token/cabeceras,
	 * no en el constructor (evita perder el token por lock de sesión memcached en paralelo).
	 */
	protected function isDeferredAuthImageUploadAjaxRequest(): bool
	{
		if (defined('FV_STATELESS_IMAGE_UPLOAD') && FV_STATELESS_IMAGE_UPLOAD) {
			return true;
		}
		if (! isset($this->input) || ! is_object($this->input) || ! method_exists($this->input, 'is_ajax_request') || ! $this->input->is_ajax_request()) {
			return false;
		}

		return strtolower((string) ($this->router->method ?? '')) === 'uploadimage';
	}

	/** @deprecated use isDeferredAuthImageUploadAjaxRequest() */
	protected function isTrustedImageUploadAjaxRequest(): bool
	{
		return $this->isDeferredAuthImageUploadAjaxRequest() && $this->resolveImageUploadApiToken() !== '';
	}

	protected function resolveImageUploadApiToken(): string
	{
		$token = '';
		if (isset($this->input) && is_object($this->input)) {
			$headerToken = $this->input->get_request_header('X-FV-Upload-Token', true);
			$token = is_string($headerToken) ? trim($headerToken) : '';
			if ($token === '') {
				$token = trim((string) ($this->input->post('fv_upload_token') ?? ''));
			}
		}
		if ($token === '' && isset($_POST['fv_upload_token'])) {
			$token = trim((string) $_POST['fv_upload_token']);
		}
		if ($token === '' && ! empty($_SERVER['HTTP_X_FV_UPLOAD_TOKEN'])) {
			$token = trim((string) $_SERVER['HTTP_X_FV_UPLOAD_TOKEN']);
		}
		if ($token === '' || ! preg_match('/^\d+\|[^\s\r\n]+$/', $token)) {
			return '';
		}

		return $token;
	}

	protected function resolveImageUploadCompanyId(): int
	{
		$companyId = 0;
		if (isset($this->input) && is_object($this->input)) {
			$headerCompanyId = $this->input->get_request_header('X-FV-Company-Id', true);
			$companyId = is_string($headerCompanyId) ? (int) trim($headerCompanyId) : 0;
			if ($companyId < 1) {
				$companyId = (int) ($this->input->post('fv_company_id') ?? 0);
			}
		}
		if ($companyId < 1 && isset($_POST['fv_company_id'])) {
			$companyId = (int) $_POST['fv_company_id'];
		}
		if ($companyId < 1 && ! empty($_SERVER['HTTP_X_FV_COMPANY_ID'])) {
			$companyId = (int) $_SERVER['HTTP_X_FV_COMPANY_ID'];
		}
		if ($companyId < 1 && isset($this->fvCachedUploadCompanyId)) {
			$companyId = (int) $this->fvCachedUploadCompanyId;
		}
		// Fallback a sesión siempre (también en uploadImage stateless: la sesión ya está cargada).
		if ($companyId < 1 && method_exists($this, 'getCompanyId')) {
			$companyId = (int) $this->getCompanyId();
		}
		if ($companyId < 1 && method_exists($this, 'getUserData')) {
			$companyId = (int) ($this->getUserData('compId') ?? 0);
		}

		return $companyId > 0 ? $companyId : 0;
	}

	protected function bootstrapDeferredImageUploadAuth(): void
	{
		$token = $this->resolveImageUploadApiToken();
		if ($token !== '' && property_exists($this, 'userToken')) {
			$this->userToken = $token;
		}
		$this->releaseSessionLockEarly();
	}

	protected function extractTrustedImageUploadToken(): string
	{
		return $this->resolveImageUploadApiToken();
	}

	protected function bootstrapTrustedImageUploadAuth(): void
	{
		$this->bootstrapDeferredImageUploadAuth();
	}

	protected function releaseSessionLockEarly(): void
	{
		if (method_exists($this, 'releaseSessionLockIfActive')) {
			$this->releaseSessionLockIfActive();
		} elseif (function_exists('session_write_close') && session_status() === PHP_SESSION_ACTIVE) {
			session_write_close();
		}
	}

    /**
     * Changes the language
     * @return void
     */
    public function changelanguage()
    {
        $langid = $this->input->post('languageid');
        $usertype = $this->input->post('usertype');
        $_SESSION['languageid'] = $langid;
        $_SESSION['usertype'] = $usertype;
        if (count($_POST) > 0) {
            redirect(company_url('login'));
            return;
        }

        $langdef = $this->langmodel->getMdl('company-login', $langid);
        $data['langdef'] = $langdef;
        $data['langid'] = $langid;
        $data['settings'] = $this->getPlatformSettings();
        $this->load->view('company/login', $data);
    }

    protected function setSessionData($user = null, $password = null): void
    {
        $token = $this->getUserData('token');;
        if (empty($token)) {
            $token = $this->getUserToken($user->email, $password);
            $this->setUserData('token', $token);
        }
        $this->userToken = $token;
        log_message('debug', "The token is: $token");
        session_write_close();
    }

    protected function ensureSessionOpened()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * API pública para comprobar sesión desde modelos/traits (ApiClient).
     */
    public function isUserLoggedIn(): bool
    {
        return $this->loggedIn();
    }

    protected function hasUserData($key)
    {
        if (! isset($this->session)) {
            return false;
        }
        $this->ensureSessionOpened();
        // No session_abort(): rompe el ciclo de vida del flashdata de CodeIgniter (mensajes que se repiten
        // en listas/vistas que llaman getUserData antes del footer).
        return $this->session->has_userdata($key);
    }

    protected function setUserData($key, $data)
    {
        $this->ensureSessionOpened();
        $this->session->set_userdata($key, $data);
        session_write_close();
    }

    /**
     * Escribe varias claves de sesión en una sola pasada (p. ej. tras login).
     * No llama session_write_close() entre claves: evita perder la sesión en memcached.
     *
     * @param array<string, mixed> $data
     */
    protected function setUserDataBatch(array $data): void
    {
        if ($data === []) {
            return;
        }
        $this->ensureSessionOpened();
        $this->session->set_userdata($data);
    }

    /**
     * Cache corta en sesión para no repetir la misma GET en cada pantalla.
     * No guarda respuestas de error.
     *
     * @param callable(): mixed $resolve
     * @return mixed
     */
    public function cachedApiValue(string $key, int $ttlSeconds, callable $resolve)
    {
        $bag = $this->getUserData('fv_api_cache');
        if (! is_array($bag)) {
            $bag = [];
        }
        $hit = $bag[$key] ?? null;
        if (is_array($hit) && (int) ($hit['exp'] ?? 0) >= time() && array_key_exists('v', $hit)) {
            return $hit['v'];
        }

        $value = $resolve();
        if (! $this->isCacheableApiValue($value)) {
            return $value;
        }

        $bag[$key] = [
            'exp' => time() + max(1, $ttlSeconds),
            'v' => $value,
        ];
        $this->setUserData('fv_api_cache', $bag);

        return $value;
    }

    /**
     * @param mixed $value
     */
    protected function isCacheableApiValue($value): bool
    {
        if ($value === null) {
            return false;
        }
        if (is_object($value) && (isset($value->error) || isset($value->errors))) {
            return false;
        }
        if (is_array($value) && (isset($value['error']) || isset($value['errors']))) {
            return false;
        }

        return true;
    }

    protected function getUserData($key)
    {
        if (! isset($this->session)) {
            return null;
        }
        $this->ensureSessionOpened();
        // No session_abort(): rompe el ciclo de vida del flashdata de CodeIgniter (mensajes que se repiten
        // en listas/vistas que llaman getUserData antes del footer).
        return $this->session->userdata($key);
    }

    protected function removeUserData($key)
    {
        $this->ensureSessionOpened();
        $this->session->unset_userdata($key);

        session_write_close();
    }

    public function testInput($data)
    {
        $data = trim($data);
        $data = stripcslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    public function uw($data)
    {
        $data = $this->testInput($data);
        $data = ucwords(strtolower($data));
        return $data;
    }


    public function uc($data)
    {
        $data = $this->testInput($data);
        $data = strtoupper($data);
        return $data;
    }


    public function lc($data)
    {
        $data = $this->testInput($data);
        $data = strtolower($data);
        return $data;
    }


    public function removeNull($array)
    {
        array_walk_recursive($array, function (&$array, $key) {
            $array = (null === $array) ? '' : $array;
        });
        return $array;
    }


    public function enc_password($password)
    {
        $encrypted_password = password_hash($password, PASSWORD_DEFAULT);
        return $encrypted_password;
    }


    /**
     * @param $order_id
     * @return void
     */
    protected function updateOrderStatusHistory($order_id, $status_id): void
    {
        $this->post('orderstatushistory', [
            "order_id" => $order_id,
            "company_id" => $this->company_info->company_id,
            "user_id" => $this->sub_user->company_id,
            "cod_status" => $status_id,
        ]);
    }

    /**
     * @param object $order
     * @param object $customer
     * @param $orderDetails
     * @return array|false[]
     */
    protected function saveOrder(object $order, object $customer, $orderDetails): array
    {
        try {
            $this->load->model('OrdenModel');
            $orderDetails = $this->OrdenModel->makecrudOrderDetails($order, $customer, $orderDetails);
            $orderResult = $this->post("order", [
                "order" => $order,
                "customerId" => $customer->customer_id,
                "details" => $orderDetails,
            ],                         false);
            $response = ['success' => true, 'orderData' => $orderResult->data];
        } catch (\Exception $e) {
            //throw new \Exception($e->getMessage());
            $response = ['success' => false];
        }

        return $response;
    }

    protected function flash($key, $value)
    {
        $this->ensureSessionOpened();
        $this->session->set_flashdata($key, $value);
        session_write_close();
    }

    protected function getFlash($key)
    {
        $this->ensureSessionOpened();
        $value = $this->session->flashdata($key);
        session_write_close();
        return $value;
    }

    protected function flashMessage($message)
    {
        $this->flash('msg', $message);
    }

    protected function getFlashMessage()
    {
        return $this->getFlash('msg');
    }

    protected function addJsFile($file)
    {
        if (!is_array($this->jsFiles)) {
            $this->jsFiles = [];
        }
        $fileWithBuster = $this->withAssetCacheBuster($file);
        // Agregar el archivo al array sin usar claves asociativas
        // para que se convierta correctamente a array en JavaScript
        if (!in_array($fileWithBuster, $this->jsFiles)) {
            $this->jsFiles[] = $fileWithBuster;
        }
    }

    /**
     * Añade un cache buster (?v=mtime) a los assets locales que existan en disco,
     * para evitar que el navegador sirva versiones obsoletas tras un deploy.
     *
     * Acepta URLs absolutas que apuntan al propio sitio (extrae el path) y rutas
     * relativas. No modifica rutas que ya traen query string.
     */
    private function withAssetCacheBuster(string $url): string
    {
        if ($url === '' || strpos($url, '?') !== false) {
            return $url;
        }
        $path = parse_url($url, PHP_URL_PATH);
        if ($path === null || $path === false || $path === '') {
            return $url;
        }
        $base = rtrim(FCPATH, '/');
        $absolute = $base . '/' . ltrim($path, '/');
        if (!is_file($absolute)) {
            return $url;
        }
        $mtime = @filemtime($absolute);
        if ($mtime === false) {
            return $url;
        }
        return $url . '?v=' . $mtime;
    }

    protected function languageIdToApiLocale(int $languageId): string
    {
        static $map = [
            1 => 'en',
            2 => 'es',
            3 => 'pt',
            4 => 'it',
            5 => 'ru',
            6 => 'de',
            7 => 'zh',
        ];

        return $map[$languageId] ?? 'en';
    }

    protected function getDefaultLanguage($companyId)
    {
        $cid = (int) ($companyId ?? 0);
        if ($cid < 1 && method_exists($this, 'getCompanyId')) {
            try {
                $cid = (int) $this->getCompanyId();
            } catch (\Throwable $e) {
                $cid = 0;
            }
        }
        if ($cid > 0 && function_exists('fv_resolve_company_language_id')) {
            return $this->languageIdToApiLocale(fv_resolve_company_language_id());
        }

        $sessionLangId = (int) ($this->getUserData('language_id') ?? 0);
        if ($sessionLangId >= 1 && $sessionLangId <= 7) {
            return $this->languageIdToApiLocale($sessionLangId);
        }

        if ($cid <= 0) {
            return 'en';
        }

        return 'en';
    }

    protected function getDictionaryFromApi($lang = null)
    {
        if (! isset($this->langmodel)) {
            $this->load->model('langmodel');
        }

        if (! isset($lang)) {
            $lang = $this->getDefaultLanguage($this->getCompanyId());
        }
        $supported = ['en', 'es', 'pt', 'it', 'ru', 'de', 'zh'];
        if (! in_array($lang, $supported, true)) {
            $lang = 'en';
        }
        $langId = function_exists('fv_locale_to_language_id')
            ? fv_locale_to_language_id($lang)
            : ($lang === 'es' ? 2 : 1);

        $fromJson = $this->dictionaryFromLocalLangJson($langId);
        if ($fromJson !== []) {
            return $fromJson;
        }

        $modul_pather = $this->getDataFromApi("langmodules?locale={$lang}");
        $result = array();

        if (! is_array($modul_pather) || isset($modul_pather['error'])) {
            return $result;
        }

        foreach ($modul_pather as $mdl) {
            if (! is_string($mdl) || $mdl === '') {
                continue;
            }
            $name = $mdl;
            $mdlLang = $this->langmodel->getMdl($name, $langId);
            $mdl = str_replace('-', '_', $mdl);
            $result[$mdl] = array(
                "name" => $name,
                "lang" => $mdlLang
            ); // Asignar una nueva clave y un array vacío al nuevo array
        }
        return $result;
    }

    /**
     * Diccionario de interfaz desde api/lang/*.json.
     * Evita una petición HTTP por módulo al armar el panel.
     *
     * @return array<string, array{name: string, lang: array<string, mixed>}>
     */
    protected function dictionaryFromLocalLangJson(int $langId): array
    {
        $base = dirname(APPPATH, 2) . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR;
        $english = $this->readLangJsonFile($base . 'en-EN.json');
        $file = function_exists('fv_lang_json_filename') ? fv_lang_json_filename($langId) : 'en-EN.json';
        $localized = ($file === 'en-EN.json') ? [] : $this->readLangJsonFile($base . $file);
        if ($english === [] && $localized === []) {
            return [];
        }

        $result = [];
        foreach (array_unique(array_merge(array_keys($english), array_keys($localized))) as $name) {
            if (! is_string($name) || $name === '') {
                continue;
            }
            $sectionEn = (isset($english[$name]) && is_array($english[$name])) ? $english[$name] : [];
            $sectionUser = (isset($localized[$name]) && is_array($localized[$name])) ? $localized[$name] : [];
            $result[str_replace('-', '_', $name)] = [
                'name' => $name,
                'lang' => array_replace($sectionEn, $sectionUser),
            ];
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    protected function readLangJsonFile(string $path): array
    {
        static $decoded = [];
        if (isset($decoded[$path])) {
            return $decoded[$path];
        }
        if (! is_readable($path)) {
            return $decoded[$path] = [];
        }
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return $decoded[$path] = [];
        }
        $data = json_decode($raw, true);

        return $decoded[$path] = is_array($data) ? $data : [];
    }

}

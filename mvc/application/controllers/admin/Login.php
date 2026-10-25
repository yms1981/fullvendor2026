<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends MY_Controller
{

	public function __construct()
	{
		parent::__construct();
	}


	public function index()
	{
		if (function_exists('admin_sync_session_language_from_request')) {
			admin_sync_session_language_from_request();
		}

		if ($this->hasUserData('userId') && $this->hasUserData('admin')):
			redirect(admin_url('dashboard'),'refresh');
		endif;

		$data = array('page' => 'login', 'msg' => '');
		$loginLang = $this->adminLoginLang();
		log_message('debug', 'admin_login.mvc.lang_loaded keys=' . count($loginLang));

		if (strtoupper((string) $this->input->method()) === 'POST')
		{
			$postedUsername = trim((string) $this->input->post('username'));
			log_message('debug', 'admin_login.mvc.post_received username=' . $postedUsername . ' password_len=' . strlen((string) $this->input->post('password')));

			$this->form_validation->set_rules(
				'username',
				$loginLang['label_username'] ?? 'Username',
				'trim|required'
			);
			$this->form_validation->set_rules(
				'password',
				$loginLang['label_password'] ?? 'Password',
				'trim|required'
			);

			if ($this->form_validation->run() == false)
			{
				$msg = '';
				if (form_error('username')) {
					$msg .= strip_tags(form_error('username'));
				}
				if (form_error('password')) {
					$msg .= strip_tags(form_error('password'));
				}
				log_message('debug', 'admin_login.mvc.validation_failed username_error=' . strip_tags((string) form_error('username')) . ' password_error=' . strip_tags((string) form_error('password')));
				if (trim($msg) === '') {
					$msg = $loginLang['login_error_generic'] ?? 'Invalid username or password.';
					log_message('debug', 'admin_login.mvc.validation_failed_empty_message fallback_used=1');
				}

				$data['msg'] = '<div class="alert alert-danger alert-dismissable">
			<a href="javascript:void(0)" class="close" data-dismiss="alert" aria-label="close">&times;</a>
			<strong>
			<i class="fa fa-check-circle-o"></i>
			</strong>
			'.htmlspecialchars($msg, ENT_QUOTES, 'UTF-8').'
			</div>';

			} else {

				$credentials = [
					'username' => trim((string) $this->input->post('username')),
					'password' => (string) $this->input->post('password'),
				];

				$response = $this->post('admin/platform-login', $credentials);
				$responseCode = '';
				$passwordMatchesDb = false;
				$hasError = isset($response['error']);
				$hasUser = isset($response['user']) && is_array($response['user']);
				$adminAccessDebug = null;
				if (isset($response['message']) && is_array($response['message']) && isset($response['message']['code'])) {
					$responseCode = (string) $response['message']['code'];
				} elseif ($hasError && isset($response['error']) && is_string($response['error'])) {
					$responseCode = (string) $response['error'];
				}
				log_message('debug', 'admin_login.mvc.api_response has_error=' . (isset($response['error']) ? '1' : '0') . ' has_user=' . (isset($response['user']) ? '1' : '0') . ' code=' . $responseCode);

				if (isset($response['error'])) {
					$errText = $this->adminLoginResolvedErrorMessage($response, $loginLang);
					if ($errText === '') {
						$errText = $loginLang['login_error_generic'] ?? 'Invalid username or password.';
						log_message('debug', 'admin_login.mvc.api_error_empty_message fallback_used=1 code=' . $responseCode);
					}
					log_message('debug', 'admin_login.mvc.api_error_message text=' . $errText);
					$data['msg'] = '<div class="alert alert-danger alert-dismissable">
                <a href="javascript:void(0)" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                <strong>
                <i class="fa fa-check-circle-o"></i>
                </strong>
                '.htmlspecialchars($errText, ENT_QUOTES, 'UTF-8').'
                </div>';
				} elseif ($hasUser) {
					$adminAccess = false;
					if (isset($response['app_access']['admin']['access'])) {
						$adminAccess = (bool) $response['app_access']['admin']['access'];
					}
					$adminAccessDebug = $adminAccess;
					$passwordMatchesDb = true;
					if ($adminAccess) {
						$langId = 0;
						$postLang = $this->input->post('lang');
						if ($postLang !== null && $postLang !== '' && function_exists('admin_lang_code_to_id')) {
							$langId = admin_lang_code_to_id((string) $postLang);
						} elseif (isset($response['user']['language_id'])) {
							$langId = (int) $response['user']['language_id'];
						}
						if ($langId < 1 || $langId > 7) {
							$langId = function_exists('admin_detect_language_id_from_request')
								? admin_detect_language_id_from_request()
								: 1;
						}
						$this->clearUnifiedLoginSessionKeys();
						$sessionBatch = [
							'admin' => '1',
							'userId' => $response['user']['id'],
							'language_id' => $langId,
							'languageid' => $langId,
						];
						if (! empty($response['user']['token'])) {
							$sessionBatch['token'] = $response['user']['token'];
							$this->userToken = $response['user']['token'];
						}
						if (isset($response['abilities'])) {
							$sessionBatch['abilities'] = $response['abilities'];
						}
						$this->setUserDataBatch($sessionBatch);
						log_message('debug', 'admin_login.mvc.login_success user_id=' . (string) $response['user']['id']);
					} else {
						$denyMsg = $loginLang['not_admin_access'] ?? '';
						log_message('debug', 'admin_login.mvc.not_admin_access user_id=' . (isset($response['user']['id']) ? (string) $response['user']['id'] : ''));
						$data['msg'] = '<div class="alert alert-danger alert-dismissable">
                <a href="javascript:void(0)" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                <strong>
                <i class="fa fa-check-circle-o"></i>
                </strong>
                '.htmlspecialchars($denyMsg, ENT_QUOTES, 'UTF-8').'
                </div>';
					}
				}

				if ($this->hasUserData('userId') && $this->hasUserData('admin'))
				{
					$redirectTarget = admin_url('dashboard');
					$redirectto = $this->input->get('redirectto');
					if ($redirectto === null || $redirectto === '') {
						$redirectto = $this->input->post('redirectto');
					}
					if ($redirectto !== null && $redirectto !== '') {
						$redirectTarget = $this->normalizeInternalRedirectUrl((string) $redirectto, $redirectTarget);
					}
					redirect($redirectTarget, 'refresh');
					return;
				}

			}
		}

		if ($data['msg'] === '' || $data['msg'] === null) {
			$data['msg'] = $this->getFlashMessage();
		}

		$data['settings'] = $this->getPlatformSettings();
		$data['adminLoginLang'] = is_array($loginLang) ? $loginLang : [];

		$redirecttoParam = $this->input->get('redirectto');
		if ($redirecttoParam === null || $redirecttoParam === '') {
			$redirecttoParam = $this->input->post('redirectto');
		}
		$data['admin_login_redirectto'] = '';
		if ($redirecttoParam !== null && $redirecttoParam !== '') {
			$data['admin_login_redirectto'] = $this->normalizeInternalRedirectUrl(
				(string) $redirecttoParam,
				admin_url('dashboard')
			);
		}

		$langId = (int) ($this->session->userdata('language_id') ?? $this->session->userdata('languageid') ?? 1);
		$langCodes = [1 => 'en', 2 => 'es', 3 => 'pt', 4 => 'it', 5 => 'ru', 6 => 'de', 7 => 'zh'];
		$data['admin_login_lang'] = $langCodes[$langId] ?? 'en';

		$this->load->view('admin/login', $data);
	}


	public function logout()
	{
		$this->removeUserData('userId');
		$this->removeUserData('admin');
		$this->removeUserData('token');
		$lang = $this->adminLoginLang();
		$ok = $lang['logout_success'] ?? '';
		$msg = '<div class="alert alert-success alert-dismissable">
	<a href="javascript:void(0)" class="close" data-dismiss="alert" aria-label="close">&times;</a>
	<strong>
	<i class="fa fa-check-circle-o"></i>
	</strong>
	'.htmlspecialchars($ok, ENT_QUOTES, 'UTF-8').'
	</div>';
		$this->flashMessage($msg);
		redirect(admin_url('login'),'refresh');
	}

	/**
	 * Bloque cmp-admin-login desde un archivo JSON concreto.
	 *
	 * @return array<string, string>
	 */
	/**
	 * Último recurso si no existe api/lang en el servidor (debe coincidir con cmp-admin-login en en-EN.json).
	 *
	 * @return array<string, string>
	 */
	private function adminLoginEmergencyFallbackEn(): array
	{
		return [
			'label_username' => 'Username',
			'label_password' => 'Password',
			'invalid_username' => 'Invalid username.',
			'invalid_password' => 'Invalid password.',
			'account_deactivated' => 'Your account has been deactivated. Please contact the administrator.',
			'not_admin_access' => 'You do not have permission to access the administration panel.',
			'logout_success' => 'You have successfully signed out.',
			'login_error_generic' => 'Sign-in failed. Please check your username and password.',
			'login_page_title' => 'Administrator sign-in',
			'login_badge_admin' => 'Administrator',
			'login_welcome_title' => 'Sign in',
			'login_welcome_subtitle' => 'Enter your administrator credentials.',
			'login_sign_in' => 'Sign in',
			'login_keep_signed_in' => 'Keep me signed in',
			'login_forgot_password' => 'Forgot password?',
		];
	}

	private function adminLoginSectionFromFile(string $jsonBasename): array
	{
		$safe = basename($jsonBasename);
		if ($safe === '' || $safe !== $jsonBasename) {
			return [];
		}
		$candidates = [];
		$fromController = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $safe;
		$candidates[] = $fromController;
		$rpCtrl = @realpath($fromController);
		if (is_string($rpCtrl) && $rpCtrl !== '') {
			$candidates[] = $rpCtrl;
		}
		$candidates[] = dirname(APPPATH, 2) . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $safe;
		$repoRoot = @realpath(APPPATH . '..' . DIRECTORY_SEPARATOR . '..');
		if (is_string($repoRoot) && $repoRoot !== '') {
			$candidates[] = $repoRoot . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $safe;
		}
		if (defined('FCPATH')) {
			$fcp = rtrim((string) FCPATH, '/\\');
			if ($fcp !== '') {
				$candidates[] = dirname($fcp) . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $safe;
			}
		}
		$candidates = array_values(array_unique($candidates));

		$path = '';
		foreach ($candidates as $p) {
			if ($p !== '' && is_readable($p)) {
				$path = $p;
				break;
			}
		}
		if ($path === '') {
			return [];
		}
		$raw = @file_get_contents($path);
		if ($raw === false || $raw === '') {
			return [];
		}
		$data = json_decode($raw, true);
		if (! is_array($data) || ! isset($data['cmp-admin-login']) || ! is_array($data['cmp-admin-login'])) {
			return [];
		}
		$out = [];
		foreach ($data['cmp-admin-login'] as $k => $v) {
			if (is_string($k) && (is_string($v) || is_numeric($v))) {
				$out[$k] = is_string($v) ? $v : (string) $v;
			}
		}

		return $out;
	}

	/**
	 * Textos del login admin: api/lang/{locale}.json → cmp-admin-login, con fallback en en-EN
	 * para que nunca falten placeholders ni etiquetas si el locale no trae todas las claves.
	 *
	 * @return array<string, string>
	 */
	private function adminLoginLang(): array
	{
		$defaults = $this->adminLoginSectionFromFile('en-EN.json');
		if ($defaults === []) {
			$defaults = $this->adminLoginEmergencyFallbackEn();
			log_message('error', 'admin_login: no se pudo leer api/lang/en-EN.json; usando cadenas EN de emergencia.');
		}
		$langId = (int) ($this->session->userdata('language_id') ?? $this->session->userdata('languageid') ?? 1);
		$map = [
			1 => 'en-EN.json',
			2 => 'es-ES.json',
			3 => 'pt-PT.json',
			4 => 'it-IT.json',
			5 => 'ru-RU.json',
			6 => 'de-DE.json',
			7 => 'zh-CN.json',
		];
		$file = $map[$langId] ?? 'en-EN.json';
		if ($file === 'en-EN.json') {
			return $defaults;
		}
		$user = $this->adminLoginSectionFromFile($file);

		return array_merge($defaults, $user);
	}

	/**
	 * Mensaje traducido si la API devuelve message.code (login admin plataforma).
	 *
	 * @param array<string, mixed> $response
	 * @param array<string, string> $loginLang
	 */
	private function adminLoginResolvedErrorMessage(array $response, array $loginLang): string
	{
		$msg = $response['message'] ?? null;
		if (is_array($msg) && isset($msg['code']) && is_string($msg['code'])) {
			$code = $msg['code'];
			if (isset($loginLang[$code]) && $loginLang[$code] !== '') {
				return $loginLang[$code];
			}
		}

		return $this->adminLoginApiErrorText($response);
	}

	private function adminLoginApiErrorText(array $response): string
	{
		$msg = $response['message'] ?? null;
		if (is_string($msg) && $msg !== '') {
			return $msg;
		}
		if (is_array($msg)) {
			if (isset($msg['message']) && is_string($msg['message'])) {
				return $msg['message'];
			}
			if (isset($msg['errors']) && is_array($msg['errors'])) {
				$parts = [];
				foreach ($msg['errors'] as $fieldErr) {
					if (is_array($fieldErr)) {
						foreach ($fieldErr as $line) {
							if (is_string($line)) {
								$parts[] = $line;
							}
						}
					} elseif (is_string($fieldErr)) {
						$parts[] = $fieldErr;
					}
				}
				return implode(' ', $parts);
			}
		}
		return '';
	}

}

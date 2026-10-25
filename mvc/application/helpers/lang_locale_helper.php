<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (! function_exists('fv_language_id_to_locale')) {
	function fv_language_id_to_locale(int $languageId): string
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
}

if (! function_exists('fv_locale_to_language_id')) {
	function fv_locale_to_language_id(string $code): int
	{
		static $map = [
			'en' => 1,
			'es' => 2,
			'pt' => 3,
			'it' => 4,
			'ru' => 5,
			'de' => 6,
			'zh' => 7,
		];
		$code = strtolower(substr(trim($code), 0, 2));

		return $map[$code] ?? 1;
	}
}

if (! function_exists('fv_lang_json_filename')) {
	function fv_lang_json_filename(int $languageId): string
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
}

if (! function_exists('fv_resolve_company_language_id')) {
	/**
	 * Idioma del panel company: companies.language_id cacheado en sesión (ver Company_Controller::syncCompanyPanelLanguageFromCompanyRecord).
	 */
	function fv_resolve_company_language_id(?int $companyId = null): int
	{
		$CI = function_exists('get_instance') ? get_instance() : null;
		if (($companyId === null || $companyId < 1) && is_object($CI) && isset($CI->session)) {
			if (session_status() === PHP_SESSION_NONE) {
				@session_start();
			}
			$companyId = (int) ($CI->session->userdata('compId') ?? 0);
		}
		if ($companyId < 1) {
			return fv_resolve_session_language_id();
		}

		if (is_object($CI) && isset($CI->session) && method_exists($CI->session, 'userdata')) {
			if (session_status() === PHP_SESSION_NONE) {
				@session_start();
			}

			$cachedCompId = (int) ($CI->session->userdata('company_panel_language_comp_id') ?? 0);
			$cachedLangId = (int) ($CI->session->userdata('company_panel_language_id') ?? 0);
			if ($cachedCompId === $companyId && $cachedLangId >= 1 && $cachedLangId <= 7) {
				return $cachedLangId;
			}

			if ((int) ($CI->session->userdata('compId') ?? 0) === $companyId) {
				foreach (['language_id', 'languageid', 'lang_id'] as $key) {
					$val = $CI->session->userdata($key);
					if ($val !== null && $val !== '' && is_numeric($val)) {
						$id = (int) $val;
						if ($id >= 1 && $id <= 7) {
							return $id;
						}
					}
				}
			}
		}

		return fv_resolve_session_language_id();
	}
}

if (! function_exists('fv_resolve_session_language_id')) {
	/**
	 * Idioma activo: ?lang= / POST lang → sesión MVC → 1 (en).
	 */
	function fv_resolve_session_language_id(): int
	{
		$CI = function_exists('get_instance') ? get_instance() : null;
		$lang = '';
		if (is_object($CI) && isset($CI->input) && is_object($CI->input)) {
			$get = method_exists($CI->input, 'get') ? $CI->input->get('lang') : null;
			$post = method_exists($CI->input, 'post') ? $CI->input->post('lang') : null;
			if ($get !== null && $get !== '') {
				$lang = (string) $get;
			} elseif ($post !== null && $post !== '') {
				$lang = (string) $post;
			}
		}
		if ($lang !== '') {
			return fv_locale_to_language_id($lang);
		}
		if (is_object($CI) && isset($CI->session) && is_object($CI->session) && method_exists($CI->session, 'userdata')) {
			foreach (['language_id', 'languageid', 'lang_id'] as $key) {
				$val = $CI->session->userdata($key);
				if ($val !== null && $val !== '' && is_numeric($val)) {
					$id = (int) $val;
					if ($id >= 1 && $id <= 7) {
						return $id;
					}
				}
			}
		}

		return 1;
	}
}

if (! function_exists('fv_sync_session_language_from_request')) {
	function fv_sync_session_language_from_request(): void
	{
		$CI = function_exists('get_instance') ? get_instance() : null;
		if (! is_object($CI) || ! isset($CI->session) || ! is_object($CI->session) || ! method_exists($CI->session, 'set_userdata')) {
			return;
		}
		$lang = '';
		if (isset($CI->input) && is_object($CI->input)) {
			$get = method_exists($CI->input, 'get') ? $CI->input->get('lang') : null;
			$post = method_exists($CI->input, 'post') ? $CI->input->post('lang') : null;
			if ($get !== null && $get !== '') {
				$lang = (string) $get;
			} elseif ($post !== null && $post !== '') {
				$lang = (string) $post;
			}
		}
		if ($lang === '') {
			return;
		}
		$CI = function_exists('get_instance') ? get_instance() : null;
		$prevId = 1;
		if (is_object($CI) && isset($CI->session)) {
			$prevId = (int) ($CI->session->userdata('language_id') ?? $CI->session->userdata('languageid') ?? 1);
		}
		$langId = fv_locale_to_language_id($lang);
		$CI->session->set_userdata('language_id', $langId);
		$CI->session->set_userdata('languageid', $langId);
		if ($prevId !== $langId && is_object($CI) && method_exists($CI, 'setUserData')) {
			$CI->setUserData('langDictionary', null);
			foreach (['en', 'es', 'pt', 'it', 'ru', 'de', 'zh'] as $loc) {
				$CI->setUserData("langDictionary_{$loc}", null);
			}
			$CI->setUserData('langDictionary_lang', fv_language_id_to_locale($langId));
		}
	}
}

if (! function_exists('fv_order_status_label')) {
	/**
	 * Nombre de estado de orden.
	 * Inglés y español salen de status_orders. El resto de idiomas usa api/lang status-orders,
	 * con el name_status_english como clave. Si no hay traducción, queda el nombre en inglés.
	 */
	function fv_order_status_label(int $languageId, string $nameEn, string $nameEs = ''): string
	{
		$nameEn = trim($nameEn);
		$nameEs = trim($nameEs);
		if ($languageId === 2) {
			return $nameEs !== '' ? $nameEs : $nameEn;
		}
		if ($languageId <= 1) {
			return $nameEn !== '' ? $nameEn : $nameEs;
		}
		$translated = '';
		if ($nameEn !== '' && function_exists('fv_lang_json_flat_section')) {
			$dict = fv_lang_json_flat_section($languageId, 'status-orders');
			$translated = trim((string) ($dict[$nameEn] ?? ''));
		}

		if ($translated !== '') {
			return $translated;
		}

		return $nameEn !== '' ? $nameEn : $nameEs;
	}
}

if (! function_exists('fv_lang_json_flat_section')) {
	/**
	 * Bloque de api/lang/*.json fusionando en-EN + locale solicitado.
	 *
	 * @return array<string, string>
	 */
	function fv_lang_json_flat_section(int $languageId, string $section): array
	{
		static $decodedFiles = [];
		$readSection = static function (string $basename, string $sec) use (&$decodedFiles): array {
			if (! array_key_exists($basename, $decodedFiles)) {
				if (function_exists('admin_lang_json_resolved_path')) {
					$path = admin_lang_json_resolved_path($basename);
				} else {
					$path = dirname(APPPATH, 2) . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $basename;
				}
				$decodedFiles[$basename] = [];
				if ($path !== '' && is_readable($path)) {
					$raw = @file_get_contents($path);
					$data = ($raw === false || $raw === '') ? null : json_decode($raw, true);
					$decodedFiles[$basename] = is_array($data) ? $data : [];
				}
			}
			$data = $decodedFiles[$basename];
			if (! is_array($data) || ! isset($data[$sec]) || ! is_array($data[$sec])) {
				return [];
			}
			$out = [];
			foreach ($data[$sec] as $k => $v) {
				if (is_string($k) && (is_string($v) || is_numeric($v))) {
					$out[$k] = is_string($v) ? $v : (string) $v;
				}
			}

			return $out;
		};

		$fromEn = $readSection('en-EN.json', $section);
		$file = fv_lang_json_filename($languageId);
		if ($file === 'en-EN.json') {
			return $fromEn;
		}
		$user = $readSection($file, $section);

		return array_merge($fromEn, $user);
	}
}

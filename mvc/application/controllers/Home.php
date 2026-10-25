<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends Application_Controller
{

	public function __construct()
	{
		// skip Application auth check
        MY_Controller::__construct();
        $this->getDictionary();
	}

	/**
	 * Idioma público de la landing (?lang=es|en|…).
	 */
	protected function resolvePublicLandingLangId(): int
	{
		$q = strtolower(trim((string) $this->input->get('lang')));
		if ($q !== '' && function_exists('fv_locale_to_language_id')) {
			$id = fv_locale_to_language_id($q);
			if ($id > 0) {
				$_SESSION['languageid'] = $id;
				$this->setUserData('language_id', $id);

				return $id;
			}
		}

		$session = (int) ($this->getUserData('language_id') ?? ($_SESSION['languageid'] ?? 0));
		if ($session > 0) {
			return $session;
		}

		// Por defecto español (sitio comercial LATAM / fullvendor.com).
		return 2;
	}

	/**
	 * Bloque CMS público (about-us): si la API devuelve 404 u otra forma inválida, evita Notice en la vista.
	 *
	 * @param array|mixed $cms
	 * @return array{pageTitle: string, content: string}
	 */
	protected function normalizePublicCmsAboutBlock($cms): array
	{
		$langCatalog = $this->getModuleLang('cmp-catalog');
		$defaultTitle = isset($langCatalog['home']) ? (string) $langCatalog['home'] : '';
		$out = ['pageTitle' => $defaultTitle, 'content' => ''];

		if (! is_array($cms) || isset($cms['error'])) {
			log_message('info', 'Home: CMS cm/about-us no disponible (404 o error de API); se usan valores por defecto.');

			return $out;
		}

		if (isset($cms['data']) && is_array($cms['data'])) {
			$cms = $cms['data'];
		}

		if (isset($cms['pageTitle']) && $cms['pageTitle'] !== null && $cms['pageTitle'] !== '') {
			$out['pageTitle'] = (string) $cms['pageTitle'];
		}
		if (isset($cms['content']) && $cms['content'] !== null) {
			$out['content'] = (string) $cms['content'];
		}

		return $out;
	}

	/**
	 * Textos de landing: JSON api/lang + fallback local (por si producción no tiene la sección).
	 *
	 * @return array<string, string>
	 */
	protected function resolvePublicLandingTexts(int $langId): array
	{
		$locale = function_exists('fv_language_id_to_locale')
			? fv_language_id_to_locale($langId)
			: 'es';
		// public-landing usa claves cortas es|en en defaults (no es-ES).
		$localeShort = strtolower(substr((string) $locale, 0, 2));
		if ($localeShort === '') {
			$localeShort = 'es';
		}

		$fromJson = function_exists('fv_lang_json_flat_section')
			? fv_lang_json_flat_section($langId, 'public-landing')
			: [];
		if (! is_array($fromJson)) {
			$fromJson = [];
		}

		$defaultsPath = APPPATH . 'config/public_landing_defaults.php';
		$defaults = [];
		if (is_readable($defaultsPath)) {
			$all = include $defaultsPath;
			if (is_array($all)) {
				$en = is_array($all['en'] ?? null) ? $all['en'] : [];
				$loc = is_array($all[$localeShort] ?? null) ? $all[$localeShort] : [];
				$defaults = array_merge($en, $loc);
			}
		}

		// Política de privacidad: cuerpo HTML vive en archivo propio del MVC
		// (producción a menudo no monta api/lang junto al front).
		$privacyPath = APPPATH . 'config/privacy_policy_bodies.php';
		$privacyDefaults = [];
		if (is_readable($privacyPath)) {
			$privacyAll = include $privacyPath;
			if (is_array($privacyAll)) {
				$enP = is_array($privacyAll['en'] ?? null) ? $privacyAll['en'] : [];
				$locP = is_array($privacyAll[$localeShort] ?? null) ? $privacyAll[$localeShort] : [];
				$privacyDefaults = array_merge($enP, $locP);
			}
		}

		$merged = array_merge($defaults, $privacyDefaults, $fromJson);

		// Si el JSON llegó sin el cuerpo (deploy parcial), forzar fallback local.
		$body = trim((string) ($merged['privacy_body_html'] ?? ''));
		if ($body === '' && isset($privacyDefaults['privacy_body_html'])) {
			$merged['privacy_body_html'] = (string) $privacyDefaults['privacy_body_html'];
		}

		return $merged;
	}

	public function index()
	{
		$langId = $this->resolvePublicLandingLangId();
		$landing = $this->resolvePublicLandingTexts($langId);

		$cms = $this->getDataFromApi("cm/about-us");
		$data['details'] = $this->normalizePublicCmsAboutBlock($cms);
		$data['landing'] = $landing;
		$data['landingLangId'] = $langId;
		$data['landingLocale'] = function_exists('fv_language_id_to_locale')
			? fv_language_id_to_locale($langId)
			: 'es';
		$data['title'] = (string) ($landing['meta_title'] ?? ($data['details']['pageTitle'] ?? 'Fullvendor'));
		$data['isPublicLanding'] = true;
		$data['landingNavPage'] = 'home';
		$data['head_content'] = 'common/public-head-landing';

		$this->showContent('index', $data, 'landing');
	}

	public function policy()
	{
		$langId = $this->resolvePublicLandingLangId();
		$landing = $this->resolvePublicLandingTexts($langId);
		$data['landing'] = $landing;
		$data['landingLangId'] = $langId;
		$data['landingLocale'] = function_exists('fv_language_id_to_locale')
			? fv_language_id_to_locale($langId)
			: 'es';
		$data['title'] = (string) ($landing['privacy_meta_title'] ?? ($landing['privacy_title'] ?? 'Privacy Policy'));
		$data['isPublicLanding'] = true;
		$data['landingNavPage'] = 'privacy';
		$data['head_content'] = 'common/public-head-landing';
		$this->showContent('policy', $data, 'landing');
	}

	public function terms()
	{
		$langId = $this->resolvePublicLandingLangId();
		$data['landing'] = $this->resolvePublicLandingTexts($langId);
		$data['details']=$info= $this->getObjectFromApi("cm/3");
		$data['title']=$info->pageTitle ?? '';
        $this->showContent('terms', $data);
	}

	public function copyright()
	{
		$langId = $this->resolvePublicLandingLangId();
		$data['landing'] = $this->resolvePublicLandingTexts($langId);
		$data['details']=$info= $this->getDataFromApi("cm/4");
		$data['title']= is_object($info) ? ($info->pageTitle ?? '') : '';
        $this->showContent('copyright', $data);
	}

}

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Settings extends MY_Controller {

	private const DEFAULT_SETTING_ID = 1;

	public function __construct()
	{
		parent::__construct();
		$this->checkLoggedInOrLoginWithRedirect();
		$this->load->helper('admin_shell');
	}

	/**
	 * Si no hay ninguna fila en settings, crea una fila por defecto vía API.
	 */
	protected function ensureDefaultSettingsRow(): void
	{
		if ($this->fetchSettingFromApi(self::DEFAULT_SETTING_ID) !== null) {
			return;
		}

		$resp = $this->getDataFromApi('setting', [
			'page' => 1,
			'perPage' => 1,
			'orderProperty' => 'settingId',
			'orderDirection' => 'asc',
		], true);

		if (is_array($resp) && ! $this->hasError($resp) && ! empty($resp['data'])) {
			return;
		}

		$this->post('setting', [
			'address' => '',
			'email' => '',
			'phone' => '',
			'facebook' => '',
			'twitter' => '',
			'linkedin' => '',
			'instagram' => '',
			'logo' => '',
			'favicon' => '',
			'title' => '',
			'website' => '',
		]);
	}

	/**
	 * @return object|null
	 */
	private function fetchSettingFromApi(int $settingId)
	{
		$resp = $this->getObjectFromApi('setting/'.$settingId);
		if ($this->hasError($resp)) {
			return null;
		}
		if (is_object($resp) && isset($resp->data) && is_object($resp->data)) {
			return $resp->data;
		}

		return is_object($resp) ? $resp : null;
	}

	/**
	 * Fila única de settings: id 1 o la primera disponible; objeto vacío si no hay datos.
	 *
	 * @return object
	 */
	protected function getSiteSettingsRow()
	{
		$this->ensureDefaultSettingsRow();

		$row = $this->fetchSettingFromApi(self::DEFAULT_SETTING_ID);
		if ($row === null) {
			$resp = $this->getDataFromApi('setting', [
				'page' => 1,
				'perPage' => 1,
				'orderProperty' => 'settingId',
				'orderDirection' => 'asc',
			], true);

			if (is_array($resp) && ! $this->hasError($resp) && ! empty($resp['data'][0])) {
				$first = $resp['data'][0];
				$row = is_array($first) ? (object) $first : $first;
			}
		}

		if (! $row) {
			return (object) [
				'settingId' => self::DEFAULT_SETTING_ID,
				'address' => '',
				'email' => '',
				'phone' => '',
				'facebook' => '',
				'twitter' => '',
				'linkedin' => '',
				'instagram' => '',
				'logo' => '',
				'favicon' => '',
				'title' => '',
				'website' => '',
			];
		}

		return $row;
	}

	/**
	 * @return int
	 */
	private function resolveSettingId(): int
	{
		$row = $this->getSiteSettingsRow();
		$id = (int) ($row->settingId ?? 0);

		return $id > 0 ? $id : self::DEFAULT_SETTING_ID;
	}

	public function site_settings()
	{
		$pt = $this->settingsTexts();
		$data = [
			'title' => $pt['page_title_site'] ?? 'Site settings',
			'page' => 'settings',
			'subpage' => 'site_settings',
			'pageTexts' => $pt,
		];
		$data['data'] = $this->getSiteSettingsRow();
		$this->load->view('admin/header', $data);
		$this->load->view('admin/site_settings', $data);
		$this->load->view('admin/footer', $data);
	}


	public function save()
	{
		$pt = $this->settingsTexts();
		if ($this->input->post('settings')) {

			$this->ensureDefaultSettingsRow();
			$phoneRaw = $this->testInput((string) $this->input->post('phone'));
			if (! $this->isSettingsPhoneNumeric($phoneRaw)) {
				$text = admin_lang_string('cmp-admin-settings', 'phone_must_be_numeric', 'Phone: digits only.');
				$this->flashMessage(json_encode([$text, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE));
				redirect(admin_url('settings/site_settings'), 'refresh');

				return;
			}
			$emailRaw = trim($this->lc((string) $this->input->post('email')));
			if ($emailRaw === '' || filter_var($emailRaw, FILTER_VALIDATE_EMAIL) === false) {
				$text = admin_lang_string('cmp-admin-settings', 'email_invalid', 'Email: invalid.');
				$this->flashMessage(json_encode([$text, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE));
				redirect(admin_url('settings/site_settings'), 'refresh');

				return;
			}
			$mydata = array(
				'address' => $this->testInput($this->input->post('address')),
				'email' => $emailRaw,
				'phone' => $phoneRaw,
				'facebook' => $this->testInput($this->input->post('facebook')),
				'twitter' => $this->testInput($this->input->post('twitter')),
				'linkedin' => $this->testInput($this->input->post('linkedin')),
				'instagram' => $this->testInput($this->input->post('instagram'))
			);

			$response = $this->put('setting/'.$this->resolveSettingId(), $mydata);
			if ($this->hasError($response)) {
				$msg = 'error';
			} else {
				$text = (string) ($pt['site_saved_success'] ?? 'Settings saved successfully.');
				$msg = json_encode([$text, 'success', '#A5DC86'], JSON_UNESCAPED_UNICODE);
			}
			$this->flashMessage($msg);
		}
		redirect(admin_url('settings/site_settings'),'refresh');
	}




	/**
	 * Logo de plataforma fijo (Full Vendor). Rutas antiguas redirigen a configuración del sitio.
	 */
	public function logo()
	{
		redirect(admin_url('settings/site_settings'), 'refresh');
	}

	/**
	 * @deprecated Logo de plataforma fijo; ya no se aceptan subidas.
	 */
	public function logosave()
	{
		redirect(admin_url('settings/site_settings'), 'refresh');
	}

	/**
	 * Teléfono: solo dígitos; se permiten espacios, guiones, paréntesis y un + inicial para formato.
	 */
	private function isSettingsPhoneNumeric(string $phone): bool
	{
		$t = trim($phone);
		if ($t === '') {
			return false;
		}
		$normalized = preg_replace('/\s+|-|\(|\)/', '', $t);
		if ($normalized !== '' && $normalized[0] === '+') {
			$normalized = substr($normalized, 1);
		}

		return $normalized !== '' && ctype_digit($normalized);
	}

	/**
	 * @return array<string, string>
	 */
	private function settingsTexts(): array
	{
		$merged = admin_lang_merged_section('cmp-admin-settings');
		$out = [];
		foreach ($merged as $key => $value) {
			if (is_string($value) && trim($value) !== '') {
				$out[$key] = $value;
			}
		}

		return $out;
	}

}

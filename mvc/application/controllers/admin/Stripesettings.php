<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Stripesettings extends MY_Controller
{
	/** @var array<string, string>|null */
	private $ssAdminTextsCache = null;

	public function __construct()
	{
		parent::__construct();
		$this->load->helper('admin_shell');
		$this->checkLoggedInOrLoginWithRedirect();
	}

	public function index(): void
	{
		$config = $this->fetchStripeConfigFromApi();

		$data = [
			'title' => $this->ssLang('page_title', 'Stripe settings'),
			'page' => 'stripe_settings',
			'subpage' => 'stripe_settings',
			'pageTexts' => $this->ssAdminTexts(),
			'entidad' => $config['entidad'],
			'stripeSecret' => $config['stripeSecret'],
			'stripePublish' => $config['stripePublish'],
		];

		$this->load->view('admin/header', $data);
		$this->load->view('admin/stripe_settings_form', $data);
		$this->load->view('admin/footer');
	}

	public function update(): void
	{
		$pt = $this->ssAdminTexts();
		$payload = [
			'entity_name' => (string) $this->input->post('entity_name'),
			'entity_address' => (string) $this->input->post('entity_address'),
			'entity_country' => (string) $this->input->post('entity_country'),
			'entity_state' => (string) $this->input->post('entity_state'),
			'entity_city' => (string) $this->input->post('entity_city'),
			'stripe_secret' => (string) $this->input->post('stripe_secret'),
			'stripe_publish' => (string) $this->input->post('stripe_publish'),
		];

		$response = $this->put('stripe-config', $payload, true);
		$savedAny = false;
		$errors = [];

		if (is_array($response) && ! $this->hasError($response)) {
			$savedAny = ! empty($response['saved_any']);
			$apiErrors = $response['errors'] ?? [];
			if (is_array($apiErrors)) {
				foreach ($apiErrors as $code) {
					$errors[] = $this->mapStripeConfigApiError((string) $code, $pt);
				}
			}
		} else {
			$errors[] = (string) ($pt['msg_save_entidad_failed'] ?? 'Stripe settings could not be saved.');
		}

		if ($errors !== []) {
			$this->flashMessage(json_encode([
				implode(' ', $errors),
				$savedAny ? 'warning' : 'error',
				$savedAny ? '#DD6B55' : '#F27474',
			], JSON_UNESCAPED_UNICODE));
		} else {
			$this->flashMessage(json_encode([
				(string) ($pt['msg_saved'] ?? 'Stripe settings saved.'),
				'success',
				'#A5DC86',
			], JSON_UNESCAPED_UNICODE));
		}

		redirect(admin_url('stripesettings'), 'refresh');
	}

	/**
	 * @return array{entidad: object|null, stripeSecret: object, stripePublish: object}
	 */
	private function fetchStripeConfigFromApi(): array
	{
		$resp = $this->getDataFromApi('stripe-config', [], true);
		if (! is_array($resp) || $this->hasError($resp)) {
			return [
				'entidad' => null,
				'stripeSecret' => (object) ['id' => 0, 'secret' => ''],
				'stripePublish' => (object) ['stripeID' => 0, 'secretAPI' => ''],
			];
		}

		$data = $resp['data'] ?? [];
		if (! is_array($data)) {
			$data = [];
		}

		$entidad = null;
		if (isset($data['issuing_entity']) && is_array($data['issuing_entity'])) {
			$entidad = (object) $data['issuing_entity'];
		} elseif (isset($data['issuing_entity']) && is_object($data['issuing_entity'])) {
			$entidad = $data['issuing_entity'];
		}

		$secret = $data['stripe_secret'] ?? ['id' => 0, 'secret' => ''];
		$publish = $data['stripe_publish'] ?? ['stripeID' => 0, 'secretAPI' => ''];

		return [
			'entidad' => $entidad,
			'stripeSecret' => is_object($secret) ? $secret : (object) (is_array($secret) ? $secret : ['id' => 0, 'secret' => '']),
			'stripePublish' => is_object($publish) ? $publish : (object) (is_array($publish) ? $publish : ['stripeID' => 0, 'secretAPI' => '']),
		];
	}

	/**
	 * @param array<string, string> $pt
	 */
	private function mapStripeConfigApiError(string $code, array $pt): string
	{
		$map = [
			'missing_issuing_entity_table' => (string) ($pt['msg_missing_entidad_table']
				?? 'Issuing entity table is missing. Create the issuing_entity table first.'),
			'invalid_issuing_entity' => (string) ($pt['msg_validation_entidad'] ?? 'Issuing entity is invalid.'),
			'save_issuing_entity_failed' => (string) ($pt['msg_save_entidad_failed'] ?? 'Issuing entity could not be saved.'),
			'save_stripe_secret_failed' => (string) ($pt['msg_save_secret_failed'] ?? 'Stripe secret could not be saved.'),
			'save_stripe_publish_failed' => (string) ($pt['msg_save_publish_failed'] ?? 'Stripe publishable key could not be saved.'),
		];

		return $map[$code] ?? $code;
	}

	/**
	 * @return array<string, string>
	 */
	private function ssAdminTexts(): array
	{
		if ($this->ssAdminTextsCache !== null) {
			return $this->ssAdminTextsCache;
		}
		if (function_exists('admin_stripe_settings_page_texts')) {
			$this->ssAdminTextsCache = admin_stripe_settings_page_texts(null);
		} elseif (function_exists('admin_lang_cmp_admin_stripe_settings_texts')) {
			$this->ssAdminTextsCache = admin_lang_cmp_admin_stripe_settings_texts();
		} else {
			$this->ssAdminTextsCache = $this->emergencyTexts();
		}

		return $this->ssAdminTextsCache;
	}

	private function ssLang(string $key, string $fallback = ''): string
	{
		$all = $this->ssAdminTexts();
		if (isset($all[$key]) && $all[$key] !== '') {
			return $all[$key];
		}

		return $fallback;
	}

	/**
	 * @return array<string, string>
	 */
	private function emergencyTexts(): array
	{
		return [
			'page_title' => 'Stripe settings',
			'section_issuing_entity' => 'Issuing entity',
			'section_stripe_secret' => 'Stripe secret key',
			'section_stripe_publish' => 'Stripe publishable key',
			'label_entidad_emisora' => 'Entity name',
			'label_entidad_direccion' => 'Address',
			'label_entidad_pais' => 'Country',
			'label_entidad_estado' => 'State / region',
			'label_entidad_ciudad' => 'City',
			'label_stripe_secret' => 'Secret key (sk_…)',
			'label_stripe_publish' => 'Publishable key (pk_…)',
			'help_stripe_secret' => 'Stored in the stripe_secret table; used by the API to call Stripe.',
			'help_stripe_publish' => 'Stored in the stripe_publish table; legacy column used by the resolver fallback.',
			'btn_submit' => 'Save changes',
			'btn_cancel' => 'Cancel',
			'msg_saved' => 'Stripe settings saved.',
			'msg_validation_entidad' => 'Issuing entity is invalid (name is required and must not exceed 100 chars).',
			'msg_missing_entidad_table' => 'Issuing entity table is missing. Create the issuing_entity table first.',
			'msg_save_entidad_failed' => 'Issuing entity could not be saved.',
			'msg_save_secret_failed' => 'Stripe secret could not be saved.',
			'msg_save_publish_failed' => 'Stripe publishable key could not be saved.',
		];
	}
}

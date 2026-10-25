<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Plan extends MY_Controller
{
	private const BILLING_MONTHLY = 'monthly';

	private const BILLING_ANNUAL = 'annual';

	/** Valores enviados por el formulario (POST). En BD se guarda 1 / 0 (bit/tinyint). */
	private const FORM_STATUS_ENABLED = 'enabled';

	private const FORM_STATUS_DISABLED = 'disabled';

	/** @var array<string, string>|null */
	private $planAdminTextsCache = null;

	/** @var string|null */
	private $planPkCache = null;

	/** @var int */
	private $lastCreatedPlanId = 0;

	public function __construct()
	{
		parent::__construct();
		$this->load->helper('admin_shell');
		$this->checkLoggedInOrLoginWithRedirect();
	}

	public function lists()
	{
		if (! $this->assertPlanModuleInstalled()) {
			return;
		}

		$data = [
			'title' => $this->planLang('list_title', 'Plans'),
			'page' => 'plan',
			'subpage' => 'planlist',
			'pageTexts' => $this->planAdminTexts(),
			'languagesById' => $this->planLanguagesById(),
			'list' => $this->fetchPlanListFromApi(),
			'planPk' => $this->planPrimaryKeyColumn(),
			'planStatusUrl' => admin_url('plan/changeStatus'),
		];

		$this->load->view('admin/header', $data);
		$this->load->view('admin/plan_list', $data);
		$this->load->view('admin/footer');
	}

	public function add()
	{
		if (! $this->assertPlanModuleInstalled()) {
			return;
		}

		$planCap = $this->planCapabilities();

		$data = [
			'title' => $this->planLang('form_add_title', 'New plan'),
			'page' => 'plan',
			'subpage' => 'planlist',
			'pageTexts' => $this->planAdminTexts(),
			'language' => $this->fetchActiveLanguagesFromApi(),
			'hasLangSlots' => (bool) ($planCap['hasLangSlots'] ?? false),
		];

		$this->load->view('admin/header', $data);
		$this->load->view('admin/plan_add', $data);
		$this->load->view('admin/footer');
	}

	public function create()
	{
		if (! admin_module_installed('plan')) {
			$this->flashMessage('error');
			redirect(admin_url('plan/lists'), 'refresh');

			return;
		}

		$parsed = $this->readPlanPostRow();
		if (! $parsed['ok']) {
			$this->flashMessage(json_encode([
				$this->planLang(
					(string) ($parsed['msg_key'] ?? 'msg_validation_error'),
					'Please check the form and try again.'
				),
				'warning',
				'#DD6B55',
			], JSON_UNESCAPED_UNICODE));
			redirect(admin_url('plan/add'), 'refresh');

			return;
		}

		$row = $parsed['row'];
		$now = date('Y-m-d H:i:s');
		$row['created_at'] = $now;
		$row['updated_at'] = $now;
		$row = $this->filterPlanTableRow($row);

		if (! $this->createPlanViaApi($row)) {
			$this->flashMessage('error');
		} else {
			$newPlanId = $this->lastCreatedPlanId;
			$this->flashMessage(json_encode([
				$this->planLang('msg_created', 'Plan created.'),
				'success',
				'#A5DC86',
			], JSON_UNESCAPED_UNICODE));
			$stripeWarn = $this->trySyncStripePlanCatalog($newPlanId, 'msg_stripe_plan_on_create_failed');
			if ($stripeWarn !== '') {
				$this->flashMessage(json_encode([$stripeWarn, 'warning', '#DD6B55'], JSON_UNESCAPED_UNICODE));
			}
		}

		redirect(admin_url('plan/lists'), 'refresh');
	}

	/**
	 * Sincroniza el plan con Stripe (Product + Price). No bloquea el guardado local.
	 */
	private function trySyncStripePlanCatalog(int $planId, string $failureMsgKey): string
	{
		if ($planId < 1) {
			return '';
		}
		if (! (bool) ($this->planCapabilities()['hasStripeFields'] ?? false)) {
			return '';
		}

		$resp = $this->post('stripe/plans/' . $planId . '/sync-catalog', []);
		if ($this->hasError($resp)) {
			$msg = $this->extractStripeApiErrorMessage($resp);
			if ($msg !== '') {
				return $msg;
			}

			return $this->planLang(
				$failureMsgKey,
				'The plan was saved locally but it could not be synced with Stripe. Use Sync Stripe catalog on edit.'
			);
		}

		return '';
	}

	/**
	 * @param array<string, mixed>|object|null $resp
	 */
	private function extractStripeApiErrorMessage($resp): string
	{
		if (! is_array($resp)) {
			return $this->planLang('msg_stripe_api_error', 'Error communicating with Stripe.');
		}

		if (isset($resp['stripe_error']) && is_string($resp['stripe_error']) && trim($resp['stripe_error']) !== '') {
			return $this->planLang('msg_stripe_api_error', 'Stripe API error') . ': ' . trim($resp['stripe_error']);
		}

		$payload = $resp['message'] ?? null;
		if (is_string($payload) && trim($payload) !== '') {
			return trim($payload);
		}
		if (is_array($payload)) {
			if (isset($payload['message']) && is_string($payload['message']) && trim($payload['message']) !== '') {
				return trim($payload['message']);
			}
			$key = isset($payload['message_key']) ? (string) $payload['message_key'] : '';
			if ($key !== '') {
				$fromKey = $this->planLang($key, '');
				if ($fromKey !== '' && $fromKey !== $key) {
					return $fromKey;
				}
			}
		}

		return $this->planLang('msg_stripe_api_error', 'Error communicating with Stripe.');
	}

	public function edit($id = false)
	{
		if (! $this->assertPlanModuleInstalled()) {
			return;
		}

		if ($id === false || $id === '') {
			show_404();
		}
		$sid = (int) $id;
		$pk = $this->planPrimaryKeyColumn();
		if ($sid < 1 || $this->fetchPlanById($sid) === null) {
			show_404();
		}

		$planCap = $this->planCapabilities();
		$data = [
			'title' => $this->planLang('form_edit_title', 'Edit plan'),
			'page' => 'plan',
			'subpage' => 'planlist',
			'pageTexts' => $this->planAdminTexts(),
			'language' => $this->fetchActiveLanguagesFromApi(),
			'planPk' => $pk,
			'data' => $this->fetchPlanById($sid),
			'hasLangSlots' => (bool) ($planCap['hasLangSlots'] ?? false),
		];

		$this->load->view('admin/header', $data);
		$this->load->view('admin/plan_edit', $data);
		$this->load->view('admin/footer');
	}

	public function update()
	{
		if (! admin_module_installed('plan')) {
			$this->flashMessage('error');
			redirect(admin_url('plan/lists'), 'refresh');

			return;
		}

		$sid = $this->postedPlanRowId();
		if ($sid < 1) {
			show_404();
		}

		$parsed = $this->readPlanPostRow();
		if (! $parsed['ok']) {
			$this->flashMessage(json_encode([
				$this->planLang(
					(string) ($parsed['msg_key'] ?? 'msg_validation_error'),
					'Please check the form and try again.'
				),
				'warning',
				'#DD6B55',
			], JSON_UNESCAPED_UNICODE));
			redirect(admin_url('plan/edit/' . $sid), 'refresh');

			return;
		}

		$row = $parsed['row'];
		$row['updated_at'] = date('Y-m-d H:i:s');
		$row = $this->filterPlanTableRow($row);
		$pk = $this->planPrimaryKeyColumn();
		$where = [$pk => $sid];

		if (! $this->updatePlanViaApi($sid, $row)) {
			$this->flashMessage('error');
		} else {
			$this->flashMessage(json_encode([
				$this->planLang('msg_updated', 'Plan updated.'),
				'success',
				'#A5DC86',
			], JSON_UNESCAPED_UNICODE));
			$stripeWarn = $this->trySyncStripePlanCatalog($sid, 'msg_stripe_plan_on_update_failed');
			if ($stripeWarn !== '') {
				$this->flashMessage(json_encode([$stripeWarn, 'warning', '#DD6B55'], JSON_UNESCAPED_UNICODE));
			}
		}

		redirect(admin_url('plan/lists'), 'refresh');
	}

	public function changeStatus(): void
	{
		header('Content-Type: application/json; charset=utf-8');
		$errMsg = $this->planLang('plan_status_update_error', 'The status could not be updated. Please try again.');
		if (! admin_module_installed('plan')) {
			echo json_encode([$errMsg, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE);

			return;
		}
		if (strtoupper((string) $this->input->method()) !== 'POST') {
			echo json_encode([$errMsg, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE);

			return;
		}
		$planRowId = $this->postedPlanRowId();
		$subscription_enabled = $this->postedPlanEnabledFlag();
		if ($planRowId < 1 || ($subscription_enabled !== 0 && $subscription_enabled !== 1)) {
			echo json_encode([$errMsg, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE);

			return;
		}
		$pk = $this->planPrimaryKeyColumn();
		$where = [$pk => $planRowId];
		if ($this->fetchPlanById($planRowId) === null) {
			echo json_encode([$errMsg, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE);

			return;
		}
		$newStatus = $subscription_enabled === 1 ? 1 : 0;
		$row = $this->filterPlanTableRow([
			'status' => $newStatus,
			'updated_at' => date('Y-m-d H:i:s'),
		]);
		if ($row === [] || ! $this->updatePlanViaApi($planRowId, $row)) {
			echo json_encode([$errMsg, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE);

			return;
		}
		if ($subscription_enabled === 1) {
			$okMsg = $this->planLang('plan_status_enabled_success', 'Plan enabled.');
		} else {
			$okMsg = $this->planLang('plan_status_disabled_success', 'Plan disabled.');
		}
		echo json_encode([$okMsg, 'success', '#A5DC86'], JSON_UNESCAPED_UNICODE);
	}

	public function delete($id = false)
	{
		if (! admin_module_installed('plan')) {
			$this->flashMessage('error');
			redirect(admin_url('plan/lists'), 'refresh');

			return;
		}

		if ($id === false || $id === '') {
			redirect(admin_url('plan/lists'), 'refresh');

			return;
		}
		$sid = (int) $id;
		if ($sid < 1) {
			redirect(admin_url('plan/lists'), 'refresh');

			return;
		}

		$pk = $this->planPrimaryKeyColumn();
		$where = [$pk => $sid];
		if ($this->fetchPlanById($sid) === null) {
			$this->flashMessage(json_encode([
				$this->planLang('msg_not_found', 'Plan not found.'),
				'warning',
				'#DD6B55',
			], JSON_UNESCAPED_UNICODE));
			redirect(admin_url('plan/lists'), 'refresh');

			return;
		}

		if ($this->countActiveSubscriptionsForPlan($sid) > 0) {
			$this->flashMessage(json_encode([
				$this->planLang(
					'msg_plan_delete_blocked_active_subscriptions',
					'You cannot delete a plan that has an active subscription linked to it.'
				),
				'warning',
				'#DD6B55',
			], JSON_UNESCAPED_UNICODE));
			redirect(admin_url('plan/lists'), 'refresh');

			return;
		}

		if (! $this->deletePlanViaApi($sid)) {
			$this->flashMessage('error');
		} else {
			$this->flashMessage(json_encode([
				$this->planLang('msg_deleted', 'Plan deleted.'),
				'success',
				'#A5DC86',
			], JSON_UNESCAPED_UNICODE));
		}

		redirect(admin_url('plan/lists'), 'refresh');
	}

	private function planPrimaryKeyColumn(): string
	{
		if ($this->planPkCache !== null) {
			return $this->planPkCache;
		}
		if (function_exists('admin_plan_primary_key_column')) {
			return $this->planPkCache = admin_plan_primary_key_column();
		}

		return $this->planPkCache = 'plan_id';
	}

	private function postedPlanRowId(): int
	{
		$id = (int) $this->input->post('plan_id');
		if ($id < 1) {
			$id = (int) $this->input->post('subscription_id');
		}

		return $id;
	}

	private function postedPlanEnabledFlag(): int
	{
		$enabled = $this->input->post('plan_enabled');
		if ($enabled === null || $enabled === '') {
			$enabled = $this->input->post('subscription_enabled');
		}

		return (int) $enabled;
	}

	/**
	 * Suscripciones activas (status = 1) en `subscriptions` con este plan_id (= PK de `plan`).
	 */
	private function countActiveSubscriptionsForPlan(int $planId): int
	{
		if ($planId < 1) {
			return 0;
		}
		$subsCap = function_exists('admin_capability_section')
			? admin_capability_section('subscriptions')
			: [];
		if (! (bool) ($subsCap['installed'] ?? false)) {
			return 0;
		}
		if (! (bool) ($subsCap['hasPlanId'] ?? false) || ! (bool) ($subsCap['hasStatus'] ?? false)) {
			return 0;
		}

		$resp = $this->getDataFromApi('subscription', [
			'planId' => $planId,
			'status' => 1,
			'page' => 1,
			'perPage' => 1,
		], true);
		if (! is_array($resp) || $this->hasError($resp)) {
			return 0;
		}

		return (int) ($resp['meta']['total'] ?? 0);
	}

	/**
	 * @return array{ok: bool, row: ?array<string, int|float|string>, msg_key: string}
	 */
	private function readPlanPostRow(): array
	{
		$fail = static function (string $msgKey): array {
			return ['ok' => false, 'row' => null, 'msg_key' => $msgKey];
		};

		$language1Id = 0;
		$language2Id = 0;
		$planCap = $this->planCapabilities();
		if (($planCap['hasLanguage1Id'] ?? false) || ($planCap['hasLanguage2Id'] ?? false)) {
			$language1Id = $this->readPostedLanguageSlotId('language1_use', 'language1_id');
			$language2Id = ($planCap['hasLanguage2Id'] ?? false)
				? $this->readPostedLanguageSlotId('language2_use', 'language2_id')
				: 0;
			if ($language1Id === -1 || $language2Id === -1) {
				return $fail('msg_validation_error');
			}
			if ($language1Id === 0 && $language2Id === 0) {
				return $fail('msg_plan_language_required');
			}
			if ($language1Id > 0 && $language2Id > 0 && $language1Id === $language2Id) {
				return $fail('msg_plan_duplicate_language');
			}
		} elseif ($planCap['hasSingleLanguageId'] ?? false) {
			$language1Id = $this->readPostedLanguageSlotId('language1_use', 'language1_id');
			if ($language1Id === -1 || $language1Id === 0) {
				return $fail('msg_plan_language_required');
			}
		}

		$title = trim((string) $this->input->post('title'));
		$description = trim((string) $this->input->post('description'));
		$billing = strtolower(trim((string) $this->input->post('billing_period')));
		$statusPost = strtolower(trim((string) $this->input->post('status')));
		$maxClients = (int) $this->input->post('max_clients');
		$maxOrders = (int) $this->input->post('max_orders');
		$maxUsers = (int) $this->input->post('max_users');
		$priceRaw = str_replace(',', '.', trim((string) $this->input->post('price')));

		$lenFn = static function (string $s): int {
			return function_exists('mb_strlen') ? (int) mb_strlen($s, 'UTF-8') : strlen($s);
		};

		$titleMax = $this->planColumnMaxLength('title', 150);
		$descMax = $this->planColumnMaxLength('description', 80);

		if ($title === '' || $lenFn($title) > $titleMax) {
			return $fail('msg_validation_error');
		}
		if ($lenFn($description) > $descMax) {
			return $fail('msg_validation_error');
		}

		$allowedBilling = [self::BILLING_MONTHLY, self::BILLING_ANNUAL];
		if (! in_array($billing, $allowedBilling, true)) {
			return $fail('msg_validation_error');
		}

		if ($statusPost === self::FORM_STATUS_ENABLED) {
			$statusDb = 1;
		} elseif ($statusPost === self::FORM_STATUS_DISABLED) {
			$statusDb = 0;
		} elseif (in_array($statusPost, ['active', '1'], true)) {
			$statusDb = 1;
		} elseif (in_array($statusPost, ['cancelled', 'expired', 'disabled', '0'], true)) {
			$statusDb = 0;
		} else {
			return $fail('msg_validation_error');
		}

		if ($maxClients < 0 || $maxOrders < 0 || $maxUsers < 0) {
			return $fail('msg_validation_error');
		}

		if ($priceRaw === '' || ! is_numeric($priceRaw)) {
			return $fail('msg_validation_error');
		}
		$price = round((float) $priceRaw, 2);
		if ($price < 0) {
			return $fail('msg_validation_error');
		}

		$ecommercePost = $this->input->post('ecommerce_permission');
		$ecommercePerm = ((int) $ecommercePost === 1) ? 1 : 0;

		$row = [
			'language1_id' => $language1Id,
			'language2_id' => $language2Id,
			'title' => $title,
			'description' => $description,
			'billing_period' => $billing,
			'status' => $statusDb,
			'max_clients' => $maxClients,
			'max_orders' => $maxOrders,
			'max_users' => $maxUsers,
			'ecommerce_permission' => $ecommercePerm,
			'price' => $price,
		];

		$legacy = $this->readLegacyPlanFields();
		foreach ($legacy as $col => $val) {
			$row[$col] = $val;
		}

		return [
			'ok' => true,
			'row' => $this->normalizePlanRowForDatabase($row),
			'msg_key' => '',
		];
	}

	/**
	 * Lee y normaliza los campos legado (presentes en BD pero no en migraciones nuevas).
	 *
	 * @return array<string, int|float|string>
	 */
	private function readLegacyPlanFields(): array
	{
		$out = [];
		$post = $this->input;

		$strFields = [
			'plan_name_spa' => 65535,
			'plan_name_eng' => 65535,
			'line1_spa' => 65535,
			'line1_eng' => 65535,
			'paymenttextp' => 65535,
		];
		foreach ($strFields as $field => $max) {
			$v = $post->post($field);
			if ($v === null) {
				continue;
			}
			$s = trim((string) $v);
			$len = function_exists('mb_strlen') ? (int) mb_strlen($s, 'UTF-8') : strlen($s);
			if ($len > $max) {
				$s = function_exists('mb_substr') ? mb_substr($s, 0, $max, 'UTF-8') : substr($s, 0, $max);
			}
			$out[$field] = $s;
		}

		$intFields = ['days', 'max_Customers', 'paymentperiocity'];
		foreach ($intFields as $field) {
			$v = $post->post($field);
			if ($v === null || $v === '') {
				continue;
			}
			$n = (int) $v;
			$out[$field] = $n < 0 ? 0 : $n;
		}

		$payment = $post->post('payment');
		if ($payment !== null && $payment !== '') {
			$p = (float) str_replace(',', '.', (string) $payment);
			$out['payment'] = $p < 0 ? 0.0 : $p;
		}

		$never = $post->post('neverexpire');
		if ($never !== null) {
			$out['neverexpire'] = ((int) $never === 1) ? 1 : 0;
		}

		return $out;
	}

	private function planColumnMaxLength(string $column, int $default): int
	{
		static $cache = [];
		if (isset($cache[$column])) {
			return $cache[$column];
		}
		$planCap = $this->planCapabilities();
		$lengths = $planCap['columnMaxLengths'] ?? [];
		if (is_array($lengths) && isset($lengths[$column])) {
			$len = (int) $lengths[$column];

			return $cache[$column] = $len > 0 ? $len : $default;
		}
		if (! $this->planColumnExists($column)) {
			return $cache[$column] = $default;
		}

		return $cache[$column] = $default;
	}

	/**
	 * Ajusta el payload al esquema real de `plan` (language1/2, status tinyint, etc.).
	 *
	 * @param array<string, int|float|string> $row
	 *
	 * @return array<string, int|float|string>
	 */
	private function normalizePlanRowForDatabase(array $row): array
	{
		if (! admin_module_installed('plan')) {
			return $row;
		}

		$planCap = $this->planCapabilities();
		if (($planCap['hasSingleLanguageId'] ?? false) && ! ($planCap['hasLanguage1Id'] ?? false)) {
			$l1 = (int) ($row['language1_id'] ?? 0);
			$l2 = (int) ($row['language2_id'] ?? 0);
			$row['language_id'] = $l1 > 0 ? $l1 : ($l2 > 0 ? $l2 : 0);
			unset($row['language1_id'], $row['language2_id']);
		} elseif ($planCap['hasLanguage1Id'] ?? false) {
			unset($row['language_id']);
		}

		if (! ($planCap['hasLanguage2Id'] ?? false)) {
			unset($row['language2_id']);
		}

		if (! ($planCap['hasStatus'] ?? false) && ($planCap['hasLifecycleStatus'] ?? false)) {
			$st = (int) ($row['status'] ?? 0);
			$row['lifecycle_status'] = $st === 1 ? 'active' : 'cancelled';
			unset($row['status']);
		} elseif ($planCap['hasStatus'] ?? false) {
			unset($row['lifecycle_status']);
			$row['status'] = (int) ($row['status'] ?? 0) === 1 ? 1 : 0;
		}

		return $this->filterPlanTableRow($row);
	}

	/**
	 * @param array<string, int|float|string> $row
	 *
	 * @return array<string, int|float|string>
	 */
	private function filterPlanTableRow(array $row): array
	{
		$columns = $this->planCapabilities()['columns'] ?? [];
		if (! is_array($columns) || $columns === []) {
			return $row;
		}
		$out = [];
		foreach ($row as $col => $val) {
			if (in_array($col, $columns, true)) {
				$out[$col] = $val;
			}
		}

		return $out;
	}

	/**
	 * Checkbox + select: si no está marcado el uso, devuelve 0; si está marcado, valida el id de idioma activo.
	 *
	 * @return int 0 sin idioma, >0 id válido, -1 inválido
	 */
	private function readPostedLanguageSlotId(string $useField, string $idField): int
	{
		$use = (int) $this->input->post($useField);
		$id = (int) $this->input->post($idField);
		if ($use !== 1) {
			return 0;
		}
		if ($id < 1) {
			return -1;
		}
		if ($this->validateActiveLanguageId($id) < 1) {
			return -1;
		}

		return $id;
	}

	/**
	 * @return array<int, object>
	 */
	private function fetchPlanListFromApi(): array
	{
		$resp = $this->getDataFromApi('plan', [
			'all' => true,
			'orderProperty' => $this->planPrimaryKeyColumn(),
			'orderDirection' => 'desc',
		], true);
		if (! is_array($resp) || $this->hasError($resp)) {
			return [];
		}
		$rows = $this->extractApiListRows($resp);
		if (! is_array($rows)) {
			return [];
		}
		$out = [];
		foreach ($rows as $row) {
			$out[] = is_array($row) ? (object) $row : $row;
		}

		return $out;
	}

	/**
	 * @return array<int, object>
	 */
	private function fetchActiveLanguagesFromApi(): array
	{
		$resp = $this->getDataFromApi('language', [
			'all' => true,
			'status' => 1,
			'orderProperty' => 'language_id',
			'orderDirection' => 'asc',
		], true);
		if (! is_array($resp) || $this->hasError($resp)) {
			return [];
		}
		$rows = $this->extractApiListRows($resp);
		if (! is_array($rows)) {
			return [];
		}
		$out = [];
		foreach ($rows as $row) {
			$out[] = is_array($row) ? (object) $row : $row;
		}

		return $out;
	}

	private function fetchPlanById(int $id): ?object
	{
		if ($id < 1) {
			return null;
		}
		$resp = $this->getObjectFromApi('plan/'.$id);
		if ($this->hasError($resp)) {
			return null;
		}
		if (is_object($resp) && isset($resp->data) && is_object($resp->data)) {
			return $resp->data;
		}

		return is_object($resp) ? $resp : null;
	}

	/**
	 * @param array<string, mixed> $row
	 */
	private function createPlanViaApi(array $row): bool
	{
		$this->lastCreatedPlanId = 0;
		$resp = $this->post('plan', $row, true);
		if (! is_array($resp) || $this->hasError($resp)) {
			return false;
		}
		$data = $resp['data'] ?? [];
		if (is_array($data)) {
			$this->lastCreatedPlanId = (int) ($data['plan_id'] ?? $data['id'] ?? 0);
		} elseif (is_object($data)) {
			$this->lastCreatedPlanId = (int) ($data->plan_id ?? $data->id ?? 0);
		}

		return $this->lastCreatedPlanId > 0;
	}

	/**
	 * @param array<string, mixed> $row
	 */
	private function updatePlanViaApi(int $id, array $row): bool
	{
		if ($id < 1) {
			return false;
		}
		$resp = $this->put('plan/'.$id, $row, true);

		return is_array($resp) && ! $this->hasError($resp);
	}

	private function deletePlanViaApi(int $id): bool
	{
		if ($id < 1) {
			return false;
		}
		$resp = $this->apiDelete('plan/'.$id, [], true);

		return ! $this->hasError($resp);
	}

	private function validateActiveLanguageId(int $id): int
	{
		if ($id < 1) {
			return -1;
		}
		$resp = $this->getObjectFromApi('language/'.$id);
		if ($this->hasError($resp)) {
			return -1;
		}
		$data = is_object($resp) && isset($resp->data) ? $resp->data : $resp;
		if (! is_object($data) || (int) ($data->status ?? 0) !== 1) {
			return -1;
		}

		return $id;
	}

	/**
	 * @return array<int, string>
	 */
	private function planLanguagesById(): array
	{
		$rows = $this->fetchActiveLanguagesFromApi();
		$out = [];
		if (! is_array($rows) && ! is_object($rows)) {
			return $out;
		}
		foreach ($rows as $r) {
			$id = isset($r->language_id) ? (int) $r->language_id : 0;
			if ($id > 0) {
				$out[$id] = trim((string) ($r->name ?? ''));
			}
		}

		return $out;
	}

	/**
	 * Textos cmp-admin-plan (resolución de rutas vía admin_shell_helper).
	 *
	 * @return array<string, string>
	 */
	private function planAdminTexts(): array
	{
		if ($this->planAdminTextsCache !== null) {
			return $this->planAdminTextsCache;
		}
		if (function_exists('admin_plan_page_texts')) {
			$this->planAdminTextsCache = admin_plan_page_texts(null);
		} elseif (function_exists('admin_lang_cmp_admin_plan_texts')) {
			$this->planAdminTextsCache = admin_lang_cmp_admin_plan_texts();
		} else {
			$this->planAdminTextsCache = [];
		}

		return $this->planAdminTextsCache;
	}

	private function planLang(string $key, string $fallback = ''): string
	{
		$all = $this->planAdminTexts();
		if (isset($all[$key]) && $all[$key] !== '') {
			return $all[$key];
		}

		return $fallback;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function planCapabilities(): array
	{
		if (function_exists('admin_capability_section')) {
			return admin_capability_section('plan');
		}

		return [];
	}

	private function assertPlanModuleInstalled(): bool
	{
		if (function_exists('admin_module_installed') && admin_module_installed('plan')) {
			return true;
		}
		show_error($this->planLang('table_missing', 'The plan table is not installed. Run database migrations.'), 503);

		return false;
	}

	private function planColumnExists(string $column): bool
	{
		$columns = $this->planCapabilities()['columns'] ?? [];
		if (is_array($columns) && $columns !== []) {
			return in_array($column, $columns, true);
		}

		return true;
	}

	public function stripe_sync_catalog($id = false): void
	{
		$sid = (int) $id;
		$pk = $this->planPrimaryKeyColumn();
		if ($sid < 1 || ! admin_module_installed('plan') || $this->fetchPlanById($sid) === null) {
			show_404();
		}

		$resp = $this->post('stripe/plans/' . $sid . '/sync-catalog', []);
		if ($this->hasError($resp)) {
			$msg = $this->extractStripeApiErrorMessage($resp);
			$this->flashMessage(json_encode([$msg, 'warning', '#DD6B55'], JSON_UNESCAPED_UNICODE));
		} else {
			$msg = is_array($resp) && isset($resp['message']) && is_string($resp['message']) && $resp['message'] !== ''
				? $resp['message']
				: $this->planLang('msg_stripe_plan_synced', 'Plan synced.');
			$this->flashMessage(json_encode([$msg, 'success', '#A5DC86'], JSON_UNESCAPED_UNICODE));
		}
		redirect(admin_url('plan/edit/' . $sid), 'refresh');
	}
}

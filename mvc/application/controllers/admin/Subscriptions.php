<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Subscriptions extends MY_Controller
{
	private const FORM_STATUS_ENABLED = 'enabled';

	private const FORM_STATUS_DISABLED = 'disabled';

	/** @var array<string, string>|null */
	private $subsAdminTextsCache = null;

	/** @var string|null */
	private $subscriptionsPkCache = null;

	/** @var int */
	private $lastCreatedSubscriptionId = 0;

	/** @var int */
	private $lastCreatedInvoiceId = 0;

	public function __construct()
	{
		parent::__construct();
		$this->load->helper('admin_shell');
		$this->checkLoggedInOrLoginWithRedirect();
	}

	public function lists(): void
	{
		if (! $this->assertSubscriptionsModuleInstalled()) {
			return;
		}

		$list = $this->fetchSubscriptionsForList();
		$pt = $this->subsAdminTexts();

		$data = [
			'title' => $this->subsLang('list_title', 'Subscriptions'),
			'page' => 'subscriptions',
			'subpage' => 'subscriptionslist',
			'pageTexts' => $pt,
			'list' => $list,
			'subscriptionsListLang' => $this->buildListLang($pt),
			'subscriptionsStatusUrl' => admin_url('subscriptions/changeStatus'),
			'subscriptionsDeleteUi' => $this->buildDeleteUi($pt),
		];

		$this->load->view('admin/header', $data);
		$this->load->view('admin/subscriptions_list', $data);
		$this->load->view('admin/footer');
	}

	public function add(): void
	{
		if (! $this->assertSubscriptionsAndPlanModules()) {
			return;
		}

		$data = [
			'title' => $this->subsLang('form_add_title', 'New subscription'),
			'page' => 'subscriptions',
			'subpage' => 'subscriptionslist',
			'pageTexts' => $this->subsAdminTexts(),
			'companies' => $this->loadCompaniesForSelect(),
			'plans' => $this->fetchPlansFromApi(),
		];

		$this->load->view('admin/header', $data);
		$this->load->view('admin/subscriptions_add', $data);
		$this->load->view('admin/footer');
	}

	public function create(): void
	{
		if (! admin_module_installed('subscriptions') || ! admin_module_installed('plan')) {
			$this->flashMessage('error');
			redirect(admin_url('subscriptions/lists'), 'refresh');

			return;
		}

		$parsed = $this->readSubscriptionPostRow();
		if (! $parsed['ok']) {
			$this->flashMessage(json_encode([
				$this->subsLang((string) ($parsed['msg_key'] ?? 'msg_validation_error'), 'Please check the form and try again.'),
				'warning',
				'#DD6B55',
			], JSON_UNESCAPED_UNICODE));
			redirect(admin_url('subscriptions/add'), 'refresh');

			return;
		}

		$row = $this->filterSubscriptionsTableRow($parsed['row']);
		$row['created_at'] = date('Y-m-d H:i:s');
		$row['updated_at'] = date('Y-m-d H:i:s');

		if (! $this->createSubscriptionViaApi($row)) {
			$this->flashMessage('error');
		} else {
			$newRowId = $this->lastCreatedSubscriptionId;
			$invoiceResult = $this->createInvoiceForNewSubscription($newRowId, $row);
			$this->syncCompanyLimitsFromActiveSubscription((int) ($row['company_id'] ?? 0));

			$this->flashMessage(json_encode([
				$this->subsLang('msg_created', 'Subscription created.'),
				'success',
				'#A5DC86',
			], JSON_UNESCAPED_UNICODE));
			if ($invoiceResult['warning'] !== '') {
				$this->flashMessage(json_encode([$invoiceResult['warning'], 'warning', '#DD6B55'], JSON_UNESCAPED_UNICODE));
			} else {
				$stripeSubWarn = $this->trySyncStripeSubscriptionAfterCreate($newRowId);
				if ($stripeSubWarn !== '') {
					$this->flashMessage(json_encode([$stripeSubWarn, 'warning', '#DD6B55'], JSON_UNESCAPED_UNICODE));
				}
			}
		}

		redirect(admin_url('subscriptions/lists'), 'refresh');
	}

	/**
	 * Crea la suscripción en Stripe (no bloquea el alta local).
	 */
	private function trySyncStripeSubscriptionAfterCreate(int $subscriptionRowId): string
	{
		if ($subscriptionRowId < 1 || ! ($this->subscriptionsCapabilities()['hasStripeSubscriptionId'] ?? false)) {
			return '';
		}

		$resp = $this->post('stripe/subscriptions/' . $subscriptionRowId . '/create', []);
		if ($this->hasError($resp)) {
			$msg = $this->extractStripeApiErrorMessage($resp);
			if ($msg !== '') {
				return $msg;
			}

			return $this->subsLang(
				'msg_stripe_subscription_on_create_failed',
				'The subscription was created but it could not be synced with Stripe. Use Create in Stripe on edit.'
			);
		}

		return '';
	}

	private function resolveInvoiceIdForSubscription(int $subscriptionRowId): int
	{
		if ($subscriptionRowId < 1) {
			return 0;
		}
		$resp = $this->getDataFromApi('invoice', [
			'subscription' => $subscriptionRowId,
			'page' => 1,
			'perPage' => 1,
			'orderProperty' => 'invoice_id',
			'orderDirection' => 'asc',
		], true);
		if (! is_array($resp) || $this->hasError($resp) || empty($resp['data'][0])) {
			return 0;
		}
		$first = $resp['data'][0];

		return (int) (is_array($first) ? ($first['invoice_id'] ?? 0) : (is_object($first) ? ($first->invoice_id ?? 0) : 0));
	}

	/**
	 * Inserta una factura local enlazada a la nueva suscripción (registro inicial).
	 *
	 * @param array<string, int|string|null> $subscriptionRow Datos persistidos en `subscriptions`.
	 *
	 * @return array{warning: string, invoice_id: int}
	 */
	private function createInvoiceForNewSubscription(int $subscriptionRowId, array $subscriptionRow): array
	{
		$empty = ['warning' => '', 'invoice_id' => 0];
		if ($subscriptionRowId < 1 || ! admin_module_installed('invoices')) {
			return $empty;
		}

		$companyId = (int) ($subscriptionRow['company_id'] ?? 0);
		$planId = (int) ($subscriptionRow['plan_id'] ?? 0);
		if ($companyId < 1 || $planId < 1 || ! admin_module_installed('plan')) {
			return $empty;
		}

		$existsResp = $this->getDataFromApi('invoice', [
			'subscription' => $subscriptionRowId,
			'page' => 1,
			'perPage' => 1,
		], true);
		$exists = 0;
		if (is_array($existsResp) && ! $this->hasError($existsResp)) {
			$exists = (int) ($existsResp['meta']['total'] ?? 0);
		}
		if ($exists > 0) {
			return [
				'warning' => '',
				'invoice_id' => $this->resolveInvoiceIdForSubscription($subscriptionRowId),
			];
		}

		$plan = $this->fetchPlanById($planId);
		if ($plan === null) {
			return [
				'warning' => $this->subsLang('msg_invoice_autocreate_failed', 'Subscription was created but the local invoice could not be generated.'),
				'invoice_id' => 0,
			];
		}
		$company = $this->fetchCompanyById($companyId);
		if ($company === null) {
			return [
				'warning' => $this->subsLang('msg_invoice_autocreate_failed', 'Subscription was created but the local invoice could not be generated.'),
				'invoice_id' => 0,
			];
		}

		$startRaw = trim((string) ($subscriptionRow['start_period_date'] ?? ''));
		if ($startRaw === '') {
			$startDate = date('Y-m-d H:i:s');
		} elseif (strpos($startRaw, ' ') === false) {
			$startDate = $startRaw . ' 00:00:00';
		} else {
			$startDate = $startRaw;
		}

		$days = isset($plan->days) ? (int) $plan->days : 0;
		$dateExpired = null;
		if ($days > 0) {
			$ts = strtotime($startDate . ' +' . $days . ' days');
			if ($ts !== false) {
				$dateExpired = date('Y-m-d H:i:s', $ts);
			}
		} elseif (! empty($subscriptionRow['end_period_date'])) {
			$endRaw = (string) $subscriptionRow['end_period_date'];
			$dateExpired = strpos($endRaw, ' ') === false ? $endRaw . ' 23:59:59' : $endRaw;
		}

		$price = isset($plan->price) ? (float) $plan->price : 0.0;
		$planTitle = trim((string) ($plan->title ?? ''));
		if ($planTitle === '') {
			$planTitle = 'Plan #' . $planId;
		}
		$companyName = trim((string) ($company->name ?? '')) ?: ('Company #' . $companyId);

		$now = date('Y-m-d H:i:s');
		$payload = [
			'subscription_id' => $subscriptionRowId,
			'company_id' => $companyId,
			'date' => $startDate,
			'date_expired' => $dateExpired,
			'paid' => 0,
			'days' => $days > 0 && $days <= 255 ? $days : null,
			'concept' => sprintf(
				$this->subsLang('invoice_concept_template', 'Subscription #%1$d · %2$s (%3$s)'),
				$subscriptionRowId,
				$planTitle,
				$companyName,
			),
			'invoice_paid' => 0,
			'debt' => $price,
			'stripe_status' => null,
			'created_at' => $now,
			'updated_at' => $now,
		];

		$entity = $this->loadFirstIssuingEntity();
		if ($entity !== null) {
			$payload['issuing_entity'] = $entity['name'];
			$payload['address_entity'] = $entity['address'];
			$payload['country_entity'] = $entity['country'];
			$payload['state_entity'] = $entity['state'];
			$payload['city_entity'] = $entity['city'];
		}

		$payload = $this->filterRowForInvoices($payload);

		if (! $this->createInvoiceViaApi($payload)) {
			log_message('error', '[subscriptions.create.invoice] insert failed via API');

			return [
				'warning' => $this->subsLang('msg_invoice_autocreate_failed', 'Subscription was created but the local invoice could not be generated.'),
				'invoice_id' => 0,
			];
		}

		return [
			'warning' => '',
			'invoice_id' => $this->lastCreatedInvoiceId,
		];
	}

	/**
	 * @return array{name: ?string, address: ?string, country: ?string, state: ?string, city: ?string}|null
	 */
	private function loadFirstIssuingEntity(): ?array
	{
		$resp = $this->getDataFromApi('stripe-config', [], true);
		if (! is_array($resp) || $this->hasError($resp)) {
			return null;
		}
		$entity = $resp['data']['issuing_entity'] ?? null;
		if (! is_array($entity) && ! is_object($entity)) {
			return null;
		}
		$row = is_array($entity) ? (object) $entity : $entity;

		return [
			'name' => isset($row->entity_name) ? (string) $row->entity_name : null,
			'address' => isset($row->entity_address) ? (string) $row->entity_address : null,
			'country' => isset($row->entity_country) ? (string) $row->entity_country : null,
			'state' => isset($row->entity_state) ? (string) $row->entity_state : null,
			'city' => isset($row->entity_city) ? (string) $row->entity_city : null,
		];
	}

	/**
	 * @param array<string, mixed> $row
	 *
	 * @return array<string, mixed>
	 */
	private function filterRowForInvoices(array $row): array
	{
		$columns = $this->invoicesCapabilities()['columns'] ?? [];
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

	public function edit($id = false): void
	{
		if (! $this->assertSubscriptionsAndPlanModules()) {
			return;
		}

		if ($id === false || $id === '') {
			show_404();
		}
		$rid = (int) $id;
		if ($rid < 1 || $this->fetchSubscriptionById($rid) === null) {
			show_404();
		}

		$data = [
			'title' => $this->subsLang('form_edit_title', 'Edit subscription'),
			'page' => 'subscriptions',
			'subpage' => 'subscriptionslist',
			'pageTexts' => $this->subsAdminTexts(),
			'companies' => $this->loadCompaniesForSelect(),
			'plans' => $this->fetchPlansFromApi(),
			'data' => $this->fetchSubscriptionById($rid),
		];

		$this->load->view('admin/header', $data);
		$this->load->view('admin/subscriptions_edit', $data);
		$this->load->view('admin/footer');
	}

	public function update(): void
	{
		if (! admin_module_installed('subscriptions') || ! admin_module_installed('plan')) {
			$this->flashMessage('error');
			redirect(admin_url('subscriptions/lists'), 'refresh');

			return;
		}

		$rid = (int) $this->input->post('subscription_row_id');
		if ($rid < 1) {
			show_404();
		}

		$parsed = $this->readSubscriptionPostRow();
		if (! $parsed['ok']) {
			$this->flashMessage(json_encode([
				$this->subsLang((string) ($parsed['msg_key'] ?? 'msg_validation_error'), 'Please check the form and try again.'),
				'warning',
				'#DD6B55',
			], JSON_UNESCAPED_UNICODE));
			redirect(admin_url('subscriptions/edit/' . $rid), 'refresh');

			return;
		}

		$row = $this->filterSubscriptionsTableRow($parsed['row']);
		$row['updated_at'] = date('Y-m-d H:i:s');

		$prevRow = $this->fetchSubscriptionById($rid);
		$prevCompanyId = is_object($prevRow) && isset($prevRow->company_id) ? (int) $prevRow->company_id : 0;

		if ($prevRow === null) {
			show_404();
		}

		if (! $this->updateSubscriptionViaApi($rid, $row)) {
			$this->flashMessage('error');
		} else {
			$companiesToSync = array_unique(array_filter([
				(int) ($row['company_id'] ?? 0),
				$prevCompanyId,
			]));
			foreach ($companiesToSync as $cid) {
				$this->syncCompanyLimitsFromActiveSubscription($cid);
			}

			$this->flashMessage(json_encode([
				$this->subsLang('msg_updated', 'Subscription updated.'),
				'success',
				'#A5DC86',
			], JSON_UNESCAPED_UNICODE));
		}

		redirect(admin_url('subscriptions/lists'), 'refresh');
	}

	public function delete($id = false): void
	{
		if (! admin_module_installed('subscriptions')) {
			$this->flashMessage('error');
			redirect(admin_url('subscriptions/lists'), 'refresh');

			return;
		}

		if ($id === false || $id === '') {
			redirect(admin_url('subscriptions/lists'), 'refresh');

			return;
		}
		$rid = (int) $id;
		if ($rid < 1) {
			redirect(admin_url('subscriptions/lists'), 'refresh');

			return;
		}

		$prev = $this->fetchSubscriptionById($rid);
		if ($prev === null) {
			$this->flashMessage(json_encode([
				$this->subsLang('msg_not_found', 'Subscription not found.'),
				'warning',
				'#DD6B55',
			], JSON_UNESCAPED_UNICODE));
			redirect(admin_url('subscriptions/lists'), 'refresh');

			return;
		}

		if ($this->subscriptionRowHasActiveInvoice($rid)) {
			$this->flashMessage(json_encode([
				$this->subsLang('msg_delete_blocked_active_invoice', 'This subscription cannot be deleted because it has an active invoice.'),
				'warning',
				'#DD6B55',
			], JSON_UNESCAPED_UNICODE));
			redirect(admin_url('subscriptions/lists'), 'refresh');

			return;
		}

		$companyAffected = is_object($prev) && isset($prev->company_id) ? (int) $prev->company_id : 0;

		if (! $this->deleteSubscriptionViaApi($rid)) {
			$this->flashMessage('error');
		} else {
			if ($companyAffected > 0) {
				$this->syncCompanyLimitsFromActiveSubscription($companyAffected);
			}
			$this->flashMessage(json_encode([
				$this->subsLang('msg_deleted', 'Subscription deleted.'),
				'success',
				'#A5DC86',
			], JSON_UNESCAPED_UNICODE));
		}

		redirect(admin_url('subscriptions/lists'), 'refresh');
	}

	public function stripe_create($id = false): void
	{
		$rid = (int) $id;
		if ($rid < 1 || ! admin_module_installed('subscriptions') || $this->fetchSubscriptionById($rid) === null) {
			show_404();
		}

		$resp = $this->post('stripe/subscriptions/' . $rid . '/create', []);
		$this->flashStripeApiResponse($resp, 'msg_stripe_subscription_created', admin_url('subscriptions/edit/' . $rid));
	}

	public function stripe_sync($id = false): void
	{
		$rid = (int) $id;
		if ($rid < 1 || ! admin_module_installed('subscriptions') || $this->fetchSubscriptionById($rid) === null) {
			show_404();
		}

		$resp = $this->post('stripe/subscriptions/' . $rid . '/sync', []);
		$this->flashStripeApiResponse($resp, 'msg_stripe_subscription_synced', admin_url('subscriptions/edit/' . $rid));
	}

	public function stripe_cancel($id = false): void
	{
		$rid = (int) $id;
		if ($rid < 1 || ! admin_module_installed('subscriptions') || $this->fetchSubscriptionById($rid) === null) {
			show_404();
		}

		$resp = $this->post('stripe/subscriptions/' . $rid . '/cancel', []);
		$this->flashStripeApiResponse($resp, 'msg_stripe_subscription_cancelled', admin_url('subscriptions/edit/' . $rid));
	}

	public function stripe_sync_all(): void
	{
		if (! admin_module_installed('subscriptions')) {
			$this->flashMessage('error');
			redirect(admin_url('subscriptions/lists'), 'refresh');

			return;
		}

		$resp = $this->post('stripe/subscriptions/sync-all', []);
		$redirect = admin_url('subscriptions/lists');
		if ($this->hasError($resp)) {
			$this->flashStripeApiResponse($resp, 'msg_stripe_subscription_synced', $redirect);

			return;
		}
		$processed = (int) ($resp['data']['processed'] ?? 0);
		$updated = (int) ($resp['data']['updated'] ?? 0);
		$msg = sprintf($this->subsLang('msg_stripe_bulk_result', 'Stripe: %1$d updated of %2$d processed.'), $updated, $processed);
		$this->flashMessage(json_encode([$msg, 'success', '#A5DC86'], JSON_UNESCAPED_UNICODE));
		redirect($redirect, 'refresh');
	}

	public function changeStatus(): void
	{
		header('Content-Type: application/json; charset=utf-8');
		$errMsg = $this->subsLang('status_update_error', 'The status could not be updated. Please try again.');
		if (! admin_module_installed('subscriptions')) {
			echo json_encode([$errMsg, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE);

			return;
		}
		if (strtoupper((string) $this->input->method()) !== 'POST') {
			echo json_encode([$errMsg, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE);

			return;
		}

		$rowId = (int) $this->input->post('subscription_row_id');
		$enabled = (int) $this->input->post('subscription_enabled');
		if ($rowId < 1 || ($enabled !== 0 && $enabled !== 1)) {
			echo json_encode([$errMsg, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE);

			return;
		}

		$prevRow = $this->fetchSubscriptionById($rowId);
		if ($prevRow === null) {
			echo json_encode([$errMsg, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE);

			return;
		}

		$newStatus = $enabled === 1 ? 1 : 0;
		$row = [
			'status' => $newStatus,
			'updated_at' => date('Y-m-d H:i:s'),
		];
		if (! $this->updateSubscriptionViaApi($rowId, $row)) {
			echo json_encode([$errMsg, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE);

			return;
		}

		if (is_object($prevRow) && isset($prevRow->company_id)) {
			$this->syncCompanyLimitsFromActiveSubscription((int) $prevRow->company_id);
		}

		$okMsg = $enabled === 1
			? $this->subsLang('toast_status_enabled', 'Subscription enabled.')
			: $this->subsLang('toast_status_disabled', 'Subscription disabled.');
		echo json_encode([$okMsg, 'success', '#A5DC86'], JSON_UNESCAPED_UNICODE);
	}

	/**
	 * Reescribe companies.max_number_of_* vía API (CompanyPlanSyncService).
	 */
	private function syncCompanyLimitsFromActiveSubscription(int $companyId): void
	{
		if ($companyId < 1 || ! admin_module_installed('companies')) {
			return;
		}

		$this->post('company/'.$companyId.'/sync-plan-limits', [], true);
	}

	/**
	 * Factura vigente: vinculada a esta fila de suscripción (subscription_id = PK) y no cerrada
	 * (no cumple: paid = 1, sin deuda y fecha de vencimiento ya pasada).
	 *
	 * @return bool true si existe al menos una factura que impide el borrado
	 */
	private function subscriptionRowHasActiveInvoice(int $subscriptionPk): bool
	{
		if ($subscriptionPk < 1) {
			return false;
		}

		$resp = $this->getDataFromApi('subscription/'.$subscriptionPk.'/has-blocking-invoice', [], true);
		if (! is_array($resp) || $this->hasError($resp)) {
			return false;
		}

		return (bool) ($resp['has_blocking_invoice'] ?? false);
	}

	private function fetchSubscriptionsForList(): array
	{
		$pk = $this->subscriptionsPrimaryKeyColumn();
		$resp = $this->getDataFromApi('subscription', [
			'all' => true,
			'orderProperty' => $pk,
			'orderDirection' => 'desc',
			'relations' => ['company', 'plan'],
		], true);
		if (! is_array($resp) || $this->hasError($resp)) {
			return [];
		}
		$rows = $this->extractApiListRows($resp);
		if (! is_array($rows)) {
			return [];
		}

		$out = [];
		foreach ($rows as $r) {
			$out[] = $this->normalizeSubscriptionListRow($r);
		}

		return $out;
	}

	/**
	 * @return list<object>
	 */
	private function loadCompaniesForSelect(): array
	{
		$resp = $this->getDataFromApi('company', [
			'all' => true,
			'orderProperty' => 'name',
			'orderDirection' => 'asc',
			'props' => ['company_id', 'name'],
		], true);
		if (! is_array($resp) || $this->hasError($resp)) {
			return [];
		}
		$rows = $this->extractApiListRows($resp);
		if (! is_array($rows)) {
			return [];
		}

		$out = [];
		foreach ($rows as $r) {
			$out[] = is_array($r) ? (object) $r : $r;
		}

		return $out;
	}

	/**
	 * @return list<object>
	 */
	private function fetchPlansFromApi(): array
	{
		$resp = $this->getDataFromApi('plan', [
			'all' => true,
			'orderProperty' => $this->planTablePrimaryKeyColumn(),
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

	private function fetchSubscriptionById(int $id): ?object
	{
		if ($id < 1) {
			return null;
		}
		$resp = $this->getObjectFromApi('subscription/'.$id);
		if ($this->hasError($resp)) {
			return null;
		}
		if (is_object($resp) && isset($resp->data) && is_object($resp->data)) {
			return $resp->data;
		}

		return is_object($resp) ? $resp : null;
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

	private function fetchCompanyById(int $id): ?object
	{
		if ($id < 1) {
			return null;
		}
		$resp = $this->getObjectFromApi('company/'.$id);
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
	private function createSubscriptionViaApi(array $row): bool
	{
		$this->lastCreatedSubscriptionId = 0;
		$resp = $this->post('subscription', $row, true);
		if (! is_array($resp) || $this->hasError($resp)) {
			return false;
		}
		$data = $resp['data'] ?? [];
		if (is_array($data)) {
			$pk = $this->subscriptionsPrimaryKeyColumn();
			$this->lastCreatedSubscriptionId = (int) ($data[$pk] ?? $data['subscription_id'] ?? $data['id'] ?? 0);
		} elseif (is_object($data)) {
			$pk = $this->subscriptionsPrimaryKeyColumn();
			$this->lastCreatedSubscriptionId = (int) ($data->{$pk} ?? $data->subscription_id ?? $data->id ?? 0);
		}

		return $this->lastCreatedSubscriptionId > 0;
	}

	/**
	 * @param array<string, mixed> $row
	 */
	private function updateSubscriptionViaApi(int $id, array $row): bool
	{
		if ($id < 1) {
			return false;
		}
		$resp = $this->put('subscription/'.$id, $row, true);

		return is_array($resp) && ! $this->hasError($resp);
	}

	private function deleteSubscriptionViaApi(int $id): bool
	{
		if ($id < 1) {
			return false;
		}
		$resp = $this->apiDelete('subscription/'.$id, [], true);

		return ! $this->hasError($resp);
	}

	/**
	 * @param array<string, mixed> $row
	 */
	private function createInvoiceViaApi(array $row): bool
	{
		$this->lastCreatedInvoiceId = 0;
		$resp = $this->post('invoice', $row, true);
		if (! is_array($resp) || $this->hasError($resp)) {
			return false;
		}
		$data = $resp['data'] ?? [];
		if (is_array($data)) {
			$this->lastCreatedInvoiceId = (int) ($data['invoice_id'] ?? $data['id'] ?? 0);
		} elseif (is_object($data)) {
			$this->lastCreatedInvoiceId = (int) ($data->invoice_id ?? $data->id ?? 0);
		}

		return $this->lastCreatedInvoiceId > 0;
	}

	private function normalizeSubscriptionListRow($row): object
	{
		$o = is_array($row) ? (object) $row : (is_object($row) ? $row : (object) []);
		if (! isset($o->company_name)) {
			$c = $o->company ?? null;
			if (is_object($c)) {
				$o->company_name = (string) ($c->name ?? '');
			} elseif (is_array($c)) {
				$o->company_name = (string) ($c['name'] ?? '');
			} else {
				$o->company_name = '';
			}
		}
		if (! isset($o->plan_title)) {
			$p = $o->plan ?? null;
			if (is_object($p)) {
				$o->plan_title = (string) ($p->title ?? '');
			} elseif (is_array($p)) {
				$o->plan_title = (string) ($p['title'] ?? '');
			} else {
				$o->plan_title = '';
			}
		}

		return $o;
	}

	/**
	 * @return array<string, string>
	 */
	private function buildListLang(array $pt): array
	{
		return [
			'addNew' => (string) ($pt['list_add_new'] ?? ''),
			'colNum' => (string) ($pt['col_num'] ?? ''),
			'colCompany' => (string) ($pt['col_company'] ?? ''),
			'colPlan' => (string) ($pt['col_plan'] ?? ''),
			'colStarts' => (string) ($pt['col_starts'] ?? ''),
			'colEnds' => (string) ($pt['col_ends'] ?? ''),
			'colNotes' => (string) ($pt['col_notes'] ?? ''),
			'colStatus' => (string) ($pt['col_status'] ?? ''),
			'colActions' => (string) ($pt['col_actions'] ?? ''),
			'actionEdit' => (string) ($pt['tooltip_edit'] ?? ''),
			'actionDelete' => (string) ($pt['tooltip_delete'] ?? ''),
			'statusSwitchAria' => (string) ($pt['status_switch_aria'] ?? ''),
			'statusError' => (string) ($pt['status_update_error'] ?? ''),
			'colStripe' => (string) ($pt['col_stripe'] ?? ''),
			'stripeCreate' => (string) ($pt['tooltip_stripe_create'] ?? ''),
			'stripeSync' => (string) ($pt['tooltip_stripe_sync'] ?? ''),
			'stripeSyncAll' => (string) ($pt['btn_stripe_sync_all'] ?? ''),
			'confirmStripeCreateTitle' => (string) ($pt['confirm_stripe_create_title'] ?? ''),
			'confirmStripeCreateText' => (string) ($pt['confirm_stripe_create_text'] ?? ''),
			'confirmStripeSyncTitle' => (string) ($pt['confirm_stripe_sync_title'] ?? ''),
			'confirmStripeSyncText' => (string) ($pt['confirm_stripe_sync_text'] ?? ''),
			'confirmStripeSyncAllTitle' => (string) ($pt['confirm_stripe_sync_title'] ?? ''),
			'confirmStripeSyncAllText' => (string) ($pt['confirm_stripe_sync_text'] ?? ''),
			'confirmYes' => (string) ($pt['delete_confirm_yes'] ?? ''),
			'confirmNo' => (string) ($pt['delete_confirm_no'] ?? ''),
		];
	}

	/**
	 * @return array<string, string>
	 */
	private function buildDeleteUi(array $pt): array
	{
		return [
			'confirmTitle' => (string) ($pt['delete_confirm_title'] ?? ''),
			'confirmText' => (string) ($pt['delete_confirm_text'] ?? ''),
			'confirmYes' => (string) ($pt['delete_confirm_yes'] ?? ''),
			'confirmNo' => (string) ($pt['delete_confirm_no'] ?? ''),
		];
	}

	/**
	 * @return array{ok: bool, row: ?array<string, int|string|null>, msg_key: string}
	 */
	private function readSubscriptionPostRow(): array
	{
		$fail = static function (string $msgKey): array {
			return ['ok' => false, 'row' => null, 'msg_key' => $msgKey];
		};

		$companyId = (int) $this->input->post('company_id');
		$planId = (int) $this->input->post('plan_id');
		$statusPost = strtolower(trim((string) $this->input->post('status')));
		$startsRaw = trim((string) $this->input->post('start_period_date'));
		if ($startsRaw === '') {
			$startsRaw = trim((string) $this->input->post('starts_at'));
		}
		$endsRaw = trim((string) $this->input->post('end_period_date'));
		if ($endsRaw === '') {
			$endsRaw = trim((string) $this->input->post('ends_at'));
		}
		$notes = (string) $this->input->post('notes');

		if ($companyId < 1 || ! $this->isValidCompanyId($companyId)) {
			return $fail('msg_company_invalid');
		}
		if ($planId < 1 || $this->fetchPlanById($planId) === null) {
			return $fail('msg_plan_invalid');
		}

		if ($statusPost === self::FORM_STATUS_ENABLED) {
			$statusDb = 1;
		} elseif ($statusPost === self::FORM_STATUS_DISABLED) {
			$statusDb = 0;
		} else {
			return $fail('msg_validation_error');
		}

		$startsAt = $startsRaw === '' ? null : $startsRaw;
		$endsAt = $endsRaw === '' ? null : $endsRaw;
		if ($startsAt !== null && ! $this->isValidYmd($startsAt)) {
			return $fail('msg_validation_error');
		}
		if ($endsAt !== null && ! $this->isValidYmd($endsAt)) {
			return $fail('msg_validation_error');
		}
		if ($startsAt !== null && $endsAt !== null && strcmp($endsAt, $startsAt) < 0) {
			return $fail('msg_dates_invalid');
		}

		if (($this->subscriptionsCapabilities()['hasNotes'] ?? false)
			&& (function_exists('mb_strlen') ? mb_strlen($notes, 'UTF-8') : strlen($notes)) > 65535) {
			return $fail('msg_validation_error');
		}

		$row = [
			'company_id' => $companyId,
			'plan_id' => $planId,
			'status' => $statusDb,
			'start_period_date' => $startsAt,
			'end_period_date' => $endsAt,
		];
		if ($this->subscriptionsCapabilities()['hasNotes'] ?? false) {
			$row['notes'] = $notes;
		}

		return [
			'ok' => true,
			'row' => $row,
			'msg_key' => '',
		];
	}

	/**
	 * @param array<string, int|string|null> $row
	 *
	 * @return array<string, int|string|null>
	 */
	private function filterSubscriptionsTableRow(array $row): array
	{
		$columns = $this->subscriptionsCapabilities()['columns'] ?? [];
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

	private function isValidYmd(string $d): bool
	{
		$dt = \DateTimeImmutable::createFromFormat('Y-m-d', $d);

		return $dt !== false && $dt->format('Y-m-d') === $d;
	}

	private function isValidCompanyId(int $companyId): bool
	{
		return $this->fetchCompanyById($companyId) !== null;
	}

	private function subscriptionsPrimaryKeyColumn(): string
	{
		if ($this->subscriptionsPkCache !== null) {
			return $this->subscriptionsPkCache;
		}
		if (function_exists('admin_subscriptions_primary_key_column')) {
			return $this->subscriptionsPkCache = admin_subscriptions_primary_key_column();
		}

		return $this->subscriptionsPkCache = 'subscription_id';
	}

	private function planTablePrimaryKeyColumn(): string
	{
		if (function_exists('admin_plan_primary_key_column')) {
			return admin_plan_primary_key_column();
		}

		return 'plan_id';
	}

	/**
	 * @return array<string, string>
	 */
	private function subsAdminTexts(): array
	{
		if ($this->subsAdminTextsCache !== null) {
			return $this->subsAdminTextsCache;
		}
		if (function_exists('admin_subscriptions_page_texts')) {
			$this->subsAdminTextsCache = admin_subscriptions_page_texts(null);
		} else {
			$this->subsAdminTextsCache = [];
		}

		return $this->subsAdminTextsCache;
	}

	private function subsLang(string $key, string $fallback = ''): string
	{
		$all = $this->subsAdminTexts();
		if (isset($all[$key]) && $all[$key] !== '') {
			return $all[$key];
		}

		return $fallback;
	}

	/**
	 * @return array<string, mixed>
	 */
	private function subscriptionsCapabilities(): array
	{
		if (function_exists('admin_capability_section')) {
			return admin_capability_section('subscriptions');
		}

		return [];
	}

	/**
	 * @return array<string, mixed>
	 */
	private function invoicesCapabilities(): array
	{
		if (function_exists('admin_capability_section')) {
			return admin_capability_section('invoices');
		}

		return [];
	}

	private function assertSubscriptionsModuleInstalled(): bool
	{
		if (function_exists('admin_module_installed') && admin_module_installed('subscriptions')) {
			return true;
		}
		show_error($this->subsLang('table_missing', 'The subscriptions table is not installed. Run database migrations.'), 503);

		return false;
	}

	private function assertSubscriptionsAndPlanModules(): bool
	{
		if (! function_exists('admin_module_installed')) {
			return true;
		}
		if (! admin_module_installed('subscriptions')) {
			show_error($this->subsLang('table_missing', 'The subscriptions table is not installed. Run database migrations.'), 503);

			return false;
		}
		if (! admin_module_installed('plan')) {
			show_error($this->subsLang('table_plan_missing', 'The plan table is not installed. Create plans before subscriptions.'), 503);

			return false;
		}

		return true;
	}

	/**
	 * @param array<string, mixed>|object|null $resp
	 */
	private function flashStripeApiResponse($resp, string $successMsgKey, string $redirectUrl): void
	{
		if ($this->hasError($resp)) {
			$msg = $this->extractStripeApiErrorMessage($resp);
			$this->flashMessage(json_encode([$msg, 'warning', '#DD6B55'], JSON_UNESCAPED_UNICODE));
		} else {
			$msg = '';
			if (is_array($resp) && isset($resp['message']) && is_string($resp['message']) && $resp['message'] !== '') {
				$msg = $resp['message'];
			} else {
				$msg = $this->subsLang($successMsgKey, $successMsgKey);
			}
			$this->flashMessage(json_encode([$msg, 'success', '#A5DC86'], JSON_UNESCAPED_UNICODE));
		}
		redirect($redirectUrl, 'refresh');
	}

	/**
	 * @param array<string, mixed>|object|null $resp
	 */
	private function extractStripeApiErrorMessage($resp): string
	{
		if (! is_array($resp)) {
			return $this->subsLang('msg_stripe_api_error', 'Error communicating with Stripe.');
		}

		$messageKey = isset($resp['message_key']) ? (string) $resp['message_key'] : '';
		if ($messageKey !== '' && $messageKey !== 'msg_stripe_api_error') {
			$fromKey = $this->subsLang($messageKey, '');
			if ($fromKey !== '' && $fromKey !== $messageKey) {
				return $fromKey;
			}
		}

		if (isset($resp['message']) && is_string($resp['message']) && trim($resp['message']) !== ''
			&& stripos($resp['message'], 'Bad Gateway') === false
			&& stripos($resp['message'], 'Server error:') !== 0) {
			return trim($resp['message']);
		}

		if (isset($resp['stripe_error']) && is_string($resp['stripe_error']) && trim($resp['stripe_error']) !== '') {
			return $this->subsLang('msg_stripe_api_error', 'Stripe API error').': '.trim($resp['stripe_error']);
		}

		$payload = $resp['message'] ?? null;
		if (is_array($payload)) {
			if (isset($payload['message_key']) && is_string($payload['message_key'])) {
				$fromKey = $this->subsLang($payload['message_key'], '');
				if ($fromKey !== '' && $fromKey !== $payload['message_key']) {
					return $fromKey;
				}
			}
			if (isset($payload['stripe_error']) && is_string($payload['stripe_error']) && trim($payload['stripe_error']) !== '') {
				return $this->subsLang('msg_stripe_api_error', 'Stripe API error').': '.trim($payload['stripe_error']);
			}
			if (isset($payload['message']) && is_string($payload['message']) && trim($payload['message']) !== '') {
				return trim($payload['message']);
			}
		}

		return $this->subsLang('msg_stripe_api_error', 'Error communicating with Stripe.');
	}
}

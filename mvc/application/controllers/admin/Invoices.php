<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Invoices extends MY_Controller
{
	/** @var array<string, string>|null */
	private $invAdminTextsCache = null;

	/** @var string|null */
	private $subscriptionsPkCache = null;

	public function __construct()
	{
		parent::__construct();
		$this->load->helper('admin_shell');
		$this->checkLoggedInOrLoginWithRedirect();
	}

	public function lists(): void
	{
		if (! $this->assertInvoicesModuleInstalled()) {
			return;
		}

		$list = $this->fetchInvoicesForList();
		$pt = $this->invAdminTexts();

		$data = [
			'title' => $this->invLang('list_title', 'Invoices'),
			'page' => 'invoices',
			'subpage' => 'invoiceslist',
			'pageTexts' => $pt,
			'list' => $list,
			'invoicesListLang' => $this->buildListLang($pt),
			'invoicesDeleteUi' => $this->buildDeleteUi($pt),
		];

		$this->load->view('admin/header', $data);
		$this->load->view('admin/invoices_list', $data);
		$this->load->view('admin/footer');
	}

	/**
	 * Las facturas no pueden crearse desde admin: redirigir al listado.
	 */
	public function add(): void
	{
		redirect(admin_url('invoices/lists'), 'refresh');
	}

	/**
	 * Las facturas no pueden crearse desde admin: redirigir al listado.
	 */
	public function create(): void
	{
		redirect(admin_url('invoices/lists'), 'refresh');
	}

	public function view($id = false): void
	{
		if (! $this->assertInvoicesModuleInstalled()) {
			return;
		}

		if ($id === false || $id === '') {
			show_404();
		}
		$rid = (int) $id;
		if ($rid < 1 || $this->fetchInvoiceById($rid) === null) {
			show_404();
		}

		$data = [
			'title' => $this->invLang('form_view_title', 'View invoice'),
			'page' => 'invoices',
			'subpage' => 'invoiceslist',
			'pageTexts' => $this->invAdminTexts(),
			'companies' => $this->loadCompaniesForSelect(),
			'subscriptionRefs' => $this->loadSubscriptionRefsForSelect(),
			'data' => $this->fetchInvoiceById($rid),
		];

		$this->load->view('admin/header', $data);
		$this->load->view('admin/invoices_view', $data);
		$this->load->view('admin/footer');
	}

	/**
	 * Las facturas no pueden modificarse desde admin: redirigir al view (si hay id) o al listado.
	 */
	public function update(): void
	{
		$rid = (int) $this->input->post('invoice_id');
		if ($rid > 0) {
			redirect(admin_url('invoices/view/' . $rid), 'refresh');

			return;
		}
		redirect(admin_url('invoices/lists'), 'refresh');
	}

	/**
	 * Alias por compatibilidad: las facturas no se editan, redirigir a view.
	 */
	public function edit($id = false): void
	{
		$rid = (int) $id;
		if ($rid > 0) {
			redirect(admin_url('invoices/view/' . $rid), 'refresh');

			return;
		}
		redirect(admin_url('invoices/lists'), 'refresh');
	}

	public function delete($id = false): void
	{
		if (! admin_module_installed('invoices')) {
			$this->flashMessage('error');
			redirect(admin_url('invoices/lists'), 'refresh');

			return;
		}

		if ($id === false || $id === '') {
			redirect(admin_url('invoices/lists'), 'refresh');

			return;
		}
		$rid = (int) $id;
		if ($rid < 1) {
			redirect(admin_url('invoices/lists'), 'refresh');

			return;
		}

		$where = ['invoice_id' => $rid];
		if ($this->fetchInvoiceById($rid) === null) {
			$this->flashMessage(json_encode([
				$this->invLang('msg_not_found', 'Invoice not found.'),
				'warning',
				'#DD6B55',
			], JSON_UNESCAPED_UNICODE));
			redirect(admin_url('invoices/lists'), 'refresh');

			return;
		}

		if (! $this->deleteInvoiceViaApi($rid)) {
			$this->flashMessage('error');
		} else {
			$this->flashMessage(json_encode([
				$this->invLang('msg_deleted', 'Invoice deleted.'),
				'success',
				'#A5DC86',
			], JSON_UNESCAPED_UNICODE));
		}

		redirect(admin_url('invoices/lists'), 'refresh');
	}

	/**
	 * POST vía redirect: crear factura en Stripe (CrearFacturaAsync).
	 */
	public function stripe_create($id = false): void
	{
		$rid = (int) $id;
		if ($rid < 1 || ! admin_module_installed('invoices') || $this->fetchInvoiceById($rid) === null) {
			show_404();
		}

		$resp = $this->post('stripe/invoices/' . $rid . '/create', []);
		$this->flashStripeApiResponse($resp, 'msg_stripe_invoice_created', admin_url('invoices/view/' . $rid));
	}

	/**
	 * Sincronizar una factura desde Stripe.
	 */
	public function stripe_sync($id = false): void
	{
		$rid = (int) $id;
		if ($rid < 1 || ! admin_module_installed('invoices') || $this->fetchInvoiceById($rid) === null) {
			show_404();
		}

		$resp = $this->post('stripe/invoices/' . $rid . '/sync', []);
		$this->flashStripeApiResponse($resp, 'msg_stripe_invoice_synced', admin_url('invoices/view/' . $rid));
	}

	/**
	 * UpdateInvoiceStripe: todas las facturas con stripe_id.
	 */
	public function stripe_sync_all(): void
	{
		if (! admin_module_installed('invoices')) {
			$this->flashMessage('error');
			redirect(admin_url('invoices/lists'), 'refresh');

			return;
		}

		$resp = $this->post('stripe/invoices/sync-all', []);
		$redirect = admin_url('invoices/lists');
		if ($this->hasError($resp)) {
			$this->flashStripeApiResponse($resp, 'msg_stripe_bulk_sync_done', $redirect);

			return;
		}
		$processed = (int) ($resp['data']['processed'] ?? 0);
		$updated = (int) ($resp['data']['updated'] ?? 0);
		$msg = sprintf($this->invLang('msg_stripe_bulk_result', 'Stripe: %1$d updated of %2$d processed.'), $updated, $processed);
		$this->flashMessage(json_encode([$msg, 'success', '#A5DC86'], JSON_UNESCAPED_UNICODE));
		redirect($redirect, 'refresh');
	}

	/**
	 * VerificarFacturaAsync: crear en Stripe las que no tienen stripe_id.
	 */
	public function stripe_create_missing(): void
	{
		if (! admin_module_installed('invoices')) {
			$this->flashMessage('error');
			redirect(admin_url('invoices/lists'), 'refresh');

			return;
		}

		$resp = $this->post('stripe/invoices/create-missing', []);
		$redirect = admin_url('invoices/lists');
		if ($this->hasError($resp)) {
			$this->flashStripeApiResponse($resp, 'msg_stripe_missing_created', $redirect);

			return;
		}
		$attempted = (int) ($resp['data']['attempted'] ?? 0);
		$created = (int) ($resp['data']['created'] ?? 0);
		$msg = sprintf($this->invLang('msg_stripe_missing_result', 'Stripe: %1$d created of %2$d attempted.'), $created, $attempted);
		$this->flashMessage(json_encode([$msg, 'success', '#A5DC86'], JSON_UNESCAPED_UNICODE));
		redirect($redirect, 'refresh');
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
				$msg = $this->invLang($successMsgKey, $successMsgKey);
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
		if (is_array($resp)) {
			if (isset($resp['message']) && is_string($resp['message']) && $resp['message'] !== '') {
				return $resp['message'];
			}
			if (isset($resp['message']) && is_array($resp['message'])) {
				$inner = $resp['message']['message'] ?? '';
				if (is_string($inner) && $inner !== '') {
					return $inner;
				}
			}
			if (isset($resp['stripe_error']) && is_string($resp['stripe_error'])) {
				return $this->invLang('msg_stripe_api_error', 'Stripe API error') . ': ' . $resp['stripe_error'];
			}
		}

		return $this->invLang('msg_stripe_api_error', 'Error communicating with Stripe.');
	}

	/**
	 * @return list<object>
	 */
	private function fetchInvoicesForList(): array
	{
		$resp = $this->getDataFromApi('invoice', [
			'all' => true,
			'orderProperty' => 'invoice_id',
			'orderDirection' => 'desc',
			'relations' => ['company', 'subscription.plan'],
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
			$out[] = $this->normalizeInvoiceListRow($r);
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
	 * @return list<object{ref_id: int, label: string}>
	 */
	private function loadSubscriptionRefsForSelect(): array
	{
		$out = [];
		$seen = [];
		$pk = $this->subscriptionsPrimaryKeyColumn();

		$resp = $this->getDataFromApi('subscription', [
			'all' => true,
			'orderProperty' => $pk,
			'orderDirection' => 'desc',
			'relations' => ['company', 'plan'],
		], true);
		foreach ($this->extractApiListRows($resp) as $r) {
			$row = is_array($r) ? $r : (array) (is_object($r) ? $r : []);
			$vid = (int) ($row[$pk] ?? $row['subscription_id'] ?? 0);
			if ($vid < 1 || isset($seen[$vid])) {
				continue;
			}
			$seen[$vid] = true;
			$company = $row['company'] ?? null;
			$cname = is_array($company) ? trim((string) ($company['name'] ?? '')) : (is_object($company) ? trim((string) ($company->name ?? '')) : '');
			$plan = $row['plan'] ?? null;
			$ptitle = is_array($plan) ? trim((string) ($plan['title'] ?? '')) : (is_object($plan) ? trim((string) ($plan->title ?? '')) : '');
			$label = '#'.$vid;
			if ($cname !== '') {
				$label .= ' — '.$cname;
			}
			if ($ptitle !== '') {
				$label .= ' — '.$ptitle;
			}
			$out[] = (object) ['ref_id' => $vid, 'label' => $label];
		}

		$planResp = $this->getDataFromApi('plan', [
			'all' => true,
			'orderProperty' => $this->planTablePrimaryKeyColumn(),
			'orderDirection' => 'asc',
		], true);
		$prefix = $this->invLang('option_plan_prefix', 'Plan');
		foreach ($this->extractApiListRows($planResp) as $p) {
			$po = is_array($p) ? (object) $p : $p;
			$pid = function_exists('admin_plan_row_id') ? admin_plan_row_id($po) : (int) ($po->plan_id ?? 0);
			if ($pid < 1 || isset($seen[$pid])) {
				continue;
			}
			$seen[$pid] = true;
			$t = trim((string) ($po->title ?? ''));
			$out[] = (object) [
				'ref_id' => $pid,
				'label' => $prefix.': '.($t !== '' ? $t : ('#'.$pid)).' (#'.$pid.')',
			];
		}

		return $out;
	}

	private function fetchInvoiceById(int $id): ?object
	{
		if ($id < 1) {
			return null;
		}
		$resp = $this->getObjectFromApi('invoice/'.$id);
		if ($this->hasError($resp)) {
			return null;
		}
		if (is_object($resp) && isset($resp->data) && is_object($resp->data)) {
			return $resp->data;
		}

		return is_object($resp) ? $resp : null;
	}

	private function deleteInvoiceViaApi(int $id): bool
	{
		if ($id < 1) {
			return false;
		}
		$resp = $this->apiDelete('invoice/'.$id, [], true);

		return ! $this->hasError($resp);
	}

	private function normalizeInvoiceListRow($row): object
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
			$pt = '';
			$sub = $o->subscription ?? null;
			if (is_object($sub) && isset($sub->plan) && is_object($sub->plan)) {
				$pt = (string) ($sub->plan->title ?? '');
			} elseif (is_array($sub) && isset($sub['plan']) && is_array($sub['plan'])) {
				$pt = (string) ($sub['plan']['title'] ?? '');
			}
			$o->plan_title = $pt;
		}

		return $o;
	}

	/**
	 * @return array<string, string>
	 */
	private function buildListLang(array $pt): array
	{
		return [
			'colNum' => (string) ($pt['col_num'] ?? ''),
			'colDocNumber' => (string) ($pt['col_doc_number'] ?? ''),
			'colCompany' => (string) ($pt['col_company'] ?? ''),
			'colSubscription' => (string) ($pt['col_subscription'] ?? ''),
			'colDate' => (string) ($pt['col_date'] ?? ''),
			'colPaid' => (string) ($pt['col_paid'] ?? ''),
			'colInvoicePaid' => (string) ($pt['col_invoice_paid'] ?? ''),
			'colDebt' => (string) ($pt['col_debt'] ?? ''),
			'colActions' => (string) ($pt['col_actions'] ?? ''),
			'actionView' => (string) ($pt['tooltip_view'] ?? ''),
			'actionDelete' => (string) ($pt['tooltip_delete'] ?? ''),
			'paidYes' => (string) ($pt['paid_display_yes'] ?? ''),
			'paidNo' => (string) ($pt['paid_display_no'] ?? ''),
			'paidDash' => (string) ($pt['paid_display_dash'] ?? ''),
			'stripeCreate' => (string) ($pt['tooltip_stripe_create'] ?? ''),
			'stripeSync' => (string) ($pt['tooltip_stripe_sync'] ?? ''),
			'stripeSyncAll' => (string) ($pt['btn_stripe_sync_all'] ?? ''),
			'stripeCreateMissing' => (string) ($pt['btn_stripe_create_missing'] ?? ''),
			'confirmStripeCreateTitle' => (string) ($pt['confirm_stripe_create_title'] ?? ''),
			'confirmStripeCreateText' => (string) ($pt['confirm_stripe_create_text'] ?? ''),
			'confirmStripeSyncTitle' => (string) ($pt['confirm_stripe_sync_title'] ?? ''),
			'confirmStripeSyncText' => (string) ($pt['confirm_stripe_sync_text'] ?? ''),
			'confirmStripeSyncAllTitle' => (string) ($pt['confirm_stripe_sync_all_title'] ?? ''),
			'confirmStripeSyncAllText' => (string) ($pt['confirm_stripe_sync_all_text'] ?? ''),
			'confirmStripeCreateMissingTitle' => (string) ($pt['confirm_stripe_create_missing_title'] ?? ''),
			'confirmStripeCreateMissingText' => (string) ($pt['confirm_stripe_create_missing_text'] ?? ''),
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
	 * @return array{ok: bool, row: ?array<string, int|string|null|float>, msg_key: string}
	 */
	private function readInvoicePostRow(): array
	{
		$fail = static function (string $msgKey): array {
			return ['ok' => false, 'row' => null, 'msg_key' => $msgKey];
		};

		$companyId = (int) $this->input->post('company_id');
		$subscriptionId = (int) $this->input->post('subscription_id');
		if ($companyId < 1 || ! $this->isValidCompanyId($companyId)) {
			return $fail('msg_company_invalid');
		}
		if ($subscriptionId < 1) {
			return $fail('msg_subscription_invalid');
		}
		$subCheck = $this->validateInvoiceSubscriptionRef($subscriptionId, $companyId);
		if (! $subCheck['ok']) {
			return $fail((string) $subCheck['msg_key']);
		}

		$docNumber = trim((string) $this->input->post('doc_number'));
		if ($docNumber !== '' && strlen($docNumber) > 50) {
			return $fail('msg_validation_error');
		}

		$dateRaw = trim((string) $this->input->post('invoice_date'));
		$dateExpiredRaw = trim((string) $this->input->post('date_expired'));
		$dateVal = $this->parseOptionalDatetime($dateRaw);
		$dateExpiredVal = $this->parseOptionalDatetime($dateExpiredRaw);
		if ($dateRaw !== '' && $dateVal === null) {
			return $fail('msg_datetime_invalid');
		}
		if ($dateExpiredRaw !== '' && $dateExpiredVal === null) {
			return $fail('msg_datetime_invalid');
		}

		$paidPost = trim((string) $this->input->post('paid'));
		$paidVal = null;
		if ($paidPost !== '' && ctype_digit($paidPost)) {
			$paidVal = (int) $paidPost;
		}

		$daysPost = trim((string) $this->input->post('days'));
		$daysVal = null;
		if ($daysPost !== '' && ctype_digit($daysPost)) {
			$d = (int) $daysPost;
			if ($d < 0 || $d > 255) {
				return $fail('msg_validation_error');
			}
			$daysVal = $d;
		}

		$issuing = trim((string) $this->input->post('issuing_entity'));
		if (strlen($issuing) > 50) {
			return $fail('msg_validation_error');
		}
		$country = trim((string) $this->input->post('country_entity'));
		if (strlen($country) > 50) {
			return $fail('msg_validation_error');
		}
		$state = trim((string) $this->input->post('state_entity'));
		if (strlen($state) > 50) {
			return $fail('msg_validation_error');
		}
		$city = trim((string) $this->input->post('city_entity'));
		if (strlen($city) > 50) {
			return $fail('msg_validation_error');
		}

		$address = (string) $this->input->post('address_entity');
		$concept = (string) $this->input->post('concept');

		$invoicePaid = $this->parseOptionalDecimal((string) $this->input->post('invoice_paid'));
		if ($invoicePaid === false) {
			return $fail('msg_validation_error');
		}
		$debt = $this->parseOptionalDecimal((string) $this->input->post('debt'));
		if ($debt === false) {
			return $fail('msg_validation_error');
		}

		$stripeId = trim((string) $this->input->post('stripe_id'));
		if (strlen($stripeId) > 255) {
			return $fail('msg_validation_error');
		}
		$stripeDoc = trim((string) $this->input->post('stripe_doc'));
		if (strlen($stripeDoc) > 512) {
			return $fail('msg_validation_error');
		}
		$stripeUrl = trim((string) $this->input->post('stripe_url'));
		if (strlen($stripeUrl) > 1024) {
			return $fail('msg_validation_error');
		}
		$stripePdf = trim((string) $this->input->post('stripe_pdf'));
		if (strlen($stripePdf) > 1024) {
			return $fail('msg_validation_error');
		}
		$stripeStatus = trim((string) $this->input->post('stripe_status'));
		if (strlen($stripeStatus) > 64) {
			return $fail('msg_validation_error');
		}

		return [
			'ok' => true,
			'row' => [
				'doc_number' => $docNumber === '' ? null : $docNumber,
				'subscription_id' => $subscriptionId,
				'company_id' => $companyId,
				'date' => $dateVal,
				'date_expired' => $dateExpiredVal,
				'paid' => $paidVal,
				'days' => $daysVal,
				'issuing_entity' => $issuing === '' ? null : $issuing,
				'address_entity' => $address === '' ? null : $address,
				'country_entity' => $country === '' ? null : $country,
				'state_entity' => $state === '' ? null : $state,
				'city_entity' => $city === '' ? null : $city,
				'concept' => $concept === '' ? null : $concept,
				'invoice_paid' => $invoicePaid,
				'debt' => $debt,
				'stripe_id' => $stripeId === '' ? null : $stripeId,
				'stripe_doc' => $stripeDoc === '' ? null : $stripeDoc,
				'stripe_url' => $stripeUrl === '' ? null : $stripeUrl,
				'stripe_pdf' => $stripePdf === '' ? null : $stripePdf,
				'stripe_status' => $stripeStatus === '' ? null : $stripeStatus,
			],
			'msg_key' => '',
		];
	}

	/**
	 * @return float|string|null|false null vacío, false inválido
	 */
	private function parseOptionalDecimal(string $raw)
	{
		$raw = trim(str_replace(',', '.', $raw));
		if ($raw === '') {
			return null;
		}
		if (! is_numeric($raw)) {
			return false;
		}

		return round((float) $raw, 2);
	}

	private function parseOptionalDatetime(string $raw): ?string
	{
		$raw = trim($raw);
		if ($raw === '') {
			return null;
		}
		$dt = \DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $raw);
		if ($dt !== false && $dt->format('Y-m-d\TH:i') === $raw) {
			return $dt->format('Y-m-d H:i:s');
		}
		$dt2 = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $raw);
		if ($dt2 !== false && $dt2->format('Y-m-d H:i:s') === $raw) {
			return $raw;
		}
		$dt3 = \DateTimeImmutable::createFromFormat('Y-m-d H:i', $raw);
		if ($dt3 !== false && $dt3->format('Y-m-d H:i') === $raw) {
			return $dt3->format('Y-m-d H:i:s');
		}
		$dt4 = \DateTimeImmutable::createFromFormat('Y-m-d', $raw);
		if ($dt4 !== false && $dt4->format('Y-m-d') === $raw) {
			return $dt4->format('Y-m-d H:i:s');
		}

		return null;
	}

	private function isValidCompanyId(int $companyId): bool
	{
		if ($companyId < 1) {
			return false;
		}
		$resp = $this->getObjectFromApi('company/'.$companyId);
		if ($this->hasError($resp)) {
			return false;
		}
		$data = is_object($resp) && isset($resp->data) ? $resp->data : $resp;

		return is_object($data);
	}

	/**
	 * @return array{ok: bool, msg_key: string}
	 */
	private function validateInvoiceSubscriptionRef(int $subscriptionId, int $companyId): array
	{
		$bad = static function (string $k): array {
			return ['ok' => false, 'msg_key' => $k];
		};

		$sub = $this->getObjectFromApi('subscription/'.$subscriptionId);
		if (! $this->hasError($sub)) {
			$data = is_object($sub) && isset($sub->data) ? $sub->data : $sub;
			if (is_object($data)) {
				if (isset($data->company_id) && (int) $data->company_id !== $companyId) {
					return $bad('msg_subscription_company_mismatch');
				}

				return ['ok' => true, 'msg_key' => ''];
			}
		}

		$plan = $this->getObjectFromApi('plan/'.$subscriptionId);
		if (! $this->hasError($plan)) {
			return ['ok' => true, 'msg_key' => ''];
		}

		return $bad('msg_subscription_invalid');
	}

	private function planTablePrimaryKeyColumn(): string
	{
		if (function_exists('admin_plan_primary_key_column')) {
			return admin_plan_primary_key_column();
		}

		return 'plan_id';
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

	/**
	 * @return array<string, string>
	 */
	private function invAdminTexts(): array
	{
		if ($this->invAdminTextsCache !== null) {
			return $this->invAdminTextsCache;
		}
		if (function_exists('admin_invoices_page_texts')) {
			$this->invAdminTextsCache = admin_invoices_page_texts(null);
		} else {
			$this->invAdminTextsCache = [];
		}

		return $this->invAdminTextsCache;
	}

	private function invLang(string $key, string $fallback = ''): string
	{
		$all = $this->invAdminTexts();
		if (isset($all[$key]) && $all[$key] !== '') {
			return $all[$key];
		}

		return $fallback;
	}

	private function assertInvoicesModuleInstalled(): bool
	{
		if (function_exists('admin_module_installed') && admin_module_installed('invoices')) {
			return true;
		}
		show_error($this->invLang('table_missing', 'The invoices table is not installed. Run database migrations.'), 503);

		return false;
	}
}

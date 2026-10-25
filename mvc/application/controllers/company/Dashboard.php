<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends Company_Controller {

	public $currency = array();
	public function __construct()
	{
		parent::__construct();
		$this->load->model('langmodel');
		$this->load->model('DashboardModel');
		$this->load->model('OrdenModel');
		$this->currency = $this->getCurrency($this->getCompanyId());
	}

	public function index()
	{
		// chart_pluggins
		$data = array('title' => 'Dashboard','page' => 'dashboard');
		$data['langdef'] = $this->HelperLanguage->get_language_head();

		$request = $this->input->get();
		if (! isset($request['fecha_desde_pordefecto']) || $request['fecha_desde_pordefecto'] === '') {
			$request['fecha_desde_pordefecto'] = date('Y-m-01');
		}

		// hasta_fecha_texto - hasta_fecha

		if(isset($request['fecha_desde_pordefecto'])){

			if ($request['fecha_desde_pordefecto'] != 'personalizado') {
				$_GET['hasta_fecha_texto'] = '';
				$_GET['hasta_fecha'] = '';
			}

		}

		$companyId = (int) $this->getCompanyId();
		if ($companyId < 1) {
			log_message('error', 'Dashboard: getCompanyId() devolvió 0; no se pueden cargar métricas');
		} else {
			log_message('debug', 'Dashboard: usando company_id=' . $companyId);
		}
		$this->DashboardModel->setCompanyId($companyId);
		$dashboard_values = $this->DashboardModel->get_filter((object) $request);

		$defaultTypeRow = [(object)['total' => 0, 'qty_orders' => 0]];
		$defaults = [
			'sales_type' => ['app' => $defaultTypeRow, 'web' => $defaultTypeRow, 'manual' => $defaultTypeRow, 'erp_import' => $defaultTypeRow],
			'data_forchart' => ['all' => [], 'total' => 0],
			'sales_bycustomer' => ['all' => [], 'otros_qty' => 0, 'otros_mount' => 0, 'otros_mount_porcent' => 0, 'total' => 0, 'total_qty' => 0],
			'sales_bycategory' => [
				'mount' => [], 'qty' => [],
				'otros' => ['otros_qty' => 0, 'otros_mount' => 0, 'otros_qty_porcent' => 0, 'otros_mount_porcent' => 0]
			],
			'sales_byvendor' => ['all' => [], 'otros_mount' => 0, 'otros_mount_porcent' => 0, 'total' => 0],
			'top10_productos' => [],
			'send_catalogs_confirmed' => [(object)['total_send_catalog' => 0]],
			'send_catalogs_reads' => [(object)['total_send_catalog' => 0]],
			'send_catalogs_noread' => [(object)['total_send_catalog' => 0]],
			'send_catalogs_open' => [(object)['total_send_catalog' => 0]],
		];
		$dashboard_values = array_merge($defaults, $dashboard_values);

		$langJson = $this->getLangJsonSection('cm-dashboard');
		$langLegacy = $this->HelperLanguage->dashboard();
		$info['lang_dashboard'] = array_merge(
			is_array($langLegacy) ? $langLegacy : [],
			is_array($langJson) ? $langJson : []
		);

		$salesTotalRow = $dashboard_values['sales_total'][0] ?? (object) ['total' => 0, 'qty_orders' => 0];
		$sendCatalogRow = $dashboard_values['send_catalogs_qty'][0] ?? (object) ['total_send_catalog' => 0];

		$info['total_amount_sales'] = (float) ($salesTotalRow->total ?? 0);
		$info['total_type'] = $dashboard_values['sales_type'];
		$info['version'] = $this->Helper->version();

		$info['qty_orders'] = (int) ($salesTotalRow->qty_orders ?? 0);
		$info['total_send_catalog'] = (int) ($sendCatalogRow->total_send_catalog ?? 0);
		$info['data_forchart'] = $dashboard_values['data_forchart'];
		$info['sales_bycustomer'] = $dashboard_values['sales_bycustomer'];
		$info['sales_bycategoryMount'] = $dashboard_values['sales_bycategory']['mount'] ?? [];
		$info['sales_bycategoryQty'] = $dashboard_values['sales_bycategory']['qty'] ?? [];
		$info['sales_bycategoryQty_all'] = $dashboard_values['sales_bycategory'];

		$info['sales_byvendor'] = $dashboard_values['sales_byvendor'];
		$info['top10_productos'] = $dashboard_values['top10_productos'];

		$info['send_catalogs_confirmed'] = $dashboard_values['send_catalogs_confirmed'];
		$info['send_catalogs_reads'] = $dashboard_values['send_catalogs_reads'];
		$info['send_catalogs_noread'] = $dashboard_values['send_catalogs_noread'];
		$info['send_catalogs_open'] = $dashboard_values['send_catalogs_open'];

		$info['colores'] = [
			"#dc2626",
			"#9ca3af",
			"#6b7280",
			"#b91c1c",
			"#d1d5db",
			"#4b5563",
		];

		// 	 json_encode($info['sales_bycategoryQty']);
		// return $this->Helper->response_json($dashboard_values['sales_total']);
		// return $this->Helper->response_json($dashboard_values['data_forchart']);

		$info['format_numeric'] = $this->Helper->format_numeric() ?: (object)['digits' => 2, 'radixPoint' => '.', 'groupSeparator' => ','];
		$format_date = $this->Helper->format_date();
		$info['format_date'] = $format_date ?: fv_default_format_date_object();
		$currency = $this->currency;
		$currencySymbol = '';
		if (is_object($currency)) {
			$currencySymbol = (string) ($currency->symbol ?? $currency->Symbol ?? '');
		} elseif (is_string($currency)) {
			$currencySymbol = $currency;
		}
		$info['currency_symbol'] = (object) [
			'symbol' => $currencySymbol,
			'Symbol' => $currencySymbol,
		];
		$info['lang_meses'] = $this->HelperLanguage->meses();

		$this->addJsFile(base_url('companyasset/js/chart.js'));
		$this->addJsFile(base_url('companyasset/company/order/ImagePopover.js'));
		$this->addJsFile(base_url('companyasset/company/dashboard/dashboard-common.js'));
		$this->addJsFile(base_url('companyasset/company/dashboard/channels.js'));
		$this->addJsFile(base_url('companyasset/company/dashboard/index.js'));
		$this->addJsFile(base_url('companyasset/company/dashboard/sales_bycustomer.js'));
		$this->addJsFile(base_url('companyasset/company/dashboard/sales_bycategoryMount.js'));
		$this->addJsFile(base_url('companyasset/company/dashboard/sales_bycategoryQty.js'));
		$this->addJsFile(base_url('companyasset/company/dashboard/sales_byVendor.js'));
		$this->addJsFile(base_url('companyasset/company/dashboard/markup_productos.js'));

        $this->showContent('company/dashboard/dashboard',$data+$info);
	}

	/**
	 * @param mixed $response
	 * @return array<int, object|array>
	 */
	private function normalizeApiList($response): array
	{
		if ($this->hasError($response)) {
			return [];
		}
		if (is_object($response) && isset($response->data) && is_array($response->data)) {
			return $response->data;
		}
		if (is_array($response)) {
			if (isset($response['data']) && is_array($response['data'])) {
				return $response['data'];
			}

			return $response;
		}

		return [];
	}

	/**
	 * @return array<int, object>
	 */
	private function searchProducts(int $companyId, string $searchText): array
	{
		$rows = $this->normalizeApiList($this->getDataFromApi('product', [
			'company' => $companyId,
			'all' => true,
			'search' => $searchText,
			'relations' => ['currency'],
		], false));

		$defaultSymbol = '';
		if (is_object($this->currency)) {
			$defaultSymbol = (string) ($this->currency->symbol ?? $this->currency->Symbol ?? '');
		}
		$out = [];
		foreach ($rows as $row) {
			$o = is_object($row) ? $row : (object) $row;
			$currency = $o->currency ?? null;
			$o->currency_symbol = is_object($currency)
				? (string) ($currency->symbol ?? $defaultSymbol)
				: $defaultSymbol;
			$out[] = $o;
		}

		return $out;
	}

	/**
	 * @return array<int, object>
	 */
	private function searchWarehouseUsers(int $companyId, string $searchText): array
	{
		$params = [
			'company_id' => $companyId,
			'type' => 'warehouse',
			'all' => true,
		];
		if ($searchText !== '') {
			$params['search'] = $searchText;
		}

		$rows = $this->normalizeApiList($this->getDataFromApi('user', $params, false));
		$out = [];
		foreach ($rows as $row) {
			$mapped = $this->mapUserToLegacyWarehouseRow($row);
			if ($searchText === '' || $this->matchesFirstOrLastName($mapped, $searchText)) {
				$out[] = $mapped;
			}
		}

		return $out;
	}

	/**
	 * @return array<int, object>
	 */
	private function searchSellers(int $companyId, string $searchText): array
	{
		$rows = $this->normalizeApiList($this->getDataFromApi('vendor', [
			'company' => $companyId,
			'all' => true,
			'relations' => ['user'],
		], false));

		$out = [];
		foreach ($rows as $row) {
			$mapped = $this->mapVendorToLegacySellerRow($row);
			if ($this->matchesFirstOrLastName($mapped, $searchText)) {
				$out[] = $mapped;
			}
		}

		return $out;
	}

	private function mapUserToLegacyWarehouseRow($user): object
	{
		$u = is_object($user) ? $user : (object) $user;
		$name = trim((string) ($u->name ?? ''));
		$parts = $name === '' ? [] : preg_split('/\s+/u', $name, 2);

		return (object) [
			'user_id' => (int) ($u->id ?? $u->user_id ?? 0),
			'first_name' => (string) ($u->first_name ?? ($parts[0] ?? '')),
			'last_name' => (string) ($u->last_name ?? ($parts[1] ?? '')),
			'email' => (string) ($u->email ?? ''),
			'phone_number' => (string) ($u->phone ?? $u->phone_number ?? ''),
			'created' => (string) ($u->created_at ?? $u->created ?? ''),
		];
	}

	private function mapVendorToLegacySellerRow($vendor): object
	{
		$v = is_object($vendor) ? $vendor : (object) $vendor;
		$u = is_object($v->user ?? null) ? $v->user : (object) [];
		$first = trim((string) ($u->first_name ?? ''));
		$last = trim((string) ($u->last_name ?? ''));
		if ($first === '' && $last === '') {
			$name = trim((string) ($u->name ?? ''));
			$parts = $name === '' ? [] : preg_split('/\s+/u', $name, 2);
			$first = (string) ($parts[0] ?? '');
			$last = (string) ($parts[1] ?? '');
		}

		return (object) [
			'first_name' => $first,
			'last_name' => $last,
			'email' => (string) ($u->email ?? ''),
			'username' => (string) ($u->username ?? $u->email ?? ''),
			'phone_number' => (string) ($v->phone_number ?? $u->phone ?? ''),
			'profile_image' => (string) ($v->profile_image ?? $u->image ?? ''),
			'created' => (string) ($v->created ?? $u->created_at ?? ''),
		];
	}

	private function matchesFirstOrLastName(object $row, string $searchText): bool
	{
		$term = mb_strtolower(trim($searchText));
		if ($term === '') {
			return true;
		}

		return mb_stripos((string) ($row->first_name ?? ''), $term) !== false
			|| mb_stripos((string) ($row->last_name ?? ''), $term) !== false;
	}

	public function searchList()  {

		$compId = (int) $this->getCompany();
		$data = array('title' => 'Search Results','page' => 'searchlist');

		$searchText = trim((string) $this->input->post('searchText'));
		if ($searchText !== '') {
			$data['productlist'] = $this->searchProducts($compId, $searchText);
			$data['warehouselist'] = $this->searchWarehouseUsers($compId, $searchText);
			$data['sellerlist'] = $this->searchSellers($compId, $searchText);
		}

		$langId = $this->getUserData('language_id');
		$data['langdef'] = $this->langmodel->getMdl('comp-login-head', $langId);
		$this->load->view('company/header', $data);
		$this->load->view('company/search_list');
		$this->load->view('company/footer');
	}
}

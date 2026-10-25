<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Warehouse extends My_Controller
{
    public $company_info = array();
    public $format_numeric = array();
    public $lang_order = array();
	public ?int $langId;
	public ?int $company;
	public $currency = array();
	/** @var object|null Igual que Order::lists (statusrol / permisos). */
	public $sub_user;

	/**
	 * Sesión de portal compañía (misma lógica que catalogo/Producto::comploggedIn).
	 */
	protected function comploggedIn(): void
	{
		if (!$this->loggedIn()) {
			$redirectto = urlencode(current_url());
			redirect($this->config->item('base_path') . 'login?redirectto=' . $redirectto, 'refresh');
		}
	}

	public function __construct()
	{
		parent::__construct();
		$this->comploggedIn();
		$this->load->model('langmodel');
		$this->load->model('Helper');
		$this->load->model('HelperLanguage');
		$this->company = (int) ($this->session->userdata('compId') ?? 0);
		$this->langId = (int) ($this->session->userdata('language_id') ?? 1);
		if (! $this->initCompanyContextFromApi()) {
			return;
		}
		$this->lang_order = $this->langmodel->getMdl('cmp-orders', $this->langId);
		$this->sub_user = null;
	}

	/**
	 * Compañía, formato numérico y moneda vía API (sin SQL directo).
	 */
	private function initCompanyContextFromApi(): bool
	{
		$companyInfo = $this->getObjectFromApi("company/{$this->company}");
		if (! is_object($companyInfo) || $this->hasError($companyInfo)) {
			$langHeadErr = $this->langmodel->getMdl('comp-login-head', $this->langId);
			$errMsg = is_array($langHeadErr) ? (string) ($langHeadErr['error_company_not_found'] ?? '') : '';
			show_error($errMsg !== '' ? $errMsg : 'Company not found', 403);

			return false;
		}
		$this->company_info = $companyInfo;
		$this->Helper->company_id = $this->company;
		$this->Helper->company_info = $companyInfo;

		$formatNumeric = $this->getObjectFromApi("company/{$this->company}/format-numeric");
		if ($this->hasError($formatNumeric)) {
			$formatNumeric = $this->getObjectFromApi('formatnumeric/default');
		}
		$this->format_numeric = (is_object($formatNumeric) && ! $this->hasError($formatNumeric))
			? $formatNumeric
			: (object) [];

		$currency = $this->Helper->currency();
		$this->currency = is_object($currency) ? $currency : (object) [];

		return true;
	}

	/**
	 * @return array<int, object>
	 */
	private function warehouseUsersForSelect(): array
	{
		$resp = $this->getObjectFromApi("company/{$this->company}/warehouse-users-for-order-assignment");
		$rows = [];
		if (is_object($resp) && isset($resp->data) && is_array($resp->data)) {
			$rows = $resp->data;
		} elseif (is_array($resp) && isset($resp['data']) && is_array($resp['data'])) {
			$rows = $resp['data'];
		}

		$out = [];
		foreach ($rows as $row) {
			$row = (object) $row;
			$name = trim((string) ($row->name ?? ''));
			if ($name === '' && isset($row->email)) {
				$name = trim((string) $row->email);
			}
			$parts = preg_split('/\s+/', $name, 2);
			$out[] = (object) [
				'user_id' => (int) ($row->user_id ?? $row->id ?? 0),
				'first_name' => (string) ($parts[0] ?? $name),
				'last_name' => (string) ($parts[1] ?? ''),
			];
		}

		return $out;
	}

	private function warehouseOrdersPaginationConfig(string $baseUrl, int $totalRows, int $showPerPage): array
	{
		$langProds = $this->langmodel->getMdl('cmp-products', $this->langId);

		return [
			'base_url' => $baseUrl,
			'num_links' => 2,
			'uri_segment' => 4,
			'total_rows' => $totalRows,
			'per_page' => $showPerPage,
			'page_query_string' => true,
			'query_string_segment' => 'page',
			'reuse_query_string' => true,
			'full_tag_open' => '<ul class="pagination pagination-sm">',
			'full_tag_close' => '</ul>',
			'num_tag_open' => '<li>',
			'num_tag_close' => '</li>',
			'first_link' => $langProds['pagination_first'] ?? '',
			'first_tag_open' => '<li>',
			'first_tag_close' => '</li>',
			'last_link' => $langProds['pagination_last'] ?? '',
			'last_tag_open' => '<li>',
			'last_tag_close' => '</li>',
			'prev_link' => $langProds['pagination_prev'] ?? '',
			'prev_tag_open' => '<li>',
			'prev_tag_close' => '</li>',
			'next_link' => $langProds['pagination_next'] ?? '',
			'next_tag_open' => '<li>',
			'next_tag_close' => '</li>',
			'cur_tag_open' => '<li class="active"><a href="#">',
			'cur_tag_close' => '</a></li>',
			'attributes' => ['class' => 'page-link'],
		];
	}

	/**
	 * Misma envoltura que Userprofile/Dashboard: HTML + CSS (layouts/company).
	 */
	protected function prepareLayoutCompanyData(array $base, string $mainContent): array
	{
		$data = $base;
		$data['main_content'] = $mainContent;
		$companyId = (int) ($this->session->userdata('compId') ?? $this->company ?? 0);
		$data['company_id'] = $companyId;
		if ($companyId > 0) {
			$data['company'] = $this->getObjectFromApi('company/'.$companyId);
			$data['format_numeric'] = $this->getObjectFromApi("company/{$companyId}/format-numeric");
			$data['format_currency'] = $this->getRawFromApi("company/{$companyId}/currency-symbol", []);
			$data['code_currency'] = $this->getRawFromApi("company/{$companyId}/currency-code", []);
		} else {
			$data['company'] = null;
			$data['format_numeric'] = null;
			$data['format_currency'] = null;
			$data['code_currency'] = null;
		}
		$data['user'] = $this->getUser();
		try {
			$langNav = $this->HelperLanguage->lang_navigation();
		} catch (\Throwable $e) {
			$langNav = [];
		}
		$data['main_navigation'] = [
			'dashboard' => ['url' => 'dashboard', 'label' => $langNav['dashboard'] ?? 'Dashboard', 'icon' => 'fa-solid fa-chart-simple'],
			'settings' => [
				'url' => 'null',
				'label' => $langNav['settings'] ?? 'Settings',
				'icon' => 'fa-solid fa-gear',
				'subitems' => [
					'units' => ['url' => 'units/lists', 'label' => $langNav['nav_units'] ?? 'Units', 'icon' => 'fa-solid fa-ruler-combined'],
					'payment_terms' => ['url' => 'terms/lists', 'label' => $langNav['nav_payment_terms'] ?? 'Payment terms', 'icon' => 'fa-solid fa-file-invoice-dollar'],
					'sellers_profile' => ['url' => 'sellerprofile/lists', 'label' => $langNav['nav_sellers_profile'] ?? 'Sellers profile', 'icon' => 'fa-solid fa-id-card'],
					'currency' => ['url' => 'currencies/lists', 'label' => $langNav['nav_currency'] ?? 'Currency', 'icon' => 'fa-solid fa-coins'],
					'customer_groups' => ['url' => 'groups', 'label' => $langNav['nav_customer_groups'] ?? 'Customer groups', 'icon' => 'fa-solid fa-users'],
					'users' => ['url' => 'users', 'label' => $langNav['user'] ?? 'Users', 'icon' => 'fa-solid fa-user-lock'],
					'warehouses' => ['url' => 'warehouses', 'label' => $langNav['warehouse'] ?? 'Warehouse', 'icon' => 'fa-solid fa-house-user'],
				],
			],
			'products' => ['url' => 'products', 'label' => $langNav['products'] ?? 'Products', 'icon' => 'fa-solid fa-box'],
			'customer' => ['url' => 'customers', 'label' => $langNav['customer'] ?? 'Customers', 'icon' => 'fa-solid fa-sack-dollar'],
			'order' => [
				'url' => 'null',
				'label' => $langNav['order'] ?? 'Orders',
				'icon' => 'fa-solid fa-paper-plane',
				'subitems' => [
					'order' => ['url' => 'orders', 'label' => $langNav['order'] ?? 'Orders', 'icon' => 'fa-solid fa-paper-plane'],
					'warehouseorders' => ['url' => 'warehouse/orders', 'label' => $langNav['warehouseorders'] ?? 'Warehouse Orders', 'icon' => 'fa-solid fa-paste'],
					'inventory' => ['url' => 'inventory/order', 'label' => $langNav['inventory'] ?? 'Inventory', 'icon' => 'fa-solid fa-warehouse'],
					'opencart' => ['url' => 'cart/abandoned', 'label' => $langNav['opencart'] ?? 'Open Cart', 'icon' => 'fa-solid fa-cart-shopping'],
				],
			],
			'catalog' => ['url' => 'catalog', 'label' => $langNav['catalog'] ?? 'Catalog', 'icon' => 'fa-solid fa-file-circle-plus'],
		];
		$data['config_menu'] = [
			'url' => '',
			'label' => $langNav['profile'] ?? 'Profile',
			'icon' => 'fa fa-cog fa-fw fa-lg',
			'subitems' => [
				'profile' => ['url' => 'profile', 'label' => $langNav['profile'] ?? 'Profile', 'icon' => 'fa fa-cog fa-fw fa-lg'],
				'import' => ['url' => 'import/lists', 'label' => $langNav['import'] ?? 'Import', 'icon' => 'mdi mdi-import'],
				'logs' => ['url' => 'logs/lists', 'label' => $langNav['logs'] ?? 'Logs', 'icon' => 'mdi mdi-database'],
			],
		];
		try {
			$data['commonTexts'] = $this->HelperLanguage->get_language_head();
		} catch (\Throwable $e) {
			$data['commonTexts'] = ['bnprofile' => 'Profile', 'bnsignout' => 'Sign out'];
		}

		if (empty($data['format_numeric']) || (is_object($data['format_numeric']) && isset($data['format_numeric']->error))) {
			$data['format_numeric'] = $this->format_numeric;
		}

		return $data;
	}


	/**
	 * @return array<int, object>
	 */
	private function fetchWarehouseUsersFromApi(): array
	{
		$resp = $this->getDataFromApi('user', [
			'company_id' => $this->company,
			'type' => 'warehouse',
			'all' => true,
		], false);
		$rows = [];
		if (is_object($resp) && isset($resp->data) && is_array($resp->data)) {
			$rows = $resp->data;
		} elseif (is_array($resp) && isset($resp['data']) && is_array($resp['data'])) {
			$rows = $resp['data'];
		} elseif (is_array($resp)) {
			$rows = $resp;
		}

		$out = [];
		foreach ($rows as $row) {
			$out[] = $this->mapUserToLegacyWarehouseRow($row);
		}

		return $out;
	}

	private function mapUserToLegacyWarehouseRow($user): object
	{
		$u = is_object($user) ? $user : (object) $user;
		$name = trim((string) ($u->name ?? ''));
		$parts = $name === '' ? [] : preg_split('/\s+/u', $name, 2);
		$deletedAt = $u->deleted_at ?? null;

		return (object) [
			'user_id' => (int) ($u->id ?? $u->user_id ?? 0),
			'first_name' => (string) ($u->first_name ?? ($parts[0] ?? '')),
			'last_name' => (string) ($u->last_name ?? ($parts[1] ?? '')),
			'email' => (string) ($u->email ?? ''),
			'phone_number' => (string) ($u->phone ?? $u->phone_number ?? ''),
			'profile_image' => (string) ($u->image ?? $u->profile_image ?? ''),
			'status' => ($deletedAt === null || $deletedAt === '') ? '1' : '0',
			'created' => (string) ($u->created_at ?? $u->created ?? ''),
		];
	}

	private function fetchWarehouseUserById(int $userId): ?object
	{
		$resp = $this->getObjectFromApi("user/{$userId}");
		if (! is_object($resp) || $this->hasError($resp)) {
			return null;
		}
		$data = $resp->data ?? $resp;

		return $this->mapUserToLegacyWarehouseRow($data);
	}

	/**
	 * @param array<string, mixed> $request
	 * @return array<string, mixed>
	 */
	private function buildWarehouseUserApiPayload(array $request, bool $isUpdate = false): array
	{
		$first = trim((string) ($request['first_name'] ?? ''));
		$last = trim((string) ($request['last_name'] ?? ''));
		$payload = [
			'company_id' => $this->company,
			'role' => 'warehouse',
			'name' => trim($first.' '.$last),
			'first_name' => $first,
			'last_name' => $last,
			'email' => trim((string) ($request['email'] ?? '')),
			'phone' => trim((string) ($request['phone_number'] ?? '')),
		];
		$password = trim((string) ($request['password'] ?? ''));
		if (! $isUpdate || $password !== '') {
			$payload['password'] = $password;
		}
		if (! empty($request['images'])) {
			$payload['image'] = $request['images'];
		}

		return $payload;
	}

	/**
	 * @param array<int, object> $users
	 * @return array<int, object>
	 */
	private function filterWarehouseUsersList(array $users, $search, $status, $order_sort, $order_type): array
	{
		$search = trim((string) $search);
		if ($order_sort === '' || $order_sort === null) {
			$order_sort = 'id';
			if ($order_type === '' || $order_type === null) {
				$order_type = 'DESC';
			}
		}
		$filtered = array_values(array_filter($users, static function ($row) use ($search, $status) {
			$name = trim((string) (($row->first_name ?? '').' '.($row->last_name ?? '')));
			$email = (string) ($row->email ?? '');
			$userId = (string) ($row->user_id ?? '');
			$phone = (string) ($row->phone_number ?? '');

			if ($status !== null && $status !== '' && $status !== '3') {
				$rowStatus = (string) ($row->status ?? '1');
				if ($rowStatus !== (string) $status) {
					return false;
				}
			}

			if ($search === '') {
				return true;
			}

			return stripos($name, $search) !== false
				|| stripos($email, $search) !== false
				|| stripos($userId, $search) !== false
				|| stripos($phone, $search) !== false;
		}));

		$dir = strtoupper((string) $order_type) === 'ASC' ? 1 : -1;
		usort($filtered, static function ($a, $b) use ($order_sort, $dir) {
			switch ((string) $order_sort) {
				case 'email':
					return $dir * strcasecmp((string) ($a->email ?? ''), (string) ($b->email ?? ''));
				case 'phone':
					return $dir * strcasecmp((string) ($a->phone_number ?? ''), (string) ($b->phone_number ?? ''));
				case 'nombres_completos':
					$na = trim((string) (($a->first_name ?? '').' '.($a->last_name ?? '')));
					$nb = trim((string) (($b->first_name ?? '').' '.($b->last_name ?? '')));
					return $dir * strcasecmp($na, $nb);
				case 'id':
				default:
					return $dir * (((int) ($a->user_id ?? 0)) <=> ((int) ($b->user_id ?? 0)));
			}
		});

		return $filtered;
	}

	private function warehouseUserEmailExists(string $email): bool
	{
		$email = trim($email);
		if ($email === '') {
			return false;
		}
		$resp = $this->getDataFromApi('user', [
			'search' => $email,
			'all' => true,
		], false);
		$rows = [];
		if (is_object($resp) && isset($resp->data) && is_array($resp->data)) {
			$rows = $resp->data;
		} elseif (is_array($resp) && isset($resp['data']) && is_array($resp['data'])) {
			$rows = $resp['data'];
		}
		foreach ($rows as $row) {
			$r = is_object($row) ? $row : (object) $row;
			if (strcasecmp(trim((string) ($r->email ?? '')), $email) === 0) {
				return true;
			}
		}

		return false;
	}

	public function buscar_filtro_general($compId,$search,$status,$order_type,$order_sort,$show_per_page,$offset,$tipo_data){
		$all = $this->filterWarehouseUsersList($this->fetchWarehouseUsersFromApi(), $search, $status, $order_sort, $order_type);
		if ($tipo_data === 'cantidad') {
			return count($all);
		}

		$limit = is_numeric($show_per_page) ? (int) $show_per_page : count($all);
		$off = is_numeric($offset) ? (int) $offset : 0;

		return array_slice($all, $off, $limit);
	}

	public function lists($page=1)
	{
		$data = array('title' => 'List of Warehouse', 'page' => 'warehouse','subpage' => 'warehouselist');
		$compId = $this->session->userdata('compId');

		if(isset($_GET['page'])){
			$page = $_GET['page'];
		}

		$show_per_page = 25;
		$offset = ($page - 1) * $show_per_page;
		$data['list'] = $this->buscar_filtro_general($compId, '', '3', '', '', $show_per_page, $offset, 'rows');
		$langId = $this->session->userdata('language_id');
		$data['langdef'] = $this->langmodel->getMdl('comp-login-head', $langId);
		$data['lang_status_aditional'] = $this->HelperLanguage->status();
		$data['lang_status'] = $this->HelperLanguage->status_active();
		$data['lang_user'] = $this->HelperLanguage->user_validate();
		$data['lang_warehouse'] = $this->HelperLanguage->warehouse();

		$js = ["js"=>[

			"general/index.js",
			"general/change_status.js",
			"general/delete_entity.js",

		]];

		$this->load->view('company/header', $data);
		$this->load->view('company/warehouse/warehouse_list' , $data);
		$this->load->view('company/footer', $js);
	}

	public function add()
	{
		$data = array(
			'title' => 'Add New warehouse',
			'page' => 'warehouse',
			'subpage' => 'warehouseadd'
		);

		$langId = $this->session->userdata('language_id');
		$data['langdef'] = $this->langmodel->getMdl('comp-login-head', $langId);

		$js = ["js"=>[
			"users/send_form.js"
			]];

		$info['lang_warehouse'] = $this->HelperLanguage->warehouse();
		$this->load->view('company/header', $data);
		$this->load->view('company/warehouse/warehouse_add', $info);
		$this->load->view('company/footer', $js);
	}


	public function edit($user_id = false)
	{
		if ($user_id == false) {
			show_404();
		}
		$warehouseUser = $this->fetchWarehouseUserById((int) $user_id);
		if ($warehouseUser === null) {
			show_404();
		}

		$data = array(
			'title' => 'Edit Warehouse',
			'page' => 'warehouse',
			'subpage' => 'warehouselist'
		);
		$data['data'] = $warehouseUser;
		$info['lang_warehouse'] = $this->HelperLanguage->warehouse();
		$langId = $this->session->userdata('language_id');
		$data['langdef'] = $this->langmodel->getMdl('comp-login-head', $langId);
		$js = ["js"=>[
			"users/send_form.js"
			]];
		$this->load->view('company/header', $data);
		$this->load->view('company/warehouse/warehouse_edit', $info);
		$this->load->view('company/footer', $js);
	}

	public function delete($id)
	{
		$warehouse_user = $this->fetchWarehouseUserById((int) $id);
		if (!$warehouse_user) return show_404();

		$lang_user = $this->HelperLanguage->user_validate();

		$this->put("user/{$id}/disable");
		$this->session->set_flashdata('msg', '["'. $lang_user['delete_success'] .'", "success", "#A5DC86"]');

		return $this->Helper->response_json(["success" => true]);
	}

	public function save(){
		$request = $this->input->post();
		$errores_validacion = $this->validar_userAlta($request);
		$lang_user = $this->HelperLanguage->user_validate();

		if (count($errores_validacion) > 0) {
			$mensaje_error = $errores_validacion[0];
			// return $this->Helper->response_json(["success"=>false,'msg'=>$mensaje_error,'question_confirm'=>false]);
			return $this->Helper->response_json(["success"=>false,'msg'=>$mensaje_error]);

		}else{

			if ($_FILES['images']['name'] != '') {

				$extension = pathinfo($_FILES['images']['name'], PATHINFO_EXTENSION);
				$name_imagen = $this->Helper->get_codhash256() . "." . $extension;
				$_FILES['images']['name'] = $name_imagen;
				$config['upload_path'] = './uploads/company/';
				$config['allowed_types'] = '*';
				$this->load->library('upload', $config);

				if ($this->upload->do_upload('images')) $request["images"] = $this->upload->data()['file_name'];
			}

			$this->post('user', $this->buildWarehouseUserApiPayload($request));
			$msg = '["'. $lang_user["user_saved"]  .'", "success", "#A5DC86"]';
			$this->session->set_flashdata('msg', $msg);

		}
		return $this->Helper->response_json(["success"=>true]);
	}

	public function validar_userAlta($request) {

		$lang_user = $this->HelperLanguage->user_validate();

		if (!isset($request['email']) || $request['email'] == null || $request['email'] == '') {
			return [$lang_user['email_is_required']];
		}

		if (!isset($request['password']) || $request['password'] == null ||
			$request['password'] == '') {
			return [$lang_user['password_is_required']];
		}

		if ($this->warehouseUserEmailExists((string) $request['email'])) {
			return [$lang_user['existe_email']];
		}

		return [];
	}

	public function validar_userEdit($request, $user_id){

		$lang_user = $this->HelperLanguage->user_validate();

		if (!isset($request['email']) || $request['email'] == null || $request['email'] == '') {
			return [$lang_user['email_is_required']];
		}

		$user = $this->fetchWarehouseUserById((int) $user_id);
		if ($user === null) {
			return [$lang_user['email_is_required']];
		}

		if (strcasecmp((string) $user->email, (string) $request['email']) !== 0
			&& $this->warehouseUserEmailExists((string) $request['email'])) {
			return [$lang_user['existe_email']];
		}

		return [];
	}

	public function actualizar($user_id){
		$request = $this->input->post();
		$errores_validacion = $this->validar_userEdit($request, $user_id);
		$lang_user = $this->HelperLanguage->user_validate();

		if (count($errores_validacion) > 0) {
			$mensaje_error = $errores_validacion[0];
			return $this->Helper->response_json(["success"=>false,'msg'=>$mensaje_error]);

		}else{

            $warehouseUser = $this->fetchWarehouseUserById((int) $user_id);
			if ($warehouseUser === null) {
				return $this->Helper->response_json(["success"=>false]);
			}

			if ($_FILES['images']['name'] != '') {

				$extension = pathinfo($_FILES['images']['name'], PATHINFO_EXTENSION);
				$name_imagen = $this->Helper->get_codhash256() . "." . $extension;

				$_FILES['images']['name'] = $name_imagen;
				$config['upload_path'] = './uploads/company/';
				$config['allowed_types'] = '*';
				$this->load->library('upload', $config);

				if ($this->upload->do_upload('images')) {
                    $request["images"] = $this->upload->data()['file_name'];
                    if ($warehouseUser->profile_image) unlink('./uploads/company/' . $warehouseUser->profile_image);
                }

			}

			$this->put("user/{$user_id}", $this->buildWarehouseUserApiPayload($request, true));
			$msg = '["'. $lang_user["user_saved"]  .'", "success", "#A5DC86"]';
			$this->session->set_flashdata('msg', $msg);

		}
		return $this->Helper->response_json(["success"=> true]);
	}


	// public function delete($userId = false)
	// {
	// 	if ($userId != false)
	// 	{
	// 		$where = array('user_id' => $userId);
	// 		$data = $this->mymodel->get_by('warehouse_users', true, $where);

	// 		if (!$this->mymodel->delete('warehouse_users', $where)) {

	// 			$msg = 'error';

	// 		} else {

	// 			if (@$data->profile_image && file_exists('./uploads/company/'.@$data->profile_image)) {
	// 				@unlink('./uploads/company/'.@$data->profile_image);
	// 			}

	// 			$msg = '["Deleted successfully.", "success", "#A5DC86"]';
	// 		}
	// 		$this->session->set_flashdata('msg', $msg);
	// 	}
	// 	redirect(company_url('warehouse/lists'),'refresh');
	// }

	public function changeStatus()
	{
		if ($this->input->post('userId'))
		{
			$userId = (int) $this->input->post('userId');
			$status = (int) $this->input->post('status');

			$catInfo = $this->fetchWarehouseUserById($userId);

			if ($catInfo !== null)
			{
				if ($status == 1) {
					$msg = 'Warehouse activated successfully!';
				} else {
					$msg = 'Warehouse deactivated successfully!';
				}

				$response = $status === 1
					? $this->put("user/{$userId}/enable")
					: $this->put("user/{$userId}/disable");

				if (! $this->hasError($response))
				{
					echo '["'.$msg.'", "success", "#A5DC86"]';
				} else {
					echo '["Some error occured, Please try again!", "error", "#DD6B55"]';
				}
			}else{
				echo '["You have not permission for Edit", "error", "#DD6B55"]';
			}
		}
	}

	/**
	 * Estados del listado depósito (cod_status API / status_orders), mismo endpoint que Order::lists.
	 */
	private const WAREHOUSE_ORDER_COD_STATUSES = [9];

	/**
	 * Igual que Order::getOrdersQuery — parámetros para GET company/{id}/orders.
	 */
	protected function buildCompanyOrdersQuery(
		$desde_fecha,
		$hasta_fecha,
		$seller_id,
		$origin,
		$status,
		$grupo_id,
		$search,
		$catalog_id
	): array {
		$queryArr = [];
		if (! empty($desde_fecha)) {
			$queryArr['from'] = $desde_fecha;
		}
		if (! empty($hasta_fecha)) {
			$queryArr['to'] = $hasta_fecha;
		}
		if (! empty($seller_id)) {
			$queryArr['sellers'] = $seller_id;
		}
		if (! empty($origin)) {
			$queryArr['source'] = $origin;
		}
		if (! empty($status)) {
			$queryArr['status'] = is_array($status) ? implode('-', $status) : (string) $status;
		}
		if (! empty($grupo_id)) {
			$queryArr['group'] = $grupo_id;
		}
		if (! empty($search)) {
			$queryArr['search'] = $search;
		}
		if (! empty($catalog_id)) {
			$queryArr['catalog'] = $catalog_id;
		}

		return $queryArr;
	}

	/**
	 * Igual que Order::getOrderStatuses (mapa cod_status =&gt; objeto).
	 *
	 * @return array<int|string, object>
	 */
	protected function getOrderStatusesForList(): array
	{
		$langId = (int) ($this->langId ?? $this->getUserData('language_id') ?? 1);
		$resp = $this->getDataFromApi('order/warehouse/status-list', ['lang_id' => $langId], false);
		$rows = [];
		if (is_array($resp) && isset($resp['data']) && is_array($resp['data'])) {
			$rows = $resp['data'];
		} elseif (is_object($resp) && isset($resp->data) && is_array($resp->data)) {
			$rows = $resp->data;
		}

		$statuses = [];
		foreach ($rows as $status) {
			$s = is_object($status) ? $status : (object) $status;
			$cod = (int) ($s->cod_status ?? 0);
			if ($cod > 0) {
				$statuses[$cod] = $s;
			}
		}

		return $statuses;
	}

	/**
	 * Órdenes de depósito vía API exclusiva: GET order/company/{id}/warehouse
	 */
	public function orderLists($page = 1)
	{
		$langId = (int) $this->getUserData('language_id');
		$langOrder = $this->langmodel->getMdl('cmp-orders', $langId);
		if (! is_array($langOrder)) {
			$langOrder = [];
		}
		$compId = (int) $this->getCompany();
		$format_date = $this->Helper->format_date();

		$show_per_page = 25;
		$customerIdGet = $this->input->get('customer');
		$warehouseUserGet = $this->input->get('warehouse_user');

		$ordersQuery = [
			'page' => max(1, (int) ($this->input->get('page') ?: $page)),
			'per_page' => $show_per_page,
		];
		if ($customerIdGet !== null && $customerIdGet !== '') {
			$ordersQuery['customer_id'] = (int) $customerIdGet;
		}
		if ($warehouseUserGet !== null && $warehouseUserGet !== '') {
			$ordersQuery['warehouse_user_id'] = (int) $warehouseUserGet;
		}

		$orders = $this->getDataFromApi("order/company/{$compId}/warehouse", $ordersQuery, true);

		$list = [];
		$apiError = '';
		if ($this->hasError($orders)) {
			$apiError = is_array($orders) && isset($orders['message'])
				? (is_string($orders['message']) ? $orders['message'] : ($orders['message']['message'] ?? ''))
				: '';
		} elseif (is_array($orders) && ! empty($orders['data']) && is_array($orders['data'])) {
			foreach ($orders['data'] as $order) {
				$o = is_object($order) ? $order : (object) $order;
				$row = new \stdClass();
				$row->order_id = (int) ($o->order_id ?? 0);
				$row->order_number = (string) ($o->order_number ?? '');
				$row->source = $o->source ?? '';
				$oc = is_object($o) ? get_object_vars($o) : (array) $o;
				$ocLower = array_change_key_case($oc, CASE_LOWER);
				$normalizeDate = static function ($v): string {
					if ($v === null || $v === '') {
						return '';
					}
					if (is_array($v)) {
						return isset($v['date']) ? (string) $v['date'] : '';
					}
					if (is_object($v)) {
						return isset($v->date) ? (string) $v->date : '';
					}

					return (string) $v;
				};
				$createdRaw = $normalizeDate($o->created ?? $oc['created'] ?? null);
				$updatedRaw = $normalizeDate($o->updated ?? $oc['updated'] ?? null);
				$fechaOrden = $normalizeDate($o->fechaorden ?? $oc['fechaorden'] ?? $ocLower['fechaorden'] ?? null);
				if ($createdRaw === '' && $fechaOrden !== '') {
					$createdRaw = $fechaOrden;
				}
				if ($createdRaw === '' && $updatedRaw !== '') {
					$createdRaw = $updatedRaw;
				}
				$row->created = $createdRaw;
				$row->updated = $updatedRaw;
				$listCreated = $normalizeDate($o->list_date_created ?? $oc['list_date_created'] ?? null);
				$listUpdated = $normalizeDate($o->list_date_updated ?? $oc['list_date_updated'] ?? null);
				if ($listCreated !== '') {
					$row->created = $listCreated;
				}
				if ($listUpdated !== '') {
					$row->updated = $listUpdated;
				}
				$row->order_comments = $o->order_comments ?? $oc['order_comments'] ?? '';
				$row->order_status = (int) ($o->order_status ?? 0);
				$nc = (string) ($o->nombre_cliente ?? '');
				$nct = (string) ($o->nombre_contacto ?? '');
				$row->customer = strtoupper(trim($nc.' ('.$nct.')'));
				$row->nombre_cliente = $nc !== '' ? $nc : null;
				$row->nombre_contacto = $nct !== '' ? $nct : null;
				$row->customer_email = $o->customer_email ?? null;
				$row->customer_phone = $o->customer_phone ?? null;
				$row->customer_cell_phone = $o->customer_cell_phone ?? null;
				$row->customer_address = $o->customer_address ?? null;
				$row->seller = strtoupper(trim((string) ($o->seller_info ?? $o->seller ?? '')));
				$row->warehouse_user = strtoupper(trim((string) ($o->warehouse_user_name ?? $o->warehouse_user ?? '')));
				$list[] = $row;
			}
		}

		$totalRows = is_array($orders) ? (int) ($orders['total'] ?? 0) : 0;
		$base_url = company_url('warehouse/orders');

		$statusMap = $this->getOrderStatusesForList();
		$info['statusMap'] = $statusMap;
		$info['statuslist'] = array_values(array_intersect_key(
			$statusMap,
			array_flip(self::WAREHOUSE_ORDER_COD_STATUSES)
		));

		$custResp = $this->getDataFromApi("company/{$compId}/customers", ['per_page' => 500], true);
		$customers = [];
		if (is_array($custResp) && isset($custResp['data']) && is_array($custResp['data'])) {
			foreach ($custResp['data'] as $c) {
				$customers[] = is_object($c) ? $c : (object) $c;
			}
		}

		$info['warelist'] = $this->warehouseUsersForSelect();
		$info['list'] = $list;
		$info['apiError'] = $apiError;
		$info['customers'] = $customers;
		$info['warehouse_return_lang'] = [
			'btn_ok' => trim((string) ($langOrder['ok'] ?? $langOrder['cerrar'] ?? 'OK')),
			'success_tpl' => (string) ($langOrder['orden_returned_by_warehouse'] ?? 'Order %s returned.'),
			'error_not_found' => (string) ($langOrder['warehouse_return_order_not_found'] ?? 'Order not found.'),
			'error_failed' => (string) ($langOrder['warehouse_return_failed'] ?? 'Could not return order.'),
		];
		$info['lang_clientes'] = $this->langmodel->getMdl('cmp-clientes', $langId);
		$info['lang_order'] = $langOrder;
		$info['format_numeric'] = $this->format_numeric;
		$info['format_date'] = $format_date;
		$info['currency_symbol'] = $this->currency;

		$config = $this->warehouseOrdersPaginationConfig($base_url, $totalRows, $show_per_page);
		$this->pagination->initialize($config);
		$info['paginate'] = $this->pagination->create_links();

		$data = [
			'title' => $langOrder['title_warehouse_order'] ?? ($langOrder['pageTitle'] ?? ''),
			'page' => 'order',
			'company_id' => $compId,
		];
		$data['langdef'] = $this->langmodel->getMdl('comp-login-head', $langId);

		$this->addJsFile(base_url('companyasset/js/select2.min.js'));
		$this->addJsFile(base_url('companyasset/company/order/enviroment.js'));

		$layoutData = array_merge($data, $info);
		// No pasar $layoutData['js'] con forma anidada ['js'=>[...]]: el footer hace foreach ($js as $js)
		// y el primer valor sería un array → URL .../company/Array y el listado falla en consola.
		$layoutData['lang_datatable'] = $this->langmodel->getMdl('company-datatable', $langId);
		$layoutData['lang_json'] = $this->getDictionaryFromApi();
		$layoutData['jsFiles'] = is_array($this->jsFiles) ? $this->jsFiles : [];
		$layoutData = $this->prepareLayoutCompanyData($layoutData, 'company/warehouse/warehouse_order_list');
		$this->load->view('layouts/company', $layoutData);
	}

	public function csvImport()
	{
		$data = array('title' => 'Warehouse Import','page' => 'warehouse');
		$langId = $this->session->userdata('language_id');
		$data['langdef'] = $this->langmodel->getMdl('comp-login-head', $langId);
		$this->load->view('company/header',$data);
		$this->load->view('company/warehouse/warehouse_import');
		$this->load->view('company/footer');
	}

	public function csvImportSuccess(){
		$compId = $this->session->userdata('compId');
		$this->load->library('Spreadsheet_import');
		if (empty($_FILES['file']['name']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
			$msg = '["Some error occured, Please try again!", "error", "#DD6B55"]';
			$this->session->set_flashdata('msg', $msg);
			redirect(company_url('warehouse/lists'), 'refresh');
			return;
		}
		if (!Spreadsheet_import::isAllowedImportFilename($_FILES['file']['name'])) {
			$msg = '["Invalid Excel file", "error", "#DD6B55"]';
			$this->session->set_flashdata('msg', $msg);
			redirect(company_url('warehouse/lists'), 'refresh');
			return;
		}

		if (is_uploaded_file($_FILES['file']['tmp_name'])) {
			$rowc = 0;
			foreach (Spreadsheet_import::readRows($_FILES['file']['tmp_name'], true) as $line) {
				$email = trim((string) ($line[2] ?? ''));
				if ($email === '' || $this->warehouseUserEmailExists($email)) {
					continue;
				}

				$this->post('user', $this->buildWarehouseUserApiPayload([
					'first_name' => (string) ($line[0] ?? ''),
					'last_name' => (string) ($line[1] ?? ''),
					'email' => $email,
					'password' => (string) ($line[3] ?? ''),
					'phone_number' => (string) ($line[4] ?? ''),
				]));
				$rowc++;
			}
			$this->post('importhistory', [
				'company_id' => $compId,
				'import_date' => date('Y-m-d H:i:s'),
				'importedfile' => 'warehouse',
				'records_imported' => $rowc,
			]);
			$msg = '["Successfully uploaded", "success", "#A5DC86"]';
		} else {
			$msg = '["Some error occured, Please try again!", "error", "#DD6B55"]';
		}
		$this->session->set_flashdata('msg', $msg);
		redirect(company_url('warehouse/lists'), 'refresh');
	}

	public function exportCSV()
	{
		$usersData = $this->fetchWarehouseUsersFromApi();

		$header = ['Unique ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Created Date'];
		$body = [];

		foreach ($usersData as $v) {
			$body[] = [
				'UID' . str_pad((string) ($v->user_id ?? ''), 6, '0', STR_PAD_LEFT),
				(string) ($v->first_name ?? ''),
				(string) ($v->last_name ?? ''),
				(string) ($v->email ?? ''),
				(string) ($v->phone_number ?? ''),
				(string) ($v->created ?? ''),
			];
		}

		$this->load->model('PrintModel');
		$this->PrintModel->print_excel($header, $body, 'warehouseuser_list' . date('Ymd') . '.xlsx');
	}


	public function warehouse_orderview($order_id = false)
	{
		if ($order_id === false || ! is_numeric($order_id)) {
			return show_404();
		}

		$orderId = (int) $order_id;
		$compId = (int) $this->company;
		$langId = (int) ($this->langId ?? $this->getUserData('language_id') ?? 1);
		$langOrderTitle = $this->langmodel->getMdl('cmp-orders', $langId);
		if (! is_array($langOrderTitle)) {
			$langOrderTitle = [];
		}
		$pageTitleWh = $langOrderTitle['titulo_view_previa_detail'] ?? ($langOrderTitle['title_warehouse_order'] ?? '');

		$showResp = $this->getObjectFromApi("order/warehouse/order/{$orderId}", ['company_id' => $compId]);
		if (! is_object($showResp) || $this->hasError($showResp) || ! isset($showResp->data)) {
			return show_404();
		}

		$payload = $showResp->data;
		$order = is_object($payload->order ?? null) ? $payload->order : null;
		if ($order === null) {
			return show_404();
		}

		$detailsResp = $this->getObjectFromApi("order/warehouse/order/{$orderId}/details", ['company_id' => $compId]);
		$detalleList = [];
		if (is_object($detailsResp) && isset($detailsResp->data) && is_array($detailsResp->data)) {
			foreach ($detailsResp->data as $row) {
				$detalleList[] = is_object($row) ? $row : (object) $row;
			}
		}

		$customerId = (int) ($order->customer_id ?? 0);
		$productosPedidos = [];
		if ($customerId > 0) {
			$ppResp = $this->getObjectFromApi('order/warehouse/previously-ordered', [
				'company_id' => $compId,
				'customer_id' => $customerId,
			]);
			if (is_object($ppResp) && isset($ppResp->data) && is_array($ppResp->data)) {
				foreach ($ppResp->data as $row) {
					$productosPedidos[] = is_object($row) ? $row : (object) $row;
				}
			}
		}

		// No cargar el catálogo completo aquí: con miles de productos provoca timeout/500.
		// El modal de añadir línea puede poblarse bajo demanda si se reactiva.
		$productList = [];

		$statusMap = $this->getOrderStatusesForList();
		$statuslist = array_values($statusMap);

		$info = [];
		$info['data'] = $order;
		$info['productList'] = $productList;
		$info['lang_perfil'] = $this->langmodel->getMdl('perfil-vendedores', $langId);
		$info['lang_catalogo'] = $this->langmodel->getMdl('cmp-catalog', $langId);
		$info['productos_pedidos'] = $productosPedidos;
		$info['customer'] = is_object($payload->customer ?? null) ? $payload->customer : null;
		$info['wuser'] = is_object($payload->wuser ?? null) ? $payload->wuser : null;
		$info['seller'] = is_object($payload->seller ?? null) ? $payload->seller : null;
		$info['langprods'] = $this->langmodel->getMdl('cmp-products', $langId);
		$info['detalle_list'] = $detalleList;
		$info['lang_order'] = $langOrderTitle;
		$info['warehouseusers'] = $this->warehouseUsersForSelect();
		$info['statuslist'] = $statuslist;
		$info['statusMap'] = $statusMap;
		$info['format_numeric'] = $this->normalizeFormatNumeric($this->format_numeric);
		$info['cursumbol'] = (string) ($payload->currency_symbol ?? '$');
		$info['company'] = $this->company_info;

		$info['mostrar_asignados'] = false;
		$orderStatus = (int) ($order->order_status ?? 0);
		$hasWarehouseAssignee = ! empty($order->warehouse_user_id) || ! empty($order->warehouse_id);
		$statusMeta = $statusMap[$orderStatus] ?? null;
		if ($statusMeta === null) {
			foreach ($statuslist as $statusRow) {
				if ((int) ($statusRow->cod_status ?? 0) === $orderStatus) {
					$statusMeta = $statusRow;
					break;
				}
			}
		}
		if (is_object($statusMeta)
			&& $hasWarehouseAssignee
			&& (int) ($statusMeta->can_show_data_asigned ?? 0) === 1) {
			$info['mostrar_asignados'] = true;
		}
		$info['order_status_meta'] = is_object($statusMeta) ? $statusMeta : null;

		$layoutData = array_merge([
			'title' => $pageTitleWh,
			'page' => 'order',
			'company_id' => $compId,
			'langdef' => $this->langmodel->getMdl('comp-login-head', $langId),
		], $info);
		$layoutData['lang_datatable'] = $this->langmodel->getMdl('company-datatable', $langId);
		$layoutData['lang_json'] = $this->getDictionaryFromApi();
		$this->addJsFile('/companyasset/company/order/export.js');
		$layoutData['jsFiles'] = is_array($this->jsFiles) ? $this->jsFiles : [];
		$layoutData = $this->prepareLayoutCompanyData($layoutData, 'company/warehouse/order_detailsview');
		$layoutData['format_numeric'] = $this->normalizeFormatNumeric($layoutData['format_numeric'] ?? $this->format_numeric);
		$this->load->view('layouts/company', $layoutData);
	}

	/**
	 * @param mixed $format
	 */
	private function normalizeFormatNumeric($format): object
	{
		if (is_object($format) && isset($format->data) && is_object($format->data)) {
			$format = $format->data;
		}
		if (! is_object($format) || isset($format->error)) {
			$format = is_object($this->format_numeric) ? $this->format_numeric : null;
		}

		return (object) [
			'digits' => (int) (is_object($format) ? ($format->digits ?? 2) : 2),
			'radixPoint' => (string) (is_object($format) ? ($format->radixPoint ?? '.') : '.'),
			'groupSeparator' => (string) (is_object($format) ? ($format->groupSeparator ?? ',') : ','),
		];
	}


	/**
	 * Retorno por almacén: API status-by-order-number (cod. 14).
	 *
	 * @return array{ok:bool,message:string,http_status:int}
	 */
	private function warehouseExecuteReturnOrder(int $orderId): array
	{
		$compId = (int) $this->session->userdata('compId');
		$lang_order = $this->lang_order;

		if ($orderId < 1) {
			return [
				'ok' => false,
				'message' => (string) ($lang_order['warehouse_return_order_not_found'] ?? 'Order not found.'),
				'http_status' => 404,
			];
		}

		$orderResp = $this->getObjectFromApi("order/warehouse/order/{$orderId}", ['company_id' => $compId]);
		if (! is_object($orderResp) || $this->hasError($orderResp) || ! isset($orderResp->data->order)) {
			return [
				'ok' => false,
				'message' => (string) ($lang_order['warehouse_return_order_not_found'] ?? 'Order not found.'),
				'http_status' => 404,
			];
		}
		$orderData = $orderResp->data->order;
		$orderNumber = trim((string) ($orderData->order_number ?? ''));
		if ($orderNumber === '') {
			return [
				'ok' => false,
				'message' => (string) ($lang_order['warehouse_return_order_not_found'] ?? 'Order not found.'),
				'http_status' => 404,
			];
		}

		$response = $this->post('order/status-by-order-number', [
			'company_id' => $compId,
			'order_number' => $orderNumber,
			'cod_status' => 14,
		]);

		if ($this->hasError($response)) {
			$errText = (string) ($lang_order['warehouse_return_failed'] ?? 'Could not return order.');
			if (is_array($response)) {
				$m = $response['message'] ?? null;
				if (is_string($m) && $m !== '') {
					$errText = $m;
				} elseif (is_array($m) && isset($m['message']) && is_string($m['message'])) {
					$errText = $m['message'];
				}
			}

			return ['ok' => false, 'message' => $errText, 'http_status' => 422];
		}

		$tpl = (string) ($lang_order['orden_returned_by_warehouse'] ?? 'Order %s returned.');
		$msg = sprintf($tpl, $orderNumber, $orderNumber);

		return ['ok' => true, 'message' => $msg, 'http_status' => 200];
	}

	public function returnorden($order_id)
	{
		$r = $this->warehouseExecuteReturnOrder((int) $order_id);
		if (! $r['ok']) {
			$this->session->set_flashdata('msg', '["'.addslashes($r['message']).'", "error", "#DD6B55"]');
		} else {
			$this->session->set_flashdata('msg', '["'.addslashes($r['message']).'", "success", "#A5DC86"]');
		}
		redirect(company_url('warehouse/orders'), 'refresh');
	}

	/**
	 * Mismo flujo que returnorden, respuesta JSON para AJAX (sin recarga inmediata).
	 */
	public function returnorden_ajax(): void
	{
		if (strtoupper((string) $this->input->server('REQUEST_METHOD')) !== 'POST') {
			$this->output->set_status_header(405);
			$this->output->set_content_type('application/json', 'utf-8');
			echo json_encode(['success' => false, 'message' => 'Method not allowed']);

			return;
		}

		$orderId = (int) $this->input->post('order_id');
		$r = $this->warehouseExecuteReturnOrder($orderId);
		$this->output->set_status_header($r['http_status']);
		$this->output->set_content_type('application/json', 'utf-8');
		echo json_encode(['success' => $r['ok'], 'message' => $r['message']]);
	}



	public function enable($id){
		$lang = $this->HelperLanguage->status_active();

		$this->put("user/{$id}/enable");
		$this->Helper->response_json(["success"=>true,"url"=> company_url('warehouse/disable/'.$id),
													 "class_toremove" =>"btn-success",
													 "type_btn" => "btn-warning",
													 "data-loading" =>$lang["loader-disabling"],
													 "text_btn"=>$lang["option_desactive"],
													 "disable" => false,
													 "change_inmodal"=>true
													]);
	}

	public function disable($id){
		$lang = $this->HelperLanguage->status_active();

		$this->put("user/{$id}/disable");
		return $this->Helper->response_json(["success"=>true,"url"=>company_url('warehouse/enable/'.$id),
						  "type_btn"=>"btn-success",
						  "class_toremove" =>"btn-warning",
						  "data-loading"=>$lang["loader-enabling"],
						  "text_btn"=>$lang["option_active"],
						  "disable" => true,
						  "change_inmodal"=>true
						]);
	}

	/**
	 * Menú "Warehouse Orders": /company/warehouse/orders
	 * Misma vista y filtros que orderLists (órdenes con estado almacén, legado).
	 */
	public function orders($page = 1)
	{
		return $this->orderLists($page);
	}

}

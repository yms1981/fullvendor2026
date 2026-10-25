<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once APPPATH . 'controllers/company/sellerprofile/Home.php';

/**
 * @property Langmodel $langmodel
 * @property HelperLanguage $HelperLanguage
 */
class Sellerprofile extends Home
{
	/**
	 * Lista de perfiles de la empresa vía API (tabla seller_profiles).
	 *
	 * @return array<int, object>
	 */
	private function fetchProfiles(): array
	{
		$companyId = $this->getCompanyId();
		$response = $this->getDataFromApi($this->entityPath, [
			'company' => $companyId,
			'all' => true,
			'orderProperty' => 'profile_id',
			'orderDirection' => 'DESC',
		], false);

		return $this->normalizeListResponse($response);
	}

	/**
	 * Perfiles activos (status = 1) de la empresa vía API.
	 *
	 * @return array<int, object>
	 */
	private function fetchActiveProfiles(): array
	{
		$companyId = $this->getCompanyId();
		$response = $this->getDataFromApi($this->entityPath, [
			'company' => $companyId,
			'status' => 1,
			'all' => true,
		], false);

		return $this->normalizeListResponse($response);
	}

	/**
	 * Un perfil por id vía API.
	 */
	private function fetchProfile($profileId)
	{
		$response = $this->getObjectFromApi("{$this->entityPath}/{$profileId}");
		if ($this->hasError($response)) {
			return null;
		}
		if (is_object($response) && isset($response->data)) {
			return $response->data;
		}

		return $response;
	}

	/**
	 * Normaliza la respuesta de listado de la API a un arreglo de objetos.
	 *
	 * @param mixed $response
	 * @return array<int, object>
	 */
	private function normalizeListResponse($response): array
	{
		if ($this->hasError($response)) {
			return [];
		}
		if (is_object($response) && isset($response->data) && is_array($response->data)) {
			return $response->data;
		}
		if (is_array($response)) {
			return $response;
		}

		return [];
	}

	public function lists($page = 1)
	{
		$this->index();
	}

	public function add()
	{
		$data = array(
			'title' => 'Add New User Profile',
			'page' => 'userprofile',
			'subpage' => 'userprofileadd'
		);

		$data['profileList'] = $this->fetchActiveProfiles();
		$langId = $this->getUserData('language_id');
		$data['langdef'] = $this->langmodel->getMdl('comp-login-head', $langId);
		$info['lang_userprofile'] = $this->langmodel->getMdl('cmp-profile', $langId);
		$info['lang_vendor'] = $this->HelperLanguage->profile_vendor();

		$this->load->view('company/header', $data);
		$this->load->view('company/profile_add',$info);
		$this->load->view('company/footer');
	}

	public function create()
	{
		$this->form_validation->set_rules('profile_name', 'Profile Name', 'trim|required');

		if ($this->form_validation->run() != false)
		{
			$compId = $this->getCompanyId();

			$mydata = array(
				'profile_name' =>$this->input->post('profile_name'),
				'company_id' => $compId,
				'can_change_price' => $this->testInput($this->input->post('can_change_price')),
				'can_send_catalog' => $this->testInput($this->input->post('can_send_catalog')),
				'can_create_customer' => $this->testInput($this->input->post('can_create_customer')),
				'order_discount' => 0,
				'order_disc_price' => '',
				'order_net_discount' => 0,
				'order_net_disc_price' => '',
				'status' => 1,
				'created' =>date("Y-m-d H:i:s")
			);

			$this->post($this->entityPath, $mydata);

			$lang_vendor = $this->HelperLanguage->profile_vendor();
			$okText = $lang_vendor['add_success'] ?? 'New User profile is added successfully!';
			$msg = json_encode([$okText, 'success', '#A5DC86']);

			if ($this->input->is_ajax_request()) {
				header('Content-Type: application/json');
				echo json_encode(['success' => true, 'redirect' => company_url('sellerprofile/lists')]);
				return;
			}

			$this->flashMessage($msg);
			redirect(company_url('sellerprofile/lists'),'refresh');
		}
	}

	public function edit($profileId = false)
	{
		if(!$profileId) return show_404();

		$profile = $this->fetchProfile($profileId);
		if(!$profile) return show_404();

		$data = array('title' => 'Edit user profile','page' => 'userprofile','subpage' => 'userprofilelist');

		$data['profileList'] = $this->fetchActiveProfiles();
		$data['data'] = $profile;
		$langId = $this->getUserData('language_id');
		$data['lang_vendor'] = $this->HelperLanguage->profile_vendor();
		$data['langdef'] = $this->langmodel->getMdl('comp-login-head', $langId);
		$data['lang_userprofile'] = $this->langmodel->getMdl('cmp-profile', $langId);

		$data['main_content'] = 'company/profile_edit';

		$companyId = $this->getCompany();
		if ($companyId) {
			$data['company'] = $this->getObjectFromApi('company/'.$companyId);
		} else {
			$data['company'] = null;
		}
		$data['user'] = $this->getUser();

		$companyIdForApi = $this->getCompanyId();
		if ($companyIdForApi) {
			$data['format_numeric'] = $this->getObjectFromApi("company/{$companyIdForApi}/format-numeric");
			$data['format_currency'] = $this->getRawFromApi("company/{$companyIdForApi}/currency-symbol", []);
			$data['code_currency'] = $this->getRawFromApi("company/{$companyIdForApi}/currency-code", []);
		} else {
			$data['format_numeric'] = null;
			$data['format_currency'] = null;
			$data['code_currency'] = null;
		}

		try {
			$langNav = $this->HelperLanguage->lang_navigation();
		} catch (\Exception $e) {
			$langNav = ['profile' => 'Profile', 'import' => 'Import', 'change_password' => 'Change Password'];
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
				]
			],
			'catalog' => ['url' => 'catalogs', 'label' => $langNav['catalog'] ?? 'Catalog', 'icon' => 'fa-solid fa-file-circle-plus'],
		];

		$data['config_menu'] = [
			'url' => '',
			'label' => $langNav['profile'] ?? 'Profile',
			'icon' => 'fa fa-cog fa-fw fa-lg',
			'subitems' => [
				'profile' => ['url' => 'profile', 'label' => $langNav['profile'] ?? 'Profile', 'icon' => 'fa fa-cog fa-fw fa-lg'],
				'import' => ['url' => 'import/lists', 'label' => $langNav['import'] ?? 'Import', 'icon' => 'mdi mdi-import'],
				'logs' => ['url' => 'logs/lists', 'label' => $langNav['logs'] ?? 'Logs', 'icon' => 'mdi mdi-database'],
			]
		];

		$this->load->view('layouts/company', $data);
	}

	public function update()
	{
		$lang_vendor = $this->HelperLanguage->profile_vendor();

		if ($this->input->post('profile_name') && $this->input->post('profile_id'))
		{
			$profile_id = $this->input->post('profile_id');

			$mydata = array(
				'profile_name' =>$this->testInput($this->input->post('profile_name')),
				'can_change_price' => $this->testInput($this->input->post('can_change_price')),
				'can_send_catalog' => $this->testInput($this->input->post('can_send_catalog')),
				'can_create_customer' => $this->testInput($this->input->post('can_create_customer')),
			);

			$isAjax = $this->input->is_ajax_request() || $this->input->server('HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest';

			$response = $this->put("{$this->entityPath}/{$profile_id}", $mydata);

			if ($this->hasError($response))
			{
				if ($isAjax) {
					$this->output->set_content_type('application/json')->set_output(json_encode([
						'success' => false,
						'message' => $lang_vendor['update_error'] ?? 'Error updating the profile'
					]));
					return;
				}
				$msg = 'error';
			} else {
				$okText = $lang_vendor['update_success'] ?? 'User Profile updated successfully!';
				if ($isAjax) {
					$this->output->set_content_type('application/json')->set_output(json_encode([
						'success' => true,
						'message' => $okText,
						'redirect' => company_url('sellerprofile/lists')
					]));
					return;
				}
				$msg = json_encode([$okText, 'success', '#A5DC86']);
			}

			$this->flashMessage($msg);
			redirect(urldecode($this->input->get('redirectto')),'refresh');
		}

		if ($this->input->is_ajax_request() || $this->input->server('HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest') {
			$this->output->set_content_type('application/json')->set_output(json_encode([
				'success' => false,
				'message' => $lang_vendor['invalid_data'] ?? 'Invalid data'
			]));
			return;
		}

		redirect(company_url('sellerprofile/lists'),'refresh');
	}

	public function delete($profile_id = false)
	{
		$profile = $this->fetchProfile($profile_id);
		if (!$profile) return show_404();

		$lang_vendor = $this->HelperLanguage->profile_vendor();
		$this->apiDelete("{$this->entityPath}/{$profile_id}");

		$okText = $lang_vendor['delete_success'] ?? 'Seller profile deleted successfully';
		$this->flashMessage(json_encode([$okText, 'success', '#A5DC86']));

		$this->output->set_content_type('application/json')->set_output(json_encode([
			'success' => true,
			'message' => $okText,
		]));
	}

	public function getEditForm($profileId = false)
	{
		if(!$profileId) {
			show_404();
			return;
		}

		$profile = $this->fetchProfile($profileId);
		if(!$profile) {
			show_404();
			return;
		}

		$data['data'] = $profile;
		$data['profileList'] = $this->fetchActiveProfiles();

		$langId = $this->getUserData('language_id');
		$data['lang_vendor'] = $this->HelperLanguage->profile_vendor();
		$data['lang_userprofile'] = $this->langmodel->getMdl('cmp-profile', $langId);
		$data['use_sellerprofile'] = true;

		$formContent = $this->load->view('company/userprofile/modals/edit_profile_form', $data, true);
		echo $formContent;
	}

	public function getAddForm()
	{
		$data['profileList'] = $this->fetchActiveProfiles();
		$data['data'] = null;

		$langId = $this->getUserData('language_id');
		$data['lang_vendor'] = $this->HelperLanguage->profile_vendor();
		$data['lang_userprofile'] = $this->langmodel->getMdl('cmp-profile', $langId);
		$data['use_sellerprofile'] = true;

		$formContent = $this->load->view('company/userprofile/modals/add_profile_form', $data, true);
		echo $formContent;
	}

	/**
	 * Herramientas CRUD: Sellerprofile.php compite con la carpeta sellerprofile/.
	 *
	 * @return array{headers: array<int, string>, rows: array<int, array<int, string>>, title: string}
	 */
	private function buildSellerProfileListPdfDataset(): array
	{
		$lang = $this->getConfigCrudLang();
		$title = $this->resolveConfigCrudPageTitle($lang, 'pageTitle', 'list_profile_vendor');
		$list = $this->fetchConfigCrudApiList('sellerprofiles', [
			'company' => $this->getCompanyId(),
		]);

		$yes = $lang['si'] ?? '1';
		$no = $lang['no'] ?? '0';

		$headers = [
			$lang['name_profile'] ?? '',
			$lang['order_discount'] ?? '',
			$lang['change_price'] ?? '',
			$lang['send_catalog'] ?? '',
			$lang['create_customer'] ?? '',
			$lang['created'] ?? '',
		];

		$rows = [];
		foreach ($list as $item) {
			$item = (object) $item;
			$rows[] = [
				(string) ($item->profile_name ?? ''),
				!empty($item->order_discount) ? $yes : $no,
				!empty($item->can_change_price) ? $yes : $no,
				!empty($item->can_send_catalog) ? $yes : $no,
				!empty($item->can_create_customer) ? $yes : $no,
				$this->normalizeConfigCrudDate($item->created ?? null),
			];
		}

		return ['headers' => $headers, 'rows' => $rows, 'title' => $title];
	}

	public function listPdf()
	{
		$dataset = $this->buildSellerProfileListPdfDataset();
		$this->sendConfigListPdf(
			$dataset['headers'],
			$dataset['rows'],
			$dataset['title'] !== '' ? $dataset['title'] : 'seller_profiles',
			'seller_profiles'
		);
	}

	public function export()
	{
		$lang = $this->getConfigCrudLang();
		$list = $this->fetchConfigCrudApiList('sellerprofiles', [
			'company' => $this->getCompanyId(),
		]);

		$headers = [
			$lang['name_profile'] ?? 'profile_name',
			$lang['order_discount'] ?? 'order_discount',
			$lang['order_net_discount'] ?? 'order_net_discount',
			$lang['change_price'] ?? 'can_change_price',
			$lang['send_catalog'] ?? 'can_send_catalog',
			$lang['create_customer'] ?? 'can_create_customer',
			$lang['created'] ?? 'created',
		];

		$yes = $lang['si'] ?? '1';
		$no = $lang['no'] ?? '0';

		$rows = [];
		foreach ($list as $item) {
			$item = (object) $item;
			$rows[] = [
				(string) ($item->profile_name ?? ''),
				!empty($item->order_discount) ? $yes : $no,
				!empty($item->order_net_discount) ? $yes : $no,
				!empty($item->can_change_price) ? $yes : $no,
				!empty($item->can_send_catalog) ? $yes : $no,
				!empty($item->can_create_customer) ? $yes : $no,
				$this->normalizeConfigCrudDate($item->created ?? null),
			];
		}

		$this->sendConfigCsvDownload($headers, $rows, 'seller_profiles_' . date('Ymd') . '.csv');
	}

	public function csvImport()
	{
		$lang = $this->getConfigCrudLang();
		$lang['template_download_url'] = import_template_download_url('sellerprofilescsv.xlsx');
		$this->langArr = $lang;
		$this->showConfigCsvImportView('sellerprofile', company_url('sellerprofile/lists'), 'csvImportSuccess');
	}

	public function csvImportSuccess()
	{
		$schema = ConfigCrudImportSchemas::sellerprofile();
		if (!empty($_FILES['file']['name']) && empty($this->input->post('import_token'))) {
			$this->handleConfigCrudImportPage('sellerprofile', company_url('sellerprofile/lists'), (string) $schema['template'], $schema);

			return;
		}
		$_POST['step'] = 'confirm';
		$this->executeConfigCrudImportConfirm(company_url('sellerprofile/lists'), $schema);
	}
}

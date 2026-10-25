<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Category extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->checkLoggedInOrLoginWithRedirect();
	}


	public function add()
	{
		$data = [
			'title' => $this->categoryAdminLang('form_add_title', 'New category'),
			'page' => 'category',
			'subpage' => 'categorylist',
			'categoryFormLang' => $this->categoryFormLang(),
		];

		$this->load->view('admin/header', $data);
		$this->load->view('admin/category_add');
		$this->load->view('admin/footer');
	}


	public function create()
	{
		if ($this->input->post('category_name') != '')
		{
				$mydata = array(
					'category_name' => $this->testInput($this->input->post('category_name')),
					'category_status' => 1,
					'category_created_at'=> date('Y-m-d H:i:s'),
				);

				$response = $this->post('companiescategory', $mydata);
				if ($this->hasError($response))
				{
					$msg = 'error';
				} else {
					$msg = json_encode([
						$this->categoryAdminLang('category_added_success', 'Category added successfully.'),
						'success',
						'#A5DC86',
					], JSON_UNESCAPED_UNICODE);
				}

			$this->flashMessage($msg);
		}
		redirect(admin_url('category/lists'),'refresh');
	}


	public function lists()
	{
		$data = [
			'title' => $this->categoryAdminLang('list_page_title', 'Company categories'),
			'page' => 'category',
			'subpage' => 'categorylist',
			'categoryListLang' => $this->categoryListTableLang(),
			'categoryDeleteUi' => $this->categoryDeleteModalLang(),
			'categoryStatusUrl' => admin_url('category/changeStatus'),
		];

		$data['list'] = $this->fetchCategoryList();

		$this->load->view('admin/header', $data);
		$this->load->view('admin/category_list');
		$this->load->view('admin/footer');
	}


	public function edit($cat_id = false)
	{
		if ($cat_id == false) {
			show_404();
		}

		$category = $this->fetchCategoryById((int) $cat_id);
		if ($category === null) {
			show_404();
		}

		$data = array(
			'title' => $this->categoryAdminLang('form_edit_title', 'Edit category'),
			'page' => 'category',
			'subpage' => 'categorylist'
		);
		$data['categoryFormLang'] = $this->categoryFormLang();
		$data['data'] = $category;

		$this->load->view('admin/header', $data);
		$this->load->view('admin/category_edit');
		$this->load->view('admin/footer');
	}


	public function update()
	{
		if ($this->input->post('category_name') && $this->input->post('cat_id'))
		{
			$cat_id = (int) $this->input->post('cat_id');
			if ($this->fetchCategoryById($cat_id) === null) {
				show_404();
			}

			$mydata = array(
				'category_name' => $this->testInput($this->input->post('category_name')),
				'category_status' => $this->input->post('category_status')
			);

			$response = $this->put('companiescategory/' . $cat_id, $mydata);
			if ($this->hasError($response)) {
				$msg = 'error';
			} else {
				$msg = json_encode([
					$this->categoryAdminLang('category_updated_success', 'Category updated successfully.'),
					'success',
					'#A5DC86',
				], JSON_UNESCAPED_UNICODE);
			}

			$this->flashMessage( $msg);
			redirect(admin_url('category/lists'),'refresh');
		} else {
			show_404();
		}
	}


	public function changeStatus(): void
	{
		header('Content-Type: application/json; charset=utf-8');
		$errMsg = $this->categoryAdminLang('category_status_update_error', 'The status could not be updated. Please try again.');
		if (strtoupper((string) $this->input->method()) !== 'POST') {
			echo json_encode([$errMsg, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE);

			return;
		}
		$category_id = (int) $this->input->post('category_id');
		$category_status = (int) $this->input->post('category_status');
		if ($category_id < 1 || ($category_status !== 0 && $category_status !== 1)) {
			echo json_encode([$errMsg, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE);

			return;
		}
		if ($this->fetchCategoryById($category_id) === null) {
			echo json_encode([$errMsg, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE);

			return;
		}

		$response = $this->put('companiescategory/' . $category_id, ['category_status' => $category_status]);
		if ($this->hasError($response)) {
			echo json_encode([$errMsg, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE);

			return;
		}
		if ($category_status === 1) {
			$okMsg = $this->categoryAdminLang('category_status_activated_success', 'Category enabled.');
		} else {
			$okMsg = $this->categoryAdminLang('category_status_deactivated_success', 'Category disabled.');
		}
		echo json_encode([$okMsg, 'success', '#A5DC86'], JSON_UNESCAPED_UNICODE);
	}


	public function delete($cId = false)
	{
		if ($cId === false || $cId === '') {
			redirect(admin_url('category/lists'), 'refresh');

			return;
		}

		$catId = (int) $cId;
		if ($this->fetchCategoryById($catId) === null) {
			$this->flashMessage(json_encode([
				$this->categoryAdminLang('category_not_found', 'Category not found.'),
				'warning',
				'#DD6B55',
			], JSON_UNESCAPED_UNICODE));
			redirect(admin_url('category/lists'), 'refresh');

			return;
		}

		if ($this->countCompaniesUsingCategoryId($catId) > 0) {
			$this->flashMessage(json_encode([
				$this->categoryAdminLang(
					'category_cannot_delete_in_use',
					'This category cannot be deleted because it is assigned to one or more companies.'
				),
				'warning',
				'#DD6B55',
			], JSON_UNESCAPED_UNICODE));
			redirect(admin_url('category/lists'), 'refresh');

			return;
		}

		$response = $this->apiDelete('companiescategory/' . $catId);
		if ($this->hasError($response)) {
			$this->flashMessage(json_encode([
				$this->categoryAdminLang('category_delete_failed', 'The category could not be deleted. Please try again.'),
				'error',
				'#DD6B55',
			], JSON_UNESCAPED_UNICODE));
		} else {
			$this->flashMessage(json_encode([
				$this->categoryAdminLang('category_deleted_success', 'Category deleted successfully.'),
				'success',
				'#A5DC86',
			], JSON_UNESCAPED_UNICODE));
		}

		redirect(admin_url('category/lists'), 'refresh');
	}

	/**
	 * @return array<int, object>
	 */
	private function fetchCategoryList(): array
	{
		$resp = $this->getDataFromApi('companiescategory', [
			'page' => 1,
			'perPage' => 5000,
			'orderProperty' => 'category_id',
			'orderDirection' => 'desc',
		], true);

		if (! is_array($resp) || $this->hasError($resp)) {
			return [];
		}

		$rows = $this->extractApiListRows($resp);
		if (! is_array($rows)) {
			return [];
		}

		$list = [];
		foreach ($rows as $row) {
			$list[] = is_array($row) ? (object) $row : $row;
		}

		return $list;
	}

	private function fetchCategoryById(int $categoryId): ?object
	{
		if ($categoryId < 1) {
			return null;
		}

		$resp = $this->getObjectFromApi('companiescategory/' . $categoryId);
		if ($this->hasError($resp)) {
			return null;
		}

		if (is_object($resp) && isset($resp->data) && is_object($resp->data)) {
			return $resp->data;
		}

		return is_object($resp) ? $resp : null;
	}

	/**
	 * Cuenta empresas que usan esta categoría (company_category_id o category_id legado).
	 */
	private function countCompaniesUsingCategoryId(int $categoryId): int
	{
		if ($categoryId < 1) {
			return 0;
		}

		$resp = $this->getDataFromApi('company', [
			'companyCategoryId' => $categoryId,
			'page' => 1,
			'perPage' => 1,
		], true);

		if (! is_array($resp) || $this->hasError($resp)) {
			return 0;
		}

		return (int) ($resp['meta']['total'] ?? 0);
	}

	private function adminLangJsonFilename(): string
	{
		$langId = (int) ($this->session->userdata('language_id') ?? $this->session->userdata('languageid') ?? 1);
		$map = [
			1 => 'en-EN.json',
			2 => 'es-ES.json',
			3 => 'pt-PT.json',
			4 => 'it-IT.json',
			5 => 'ru-RU.json',
			6 => 'de-DE.json',
			7 => 'zh-CN.json',
		];

		return $map[$langId] ?? 'en-EN.json';
	}

	private function categoryAdminLang(string $key, string $fallback = ''): string
	{
		$path = dirname(APPPATH, 2) . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $this->adminLangJsonFilename();
		if (! is_readable($path)) {
			return $fallback;
		}
		$raw = @file_get_contents($path);
		if ($raw === false || $raw === '') {
			return $fallback;
		}
		$data = json_decode($raw, true);
		if (! is_array($data) || ! isset($data['cmp-admin-category'][$key]) || ! is_string($data['cmp-admin-category'][$key])) {
			return $fallback;
		}
		$v = $data['cmp-admin-category'][$key];

		return $v !== '' ? $v : $fallback;
	}

	/**
	 * @return array<string, string>
	 */
	private function categoryListTableLang(): array
	{
		return [
			'list_add_new' => $this->categoryAdminLang('list_add_new', 'Add category'),
			'col_num' => $this->categoryAdminLang('col_num', '#'),
			'col_category_name' => $this->categoryAdminLang('col_category_name', 'Category name'),
			'col_created' => $this->categoryAdminLang('col_created', 'Created'),
			'col_status' => $this->categoryAdminLang('col_status', 'Status'),
			'col_actions' => $this->categoryAdminLang('col_actions', 'Actions'),
			'status_badge_enable' => $this->categoryAdminLang('status_badge_enable', 'Enabled'),
			'status_badge_disable' => $this->categoryAdminLang('status_badge_disable', 'Disabled'),
			'statusSwitchAria' => $this->categoryAdminLang('category_status_switch_aria', 'Enable or disable this category'),
			'statusError' => $this->categoryAdminLang('category_status_update_error', 'The status could not be updated. Please try again.'),
			'tooltip_edit' => $this->categoryAdminLang('tooltip_edit', 'Edit'),
			'tooltip_delete' => $this->categoryAdminLang('tooltip_delete', 'Delete'),
		];
	}

	/**
	 * @return array<string, string>
	 */
	private function categoryDeleteModalLang(): array
	{
		return [
			'title' => $this->categoryAdminLang('delete_modal_title', 'Delete this category?'),
			'text' => $this->categoryAdminLang('delete_modal_text', 'This action cannot be undone.'),
			'yes' => $this->categoryAdminLang('delete_modal_yes', 'Yes, delete'),
			'no' => $this->categoryAdminLang('delete_modal_no', 'Cancel'),
		];
	}

	/**
	 * @return array<string, string>
	 */
	private function categoryFormLang(): array
	{
		return [
			'label_category_name' => $this->categoryAdminLang('label_category_name', 'Category name'),
			'placeholder_category_name' => $this->categoryAdminLang('placeholder_category_name', 'Category name'),
			'label_status' => $this->categoryAdminLang('label_status', 'Status'),
			'status_option_enable' => $this->categoryAdminLang('status_option_enable', 'Enabled'),
			'status_option_disable' => $this->categoryAdminLang('status_option_disable', 'Disabled'),
			'btn_submit' => $this->categoryAdminLang('btn_submit', 'Save'),
			'btn_cancel' => $this->categoryAdminLang('btn_cancel', 'Cancel'),
		];
	}


}

<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Cms extends MY_Controller
{
	/** @var array<string, string>|null */
	private $cmsAdminTextsCache = null;

	public function __construct()
	{
		parent::__construct();
		$this->load->helper('admin_shell');
		$this->checkLoggedInOrLoginWithRedirect();
	}

	public function index()
	{
		$data = [
			'title' => $this->cmsLang('list_title', 'CMS pages'),
			'page' => 'cms',
			'subpage' => 'list',
			'pageTexts' => $this->cmsAdminTexts(),
			'cmsDeleteUi' => $this->cmsDeleteUiPayload(),
			'list' => $this->fetchCmsList(),
		];

		$this->load->view('admin/header', $data);
		$this->load->view('admin/cms_list', $data);
		$this->load->view('admin/footer');
	}

	public function add()
	{
		$data = [
			'title' => $this->cmsLang('form_add_title', 'New CMS page'),
			'page' => 'cms',
			'subpage' => 'list',
			'pageTexts' => $this->cmsAdminTexts(),
		];

		$this->load->view('admin/header', $data);
		$this->load->view('admin/cms_add', $data);
		$this->load->view('admin/footer');
	}

	public function create()
	{
		$row = $this->readCmsPostRow();
		if ($row === null) {
			$this->flashMessage(json_encode([
				$this->cmsLang('msg_validation_error', 'Please check the form and try again.'),
				'warning',
				'#DD6B55',
			], JSON_UNESCAPED_UNICODE));
			redirect(admin_url('cms/add'), 'refresh');

			return;
		}

		$now = date('Y-m-d H:i:s');
		$row['created'] = $now;
		$row['modified'] = $now;

		$response = $this->post('cm', $row);
		if ($this->hasError($response)) {
			$this->flashMessage('error');
		} else {
			$this->flashMessage(json_encode([
				$this->cmsLang('msg_created', 'Page created.'),
				'success',
				'#A5DC86',
			], JSON_UNESCAPED_UNICODE));
		}

		redirect(admin_url('cms'), 'refresh');
	}

	public function view($pageId = false)
	{
		if ($pageId === false || $pageId === '') {
			show_404();
		}
		$pid = (int) $pageId;
		$page = $this->fetchCmsById($pid);
		if ($page === null) {
			show_404();
		}

		$data = [
			'title' => $this->cmsLang('form_view_title', 'View CMS page'),
			'page' => 'cms',
			'subpage' => 'list',
			'pageTexts' => $this->cmsAdminTexts(),
			'data' => $page,
		];

		$this->load->view('admin/header', $data);
		$this->load->view('admin/cms_view', $data);
		$this->load->view('admin/footer');
	}

	public function edit($pageId = false)
	{
		if ($pageId === false || $pageId === '') {
			show_404();
		}
		$pid = (int) $pageId;
		$page = $this->fetchCmsById($pid);
		if ($page === null) {
			show_404();
		}

		$data = [
			'title' => $this->cmsLang('form_edit_title', 'Edit CMS page'),
			'page' => 'cms',
			'subpage' => 'list',
			'pageTexts' => $this->cmsAdminTexts(),
			'data' => $page,
		];

		$this->load->view('admin/header', $data);
		$this->load->view('admin/cms_edit', $data);
		$this->load->view('admin/footer');
	}

	public function update()
	{
		$pid = (int) $this->input->post('pageId');
		if ($pid < 1) {
			show_404();
		}

		if ($this->fetchCmsById($pid) === null) {
			show_404();
		}

		$row = $this->readCmsPostRow();
		if ($row === null) {
			$this->flashMessage(json_encode([
				$this->cmsLang('msg_validation_error', 'Please check the form and try again.'),
				'warning',
				'#DD6B55',
			], JSON_UNESCAPED_UNICODE));
			redirect(admin_url('cms/edit/' . $pid), 'refresh');

			return;
		}

		$row['modified'] = date('Y-m-d H:i:s');

		$response = $this->put('cm/' . $pid, $row);
		if ($this->hasError($response)) {
			$this->flashMessage('error');
		} else {
			$this->flashMessage(json_encode([
				$this->cmsLang('msg_updated', 'Page updated.'),
				'success',
				'#A5DC86',
			], JSON_UNESCAPED_UNICODE));
		}

		redirect(admin_url('cms'), 'refresh');
	}

	public function delete($id = false)
	{
		if ($id === false || $id === '') {
			redirect(admin_url('cms'), 'refresh');

			return;
		}
		$pid = (int) $id;
		if ($pid < 1) {
			redirect(admin_url('cms'), 'refresh');

			return;
		}

		if ($this->fetchCmsById($pid) === null) {
			$this->flashMessage(json_encode([
				$this->cmsLang('msg_not_found', 'Page not found.'),
				'warning',
				'#DD6B55',
			], JSON_UNESCAPED_UNICODE));
			redirect(admin_url('cms'), 'refresh');

			return;
		}

		$response = $this->apiDelete('cm/' . $pid);
		if ($this->hasError($response)) {
			$this->flashMessage('error');
		} else {
			$this->flashMessage(json_encode([
				$this->cmsLang('msg_deleted', 'Page deleted.'),
				'success',
				'#A5DC86',
			], JSON_UNESCAPED_UNICODE));
		}

		redirect(admin_url('cms'), 'refresh');
	}

	/**
	 * @return array<int, object>
	 */
	private function fetchCmsList(): array
	{
		$resp = $this->getDataFromApi('cm', [
			'page' => 1,
			'perPage' => 5000,
			'orderProperty' => 'pageId',
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

	private function fetchCmsById(int $pageId): ?object
	{
		if ($pageId < 1) {
			return null;
		}

		$resp = $this->getObjectFromApi('cm/' . $pageId);
		if ($this->hasError($resp)) {
			return null;
		}

		if (is_object($resp) && isset($resp->data) && is_object($resp->data)) {
			return $resp->data;
		}

		return is_object($resp) ? $resp : null;
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private function readCmsPostRow(): ?array
	{
		$pageTitle = trim(strip_tags((string) $this->input->post('pageTitle')));
		$pageText = trim(strip_tags((string) $this->input->post('pageText')));
		$linkRaw = trim((string) $this->input->post('link'));
		$content = $this->input->post('content', false);
		$content = is_string($content) ? trim($content) : '';

		$len = static function (string $s): int {
			return function_exists('mb_strlen') ? (int) mb_strlen($s, 'UTF-8') : strlen($s);
		};

		if ($pageTitle === '' || $len($pageTitle) > 100) {
			return null;
		}
		if ($pageText === '' || strlen($pageText) > 250) {
			return null;
		}
		if ($linkRaw !== '' && $len($linkRaw) > 255) {
			return null;
		}
		if ($content === '' || strlen($content) > 500000) {
			return null;
		}

		$link = $linkRaw === '' ? null : strip_tags($linkRaw);

		return [
			'pageTitle' => $pageTitle,
			'pageText' => $pageText,
			'link' => $link,
			'content' => $content,
		];
	}

	/**
	 * @return array<string, string>
	 */
	private function cmsAdminTexts(): array
	{
		if ($this->cmsAdminTextsCache !== null) {
			return $this->cmsAdminTextsCache;
		}
		if (function_exists('admin_cms_page_texts')) {
			$this->cmsAdminTextsCache = admin_cms_page_texts(null);
		} elseif (function_exists('admin_lang_cmp_admin_cms_texts')) {
			$this->cmsAdminTextsCache = admin_lang_cmp_admin_cms_texts();
		} else {
			$this->cmsAdminTextsCache = [];
		}

		return $this->cmsAdminTextsCache;
	}

	private function cmsLang(string $key, string $fallback = ''): string
	{
		$all = $this->cmsAdminTexts();
		if (isset($all[$key]) && $all[$key] !== '') {
			return $all[$key];
		}

		return $fallback;
	}

	/**
	 * @return array<string, string>
	 */
	private function cmsDeleteUiPayload(): array
	{
		return [
			'title' => $this->cmsLang('delete_confirm_title', ''),
			'text' => $this->cmsLang('delete_confirm_text', ''),
			'yes' => $this->cmsLang('delete_confirm_yes', ''),
			'no' => $this->cmsLang('delete_confirm_no', ''),
		];
	}
}

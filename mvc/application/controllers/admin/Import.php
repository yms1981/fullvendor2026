<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Import extends MY_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->checkLoggedInOrLoginWithRedirect();
	}

	public function lists()
	{
		$data = array(
			'title' => admin_lang_string('cmp-admin-import', 'list_page_title', 'Import history'),
			'page' => 'import',
			'subpage' => 'importlist',
			'importListLang' => admin_lang_merged_section('cmp-admin-import'),
		);

		$data['list'] = $this->fetchImportHistoryList();

		$this->load->view('admin/header', $data);
		$this->load->view('admin/import_history_list');
		$this->load->view('admin/footer');
	}

	/**
	 * @return array<int, object>
	 */
	private function fetchImportHistoryList(): array
	{
		$resp = $this->getDataFromApi('importhistory', [
			'page' => 1,
			'perPage' => 5000,
			'orderProperty' => 'import_id',
			'orderDirection' => 'desc',
			'relations' => ['company:company_id,name'],
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

}

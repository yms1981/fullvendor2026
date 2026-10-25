<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class Status extends My_Controller
{
	public $company_info = array();

	public function __construct()
	{
		parent::__construct();
		$this->checkLoggedInOrLoginWithRedirect();
		$this->company_info = $this->Helper->get_company_info();
	}

	/**
	 * @param mixed $response
	 * @return array<int, object>
	 */
	private function normalizeApiList($response): array
	{
		if ($this->hasError($response)) {
			return [];
		}

		$rows = [];
		if (is_object($response) && isset($response->data) && is_array($response->data)) {
			$rows = $response->data;
		} elseif (is_array($response) && isset($response['data']) && is_array($response['data'])) {
			$rows = $response['data'];
		} elseif (is_array($response)) {
			$rows = $response;
		}

		$out = [];
		foreach ($rows as $row) {
			$out[] = is_object($row) ? $row : (object) $row;
		}

		return $out;
	}

	/**
	 * @return array<int, object>
	 */
	private function fetchDeliveryStatusOrders(int $langId): array
	{
		$rows = $this->normalizeApiList($this->getDataFromApi('statusorder', [
			'tipo_d' => 'D',
			'language_id' => $langId,
			'all' => true,
		], false));

		$byCodStatus = [];
		foreach ($rows as $row) {
			if (! isset($row->cod_status)) {
				continue;
			}
			$key = (int) $row->cod_status;
			if (! array_key_exists($key, $byCodStatus)) {
				$byCodStatus[$key] = $row;
			}
		}
		ksort($byCodStatus, SORT_NUMERIC);

		return array_values($byCodStatus);
	}

	/**
	 * @return array<int, object>
	 */
	private function fetchCompanyStatusEmails(int $companyId): array
	{
		return $this->normalizeApiList($this->getDataFromApi('companystatusorder', [
			'company' => $companyId,
			'all' => true,
		], false));
	}

	/**
	 * @param array<int, object> $statusList
	 */
	private function bootstrapCompanyStatusEmails(int $companyId, array $statusList): void
	{
		foreach ($statusList as $status) {
			$codStatus = (int) ($status->cod_status ?? 0);
			if ($codStatus < 1) {
				continue;
			}

			$this->post('companystatusorder', [
				'company_id' => $companyId,
				'cod_status' => $codStatus,
			], false);
		}
	}

	private function normalizeStatusEmailForUpdate(string $email): ?string
	{
		$email = trim($email);

		return $email === '' ? null : $email;
	}

	public function index()
	{
		$langId = (int) ($this->company_info->language_id ?? 1);
		$data['langdef'] = $this->HelperLanguage->get_language_head();
		$info['lang_order'] = $this->HelperLanguage->lang_order();

		$compId = (int) ($this->company_info->company_id ?? 0);
		$info['status'] = $statusList = $this->fetchDeliveryStatusOrders($langId);

		$statusEmails = $this->fetchCompanyStatusEmails($compId);
		if ($statusEmails === [] && $statusList !== []) {
			$this->bootstrapCompanyStatusEmails($compId, $statusList);
			$statusEmails = $this->fetchCompanyStatusEmails($compId);
		}

		$info['status_emails'] = $statusEmails;

		$js = ['js' => [
			'general/index.js',
			'general/send_form.js',
		]];

		$this->load->view('common/head');
		$this->load->view('company/header-scripts/scripts-css');
		echo '<body>';
		$this->load->view('company/header', $data);
		$this->load->view('company/email_status/save_email_status', $info);
		$this->load->view('company/footer', $js);
	}

	public function save_configuracion()
	{
		$request = $this->input->post();
		$lang_order = $this->HelperLanguage->lang_order();

		if (! is_array($request) || ! isset($request['status']) || ! is_array($request['status'])) {
			return $this->Helper->response_json(['success' => false]);
		}

		foreach ($request['status'] as $s) {
			if (! is_array($s)) {
				continue;
			}
			$id = (int) ($s['id_status_email'] ?? 0);
			if ($id < 1) {
				continue;
			}

			$this->put('companystatusorder/'.$id, [
				'email' => $this->normalizeStatusEmailForUpdate((string) ($s['emails'] ?? '')),
			], false);
		}

		$this->Helper->show_sessionmsg($lang_order['status_email_actualizado'], 'success');

		return $this->Helper->response_json(['success' => true, 'load' => true]);
	}

}

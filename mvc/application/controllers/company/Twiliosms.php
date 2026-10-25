<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use Twilio\Rest\Client;

/**
 * Envío de SMS de catálogo (Twilio). Datos vía API Laravel.
 */
class Twiliosms extends Company_Controller
{

	public function __construct()
	{
		parent::__construct();
		require 'vendor/autoload.php';
	}

	public function catalog_send()
	{
		$data = [
			'title' => 'Send New Sms',
			'page' => 'catalog',
			'subpage' => 'smsadd',
		];
		$compId = (int) $this->getCompany();
		$data['companydata'] = $this->fetchCompanyRowForTwilio($compId);

		$this->load->view('company/header', $data);
		$this->load->view('company/sms_send');
		$this->load->view('company/footer');
	}

	public function sendrequestcatalogSms($requestId = false)
	{
		$data = [
			'title' => 'Send catalog',
			'page' => 'catalog',
			'subpage' => 'catalogsend',
		];

		$compId = (int) $this->getCompany();
		$requestId = (int) $requestId;
		$data['catalogs'] = $this->fetchActiveCatalogsForCompany($compId);
		$requestInfo = $this->fetchCatalogRequestById($requestId);
		$sentmailInfo = $this->fetchCatalogSentMailForRequest($compId, $requestId);
		$catalog_email_text = $sentmailInfo !== null ? ($sentmailInfo->catalog_email_text ?? '') : '';
		$customerId = is_object($requestInfo) ? (int) ($requestInfo->customer_id ?? 0) : 0;
		$customerinfo = $this->fetchCustomerById($customerId, $compId);
		$customerName = is_object($customerinfo) ? ($customerinfo->name ?? '') : '';
		$data['requestId'] = $requestId;

		$catalogId = is_object($sentmailInfo) ? ($sentmailInfo->catalog_id ?? 0) : 0;
		$hidePrice = is_object($sentmailInfo) ? ($sentmailInfo->hide_price ?? 0) : 0;

		$sendctrequestlink = base_url() . 'products/ordercatalogproduct?req=' . base64_encode((string) $requestId)
			. '&hprice=' . $hidePrice
			. '&catalog=' . base64_encode((string) $catalogId)
			. '&comp=' . $compId
			. '&customer=' . base64_encode((string) $customerId);
		$sms_sender = '19704258508';
		$sms_reciever = is_object($customerinfo) ? ($customerinfo->phone ?? '') : '';
		$sms_message = 'Hello ' . $customerName . ',' . $catalog_email_text . 'You will be able to see the catalog by clicking the link-   ';
		$sms_message .= $sendctrequestlink;
		$from = '+' . $sms_sender;
		$to = '+' . $sms_reciever;

		$client = new Client(TWILIO_SID, TWILIO_AUTHTOKEN);

		try {
			$client->messages->create(
				$to,
				[
					'from' => $from,
					'body' => $sms_message,
				]
			);

			$msg = '["SMS Send successfully.", "success", "#A5DC86"]';
		} catch (Twilio\Exceptions\RestException $e) {
			$messge = $to . ' The number  is unverified. Trial accounts cannot send messages to unverified numbers.';
			$msg = '["' . $messge . '", "error", "#DD6B55"]';
		}
		$this->flashMessage($msg);
		redirect(company_url('catalog/requestLists'), 'refresh');
	}

	public function send_catalogSMS()
	{
		$compId = (int) $this->getCompany();
		$catalogId = $this->input->post('catalogId');
		$customerId = $this->input->post('customerIdsms');

		if (is_array($customerId) && count($customerId) > 0) {
			$msg = '';
			foreach ($customerId as $csId) {
				$custmInfo = $this->fetchCustomerById((int) $csId, $compId);
				if (! is_object($custmInfo)) {
					continue;
				}
				$sms_reciever = $custmInfo->phone ?? '';
				$sendlink = base_url() . 'products/sendcatalogproduct?catalog=' . base64_encode((string) $catalogId)
					. '&comp=' . $compId
					. '&customer=' . base64_encode((string) ($custmInfo->customer_id ?? $csId));
				$sms_sender = '18595458354';
				$sms_message = 'Hello ' . ($custmInfo->name ?? '') . ',This is the catalog of our products.You will be able to see the catalog by clicking the link-   ';
				$sms_message .= $sendlink;
				$from = '+' . $sms_sender;
				$to = $sms_reciever;
				$client = new Client('ACafaf08a8c456f18141915a28800644ed', 'd27de67d06b0e8a4e29cfdde3d7497db');

				try {
					$client->messages->create(
						$to,
						[
							'from' => $from,
							'body' => $sms_message,
						]
					);

					$msg = '["SMS Send successfully.", "success", "#A5DC86"]';
				} catch (Twilio\Exceptions\RestException $e) {
					$messge = $to . ' The number  is unverified. Trial accounts cannot send messages to unverified numbers.';
					$msg = '["' . $messge . '", "error", "#DD6B55"]';
				}
			}
		} else {
			$msg = '["Select customer first.", "error", "#DD6B55"]';
		}

		$this->flashMessage($msg);
		redirect(company_url('catalog'), 'refresh');
	}

	private function fetchCompanyRowForTwilio(int $companyId): ?object
	{
		if ($companyId < 1) {
			return null;
		}
		$resp = $this->getObjectFromApi('company/' . $companyId);
		if ($this->hasError($resp)) {
			return null;
		}
		if (is_object($resp) && isset($resp->data) && is_object($resp->data)) {
			return $resp->data;
		}

		return is_object($resp) ? $resp : null;
	}

	/**
	 * @return list<object>
	 */
	private function fetchActiveCatalogsForCompany(int $companyId): array
	{
		if ($companyId < 1) {
			return [];
		}
		$resp = $this->getDataFromApi('catalog', [
			'company' => $companyId,
			'status' => 1,
			'all' => true,
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

	private function fetchCatalogRequestById(int $requestId): ?object
	{
		if ($requestId < 1) {
			return null;
		}
		$resp = $this->getObjectFromApi('catalogrequest/' . $requestId);
		if ($this->hasError($resp)) {
			return null;
		}
		if (is_object($resp) && isset($resp->data) && is_object($resp->data)) {
			return $resp->data;
		}

		return is_object($resp) ? $resp : null;
	}

	private function fetchCatalogSentMailForRequest(int $companyId, int $requestId): ?object
	{
		if ($companyId < 1 || $requestId < 1) {
			return null;
		}
		$resp = $this->getDataFromApi('catalogsentmail', [
			'company' => $companyId,
			'all' => true,
		], true);
		if (! is_array($resp) || $this->hasError($resp)) {
			return null;
		}
		$rows = $this->extractApiListRows($resp);
		if (! is_array($rows)) {
			return null;
		}
		foreach ($rows as $row) {
			$o = is_array($row) ? (object) $row : $row;
			if ((int) ($o->catlog_reqest_id ?? 0) === $requestId) {
				return $o;
			}
		}

		return null;
	}

	private function fetchCustomerById(int $customerId, int $companyId): ?object
	{
		if ($customerId < 1 || $companyId < 1) {
			return null;
		}
		$resp = $this->getObjectFromApi('customer/' . $customerId);
		if ($this->hasError($resp)) {
			return null;
		}
		$data = is_object($resp) && isset($resp->data) ? $resp->data : $resp;
		if (! is_object($data) || (int) ($data->company_id ?? 0) !== $companyId) {
			return null;
		}

		return $data;
	}
}

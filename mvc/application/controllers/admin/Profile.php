<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Profile extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->checkLoggedInOrLoginWithRedirect();
		$this->load->helper('admin_shell');
	}


	public function index()
	{
		$pt = $this->profileTexts();
		$data = [
			'title' => $pt['page_title_profile'] ?? 'My profile',
			'pageTexts' => $pt,
		];
		$data['userId'] = $this->getUserId();
		$data['data'] = $this->fetchAdminProfile();

		$this->load->view('admin/header', $data);
		$this->load->view('admin/profile', $data);
		$this->load->view('admin/footer');
	}


	public function save()
	{
		$pt = $this->profileTexts();
		if ($this->input->post('name')) {

			$response = $this->put('admin/account', [
				'name' => $this->testInput($this->input->post('name')),
				'email' => $this->testInput($this->input->post('email')),
			], true);

			if ($this->hasError($response) || ! (is_array($response) && ! empty($response['success']))) {
				$text = (string) ($pt['profile_update_error'] ?? 'Profile could not be updated.');
				$msg = json_encode([$text, 'error', '#DD6B55'], JSON_UNESCAPED_UNICODE);
			} else {
				$text = (string) ($pt['profile_updated_success'] ?? 'Profile updated successfully.');
				$msg = json_encode([$text, 'success', '#A5DC86'], JSON_UNESCAPED_UNICODE);
			}
			$this->flashMessage($msg);
		}
		redirect(admin_url('profile'),'refresh');
	}


	public function check_store_name()
	{
		if ($this->input->post('storeName')) {
			echo '';
		}
	}

	/**
	 * @return array<string, string>
	 */
	private function profileTexts(): array
	{
		$merged = admin_lang_merged_section('cmp-admin-profile');
		$out = [];
		foreach ($merged as $key => $value) {
			if (is_string($value) && trim($value) !== '') {
				$out[$key] = $value;
			}
		}

		return $out;
	}

	/**
	 * @return object
	 */
	private function fetchAdminProfile(): object
	{
		$resp = $this->getDataFromApi('admin/account', [], true);
		if (is_array($resp) && ! $this->hasError($resp) && isset($resp['data']) && is_array($resp['data'])) {
			return (object) $resp['data'];
		}

		return (object) [
			'userId' => (int) $this->getUserId(),
			'name' => '',
			'email' => '',
		];
	}

}

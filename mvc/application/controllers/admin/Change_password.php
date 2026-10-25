<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Change_password extends MY_Controller {

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
			'title' => $pt['page_title_change_password'] ?? 'Change password',
			'pageTexts' => $pt,
		];
		$data['userId'] = $this->getUserId();

		$this->load->view('admin/header', $data);
		$this->load->view('admin/change_password', $data);
		$this->load->view('admin/footer');
	}


	public function update()
	{
		$pt = $this->profileTexts();
		if ($this->input->post('c_password')) {

			$this->form_validation->set_rules('c_password', (string) ($pt['label_current_password'] ?? 'Current password'), 'trim|required');
			$this->form_validation->set_rules('n_password_confirmation', (string) ($pt['label_new_password'] ?? 'New password'), 'trim|required');
			$this->form_validation->set_rules('n_password', (string) ($pt['label_repeat_password'] ?? 'Repeat password'), 'trim|required|matches[n_password_confirmation]');
			$msg = '';

			if ($this->form_validation->run() == false) {
				if (form_error('c_password')) {
					$msg .= strip_tags(form_error('c_password'));
				}
				if (form_error('n_password_confirmation')) {
					$msg .= strip_tags(form_error('n_password_confirmation'));
				}
				if (form_error('n_password')) {
					$msg .= strip_tags(form_error('n_password'));
				}
			} else {
				$c_password = (string) $this->input->post('c_password');
				$n_password = (string) $this->input->post('n_password');

				$changeResponse = $this->post('admin/password/change', [
					'current_password' => $c_password,
					'new_password' => $n_password,
				], true);

				if ($this->hasError($changeResponse) || ! (is_array($changeResponse) && ! empty($changeResponse['success']))) {
					$apiMessage = '';
					if (is_array($changeResponse) && isset($changeResponse['message'])) {
						$apiMessage = is_array($changeResponse['message'])
							? (string) ($changeResponse['message']['message'] ?? '')
							: (string) $changeResponse['message'];
					}
					if ($apiMessage === '' || stripos($apiMessage, 'incorrect') !== false) {
						$text = (string) ($pt['password_wrong_current'] ?? 'You have entered wrong password.');
						$msg = json_encode([$text, 'warning', '#F29F06'], JSON_UNESCAPED_UNICODE);
					} else {
						$msg = json_encode([$apiMessage, 'warning', '#F29F06'], JSON_UNESCAPED_UNICODE);
					}
				} else {
					$text = (string) ($pt['password_changed_success'] ?? 'Password changed successfully.');
					$msg = json_encode([$text, 'success', '#A5DC86'], JSON_UNESCAPED_UNICODE);
				}
			}
			$this->flashMessage($msg);
		}
		redirect(admin_url('change_password'),'refresh');
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

}

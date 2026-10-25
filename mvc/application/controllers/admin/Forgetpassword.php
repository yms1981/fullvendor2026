<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Forgetpassword extends MY_Controller
{

	public function __construct()
	{
		parent::__construct();
		if (function_exists('admin_sync_session_language_from_request')) {
			admin_sync_session_language_from_request();
		}
	}

	public function index()
	{
		$setting = $this->getPlatformSettings();
		$fpLang = $this->forgetPasswordLang();
		$data = [
			'page' => 'forgetpass',
			'settings' => $setting,
			'forgetPasswordLang' => $fpLang,
		];

		if ($this->input->post('username')) {
			$this->form_validation->set_rules(
				'username',
				$this->fpString('label_email', 'Email'),
				'trim|required'
			);
			$username = $this->input->post('username');
			if ($this->form_validation->run() == false) {
				$msg = '';
				if (form_error('username')) {
					$msg .= strip_tags(form_error('username'));
				}
				$data['msg'] = $this->buildAlert('danger', $msg);
			} else {
				$emailStatus = $this->post('admin/password/email-status', ['email' => $username]);
				$emailExists = is_array($emailStatus) && ! empty($emailStatus['exists']);
				if (! $emailExists) {
					$data['msg'] = $this->buildAlert('danger', $this->fpString('invalid_email', 'Invalid email.'));
				} else {
					$user = (object) [
						'email' => $emailStatus['email'] ?? $username,
						'name' => $emailStatus['name'] ?? '',
					];

					$url = admin_url() . 'forgetpassword/resetpassword/' . base64_encode($user->email) . '/';
					$to = @$user->email;
					$subject = $this->fpString('email_reset_subject', 'Reset your password | Full Vendor');
					$body = $this->buildResetRequestEmailBody($user, $url, $setting);

					if (FullvendorEmailHelper::sendMailFromNoReply($to, $subject, $body)) {
						$data['msg'] = $this->buildAlert(
							'success',
							$this->fpString('email_sent_check', 'Please check your email to reset your password.')
						);
					} else {
						$data['msg'] = $this->buildAlert(
							'danger',
							$this->fpString('unknown_error', 'An unknown error occurred.')
						);
					}
				}
			}
		}

		$this->load->view('admin/forgetpass', $data);
	}

	public function resetpassword($useremail = false)
	{
		if ($useremail != '') {
			$this->setUserData('sessremail', $useremail);
		}
		$data = [
			'settings' => $this->getPlatformSettings(),
			'forgetPasswordLang' => $this->forgetPasswordLang(),
		];
		$this->load->view('admin/reset_password', $data);
	}

	public function updatepassword()
	{
		$sseemail = $this->getUserData('sessremail');
		if ($sseemail == '') {
			$this->flashMessage($this->fpString('session_expired', 'Your password reset session has expired.'));
			$this->close_method();

			return;
		}

		$data = [
			'settings' => $this->getPlatformSettings(),
			'forgetPasswordLang' => $this->forgetPasswordLang(),
		];
		$decodemail = base64_decode($sseemail);
		$user = (object) ['email' => $decodemail, 'name' => ''];

		if (! $this->input->post('c_password')) {
			$this->load->view('admin/reset_password', $data);

			return;
		}

		$c_password = $this->input->post('c_password');
		$n_password = $this->input->post('n_password');

		if ($n_password != $c_password) {
			$data['msg'] = $this->buildAlert(
				'danger',
				$this->fpString('password_mismatch', 'The new password and confirmation do not match.')
			);
			$this->load->view('admin/reset_password', $data);

			return;
		}

		$resetResponse = $this->post('admin/password/reset', [
			'email' => $decodemail,
			'password' => $n_password,
		]);

		if ($this->hasError($resetResponse) || ! (is_array($resetResponse) && ! empty($resetResponse['success']))) {
			$data['msg'] = $this->buildAlert(
				'danger',
				$this->fpString('reset_error', 'An error occurred. Please try again.')
			);
			$this->load->view('admin/reset_password', $data);

			return;
		}

		$user->name = $resetResponse['name'] ?? '';
		$to = $resetResponse['email'] ?? $decodemail;
		$subject = $this->fpString('email_success_subject', 'Password reset successfully | Full Vendor');
		$body = $this->buildResetSuccessEmailBody($user, admin_url(), $data['settings']);

		if (FullvendorEmailHelper::sendMailFromNoReply($to, $subject, $body)) {
			$this->removeUserData('sessremail');
		}

		$this->flashMessage($this->fpString('reset_success', 'Password reset successfully.'));
		redirect(admin_url('login'));
	}

	public function close_method()
	{
		echo "<script type='text/javascript'>";
		echo 'window.close();';
		echo '</script>';
	}

	/**
	 * @return array<string, string>
	 */
	private function forgetPasswordLang(): array
	{
		if (function_exists('admin_lang_merged_section')) {
			return admin_lang_merged_section('cmp-admin-forget-password');
		}

		return [];
	}

	private function fpString(string $key, string $fallback = ''): string
	{
		if (function_exists('admin_lang_string')) {
			return admin_lang_string('cmp-admin-forget-password', $key, $fallback);
		}
		$lang = $this->forgetPasswordLang();
		$v = trim((string) ($lang[$key] ?? ''));

		return $v !== '' ? $v : $fallback;
	}

	private function buildAlert(string $type, string $message): string
	{
		$type = $type === 'success' ? 'success' : 'danger';
		$safe = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

		return '<div class="alert alert-'.$type.' alert-dismissable">
			<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
			<strong>
			<i class="fa fa-check-circle-o"></i>
			</strong>
			'.$safe.'
			</div>';
	}

	private function buildResetRequestEmailBody(object $user, string $url, object $setting): string
	{
		$greetingTpl = $this->fpString('email_reset_greeting', 'Dear %s');
		$greeting = htmlspecialchars(sprintf($greetingTpl, (string) ($user->name ?? '')), ENT_QUOTES, 'UTF-8');
		$intro = htmlspecialchars($this->fpString('email_reset_intro', 'That\'s okay, it happens! Click the button below to reset your password.'), ENT_QUOTES, 'UTF-8');
		$button = htmlspecialchars($this->fpString('email_reset_button', 'Reset your password'), ENT_QUOTES, 'UTF-8');
		$signoff = htmlspecialchars($this->fpString('email_signoff_sincerely', 'Sincerely,'), ENT_QUOTES, 'UTF-8');
		$brand = htmlspecialchars($this->fpString('email_signoff_brand', 'Full Vendor'), ENT_QUOTES, 'UTF-8');
		$auto = htmlspecialchars($this->fpString('email_auto_response', 'This is an automated message. Please do not reply.'), ENT_QUOTES, 'UTF-8');
		$logo = base_url('uploads/logos/' . @$setting->favicon);
		$safeUrl = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

		return "<!Doctype html>
		<html>
		<head>
		<meta charset='utf-8'>
		<meta name='viewport' content='width=device-width, initial-scale=1'>
		<link href='https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700&display=swap' rel='stylesheet'>
		<body>
		<div style='max-width:600px;margin:auto;border:1px solid #eee;box-shadow:0 0 10px rgba(0,0,0,.15);line-height:17px;font-size:13px;box-sizing:border-box;-webkit-print-color-adjust:exact;font-family:Poppins,sans-serif;'>
		<div style='padding:20px;box-sizing:border-box;text-align:center;background:#fff;'>
		<a href='#'><img src='".$logo."' style='max-width:17%;width:300px;'></a>
		</div>
		<div style='width:400px;margin:50px auto;background:#ffffffd1;text-align:center;'>
		<h1 style='font-size:30px;line-height:32px;color:#0b0b0b;margin:30px 0;'>".$greeting."</h1>
		<p style='font-size:15px;color:#262626;line-height:24px;margin:20px 0;'>".$intro."</p>
		<p><a href='".$safeUrl."' target='_blank' style='text-decoration:none;margin-top:10px;display:inline-block;min-width:100px;background:#1EA910;padding:10px;color:#fff;text-transform:uppercase;border-radius:10px;box-shadow:0px 2px 0px #1EA910;font-size:12px'>".$button."</a></p>
		</div>
		<div style='background:#000;text-align:left;box-sizing:border-box;width:100%;padding:20px 50px;color:#fff;'>
		<p style='margin:5px 0;font-size:12px;'>".$signoff."</p>
		<p style='margin:5px 0;font-size:12px;'>".$brand."</p>
		<p style='margin:5px 0;font-size:11px;'>".$auto."</p>
		</div>
		</body>
		</html>";
	}

	private function buildResetSuccessEmailBody(object $user, string $loginUrl, object $setting): string
	{
		$greetingTpl = $this->fpString('email_success_greeting', 'Dear %s');
		$greeting = htmlspecialchars(sprintf($greetingTpl, (string) ($user->name ?? '')), ENT_QUOTES, 'UTF-8');
		$bodyText = htmlspecialchars($this->fpString('email_success_body', 'Your password was reset successfully.'), ENT_QUOTES, 'UTF-8');
		$button = htmlspecialchars($this->fpString('email_success_button', 'Sign in'), ENT_QUOTES, 'UTF-8');
		$signoff = htmlspecialchars($this->fpString('email_signoff_sincerely', 'Sincerely,'), ENT_QUOTES, 'UTF-8');
		$brand = htmlspecialchars($this->fpString('email_signoff_brand', 'Full Vendor'), ENT_QUOTES, 'UTF-8');
		$auto = htmlspecialchars($this->fpString('email_auto_response', 'This is an automated message. Please do not reply.'), ENT_QUOTES, 'UTF-8');
		$logo = base_url('uploads/logos/' . @$setting->favicon);
		$safeLoginUrl = htmlspecialchars($loginUrl, ENT_QUOTES, 'UTF-8');

		return "<!Doctype html>
		<html>
		<head>
		<meta charset='utf-8'>
		<meta name='viewport' content='width=device-width, initial-scale=1'>
		<link href='https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700&display=swap' rel='stylesheet'>
		<body>
		<div style='max-width:600px;margin:auto;border:1px solid #eee;box-shadow:0 0 10px rgba(0,0,0,.15);line-height:17px;font-size:13px;box-sizing:border-box;-webkit-print-color-adjust:exact;font-family:Poppins,sans-serif;'>
		<div style='padding:20px;box-sizing:border-box;text-align:center;background:#fff;'>
		<a href='#'><img src='".$logo."' style='max-width:17%;width:300px;'></a>
		</div>
		<div style='width:400px;margin:50px auto;background:#ffffffd1;text-align:center;'>
		<h1 style='font-size:30px;line-height:32px;color:#0b0b0b;margin:30px 0;'>".$greeting."</h1>
		<p style='font-size:15px;color:#262626;line-height:24px;margin:20px 0;'>".$bodyText."</p>
		<p><a href='".$safeLoginUrl."' target='_blank' style='text-decoration:none;margin-top:10px;display:inline-block;min-width:100px;background:#1EA910;padding:10px;color:#fff;text-transform:uppercase;border-radius:10px;box-shadow:0px 2px 0px #1EA910;font-size:12px'>".$button."</a></p>
		</div>
		<div style='background:#000;text-align:left;box-sizing:border-box;width:100%;padding:20px 50px;color:#fff;'>
		<p style='margin:5px 0;font-size:12px;'>".$signoff."</p>
		<p style='margin:5px 0;font-size:12px;'>".$brand."</p>
		<p style='margin:5px 0;font-size:11px;'>".$auto."</p>
		</div>
		</body>
		</html>";
	}
}

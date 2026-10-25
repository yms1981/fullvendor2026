<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Forgetpassword extends MY_Controller
{

	public function __construct()
	{
		parent::__construct();
	}


	public function index()
	{
		$setting = $this->getPlatformSettings();
		$data = array('page' => 'forgetpass');

		if ($this->input->post('username'))
		{
			$this->form_validation->set_rules('username', 'Email', 'trim|required');
			$username = $this->input->post('username');
			if ($this->form_validation->run() == false)
			{
				$msg = '';

				if (form_error('username'))
				{
					$msg .= strip_tags(form_error('username'));
				}


				$data['msg'] = '<div class="alert alert-danger alert-dismissable">
				<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
				<strong>
				<i class="fa fa-check-circle-o"></i>
				</strong>
				'.$msg.'
				</div>';

			} else {

				$emailStatus = $this->post('auth/password/email-status', ['email' => $username]);
				$emailExists = is_array($emailStatus) && ! empty($emailStatus['exists']);
				if(!$emailExists){
					$data['msg'] = '<div class="alert alert-danger alert-dismissable">
					<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
					<strong>
					<i class="fa fa-check-circle-o"></i>
					</strong>
					Invalid Email.
					</div>';
				}else{
					$user = (object) [
						'email' => $emailStatus['email'] ?? $username,
						'name' => $emailStatus['name'] ?? '',
					];

					$url = base_url() . 'reset-password/' . base64_encode($user->email).'/';
					$to = @$user->email;
					$subject = "Reset your password | Full Vendor ";

					$body = "<!Doctype html>
					<html>
					<head>
					<meta charset='utf-8'>
					<meta name='viewport' content='width=device-width, initial-scale=1'>

					<link href='https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700&display=swap' rel='stylesheet'>

					<body>
					<div style='max-width:600px;
					margin:auto;
					border:1px solid #eee;
					box-shadow:0 0 10px rgba(0, 0, 0, .15);
					line-height:17px;
					font-size:13px;
					box-sizing:border-box; -webkit-print-color-adjust: exact;font-family: Poppins, sans-serif;'>
					<div style='padding:20px; box-sizing: border-box;text-align: center; background: #fff;'>
					<a href='#'><img src='".base_url('uploads/logos/'.@$setting->favicon)."' style='max-width: 17%;width: 300px;'></a>
					</div>
					<div style='width: 400px; margin:50px auto;background: #ffffffd1;text-align: center;'>
					<h1 style=' font-size: 30px; line-height: 32px; color: #0b0b0b; margin: 30px 0;'>Dear ".$user->name."</h1>
					<p style='font-size: 15px;color: #262626;line-height: 24px;margin: 20px 0;'>That's okay,It happens ! Click on the button below to reset your password</p>
					<p><a href=".$url." target='_blank' style='text-decoration: none;
					margin-top: 10px;
					display: inline-block;
					min-width: 100px;
					background: #dc3545;
					padding: 10px;
					color: #fff;
					text-transform: uppercase;
					border-radius: 10px;
					box-shadow: 0px 2px 0px #c82333;font-size: 12px'>Reset Your Password</a></p>
					</div>
					<div style='background: #000;
					text-align: left;
					box-sizing: border-box;
					width: 100%;
					padding: 20px 50px;
					color: #fff;'>
					<p style='margin: 5px 0;font-size: 12px;'>Sincerely,</p>
					<p style='margin: 5px 0;font-size: 12px;'>Full Vendor</p>
					<p style='margin: 5px 0;font-size: 11px;'>This is an automated response, please do not reply.</p>
					</div>
					</body>
					</html>";

				     if(FullvendorEmailHelper::sendMailFromNoReply($to,$subject,$body)){
				       $data['msg'] = '<div class="alert alert-success alert-dismissable">
					<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
					<strong>
					<i class="fa fa-check-circle-o"></i>
					</strong>
					Please check your email for reset password.
					</div>';
				        }
				        else{
				        	$data['msg'] = '<div class="alert alert-success alert-dismissable">
					<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
					<strong>
					<i class="fa fa-check-circle-o"></i>
					</strong>
					Unknown error.
					</div>';
				        }
				    }

				}
			}


			$data['settings'] = $this->getPlatformSettings();
			$data['forgetPasswordLang'] = $this->forgetPasswordLangTexts();
			$this->load->view('company/forgetpass', $data);
		}


		public function logout()
		{
			$this->removeUserData('compId');
			$this->removeUserData('company');
			$msg = '<div class="alert alert-success alert-dismissable">
			<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
			<strong>
			<i class="fa fa-check-circle-o"></i>
			</strong>
			You have successfully logout!
			</div>';
			$this->flashMessage($msg);
			redirect(base_url('login'),'refresh');
		}
		public function resetpassword($useremail=false)
		{
			//$data['useremail']=$useremail;
			if($useremail!=""){
				$this->setUserData('sessremail',$useremail);
			}
			$data['settings'] = $this->getPlatformSettings();
			$data['forgetPasswordLang'] = $this->forgetPasswordLangTexts();
			$this->load->view('company/reset_password',$data);
		}
		public function updatepassword(){
			$sseemail=$this->getUserData('sessremail');
			if($sseemail==""){
				$msg="Session Expired for password reset";
				$this->flashMessage($msg);
				$this->close_method();
			}else{
			$useremail=$sseemail;
			$data['settings'] = $this->getPlatformSettings();
			$decodemail=base64_decode($useremail);

			$user = (object) ['email' => $decodemail, 'name' => ''];

			if ($this->input->post('c_password'))
			{
				$c_password = $this->input->post('c_password');
				$n_password = $this->input->post('n_password');

				$msg='';
				if ($n_password!=$c_password)
				{
					$data['msg'] = '<div class="alert alert-danger alert-dismissable">
			<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
			<strong>
			<i class="fa fa-check-circle-o"></i>
			</strong>
			You new password does not match with confirm password!
			</div>';


				} else {

					$resetResponse = $this->post('auth/password/reset', [
						'email' => $decodemail,
						'password' => $n_password,
					]);

					if ($this->hasError($resetResponse) || ! (is_array($resetResponse) && ! empty($resetResponse['success'])))
					{
						$data['msg']= '<div class="alert alert-danger alert-dismissable">
					<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
					<strong>
					<i class="fa fa-check-circle-o"></i>
					</strong>
					Some error occured, Please try again!
					</div>';


					} else {
						$data['msg'] = '<div class="alert alert-success alert-dismissable">
					<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
					<strong>
					<i class="fa fa-check-circle-o"></i>
					</strong>
					Password reset successfully..
					</div>';
					$user->name = $resetResponse['name'] ?? '';
					$to=$resetResponse['email'] ?? $decodemail;
				    $subject = "Password reset Successfully | Full Vendor ";

					$loginurl= company_url();
					$body = "<!Doctype html>
					<html>
					<head>
					<meta charset='utf-8'>
					<meta name='viewport' content='width=device-width, initial-scale=1'>

					<link href='https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700&display=swap' rel='stylesheet'>

					<body>
					<div style='max-width:600px;
					margin:auto;
					border:1px solid #eee;
					box-shadow:0 0 10px rgba(0, 0, 0, .15);
					line-height:17px;
					font-size:13px;
					box-sizing:border-box; -webkit-print-color-adjust: exact;font-family: Poppins, sans-serif;'>
					<div style='padding:20px; box-sizing: border-box;text-align: center; background: #fff;'>
					<a href='#'><img src='".base_url('uploads/logos/'.@$setting->favicon)."' style='max-width: 17%;width: 300px;'></a>
					</div>
					<div style='width: 400px; margin:50px auto;background: #ffffffd1;text-align: center;'>
					<h1 style=' font-size: 30px; line-height: 32px; color: #0b0b0b; margin: 30px 0;'>Dear ".$user->name."</h1>
					<p style='font-size: 15px;color: #262626;line-height: 24px;margin: 20px 0;'>Password reset Successfully</p>
					<p><a href=".$loginurl." target='_blank' style='text-decoration: none;
					margin-top: 10px;
					display: inline-block;
					min-width: 100px;
					background: #dc3545;
					padding: 10px;
					color: #fff;
					text-transform: uppercase;
					border-radius: 10px;
					box-shadow: 0px 2px 0px #c82333;font-size: 12px'>Click for login</a></p>
					</div>
					<div style='background: #000;
					text-align: left;
					box-sizing: border-box;
					width: 100%;
					padding: 20px 50px;
					color: #fff;'>
					<p style='margin: 5px 0;font-size: 12px;'>Sincerely,</p>
					<p style='margin: 5px 0;font-size: 12px;'>Full Vendor</p>
					<p style='margin: 5px 0;font-size: 11px;'>This is an automated response, please do not reply.</p>
					</div>
					</body>
					</html>";
				       if(FullvendorEmailHelper::sendMailFromNoReply($to,$subject,$body)){
				        	$this->removeUserData('sessremail');
				        }

					}

				}

				$this->flashMessage($msg);
			$this->load->view('company/reset_password',$data);
			}
			}

		}
		function close_method(){
		    echo  "<script type='text/javascript'>";
		    echo "window.close();";
		    echo "</script>";
		}

	/**
	 * Textos de recuperación de contraseña (api/lang/*.json → cmp-admin-forget-password).
	 *
	 * @return array<string, string>
	 */
	protected function forgetPasswordLangTexts(): array
	{
		$langId = 2;
		$sessionLang = $this->getUserData('language_id');
		if ($sessionLang !== null && (int) $sessionLang >= 1 && (int) $sessionLang <= 7) {
			$langId = (int) $sessionLang;
		}
		$qs = strtolower((string) ($this->input->get('lang') ?? ''));
		$map = ['en' => 1, 'es' => 2, 'pt' => 3, 'it' => 4, 'ru' => 5, 'de' => 6, 'zh' => 7];
		if (isset($map[$qs])) {
			$langId = $map[$qs];
		}
		if (function_exists('fv_lang_json_flat_section')) {
			$section = fv_lang_json_flat_section($langId, 'cmp-admin-forget-password');
			if (is_array($section) && $section !== []) {
				return $section;
			}
		}

		return [];
	}

	}

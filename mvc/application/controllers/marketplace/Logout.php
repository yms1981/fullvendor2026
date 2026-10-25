<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Logout extends MY_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->load->model('mymodel');
		$this->load->model('langmodel');
		//$this->load->library('form_validation');
	}

	public function index()
	{
		$this->removeUserData('marketplace_customer_id');
		$this->removeUserData('marketplace_user');
		$language_id = $this->getUserData('lang_marketplace');
		$this->removeUserData('lang_marketplace');

		if ($language_id==1 ){
			$rawmsg = "You have successfully logout!";
		}else{
			$rawmsg = "¡Ha finalizado la sesión correctamente!";
		}

		$msg = '<div class="alert alert-success alert-dismissable">
		<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
		<strong>
		<i class="fa fa-check-circle-o"></i>
		</strong>
		'.$rawmsg.'
		</div>';

		/*
			setcookie("COOKIE_INDEFINED_SESSION", 0, time() - 31622400);
		 	setcookie("COOKIE_DATA_INDEFINED_SESSION[tu]", null);
			setcookie("COOKIE_DATA_INDEFINED_SESSION[gu]",null);

		*/

		$this->flashMessage($msg);
		redirect(marketplace_url('login'),'refresh');
	}

}

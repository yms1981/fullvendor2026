<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Register extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
	}

	public function index()
	{
		$data['title']="Register a Company";
		$this->load->view('header');
		$this->load->view('register', $data);
		$this->load->view('footer');
		
	}

}

/* End of file Company.php */
/* Location: ./application/controllers/Company.php */
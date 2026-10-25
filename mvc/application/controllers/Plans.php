<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Plans extends My_Controller 
{

	public function __construct()
	{
		parent::__construct();
	}

	public function index()
	{
		$data['title']="Our plans";
		$this->load->view('header', $data);
		$this->load->view('plans');
		$this->load->view('footer');	
	}

}

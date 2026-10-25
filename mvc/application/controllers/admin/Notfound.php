<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Notfound extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
	}


	public function index()
	{
		$data = [
			'notFoundLang' => function_exists('admin_lang_merged_section')
				? admin_lang_merged_section('cmp-admin-shell')
				: [],
		];
		$this->load->view('admin/404', $data);
	}

}

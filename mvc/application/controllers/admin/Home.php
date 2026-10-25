<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends MY_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->checkLoggedInOrLoginWithRedirect();
	}

	public function index()
	{
		$dashboardTitle = function_exists('admin_lang_string')
			? admin_lang_string('cmp-admin-shell', 'dashboard_page_title', 'Dashboard')
			: 'Dashboard';
		$data = array(
			'title' => $dashboardTitle,
			'page' => 'dashboard',
			'subpage' => '',
			'dashboardLang' => function_exists('admin_lang_merged_section') ? admin_lang_merged_section('cmp-admin-shell') : [],
		);
		$stats = $this->getObjectFromApi('dashboard/admin-stats');
		$stats = is_object($stats) && ! isset($stats->error) ? $stats : (object) [];

		$data['totsalerevenue'] = (int) ($stats->total_revenue ?? 0);
		$data['totalsalesno'] = (int) ($stats->total_sales_qty ?? 0);
		$data['avgvaluepermonth'] = (int) ($stats->avg_value_per_month ?? 0);
		$data['salesByMonth'] = isset($stats->sales_by_month) ? $stats->sales_by_month : null;

		$this->load->view('admin/header', $data);
		$this->load->view('admin/dashboard', $data);
		$this->load->view('admin/footer');

}

}

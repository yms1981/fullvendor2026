<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Language extends MY_Controller {

    public ?int $company;
	public ?int $langId;

    public function __construct()
	{
		parent::__construct();
		$this->checkLoggedInOrLoginWithRedirect();
		$this->load->model('langmodel');
        $this->company = $this->getCompany();;
		$this->langId = $this->getUserData('language_id');
	}

	public function index()
	{
        $modul_languaje = $this->getDictionaryFromApi();
        echo json_encode($modul_languaje);
    }
}

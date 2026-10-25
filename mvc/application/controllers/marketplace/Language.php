<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @property Commonmodel $commonmodel
 */
class Language extends MY_Controller {

    public ?int $company;
	public ?int $langId;

    public function __construct()
	{
		parent::__construct();
		$this->checkLoggedInOrLoginWithRedirect();
        $this->company = $this->getUserData('marketplace_user');
		$this->langId = $this->getUserData('lang_marketplace');
        $this->load->model('commonmodel');

	}

	public function index()
	{
        $lang = $this->langId==1?"en":"es";
		return $this->Helper->response_json($this->getDictionaryFromApi($lang));
    }
}

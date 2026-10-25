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
        $this->load->model('commonmodel');
	}

	public function index()
	{
        $request = $this->input->get();

        if (!isset($request["token"])){
            throw new Exception("Error en Token");
        }

        $token = $request["token"];
        $car_detail = $this->mymodel->get_by('catalog_cart',true,['token_kor'=>$token]);

        $info_company = $this->mymodel->get('companies', true, 'company_id', $car_detail->company_id);
        $lang = $info_company->language_id==1?"eng":"spa";
        return $this->Helper->response_json($this->getDictionaryFromApi($lang));
    }
}

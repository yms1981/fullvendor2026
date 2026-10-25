<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class ProfileModel extends CI_Model 
{   
    public $company_info = array();
	
    function __construct() 
	{ 
        parent::__construct();
        $this->company_info = $this->Helper->get_company_info();
    }

    public function get_default($id_company = null){
        
        $id_company = $id_company?$id_company:$this->company->company_id;
        return $this->mymodel->get_by("seller_profiles",true,
        [
            "company_id"=>$id_company,
            "default"=>1
        ]);
    }

    public function exist_default($id_company){        
        return $this->get_default($id_company);
    }

}
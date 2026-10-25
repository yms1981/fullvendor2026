<?php 
if (!defined('BASEPATH')) exit('No direct script access allowed');

class ProfileSellerModel extends CI_Model 
{
    public $company = array();
    public $lang_seller = array();
    
    function __construct() 
	{
        $this->company = $this->Helper->get_company_info();
    }

    public function delete($profile_id) {

        $this->mymodel->update(['deleted_at' => date("Y-m-d H:i:s")], 'seller_profiles', array(
            'company_id' => $this->company->company_id,
			'profile_id' => $profile_id,
		));
    }

    public function get_user_profiles($id = null){
        
        if ($id) return $this->mymodel->get_by('seller_profiles', true, [
            'company_id' => $this->company->company_id,
            'profile_id' => $id]);
        
        return $this->mymodel->get_by('seller_profiles', false, 
            ['company_id' => $this->company->company_id, 
            'deleted_at' => null],
            $order_by = 'profile_id', $limit = NULL,$type_order = "DESC"
        
        );
    }
}
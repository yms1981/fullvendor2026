<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class InventoryModel extends CI_Model 
{
    public $company = array();
        
    function __construct()
	{ 
        parent::__construct();
        //$this->company = $this->Helper->get_company_info();
    }


    public function validar_stock($pro_id){ 
        
        $product = $this->mymodel->get_by('products', true, ['product_id' => $pro_id]);
                
    }


}   
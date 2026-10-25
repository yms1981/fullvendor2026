<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class ImageModel extends CI_Model 
{
    function __construct() 
	{
    }

    public function product_imagesDefault($company_id){
		
		$query = "SELECT pi.img_id, pi.product_id FROM product_images pi INNER JOIN products p ON p.pro_id = pi.product_id WHERE p.company_id = $company_id GROUP BY pi.product_id";		        
        return $this->db->query($query)->result();
    }

}	

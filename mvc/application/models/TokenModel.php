<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class TokenModel extends CI_Model {

    public $company = array();
    function __construct()
	{
		parent::__construct();
        $this->company = $this->Helper->get_company_info();

    }

    public function generatePassword($length)
    {
        $key = "";
        $pattern = "1234567890abcdefghijklmnopqrstuvwxyz";

        $max = strlen($pattern)-1;
        for($i = 0; $i < $length; $i++){
            $key .= substr($pattern, mt_rand(0,$max), 1);
        }

        return $key;
    }

    public function get_codhash256($string,$company_id = null){

        $company_id = $company_id ? $company_id : $this->company->company_id;
        return hash('sha256', "CoMpAnY". $company_id . $string);
    }

    public function get_tokenCart($company_id = null){
        $length = 16; // Longitud del token en bytes
        $secure_token = bin2hex(openssl_random_pseudo_bytes($length));
        return $secure_token;
    }

}

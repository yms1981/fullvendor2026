<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class SessionCart extends CI_Model 
{
    function __construct() 
    {
        parent::__construct();

    }

    public function get_session_onecart_or_all($token = null){
        return $token ?  $this->mymodel->get_by('session_cart', true , ['session_token'=>$token]) : [];
    }

    public function get_last_session ($where_filter = []) {
        return $this->mymodel->get_by('session_cart', true , $where_filter,
                                       $order_by = 'id', 
                                       $limit = 1,
                                       $type_order = "DESC");
    }

    public function save_session($session_values = []) {        
        $this->mymodel->save('session_cart', $session_values);
    }


    public function update_session($data_values = [], $where_filter = []) {

        $this->mymodel->update($data_values, 'session_cart',$where_filter);
        
    }



    public function generate_tokensession(){
                
        return $this->Helper->get_token();
    }

}
<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class HistoryStatusOrderModel extends CI_Model 
{
    public $company = array();
        
    function __construct() 
	{
        
    }

    public function add_history($request){
        
        $request = $this->Helper->cast($request);
        
        $this->mymodel->save('order_status_history', [
            "order_id" => $request->order_id,
            "company_id" => $request->company_id,
            "user_id" => $request->user_id,
            "cod_status" => $request->status_id,
        ]);
    }


    public function get_history_order($company_id, $order_id, $langId){
        
        switch ($langId) {
			case 1:
			$name = 'name_status_english';	
			break;
			case 2:
			$name = 'name_status_spanish';
			break;
			default:
			$name = 'name_status_english';
			break;
		}

        $query = "SELECT h.*,s.$name as name_status,s.color,created_at,c.email as user,c.short_name as user_name FROM order_status_history AS h
                  INNER JOIN status_orders s ON h.cod_status = s.cod_status
                  INNER JOIN companies AS c ON c.company_id = h.user_id
                  where h.company_id = $company_id and h.order_id = $order_id
                  ORDER BY h.history_id DESC";

        return $this->db->query($query)->result();

    }

 }
    
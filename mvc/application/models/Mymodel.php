<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Mymodel extends MY_Model{

	public function insert($table, $data){
		if($this->db->insert($table, $data)){
			return true;
		} else {
			return false;
		}
	}

	public function getCategories($languageId=1){
     	if (!is_null($languageId)) {
     		$where = array(
     			'category_status' => 1,
     			'language_id' => $languageId
     		);
     		$list = $this->get_by('categories', false, $where);

     	} else {
     		$where = array(
     			'category_status' => 1,
     			'language_id' => 1
     		);

     		$list = $this->get('categories', false, $where);
     	}
     	return $list;
    }

	public function getApprovalStatus($userId){
		$user = $this->select('approval', 'users', true, ['userId' => $userId]);
		return $user->approval;
	}

	public function getSettings(){
		return $this->get('settings', true, 'settingId', '1');
	}

	public function add_details($tbl,$data){
		$this->db->insert($tbl,$data);
		$lastid= $this->db->insert_id();
		return $lastid;
	}

  	public function getorder_by($table, $single = False, $where = NULL, $order_by = NULL, $limit = NULL){
		if (!$where == NULL) {
			$this->db->where($where);
    	}

		$this->db->from($table);

		if ($single == FALSE) {
			$method = 'result';
		} else {
			$method = 'row';
		}

		if (!$limit == NULL) {
			$this->db->limit($limit);
		}

		if (!$order_by == NULL) {
			$this->db->order_by($order_by, 'ASC');
		}

		$query = $this->db->get();

		$result = $query->$method();

		return $result;
	}

	public function get_cond($tablename,$cond){
		$this->db->select('*');
		$this->db->from($tablename);
		$this->db->where($cond);
		$query = $this->db->get();
		$res = $query->row();
		return $res;
    }

	public function getCatgoryTitle($cid){
		$info = $this->select('categoryName', 'category', true, ['categoryId' => $cid]);
		return $info->categoryName;
	}

	public function getSubCatgoryTitle($cid){
		$info = $this->select('subcategoryName', 'subcategory', true, ['subcategoryId' => $cid]);
		return $info->subcategoryName;
	}

	public function getTotalPayment($bookingId){
		$sql = "SELECT SUM(amount) AS total FROM booking_order_payment WHERE bookingId = '$bookingId' AND status=1";
	 	$total=$this->fetch($sql, true)->total;
	 	if(empty($total)){
	 		$total = "0.00";
	 	}
		return $total;
	}

    public function get_currency_symbol($cc = 'USD'){
        $cc       = strtoupper($cc);
        $currency = array(
            "USD" => "$", //U.S. Dollar
            "AUD" => "A$", //Australian Dollar
            "BRL" => "R$", //Brazilian Real
            "CAD" => "C$", //Canadian Dollar
            "XCD" => "X$", //Caribbean island currency Dollar
            "CZK" => "Kč", //Czech Koruna
            "DKK" => "kr", //Danish Krone
            "EUR" => "€", //Euro
            "HKD" => "&#36", //Hong Kong Dollar
            "HUF" => "Ft", //Hungarian Forint
            "ILS" => "₪", //Israeli New Sheqel
            "INR" => "₹", //Indian Rupee
            "JPY" => "¥", //Japanese Yen
            "MYR" => "RM", //Malaysian Ringgit
            "MXN" => "&#36", //Mexican Peso
            "NOK" => "kr", //Norwegian Krone
            "NZD" => "&#36", //New Zealand Dollar
            "PHP" => "₱", //Philippine Peso
            "PLN" => "zł", //Polish Zloty
            "GBP" => "£", //Pound Sterling
            "SEK" => "kr", //Swedish Krona
            "CHF" => "Fr", //Swiss Franc
            "TWD" => "$", //Taiwan New Dollar
            "THB" => "฿", //Thai Baht
            "TRY" => "₺", //Turkish Lira
        );

        if (array_key_exists($cc, $currency)){
            return $currency[$cc];
        }
    }

    public function my_encrypt($data){
    	return $this->my_simple_crypt($data, 'e');
    }

    public function my_decrypt($data){
    	return $this->my_simple_crypt($data, 'd');
    }

    public function my_simple_crypt( $string, $action='e'){
    	$secret_key = HASH_KEY;
    	$secret_iv = HASH_KEY;
    	$output = false;
    	$encrypt_method = "AES-256-CBC";
    	$key = hash('sha256', $secret_key);
    	$iv = substr(hash('sha256', $secret_iv), 0, 16 );

    	if ($action =='e'){
    		$output = urlencode(base64_encode(openssl_encrypt($string, $encrypt_method, $key, 0, $iv)));
    	} else if ($action =='d'){
    		$output = urldecode(openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv));
    	}
    	return $output;
    }

    public function myUrlEncode($string){
    	$entities = array('%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D', '%2B', '%24', '%2C', '%2F', '%3F', '%25', '%23', '%5B', '%5D');
    	$replacements = array('!', '*', "'", "(", ")", ";", ":", "@", "&", "=", "+", "$", ",", "/", "?", "%", "#", "[", "]");
    	return str_replace($entities, $replacements, urlencode($string));
    }


    public function randString($length, $charset='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz123456789'){
    	$str = '';
    	$count = strlen($charset);
    	while ($length--){
    		$str .= $charset[mt_rand(0, $count-1)];
    	}

    	return $str;
    }

	function curl_get_file_contents($URL){
		$c = curl_init();
		curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($c, CURLOPT_URL, $URL);
		$contents = curl_exec($c);
		curl_close($c);
		if ($contents)
			return $contents;
		else
			return FALSE;
	}

    /**
     * @param $company array company read with model get from companies
     * @param $default
     * @return mixed
     */
    function getPlusTaxesLabel($company, $defaultPlusTaxes="Plus taxes") {
        return ($company->show_plus_taxes_label)?(!empty($company->plus_taxes_label) ? $company->plus_taxes_label  : $defaultPlusTaxes):"";
    }

}

<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * @property Mymodel $mymodel
 *
 */
class SellerModel extends CI_Model
{
    public $company = array();
    public $lang_seller = array();

    function __construct()
	{
        parent::__construct();
        $this->load->model("CustomerModel");
        $this->company = $this->Helper->get_company_info();
        //$this->lang_seller = $this->company?$this->langmodel->getMdl('cmp-vendedor'):null;
        //echo json_encode($this->langmodel->getMdl('cmp-vendedor'));
    }


    public function get_sellers_wherein_id($ids){

        $sellers_list = explode(",", $ids);
        $ids_sellers = implode("','", $sellers_list);
        //  echo $ids;
        $query = "SELECT * FROM seller WHERE user_id IN('$ids_sellers') ORDER BY first_name DESC";
        // echo $query;
        $result = $this->db->query($query)->result();

        return $result;

    }


    public function getOnebyId_or_all($seller_id = null){

        $id_company = $this->company->company_id;

        return $seller_id
          ? $this->mymodel->get_by('sellers', true, ["company_id"=>$id_company,"user_id" => $seller_id])
          : $this->mymodel->select("user_id, CONCAT(first_name,' ',last_name) AS name_complete,profile_image,first_name,last_name,default,status,email,username,phone_number,created", "sellers", false, ["company_id" => $id_company], null, "user_id", "DESC");

    }

    public function operate_withallCustomers($sellers_toasign,$type_asign){

        //asignar vendedor solo a los customers que no tienen a ese vendedor
        $all_customers = $this->CustomerModel->get_allcustomers();
        foreach($all_customers as $customer){
            $sallesmand_found = explode(",", $customer->user_id);
            $sallesman_updated = $this->CustomerModel->return_seller_toassgined($sellers_toasign,$sallesmand_found,$type_asign);
			$this->CustomerModel->update_customersseller($sallesman_updated,$customer->customer_id);
        }
    }

    public function get_lang(){

        return $this->lang_seller = $this->company?$this->langmodel->getMdl('cmp-vendedor'):null;
    }

    public function update($request){

        $request = $this->Helper->cast($request);
        $seller = $this->mymodel->get_by("sellers",true,["company_id"=>$this->company->company_id,"user_id"=>$request->userId]);

        $default = isset($request->default)?$request->default:null;
        $this->desactivar_vendorDefault($request);
        if($request->all_customers!=$seller->all_customers)
            $this->active_allcustomer($request,$seller);

        $this->mymodel->update(
            [
                'username' => $this->Helper->removespaces($request->userName),
                'first_name' => $this->Helper->removespaces($request->firstName),
                'last_name' => $this->Helper->removespaces($request->lastName),
                'email' => $this->Helper->removespaces($request->email),
                'phone_number' => $this->Helper->removespaces($request->mobile),
                'cell_number' => $this->Helper->removespaces($request->cellNumber),
				'profile' => $request->profile,
				'password' =>  $request->password?$this->Helper->enc_password($request->password):$seller->password,
				'fax' =>$request->fax,
				'default'=> $default,
                'proforma' => isset($request->proforma)?$request->proforma:0,
				'all_customers' => isset($request->all_customers)?$request->all_customers:null,
                'profile_image' => isset($request->images)?$request->images:$seller->profile_image,
                'email_on_salesman_order' => @$request->email_on_salesman_order
            ],
            'sellers',
            ['company_id' => $this->company->company_id, "user_id" => $request->userId]);
    }

    public function get_seller($id){

        return $this->mymodel->get_by("sellers",true,
        [
         "company_id"=>$this->company->company_id,
         "user_id"=>$id
        ]);
    }

    public function create($request){

        $request = $this->Helper->cast($request);
        $this->desactivar_vendorDefault($request);

        $mydata = array(
            'company_id' => $this->company->company_id,
			'username' => $this->Helper->removespaces($request->userName),
			'first_name' => $this->Helper->removespaces($request->firstName),
			'last_name' => $this->Helper->removespaces($request->lastName),
            'email' => $this->Helper->removespaces($request->email),
			'phone_number' => $this->Helper->removespaces($request->mobile),
			'cell_number' => $this->Helper->removespaces($request->cellNumber),
			'password' => $this->Helper->enc_password($request->password),
			'profile_image' => isset($request->images)?$request->images:null,
			'fax' =>$this->Helper->removespaces($request->fax),
			'status' => 1,
			'default'=>isset($request->default)?$request->default:null,
            'proforma' => isset($request->proforma)?$request->proforma:0,
			'email_verification' => 1,
			'created' =>date("Y-m-d H:i:s"),
            'profile' => $request->profile,
			'all_customers' => isset($request->all_customers)?$request->all_customers:null,
            'email_on_salesman_order' => @$request->email_on_salesman_order,
		);

		$this->mymodel->insert("sellers", $mydata);

        $seller = $this->get_seller($this->db->insert_id());
        $this->active_allcustomer($request,$seller);

    }

    private function active_allcustomer($request,$seller){

        if (isset($request->all_customers) && $request->all_customers==1){
            $this->operate_withallCustomers($seller->user_id,true);

        }else{
            $this->operate_withallCustomers($seller->user_id,false);
        }
    }

    public function exist_sellerdefault($id){

        $seller = $this->get_seller($id);
        if ($seller_default = $this->get_default()) {
            if ($seller->user_id == $seller_default->user_id) {
                return true;
            }
        }
        return false;
    }

    public function get_default(){
        if ($this->db->field_exists('default', 'sellers')) {
            return $this->mymodel->get_by("sellers", true, [
                "company_id" => $this->company->company_id,
                "default" => 1
            ]);
        }

        return $this->mymodel->get_by("sellers", true, [
            "company_id" => $this->company->company_id
        ], 'user_id', 1, 'ASC');
    }

    public function desactivar_vendorDefault($request){

        $default = isset($request->default)?$request->default:null;
		if ($default == 1) {
            $this->mymodel->update(['default'=>null], 'sellers', ['company_id'=>$this->company->company_id,'default'=>1]);
		}
	}

    public function exist_default($id_company){
        if ($this->db->field_exists('default', 'sellers')) {
            return $this->mymodel->get_by("sellers", true, [
                "company_id" => $id_company,
                "default" => 1
            ]);
        }

        return $this->mymodel->get_by("sellers", true, [
            "company_id" => $id_company
        ], 'user_id', 1, 'ASC');
    }
}

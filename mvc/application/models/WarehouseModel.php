<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class WarehouseModel extends CI_Model
{
    public $company = array();

    function __construct()
	{
        parent::__construct();
        $this->company = $this->Helper->get_company_info();
    }

    public function getAll($id = null) {
        $id_comp = $this->company->company_id;
        if ($id) return $this->mymodel->get_by('warehouse_users', true,
            [
                'company_id' => $id_comp,
                'user_id' => $id
            ]);


        return $this->mymodel->get_by('warehouse_users', false,
            [
                'company_id' => $id_comp,
                'deleted_at' => null
            ],
            $order_by = 'user_id', $limit = NULL,$type_order = "DESC"

        );
    }

    public function create($request) {

        $request = $this->Helper->cast($request);
        $company = $this->Helper->get_company_info();

        $mydata = array(

            'unique_id' =>"UID".$this->Helper->generate_numbers(6),
            'company_id' => $company->company_id,
            'first_name' => @$request->first_name,
            'last_name' => @$request->last_name,
            'email' => @$request->email,
            'phone_number' => @$request->phone_number,
            'password' => $this->Helper->enc_password($request->password),
            'status' => 1,
            'email_verification' => 1,
            'created' =>date("Y-m-d H:i:s"),
            'profile_image' => isset($request->images)?$request->images:null,
        );

        $this->mymodel->insert("warehouse_users", $mydata);

    }

    public function actualizar($warehouseUser, $request){

        $request = $this->Helper->cast($request);
        $company = $this->Helper->get_company_info();
        $dataWarehouse =[
            'first_name' => @$request->first_name,
            'last_name' =>  @$request->last_name,
            'phone_number' => @$request->phone_number,
            'email' => @$request->email,
        ];
        if ($request->images) $dataWarehouse['profile_image'] = $request->images;
        $this->mymodel->update(
            $dataWarehouse,
            'warehouse_users', array('company_id'=>$company->company_id,'user_id' => $warehouseUser->user_id)
        );
    }

    public function delete($id){

		$this->mymodel->update(['deleted_at' => date("Y-m-d H:i:s")], 'warehouse_users', array(
			'user_id' => $id,
		));
    }

    public function disable($id){

        $company = $this->Helper->get_company_info();
		$this->mymodel->update(['status' => 0], 'warehouse_users', array(
			'company_id' => $company->company_id,
            'user_id' => $id
		));
    }

    public function enable($id) {

        $company = $this->Helper->get_company_info();
		$this->mymodel->update(['status' => 1], 'warehouse_users', array(
			'company_id' => $company->company_id,
            'user_id' => $id
		));
    }

}



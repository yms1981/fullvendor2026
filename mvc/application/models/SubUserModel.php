<?php 
if (!defined('BASEPATH')) exit('No direct script access allowed');

class SubUserModel extends CI_Model 
{
    public $company = array();
    public $lang_seller = array();
    
    function __construct() 
	{
        $this->company = $this->Helper->get_company_info();
    }


    public function get_users($id = null){
        
        if ($id) return $this->mymodel->get_by('companies', true, ['company_id' => $id]);
        
        return $this->mymodel->get_by('companies', false, 
            ['createdby_comp_id' => $this->company->company_id, 'deleted_at' => null],
            $order_by = 'company_id', $limit = NULL,$type_order = "DESC"
        
        );
    }

    public function delete($id){
                
		$this->mymodel->update(['deleted_at' => date("Y-m-d H:i:s")], 'companies', array(
			'company_id' => $id,
		));
    }

    public function actualizar($user_id, $request){

        $request = $this->Helper->cast($request);
        $company = $this->Helper->get_company_info();
        $user = $this->mymodel->get_by('companies', true, ["company_id"=> $user_id]);
        

        $this->mymodel->update(            
            [
                'email' => $request->email,
                'password' =>  $request->password?$this->Helper->enc_password($request->password):$user->password,
                'phone_number' => $request->phone_number,
                'cell_number' => $request->cell_number,
                'short_name' => $request->name_user,
                'rol_id' => $request->rol_id ? $request->rol_id : null,
                'status' => isset($request->status)?$request->status:1,
                'createdby_comp_id'=>$company->company_id,
                'image' => isset($request->images)?$request->images:$user->image,
                'created'=> date('Y-m-d H:i:s'),            
                'sellers_ids' => isset($request->seller) ? implode(",", $request->seller) : null
            
            ], 
            'companies', array('company_id' => $user_id)        
        );
    }


    public function create($request){

        $request = $this->Helper->cast($request);
        $company = $this->Helper->get_company_info();

        $mydata = array(            
            'email' => $request->email,
            'password' =>$this->Helper->enc_password($request->password),
            'phone_number' => $request->phone_number,
            'cell_number' => $request->cell_number,
            'short_name' => $request->name_user,
            'rol_id' => $request->rol_id ? $request->rol_id : null,
            'status' => isset($request->status)?$request->status:1,
            'createdby_comp_id'=>$company->company_id,
            'image' => isset($request->images)?$request->images:null,
            'created'=> date('Y-m-d H:i:s'),
            'sellers_ids' => isset($request->seller) ? implode(",", $request->seller) : null
        );

        $this->mymodel->insert("companies", $mydata);
    }

    public function disable($id){
        
        $company = $this->Helper->get_company_info();        
		$this->mymodel->update(['status' => 0], 'companies', array(
			'company_id' => $id
		));
    }

    public function enable($id){
        
        $company = $this->Helper->get_company_info();        
		$this->mymodel->update(['status' => 1], 'companies', array(
			'company_id' => $id
		));
    }
}


<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class CustomerModel extends MY_Model
{
    public $company_info = array();

    function __construct()
	{
        parent::__construct();
        $this->company_info = $this->Helper->get_company_info();
		$this->load->model("SellerModel");

    }

	public function get_seller_cliente($sellers_list_customer){

		if(count($sellers_list_customer) > 0){
			foreach($sellers_list_customer as $seller){
				if($seller_found = $this->SellerModel->get_seller($seller))
					return $seller_found;
			}
		}
		return $this->SellerModel->get_default();
	}

	public function all_activesBygroup($id){

		$id_company = $this->company_info->company_id;
		$query="SELECT business_name,name,customer_id as id FROM customers
				WHERE company_id = $id_company
				and customer_status=1
				and catalog_emails=1";

		if ($id !="" && is_numeric($id) && $id !="vacio" && $id !="todo"){
			$query .= " AND FIND_IN_SET('$id',group_id)";

		}else if($id == 'vacio'){
			$query .= " AND (group_id is NULL OR group_id = '')";
		}

		$query .= " ORDER BY business_name ASC";
		return $this->db->query($query)->result();
    }

	public function all_actives(){

        return $this->mymodel->select("business_name,name,customer_id as id",
                                      "customers",
                                      false,
                                      ["company_id"=>$this->company_info->company_id,
									   "customer_status"=>1],
                                      null,
                                      "business_name",
                                      "ASC"
                                    );
    }

    public function get_allcustomers(){
        return $this->mymodel->get_by("customers",false,["company_id"=>$this->company_info->company_id]);
    }

	public function get_total_customers(){

		$id = $this->company_info->company_id;
		$query = "SELECT COUNT(customer_id) AS qty_customers FROM customers WHERE company_id = $id";
		return $this->db->query($query)->row()->qty_customers;

	}

	public function get_all(){
		$id = $this->company_info->company_id;
		$query = "SELECT c.* FROM customers AS c WHERE company_id = $id ORDER BY c.business_name ASC";
		return $this->db->query($query)->result();
	}

	public function customers_enabled(){

		$id = $this->company_info->company_id;
		$query = "SELECT c.* FROM customers AS c
					INNER JOIN customer_groups AS g ON g.group_id = c.group_id
					WHERE c.company_id =$id
					AND c.customer_status = 1
					AND c.catalog_emails = 1";
		return $this->db->query($query)->result();
	}

	/**
     * Obtener cliente por ID (usado p. ej. por catálogo público).
     * No sobrescribe MY_Model::get() para evitar warning de compatibilidad.
     */
    public function getById($customerId)
    {
        return $this->getCustomer($customerId, true);
    }

	/**
     * Obtener cliente por ID usando la BD local (sin API).
     * Útil para el catálogo público que no tiene autenticación.
     */
    public function getByIdLocal($customerId)
    {
        $query = "SELECT c.*, g.name as group_name, g.percentage_on_price, g.percent_price_amount
                  FROM customers AS c
                  LEFT JOIN customer_groups AS g ON c.group_id = g.group_id
                  WHERE c.customer_id = ?";
        return $this->db->query($query, [$customerId])->row();
    }

	public function getCustomer($customerId, $withGroup = false)
    {
        $resourse = $withGroup ? "customer/{$customerId}?withGroup" : "customer/{$customerId}";
        return $this->getObjectFromApi($resourse);
	}

    public function  update_customersseller($sallesman_updated,$customer_id){
		$this->mymodel->update(array(
			'user_id' => $sallesman_updated),
			'customers',
			array(
			'company_id'=>$this->company,
			'customer_id'=>$customer_id));

	}

    public function return_seller_toassgined($sellers_toasigned,$sallesmand_found,$type_asign){

        // si es true agrega el seller
		$sellers_toasigned = explode(",", $sellers_toasigned);

		if ($type_asign) {
			foreach($sellers_toasigned as $new_seller){
				if (!in_array($new_seller,$sallesmand_found)) {
					array_push($sallesmand_found, $new_seller);
				}
			}
			$sallesmand_found = implode(',',$sallesmand_found);
        	// si false lo remueve
		}else{
			foreach($sellers_toasigned as $new_seller){
				if (in_array($new_seller,$sallesmand_found)) {

					$clave = array_search($new_seller, $sallesmand_found);
					unset($sallesmand_found[$clave]);
				}
			}
			$sallesmand_found = implode(',',$sallesmand_found);
		}

		if(!empty($sallesmand_found)){

			return $sallesmand_found[0] ==','
					   ?substr($sallesmand_found,1)
					   :$sallesmand_found;
		}

		return "";
	}

	public function tiene_catalogospendientes($customerId){
		$companyId = (int) $this->company_info->company_id;
		$customerId = (int) $customerId;
		$hasPending = $this->getObjectFromApi(
			"catalogcart/company/{$companyId}/customer/{$customerId}/hasPendings"
		);

		return $hasPending ? 1 : 0;
	}

	public function tiene_catalogosexpiradors($customerId){
		$companyId = (int) $this->company_info->company_id;
		$customerId = (int) $customerId;
		$count = $this->getObjectFromApi(
			"catalogcart/company/{$companyId}/customer/{$customerId}/expired-count"
		);

		return (int) $count;
	}

	public function delete_catalogosexpirados($customerId){


	}

	public function saveCustomer($request){

		$request = $this->Helper->cast($request);
		$seller = $this->SellerModel->get_default();
		$id_seller = $seller?$seller->user_id:null;
		$user_id = isset($request->user_id)?implode(",", $request->user_id):$id_seller;

		$this->mymodel->save('customers', [

			'company_id' => $this->company_info->company_id,
			'name' => $this->Helper->removespaces($request->name),
			'business_name' => $this->Helper->removespaces($request->business_name),
			'tax_id' => $request->tax_id,
			'discount' => $request->discount,
			'term_id' => $request->term_id,
			'group_id' => $request->group_id,
			'email' => $request->email,
			'phone' => $request->phone,
			'cell_phone' => $request->cell_phone,
			'user_id'=> $user_id,
			'notes'=>null,
			'commercial_address' =>$request->commercial_address,
			'commercial_delivery_address' =>$request->commercial_delivery_address,
			'commercial_country' =>$request->commercial_country,
			'commercial_state' =>$request->commercial_state,
			'commercial_city' =>$request->commercial_city,
			'commercial_zone' =>$request->commercial_zone,
			'commercial_zip_code' =>$request->commercial_zip_code,
			'dispatch_address' =>$request->dispatch_address,
			'dispatch_country' =>$request->dispatch_country,
			'dispatch_state' => $request->dispatch_state,
			'dispatch_city' => $request->dispatch_city,
			'dispatch_zone' =>$request->dispatch_zone,
			'dispatch_zip_code' => $request->dispatch_zip_code,
			'dispatch_shipping_notes'=>$request->dispatch_shipping_notes,
			'catalog_emails' => $request->catalog_emails,
			'password' =>  $request->password? $this->Helper->enc_password($request->password) : $request->password,
			'customer_status' =>isset($request->customer_status)?$request->customer_status:1,
			'assign_catalog'=>isset($request->assign_catalog)?$request->assign_catalog:null,//seller catalogo
			'customer_created_at' =>date("Y-m-d H:i:s")
        ]);

	}

	public function updateCustomer($request,$id){

		$customer = $this->getCustomer($id);
		$request = $this->Helper->cast($request);
		$seller = $this->SellerModel->get_default();
		$id_seller = $seller?$seller->user_id:null;
		$user_id = isset($request->user_id)?implode(",", $request->user_id):$id_seller;

		$this->mymodel->update([
			'name' => $this->Helper->removespaces($request->name),
			'business_name' => $this->Helper->removespaces($request->business_name),
			'email'=> $request->newemail==null?$customer->email:$request->newemail,
			'tax_id' => $request->tax_id,
			'discount' => $request->discount,
			'term_id' => $request->term_id,
			'group_id' => $request->group_id,
			'phone' => $request->phone,
			'user_id'=> $user_id,
			'commercial_address' => $request->commercial_address,
			'commercial_delivery_address' => $request->commercial_delivery_address,
			'commercial_country' => $request->commercial_country,
			'commercial_state' => $request->commercial_state,
			'commercial_city' => $request->commercial_city,
			'commercial_zone' => $request->commercial_zone,
			'commercial_zip_code' => $request->commercial_zip_code,
			'dispatch_address' => $request->dispatch_address,
			'dispatch_country' =>$request->dispatch_country,
			'dispatch_state' =>$request->dispatch_state,
			'dispatch_city' =>$request->dispatch_city,
			'dispatch_zone' =>$request->dispatch_zone,
			'dispatch_zip_code' =>$request->dispatch_zip_code,
			'catalog_emails' => $request->catalog_emails,
			'password' =>  $request->password ? $this->Helper->enc_password($request->password) : $customer->password,
			'dispatch_shipping_notes'=> $request->dispatch_shipping_notes,
			'customer_status' =>isset($request->customer_status)?$request->customer_status:1,
			'assign_catalog'=>isset($request->assign_catalog)?$request->assign_catalog:null

		], 'customers', ['company_id' => $this->company_info->company_id, "customer_id" => $id]);
	}

	public function deleteCustomer($id){
		$this->mymodel->delete('customers',["company_id"=>$this->company_info->company_id,"customer_id"=>$id]);
	}

	public function get_group($customer_id){

		$consulta = "SELECT c.business_name,g.name as group_name,g.percentage_on_price as percentage_on_price,
		             g.percent_price_amount as percent_price_amount FROM customers AS c LEFT JOIN customer_groups AS g ON c.group_id = g.group_id
					 WHERE c.customer_id=$customer_id";
		$result = $this->db->query($consulta)->row();
		return $result;
	}

	public function update_seller_withoutseller(){
		$seller = $this->SellerModel->get_default();
		$id_company= $this->company_info->company_id;
		$query = "UPDATE customers
				  SET user_id = $seller->user_id
				  WHERE company_id = $id_company
				  AND (user_id = '' OR user_id IS NULL)";
		$this->db->query($query);
	}

    public function obtenerOrdenesClientes($customerId, $sellerId = null, $limit = 5, $page=null){


        $offset = $page ? ($page - 1) * $limit : null;

        $query = "SELECT o.order_number,
                o.order_id,
	            o.order_status,
	            o.source,
	            o.created,
	            o.total_amount, CONCAT(v.first_name, '', v.last_name)  as vendedor
                FROM orders o
                INNER JOIN seller v ON v.user_id  = o.user_id
                AND o.customer_id = $customerId";
        if($sellerId) $query .= " AND o.user_id = $sellerId";
        $query .= " order by o.created DESC limit $limit";

        if($offset) $query .= " OFFSET ". $offset;
        return $this->mymodel->fetch($query);
    }
}

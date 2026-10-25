<?php

defined('BASEPATH') or exit('No direct script access allowed');

class CategoryModel extends CI_Model
{
    public $company = array();

    public function __construct()
    {
        parent::__construct();
        $this->company = $this->Helper->get_company_info();
    }

	public function get_lang(){
		return $this->langmodel->getMdl('cmp-categorias', $this->company->language_id);
	}


    public function category_belongcategory($id){
        if ($this->mymodel->count('categories', ['company_id'=> $this->company->company_id,'cat_id'=>$id]) != 1) {
            return false;
        }
        return true;
    }

    public function all_categories_ordered(){

        $company_id = $this->company->company_id;
        $query = "SELECT cat_id,category_name
        FROM categories
        WHERE company_id = $company_id
        AND category_status = 1
        AND category_default IS null
        ORDER BY TRIM(BOTH ' ' FROM category_name) ASC;
        ";


        return $this->db->query($query)->result();

    }

    public function all_categories()
    {
        return $this->mymodel->get('categories', false, "company_id", $this->company->company_id, null, "category_id");
    }

    public function get_default()
    {
        return $this->mymodel->get_by('categories', true,
		['company_id' => $this->company->company_id,
		'category_default' => 1]);
    }

    public function get_category($category_id)
    {
        return $this->mymodel->get_by('categories', true,
		[
            'company_id' => $this->company->company_id,
		    'cat_id' => $category_id
        ]);
    }

    public function updateCategory($category, $request)
    {
		$request = $this->Helper->cast($request);
        $dataCategory = [
            "images"=> isset($request->images) ? $request->images :$category->images,
        ];
        if(!$category->category_default){
            $dataCategory['category_name'] = $request->category_name;
            $dataCategory['category_status'] = $request->category_status;

        }
		$this->mymodel->update($dataCategory, 'categories', ['company_id' => $this->company->company_id, "cat_id" => $category->cat_id]);
    }

    public function save_category($request)
    {
        $request = $this->Helper->cast($request);
        $this->mymodel->save('categories', [
            "company_id" => $this->company->company_id,
            "category_name"=>rtrim(ltrim($request->category_name)),
			"category_status"=>1,
			"images"=> isset($request->images) ? $request->images : null,
        ]);
    }

    public function delete($id)
    {
        $this->mymodel->delete('categories', ["company_id" => $this->company->company_id, "cat_id" => $id]);
    }

	public function exist_category($id=null,$request) {

        $request = $this->Helper->cast($request);
        $id_company = $this->company->company_id;
        $formato = '^' .rtrim(ltrim($request->category_name)) . '$';
        $query = "SELECT category_name FROM categories WHERE company_id=$id_company AND category_name REGEXP '$formato'";

        if ($id) {
            // validate to update
            if ($this->get_category($id)->category_name != rtrim(ltrim($request->category_name))) {
                if ($this->db->query($query)->num_rows()>0) return true;
            }

        }else{
            // validate to create
            if ($this->db->query($query)->num_rows()>0) return true;
        }
        return false;
	}

    public function enable($id){

		$this->mymodel->update([
			"category_status"=>1,
		], 'categories', ['company_id' => $this->company->company_id, "cat_id" => $id]);

    }

    public function disable($id){

        $this->mymodel->update(["category_status"=>0], 'categories', ['company_id' => $this->company->company_id, "cat_id" => $id]);

    }

    public function categoria_tieneproductos($id){

		$id_company = $this->company->company_id;
		$query = "SELECT pro_id FROM `products` as p WHERE company_id=" . $id_company ." AND FIND_IN_SET(". $id.",p.category_id)";
        $result = $this->db->query($query)->num_rows();

        if ($result > 0) return true;
		return false;
	}

    public function is_default($id){

        if ($this->get_category($id)->category_default==1) return true;

        return false;
    }

    public function exist_default($id_company){

        return $this->mymodel->get_by('categories', true,
		[   'company_id' => $id_company,
		    'category_default' => 1
        ]);
    }

    public function filter_ByIdsCategories($IdsCats,$request_post){

		$compId = $this->company_info->company_id;
		$query = "SELECT c.* FROM categories as c WHERE c.company_id = $compId AND c.cat_id IN($IdsCats)";

		$order_type = $request_post['order_type'];
		$order_sort = $request_post['sort'];

		if ($order_type!='' && $order_sort!='') {

			switch ($order_sort) {
				case 'id':
				$query .= " ORDER BY c.cat_id " . $order_type;
				break;
				case 'nombre':
				$query .= " ORDER BY c.category_name " . $order_type;
				break;
			}

		}else{
			$query .= " ORDER BY c.cat_id DESC";
		}



        return $this->db->query($query)->result();
	}

}

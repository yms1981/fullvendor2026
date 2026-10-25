<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class TermModel extends CI_Model 
{
    public $company = array();
        
    function __construct() 
	{ 
        parent::__construct(); 
        $this->company = $this->Helper->get_company_info();
    
    }

    public function all_terms(){
        return $this->mymodel->get_by("terms_of_sales",false,["company_id"=>$this->company->company_id],'term_id');
    }

    public function validar_termEditar($name,$term_id){

		$errores = array();
		$mensajes = $this->langmodel->getMdl('errores_termino', $this->company->language_id);
        $compId = $this->company->company_id;
		
        //buscamos el term of sale
		$term = $this->mymodel->get_by('terms_of_sales', true, array('company_id'=> $compId,'term_id' =>$term_id));
		$nombre_termino = $term->name;
		
		if ($nombre_termino != $name) {

			$formato = '^'. rtrim(ltrim($name)).'$';
			$searchcatSql="SELECT * from terms_of_sales WHERE company_id='$compId' and name REGEXP '$formato' and term_id != '$term_id'";

			$term_repetido = $this->db->query($searchcatSql)->num_rows();

			if ($term_repetido > 0) {

				$msg = $mensajes['term_existente'];
				array_push($errores, $msg);
			}
		}	

		return $errores;

	}

    public function get_term($id){

        return $this->mymodel->get_by("terms_of_sales",true,
        [
         "company_id"=>$this->company->company_id,
         "term_id"=>$id
        ]);
    }

    public function exist_termdefault($id){

        $term = $this->get_term($id);        

        if ($term_default = $this->get_default()) {
            if ($term->term_id == $term_default->term_id) {
                return true;    
            }
        }
        return false;
    }

    public function get_default(){
        if ($this->db->field_exists('default', 'terms_of_sales')) {
            return $this->mymodel->get_by("terms_of_sales", true, [
                "company_id" => $this->company->company_id,
                "default" => 1
            ]);
        }

        return $this->mymodel->get_by("terms_of_sales", true, [
            "company_id" => $this->company->company_id
        ], 'term_id', 1, 'ASC');
    }

    public function exist_default($id_company){
        if ($this->db->field_exists('default', 'terms_of_sales')) {
            return $this->mymodel->get_by("terms_of_sales", true, [
                "company_id" => $id_company,
                "default" => 1
            ]);
        }

        return $this->mymodel->get_by("terms_of_sales", true, [
            "company_id" => $id_company
        ], 'term_id', 1, 'ASC');
    }

    public function validar_termAlta($name){
		
		$errores = array();
		$compId = $this->company->company_id;
		$mensajes = $this->langmodel->getMdl('errores_termino', $this->company->language_id);

		$formato = '^'. rtrim(ltrim($name)).'$';
		$searchcatSql="SELECT * from terms_of_sales as t WHERE company_id='$compId' and t.name REGEXP '$formato'";
		
		$term_repetido = $this->db->query($searchcatSql)->num_rows();
		
		if ($term_repetido > 0) {

			$msg = $mensajes['term_existente'];
			array_push($errores, $msg);
		}

		return $errores;
	}


    public function desactivar_termDefault($request){

        $default = isset($request->default)?$request->default:null;
		if ($default == 1) {
            $this->mymodel->update(['default'=>null], 'terms_of_sales', ['company_id'=>$this->company->company_id,'default'=>1]);
		}
	}

    public function update($request,$id){
        
        $request = $this->Helper->cast($request);
        $this->desactivar_termDefault($request);
        $default = isset($request->default)?$request->default:null;

        $this->mymodel->update(['name' =>rtrim(ltrim($request->name)),'default'=>$default],
                                'terms_of_sales', ['company_id'=>$this->company->company_id,'term_id'=>$id]);

    }

    public function create($request){
        $this->desactivar_termDefault($request);
        $request = $this->Helper->cast($request);    
        $mydata = array(
            'company_id' => $this->company->company_id,
			'name' => rtrim(ltrim($request->name)),
            'default'=>isset($request->default)?$request->default:null,
            'created_at' =>date("Y-m-d H:i:s")
		);

		$this->mymodel->insert("terms_of_sales", $mydata);
        
    }

    public function existe_default($id){
        if ($this->db->field_exists('default', 'terms_of_sales')) {
            return $this->mymodel->get_by("terms_of_sales", true, [
                "company_id" => $id,
                "default" => 1
            ]);
        }

        return $this->mymodel->get_by("terms_of_sales", true, [
            "company_id" => $id
        ], 'term_id', 1, 'ASC');
    }

}
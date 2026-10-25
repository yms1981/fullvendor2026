<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class UnitModel extends CI_Model 
{
    public $company = array();
        
    function __construct() 
	{ 
        parent::__construct(); 
        $this->company = $this->Helper->get_company_info();    
    }

    public function all_units(){
        return $this->mymodel->get_by("units",false,["company_id"=>$this->company->company_id],'unit_id');
    }


    public function validar_unitEditar($name,$unit_id){

		$errores = array();
		$mensajes = $this->HelperLanguage->lang_erroresunits();
        $compId = $this->company->company_id;
		
        //buscamos el unit
		$unit = $this->mymodel->get_by('units', true, array('company_id'=> $compId,'unit_id' =>$unit_id));
		$name_ofunit = $unit->description;
		
		if ($name_ofunit != $name) {

			$formato = '^'. rtrim(ltrim($name)).'$';
			$query="SELECT * from units WHERE company_id='$compId' and unit REGEXP '$formato' and unit_id != '$unit_id'";

			$unit_repetido = $this->db->query($query)->num_rows();

			if ($unit_repetido > 0) {

				$msg = $mensajes['unidad_rechazada'];
				array_push($errores, $msg);
			}
		}	
		return $errores;

	}

    public function validar_unitAlta($name){
		
		$errores = array();
		$mensajes = $this->HelperLanguage->lang_erroresunits();
        $compId = $this->company->company_id;

		$formato = '^'. rtrim(ltrim($name)).'$';
		$query="SELECT * from units as u WHERE company_id='$compId' and u.unit REGEXP '$formato'";
		$unit_repetido = $this->db->query($query)->num_rows();
		
		if ($unit_repetido > 0) {

			$msg = $mensajes['unidad_rechazada'];
			array_push($errores, $msg);
		}

		return $errores;
	}


    public function get_unit($id){

        return $this->mymodel->get_by("units",true,
        [
         "company_id"=>$this->company->company_id,
         "unit_id"=>$id
        ]);
    }

    public function exist_unitdefault($id){

        $unit = $this->get_unit($id);
        if ($unit_default = $this->get_default()) {
            if ($unit->unit_id == $unit_default->unit_id) {
                return true;
            }
        }
        return false;
    }

    public function get_default(){
        return $this->mymodel->get_by("units",true,
        [
         "company_id"=>$this->company->company_id,
         "default"=>1
        ]);
    }

    public function desactivar_unitDefault($request){

        $default = isset($request->default)?$request->default:null;
		if ($default == 1) {
            $this->mymodel->update(['default'=>null], 'units', ['company_id'=>$this->company->company_id,'default'=>1]);
		}
	}

    public function update($request,$id){
        
        $request = $this->Helper->cast($request);
        $unit = $this->get_unit($id);

        $this->desactivar_unitDefault($request);
        $default = isset($request->default)?$request->default:null;
                        
        $this->mymodel->update(['description' =>$request->description,
                                'unit' => rtrim(ltrim($request->unit)),
                                'default'=>$default,
                                'formato'=> $request->formato_unidad], 
                                'units',
                                ['company_id'=>$this->company->company_id,'unit_id'=>$id]);
        // actualizamos productos
        $this->mymodel->update(['unit_type'=>$request->unit], 'products', ['company_id'=>$this->company->company_id,'unit_type'=> $unit->unit]);
    }

    public function create($request){

        $this->desactivar_unitDefault($request);
        $request = $this->Helper->cast($request);    
        $mydata = array(
            'company_id' => $this->company->company_id,
            'unit'=> rtrim(ltrim($request->unit)),
            'formato'=> $request->formato_unidad,
			'description' =>$request->description,
            'status'=> 1,
            'created' =>date("Y-m-d H:i:s"),
            'default'=>isset($request->default)?$request->default:null
		);

    	$this->mymodel->insert("units", $mydata);
    }

    public function exist_default($id_company){

		if ($this->db->field_exists('default', 'units')) {
			return $this->mymodel->get_by("units",true,
				[
					"company_id"=>$id_company,
					"default"=>1
				]);
		}

		return $this->mymodel->get_by("units",true, ["company_id"=>$id_company], 'unit_id', 1, 'ASC');
    }

}
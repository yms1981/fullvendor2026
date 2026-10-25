<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @property Commonmodel $Commonmodel
 * @property OrdenModel $OrdenModel
 *
 */
class GroupModel extends CI_Model
{
    public $company = array();

    function __construct()
	{
        parent::__construct();
        $this->load->model('OrdenModel');
        $this->company = $this->Helper->get_company_info();
    }

    public function all(){
        return $this->mymodel->get_by("customer_groups",false,["company_id"=>$this->company->company_id]);
    }

    public function all_actives(){

        $id_company = $this->company->company_id;
        // Actualizado: eliminada referencia a g.default ya que la columna fue eliminada
        // Ahora se usa companies.default_customer_group_id para indicar el grupo por defecto
        // Usando comillas invertidas para 'default' porque es palabra reservada en SQL
        $query = "SELECT g.group_id as id, g.name, 
                  CASE WHEN comp.default_customer_group_id = g.group_id THEN 1 ELSE NULL END as `default`
                  FROM customer_groups AS g
                  LEFT JOIN customers AS c ON g.group_id = c.group_id
                  LEFT JOIN companies AS comp ON comp.company_id = g.company_id
                  WHERE g.company_id = $id_company
                  AND g.group_status = 1
                  AND c.customer_status = 1
                  GROUP BY g.group_id, g.name, comp.default_customer_group_id
                  HAVING COUNT(c.customer_id) > 0
                  ORDER BY g.name ASC;";

        return $this->db->query($query)->result();
            //return $this->mymodel->get_by("customer_groups",false,["company_id"=>$this->company->company_id,"group_status"=> 1], "name",null,"ASC");
    }

    /**
     * Devuelve todos los grupos activos de la empresa (group_status = 1),
     * sin filtrar por tener clientes. Para uso en modal de descarga/enviar catálogo.
     * Misma estructura que all_actives(): id, name, default.
     */
    public function all_actives_for_catalog(){
        $id_company = $this->company->company_id;
        $query = "SELECT g.group_id as id, g.name,
                  CASE WHEN comp.default_customer_group_id = g.group_id THEN 1 ELSE NULL END as `default`
                  FROM customer_groups AS g
                  LEFT JOIN companies AS comp ON comp.company_id = g.company_id
                  WHERE g.company_id = $id_company
                  AND g.group_status = 1
                  ORDER BY g.name ASC";
        return $this->db->query($query)->result();
    }

    public function validar_toEditar($name,$id){

		$errores = array();
		$mensajes = $this->HelperLanguage->lang_groups();
        $compId = $this->company->company_id;

        //buscamos el unit
		$group = $this->mymodel->get_by('customer_groups', true, array('company_id'=> $compId,'group_id' =>$id));
		$name_ofgroup = $group->name;

		if ($name_ofgroup != $name) {

			$formato = '^'. rtrim(ltrim($name)).'$';
			$query="SELECT * from customer_groups WHERE company_id=$compId and name REGEXP '$formato' and group_id != '$id'";

			$repetido = $this->db->query($query)->num_rows();

			if ($repetido > 0) {

				$msg = $mensajes['group_existente'];
				array_push($errores, $msg);
			}
		}
		return $errores;

	}

    public function validar_toAlta($name){

		$errores = array();
		$mensajes = $this->HelperLanguage->lang_groups();
        $compId = $this->company->company_id;

		$formato = '^'. rtrim(ltrim($name)).'$';
		$query="SELECT * from customer_groups as g WHERE company_id=$compId and g.name REGEXP '$formato'";

        $unit_repetido = $this->db->query($query)->num_rows();

		if ($unit_repetido > 0) {

			$msg = $mensajes['group_existente'];
			array_push($errores, $msg);
		}

		return $errores;
	}

    public function get($id){

        $group = $this->get_group($id);
        return $group?$group:$this->get_default();
    }


    public function get_group($id){

        return $this->mymodel->get_by("customer_groups",true,
        [
         "company_id"=>$this->company->company_id,
         "group_id"=>$id
        ]);
    }

    public function exist_default($id){

        $group = $this->get_group($id);
        if ($group_default = $this->get_default()) {
            if ($group->group_id == $group_default->group_id) {
                return true;
            }
        }
        return false;
    }

    public function existe_default($id){
        if ($this->db->field_exists('default', 'customer_groups')) {
            return $this->mymodel->get_by("customer_groups", true, [
                "company_id" => $id,
                "default" => 1
            ]);
        }

        return $this->db
            ->select('g.*')
            ->from('customer_groups g')
            ->join('companies c', 'c.company_id = g.company_id', 'left')
            ->where('g.company_id', $id)
            ->where('c.default_customer_group_id = g.group_id', null, false)
            ->limit(1)
            ->get()
            ->row();
    }

    public function get_default(){
        return $this->mymodel->get_by("customer_groups",true,
        [
         "company_id"=>$this->company->company_id,
         "default"=>1
        ]);
    }

    public function desactivar_Default($request){

        $default = isset($request->default)?$request->default:null;
		if ($default == 1) {
            $this->mymodel->update(['default'=>null], 'customer_groups', ['company_id'=>$this->company->company_id,'default'=>1]);
		}
	}

    public function update($request,$id){

        $request = $this->Helper->cast($request);

        $this->desactivar_Default($request);
        $default = isset($request->default)?$request->default:null;

        $mydata = array(
            'name' => $request->name,
            'percentage_on_price' =>$request->percentage_on_price,
            'percent_price_amount'=> $this->Commonmodel->get_value_format($request->percent_price_amount),
            'default' => $default
        );
        $this->mymodel->update($mydata, 'customer_groups',['company_id'=>$this->company->company_id,'group_id'=>$id]);
    }

    public function create($request){

        $this->desactivar_Default($request);
        $request = $this->Helper->cast($request);
        $mydata = array(
            'company_id' => $this->company->company_id,
            'name' =>$request->name,
            'percentage_on_price' => $request->percentage_on_price,
            'percent_price_amount'=> $this->Commonmodel->get_value_format($request->percent_price_amount),
            'group_status' => 1,
            'default' => isset($request->default)?$request->default:null,
            'created_at' =>date("Y-m-d H:i:s")
        );

        $this->mymodel->insert("customer_groups", $mydata);
    }

    function obtenerLabelTipoPorcentaje($grupo, $langprods, $percentage = true) {

        $percent_price_amount = $percentage ?  $grupo->percent_price_amount.'%' : "";
        $labels = [
            'Decrease on Price List %' => '('.$langprods['decrease']. ') '.$percent_price_amount,
            'Increase on Price List %' => '('.$langprods['increase']. ') '.$percent_price_amount,
            'Nothing' => '('.$langprods['ninguno'] . ') 0%',
        ];
        return $labels[$grupo->percentage_on_price] ?? '('.$langprods['ninguno'] . ') 0%';
    }

    public function obtenerGrupoDePrecios($gruposClientes, $precioProducto = 0, $lanprods = null){

        foreach ($gruposClientes as $grupo) {
            $grupo->sale_price_recalculado = $this->OrdenModel->recalcularPrecio($grupo,$precioProducto);
            $grupo->sale_price_recalculado = $this->OrdenModel->formatNumber($grupo->sale_price_recalculado);
            $grupo->labelTipoPorcentaje = $lanprods ? $this->obtenerLabelTipoPorcentaje($grupo, $lanprods) : "";
        }
        return $gruposClientes;
    }

    public function getGroupText($groupId)
    {
        $grupoCliente = $this->get($groupId);
        $langprods = $this->HelperLanguage->lang_products();

        return $grupoCliente->name." ". $this->GroupModel->obtenerLabelTipoPorcentaje(
            $grupoCliente,
            $this->HelperLanguage->lang_products()
        );
    }
}

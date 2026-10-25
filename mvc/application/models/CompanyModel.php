<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');


class CompanyModel extends MY_Model
{
    public $company = array();

    function __construct()
	{
        parent::__construct();
        $this->load->model("SellerModel");
        $this->load->model("ProfileModel");
        $this->load->model("CategoryModel");
        $this->load->model("GroupModel");
        $this->load->model("TermModel");
        $this->load->model("UnitModel");
    }

    public function all(){
        return $this->mymodel->get_by("companies",false);
    }

    public function getCompany($id){
        return $this->mymodel->get_by("companies",true,["company_id"=>$id]);
    }

    public function get_typelang($id){

        if ($id !== null) {
            $lang = $this->mymodel->get_by('languages',true,['language_id'=>$id]);
            if ($lang){
                return $lang;
            }
        }
        
        // Si no se encuentra el idioma o $id es null, usar el idioma por defecto
        return $this->mymodel->get_by('languages',true,['default'=> 1]);
    }

    public function init_databydefault($id_company){

        $data_default = $this->langmodel->get_lang("cmp-data-default", $id_company);
        $this->mergeCompanyBootstrapDefaults($id_company, $data_default);

        if(!$this->CategoryModel->exist_default($id_company)) $this->create_category($id_company,$data_default);
        if(!$this->GroupModel->existe_default($id_company)) $this->create_Fullvendorgroup($id_company,$data_default);
        if($this->companyShouldCreateDefaultPaymentTerm($id_company)) $this->create_termino_Sales($id_company,$data_default);
        if(!$this->ProfileModel->exist_default($id_company)) $this->create_profile($id_company,$data_default);
        if(!$this->SellerModel->exist_default($id_company)) $this->create_seller($id_company,$data_default);
        if($this->companyShouldCreateDefaultUnit($id_company)) $this->create_unit($id_company,$data_default);

        $this->syncCompanyDefaultUnitAndPaymentTermIds($id_company);

    }

	/**
	 * Textos por defecto al crear compañía: API langmdl + respaldo si falla.
	 * Unidad de medida por defecto siempre "Un" (código interno de unidad).
	 *
	 * @param array<string, mixed>|null $data_default
	 */
	private function mergeCompanyBootstrapDefaults(int $id_company, &$data_default): void
	{
		if (! is_array($data_default)) {
			$data_default = [];
		}
		$company = $this->getCompany($id_company);
		$es = $company && isset($company->language_id) && (int) $company->language_id === 2;
		$fallback = [
			'category_default' => $es ? 'Sin Categoria' : 'Uncategorized',
			'group_default' => 'Group Fullvendor',
			'term_default' => $es ? 'Contado' : 'Cash',
			'profile_default' => $es ? 'Administrador' : 'Administrator',
			'username_default' => $es ? 'administrador' : 'administrator',
		];
		foreach ($fallback as $k => $v) {
			if (! isset($data_default[$k]) || trim((string) $data_default[$k]) === '') {
				$data_default[$k] = $v;
			}
		}
		$data_default['unit_default'] = 'Un';
	}

	private function companyShouldCreateDefaultPaymentTerm(int $id_company): bool
	{
		if ($this->db->field_exists('default_term_id', 'companies')) {
			$co = $this->getCompany($id_company);
			if ($co && isset($co->default_term_id) && (int) $co->default_term_id > 0) {
				return false;
			}
		}

		return ! (bool) $this->TermModel->existe_default($id_company);
	}

	private function companyShouldCreateDefaultUnit(int $id_company): bool
	{
		if ($this->db->field_exists('default_unit_id', 'companies')) {
			$co = $this->getCompany($id_company);
			if ($co && isset($co->default_unit_id) && (int) $co->default_unit_id > 0) {
				return false;
			}
		}
		if ($this->mymodel->get_by('units', true, ['company_id' => $id_company, 'unit' => 'Un'])) {
			return false;
		}
		if ($this->db->field_exists('default', 'units')) {
			return ! (bool) $this->UnitModel->exist_default($id_company);
		}

		return $this->mymodel->count('units', ['company_id' => $id_company]) < 1;
	}

	/**
	 * Asigna default_unit_id / default_term_id en companies si existen columnas y siguen vacíos.
	 */
	private function syncCompanyDefaultUnitAndPaymentTermIds(int $id_company): void
	{
		$co = $this->getCompany($id_company);
		if (! $co) {
			return;
		}
		$patch = [];
		if ($this->db->field_exists('default_unit_id', 'companies')
			&& (empty($co->default_unit_id) || (int) $co->default_unit_id < 1)) {
			$u = $this->mymodel->get_by('units', true, ['company_id' => $id_company, 'unit' => 'Un']);
			if (! $u || ! isset($u->unit_id)) {
				$u = $this->mymodel->get_by('units', true, ['company_id' => $id_company], 'unit_id', 1, 'ASC');
			}
			if ($u && isset($u->unit_id)) {
				$patch['default_unit_id'] = (int) $u->unit_id;
			}
		}
		if ($this->db->field_exists('default_term_id', 'companies')
			&& (empty($co->default_term_id) || (int) $co->default_term_id < 1)) {
			$t = $this->mymodel->get_by('terms_of_sales', true, ['company_id' => $id_company], 'term_id', 1, 'ASC');
			if ($t && isset($t->term_id)) {
				$patch['default_term_id'] = (int) $t->term_id;
			}
		}
		if ($patch === []) {
			return;
		}
		$patch = $this->filterExistingColumns('companies', $patch);
		if ($patch !== []) {
			$this->mymodel->update($patch, 'companies', ['company_id' => $id_company]);
		}
	}

    public function create_category($id_company,$data_default){

        $mydata = array(
            'company_id' => $id_company,
            'category_name'=> $data_default['category_default'],
            'category_status' => 1,
            'category_created_at' =>date("Y-m-d H:i:s"),
            'category_default'=>1
        );

        $this->mymodel->insert("categories", $mydata);
    }

    public function create_Fullvendorgroup($id_company,$data_default){

        $mydata = array(
            'company_id' => $id_company,
            'name' => $data_default['group_default'],
            'percentage_on_price' => 'Nothing',
            'percent_price_amount'=> 0,
            'group_status' => 1,
            'created_at' =>date("Y-m-d H:i:s")
        );
        if ($this->db->field_exists('default', 'customer_groups')) {
            $mydata['default'] = 1;
        }

        $this->mymodel->insert("customer_groups", $mydata);
        $newGroupId = (int) $this->db->insert_id();
        if ($newGroupId > 0 && $this->db->field_exists('default_customer_group_id', 'companies')) {
            $this->mymodel->update(
                ['default_customer_group_id' => $newGroupId],
                'companies',
                ['company_id' => $id_company]
            );
        }

    }

    public function create_termino_Sales($id_company,$data_default){

        $mydata = array(
            'company_id' => $id_company,
			'name' => $data_default['term_default'],
            'created_at' =>date("Y-m-d H:i:s")
		);
        if ($this->db->field_exists('default', 'terms_of_sales')) {
            $mydata['default'] = 1;
        }
		if ($this->db->field_exists('term_status', 'terms_of_sales')) {
			$mydata['term_status'] = 1;
		}
		$mydata = $this->filterExistingColumns('terms_of_sales', $mydata);
		$this->mymodel->insert("terms_of_sales", $mydata);
    }

    public function create_profile($id_company,$data_default){
        $mydata = array(
           'profile_name' =>$data_default['profile_default'],
           'company_id' => $id_company,
           'order_discount' =>1,
           'order_disc_price'=>1,
           'order_net_discount' => 1,
           'order_net_disc_price'=> 1,
           'can_change_price' => 1,
           'can_send_catalog' => 1,
           'can_create_customer' => 1,
           'status' => 1,
           'created' =>date("Y-m-d H:i:s"),
           'default'=> 1
       );

       $this->mymodel->insert("seller_profiles", $mydata);
    }


    public function create_seller($id_company,$data_default){

        $profile_default = $this->ProfileModel->get_default($id_company);
        $company = $this->getCompany($id_company);

        $mydata = array(
            'company_id' => $id_company,
			'username' => $data_default['username_default'],
			'first_name' => $data_default['profile_default'],
			'last_name' => '',
            'email' => $company->email,
			'phone_number' => '--',
			'cell_number' => '--',
			'password' => $company->password,
			'profile_image' => null,
			'fax' => '',
			'status' => 1,
			'email_verification' => 1,
			'created' =>date("Y-m-d H:i:s"),
            'profile' => $profile_default->profile_id,
			'all_customers' => 1
		);
        if ($this->db->field_exists('default', 'sellers')) {
            $mydata['default'] = 1;
        }
        $mydata = $this->filterExistingColumns('sellers', $mydata);
		$this->mymodel->insert("sellers", $mydata);

    }

    /**
     * Filtra un payload dejando solo columnas existentes en la tabla.
     *
     * @param string $table
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    private function filterExistingColumns(string $table, array $payload): array
    {
        $filtered = [];
        foreach ($payload as $col => $value) {
            if ($this->db->field_exists($col, $table)) {
                $filtered[$col] = $value;
            }
        }
        return $filtered;
    }

    public function create_unit($id_company,$data_default){

		$unitLabel = isset($data_default['unit_default']) ? trim((string) $data_default['unit_default']) : 'Un';
		if ($unitLabel === '') {
			$unitLabel = 'Un';
		}

        $mydata = array(
            'company_id' => $id_company,
            'unit'=> $unitLabel,
            'formato'=> null,
			'description' => $unitLabel,
            'status'=> 1,
            'created' =>date("Y-m-d H:i:s"),
		);
		if ($this->db->field_exists('default', 'units')) {
			$mydata['default'] = 1;
		}

		$mydata = $this->filterExistingColumns('units', $mydata);
    	$this->mymodel->insert("units", $mydata);
    }

    public function getNextOrderNumber($companyId, $prefix="ORD")
    {
        $maxOrderNumber = 0;
        $sql = "SELECT
                MAX(CAST(substr(order_number,4) as INT )) as orderNumber,
                substr(order_number,1,3) as prefix
                FROM orders t WHERE company_id={$companyId}
                GROUP BY company_id, prefix";
        $res = $this->db->query($sql)->result();
        foreach ($res as $row) {
            if($row->prefix == $prefix){
                $maxOrderNumber = (int) $row->orderNumber;
                break;
            }
        }
        $initial = $this->getInitialOrderNumber($companyId);
        if ($initial !== null && $initial !== '') {
            $maxOrderNumber = max($maxOrderNumber, (int) $initial);
        }
        return $maxOrderNumber + 1;

    }

    /**
     * @param $companyId
     * @return boolean
     */
    public function hasInitialOrderNumber($companyId) {
         return $this->count('initial_order_number', ['company_id' => $companyId]) > 0;
    }

    public function getInitialOrderNumber($companyId) {
        $initialForCompany = $this->get_by('initial_order_number', true, ['company_id' => $companyId]);
        if (! is_object($initialForCompany) || ! isset($initialForCompany->initial_number)) {
            return null;
        }
        return $initialForCompany->initial_number;
    }

    public function saveInitialOrderNumber($newInitialOrderNumber, $companyId)
    {
        $initialOrderNumber = $this->getInitialOrderNumber($companyId);
        if ($newInitialOrderNumber) {
            if (!$initialOrderNumber) {
                $this->save('initial_order_number', ["company_id" => $companyId, "initial_number" => $newInitialOrderNumber]);
            } else {
                $this->update(["initial_number" => $newInitialOrderNumber], 'initial_order_number', ["company_id" => $companyId]);
            }
        }
    }


}

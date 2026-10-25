<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @property Langmodel $langmodel
 * @property Helper $Helper
 * @property CompanyModel $CompanyModel
 */
class Commonmodel extends MY_Model
{

    const salt = '!ME7rW3$L1#E';// contraseña de encriptado
    private $algorithm;
    public ?int $company;
    public $company_info = array();
    public $format_numeric = array();
    public ?int $langId;

    function __construct()
    {
        parent::__construct();
        $this->load->model('langmodel');
        $this->company = $this->session->userdata('compId');
        $this->langId = $this->session->userdata('language_id');
    }

    /**
     * Carga company_info y format_numeric vía API (sin BD) bajo demanda.
     */
    protected function ensureCompanyContext(): void
    {
        if (! empty($this->company_info) || empty($this->company)) {
            return;
        }
        $company = $this->getObjectFromApi("company/{$this->company}");
        if (! is_object($company) || isset($company->error)) {
            return;
        }
        $this->company_info = $company;

        $formatId = $company->format_id ?? null;
        $format = $formatId
            ? $this->getObjectFromApi("formatnumeric/{$formatId}")
            : $this->getObjectFromApi('formatnumeric/default');
        if (is_object($format) && ! isset($format->error)) {
            $this->format_numeric = $format;
        }
    }

    public function fetch_all($tbl)
    {
        $this->db->select('*');
        $query = $this->db->get($tbl);
        return $query->result();
    }

    public function fetch_customer_data($companyId)
    {
        $query = "SELECT name,business_name,tax_id,discount,term_id,group_id,email,phone,cell_phone,user_id,notes,commercial_address,commercial_delivery_address,commercial_country,commercial_state,commercial_city,commercial_zone,commercial_zip_code,dispatch_address,dispatch_delivery_address,dispatch_country,dispatch_state,dispatch_city,dispatch_zone,dispatch_zip_code,dispatch_shipping_notes,catalog_emails,customer_status,DATE_FORMAT(customer_created_at,'%Y-%m-%d') FROM customers WHERE company_id='" . $companyId . "'";
        $usersData = $this->db->query($query)->result_array();
        return $usersData;
    }

    public function fetch_warehouse_data($companyId)
    {
        $query = "SELECT unique_id,first_name,last_name,email,phone_number,DATE_FORMAT(created,'%Y-%m-%d') FROM warehouse_users WHERE company_id='" . $companyId . "'";
        $usersData = $this->db->query($query)->result_array();
        return $usersData;
    }

    public function fetch_category_data($companyId)
    {
        $query = "SELECT category_id,category_name,DATE_FORMAT(category_created_at,'%Y-%m-%d') FROM categories  WHERE company_id='" . $companyId . "'";
        $usersData = $this->db->query($query)->result_array();
        return $usersData;
    }


    public function fetch_ordersdata($orderId)
    {
        $query = "SELECT c.business_name,p.sku,p.name,od.qty,od.sale_price,od.qty * od.sale_price AS totPrice,DATE_FORMAT(od.created,'%Y-%m-%d') FROM order_details AS od JOIN orders AS ord ON od.order_id=ord.order_id JOIN customers AS c ON c.customer_id=ord.customer_id JOIN products AS p ON p.product_id=od.product_id WHERE od.order_id='" . $orderId . "'";
        $usersData = $this->db->query($query)->result_array();
        return $usersData;
    }

    public function fetch_totalordersdata($compId, $orderids)
    {
        $query = "SELECT ord.order_number,(SELECT CONCAT(first_name,' ',last_name)
						FROM warehouse_users
						WHERE user_id=ord.warehouse_user_id LIMIT 1) as deposito_asignado,
						ord.order_status,CONCAT(v.first_name,' ',v.last_name) AS seller,
						ord.discount_type,ord.discount,ord.order_id,c.tax_id,c.cust_id_kor,
						c.business_name,p.sku,p.stock as stock_erp,p.name,od.qty,od.sale_price,od.qty * od.sale_price AS totPrice,
						od.comments,ord.order_comments as notas_orden,c.dispatch_shipping_notes as notas_entrega,
						DATE_FORMAT(od.created,'%Y-%m-%d') as created,
						(SELECT SUM(qty * sale_price) FROM order_details
						WHERE order_id = ord.order_id)  AS total_bruto
						FROM order_details AS od JOIN orders AS ord ON od.order_id=ord.order_id
						INNER JOIN products as p ON od.product_id=p.pro_id
						LEFT JOIN customers as c ON ord.customer_id=c.customer_id
						LEFT JOIN seller as v ON v.user_id=ord.user_id
						WHERE ord.company_id=$compId
						AND ord.order_id IN('$orderids')
						GROUP BY ord.order_number,od.product_id ORDER BY ord.order_id DESC, od.detail_id ASC";

        $usersData = $this->db->query($query)->result();
        return $usersData;
    }

    public function fetch_all_join($query)
    {
        $query = $this->db->query($query);
        return $query->result();
    }

    public function fetch_single_join($query)
    {
        $query = $this->db->query($query);
        return $query->row();
    }

    public function fetch_row($tbl, $where)
    {
        $this->db->select('*');
        $this->db->where($where);
        $query = $this->db->get($tbl);
        return $query->row();
    }

    public function fetch_all_rows($tbl, $where)
    {
        $this->db->select('*');
        $this->db->where($where);
        $query = $this->db->get($tbl);
        return $query->result();
    }

    public function fetch_all_rows_limit($tbl, $where, $limit)
    {
        $this->db->select('*');
        $this->db->where($where);
        $this->db->limit($limit);
        $query = $this->db->get($tbl);
        return $query->result();
    }

    public function fetch_by_limit_offset($tbl, $where, $limit = null, $offset = null)
    {
        $this->db->select('*');
        $this->db->where($where);
        $query = $this->db->from($tbl);
        $this->db->limit($limit, $offset);
        $query = $this->db->get();
        return $query->result();
    }

    public function delete_single_con($tbl, $where)
    {
        $this->db->where($where);
        $delete = $this->db->delete($tbl);
        return $delete;
    }

    public function edit_single_row($tbl, $data, $where)
    {
        $this->db->where($where);
        if ($this->db->update($tbl, $data)) {
            return true;
        } else {
            return false;
        }
    }

    public function add_details($tbl, $data)
    {
        $this->db->insert($tbl, $data);
        $lastid = $this->db->insert_id();
        return $lastid;
    }

    public function total_count($tbl, $where)
    {
        $this->db->select('*');
        $this->db->where($where);
        return $this->db->count_all_results($tbl);
    }

    protected $key = 'id';

    public function get($table, $single = false, $col = null, $id = null, $limit = null, $order_by = null)
    {
        if (!$col == null && !$id == null) {
            $this->db->where($col, $id);
        }

        $this->db->from($table);

        if ($single == false) {
            $method = 'result';
        } else {
            $method = 'row';
        }

        if (!$limit == null) {
            $this->db->limit($limit);
        }

        if (!$order_by == null) {
            $this->db->order_by($order_by, 'DESC');
        }

        $query = $this->db->get();

        $result = $query->$method();

        return $result;
    }

    public function obtener_resultado(
        $table,
        $single = false,
        $where = null,
        $order_by = null,
        $type_order = null,
        $limit = null
    ) {
        if (!$where == null) {
            $this->db->where($where);
        }

        $this->db->from($table);

        if ($single == false) {
            $method = 'result';
        } else {
            $method = 'row';
        }

        if (!$limit == null) {
            $this->db->limit($limit);
        }

        if (!$order_by == null && !$type_order == null) {
            $this->db->order_by($order_by, $type_order);
        }

        $query = $this->db->get();
        $result = $query->$method();

        return $result;
    }


    public function get_by($table, $single = false, $where = null, $order_by = null, $limit = null, $type_order = 'DESC')
    {
        if (!$where == null) {
            $this->db->where($where);
        }

        $this->db->from($table);

        if ($single == false) {
            $method = 'result';
        } else {
            $method = 'row';
        }

        if (!$limit == null) {
            $this->db->limit($limit);
        }

        if (!$order_by == null) {
            $this->db->order_by($order_by, $type_order);
        }

        $query = $this->db->get();

        $result = $query->$method();

        return $result;
    }

    /**
     * Usuarios depósito para &lt;select&gt;: user_id + first_name/last_name (compat. vistas).
     * Esquema nuevo: company_user.type = warehouse + users.name; legado: tabla warehouse_users con first_name.
     *
     * @return array<int, object>
     */
    public function getWarehouseUsersForCompanySelect(int $companyId): array
    {
        if ($this->db->table_exists('company_user')
            && $this->db->field_exists('type', 'company_user')
            && $this->db->table_exists('users')) {
            $activeUser = $this->db->field_exists('deleted_at', 'users')
                ? ' AND u.deleted_at IS NULL'
                : '';
            $sql = 'SELECT DISTINCT u.id AS user_id, u.name, u.email
                FROM company_user cu
                INNER JOIN users u ON u.id = cu.user_id
                WHERE cu.company_id = ? AND cu.type = ?' . $activeUser . '
                ORDER BY u.name ASC';
            $rows = $this->db->query($sql, [(int) $companyId, 'warehouse'])->result();
            $out = [];
            foreach ($rows as $row) {
                $out[] = $this->warehouseUserRowFromUserName($row);
            }

            return $out;
        }

        if ($this->db->table_exists('warehouse_users')
            && $this->db->field_exists('first_name', 'warehouse_users')) {
            $r = $this->get_by('warehouse_users', false, ['company_id' => $companyId], 'first_name', null, 'ASC');

            return is_array($r) ? $r : [];
        }

        return [];
    }

    /**
     * Fila users.id → mismo shape que getWarehouseUsersForCompanySelect (first_name/last_name desde name).
     *
     * @param object $row propiedades user_id o id, name, email opcional
     */
    public function warehouseUserRowFromUserName(object $row): object
    {
        $uid = isset($row->user_id) ? (int) $row->user_id : (int) ($row->id ?? 0);
        $name = isset($row->name) ? trim((string) $row->name) : '';
        $parts = $name === '' ? [] : preg_split('/\s+/u', $name, 2);
        $o = new \stdClass();
        $o->user_id = $uid;
        $o->first_name = $parts[0] ?? '';
        $o->last_name = $parts[1] ?? '';
        $o->email = $row->email ?? '';

        return $o;
    }


    public function select($select, $table, $single = false, $where = null, $limit = null, $order_by = null, $type_order = 'DESC')
    {
        $this->db->select($select);

        if (!$where == null) {
            $this->db->where($where);
        }

        $this->db->from($table);

        if ($single == false) {
            $method = 'result';
        } else {
            $method = 'row';
        }

        if (!$limit == null) {
            $this->db->limit($limit);
        }

        if (!$order_by == null) {
            $this->db->order_by($order_by, $type_order);
        }

        $query = $this->db->get();

        $result = $query->$method();

        return $result;
    }


    public function save($table, $data)
    {
        if ($this->db->insert($table, $data)) {
            return true;
        } else {
            return false;
        }
    }


    public function update($data, $table, $where)
    {
        $this->db->where($where);

        if ($this->db->update($table, $data)) {
            return true;
        } else {
            return false;
        }
    }


    public function delete($table, $where)
    {
        if ($this->db->delete($table, $where)) {
            return true;
        } else {
            return false;
        }
    }


    public function count($table, $where = null)
    {
        if ($where != null) {
            $this->db->where($where);
        }

        $this->db->from($table);
        return $this->db->count_all_results();
    }


    public function fetch($sql, $single = false)
    {
        $query = $this->db->query($sql);

        if ($single == false) {
            $result = $query->result();
        } else {
            $result = $query->row();
        }
        return $result;
    }


    public function change_status($table, $where, $status)
    {
        if ($this->update($status, $table, $where) == true) {
            return true;
        } else {
            return false;
        }
    }

    public function fetchCompany_logsdata($fromdate, $todate, $logtype, $compId = false)
    {
        if ($fromdate != '' && $todate != '' && $logtype != '') {
            $searchSql = "SELECT * FROM logs WHERE ";
            if ($fromdate != '') {
                $searchSql .= "(date(created_on) >= '" . $fromdate . "'";
            }
            if ($todate != '') {
                $searchSql .= " AND date(created_on) <= '" . $todate . "'";
            }
            if ($logtype != '') {
                $searchSql .= " AND log_type = '" . $logtype . "') ";
            }
            $searchSql .= " AND company_id='" . $compId . "' ORDER BY log_id ASC";
        } else {
            $searchSql = "SELECT * FROM `logs` WHERE company_id='" . $compId . "'";
            if ($fromdate != '') {
                $searchSql .= "AND date(created_on) = '" . $fromdate . "'";
            }
            if ($todate != '') {
                $searchSql .= "AND date(created_on) = '" . $todate . "'";
            }
            if ($logtype != '') {
                $searchSql .= "AND log_type = '" . $logtype . "'";
            }
            $searchSql .= " ORDER BY  log_id  ASC ";
        }
        $chlist = $this->db->query($searchSql)->result();
        $output = '';
        if (count($chlist) > 0) {
            $j = 0;
            foreach ($chlist as $log) {
                $vendor = $this->db->get_where('sellers', array('user_id' => @$log->user_id))->row();
                $j++;

                $output .= '<tr>
				<td>' . $j . '</td>
				<td>' . @$vendor->email . '</td>
				<td>' . @$log->log_type . '</td>
				<td>' . fv_format_display_date(@$log->created_on, fv_resolve_display_date_format() . ' H:i A') . '</td>
				</tr>';
            }
        } else {
            $output .= '<tr class="text-center"><td colspan="5">
			No data available
			</td></tr>';
        }

        return $output;
    }

    public function fetchtotal_logsdata($fromdate, $todate, $logtype)
    {
        if ($fromdate != '' && $todate != '' && $logtype != '') {
            $searchSql = "SELECT * FROM logs ";
            if ($fromdate != '') {
                $searchSql .= "WHERE (date(created_on) >= '" . $fromdate . "'";
            }
            if ($todate != '') {
                $searchSql .= " AND date(created_on) <= '" . $todate . "'";
            }
            if ($logtype != '') {
                $searchSql .= " AND log_type = '" . $logtype . "') ";
            }
            $searchSql .= " ORDER BY log_id ASC";
        } else {
            $searchSql = "SELECT * FROM `logs` WHERE ";
            if ($fromdate != '') {
                $searchSql .= " date(created_on) >= '" . $fromdate . "'";
            }
            if ($todate != '') {
                $searchSql .= "AND date(created_on) <= '" . $todate . "'";
            }
            if ($logtype != '') {
                $searchSql .= "AND log_type = '" . $logtype . "'";
            }
            $searchSql .= " ORDER BY  log_id  ASC ";
        }
        $chlist = $this->db->query($searchSql)->result();

        $output = '';
        if (count($chlist) > 0) {
            $j = 0;
            foreach ($chlist as $log) {
                $vendor = $this->db->get_where('sellers', array('user_id' => @$log->user_id))->row();
                $j++;

                $output .= '<tr>
				<td>' . $j . '</td>
				<td>' . @$vendor->email . '</td>
				<td>' . @$log->log_type . '</td>
				<td>' . fv_format_display_date(@$log->created_on, fv_resolve_display_date_format() . ' H:i A') . '</td>
				</tr>';
            }
        } else {
            $output .= '<tr class="text-center"><td colspan="5">
			No data available
			</td></tr>';
        }
        return $output;
    }

    public function fetchProductsdatastatusupdate($compId, $categoryId = false, $statusId = false)
    {
        $searchcatSql = "SELECT p.pro_id,p.category_id, p.name,p.minimum_stock, p.sku, p.sale_price, p.purchase_price, p.barcode, p.company_id, p.stock, p.status, p.currency_type, (SELECT img.name FROM product_images AS pimg JOIN images AS img ON img.uuid = pimg.image_id WHERE pimg.product_id = p.pro_id LIMIT 1) AS productImage
		FROM products AS p WHERE company_id ='" . $compId . "' ";

        if ($statusId == "0") {
            $searchcatSql .= "AND p.status='0'";
        } elseif ($statusId == "1") {
            $searchcatSql .= "AND p.status='1' ";
        } elseif ($statusId == "3") {
            $searchcatSql .= "AND (p.status='1' OR  p.status='0')";
        }

        if ($categoryId != "") {
            $searchcatSql .= " AND FIND_IN_SET('" . $categoryId . "',p.category_id) ";
        }
        if ($statusId == "" && $categoryId == "") {
            $searchcatSql = "SELECT p.pro_id,p.category_id, p.name,p.minimum_stock, p.sku, p.sale_price, p.purchase_price, p.barcode, p.company_id, p.stock, p.status, p.currency_type, (SELECT img.name FROM product_images AS pimg JOIN images AS img ON img.uuid = pimg.image_id WHERE pimg.product_id = p.pro_id LIMIT 1) AS productImage
			FROM products AS p WHERE company_id ='" . $compId . "' AND p.status='1'";
        }
        $searchcatSql .= " ORDER BY p.pro_id DESC";
        //echo $searchcatSql;
        $chproductlist = $this->db->query($searchcatSql)->result();
        $output = '';
        if (count($chproductlist) > 0) {
            $i = 0;
            foreach ($chproductlist as $v) {
                $i++;
                if ($v->status == "1") {
                    $status = 'Enable';
                } elseif ($v->status == "0") {
                    $status = 'Disable';
                }
                $catnames = explode(",", @$v->category_id);

                $catname = array();
                foreach ($catnames as $cat) {
                    $productcat = $this->mymodel->get_by('categories', true, array('cat_id' => $cat));

                    $catname[] = @$productcat->category_name;
                }
                $comma_separated = implode(",", $catname);

                $output .= '<tr class="prodlist">
				<td><input type="checkbox"  value="' . @$v->pro_id . '" class="checkBoxClass"></td><td>' . $i . '</td> <td class="proimg">';
                if ($v->productImage != "") {
                    $output .= '<img src="' . base_url(
                            'uploads/products/'
                        ) . $v->productImage . '" class="img-responsive" alt="" style="width: 120px; max-height: 100px;">';
                } else {
                    $output .= '<img src="' . base_url(
                            'uploads/noimg.png'
                        ) . '" class="img-responsive" alt="" style="width: 120px; max-height: 100px;">';
                }
                $output .= '</td><td>' . @$comma_separated . '</td><td>' . $v->name . '</td>
				<td>' . $v->sku . '</td>
				<td>' . $v->stock . '</td>
				<td>' . @$v->minimum_stock . '</td>
				<td><span class="text-success">' . $v->currency_type . ' ' . $v->purchase_price . '</span> </td>
				<td width="120px">' . $status . '</td><td>
				<a href="' . company_url('product/view/' . $v->pro_id) . '" class="btn btn-warning" data-toggle="tooltip" title="View">
				<i class="mdi mdi-eye"></i>
				</a>
				<a href="' . company_url('product/edit/' . $v->pro_id) . '" class="btn btn-success" data-toggle="tooltip" title="Edit">
				<i class="mdi mdi-pencil"></i>
				</a>
				<button  type="button" class="btn btn-success"  data-toggle="modal" data-target="#GSCCModal' . $v->pro_id . '" title="Add to catalog">
				<i class="mdi mdi-plus"></i>
				</button>
				</td>
				</tr>';
            }
            $output .= '<input type="hidden" id="totalordcount" value="' . count($chproductlist) . '">';
        } else {
            $output .= '<tr class="text-center"><td colspan="11">
			No data available
			</td></tr>';
            $output .= '<input type="hidden" id="totalordcount" value="' . count($chproductlist) . '">';
        }
        return $output;
    }

    function getAllcompany($offset = 0, $limit = 40, $table = false)
    {
        if ($table) {
            $this->table = $table;
        }
        $this->db->order_by('company_id', 'DESC');
        $this->db->limit($limit, $offset);
        $this->db->where('status', '1');
        $this->db->where('createdby_comp_id', '0');
        $rest = $this->db->get($this->table);
        $data['results'] = $rest->result();
        $this->db->where('createdby_comp_id', '0');
        $this->db->where('status', '1');
        $restnum = $this->db->get($this->table);
        $data['total'] = $restnum->num_rows();
        //echo $this->db->last_query();die;
        return $data;
    }

    public function marketplacefetchProductsdata($categoryId = false, $companyId = false)
    {
        if ($categoryId != '') {
            $searchcatSql = "SELECT p.pro_id,p.category_id, p.name, p.sku, p.sale_price,p.stock, p.currency_type, (SELECT img.name FROM product_images AS pimg JOIN images AS img ON img.uuid = pimg.image_id WHERE pimg.product_id = p.pro_id LIMIT 1) AS productImage
			FROM products AS p WHERE p.company_id='" . $companyId . "'AND p.status ='1' AND FIND_IN_SET('" . $categoryId . "',p.category_id) ORDER BY p.pro_id DESC ";


            $chproductlist = $this->db->query($searchcatSql)->result();
            $output = '';
            if (count($chproductlist) > 0) {
                $i = 0;
                foreach ($chproductlist as $v) {
                    $i++;

                    $output .= '<tr role="row">
					<td>' . $i . '</td> <td class="proimg">';
                    if ($v->productImage != "") {
                        $output .= '<img src="' . base_url(
                                'uploads/products/'
                            ) . $v->productImage . '" class="img-responsive" alt="" style="width: 120px; max-height: 100px;">';
                    } else {
                        $output .= '<img src="' . base_url(
                                'uploads/noimg.png'
                            ) . '" class="img-responsive" alt="" style="width: 120px; max-height: 120px;">';
                    }
                    $output .= '</td><td>' . $v->name . '</td>
					<td>' . $v->sku . '</td>
					<td>' . $v->stock . '</td>
					<td><span class="text-success">' . $v->currency_type . ' ' . $v->sale_price . '</span> </td>
					<td>
					<a href="' . base_url('products/details/' . $v->pro_id) . '" class="btn btn-secondary btn-sm">View Details</a>
					</td>
					</tr>';
                }
            } else {
                $output .= '<tr class="text-center"><td colspan="7">
				No data available
				</td></tr>';
            }
            return $output;
        }
    }

    function getAllcompanyProduct($offset = 0, $limit = 40, $table = false, $categoryId = false)
    {
        if ($table) {
            $this->table = $table;
        }

        $getres = $this->db->query(
            "SELECT p.pro_id,p.category_id,p.name,p.sku, p.sale_price, p.stock, p.status, p.currency_type, (SELECT img.name FROM product_images AS pimg JOIN images AS img ON img.uuid = pimg.image_id WHERE pimg.product_id = p.pro_id LIMIT 1) AS productImage FROM products AS p WHERE p.status ='1' AND FIND_IN_SET('" . $categoryId . "',p.category_id) ORDER BY p.pro_id DESC LIMIT " . $offset . "," . $limit . ""
        );
        $data['results'] = $getres->result();
        $data['total'] = $this->db->query(
            "SELECT p.pro_id,p.category_id,p.name,p.sku, p.sale_price, p.stock, p.status, p.currency_type, (SELECT img.name FROM product_images AS pimg JOIN images AS img ON img.uuid = pimg.image_id WHERE pimg.product_id = p.pro_id LIMIT 1) AS productImage FROM products AS p WHERE p.status ='1' AND FIND_IN_SET('" . $categoryId . "',p.category_id) ORDER BY p.pro_id DESC"
        )->num_rows();
        return $data;
    }

    public function fetchproductIds($prids = false, $compId = false)
    {
        $cat = "";
        foreach ($prids as $prid) {
            $prdetl = $this->mymodel->get_by('products', true, array('pro_id' => $prid));
            $catids = explode(",", $prdetl->category_id);
            $catname = [];
            foreach ($catids as $catid) {
                $ctdetl = $this->mymodel->get_by('categories', true, array('category_id' => $catid));
                $catname[] = @$ctdetl->category_name;
            }
            $comma_separated = implode(",", $catname);

            $prquery = "SELECT '" . $comma_separated . "',pd.sku,pd.name AS prname,pd.barcode,(SELECT u.unit FROM units u WHERE u.unit_id = pd.unit_id LIMIT 1) as unit_type,pd.sale_price,pd.fob_price,pd.purchase_price,pd.stock,pd.minimum_stock,pd.notify_minimum_stock,pd.tags,pd.descriptions,pd.locations,pd.status,DATE_FORMAT(pd.created_on,'%Y-%m-%d') as prdate FROM products AS pd  WHERE pd.company_id='" . $compId . "' AND pd.pro_id='" . intval(
                    $prid
                ) . "'";
            $usersProductData[] = $this->db->query($prquery)->row_array();
        }
        return $usersProductData;
    }


    public function fetchcustomersIds($crids = false, $compId = false)
    {
        foreach ($crids as $crid) {
            $query = "SELECT name,business_name,tax_id,discount,term_id,group_id,email,phone,cell_phone,user_id,notes,commercial_address,commercial_delivery_address,commercial_country,commercial_state,
					commercial_city,commercial_zone,commercial_zip_code,dispatch_address,dispatch_delivery_address,
					dispatch_country,dispatch_state,dispatch_city,dispatch_zone,dispatch_zip_code,dispatch_shipping_notes,catalog_emails,customer_status,DATE_FORMAT(customer_created_at,'%Y-%m-%d') FROM customers WHERE customer_id='" . $crid . "' AND company_id='" . $compId . "'";
            $usersCustomerData[] = $this->db->query($query)->row_array();
        }
        return $usersCustomerData;
    }

    public function customerIds($companyId)
    {
        $query = "SELECT ord.customer_id,cu.customer_id,cu.business_name,cu.email,cu.phone FROM orders AS ord JOIN customers AS cu ON cu.customer_id=ord.customer_id  WHERE ord.company_id='" . $companyId . "' GROUP BY ord.customer_id";
        $usersData = $this->db->query($query)->result_array();
        return $usersData;
    }

    public function fetch_product_data($companyId)
    {
        $query = "SELECT pd.sku,pd.name AS prname,pd.sale_price,ct.category_name,pd.barcode,pd.tags,pd.descriptions,pd.stock,pd.minimum_stock,pd.fob_price,pd.purchase_price,DATE_FORMAT(pd.created_on,'%Y-%m-%d') as prdate FROM products AS pd JOIN categories AS ct ON ct.cat_id=pd.category_id  WHERE pd.company_id='" . $companyId . "'";
        $usersData = $this->db->query($query)->result_array();
        return $usersData;
    }

    public function fetch_catalog_data($catlogId)
    {
        $query = "SELECT ct.catalog_name,ct.cat_id,ct.product_name,ce.customer_emails,DATE_FORMAT(ce.send_date,'%Y-%m-%d') FROM catalog AS ct  JOIN catalog_sent_mail AS ce ON ct.catalog_id=ce.catalog_id  WHERE ct.catalog_id='" . $catlogId . "'";
        $usersData = $this->db->query($query)->result_array();
        return $usersData;
    }

    // Buscar al presionar teclado
    public function buscar_filtro_general(
        $prsearch = null,
        $compId,
        $rrURL = null,
        $prdstatus = null,
        $categoryId = null,
        $filter_photo = null,
        $tag,
        $ubicacion,
        $catalogo,
        $order_type,
        $order_sort,
        $info = null,
        $show_per_page,
        $offset
    ) {
        $version = substr($this->Helper->get_codhash256(), 0, 6);
        $noimg = base_url('uploads/noimg.png') . "?v=$version";
        // echo $noimg . '<br>';
        $consulta_adicional = '';
        $searchcatSql = '';
        if ($catalogo != 'todo') {
            $searchcatSql = "SELECT
						DISTINCT(p.sku),p.notify_minimum_stock,p.fob_price,p.image_updated,p.tags,p.descriptions,
						p.unit_id,p.locations,p.pro_id,p.category_id,p.name,p.catalog_name,p.company_catalog_id,p.catalog_id,
						p.catalog_categories,p.catalog_producto,p.minimum_stock,p.sale_price,p.purchase_price,p.barcode,p.company_id,
						p.stock,p.stock,p.`status`,p.currency_type,p.image_default,0 as is_there_images,p.imagen_por_defecto,
						p.qty_images,p.group_images
						FROM (SELECT  null as product_adicional,p.*,g.qty_images,g.group_images,c.catalog_name,c.company_id company_catalog_id,c.catalog_id,
						c.cat_id catalog_categories,c.product_name catalog_producto,
						'$noimg' as image_default,
						'' AS imagen_por_defecto
						FROM products as p
						INNER JOIN catalog as c ON CONCAT(',',p.category_id,',')
						REGEXP CONCAT(',(',REPLACE(c.cat_id,',','|'),'),')
						LEFT join get_imagesproducts as g on g.product_id = p.pro_id
						WHERE c.company_id = '$compId' AND c.catalog_id = '$catalogo'
						UNION SELECT '1' as product_adicional, p.*,g.qty_images,g.group_images,c.catalog_name,c.company_id company_catalog_id,c.catalog_id,
						c.cat_id catalog_categories,c.product_name catalog_producto,'$noimg' as image_default,
						'' AS imagen_por_defecto FROM products as p
						INNER JOIN catalog as c ON FIND_IN_SET(p.pro_id,c.product_name)
						left join get_imagesproducts as g on g.product_id = p.pro_id
						WHERE c.company_id = '$compId' AND c.catalog_id = '$catalogo') p";
        } else {
            $searchcatSql = "SELECT null as product_adicional,p.notify_minimum_stock,p.image_updated,
					p.fob_price,p.descriptions,p.unit_id,p.tags,p.locations,p.pro_id,p.category_id,
					p.name,p.minimum_stock, p.sku, p.sale_price, p.purchase_price, p.barcode,
					p.company_id, p.stock, p.status, p.currency_type , '$noimg' as image_default,
					'' AS imagen_por_defecto,0 as is_there_images,g.qty_images,g.group_images,
					(SELECT curr.symbol
					FROM currency as curr WHERE curr.currency_id = p.currency_type LIMIT 1) as currInf
					FROM products AS p
					left join get_imagesproducts as g on g.product_id = p.pro_id
					WHERE p.company_id ='$compId'";
        }

        //CONSULTA ADICIONAL
        $consulta_adicional .= $this->consulta_adicional(
            $compId,
            $catalogo,
            $categoryId,
            $prdstatus,
            $prsearch,
            $tag,
            $ubicacion,
            $filter_photo,
            $order_type,
            $order_sort,
            $show_per_page,
            $offset
        );
        $searchcatSql .= $consulta_adicional;

        $chproductlist = $this->db->query($searchcatSql)->result();
        $rrURL = false;

        $chproductlist_validado = [];
        //FILTRO CON FOTO O SIN FOTO FALTA AGREGAR
        return $chproductlist;
        /*return $this->obtener_filas($chproductlist_validado,$rrURL,$info);*/
    }

    public function consulta_adicional(
        $compId,
        $catalogo,
        $categoryId,
        $prdstatus,
        $prsearch,
        $tag,
        $ubicacion,
        $filter_photo,
        $order_type,
        $order_sort,
        $show_per_page,
        $offset
    ) {
        $consulta_adicional = '';
        if ($catalogo != 'todo') {
            $consulta_adicional .= " WHERE p.company_id= '$compId'";
        }

        if ($categoryId != "" && is_numeric($categoryId) && $categoryId != "vacio" && $categoryId != "todo") {
            $consulta_adicional .= " AND FIND_IN_SET('" . $categoryId . "',p.category_id)";
        } else {
            if ($categoryId == 'vacio') {
                $consulta_adicional .= " AND (p.category_id is NULL OR p.category_id = '')";
            }
        }


        switch ($prdstatus) {
            case '0':
                $consulta_adicional .= " AND p.status='0'";
                break;
            case '1':
                $consulta_adicional .= " AND p.status='1'";
                break;
            case '3':
                $consulta_adicional .= "";
                break;
        }
        if ($prsearch != '') {
            $consulta_adicional .= " AND (p.name LIKE '%$prsearch%' OR (p.sku LIKE '%$prsearch%' OR p.sku LIKE '%$prsearch' OR p.sku LIKE '$prsearch%') OR p.pro_id LIKE '%$prsearch')";
        }
        if ($tag != '') {
            $consulta_adicional .= " AND (p.tags LIKE '%$tag%')";
        }
        if ($ubicacion != '') {
            $consulta_adicional .= " AND (p.locations LIKE '%$ubicacion%')";
        }

        if ($filter_photo == 'with_photo_whithout_photo') {
            $consulta_adicional .= "";
        } else {
            if ($filter_photo == 'whith_photo') {
                $consulta_adicional .= " AND group_images is not null ";
            } else {
                $consulta_adicional .= " AND group_images is null ";
            }
        }

        if ($order_type != '' && $order_sort != '') {
            switch ($order_sort) {
                case 'id':
                    $consulta_adicional .= " ORDER BY p.pro_id " . $order_type;
                    break;
                case 'sku':
                    $consulta_adicional .= " ORDER BY p.sku " . $order_type;
                    break;
                case 'name':
                    $consulta_adicional .= " ORDER BY p.name " . $order_type;
                    break;
                case 'price':
                    $consulta_adicional .= " ORDER BY p.sale_price " . $order_type;
                    break;
                case 'stock':
                    $consulta_adicional .= " ORDER BY p.stock " . $order_type;
                    break;
                case 'moq':
                    $consulta_adicional .= " ORDER BY p.minimum_stock " . $order_type;
                    break;
                case 'tags':
                    $consulta_adicional .= " ORDER BY p.tags " . $order_type;
                    break;
                case 'location':
                    $consulta_adicional .= " ORDER BY p.locations " . $order_type;
                    break;
            }
        } else {
            $consulta_adicional .= " ORDER BY p.pro_id DESC";
        }


        if ($show_per_page == 'sin_limite' && $offset == 'sin_limite') {
        } else {
            $consulta_adicional .= " LIMIT " . $show_per_page . " OFFSET " . $offset;
        }
        return $consulta_adicional;
    }

    public function obtener_cantidad(
        $prsearch = null,
        $compId,
        $rrURL = null,
        $prdstatus = null,
        $categoryId = null,
        $filter_photo = null,
        $tag,
        $ubicacion,
        $catalogo,
        $info = null,
        $show_per_page,
        $offset
    ) {
        $consulta_adicional = '';
        $searchcatSql = '';
        if ($catalogo != 'todo') {
            $consulta_adicional .= " WHERE p.company_id= '$compId'";
            $searchcatSql = "SELECT
						DISTINCT(p.sku),p.notify_minimum_stock,p.fob_price,p.image_updated,p.tags,p.descriptions,
						p.unit_id,p.locations,p.pro_id,p.category_id,p.name,p.catalog_name,p.company_catalog_id,p.catalog_id,
						p.catalog_categories,p.catalog_producto,p.minimum_stock,p.sale_price,p.purchase_price,p.barcode,p.company_id,
						p.stock,p.stock,p.`status`,p.currency_type,
						p.group_images
						FROM (SELECT  null as product_adicional,p.*,g.group_images,c.catalog_name,c.company_id company_catalog_id,c.catalog_id,
						c.cat_id catalog_categories,c.product_name catalog_producto
						FROM products as p
						INNER JOIN catalog as c ON CONCAT(',',p.category_id,',')
						REGEXP CONCAT(',(',REPLACE(c.cat_id,',','|'),'),')
						LEFT join get_imagesproducts as g on g.product_id = p.pro_id
						WHERE c.company_id = '$compId' AND c.catalog_id = '$catalogo'

						UNION SELECT '1' as product_adicional, p.*,g.group_images,c.catalog_name,c.company_id company_catalog_id,c.catalog_id,
						c.cat_id catalog_categories,c.product_name catalog_producto
						FROM products as p
						INNER JOIN catalog as c ON FIND_IN_SET(p.pro_id,c.product_name)
						left join get_imagesproducts as g on g.product_id = p.pro_id
						WHERE c.company_id = '$compId' AND c.catalog_id = '$catalogo') p";
        } else {
            $searchcatSql = "SELECT null as product_adicional,p.notify_minimum_stock,p.image_updated,
					p.fob_price,p.descriptions,p.unit_id,p.tags,p.locations,p.pro_id,p.category_id,
					p.name,p.minimum_stock, p.sku, p.sale_price, p.purchase_price, p.barcode,
					p.company_id, p.stock, p.status, p.currency_type ,
					g.group_images,
					(SELECT curr.symbol
					FROM currency as curr WHERE curr.currency_id = p.currency_type LIMIT 1) as currInf
					FROM products AS p
					left join get_imagesproducts as g on g.product_id = p.pro_id
					WHERE p.company_id ='$compId'";
        }

        if ($categoryId != "" && is_numeric($categoryId) && $categoryId != "vacio" && $categoryId != "todo") {
            $consulta_adicional .= " AND FIND_IN_SET('" . $categoryId . "',p.category_id)";
        } else {
            if ($categoryId == 'vacio') {
                $consulta_adicional .= " AND (p.category_id is NULL OR p.category_id = '')";
            }
        }

        switch ($prdstatus) {
            case '0':
                $consulta_adicional .= " AND p.status='0'";
                break;
            case '1':
                $consulta_adicional .= " AND p.status='1'";
                break;
            case '3':
                $consulta_adicional .= "";
                break;
        }

        if ($prsearch != '') {
            $consulta_adicional .= " AND (p.name LIKE '%$prsearch%' OR p.sku LIKE '%$prsearch%' OR p.pro_id LIKE '%$prsearch%')";
        }

        if ($tag != '') {
            $consulta_adicional .= " AND (p.tags LIKE '%$tag%')";
        }
        if ($ubicacion != '') {
            $consulta_adicional .= " AND (p.locations LIKE '%$ubicacion%')";
        }

        if ($filter_photo == 'with_photo_whithout_photo') {
            $consulta_adicional .= "";
        } else {
            if ($filter_photo == 'whith_photo') {
                $consulta_adicional .= " AND group_images is not null ";
            } else {
                $consulta_adicional .= " AND group_images is null ";
            }
        }

        $searchcatSql .= $consulta_adicional;
        $chproductlist = $this->db->query($searchcatSql)->num_rows();
        $rrURL = false;
        return $chproductlist;
    }

    public function obtener_valores_formato()
    {
        $compId = $this->session->userdata('compId');
        $company = $this->mymodel->get_by('companies', true, array('company_id' => $compId));
        $currency_format = $this->mymodel->get_by(
            'currency_formats',
            true,
            array('format_id' => $company->currency_code_id)
        );

        $codigo_regional = $currency_format->CodeRegional;

        $grupo = null;
        $separator = null;
        $decimal_point = null;
        $array_list = array();
        $locale_info = null;

        if ($codigo_regional != null) {
            $fmt = new NumberFormatter($codigo_regional, NumberFormatter::DECIMAL);
            $numero_ejemplo = $fmt->format(25556746.25);
            $grupo = $fmt->getAttribute(NumberFormatter::SECONDARY_GROUPING_SIZE);
            if ($grupo == 0) {
                $grupo = 3;
            }
            $separator = $fmt->getSymbol(NumberFormatter::GROUPING_SEPARATOR_SYMBOL);
            setlocale(LC_ALL, $codigo_regional);
            $locale_info = localeconv();
            $decimal_point = $locale_info['decimal_point'];
        }
        if ($separator === '.') {
            $decimal_point = ',';
        }

        $espacio = false;
        if ($separator !== '.' && $separator !== ',' && $separator !== "'") {
            $espacio = true;
        }

        if ($espacio) {
            if (strlen(strstr($numero_ejemplo, ',')) > 0) {
                $decimal_point = ',';
            }
        }

        $decimal_point = ($decimal_point == null) ? '.' : $decimal_point;

        array_push(
            $array_list,
            [
                'grupo' => $grupo,
                'decimal_point' => $decimal_point,
                'separador' => $separator,
                'tiene_codigoRegional' => $this->tiene_codeRegional(),
                'codigoRegional' => $codigo_regional,
                'espacio' => $espacio,
                'lacale_conv' => $locale_info
            ]
        );
        return $array_list;
    }

    public function tiene_codeRegional()
    {
        $compId = $this->session->userdata('compId');
        $company = $this->mymodel->get_by('companies', true, array('company_id' => $compId));
        $currency_format = $this->mymodel->get_by(
            'currency_formats',
            true,
            array('format_id' => $company->currency_code_id)
        );

        $codigo_regional = $currency_format->CodeRegional;
        $tiene = false;
        if ($codigo_regional != null) {
            $tiene = true;
        }
        return $tiene;
    }

    //DESFORMATEAR NUMERO
    public function convertir_textAdouble($precio)
    {
        $compId = $this->session->userdata('compId');
        $company = $this->mymodel->get_by('companies', true, array('company_id' => $compId));
        $currency_format = $this->mymodel->get_by(
            'currency_formats',
            true,
            array('format_id' => $company->currency_code_id)
        );

        $codigo_regional = $currency_format->CodeRegional;
        if ($codigo_regional != null) {
            $fmt = new NumberFormatter($codigo_regional, NumberFormatter::DECIMAL);
            $precio = $fmt->parse($precio);
        }
        return $precio;
    }

    public function obtener_pais_company()
    {
        $compId = $this->session->userdata('compId');
        $company = $this->mymodel->get_by('companies', true, array('company_id' => $compId));
        $country_company_id = $company->country;
        $country = $this->mymodel->get_by('countries', true, array('id' => $country_company_id));
        return $country;
    }

    public function obtener_countries()
    {
        $countries = $this->db->query("select * from countries order by countries.name ASC")->result();
        return $countries;
    }

    public function obtener_productos()
    {
        $compId = $this->session->userdata('compId');
        $products = $this->db->query(
            "select * from products WHERE products.company_id = '$compId' AND products.status=1 order by products.name ASC"
        )->result();
        return $products;
    }


    public function obtener_configuracion_paginacion($base_url, $total_rows, $show_per_page)
    {
        $langId = $this->session->userdata('language_id');
        $info['langprods'] = $this->langmodel->getMdl('cmp-products', $langId);

        $config['base_url'] = $base_url;
        $config['num_links'] = 2;
        $config['uri_segment'] = 4;
        $config['total_rows'] = $total_rows;
        $config['per_page'] = $show_per_page;
        $config['full_tag_open'] = '<ul class="pagination pagination-sm">';
        $config['full_tag_close'] = '</ul>';
        $config['num_tag_open'] = '<li>';
        $config['num_tag_close'] = '</li>';
        $config['first_link'] = $info['langprods']['pagination_first'];
        $config['first_tag_open'] = '<li>';
        $config['first_tag_close'] = '</li>';
        $config['last_link'] = $info['langprods']['pagination_last'];
        $config['last_tag_open'] = '<li>';
        $config['last_tag_close'] = '</li>';
        $config['prev_link'] = $info['langprods']['pagination_prev'];
        $config['prev_tag_open'] = '<li>';
        $config['prev_tag_close'] = '</li>';
        $config['next_link'] = $info['langprods']['pagination_next'];
        $config['next_tag_open'] = '<li>';
        $config['next_tag_close'] = '</li>';
        $config['cur_tag_open'] = '<li class="active"><a href="#">';
        $config['cur_tag_close'] = '</a></li>';
        $config['use_page_numbers'] = true;
        $config['use_page_numbers'] = true;
        $config['page_query_string'] = true;
        $config['query_string_segment'] = 'page';
        $config['reuse_query_string'] = true;

        return $config;
    }

    public function generatePassword($length)
    {
        $key = "";
        $pattern = "1234567890abcdefghijklmnopqrstuvwxyz";

        $max = strlen($pattern) - 1;
        for ($i = 0; $i < $length; $i++) {
            $key .= substr($pattern, mt_rand(0, $max), 1);
        }

        return $key;
    }

    public function encrypt_decrypt($action, $string): string
    {
        $output = false;
        $encrypt_method = "AES-256-CBC";
        $secret_key = 'tu_clave_secreta';
        $secret_iv = 'salt_secreto';

        $key = hash('sha256', $secret_key);

        $iv = substr(hash('sha256', $secret_iv), 0, 16);
        if ($action == 'encrypt') {
            $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
            $output = base64_encode($output);
        } else {
            if ($action == 'decrypt') {
                $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
            }
        }
        return $output;
    }


    public function generar_token()
    {
        $response = $this->post('catalogcart/legacy-token', [], false);
        if ($this->hasError($response)) {
            throw new \RuntimeException('catalog_token_generation_failed');
        }

        $data = is_object($response) ? $response : (object) $response;
        if (isset($data->data)) {
            $data = is_object($data->data) ? $data->data : (object) $data->data;
        }

        return [
            'password_generado' => (string) ($data->password_generado ?? ''),
            'password_encrypt' => (string) ($data->password_encrypt ?? ''),
        ];
    }

    public function validar_multiplo($numero, $multiplo)
    {
        $count = $numero;
        $msg = null;
        if (($count % $multiplo) == 0) {
            $msg = true;
        } else {
            $msg = false;
        }
        return $msg;
    }

    public function creat_fullvendorcatalog($company_id = null)
    {
        $compId = $company_id ? $company_id : $this->session->userdata('compId');
        if ($compId === null || $compId === '') {
            return;
        }
        $categorias = $this->mymodel->get_by('categories', false, array('company_id' => $compId, 'category_status' => 1)
        );
        $list_idcategs = array();

        foreach ($categorias as $cat) {
            array_push($list_idcategs, $cat->cat_id);
        }

        $categories_unidas = implode(',', $list_idcategs);
        $catalogo_fullvendor = $this->mymodel->get_by(
            'catalog',
            true,
            array('company_id' => $compId, 'default_catalog' => 1)
        );
        $categoria_default = $this->mymodel->get_by(
            'categories',
            true,
            array('company_id' => $compId, 'category_default' => 1)
        );

        if ($catalogo_fullvendor != null) {
            //actualizamos
            $mydata = array('cat_id' => $categories_unidas);
            $this->mymodel->update(
                $mydata,
                'catalog',
                ['company_id' => $compId, 'catalog_id' => $catalogo_fullvendor->catalog_id]
            );

            $this->Helper->updateorder_catalogo($catalogo_fullvendor, $categoria_default);
        } else {
            //creamos
            $mydata = array(
                'cat_id' => $categories_unidas,
                'company_id' => $compId,
                'catalog_name' => 'FullVendor Catalog',
                'status' => 1,
                'created' => date("Y-m-d H:i:s"),
                'default_catalog' => 1
            );

            // 2.Creas el ordern categorias del catalogo por defecto
            $this->mymodel->insert("catalog", $mydata);
            $catalogo_fullvendor = $this->mymodel->get_by(
                'catalog',
                true,
                array('company_id' => $compId, 'default_catalog' => 1)
            );
            $this->Helper->updateorder_catalogo($catalogo_fullvendor, $categoria_default);
        }
    }


    public function calcular_total_ajax($order_id)
    {
        $lista_completa = $this->getObjectFromApi("order/{$order_id}/total-details");

        $valores_formato = $this->obtener_valores_formato()[0];

        if ($valores_formato['tiene_codigoRegional']) {
            $codigo_regional = $valores_formato['codigoRegional'];
            $fmt = new NumberFormatter($codigo_regional, NumberFormatter::DECIMAL);
            $fmt->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 2);
            $fmt->setAttribute(NumberFormatter::CURRENCY_SYMBOL, 2);

            $lista_completa->discount_total = $fmt->format($lista_completa->discount_total);
            $lista_completa->sub_total = $fmt->format($lista_completa->sub_total);
            $lista_completa->total_orden = $fmt->format($lista_completa->total_orden);
        }

        return $lista_completa;
    }


    public function obtener_status_orders($langId)
    {
        $consulta = null;
        switch ($langId) {
            case 1:
                $consulta = 'SELECT name_status_english as name_status,cod_status,color,disabled,tipo_d FROM status_orders ORDER BY name_status_english ASC';
                break;
            case 2:
                $consulta = 'SELECT name_status_spanish as name_status,cod_status,color,disabled,tipo_d FROM status_orders ORDER BY name_status_spanish ASC';
                break;
            default:
                $consulta = 'SELECT name_status_english as name_status,cod_status,color,disabled,tipo_d FROM status_orders ORDER BY name_status_english ASC';
                break;
        }

        $result = $this->db->query($consulta)->result();
        return $result;
    }

    public function obtener_status_ordersWarehouse($langId)
    {
        $consulta = null;
        switch ($langId) {
            case 1:
                $consulta = 'SELECT name_status_english as name_status,cod_status,color,disabled,can_show_data_asigned FROM status_orders WHERE cod_status IN(9,11,12) ORDER BY name_status_english ASC';
                break;
            case 2:
                $consulta = 'SELECT name_status_spanish as name_status,cod_status,color,disabled,can_show_data_asigned FROM status_orders WHERE cod_status IN(9,11,12) ORDER BY name_status_spanish ASC';
                break;
            default:
                $consulta = 'SELECT name_status_english as name_status,cod_status,color,disabled,can_show_data_asigned FROM status_orders WHERE cod_status IN(9,11,12) ORDER BY name_status_english ASC';
                break;
        }

        $result = $this->db->query($consulta)->result();
        return $result;
    }


    public function obtener_detailOrder($order_id)
    {
        $compId = $this->session->userdata('compId');

        $order = $this->mymodel->get_by('orders', true, ['order_id' => $order_id]);


        $consulta = '';

        $consulta = "SELECT p.*, o.*,o.sale_price as precio_cliente,(SELECT formato from units where unit_id=p.unit_id and company_id='$compId') as formato_unidad,(o.qty * o.sale_price) as total_importe,(o.delivered_quantity * o.sale_price) as total_importe_asignado,(SELECT img.name FROM product_images AS pimg JOIN images AS img ON img.uuid = pimg.image_id WHERE pimg.product_id=p.pro_id ORDER BY pimg.img_order ASC LIMIT 1) as imagen_principal,
			(SELECT img.name FROM product_images AS pimg JOIN images AS img ON img.uuid = pimg.image_id WHERE pimg.product_id = p.pro_id ORDER BY pimg.img_order ASC LIMIT 1) AS imagen_por_defecto,(SELECT `name` from customer_groups where group_id=(SELECT group_id from customers where customer_id = (SELECT customer_id FROM orders WHERE order_id='$order_id'))) as grupo_nombre,(SELECT percentage_on_price from customer_groups where group_id=(SELECT group_id from customers where customer_id = (SELECT customer_id FROM orders WHERE order_id= '$order_id'))) as percentage_on_price  FROM order_details as o INNER JOIN products as p ON o.product_id=p.pro_id where o.order_id = '$order_id' ORDER BY o.detail_id ASC";


        $result = $this->db->query($consulta)->result();
        return $result;
    }


    public function cumple_formato($number_toformatm, $number_format)
    {
        if (strlen(intval(preg_replace('/[^0-9]+/', '', $number_toformatm), 10)) == strlen(
                intval(preg_replace('/[^0-9]+/', '', $number_format), 10)
            )) {
            return true;
        }

        return false;
    }

        public function get_value_format($numberToFormat){

            if (is_numeric($numberToFormat)) {
              return (float)$numberToFormat;
            }

        $this->ensureCompanyContext();
        $fmt = new NumberFormatter($this->format_numeric->code_regional ?? 'en_US', NumberFormatter::DECIMAL);
            $number_format = $fmt->parse($numberToFormat);

        return $number_format;
    }

    public function format_numerictext($number_toformat)
    {
        $this->ensureCompanyContext();
        return number_format(
            $number_toformat,
            $this->format_numeric->digits ?? 2,
            $this->format_numeric->radixPoint ?? '.',
            $this->format_numeric->groupSeparator ?? ','
        );
    }

    public function implode_key($glue, $arr, $key)
    {
        $arr2 = array();
        foreach ($arr as $f) {
            if (!isset($f[$key])) {
                continue;
            }
            $arr2[] = $f[$key];
        }
        return implode($glue, $arr2);
    }

    public function update_orderdetails($order_id)
    {
        $query = "update order_details od INNER JOIN orders om ON om.order_id = od.order_id
			          set od.amount_sales = od.qty * od.sale_price,
					  od.discount_amount = (od.qty * od.sale_price) * (IF(om.discount>0,om.discount/100,0)),
					  od.total_amount = (od.qty * od.sale_price)-((od.qty * od.sale_price) * (IF(om.discount>0,om.discount/100,0))),
					  od.amount_delivered = od.delivered_quantity * od.sale_price,
					  od.discount_delivered = (od.delivered_quantity * od.sale_price)*(IF(om.discount>0,om.discount/100,0)),
					  od.total_delivered = (od.delivered_quantity * od.sale_price)-((od.delivered_quantity * od.sale_price)*(IF(om.discount>0,om.discount/100,0))),
					  od.amount = IF(od.delivered_quantity <> 0, od.delivered_quantity * od.sale_price, od.qty * od.sale_price),
					  od.discountt = IF(od.delivered_quantity <> 0, (od.qty * od.sale_price)*(IF(om.discount>0,om.discount/100,0)),
					  	(od.delivered_quantity * od.sale_price)*(IF(om.discount>0,om.discount/100,0))),
					  od.total = IF(od.delivered_quantity <> 0, (od.delivered_quantity * od.sale_price)-((od.delivered_quantity * od.sale_price) * (IF(om.discount>0,om.discount/100,0))),
					     (od.qty * od.sale_price)-((od.qty * od.sale_price)*(IF(om.discount>0,om.discount/100,0))))
					WHERE od.order_id = $order_id";
        $this->db->query($query);
    }

    public function update_caborder($order_id)
    {
        $query = "UPDATE orders o INNER JOIN rs_orders rs  ON rs.order_id = o.order_id
			         SET o.amount = rs.amount,
					 o.discount_a = rs.discount,
					 o.total_amount = rs.total_amount,
					 o.amount_delivered = rs.amount_delivered,
					 o.discount_delivered = rs.discount_delivered,
					 o.total_delivered = rs.total_delivered WHERE o.order_id = $order_id;";

        $this->db->query($query);
    }

    public function update_order($order_id)
    {
        $this->update_orderdetails($order_id);
        $this->update_caborder($order_id);
    }


    public function obtener_status_orders_withstatus($langId, $status_required)
    {
        $status = implode("','", $status_required);
        $consulta = null;
        switch ($langId) {
            case 1:
                $consulta = "SELECT name_status_english as name_status,cod_status,color,disabled FROM status_orders WHERE cod_status IN ('$status') ORDER BY name_status_english ASC";
                break;
            case 2:
                $consulta = "SELECT name_status_spanish as name_status,cod_status,color,disabled FROM status_orders WHERE cod_status IN ('$status') ORDER BY name_status_spanish ASC";
                break;
            default:
                $consulta = "SELECT name_status_english as name_status,cod_status,color,disabled FROM status_orders WHERE cod_status IN ('$status') ORDER BY name_status_english ASC";
                break;
        }

        $result = $this->db->query($consulta)->result();
        return $result;
    }

    public function getcurrencies_father()
    {
        $currencies_pather = $this->db->query("SELECT GroupID FROM currencybycountries GROUP BY GroupID")->result();
        $currency_all = $this->db->query("SELECT * FROM currencybycountries")->result();

        foreach ($currencies_pather as $cur) {
            $cur->currencies = array();
        }

        foreach ($currency_all as $cur_child) {
            if (in_array($cur_child->GroupID, array_column($currencies_pather, "GroupID"))) {
                $index = array_search($cur_child->GroupID, array_column($currencies_pather, 'GroupID'));
                array_push($currencies_pather[$index]->currencies, $cur_child);
            }
        }

        return $currencies_pather;
    }

    public function actualizar_Currency()
    {
    }

    public function show_sessionmsg($msg, $type)
    {
        $msg = '["' . $msg . '.", "' . $type . '", "#DD6B55"]';
        $this->session->set_flashdata('msg', $msg);
    }

    /**
     * @param string $lang
     */
    public function get_dictionary($lang=null)
    {
        if(!isset($lang)) {
            $compId = $this->session->userdata('compId');
            $company = $this->getObjectFromApi("company/{$compId}", [
                "relations" => ["language"]
            ]);
            $lang = $company->language->type;
        }

        $modul_pather = $this->getDataFromApi("langmodules?locale={$lang}");
        $result = array();

        foreach ($modul_pather as $mdl) {
            $name = $mdl;
            $mdlLang = $this->langmodel->getMdl($name);
            $mdl = str_replace('-', '_', $mdl);
            $result[$mdl] = array(
                "name" => $name,
                "lang" => $mdlLang
            ); // Asignar una nueva clave y un array vacío al nuevo array
        }

        return $result;
    }
}

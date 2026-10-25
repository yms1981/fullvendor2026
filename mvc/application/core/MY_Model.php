<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @property CI_DB_query_builder $db
 */
class MY_Model extends CI_Model
{
    use ApiClient;

    protected $key = 'id';

    public function __construct()
    {
        $this->getHttpClient();
    }

    public function get($table, $single = False, $col = NULL, $id = NULL, $limit = NULL, $order_by = NULL)
	{
		if (!$col == NULL && !$id == NULL) {
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

    public function select(
        $select,
        $table,
        $single = false,
        $where = null,
        $limit = null,
        $order_by = null,
        $type_order = "DESC"
    ) {
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


    public function get_by(
        $table,
        $single = false,
        $where = null,
        $order_by = null,
        $limit = null,
        $type_order = "DESC"
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

        if (!$order_by == null) {
            $this->db->order_by($order_by, $type_order);
        }

        $query = $this->db->get();

        $result = $query->$method();

        return $result;
    }

    function getAll($offset = 0, $limit = 40, $table = false, $company_id = false, $orderby = false)
    {
        if ($table) {
            $this->table = $table;
        }

        $this->db->order_by($orderby, 'DESC');
        $this->db->where('company_id', $company_id);
        $this->db->limit($limit, $offset);
        $rest = $this->db->get($this->table);
        $data['results'] = $rest->result();
        $this->db->where('company_id', $company_id);
        $data['total'] = $this->db->get($this->table)->num_rows();
        return $data;
    }

    function getAllproducts($offset = 0, $limit = 40, $table = false, $company_id = false, $orderby = false)
    {
        if ($table) {
            $this->table = $table;
        }
        $this->db->order_by($orderby, 'DESC');
        $this->db->where('company_id', $company_id);
        $this->db->where('status', 1);
        $this->db->limit($limit, $offset);
        $rest = $this->db->get($this->table);
        $data['results'] = $rest->result();
        $this->db->where('company_id', $company_id);
        $this->db->where('status', 1);
        $data['total'] = $this->db->get($this->table)->num_rows();
        return $data;
    }

    function getAllcompanyusers($offset = 0, $limit = 40, $table = false, $company_id = false, $orderby = false)
    {
        if ($table) {
            $this->table = $table;
        }
        $this->db->order_by($orderby, 'DESC');
        $this->db->where('createdby_comp_id', $company_id);
        $this->db->limit($limit, $offset);
        $rest = $this->db->get($this->table);
        $data['results'] = $rest->result();
        $this->db->where('createdby_comp_id', $company_id);
        $data['total'] = $this->db->get($this->table)->num_rows();
        return $data;
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

    public function fetch_array($sql, $single = false)
    {
        $query = $this->db->query($sql);
        $result = $query->result_array();

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

    public function fetch_user($userId)
    {
        if (is_numeric($userId)) {
            $sql = "SELECT firstName, lastName FROM users WHERE userId='" . $userId . "'";

            $query = $this->db->query($sql);
            $result = $query->row();
        } else {
            $sql = "SELECT eshopName FROM eshop WHERE eshopId='" . $userId . "'";
            $query = $this->db->query($sql);
            $result = $query->row();
        }
        return $result;
    }

    public function getCurrencySymbol($currency_code = 'INR')
    {
        $query = $this->db->select('symbol')->where('currency_code', $currency_code)->get('currency');
        $res = $query->row();
        if (!empty($res->symbol)) {
            return (string)$res->symbol;
        } else {
            return 'Rs ';
        }
    }

    public function avgStarRating($productId)
    {
        $sql = "SELECT AVG(starRating) AS avgStarRating FROM product_rating WHERE productId = '$productId'";
        return $this->fetch($sql, true)->avgStarRating;
    }

    public function generateRandomString($length = 8)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    protected function jsonArray($jsonString)
    {
        return json_decode((string)$jsonString, true);
    }

    public function table_exists($tableName)
    {
        return $this->db->table_exists($tableName);
    }

    public function lastId() {
        return $this->db->insert_id();
    }
}

<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class StatusModel extends CI_Model
{
    public $company = array();

    function __construct()
    {
        parent::__construct();
        $this->load->model('CustomerModel');
        $this->company = $this->Helper->get_company_info();
    }


    public function get_status_rol($rol_id)
    {
        $query = "SELECT sr.rol_id,st.name_status_spanish,sr.id_status,st.cod_status FROM status_rol AS sr
                  INNER JOIN status_orders AS st ON sr.id_status = st.id_status
                  WHERE sr.rol_id = $rol_id";
        return $this->db->query($query)->result();
    }

    public function obtener_status_orders($tipo_d = null)
    {
        $consulta = null;
        $id = $this->company->language_id;
        $query_tipo = $tipo_d ? "WHERE tipo_d='$tipo_d'" : "";

        switch ($id) {
            case 1:
                $consulta = "SELECT name_status_english as name_status,cod_status,color,cannot_edit,tipo_d,disabled,send_email,id_status FROM status_orders $query_tipo ORDER BY name_status_english ASC";
                break;
            case 2:
                $consulta = "SELECT name_status_spanish as name_status,cod_status,color,cannot_edit,tipo_d,disabled,send_email,id_status FROM status_orders $query_tipo ORDER BY name_status_spanish ASC";
                break;
            default:
                $consulta = "SELECT name_status_english as name_status,cod_status,color,cannot_edit,tipo_d,disabled,send_email,id_status FROM status_orders $query_tipo ORDER BY name_status_english ASC";
                break;
        }
        return $this->db->query($consulta)->result();
    }


    public function get_status($cod_status = null)
    {
        return $cod_status
            ? $this->mymodel->get_by('status_orders', false, ['tipo_d' => 'D', 'cod_status' => $cod_status])
            : $this->mymodel->get_by('status_orders', false, ['tipo_d' => 'D']);
    }

    public function get_status_emails_company($company_id, $cod_status = null)
    {
        return $cod_status
            ? $this->mymodel->get_by(
                'company_status_order',
                true,
                ['company_id' => $company_id, 'cod_status' => $cod_status]
            )
            : $this->mymodel->get_by('company_status_order', false, ['company_id' => $company_id]);
    }

    public function get_status_email_one($company_id, $cod_status = 0)
    {
        return $this->mymodel->get_by(
            'company_status_order',
            true,
            ['company_id' => $company_id, 'cod_status' => $cod_status]
        );
    }

    public function save_status_email($request)
    {
        $request = $this->Helper->cast($request);
        $this->mymodel->save('company_status_order', [
            "company_id" => $request->company_id,
            "cod_status" => $request->cod_status,
        ]);
    }

    public function update_status_email($data, $id)
    {
        $data = $this->Helper->cast($data);
        $email = empty(trim($data->email)) ? null : $data->email;
        $this->mymodel->update([
                                   "email" => $email,
                               ], 'company_status_order', ["id" => $id]);
    }

    public function active_status_emails($compID)
    {
        $status_emails = $this->StatusModel->get_status_emails_company($compID);

        $status = $this->OrdenModel->obtener_status_orders(null, 'D');
        if (count($status_emails) <= 0) {
            foreach ($status as $key => $s) {
                $request = [
                    "company_id" => $compID,
                    "cod_status" => $s->cod_status,
                ];
                $this->StatusModel->save_status_email($request);
            }
        }
    }

    protected function getBodyMailStatusChanged($status_toChanged, $order, $st_past, $st_new)
    {
        $sub_user = $this->mymodel->get('companies', true, 'company_id', $this->session->userdata('user_logueado'));
        $customer = $this->CustomerModel->getCustomer($order->customer_id);
        $data_email = [
            "lang_order" => $this->HelperLanguage->lang_order(),
            "order_number" => $order->order_number,
            "customer" => $customer->business_name,
            "status_past" => $st_past,
            "status_new" => $st_new,
            "sub_user" => $sub_user->email,
            "sub_user_name" => $sub_user->short_name,
        ];
        return $this->load->view('company/order/template_email_aprobado', $data_email, true);
    }

    protected function getEmails_ofStatus($status_toChanged, $compId): array
    {
        $status_emails = $this->get_status_email_one($compId, $status_toChanged);
        $email_list = explode(",", $status_emails->email);
        return array_map('trim', $email_list);
    }

    protected function getSubjectStatusChanged($order, $newStatusName): string
    {
        $lang_order = $this->HelperLanguage->lang_order();
        return $lang_order['change_order'] . " #" . $order->order_number . " {$newStatusName}";
    }

    public function sendEmailStatusChanged($status, $order, $previousStatusName, $newStatusName, $sellerEmail)
    {
        $bodyHtmlMail = $this->getBodyMailStatusChanged($status, $order, $previousStatusName, $newStatusName);
        $subject = $this->getSubjectStatusChanged($order, $newStatusName);

        $this->load->helper('send_template');
        $companyId = (int) ($order->company_id ?? 0);
        $customerName = '';
        if (isset($order->customer_name) && is_scalar($order->customer_name)) {
            $customerName = trim((string) $order->customer_name);
        } elseif (isset($order->name) && is_scalar($order->name)) {
            $customerName = trim((string) $order->name);
        }
        $companyName = '';
        if (is_object($this->company)) {
            $companyName = (string) ($this->company->company_name ?? $this->company->name ?? '');
        }
        $rendered = fv_send_template_render($companyId, 'order', 'email', [
            '{{customer_name}}' => $customerName,
            '{{company_name}}' => $companyName,
            '{{catalog_name}}' => '',
            '{{catalog_link}}' => '',
            '{{order_number}}' => (string) ($order->order_number ?? ''),
            '{{order_total}}' => (string) ($order->total_amount ?? $order->total ?? ''),
            '{{order_status}}' => (string) $newStatusName,
            '{{message}}' => (string) ($previousStatusName . ' → ' . $newStatusName),
        ]);
        if (is_array($rendered) && trim((string) ($rendered['body'] ?? '')) !== '') {
            $bodyHtmlMail = (string) $rendered['body'];
            if (trim((string) ($rendered['subject'] ?? '')) !== '') {
                $subject = (string) $rendered['subject'];
            }
        }

        FullvendorEmailHelper::sendMailFromNoReply($sellerEmail, $subject, $bodyHtmlMail);
        $email_list = $this->getEmails_ofStatus($status, $order->company_id);

        foreach ($email_list as $email) {
            if (!empty($email)) {
                FullvendorEmailHelper::sendMailFromNoReply($email, $subject, $bodyHtmlMail);
            }
        }
    }
}

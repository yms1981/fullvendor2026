<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * @property Mymodel $mymodel
 */
class EmailOrderModel extends MY_Model
{

    function __construct()
    {
        $this->load->model('OrdenModel');
    }

    public function getEmailOrderSubject($order)
    {
        $company_info = $this->mymodel->get_by('companies', true, ['company_id' => $order->company_id]);
        $langOrden = $this->langmodel->get_lang('cmp-orders', $company_info->company_id);

        $order_confirmation = sprintf($langOrden['order_confirmation'],
            $order->order_number,
            $order->order_number);

        return $company_info->name . ' - ' . $order_confirmation;

    }


    public function obtener_detailOrder($order_id)
    {

        $compId = $this->session->userdata('compId');

        $query = "SELECT p.*, o.*,o.sale_price as precio_cliente,(SELECT formato from units where unit_id=p.unit_id and company_id='$compId') as formato_unidad,(o.qty * o.sale_price) as total_importe,(o.delivered_quantity * o.sale_price) as total_importe_asignado,(SELECT img.name FROM product_images AS pimg JOIN images AS img ON img.uuid = pimg.image_id WHERE pimg.product_id=p.pro_id ORDER BY pimg.img_order ASC LIMIT 1) as imagen_principal,
		(SELECT img.name FROM product_images AS pimg JOIN images AS img ON img.uuid = pimg.image_id WHERE pimg.product_id = p.pro_id ORDER BY pimg.img_order ASC LIMIT 1) AS imagen_por_defecto,
		(SELECT `name` from customer_groups
		where group_id= (SELECT group_id from customers
			where customer_id = (SELECT customer_id FROM orders
				WHERE order_id= $order_id))) as grupo_nombre,
		(SELECT percentage_on_price
		from customer_groups
		where group_id = (SELECT group_id from customers
			where customer_id = (SELECT customer_id FROM orders WHERE order_id= $order_id))) as percentage_on_price
		FROM order_details as o INNER JOIN products as p ON o.product_id=p.pro_id
		where o.order_id = $order_id ORDER BY o.detail_id ASC";

        $result = $this->db->query($query)->result();
        return $result;

    }


    public function obtener_orden($order_id, $compId)
    {

        $consulta = "SELECT o.*,
					(SELECT s.user_id FROM sellers s WHERE s.id = o.seller_id AND s.company_id = o.company_id LIMIT 1) AS seller_id_order,
					c.*, (SELECT percentage_on_price FROM customer_groups
					WHERE group_id = c.group_id) AS porcentaje_on_price
					FROM orders o INNER JOIN customers c ON o.customer_id = c.customer_id
					WHERE o.order_id = " . (int)$order_id . " AND o.company_id = " . (int)$compId;
        $result = $this->db->query($consulta)->row();
        return $result;

    }


    /**
     * @param $order
     * @param $status_order
     * @param $seller
     * @param $request
     * @return mixed
     */
    public function getEmailOrderHtmlBody($order, $status_order, $seller=null, $request=null)
    {
        $company_id = isset($order->company_id) ? $order->company_id : (isset($order->company) && is_object($order->company) ? ($order->company->company_id ?? null) : null);
        if (!$company_id) {
            log_message('error', 'EmailOrderModel::getEmailOrderHtmlBody: order has no company_id');
            return null;
        }

        $company_info = $order->company ?? null;
        if (!$company_info) {
            $company_info = $this->getObjectFromApi("company/{$company_id}");
            if (is_array($company_info) && isset($company_info['error'])) {
                $company_info = null;
            }
        }
        if (!$company_info) {
            log_message('error', 'EmailOrderModel::getEmailOrderHtmlBody: could not load company ' . $company_id);
            return null;
        }
        $company_info = is_array($company_info) ? (object) $company_info : $company_info;

        $vendor_id = $order->user_id ?? $order->seller_id ?? null;
        if (!isset($seller) && $vendor_id) {
            $seller = $this->getObjectFromApi("vendor/{$vendor_id}");
            if (is_array($seller) && isset($seller['error'])) {
                $seller = null;
            }
        }

        $langOrden = $this->langmodel->get_lang('cmp-orders', $company_id);
        $langCatalogo = $this->langmodel->get_lang('cmp-catalog', $company_id);
        $customer = $order->customer ?? null;
        $token = $this->Helper->generar_token();

        $order_details = $order->order_details ?? null;
        if ($order_details === null) {
            $orderFull = $this->getObjectFromApi("order/{$order->order_id}");
            $order_details = (is_array($orderFull) && isset($orderFull['error'])) ? [] : (isset($orderFull->order_details) ? $orderFull->order_details : (isset($orderFull['order_details']) ? $orderFull['order_details'] : []));
        }

        $info = [];
        $info['lang_order'] = $langOrden;
        $info['lang_catalogo'] = $langCatalogo;
        $info['company'] = $company_info;
        $info['detalle_list'] = $order_details;

        $info['seller'] = $seller;
        $info['data'] = $this->getObjectFromApi("order/{$order->order_id}");
        if (is_array($info['data']) && isset($info['data']['error'])) {
            $info['data'] = null;
        }

        $formatNumericRes = $this->getObjectFromApi("company/{$company_id}/format-numeric");
        if (is_array($formatNumericRes) && isset($formatNumericRes['error'])) {
            $info['format_numeric'] = (object) ['digits' => 2, 'radixPoint' => '.', 'groupSeparator' => ','];
        } else {
            $info['format_numeric'] = is_array($formatNumericRes) ? (object) $formatNumericRes : $formatNumericRes;
        }

        $currency_id = $company_info->currency ?? $company_info->currency_id ?? null;
        $currency = $currency_id ? $this->getObjectFromApi("currencybycountry/{$currency_id}") : null;
        $info['currency_symbol'] = $currency ?? $this->getObjectFromApi("currencybycountry/default");
        if (is_array($info['currency_symbol']) && isset($info['currency_symbol']['error'])) {
            $info['currency_symbol'] = (object) ['symbol' => '$'];
        }

        $formato_date = $company_info->formatDate ?? $company_info->format_date ?? null;
        if (!$formato_date) {
            $formato_date = $this->getObjectFromApi("formatdate/default");
        }
        $info['format_date'] = $formato_date;

        $expiration_days = (is_array($request) ? ($request['expiration_days'] ?? null) : null) ?? ($company_info->ordersend_time ?? null) ?? 7;

        //si el token existe ponemos el mismo token
        if (isset($order->token_pass) && $order->token_pass != null && $order->token_pass !== '') {
            $token = $order->token_pass;

            $fecha_actual = date("Y-m-d h:i:s");
            $fecha_expired = date("Y-m-d h:i:s", strtotime($fecha_actual . "+ " . $expiration_days . " days"));
            $this->mymodel->update(['date_expired_email' => $fecha_expired, 'expiration_days' => $expiration_days],
                'orders', ['order_id' => $order->order_id]);

            $this->put("order/{$order->order_id}", ['date_expired_email' => $fecha_expired, 'expiration_days' => $expiration_days]);


        } else {
            // Actualizamos el token de validacion y envio de emails
            // estado pendiente = 1
            $fecha_actual = date("Y-m-d h:i:s");
            $fecha_expired = date("Y-m-d h:i:s", strtotime($fecha_actual . "+ " . $expiration_days . " days"));

            $this->put("order/{$order->order_id}", ['token_pass' => $token,
                'send_email_order' => date('Y-m-d h:i:s'),
                'order_status' => $status_order,
                'date_expired_email' => $fecha_expired,
                'expiration_days' => $expiration_days,
            ]);
            // guardamos historial
            $data_historuy_status_order = [
                "order_id" => $order->order_id,
                "company_id" => $order->company_id,
                "user_id" => $order->company_id,
                "status_id" => 1,
            ];

            $this->post("orderstatushistory", $data_historuy_status_order);
            $data_historuy_status_order["status_id"] = $status_order;
            $this->post("orderstatushistory", $data_historuy_status_order);
        }

        $info['orden'] = $order;
        $info['token'] = $token;

        if (!isset($request['descriptions'])) {

            $cliente = $customer->name . '(' . $customer->business_name . ')';
            $fecha = date($formato_date->format_php, strtotime($order->created));
            $description_default = sprintf($info['lang_order']['description_defecto_order'], $cliente, $fecha);
            $request['descriptions'] = $description_default;
        }

        $info['request'] = $request;
        $info['show_buttons'] = !($status_order == OrdenModel::STATUS_APPROVED);
        $defaultTaxLabel = (isset($info['lang_order']['includes_tax']) && $info['lang_order']['includes_tax'] !== '') ? $info['lang_order']['includes_tax'] : ((isset($company_info->language_id) && (int)$company_info->language_id === 2) ? 'Incluye impuesto' : 'Includes tax');
        $info['plusTaxesLabel'] = $this->mymodel->getPlusTaxesLabel($company_info, $defaultTaxLabel);

        return $this->load->view('company/order/order_template_email', $info, TRUE);
    }

    /**
     * @param $order
     * @param null $forceToEmail
     * @return mixed
     */
    public function getEmailOrderTo($order, $forceToEmail = null)
    {
        return $forceToEmail ?? $this->mymodel->get_by('customers', true, [
            'company_id' => $order->company_id,
            'customer_id' => $order->customer_id
        ])->email;
    }
}

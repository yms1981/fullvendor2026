<?php

class Save extends Company_Controller
{
    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('order', 'company/order/', 'orders');
        $this->langArr = $this->getModuleLang('cmp-orders');
    }

    /**
     * Build API payload from form POST when 'data' JSON is not sent (e.g. native form submit).
     * API expects: customer_id, seller_id, discount_type, discount, products[], tipo_d.
     */
    protected function buildPayloadFromForm()
    {
        $customer_id = $this->input->post('customer_id');
        $user_id = $this->input->post('user_id');
        $discount_type = $this->input->post('discount_type');
        $discount = $this->input->post('discount');
        $detalle = $this->input->post('detalle');

        $products = [];
        if (is_array($detalle)) {
            foreach ($detalle as $row) {
                $pro_id = isset($row['id_producto']) ? $row['id_producto'] : (isset($row['pro_id']) ? $row['pro_id'] : null);
                if (empty($pro_id)) {
                    continue;
                }
                $qty = isset($row['qty']) ? (float) str_replace([',', ' '], ['.', ''], $row['qty']) : 0;
                if ($qty <= 0) {
                    continue;
                }
                $precio = isset($row['precio']) ? $row['precio'] : (isset($row['price']) ? $row['price'] : '0');
                $price = (float) preg_replace('/[^\d.,-]/', '', str_replace(',', '.', $precio));
                if ($price < 0) {
                    $price = 0;
                }
                $fixed = isset($row['precio_fijo']) ? (bool) $row['precio_fijo'] : (isset($row['fixed_price']) ? (bool) $row['fixed_price'] : false);
                $comment = isset($row['comentarios']) ? trim((string) $row['comentarios']) : (isset($row['comment']) ? trim((string) $row['comment']) : '');

                $products[] = [
                    'pro_id' => $pro_id,
                    'qty' => $qty,
                    'price' => $price,
                    'fixed_price' => $fixed,
                    'comment' => $comment,
                ];
            }
        }

        $payload = [
            'customer_id' => $customer_id,
            'seller_id' => $user_id !== null && $user_id !== '' ? (int) $user_id : 0,
            'discount_type' => $discount_type !== null && $discount_type !== '' ? (string) $discount_type : '1',
            'discount' => $discount !== null && $discount !== '' ? (float) str_replace([',', ' '], ['.', ''], $discount) : 0,
            'internal_comments' => $this->input->post('internal_comments') ?: '',
            'tipo_d' => $this->input->post('tipo_d') ?: 'D',
            'proforma' => (bool) $this->input->post('proforma'),
            'products' => $products,
            'source' => 'manual',
        ];
        $cartId = (int) $this->input->post('cart_id');
        if ($cartId > 0) {
            $payload['cart_id'] = $cartId;
        }
        $tokenKor = trim((string) $this->input->post('token_kor'));
        if ($tokenKor !== '') {
            $payload['token_kor'] = $tokenKor;
        }

        return $payload;
    }

    public function run()
    {
        $json = $this->input->post('data');
        $data = null;
        if ($json !== null && $json !== '') {
            $data = json_decode($json, true);
        }

        if (!is_array($data) || empty($data)) {
            $data = $this->buildPayloadFromForm();
        }

        if (empty($data['customer_id']) || !isset($data['products']) || !is_array($data['products']) || count($data['products']) === 0) {
            $this->jsonError(['message' => 'Datos incompletos: cliente y al menos un producto son obligatorios.'], 'Error creating the order.');
            return;
        }

        if (!isset($data['seller_id'])) {
            $data['seller_id'] = (int) $this->input->post('user_id') ?: 0;
        }
        if (!isset($data['discount_type']) || $data['discount_type'] === '') {
            $data['discount_type'] = '1';
        }

        if (isset($data['cart_id'])) {
            $data['cart_id'] = (int) $data['cart_id'];
            if ($data['cart_id'] < 1) {
                unset($data['cart_id']);
            }
        }
        if (isset($data['token_kor']) && trim((string) $data['token_kor']) === '') {
            unset($data['token_kor']);
        }

        $response = $this->post("{$this->entityPath}", $data);

        if ($this->hasError($response)) {
            if ($this->input->is_ajax_request()) {
                $this->jsonError($response, 'Error creating the order.');
                return;
            }
            $this->session->set_flashdata('msg', $this->langArr['error_orden'] ?? 'Error al crear la orden.');
            redirect(company_url('orders'), 'refresh');
            return;
        }

        if ($this->input->is_ajax_request()) {
            $this->jsonSuccess($this->langArr['order_saved'] ?? 'Order saved');
            return;
        }

        $this->session->set_flashdata('msg', $this->langArr['order_saved'] ?? 'Orden guardada correctamente.');
        $orderId = null;
        if (is_array($response) && isset($response['data']['order_id'])) {
            $orderId = $response['data']['order_id'];
        } elseif (is_object($response) && isset($response->data->order_id)) {
            $orderId = $response->data->order_id;
        } elseif (is_array($response) && isset($response['order_id'])) {
            $orderId = $response['order_id'];
        } elseif (is_object($response) && isset($response->order_id)) {
            $orderId = $response->order_id;
        }
        if ($orderId) {
            redirect(company_url("orders/view/{$orderId}"), 'refresh');
        } else {
            redirect(company_url('orders'), 'refresh');
        }
    }
}

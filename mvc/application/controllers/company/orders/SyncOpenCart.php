<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Sincroniza la grilla de alta de orden con Open Cart (catalog_cart + detail_car).
 */
class SyncOpenCart extends Company_Controller
{
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->langArr = $this->getModuleLang('cmp-orders');
    }

    public function run()
    {
        $json = $this->input->post('data');
        $data = is_string($json) && $json !== '' ? json_decode($json, true) : null;
        if (! is_array($data)) {
            $data = [
                'customer_id' => $this->input->post('customer_id'),
                'cart_id' => $this->input->post('cart_id'),
                'products' => $this->input->post('products'),
            ];
        }

        $companyId = (int) $this->getCompanyId();
        $customerId = (int) ($data['customer_id'] ?? 0);
        if ($companyId < 1 || $customerId < 1) {
            $this->jsonError(
                ['message' => $this->langArr['debe_elegir_cliente'] ?? ''],
                $this->langArr['debe_elegir_cliente'] ?? ''
            );

            return;
        }

        $products = $data['products'] ?? [];
        if (! is_array($products)) {
            $products = [];
        }

        $payload = [
            'customer_id' => $customerId,
            'cart_id' => (int) ($data['cart_id'] ?? 0),
            'products' => $products,
        ];
        if (! empty($data['seller_user_id'])) {
            $payload['seller_user_id'] = (int) $data['seller_user_id'];
        }

        $resp = $this->post("catalogcart/company/{$companyId}/openCart/sync", $payload, true);
        if ($this->hasError($resp)) {
            $this->jsonError(is_array($resp) ? $resp : ['message' => ''], $this->langArr['error_orden'] ?? '');

            return;
        }

        $result = [];
        if (is_array($resp)) {
            $result = is_array($resp['data'] ?? null) ? $resp['data'] : $resp;
        } elseif (is_object($resp)) {
            $result = isset($resp->data) ? (array) $resp->data : (array) $resp;
        }

        $this->jsonSuccess('', [
            'success' => true,
            'cart_id' => (int) ($result['cart_id'] ?? 0),
            'token_kor' => (string) ($result['token_kor'] ?? ''),
        ]);
    }
}

<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Token de carrito catalog_cart (type_cart to_customer) vía API.
 *
 * @property Helper $Helper
 */
class Customer_portal_catalog_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('Helper');
    }

    public function ensure_catalog_token(int $companyId, int $customerId): string
    {
        if ($companyId <= 0 || $customerId <= 0) {
            return '';
        }

        try {
            $res = $this->post("customer/{$customerId}/ensure-catalog-cart", [], true);
            if (is_array($res) && isset($res['data']['token_kor']) && $res['data']['token_kor'] !== '') {
                return (string) $res['data']['token_kor'];
            }
            if (is_object($res) && isset($res->data->token_kor) && $res->data->token_kor !== '') {
                return (string) $res->data->token_kor;
            }
            if (is_array($res) && isset($res['error'])) {
                log_message('error', 'Customer_portal_catalog_model ensure-catalog-cart: ' . json_encode($res));
            }
        } catch (\Throwable $e) {
            log_message('error', 'Customer_portal_catalog_model ensure_catalog_token: ' . $e->getMessage());
        }

        return '';
    }
}

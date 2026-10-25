<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Sesión del portal cliente (claves customer_portal_*), reutilizable tras login en /auth.
 */
class Customer_portal_session
{
    private const CUSTOMER_COMPANY_KEY = 'customer_portal_company_id';
    private const CUSTOMER_ID_KEY = 'customer_portal_customer_id';
    private const CUSTOMER_LANG_KEY = 'customer_portal_language_id';
    private const CUSTOMER_CART_TOKEN_KEY = 'customer_portal_session_token';

    /** @var CI_Controller */
    protected $CI;

    public function __construct()
    {
        $this->CI = &get_instance();
    }

    /**
     * Resuelve customer_id vía API y aplica la misma sesión que Customer_portal::establishCustomerSession.
     *
     * @return bool true si se pudo fijar company + customer en sesión
     */
    public function establish_for_auth_user(int $userId, int $companyId, int $languageId): bool
    {
        if ($userId <= 0 || $companyId <= 0) {
            return false;
        }

        $resource = 'user/' . $userId . '/company/' . $companyId . '/customer';
        $res = $this->CI->getObjectFromApi($resource);
        $customerId = $this->extract_customer_id($res);
        if ($customerId <= 0) {
            return false;
        }

        $this->establish_session($companyId, $customerId, $languageId);
        $this->CI->session->set_userdata('customer_portal_user_id', $userId);

        return true;
    }

    /**
     * @param object|array<string,mixed>|null $res
     */
    private function extract_customer_id($res): int
    {
        if (is_object($res) && isset($res->customer_id)) {
            return (int) $res->customer_id;
        }
        if (is_array($res) && isset($res['customer_id'])) {
            return (int) $res['customer_id'];
        }
        if (is_object($res) && isset($res->data)) {
            $d = $res->data;
            if (is_object($d) && isset($d->customer_id)) {
                return (int) $d->customer_id;
            }
            if (is_array($d) && isset($d['customer_id'])) {
                return (int) $d['customer_id'];
            }
        }
        if (is_array($res) && isset($res['data']['customer_id'])) {
            return (int) $res['data']['customer_id'];
        }

        return 0;
    }

    /**
     * @see Customer_portal::establishCustomerSession
     */
    private function establish_session(int $companyId, int $customerId, int $languageId): void
    {
        $keys = [
            self::CUSTOMER_COMPANY_KEY,
            self::CUSTOMER_ID_KEY,
            self::CUSTOMER_LANG_KEY,
            self::CUSTOMER_CART_TOKEN_KEY,
        ];
        $this->CI->session->unset_userdata($keys);

        $this->CI->load->model('Helper');
        $token_session = $this->ensure_portal_session_cart_via_api($customerId, $companyId);

        $this->CI->session->set_flashdata('loader', true);

        $this->CI->session->set_userdata(self::CUSTOMER_COMPANY_KEY, $companyId);
        $this->CI->session->set_userdata(self::CUSTOMER_ID_KEY, $customerId);
        $this->CI->session->set_userdata(self::CUSTOMER_LANG_KEY, $languageId);
        $this->CI->session->set_userdata(self::CUSTOMER_CART_TOKEN_KEY, $token_session);
        $this->CI->setApplication('marketplace');
        $this->CI->session->set_userdata('role', 'customer');
        $this->CI->session->set_userdata('compId', $companyId);
    }

    private function ensure_portal_session_cart_via_api(int $customerId, int $companyId): string
    {
        if ($customerId <= 0) {
            return substr((string) $this->CI->Helper->get_token(), 0, 6);
        }

        try {
            $res = $this->CI->post('customer/' . $customerId . '/ensure-portal-session-cart', [], true);
            if (is_array($res) && isset($res['data']['session_token']) && $res['data']['session_token'] !== '') {
                return (string) $res['data']['session_token'];
            }
            if (is_object($res) && isset($res->data->session_token) && $res->data->session_token !== '') {
                return (string) $res->data->session_token;
            }
        } catch (\Throwable $e) {
            log_message('error', 'Customer_portal_session ensure_portal_session_cart_via_api: ' . $e->getMessage());
        }

        return substr((string) $this->CI->Helper->get_token(), 0, 6);
    }
}

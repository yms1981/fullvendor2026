<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'controllers/catalogo/Producto.php';

/**
 * HTML del catálogo para el iframe del portal (rutas customer/catalog/embed).
 * La pestaña “Catálogo” con header compartido la sirve {@see Customer_portal::catalog()}.
 */
class Customer_catalog extends Producto
{
    public function __construct()
    {
        parent::__construct();
        if (! $this->session->has_userdata('customer_portal_company_id') || ! $this->session->has_userdata('customer_portal_customer_id')) {
            redirect(site_url('customer/login'));

            return;
        }
    }

    /**
     * Listado de productos (solo cuerpo del catálogo, sin shell del portal).
     */
    public function embed(): void
    {
        parent::index();
    }

    /**
     * Carrito / checkout dentro del iframe.
     */
    public function embed_cart(): void
    {
        parent::carts();
    }

    protected function isCustomerPortalCatalogController(): bool
    {
        return false;
    }

    protected function hideCatalogHeaderCustomerLine(): bool
    {
        return true;
    }

    protected function catalogPublicUriIndex(): string
    {
        return 'customer/catalog/embed';
    }

    protected function catalogPublicUriCarts(): string
    {
        return 'customer/catalog/embed/cart';
    }

    protected function getIncomingCatalogToken(): ?string
    {
        $fromGet = parent::getIncomingCatalogToken();
        $companyId = (int) $this->session->userdata('customer_portal_company_id');
        $customerId = (int) $this->session->userdata('customer_portal_customer_id');
        if ($companyId <= 0 || $customerId <= 0) {
            return null;
        }

        $this->load->model('Customer_portal_catalog_model');
        $sessionToken = $this->Customer_portal_catalog_model->ensure_catalog_token($companyId, $customerId);
        if ($sessionToken === '') {
            return null;
        }

        if ($fromGet !== null && $fromGet !== '' && $fromGet !== $sessionToken) {
            log_message('debug', 'Customer_catalog: query token distinto al carrito del portal; se ignora.');
        }

        return $sessionToken;
    }
}

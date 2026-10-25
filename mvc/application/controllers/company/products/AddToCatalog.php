<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Acción por producto "Agregar a catálogo" desde el listado (botón +).
 * Abre el listado de productos con el modal de catálogo para ese producto.
 */
class AddToCatalog extends Company_Controller
{
    use IsCRUD;
    use IsAction;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('product', 'company/product/', 'products');
    }

    /**
     * @param int|string $productId
     */
    public function run($productId = null): void
    {
        $id = (int) $productId;
        if ($id < 1) {
            redirect(company_url('products'), 'refresh');
            return;
        }

        $this->session->set_flashdata('add_to_catalog_product_id', $id);
        redirect(company_url('products'), 'refresh');
    }
}

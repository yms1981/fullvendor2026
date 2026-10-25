<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once APPPATH . 'controllers/company/orders/Form.php';

/**
 * Datos del cliente para el formulario de crear/editar orden (JSON).
 */
class CustomerFormData extends Form
{
    public function index()
    {
        header('Content-Type: application/json; charset=utf-8');

        $customerId = (int) $this->input->get('customer_id');
        if ($customerId < 1) {
            echo json_encode(['error' => true]);

            return;
        }

        $customer = $this->getObjectFromApi("customer/{$customerId}", [
            'relations' => ['sellers', 'customer_group'],
        ]);

        if ($this->hasError($customer)) {
            echo json_encode(['error' => true]);

            return;
        }

        echo json_encode($this->buildCustomerFormPayload($customer));
    }
}

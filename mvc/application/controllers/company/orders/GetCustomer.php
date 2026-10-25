<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Lista de clientes de la empresa activa (sesión compId).
 * Sustituye order/getCustomer para el flujo /company/orders.
 */
class GetCustomer extends Company_Controller
{
    public function index()
    {
        header('Content-Type: application/json; charset=utf-8');

        $companyId = (int) $this->getCompanyId();
        if ($companyId < 1) {
            echo json_encode(['data' => []]);

            return;
        }

        $response = $this->getObjectFromApi("company/{$companyId}/customers");
        $list = $this->hasError($response) ? [] : $this->extractApiListItems($response);

        echo json_encode(['data' => array_values($list)]);
    }
}

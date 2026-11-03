<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class ReturnOverSalesPdf extends Company_Controller
{
    use IsAction;

    public function run($customerId = null)
    {
        $customerId = (int) $customerId;
        if ($customerId < 1) {
            show_404();

            return;
        }

        $response = $this->apiRawRequest('order/return-over-sales/pdf', [
            'customer_id' => $customerId,
            'company_id' => (int) $this->getCompanyId(),
        ], 'GET', [
            'timeout' => 120.0,
            'connect_timeout' => 20.0,
        ]);

        if ($this->hasError($response) || ! is_object($response) || ! method_exists($response, 'getBody')) {
            show_404();

            return;
        }

        $body = $response->getBody()->getContents();
        if (! is_string($body) || strncmp($body, '%PDF', 4) !== 0) {
            show_404();

            return;
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="return-over-sales-' . $customerId . '.pdf"');
        header('Content-Length: ' . (string) strlen($body));
        echo $body;
    }
}

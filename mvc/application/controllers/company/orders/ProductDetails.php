<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class ProductDetails extends Company_Controller
{
    public function index()
    {
        $pro_id = $this->input->get('pro_id');
        $customer_id = $this->input->get('customer_id');

        $product = $this->getObjectFromApi("product/{$pro_id}",['relations' => ['images', 'default_image', 'unit', 'productRating']]);

        $price = $this->getDataFromApi("customer/{$customer_id}/product/{$pro_id}/price");

        if ($this->hasError($product) || $this->hasError($price)) {
            echo json_encode(['error' => true]);
            return;
        }

        $companyId = (int) $this->getCompanyId();
        $moneda = '$';
        if ($companyId) {
            try {
                $sym = $this->getRawFromApi("company/{$companyId}/currency-symbol", []);
                if (is_string($sym) && $sym !== '') {
                    $moneda = $sym;
                }
            } catch (\Throwable $e) {
                $moneda = '$';
            }
        }

        $htmlData = $this->load->view('company/order/order_addProducto',
            [
                'product' => $product,
                'pageTexts' => $this->getModuleLang('cmp-orders'),
                'customer_product_price' => $price['amount'],
                'moneda' => $moneda,
            ],
            true
        );
        $htmlImages = $this->load->view('company/order/order_imagenProducto', ['product' => $product], true);

        echo json_encode([
            'error' => false,
            'html_datos' => $htmlData,
            'html_imagen' => $htmlImages
        ]);
    }
}

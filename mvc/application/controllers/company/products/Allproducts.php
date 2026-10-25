<?php

class Allproducts extends Company_Controller
{
    use IsCRUD;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('product', 'company/product/', 'products');
    }

    public function index() {
        $companyId = (int) $this->getCompanyId();
        if ($companyId < 1) {
            return $this->Helper->response_json([]);
        }

        $response = $this->getDataFromApi("company/{$companyId}/products", [], true);
        if ($this->hasError($response)) {
            return $this->Helper->response_json([]);
        }

        $list = [];
        if (is_array($response)) {
            $list = isset($response['data']) && is_array($response['data']) ? $response['data'] : $response;
        }

        $mapped = [];
        foreach ($list as $product) {
            if (! is_array($product) && ! is_object($product)) {
                continue;
            }
            $mapped[] = $this->mapProductForOrderSearch($product);
        }

        return $this->Helper->response_json($mapped);
    }

}

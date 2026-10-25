<?php

class PriceGroups extends Company_Controller
{
    use IsCRUD;
    use IsAction;


    public function __construct()
    {
        parent::__construct();
        $this->initCrud('product', 'company/product/', 'products');
    }

    public function run() {
        $precio = $this->Commonmodel->get_value_format($this->input->post('precio_venta'));
        $grupos = $this->getObjectFromApi('product/price-list', [
            'company_id' => $this->getCompanyId(),
            'price' => $precio,
        ]);

        $list = [];
        if (is_array($grupos)) {
            foreach ($grupos as $item) {
                if (is_array($item) || is_object($item)) {
                    $list[] = $item;
                }
            }
        } elseif (is_object($grupos) && ! isset($grupos->error)) {
            foreach ($grupos as $item) {
                if (is_array($item) || is_object($item)) {
                    $list[] = $item;
                }
            }
        }

        return $this->Helper->response_json($list);
    }
}

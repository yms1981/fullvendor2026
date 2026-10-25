<?php
require_once APPPATH . 'controllers/company/products/Form.php';
class Add extends Form
{

    public function index() {
        $data = array('title' => 'Add New Product', 'page' => 'products', 'subpage' => 'productadd');
        $data['groups'] = $this->fetchProductPriceGroups(null);

        $this->addJsFile("/companyasset/company/product/semaforo.js");
        $this->addJsFile("/companyasset/company/send_add_form.js");
        $this->addJsFile("/companyasset/company/product/category-groups.js");

        $this->showContent('company/product/product_add', $data);
    }

}

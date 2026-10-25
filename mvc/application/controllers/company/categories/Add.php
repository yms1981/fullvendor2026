<?php
require_once APPPATH . 'controllers/company/categories/Form.php';

class Add extends Form
{
    use IsAction;

    public function run()
    {
        $this->addJsFile("/companyasset/company/send_add_form.js");
        $this->showContent('company/categorias/category_add');
    }
}

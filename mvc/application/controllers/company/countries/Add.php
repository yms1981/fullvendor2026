<?php
require_once APPPATH . 'controllers/company/countries/Form.php';
class Add extends Form
{

    use IsAction;

    public function run()
    {
        $this->addJsFile("/companyasset/company/send_add_form.js");
        $this->showContent('company/country/country_add');
    }
}

<?php
require_once APPPATH . 'controllers/company/cities/Form.php';
class Add extends Form
{

    use IsAction;

    public function run()
    {
        $this->addJsFile("/companyasset/company/send_add_form.js");
        $this->addJsFile('/companyasset/company/locations/stateSearch.js');
        $this->showContent('company/city/city_add');
    }
}

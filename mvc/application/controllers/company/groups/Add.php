<?php
require_once APPPATH . 'controllers/company/groups/Form.php';
class Add extends Form
{

    use IsAction;

    public function run()
    {
        $response = $this->getObjectFromApi("company/{$this->getCompanyId()}",["props" => ['default_customer_group_id']]);

        $data['defaultGroup'] = $this->hasError($response) ? null : $response->default_customer_group_id ;

        $this->addJsFile("/companyasset/company/send_add_form_with_default.js");
        $this->addJsFile("/companyasset/company/groups/price-adjustment.js");

        $this->showContent('company/groups/group_add', $data);
    }
}

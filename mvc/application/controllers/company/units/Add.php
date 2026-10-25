<?php
require_once APPPATH . 'controllers/company/units/Form.php';
class Add extends Form
{
    use IsAction;

    public function run()
    {
        $data = [];
        $response = $this->getObjectFromApi("{$this->entityPath}/company/{$this->getCompanyId()}");

        $data['defaultUnit'] = $this->hasError($response) ? null : $response;

        $this->addJsFile("/companyasset/company/send_add_form_with_default.js");
        $this->showContent('company/units/units_add', $data);
    }
}

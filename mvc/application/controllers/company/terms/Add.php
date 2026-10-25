<?php
require_once APPPATH . 'controllers/company/terms/Form.php';
class Add extends Form
{

    use IsAction;

    public function run()
    {
        $response = $this->getObjectFromApi("{$this->entityPath}/company/{$this->getCompanyId()}");

        $data['defaultTerm'] = $this->hasError($response) ? null : $response;

        $this->addJsFile("/companyasset/company/send_add_form_with_default.js");
        $this->showContent('company/terms/term_add', $data);
    }
}

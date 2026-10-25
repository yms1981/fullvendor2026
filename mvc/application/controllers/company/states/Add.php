<?php
require_once APPPATH . 'controllers/company/states/Form.php';
class Add extends Form
{

    use IsAction;

    public function run()
    {
        $this->addJsFile("/companyasset/company/send_add_form.js");
        $this->addJsFile('/companyasset/company/locations/countrySearch.js');
        $this->showContent('company/state/state_add');
    }
}

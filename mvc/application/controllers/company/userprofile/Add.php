<?php

require_once APPPATH . 'controllers/company/userprofile/Form.php';

class Add extends Form
{
    use IsAction;

    public function run(): void
    {
        $data = [];
        $this->addJsFile("/companyasset/company/send_add_form.js");
        $this->showContent('company/userprofile/userprofile_add', $data, 'company');
    }
}


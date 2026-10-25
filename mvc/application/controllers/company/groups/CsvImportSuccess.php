<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once APPPATH . 'controllers/company/groups/Home.php';

class CsvImportSuccess extends Home
{
    public function index()
    {
        $schema = ConfigCrudImportSchemas::groups();
        if (!empty($_FILES['file']['name']) && empty($this->input->post('import_token'))) {
            $this->handleConfigCrudImportPage('groups', company_url('groups'), (string) $schema['template'], $schema);

            return;
        }
        $_POST['step'] = 'confirm';
        $this->executeConfigCrudImportConfirm(company_url('groups'), $schema);
    }
}

<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once APPPATH . 'controllers/company/terms/Home.php';

class CsvImportSuccess extends Home
{
    public function index()
    {
        $schema = ConfigCrudImportSchemas::terms();
        if (!empty($_FILES['file']['name']) && empty($this->input->post('import_token'))) {
            $this->handleConfigCrudImportPage('terms', company_url('terms/lists'), (string) $schema['template'], $schema);

            return;
        }
        $_POST['step'] = 'confirm';
        $this->executeConfigCrudImportConfirm(company_url('terms/lists'), $schema);
    }
}

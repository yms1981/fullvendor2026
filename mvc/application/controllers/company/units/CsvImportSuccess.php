<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once APPPATH . 'controllers/company/units/Lists.php';

class CsvImportSuccess extends Lists
{
    public function index()
    {
        $schema = ConfigCrudImportSchemas::units();
        if (!empty($_FILES['file']['name']) && empty($this->input->post('import_token'))) {
            $this->handleConfigCrudImportPage('units', company_url('units/lists'), (string) $schema['template'], $schema);

            return;
        }
        $_POST['step'] = 'confirm';
        $this->executeConfigCrudImportConfirm(company_url('units/lists'), $schema);
    }
}

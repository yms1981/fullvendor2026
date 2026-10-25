<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once APPPATH . 'controllers/company/warehouses/Home.php';

class CsvImportSuccess extends Home
{
    use IsAction;

    public function run()
    {
        $schema = ConfigCrudImportSchemas::warehouses();
        if (!empty($_FILES['file']['name']) && empty($this->input->post('import_token'))) {
            $this->handleConfigCrudImportPage('warehouses', company_url('warehouses'), (string) $schema['template'], $schema);

            return;
        }

        $_POST['step'] = 'confirm';
        $this->executeConfigCrudImportConfirm(company_url('warehouses'), $schema);
    }
}

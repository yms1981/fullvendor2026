<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once APPPATH . 'controllers/company/customers/Home.php';

class CsvImportSuccess extends Home
{
    use IsAction;

    public function run()
    {
        $schema = ConfigCrudImportSchemas::customers();
        if (!empty($_FILES['file']['name']) && empty($this->input->post('import_token'))) {
            $this->handleConfigCrudImportPage('customers', company_url('customers'), (string) $schema['template'], $schema);

            return;
        }

        $_POST['step'] = 'confirm';
        $this->executeConfigCrudImportConfirm(company_url('customers'), $schema);
    }
}

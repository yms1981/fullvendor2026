<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class CsvImportSuccess extends Company_Controller
{
    use ConfigCrudListTools;

    public function __construct()
    {
        parent::__construct();
        $this->checkLoggedInOrLoginWithRedirect();
        $this->langArr = $this->getModuleLang('cm-currency');
    }

    public function index()
    {
        $schema = ConfigCrudImportSchemas::currencies();
        if (!empty($_FILES['file']['name']) && empty($this->input->post('import_token'))) {
            $this->handleConfigCrudImportPage('currencies', company_url('currencies/lists'), (string) $schema['template'], $schema);

            return;
        }
        $_POST['step'] = 'confirm';
        $this->executeConfigCrudImportConfirm(company_url('currencies/lists'), $schema);
    }
}

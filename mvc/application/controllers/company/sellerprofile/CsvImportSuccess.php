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
        $this->langArr = $this->getModuleLang('cmp-profile-vendor');
    }

    public function index()
    {
        $schema = ConfigCrudImportSchemas::sellerprofile();
        if (!empty($_FILES['file']['name']) && empty($this->input->post('import_token'))) {
            $this->handleConfigCrudImportPage('sellerprofile', company_url('sellerprofile/lists'), (string) $schema['template'], $schema);

            return;
        }
        $_POST['step'] = 'confirm';
        $this->executeConfigCrudImportConfirm(company_url('sellerprofile/lists'), $schema);
    }
}

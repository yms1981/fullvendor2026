<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class CsvImport extends Company_Controller
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
        $lang = $this->getConfigCrudLang();
        $lang['template_download_url'] = import_template_download_url('sellerprofilescsv.xlsx');
        $this->langArr = $lang;
        $this->showConfigCsvImportView('sellerprofile', company_url('sellerprofile/lists'), 'csvImportSuccess');
    }
}

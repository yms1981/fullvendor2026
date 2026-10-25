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
        $this->langArr = $this->getModuleLang('cm-currency');
    }

    public function index()
    {
        $lang = $this->getConfigCrudLang();
        $lang['template_download_url'] = import_template_download_url('currenciescsv.xlsx');
        $this->langArr = $lang;
        $this->showConfigCsvImportView('currencies', company_url('currencies/lists'), 'csvImportSuccess');
    }
}

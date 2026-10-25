<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once APPPATH . 'controllers/company/terms/Home.php';

class CsvImport extends Home
{
    public function index()
    {
        $lang = $this->getConfigCrudLang();
        $lang['template_download_url'] = import_template_download_url('termscsv.xlsx');
        $this->langArr = $lang;
        $this->showConfigCsvImportView('terms', company_url('terms/lists'), 'csvImportSuccess');
    }
}

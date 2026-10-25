<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once APPPATH . 'controllers/company/groups/Home.php';

class CsvImport extends Home
{
    public function index()
    {
        $lang = $this->getConfigCrudLang();
        $lang['template_download_url'] = import_template_download_url('groupscsv.xlsx');
        $this->langArr = $lang;
        $this->showConfigCsvImportView('groups', company_url('groups'), 'csvImportSuccess');
    }
}

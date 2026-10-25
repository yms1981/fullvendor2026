<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once APPPATH . 'controllers/company/users/Home.php';

class CsvImport extends Home
{
    use ConfigCrudListTools;

    public function index()
    {
        $lang = $this->getConfigCrudLang();
        $lang['template_download_url'] = import_template_download_url('userscsv.xlsx');
        $this->langArr = $lang;
        $this->showConfigCsvImportView('users', company_url('users'), 'csvImportSuccess');
    }
}

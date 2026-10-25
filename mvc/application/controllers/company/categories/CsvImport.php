<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once APPPATH . 'controllers/company/categories/Home.php';

class CsvImport extends Home
{
    use ConfigCrudListTools;

    public function index()
    {
        $this->showConfigCsvImportView('categories', company_url('categories'), 'csvImportSuccess');
    }
}

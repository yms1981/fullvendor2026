<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once APPPATH . 'controllers/company/warehouses/Home.php';

class CsvImport extends Home
{
    public function index()
    {
        $this->showConfigCsvImportView('warehouses', company_url('warehouses'), 'csvImportSuccess');
    }
}

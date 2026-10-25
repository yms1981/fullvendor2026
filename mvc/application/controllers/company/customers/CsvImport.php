<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once APPPATH . 'controllers/company/customers/Home.php';

class CsvImport extends Home
{
    public function index()
    {
        $this->showConfigCsvImportView('customers', company_url('customers'), 'csvImportSuccess');
    }
}

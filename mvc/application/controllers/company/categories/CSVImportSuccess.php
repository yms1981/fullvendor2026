<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once APPPATH . 'controllers/company/categories/Home.php';

class CSVImportSuccess extends Home
{
    public function run()
    {
        redirect(company_url('categories/csvImport'));
    }
}

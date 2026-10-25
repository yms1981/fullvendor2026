<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once APPPATH . 'controllers/company/units/Lists.php';

class CsvImport extends Lists
{
    public function index()
    {
        if (method_exists($this, 'getLangJsonSection')) {
            $this->langArr = array_merge(
                is_array($this->langArr ?? null) ? $this->langArr : [],
                $this->getLangJsonSection('config-crud-tools')
            );
        }
        $lang = $this->getConfigCrudLang();
        $lang['template_download_url'] = import_template_download_url('unitscsv.xlsx');
        $this->langArr = $lang;
        $this->showConfigCsvImportView('units', company_url('units/lists'), 'csvImportSuccess');
    }
}

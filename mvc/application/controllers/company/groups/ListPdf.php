<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once APPPATH . 'controllers/company/groups/Export.php';

class ListPdf extends Export
{
    use ConfigCrudListTools;

    public function index()
    {
        $lang = $this->getConfigCrudLang();
        $title = $this->resolveConfigCrudPageTitle($lang, 'pageTitle', 'list_page_title', 'list_of_group');
        $dataset = $this->buildGroupsExportDataset($lang);

        $this->sendConfigListPdf(
            $dataset['headers'],
            $dataset['rows'],
            $title !== '' ? $title : 'groups',
            'groups_list'
        );
    }
}

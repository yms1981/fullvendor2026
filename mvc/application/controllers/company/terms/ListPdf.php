<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once APPPATH . 'controllers/company/terms/Home.php';

class ListPdf extends Home
{
    use ConfigCrudListTools;

    public function index()
    {
        $lang = $this->getConfigCrudLang();
        $title = $this->resolveConfigCrudPageTitle($lang, 'pageTitle', 'titulo');
        $list = $this->fetchConfigCrudApiList('termsofsale', [
            'company' => $this->getCompanyId(),
            'props' => ['term_id', 'name', 'default', 'created_at'],
        ]);

        $headers = [
            $lang['name'] ?? '',
            $lang['default'] ?? '',
            $lang['alta'] ?? '',
        ];

        $rows = [];
        foreach ($list as $item) {
            $item = (object) $item;
            $rows[] = [
                (string) ($item->name ?? ''),
                !empty($item->isDefault) || !empty($item->default) ? '1' : '0',
                $this->normalizeConfigCrudDate($item->created ?? $item->created_at ?? null),
            ];
        }

        $this->sendConfigListPdf($headers, $rows, $title !== '' ? $title : 'terms', 'terms_list');
    }
}

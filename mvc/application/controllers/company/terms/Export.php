<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once APPPATH . 'controllers/company/terms/Home.php';

class Export extends Home
{
    use ConfigCrudListTools;

    public function index()
    {
        $lang = $this->getConfigCrudLang();
        $list = $this->fetchConfigCrudApiList('termsofsale/all', [
            'company' => $this->getCompanyId(),
        ]);

        $headers = [
            $lang['name'] ?? 'name',
            $lang['default'] ?? 'default',
            $lang['alta'] ?? 'created',
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

        $this->sendConfigCsvDownload($headers, $rows, 'terms_list_' . date('Ymd') . '.csv');
    }
}

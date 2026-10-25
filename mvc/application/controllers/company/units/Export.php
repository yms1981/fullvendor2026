<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once APPPATH . 'controllers/company/units/Lists.php';

class Export extends Lists
{
    use ConfigCrudListTools;

    public function index()
    {
        $lang = $this->getConfigCrudLang();
        $list = $this->fetchConfigCrudApiList('unit', [
            'company' => $this->getCompanyId(),
            'props' => ['unit_id', 'unit', 'description', 'formato', 'decimales', 'default', 'created'],
        ]);

        $headers = [
            $lang['unidad_nombre'] ?? 'unit',
            $lang['description'] ?? 'description',
            $lang['formato_unidad'] ?? 'formato',
            $lang['decimales'] ?? 'decimales',
            $lang['default'] ?? 'default',
            $lang['unidad_alta'] ?? 'created',
        ];

        $rows = [];
        foreach ($list as $item) {
            $item = (object) $item;
            $rows[] = [
                (string) ($item->unit ?? ''),
                (string) ($item->description ?? ''),
                (string) ($item->formato ?? ''),
                (string) ($item->decimales ?? ''),
                !empty($item->isDefault) || !empty($item->default) ? '1' : '0',
                $this->normalizeConfigCrudDate($item->created ?? null),
            ];
        }

        $this->sendConfigExcelDownload($headers, $rows, 'units_list_' . date('Ymd') . '.xlsx');
    }
}

<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once APPPATH . 'controllers/company/units/Lists.php';

class ListPdf extends Lists
{
    use ConfigCrudListTools;

    public function index()
    {
        $lang = $this->getConfigCrudLang();
        $title = $this->resolveConfigCrudPageTitle($lang, 'pageTitle', 'list_tipo_unidades');
        $list = $this->fetchConfigCrudApiList('unit', [
            'company' => $this->getCompanyId(),
            'props' => ['unit_id', 'unit', 'description', 'formato', 'decimales', 'default', 'created'],
        ]);

        $headers = [
            $lang['unidad_nombre'] ?? '',
            $lang['description'] ?? '',
            $lang['formato_unidad'] ?? '',
            $lang['default'] ?? '',
            $lang['unidad_alta'] ?? '',
        ];

        $rows = [];
        foreach ($list as $item) {
            $item = (object) $item;
            $rows[] = [
                (string) ($item->unit ?? ''),
                (string) ($item->description ?? ''),
                (string) ($item->formato ?? ''),
                !empty($item->isDefault) || !empty($item->default) ? '1' : '0',
                $this->normalizeConfigCrudDate($item->created ?? null),
            ];
        }

        $this->sendConfigListPdf($headers, $rows, $title !== '' ? $title : 'units', 'units_list');
    }
}

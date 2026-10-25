<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once APPPATH . 'controllers/company/groups/Home.php';

/**
 * Exportación CSV/Excel del listado de grupos (/company/groups/export).
 */
class Export extends Home
{
    use ConfigCrudListTools;

    public function index()
    {
        $lang = $this->getConfigCrudLang();
        $dataset = $this->buildGroupsExportDataset($lang);

        $this->sendConfigCsvDownload(
            $dataset['headers'],
            $dataset['rows'],
            'groups_list_' . date('Ymd') . '.csv'
        );
    }

    /**
     * @param array<string, string> $lang
     * @return array{headers: array<int, string>, rows: array<int, array<int, string>>}
     */
    protected function buildGroupsExportDataset(array $lang): array
    {
        // Misma fuente que el listado; NO pedir columna "default" (eliminada de customer_groups).
        $list = $this->fetchConfigCrudApiList('customergroup/all', [
            'company' => $this->getCompanyId(),
            'props' => ['group_id', 'name', 'percentage_on_price', 'percent_price_amount', 'created_at', 'group_status'],
        ]);

        $headers = [
            $lang['name'] ?? 'name',
            $lang['on_price'] ?? 'percentage_on_price',
            $lang['%'] ?? 'percent_price_amount',
            $lang['default'] ?? 'default',
            $lang['created'] ?? 'created',
        ];

        $noneLabel = (string) ($lang['ninguno'] ?? '');
        $rows = [];
        foreach ($list as $item) {
            $item = (object) $item;
            $percentage = $item->percentage_on_price ?? null;
            if ($percentage === null || $percentage === '' || $percentage === 'nothing') {
                $percentageLabel = $noneLabel;
            } elseif ($percentage === 'Increase on Price List %') {
                $percentageLabel = (string) ($lang['percentage_increase_option'] ?? $percentage);
            } elseif ($percentage === 'Decrease on Price List %') {
                $percentageLabel = (string) ($lang['percentage_decrease_option'] ?? $percentage);
            } else {
                $percentageLabel = (string) $percentage;
            }

            $rows[] = [
                (string) ($item->name ?? ''),
                $percentageLabel,
                (string) ($item->percent_price_amount ?? '0'),
                !empty($item->isDefault) || !empty($item->is_default) || !empty($item->default) ? '1' : '0',
                $this->normalizeConfigCrudDate($item->created ?? $item->created_at ?? null),
            ];
        }

        return ['headers' => $headers, 'rows' => $rows];
    }
}

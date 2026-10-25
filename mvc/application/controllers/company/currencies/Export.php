<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Export extends Company_Controller
{
    use ConfigCrudListTools;

    public function __construct()
    {
        parent::__construct();
        $this->checkLoggedInOrLoginWithRedirect();
        $this->langArr = $this->getModuleLang('cm-currency');
    }

    public function index()
    {
        $lang = $this->getConfigCrudLang();
        $langUnit = $this->getModuleLang('unit-languaje');
        $groups = $this->fetchCurrencyGroups();

        $headers = [
            $langUnit['continent'] ?? 'continent',
            $langUnit['country'] ?? 'country',
            $langUnit['currency'] ?? 'currency',
            $langUnit['code'] ?? 'code',
            $langUnit['symbol'] ?? 'symbol',
        ];

        $rows = [];
        foreach ($groups as $group) {
            $group = (object) $group;
            $children = is_array($group->currencies ?? null) ? $group->currencies : [];
            foreach ($children as $child) {
                $child = (object) $child;
                $rows[] = [
                    (string) ($group->name ?? $group->GroupID ?? ''),
                    (string) ($child->country ?? $child->Contry ?? ''),
                    (string) ($child->name ?? $child->Name ?? ''),
                    (string) ($child->code ?? $child->Code ?? ''),
                    (string) ($child->symbol ?? $child->Symbol ?? ''),
                ];
            }
        }

        $this->sendConfigCsvDownload($headers, $rows, 'currencies_catalog_' . date('Ymd') . '.csv');
    }

    /**
     * @return array<int, object|array>
     */
    private function fetchCurrencyGroups(): array
    {
        $response = $this->getObjectFromApi('currency/grouped-by-continent');
        if (is_object($response) && isset($response->data) && is_array($response->data)) {
            return $response->data;
        }
        if (is_array($response) && !isset($response['error'])) {
            return $response;
        }

        return [];
    }
}

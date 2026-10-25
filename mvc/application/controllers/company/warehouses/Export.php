<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Export extends Company_Controller
{
    use IsCRUD;
    use IsAction;
    use ConfigCrudListTools;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('warehouse', 'company/warehouse/', 'warehouses');
        $this->langArr = $this->getModuleLang('cmp-warehouse');
    }

    public function run()
    {
        $companyId = $this->getCompanyId();

        $response = $this->getObjectFromApi('warehouse', [
            'all' => true,
            'company' => $companyId,
            'relations' => ['address.city', 'address.state', 'address.country'],
        ]);

        $list = [];
        if (is_object($response) && isset($response->data)) {
            $list = $response->data;
        } elseif (is_array($response) && ! isset($response['error'])) {
            $list = $response;
        }

        $headers = [
            $this->langArr['id'] ?? 'ID',
            $this->langArr['name'] ?? 'Name',
            $this->langArr['street'] ?? 'Street',
            $this->langArr['street_number'] ?? 'Number',
            $this->langArr['city'] ?? 'City',
            $this->langArr['state'] ?? 'State',
            $this->langArr['country'] ?? 'Country',
            $this->langArr['created'] ?? 'Created',
        ];

        $rows = [];
        foreach ($list as $warehouse) {
            $warehouse = (object) $warehouse;
            $address = isset($warehouse->address) ? (object) $warehouse->address : null;

            $rows[] = [
                (string) ($warehouse->id ?? ''),
                (string) ($warehouse->name ?? ''),
                (string) ($address->street ?? ''),
                (string) ($address->street_number ?? ''),
                $this->resolveRelationName($address, 'city', 'city_text'),
                $this->resolveRelationName($address, 'state', 'state_text'),
                $this->resolveRelationName($address, 'country', null),
                $this->normalizeDate($warehouse->created_at ?? null),
            ];
        }

        $this->sendConfigExcelDownload($headers, $rows, 'warehouse_list' . date('Ymd') . '.xlsx');
    }

    private function resolveRelationName($address, string $relation, ?string $textAttribute): string
    {
        if (! is_object($address)) {
            return '';
        }

        if (isset($address->$relation) && (is_object($address->$relation) || is_array($address->$relation))) {
            $related = (object) $address->$relation;
            if (isset($related->name) && trim((string) $related->name) !== '') {
                return (string) $related->name;
            }
        }

        if ($textAttribute !== null && isset($address->$textAttribute) && trim((string) $address->$textAttribute) !== '') {
            return (string) $address->$textAttribute;
        }

        return '';
    }

    private function normalizeDate($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (is_array($value)) {
            return isset($value['date']) ? substr((string) $value['date'], 0, 10) : '';
        }
        if (is_object($value)) {
            return isset($value->date) ? substr((string) $value->date, 0, 10) : '';
        }

        return substr((string) $value, 0, 10);
    }
}

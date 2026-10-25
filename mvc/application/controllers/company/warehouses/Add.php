<?php

require_once APPPATH . 'controllers/company/warehouses/Form.php';

class Add extends Form
{
    use IsAction;
    use SupportsWarehouseUsers;

    public function run(): void
    {
        $countriesResponse = $this->getObjectFromApi('country', [
            'props' => ['id', 'name', 'sortname', 'phonecode', 'phonemask'],
            'all' => true,
            'orderProperty' => 'name',
        ]);
        $data['countries'] = (is_object($countriesResponse) && isset($countriesResponse->data))
            ? $countriesResponse->data
            : (is_array($countriesResponse) ? $countriesResponse : []);

        $companyId = (int) $this->getCompanyId();
        $data['company'] = $companyId > 0 ? $this->getObjectFromApi('company/' . $companyId) : null;
        $data['companyDefaultCountryId'] = $this->resolveCompanyDefaultCountryId($data['company'], $data['countries']);

        $warehouseRoleUsers = [];
        foreach ($this->fetchWarehouseUsersFromApi() as $warehouseUserRow) {
            $userId = (int) ($warehouseUserRow->user_id ?? 0);
            if ($userId < 1 || (string) ($warehouseUserRow->status ?? '1') !== '1') {
                continue;
            }
            $displayName = trim((string) (($warehouseUserRow->first_name ?? '') . ' ' . ($warehouseUserRow->last_name ?? '')));
            if ($displayName === '') {
                $displayName = trim((string) ($warehouseUserRow->email ?? ''));
            }
            $warehouseRoleUsers[] = (object) [
                'id' => $userId,
                'name' => $displayName,
                'email' => trim((string) ($warehouseUserRow->email ?? '')),
            ];
        }
        usort($warehouseRoleUsers, static function ($a, $b): int {
            return strcasecmp((string) ($a->name ?? ''), (string) ($b->name ?? ''));
        });
        $data['warehouseRoleUsers'] = $warehouseRoleUsers;
        $data['warehouseDraft'] = (object) [
            'id' => 0,
            'users' => [],
        ];

        $this->addJsFile(base_url('companyasset/js/select2.min.js'));
        $this->addJsFile(base_url('companyasset/company/warehouse/warehouse_form_validate.js'));
        $this->addJsFile(base_url('companyasset/company/send_add_form.js'));
        $this->addJsFile(base_url('companyasset/company/locations/citySearch.js'));
        $this->addJsFile(base_url('companyasset/company/locations/stateSearch.js'));
        $this->addJsFile(base_url('companyasset/company/warehouse/warehouse_address_form.js'));
        $this->addJsFile(base_url('companyasset/company/warehouse/assign_warehouse_user.js'));
        $this->addJsFile(base_url('companyasset/company/assign_user.js'));

        $this->showContent('company/warehouse/warehouse_add', $data);
    }
}

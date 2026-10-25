<?php

require_once APPPATH . 'controllers/company/warehouses/Form.php';

class Edit extends Form
{
    use IsAction;
    use SupportsWarehouseUsers;

    public function run($warehouseId = null)
    {
        $warehouseId = (int) $warehouseId;
        if ($warehouseId < 1) {
            show_404();
        }

        $warehouse = $this->getObjectFromApi("{$this->entityPath}/{$warehouseId}", [
            'relations' => [
                'address.city:id,name',
                'address.state:id,name',
                'address.country:id,name',
                'users:id,name,email,phone',
            ],
        ]);

        if ($this->hasError($warehouse)) {
            show_404();
        }

        $countriesResponse = $this->getObjectFromApi('country', [
            'props' => ['id', 'name', 'sortname', 'phonecode', 'phonemask'],
            'all' => true,
            'orderProperty' => 'name',
        ]);
        $data['countries'] = (is_object($countriesResponse) && isset($countriesResponse->data))
            ? $countriesResponse->data
            : (is_array($countriesResponse) ? $countriesResponse : []);

        $data['warehouse'] = $warehouse;

        $assignedUserIds = [];
        $entityUsers = $warehouse->users ?? [];
        if (is_array($entityUsers)) {
            foreach ($entityUsers as $assignedUser) {
                $assignedUserIds[] = (int) (is_object($assignedUser) ? ($assignedUser->id ?? 0) : 0);
            }
        }
        $assignedUserIds = array_values(array_filter(array_unique($assignedUserIds)));

        $warehouseRoleUsers = [];
        foreach ($this->fetchWarehouseUsersFromApi() as $warehouseUserRow) {
            $userId = (int) ($warehouseUserRow->user_id ?? 0);
            if ($userId < 1 || in_array($userId, $assignedUserIds, true)) {
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

        $this->addJsFile(base_url('companyasset/js/select2.min.js'));
        $this->addJsFile(base_url('companyasset/company/warehouse/warehouse_form_validate.js'));
        $this->addJsFile(base_url('companyasset/company/send_edit_form.js'));
        $this->addJsFile(base_url('companyasset/company/locations/citySearch.js'));
        $this->addJsFile(base_url('companyasset/company/locations/stateSearch.js'));
        $this->addJsFile(base_url('companyasset/company/warehouse/warehouse_address_form.js'));
        $this->addJsFile(base_url('companyasset/company/warehouse/assign_warehouse_user.js'));
        $this->addJsFile(base_url('companyasset/company/assign_user.js'));
        $this->addJsFile(base_url('companyasset/company/edit_assigned_user.js'));
        $this->addJsFile(base_url('companyasset/company/unassign_assigned_user.js'));

        $this->showContent('company/warehouse/warehouse_edit', $data);
    }
}

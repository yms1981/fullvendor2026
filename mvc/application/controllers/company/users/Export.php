<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once APPPATH . 'controllers/company/users/Home.php';

class Export extends Home
{
    use ConfigCrudListTools;

    public function index()
    {
        $lang = $this->getConfigCrudLang();
        $filter = $this->getFilterData();
        unset($filter['page'], $filter['per_page']);
        $list = $this->fetchConfigCrudApiList('user', $filter);

        $headers = [
            $lang['name'] ?? 'name',
            'Email',
            $lang['role'] ?? 'role',
            $lang['profile'] ?? 'profile',
            $lang['alta'] ?? 'created',
        ];

        $rows = [];
        foreach ($list as $item) {
            $item = (object) $item;
            $role = '';
            $profile = '';
            if (isset($item->companyUsers) && is_array($item->companyUsers)) {
                foreach ($item->companyUsers as $cu) {
                    $cu = (object) $cu;
                    $role = (string) ($cu->type ?? $role);
                    if (isset($cu->profileable) && is_object($cu->profileable)) {
                        $profile = (string) ($cu->profileable->profile_name ?? $cu->profileable->name ?? $profile);
                    }
                }
            }
            $rows[] = [
                (string) ($item->name ?? ''),
                (string) ($item->email ?? ''),
                $role,
                $profile,
                $this->normalizeConfigCrudDate($item->created_at ?? null),
            ];
        }

        $this->sendConfigCsvDownload($headers, $rows, 'users_list_' . date('Ymd') . '.csv');
    }
}

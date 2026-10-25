<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once APPPATH . 'controllers/company/users/Home.php';

class ListPdf extends Home
{
    use ConfigCrudListTools;

    public function index()
    {
        $lang = $this->getConfigCrudLang();
        $title = $this->resolveConfigCrudPageTitle($lang, 'pageTitle', 'list_users');
        $filter = $this->getFilterData();
        unset($filter['page'], $filter['per_page']);
        $list = $this->fetchConfigCrudApiList('user', $filter);

        $headers = [
            $lang['name'] ?? '',
            'Email',
            $lang['role'] ?? '',
            $lang['alta'] ?? '',
        ];

        $rows = [];
        foreach ($list as $item) {
            $item = (object) $item;
            $role = '';
            if (isset($item->companyUsers) && is_array($item->companyUsers)) {
                foreach ($item->companyUsers as $cu) {
                    $cu = (object) $cu;
                    $role = (string) ($cu->type ?? $role);
                }
            }
            $rows[] = [
                (string) ($item->name ?? ''),
                (string) ($item->email ?? ''),
                $role,
                $this->normalizeConfigCrudDate($item->created_at ?? null),
            ];
        }

        $this->sendConfigListPdf($headers, $rows, $title !== '' ? $title : 'users', 'users_list');
    }
}

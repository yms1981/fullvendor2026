<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class ListPdf extends Company_Controller
{
    use ConfigCrudListTools;

    public function __construct()
    {
        parent::__construct();
        $this->langArr = $this->getModuleLang('cmp-profile-vendor');
    }

    public function index()
    {
        $lang = $this->getConfigCrudLang();
        $title = $this->resolveConfigCrudPageTitle($lang, 'pageTitle', 'list_profile_vendor');
        $list = $this->fetchConfigCrudApiList('sellerprofiles', [
            'company' => $this->getCompanyId(),
        ]);

        $yes = $lang['si'] ?? '1';
        $no = $lang['no'] ?? '0';

        $headers = [
            $lang['name_profile'] ?? '',
            $lang['order_discount'] ?? '',
            $lang['change_price'] ?? '',
            $lang['send_catalog'] ?? '',
            $lang['create_customer'] ?? '',
            $lang['created'] ?? '',
        ];

        $rows = [];
        foreach ($list as $item) {
            $item = (object) $item;
            $rows[] = [
                (string) ($item->profile_name ?? ''),
                !empty($item->order_discount) ? $yes : $no,
                !empty($item->can_change_price) ? $yes : $no,
                !empty($item->can_send_catalog) ? $yes : $no,
                !empty($item->can_create_customer) ? $yes : $no,
                $this->normalizeConfigCrudDate($item->created ?? null),
            ];
        }

        $this->sendConfigListPdf($headers, $rows, $title !== '' ? $title : 'seller_profiles', 'seller_profiles');
    }
}

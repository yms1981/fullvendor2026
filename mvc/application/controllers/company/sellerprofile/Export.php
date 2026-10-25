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
        $this->langArr = $this->getModuleLang('cmp-profile-vendor');
    }

    public function index()
    {
        $lang = $this->getConfigCrudLang();
        $list = $this->fetchConfigCrudApiList('sellerprofiles', [
            'company' => $this->getCompanyId(),
        ]);

        $headers = [
            $lang['name_profile'] ?? 'profile_name',
            $lang['order_discount'] ?? 'order_discount',
            $lang['order_net_discount'] ?? 'order_net_discount',
            $lang['change_price'] ?? 'can_change_price',
            $lang['send_catalog'] ?? 'can_send_catalog',
            $lang['create_customer'] ?? 'can_create_customer',
            $lang['created'] ?? 'created',
        ];

        $yes = $lang['si'] ?? '1';
        $no = $lang['no'] ?? '0';

        $rows = [];
        foreach ($list as $item) {
            $item = (object) $item;
            $rows[] = [
                (string) ($item->profile_name ?? ''),
                !empty($item->order_discount) ? $yes : $no,
                !empty($item->order_net_discount) ? $yes : $no,
                !empty($item->can_change_price) ? $yes : $no,
                !empty($item->can_send_catalog) ? $yes : $no,
                !empty($item->can_create_customer) ? $yes : $no,
                $this->normalizeConfigCrudDate($item->created ?? null),
            ];
        }

        $this->sendConfigCsvDownload($headers, $rows, 'seller_profiles_' . date('Ymd') . '.csv');
    }
}

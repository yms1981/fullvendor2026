<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Lists extends Company_Controller
{
    use IsCRUD;
    use ConfigCrudListTools;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('sellerprofiles', 'company/userprofile/', 'userprofiles');
        $this->langArr = $this->getModuleLang('cmp-profile-vendor');
    }

    public function index()
    {
        $indexJsFiles = [
            'companyasset/company/enable-disable.js',
            'companyasset/company/common/delete-alert.js',
        ];

        foreach ($indexJsFiles as $indexJsFile) {
            $this->addJsFile(base_url($indexJsFile));
        }

        $this->list();
    }

    protected function initListButtonBar(): void
    {
        $this->listButtonBar = new ButtonBar();
        $this->appendConfigCrudListToolbar('sellerprofile', 'sellerprofilescsv.csv');
    }

    protected function getExtraListViewData($paginatedList, $contextualButtonBar = null): array
    {
        return $this->getConfigCrudDatagridViewData();
    }

    protected function getConfigCrudInlineEditableFields(): array
    {
        return ['profile_name'];
    }

    protected function getItemIdField(): string
    {
        return 'profile_id';
    }

    protected function initItemActions()
    {
        $langtoltip = $this->Langmodel->getMdl('cmp-toltip', $this->langId);
        $this->addItemAction('edit', $langtoltip['edit'] ?? '', company_url('userprofile/edit/'), 'mdi mdi-pencil');
        $this->addItemAction('delete', $langtoltip['delete'] ?? '', company_url('userprofile/delete/'), 'mdi mdi-delete', 'go-delete btn-warning');
    }

    protected function getPageTexts($pageContentObject = null): array
    {
        $lang = is_array($this->langArr) ? $this->langArr : [];
        $pageTitle = $this->resolveConfigCrudPageTitle($lang, 'pageTitle', 'list_profile_vendor');

        return [
            'pageTitle' => $pageTitle,
            'company_total_items_label' => $lang['total_profiles'] ?? '',
            'cantidad_productos' => $lang['total_profiles_found'] ?? '',
            'entityNamePlural' => $lang['list_profile_vendor'] ?? '',
            'entityName' => $lang['name_profile'] ?? '',
            'fields' => [
                'profile_id' => 'ID',
                'profile_name' => $lang['name_profile'] ?? '',
                'order_discount' => $lang['order_discount'] ?? '',
                'order_disc_price' => $lang['order_disc_price'] ?? '',
                'order_net_discount' => $lang['order_net_discount'] ?? '',
                'order_net_disc_price' => $lang['order_net_disc_price'] ?? '',
                'can_change_price' => $lang['change_price'] ?? '',
                'can_send_catalog' => $lang['send_catalog'] ?? '',
                'can_create_customer' => $lang['create_customer'] ?? '',
                'status' => $lang['status'] ?? '',
                'default' => $lang['default'] ?? '',
                'created' => $lang['created'] ?? '',
            ],
            'colmn_action' => $lang['actions'] ?? '',
            'colmn_status' => $lang['status'] ?? '',
        ] + $lang;
    }

    protected function getListItemFields($list)
    {
        if (empty($list)) {
            return [];
        }

        return [
            'profile_id' => 'profile_id',
            'profile_name' => 'profile_name',
            'order_discount' => 'order_discount',
            'can_change_price' => 'can_change_price',
            'can_send_catalog' => 'can_send_catalog',
            'can_create_customer' => 'can_create_customer',
            'status' => 'status',
        ];
    }

    protected function getFieldTemplates(): array
    {
        return [
            'can_change_price' => 'company/userprofile/list/boolean_field',
            'can_send_catalog' => 'company/userprofile/list/boolean_field',
            'can_create_customer' => 'company/userprofile/list/boolean_field',
        ];
    }
}

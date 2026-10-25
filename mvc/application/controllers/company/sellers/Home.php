<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Home extends Company_Controller
{

    use IsCRUD;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('vendor', 'company/seller/', 'sellers');
        $this->langArr = $this->getModuleLang('cmp-vendedor');
    }

    /* List */
    public function index()
    {
        $indexJsFiles = [
            'companyasset/company/enable-disable.js',
            'companyasset/company/common/delete-alert.js'
        ];

        foreach ($indexJsFiles as $indexJsFile) {
            $this->addJsFile(base_url($indexJsFile));
        }

        $this->list();
    }


    protected function initItemActions()
    {
        $langtoltip = $this->Langmodel->getMdl('cmp-toltip', $this->langId);
        $this->addItemAction('edit', $langtoltip['edit'] ?? 'Edit', company_url('sellers/edit/'), 'mdi mdi-pencil');
        $this->addItemAction('delete', $langtoltip['delete'] ?? 'Delete', company_url('sellers/delete/'), 'mdi mdi-delete', 'go-delete btn-warning');
    }

    protected function getPageTexts($pageContentObject = null): array
    {
        $langToggleStatusButton = $this->getModuleLang('cmp-enabled');
        $langBasics = $this->getModuleLang('cmp-option_basic');
        $langUser = $this->getModuleLang('cmp-user');

        return array_merge(
            $langBasics,
            $this->langArr,
            $langUser,
            [
                'company_total_items_label' => $this->langArr['total_sellers'] ?? 'Total sellers',
                'cantidad_productos' => $this->langArr['total_sellers_found'] ?? 'Total sellers found',
                'entityNamePlural' => $this->langArr['sellers'] ?? 'Sellers',
                'entityName' => $this->langArr['seller'] ?? 'Seller',
                'fields' => [
                    'id' => 'Id',
                    'image' => $this->langArr['image'] ?? 'Image',
                    'name' => $this->langArr['name'] ?? 'Name',
                    'email' => $this->langArr['email'] ?? 'Email',
                    'cellphone' => $this->langArr['cell_number'] ?? 'Cell Number',
                    'isDefault' => $this->langArr['defecto'] ?? 'By Default',
                    'created' => $this->langArr['alta'] ?? 'Join Date',
                ],
                'colmn_status' => $langUser['status'] ?? 'Status',
                'colmn_action' => $this->langArr['colmn_action'] ?? 'Actions',
                'option_enable' => $langToggleStatusButton['option_enable'],
                'option_disable' => $langToggleStatusButton['option_disable'],
                'loader-enable' => $langToggleStatusButton['loader-enabling'],
                'loader-disable' => $langToggleStatusButton['loader-disabling'],
                'expandSelectionInfo' => $expandSelectionInfo ?? null
            ]);
    }

    protected function getListItemFields($list)
    {
        return [
            'id',
            'image',
            'name',
            'email',
            'cellphone',
            'isDefault',
            'created'
        ];
    }

    protected function getSortableFields($list)
    {
        return [];
    }

    protected function getFieldTemplates(): array
    {
        return [
            'image' => 'company/seller/list/user_image_field',
            'name' => 'company/seller/list/user_name_field',
            'email' => 'company/seller/list/user_email_field',
            'cellphone' => 'company/seller/list/user_phone_field',
            'isDefault' => 'components/crud/default_field',
            'created' => 'components/crud/created_field',
        ];
    }

    protected function getFilterData(): array
    {
        return [
            'props' => ['id', 'company_id', 'user_id', 'phone_number', 'status', 'created'],
            'relations' => ['user:id,name,email,image,cellphone'],
        ];
    }
}

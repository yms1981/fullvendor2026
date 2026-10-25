<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Home extends Company_Controller
{
    use IsCRUD;
    use ConfigCrudListTools;

    protected $canChangeStatus = false;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('warehouse', 'company/warehouse/', 'warehouses');
        $this->langArr = $this->getModuleLang('cmp-warehouse');
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
        $toolsLang = $this->getModuleLang('config-crud-tools');
        $templateLabel = trim((string) (
            $this->langArr['btn_download_template']
            ?? ($toolsLang['btn_download_template'] ?? '')
        ));

        $this->listButtonBar = new ButtonBar();
        $this->listButtonBar->addLink(
            $this->langArr['add_new_deposit'] ?? $this->langArr['btn_new'] ?? '',
            $this->getAddLink(),
            'mdi mdi-plus-circle'
        );
        $this->listButtonBar->addLink(
            $this->langArr['import'] ?? '',
            company_url('warehouses/csvImport'),
            'mdi mdi-import'
        );
        $this->listButtonBar->addLink(
            trim((string) ($this->langArr['export'] ?? '')),
            company_url('warehouses/export'),
            'mdi mdi-download'
        );
        $this->listButtonBar->addLink(
            $templateLabel,
            import_template_download_url('warehousecsv.xlsx'),
            'mdi mdi-file-document-outline',
            [
                'download' => '',
                'class' => 'fv-config-crud-btn fv-config-crud-btn--template',
                'title' => $templateLabel,
            ]
        );
    }

    protected function initItemActions(): void
    {
        $langtoltip = $this->Langmodel->getMdl('cmp-toltip', $this->langId);
        $this->addItemAction('edit', $langtoltip['edit'] ?? 'Edit', company_url('warehouses/edit/'), 'mdi mdi-pencil');
        $this->addItemAction('delete', $langtoltip['delete'] ?? 'Delete', company_url('warehouses/delete/'), 'mdi mdi-delete', 'go-delete btn-warning');
    }

    protected function getPageTexts($pageContentObject = null): array
    {
        $langToggleStatusButton = $this->getModuleLang('cmp-enabled');
        $langArr = is_array($this->langArr) ? $this->langArr : [];
        $pageTitle = $this->resolveConfigCrudPageTitle(
            $langArr,
            'pageTitle',
            'list_page_title',
            'warehouses',
            'warehouse'
        );

        return [
            'pageTitle' => $pageTitle,
            'company_total_items_label' => $langArr['total_warehouses'] ?? '',
            'cantidad_productos' => $langArr['total_warehouses_found'] ?? '',
            'entityNamePlural' => $langArr['warehouses'] ?? '',
            'entityName' => $langArr['warehouse'] ?? '',
            'fields' => [
                'id' => '#',
                'name' => $langArr['name'] ?? '',
                'address' => $langArr['col_address'] ?? ($langArr['street'] ?? ''),
                'email' => $langArr['email'] ?? '',
                'phone_number' => $langArr['phone_number'] ?? '',
                'created_at' => $langArr['created'] ?? '',
            ],
            'colmn_status' => $langArr['status'] ?? '',
            'colmn_action' => $langArr['colmn_action'] ?? '',
            'option_enable' => $langToggleStatusButton['option_enable'] ?? '',
            'option_disable' => $langToggleStatusButton['option_disable'] ?? '',
            'loader-enable' => $langToggleStatusButton['loader-enabling'] ?? '',
            'loader-disable' => $langToggleStatusButton['loader-disabling'] ?? '',
        ] + $langArr;
    }

    protected function getListItemFields($list)
    {
        return [
            'id',
            'name',
            'address',
            'email',
            'phone_number',
            'created_at',
        ];
    }

    protected function getFieldTemplates(): array
    {
        return [
            'address' => 'company/warehouse/list/address_field',
            'email' => 'company/warehouse/list/email_field',
            'phone_number' => 'company/warehouse/list/phone_field',
            'created_at' => 'components/crud/created_field',
        ];
    }

    protected function getFilterData(): array
    {
        return [
            'props' => ['id', 'name', 'address_id', 'created_at'],
            'relations' => ['address.city', 'address.state', 'address.country', 'users'],
        ];
    }

    protected function getExtraListViewData($paginatedList, $contextualButtonBar = null): array
    {
        return $this->getConfigCrudDatagridViewData();
    }
}

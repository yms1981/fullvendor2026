<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Home extends Company_Controller
{
    use IsCRUD;
    use ConfigCrudListTools;
    use CrudBooleanFieldToggleAction;

    /** @var bool */
    protected $canChangeStatus = false;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('sellerprofiles', 'company/sellerprofile/', 'sellerprofile');
        $this->langArr = $this->getModuleLang('cmp-profile-vendor');
        $this->load->model('HelperLanguage');
    }

    public function index()
    {
        $indexJsFiles = [
            'companyasset/company/common/delete-alert.js',
            'companyasset/company/common/validation-alert.js',
            'companyasset/company/sellerprofile/modal_add.js',
            'companyasset/company/sellerprofile/modal_edit.js',
        ];

        foreach ($indexJsFiles as $indexJsFile) {
            $this->addJsFile(base_url($indexJsFile));
        }

        $this->addModal('company/sellerprofile/modals/add_profile');
        $this->addModal('company/sellerprofile/modals/edit_profile');

        $this->list();
    }

    protected function getExtraListViewData($paginatedList, $contextualButtonBar = null): array
    {
        $lang = $this->getConfigCrudLang();

        return array_merge($this->getConfigCrudDatagridViewData(), [
            'configCrudBooleanSwitch' => [
                'messages' => [
                    'success' => trim((string) ($lang['boolean_field_switch_success'] ?? '')),
                    'error' => trim((string) ($lang['boolean_field_switch_error'] ?? '')),
                ],
            ],
        ]);
    }

    protected function initListButtonBar(): void
    {
        $this->listButtonBar = new ButtonBar();
        $this->listButtonBar->addModalButton(
            $this->langArr['add_new_profile_vendor'] ?? '',
            '#modal_add_profile',
            'company/sellerprofile/modals/add_profile',
            'mdi mdi-plus-circle'
        );
        $this->appendConfigCrudListToolbar('sellerprofile', 'sellerprofilescsv.csv');
    }

    protected function initItemActions()
    {
        $langtoltip = $this->Langmodel->getMdl('cmp-toltip', $this->resolveConfigCrudLangId());
        $this->addItemAction(
            'edit',
            $langtoltip['edit'] ?? '',
            company_url('sellerprofile/edit/'),
            'mdi mdi-pencil'
        );
        $this->addItemAction(
            'delete',
            $langtoltip['delete'] ?? '',
            company_url('sellerprofile/delete/'),
            'mdi mdi-delete',
            'btn-warning'
        );
    }

    protected function getPageTexts($pageContentObject = null): array
    {
        $lang = is_array($this->langArr) ? $this->langArr : [];

        return [
            'pageTitle' => $this->resolveConfigCrudPageTitle($lang, 'pageTitle', 'list_profile_vendor'),
            'company_total_items_label' => $lang['total_profiles'] ?? '',
            'cantidad_productos' => $lang['total_profiles_found'] ?? '',
            'entityNamePlural' => $lang['list_profile_vendor'] ?? '',
            'entityName' => $lang['name_profile'] ?? '',
            'fields' => [
                'profile_name' => $lang['name_profile'] ?? '',
                'can_change_price' => $lang['change_price'] ?? '',
                'can_send_catalog' => $lang['send_catalog'] ?? '',
                'can_create_customer' => $lang['create_customer'] ?? '',
                'created' => $lang['created'] ?? '',
            ],
            'colmn_action' => $lang['actions'] ?? '',
        ] + $lang;
    }

    protected function getListItemFields($list)
    {
        return [
            'profile_name',
            'can_change_price',
            'can_send_catalog',
            'can_create_customer',
            'created',
        ];
    }

    protected function getSortableFields($list)
    {
        return [
            'profile_name',
            'created',
        ];
    }

    protected function getConfigCrudInlineEditableFields(): array
    {
        return ['profile_name'];
    }

    protected function getFieldTemplates(): array
    {
        return [
            'can_change_price' => 'company/sellerprofile/list/boolean_switch_field',
            'can_send_catalog' => 'company/sellerprofile/list/boolean_switch_field',
            'can_create_customer' => 'company/sellerprofile/list/boolean_switch_field',
            'created' => 'components/crud/created_field',
        ];
    }

    protected function getActionButtonTemplates(): array
    {
        return [
            'edit' => 'company/sellerprofile/list/edit_action_button',
            'delete' => 'company/sellerprofile/list/delete_action_button',
        ];
    }

    protected function getItemIdField(): string
    {
        return 'profile_id';
    }

    protected function getFilterData(): array
    {
        return [
            'company' => $this->getCompanyId(),
            'orderProperty' => 'profile_id',
            'orderDirection' => 'DESC',
            'props' => [
                'profile_id',
                'profile_name',
                'can_change_price',
                'can_send_catalog',
                'can_create_customer',
                'created',
                'default',
                'status',
            ],
        ];
    }

    protected function getToggleableBooleanFields(): array
    {
        return [
            'can_change_price',
            'can_send_catalog',
            'can_create_customer',
        ];
    }

    protected function getBooleanSwitchFieldTemplate(): string
    {
        return 'company/sellerprofile/list/boolean_switch_field';
    }

    /**
     * Alternar permiso booleano desde la grilla (Sellerprofile.php compite con la carpeta sellerprofile/).
     */
    public function toggleField($itemId, $fieldName = null): void
    {
        $this->runBooleanFieldToggle($itemId, $fieldName);
    }
}

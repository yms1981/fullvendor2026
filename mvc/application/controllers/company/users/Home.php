<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require_once APPPATH . 'controllers/company/users/Form.php';

class Home extends Form
{
    protected $canChangeStatus = true;
    public function __construct()
    {
        parent::__construct();
    }

    protected function getConfigCrudStatusSwitchConfig(): array
    {
        $config = parent::getConfigCrudStatusSwitchConfig();
        if (!isset($config['messages']) || !is_array($config['messages'])) {
            $config['messages'] = [];
        }
        $config['messages']['enabled'] = $this->usersLang('enabled_success');
        $config['messages']['disabled'] = $this->usersLang('disabled_success');
        $config['messages']['error'] = $this->usersLang('status_update_error');

        return $config;
    }

    protected function usersLang(string $key): string
    {
        $moduleLang = isset($this->langArr) && is_array($this->langArr) ? $this->langArr : null;

        return $this->resolveModuleLangString('cmp-user', $key, $moduleLang);
    }

    /* List */
    public function index()
    {
        $indexJsFiles = [
            'companyasset/js/sweetalert2.js',
            'companyasset/company/users/send_form.js',
            'companyasset/company/users/user_list_modal.js',
            'companyasset/company/users/user_edit_modal.js',
        ];

        foreach ($indexJsFiles as $indexJsFile) {
            $this->addJsFile(base_url($indexJsFile));
        }

        $this->list();
    }

    /**
     * Override list method to use custom view with tabs
     */
    public function list(ButtonBar $contextualButtonBar = null)
    {
        $tabType = $this->input->get('type');
        if (!$tabType || !in_array($tabType, ['manager', 'customer', 'seller', 'warehouse'], true)) {
            $tabType = 'manager';
        }

        $filterData = $this->getFilterData();
        // Customers (~1k+): no cargar all=true — el render cliente era 10+s.
        $heavyCustomerTab = ($tabType === 'customer');
        $useClientDatagrid = $this->usesConfigCrudDatagrid() && !$heavyCustomerTab;
        if ($useClientDatagrid) {
            $filterData['all'] = true;
        }
        $paginatedList = $this->getPaginatedList(
            $this->entityPath,
            $filterData,
            max(1, (int) ($this->input->get('page') ?? 1)),
            $useClientDatagrid ? 5000 : (int) ($this->input->get('per_page') ?? 25)
        );

        // Manejar errores de API (cuando devuelve array en lugar de objeto)
        if (is_array($paginatedList) || (is_object($paginatedList) && isset($paginatedList->error))) {
            $paginatedList = (object)[
                'data' => [],
                'total' => 0,
                'per_page' => 25,
                'current_page' => 1,
                'last_page' => 1,
                'from' => null,
                'to' => null
            ];
        }
        $paginatedList = $this->enrichCrudListDefaultFlags($paginatedList);
        if (method_exists($this, 'prepareConfigCrudPaginatedList')) {
            $paginatedList = $this->prepareConfigCrudPaginatedList($paginatedList);
        }

        $this->pagination->initialize(
            $this->getCodeIgniterPaginationConfig($paginatedList->total, $paginatedList->per_page)
        );

        $this->initListButtonBar();
        $this->initItemActions();
        $this->benchmark->mark('before-list-data');
        $listData = $this->getListData($paginatedList, $contextualButtonBar);
        $this->benchmark->mark('after-list-data');
        if ($useClientDatagrid) {
            $listData = array_merge($listData, $this->getConfigCrudDatagridViewData());
        } else {
            $listData['useConfigCrudDatagrid'] = false;
            $listData['hideServerPagination'] = false;
            $listData['hideServerFilter'] = true;
        }
        $listData['filterData'] = $filterData;
        
        // Agregar información de tabs
        $currentType = $tabType;
        $listData['currentType'] = $currentType;
        $listData['tabs'] = [
            'manager' => $this->langArr['tab_manager'] ?? 'Managers',
            'customer' => $this->langArr['tab_customer'] ?? 'Customers',
            'seller' => $this->langArr['tab_seller'] ?? 'Sellers',
            'warehouse' => $this->langArr['tab_warehouse'] ?? 'Warehouse Users',
        ];

        $this->fillEmptyUserFormLabels($listData['pageTexts']);
        // Asegurar claves avatar desde JSON (langmdl a menudo no las trae).
        $avatarKeys = [
            'avatar_change', 'avatar_modal_title', 'avatar_select_file', 'avatar_save',
            'avatar_cancel', 'avatar_hint', 'avatar_updated', 'avatar_uploading',
        ];
        $cmpUserJson = $this->getLangJsonSection('cmp-user');
        foreach ($avatarKeys as $ak) {
            $cur = isset($listData['pageTexts'][$ak]) ? trim((string) $listData['pageTexts'][$ak]) : '';
            if ($cur === '' && isset($cmpUserJson[$ak])) {
                $listData['pageTexts'][$ak] = (string) $cmpUserJson[$ak];
            }
        }
        $company = $listData['company'] ?? null;
        if (! $company && $this->getCompanyId()) {
            $company = $this->getObjectFromApi('company/' . $this->getCompanyId());
        }
        $listData['company'] = $company;
        $modalCtx = ['pageTexts' => $listData['pageTexts'], 'company' => $company];
        $this->injectCompanyUserPhoneCountryMeta($modalCtx);
        $listData['companyPhoneCountryMeta'] = $modalCtx['companyPhoneCountryMeta'] ?? [];
        $listData['companyUserCountryFlagsUrl'] = $modalCtx['companyUserCountryFlagsUrl'] ?? (rtrim((string) base_url(), '/') . '/images/flags/');

        $sp = $this->getObjectFromApi('sellerprofiles', [
            'status' => 1,
            'props' => ['profile_id', 'profile_name', 'status', 'default'],
            'all' => true,
        ]);
        $listData['userModalSellerProfiles'] = $this->hasError($sp) ? [] : $sp;

        $cust = $this->getObjectFromApi('customer', [
            'all' => true,
            'orderProperty' => 'name',
            'props' => ['customer_id', 'name', 'business_name', 'email'],
        ]);
        $listData['userModalCustomers'] = [];
        if (! $this->hasError($cust)) {
            if (is_array($cust)) {
                $listData['userModalCustomers'] = $cust;
            } elseif (is_object($cust) && isset($cust->data) && is_array($cust->data)) {
                $listData['userModalCustomers'] = $cust->data;
            }
        }
        $listData['userModalCustomerLabels'] = $this->getLangJsonSection('cmp-clientes');

        $wh = $this->getDataFromApi('warehouse', [
            'all' => true,
            'orderProperty' => 'name',
            'orderDirection' => 'asc',
            'props' => ['id', 'name', 'company_id'],
        ], true);
        $listData['userModalWarehouses'] = [];
        if (! $this->hasError($wh)) {
            foreach ($this->extractApiListRows($wh) as $row) {
                $item = is_array($row) ? $row : (array) (is_object($row) ? $row : []);
                $wid = (int) ($item['id'] ?? 0);
                if ($wid < 1) {
                    continue;
                }
                $listData['userModalWarehouses'][] = (object) [
                    'id' => $wid,
                    'name' => trim((string) ($item['name'] ?? '')),
                ];
            }
        }

        $listData['userTypes'] = ['manager', 'seller', 'warehouse', 'customer'];

        $reloadQ = ['type' => $tabType];
        $st = $this->input->get('status');
        if ($st !== null && $st !== '' && $st !== false) {
            $reloadQ['status'] = $st;
        }
        $sc = $this->input->get('search');
        if ($sc !== null && trim((string) $sc) !== '') {
            $reloadQ['search'] = trim((string) $sc);
        }
        $listData['userModalListReloadUrl'] = company_url('users?' . http_build_query($reloadQ));

        $planLimits = $this->fetchCompanyPlanLimits();
        $listData['planLimitBlock'] = $this->buildPlanLimitBlock($planLimits, 'sellers', 'cmp-user');
        
        // Usar vista personalizada con tabs
        $layout = isset($this->layout) ? $this->layout : 'company';
        $this->showContent('company/users/list_with_tabs', $listData, $layout);
    }

    protected function initItemActions(): void
    {
        $this->itemActions = [];
        $langtoltip = $this->Langmodel->getMdl('cmp-toltip', $this->langId);
        $this->addItemAction(
            'edit',
            $langtoltip['edit'] ?? ($this->langArr['edit'] ?? ''),
            company_url('users/edit/'),
            'mdi mdi-pencil'
        );
    }

    protected function getActionButtonTemplates(): array
    {
        return [
            'edit' => 'company/users/list/edit_action_button',
        ];
    }

    protected function getPageTexts($pageContentObject = null): array
    {
        $langToggleStatusButton = $this->getModuleLang('cmp-enabled');
        $lang = is_array($this->langArr) ? $this->langArr : [];
        $texts = [
                'pageTitle' => $this->resolveConfigCrudPageTitle($lang, 'pageTitle', 'list_users'),
                'company_total_items_label' => $this->langArr['total_users'] ?? 'Total users',
                'cantidad_productos' => $this->langArr['total_users_found'] ?? 'Total users found',
                'entityNamePlural' => $this->langArr['users'] ?? 'Users',
                'entityName' => $this->langArr['user'] ?? 'User',
                'fields' => [
                    'avatar' => $this->langArr['image'] ?? 'Avatar',
                    'name' => $this->langArr['name'] ?? 'Name',
                    'email' => 'Email',
//                    'phone' => 'By Default',
                    'role' => $this->langArr['role'] ?? 'Role',
                    'profile' => 'Profile',
                    'created_at' => $this->langArr['alta'] ?? 'Join Date',
                ],
                'colmn_status' => $this->langArr['status'] ?? 'Status',
                'colmn_action' => $this->langArr['colmn_action'] ?? 'Actions',
                'option_enable' => $langToggleStatusButton['option_enable'],
                'option_disable' => $langToggleStatusButton['option_disable'],
                'loader-enable' => $langToggleStatusButton['loader-enabling'],
                'loader-disable' => $langToggleStatusButton['loader-disabling'],
            ] + $this->langArr;
        return $texts;
    }

    protected function getListItemFields($list)
    {
        return [
            'avatar',
            'name',
            'email',
//            'phone',
            'role',
            'profile',
            'created_at'
        ];
    }

    protected function getSortableFields($list): array
    {
        return [
            'name',
            'email',
            'created_at',
        ];
    }

    protected function getFieldTemplates(): array
    {
        return [
            'avatar' => 'company/users/list/avatar_field',
            'name' => 'company/users/list/name_field',
            'role' => 'company/users/list/role_field',
            'profile' => 'company/users/list/profile_field',
            'created_at' => 'components/crud/created_field',
        ];
    }

    protected function getItemIdField(): string
    {
        return 'id';
    }

    protected function getStatusField(): string
    {
        return 'deleted_at';
    }

    protected function getFilterData(): array
    {
        $companyId = $this->getCompany();
        $filterData = [
            'withTrashed' => true,
            'props' => ['id', 'name', 'email', 'image', 'created_at', 'deleted_at'],
            'relations' => ['companyUsers'],
        ];
        
        // Agregar company_id para que el scope ForCompany cargue profileable
        if ($companyId) {
            $filterData['company_id'] = $companyId;
        }
        
        // Filtrar por tipo de rol (por defecto 'manager')
        $type = $this->input->get('type');
        if (!$type || !in_array($type, ['manager', 'customer', 'seller', 'warehouse'])) {
            $type = 'manager';
        }
        $filterData['type'] = $type;
        
        // Filtro de búsqueda
        $search = $this->input->get('search');
        if ($search && !empty(trim($search))) {
            $filterData['search'] = trim($search);
        }
        
        // Filtro de estado ('0' es falsy en PHP: no usar if ($status && ...))
        $status = $this->input->get('status');
        if ($status !== null && $status !== '' && $status !== 'all') {
            if ($status === '1') {
                // Activos: sin deleted_at
                $filterData['status'] = 'active';
            } elseif ($status === '0') {
                // Inactivos: con deleted_at
                $filterData['status'] = 'inactive';
            }
        }
        
        return $filterData;
    }

}

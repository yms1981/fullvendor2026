<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Home extends Company_Controller
{

    use IsCRUD;
    use ConfigCrudListTools;

    protected $hasMultipleSelection = true;
    public function __construct()
    {
        parent::__construct();
        $this->langId = (int) ($this->session->userdata('language_id') ?? 1);
        $this->initCrud('customer', 'company/customer/', 'customers');
        $this->langArr = $this->getModuleLang('cmp-clientes');
    }

    /**
     * Clientes usa paginación/filtros de servidor; no el DataGrid de Config CRUD
     * (evita all=true y hasta 5000 filas en una sola carga).
     */
    protected function isConfigCrudModule(): bool
    {
        return false;
    }

    /**
     * @return array<int, object>
     */
    protected function loadCustomerGroupList(): array
    {
        $companyId = $this->getCompanyId();

        return $this->extractApiListItems($this->cachedApiValue('custgroups:'.$companyId, 120, function () use ($companyId) {
            return $this->getObjectFromApi('customergroup', [
                'props' => ['group_id', 'name'],
                'all' => true,
                'company' => $companyId,
            ]);
        }));
    }

    /**
     * @return array<int, object>
     */
    protected function loadCustomerSellerList(): array
    {
        $companyId = $this->getCompanyId();

        return $this->extractApiListItems($this->cachedApiValue('custsellers:'.$companyId, 120, function () use ($companyId) {
            return $this->getObjectFromApi('vendor', [
                'props' => ['id', 'user_id'],
                'all' => true,
                'relations' => ['user'],
                'company' => $companyId,
            ]);
        }));
    }

    /**
     * @return array<int, object>
     */
    protected function loadCustomerTermList(): array
    {
        return $this->extractApiListItems($this->cachedApiValue('custterms', 300, function () {
            return $this->getObjectFromApi('termsofsale', [
                'props' => ['term_id', 'name'],
                'all' => true,
            ]);
        }));
    }

    /* List */
    public function index()
    {
        $indexJsFiles = [
            'companyasset/company/enable-disable.js',
            'companyasset/company/common/delete-alert.js',
            'companyasset/company/product/buscar_porfiltro.js', //centralizamos este js?
            'companyasset/company/customer/eventos.js',
        ];

        foreach ($indexJsFiles as $indexJsFile) {
            $this->addJsFile(base_url($indexJsFile));
        }

        $this->list($this->getContextualButtonBar());
    }

    /**
     * @return array<int, object>
     */
    protected function getCachedCountryListFull(): array
    {
        return $this->getPersistedOrCompute('api_customer_country_list_full', function () {
            $r = $this->getObjectFromApi('country', [
                'props' => ['id', 'name', 'sortname', 'phonecode', 'phonemask'],
                'all' => true,
            ]);

            return (is_array($r) && !isset($r['error'])) ? $r : [];
        });
    }

    protected function getDataForView($view = null, $dataForView = [])
    {
        $dataForView = parent::getDataForView($view, $dataForView);

        $dataForView['groupList'] = $this->loadCustomerGroupList();
        $dataForView['sellerList'] = $this->loadCustomerSellerList();
        $dataForView['termList'] = $this->loadCustomerTermList();
        $dataForView['countryList'] = $this->getCachedCountryListFull();
        
        // Cargar estados iniciales si hay un país seleccionado en el filtro
        $selectedCountryId = $this->input->get('paisId');
        if ($selectedCountryId && $selectedCountryId !== 'todo' && $selectedCountryId !== '') {
            $dataForView['stateList'] = $this->getPersistedOrCompute('api_state_country_' . $selectedCountryId, function () use ($selectedCountryId) {
                $r = $this->getObjectFromApi('state', [
                    'props' => ['id', 'name', 'country_id'],
                    'all' => true,
                    'country_id' => $selectedCountryId,
                ]);

                return (is_array($r) && !isset($r['error'])) ? $r : [];
            });
        } else {
            $dataForView['stateList'] = [];
        }
        
        // Crear un mapa de países por nombre para buscar sortname rápidamente
        $countryMap = [];
        if (isset($dataForView['countryList']) && is_array($dataForView['countryList'])) {
            foreach ($dataForView['countryList'] as $country) {
                $countryMap[$country->name] = $country->sortname ?? '';
            }
        }
        $dataForView['countryMap'] = $countryMap;
        
        // Pasar el token de API para usar en JavaScript
        $dataForView['apiToken'] = $this->getUserToken();

        $catalogCompanyId = $this->getCompanyId();
        $dataForView['catalogList'] = $this->cachedApiValue('catalogs:'.$catalogCompanyId, 120, function () {
            return $this->getObjectFromApi('catalog', ['all' => true]);
        });

        return $dataForView;
    }

    protected function initListButtonBar()
    {
        $this->listButtonBar = new ButtonBar();
        $this->listButtonBar->addLink($this->langArr['add'] ?? 'Agregar Cliente', company_url('customers/add'), 'mdi mdi-plus-circle');
        $this->listButtonBar->addLink($this->langArr['importar'], company_url('customers/csvImport'), 'mdi mdi-import');
        // Exportación directa a Excel (sin modal CSV).
        $this->listButtonBar->addLink(
            $this->langArr['exportar'] ?? 'Export',
            'javascript:void(0)',
            'mdi mdi-file-excel',
            [
                'id' => 'btn-export-customers',
                'class' => 'fv-customers-export-btn',
                'role' => 'button',
            ]
        );
    }

    protected function initItemActions()
    {
        $langtoltip = $this->Langmodel->getMdl('cmp-toltip', $this->langId);
        $viewIncomeLabel = $this->langArr['view_income'] ?? ($langtoltip['view_income'] ?? 'View Income');
        $this->addItemAction('view', $viewIncomeLabel, company_url('customers/income/'), 'mdi mdi-wallet', 'info');
        $this->addItemAction('edit', $langtoltip['edit'], company_url('customers/edit/'), 'mdi mdi-pencil');
        $this->addItemAction('delete', $langtoltip['delete'], company_url('customers/delete/'), 'mdi mdi-delete', 'go-delete btn-warning');
    }

    protected function getContextualButtonBar(): ButtonBar
    {
        $contextualButtonBar = new ButtonBar();

        $contextualButtonBar->addModalButton(
            $this->langArr['bnAssignseller'],
            "#modalasignSeller",
            'company/customer/modals/assign_seller',
            'mdi mdi-account-plus'
        );

        $contextualButtonBar->addModalButton(
            $this->langArr['btnRemoveSeller'],
            "#modalremoveSeller",
            'company/customer/modals/remove_seller',
            'mdi mdi-account-minus'
        );

        $contextualButtonBar->addModalButton(
            $this->langArr['send_catalog'],
            "#exampleModal",
            'company/customer/modals/send_catalog',
            'mdi mdi-send'
        );
        
        //action
        $contextualButtonBar->addButton($this->langArr['eliminar_grupo'], 'eliminarClienteGrupo();', 'mdi mdi-delete');

        return $contextualButtonBar;
    }

    protected function getPageTexts($pageContentObject = null): array
    {
        $langToggleStatusButton = $this->getModuleLang('cmp-enabled');
        $langBasics = $this->getModuleLang('cmp-option_basic');
        $langProducts = $this->getModuleLang('cmp-clientes');

        if (isset($pageContentObject)) {
            $expandSelectionInfo = sprintf(
                $this->langArr['alert_cantidad_clientes'],
                $pageContentObject->total,
                $pageContentObject->total
            );
        }

        return array_merge(
            $langBasics,
            $langProducts,
            $this->langArr,
            [
                'company_total_items_label' => $this->langArr['total_customers'] ?? 'Total customers',
                'cantidad_productos' => $this->langArr['total_customers_found'] ?? 'Total customers found',
                'entityNamePlural' => $this->langArr['customers'] ?? 'Customers',
                'entityName' => $this->langArr['customer'] ?? 'Customer',
                'fields' => [
                    'customer_id' => 'Id',
                    'cust_id_kor' => $this->langArr['field_cust_id_kor'] ?? 'ERP Id',
                    'business_name_extended' => $this->langArr['field_business_name_extended'] ?? 'Business Name',
                    'email' => 'Email',
                    'customer_group' => $this->langArr['field_customer_group'] ?? 'Customer Group',
                    'sellers' => $this->langArr['field_sellers'] ?? 'Assigned Seller',
                    'catalog_emails' => $this->langArr['field_catalog_emails'] ?? 'Send Catalog'
                ],
                'colmn_status' => $this->langArr['colmn_status'] ?? 'Enable/Disable',
                'colmn_action' => $this->langArr['colmn_action'] ?? 'Actions',
                'option_enable' => $langToggleStatusButton['option_enable'],
                'option_disable' => $langToggleStatusButton['option_disable'],
                'loader-enable' => $langToggleStatusButton['loader-enabling'],
                'loader-disable' => $langToggleStatusButton['loader-disabling'],
                'expandSelectionInfo' => $expandSelectionInfo ?? null,
                'pageTitle' => $this->langArr['titulo_lists']
                    ?? $this->langArr['customers']
                    ?? $this->langArr['customer']
                    ?? $this->entityPath,
            ]);
    }

    protected function getListItemFields($list)
    {
        return [
            'cust_id_kor',
            'business_name_extended',      
            'email',
            'customer_group',
            'sellers',
            'catalog_emails'
        ];
    }

    protected function getSortableFields($list)
    {
        return [
            'cust_id_kor'
        ];
    }

    protected function getFieldTemplates(): array
    {
        return [
            'customer_group' => 'company/customer/list/group_field',
            'sellers' => 'company/customer/list/sellers_field',
            'catalog_emails' => 'company/customer/list/send-catalog_button',
            'business_name_extended' => 'company/customer/list/business_name_field',
            'email' => 'company/customer/list/email_field',
        ];
    }

    protected function getFormFilterView(): string
    {
        return 'company/customer/form_filter';
    }
    protected function getStatusField(): string
    {
        return 'customer_status';
    }

    protected function getItemIdField(): string
    {
        return 'customer_id';
    }

    protected function getListData($paginatedList, $contextualButtonBar = null): array
    {
        // Llamar al método del trait IsCRUD directamente
        $paginationTexts = $this->Langmodel->getMdl('pagination');
        $modals = $this->getModals($contextualButtonBar);
        
        // Optimización: Extraer países únicos de los clientes en esta página y solo procesar esos
        $countryMap = [];
        $countryDataMap = []; // Mapa completo con phonecode y phonemask
        
        // Extraer países únicos de los clientes en esta página
        $uniqueCountryNames = [];
        if (is_array($paginatedList->data) && !empty($paginatedList->data)) {
            foreach ($paginatedList->data as $customer) {
                $countryName = trim($customer->commercial_country ?? '');
                if (!empty($countryName) && !in_array($countryName, $uniqueCountryNames)) {
                    $uniqueCountryNames[] = $countryName;
                }
            }
        }
        
        // Misma lista de países que en getDataForView (cache en sesión; evita 1 HTTP pesado por carga de listado)
        if (!empty($uniqueCountryNames)) {
            $countryList = $this->getCachedCountryListFull();

            if (is_array($countryList)) {
                // Convertir a array asociativo para búsqueda rápida
                $uniqueCountryNamesMap = array_flip($uniqueCountryNames);
                
                foreach ($countryList as $country) {
                    $countryName = $country->name ?? '';
                    // Solo procesar países que están en la lista de países usados
                    if (isset($uniqueCountryNamesMap[$countryName])) {
                        $countryMap[$countryName] = $country->sortname ?? '';
                        $countryDataMap[$countryName] = [
                            'sortname' => $country->sortname ?? '',
                            'phonecode' => $country->phonecode ?? '',
                            'phonemask' => $country->phonemask ?? ''
                        ];
                    }
                }
            }
        }
        
        $allFields = $this->getListItemFields($paginatedList->data);

        $pageTexts = $this->getPageTexts($paginatedList) + $paginationTexts;

        $listData = [
            'pageTitle' => $pageTexts['pageTitle'] ?? $this->entityPath,
            'addItem' => [
                'label' => $this->langArr['btn_new'] ?? 'New',
                'link' => $this->getAddLink(),
            ],
            'totalItemsCount' => $paginatedList->total,
            'page' => $paginatedList,
            'buttonBar' => isset($this->listButtonBar) ? $this->listButtonBar->getButtons() : [],
            'contextualButtonBar' => $contextualButtonBar ? $contextualButtonBar->getButtons() : [],
            'formFilterView' => $this->getFormFilterView(),
            'selectionExpansible' => $this->selectionExpansible,
            'pageTexts' => $pageTexts,
            'list' => $paginatedList->data,
            'fields' => array_values($allFields),
            'sortableFields' => $this->getSortableFields($paginatedList->data),
            'fieldTemplates' => $this->getFieldTemplates(),
            'actionButtonTemplates' => $this->getActionButtonTemplates(),
            'itemActions' => $this->itemActions,
            'itemIdField' => $this->getItemIdField(),
            'itemStatusField' => $this->getStatusField(),
            'modals' => $modals,
            'controllerUrl' => $this->paginationUrl(),
            'multipleSelection' => $this->hasMultipleSelection ?? false,
            'changeStatus' => $this->canChangeStatus ?? true,
            'countryMap' => $countryMap,
            'countryDataMap' => $countryDataMap, // Mapa completo con phonecode y phonemask
            'groupList' => $this->loadCustomerGroupList(),
            'apiToken' => $this->getUserToken(),
            'apiBaseUrl' => '/api',
            'showRowIndexColumn' => false,
        ];

        return $listData;
    }

    protected function getFilterData(): array
    {
        $sort = $this->input->get('sort');
        if ($sort === null || $sort === '') {
            $sort = 'modified_at';
        }
        $orderDirection = strtolower((string) ($this->input->get('sort_direction') ?? $this->input->get('orderDirection') ?? 'desc'));

        return array_merge(
            $this->getFormFilters(),
            [
                'sort' => $sort,
                'orderProperty' => $sort,
                'orderDirection' => $orderDirection,
                'props' => [
                    'customer_id',
                    'cust_id_kor',
                    'business_name',
                    'name',
                    'email',
                    'phone',
                    'cell_phone',
                    'catalog_emails',
                    'customer_status',
                    'group_id',
                    'commercial_address',
                    'commercial_delivery_address',
                    'commercial_country',
                    'commercial_state',
                    'commercial_city',
                    'commercial_zone',
                    'commercial_zip_code',
                    'customer_created_at',
                    'modified_at',
                ],
                'relations' => ['customer_group', 'sellers.user'],
            ]
        );
    }

    protected function getFormFilters(): array
    {
        $formFilter = [
            'search' => $this->input->get("prsearch"),
            'status' => $this->getStatusValue(),
            'input_todos' => $this->input->get("input_todos") === 'todos',
        ];

        $filters = [
            'grupoId' => 'group',
            'vendedorId' => 'seller',
            'termId' => 'term',
        ];

        foreach ($filters as $param => $key) {
            $value = $this->input->get($param);
            if ($value !== null && $value !== '' && $value !== 'todo') {
                $formFilter[$key] = $value;
            }
        }
        return $formFilter;
    }

    private function getStatusValue()
    {
        $status = $this->input->get("status");

        // Preservar "0" (DISABLED); no usar empty()/if ($status).
        if ($status === 'todo') {
            return 'all';
        }
        if ($status === null || $status === '') {
            return '1';
        }

        return (string) $status;
    }

}

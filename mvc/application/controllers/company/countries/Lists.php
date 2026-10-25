<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Lists extends Company_Controller
{
    use IsCRUD;

    protected $canChangeStatus = false;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('country', 'company/countries', 'countries');
        $this->langArr = $this->getModuleLang('cmp-countries');
    }

    /* List */
    public function index()
    {
        $filterData = $this->getFilterData();

        try {
            $paginatedList = $this->getPaginatedList(
                $this->entityPath,
                $filterData,
                $this->input->get('page') ?? 1,
                $this->input->get('per_page') ?? 25
            );
        } catch (\Throwable $e) {
            log_message('error', 'Countries Lists getPaginatedList: ' . $e->getMessage());
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

        // Normalizar: la API puede devolver array (error o formato antiguo) o objeto sin data/total
        if (is_array($paginatedList) && !isset($paginatedList['error'])) {
            $total = count($paginatedList);
            $paginatedList = (object)[
                'data' => $paginatedList,
                'total' => $total,
                'per_page' => $total ?: 25,
                'current_page' => 1,
                'last_page' => 1,
                'from' => $total > 0 ? 1 : null,
                'to' => $total
            ];
        } elseif (is_array($paginatedList) || (is_object($paginatedList) && isset($paginatedList->error)) || !is_object($paginatedList)) {
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

        // Asegurar estructura: Laravel paginator puede usar meta->total
        if (!isset($paginatedList->total) && isset($paginatedList->meta) && is_object($paginatedList->meta)) {
            $paginatedList->total = $paginatedList->meta->total ?? 0;
        }
        if (!isset($paginatedList->total)) {
            $paginatedList->total = 0;
        }
        if (!isset($paginatedList->per_page)) {
            $paginatedList->per_page = 25;
        }
        if (!isset($paginatedList->data)) {
            $paginatedList->data = [];
        }

        try {
            $this->pagination->initialize(
                $this->getCodeIgniterPaginationConfig($paginatedList->total, $paginatedList->per_page)
            );

            $this->initListButtonBar();
            $this->initItemActions();
            $this->benchmark->mark('before-list-data');
            $listData = $this->getListData($paginatedList, null);
            $this->benchmark->mark('after-list-data');
            $listData['filterData'] = $filterData;
            $layout = isset($this->layout) ? $this->layout : 'company';
            $this->showContent('components/crud/list', $listData, $layout);
        } catch (\Throwable $e) {
            log_message('error', 'Countries Lists render: ' . $e->getMessage());
            log_message('error', 'Countries Lists trace: ' . $e->getTraceAsString());
            try {
                $fallback = (object)[
                    'data' => [], 'total' => 0, 'per_page' => 25,
                    'current_page' => 1, 'last_page' => 1, 'from' => null, 'to' => null
                ];
                $this->pagination->initialize($this->getCodeIgniterPaginationConfig(0, 25));
                $this->initListButtonBar();
                $this->initItemActions();
                $listData = $this->getListData($fallback, null);
                $listData['filterData'] = $filterData;
                $this->showContent('components/crud/list', $listData, isset($this->layout) ? $this->layout : 'company');
            } catch (\Throwable $e2) {
                log_message('error', 'Countries Lists fallback: ' . $e2->getMessage());
                show_error('Error al cargar la lista de países.', 500, 'Error');
            }
        }
    }

    protected function initListButtonBar()
    {
        // No hay botones en esta vista de solo lectura
        $this->listButtonBar = new ButtonBar();
    }

    protected function initItemActions()
    {
        $this->addItemAction('view_states', $this->langArr['view_states'] ?? 'View States', 'javascript:void(0);', 'mdi mdi-map-marker', 'btn-info view-states-btn');
        $this->addItemAction('view_cities', $this->langArr['view_cities'] ?? 'View Cities', 'javascript:void(0);', 'mdi mdi-city', 'btn-info view-cities-btn');
    }

    protected function getActionButtonTemplates(): array
    {
        return [
            'view_states' => 'company/countries/list/view_states_button',
            'view_cities' => 'company/countries/list/view_cities_button',
        ];
    }

    protected function getPageTexts($pageContentObject = null): array
    {
        $texts = [
            'company_total_items_label' => $this->langArr['total_countries'] ?? 'Total countries',
            'cantidad_productos' => $this->langArr['total_countries_found'] ?? 'Total countries found',
            'entityNamePlural' => $this->langArr['countries'] ?? 'Countries',
            'entityName' => $this->langArr['country'] ?? 'Country',
            'search' => $this->langArr['search'] ?? 'Search',
            'search_placeholder' => $this->langArr['search_placeholder'] ?? 'Search by name or code',
            'seleccionar_por' => $this->langArr['seleccionar_por'] ?? 'Filter by',
            'fields' => [
                'country_flag' => '',
                'country_flag_spacer' => '',
                'name' => 'Name',
                'sortname' => 'Code',
                'phonecode' => 'Phone Code',
                'time_zone' => 'Time Zone'
            ],
            'colmn_action' => $this->langArr['colmn_action'] ?? 'Actions',
        ] + $this->langArr;
        return $texts;
    }

    protected function getFormFilterView(): string
    {
        return 'company/countries/country_filter';
    }

    protected function getListItemFields($list)
    {
        // Siempre retornar los campos fijos, independientemente de si la lista está vacía
        return [
            'country_flag',
            'country_flag_spacer',
            'name',
            'sortname',
            'phonecode',
            'time_zone'
        ];
    }

    protected function getSortableFields($list): array
    {
        // Retornar los mismos campos que getListItemFields
        return $this->getListItemFields($list);
    }

    protected function getFieldTemplates(): array
    {
        return [
            'country_flag' => 'company/countries/list/flag_avatar',
            'country_flag_spacer' => 'company/countries/list/spacer',
        ];
    }

    protected function getItemIdField(): string
    {
        return 'id';
    }

    protected function paginationUrl($url = '')
    {
        return company_url('countries/lists' . ($url ? '/' . ltrim($url, '/') : ''));
    }

    protected function getFilterData(): array
    {
        $filter = [
            'props' => ['id', 'name', 'sortname', 'phonecode', 'phonemask', 'time_zone'],
        ];
        $searchTerm = $this->input->get('prsearch');
        if (!empty($searchTerm) && trim($searchTerm) !== '') {
            $filter['search'] = trim($searchTerm);
        }
        return $filter;
    }

    protected function getListData($paginatedList, $contextualButtonBar = null): array
    {
        // Validar que paginatedList no sea null
        if (!isset($paginatedList) || !is_object($paginatedList)) {
            $paginatedList = (object)[
                'total' => 0,
                'per_page' => 25,
                'data' => []
            ];
        }
        
        $paginationTexts = $this->Langmodel->getMdl('pagination');
        if (!is_array($paginationTexts)) {
            $paginationTexts = [];
        }
        
        $modals = $this->getModals($contextualButtonBar);
        $modals[] = 'company/countries/modals/states';
        $modals[] = 'company/countries/modals/cities';
        
        // Asegurar que itemActions esté inicializado
        if (!isset($this->itemActions)) {
            $this->initItemActions();
        }
        
        // Validar que paginatedList tenga las propiedades necesarias
        $totalItemsCount = isset($paginatedList->total) ? $paginatedList->total : 0;
        $listData = isset($paginatedList->data) ? $paginatedList->data : [];
        
        // Asegurar que listData sea un array
        if (!is_array($listData)) {
            if (is_object($listData) && method_exists($listData, 'toArray')) {
                $listData = $listData->toArray();
            } elseif (is_object($listData) && ($listData instanceof \Traversable || is_iterable($listData))) {
                $listData = iterator_to_array($listData);
            } else {
                $listData = [];
            }
        }
        
        // Obtener los campos y filtrar id para ocultarlo de la visualización
        $allFields = $this->getListItemFields($listData);
        $visibleFields = array_filter($allFields, function($field) {
            return $field !== 'id';
        });
        
        $listDataArray = [
            'pageTitle' => $this->entityPath,
            'addItem' => null, // No permitir agregar desde esta vista
            'totalItemsCount' => $totalItemsCount,
            'page' => $paginatedList,
            'buttonBar' => isset($this->listButtonBar) ? $this->listButtonBar->getButtons() : [],
            'contextualButtonBar' => $contextualButtonBar ? $contextualButtonBar->getButtons() : [],
            'formFilterView' => $this->getFormFilterView(),
            'selectionExpansible' => $this->selectionExpansible,
            'pageTexts' => $this->getPageTexts($paginatedList) + $paginationTexts,
            'list' => $listData,
            'fields' => array_values($visibleFields),
            'sortableFields' => $this->getSortableFields($listData),
            'fieldTemplates' => $this->getFieldTemplates(),
            'actionButtonTemplates' => $this->getActionButtonTemplates(),
            'itemActions' => $this->itemActions ?? [],
            'itemIdField' => $this->getItemIdField(),
            'itemStatusField' => $this->getStatusField(),
            'modals' => $modals,
            'controllerUrl' => $this->paginationUrl(),
            'multipleSelection' => $this->hasMultipleSelection ?? false,
            'changeStatus' => false, // No permitir cambiar estado
            'apiToken' => $this->getUserToken(),
            'apiBaseUrl' => base_url('api'),
        ];
        
        return $listDataArray;
    }
}


<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Home extends Company_Controller
{
    protected $canChangeStatus = false;

    use IsCRUD;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('country', 'company/countries', 'countries');
        $this->langArr = $this->getModuleLang('cmp-countries');
    }

    /* List */
    public function index()
    {
        $this->addJsFile(base_url('companyasset/company/common/delete-alert.js'));
        $this->list();
    }

    protected function initListButtonBar()
    {
        $this->listButtonBar = new ButtonBar();
        $this->listButtonBar->addLink($this->langArr['btn_states'] ?? 'States', company_url('states'));
        $this->listButtonBar->addLink($this->langArr['btn_cities'] ?? 'Cities', company_url('cities'));
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
                    'country_flag_spacer' => '',  // Columna en blanco para separación
                    'name' => 'Name',
                    'phonemask' => 'Phone Mask',
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
        return [
            'country_flag',  // Avatar de bandera
            'country_flag_spacer',  // Columna en blanco para separación
            'name',
            'phonemask',    // Input editable (incluye phonecode como label)
            'time_zone'
        ];
    }

    protected function getFieldTemplates(): array
    {
        return [
            'country_flag' => 'company/countries/list/flag_avatar',
            'country_flag_spacer' => 'company/countries/list/spacer',  // Columna en blanco
            'phonemask' => 'company/countries/list/phonemask_input',
        ];
    }

    protected function getItemIdField(): string
    {
        return 'id';
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
        // Llamar al método del trait IsCRUD directamente
        $paginationTexts = $this->Langmodel->getMdl('pagination');
        $modals = $this->getModals($contextualButtonBar);
        
        // Agregar modales de estados y ciudades
        $modals[] = 'company/countries/modals/states';
        $modals[] = 'company/countries/modals/cities';
        
        // Asegurar que itemActions esté inicializado
        if (!isset($this->itemActions)) {
            $this->initItemActions();
        }
        
        // Obtener los campos y filtrar id para ocultarlo de la visualización
        $allFields = $this->getListItemFields($paginatedList->data);
        $visibleFields = array_filter($allFields, function($field) {
            return $field !== 'id'; // Ocultar id
        });
        
        $listData = [
            'pageTitle' => $this->entityPath,
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
            'pageTexts' => $this->getPageTexts($paginatedList) + $paginationTexts,
            'list' => $paginatedList->data,
            'fields' => array_values($visibleFields), // Usar solo los campos visibles
            'sortableFields' => $this->getSortableFields($paginatedList->data),
            'fieldTemplates' => $this->getFieldTemplates(),
            'actionButtonTemplates' => $this->getActionButtonTemplates(),
            'itemActions' => $this->itemActions ?? [],
            'itemIdField' => $this->getItemIdField(),
            'itemStatusField' => $this->getStatusField(),
            'modals' => $modals,
            'controllerUrl' => $this->paginationUrl(),
            'multipleSelection' => $this->hasMultipleSelection ?? false,
            'changeStatus' => $this->canChangeStatus ?? true,
            'apiToken' => $this->getUserToken(),
        ];
        
        return $listData;
    }
}

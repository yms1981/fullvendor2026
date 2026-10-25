<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Countrylists extends Company_Controller
{
    use IsCRUD;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('country', 'company/customer/countrylists', 'countries');
        $this->langArr = $this->getModuleLang('cmp-countries');
    }

    /* List */
    public function index()
    {
        $this->list();
    }

    protected function getPageTexts($pageContentObject = null): array
    {
        $texts = [
            'company_total_items_label' => $this->langArr['total_countries'] ?? 'Total countries',
            'cantidad_productos' => $this->langArr['total_countries_found'] ?? 'Total countries found',
            'entityNamePlural' => $this->langArr['countries'] ?? 'Countries',
            'entityName' => $this->langArr['country'] ?? 'Country',
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

    protected function getListItemFields($list)
    {
        return [
            'country_flag',
            'country_flag_spacer',
            'name',
            'sortname',
            'phonecode',
            'time_zone'
        ];
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

    protected function getFilterData(): array
    {
        return [
            'props' => ['id', 'name', 'sortname', 'phonecode', 'phonemask', 'time_zone'],
        ];
    }

    protected function getListData($paginatedList, $contextualButtonBar = null): array
    {
        $paginationTexts = $this->Langmodel->getMdl('pagination');
        $modals = $this->getModals($contextualButtonBar);
        
        // Obtener los campos y filtrar id para ocultarlo de la visualización
        $allFields = $this->getListItemFields($paginatedList->data);
        $visibleFields = array_filter($allFields, function($field) {
            return $field !== 'id';
        });
        
        $listData = [
            'pageTitle' => $this->entityPath,
            'addItem' => null, // No permitir agregar desde esta vista
            'totalItemsCount' => $paginatedList->total,
            'page' => $paginatedList,
            'buttonBar' => isset($this->listButtonBar) ? $this->listButtonBar->getButtons() : [],
            'contextualButtonBar' => $contextualButtonBar ? $contextualButtonBar->getButtons() : [],
            'formFilterView' => $this->getFormFilterView(),
            'selectionExpansible' => $this->selectionExpansible,
            'pageTexts' => $this->getPageTexts($paginatedList) + $paginationTexts,
            'list' => $paginatedList->data,
            'fields' => array_values($visibleFields),
            'sortableFields' => $this->getSortableFields($paginatedList->data),
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
        ];
        
        return $listData;
    }
}


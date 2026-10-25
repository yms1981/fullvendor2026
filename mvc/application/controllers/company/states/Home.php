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
        $this->initCrud('state', 'company/states', 'states');
        $this->langArr = $this->getModuleLang('cmp-states');
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
        $this->listButtonBar->addLink($this->langArr['btn_countries'] ?? 'Countries', company_url('countries'));
        $this->listButtonBar->addLink($this->langArr['btn_cities'] ?? 'Cities', company_url('cities'));
    }

    protected function initItemActions()
    {
        $this->addItemAction('view_countries', $this->langArr['view_countries'] ?? 'View Countries', 'javascript:void(0);', 'mdi mdi-map-marker', 'btn-info view-states-btn');
        $this->addItemAction('view_cities', $this->langArr['view_cities'] ?? 'View Cities', 'javascript:void(0);', 'mdi mdi-city', 'btn-info view-cities-btn');
    }

    protected function getPageTexts($pageContentObject = null): array
    {
        $texts = [
                'company_total_items_label' => $this->langArr['total_states'] ?? 'Total states',
                'cantidad_productos' => $this->langArr['total_states_found'] ?? 'Total states found',
                'entityNamePlural' => $this->langArr['states'] ?? 'States',
                'entityName' => $this->langArr['state'] ?? 'State',
                'fields' => [
                    'id' => 'State Id',
                    'name' => 'Name',
                    'country' => 'Country'
                ],
                'colmn_action' => $this->langArr['colmn_action'] ?? 'Actions',
            ] + $this->langArr;
        return $texts;
    }

    protected function getListItemFields($list)
    {
        return [
            'id',
            'name',
            'country'
        ];
    }

    protected function getFieldTemplates(): array
    {
        return [
            'country' => 'company/state/list/related_country_field'
        ];
    }

    protected function getItemIdField(): string
    {
        return 'id';
    }

    protected function getFilterData(): array
    {
        return [
            'props' => ['id', 'name', 'country_id'],
            'relations' => ['country'],
        ];
    }
}

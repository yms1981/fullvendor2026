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
        $this->initCrud('city', 'company/cities', 'cities');
        $this->langArr = $this->getModuleLang('cmp-cities');
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
        $this->listButtonBar->addLink($this->langArr['btn_states'] ?? 'States', company_url('states'));
    }

    protected function initItemActions()
    {
        $langtoltip = $this->Langmodel->getMdl('cmp-toltip', $this->langId);
        $this->addItemAction('edit', $langtoltip['edit'] ?? 'Edit', company_url('cities/edit/'), 'mdi mdi-pencil');
        $this->addItemAction('delete', $langtoltip['delete'] ?? 'Delete', company_url('cities/delete/'), 'mdi mdi-delete', 'go-delete btn-warning');
    }

    protected function getPageTexts($pageContentObject = null): array
    {
        $texts = [
                'company_total_items_label' => $this->langArr['total_cities'] ?? 'Total cities',
                'cantidad_productos' => $this->langArr['total_cities_found'] ?? 'Total cities found',
                'entityNamePlural' => $this->langArr['cities'] ?? 'Cities',
                'entityName' => $this->langArr['city'] ?? 'City',
                'fields' => [
                    'id' => 'City Id',
                    'name' => 'Name',
                    'state' => 'State'
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
            'state'
        ];
    }

    protected function getFieldTemplates(): array
    {
        return [
            'state' => 'company/city/list/related_state_field'
        ];
    }

    protected function getItemIdField(): string
    {
        return 'id';
    }


    protected function getFilterData(): array
    {
        return [
            'props' => ['id', 'name','state_id'],
            'relations' => ['state'],
        ];
    }
}

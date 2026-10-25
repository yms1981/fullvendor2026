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
        $this->initCrud('unit', 'company/unit/', 'units');
        $this->langArr = $this->getModuleLang('unit-languaje');
    }

    /* List */
    public function index()
    {
        $indexJsFiles = [
            'companyasset/company/enable-disable.js',
            'companyasset/company/common/delete-alert.js',
        ];

        foreach ($indexJsFiles as $indexJsFile) {
            $this->addJsFile(base_url($indexJsFile));
        }

        $this->list($this->getContextualButtonBar());
    }

    protected function getDataForView($view = null, $dataForView = [])
    {
        $dataForView = parent::getDataForView($view, $dataForView);
        $dataForView['itemStatusField'] = 'status';
        return $dataForView;
    }

    protected function initListButtonBar()
    {
        $this->listButtonBar = new ButtonBar();
        $this->listButtonBar->addLink(
            $this->langArr['btn_agregar_unidad'] ?? 'Agregar Unidad',
            company_url('units/add'),
            'mdi mdi-plus-circle'
        );
    }

    protected function initItemActions()
    {
        $langtoltip = $this->Langmodel->getMdl('cmp-toltip', $this->langId);
        $this->addItemAction('edit', $langtoltip['edit'], company_url('units/edit/'), 'mdi mdi-pencil');
        $this->addItemAction('delete', $langtoltip['delete'], company_url('units/delete/'), 'mdi mdi-delete', 'go-delete btn-warning');
    }

    protected function getContextualButtonBar(): ButtonBar
    {
        $buttonBar = new ButtonBar();
        return $buttonBar;
    }

    protected function getPageTexts($pageContentObject = null): array
    {
        $langToggleStatusButton = $this->getModuleLang('cmp-enabled');
        $texts = [
            'title' => $this->langArr['list_tipo_unidades'] ?? 'Lista de Unidades',
            'add' => $this->langArr['btn_agregar_unidad'] ?? 'Agregar Unidad',
            'company_total_items_label' => 'Total de unidades',
            'cantidad_productos' => 'Total de unidades encontrados',
            'entityNamePlural' => 'Unidades',
            'entityName' => 'Unidad',
            'fields' => [
                'unit' => $this->langArr['unidad_nombre'] ?? 'Nombre',
                'description' => $this->langArr['unidad_descripcion'] ?? 'Descripción',
                'isDefault' => $this->langArr['default-column'] ?? 'Por Defecto',
                'created' => $this->langArr['unidad_alta'] ?? 'Creado',
            ],
            'colmn_status' => $this->langArr['unidad_estado'] ?? 'Estado',
            'colmn_action' => $this->langArr['unidad_acciones'] ?? 'Acciones',
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
            'unit',
            'description',
            'isDefault',
            'created'
        ];
    }

    protected function getSortableFields($list)
    {
        return [
            'unit',
            'created',
        ];
    }

    protected function getFieldTemplates(): array
    {
        return [
            'isDefault' => 'components/crud/default_field',
            'created' => 'components/crud/created_field',
        ];
    }

    protected function getFormFilterView(): string
    {
        return '';
    }

    protected function getItemIdField(): string
    {
        return 'unit_id';
    }

    protected function getStatusField(): string
    {
        return 'status';
    }

    protected function getFilterData(): array
    {
        return [
            'company' => $this->getCompanyId(),
            'props' => ['unit_id', 'unit', 'description', 'created', 'status'],
        ];
    }

    protected function getPaginatedList(string $resource, array $filter, $page, $perPage)
    {
        $filter['page'] = $page;
        $filter['per_page'] = $perPage;

        return $this->getObjectFromApi($resource, $filter);
    }
}

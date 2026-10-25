<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Lists extends Company_Controller
{
    use IsCRUD;
    use IsAction;
    use ConfigCrudListTools;

    /** @var bool true cuando se llega desde productos: oculta columna default y acciones. */
    private bool $readonlyFromProducts = false;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('unit', 'company/unit/', 'units');
        $this->langArr = $this->getModuleLang('unit-languaje');
        $this->readonlyFromProducts = ((string) $this->input->get('from')) === 'products';
    }

    public function run()
    {
        $indexJsFiles = [
            'companyasset/company/enable-disable.js',
            'companyasset/company/common/delete-alert.js',
            'companyasset/company/common/validation-alert.js',
            'companyasset/company/units/modal_add.js',
            'companyasset/company/units/modal_edit.js'
        ];

        foreach ($indexJsFiles as $indexJsFile) {
            $this->addJsFile(base_url($indexJsFile));
        }

        // Agregar los modales
        $this->addModal('company/units/modals/add_unit');
        $this->addModal('company/units/modals/edit_unit');

        // Modo lectura desde productos: muestra acciones y switch pero bloqueados.
        if ($this->readonlyFromProducts) {
            $this->addModal('company/units/list/readonly_lock');
        }

        $this->list($this->getContextualButtonBar());
    }

    protected function getDataForView($view = null, $dataForView = [])
    {
        $dataForView = parent::getDataForView($view, $dataForView);
        // Mostrar columna enabled/disabled en el listado.
        $dataForView['changeStatus'] = true;
        return $dataForView;
    }

    protected function getExtraListViewData($paginatedList, $contextualButtonBar = null): array
    {
        return array_merge(
            $this->getConfigCrudDatagridViewData(),
            [
                'configCrudShowServerFilterWithDatagrid' => true,
            ]
        );
    }

    protected function initListButtonBar()
    {
        $this->listButtonBar = new ButtonBar();
        $this->listButtonBar->addModalButton(
            $this->langArr['btn_agregar_unidad'] ?? 'Agregar Unidad',
            "#modal_add_unit",
            'company/units/modals/add_unit',
            'mdi mdi-plus-circle'
        );
        $this->appendConfigCrudListToolbar('units', 'unitscsv.csv');
    }

    protected function initItemActions()
    {
        $langtoltip = $this->Langmodel->getMdl('cmp-toltip', $this->resolveConfigCrudLangId());
        $this->addItemAction('edit', $langtoltip['edit'], company_url('units/edit/'), 'mdi mdi-pencil');
        $this->addItemAction('delete', $langtoltip['delete'], company_url('units/delete/'), 'mdi mdi-delete', 'btn-go-delete btn-warning');
    }

    protected function getContextualButtonBar(): ButtonBar
    {
        $buttonBar = new ButtonBar();
        return $buttonBar;
    }

    protected function getPageTexts($pageContentObject = null): array
    {
        $langToggleStatusButton = $this->getModuleLang('cmp-enabled');
        $lang = is_array($this->langArr) ? $this->langArr : [];
        $texts = [
            'pageTitle' => $this->resolveConfigCrudPageTitle($lang, 'pageTitle', 'list_tipo_unidades'),
            'title' => $this->resolveConfigCrudPageTitle($lang, 'pageTitle', 'list_tipo_unidades'),
            'add' => $this->langArr['btn_agregar_unidad'] ?? 'Agregar Unidad',
            'company_total_items_label' => $this->langArr['total_units'] ?? 'Total units',
            'cantidad_productos' => $this->langArr['total_units_found'] ?? 'Total units found',
            'entityNamePlural' => $this->langArr['units'] ?? 'Units',
            'entityName' => $this->langArr['unit'] ?? 'Unit',
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
            'btn_filter' => $this->langArr['btn_filter'] ?? 'Filter',
            'seleccionar_por' => $this->langArr['seleccionar_por'] ?? 'Filter by',
            'search' => $this->langArr['search'] ?? 'Search',
            'search_placeholder' => $this->langArr['search_placeholder'] ?? 'Search by name or description'
        ] + $this->langArr;
        return $texts;
    }

    protected function getConfigCrudInlineEditableFields(): array
    {
        if ($this->readonlyFromProducts) {
            return [];
        }

        return ['unit', 'description'];
    }

    protected function getListItemFields($list)
    {
        if ($this->readonlyFromProducts) {
            return [
                'unit',
                'description',
                'created',
            ];
        }

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

    protected function getActionButtonTemplates(): array
    {
        return [
            'edit' => 'company/units/list/edit_action_button'
        ];
    }

    protected function getFormFilterView(): string
    {
        return 'company/units/unit_filter';
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
        $searchTerm = $this->input->get("prsearch");
        $filter = [
            'company' => $this->getCompanyId(),
            'props' => ['unit_id', 'unit', 'description', 'created', 'status'],
        ];
        
        // Agregar términos de búsqueda si existen
        if (!empty($searchTerm)) {
            $filter['terms'] = explode(' ', trim($searchTerm));
        }
        
        return $filter;
    }
}


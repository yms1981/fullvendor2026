<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Home extends Company_Controller
{

    use IsCRUD;
    use ConfigCrudListTools;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('termsofsale', 'company/term/', 'terms');
        $this->langArr = $this->getModuleLang('cmp-terms');
    }

    /* List */
    public function index()
    {
        $indexJsFiles = [
            'companyasset/company/enable-disable.js',
            'companyasset/company/common/delete-alert.js',
            'companyasset/company/common/validation-alert.js',
            'companyasset/company/terms/modal_add.js',
            'companyasset/company/terms/modal_edit.js',
        ];

        foreach ($indexJsFiles as $indexJsFile) {
            $this->addJsFile(base_url($indexJsFile));
        }

        // Agregar los modales
        $this->addModal('company/terms/modals/add_term');
        $this->addModal('company/terms/modals/edit_term');

        $this->list();
    }

    protected function getExtraListViewData($paginatedList, $contextualButtonBar = null): array
    {
        return $this->getConfigCrudDatagridViewData();
    }

    protected function initListButtonBar()
    {
        $this->listButtonBar = new ButtonBar();
        $this->listButtonBar->addModalButton(
            $this->langArr['add_new_term'] ?? 'Add New Term of Sale',
            "#modal_add_term",
            'company/terms/modals/add_term',
            'mdi mdi-plus-circle'
        );
        $this->appendConfigCrudListToolbar('terms', 'termscsv.xlsx');
    }

    protected function initItemActions()
    {
        $langtoltip = $this->Langmodel->getMdl('cmp-toltip', $this->resolveConfigCrudLangId());
        $this->addItemAction('edit', $langtoltip['edit'], company_url('terms/edit/'), 'mdi mdi-pencil');
        $this->addItemAction('delete', $langtoltip['delete'], company_url('terms/delete/'), 'mdi mdi-delete', 'go-delete btn-warning');
    }

    protected function getPageTexts($pageContentObject = null): array
    {
        $langToggleStatusButton = $this->getModuleLang('cmp-enabled');
        $lang = is_array($this->langArr) ? $this->langArr : [];
        $texts = [
                'pageTitle' => $this->resolveConfigCrudPageTitle($lang, 'pageTitle', 'titulo'),
                'company_total_items_label' => $this->langArr['total_terms'] ?? 'Total terms',
                'cantidad_productos' => $this->langArr['total_terms_found'] ?? 'Total terms found',
                'entityNamePlural' => $this->langArr['terms'] ?? 'Terms',
                'entityName' => $this->langArr['term'] ?? 'Term',
                'search' => $this->langArr['search'] ?? 'Search',
                'search_placeholder' => $this->langArr['search_placeholder'] ?? 'Search by name or ID',
                'seleccionar_por' => $this->langArr['seleccionar_por'] ?? 'Filter by',
                'fields' => [
                    'name' => $this->langArr['name'] ?? 'Name',
                    'isDefault' => $this->langArr['default'] ?? 'By Default',
                    'created' => $this->langArr['alta'] ?? ($this->langArr['created'] ?? 'Creation Date'),
                ],
                'colmn_status' => $this->langArr['status'],
                'colmn_action' => $this->langArr['actions'],
                'option_enable' => $langToggleStatusButton['option_enable'],
                'option_disable' => $langToggleStatusButton['option_disable'],
                'loader-enable' => $langToggleStatusButton['loader-enabling'],
                'loader-disable' => $langToggleStatusButton['loader-disabling'],
            ] + $this->langArr;
        return $texts;
    }

    protected function getFormFilterView(): string
    {
        return 'company/terms/term_filter';
    }

    protected function getListItemFields($list)
    {
        return [
            'name',
            'isDefault',
            'created'
        ];
    }

    protected function getSortableFields($list)
    {
        return [
            'name',
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
            'edit' => 'company/terms/list/edit_action_button'
        ];
    }

    protected function getItemIdField(): string
    {
        return 'term_id';
    }

    protected function getConfigCrudInlineEditableFields(): array
    {
        return ['name'];
    }

    protected function getStatusField(): string
    {
        return 'term_status';
    }

    protected function getListApiResource(): string
    {
        return 'termsofsale/all';
    }

    protected function getFilterData(): array
    {
        $searchTerm = $this->input->get('prsearch');
        $filter = [
            'company' => $this->getCompanyId(),
            'props' => ['term_id', 'name', 'term_status', 'created_at'],
        ];
        if (!$this->usesConfigCrudDatagrid() && !empty($searchTerm) && trim($searchTerm) !== '') {
            $filter['terms'] = array_values(array_filter(explode(' ', trim($searchTerm))));
        }
        return $filter;
    }
}

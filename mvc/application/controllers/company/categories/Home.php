<?php

class Home extends Company_Controller
{
    use IsCRUD;
    protected $hasMultipleSelection = true;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('category', 'company/categorias/', 'categories');
        $this->langArr = $this->getModuleLang('cmp_categorias');
    }

    public function index()
    {
        $this->load->helper('text');
        $indexJsFiles = [
            'companyasset/company/order/ImagePopover.js',
            'companyasset/company/enable-disable.js',
            'companyasset/company/common/validation-alert.js',
            'companyasset/company/common/delete-alert.js',
            'companyasset/company/categories/eventos.js',//centralize?
            'companyasset/company/categories/modal_edit.js',
            'companyasset/company/categories/modal_add.js'
        ];

        foreach ($indexJsFiles as $indexJsFile) {
            $this->addJsFile(base_url($indexJsFile));
        }

        // Agregar los modales
        $this->addModal('company/categorias/modals/edit_category');
        $this->addModal('company/categorias/modals/add_category');

        $this->list($this->getContextualButtonBar());
    }

    protected function getDataForView($view = null, $dataForView = [])
    {
        $dataForView = parent::getDataForView($view, $dataForView);
        $dataForView['itemStatusField'] = 'category_status';
        $dataForView['status_options'] = [
            ['value' => null, 'text' => strtoupper($this->langArr['todo'])],
            ['value' => '1', 'text' => strtoupper($this->langArr['activo'])],
            ['value' => '0', 'text' => strtoupper($this->langArr['inactivo'])]
        ];
        
        // Obtener traducciones de toltip para el botón eliminar
        $dataForView['deleteLang'] = $this->getModuleLang('cmp-toltip');

        return $dataForView;
    }

    protected function initListButtonBar()
    {
        $this->listButtonBar = new ButtonBar();
        $this->listButtonBar->addModalButton(
            $this->langArr['add'] ?? 'Nueva Categoría',
            "#modal_add_category",
            'company/categorias/modals/add_category',
            'mdi mdi-plus-circle'
        );
        $this->listButtonBar->addLink(
            $this->langArr['importar'],
            company_url('categories/csvImport'),
            'mdi mdi-import'
        );
        $this->listButtonBar->addLink(
            $this->langArr['btn-importar-imagenes'],
            company_url('categories/importImages'),
            'mdi mdi-camera'
        );
        // Exportación directa a Excel (sin modal).
        $this->listButtonBar->addLink(
            $this->langArr['exportar'] ?? 'Export',
            'javascript:void(0)',
            'mdi mdi-download',
            [
                'id' => 'btn-export-categories',
                'class' => 'fv-categories-export-btn',
                'role' => 'button',
            ]
        );
    }

    protected function initItemActions()
    {
        $this->addItemAction(
            'edit',
            $this->langArr['edit'] ?? '',
            company_url('categories/edit/'),
            'mdi mdi-pencil'
        );
        $this->addItemAction(
            'groups',
            $this->langArr['define_groups'] ?? '',
            company_url('categories/groups/'),
            'mdi mdi-folder-multiple',
            'info'
        );
        $this->addItemAction(
            'delete',
            $this->langArr['delete'] ?? '',
            company_url('categories/delete/'),
            'mdi mdi-delete',
            'go-delete btn-warning'
        );
    }

    protected function getContextualButtonBar(): ButtonBar
    {
        $contextualButtonBar = new ButtonBar();
        $contextualButtonBar->addButton(
            $this->langArr['bnBulkDelete'] ?? ($this->langArr['delete'] ?? 'Delete'),
            'eliminarCategoriasSeleccionadas();',
            'mdi mdi-delete-forever'
        );

        return $contextualButtonBar;
    }

    protected function getPageTexts($pageContentObject): array
    {
        // Asegurarse de que langArr es un array
        $langArr = is_array($this->langArr) ? $this->langArr : [];

        $pageTitle = trim((string) (
            $langArr['pageTitle']
            ?? $langArr['list_page_title']
            ?? $langArr['categorias']
            ?? $langArr['list_categories']
            ?? ''
        ));
        
        $texts = [
            'pageTitle' => $pageTitle,
            'title' => $pageTitle !== '' ? $pageTitle : ($langArr['list_categories'] ?? ''),
            'add' => $langArr['add'] ?? 'Add',
            'import' => $langArr['importar'] ?? 'Import',
            'export' => $langArr['exportar'] ?? 'Export',
            'import_images' => $langArr['btn-importar-imagenes'] ?? 'Import Images',
            'active_inactive' => $langArr['activos_innactivos'] ?? 'Active/Inactive',
            'all' => $langArr['todo'] ?? 'All',
            'active' => $langArr['activo'] ?? 'Active',
            'inactive' => $langArr['inactivo'] ?? 'Inactive',
            'search' => $langArr['buscar'] ?? 'Search',
            'image' => $langArr['imagen'] ?? 'Image',
            'select_imagen' => $langArr['select_imagen'] ?? 'Seleccionar imagen',
            'cargar_archivo' => $langArr['select_imagen'] ?? 'Cargar archivo',
            'category_name' => $langArr['nombre_categoria'] ?? 'Category Name',
            'created' => $langArr['creado'] ?? 'Created',
            'status' => $langArr['estado'] ?? 'Status',
            'actions' => $langArr['acciones'] ?? 'Actions',
            'fields' => [
                'image' => $langArr['imagen'] ?? 'Image',
                'category_name' => $langArr['nombre_categoria'] ?? 'Category Name',
                'category_created_at' => $langArr['creado'] ?? 'Created',
            ],
            'entityName' => $langArr['categoria'] ?? '',
            'entityNamePlural' => $langArr['categorias'] ?? '',
            'columnAll' => $langArr['todo'] ?? 'All',
            'colmn_status' => $langArr['estado'] ?? 'Status',
            'colmn_action' => $langArr['acciones'] ?? 'Actions',
            'marcar_todos' => $langArr['marcar_todos'] ?? 'Marcar todos?',
            'desmarcar_todos' => $langArr['desmarcar_todos'] ?? 'Desmarcar todos?',
            'toltip_paginas' => $langArr['toltip_paginas'] ?? 'Seleccionar todos en esta página',
            'cantidad_productos' => $langArr['cantidad_categorias'] ?? $langArr['total_categories_found'] ?? 'Total categories found',
            'btn_filter' => $langArr['btn_filter'] ?? 'Filter',
            'seleccionar_por' => $langArr['seleccionar_por'] ?? 'Filter by',
            'company_total_items_label' => $langArr['total_categories'] ?? 'Total categories',
            // Textos para el modal de editar
            'titulo_editar' => $langArr['titulo_editar'] ?? 'EDITAR CATEGORÍA',
            'titulo_agregar' => $langArr['titulo_agregar'] ?? 'AGREGAR CATEGORÍA',
            'boton_cancelar' => $langArr['boton_cancelar'] ?? $langArr['cancel'] ?? $langArr['cancelar'] ?? 'Cancelar',
            'boton_enviar' => $langArr['boton_enviar'] ?? $langArr['guardar'] ?? $langArr['save'] ?? '',
            'btn_edit' => $langArr['boton_enviar'] ?? $langArr['guardar'] ?? $langArr['save'] ?? $langArr['update'] ?? $langArr['actualizar'] ?? '',
            'titulo_exportacion' => $langArr['titulo_exportacion'] ?? '',
            'bnExport' => $langArr['bnExport'] ?? $langArr['exportar'] ?? '',
            'export_excel_hint' => $langArr['export_excel_hint'] ?? '',
            'error_export' => $langArr['error_export'] ?? '',
            'exportar' => $langArr['exportar'] ?? '',
            'edit' => $langArr['edit'] ?? '',
            'delete' => $langArr['delete'] ?? '',
            'define_groups' => $langArr['define_groups'] ?? '',
            'loading' => $langArr['loading'] ?? '',
            'load_form_error' => $langArr['load_form_error'] ?? '',
            'category_save_error_generic' => $langArr['category_save_error_generic'] ?? '',
            'ok' => $langArr['ok'] ?? '',
        ];

        // Completar con el resto del módulo (evita claves sueltas sin traducir).
        $texts = array_merge($langArr, $texts);        
        // Obtener textos comunes para botones si no están en langArr
        $commonTexts = $this->getCommonTexts();
        if (!isset($texts['boton_cancelar']) || $texts['boton_cancelar'] === 'Cancelar') {
            $texts['boton_cancelar'] = $commonTexts['cancel'] ?? $texts['boton_cancelar'] ?? 'Cancelar';
        }
        if (!isset($texts['btn_edit']) || $texts['btn_edit'] === '' || $texts['btn_edit'] === 'Actualizar' || $texts['btn_edit'] === 'Update') {
            $texts['btn_edit'] = $commonTexts['guardar'] ?? $commonTexts['save'] ?? $commonTexts['update'] ?? $texts['btn_edit'] ?? '';
        }
        
        // Asegurarse de que getModuleLang devuelve un array antes de sumar
        $enabledLang = $this->getModuleLang('cmp-enabled');
        if (is_array($enabledLang)) {
            $texts = array_merge($texts, $enabledLang);
        }

        if (isset($pageContentObject)) {
            $texts['expandSelectionInfo'] = sprintf(
                $langArr['alert_cantidad_categorias'] ?? $langArr['total_categories_found'] ?? 'There are a total of %d categories found',
                $pageContentObject->total ?? 0,
                $pageContentObject->total ?? 0
            );
        }

        return $texts;
    }

    protected function getFieldTemplates(): array
    {
        return [
            'category_name' => 'company/categorias/list/category_name_field',
            'category_created_at' => 'company/categorias/list/category_created_at_field',
            'image' => 'company/categorias/list/image_field'
        ];
    }

    protected function getActionButtonTemplates(): array
    {
        return [
            'edit' => 'company/categorias/list/edit_action_button',
            'groups' => 'company/categorias/list/groups_action_button',
            'delete' => 'company/categorias/list/delete_action_button',
        ];
    }

    protected function getFormFilterView(): string
    {
        return 'company/categorias/category_filter';
    }

    protected function getFilterData(): array
    {
        $searchTerm = $this->input->get("prsearch");
        $status = $this->input->get('status');
        $filter = [
            'company' => $this->getCompanyId(),
            'props' => [
                'cat_id',
                'category_name',
                'category_status',
                'category_created_at',
                'category_default',
                'image',
                'company_id',
            ],
            // imageRecord evita colisión con la FK `categories.image` (UUID string).
            'relations' => ['imageRecord'],
        ];
        
        // Agregar filtro de status solo si está definido
        if ($status !== null && $status !== '') {
            $filter['status'] = $status;
        }
        
        // Agregar términos de búsqueda si existen
        if (!empty($searchTerm)) {
            $filter['terms'] = explode(' ', trim($searchTerm));
        }
        
        return $filter;
    }

    protected function getItemIdField(): string
    {
        return 'cat_id';
    }

    protected function getSortableFields($list)
    {
        return [
            'nombre' => 'category_name',
            'created' => 'category_created_at',
            'status' => 'category_status'
        ];
    }

    protected function getListItemFields($list)
    {
        return [
            'image',
            'category_name',
            'category_created_at',
        ];
    }

    protected function getPaginatedList(string $resource, array $filter, $page, $perPage)
    {
        $filter['page'] = $page;
        $filter['per_page'] = $perPage;
        // imageRecord (no "image"): mantiene el UUID en categories.image al serializar.
        $filter['relations'] = ['imageRecord'];

        return $this->getObjectFromApi($resource, $filter);
    }
}

<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Lists extends Company_Controller
{
    protected $canChangeStatus = false;

    use IsCRUD;

    public function __construct()
    {
        parent::__construct();
        $this->initCrud('rols', 'company/rols', 'roles');
        $this->langArr = $this->getModuleLang('cmp-rol');
    }

    /* List */
    public function index()
    {
        $this->addJsFile(base_url('companyasset/company/common/delete-alert.js'));
        $this->addJsFile(base_url('companyasset/company/rols/modal_add.js'));
        $this->addJsFile(base_url('companyasset/company/rols/modal_edit.js'));
        $this->addModal('company/rols/modals/add_rol');
        $this->addModal('company/rols/modals/edit_rol');
        $this->list();
    }

    protected function initListButtonBar()
    {
        $this->listButtonBar = new ButtonBar();
        $this->listButtonBar->addModalButton(
            $this->langArr['add_new_rol'] ?? 'Crear Rol',
            "#modal_add_rol",
            'company/rols/modals/add_rol',
            'mdi mdi-plus-circle'
        );
    }

    protected function initItemActions()
    {
        $this->addItemAction('edit', $this->langArr['edit'] ?? 'Edit', 'javascript:void(0);', 'mdi mdi-pencil', 'success');
        $this->addItemAction('delete', $this->langArr['delete'] ?? 'Delete', 'javascript:void(0);', 'mdi mdi-delete', 'danger');
    }

    protected function getPageTexts($pageContentObject = null): array
    {
        $texts = [
                'company_total_items_label' => $this->langArr['total_roles'] ?? 'Total roles',
                'cantidad_productos' => $this->langArr['total_roles_found'] ?? 'Total roles found',
                'entityNamePlural' => $this->langArr['roles'] ?? 'Roles',
                'entityName' => $this->langArr['role'] ?? 'Role',
                'fields' => [
                    'rol_id' => 'ID',
                    'type' => $this->langArr['type'] ?? 'Type',
                    'desc_es' => $this->langArr['desc_es'] ?? 'Spanish Description',
                    'desc_en' => $this->langArr['desc_en'] ?? 'English Description',
                ],
                'colmn_action' => $this->langArr['colmn_action'] ?? 'Actions',
            ] + $this->langArr;
        return $texts;
    }

    protected function getListItemFields($list)
    {
        // Siempre retornar los campos fijos, independientemente de si la lista está vacía
        return [
            'rol_id',
            'type',
            'desc_es',
            'desc_en'
        ];
    }

    protected function getSortableFields($list): array
    {
        // Retornar los mismos campos que getListItemFields
        return $this->getListItemFields($list);
    }

    protected function getFieldTemplates(): array
    {
        return [];
    }

    protected function getActionButtonTemplates(): array
    {
        return [
            'edit' => 'company/rols/list/edit_action_button'
        ];
    }

    protected function getItemIdField(): string
    {
        return 'rol_id';
    }

    protected function getFilterData(): array
    {
        // Solo campos de la tabla rols, sin relaciones ni foreign keys
        return [
            'props' => ['rol_id', 'type', 'desc_es', 'desc_en'],
            // No hay relations porque la tabla rols no tiene foreign keys
        ];
    }

    protected function getListData($paginatedList, $contextualButtonBar = null): array
    {
        // Implementar getListData directamente (no se puede usar parent:: con traits)
        $paginationTexts = $this->Langmodel->getMdl('pagination');
        if (!is_array($paginationTexts)) {
            $paginationTexts = [];
        }
        
        $modals = $this->getModals($contextualButtonBar);
        
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
            } else {
                $listData = [];
            }
        }
        
        return [
            'pageTitle' => $this->entityPath,
            'addItem' => null, // Ya no se usa, el botón está en buttonBar
            'totalItemsCount' => $totalItemsCount,
            'page' => $paginatedList,
            'buttonBar' => isset($this->listButtonBar) ? $this->listButtonBar->getButtons() : [],
            'contextualButtonBar' => $contextualButtonBar ? $contextualButtonBar->getButtons() : [],
            'formFilterView' => $this->getFormFilterView(),
            'selectionExpansible' => $this->selectionExpansible,
            'pageTexts' => $this->getPageTexts($paginatedList) + $paginationTexts,
            'list' => $listData,
            'fields' => $this->getListItemFields($listData),
            'sortableFields' => $this->getSortableFields($listData),
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
    }
}

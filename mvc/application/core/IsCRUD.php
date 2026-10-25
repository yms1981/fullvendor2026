<?php

/**
 * @property CI_Loader $load
 * @property CI_Form_validation $form_validation
 */
trait IsCRUD
{
    use CrudDefaultListEnrichment;
    use ApiClient;
    use HasPagination;

    /** @var string The entity path name in the backend API */
    protected string $entityPath = '';

    protected string $pluralizedEntity;

    /** @var string The path relative to the view directory where the views of the module are found */
    protected string $moduleViewPath = '';
    /** @var array  the primary buttons for the main page button bar of this crud */
    protected array $barButtons = [];
    /** @var bool determines if the selection could be expanded to every item matching the criteria without seeing it on screen */
    protected bool $selectionExpansible = true;
    /** @var array the list actions available for each item of the list (each one a hashed array with tags: [label,action,iconClass]) */
    protected array $itemActions;
    /** @var ButtonBar $listButtonBar */
    protected ButtonBar $listButtonBar;

    /** @var array the list of modal views to include on the list/main page of the crud */
    protected $modals = [];

    protected $validationRules = [];
    protected $validationMessages = [];
    protected $validationAttributes = [];

    /**
     * Initializes the crud setting entity Path and module view path
     * @param string $entityPath the name of the entity/resource on the api
     * @param string $moduleViewPath the path of the views directory path of the module
     * @param string $pluralizedEntity the entity name in plural form
     * @return void
     */
    protected function initCrud($entityPath, $moduleViewPath, $pluralizedEntity): void
    {
        $this->entityPath = $entityPath;
        $this->moduleViewPath = $moduleViewPath;
        $this->pluralizedEntity = $pluralizedEntity;
        if(method_exists($this,'setupValidationRules'))
            $this->setupValidationRules();
    }

    protected function paginationUrl($url = '')
    {
        if (!empty($url) && !(str_starts_with($url, '/'))) {
            $url = '/' . $url;
        }
        return company_url($this->pluralizedEntity . $url);
    }


    /**
     * Initializes the button bar
     * @return void
     */
    protected function initListButtonBar(): void
    {
        $this->listButtonBar = new ButtonBar();
    }


    /**
     * Gets the link that the add button/link should have
     * @return string
     */
    protected function getAddLink()
    {
        return "{$this->paginationUrl()}/add";
    }

    /**
     * Adds an item action to the action
     * @param $actionName
     * @param $label
     * @param $action
     * @param $icon
     * @return void
     */
    protected function addItemAction($actionName, $label, $action, $icon = null, $buttonClass = null): void
    {
        $this->itemActions[$actionName] = [
            'label' => $label,
            'action' => $action,
            'iconClass' => $icon,
            'buttonClass' => $buttonClass
        ];;
    }

    /**
     * Initializes the item actions list
     * @return void
     */
    protected function initItemActions(): void
    {
        $this->itemActions = [];
    }

    /**
     * Gets the page texts of every crud pages together
     * @return array
     */
    protected function getPageTexts(): array
    {
        return [];
    }



    /**
     * Gets the filter information (data from request to use as filter data), it's expected to be overriden on every
     * controller that uses the trait
     * @return array
     */
    protected function getFilterData(): array
    {
        return [];
    }

    /**
     * CRUD de configuración: carga todos los registros y delega paginación/búsqueda al DataGrid cliente.
     */
    protected function usesConfigCrudDatagrid(): bool
    {
        return method_exists($this, 'isConfigCrudModule') && $this->isConfigCrudModule();
    }

    /**
     * Recurso API para el listado (por defecto entityPath; p. ej. termsofsale/all).
     */
    protected function getListApiResource(): string
    {
        return $this->entityPath;
    }

    /**
     * Garantiza que el listado API incluya la clave primaria (p. ej. term_id) para switches de estado.
     * No mezcla getListItemFields(): pueden incluir campos virtuales (isDefault) o alias (created vs created_at).
     */
    protected function ensureFilterPropsIncludeItemId(array $filter): array
    {
        if (!method_exists($this, 'getItemIdField')) {
            return $filter;
        }

        $idField = (string) $this->getItemIdField();
        if ($idField === '') {
            return $filter;
        }

        if (!isset($filter['props']) || !is_array($filter['props'])) {
            $filter['props'] = [$idField];

            return $filter;
        }

        if (!in_array($idField, $filter['props'], true) && !in_array('*', $filter['props'], true)) {
            array_unshift($filter['props'], $idField);
        }

        return $filter;
    }

    public function list(ButtonBar $contextualButtonBar = null)
    {
        $filterData = $this->ensureFilterPropsIncludeItemId($this->getFilterData());
        $page = (int) ($this->input->get('page') ?? 1);
        $perPage = (int) ($this->input->get('per_page') ?? 25);
        if ($this->usesConfigCrudDatagrid()) {
            $filterData['all'] = true;
            $page = 1;
            $perPage = 5000;
        }
        $paginatedList = $this->getPaginatedList(
            $this->getListApiResource(),
            $filterData,
            $page,
            $perPage
        );
        $paginatedList = $this->enrichCrudListDefaultFlags($paginatedList);
        if (method_exists($this, 'prepareConfigCrudPaginatedList')) {
            $paginatedList = $this->prepareConfigCrudPaginatedList($paginatedList);
        }

        $this->pagination->initialize(
            $this->getCodeIgniterPaginationConfig($paginatedList->total, $paginatedList->per_page)
        );

        $this->initListButtonBar();
        $this->initItemActions();
        $this->benchmark->mark('before-list-data');
        $listData = $this->getListData($paginatedList, $contextualButtonBar);
        $this->benchmark->mark('after-list-data');
        $listData['filterData'] = $filterData;
        // Usar el layout del controlador si está definido, sino usar 'company' por defecto
        $layout = isset($this->layout) ? $this->layout : 'company';
        $this->showContent('components/crud/list', $listData, $layout);
    }

    /**
     * Gets the default form (edit/create) data to load into the form display
     * @return array
     */
    protected function getDefaultFormData(): array
    {
        return [];
    }

    /**
     * Gets the form data to load the form data given the itemId or the default if no itemId is given
     * @param $itemId
     * @return array
     */
    protected function getFormData($itemId): array
    {
        if (!isset($itemId)) {
            return $this->getDefaultFormData();
        }
        try {
            return $this->getDataFromApi("{$this->entityPath}/{$itemId}");
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            return [];
        }
    }

    protected function validate() {
        return $this->form_validation->run() !== false;
    }


    /**
     * Displays the edit form
     * @return void
     */
    public function showEditForm()
    {
        $itemId = $this->input->get('id');

        // Usar el layout del controlador si está definido, sino usar 'company' por defecto
        $layout = isset($this->layout) ? $this->layout : 'company';
        $this->showContent('components/crud/form', $this->getFormData($itemId), $layout);
    }

    /**
     * Stores a new item of the entity
     * @return array
     */
    public function store($params=null)
    {
        try {
            if (!isset($params)) {
                $params = $this->input->post();
            }
            if ($this->validate()) {
                return $this->post("{$this->entityPath}", $params);
            }
            return [
                'error'   => true,
                'message' => $this->getValidationErrorMessage(),
                'errors'  => $this->form_validation->error_array()
            ];
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            return ['error' => 'Server error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Mensaje mostrado cuando falla la validación del formulario.
     * Sobrescribir en el controlador para usar el idioma configurado.
     */
    protected function getValidationErrorMessage()
    {
        return 'Validation error';
    }

    /**
     * Updates an item of the entity
     * @return array
     */
    public function updateItem($itemId,$params=null)
    {
        try {
            if(!isset($params)) $params = $this->input->post();
            return $this->put("{$this->entityPath}/{$itemId}", $params);
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            return ['error' => 'Server error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Deletes an item of the entity
     * @return array
     */
    public function destroy($id = null, $params = null)
    {
        $itemId = $id ?? $this->input->get('id');
        $params = $params ?? $this->input->post();

        try {
            return $this->apiDelete("{$this->entityPath}/{$itemId}", $params);
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            return ['error' => 'Server error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Gets the fields (or psuedo-fields) that the list should have for every item
     * @param $list
     * @return string[]|null
     */
    protected function getListItemFields($list)
    {
        $cols = array_keys((array)$list[0]);
        $fields = array_combine($cols, $cols);
        return $fields;
    }


    /**
     * Gets the sortable field list of the list
     * @return array
     */
    protected function getSortableFields($list): array
    {
        return $this->getListItemFields($list);
    }

    /**
     * Gets the array of field template view paths one for each customized column
     * @return array
     */
    protected function getFieldTemplates(): array
    {
        return [];
    }

    /**
     * Gets the array of action button view paths that each item will have on its action column
     * @return array
     */
    protected function getActionButtonTemplates(): array
    {
        return [];
    }

    /**
     * Gets the path of the template view of the filter of the list for the crud
     * @return string
     */
    protected function getFormFilterView(): string
    {
        return '';
    }


    /**
     * Gets the name of the crud model identifying column
     * @return string
     */
    protected function getItemIdField(): string
    {
        return 'id';
    }

    protected function getStatusField(): string
    {
        return 'status';
    }

    /**
     * @param string $modalViewPath
     * @return void
     */
    protected function addModal($modalViewPath): void
    {
        $this->modals[] = $modalViewPath;
    }
    protected function getModals(ButtonBar $contextualButtonBar = null): array
    {
        $contextualButtonBarModals = $contextualButtonBar ? $contextualButtonBar->getModals() : [];
        $listButtonBarModals = isset($this->listButtonBar) ? $this->listButtonBar->getModals() : [];
        return array_merge($this->modals, $listButtonBarModals, $contextualButtonBarModals);
    }

    /**
     * Claves extra para la vista del listado CRUD (p. ej. listCenterAllDataColumns).
     * Sobrescribir en el controlador que use el trait; no usar parent::getListData() para esto,
     * porque getListData vive en el trait, no en Company_Controller.
     *
     * @param object|null $paginatedList
     * @param ButtonBar|null $contextualButtonBar
     * @return array<string, mixed>
     */
    protected function getExtraListViewData($paginatedList, $contextualButtonBar = null): array
    {
        return [];
    }

    /**
     * @param object|null $paginatedList the page format of laravel pagination, with properties:
     * @param ButtonBar|null $contextualButtonBar
     * @return array
     */
    protected function getListData($paginatedList, $contextualButtonBar = null): array
    {
        $paginationTexts = $this->Langmodel->getMdl('pagination');
        $modals = $this->getModals($contextualButtonBar);
        $pageTexts = $this->getPageTexts($paginatedList) + $paginationTexts;
        return array_merge(
            [
            'pageTitle' => $pageTexts['pageTitle'] ?? $this->entityPath,
            'addItem' => [
                'label' => 'Nuevo',
                'link' => $this->getAddLink(),
            ],
            'totalItemsCount' => $paginatedList->total,
            'page' => $paginatedList,
            'buttonBar' => isset($this->listButtonBar) ? $this->listButtonBar->getButtons() : [],
            'contextualButtonBar' => $contextualButtonBar ? $contextualButtonBar->getButtons() : [],
            'formFilterView' => $this->getFormFilterView(),
            'selectionExpansible' => $this->selectionExpansible,
            'pageTexts' => $pageTexts,
            'list' => $paginatedList->data,
            'fields' => $this->getListItemFields($paginatedList->data),
            'sortableFields' => $this->getSortableFields($paginatedList->data),
            'fieldTemplates' => $this->getFieldTemplates(),
            'actionButtonTemplates' => $this->getActionButtonTemplates(),
            'itemActions' => $this->itemActions,
            'itemIdField' => $this->getItemIdField(),
            'itemStatusField' => $this->getStatusField(),
            'modals' => $modals,
            'controllerUrl' => $this->paginationUrl(),
            'multipleSelection' => $this->hasMultipleSelection ?? false,
            'changeStatus' => $this->canChangeStatus ?? true,
            ],
            $this->getExtraListViewData($paginatedList, $contextualButtonBar)
        );
    }

    /**
     * @param $request object
     * @return object|array|string
     */
    protected function  getBulkItems($request) {
        $pluralizedEntity = $this->pluralizedEntity;
        $bulkItemsFromRequestOrAll = json_decode($request->$pluralizedEntity);
        if(isset($request->all) || $request->input_todos == 'todos')
            $bulkItemsFromRequestOrAll = 'todos';

        return $bulkItemsFromRequestOrAll;
    }


    protected function getBulkRelatedItemIds($request,$relatedEntityProperty)
    {
        $bulkRelatedFromRequest = $request->$relatedEntityProperty;
        if(is_string($bulkRelatedFromRequest) && !empty(trim($bulkRelatedFromRequest)))
            $bulkRelatedFromRequest = json_decode($request->$relatedEntityProperty);
        return $bulkRelatedFromRequest;
    }

    protected function getParamsForBulkItems($request)
    {
        $params = [];
        $items = $this->getBulkItems($request);
        $params[$this->pluralizedEntity] = $items;
        $companyId = $this->getCompanyId();
        if($companyId && $companyId > 0)
            $params['company'] = $companyId;
        if(is_string($items) && $items=='todos')
        {
            $filterData = $this->getFilterData();
            $params = array_merge($filterData, $params);
            // Propagar exclusiones cuando se opera sobre "todos" los resultados del filtro.
            $excluded = $this->getBulkExcludedIds($request);
            if (!empty($excluded)) {
                $params['excluded_ids'] = $excluded;
            }
        }
        return $params;
    }

    /**
     * Lee y normaliza el listado de IDs excluidos de un bulk en modo "todos".
     * Acepta tanto JSON (string) como arrays.
     *
     * @param object $request
     * @return array<int,int|string>
     */
    protected function getBulkExcludedIds($request): array
    {
        if (!isset($request->excluded_ids)) {
            return [];
        }
        $raw = $request->excluded_ids;
        $list = $raw;
        if (is_string($raw) && trim($raw) !== '') {
            $decoded = json_decode($raw, true);
            $list = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($list)) {
            return [];
        }
        $normalized = [];
        foreach ($list as $v) {
            if (is_numeric($v)) {
                $iv = (int) $v;
                if ($iv > 0) {
                    $normalized[] = $iv;
                }
                continue;
            }
            $sv = trim((string) $v);
            if ($sv !== '') {
                $normalized[] = $sv;
            }
        }
        return $normalized;
    }

    /**
     * Updates the given property to the items received in the request (ids array or "todos"), then flashes the result
     * @param $request object
     * @param $itemProperty string the property of the request where the items are
     * @param $failMessage string the message to show when the bulk update fails
     * @param $successMessage string the message to show when the bulk update succeeds
     * @return string|false
     */
    public function bulkUpdate($request,$itemProperty,$failMessage,$successMessage)
    {
        $paramsBase = $this->getParamsForBulkItems($request);
        $params = array_merge($paramsBase, [
            $this->pluralizedEntity => $paramsBase[$this->pluralizedEntity]
            , $itemProperty => $request->$itemProperty
        ]);
        try {

            $response = $this->put("{$this->entityPath}/{$itemProperty}",$params);
            $message_key = $successMessage;
            if($response) {
                $this->Commonmodel->show_sessionmsg($response." ". $this->langArr[$message_key], "success");
                return json_encode(['success' => true]);
            }
            else
            {
                $this->Commonmodel->show_sessionmsg($failMessage, "warning");
                return json_encode(['success' => false, 'msg' => $failMessage]);
            }
        }
        catch (\GuzzleHttp\Exception\GuzzleException $e) {
            return json_encode(['success' => false, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * @param $relateAction string put or delete
     * @param $request object with the items to relate/unrelate, and the related items to relate/unrelate, the filter in case of using filter to determine the items to relate/unrelate
     * @param $relateEntityProperty string The property of the request where the related entity ids should be got
     * @param $relatedResourcePath string the resource path of the related entity
     * @param $itemPropertyPath string the property path of the item entity to relate/unrelate (to build $relatedResourcePath/$relatedItemId/$itemPropertyPath uri to put/delete)
     * @param $relatedMissigErrorMessage string the lang label of the error when the related items are unset
     * @param $relatedActionDoneMessage string the lang label of the success message when the related items are set
     * @param $relationDetailMessage string the lang label of the success message when the related items are set
     * @return string|false
     */
    public function bulkActionRelated(
        $relateAction,
        $request,
        $relateEntityProperty,
        $relatedResourcePath,
        $itemPropertyPath,
        $relatedNameProperty,
        $relatedMissigErrorMessage,
        $relatedActionDoneMessage,
        $relationDetailMessage
    )
    {
        $relatedItemsIds = $this->getBulkRelatedItemIds($request, $relateEntityProperty);
        $amountToRelate = count($relatedItemsIds);

        if (!isset($relatedItemsIds) || $amountToRelate <= 0) {
            return json_encode(['success' => false, 'msg' => $this->langArr[$relatedMissigErrorMessage]]);
        }

        try {
            $params = $this->getParamsForBulkItems($request);
            foreach ($relatedItemsIds as $relatedItemId) {
                $updresp =  $this->$relateAction("{$relatedResourcePath}/{$relatedItemId}/{$itemPropertyPath}", $params);

            }
            $msg = "{$this->langArr[$relatedActionDoneMessage]} ";
            if($amountToRelate == 1)
            {
                $related = $this->getDataFromApi(
                    "{$relatedResourcePath}/$relatedItemId"
                );
                $msg .=  $related[$relatedNameProperty];
            }
            else
                $msg .=  $amountToRelate . " " . $this->langArr[$relationDetailMessage];

            $translatedPluralizedEntityName = $this->langArr[$this->pluralizedEntity];
            $itemsRelatedAmount = is_array($params[$this->pluralizedEntity])? count($params[$this->pluralizedEntity]) : $params[$this->pluralizedEntity];
            $msg .= " $itemsRelatedAmount  $translatedPluralizedEntityName";
            $this->Commonmodel->show_sessionmsg($msg, "success");
            return json_encode(['success' => true]);
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            return json_encode(['success' => false, 'msg' => $e->getMessage()]);
        }
    }

    /**
     * @param $request object with the items to relate/unrelate, and the related items to relate/unrelate, the filter in case of using filter to determine the items to relate/unrelate
     * @param $relateEntityProperty string The property of the request where the related entity ids should be got
     * @param $relatedResourcePath string the resource path of the related entity
     * @param $itemPropertyPath string the property path of the item entity to relate/unrelate (to build $relatedResourcePath/$relatedItemId/$itemPropertyPath uri to put/delete)
     * @param $relatedNameProperty
     * @param $relatedConfirmedMsg string the lang label of the success message when the related items are set
     * @param $relatedMissigErrorMessage string the lang label of the error when the related items are unset
     * @param $relationDetailMsg string the lang label of the success message when the related items are set
     * @return string|false
     */
    public function bulkUnrelate(
        $request,
        $relatedEntityProperty,
        $relatedResourcePath,
        $itemPropertyPath,
        $relatedNameProperty,
        $relatedMissingErrorMsg,
        $unrelatedConfirmedMsg,
        $relationDetailMsg
    )
    {
        return $this->bulkActionRelated(
            'apiDelete',
            $request,
            $relatedEntityProperty,
            $relatedResourcePath,
            $itemPropertyPath,
            $relatedNameProperty,
            $relatedMissingErrorMsg,
            $unrelatedConfirmedMsg,
            $relationDetailMsg
        );
    }

    /**
     * Relates or Unrelates a set of given related items to the list of items for the given entity (bulk N - M add/remove)
     * @param $request object with the items to relate/unrelate, and the related items to relate/unrelate, the filter in case of using filter to determine the items to relate/unrelate
     * @param $relateEntityProperty string The property of the request where the related entity ids should be got
     * @param $relatedResourcePath string the resource path of the related entity
     * @param $itemPropertyPath string the property path of the item entity to relate/unrelate (to build $relatedResourcePath/$relatedItemId/$itemPropertyPath uri to put/delete)
     * @param $relatedNameProperty
     * @param $relatedMissigErrorMsg string the lang label of the error when the related items are unset
     * @param $relatedConfirmedMsg string the lang label of the success message when the related items are set
     * @param $relationDetailMsg string the lang label of the success message when the related items are set
     * @return string|false
     */
    public function bulkRelate(
        $request,
        $relateEntityProperty,
        $relatedResourcePath,
        $itemPropertyPath,
        $relatedNameProperty,
        $relatedMissigErrorMsg,
        $relatedConfirmedMsg,
        $relationDetailMsg
    )
    {
       return $this->bulkActionRelated(
           'put',
           $request,
           $relateEntityProperty,
           $relatedResourcePath,
           $itemPropertyPath,
           $relatedNameProperty,
           $relatedMissigErrorMsg,
           $relatedConfirmedMsg,
           $relationDetailMsg
       );
    }

}

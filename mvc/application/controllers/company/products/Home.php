<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * @property PrintModel $PrintModel
 * @property GroupModel $GroupModel
 * @property Langmodel $Langmodel
 * @property HelperLanguage $HelperLanguage
 */
class Home extends Company_Controller
{

    protected $hasMultipleSelection = true;

    use IsCRUD;
    use ConfigCrudListTools;

    public $mensajes_producto = array();
    
	

    public function __construct()
    {
        parent::__construct();
        $this->benchmark->mark('module_start');
        $this->langArr = $this->getModuleLang('cmp-products');
        $this->initCrud('product', 'company/product/', 'products');
    }

    /**
     * Productos usa paginación/filtros de servidor propios; no el DataGrid de Config CRUD.
     * Se usa ConfigCrudListTools solo para exportar Excel (sendConfigExcelDownload).
     */
    protected function isConfigCrudModule(): bool
    {
        return false;
    }


    /**
     * Oculta la columna # y no muestra la columna Id (pro_id sigue en cada fila para checkboxes y acciones).
     */
    protected function getExtraListViewData($paginatedList, $contextualButtonBar = null): array
    {
        $addToCatalogProductId = (int) ($this->session->flashdata('add_to_catalog_product_id') ?: 0);

        return [
            'showRowIndexColumn' => false,
            'listCompanyId' => (int) $this->getCompanyId(),
            'enableProductViewModes' => true,
            'addToCatalogProductId' => $addToCatalogProductId > 0 ? $addToCatalogProductId : null,
            'hideExpandSelectionAlert' => true,
        ];
    }

    protected function getBenchmarkMessagesContent()
    {
       
        $this->addBenchmarkMessage('module_start', 'show-content-end', 'Carga total de pagina completa');
        $this->addBenchmarkMessage('load-view-layout-start', 'show-content-end');
        $this->addBenchmarkMessage('company_showcontent_start', 'navigation');
        $this->addBenchmarkMessage('navigation', 'configmenu');
        $this->addBenchmarkMessage('configmenu', 'company');
        //list
        $this->addBenchmarkMessage('index_start', 'products-data-for-view');
        $this->addBenchmarkMessage('index_start', 'before-list-data');
        $this->addBenchmarkMessage('before-list-data', 'after-list-data');
        $this->addBenchmarkMessage('products-data-for-view', 'category-list');
        $this->addBenchmarkMessage('category-list', 'locations');
        $this->addBenchmarkMessage('locations', 'total-items-count');
        $this->addBenchmarkMessage('total-items-count', 'catalog-list');

        $this->addBenchmarkMessage('module_start', 'company');
        $this->addBenchmarkMessage('company', 'show-content-end');
        //$this->addBenchmarkMessage('Monedas', json_encode($d));
        //$this->addBenchmarkMessage('Monedas', json_encode($d1));
        //$this->addBenchmarkMessage('Comp', 'companyId');
        return parent::getBenchmarkMessagesContent();
    }

    private function addCatalogList(&$data, $companyId)
    {
        try {
            $response = $this->getObjectFromApi("company/{$companyId}/catalogs");
            if (is_array($response) && isset($response['error'])) {
                $data['cataloglist'] = [];
                return;
            }
            if (is_object($response) && isset($response->data)) {
                $data['cataloglist'] = is_array($response->data) ? $response->data : [];
                return;
            }
            $data['cataloglist'] = is_array($response) ? $response : [];
        } catch (\Throwable $e) {
            log_message('error', 'addCatalogList: ' . $e->getMessage());
            $data['cataloglist'] = [];
        }
    }

    private function addCurrencyList(&$data, $companyId)
    {
        $data['cursymbol'] = $this->getObjectFromApi("company/{$companyId}/currency-code");
    }

    /**
     * @return array|object
     */
    /**
     * Ubicaciones distintas de productos de la empresa (siempre fresco; sin caché de sesión).
     *
     * @return list<string>
     */
    private function getLocations()
    {
        // Invalidar caché antigua (persistía solo el primer fetch y ocultaba ubicaciones nuevas).
        $cacheKey = 'locations-' . $this->getCompany();
        if (method_exists($this, 'persist')) {
            $this->persist($cacheKey, null);
        }

        try {
            // associative=true: lista JSON → array PHP (getObjectFromApi usa false y a veces stdClass).
            $response = $this->getDataFromApi("company/{$this->getCompany()}/products/locations");
            if (is_array($response) && isset($response['error'])) {
                return [];
            }
            if (is_object($response)) {
                if (isset($response->data) && (is_array($response->data) || is_object($response->data))) {
                    $response = $response->data;
                }
                $response = array_values((array) $response);
            }
            if (! is_array($response)) {
                return [];
            }
            // Respuesta envuelta tipo { data: [...] }
            if (isset($response['data']) && is_array($response['data'])) {
                $response = $response['data'];
            }

            $locations = [];
            foreach ($response as $item) {
                if ($item === null || $item === '' || is_array($item) || is_object($item)) {
                    continue;
                }
                $part = trim((string) $item);
                if ($part === '') {
                    continue;
                }
                $key = function_exists('mb_strtolower') ? mb_strtolower($part, 'UTF-8') : strtolower($part);
                if (! isset($locations[$key])) {
                    $locations[$key] = $part;
                }
            }

            $list = array_values($locations);
            natcasesort($list);

            return array_values($list);
        } catch (\Throwable $e) {
            log_message('error', 'getLocations: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * Add common view data to the data built to showContent's data
     * @param $view
     * @param $dataForView
     * @return array|mixed
     */
    protected function getDataForView($view = null, $dataForView = [])
    {
        $dataForView = parent::getDataForView($view, $dataForView);

        $companyId = $this->getCompanyId();

        $this->benchmark->mark('products-data-for-view');
        try {
            $catResponse = $this->getObjectFromApi(
                'category',
                [
                    'all' => 1,
                    'props' => ['cat_id', 'category_name', 'image'],
                    'relations' => ['image'],
                    'sort' => 'category_name',
                    'companyId' => $this->getCompany()
                ]
            );
            $dataForView['categorylist'] = (is_array($catResponse) && isset($catResponse['error'])) ? [] : (is_array($catResponse) ? $catResponse : (is_object($catResponse) && isset($catResponse->data) ? $catResponse->data : []));
        } catch (\Throwable $e) {
            log_message('error', 'getDataForView categorylist: ' . $e->getMessage());
            $dataForView['categorylist'] = [];
        }

        $this->benchmark->mark('category-list');
        try {
            $locations = $this->getLocations();
            $dataForView['locations'] = $locations;
        } catch (\Throwable $e) {
            log_message('error', 'getDataForView locations: ' . $e->getMessage());
            $dataForView['locations'] = [];
        }
        $this->benchmark->mark('locations');
        try {
            $countResponse = $this->getDataFromApi("company/{$this->getCompanyId()}/products/count");
            $dataForView['companyTotalItemsCount'] = (is_array($countResponse) && isset($countResponse['error'])) ? 0 : $countResponse;
        } catch (\Throwable $e) {
            log_message('error', 'getDataForView companyTotalItemsCount: ' . $e->getMessage());
            $dataForView['companyTotalItemsCount'] = 0;
        }
        $this->benchmark->mark('total-items-count');
        $this->addCatalogList($dataForView, $companyId);
        try {
            $this->addCurrencyList($dataForView, $companyId);
        } catch (\Throwable $e) {
            log_message('error', 'getDataForView addCurrencyList: ' . $e->getMessage());
            $dataForView['cursymbol'] = null;
        }
        $this->benchmark->mark('catalog-list');


        return $dataForView;
    }


    /* List */
    public function index()
    {
        $this->benchmark->mark('index_start');


        // Some code happens here
        $this->load->helper('text');
        // Scripts base que deben cargarse primero (dependencias)
        $baseJsFiles = [
            'companyasset/js/select2.min.js', // Debe cargarse antes de los scripts que lo usan
        ];
        
        // Scripts de módulo que dependen de las librerías base
        $productsModuleJsFiles = [
            'companyasset/company/enable-disable.js',
            'companyasset/company/product/index.js',
            'companyasset/company/product/AssignOrRemove.js',
            'companyasset/company/product/export.js',
            'companyasset/company/product/validar_campo.js',
            'companyasset/company/product/submit_filtro.js',
            'companyasset/company/product/eventos.js',
            'companyasset/company/product/buscar_porfiltro.js',
            'companyasset/company/product/product-view-modes.js',
            'companyasset/company/product/category-groups.js',
            'companyasset/company/product/list-taxonomy.js',
            'companyasset/company/product/product-specs.js',
        ];

        // Orden importante: primero las dependencias, luego los scripts de módulo
        $indexJsFiles = array_merge($baseJsFiles, $productsModuleJsFiles);

        foreach ($indexJsFiles as $indexJsFile) {
            $this->addJsFile(function_exists('dated_file_url') ? dated_file_url($indexJsFile) : base_url($indexJsFile));
        }

        // Modals invoked from javascript using regular buttons of the contextual bar
        $this->addModal('company/product/modals/category_assign');
        $this->addModal('company/product/modals/category_remove');
        $this->addModal('company/product/modals/status_change');
        // Modal conservado por flujo de alta (flash) sin botón en el listado.
        $this->addModal('company/product/modals/add_to_catalog');
        $this->addModal('company/product/modals/list_taxonomy');
        $this->addModal('company/product/modals/category_groups');
        $this->addModal('company/product/modals/specs');

        $this->applyPersistedProductListFilters();
        $this->list($this->getContextualButtonBar());
    }

    protected function initListButtonBar()
    {
        $this->listButtonBar = new ButtonBar();
        $this->listButtonBar->addLink($this->langArr['bnAddNewProd'], $this->getAddLink(), 'mdi mdi-plus-circle');
        $this->listButtonBar->addLink($this->langArr['bnImport'], company_url('products/csvImport'), 'mdi mdi-import');
        $this->listButtonBar->addLink(
            $this->langArr['btn-importar-imagenes'],
            company_url('products/importImages'),
            'mdi mdi-camera'
        );
    }

    protected function initItemActions()
    {
        $langtoltip = $this->getModuleLang('cmp-toltip');
        $this->addItemAction('view', $langtoltip['view'], company_url('products/view/'), 'mdi mdi-eye');
        $this->addItemAction(
            'specs',
            $langtoltip['specs'] ?? ($this->langArr['specs_action'] ?? ''),
            company_url('products/specs/'),
            'mdi mdi-clipboard-text-outline',
            'info'
        );
        $this->addItemAction('edit', $langtoltip['edit'], company_url('products/edit/'), 'mdi mdi-pencil', 'warning');
    }

    protected function getActionButtonTemplates(): array
    {
        return [
            'specs' => 'company/product/list/specs_action_button',
        ];
    }

    /**
     * @return ButtonBar
     */
    protected function  getContextualButtonBar(): ButtonBar
    {
        $contextualButtonBar = new ButtonBar();
        $contextualButtonBar->addButton($this->langArr['bnAssignCat'], "asign_category();");
        $contextualButtonBar->addButton($this->langArr['btn-delete-categoria'], 'remove_category();', 'mdi mdi-delete');
        $contextualButtonBar->addButton($this->langArr['bnChangeStat'], 'change_status();');
        $contextualButtonBar->addButton(
            $this->langArr['bnBulkDelete'] ?? ($this->langArr['eliminar'] ?? 'Delete'),
            'eliminarProductosSeleccionados();',
            'mdi mdi-delete-forever'
        );
        $contextualButtonBar->addModalButton(
            $this->langArr['bnExport'],
            "#modal_export",
            'company/product/modals/export',
            "mdi mdi-download"
        );
        return $contextualButtonBar;
    }

    protected function getPageTexts($pageContentObject = null): array
    {
        $lang_export = $this->getModuleLang('cmp-modal-export-layout');
        $cur = $this->getCurrency();
        $countProducts = 0;
        try {
            $countResp = $this->getDataFromApi("company/{$this->getCompanyId()}/products/count");
            $countProducts = (is_array($countResp) && isset($countResp['error'])) ? 0 : $countResp;
        } catch (\Throwable $e) {
            log_message('error', 'getPageTexts companyTotalItemsCount: ' . $e->getMessage());
        }
        $texts = [
                'company_total_items_label' => $this->langArr['total_productos'],
                'companyTotalItemsCount' => $countProducts,
                'currencySymbol' => ($cur && is_object($cur) && isset($cur->symbol)) ? $cur->symbol : '',
                'entityNamePlural' => $this->langArr['products'],
                'entityName' => $this->langArr['product'],
                'fields' => [
                    'pro_id' => $this->langArr['field_pro_id'] ?? 'Id',
                    'productimage' => $this->langArr['field_productimage'] ?? 'Image',
                    'sku' => $this->langArr['field_sku'] ?? 'SKU',
                    'name' => $this->langArr['field_name'] ?? 'Product Name',
                    'sale_price' => $this->langArr['field_sale_price'] ?? 'Price',
                    'category_name' => $this->langArr['field_category_name'] ?? 'Category',
                    'tags' => $this->langArr['field_tags'] ?? 'Tags',
                    'stock' => $this->langArr['field_stock'] ?? 'Stock',
                    'locations' => $this->langArr['field_locations'] ?? 'Location',
                    'minimum_quantity' => $this->langArr['field_minimum_quantity'] ?? 'MOQ'
                ],
            ] + $this->langArr + $lang_export;
        // No mostrar el banner amarillo "Hay un total de X productos encontrados".
        // El total ya aparece en la barra superior (Total de productos / encontrados).
        $texts['expandSelectionInfo'] = '';
        return $texts;
    }

    protected function getFieldTemplates(): array
    {
        return [
            'productimage' => 'company/product/list/image_field',
            'pro_id' => 'company/product/list/pro_id_field',
            'name' => 'company/product/list/name_field',
            'sale_price' => 'company/product/list/sale_price_field',
            'category_name' => 'company/product/list/categories_field',
            'stock' => 'company/product/list/stock_field',
            'tags' => 'company/product/list/tags_field',
            'minimum_quantity' => 'company/product/list/moq_field',
        ];
    }

    protected function getFormFilterView(): string
    {
        return 'company/product/form_filter';
    }

    protected function getFilterData(): array
    {
        $q = $this->resolveProductListFilterQueryParams();
        $filtroCatalog = $q['filtro_catalog'] ?? null;
        $prsearch = $q['prsearch'] ?? null;

        return [
            'prdstatus' => $q['prdstatus'] ?? 1,
            'category' => (($q['categoryId'] ?? 'todo') === 'todo') ? null : ($q['categoryId'] ?? null),
            'photo' => $q['filtro_foto'] ?? null,
            'terms' => ($prsearch !== null && $prsearch !== '') ? explode(' ', $prsearch) : [],
            'tag' => $q['filtro_tag'] ?? null,
            'location' => $q['filtro_ubicacion'] ?? null,
            'catalog' => ($filtroCatalog !== null && $filtroCatalog !== 'todo') ? $filtroCatalog : null,
            'order_type' => $q['order_type'] ?? null,
            'sort' => ($q['sort'] ?? null) !== null && ($q['sort'] ?? '') !== '' ? $q['sort'] : 'modified_on',
            'orderDirection' => ($q['sort_direction'] ?? null) !== null && ($q['sort_direction'] ?? '') !== ''
                ? $q['sort_direction']
                : 'desc',
            'input_todos' => $q['input_todos'] ?? null,
        ];
    }

    /**
     * Clave de sesión para filtros de la lista de productos (por compañía).
     */
    private function getProductsFiltersSessionKey(): string
    {
        return 'products-list-filters-' . $this->getCompanyId();
    }

    /**
     * Parámetros GET del formulario de filtros (excluye paginación).
     *
     * @return string[]
     */
    private function getProductListFilterQueryKeys(): array
    {
        return [
            'prdstatus',
            'categoryId',
            'filtro_foto',
            'prsearch',
            'filtro_tag',
            'filtro_ubicacion',
            'filtro_catalog',
            'order_type',
            'sort',
            'sort_direction',
            'input_todos',
        ];
    }

    private function hasProductListFilterInRequest(): bool
    {
        // Incluye claves presentes aunque vengan vacías (p. ej. prsearch=).
        // Así, al borrar la búsqueda no se restaura el término guardado en sesión.
        foreach ($this->getProductListFilterQueryKeys() as $key) {
            if (array_key_exists($key, $_GET)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, string>
     */
    private function captureProductListFiltersFromRequest(): array
    {
        $query = [];
        foreach ($this->getProductListFilterQueryKeys() as $key) {
            $value = $this->input->get($key);
            // Conservar "0" (p. ej. prdstatus DISABLED); no usar empty().
            if ($value !== null && $value !== false && $value !== '') {
                $query[$key] = (string) $value;
            } elseif ($value === 0 || $value === '0') {
                $query[$key] = '0';
            }
        }

        return $query;
    }

    /**
     * @return array<string, string>
     */
    private function getSavedProductListFilters(): array
    {
        $key = $this->getProductsFiltersSessionKey();
        if (array_key_exists($key, $this->persisted)) {
            $saved = $this->persisted[$key];
        } else {
            $saved = $this->getUserData($key);
        }

        return is_array($saved) && $saved !== [] ? $saved : [];
    }

    private function saveProductListFilters(array $query): void
    {
        $this->persist($this->getProductsFiltersSessionKey(), $query);
    }

    private function clearSavedProductListFilters(): void
    {
        $key = $this->getProductsFiltersSessionKey();
        unset($this->persisted[$key]);
        $this->setUserData($key, null);
    }

    /**
     * Parámetros de filtro actuales: request GET o, si no hay, sesión guardada.
     *
     * @return array<string, string>
     */
    private function resolveProductListFilterQueryParams(): array
    {
        if ($this->hasProductListFilterInRequest()) {
            return $this->captureProductListFiltersFromRequest();
        }

        return $this->getSavedProductListFilters();
    }

    /**
     * Guarda filtros en sesión al aplicarlos y restaura la URL al volver sin query de filtros.
     */
    private function applyPersistedProductListFilters(): void
    {
        if ($this->input->get('reset_filters') === '1') {
            $this->clearSavedProductListFilters();

            return;
        }

        if ($this->hasProductListFilterInRequest()) {
            $captured = $this->captureProductListFiltersFromRequest();
            if ($captured === []) {
                $this->clearSavedProductListFilters();
            } else {
                $this->saveProductListFilters($captured);
            }

            return;
        }

        $saved = $this->getSavedProductListFilters();
        if ($saved === []) {
            return;
        }

        $query = $saved;
        if ($this->input->get('page')) {
            $query['page'] = $this->input->get('page');
        }
        if ($this->input->get('per_page')) {
            $query['per_page'] = $this->input->get('per_page');
        }

        $qs = http_build_query($query);
        redirect(company_url('products' . ($qs !== '' ? '?' . $qs : '')));
        exit;
    }

    protected function getItemIdField(): string
    {
        return 'pro_id';
    }



    protected function getSortableFields($list)
    {
        return [
            'name',
            'sale_price',
            'tags',
            'stock',
            'locations',
            'minimum_quantity'
        ];
    }

    protected function getListItemFields($list)
    {
        $locations = $this->getLocations();
        $fields = [
            'productimage',
            'name',
            'sale_price',
            'category_name',
            'tags',
            'stock',
        ];
        if (strlen(trim(implode('', $locations))) > 0) {
            $fields[] = 'locations';
        }
        $fields[] = 'minimum_quantity';
        return $fields;
    }

    protected function getPaginatedList(string $resource, array $filter = [], $page = null, $perPage = null)
    {
        $params = array_merge(
            $filter,
            [
                'page' => $page,
                'perPage' => $perPage,
                'props' => [
                    'pro_id',
                    'language_id',
                    'name',
                    'sku',
                    'sale_price',
                    'tags',
                    'locations',
                    'purchase_price',
                    'barcode',
                    'company_id',
                    'unit_id',
                    'stock',
                    'status',
                    //'unit',
                    'currency_type',
                    'created_on',
                    'modified_on',
                    //'minimum_quantity', //appends
                    //'product_image' //appends
                ],
                'company' => $this->getCompany()
            ]
        );
        $sort = $this->input->get('sort');
        if ($sort === null || $sort === '') {
            $sort = (string) ($filter['sort'] ?? 'modified_on');
        }
        if ($sort === '') {
            $sort = 'modified_on';
        }
        $params['orderProperty'] = $sort;
        $params['orderDirection'] = strtoupper((string) (
            $this->input->get('sort_direction')
            ?? $filter['orderDirection']
            ?? 'desc'
        ));
        // product_images.image: una sola query eager; evita N+1 HTTP por fila (antes: getObjectFromApi por cada producto).
        $params['relations'] = [
            'categories.imageRecord',
            'product_images.image',
            'default_image',
            'unit',
            'categoryGroups',
            'categorySubgroups',
            'productRating',
        ];
        $paginatedList = $this->getObjectFromApi("$resource/", $params);

        if (!empty($paginatedList->data) && is_array($paginatedList->data)) {
            $categoryImageMap = $this->getCategoryImageMapForList();
            foreach ($paginatedList->data as $product) {
                $this->attachCategoryImagesToProduct($product, $categoryImageMap);
                if (!isset($product->pro_id) || !empty($product->first_ordered_image)) {
                    continue;
                }
                if (!empty($product->product_image) && is_string($product->product_image)) {
                    $product->first_ordered_image = $product->product_image;
                    continue;
                }
                $rows = $product->product_images ?? null;
                if ($rows === null || $rows === '') {
                    continue;
                }
                if (is_object($rows) && isset($rows->data)) {
                    $rows = $rows->data;
                }
                $list = [];
                foreach ((array) $rows as $pi) {
                    $list[] = is_object($pi) ? $pi : (object) (array) $pi;
                }
                usort($list, function ($a, $b) {
                    return ((int) ($a->img_order ?? 999999)) <=> ((int) ($b->img_order ?? 999999));
                });
                foreach ($list as $pi) {
                    if (!empty($pi->image)) {
                        $img = is_object($pi->image) ? $pi->image : (object) (array) $pi->image;
                        if (!empty($img->uuid)) {
                            $product->first_ordered_image = $img->uuid;
                            break;
                        }
                    }
                    if (!empty($pi->image_id)) {
                        $product->first_ordered_image = $pi->image_id;
                        break;
                    }
                }
            }
        }

        return $paginatedList;
    }

    /**
     * @return array<int, object>
     */
    private function getCategoryImageMapForList(): array
    {
        try {
            $catResponse = $this->getObjectFromApi(
                'category',
                [
                    'all' => 1,
                    'props' => ['cat_id', 'category_name', 'image'],
                    'relations' => ['imageRecord'],
                    'companyId' => $this->getCompany(),
                ]
            );
        } catch (\Throwable $e) {
            return [];
        }

        $items = [];
        if (is_array($catResponse)) {
            $items = $catResponse;
        } elseif (is_object($catResponse) && isset($catResponse->data) && is_array($catResponse->data)) {
            $items = $catResponse->data;
        }

        $map = [];
        foreach ($items as $cat) {
            $cat = is_object($cat) ? $cat : (object) (array) $cat;
            $id = (int) ($cat->cat_id ?? 0);
            if ($id > 0) {
                $map[$id] = $cat;
            }
        }

        return $map;
    }

    /**
     * @param array<int, object> $categoryImageMap
     */
    private function attachCategoryImagesToProduct(object $product, array $categoryImageMap): void
    {
        $categories = $product->categories ?? null;
        if ($categories === null || $categories === '') {
            return;
        }
        if (is_object($categories) && isset($categories->data)) {
            $categories = $categories->data;
        }
        if (! is_array($categories)) {
            return;
        }

        foreach ($categories as $index => $cat) {
            $cat = is_object($cat) ? $cat : (object) (array) $cat;
            $catId = (int) ($cat->cat_id ?? 0);
            if ($catId > 0 && isset($categoryImageMap[$catId])) {
                $full = $categoryImageMap[$catId];
                $catHasImage = ! empty($cat->image) || ! empty($cat->image_uuid) || ! empty($cat->imageRecord);
                if (! empty($full->imageRecord)) {
                    $cat->imageRecord = $full->imageRecord;
                }
                if (! $catHasImage) {
                    if (! empty($full->image)) {
                        $cat->image = $full->image;
                    }
                    if (! empty($full->image_uuid)) {
                        $cat->image_uuid = $full->image_uuid;
                    }
                } else {
                    // Preferir el registro completo (path) del mapa para URL estática.
                    if (is_object($full->image ?? null) && ! empty($full->image->path)) {
                        $cat->image = $full->image;
                    } elseif (empty($cat->image) && ! empty($full->image)) {
                        $cat->image = $full->image;
                    }
                    if (empty($cat->image_uuid) && ! empty($full->image_uuid)) {
                        $cat->image_uuid = $full->image_uuid;
                    }
                }
                // Si solo tenemos imageRecord, exponer UUID en image_uuid para el helper.
                if (empty($cat->image_uuid) && is_object($cat->imageRecord ?? null) && ! empty($cat->imageRecord->uuid)) {
                    $cat->image_uuid = $cat->imageRecord->uuid;
                } elseif (empty($cat->image_uuid) && is_array($cat->imageRecord ?? null) && ! empty($cat->imageRecord['uuid'])) {
                    $cat->image_uuid = $cat->imageRecord['uuid'];
                }
                if (empty($cat->category_name) && ! empty($full->category_name)) {
                    $cat->category_name = $full->category_name;
                }
            }
            $categories[$index] = $cat;
        }
        $product->categories = $categories;
    }

    /* Update */

    public function update($producto_id = null)
    {

    }

    /**
     * @param mixed $response
     * @return array<int, object|array>
     */
    private function normalizeApiList($response): array
    {
        if ($this->hasError($response)) {
            return [];
        }
        if (is_object($response) && isset($response->data) && is_array($response->data)) {
            return $response->data;
        }
        if (is_array($response)) {
            if (isset($response['data']) && is_array($response['data'])) {
                return $response['data'];
            }

            return $response;
        }

        return [];
    }

    /**
     * @return array<int, object|array>
     */
    private function fetchAllCompanyProducts(int $companyId, array $extraParams = []): array
    {
        $params = array_merge([
            'company' => $companyId,
            'all' => true,
        ], $extraParams);

        return $this->normalizeApiList($this->getDataFromApi('product', $params, false));
    }

    private function fetchProductById(int $productId, array $params = [])
    {
        $response = $this->getObjectFromApi("product/{$productId}", $params);

        return ($response && !$this->hasError($response)) ? $response : null;
    }

    private function fetchCategoryById($categoryId)
    {
        if ($categoryId === null || $categoryId === '' || !is_numeric($categoryId)) {
            return null;
        }

        $response = $this->getObjectFromApi('category/' . (int) $categoryId);

        return ($response && !$this->hasError($response)) ? $response : null;
    }

    private function fetchCatalogById($catalogId)
    {
        if ($catalogId === null || $catalogId === '' || !is_numeric($catalogId)) {
            return null;
        }

        $response = $this->getObjectFromApi('catalog/' . (int) $catalogId);

        return ($response && !$this->hasError($response)) ? $response : null;
    }

    private function productSkuExists(string $sku, int $companyId, ?int $excludeProductId = null): bool
    {
        $existing = $this->getDataFromApi('product/sku-' . rawurlencode($sku), ['companyId' => $companyId], false);
        if ($this->hasError($existing)) {
            return false;
        }

        $existingProductId = null;
        if (is_object($existing) && isset($existing->pro_id)) {
            $existingProductId = (int) $existing->pro_id;
        } elseif (is_array($existing) && isset($existing['pro_id'])) {
            $existingProductId = (int) $existing['pro_id'];
        }

        if ($existingProductId === null) {
            return false;
        }

        return $excludeProductId === null || $existingProductId !== $excludeProductId;
    }

    private function productHasOrderMoves(int $productId): bool
    {
        $product = $this->fetchProductById($productId, ['relations' => ['order_details']]);
        if (!$product || empty($product->order_details)) {
            return false;
        }

        $details = is_array($product->order_details) ? $product->order_details : (array) $product->order_details;

        return count($details) > 0;
    }

    private function productHasCartItems(int $productId, int $companyId): bool
    {
        $product = $this->fetchProductById($productId, ['relations' => ['detail_cars']]);
        if (!$product || empty($product->detail_cars)) {
            return false;
        }

        foreach ((array) $product->detail_cars as $detail) {
            $detail = is_object($detail) ? $detail : (object) $detail;
            if ((int) ($detail->company_id ?? 0) === $companyId) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, mixed>
     */
    private function productFoundInCatalog(int $productId): array
    {
        $product = $this->fetchProductById($productId, ['relations' => ['catalogs']]);
        if (!$product || empty($product->catalogs)) {
            return [false];
        }

        $catalogs = is_array($product->catalogs) ? $product->catalogs : (array) $product->catalogs;
        if (count($catalogs) === 0) {
            return [false];
        }

        $first = is_object($catalogs[0]) ? $catalogs[0] : (object) $catalogs[0];

        return [true, $first->catalog_name ?? ''];
    }

    /**
     * @return array<string, mixed>
     */
    private function legacyBulkProductsParams(int $companyId): array
    {
        $todo = $this->input->post('todos');
        $productIds = array_values(array_filter(array_map('trim', explode(',', (string) $this->input->post('productsIds')))));
        $params = ['company' => $companyId];

        if ($todo === 'todos') {
            $params['products'] = 'todos';
        } else {
            $params['products'] = json_encode(array_values(array_filter(array_map('intval', $productIds))));
        }

        return $params;
    }

    private function removeDefaultCategoryBeforeAssign(int $companyId, array $bulkParams): void
    {
        $default = $this->getObjectFromApi("company/{$companyId}/categories/default");
        if ($this->hasError($default) || !isset($default->cat_id)) {
            return;
        }

        $this->apiDelete("category/{$default->cat_id}/products", $bulkParams);
    }

    private function resolveExportCategoryName($product, int $companyId): string
    {
        $product = is_object($product) ? $product : (object) $product;

        if (!empty($product->categories)) {
            $categories = is_array($product->categories) ? $product->categories : (array) $product->categories;
            if (count($categories) > 0) {
                $first = is_object($categories[0]) ? $categories[0] : (object) $categories[0];

                return (string) ($first->category_name ?? '');
            }
        }

        if (!empty($product->category_id)) {
            $catIds = explode(',', (string) $product->category_id);
            $cat = $this->fetchCategoryById(trim($catIds[0]));
            if ($cat) {
                return (string) ($cat->category_name ?? '');
            }
        }

        return '';
    }

    public function validar_skuEditar($sku_nuevo, $productId, $compId, $sale_price = null)
    {
        $errores = array();
        $mensajes = $this->HelperLanguage->errores_productos();
        $producto = $this->fetchProductById((int) $productId);

        if (!$producto) {
            return $errores;
        }

        $sku_existente = str_replace('&quot;', '"', (string) ($producto->sku ?? ''));

        if ($sku_existente != $sku_nuevo) {
            $msg = $mensajes['sku_existente'];

            if ($this->productSkuExists($sku_nuevo, (int) $compId, (int) $productId)) {
                array_push($errores, $msg);
                return $errores;
            }

            if ($this->productHasOrderMoves((int) $productId)) {
                $msg = $mensajes['moves_movement'];
                array_push($errores, $msg);
                return $errores;
            }

            if ($this->productHasCartItems((int) $productId, (int) $compId)) {
                $msg = $mensajes['moves_movement'];
                array_push($errores, $msg);
                return $errores;
            }

            $validate_msg = $this->productFoundInCatalog((int) $productId);

            if (count($validate_msg) > 0) {
                if ($validate_msg[0]) {
                    $msg = $mensajes['found_in_catalog'] . " " . $validate_msg[1];
                    array_push($errores, $msg);
                    return $errores;
                }
            }
        }

        if ($sale_price && $sale_price <= 0 || !$sale_price) {
            array_push($errores, $mensajes["sale_price_notmustbe_0"]);
        }
        return $errores;
    }

    public function validar_skuAlta($sku_nuevo, $compId, $sale_price = null)
    {
        $errores = array();
        $mensajes = $this->getModuleLang('errores_producto');

        if ($this->productSkuExists($sku_nuevo, (int) $compId)) {
            $msg = $mensajes['sku_existente'];
            array_push($errores, $msg);
        }

        if ($sale_price && $sale_price <= 0 || !$sale_price) {
            array_push($errores, $mensajes["sale_price_notmustbe_0"]);
        }
        return $errores;
    }

    private function mapProductExportRow($product): array
    {
        $product = is_object($product) ? $product : (object) $product;

        $categoryNames = '';
        if (!empty($product->categories)) {
            $names = [];
            foreach ((array) $product->categories as $cat) {
                $cat = is_object($cat) ? $cat : (object) $cat;
                if (!empty($cat->category_name)) {
                    $names[] = $cat->category_name;
                }
            }
            $categoryNames = implode(',', $names);
        } elseif (!empty($product->category_id)) {
            $categoryNames = $this->resolveExportCategoryName($product, (int) $this->getCompanyId());
        }

        $unitType = '';
        if (!empty($product->unit)) {
            $unit = is_object($product->unit) ? $product->unit : (object) $product->unit;
            $unitType = (string) ($unit->unit ?? '');
        }

        $sanitize = static function ($value, bool $tagsOnly = false): string {
            $text = (string) $value;
            if ($tagsOnly) {
                $text = str_replace(';', ' ', $text);
            } else {
                $text = str_replace([',', ';'], ' ', $text);
            }

            return trim($text, ';, ');
        };

        $formatNumber = static function ($value) {
            if ($value === null || $value === '') {
                return '';
            }
            if (!is_numeric($value)) {
                return $value;
            }

            return number_format((float) $value, 2, '.', '');
        };

        return [
            $sanitize($product->name ?? ''),
            $sanitize($categoryNames),
            $sanitize($product->sku ?? ''),
            $sanitize($product->barcode ?? ''),
            $sanitize($unitType),
            $formatNumber($product->sale_price ?? null),
            $formatNumber($product->fob_price ?? null),
            $formatNumber($product->purchase_price ?? null),
            $product->stock ?? '',
            $formatNumber($product->minimum_stock ?? null),
            $product->notify_minimum_stock ?? '',
            $sanitize($product->tags ?? '', true),
            $sanitize($product->locations ?? ''),
            $sanitize($product->descriptions ?? ''),
            $product->status ?? '',
        ];
    }

    public function exportCSV($separador = false)
    {
        $compId = (int) $this->getCompanyId();

        $products = $this->fetchAllCompanyProducts($compId, [
            'relations' => ['categories', 'unit'],
            'props' => [
                'name',
                'sku',
                'barcode',
                'sale_price',
                'fob_price',
                'purchase_price',
                'stock',
                'minimum_stock',
                'notify_minimum_stock',
                'tags',
                'locations',
                'descriptions',
                'status',
                'category_id',
                'unit_id',
            ],
        ]);

        $headers = [
            'Product Name',
            'Category',
            'SKU',
            'Barcode',
            'Unit Type',
            'Sale Price',
            'FOB Price',
            'Purchase Price',
            'Stock',
            'MOQ',
            'Notify Minimum Stock',
            'Tags',
            'Locations',
            'Descriptions',
            'Status',
        ];

        $rows = [];
        foreach ($products as $product) {
            $rows[] = $this->mapProductExportRow($product);
        }

        $this->sendConfigExcelDownload($headers, $rows, 'product_list' . date('Ymd') . '.xlsx');
    }


    public function get_body($products, $compId)
    {
        $list = [];

        foreach ($products as $producto) {
            $producto = is_object($producto) ? $producto : (object) $producto;
            $lista_categorias = explode(',', (string) ($producto->category_id ?? ''));
            $catname = array();

            foreach ($lista_categorias as $cat_id) {
                $cat = $this->fetchCategoryById(trim($cat_id));
                if ($cat) {
                    $cat->category_name = ltrim(rtrim(str_replace([',', ';'], ' ', $cat->category_name)));
                    $cat->category_name = trim($cat->category_name, ';,');
                    array_push($catname, $cat->category_name);
                }
            }

            $producto->category_id = implode(',', $catname);
            $producto->name = ltrim(rtrim(str_replace([',', ';'], ' ', $producto->name)));
            $producto->name = trim($producto->name, ';,');
            $producto->descriptions = ltrim(rtrim(str_replace([',', ';'], ' ', $producto->descriptions)));
            $producto->descriptions = trim($producto->descriptions, ';,');
            $producto->sku = ltrim(rtrim(str_replace([',', ';'], ' ', $producto->sku)));
            $producto->sku = trim($producto->sku, ';,');
            $producto->barcode = ltrim(rtrim(str_replace([',', ';'], ' ', $producto->barcode)));
            $producto->barcode = trim($producto->barcode, ';,');
            $producto->locations = ltrim(rtrim(str_replace([',', ';'], ' ', $producto->locations)));
            $producto->locations = trim($producto->locations, ';,');
            $producto->tags = ltrim(rtrim(str_replace(';', ' ', $producto->tags)));
            $producto->tags = trim($producto->tags, ';,');

            array_push($list, [
                $producto->name,
                $producto->category_id,
                $producto->sku,
                $producto->barcode,
                $producto->unit_type,
                $producto->sale_price,
                $producto->fob_price,
                $producto->purchase_price,
                $producto->stock,
                $producto->minimum_stock,
                $producto->notify_minimum_stock,
                $producto->tags,
                $producto->locations,
                $producto->descriptions,
                $producto->status

            ]);
        }

        return $list;
    }

    //ELIMINAR CATEGORIAS EN GRUPO  (no se usa)

    public function eliminar_categoria_grupo()
    {
        $compId = (int) $this->getCompanyId();
        $categorias_entrantes = array_filter(explode(",", (string) $this->input->post('categoryselectedIds')));
        $params = $this->legacyBulkProductsParams($compId);

        foreach ($categorias_entrantes as $categoryId) {
            if (trim((string) $categoryId) === '') {
                continue;
            }
            $this->apiDelete("category/{$categoryId}/products", $params);
        }

        $msg = $this->langArr['successfully_removed_category'] ?? 'Successfully removed category';
        echo '["' . $msg . '", "success", "#A5DC86"]';
    }

    // AGREGAR CATEGORIA EN GRUPO A PRODUCTOS

    public function addcategorytoproducts()
    {
        $compId = (int) $this->getCompanyId();
        $categorias_entrantes = array_filter(explode(",", (string) $this->input->post('categoryselectedIds')));
        $params = $this->legacyBulkProductsParams($compId);

        $this->removeDefaultCategoryBeforeAssign($compId, $params);

        foreach ($categorias_entrantes as $categoryId) {
            if (trim((string) $categoryId) === '') {
                continue;
            }
            $this->put("category/{$categoryId}/products", $params);
        }

        $msg = $this->langArr['successfully_assigned_category'] ?? 'Successfully assigned category';
        echo '["' . $msg . '", "success", "#A5DC86"]';
    }

    // no se usa

    public function removecategoryfromproducts()
    {
        $compId = (int) $this->getCompanyId();
        $productIds = array_values(array_filter(array_map('trim', explode(',', (string) $this->input->post('productsIds')))));
        $catIds = array_filter(explode(",", (string) $this->input->post('categoryselectedIds')));
        $params = [
            'company' => $compId,
            'products' => json_encode(array_values(array_filter(array_map('intval', $productIds)))),
        ];

        foreach ($catIds as $categoryId) {
            if (trim((string) $categoryId) === '') {
                continue;
            }
            $this->apiDelete("category/{$categoryId}/products", $params);
        }

        $msg = $this->langArr['successfully_removed_category'] ?? 'Successfully removed category';
        echo '["' . $msg . '", "success", "#A5DC86"]';
    }

    public function addcatelogtoproducts()
    {
        $compId = (int) $this->getCompanyId();
        $catalogIds = array_filter(explode(",", (string) $this->input->post('catlogIds')));
        $productsIds = array_values(array_filter(array_map('intval', explode(',', (string) $this->input->post('productsIds')))));
        $params = [
            'company' => $compId,
            'products' => json_encode($productsIds),
        ];

        foreach ($catalogIds as $catalogId) {
            if (trim((string) $catalogId) === '') {
                continue;
            }
            $this->put("catalog/{$catalogId}/products", $params);
        }

        $msg = $this->langArr['successfully_updated_catalog'] ?? 'Successfully updated to catalog';
        echo '["' . $msg . '", "success", "#A5DC86"]';
    }

    public function removeFromString($str, $item)
    {
        $parts = explode(',', $str);

        while (($i = array_search($item, $parts)) !== false) {
            unset($parts[$i]);
        }

        return implode(',', $parts);
    }

    public function getcategorybyproductIds()
    {
        $product_id = explode(",", (string) $this->input->post('productsIds'));
        $catids = [];
        foreach ($product_id as $prodid) {
            if (trim((string) $prodid) === '') {
                continue;
            }

            $categories = $this->normalizeApiList($this->getDataFromApi("product/{$prodid}/categories", [], false));
            foreach ($categories as $cat) {
                $cat = is_object($cat) ? $cat : (object) $cat;
                if (!empty($cat->cat_id)) {
                    $catids[] = $cat->cat_id;
                }
            }
        }

        $arrunqcatIds = array_unique($catids);
        foreach ($arrunqcatIds as $arq) {
            $catdetl = $this->fetchCategoryById($arq);
            if (!$catdetl) {
                continue;
            }

            echo '<p class="mb-0"><label><input type="checkbox" class="categorycheckbox" name="categoryId[]" value="' . $catdetl->cat_id . '"> ' . $catdetl->category_name . '</label></p>';
        }
    }

    public function getcatalogbyproductIds()
    {
        $product_id = explode(",", (string) $this->input->post('productsIds'));
        $catids = [];

        foreach ($product_id as $prodid) {
            if (trim((string) $prodid) === '') {
                continue;
            }

            $product = $this->fetchProductById((int) $prodid, ['relations' => ['catalogs']]);
            if (!$product || empty($product->catalogs)) {
                continue;
            }

            foreach ((array) $product->catalogs as $cat) {
                $cat = is_object($cat) ? $cat : (object) $cat;
                if (!empty($cat->catalog_id)) {
                    $catids[] = $cat->catalog_id;
                }
            }
        }

        $arrunqcatIds = array_unique($catids);

        foreach ($arrunqcatIds as $arq) {
            $catdetl = $this->fetchCatalogById($arq);
            if (!$catdetl) {
                continue;
            }

            echo '<p class="mb-0"><label><input type="checkbox" class="catalogcheckbox" name="catelogId[]" value="' . $catdetl->catalog_id . '"> ' . $catdetl->catalog_name . '</label></p>';
        }
    }




    public function delete($pId = false)
    {
        if ($pId != false) {
            $result = $this->apiDelete("product/{$pId}");

            if ($this->hasError($result)) {
                $msg = $this->langArr['error_general'] ?? 'error';
            } else {
                $deletedMsg = $this->langArr['deleted_successfully'] ?? 'Deleted successfully';
                $msg = '["' . $deletedMsg . '", "success", "#A5DC86"]';
            }

            $this->flashMessage($msg);
        }
        redirect(company_url('product/lists'), 'refresh');
    }




}

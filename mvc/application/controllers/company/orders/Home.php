<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Listado principal de órdenes (/company/orders).
 *
 * CRUD canónico (IsCRUD + API). Rutas legacy /company/order/* redirigen vía Order.php (shim).
 *
 * Sprint 1 migración (2026): fix company_id en API OrderController::index; wrappers
 * GetHistorial, UpdateOrderLine, DeleteOrderOne, GenerateCatalogOrder.
 */
class Home extends Company_Controller
{

    use IsCRUD;

    protected $hasMultipleSelection = true;
    protected $canChangeStatus = false;
    /** @var array Map cod_status => id_status para filtro y API */
    protected $_codStatusToIdMap = [];
    public function __construct()
    {
        parent::__construct();
        $this->initCrud('order', 'company/order/', 'orders');
        $this->langArr = $this->getModuleLang('cmp-orders');
    }

    /**
     * Sin columna #; sin centrado global para que la columna Customer quede alineada a la izquierda.
     */
    protected function getExtraListViewData($paginatedList, $contextualButtonBar = null): array
    {
        $planLimits = $this->fetchCompanyPlanLimits();

        return [
            'showRowIndexColumn' => false,
            'planLimitBlock' => $this->buildPlanLimitBlock($planLimits, 'orders_month', 'cmp-orders'),
            'company_id' => (int) $this->getCompanyId(),
        ];
    }

    /* List */
    public function index()
    {
        // Usar idioma del usuario para toda la lista (columnas, botones, modal combinar)
        $langId = $this->getUserData('language_id');
        if ($langId && !empty($this->langmodel)) {
            $orderLang = $this->langmodel->getMdl('cmp-orders', $langId);
            if (is_array($orderLang)) {
                $this->langArr = $orderLang;
            }
        }

        // Forzar saneo de qty/totales negativos en órdenes D antes de pintar el listado.
        $companyId = (int) $this->getCompanyId();
        if ($companyId > 0) {
            try {
                $this->apiRequest('order/sanitize-document', 'POST', [
                    'json' => ['company_id' => $companyId],
                    'timeout' => 120.0,
                ]);
            } catch (\Throwable $e) {
                log_message('error', 'order sanitize-document failed: ' . $e->getMessage());
            }
        }

        $indexJsFiles = [
            'companyasset/js/sweetalert2.js',
            'companyasset/company/product/buscar_porfiltro.js',
            'companyasset/company/order/eventos.js',
            'companyasset/company/order/export.js',
        ];

        foreach ($indexJsFiles as $indexJsFile) {
            $this->addJsFile(base_url($indexJsFile));
        }
        $this->addJsFile(dated_file_url('companyasset/company/order/change_status.js'));

        // Archivos JavaScript necesarios para el modal de agregar nueva orden
        $this->addJsFile(base_url('companyasset/company/order/add-order-customer-modal.js'));
        $jsVueFiles = [
            'companyasset/company/store_vue/index.js',
            'companyasset/company/order/order_vue.js',
        ];
        foreach ($jsVueFiles as $jsVueFile) {
            $this->addJsFile(base_url($jsVueFile));
        }

        $this->list($this->getContextualButtonBar());
    }

    protected function getDataForView($view = null, $dataForView = [])
    {
        $dataForView = parent::getDataForView($view, $dataForView);

        $companyId = (int) $this->getCompanyId();
        $dataForView['groupList'] = $this->extractApiListItems($this->getObjectFromApi('customergroup', [
            'props' => ['group_id', 'name'],
            'all' => true,
            'company' => $companyId,
        ]));
        $dataForView['sellerList'] = $this->extractApiListItems($this->getObjectFromApi('vendor', [
            'props' => ['id', 'user_id'],
            'all' => true,
            'relations' => ['user'],
            'company' => $companyId,
        ]));
        $orderStatusesRaw = $this->getObjectFromApi('statusorder/list');
        $dataForView['orderStatuses'] = $this->normalizeAndDeduplicateOrderStatuses($orderStatusesRaw ?: []);

        // Variables necesarias para el modal de agregar nueva orden
        $langId = $this->getUserData('language_id');
        $dataForView['lang_order'] = $this->langmodel->getMdl('cmp-orders', $langId);

        // Inglés y español: columnas de status_orders. Otros idiomas: JSON status-orders.
        $dataForView['statusLabelMap'] = [];
        $dataForView['codStatusToIdMap'] = [];
        $statusLangId = $this->resolveCompanyPanelLangId();
        foreach ((array) $dataForView['orderStatuses'] as $status) {
            $codStatus = $status->cod_status ?? null;
            if ($codStatus === null && $codStatus !== '0') {
                continue;
            }
            $codInt = (int) $codStatus;
            $dataForView['codStatusToIdMap'][$codInt] = (int) ($status->id_status ?? 0);
            $nameEn = trim((string) ($status->name_status_english ?? ''));
            $nameEs = trim((string) ($status->name_status_spanish ?? ''));
            $label = fv_order_status_label($statusLangId, $nameEn, $nameEs);
            $dataForView['statusLabelMap'][$codInt] = $label !== '' ? $label : (string) $codStatus;
        }
        $this->_codStatusToIdMap = $dataForView['codStatusToIdMap'] ?? [];
        $dataForView['changeStatusUrl'] = company_url('orders/changeStatus');
        $dataForView['langprods'] = $this->langmodel->getMdl('cmp-products', $langId);
        $this->load->model('HelperLanguage');
        $dataForView['lang_login_seller'] = $this->HelperLanguage->login_seller();
        $dataForView['lang_modal'] = $this->HelperLanguage->modal();
        
        // Verificar si hay opciones de orden (puede ser null)
        $dataForView['options_order'] = null; // Se puede configurar según necesidades

        // Mapa de países (nombre => phonecode, phonemask) para máscara de teléfono en columna cliente
        try {
            $countryList = $this->getObjectFromApi('country', [
                'props' => ['id', 'name', 'sortname', 'phonecode', 'phonemask'],
                'all' => true
            ]);
            $dataForView['countryDataMap'] = [];
            if (is_array($countryList)) {
                foreach ($countryList as $country) {
                    $name = $country->name ?? '';
                    if ($name !== '') {
                        $dataForView['countryDataMap'][$name] = [
                            'sortname' => $country->sortname ?? '',
                            'phonecode' => $country->phonecode ?? '',
                            'phonemask' => $country->phonemask ?? ''
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            $dataForView['countryDataMap'] = [];
        }

        $planLimits = $this->fetchCompanyPlanLimits();
        $dataForView['planLimitBlock'] = $this->buildPlanLimitBlock($planLimits, 'orders_month', 'cmp-orders');

        return $dataForView;
    }

    protected function initListButtonBar()
    {
        $toolsLang = $this->getModuleLang('config-crud-tools') ?: [];
        $importLabel = trim((string) (
            $this->langArr['importar']
            ?? ($this->langArr['bnImport'] ?? ($toolsLang['import'] ?? ''))
        ));
        $templateLabel = trim((string) (
            $this->langArr['btn_download_template']
            ?? ($this->langArr['descargar_planilla'] ?? ($toolsLang['btn_download_template'] ?? ''))
        ));

        $this->listButtonBar = new ButtonBar();
        $this->listButtonBar->addModalButton(
            $this->langArr['add_new_order'] ?? 'Add New Order',
            '#modal_catalogorder',
            'company/order/modal_addneworder',
            'mdi mdi-plus-circle'
        );
        $this->listButtonBar->addButton(
            $this->langArr['new_credit_note'] ?? 'Nueva Nota de Crédito',
            "var el=document.getElementById('tipo_d_addorder');if(el)el.value='C';var m=typeof jQuery!=='undefined'&&jQuery('#modal_catalogorder');if(m.length)m.modal('show');",
            'mdi mdi-note-plus-outline'
        );
        $this->listButtonBar->addButton(
            $importLabel,
            "window.fvOrderPreferFileImport=true;var el=document.getElementById('tipo_d_addorder');if(el)el.value='D';var m=typeof jQuery!=='undefined'&&jQuery('#modal_catalogorder');if(m.length)m.modal('show');",
            'mdi mdi-import'
        );
        $this->listButtonBar->addModalButton(
            $this->langArr['import_json_btn'] ?? ($this->langArr['import_json_title'] ?? 'Import JSON'),
            '#modal_import_orders_json',
            'company/order/modal_import_json',
            'mdi mdi-code-json'
        );
        $this->listButtonBar->addLink(
            $templateLabel,
            import_template_download_url('new-order-example.xlsx'),
            'mdi mdi-file-document-outline',
            [
                'download' => '',
                'class' => 'fv-config-crud-btn fv-config-crud-btn--template',
                'title' => $templateLabel,
            ]
        );
    }

    protected function getContextualButtonBar(): ButtonBar
    {
        $contextualButtonBar = new ButtonBar();

        $contextualButtonBar->addModalButton(
            $this->langArr['bnExport'],
            "#modal_export",
            'company/order/modal_exportacion',
            "mdi mdi-download"
        );

        $urlDuplicateSilent = company_url('orders/duplicate_silent');
        $urlCombinePreview = company_url('orders/combine_preview');
        $urlCombineOrders = company_url('orders/combine_orders');
        $msgSelectAtLeastOne = addslashes($this->langArr['alert_select_at_least_one'] ?? 'Please select at least one order.');
        $msgErrorDuplicar = addslashes($this->langArr['error_duplicar'] ?? 'Error duplicating orders.');
        $msgCombineMinOrders = addslashes($this->langArr['combine_min_orders'] ?? 'Debe seleccionar al menos 2 órdenes para combinar.');
        $msgCombineConfirmTitle = addslashes($this->langArr['combine_confirm_title'] ?? 'Confirmar combinación de órdenes');
        $msgCombineCustomer = addslashes($this->langArr['combine_customer_label'] ?? 'Cliente');
        $msgCombineOrders = addslashes($this->langArr['combine_orders_label'] ?? 'Órdenes a combinar');
        $msgCombineTotal = addslashes($this->langArr['combine_total_label'] ?? 'Total consolidado');
        $msgCombineConfirmBtn = addslashes($this->langArr['swal_confirm'] ?? 'Confirmar');
        $msgCombineCancelBtn = addslashes($this->langArr['swal_cancel'] ?? 'Cancelar');
        $msgCombineLoading = addslashes($this->langArr['cargando'] ?? 'Cargando...');
        $msgCombineError = addslashes($this->langArr['combine_error'] ?? 'Error al combinar órdenes.');

        $contextualButtonBar->addButton(
            $this->langArr['duplicate'] ?? 'Duplicar',
            "var c=document.querySelectorAll('#infoTable tbody input.checkBoxClass:checked');if(c.length===0){if(typeof Swal!=='undefined'&&Swal.fire){Swal.fire({icon:'warning',title:'" . $msgSelectAtLeastOne . "'});}else{alert('" . $msgSelectAtLeastOne . "');}return;}var ids=Array.from(c).map(function(x){return x.value});var fd=new FormData();ids.forEach(function(id){fd.append('order_ids[]',id)});var btn=this;btn.disabled=true;if(typeof showOrderActionLoader==='function') showOrderActionLoader(typeof orderActionLoaderMessage!=='undefined'?orderActionLoaderMessage:'Procesando...');fetch('" . $urlDuplicateSilent . "',{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.json();}).then(function(d){if(d.success){if(typeof Swal!=='undefined'&&Swal.fire){Swal.fire({title:d.message,icon:'success'}).then(function(){window.location.reload();});}else if(typeof swal!=='undefined'){swal({title:d.message,icon:'success'}).then(function(){window.location.reload();});}else{alert(d.message);window.location.reload();}}else{if(typeof Swal!=='undefined'&&Swal.fire){Swal.fire({icon:'error',title:d.message||'Error'});}else{alert(d.message||'Error');}btn.disabled=false;}}).catch(function(){if(typeof Swal!=='undefined'&&Swal.fire){Swal.fire({icon:'error',title:'" . $msgErrorDuplicar . "'});}else{alert('" . $msgErrorDuplicar . "');}btn.disabled=false;}).finally(function(){if(typeof hideOrderActionLoader==='function') hideOrderActionLoader();});",
            'mdi mdi-content-copy'
        );

        $combineOnClick = "var c=document.querySelectorAll('tbody input.checkBoxClass:checked');if(c.length<2){if(typeof Swal!=='undefined'&&Swal.fire){Swal.fire({icon:'warning',title:'" . $msgCombineMinOrders . "'});}else{alert('" . $msgCombineMinOrders . "');}return;}var ids=Array.from(c).map(function(x){return x.value});var urlPreview='" . addslashes($urlCombinePreview) . "';var urlCombine='" . addslashes($urlCombineOrders) . "';var msgCustomer='" . $msgCombineCustomer . "';var msgOrders='" . $msgCombineOrders . "';var msgTotal='" . $msgCombineTotal . "';var msgConfirm='" . $msgCombineConfirmBtn . "';var msgCancel='" . $msgCombineCancelBtn . "';var msgLoading='" . $msgCombineLoading . "';var msgErr='" . $msgCombineError . "';function esc(s){if(s==null||s===undefined)return'';var d=document.createElement('div');d.textContent=s;return d.innerHTML;}if(typeof showOrderActionLoader==='function')showOrderActionLoader(msgLoading);var fd=new FormData();ids.forEach(function(id){fd.append('order_ids[]',id);});fetch(urlPreview,{method:'POST',body:fd,credentials:'same-origin'}).then(function(r){return r.json();}).then(function(d){if(typeof hideOrderActionLoader==='function')hideOrderActionLoader();if(!d.success){if(typeof Swal!=='undefined'&&Swal.fire)Swal.fire({icon:'error',title:d.message||msgErr});else alert(d.message||msgErr);return;}var cust=d.customer||{};var custInfo=[cust.name,cust.business_name,cust.email].filter(Boolean).join(' | ');var html='<div class=\"text-start\"><p><strong>'+esc(msgCustomer)+':</strong> '+esc(custInfo)+'</p>';html+='<p><strong>'+esc(msgOrders)+':</strong></p><table class=\"table table-sm table-bordered\"><thead><tr><th>#</th><th>Subtotal</th><th>Desc.</th><th>Total</th></tr></thead><tbody>';(d.orders||[]).forEach(function(o){html+='<tr><td>'+esc(o.order_number)+'</td><td>$'+o.subtotal.toFixed(2)+'</td><td>$'+o.discount.toFixed(2)+'</td><td>$'+o.total.toFixed(2)+'</td></tr>';});html+='</tbody></table>';html+='<p class=\"mt-2\"><strong>'+esc(msgTotal)+':</strong> <span class=\"text-success fs-5\">$'+d.total.toFixed(2)+'</span></p></div>';if(typeof Swal!=='undefined'&&Swal.fire){Swal.fire({title:'" . $msgCombineConfirmTitle . "',html:html,showCancelButton:true,confirmButtonText:msgConfirm,cancelButtonText:msgCancel,icon:'question',width:'600px'}).then(function(result){if(!result.isConfirmed)return;if(typeof showOrderActionLoader==='function')showOrderActionLoader(msgLoading);var fd2=new FormData();ids.forEach(function(id){fd2.append('order_ids[]',id);});fetch(urlCombine,{method:'POST',body:fd2,credentials:'same-origin'}).then(function(r){return r.json();}).then(function(d){if(typeof hideOrderActionLoader==='function')hideOrderActionLoader();if(d.success){Swal.fire({icon:'success',title:d.message||''}).then(function(){window.location.reload();});}else{Swal.fire({icon:'error',title:d.message||msgErr});}}).catch(function(){if(typeof hideOrderActionLoader==='function')hideOrderActionLoader();Swal.fire({icon:'error',title:msgErr});});});}}).catch(function(){if(typeof hideOrderActionLoader==='function')hideOrderActionLoader();if(typeof Swal!=='undefined'&&Swal.fire)Swal.fire({icon:'error',title:msgErr});});";
        $contextualButtonBar->addButton(
            $this->langArr['combinar'] ?? 'Combinar',
            $combineOnClick,
            'mdi mdi-merge'
        );

        return $contextualButtonBar;
    }

    protected function initItemActions()
    {
        $langtoltip = $this->Langmodel->getMdl('cmp-toltip', $this->langId);
        $this->addItemAction('view', $langtoltip['view'] ?? 'Ver', company_url('orders/view/'), 'mdi mdi-eye', 'btn-info');
        $this->addItemAction('edit', $langtoltip['edit'], company_url('orders/edit/'), 'mdi mdi-pencil');
    }

    protected function getPageTexts($pageContentObject = null): array
    {
        $langToggleStatusButton = $this->getModuleLang('cmp-enabled');
        $langBasics = $this->getModuleLang('cmp-option_basic');
        $langProducts = $this->getModuleLang('cmp-products');

        if (isset($pageContentObject)) {
            $expandSelectionInfo = sprintf(
                $this->langArr['alert_cantidad_clientes'],
                $pageContentObject->total,
                $pageContentObject->total
            );
        }

        $lang = $this->langArr;
        $langExport = $this->getModuleLang('cmp-modal-export-layout');
        return array_merge(
            $langBasics,
            $langProducts,
            $lang,
            $langExport,
            [
                'company_total_items_label' => $lang['company_total_items_label'] ?? '',
                'cantidad_productos' => $lang['cantidad_productos'] ?? '',
                'entityNamePlural' => $lang['entityNamePlural'] ?? '',
                'entityName' => $lang['entityName'] ?? '',
                'fields' => [
                    'order_number' => $lang['order_number'] ?? 'Order Number',
                    'approved' => $lang['approved'] ?? 'Approved',
                    'customer' => $lang['customer'] ?? 'Customer',
                    'seller' => $lang['seller'] ?? 'Seller',
                    'warehouse' => $lang['warehouse'] ?? 'Warehouse',
                    'updated' => $lang['fecha_creacion_order'] ?? ($lang['created'] ?? 'Date'),
                    'created' => $lang['created'] ?? 'Created at',
                    'total' => $lang['total'] ?? 'Total',
                    'notes' => $lang['comments'] ?? 'Comments',
                    'statusOrder' => $lang['status'] ?? 'Status',
                ],
                'amount_requested' => $lang['amount_requested'] ?? ($lang['importe_solicitado'] ?? ($lang['total_pedido'] ?? 'Total')),
                'amount_assigned' => $lang['amount_assigned'] ?? ($lang['total_asigned'] ?? ''),
                'colmn_status' => $lang['status'] ?? 'Status',
                'colmn_action' => $lang['actions'] ?? $lang['action'] ?? 'Actions',
                'option_enable' => $langToggleStatusButton['option_enable'],
                'option_disable' => $langToggleStatusButton['option_disable'],
                'loader-enable' => $langToggleStatusButton['loader-enabling'],
                'loader-disable' => $langToggleStatusButton['loader-disabling'],
                'expandSelectionInfo' => $expandSelectionInfo ?? null
            ]);
    }

    protected function getListItemFields($list)
    {
        return [
            'order_number',
            'approved',
            'customer',
            'seller',
            'warehouse',
            'updated',
            'total',
            'notes',
            'statusOrder'
        ];
    }

    protected function getFieldTemplates(): array
    {
        return [
            'order_number' => 'company/order/list/order_number_field',
            'approved' => 'company/order/list/approved_field',
            'customer' => 'company/order/list/customer_field',
            'seller' => 'company/order/list/seller_field',
            'warehouse' => 'company/order/list/warehouse_field',
            'updated' => 'company/order/list/created_field',
            'created' => 'company/order/list/created_field',
            'total' => 'company/order/list/total_field',
            'notes' => 'company/order/list/notes_field',
            'statusOrder' => 'company/order/list/status_field',
        ];
    }

    protected function getActionButtonTemplates(): array
    {
        return [
            'view' => 'company/order/list/view_action',
            'edit' => 'company/order/list/edit_action',
        ];
    }

    protected function getFormFilterView(): string
    {
        return 'company/order/form_filter';
    }

    protected function getItemIdField(): string
    {
        return 'order_id';
    }

    protected function getFilterData(): array
    {
        $formFilter = $this->getFormFilters();
        // Convertir filtro status (cod_status desde el form) a id_status para la API
        $statusCod = $formFilter['status'] ?? null;
        if ($statusCod !== null && $statusCod !== '' && $statusCod !== []) {
            $statusCod = is_array($statusCod) ? $statusCod : [$statusCod];
            $statusCod = array_map('intval', $statusCod);
            $formFilter['status_cod'] = $statusCod; // para la vista (select con value=cod_status)
            // API enlaza por cod_status: enviamos cod_status y el OrderFilter resuelve a id_status
            $formFilter['status'] = array_map('intval', $statusCod);
        }
        if (!isset($formFilter['status_cod'])) {
            $formFilter['status_cod'] = [];
        }
        $sort = $this->input->get('sort');
        if ($sort === null || $sort === '') {
            $sort = 'updated';
        }
        $sortAliases = [
            'orden_date' => 'created',
            'order_date' => 'created',
            'fecha' => 'created',
            'list_date_updated' => 'updated',
            'list_date_created' => 'created',
        ];
        if (isset($sortAliases[$sort])) {
            $sort = $sortAliases[$sort];
        }
        $orderDirection = strtolower((string) ($this->input->get('sort_direction') ?? $this->input->get('orderDirection') ?? 'desc'));
        if (! in_array($orderDirection, ['asc', 'desc'], true)) {
            $orderDirection = 'desc';
        }

        return array_merge(
            $formFilter,
            [
                'sort' => $sort,
                'orderDirection' => $orderDirection,
                'props' => [
                    'order_id',
                    'customer_id',
                    'seller_id',
                    'warehouse_id',
                    'warehouse_user_id',
                    'address_id',
                    'status_id',
                    'order_number',
                    'tipo_d',
                    'amount',
                    'total_amount',
                    'discount',
                    'discount_a',
                    'discount_type',
                    'source',
                    'send_email_order',
                    'leido',
                    'date_confirmed',
                    'date_rejected_email',
                    'created',
                    'updated'
                ],
                'relations' => ['customer', 'seller.user', 'warehouse', 'warehouse_user.user', 'warehouse_user.warehouse', 'address.city:id,name', 'address.state:id,name', 'address.country:id,name', 'notes', 'statusOrder', 'order_details'],
            ]
        );
    }

    protected function getFormFilters(): array
    {
        $formFilter = [
            'search' => $this->input->get("search"),
        ];

        $filters = [
            'groupId' => 'group',
            'sellerId' => 'seller',
            'origin' => 'source',
            'date_from' => 'date_from',
            'date_to'   => 'date_to',
            'status'    => 'status',
        ];

        foreach ($filters as $param => $key) {
            $value = $this->input->get($param);
            if ($value !== null && $value !== '' && $value !== 'todo') {
                $formFilter[$key] = $value;
            }
        }

        // Valores por defecto: from = últimos 30 días, to = fecha actual
        if (empty($formFilter['date_from'])) {
            $formFilter['date_from'] = date('Y-m-d', strtotime('-30 days'));
        }
        if (empty($formFilter['date_to'])) {
            $formFilter['date_to'] = date('Y-m-d');
        }

        return $formFilter;
    }

    protected function getPaginatedList(string $resource, array $filter = [], $page = null, $perPage = null): object
    {
        $sort = $this->input->get('sort');
        if ($sort === null || $sort === '') {
            $sort = 'updated';
        }
        // Alias legados del filtro UI → columnas reales de orders.
        $sortAliases = [
            'orden_date' => 'created',
            'order_date' => 'created',
            'fecha' => 'created',
            'list_date_updated' => 'updated',
            'list_date_created' => 'created',
        ];
        if (isset($sortAliases[$sort])) {
            $sort = $sortAliases[$sort];
        }
        $orderDirection = strtoupper((string) ($this->input->get('sort_direction') ?? $this->input->get('orderDirection') ?? 'desc'));
        if (! in_array($orderDirection, ['ASC', 'DESC'], true)) {
            $orderDirection = 'DESC';
        }

        $filter['orderProperty'] = $sort;
        $filter['orderDirection'] = $orderDirection;
        $filter['sort'] = $sort;

        $params = array_merge($filter, ['page' => $page, 'per_page' => $perPage]);
        $result = $this->getObjectFromApi("$resource/", $params);

        if (! is_object($result)) {
            if (is_array($result) && method_exists($this, 'hasError') && $this->hasError($result)) {
                log_message('error', 'getPaginatedList API error for ' . $resource . ': ' . json_encode($result));
            }

            $pageNum = max(1, (int) ($page ?? 1));
            $perPageNum = max(1, (int) ($perPage ?? 25));

            return (object) [
                'data' => [],
                'total' => 0,
                'per_page' => $perPageNum,
                'current_page' => $pageNum,
                'last_page' => 1,
            ];
        }

        return $result;
    }

    /**
     * Elimina en lote las órdenes seleccionadas (POST order_ids[]).
     * Redirige a la lista de órdenes con mensaje de éxito o error.
     */
    public function bulkDelete()
    {
        if ($this->input->method(true) !== 'POST') {
            $this->Commonmodel->show_sessionmsg($this->langArr['alert_select_at_least_one'] ?? 'Seleccione al menos una orden.', 'error');
            redirect(company_url('orders'));
            return;
        }
        $orderIds = $this->input->post('order_ids');
        if (!is_array($orderIds)) {
            $orderIds = $orderIds !== null && $orderIds !== '' ? [ $orderIds ] : [];
        }
        $orderIds = array_filter(array_map('intval', $orderIds));
        if (empty($orderIds)) {
            $this->Commonmodel->show_sessionmsg($this->langArr['alert_select_at_least_one'] ?? 'Seleccione al menos una orden.', 'error');
            redirect(company_url('orders'));
            return;
        }
        $deleted = 0;
        $errors = [];
        foreach ($orderIds as $orderId) {
            if ($orderId <= 0) {
                continue;
            }
            $resp = $this->apiDelete("order/{$orderId}");
            if (!$this->hasError($resp)) {
                $deleted++;
            } else {
                $errors[] = $orderId;
            }
        }
        if ($deleted > 0) {
            $msg = $deleted === 1
                ? ($this->langArr['order_deleted_success'] ?? 'Orden eliminada correctamente.')
                : sprintf($this->langArr['orders_deleted_success'] ?? 'Se eliminaron %d órdenes correctamente.', $deleted);
            $this->Commonmodel->show_sessionmsg($msg, 'success');
        }
        if (!empty($errors)) {
            $this->Commonmodel->show_sessionmsg(
                sprintf($this->langArr['orders_delete_error'] ?? 'No se pudieron eliminar %d orden(es).', count($errors)),
                'error'
            );
        }
        redirect(company_url('orders'));
    }

}

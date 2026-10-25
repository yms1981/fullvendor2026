<?php
/**
 * Modal: listbox de clientes + opciones de origen + botón Crear.
 *
 * @var array $planLimitBlock
 * @var array $lang_order
 * @var array $lang_login_seller
 * @var array $langprods
 * @var array|null $lang_modal
 * @var mixed $options_order
 * @var array $countryDataMap
 */
$planLimitBlock = isset($planLimitBlock) && is_array($planLimitBlock) ? $planLimitBlock : ['can_create' => true];
$planLimitBlocked = empty($planLimitBlock['can_create']);
$langOrder = is_array($lang_order ?? null) ? $lang_order : [];
$langModal = is_array($lang_modal ?? null) ? $lang_modal : [];
$countryDataMap = isset($countryDataMap) && is_array($countryDataMap) ? $countryDataMap : [];
$flagsBaseUrl = rtrim(base_url('images/flags/'), '/') . '/';
?>
<link rel="stylesheet" href="<?= dated_file_url('companyasset/css/fv-order-customer-modal.css') ?>" />
<div class="modal fade fv-customer-picker-modal" id="modal_catalogorder" tabindex="-1" role="dialog"
     aria-labelledby="addOrderModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="addOrderModalTitle">
                        <span class="fv-customer-picker-modal__title-text"><?= htmlspecialchars((string) ($langOrder['add_new_order'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                    </h5>
                    <p class="modal-subtitle"><?= htmlspecialchars((string) ($langOrder['add_order_modal_subtitle'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= htmlspecialchars((string) ($langprods['btn-cancelar'] ?? 'Cancelar'), ENT_QUOTES, 'UTF-8') ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <?php $this->load->view('company/plan_limit_notice', ['planLimitBlock' => $planLimitBlock]); ?>
                <form method="post" id="form-nueva-ordern-catalogo" enctype="multipart/form-data"
                      action="<?= company_url('orders/generateCatalogOrder') ?>"
                      data-plan-limit-blocked="<?= $planLimitBlocked ? '1' : '0' ?>"
                      data-plan-limit-message="<?= htmlspecialchars((string) ($planLimitBlock['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="customer_id" id="addorder_customer_id" value="">
                    <input type="hidden" name="tipo_d" id="tipo_d_addorder" value="D">
                    <input type="hidden" name="type_catalog" id="hidden_type_catalog" value="">
                    <input type="hidden" name="type_grid" id="hidden_type_grid" value="">
                    <input type="hidden" name="type_file" id="hidden_type_file" value="">

                    <div class="fv-customer-picker">
                        <div class="fv-customer-picker__search-wrap">
                            <i class="mdi mdi-magnify" aria-hidden="true"></i>
                            <input type="search"
                                   id="fv_customer_search"
                                   class="fv-customer-picker__search"
                                   autocomplete="off"
                                   placeholder="<?= htmlspecialchars((string) ($langOrder['add_order_search_placeholder'] ?? ($lang_login_seller['search_customer'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"
                                   data-search-url="<?= company_url('orders/searchCustomer') ?>"
                                   aria-label="<?= htmlspecialchars((string) ($lang_login_seller['search_customer'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="fv-customer-picker__list" role="listbox"
                             aria-label="<?= htmlspecialchars((string) ($langOrder['customer_order'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>

                    <div class="fv-order-source-options" id="fv_order_source_options">
                        <div class="fv-order-source-radios" id="accordion_addneworder">
                            <div class="form-check fv-order-source-radio" id="addneworder_row_catalog">
                                <input class="form-check-input" type="radio" name="order_source" id="type_catalog" value="catalog" checked>
                                <label class="form-check-label" for="type_catalog"><?= htmlspecialchars((string) ($langOrder['desde_catalogo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
                            </div>
                            <div class="form-check fv-order-source-radio">
                                <input class="form-check-input" type="radio" name="order_source" id="type_grid" value="grilla">
                                <label class="form-check-label" for="type_grid"><?= htmlspecialchars((string) ($langOrder['desde_grid'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
                            </div>
                            <div class="form-check fv-order-source-radio">
                                <input class="form-check-input" type="radio" name="order_source" id="type_file" value="csv">
                                <label class="form-check-label" for="type_file"><?= htmlspecialchars((string) ($langOrder['desde_file'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
                            </div>
                        </div>
                        <div id="collapseCsvFile" class="fv-order-source-csv collapse">
                            <div class="custom-file">
                                <input name="file_csv" type="file" class="custom-file-input" accept=".xlsx,.xls" id="validatedCustomFile">
                                <label class="custom-file-label" for="validatedCustomFile"><?= htmlspecialchars((string) ($langOrder['elegir_archivo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>...</label>
                            </div>
                            <a href="<?= import_template_download_url('new-order-example.xlsx') ?>" download class="fv-order-source-csv__example">
                                <i class="fa-solid fa-cloud-arrow-down" aria-hidden="true"></i>
                                <?= htmlspecialchars((string) ($langOrder['btn_download_template'] ?? $langOrder['descargar_ejemplo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </a>
                            <div id="fv_order_import_preview" class="fv-order-import-preview" hidden>
                                <div class="fv-order-import-preview__head">
                                    <strong id="fv_order_import_preview_title"></strong>
                                    <span id="fv_order_import_preview_summary" class="fv-order-import-preview__summary"></span>
                                </div>
                                <div class="fv-order-import-preview__table-wrap">
                                    <table class="table table-sm mb-0 fv-order-import-preview__table">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>SKU</th>
                                                <th><?= htmlspecialchars((string) ($langOrder['product'] ?? $langOrder['order_product'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                                                <th><?= htmlspecialchars((string) ($langOrder['qty'] ?? $langOrder['order_qty'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                                                <th><?= htmlspecialchars((string) ($langOrder['sale_price'] ?? $langOrder['order_price'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                                                <th><?= htmlspecialchars((string) ($langOrder['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                                            </tr>
                                        </thead>
                                        <tbody id="fv_order_import_preview_body"></tbody>
                                    </table>
                                </div>
                                <p id="fv_order_import_preview_msg" class="fv-order-import-preview__msg mb-0"></p>
                            </div>
                        </div>
                    </div>
                    <?php if (!empty($options_order)) : ?>
                        <div class="fv-order-extra-options">
                            <label class="fv-order-extra-options__item" for="show_stock_erp">
                                <input id="show_stock_erp" type="checkbox" value="1" name="show_stock_erp">
                                <span><?= htmlspecialchars((string) ($langModal['show_stock_erp'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                            </label>
                            <label class="fv-order-extra-options__item" for="show_comment">
                                <input id="show_comment" type="checkbox" value="1" name="show_comment">
                                <span><?= htmlspecialchars((string) ($langModal['show_comments'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                            </label>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light fv-crud-btn" data-dismiss="modal">
                    <?= htmlspecialchars((string) ($langprods['btn-cancelar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </button>
                <button type="button" id="addorder_btn_create" class="btn btn-success fv-crud-btn" disabled<?= $planLimitBlocked ? ' data-plan-limit-blocked="1"' : '' ?>>
                    <?= htmlspecialchars((string) ($langOrder['add_order_create_btn'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </button>
            </div>
        </div>
    </div>
</div>
<script>
window.fvAddOrderModalLang = <?= json_encode([
    'title_order' => $langOrder['add_new_order'] ?? '',
    'title_credit' => $langOrder['new_ordertitle_credit_note'] ?? ($langOrder['new_credit_note'] ?? ''),
    'subtitle' => $langOrder['add_order_modal_subtitle'] ?? '',
    'loading' => $langOrder['add_order_loading_customers'] ?? ($langOrder['cargando'] ?? ''),
    'empty' => $langOrder['sin_resultados'] ?? '',
    'processing' => $langOrder['cargando'] ?? '',
    'connection_error' => $langOrder['add_order_connection_error'] ?? '',
    'select_customer' => $langOrder['debe_elegir_cliente'] ?? '',
    'select_source' => $langOrder['seleccione_tipo_orden'] ?? '',
    'select_csv' => $langOrder['add_order_select_csv'] ?? '',
    'create' => $langOrder['add_order_create_btn'] ?? '',
    'preview_title' => $langOrder['import_order_preview_title'] ?? '',
    'preview_ok' => $langOrder['import_order_preview_ok'] ?? '',
    'preview_has_errors' => $langOrder['import_order_preview_has_errors'] ?? '',
    'preview_validating' => $langOrder['import_order_preview_validating'] ?? '',
    'status_ok' => $langOrder['import_order_status_ok'] ?? '',
    'summary_tpl' => $langOrder['import_order_preview_summary'] ?? '',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
window.fvAddOrderModalConfig = <?= json_encode([
    'countryDataMap' => $countryDataMap,
    'flagsBaseUrl' => $flagsBaseUrl,
    'importPreviewUrl' => company_url('orders/importPreview'),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>

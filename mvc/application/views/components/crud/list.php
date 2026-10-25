<?php
/**
 * @var bool $selectionExpansible Determines if the select all could be converted in select all that matches the filter criteria instead of this page only
 * @var string $pageTitle The page title
 * @var object $page The page with the list, and the pagination info
 * @var string $itemIdField the field name of the identifying value of a list item
 * @var array $pageTexts The page standard texts for the entity module listing
 * @var array $filterData The current filter data for each item of this filter form
 * @var array $addItem the add item link definition {'link', 'label'}
 * @var array $buttonBar the list of buttons or links definition of the main button bar
 * @var array $contextualButtonBar the list of buttons or links definition of the main button bar
 * @var int $totalItemsCount the total amount of items matching the filter criteria
 * @var array $fields the list of fields/columns shown for the list items
 * @var array $sortableFields the list of fields/columns that can be used as sorting for list items
 * @var string $itemStatusField the status fild name of the list item
 * @var array $list the list of items shown on the current page
 * @var array $modals the list of modal view files used on this page
 * @var bool $multipleSelection Determines if the list shows checkboxes to select multiple items
 * @var array $itemActions the list of item actions that each item will have on the actions field
 * @var string $paginate the pagination links html
 * @var string $controllerUrl the controller (crud as a subcontroller of an application) base url
 * @var array $benchmarkmessages the list of benchmark messages for speedup reasons
 * @var bool $changeStatus Determines if the enable-disable column buttons shows
 *
 * @var int $companyTotalItemsCount the total amount of items available in the company (implemented initially for products)
 * @var array $deleteLang Las traducciones para el botón eliminar (cmp-toltip)
 * @var bool $listCenterAllDataColumns Si es true, centra cabeceras y celdas de todas las columnas de datos (p. ej. listado de órdenes)
 * @var bool $showRowIndexColumn Si es false, oculta la columna numerada # (por defecto true)
 *
 */
$useConfigCrudDatagrid = !empty($useConfigCrudDatagrid);
?>
<script>
// Traducciones para el botón eliminar - pasadas directamente desde PHP
window.deleteLang = <?= json_encode($deleteLang ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
// Traducciones para alertas de validación - pasadas directamente desde PHP
window.validationLang = <?= json_encode($validationLang ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
// Mensaje del loader durante acciones (traducción)
window.orderActionLoaderMessage = <?= json_encode($pageTexts['procesando_accion'] ?? $pageTexts['cargando'] ?? '', JSON_UNESCAPED_UNICODE) ?>;
window.listLoaderMessage = <?= json_encode($pageTexts['loading_data'] ?? $pageTexts['cargando'] ?? $pageTexts['procesando_accion'] ?? '', JSON_UNESCAPED_UNICODE) ?>;
<?php if (!empty($enableProductViewModes)) : ?>
window.fvProductsViewLang = <?= json_encode([
    'view_mode_table' => $pageTexts['view_mode_table'] ?? '',
    'view_mode_cards' => $pageTexts['view_mode_cards'] ?? '',
    'view_mode_label' => $pageTexts['view_mode_label'] ?? '',
], JSON_UNESCAPED_UNICODE) ?>;
<?php endif; ?>
<?php if (!empty($addToCatalogProductId)) : ?>
window.fvAddToCatalogProductId = <?= (int) $addToCatalogProductId ?>;
<?php endif; ?>
<?php if (!empty($hideExpandSelectionAlert)) : ?>
window.fvHideExpandSelectionAlert = true;
<?php endif; ?>
<?php if (isset($planLimitBlock) && is_array($planLimitBlock)) : ?>
window.ordersPlanLimitBlock = <?= json_encode($planLimitBlock, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
<?php endif; ?>
window.showOrderActionLoader = function(msg) {
    var L = document.getElementById('products-loader');
    if (L) {
        L.classList.add('show');
        var p = L.querySelector('p');
        if (p) p.textContent = msg || orderActionLoaderMessage || 'Procesando...';
    }
};
window.hideOrderActionLoader = function() {
    var L = document.getElementById('products-loader');
    if (L) L.classList.remove('show');
};
</script>
<?php if (in_array('catalog_name', $fields ?? [], true)) : ?>
<link rel="stylesheet" href="<?= dated_file_url('companyasset/company/catalog/catalog-list.css') ?>" />
<?php endif; ?>
<style>
/* Loader oculto por defecto; solo visible con clase .show (paginación/filtros/ordenación) */
.products-loader-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    min-height: 220px;
    background: rgba(255, 255, 255, 0.82);
    z-index: 1000;
    display: none;
    align-items: center;
    justify-content: center;
    border-radius: 0.375rem;
    backdrop-filter: blur(1px);
    -webkit-backdrop-filter: blur(1px);
}
.products-loader-overlay.show {
    display: flex !important;
    opacity: 1;
}
.products-loader-overlay.hiding {
    opacity: 0;
    transition: opacity 0.3s ease;
}
.products-loader-content {
    text-align: center;
    padding: 1.25rem 1.5rem;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
    min-width: 180px;
}
.products-loader-content p {
    color: #334155 !important;
    font-weight: 600;
    font-size: 0.92rem !important;
}
.table-loader {
    width: 48px;
    height: 48px;
    position: relative;
    margin: 0 auto;
}
.table-loader-spinner {
    width: 100%;
    height: 100%;
    border: 3px solid rgba(220, 53, 69, 0.15);
    border-top-color: #dc3545;
    border-right-color: #dc3545;
    border-radius: 50%;
    animation: fv-table-loader-spin 0.8s linear infinite;
    box-sizing: border-box;
}
@keyframes fv-table-loader-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
/* Contextuales deshabilitados: mismo gris secundario (al activar, JS pone btn-success → #1EA910 en style_adicional.css) */
#contextual-actions .contextual-action-btn[disabled],
#contextual-actions .contextual-action-btn.data-disabled,
#contextual-actions a.contextual-action-btn.disabled {
    opacity: 0.7;
    cursor: not-allowed;
    pointer-events: none;
}
/* Columna status compacta (catálogo / órdenes) */
.fv-modern-grid__table th.crud-list-status-column,
.fv-modern-grid__table td.crud-list-status-cell,
.fv-crud-table th.crud-list-status-column,
.fv-crud-table td.crud-list-status-cell {
    width: 1%;
    min-width: 148px;
    max-width: 220px;
    text-align: center !important;
    white-space: nowrap;
    vertical-align: middle !important;
}
.fv-modern-grid__table th[data-field="statusOrder"],
.fv-modern-grid__table td[data-field="statusOrder"],
.fv-crud-table th[data-field="statusOrder"],
.fv-crud-table td[data-field="statusOrder"] {
    width: 1%;
    min-width: max-content;
    max-width: none;
    text-align: center !important;
    white-space: nowrap;
    vertical-align: middle !important;
    overflow: visible;
}
.user-list-avatar-column {
    width: 68px !important;
    min-width: 68px !important;
    max-width: 76px !important;
    text-align: center !important;
    vertical-align: middle !important;
}
.product-image-column {
    width: 118px !important;
    min-width: 118px !important;
    max-width: 118px !important;
    text-align: center !important;
    vertical-align: middle !important;
    padding-right: 1.25rem !important;
    overflow: hidden;
    box-sizing: border-box;
}
</style>
<div class="container-fluid page-body-wrapper">
    <div class="main-panel">
        <div class="container-fluid pt-3">
            <div class="row">
                <div class="col-md-12">
                    <?php $this->load->view('components/crud/page_heading', ['pageTitle' => $pageTitle, 'pageIcon' => $pageIcon ?? null]); ?>
                    <?php
                    if (isset($planLimitBlock) && is_array($planLimitBlock)) {
                        $this->load->view('company/plan_limit_notice', ['planLimitBlock' => $planLimitBlock]);
                    }
                    ?>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="boxsrch">
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group fv-config-crud-toolbar<?= !empty($useConfigCrudDatagrid) ? ' fv-config-crud-toolbar--page' : '' ?>" id="module-actions" style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem; overflow-x: auto;">
                                    <div class="crud-main-buttons fv-config-crud-toolbar-wrap" style="display: inline-flex; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                                        <?php $this->load->view('components/crud/button-bar', [
                                            'buttons' => $buttonBar,
                                            'defaultButtonClass' => 'btn-success',
                                            'defaultDisabled' => false,
                                            'configCrudToolbar' => $useConfigCrudDatagrid,
                                        ]) ?>
                                    </div>
                                    <div id="contextual-actions" style="display: inline-flex; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                                        <?php $this->load->view('components/crud/button-bar',['buttons'=>$contextualButtonBar,'defaultButtonClass'=>'btn-secondary','defaultDisabled'=>true,'extraButtonClass'=>'contextual-action-btn']) ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 mt-2">
                                <div id="list-info" class="fv-products-view-toolbar">
                                    <?php if (!empty($enableProductViewModes)) : ?>
                                        <div class="fv-products-view-toggle btn-group mr-auto" role="group" aria-label="<?= htmlspecialchars((string) ($pageTexts['view_mode_label'] ?? 'View'), ENT_QUOTES, 'UTF-8') ?>">
                                            <button type="button"
                                                    class="btn btn-sm btn-primary fv-products-view-toggle__btn"
                                                    data-view-mode="table"
                                                    aria-pressed="true"
                                                    title="<?= htmlspecialchars((string) ($pageTexts['view_mode_table'] ?? 'Table'), ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="fa-solid fa-table-list" aria-hidden="true"></i>
                                                <span class="d-none d-md-inline"><?= htmlspecialchars((string) ($pageTexts['view_mode_table'] ?? 'Table'), ENT_QUOTES, 'UTF-8') ?></span>
                                            </button>
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-secondary fv-products-view-toggle__btn"
                                                    data-view-mode="cards"
                                                    aria-pressed="false"
                                                    title="<?= htmlspecialchars((string) ($pageTexts['view_mode_cards'] ?? 'Cards'), ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="fa-solid fa-grip" aria-hidden="true"></i>
                                                <span class="d-none d-md-inline"><?= htmlspecialchars((string) ($pageTexts['view_mode_cards'] ?? 'Cards'), ENT_QUOTES, 'UTF-8') ?></span>
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                    <div style="display: flex; justify-content: flex-end; align-items: center; gap: 1rem; flex-wrap: wrap; margin-left: auto;">
                                    <?php
                                    if (isset($companyTotalItemsCount)):
                                        ?>
                                        <span><?= $pageTexts['company_total_items_label'] ?>: <b><?= $companyTotalItemsCount ?></b></span>
                                    <?php
                                    endif; //isset($companyTotalItemsCount)
                                    ?>
                                    <span><?= $pageTexts['cantidad_productos'] ?>: <b id="countprd"><?= $totalItemsCount ?></b></span>
                                    </div>
                                </div>
                            </div>
                            <?php
                            $useDatatableFilterToolbar = !$useConfigCrudDatagrid && !empty($listFilterDatatableToolbar) && !empty($formFilterView);
                            if (!empty($formFilterView) && !$useDatatableFilterToolbar && !$useConfigCrudDatagrid) { ?>
                                <div class="col-md-12 fv-modern-filters-wrap">
                                    <div class="fv-modern-filters fv-filter-panel fv-filter-panel--enhanced">
                                        <?php
                                        $this->load->view($formFilterView, [
                                            'format_date' => $format_date ?? null,
                                        ]);
                                        ?>
                                    </div>
                                </div>
                                <script>
                                (function() {
                                    var fvSelectpickerNoneText = <?= json_encode((string) ($pageTexts['nothing_selected'] ?? $pageTexts['all'] ?? 'Nada seleccionado'), JSON_UNESCAPED_UNICODE) ?>;
                                    function initFilterSelectpicker() {
                                        if (typeof jQuery !== 'undefined' && jQuery.fn.selectpicker) {
                                            if (jQuery.fn.selectpicker.defaults) {
                                                jQuery.fn.selectpicker.defaults.noneSelectedText = fvSelectpickerNoneText;
                                            }
                                            jQuery('#form-buscar-filtro .selectpicker').selectpicker();
                                        } else {
                                            setTimeout(initFilterSelectpicker, 100);
                                        }
                                    }
                                    if (window.addEventListener) {
                                        window.addEventListener('load', initFilterSelectpicker);
                                    } else {
                                        window.attachEvent('onload', initFilterSelectpicker);
                                    }
                                })();
                                </script>
                            <?php
                            } ?>
                        </div>
                    </div>
                    <div class="row" id="contextual-actions-container"<?= !empty($hideExpandSelectionAlert) ? ' data-hide-expand-alert="1"' : '' ?>>
                            <?php
                            $todos = (isset($filterData['input_todos']) && $filterData['input_todos'] === 'todos');
                            if ($selectionExpansible) {
                                $hideExpandSelectionAlert = !empty($hideExpandSelectionAlert);
                                ?>
                        <div class="col-12<?= $hideExpandSelectionAlert ? ' d-none' : '' ?>">
                                <?php if ($hideExpandSelectionAlert) : ?>
                                <div id="expand-selection" class="d-none" aria-hidden="true">
                                    <strong id="expand-selection-info" data-total-filtered-items="<?= (int) $totalItemsCount ?>"></strong>
                                    <a id="link-todos" href="javascript:void(0)" class="d-none" tabindex="-1"></a>
                                </div>
                                <?php else : ?>
                                <div id="expand-selection" class="alert alert-warning <?php

                                if (isset($filterData['orderDirection'])) {
                                    echo "show";
                                }
                                ?>" role="alert"<?= ($todos) ? '' : 'style="display: none;"'
                                ?>>
                                    <strong id="expand-selection-info" data-total-filtered-items="<?= $totalItemsCount ?>"><?= $pageTexts['expandSelectionInfo'] ?? 'Expandir selección' ?></strong>
                                    <a id="link-todos"
                                       href="javascript:void(0)"><?= ($todos) ? ($pageTexts['desmarcar_todos'] ?? 'Desmarcar todos') : ($pageTexts['marcar_todos'] ?? 'Marcar todos') ?></a>
                                </div>
                                <?php endif; ?>
                        </div>
                                <?php
                            }
                            ?>
                        <?php if (empty($hideExpandSelectionAlert)) : ?>
                        <div class="col-md-10">
                            <div class="form-group">
                                <div id="expansible-info"></div>
                            </div>

                        </div>
                        <?php else : ?>
                        <div id="expansible-info" class="d-none" aria-hidden="true"></div>
                        <?php endif; ?>

                    </div>
                    <div class="row">
                        <div class="col-12 position-relative<?= !empty($useConfigCrudDatagrid) ? ' fv-config-crud-datagrid-page' : '' ?>">
                            <?php
                            if (!empty($formFilterView) && !empty($listFilterDatatableToolbar) && !$useConfigCrudDatagrid) {
                                $this->load->view($formFilterView);
                            }
                            if (!empty($formFilterView) && !empty($configCrudShowServerFilterWithDatagrid) && $useConfigCrudDatagrid) {
                                $this->load->view($formFilterView);
                            }
                            ?>
                            <?php if (empty($useConfigCrudDatagrid)) : ?>
                            <?php
                            $listLoaderLabel = (string) ($pageTexts['loading_data'] ?? $pageTexts['cargando'] ?? $pageTexts['procesando_accion'] ?? '');
                            ?>
                            <!-- Loader overlay -->
                            <div id="products-loader" class="products-loader-overlay" aria-live="polite" aria-busy="false">
                                <div class="products-loader-content">
                                    <div class="table-loader">
                                        <div class="table-loader-spinner" role="status" aria-label="<?= htmlspecialchars($listLoaderLabel, ENT_QUOTES, 'UTF-8') ?>"></div>
                                    </div>
                                    <p class="mt-3 mb-0"><?= htmlspecialchars($listLoaderLabel, ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                            </div>
                            <?php endif; ?>
                            <div id="fv-products-table-view" class="fv-products-table-view<?= !empty($enableProductViewModes) ? '' : '' ?>">
                            <div class="fv-modern-grid fv-config-crud-datagrid-wrap<?= $useConfigCrudDatagrid ? ' fv-config-crud-datagrid-wrap--active' : '' ?>" id="list">
                                <div class="fv-modern-grid__shell">
                                <div class="fv-modern-grid__scroll">

                                <table id="infoTable" class="fv-modern-grid__table fv-config-crud-datagrid<?= in_array('catalog_name', $fields ?? [], true) ? ' catalog-list-table' : '' ?>" data-fv-server-list="<?= $useConfigCrudDatagrid ? '0' : '1' ?>">
                                    <?php
                                    $listCenterAllDataColumns = !empty($listCenterAllDataColumns);
                                    // Por defecto ocultar columna # (enumeración 1,2,3...)
                                    $showRowIndexColumn = $showRowIndexColumn ?? false;
                                    $showConfigCrudOrderAvatar = !empty($showConfigCrudOrderAvatar) && !empty($useConfigCrudDatagrid);
                                    $configCrudOrderColumnLabel = trim((string) ($configCrudOrderColumnLabel ?? ($pageTexts['column_order'] ?? '#')));
                                    // Filtrar campo 'id' de las columnas visibles (ocultar Id por defecto)
                                    $showIdColumn = $showIdColumn ?? false;
                                    if (!$showIdColumn) {
                                        $fields = array_filter($fields, function($field) {
                                            return $field !== 'id';
                                        });
                                        // Reindexar array para evitar gaps
                                        $fields = array_values($fields);
                                    }
                                    $configCrudInlineEditConfig = is_array($configCrudInlineEdit ?? null) ? $configCrudInlineEdit : [];
                                    $configCrudInlineEditableFields = $configCrudInlineEditConfig['editableFields'] ?? [];
                                    ?>
                                    <thead>
                                    <tr>
                                        <?php
                                        if ($multipleSelection):
                                            $selectAllLabel = (string) ($pageTexts['columnAll'] ?? $pageTexts['todo'] ?? 'Select all');
                                        ?>
                                            <th class="fuente_16px fv-crud-select-all-column<?= $listCenterAllDataColumns ? ' text-center' : '' ?>" scope="col" aria-label="<?= htmlspecialchars($selectAllLabel, ENT_QUOTES, 'UTF-8') ?>">
                                                <span class="d-flex<?= $listCenterAllDataColumns ? ' justify-content-center' : '' ?> justify-content-center">
                                                    <input
                                                        title="<?= ($todos) ? ($pageTexts['desmarcar_todos'] ?? 'Desmarcar todos') : ($pageTexts['toltip_paginas'] ?? 'Todas las páginas') ?>"
                                                        type="checkbox" <?= ($todos) ? 'checked' : '' ?> name=""
                                                        id="ckbCheckAll"
                                                        aria-label="<?= htmlspecialchars($selectAllLabel, ENT_QUOTES, 'UTF-8') ?>">
                                                </span>
                                            </th>
                                        <?php
                                        endif; ?>
                                        <?php if ($showRowIndexColumn): ?>
                                        <th class="fuente_16px text-center fv-config-crud-order-column fv-dt-no-sort" style="width: 60px;" scope="col" aria-label="<?= htmlspecialchars($configCrudOrderColumnLabel, ENT_QUOTES, 'UTF-8') ?>"><?= $showConfigCrudOrderAvatar ? '' : '#' ?></th>
                                        <?php endif; ?>
                                        <?php
                                        foreach ($fields as $field): 
                                            // Determinar alineación según el tipo de campo
                                            if ($listCenterAllDataColumns) {
                                                $alignClass = 'text-center';
                                            } else {
                                            $alignClass = 'text-left';
                                            if (in_array($field, ['status', 'statusOrder', 'default_indicator', 'isDefault', 'pro_id', 'id', 'cat_id', 'catalog_id'])) {
                                                $alignClass = 'text-center';
                                            } elseif (in_array($field, ['created', 'updated', 'created_at', 'updated_at', 'date'])) {
                                                $alignClass = 'text-center';
                                            } elseif (in_array($field, ['country_flag', 'productimage', 'image', 'avatar'])) {
                                                $alignClass = 'text-center';
                                            } elseif (in_array($field, ['total', 'total_amount', 'totsale', 'price', 'amount'])) {
                                                $alignClass = 'text-center';
                                            } elseif (isset($fieldTemplates[$field]) && strpos((string) $fieldTemplates[$field], 'boolean_switch') !== false) {
                                                $alignClass = 'text-center';
                                            }
                                            }
                                            $thStyle = ($field === 'statusOrder') ? ' style="width: 1%; white-space: nowrap;"' : '';
                                            if ($field === 'status') {
                                                $thStyle = ' style="width: 96px; min-width: 96px; white-space: nowrap;"';
                                            }
                                            $thExtraClass = ($field === 'statusOrder' || $field === 'status') ? ' crud-list-status-column' : '';
                                            if (isset($fieldTemplates[$field]) && strpos((string) $fieldTemplates[$field], 'boolean_switch') !== false) {
                                                $thExtraClass .= ' fv-crud-switch-cell';
                                                if ($thStyle === '') {
                                                    $thStyle = ' style="width: 1%; min-width: 88px; white-space: nowrap;"';
                                                }
                                            }
                                        ?>
                                            <th class="fuente_16px <?= $alignClass ?> <?= $field === 'country_flag' ? 'country-flag-column' : '' ?> <?= in_array($field, ['productimage', 'default_indicator'], true) ? 'product-image-column' : '' ?> <?= $field === 'avatar' ? 'user-list-avatar-column' : '' ?><?= $thExtraClass ?>" data-field="<?= $field ?>"<?= $thStyle ?>>
                                                <?php
                                                if (!$useConfigCrudDatagrid && in_array($field, $sortableFields)): ?>
                                                    <a data-sort="<?= $field ?>"
                                                       data-order_type="<?php
                                                       $orderType = "order_type_$field";
                                                       echo $orderType;
                                                       $currentSort = $filterData['sort'] ?? null;
                                                       $currentDir = $filterData['orderDirection'] ?? 'asc';
                                                       $toggleDir = ($currentSort == $field) ? (($currentDir == 'asc') ? 'desc' : 'asc') : 'asc';
                                                       ?>"
                                                       data-sort_direction="<?= $toggleDir ?>"
                                                       class="sort fv-crud-sort-link">
                                                        <span class="fv-crud-sort-link__text"><?= $pageTexts['fields'][$field] ?? $field ?></span><?= ($currentSort == $field) ? (' <span class="fv-crud-sort-link__indicator" aria-hidden="true">' . ($toggleDir == "asc" ? '⌄' : '⌃') . '</span>') : '' ?>
                                                    </a>
                                                <?php
                                                else: ?>
                                                    <?= $pageTexts['fields'][$field] ?? $field ?>
                                                <?php
                                                endif; ?>
                                            </th>
                                        <?php
                                        endforeach; ?>

                                        <?php
                                        if ($changeStatus): ?>
                                            <th class="fuente_16px text-center crud-list-status-column"><?= $pageTexts['colmn_status'] ?></th>
                                        <?php endif; ?>

                                        <?php if (!empty($itemActions)): ?>
                                        <th class="fuente_16px text-center crud-list-actions-column fv-crud-actions-column fv-modern-grid__actions fv-dt-no-sort" scope="col"><?= htmlspecialchars((string) ($pageTexts['colmn_action'] ?? 'Actions'), ENT_QUOTES, 'UTF-8') ?></th>
                                        <?php endif; ?>
                                    </tr>
                                    </thead>
                                    <tbody <?php
                                    if (isset($listBodyClass)) echo "class='{$listBodyClass}'" ?>>
                                    <?php
                                    $orderNumber = 1;
                                    if ($showRowIndexColumn) {
                                        // Calcular el número de orden inicial considerando la paginación
                                        $currentPage = 1;
                                        $perPage = 25;

                                        // Intentar obtener la información de paginación del objeto $page
                                        if (isset($page)) {
                                            if (is_object($page)) {
                                                // Laravel pagination object tiene métodos currentPage() y perPage()
                                                if (method_exists($page, 'currentPage')) {
                                                    $currentPage = $page->currentPage();
                                                } elseif (isset($page->current_page)) {
                                                    $currentPage = $page->current_page;
                                                } elseif (isset($page->page)) {
                                                    $currentPage = $page->page;
                                                }

                                                if (method_exists($page, 'perPage')) {
                                                    $perPage = $page->perPage();
                                                } elseif (isset($page->per_page)) {
                                                    $perPage = $page->per_page;
                                                } elseif (isset($page->perPage)) {
                                                    $perPage = $page->perPage;
                                                }
                                            } elseif (is_array($page)) {
                                                $currentPage = isset($page['current_page']) ? $page['current_page'] : (isset($page['page']) ? $page['page'] : 1);
                                                $perPage = isset($page['per_page']) ? $page['per_page'] : (isset($page['perPage']) ? $page['perPage'] : 25);
                                            }
                                        }

                                        // Completar página/tamaño desde la query si hace falta (p. ej. respuesta sin meta)
                                        if (isset($this->input)) {
                                            $inputPage = $this->input->get('page');
                                            if ($inputPage) {
                                                $currentPage = (int) $inputPage;
                                            }
                                            $inputPerPage = $this->input->get('per_page');
                                            if ($inputPerPage) {
                                                $perPage = (int) $inputPerPage;
                                            }
                                        }

                                        // Preferir `from` del paginador Laravel (API); es el índice global correcto por página
                                        $orderNumber = (($currentPage - 1) * $perPage) + 1;
                                        if (isset($page) && is_object($page) && isset($page->from) && $page->from !== null && (int) $page->from > 0) {
                                            $orderNumber = (int) $page->from;
                                        } elseif (isset($page) && is_array($page) && isset($page['from']) && $page['from'] !== null && (int) $page['from'] > 0) {
                                            $orderNumber = (int) $page['from'];
                                        }
                                    }

                                    foreach (@$list as $key => $listItem): ?>
                                        <?php
                                        $rowExtraClass = '';
                                        if (isset($listItem->default_catalog) && (int) $listItem->default_catalog === 1) {
                                            $rowExtraClass = ' fv-catalog-row--default';
                                        }
                                        $rowIsDefault = !empty($listItem->isDefault) || !empty($listItem->is_default)
                                            || (isset($listItem->default) && (int) $listItem->default === 1)
                                            || (isset($listItem->category_default) && (int) $listItem->category_default === 1);
                                        if ($rowIsDefault) {
                                            $rowExtraClass .= ' fondo-default';
                                        }
                                        $isDefaultCategoryRow = isset($listItem->category_default) && (int) $listItem->category_default === 1;
                                        ?>
                                        <?php
                                        $rowStatusValue = $listItem->$itemStatusField ?? null;
                                        $rowIsDisabled = $rowStatusValue !== null && (int) $rowStatusValue === 0;
                                        ?>
                                        <tr class="<?= $rowIsDisabled ? 'disabled' : '' ?><?= $rowExtraClass ?>">
                                            <?php
                                            if ($multipleSelection): ?>
                                                <td class="fv-crud-select-all-cell<?= $listCenterAllDataColumns ? ' text-center ' : '' ?>" style="vertical-align: middle;">
                                                    <input type="checkbox"
                                                           name="<?= "{$pageTexts['entityName']}Ids[]" ?>" <?= ($todos) ? 'checked' : '' ?>
                                                           value="<?= @$listItem->$itemIdField ?>" class="checkBoxClass"
                                                           <?= $isDefaultCategoryRow ? 'disabled title="' . htmlspecialchars((string) ($pageTexts['error_delete'] ?? $pageTexts['error_edit'] ?? ''), ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
                                                </td>
                                            <?php
                                            endif; ?>
                                            <?php if ($showRowIndexColumn): ?>
                                            <td class="fuente_16px text-center fv-config-crud-order-cell" style="width: 60px; vertical-align: middle;" data-order="<?= (int) $orderNumber ?>">
                                                <?php if ($showConfigCrudOrderAvatar): ?>
                                                    <?php $this->load->view('components/crud/config_crud_order_avatar', [
                                                        'orderNumber' => $orderNumber,
                                                        'isDefaultRow' => $rowIsDefault,
                                                    ]); ?>
                                                <?php else: ?>
                                                    <?= $orderNumber ?>
                                                <?php endif; ?>
                                            </td>
                                            <?php $orderNumber++; ?>
                                            <?php endif; ?>
                                            <?php
                                            foreach ($fields as $field): 
                                                // Determinar alineación según el tipo de campo (debe coincidir con las cabeceras)
                                                if ($listCenterAllDataColumns) {
                                                    $alignClass = 'text-center';
                                                } else {
                                                $alignClass = 'text-left';
                                                if (in_array($field, ['status', 'statusOrder', 'default_indicator', 'isDefault', 'pro_id', 'id', 'cat_id', 'catalog_id'])) {
                                                    $alignClass = 'text-center';
                                                } elseif (in_array($field, ['created', 'updated', 'created_at', 'updated_at', 'date'])) {
                                                    $alignClass = 'text-center';
                                                } elseif (in_array($field, ['country_flag', 'productimage', 'image', 'avatar'])) {
                                                    $alignClass = 'text-center';
                                                } elseif (in_array($field, ['total', 'total_amount', 'totsale', 'price', 'amount'])) {
                                                    $alignClass = 'text-center';
                                                } elseif (isset($fieldTemplates[$field]) && strpos((string) $fieldTemplates[$field], 'boolean_switch') !== false) {
                                                    $alignClass = 'text-center';
                                                }
                                                }
                                                $tdExtraClass = ($field === 'statusOrder') ? ' crud-list-status-cell' : '';
                                                if ($field === 'status') {
                                                    $tdExtraClass .= ' crud-list-status-cell';
                                                }
                                                if (isset($fieldTemplates[$field]) && strpos((string) $fieldTemplates[$field], 'boolean_switch') !== false) {
                                                    $tdExtraClass .= ' fv-crud-switch-cell';
                                                }
                                                $isInlineEditableCell = !empty($useConfigCrudDatagrid)
                                                    && in_array($field, $configCrudInlineEditableFields, true)
                                                    && !isset($fieldTemplates[$field]);
                                                if ($isInlineEditableCell) {
                                                    $tdExtraClass .= ' fv-dt-editable';
                                                }
                                                $inlineCellAttrs = '';
                                                if ($isInlineEditableCell) {
                                                    $inlineCellAttrs = sprintf(
                                                        ' data-editable="1" data-inline-field="%s" data-item-id="%s" title="%s"',
                                                        htmlspecialchars($field, ENT_QUOTES, 'UTF-8'),
                                                        htmlspecialchars((string) ($listItem->{$itemIdField} ?? ''), ENT_QUOTES, 'UTF-8'),
                                                        htmlspecialchars((string) ($configCrudInlineEditConfig['messages']['hint'] ?? ''), ENT_QUOTES, 'UTF-8')
                                                    );
                                                }
                                            ?>
                                                <td class="fuente_16px <?= $alignClass ?> <?= $field === 'country_flag' ? 'country-flag-column' : '' ?> <?= in_array($field, ['productimage', 'default_indicator'], true) ? 'product-image-column' : '' ?> <?= $field === 'avatar' ? 'user-list-avatar-column' : '' ?> <?= ($field === 'pro_id' || $field === 'id' || $field === 'customer_id') ? 'id-column' : '' ?><?= $tdExtraClass ?>" data-field="<?= $field ?>" style="vertical-align: middle;"<?= $inlineCellAttrs ?>>
                                                    <?php
                                                    if (isset($fieldTemplates[$field])):
                                                        // Para campos virtuales como country_flag, no intentar acceder a la propiedad
                                                        $fieldValue = '';
                                                        if ($field === 'country_flag') {
                                                            $fieldValue = ''; // Campo virtual, no existe en los datos
                                                        } elseif (isset($listItem->{$field})) {
                                                            $fieldValue = $listItem->{$field};
                                                        }
                                                        $templateData = array(
                                                            'listItem' => $listItem,
                                                            'value' => $fieldValue,
                                                            'fieldName' => $field,
                                                        );
                                                        // Pasar datos adicionales si existen (como countryMap)
                                                        if (isset($countryMap)) {
                                                            $templateData['countryMap'] = $countryMap;
                                                        }
                                                        if (isset($countryDataMap)) {
                                                            $templateData['countryDataMap'] = $countryDataMap;
                                                        }
                                                        if (isset($countryList)) {
                                                            $templateData['countryList'] = $countryList;
                                                        }
                                                        if (isset($groupList)) {
                                                            $templateData['groupList'] = $groupList;
                                                        }
                                                        if (isset($orderStatuses)) {
                                                            $templateData['orderStatuses'] = $orderStatuses;
                                                        }
                                                        if (isset($statusLabelMap)) {
                                                            $templateData['statusLabelMap'] = $statusLabelMap;
                                                        }
                                                        if (isset($changeStatusUrl)) {
                                                            $templateData['changeStatusUrl'] = $changeStatusUrl;
                                                        }
                                                        if (isset($apiToken)) {
                                                            $templateData['apiToken'] = $apiToken;
                                                        }
                                                        if (isset($apiBaseUrl)) {
                                                            $templateData['apiBaseUrl'] = $apiBaseUrl;
                                                        }
                                                        // Pasar variables necesarias para enable-disable_button
                                                        if (isset($itemStatusField)) {
                                                            $templateData['itemStatusField'] = $itemStatusField;
                                                        }
                                                        if (isset($itemIdField)) {
                                                            $templateData['itemIdField'] = $itemIdField;
                                                        }
                                                        if (isset($controllerUrl)) {
                                                            $templateData['controllerUrl'] = $controllerUrl;
                                                        }
                                                        if (isset($pageTexts)) {
                                                            $templateData['pageTexts'] = $pageTexts;
                                                        }
                                                        if (isset($format_numeric)) {
                                                            $templateData['format_numeric'] = $format_numeric;
                                                        }
                                                        if (isset($format_currency)) {
                                                            $templateData['format_currency'] = $format_currency;
                                                        }
                                                        if (isset($format_date)) {
                                                            $templateData['format_date'] = $format_date;
                                                        }
                                                        $this->load->view(
                                                            $fieldTemplates[$field],
                                                            $templateData
                                                        );
                                                    else:
                                                        $rawValue = isset($listItem->{$field}) ? $listItem->{$field} : '';
                                                        // Aplicar formato numérico a totales/importes cuando no hay plantilla
                                                        if (in_array($field, ['total', 'total_amount', 'totsale', 'price', 'amount']) && is_numeric($rawValue)) {
                                                            if (function_exists('fv_format_money')) {
                                                                $rawValue = fv_format_money($rawValue, $format_numeric ?? null, is_string($format_currency ?? null) ? $format_currency : null);
                                                            } elseif (isset($format_numeric) && (is_object($format_numeric) || is_array($format_numeric))) {
                                                            $digits = (int) (is_object($format_numeric) ? ($format_numeric->digits ?? 2) : ($format_numeric['digits'] ?? 2));
                                                            $radix = is_object($format_numeric) ? ($format_numeric->radixPoint ?? '.') : ($format_numeric['radixPoint'] ?? '.');
                                                            $group = function_exists('fv_resolve_numeric_group_separator')
                                                                ? fv_resolve_numeric_group_separator($format_numeric)
                                                                : (is_object($format_numeric) ? ($format_numeric->groupSeparator ?? ',') : ($format_numeric['groupSeparator'] ?? ','));
                                                            $rawValue = number_format((float) $rawValue, $digits, $radix, $group);
                                                            }
                                                        }
                                                        ?>
                                                        <?= $rawValue ?>
                                                    <?php
                                                    endif; ?>
                                                </td>
                                            <?php
                                            endforeach; ?>

                                            <?php
                                            if ($changeStatus): ?>
                                                <td class="text-center crud-list-status-cell" style="vertical-align: middle;">
                                                    <?php $this->load->view('components/crud/enable-disable_button', [
                                                        'listItem' => $listItem,
                                                        'itemIdField' => $itemIdField,
                                                        'itemStatusField' => $itemStatusField,
                                                        'controllerUrl' => $controllerUrl,
                                                        'pageTexts' => $pageTexts ?? [],
                                                    ]); ?>
                                                </td>
                                            <?php endif; ?>

                                            <?php if (!empty($itemActions)): ?>
                                            <td class="text-center crud-list-actions-column fv-modern-grid__actions" style="vertical-align: middle;">
                                                <div class="fv-modern-grid__actions-inner fv-crud-row-actions">
                                                    <?php foreach ($itemActions as $actionName => $itemAction):
                                                        if(isset($actionButtonTemplates[$actionName])):
                                                            $this->load->view($actionButtonTemplates[$actionName], [
                                                                'listItem' => $listItem,
                                                                'itemAction' => $itemAction,
                                                                'itemIdField' => $itemIdField,
                                                                'actionName' => $actionName,
                                                                'pageTexts' => $pageTexts ?? [],
                                                            ]);
                                                        else:
                                                            $isDeleteButton = ($actionName === 'delete' || strpos($itemAction['buttonClass'] ?? '', 'go-delete') !== false || strpos($itemAction['buttonClass'] ?? '', 'btn-go-delete') !== false);
                                                            $isDefaultCatalog = isset($listItem->default_catalog) && (int) $listItem->default_catalog === 1;
                                                            $rowBtnClass = (strpos($itemAction['buttonClass'] ?? '', 'btn-') === 0) ? $itemAction['buttonClass'] : 'btn-' . ($itemAction['buttonClass'] ?? 'primary');
                                                        ?>
                                                    <?php if ($isDeleteButton && !$isDefaultCatalog): ?>
                                                    <button type="button"
                                                            class="btn fv-crud-btn fv-crud-btn--row <?= $rowBtnClass ?> btn-sm go-delete btn-delete-catalog"
                                                            title="<?= $itemAction['label'] ?>"
                                                            data-delete-url="<?= $itemAction['action'] ?><?= $listItem->$itemIdField ?>"
                                                            data-catalog-id="<?= $listItem->$itemIdField ?>">
                                                        <i class="<?=$itemAction['iconClass']?>"></i>
                                                    </button>
                                                    <?php elseif ($isDeleteButton && $isDefaultCatalog): ?>
                                                    <span class="btn fv-crud-btn fv-crud-btn--row btn-secondary btn-sm disabled" title="<?= htmlspecialchars((string) ($pageTexts['catalogo_default'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><i class="mdi mdi-lock"></i></span>
                                                    <?php else: ?>
                                                    <a href="<?= $itemAction['action'] ?><?= $listItem->$itemIdField ?>"
                                                       class="btn fv-crud-btn fv-crud-btn--row <?= $rowBtnClass ?> btn-sm"
                                                       title="<?= $itemAction['label'] ?>"
                                                    >
                                                        <i class="<?=$itemAction['iconClass']?>"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                    <?php
                                                    endif;
                                                    endforeach; ?>
                                                </div>
                                            </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php
                                    endforeach ?>
                                    </tbody>
                                </table>
                                </div>
                                </div>
                            </div>
                            </div>
                            <?php
                                if (!empty($enableProductViewModes)) {
                                    $this->load->view('company/product/list/cards_view', [
                                        'list' => $list ?? [],
                                        'itemActions' => $itemActions ?? [],
                                        'actionButtonTemplates' => $actionButtonTemplates ?? [],
                                        'itemIdField' => $itemIdField,
                                        'itemStatusField' => $itemStatusField,
                                        'changeStatus' => $changeStatus ?? true,
                                        'multipleSelection' => $multipleSelection ?? false,
                                        'pageTexts' => $pageTexts,
                                        'controllerUrl' => $controllerUrl,
                                        'format_numeric' => $format_numeric ?? null,
                                        'format_currency' => $format_currency ?? null,
                                        'todos' => $todos ?? false,
                                    ]);
                                }
                            ?>
                            <div class="fv-modern-grid__toolbar fv-modern-grid__toolbar--bottom fv-products-list-pagination">
                                <?php
                                if (empty($hideServerPagination)) {
                                    $this->load->view('components/crud/pagination');
                                }
                                ?>
                            </div>
                            <?php
                                // Cargar modales de edición para cada catálogo en la lista
                                // Deshabilitado cuando se usa Catalog Studio (página completa)
                                if (empty($disableCatalogEditModals) && isset($list) && !empty($list) && isset($product) && isset($category)) {
                                    foreach ($list as $listItem) {
                                        $catalogId = $listItem->catalog_id ?? $listItem->{$itemIdField} ?? null;
                                        if ($catalogId) {
                                            $editModalData = [
                                                'catalog_id' => $catalogId,
                                                'product' => $product,
                                                'category' => $category,
                                                'lang_prods' => $lang_prods ?? [],
                                                'lang_labels' => $lang_labels ?? [],
                                                'lang_optionbasic' => $lang_optionbasic ?? [],
                                                'pageTexts' => $pageTexts ?? [],
                                            ];
                                            $this->load->view('company/catalog/modals/edit_catalog_form', $editModalData);
                                        }
                                    }
                                }
                                
                                // Cargar modales de envío de catálogo para cada catálogo en la lista (igual que en producción)
                                if (isset($langcatalog) && isset($list) && !empty($list)) {
                                    foreach ($list as $listItem) {
                                        $catalogId = $listItem->catalog_id ?? $listItem->{$itemIdField} ?? null;
                                        if ($catalogId) {
                                            $sendModalData = [
                                                'v' => $listItem, // Usar 'v' como en producción
                                                'group_list' => isset($group_list) ? $group_list : [],
                                                'customerlist' => isset($customerlist) ? $customerlist : [],
                                                'company' => isset($company) ? $company : null,
                                                'lang_optionbasic' => isset($lang_optionbasic) ? $lang_optionbasic : [],
                                                'langcatalog' => isset($langcatalog) ? $langcatalog : [],
                                            ];
                                            $this->load->view('company/catalog/modals/modal_sendcatalog', $sendModalData);
                                        }
                                    }
                                }

                                // Cargar modales de preview para cada catálogo en la lista (replicado de producción)
                                if (isset($langcatalog) && isset($list) && !empty($list)) {
                                    // Obtener catalogEnrichedMap si está disponible
                                    $catalogEnrichedMap = isset($catalogEnrichedMap) ? $catalogEnrichedMap : [];
                                    
                                    foreach ($list as $listItem) {
                                        $catalogId = $listItem->catalog_id ?? $listItem->{$itemIdField} ?? null;
                                        if ($catalogId) {
                                            // Enriquecer el catálogo con products y categories si están disponibles
                                            $enrichedCatalog = clone $listItem;
                                            if (isset($catalogEnrichedMap[$catalogId])) {
                                                $enrichedCatalog->products = $catalogEnrichedMap[$catalogId]['products'] ?? [];
                                                $enrichedCatalog->categories = $catalogEnrichedMap[$catalogId]['categories'] ?? [];
                                            } else {
                                                $enrichedCatalog->products = [];
                                                $enrichedCatalog->categories = [];
                                                $productNameRaw = trim((string) ($listItem->product_name ?? ''));
                                                if ($productNameRaw !== '') {
                                                    foreach (array_filter(array_map('trim', explode(',', $productNameRaw))) as $skuToken) {
                                                        $enrichedCatalog->products[] = (object) [
                                                            'sku' => $skuToken,
                                                            'name' => '',
                                                        ];
                                                    }
                                                }
                                            }
                                            
                                            $previewModalData = [
                                                'v' => $enrichedCatalog, // Usar 'v' como en producción, con datos enriquecidos
                                                'langcatalog' => isset($langcatalog) ? $langcatalog : [],
                                                'lang_modal' => isset($lang_modal) ? $lang_modal : [],
                                            ];
                                            
                                            try {
                                                $this->load->view('company/catalog/modals/modal_preview', $previewModalData);
                                            } catch (\Exception $e) {
                                                log_message('error', 'Error al cargar modal preview: ' . $e->getMessage());
                                                echo '<!-- Error al cargar modal preview para catalog_id: ' . $catalogId . ' -->';
                                            }
                                        }
                                    }
                                }

                                // Cargar modales de download para cada catálogo en la lista (replicado de producción)
                                if (isset($langcatalog) && isset($list) && !empty($list)) {
                                    foreach ($list as $listItem) {
                                        $catalogId = $listItem->catalog_id ?? $listItem->{$itemIdField} ?? null;
                                        if ($catalogId) {
                                            $downloadModalData = [
                                                'v' => $listItem, // Usar 'v' como en producción
                                                'group_list' => isset($group_list) ? $group_list : [],
                                                'format_list' => isset($format_list) ? $format_list : [],
                                                'lang_optionbasic' => isset($lang_optionbasic) ? $lang_optionbasic : [],
                                                'langcatalog' => isset($langcatalog) ? $langcatalog : [],
                                                'lang_modal' => isset($lang_modal) ? $lang_modal : [],
                                            ];
                                            
                                            try {
                                                $this->load->view('company/catalog/modals/modal_download', $downloadModalData);
                                            } catch (\Exception $e) {
                                                log_message('error', 'Error al cargar modal download: ' . $e->getMessage());
                                                echo '<!-- Error al cargar modal download para catalog_id: ' . $catalogId . ' -->';
                                            }
                                        }
                                    }
                                }

                                // Cargar modales de performance para cada catálogo en la lista (replicado de producción)
                                if (isset($langcatalog) && isset($list) && !empty($list)) {
                                    foreach ($list as $listItem) {
                                        $catalogId = $listItem->catalog_id ?? $listItem->{$itemIdField} ?? null;
                                        if ($catalogId) {
                                            // Obtener datos de performance pre-cargados
                                            $perfData = isset($catalogPerformanceMap[$catalogId]) ? $catalogPerformanceMap[$catalogId] : [
                                                'sending' => 0,
                                                'customer_count' => 0,
                                                'total' => 0,
                                            ];
                                            
                                            $performanceModalData = [
                                                'v' => $listItem,
                                                'lang_optionbasic' => isset($lang_optionbasic) ? $lang_optionbasic : [],
                                                'langcatalog' => isset($langcatalog) ? $langcatalog : (isset($pageTexts) ? $pageTexts : []),
                                                'format_numeric' => isset($format_numeric) ? $format_numeric : (object) [],
                                                'moneda' => isset($moneda) ? $moneda : '',
                                                'lang_modal' => isset($lang_modal) ? $lang_modal : [],
                                                'sending' => $perfData['sending'],
                                                'customer_count' => $perfData['customer_count'],
                                                'total_import' => $perfData['total'],
                                            ];
                                            
                                            try {
                                                $this->load->view('company/catalog/modals/modal_performance', $performanceModalData);
                                            } catch (\Exception $e) {
                                                log_message('error', 'Error al cargar modal performance: ' . $e->getMessage());
                                                echo '<!-- Error al cargar modal performance para catalog_id: ' . $catalogId . ' -->';
                                            }
                                        }
                                    }
                                }
                                
                                // Modal de formatos (solo Catálogos; también con lista vacía)
                                $isCatalogFormatsPage = isset($langcatalog) || (isset($catalogPerformanceMap));
                                static $formatsModalLoaded = false;
                                if ($isCatalogFormatsPage && !$formatsModalLoaded) {
                                    $formatsModalData = [
                                        'lang_optionbasic' => isset($lang_optionbasic) ? $lang_optionbasic : [],
                                        'langcatalog' => isset($langcatalog) ? $langcatalog : (isset($pageTexts) ? $pageTexts : []),
                                        'lang_modal' => isset($lang_modal) ? $lang_modal : [],
                                    ];

                                    try {
                                        $this->load->view('company/catalog/modals/modal_formats', $formatsModalData);
                                        $formatsModalLoaded = true;
                                    } catch (\Exception $e) {
                                        log_message('error', 'Error al cargar modal formats: ' . $e->getMessage());
                                        echo '<!-- Error al cargar modal formats -->';
                                    }
                                }
                                
                                if(!empty($modals))
                                    foreach ($modals as $modalsView){
                                        $modalData = [];
                                        if (isset($apiToken)) {
                                            $modalData['apiToken'] = $apiToken;
                                        }
                                        if (isset($apiBaseUrl)) {
                                            $modalData['apiBaseUrl'] = $apiBaseUrl;
                                        }
                                        if (isset($pageTexts)) {
                                            $modalData['pageTexts'] = $pageTexts;
                                        }
                                        if (isset($format_numeric)) {
                                            $modalData['format_numeric'] = $format_numeric;
                                        }
                                        if (isset($format_currency)) {
                                            $modalData['format_currency'] = $format_currency;
                                        }
                                        if (isset($code_currency)) {
                                            $modalData['code_currency'] = $code_currency;
                                        }
                                        // Pasar todos los datos adicionales disponibles (para modales que los necesiten)
                                        if (isset($product)) {
                                            $modalData['product'] = $product;
                                        }
                                        if (isset($category)) {
                                            $modalData['category'] = $category;
                                        }
                                        if (isset($lang_prods)) {
                                            $modalData['lang_prods'] = $lang_prods;
                                        }
                                        if (isset($lang_labels)) {
                                            $modalData['lang_labels'] = $lang_labels;
                                        }
                                        if (isset($lang_optionbasic)) {
                                            $modalData['lang_optionbasic'] = $lang_optionbasic;
                                        }
                                        if (isset($category_selected_order)) {
                                            $modalData['category_selected_order'] = $category_selected_order;
                                        }
                                        if (isset($lang_order)) {
                                            $modalData['lang_order'] = $lang_order;
                                        }
                                        if (isset($lang_login_seller)) {
                                            $modalData['lang_login_seller'] = $lang_login_seller;
                                        }
                                        if (isset($langprods)) {
                                            $modalData['langprods'] = $langprods;
                                        }
                                        if (isset($lang_modal)) {
                                            $modalData['lang_modal'] = $lang_modal;
                                        }
                                        if (isset($options_order)) {
                                            $modalData['options_order'] = $options_order;
                                        }
                                        // Modales de clientes (assign/remove seller, enviar catálogo, etc.): el listado
                                        // ya tiene estos datos en getDataForView; hay que pasarlos al hijo (load->view no hereda el scope).
                                        if (isset($sellerList)) {
                                            $modalData['sellerList'] = $sellerList;
                                        }
                                        if (isset($termList)) {
                                            $modalData['termList'] = $termList;
                                        }
                                        if (isset($catalogList)) {
                                            $modalData['catalogList'] = $catalogList;
                                        }
                                        if (isset($company)) {
                                            $modalData['company'] = $company;
                                        }
                                        if (isset($groupList)) {
                                            $modalData['groupList'] = $groupList;
                                        }
                                        if (isset($planLimitBlock) && is_array($planLimitBlock)) {
                                            $modalData['planLimitBlock'] = $planLimitBlock;
                                        }
                                        if (isset($countryDataMap)) {
                                            $modalData['countryDataMap'] = $countryDataMap;
                                        }
                                        if (isset($categorylist)) {
                                            $modalData['categorylist'] = $categorylist;
                                        }
                                        $this->load->view($modalsView, $modalData);
                                    }
                                ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para ver detalles del cliente -->
<?php if (isset($apiToken) && isset($apiBaseUrl)): ?>
<div class="modal fade" id="modalViewCustomer" tabindex="-1" role="dialog" aria-labelledby="modalViewCustomerLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalViewCustomerLabel">Detalles del Cliente</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="customerDetailsContent">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only"></span>
                    </div>
                    <p class="mt-2"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <a href="javascript:void(0)" id="btnEditCustomer" class="btn btn-primary" style="display: none;">
                    <i class="mdi mdi-pencil"></i> Editar Cliente
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Definir funciones globales (no dependen de jQuery)
window.openurl = function(url) {
    var w = 1500;
    var h = 1200;
    var left = (screen.width / 2) - (w / 2);
    var top = (screen.height / 2) - (h / 2);

    var childWindow = window.open(url, 'NewWindow33', 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=no, width=' + w + ', height=' + h + ', top=' + top + ', left=' + left);

    if (childWindow) {
        // Enfocar la ventana
        childWindow.focus();
        // Manejar evento cuando la ventana hijo se cierre
        childWindow.onbeforeunload = function() {
            // Refrescar la ventana padre
            location.reload();
        };
    } else {
        alert('Por favor, permite ventanas emergentes para este sitio.');
    }
};

// Alias para compatibilidad
function openurl(url) {
    window.openurl(url);
}

// Esperar a que jQuery esté disponible antes de ejecutar
// Usa CoreLoader para evitar duplicación de código
(function() {
    // Usar CoreLoader si está disponible, sino usar fallback simple
    var waitForJQuery = (typeof CoreLoader !== 'undefined' && CoreLoader.waitForJQuery) 
        ? CoreLoader.waitForJQuery 
        : function(callback) {
            if (typeof jQuery !== 'undefined') {
                callback();
            } else {
                setTimeout(function() { waitForJQuery(callback); }, 100);
            }
        };
    
    waitForJQuery(function() {
        jQuery(document).ready(function($) {
            var apiToken = '<?= $apiToken ?>';
            var apiBaseUrl = '<?= $apiBaseUrl ?>';
            
            // Manejar click en el link del ID del cliente
            $(document).on('click', '.view-customer-link', function(e) {
        e.preventDefault();
        var customerId = $(this).data('customer-id');
        
        if (!customerId) {
            alert('Error: No se pudo obtener el ID del cliente');
            return;
        }
        
        // Mostrar modal
        $('#modalViewCustomer').modal('show');
        
        // Limpiar contenido anterior
        $('#customerDetailsContent').html(
            '<div class="text-center">' +
            '<div class="spinner-border text-primary" role="status">' +
            '<span class="sr-only">Cargando...</span>' +
            '</div>' +
            '<p class="mt-2">Cargando información del cliente...</p>' +
            '</div>'
        );
        $('#btnEditCustomer').hide();
        
        // Cargar datos del cliente con relaciones
        $.ajax({
            url: apiBaseUrl + '/v2/customer/' + customerId,
            method: 'GET',
            data: {
                'relations': ['customer_group', 'sellers.user']
            },
            headers: {
                'Accept': 'application/json',
                'Authorization': 'Bearer ' + apiToken
            },
            success: function(response) {
                console.log('Response recibida:', response);
                // La API puede devolver los datos directamente o dentro de response.data
                var customer = response.data || response;
                
                if (customer && (customer.customer_id || customer.id)) {
                    renderCustomerDetails(customer);
                    var editUrl = '<?= company_url("customers/edit/") ?>' + customerId;
                    $('#btnEditCustomer').attr('onclick', 'openurl(\'' + editUrl.replace(/'/g, "\\'") + '\'); return false;').show();
                } else {
                    console.error('Estructura de respuesta inesperada:', response);
                    $('#customerDetailsContent').html(
                        '<div class="alert alert-danger">Error: Estructura de datos inesperada. Por favor, revise la consola del navegador para más detalles.</div>'
                    );
                }
            },
            error: function(xhr, status, error) {
                console.error('Error en AJAX:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    statusCode: xhr.status
                });
                var errorMsg = 'Error al cargar los datos del cliente';
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    } else if (xhr.responseJSON.error) {
                        errorMsg = xhr.responseJSON.error;
                    }
                } else if (xhr.responseText) {
                    try {
                        var parsed = JSON.parse(xhr.responseText);
                        errorMsg = parsed.message || parsed.error || errorMsg;
                    } catch(e) {
                        errorMsg = 'Error ' + xhr.status + ': ' + (xhr.responseText || error);
                    }
                }
                $('#customerDetailsContent').html(
                    '<div class="alert alert-danger">' + errorMsg + '</div>'
                );
            }
        });
    });
    
    function renderCustomerDetails(customer) {
        var html = '<div class="row">';
        
        // Información Básica
        html += '<div class="col-md-12"><h6 class="mb-3"><i class="mdi mdi-information"></i> Información Básica</h6></div>';
        html += '<div class="col-md-6 mb-3">';
        html += '<label class="font-weight-bold">ID:</label><br>';
        html += '<span>' + (customer.customer_id || 'N/A') + '</span>';
        html += '</div>';
        html += '<div class="col-md-6 mb-3">';
        html += '<label class="font-weight-bold">ID ERP:</label><br>';
        html += '<span>' + (customer.cust_id_kor || 'N/A') + '</span>';
        html += '</div>';
        html += '<div class="col-md-6 mb-3">';
        html += '<label class="font-weight-bold">Nombre:</label><br>';
        html += '<span>' + (customer.name || 'N/A') + '</span>';
        html += '</div>';
        html += '<div class="col-md-6 mb-3">';
        html += '<label class="font-weight-bold">Empresa:</label><br>';
        html += '<span>' + (customer.business_name || 'N/A') + '</span>';
        html += '</div>';
        html += '<div class="col-md-6 mb-3">';
        html += '<label class="font-weight-bold">Email:</label><br>';
        html += '<span>' + (customer.email || 'N/A') + '</span>';
        html += '</div>';
        html += '<div class="col-md-6 mb-3">';
        html += '<label class="font-weight-bold">Teléfono:</label><br>';
        html += '<span>' + (customer.phone || 'N/A') + '</span>';
        html += '</div>';
        html += '<div class="col-md-6 mb-3">';
        html += '<label class="font-weight-bold">Celular:</label><br>';
        html += '<span>' + (customer.cell_phone || 'N/A') + '</span>';
        html += '</div>';
        html += '<div class="col-md-6 mb-3">';
        html += '<label class="font-weight-bold">Estado:</label><br>';
        var statusBadge = customer.customer_status == 1 
            ? '<span class="badge badge-success">Activo</span>' 
            : '<span class="badge badge-danger">Inactivo</span>';
        html += '<span>' + statusBadge + '</span>';
        html += '</div>';
        
        // Dirección Comercial
        html += '<div class="col-md-12 mt-4"><h6 class="mb-3"><i class="mdi mdi-map-marker"></i> Dirección Comercial</h6></div>';
        html += '<div class="col-md-12 mb-3">';
        html += '<label class="font-weight-bold">Dirección:</label><br>';
        html += '<span>' + (customer.commercial_address || 'N/A') + '</span>';
        html += '</div>';
        html += '<div class="col-md-4 mb-3">';
        html += '<label class="font-weight-bold">País:</label><br>';
        html += '<span>' + (customer.commercial_country || 'N/A') + '</span>';
        html += '</div>';
        html += '<div class="col-md-4 mb-3">';
        html += '<label class="font-weight-bold">Estado:</label><br>';
        html += '<span>' + (customer.commercial_state || 'N/A') + '</span>';
        html += '</div>';
        html += '<div class="col-md-4 mb-3">';
        html += '<label class="font-weight-bold">Ciudad:</label><br>';
        html += '<span>' + (customer.commercial_city || 'N/A') + '</span>';
        html += '</div>';
        html += '<div class="col-md-4 mb-3">';
        html += '<label class="font-weight-bold">Zona:</label><br>';
        html += '<span>' + (customer.commercial_zone || 'N/A') + '</span>';
        html += '</div>';
        html += '<div class="col-md-4 mb-3">';
        html += '<label class="font-weight-bold">Código Postal:</label><br>';
        html += '<span>' + (customer.commercial_zip_code || 'N/A') + '</span>';
        html += '</div>';
        
        // Información Adicional
        if (customer.customer_group) {
            html += '<div class="col-md-12 mt-4"><h6 class="mb-3"><i class="mdi mdi-account-group"></i> Información Adicional</h6></div>';
            html += '<div class="col-md-6 mb-3">';
            html += '<label class="font-weight-bold">Grupo de Clientes:</label><br>';
            html += '<span>' + (customer.customer_group.name || 'N/A') + '</span>';
            html += '</div>';
        }
        
        if (customer.sellers && customer.sellers.length > 0) {
            var sellersList = customer.sellers.map(function(seller) {
                return seller.user ? seller.user.name : 'N/A';
            }).join(', ');
            html += '<div class="col-md-6 mb-3">';
            html += '<label class="font-weight-bold">Vendedores:</label><br>';
            html += '<span>' + sellersList + '</span>';
            html += '</div>';
        }
        
        html += '</div>';
        
            $('#customerDetailsContent').html(html);
        }
        });
    });
})();
</script>

<script>
// Script para asegurar que los botones de modal funcionen correctamente
(function() {
    // Usar CoreLoader si está disponible
    var waitForJQuery = (typeof CoreLoader !== 'undefined' && CoreLoader.waitForJQuery) 
        ? CoreLoader.waitForJQuery 
        : function(callback) {
            if (typeof jQuery !== 'undefined') {
                callback();
            } else {
                setTimeout(function() { waitForJQuery(callback); }, 100);
            }
        };
    
    waitForJQuery(function() {
        var $ = jQuery;
        
        // Asegurar que los botones con data-target abran los modales
        $(document).on('click', 'button[data-target="#modalAddCatalog"], button[data-bs-target="#modalAddCatalog"]', function(e) {
            e.preventDefault();
            var target = $(this).attr('data-target') || $(this).attr('data-bs-target');
            if (target) {
                $(target).modal('show');
            }
        });
        
        // También manejar clics en botones con onclick que contengan modal
        $(document).on('click', 'button[onclick*="modalAddCatalog"]', function(e) {
            // El onclick ya maneja el modal, pero verificamos que exista
            if ($('#modalAddCatalog').length === 0) {
                console.error('Modal #modalAddCatalog no encontrado en el DOM');
            }
        });
        
        // Manejar botones de eliminar con data-delete-url (si no tienen la clase go-delete)
        $(document).on('click', 'button[data-delete-url]:not(.go-delete)', function(e) {
            console.log('🗑️ === BOTÓN ELIMINAR CLICKEADO ===');
            console.log('🔘 Elemento:', this);
            console.log('📋 Clases del botón:', $(this).attr('class'));
            
            e.preventDefault();
            e.stopPropagation();
            
            var deleteButton = $(this);
            var url = deleteButton.data('delete-url');
            
            console.log('🌐 URL de eliminación:', url);
            console.log('📌 Botón completo:', deleteButton);
            
            if (!url) {
                console.error('❌ No se encontró URL para eliminar');
                var errorMsg = (window.deleteLang && window.deleteLang.error_no_url_message) || (window.fvUiText && window.fvUiText('error_no_url_message')) || '';
                if (typeof swal !== 'undefined') {
                    swal({
                        title: (window.deleteLang && window.deleteLang.error_no_url) || (window.fvUiText && window.fvUiText('error_no_url')) || '',
                        text: errorMsg,
                        icon: 'error'
                    });
                } else {
                    alert(errorMsg);
                }
                return false;
            }
            
            // Alerta genérica sin título (window.deleteLang desde cmp-toltip)
            var toltipLang = window.deleteLang || {};
            var confirmText = toltipLang.desea_eliminar_registro || toltipLang.esta_seguro || (window.fvUiText && window.fvUiText('desea_eliminar_registro')) || (window.fvUiText && window.fvUiText('esta_seguro')) || '';
            var btnCancel = toltipLang.cancelar || (window.fvUiText && window.fvUiText('cancelar')) || '';
            var btnConfirm = toltipLang.eliminar || (window.fvUiText && window.fvUiText('eliminar')) || '';
            console.log('❓ Mostrando confirmación de eliminación');
            
            if (typeof swal !== 'undefined') {
                console.log('✅ SweetAlert disponible, usando swal para confirmación');
                swal({
                    title: '',
                    text: confirmText,
                    icon: 'warning',
                    buttons: {
                        cancel: {
                            text: btnCancel,
                            value: false,
                            visible: true,
                            className: "sweet-danager",
                            closeModal: true,
                        },
                        confirm: {
                            text: btnConfirm,
                            value: true,
                            visible: true,
                            className: "sweet-warning",
                            closeModal: true
                        },
                    },
                }).then((willDelete) => {
                    console.log('👤 Usuario respondió:', willDelete ? 'Confirmar eliminación' : 'Cancelar');
                    if (willDelete) {
                        console.log('✅ Usuario confirmó, procediendo a eliminar...');
                        performDelete(url, deleteButton);
                    } else {
                        console.log('❌ Usuario canceló la eliminación');
                    }
                });
            } else {
                console.log('⚠️ SweetAlert no disponible, usando confirm nativo');
                if (confirm(confirmText)) {
                    console.log('✅ Usuario confirmó, procediendo a eliminar...');
                    performDelete(url, deleteButton);
                } else {
                    console.log('❌ Usuario canceló la eliminación');
                }
            }
        });
        
        // Manejar botones con clase go-delete (incluyendo los que tienen data-delete-url)
        $(document).on('click', '.go-delete, .btn-go-delete, .btn-delete-catalog', function(e) {
            console.log('🗑️ === BOTÓN ELIMINAR (go-delete) CLICKEADO ===');
            console.log('🔘 Elemento:', this);
            console.log('📋 Clases del botón:', $(this).attr('class'));
            console.log('📌 Tag:', this.tagName);
            
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            var deleteButton = $(this);
            var url = deleteButton.attr('href') || deleteButton.data('delete-url');

            if (typeof window.showDeleteConfirmation === 'function') {
                window.showDeleteConfirmation(url, deleteButton, {});
                return false;
            }
            
            var catalogId = deleteButton.data('catalog-id');
            
            console.log('🌐 URL de eliminación:', url);
            console.log('🆔 Catalog ID:', catalogId);
            
            if (!url) {
                console.error('❌ No se encontró URL para eliminar');
                var dl0 = window.deleteLang || {};
                showAlert(
                    (window.fvUiText && window.fvUiText('error_no_url')) || dl0.error_no_url || dl0.error_occurred || '',
                    (window.fvUiText && window.fvUiText('error_no_url_message')) || dl0.error_no_url_message || '',
                    'error'
                );
                return false;
            }
            
            var confirmMessage = (window.fvUiText && window.fvUiText('desea_eliminar_registro'))
                || (window.fvUiText && window.fvUiText('esta_seguro'))
                || '';
            console.log('❓ Mostrando confirmación de eliminación');
            
            showConfirmDialog(
                confirmMessage,
                confirmMessage,
                function() {
                    // Usuario confirmó
                    console.log('✅ Usuario confirmó, procediendo a eliminar...');
                    performDelete(url, deleteButton);
                },
                function() {
                    // Usuario canceló
                    console.log('❌ Usuario canceló la eliminación');
                }
            );
            
            return false;
        });
        
        // Función para mostrar alertas (compatible con SweetAlert y alert nativo)
        function showAlert(title, message, type) {
            type = type || 'info';
            if (typeof swal !== 'undefined' && typeof swal === 'function') {
                swal({
                    title: title,
                    text: message,
                    icon: type
                });
            } else {
                alert(title + '\n\n' + message);
            }
        }
        
        // Función para mostrar diálogo de confirmación
        function showConfirmDialog(title, message, onConfirm, onCancel) {
            onCancel = onCancel || function() {};
            
            // Usar traducciones pasadas directamente desde PHP (window.deleteLang)
            const toltipLang = Object.assign({}, window.deleteLang || {}, window.fvUiLang || {});
            
            const lang = {
                cancelar: toltipLang.cancelar || (window.fvUiText && window.fvUiText('cancelar')) || '',
                eliminar: toltipLang.eliminar || (window.fvUiText && window.fvUiText('eliminar')) || ''
            };
            
            if (typeof swal !== 'undefined' && typeof swal === 'function') {
                console.log('✅ SweetAlert disponible, usando swal para confirmación');
                swal({
                    title: title,
                    text: message,
                    icon: 'warning',
                    buttons: {
                        cancel: {
                            text: lang.cancelar,
                            value: false,
                            visible: true,
                            className: "btn-secondary",
                            closeModal: true,
                        },
                        confirm: {
                            text: lang.eliminar,
                            value: true,
                            visible: true,
                            className: "btn-danger",
                            closeModal: true
                        },
                    },
                    dangerMode: true
                }).then((willDelete) => {
                    console.log('👤 Usuario respondió:', willDelete ? 'Confirmar eliminación' : 'Cancelar');
                    if (willDelete) {
                        onConfirm();
                    } else {
                        onCancel();
                    }
                });
            } else {
                console.log('⚠️ SweetAlert no disponible, usando confirm nativo');
                if (confirm(title + '\n\n' + message)) {
                    onConfirm();
                } else {
                    onCancel();
                }
            }
        }
        
        // Función para realizar la eliminación
        function performDelete(url, deleteButton) {
            console.log('🚀 === INICIANDO ELIMINACIÓN ===');
            console.log('🌐 URL:', url);
            console.log('🔘 Botón:', deleteButton);
            
            var dl = Object.assign({}, window.deleteLang || {}, window.fvUiLang || {});
            deleteButton.prop('disabled', true);
            var originalHtml = deleteButton.html();
            var delProg = ((window.fvUiText && window.fvUiText('delete_progress')) || dl.delete_progress || '').trim();
            deleteButton.html('<span class="spinner-border spinner-border-sm" role="status"></span>' + (delProg ? (' ' + delProg) : ''));
            console.log('🔒 Botón deshabilitado y mostrando spinner');
            
            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                timeout: 30000,
                beforeSend: function() {
                    console.log('📡 Enviando petición AJAX de eliminación...');
                },
                success: function(response) {
                    console.log('✅ === ELIMINACIÓN EXITOSA ===');
                    console.log('📥 Respuesta del servidor:', response);
                    
                    var message = response.message || response.msg
                        || (window.fvUiText && window.fvUiText('success_deleted'))
                        || dl.success_deleted
                        || '';
                    console.log('💬 Mensaje a mostrar:', message);
                    
                    var successTitle = ((window.fvUiText && window.fvUiText('delete_success_alert_title')) || dl.delete_success_alert_title || dl.success_message || '').trim();
                    if (!successTitle) {
                        successTitle = ((window.fvUiText && window.fvUiText('success_deleted')) || dl.success_deleted || message || '').trim();
                    }
                    showAlert(successTitle, message, 'success');
                    
                    // Recargar la página después de un breve delay
                    setTimeout(function() {
                        console.log('🔄 Recargando página...');
                        location.reload();
                    }, 1500);
                },
                error: function(xhr, status, error) {
                    console.error('❌ === ERROR EN ELIMINACIÓN ===');
                    console.error('📊 Status:', xhr.status);
                    console.error('📝 Status Text:', xhr.statusText);
                    console.error('🔴 Error:', error);
                    console.error('📄 Response Text:', xhr.responseText);
                    console.error('🌐 URL:', url);
                    
                    // Restaurar botón
                    deleteButton.prop('disabled', false);
                    deleteButton.html(originalHtml);
                    console.log('🔓 Botón rehabilitado');
                    
                    var errorMessage = dl.error_server_message || dl.error_occurred || '';
                    try {
                        if (xhr.responseText) {
                            var responseData = JSON.parse(xhr.responseText);
                            errorMessage = responseData.msg || responseData.message || errorMessage;
                            console.log('📋 Mensaje de error parseado:', errorMessage);
                        }
                    } catch (e) {
                        console.error('⚠️ Error al parsear respuesta:', e);
                        if (xhr.responseText && xhr.responseText.length > 0) {
                            // Intentar extraer mensaje de error del HTML si es posible
                            var textMatch = xhr.responseText.match(/<title>(.*?)<\/title>/i);
                            if (textMatch) {
                                errorMessage = textMatch[1];
                            } else {
                                errorMessage = (dl.error_server || dl.error_occurred || (window.fvUiText && window.fvUiText('error_server')) || (window.fvUiText && window.fvUiText('error_occurred')) || '') + ': ' + xhr.status;
                            }
                        }
                    }
                    
                    showAlert(dl.error_occurred || (window.fvUiText && window.fvUiText('error_occurred')) || '', errorMessage, 'error');
                }
            });
        }
    });
})();
</script>
<?php endif; ?>
<script>
// Asegurar que jQuery esté cargado antes de ejecutar
(function() {
    // Usar CoreLoader si está disponible
    var waitForJQuery = (typeof CoreLoader !== 'undefined' && CoreLoader.waitForJQuery) 
        ? CoreLoader.waitForJQuery 
        : function(callback) {
            if (typeof jQuery !== 'undefined') {
                callback();
            } else {
                setTimeout(function() { waitForJQuery(callback); }, 100);
            }
        };
    
    waitForJQuery(function() {
        // Verificar nuevamente que jQuery esté disponible
        if (typeof jQuery === 'undefined') {
            console.warn('jQuery no está disponible, saltando inicialización del loader');
            return;
        }
        
        var $ = jQuery;
        
        $(document).ready(function() {
            var loader = $('#products-loader');
            var loaderMsg = (typeof window.listLoaderMessage === 'string' && window.listLoaderMessage)
                ? window.listLoaderMessage
                : '';

            function showListLoader() {
                if (!loader.length) {
                    return;
                }
                loader.attr('aria-busy', 'true').removeClass('hiding').addClass('show');
                var $p = loader.find('p');
                if ($p.length && loaderMsg) {
                    $p.text(loaderMsg);
                }
                try {
                    if (typeof AppLoader !== 'undefined' && AppLoader && typeof AppLoader.show === 'function') {
                        AppLoader.show(loaderMsg || undefined);
                    }
                } catch (e) {}
            }

            function hideListLoader() {
                if (!loader.length) {
                    return;
                }
                loader.attr('aria-busy', 'false').removeClass('show hiding');
                try {
                    if (typeof AppLoader !== 'undefined' && AppLoader && typeof AppLoader.hide === 'function') {
                        AppLoader.hide();
                    }
                } catch (e2) {}
            }
            
            // No mostrar loader en carga inicial (los datos ya vienen en el HTML). Solo mostrarlo en paginación/filtros.
            if (loader.length) {
                hideListLoader();
                
                // Manejar loader durante paginación, ordenación y filtros
                $(document).on('click', '#list .pagination a, .fv-products-list-pagination .pagination a, #list .sort, #form-buscar-filtro button[type="submit"]', function() {
                    showListLoader();
                });
                $(document).on('submit', '#form-buscar-filtro', function() {
                    showListLoader();
                });
            }
            
            // Checkbox "All": seleccionar/deseleccionar todas las filas (delegación para que funcione siempre)
            $(document).on('change', '#ckbCheckAll', function() {
                var checked = $(this).prop('checked');
                $('#infoTable tbody input.checkBoxClass').prop('checked', checked);
                var n = checked ? $('#infoTable tbody input.checkBoxClass').length : 0;
                updateContextualActionsState(n);
            });
            $(document).on('change', '#infoTable tbody input.checkBoxClass', function() {
                var total = $('#infoTable tbody input.checkBoxClass').length;
                var checked = $('#infoTable tbody input.checkBoxClass:checked').length;
                $('#ckbCheckAll').prop('checked', total > 0 && total === checked);
                updateContextualActionsState(checked);
            });

            function updateContextualActionsState(selectedCount) {
                var $btns = $('#contextual-actions .contextual-action-btn');
                if ($btns.length === 0) return;
                selectedCount = selectedCount || 0;
                var isOrdersList = $btns.length >= 4 && (/Duplicate|Duplicar/i).test($btns.eq(1).text());
                if (isOrdersList) {
                    $btns.eq(0).each(function() { setContextualEnabled(this, selectedCount >= 1); });
                    $btns.eq(1).each(function() { setContextualEnabled(this, selectedCount >= 1); });
                    $btns.eq(2).each(function() { setContextualEnabled(this, selectedCount >= 2); });
                    $btns.eq(3).each(function() { setContextualEnabled(this, selectedCount >= 1); });
                } else {
                    $btns.each(function() { setContextualEnabled(this, selectedCount >= 1); });
                }
            }
            function setContextualEnabled(el, enabled) {
                var $el = $(el);
                if (enabled) {
                    $el.removeClass('btn-secondary btn-primary btn-danger disabled fv-bulk-idle').addClass('btn-success');
                    $el.attr('aria-disabled', 'false');
                } else {
                    $el.removeClass('btn-success btn-primary btn-danger').addClass('btn-secondary disabled fv-bulk-idle');
                    $el.attr('aria-disabled', 'true');
                }
                if (el.tagName === 'BUTTON') {
                    $el.prop('disabled', !enabled);
                } else if (enabled) {
                    $el.css('pointer-events', '');
                } else {
                    $el.css('pointer-events', 'none');
                }
            }
            $(document).on('click', '#contextual-actions a.contextual-action-btn.disabled', function(e) {
                e.preventDefault();
                return false;
            });
            updateContextualActionsState(0);

            // Botón eliminar con data-delete-url (siempre activo para todas las listas: groups, etc.)
            $(document).on('click', 'button[data-delete-url]:not(.go-delete)', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    var deleteButton = $(this);
                    var url = deleteButton.data('delete-url');
                    if (!url) {
                        var errMsg = (window.deleteLang && window.deleteLang.error_no_url_message) || (window.fvUiText && window.fvUiText('error_no_url_message')) || '';
                        if (typeof swal !== 'undefined') {
                            swal({ title: (window.deleteLang && window.deleteLang.error_no_url) || (window.fvUiText && window.fvUiText('error_no_url')) || '', text: errMsg, icon: 'error' });
                        } else {
                            alert(errMsg);
                        }
                        return false;
                    }
                    var toltipLang = window.deleteLang || {};
                    // Alerta genérica sin título, solo texto
                    var confirmText = toltipLang.desea_eliminar_registro || toltipLang.esta_seguro || (window.fvUiText && window.fvUiText('desea_eliminar_registro')) || (window.fvUiText && window.fvUiText('esta_seguro')) || '';
                    var btnCancel = toltipLang.cancelar || (window.fvUiText && window.fvUiText('cancelar')) || '';
                    var btnConfirm = toltipLang.eliminar || (window.fvUiText && window.fvUiText('eliminar')) || '';
                    var originalHtml = deleteButton.html();
                    function doDelete() {
                        var dlx = window.deleteLang || {};
                        var delProg2 = (dlx.delete_progress || '').trim();
                        deleteButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status"></span>' + (delProg2 ? (' ' + delProg2) : ''));
                        $.ajax({
                            url: url,
                            type: 'GET',
                            dataType: 'json',
                            timeout: 30000,
                            success: function(response) {
                                var msg = response.message || response.msg || dlx.success_deleted || '';
                                var titleOk = (dlx.delete_success_alert_title || dlx.success_message || '').trim();
                                if (!titleOk) {
                                    titleOk = (dlx.success_deleted || msg || '').trim();
                                }
                                if (typeof swal !== 'undefined') {
                                    swal({ title: titleOk, text: msg, icon: 'success' }).then(function() { location.reload(); });
                                } else {
                                    alert((titleOk ? (titleOk + '\n\n') : '') + msg);
                                    location.reload();
                                }
                            },
                            error: function(xhr) {
                                deleteButton.prop('disabled', false).html(originalHtml);
                                var errMsg = dlx.error_server_message || dlx.error_occurred || '';
                                try {
                                    if (xhr.responseText) {
                                        var d = JSON.parse(xhr.responseText);
                                        errMsg = d.msg || d.message || errMsg;
                                    }
                                } catch (e) {}
                                if (typeof swal !== 'undefined') {
                                    swal({ title: dlx.error_occurred || (window.fvUiText && window.fvUiText('error_occurred')) || '', text: errMsg, icon: 'error' });
                                } else {
                                    alert(errMsg);
                                }
                            }
                        });
                    }
                    if (typeof swal !== 'undefined' && typeof swal === 'function') {
                        swal({
                            title: '',
                            text: confirmText,
                            icon: 'warning',
                            buttons: { cancel: { text: btnCancel, value: false, visible: true, className: 'sweet-danager', closeModal: true }, confirm: { text: btnConfirm, value: true, visible: true, className: 'sweet-warning', closeModal: true } }
                        }).then(function(willDelete) {
                            if (willDelete) { doDelete(); }
                        });
                    } else {
                        if (confirm(confirmText)) { doDelete(); }
                    }
                    return false;
                });
        });
    });
})();
</script>

<!-- Scripts para el modal de enviar catálogo -->
<script>
// Definir companyUrl si no está definido (para filter_bygroup.js)
if (typeof companyUrl === 'undefined') {
    var companyUrl = '<?= base_url("company") ?>';
}

// Definir options.url si no está definido (pdfmake removido)
if (typeof options === 'undefined') {
    var options = {};
}
if (typeof options.url === 'undefined') {
    options.url = companyUrl || '<?= base_url("company") ?>';
}
</script>
<script src="<?= base_url('companyasset/company/catalogo/open_modalCatalog.js') ?>"></script>
<script src="<?= base_url('companyasset/company/catalogo/filter_bygroup.js') ?>"></script>
<!-- PDF download: catalog-list-download.js (preview + Exportar + loader) -->

<!-- Script para descargar catálogo con pdfMake - REMOVIDO -->
<!-- <script>
// Cargar pdfmake_table.js después de que pdfMake esté disponible
(function() {
    function loadPdfMakeTableScript() {
        if (typeof window.pdfMake !== 'undefined' && window.pdfMake !== null && typeof window.pdfMake.createPdf === 'function') {
            // pdfMake está disponible, cargar el script
            var script = document.createElement('script');
            script.src = '<?= base_url('companyasset/company/catalog/pdfmake_table.js') ?>';
            script.onerror = function() {
                console.error('Error al cargar pdfmake_table.js');
            };
            document.head.appendChild(script);
        } else {
            // Esperar un poco más
            setTimeout(loadPdfMakeTableScript, 100);
        }
    }
    
    // Iniciar la carga después de un pequeño delay para asegurar que el footer se haya procesado
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(loadPdfMakeTableScript, 500);
        });
    } else {
        setTimeout(loadPdfMakeTableScript, 500);
    }
})();
</script> -->

<!-- Console.log del company_id -->
<script>
(function() {
    // Usar CoreLoader si está disponible
    var waitForJQuery = (typeof CoreLoader !== 'undefined' && CoreLoader.waitForJQuery) 
        ? CoreLoader.waitForJQuery 
        : function(callback) {
            if (typeof jQuery !== 'undefined') {
                callback();
            } else {
                setTimeout(function() { waitForJQuery(callback); }, 100);
            }
        };
    
    waitForJQuery(function() {
        var companyId = <?= json_encode((int) ($company_id ?? 0)) ?>;
        if (typeof window.options === 'object' && window.options !== null && !window.options.company_id && companyId > 0) {
            window.options.company_id = companyId;
        }
        
        // Manejador para enlaces de nombre de cliente - redirige en la misma ventana
        // Manejador inmediato para enlaces de nombre - se ejecuta antes que jQuery ready
        (function() {
            document.addEventListener('click', function(e) {
                var target = e.target;
                // Buscar el enlace del nombre o su padre
                while (target && target !== document.body) {
                    if (target.classList && target.classList.contains('customer-name-link')) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        e.stopPropagation();
                        var url = target.getAttribute('data-edit-url');
                        if (url) {
                            // Redirigir en la misma ventana en lugar de abrir nueva
                            window.location.href = url;
                        }
                        return false;
                    }
                    target = target.parentElement;
                }
            }, true); // Usar capture phase para máxima prioridad
        })();
        
        // Manejador con jQuery para compatibilidad
        var $ = jQuery;
        $(document).on('click', '.customer-name-link', function(e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            e.stopPropagation();
            var url = $(this).data('edit-url') || $(this).attr('data-edit-url');
            if (url) {
                // Redirigir en la misma ventana en lugar de abrir nueva
                window.location.href = url;
            }
            return false;
        });
    });
})();

// Abrir modal de formatos automáticamente si se accede desde /company/catalog/formats
(function() {
    // Usar CoreLoader si está disponible
    var waitForJQuery = (typeof CoreLoader !== 'undefined' && CoreLoader.waitForJQuery) 
        ? CoreLoader.waitForJQuery 
        : function(callback) {
            if (typeof jQuery !== 'undefined') {
                callback();
            } else {
                setTimeout(function() { waitForJQuery(callback); }, 100);
            }
        };
    
    waitForJQuery(function() {
        $(document).ready(function() {
            // Verificar si se debe abrir el modal de formatos
            var urlParams = new URLSearchParams(window.location.search);
            var shouldOpenFormatsModal = urlParams.get('open_formats_modal') === 'true' || 
                                        window.location.pathname.indexOf('/catalog/formats') !== -1;
            
            if (shouldOpenFormatsModal) {
                // Esperar a que el modal esté cargado en el DOM
                setTimeout(function() {
                    var $formatsModal = $('#formatsModal');
                    if ($formatsModal.length) {
                        $formatsModal.modal('show');
                        // Limpiar el parámetro de la URL sin recargar la página
                        if (window.history && window.history.replaceState) {
                            var newUrl = window.location.pathname;
                            window.history.replaceState({}, document.title, newUrl);
                        }
                    } else {
                        console.warn('Modal #formatsModal no encontrado en el DOM');
                    }
                }, 500);
            }

            $(document).on('show.bs.modal', '#modal_catalogorder', function (e) {
                if (window.ordersPlanLimitBlock && window.ordersPlanLimitBlock.can_create === false) {
                    e.preventDefault();
                    var msg = window.ordersPlanLimitBlock.message || '';
                    if (typeof swal === 'function') {
                        swal({ title: msg, icon: 'warning' });
                    } else if (msg) {
                        alert(msg);
                    }
                }
            });
        });
    });
})();
</script>
<?php if (!empty($useConfigCrudDatagrid)) : ?>
    <?php $this->load->view('components/crud/config_crud_datagrid_init', [
        'langDatatable' => $langDatatable ?? [],
        'configCrudInlineEdit' => $configCrudInlineEdit ?? [],
        'configCrudBooleanSwitch' => $configCrudBooleanSwitch ?? [],
        'configCrudStatusSwitch' => $configCrudStatusSwitch ?? [],
        'changeStatus' => $changeStatus ?? false,
    ]); ?>
<?php endif; ?>

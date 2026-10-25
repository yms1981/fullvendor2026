<?php
/**
 * Vista personalizada con tabs para filtrar usuarios por rol
 * Esta vista extiende la vista estándar del CRUD agregando tabs para filtrar por tipo de rol
 */
$useConfigCrudDatagrid = !empty($useConfigCrudDatagrid);
?>
<style>
/* Estilo para hacer la columna de la bandera más pequeña y pegada al checkbox */
.country-flag-column {
    width: 30px !important;
    min-width: 30px !important;
    max-width: 30px !important;
    padding: 8px 4px !important;
    text-align: center;
}

/* Estilo para reducir el tamaño de la columna ID */
.id-column {
    width: 60px !important;
    min-width: 60px !important;
    max-width: 80px !important;
    padding: 0.75rem 0.5rem !important;
    text-align: center !important;
}

/* Columna avatar (listado usuarios empresa) */
.user-list-avatar-column {
    width: 68px !important;
    min-width: 68px !important;
    max-width: 76px !important;
    padding: 0.5rem 0.35rem !important;
    text-align: center !important;
    vertical-align: middle !important;
}
.user-list-avatar-btn {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    margin: 0 auto;
    border: 0;
    background: transparent;
    cursor: pointer;
    border-radius: 50%;
}
.user-list-avatar-btn:disabled {
    cursor: default;
}
.user-list-avatar-btn:not(:disabled):hover .user-list-avatar-inner,
.user-list-avatar-btn:not(:disabled):focus .user-list-avatar-inner {
    box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.28);
    border-radius: 50%;
}
.user-list-avatar-edit {
    position: absolute;
    right: -2px;
    bottom: -2px;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    background: #dc3545;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    line-height: 1;
    border: 2px solid #fff;
    box-shadow: 0 1px 4px rgba(0,0,0,.2);
    pointer-events: none;
}
.fv-user-avatar-preview {
    width: 96px;
    height: 96px;
}
.fv-user-avatar-preview img,
.fv-user-avatar-preview span {
    width: 96px;
    height: 96px;
    object-fit: cover;
}
.fv-user-avatar-preview span {
    font-size: 28px;
    background: #6c757d;
}

/* Estilo para centrar la imagen de productos en su columna */
.product-image-column {
    text-align: center !important;
    vertical-align: middle !important;
}

.product-image-column td,
.product-image-column th {
    text-align: center !important;
    vertical-align: middle !important;
}

/* Centrar imágenes de productos en las celdas */
table td:has(figure[data-defeto]),
table th:has(figure[data-defeto]) {
    text-align: center !important;
    vertical-align: middle !important;
}

/* Alternativa más específica para la columna de imagen de productos */
td[data-field="productimage"],
th[data-field="productimage"] {
    text-align: center !important;
    vertical-align: middle !important;
}

.products-loader-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(250, 250, 250, 0.98) 100%);
    z-index: 1000;
    display: none;
    align-items: center;
    justify-content: center;
    border-radius: 0.375rem;
    backdrop-filter: blur(2px);
    -webkit-backdrop-filter: blur(2px);
    transition: opacity 0.3s ease-in-out;
}
.products-loader-overlay.show {
    display: flex !important;
    opacity: 1;
}
.products-loader-overlay.hiding {
    opacity: 0;
}
.products-loader-content {
    text-align: center;
    padding: 1.5rem;
}
.table-loader {
    width: 50px;
    height: 50px;
    position: relative;
    margin: 0 auto;
}
.table-loader-spinner {
    width: 100%;
    height: 100%;
    border: 3px solid rgba(220, 53, 69, 0.1);
    border-top-color: #dc3545;
    border-right-color: #dc3545;
    border-radius: 50%;
    animation: table-loader-spin 0.8s linear infinite;
}
.table-loader-spinner::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 70%;
    height: 70%;
    border: 2px solid rgba(0, 0, 0, 0.1);
    border-bottom-color: #000;
    border-left-color: #000;
    border-radius: 50%;
    animation: table-loader-spin-reverse 1s linear infinite;
}
@keyframes table-loader-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
@keyframes table-loader-spin-reverse {
    0% { transform: translate(-50%, -50%) rotate(0deg); }
    100% { transform: translate(-50%, -50%) rotate(-360deg); }
}

/* Estilos de grilla para header y footer */
.fuente_16px {
    font-size: 16px !important;
}
.table thead {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
    color: white !important;
}
.table thead th {
    color: white !important;
    border: none !important;
    font-weight: 600 !important;
    vertical-align: middle !important;
}
.table tbody tr.disabled {
    opacity: 0.6;
}
.table tbody tr.fondo-default {
    background-color: #e3f2fd;
}

.fv-user-role-badges {
    display: inline-flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    align-items: center;
}

.fv-user-role-badge {
    font-size: 0.72rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    padding: 0.35em 0.65em;
    border-radius: 999px;
    text-transform: uppercase;
}

.fv-user-role-badge--manager {
    background: #dbeafe;
    color: #1d4ed8;
}

.fv-user-role-badge--customer {
    background: #ede9fe;
    color: #6d28d9;
}

.fv-user-role-badge--seller {
    background: #dcfce7;
    color: #15803d;
}

.fv-user-role-badge--warehouse {
    background: #ffedd5;
    color: #c2410c;
}

.fv-user-role-badge--default {
    background: #f3f4f6;
    color: #374151;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}
.card-body {
    padding: 1.5rem;
}
.table-responsive {
    display: block;
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.table {
    width: 100%;
    margin-bottom: 1rem;
    color: #212529;
}
.table td, .table th {
    padding: 0.75rem;
    vertical-align: top;
    border-top: 1px solid #dee2e6;
}
/* Estilos para el footer de paginación */
.paginate {
    padding: 1rem 0;
    margin-top: 1rem;
    border-top: 1px solid #dee2e6;
}
.paginate .showing {
    font-size: 0.875rem;
    color: #6c757d;
}
.paginate .pagination {
    margin-bottom: 0;
}

/* Estilos mejorados para las pestañas con colores llamativos */
.nav-tabs.users-tabs {
    border-bottom: 3px solid #e9ecef;
    margin-bottom: 1.5rem;
}

.nav-tabs.users-tabs .nav-item {
    margin-right: 0.5rem;
}

.nav-tabs.users-tabs .nav-link {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    color: #495057;
    border: 2px solid #dee2e6;
    border-radius: 8px 8px 0 0;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.nav-tabs.users-tabs .nav-link i {
    font-size: 1.2rem;
}

.nav-tabs.users-tabs .nav-link:hover {
    background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
    border-color: #adb5bd;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.nav-tabs.users-tabs .nav-link.active {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    border-color: #dc3545;
    border-bottom-color: white;
    position: relative;
    z-index: 1;
    box-shadow: 0 -4px 8px rgba(220, 53, 69, 0.3);
}

.nav-tabs.users-tabs .nav-link.active i {
    color: white;
}

/* Colores específicos para cada pestaña */
.nav-tabs.users-tabs .nav-link[href*="type=manager"].active {
    background: linear-gradient(135deg, #6f42c1 0%, #5a32a3 100%);
    border-color: #6f42c1;
    box-shadow: 0 -4px 8px rgba(111, 66, 193, 0.3);
}

.nav-tabs.users-tabs .nav-link[href*="type=customer"].active {
    background: linear-gradient(135deg, #28a745 0%, #218838 100%);
    border-color: #28a745;
    box-shadow: 0 -4px 8px rgba(40, 167, 69, 0.3);
}

.nav-tabs.users-tabs .nav-link[href*="type=seller"].active {
    background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
    border-color: #ffc107;
    color: #212529;
    box-shadow: 0 -4px 8px rgba(255, 193, 7, 0.3);
}

.nav-tabs.users-tabs .nav-link[href*="type=seller"].active i {
    color: #212529;
}

.nav-tabs.users-tabs .nav-link[href*="type=warehouse"].active {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
    border-color: #17a2b8;
    box-shadow: 0 -4px 8px rgba(23, 162, 184, 0.3);
}

/* Estilos para el formulario de filtros */
.users-filter-form {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    border: 1px solid #dee2e6;
}

.users-filter-form .form-group label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 0.5rem;
}

.users-filter-form .btn-action {
    margin-left: 0.5rem;
}

/* Estilos para el buscador */
.search-input-group {
    position: relative;
}

.search-input-group i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #6c757d;
    z-index: 10;
}

.search-input-group input {
    padding-left: 40px;
}
</style>
<div class="container-fluid page-body-wrapper">
    <div class="main-panel">
        <div class="container-fluid pt-3">
            <div class="row">
                <div class="col-md-12">
                    <div class="page-header">
                        <h3 class="page-title"><?= htmlspecialchars((string) ($pageTitle ?? ($pageTexts['pageTitle'] ?? ($pageTexts['list_users'] ?? ''))), ENT_QUOTES, 'UTF-8') ?></h3>
                    </div>
                    <?php
                    $planLimitBlock = isset($planLimitBlock) && is_array($planLimitBlock) ? $planLimitBlock : ['can_create' => true];
                    $planLimitBlocked = empty($planLimitBlock['can_create']);
                    $this->load->view('company/plan_limit_notice', ['planLimitBlock' => $planLimitBlock]);
                    ?>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <!-- Tabs para filtrar por tipo de rol -->
                    <ul class="nav nav-tabs users-tabs mb-3" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link <?= ($currentType === 'manager') ? 'active' : '' ?>" 
                               href="<?= company_url('users?type=manager' . (isset($_GET['status']) ? '&status=' . $_GET['status'] : '') . (isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '')) ?>">
                                <i class="mdi mdi-account-supervisor"></i>
                                <?= $tabs['manager'] ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= ($currentType === 'customer') ? 'active' : '' ?>" 
                               href="<?= company_url('users?type=customer' . (isset($_GET['status']) ? '&status=' . $_GET['status'] : '') . (isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '')) ?>">
                                <i class="mdi mdi-account"></i>
                                <?= $tabs['customer'] ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= ($currentType === 'seller') ? 'active' : '' ?>" 
                               href="<?= company_url('users?type=seller' . (isset($_GET['status']) ? '&status=' . $_GET['status'] : '') . (isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '')) ?>">
                                <i class="mdi mdi-account-tie"></i>
                                <?= $tabs['seller'] ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= ($currentType === 'warehouse') ? 'active' : '' ?>" 
                               href="<?= company_url('users?type=warehouse' . (isset($_GET['status']) ? '&status=' . $_GET['status'] : '') . (isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '')) ?>">
                                <i class="mdi mdi-warehouse"></i>
                                <?= $tabs['warehouse'] ?>
                            </a>
                        </li>
                    </ul>
                    
                    <!-- Formulario de filtros y acciones -->
                    <div class="users-filter-form">
                        <form id="form-buscar-filtro" method="get" action="<?= company_url('users') ?>">
                            <input type="hidden" name="type" value="<?= $currentType ?>">
                            <div class="row align-items-end">
                                <!-- Buscador -->
                                <div class="col-lg-4 col-md-6 col-sm-12 mb-3">
                                    <div class="form-group mb-0">
                                        <label><?= $pageTexts['search'] ?></label>
                                        <div class="search-input-group">
                                            <i class="mdi mdi-magnify"></i>
                                            <input type="text" 
                                                   class="form-control" 
                                                   name="search" 
                                                   id="search-input"
                                                   placeholder="<?= $pageTexts['search_placeholder'] ?>"
                                                   value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Filtro de estado -->
                                <div class="col-lg-3 col-md-6 col-sm-12 mb-3">
                                    <div class="form-group mb-0">
                                        <label><?= $pageTexts['filter_status'] ?></label>
                                        <select class="form-control" 
                                                name="status" 
                                                id="status-filter"
                                                onchange="this.form.submit()">
                                            <option value="all" <?= (!isset($_GET['status']) || $_GET['status'] === 'all') ? 'selected' : '' ?>>
                                                <?= strtoupper($pageTexts['all']) ?>
                                            </option>
                                            <option value="1" <?= (isset($_GET['status']) && $_GET['status'] === '1') ? 'selected' : '' ?>>
                                                <?= strtoupper($pageTexts['active']) ?>
                                            </option>
                                            <option value="0" <?= (isset($_GET['status']) && $_GET['status'] === '0') ? 'selected' : '' ?>>
                                                <?= strtoupper($pageTexts['inactive']) ?>
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Botones de acción -->
                                <div class="col-lg-5 col-md-12 col-sm-12 mb-3">
                                    <div class="form-group mb-0 d-flex align-items-end gap-2">
                                        <button type="submit" 
                                                class="btn btn-primary btn-action">
                                            <i class="mdi mdi-filter"></i>
                                            <?= $pageTexts['search'] ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <div class="boxsrch">
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group fv-config-crud-toolbar<?= !empty($useConfigCrudDatagrid) ? ' fv-config-crud-toolbar--page' : '' ?>" id="module-actions" style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
                                    <?php $this->load->view('components/crud/button-bar', [
                                        'buttons' => $buttonBar,
                                        'defaultButtonClass' => 'btn-success',
                                        'defaultDisabled' => false,
                                        'configCrudToolbar' => !empty($useConfigCrudDatagrid),
                                    ]) ?>
                                    <!-- Botones contextuales en la misma fila -->
                                    <div id="contextual-actions" style="display: inline-block;">
                                        <?php $this->load->view('components/crud/button-bar',['buttons'=>$contextualButtonBar,'defaultButtonClass'=>'btn-secondary','defaultDisabled'=>true]) ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 mt-2">
                                <div id="list-info" style="text-align: right; display: flex; justify-content: flex-end; align-items: center; gap: 1rem; flex-wrap: wrap;">
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
                            <?php
                            if (!empty($formFilterView) && empty($hideServerFilter)) { ?>
                                <div class="col-md-12">
                                    <div class="row">
                                        <div class="col-12 p-4">
                                            <div class="row border">
                                                <?php
                                                $this->load->view($formFilterView) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php
                            } ?>
                        </div>
                    </div>
                    <div class="row" id="contextual-actions-container">
                            <?php
                            if ($selectionExpansible) {
                                $todos = isset($filterData['input_todos']) && $filterData['input_todos'] === 'todos';
                                ?>
                        <div class="col-12">
                                <div id="expand-selection" class="alert alert-warning <?php

                                if (isset($filterData['orderDirection'])) {
                                    echo "show";
                                }
                                ?>" role="alert"<?= ($todos) ? '' : 'style="display: none;"'
                                ?>>
                                    <strong id="expand-selection-info" data-total-filtered-items="<?= $totalItemsCount ?>"><?= htmlspecialchars($pageTexts['expandSelectionInfo'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
                                    <a id="link-todos"
                                       href="javascript:void(0)"><?= ($todos) ? htmlspecialchars($pageTexts['desmarcar_todos'] ?? '', ENT_QUOTES, 'UTF-8') : htmlspecialchars($pageTexts['marcar_todos'] ?? '', ENT_QUOTES, 'UTF-8') ?></a>
                                </div>
                        </div>
                                <?php
                            }
                            ?>
                        <div class="col-md-10">
                            <div class="form-group">
                                <div id="expansible-info"></div>
                            </div>

                        </div>

                    </div>
                    <div class="row">
                        <div class="col-12 position-relative<?= !empty($useConfigCrudDatagrid) ? ' fv-config-crud-datagrid-page' : '' ?>">
                            <?php if (empty($useConfigCrudDatagrid)) : ?>
                            <!-- Loader overlay -->
                            <div id="products-loader" class="products-loader-overlay">
                                <div class="products-loader-content">
                                    <div class="table-loader">
                                        <div class="table-loader-spinner" role="status" aria-label="Cargando..."></div>
                                    </div>
                                    <p class="mt-3 mb-0 text-muted" style="font-size: 0.9rem;">Cargando datos...</p>
                                </div>
                            </div>
                            <?php endif; ?>
                            <div class="fv-modern-grid fv-config-crud-datagrid-wrap<?= !empty($useConfigCrudDatagrid) ? ' fv-config-crud-datagrid-wrap--active' : '' ?>" id="list">
                                <div class="fv-modern-grid__shell">
                                <div class="fv-modern-grid__scroll">
                                <table id="infoTable" class="table fv-modern-grid__table w-100 fv-config-crud-datagrid">
                                    <thead>
                                    <?php if (empty($hideServerPagination)) {
                                        $this->load->view('components/crud/pagination');
                                    } ?>
                                    <tr>
                                        <?php
                                        if ($multipleSelection): 
                                            $todos = isset($filterData['input_todos']) && $filterData['input_todos'] === 'todos';
                                            $selectAllLabel = (string) ($pageTexts['columnAll'] ?? $pageTexts['todo'] ?? 'Select all');
                                        ?>
                                            <th class="fuente_16px fv-crud-select-all-column" scope="col" aria-label="<?= htmlspecialchars($selectAllLabel, ENT_QUOTES, 'UTF-8') ?>">
                                                <span class="d-flex justify-content-center">
                                                    <input
                                                        title="<?= ($todos) ? $pageTexts['desmarcar_todos'] : $pageTexts['toltip_paginas'] ?>"
                                                        type="checkbox" <?= ($todos) ? 'checked' : '' ?> name=""
                                                        id="ckbCheckAll"
                                                        aria-label="<?= htmlspecialchars($selectAllLabel, ENT_QUOTES, 'UTF-8') ?>">
                                                </span>
                                            </th>
                                        <?php
                                        endif; ?>
                                        <?php
                                        foreach ($fields as $field): ?>
                                            <th class="fuente_16px <?= $field === 'country_flag' ? 'country-flag-column' : '' ?> <?= $field === 'productimage' ? 'product-image-column' : '' ?>" data-field="<?= $field ?>">
                                                <?php
                                                if (!$useConfigCrudDatagrid && in_array($field, $sortableFields)): ?>
                                                    <?php
                                                    $fdSort = $filterData['sort'] ?? null;
                                                    $fdOrder = $filterData['orderDirection'] ?? 'asc';
                                                    $orderType = "order_type_$field";
                                                    $toggleDir = ($fdSort == $field) ? (($fdOrder == 'asc') ? 'desc' : 'asc') : 'asc';
                                                    ?>
                                                    <a data-sort="<?= $field ?>"
                                                       data-order_type="<?= $orderType ?>"
                                                       data-sort_direction="<?= $toggleDir ?>"
                                                       class="sort">
                                                        <u><?= $pageTexts['fields'][$field] ?? $field ?> </u> <?= ($fdSort == $field) ? ($toggleDir == "asc" ? '⌄' : '⌃') : '' ?>
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
                                            <th class="fuente_16px"><?= $pageTexts['colmn_status'] ?></th>
                                        <?php endif; ?>

                                        <?php if (!empty($itemActions)): ?>
                                        <th class="fuente_16px text-center crud-list-actions-column fv-crud-actions-column fv-dt-no-sort" scope="col"><?= htmlspecialchars((string) ($pageTexts['colmn_action'] ?? 'Actions'), ENT_QUOTES, 'UTF-8') ?></th>
                                        <?php endif; ?>
                                    </tr>
                                    </thead>
                                    <tbody <?php
                                    if (isset($listBodyClass)) echo "class='{$listBodyClass}'" ?>>
                                    <?php
                                    foreach (@$list as $key => $listItem):
                                        $rowStatusValue = $listItem->$itemStatusField ?? null;
                                        if ($itemStatusField === 'deleted_at') {
                                            $rowIsDisabled = ($rowStatusValue !== null && $rowStatusValue !== '');
                                        } else {
                                            $rowIsDisabled = $rowStatusValue !== null && (int) $rowStatusValue === 0;
                                        }
                                        ?>
                                        <tr class="<?= $rowIsDisabled ? 'disabled' : '' ?>">
                                            <?php
                                            if ($multipleSelection): 
                                                $todos = isset($filterData['input_todos']) && $filterData['input_todos'] === 'todos';
                                            ?>
                                                <td>
                                                    <input type="checkbox"
                                                           name="<?= "{$pageTexts['entityName']}Ids[]" ?>" <?= ($todos) ? 'checked' : '' ?>
                                                           value="<?= @$listItem->$itemIdField ?>" class="checkBoxClass">
                                                </td>
                                            <?php
                                            endif; ?>
                                            <?php
                                            foreach ($fields as $field): ?>
                                                <td class="fuente_16px <?= $field === 'country_flag' ? 'country-flag-column' : '' ?> <?= $field === 'productimage' ? 'product-image-column' : '' ?> <?= $field === 'avatar' ? 'user-list-avatar-column' : '' ?> <?= ($field === 'pro_id' || $field === 'id') ? 'id-column' : '' ?>" data-field="<?= $field ?>">
                                                    <?php
                                                    if (isset($fieldTemplates[$field])):
                                                        // Para campos virtuales como country_flag, no intentar acceder a la propiedad
                                                        $fieldValue = '';
                                                        if ($field === 'country_flag') {
                                                            $fieldValue = ''; // Campo virtual, no existe en los datos
                                                        } elseif (isset($listItem->{$field})) {
                                                            $fieldValue = $listItem->{$field};
                                                        }
                                                        $templateData = array('listItem' => $listItem,'value'=>$fieldValue);
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
                                                        if (isset($currentType)) {
                                                            $templateData['currentType'] = $currentType;
                                                        }
                                                        $this->load->view(
                                                            $fieldTemplates[$field],
                                                            $templateData
                                                        );
                                                    else:
                                                        ?>
                                                        <?= isset($listItem->{$field}) ? $listItem->{$field} : '' ?>
                                                    <?php
                                                    endif; ?>
                                                </td>
                                            <?php
                                            endforeach; ?>

                                            <?php
                                            if ($changeStatus): ?>
                                                <td style="vertical-align: top;">
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
                                            <td class="text-center crud-list-actions-column fv-modern-grid__actions">
                                                <div class="fv-modern-grid__actions-inner fv-crud-row-actions">
                                                    <?php foreach ($itemActions as $actionName => $itemAction):
                                                        if(isset($actionButtonTemplates[$actionName])):
                                                            $this->load->view($actionButtonTemplates[$actionName], [
                                                                'listItem' => $listItem,
                                                                'itemAction' => $itemAction,
                                                                'itemIdField' => $itemIdField,
                                                            ]);
                                                        else:
                                                            $isDeleteButton = ($actionName === 'delete' || strpos($itemAction['buttonClass'] ?? '', 'go-delete') !== false || strpos($itemAction['buttonClass'] ?? '', 'btn-go-delete') !== false);
                                                            $rowBtnClass = (strpos($itemAction['buttonClass'] ?? '', 'btn-') === 0) ? $itemAction['buttonClass'] : 'btn-' . ($itemAction['buttonClass'] ?? 'primary');
                                                        ?>
                                                    <?php if ($isDeleteButton): ?>
                                                    <button type="button"
                                                            class="btn fv-crud-btn fv-crud-btn--row <?= $rowBtnClass ?> btn-sm go-delete"
                                                            title="<?= $itemAction['label'] ?>"
                                                            data-delete-url="<?= $itemAction['action'] ?><?= $listItem->$itemIdField ?>">
                                                        <i class="<?=$itemAction['iconClass']?>"></i>
                                                    </button>
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
                                <?php if (empty($hideServerPagination)) : ?>
                                <div class="fv-modern-grid__toolbar fv-modern-grid__toolbar--bottom">
                                <?php
                                    $this->load->view('components/crud/pagination');
                                ?>
                                </div>
                                <?php endif; ?>
                                </div>
                            </div>
                                <?php
                                if(!empty($modals))
                                    foreach ($modals as $modalsView){
                                        $modalData = [];
                                        if (isset($apiToken)) {
                                            $modalData['apiToken'] = $apiToken;
                                        }
                                        if (isset($pageTexts)) {
                                            $modalData['pageTexts'] = $pageTexts;
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

<?php
$this->load->view('company/users/_modal_add_user', [
    'pageTexts' => $pageTexts,
    'currentType' => $currentType,
    'company' => $company ?? null,
    'sellerProfiles' => $userModalSellerProfiles ?? [],
    'userModalCustomers' => $userModalCustomers ?? [],
    'userModalWarehouses' => $userModalWarehouses ?? [],
    'companyPhoneCountryMeta' => $companyPhoneCountryMeta ?? [],
    'companyUserCountryFlagsUrl' => $companyUserCountryFlagsUrl ?? '',
    'userTypes' => $userTypes ?? ['manager', 'seller', 'warehouse', 'customer'],
    'userModalCustomerLabels' => $userModalCustomerLabels ?? [],
    'userModalListReloadUrl' => $userModalListReloadUrl ?? company_url('users?type=' . rawurlencode($currentType ?? 'manager')),
    'planLimitBlock' => $planLimitBlock ?? ['can_create' => true],
]);
$this->load->view('company/users/_modal_edit_user', [
    'pageTexts' => $pageTexts ?? [],
]);
$this->load->view('company/users/_modal_avatar_upload', [
    'pageTexts' => $pageTexts ?? [],
]);
?>
<script>
window.FV_USER_AVATAR = <?= json_encode([
    'uploadUrl' => company_url('users/upload_image'),
    'updateUrlBase' => company_url('users/update'),
    'imageBaseUrl' => rtrim(company_url('image'), '/'),
    'lang' => [
        'avatar_change' => (string) ($pageTexts['avatar_change'] ?? ''),
        'avatar_modal_title' => (string) ($pageTexts['avatar_modal_title'] ?? ''),
        'avatar_select_file' => (string) ($pageTexts['avatar_select_file'] ?? ''),
        'avatar_save' => (string) ($pageTexts['avatar_save'] ?? ''),
        'avatar_cancel' => (string) ($pageTexts['avatar_cancel'] ?? ''),
        'avatar_hint' => (string) ($pageTexts['avatar_hint'] ?? ''),
        'avatar_updated' => (string) ($pageTexts['avatar_updated'] ?? ''),
        'avatar_uploading' => (string) ($pageTexts['avatar_uploading'] ?? ''),
        'user_error_upload' => (string) ($pageTexts['user_error_upload'] ?? ''),
        'user_error_save' => (string) ($pageTexts['user_error_save'] ?? ''),
        'user_error_generic' => (string) ($pageTexts['user_error_generic'] ?? ''),
        'ok' => (string) ($pageTexts['ok'] ?? 'OK'),
    ],
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) ?>;
</script>
<script src="<?= base_url('companyasset/company/users/user-avatar-upload.js?v=' . (defined('APP_VERSION') ? APP_VERSION : '1')) ?>"></script>

<?php
$fvUserSuccess = $this->session->flashdata('user_form_success_message');
$fvUserSuccessText = $this->session->flashdata('user_form_success_text');
$btnOkUsersList = htmlspecialchars((string) ($pageTexts['ok'] ?? 'OK'), ENT_QUOTES, 'UTF-8');
if (is_string($fvUserSuccess) && trim($fvUserSuccess) !== '') :
    ?>
<script>
(function () {
    var msg = <?= json_encode($fvUserSuccess, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    var detail = <?= json_encode((string) $fvUserSuccessText, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    var btnOk = <?= json_encode($btnOkUsersList, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    var attempts = 0;
    function showSuccess() {
        if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
            Swal.fire({
                icon: 'success',
                title: msg,
                text: detail || undefined,
                confirmButtonText: btnOk
            });
            return;
        }
        if (typeof swal === 'function') {
            swal({ title: msg, text: detail || undefined, icon: 'success' });
            return;
        }
        attempts++;
        if (attempts < 120) {
            setTimeout(showSuccess, 50);
            return;
        }
        window.alert(msg + (detail ? '\n' + detail : ''));
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', showSuccess);
    } else {
        showSuccess();
    }
})();
</script>
<?php endif; ?>

<script>
(function () {
    function showCreatedFromStorage() {
        var title = '';
        var text = '';
        try {
            title = sessionStorage.getItem('company_users_create_success_title') || '';
            text = sessionStorage.getItem('company_users_create_success_text') || '';
            sessionStorage.removeItem('company_users_create_success_title');
            sessionStorage.removeItem('company_users_create_success_text');
        } catch (e) {
            return;
        }
        if (!title) {
            return;
        }
        if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
            Swal.fire({
                icon: 'success',
                title: title,
                text: text || undefined,
                confirmButtonText: <?= json_encode((string) ($pageTexts['ok'] ?? 'OK'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
            });
            return;
        }
        if (typeof swal === 'function') {
            swal({ title: title, text: text || undefined, icon: 'success' });
            return;
        }
        window.alert(title + (text ? '\n' + text : ''));
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', showCreatedFromStorage);
    } else {
        showCreatedFromStorage();
    }
})();
</script>

<script>
// Asegurar que jQuery esté cargado antes de ejecutar
(function() {
    function waitForJQuery(callback, maxAttempts) {
        maxAttempts = maxAttempts || 50;
        var attempts = 0;
        
        function check() {
            attempts++;
            if (typeof jQuery !== 'undefined') {
                callback();
            } else if (attempts < maxAttempts) {
                setTimeout(check, 100);
            } else {
                console.error('jQuery not loaded after ' + (maxAttempts * 100) + 'ms');
            }
        }
        check();
    }
    
    waitForJQuery(function() {
        jQuery(document).ready(function($) {
            var loader = $('#products-loader');
            
            // Mostrar loader al cargar la página
            if (loader.length) {
                loader.addClass('show');
                
                // Ocultar loader cuando la página termine de cargar completamente
                $(window).on('load', function() {
                    loader.addClass('hiding');
                    setTimeout(function() {
                        loader.removeClass('show hiding');
                    }, 300);
                });
                
                // También ocultar loader después de un pequeño delay por si acaso
                setTimeout(function() {
                    if (loader.hasClass('show')) {
                        loader.addClass('hiding');
                        setTimeout(function() {
                            loader.removeClass('show hiding');
                        }, 300);
                    }
                }, 500);
                
                // Manejar loader durante paginación y filtros
                $(document).on('click', '.pagination a, .sort, button[type="submit"]', function() {
                    loader.removeClass('hiding').addClass('show');
                });
            }
            
            // Búsqueda con Enter
            $('#search-input').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $('#form-buscar-filtro').submit();
                }
            });
        });
    });
})();

// Función para imprimir usuarios
function printUsers() {
    window.print();
}

// Función para exportar usuarios
function exportUsers() {
    var form = document.getElementById('form-buscar-filtro');
    var formData = new FormData(form);
    formData.append('export', 'csv');
    
    // Crear un formulario temporal para la exportación
    var tempForm = document.createElement('form');
    tempForm.method = 'POST';
    tempForm.action = '<?= company_url('users/export') ?>';
    tempForm.style.display = 'none';
    
    // Agregar todos los campos del formulario
    for (var pair of formData.entries()) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = pair[0];
        input.value = pair[1];
        tempForm.appendChild(input);
    }
    
    document.body.appendChild(tempForm);
    tempForm.submit();
    document.body.removeChild(tempForm);
}
</script>
<?php if (!empty($useConfigCrudDatagrid)) : ?>
    <?php $this->load->view('components/crud/config_crud_datagrid_init', [
        'langDatatable' => $langDatatable ?? [],
        'configCrudStatusSwitch' => $configCrudStatusSwitch ?? [],
        'changeStatus' => $changeStatus ?? true,
        'configCrudInlineEdit' => $configCrudInlineEdit ?? [],
        'configCrudBooleanSwitch' => $configCrudBooleanSwitch ?? [],
    ]); ?>
<?php endif; ?>


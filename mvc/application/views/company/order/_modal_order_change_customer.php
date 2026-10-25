<?php
/**
 * Modal: seleccionar / cambiar cliente en crear-editar orden.
 *
 * @var array $pageTexts
 * @var array $orderFormConfig
 */
$pageTexts = is_array($pageTexts ?? null) ? $pageTexts : [];
$orderFormConfig = is_array($orderFormConfig ?? null) ? $orderFormConfig : [];
$searchUrl = (string) ($orderFormConfig['searchUrl'] ?? company_url('orders/searchCustomer'));
?>
<link rel="stylesheet" href="<?= dated_file_url('companyasset/css/fv-order-customer-modal.css') ?>" />
<div class="modal fade fv-customer-picker-modal" id="modal_order_change_customer" tabindex="-1" role="dialog"
     aria-labelledby="orderChangeCustomerModalTitle" aria-hidden="true"
     data-fv-modal-icon="mdi-account-multiple">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="orderChangeCustomerModalTitle">
                        <?= htmlspecialchars((string) ($pageTexts['customer_order'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </h5>
                    <p class="modal-subtitle"><?= htmlspecialchars((string) ($pageTexts['add_order_modal_subtitle'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= htmlspecialchars((string) ($pageTexts['cancelar_add'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="fv-customer-picker">
                    <div class="fv-customer-picker__search-wrap">
                        <i class="mdi mdi-magnify" aria-hidden="true"></i>
                        <input type="search"
                               id="fv_order_customer_search"
                               class="fv-customer-picker__search"
                               autocomplete="off"
                               placeholder="<?= htmlspecialchars((string) ($pageTexts['add_order_search_placeholder'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                               data-search-url="<?= htmlspecialchars($searchUrl, ENT_QUOTES, 'UTF-8') ?>"
                               aria-label="<?= htmlspecialchars((string) ($pageTexts['add_order_search_placeholder'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                    <div class="fv-customer-picker__list" id="fv_order_customer_picker_list" role="listbox"
                         aria-label="<?= htmlspecialchars((string) ($pageTexts['customer_order'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light fv-crud-btn" data-dismiss="modal">
                    <?= htmlspecialchars((string) ($pageTexts['cancelar_add'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </button>
            </div>
        </div>
    </div>
</div>

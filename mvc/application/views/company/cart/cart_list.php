<?php
$order_type_general = $_GET['order_type_general'] ?? '';
$order_type_sort = $_GET['order_type_sort'] ?? '';
$order_type_id = $_GET['order_type_id'] ?? '';
$order_type_customer = $_GET['order_type_customer'] ?? '';
$order_type_monto = $_GET['order_type_monto'] ?? '';
$order_type_created = $_GET['order_type_created'] ?? '';

$desdeFecha = $this->input->get('desde_fecha') ?: date('Y-m-01');
$hastaFecha = $this->input->get('hasta_fecha') ?: date('Y-m-d');
$estadoSel = (string) ($this->input->get('estado') ?? '');
$searchVal = (string) ($this->input->get('search') ?? '');

$resolveSourceMeta = static function ($cart, array $langCart): array {
    $source = strtolower(trim((string) ($cart->source ?? '')));
    if ($source === '') {
        $type = strtolower(trim((string) ($cart->type_cart ?? '')));
        if ($type === 'to_company') {
            $source = 'manual';
        } elseif (in_array($type, ['to_customer', 'to_portal_seller'], true)) {
            $source = 'catalog';
        } elseif ((int) ($cart->catalog_id ?? 0) > 0) {
            $source = 'catalog';
        } else {
            $source = 'catalog';
        }
    }

    if (in_array($source, ['app', 'mobile', 'android', 'ios'], true)) {
        return [
            'key' => 'app',
            'icon' => 'mdi mdi-cellphone-android',
            'class' => 'oc-source--app',
            'title' => (string) ($langCart['source_app'] ?? ''),
        ];
    }
    if (in_array($source, ['manual'], true)) {
        return [
            'key' => 'manual',
            'icon' => 'mdi mdi-pencil-box-outline',
            'class' => 'oc-source--manual',
            'title' => (string) ($langCart['source_manual'] ?? ''),
        ];
    }
    if (in_array($source, ['marketplace', 'seller_portal'], true)) {
        return [
            'key' => 'marketplace',
            'icon' => 'mdi mdi-cart-outline',
            'class' => 'oc-source--marketplace',
            'title' => (string) ($langCart['source_marketplace'] ?? ''),
        ];
    }

    return [
        'key' => 'catalog',
        'icon' => 'mdi mdi-store',
        'class' => 'oc-source--catalog',
        'title' => (string) ($langCart['source_catalog'] ?? ''),
    ];
};
?>
<link rel="stylesheet" href="<?= dated_file_url('companyasset/company/cart/open-cart-list.css') ?>">

<div class="container-fluid page-body-wrapper open-cart-view open-cart-page">
    <div class="main-panel">
        <div class="container-fluid oc-wrap">
            <div class="oc-top">
                <div class="page-header mb-0">
                    <h3 class="page-title mb-0"><?= htmlspecialchars(fv_page_heading_text((string) ($lang_order['carrito'] ?? '')), ENT_QUOTES, 'UTF-8') ?></h3>
                </div>
                <div class="oc-pills">
                    <div class="oc-pill">
                        <span><?= htmlspecialchars((string) ($lang_cart['count_total'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        <strong id="total_qty"><?= (int) $count_total ?></strong>
                    </div>
                    <div class="oc-pill">
                        <span><?= htmlspecialchars((string) ($lang_cart['filtered_results'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        <strong id="countprd"><?= (int) $count_filtered ?></strong>
                    </div>
                </div>
            </div>

            <?php if (!empty($order_created = $this->session->flashdata('orden_created'))): ?>
            <div class="alert alert-success" role="alert">
                <?= sprintf($lang_status['orden_created'], $order_created) ?>
            </div>
            <?php endif; ?>

            <div class="card open-cart-card">
                <div class="card-body">
                    <div class="oc-panel">
                        <div class="oc-filters">
                            <form id="form-buscar-filtro" action="<?= company_url('cart/abandoned') ?>">
                                <div class="row">
                                    <div class="col-lg-3 col-md-6">
                                        <div class="form-group">
                                            <label for="date_picker_click"><?= htmlspecialchars((string) ($lang_order['desde_fecha'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
                                            <?= fv_render_company_date_input('desde_fecha', $desdeFecha, [
                                                'id' => 'date_picker_click',
                                                'data-fv-autosubmit' => '1',
                                            ]) ?>
                                            <input hidden id="order_type" name="order_type"
                                                value="<?= htmlspecialchars((string) $order_type_general, ENT_QUOTES, 'UTF-8') ?>">
                                            <input hidden id="order_sort" name="sort"
                                                value="<?= htmlspecialchars((string) $order_type_sort, ENT_QUOTES, 'UTF-8') ?>">
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="form-group">
                                            <label for="oc_hasta"><?= htmlspecialchars((string) ($lang_order['hasta_fecha'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
                                            <?= fv_render_company_date_input('hasta_fecha', $hastaFecha, [
                                                'id' => 'oc_hasta',
                                                'data-fv-autosubmit' => '1',
                                            ]) ?>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="form-group">
                                            <label for="oc_estado"><?= htmlspecialchars((string) ($lang_order['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
                                            <select id="oc_estado" name="estado" class="form-control selectpicker"
                                                data-size="5" onchange="buscar_porfiltro()">
                                                <option value="all" <?= ($estadoSel === '' || $estadoSel === 'all') ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars(strtoupper((string) ($lang_order['all'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                                                </option>
                                                <?php foreach ($status_cart as $status) { ?>
                                                <option value="<?= htmlspecialchars((string) ($status->cod ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                    <?= ((string) $estadoSel === (string) ($status->cod ?? '')) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars(strtoupper((string) ($status->name ?? '')), ENT_QUOTES, 'UTF-8') ?>
                                                </option>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="form-group">
                                            <label for="oc_search"><?= htmlspecialchars((string) ($langprods['caja_text_search'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
                                            <div class="input-group">
                                                <input id="oc_search" type="text" name="search"
                                                    class="form-control text-search"
                                                    placeholder="<?= htmlspecialchars((string) ($langprods['caja_text_search'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                    value="<?= htmlspecialchars($searchVal, ENT_QUOTES, 'UTF-8') ?>">
                                                <button type="submit" form="form-buscar-filtro" class="btn btn-light">
                                                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>
                                            <a class="sort" data-sort="id"
                                                data-order_type="<?= htmlspecialchars((string) $order_type_id, ENT_QUOTES, 'UTF-8') ?>"
                                                href="#"><?= htmlspecialchars((string) ($lang_order['order_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                                        </th>
                                        <th>
                                            <a class="sort" data-sort="customer"
                                                data-order_type="<?= htmlspecialchars((string) $order_type_customer, ENT_QUOTES, 'UTF-8') ?>"
                                                href="#"><?= htmlspecialchars((string) ($lang_order['customer'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                                        </th>
                                        <th>
                                            <a class="sort" data-sort="monto"
                                                data-order_type="<?= htmlspecialchars((string) $order_type_monto, ENT_QUOTES, 'UTF-8') ?>"
                                                href="#"><?= htmlspecialchars((string) ($lang_order['monto'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                                        </th>
                                        <th>
                                            <a class="sort" data-sort="created"
                                                data-order_type="<?= htmlspecialchars((string) $order_type_created, ENT_QUOTES, 'UTF-8') ?>"
                                                href="#"><?= htmlspecialchars((string) ($lang_order['created'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                                        </th>
                                        <th><?= htmlspecialchars((string) ($lang_order['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                                        <th class="text-right"><?= htmlspecialchars((string) ($lang_order['action'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($cart_history)) : ?>
                                    <tr>
                                        <td colspan="6">
                                            <div class="oc-empty"><?= htmlspecialchars((string) ($lang_cart['list_empty'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php foreach ($cart_history as $cart) {
                                        $cartId = (int) ($cart->cart_id ?? 0);
                                        $orderId = (int) ($cart->order_id ?? 0);
                                        $orderLabel = trim((string) ($cart->order_number ?? ''));
                                        if ($orderLabel === '') {
                                            $orderLabel = (string) $cartId;
                                        }
                                        $orderHref = $orderId > 0
                                            ? company_url('orders/view/' . $orderId)
                                            : company_url('cart/opencart/' . $cartId);
                                        $sourceMeta = $resolveSourceMeta($cart, is_array($lang_cart) ? $lang_cart : []);

                                        $color = '#64748b';
                                        $name_status = '';
                                        $disabled = '';
                                        $statusCodes = array_map('strval', array_column($status_cart, 'cod'));
                                        $statusKey = array_search((string) ($cart->status_cart ?? ''), $statusCodes, true);
                                        if ($statusKey !== false) {
                                            $color = (string) ($status_cart[$statusKey]->color ?? $color);
                                            $name_status = (string) ($status_cart[$statusKey]->name ?? '');
                                            $disabled = ((int) ($status_cart[$statusKey]->nosubmit_order ?? 0) === 1) ? 'disabled' : '';
                                        }
                                        $digits = (int) ($format_numeric->digits ?? 2);
                                        $radix = (string) ($format_numeric->radixPoint ?? '.');
                                        $groupSep = (string) ($format_numeric->groupSeparator ?? ',');
                                        $total_orden = number_format((float) ($cart->total ?? 0), $digits, $radix, $groupSep);
                                        $createdTs = ! empty($cart->created) ? strtotime((string) $cart->created) : false;
                                        $createdLabel = $createdTs ? date((string) ($format_date->format_php ?? 'Y-m-d'), $createdTs) : '';
                                    ?>
                                    <tr>
                                        <td>
                                            <a class="oc-order" href="<?= htmlspecialchars($orderHref, ENT_QUOTES, 'UTF-8') ?>"
                                                title="<?= htmlspecialchars($sourceMeta['title'], ENT_QUOTES, 'UTF-8') ?>">
                                                <span class="oc-source <?= htmlspecialchars($sourceMeta['class'], ENT_QUOTES, 'UTF-8') ?>">
                                                    <i class="<?= htmlspecialchars($sourceMeta['icon'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
                                                </span>
                                                <span><?= htmlspecialchars($orderLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                            </a>
                                        </td>
                                        <td>
                                            <a class="oc-customer" href="<?= company_url('cart/opencart/' . $cartId) ?>">
                                                <?= htmlspecialchars((string) ($cart->customer ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                            </a>
                                        </td>
                                        <td><span class="oc-amount"><?= htmlspecialchars($moneda . $total_orden, ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <td><span class="oc-date"><?= htmlspecialchars($createdLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                                        <td>
                                            <span class="oc-status">
                                                <i class="fa-solid fa-circle" style="color: <?= htmlspecialchars($color, ENT_QUOTES, 'UTF-8') ?>;" aria-hidden="true"></i>
                                                <?= htmlspecialchars($name_status, ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <div class="oc-actions">
                                                <a href="<?= company_url('cart/convert_carttoorder/' . $cartId) ?>"
                                                    class="btn btn-oc-confirm btn-sm btn-submit-cart-order <?= $disabled ?>"
                                                    data-toggle="tooltip"
                                                    data-has-items="<?= ((float) ($cart->total ?? 0) > 0) ? '1' : '0' ?>"
                                                    data-redirect="<?= company_url('cart/abandoned') ?>"
                                                    title="<?= htmlspecialchars((string) ($lang_cart['submit_order'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                    data-loader="<?= htmlspecialchars((string) ($lang_cart['submiting_order'] ?? $lang_status['submiting_order'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                    <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                                                </a>
                                                <a tabindex="-1"
                                                    href="<?= company_url('cart/opencart/' . $cartId) ?>"
                                                    class="btn btn-oc-view btn-sm"
                                                    data-toggle="tooltip"
                                                    title="<?= htmlspecialchars((string) ($lang_order['view_tol'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                    <i class="mdi mdi-eye" aria-hidden="true"></i>
                                                </a>
                                                <button type="button"
                                                    class="btn btn-oc-delete btn-sm btn-delete-cart"
                                                    data-toggle="tooltip"
                                                    data-action="delete-cart"
                                                    title="<?= htmlspecialchars((string) ($lang_cart['delete_thiscart'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                    data-url="<?= company_url('cart/delete_cart/' . $cartId) ?>"
                                                    data-redirect="<?= company_url('cart/abandoned') ?>">
                                                    <i class="mdi mdi-delete" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (! empty($paginate)) : ?>
                        <div class="paginate"><?= $paginate ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.fvOpenCartLang = <?= json_encode([
    'question_delete' => (string) ($lang_cart['question_delete'] ?? ''),
    'question_confirm_order' => (string) ($lang_cart['question_confirm_order'] ?? ''),
    'submit_order' => (string) ($lang_cart['submit_order'] ?? ''),
    'submiting_order' => (string) ($lang_cart['submiting_order'] ?? ''),
    'empty_cart' => (string) ($lang_cart['empty_cart'] ?? ''),
    'submit_failed' => (string) ($lang_cart['submit_failed'] ?? ''),
    'not_found' => (string) ($lang_cart['not_found'] ?? ''),
    'invalid_cart' => (string) ($lang_cart['invalid_cart'] ?? ''),
    'nosubmit' => (string) ($lang_cart['nosubmit'] ?? ''),
    'expired' => (string) ($lang_cart['expired'] ?? ''),
    'orden_created' => (string) ($lang_cart['orden_created'] ?? ''),
    'yes' => (string) ($lang_cart['yes'] ?? ''),
    'cancel' => (string) ($lang_cart['cancel'] ?? ''),
    'ok' => (string) ($lang_order['cerrar'] ?? ''),
    'cart_deleted' => (string) ($lang_cart['cart_deleted'] ?? ''),
    'not_deleted' => (string) ($lang_cart['not_deleted'] ?? ''),
    'delete_error' => (string) ($lang_cart['delete_error'] ?? ''),
    'notify_error' => (string) ($lang_cart['notify_error'] ?? ''),
], JSON_UNESCAPED_UNICODE) ?>;

$(document).ready(function() {
    if (typeof $.fn.tooltip === 'function') {
        $('[data-toggle="tooltip"]').tooltip();
    }
    $(document).on('click', '.sort', function(e) {
        e.preventDefault();
        $('#order_type').val($(this).data('order_type'));
        $('#order_sort').val($(this).data('sort'));
        $('#form-buscar-filtro').submit();
    });
    $(document).on('change', 'input[name="desde_fecha"], input[name="hasta_fecha"]', function() {
        $('#form-buscar-filtro').submit();
    });
});
function buscar_porfiltro() {
    $('#form-buscar-filtro').submit();
}
</script>

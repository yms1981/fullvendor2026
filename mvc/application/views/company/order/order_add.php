<?php
/**
 * @var $customer object the customer data
 * @var $sellers array company sellers
 * @var $pageTexts array
 * @var $moneda string
 * @var $format_numeric object
 * @var array $orderFormConfig
 */
$seller = $customer->sellers[0] ?? null;
$moneda = isset($moneda) && $moneda !== '' ? $moneda : '$';
$fmt = (object)['digits' => 2, 'radixPoint' => ',', 'groupSeparator' => '.'];
if (isset($format_numeric) && is_object($format_numeric) && isset($format_numeric->digits)) {
    $fmt = $format_numeric;
}
$fmtGroup = function_exists('fv_resolve_numeric_group_separator')
    ? fv_resolve_numeric_group_separator($fmt)
    : (string) (($fmt->groupSeparator ?? '') !== '' ? $fmt->groupSeparator : (($fmt->radixPoint ?? '.') === ',' ? '.' : ','));
$fmtZero = number_format(0, (int) $fmt->digits, (string) ($fmt->radixPoint ?? '.'), $fmtGroup);
$planLimitBlock = isset($planLimitBlock) && is_array($planLimitBlock) ? $planLimitBlock : ['can_create' => true];
$planLimitBlocked = empty($planLimitBlock['can_create']);
$pageTexts = is_array($pageTexts ?? null) ? $pageTexts : [];
?>
<div id="app" class="fv-order-form-page">
    <div class="container-fluid page-body-wrapper pt-3">
        <div class="main-panel">
            <div class="container-fluid">
                <div class="page-header mb-3">
                    <h3 class="page-title"><?= htmlspecialchars(fv_page_heading_text((isset($tipo_d) && $tipo_d === 'D') ? ($pageTexts['new_ordertitle_order'] ?? $pageTexts['new_ordertitle'] ?? '') : ($pageTexts['new_ordertitle_credit_note'] ?? $pageTexts['new_credit_note'] ?? '')), ENT_QUOTES, 'UTF-8') ?></h3>
                </div>

                <?php $this->load->view('company/plan_limit_notice', ['planLimitBlock' => $planLimitBlock]); ?>

                <div class="card fv-order-form-card">
                    <form id="order_form" class="forms-sample" action="<?= company_url('orders/save') ?>"
                        method="post" enctype="multipart/form-data" novalidate
                        data-plan-limit-blocked="<?= $planLimitBlocked ? '1' : '0' ?>"
                        data-plan-limit-message="<?= htmlspecialchars((string) ($planLimitBlock['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="tipo_d" id="tipo_d_order" value="<?= isset($tipo_d) ? htmlspecialchars($tipo_d) : 'D' ?>">
                        <input type="hidden" name="cart_id" id="open_cart_id" value="">
                        <input type="hidden" name="token_kor" id="open_cart_token" value="">

                        <div class="card-body">
                            <?php $this->load->view('company/order/_order_form_customer', [
                                'pageTexts' => $pageTexts,
                                'orderFormConfig' => $orderFormConfig ?? [],
                                'customers' => $customers ?? [],
                                'sellers' => $sellers,
                                'seller' => $seller,
                                'mode' => 'add',
                            ]); ?>
                        </div>

                        <div class="fv-order-lines">
                            <div class="fv-order-lines__table-wrap">
                                <table class="table mb-0">
                                    <thead>
                                        <tr>
                                            <th width="2%">#</th>
                                            <th width="10%"><?= htmlspecialchars((string) ($pageTexts['image'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                                            <th width="25%"><?= htmlspecialchars((string) ($pageTexts['product_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                                            <th width="10%"><?= htmlspecialchars((string) ($pageTexts['sku'] ?? 'Sku'), ENT_QUOTES, 'UTF-8') ?></th>
                                            <th width="10%" class="text-center"><?= htmlspecialchars((string) ($pageTexts['cantidad'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                                            <th width="13%" class="text-center"><?= htmlspecialchars((string) ($pageTexts['sale_price'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                                            <th width="10%" class="text-center"><?= htmlspecialchars((string) ($pageTexts['total'] ?? 'Total'), ENT_QUOTES, 'UTF-8') ?></th>
                                            <th width="20%" class="text-center"><?= htmlspecialchars((string) ($pageTexts['comments_detail'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (isset($order['details'])): ?>
                                        <?php $i = 1; ?>
                                        <?php foreach ($order['details'] as $detail): ?>
                                            <tr class="notranslate item" data-product-id="<?= (int) ($detail['product_id'] ?? 0) ?>">
                                                <td>
                                                    <?= $i ?>
                                                    <input type="hidden" class="input-id" name="detalle[<?= $i ?>][id]" value="<?= htmlspecialchars((string) ($detail['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="hidden" name="detalle[<?= $i ?>][row]" value="<?= $i ?>">
                                                    <input type="hidden" class="input-product-id" name="detalle[<?= $i ?>][id_producto]" value="<?= (int) ($detail['product_id'] ?? 0) ?>">
                                                </td>
                                                <td class="proimg text-center align-middle">
                                                    <?php
                                                    $listItem = (object) [
                                                        'sku' => (string) ($detail['sku'] ?? ''),
                                                        'default_image_id' => trim((string) ($detail['image'] ?? '')),
                                                        'product_image' => trim((string) ($detail['image'] ?? '')),
                                                    ];
                                                    $this->load->view('company/product/partials/thumbnail', ['listItem' => $listItem]);
                                                    ?>
                                                </td>
                                                <td class="align-middle"><?= htmlspecialchars(strtoupper((string) ($detail['name'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="align-middle"><?= htmlspecialchars((string) ($detail['sku'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="align-middle text-center order-edit-cell-qty">
                                                    <input type="number" class="form-control text-center input-qty" name="detalle[<?= $i ?>][qty]" value="<?= htmlspecialchars((string) ($detail['qty'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" min="0">
                                                </td>
                                                <td class="align-middle order-edit-cell-price">
                                                    <div class="input-group input-group-sm">
                                                        <div class="input-group-prepend"><span class="input-group-text"><?= htmlspecialchars($moneda, ENT_QUOTES, 'UTF-8') ?></span></div>
                                                        <input class="form-control text-right input-price" name="detalle[<?= $i ?>][precio]" type="text"
                                                               value="<?= number_format((float) ($detail['sale_price'] ?? 0), $fmt->digits, $fmt->radixPoint, $fmt->groupSeparator) ?>">
                                                    </div>
                                                    <input type="hidden" class="check-fixed-price" name="detalle[<?= $i ?>][precio_fijo]" value="<?= !empty($detail['fixed_price']) ? '1' : '0' ?>">
                                                </td>
                                                <td class="align-middle text-center">
                                                    <strong><?= htmlspecialchars($moneda, ENT_QUOTES, 'UTF-8') ?>
                                                        <span class="product-total"><?= number_format((float) (($detail['qty'] ?? 0) * ($detail['sale_price'] ?? 0)), (int) $fmt->digits, (string) ($fmt->radixPoint ?? '.'), $fmtGroup) ?></span>
                                                    </strong>
                                                </td>
                                                <td class="align-middle">
                                                    <textarea rows="2" class="form-control input-comment" name="detalle[<?= $i ?>][comentarios]"><?= htmlspecialchars(trim((string) ($detail['comments'] ?? '')), ENT_QUOTES, 'UTF-8') ?></textarea>
                                                </td>
                                            </tr>
                                            <?php $i++; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                        <tr class="notranslate fv-order-total-row">
                                            <td colspan="6"><strong><?= htmlspecialchars((string) ($pageTexts['sub_total'] ?? 'Sub Total'), ENT_QUOTES, 'UTF-8') ?></strong></td>
                                            <td class="text-right"><strong><?= htmlspecialchars($moneda, ENT_QUOTES, 'UTF-8') ?><strong id="sub_total"><?= $fmtZero ?></strong></strong></td>
                                            <td></td>
                                        </tr>
                                        <tr class="notranslate fv-order-total-row">
                                            <td colspan="6"><strong><?= htmlspecialchars((string) ($pageTexts['discount_total'] ?? ''), ENT_QUOTES, 'UTF-8') ?> (<span id="span_discount"></span>)</strong></td>
                                            <td class="text-right"><strong><?= htmlspecialchars($moneda, ENT_QUOTES, 'UTF-8') ?><strong id="total_descuento"><?= $fmtZero ?></strong></strong></td>
                                            <td></td>
                                        </tr>
                                        <tr class="notranslate fv-order-total-row fv-order-total-row--grand">
                                            <td colspan="6"><strong><?= htmlspecialchars((string) ($pageTexts['price_total'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></td>
                                            <td class="text-right"><strong><?= htmlspecialchars($moneda, ENT_QUOTES, 'UTF-8') ?><strong id="total_orden"><?= $fmtZero ?></strong></strong></td>
                                            <td></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="fv-order-notes">
                            <label><?= htmlspecialchars((string) ($pageTexts['order_internal'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
                            <textarea placeholder="..." class="form-control" rows="4" name="internal_comments"><?= htmlspecialchars((string) ($order['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php $this->load->view('company/order/_order_form_actions', [
        'pageTexts' => $pageTexts,
        'mode' => 'add',
        'planLimitBlocked' => $planLimitBlocked,
        'orderId' => 0,
    ]); ?>
    <?php $this->load->view('company/order/modal_addproduct', ['pageTexts' => $pageTexts, 'moneda' => $moneda, 'format_numeric' => $fmt]); ?>
</div>
<script>
(function(){ var u = '<?= rtrim(company_url(), "/") ?>'; if (typeof options === 'undefined') window.options = {}; if (typeof options.url === 'undefined') options.url = u; })();
window.FORMAT_NUMERIC_ORDER = <?= json_encode([
    'digits' => (int) $fmt->digits,
    'radixPoint' => $fmt->radixPoint ?? ',',
    'groupSeparator' => $fmtGroup,
], JSON_UNESCAPED_UNICODE) ?>;
window.planLimitBlock = <?= json_encode($planLimitBlock, JSON_UNESCAPED_UNICODE) ?>;
window.fvOpenCart = window.fvOpenCart || { cart_id: 0, token_kor: '' };
window.orderAddLang = <?= json_encode([
    'updating' => $pageTexts['updating'] ?? '',
    'mensaje_success' => $pageTexts['mensaje_success'] ?? '',
    'error_orden' => $pageTexts['error_orden'] ?? '',
    'boton_save' => $pageTexts['boton-save'] ?? $pageTexts['guardar'] ?? '',
], JSON_UNESCAPED_UNICODE) ?>;
$(function(){ if (typeof window.calcularTotales === 'function') window.calcularTotales(); });
</script>

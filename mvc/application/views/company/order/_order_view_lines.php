<?php
/**
 * Líneas de orden — solo lectura (vista detalle).
 * Si la orden está despachada, muestra delivered_quantity / delivered_pack y totales asignados.
 *
 * @var object $order
 * @var array $pageTexts
 * @var string $moneda
 * @var object $fmt
 * @var bool $show_dispatched_info
 * @var string|null $dispatched_warehouse_display
 */
$pageTexts = is_array($pageTexts ?? null) ? $pageTexts : [];
$order = $order ?? null;
$moneda = ($moneda ?? '') !== '' ? $moneda : '$';
$fmt = $fmt ?? (object) ['digits' => 2, 'radixPoint' => ',', 'groupSeparator' => '.'];
$showDispatched = !empty($show_dispatched_info);
$warehouseDisplay = isset($dispatched_warehouse_display) ? trim((string) $dispatched_warehouse_display) : '';
$fmtNum = static function ($n) use ($fmt) {
    return number_format((float) $n, (int) $fmt->digits, (string) $fmt->radixPoint, (string) $fmt->groupSeparator);
};
$fmtDelivered = static function ($n) use ($fmt, $pageTexts) {
    if ($n === null || $n === '') {
        return '—';
    }
    $v = (float) $n;
    if ($v < 0) {
        return (string) ($pageTexts['no_disponible'] ?? '—');
    }

    return number_format($v, (int) $fmt->digits, (string) $fmt->radixPoint, (string) $fmt->groupSeparator);
};

$discountLabel = '';
if ($order && isset($order->discount_type, $order->discount)) {
    $dVal = (float) $order->discount;
    if ((string) $order->discount_type === '1') {
        $discountLabel = $fmtNum($dVal) . '%';
    } elseif ((string) $order->discount_type === '2') {
        $discountLabel = $moneda . $fmtNum($dVal);
    }
}

$orderDetails = $order->order_details ?? $order->orderDetails ?? [];
if (is_object($orderDetails) && method_exists($orderDetails, 'all')) {
    $orderDetails = $orderDetails->all();
}
if (!is_array($orderDetails)) {
    $orderDetails = [];
}

$colspanLabel = $showDispatched ? 12 : 9;
$colspanEmpty = $showDispatched ? 13 : 10;

$subtotalAsignado = 0.0;
$descuentoAsignado = 0.0;
$totalAsignado = 0.0;
if ($showDispatched && $order) {
    if (isset($order->subtotal_asignado)) {
        $subtotalAsignado = (float) $order->subtotal_asignado;
    } else {
        foreach ($orderDetails as $d) {
            $dd = is_array($d) ? (object) $d : $d;
            $dq = (float) ($dd->delivered_quantity ?? 0);
            $sp = (float) ($dd->sale_price ?? 0);
            if ($dq > 0) {
                $subtotalAsignado += $dq * $sp;
            }
        }
    }
    if (isset($order->descuento_asignado)) {
        $descuentoAsignado = (float) $order->descuento_asignado;
    } else {
        $dtype = (int) ($order->discount_type ?? 0);
        $disc = (float) ($order->discount ?? 0);
        if ($dtype === 1) {
            $descuentoAsignado = $subtotalAsignado * ($disc / 100);
        } elseif ($dtype === 2) {
            $descuentoAsignado = $disc;
        }
    }
    $totalAsignado = isset($order->total_asignado)
        ? (float) $order->total_asignado
        : max(0.0, $subtotalAsignado - $descuentoAsignado);
}
?>
<?php if ($showDispatched): ?>
    <div class="alert alert-info fv-order-dispatched-banner mb-3" role="status">
        <strong><?= htmlspecialchars((string) ($pageTexts['order_dispatched_section'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
        <?php if ($warehouseDisplay !== ''): ?>
            <span class="ml-2">
                <?= htmlspecialchars((string) ($pageTexts['warehouse_user'] ?? ''), ENT_QUOTES, 'UTF-8') ?>:
                <strong><?= htmlspecialchars($warehouseDisplay, ENT_QUOTES, 'UTF-8') ?></strong>
            </span>
        <?php endif; ?>
        <div class="small mt-1 mb-0"><?= htmlspecialchars((string) ($pageTexts['order_dispatched_section_hint'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<div class="fv-order-lines">
    <div class="fv-order-lines__table-wrap">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th width="2%">#</th>
                    <th width="<?= $showDispatched ? '8%' : '9%' ?>"><?= htmlspecialchars((string) ($pageTexts['image'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                    <th width="<?= $showDispatched ? '18%' : '24%' ?>"><?= htmlspecialchars((string) ($pageTexts['product_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                    <th width="8%"><?= htmlspecialchars((string) ($pageTexts['sku'] ?? 'SKU'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th width="8%" class="text-center"><?= htmlspecialchars((string) ($pageTexts['unit_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                    <th width="7%" class="text-center"><?= htmlspecialchars((string) ($pageTexts['cantidad'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                    <?php if ($showDispatched): ?>
                        <th width="7%" class="text-center text-success"><?= htmlspecialchars((string) ($pageTexts['qty_asigned'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                        <th width="7%" class="text-center text-success"><?= htmlspecialchars((string) ($pageTexts['pack'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                    <?php endif; ?>
                    <th width="7%" class="text-center"><?= htmlspecialchars((string) ($pageTexts['stock'] ?? 'Stock'), ENT_QUOTES, 'UTF-8') ?></th>
                    <th width="9%" class="text-center"><?= htmlspecialchars((string) ($pageTexts['sale_price'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                    <th width="9%" class="text-center"><?= htmlspecialchars((string) ($pageTexts['total'] ?? $pageTexts['total_pedido'] ?? 'Total'), ENT_QUOTES, 'UTF-8') ?></th>
                    <?php if ($showDispatched): ?>
                        <th width="9%" class="text-center text-success"><?= htmlspecialchars((string) ($pageTexts['total_asigned'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                    <?php endif; ?>
                    <th width="<?= $showDispatched ? '10%' : '17%' ?>" class="text-center"><?= htmlspecialchars((string) ($pageTexts['comments_detail'] ?? $pageTexts['comments'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($orderDetails)): ?>
                <?php $i = 1; ?>
                <?php foreach ($orderDetails as $detail): ?>
                    <?php
                    $detail = is_array($detail) ? (object) $detail : $detail;
                    $product = $detail->product ?? null;
                    $salePrice = (float) ($detail->sale_price ?? $detail->salePrice ?? 0);
                    $qty = (float) ($detail->qty ?? 0);
                    $lineTotal = (float) ($detail->total_amount ?? $detail->totalAmount ?? ($salePrice * $qty));
                    $comment = trim((string) ($detail->comments ?? $detail->comment ?? ''));
                    $deliveredQty = $detail->delivered_quantity ?? $detail->delivered_qty ?? null;
                    $deliveredPack = $detail->delivered_pack ?? null;
                    $assignedTotal = isset($detail->total_amount_assigned)
                        ? (float) $detail->total_amount_assigned
                        : (((float) ($deliveredQty ?? 0) > 0) ? ((float) $deliveredQty * $salePrice) : 0.0);
                    ?>
                    <tr class="notranslate fv-order-view-line">
                        <td class="align-middle"><?= $i ?></td>
                        <td class="proimg text-center align-middle">
                            <div class="position-relative d-inline-block fv-order-line-thumb">
                                <?php if (!empty($detail->star)): ?>
                                    <a data-toggle="modal" data-target="#modal-histoy"
                                       data-sku-product="<?= htmlspecialchars((string) ($product->sku ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                       data-name-product="<?= htmlspecialchars((string) ($product->name ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                       data-id="<?= (int) ($product->pro_id ?? 0) ?>"
                                       style="color:#ffc107;cursor:pointer;z-index:2;"
                                       class="position-absolute top-0 start-0"
                                       title="<?= htmlspecialchars((string) ($pageTexts['sold'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="fa-solid fa-star"></i>
                                    </a>
                                <?php endif; ?>
                                <?php $this->load->view('company/product/partials/thumbnail', ['listItem' => $product]); ?>
                            </div>
                        </td>
                        <td class="align-middle">
                            <?= htmlspecialchars(strtoupper((string) ($product->name ?? '')), ENT_QUOTES, 'UTF-8') ?>
                            <?php if (isset($product->status) && (int) $product->status === 0): ?>
                                <br><span class="badge badge-danger"><?= htmlspecialchars((string) ($pageTexts['producto_innactivo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="align-middle">
                            <?php if (!empty($product->pro_id)): ?>
                                <a href="<?= company_url('products/edit/' . (int) $product->pro_id) ?>" title="Edit"><?= htmlspecialchars((string) ($product->sku ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                            <?php else: ?>
                                <?= htmlspecialchars((string) ($product->sku ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </td>
                        <?php
                        $unitLabel = function_exists('fv_product_unit_label') ? fv_product_unit_label($product) : '';
                        ?>
                        <td class="align-middle text-center notranslate"><?= $unitLabel !== '' ? htmlspecialchars($unitLabel, ENT_QUOTES, 'UTF-8') : '—' ?></td>
                        <?php
                        $qtyPlaces = function_exists('fv_product_unit_decimal_places')
                            ? fv_product_unit_decimal_places($product)
                            : 0;
                        $fmtQty = static function ($n) use ($fmt, $qtyPlaces) {
                            return number_format((float) $n, $qtyPlaces, (string) $fmt->radixPoint, (string) $fmt->groupSeparator);
                        };
                        ?>
                        <td class="align-middle text-center"><strong><?= $fmtQty($qty) ?></strong></td>
                        <?php if ($showDispatched): ?>
                            <td class="align-middle text-center text-success"><strong><?php
                                if ($deliveredQty === null || $deliveredQty === '' || (float) $deliveredQty < 0) {
                                    echo htmlspecialchars((string) (($deliveredQty !== null && $deliveredQty !== '' && (float) $deliveredQty < 0) ? ($pageTexts['no_disponible'] ?? '—') : '—'), ENT_QUOTES, 'UTF-8');
                                } else {
                                    echo htmlspecialchars($fmtQty($deliveredQty), ENT_QUOTES, 'UTF-8');
                                }
                            ?></strong></td>
                            <td class="align-middle text-center text-success"><strong><?= htmlspecialchars($fmtDelivered($deliveredPack), ENT_QUOTES, 'UTF-8') ?></strong></td>
                        <?php endif; ?>
                        <td class="align-middle text-center"><strong><?= htmlspecialchars($fmtQty(is_object($product) ? ($product->stock ?? 0) : 0), ENT_QUOTES, 'UTF-8') ?></strong></td>
                        <td class="align-middle text-center"><strong><?= htmlspecialchars($moneda, ENT_QUOTES, 'UTF-8') ?><?= $fmtNum($salePrice) ?></strong></td>
                        <td class="align-middle text-center"><strong><?= htmlspecialchars($moneda, ENT_QUOTES, 'UTF-8') ?><?= $fmtNum($lineTotal) ?></strong></td>
                        <?php if ($showDispatched): ?>
                            <td class="align-middle text-center text-success"><strong><?= htmlspecialchars($moneda, ENT_QUOTES, 'UTF-8') ?><?= $fmtNum($assignedTotal) ?></strong></td>
                        <?php endif; ?>
                        <td class="align-middle"><?= $comment !== '' ? htmlspecialchars($comment, ENT_QUOTES, 'UTF-8') : '—' ?></td>
                    </tr>
                    <?php $i++; ?>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?= (int) $colspanEmpty ?>" class="text-center text-muted py-4"><?= htmlspecialchars((string) ($pageTexts['sin_resultados'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endif; ?>
                <tr class="notranslate fv-order-total-row">
                    <td colspan="<?= (int) $colspanLabel ?>"><strong><?= htmlspecialchars((string) ($pageTexts['sub_total'] ?? 'Sub Total'), ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td class="text-right"><strong><?= htmlspecialchars($moneda, ENT_QUOTES, 'UTF-8') ?><?= $fmtNum($order->subtotal ?? $order->amount ?? 0) ?></strong></td>
                </tr>
                <tr class="notranslate fv-order-total-row">
                    <td colspan="<?= (int) $colspanLabel ?>"><strong><?= htmlspecialchars((string) ($pageTexts['discount_total'] ?? ''), ENT_QUOTES, 'UTF-8') ?> (<span><?= htmlspecialchars($discountLabel, ENT_QUOTES, 'UTF-8') ?></span>)</strong></td>
                    <td class="text-right"><strong><?= htmlspecialchars($moneda, ENT_QUOTES, 'UTF-8') ?><?= $fmtNum($order->descuento ?? $order->discount ?? 0) ?></strong></td>
                </tr>
                <tr class="notranslate fv-order-total-row fv-order-total-row--grand">
                    <td colspan="<?= (int) $colspanLabel ?>"><strong><?= htmlspecialchars((string) ($pageTexts['price_total'] ?? 'Total'), ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td class="text-right"><strong><?= htmlspecialchars($moneda, ENT_QUOTES, 'UTF-8') ?><?= $fmtNum($order->total ?? $order->total_amount ?? 0) ?></strong></td>
                </tr>
                <?php if ($showDispatched): ?>
                <tr class="notranslate fv-order-total-row text-success">
                    <td colspan="<?= (int) $colspanLabel ?>"><strong><?= htmlspecialchars((string) ($pageTexts['sub_total_asigned'] ?? $pageTexts['total_asigned'] ?? ''), ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string) ($pageTexts['dispatched'] ?? ''), ENT_QUOTES, 'UTF-8') ?>)</strong></td>
                    <td class="text-right"><strong><?= htmlspecialchars($moneda, ENT_QUOTES, 'UTF-8') ?><?= $fmtNum($subtotalAsignado) ?></strong></td>
                </tr>
                <tr class="notranslate fv-order-total-row text-success">
                    <td colspan="<?= (int) $colspanLabel ?>"><strong><?= htmlspecialchars((string) ($pageTexts['discount_total'] ?? ''), ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string) ($pageTexts['dispatched'] ?? ''), ENT_QUOTES, 'UTF-8') ?>)</strong></td>
                    <td class="text-right"><strong><?= htmlspecialchars($moneda, ENT_QUOTES, 'UTF-8') ?><?= $fmtNum($descuentoAsignado) ?></strong></td>
                </tr>
                <tr class="notranslate fv-order-total-row fv-order-total-row--grand text-success">
                    <td colspan="<?= (int) $colspanLabel ?>"><strong><?= htmlspecialchars((string) ($pageTexts['total_asigned'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td class="text-right"><strong><?= htmlspecialchars($moneda, ENT_QUOTES, 'UTF-8') ?><?= $fmtNum($totalAsignado) ?></strong></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

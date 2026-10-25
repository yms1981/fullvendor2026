<?php
/**
 * @var $order object
 * @var $sellers
 * @var $pageTexts array
 * @var $format_currency string
 * @var $format_numeric object
 * @var array $orderFormConfig
 */

$moneda = !empty($format_currency) ? $format_currency : null;
if ($moneda === null && !empty($company)) {
    $moneda = $company->currencies[0]->symbol ?? $company->currency->symbol ?? null;
}
if ($moneda === null || $moneda === '') {
    $moneda = '$';
}
$fmt = $format_numeric;
if (!$fmt || !isset($fmt->digits)) {
    $fmt = (object)['digits' => 2, 'radixPoint' => ',', 'groupSeparator' => '.'];
}
$fmtGroup = function_exists('fv_resolve_numeric_group_separator')
    ? fv_resolve_numeric_group_separator($fmt)
    : (string) (($fmt->groupSeparator ?? '') !== '' ? $fmt->groupSeparator : (($fmt->radixPoint ?? '.') === ',' ? '.' : ','));
$fmtNum = function ($n) use ($fmt, $fmtGroup) {
    return number_format((float) $n, (int) $fmt->digits, (string) ($fmt->radixPoint ?? '.'), $fmtGroup);
};

$customer = $order->customer;
$seller = $customer->sellers[0] ?? null;
$pageTexts = is_array($pageTexts ?? null) ? $pageTexts : [];

$discountLabel = '';
if (isset($order->discount_type) && isset($order->discount)) {
    $dVal = (float) $order->discount;
    if ($order->discount_type == 1) {
        $discountLabel = $fmtNum($dVal) . '%';
    } elseif ($order->discount_type == 2) {
        $discountLabel = $moneda . $fmtNum($dVal);
    }
}

$orderNote = null;
$internalNote = null;
if (!empty($order->notes) && is_array($order->notes)) {
    foreach ($order->notes as $note) {
        if (empty($note->type) || empty($note->note)) {
            continue;
        }
        if ($note->type === 'order' && !$orderNote) {
            $orderNote = $note->note;
        } elseif ($note->type === 'internal' && !$internalNote) {
            $internalNote = $note->note;
        }
        if ($internalNote && $orderNote) {
            break;
        }
    }
}
?>
<div id="app" class="fv-order-form-page">
    <div class="container-fluid page-body-wrapper pt-3">
        <div class="main-panel">
            <div class="container-fluid">
                <div class="page-header mb-3">
                    <h3 class="page-title"><?= htmlspecialchars(fv_page_heading_text((isset($order->tipo_d) && $order->tipo_d === 'D') ? ($pageTexts['titulo_edit_order'] ?? $pageTexts['titulo_edit_detail'] ?? '') : ($pageTexts['titulo_edit_credit_note'] ?? $pageTexts['titulo_edit_detail'] ?? '')), ENT_QUOTES, 'UTF-8') ?></h3>
                </div>

                <div class="card fv-order-form-card">
                    <form id="order_form" class="forms-sample"
                        action="<?= company_url('orders/update/'.$order->order_id) ?>" method="post"
                        enctype="multipart/form-data" novalidate>
                        <div class="card-body">
                            <?php $this->load->view('company/order/_order_form_customer', [
                                'pageTexts' => $pageTexts,
                                'orderFormConfig' => $orderFormConfig ?? [],
                                'customers' => $customers ?? [],
                                'sellers' => $sellers,
                                'seller' => $seller,
                                'mode' => 'edit',
                                'order' => $order,
                            ]); ?>
                        </div>

                        <div class="fv-order-lines">
                            <div class="fv-order-lines__table-wrap">
                                <table class="table mb-0">
                                    <thead>
                                        <tr>
                                            <th width="2%">#</th>
                                            <th width="9%"><?= htmlspecialchars((string) ($pageTexts['image'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                                            <th width="22%"><?= htmlspecialchars((string) ($pageTexts['product_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                                            <th width="9%"><?= htmlspecialchars((string) ($pageTexts['sku'] ?? 'SKU'), ENT_QUOTES, 'UTF-8') ?></th>
                                            <th width="8%" class="text-center"><?= htmlspecialchars((string) ($pageTexts['unit_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                                            <th width="8%" class="text-center"><?= htmlspecialchars((string) ($pageTexts['cantidad'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                                            <th width="12%" class="text-center"><?= htmlspecialchars((string) ($pageTexts['sale_price'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                                            <th width="10%" class="text-center"><?= htmlspecialchars((string) ($pageTexts['total'] ?? 'Total'), ENT_QUOTES, 'UTF-8') ?></th>
                                            <th width="18%" class="text-center"><?= htmlspecialchars((string) ($pageTexts['comments_detail'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                                            <th width="4%" class="text-center"><?= htmlspecialchars((string) ($pageTexts['actions'] ?? $pageTexts['action'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (isset($order->order_details)): ?>
                                        <?php $i = 1; ?>
                                        <?php foreach ($order->order_details as $detail): ?>
                                            <tr class="notranslate item" data-product-id="<?= (int) ($detail->product->pro_id ?? 0) ?>">
                                                <td class="align-middle">
                                                    <span class="row-index"><?= $i ?></span>
                                                    <input type="hidden" class="input-id" name="detalle[<?= $i ?>][id]" value="<?= (int) ($detail->detail_id ?? 0) ?>">
                                                    <input type="hidden" name="detalle[<?= $i ?>][row]" value="<?= $i ?>">
                                                    <input type="hidden" class="input-product-id" name="detalle[<?= $i ?>][id_producto]" value="<?= (int) ($detail->product->pro_id ?? 0) ?>">
                                                </td>
                                                <td class="proimg text-center align-middle">
                                                    <div class="position-relative d-inline-block fv-order-line-thumb">
                                                        <?php if (!empty($detail->star)) : ?>
                                                            <a data-toggle="modal" data-target="#modal-histoy"
                                                               data-sku-product="<?= htmlspecialchars((string) ($detail->product->sku ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                               data-name-product="<?= htmlspecialchars((string) ($detail->product->name ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                               data-id="<?= (int) ($detail->product->pro_id ?? 0) ?>"
                                                               style="color:#ffc107;cursor:pointer;z-index:2;"
                                                               class="position-absolute top-0 start-0"
                                                               title="<?= htmlspecialchars((string) ($pageTexts['sold'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                                <i class="fa-solid fa-star"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <?php $this->load->view('company/product/partials/thumbnail', ['listItem' => $detail->product ?? null]); ?>
                                                    </div>
                                                </td>
                                                <td class="align-middle"><?= htmlspecialchars(strtoupper((string) ($detail->product->name ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                                <td class="align-middle"><?= htmlspecialchars((string) ($detail->product->sku ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                                <?php $unitLabel = function_exists('fv_product_unit_label') ? fv_product_unit_label($detail->product ?? null) : ''; ?>
                                                <td class="align-middle text-center notranslate"><?= $unitLabel !== '' ? htmlspecialchars($unitLabel, ENT_QUOTES, 'UTF-8') : '—' ?></td>
                                                <?php
                                                $qtyPlaces = function_exists('fv_product_unit_decimal_places')
                                                    ? fv_product_unit_decimal_places($detail->product ?? null)
                                                    : 0;
                                                $qtyStep = function_exists('fv_unit_quantity_step')
                                                    ? fv_unit_quantity_step($qtyPlaces)
                                                    : '1';
                                                $qtyValue = number_format((float) ($detail->qty ?? 0), $qtyPlaces, '.', '');
                                                ?>
                                                <td class="align-middle text-center order-edit-cell-qty">
                                                    <input type="number" class="form-control text-center input-qty order-edit-qty" name="detalle[<?= $i ?>][qty]" value="<?= htmlspecialchars($qtyValue, ENT_QUOTES, 'UTF-8') ?>" min="0" step="<?= htmlspecialchars($qtyStep, ENT_QUOTES, 'UTF-8') ?>" style="min-width:9rem;">
                                                </td>
                                                <td class="align-middle order-edit-cell-price">
                                                    <div class="d-flex flex-nowrap align-items-center">
                                                        <div class="input-group input-group-sm" style="min-width:12rem;">
                                                            <div class="input-group-prepend"><span class="input-group-text"><?= htmlspecialchars($moneda, ENT_QUOTES, 'UTF-8') ?></span></div>
                                                            <input class="form-control text-right input-price" name="detalle[<?= $i ?>][precio]" type="text" value="<?= $fmtNum($detail->sale_price ?? 0) ?>">
                                                        </div>
                                                        <div class="fixed-price-cell" style="display:none!important;">
                                                            <label class="d-flex align-items-center mb-0 small ml-2 flex-shrink-0">
                                                                <input type="checkbox" class="check-fixed-price mr-1" name="detalle[<?= $i ?>][precio_fijo]" id="fixed_price_<?= $i ?>" <?= !empty($detail->fixed_pricedate) ? 'checked' : '' ?>>
                                                                <span><u><?= htmlspecialchars((string) ($pageTexts['fixed_price'] ?? ''), ENT_QUOTES, 'UTF-8') ?></u></span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="align-middle text-center">
                                                    <strong><?= htmlspecialchars($moneda, ENT_QUOTES, 'UTF-8') ?><span class="product-total"><?= $fmtNum(($detail->qty ?? 0) * ($detail->sale_price ?? 0)) ?></span></strong>
                                                </td>
                                                <td class="align-middle">
                                                    <textarea rows="2" class="form-control input-comment" name="detalle[<?= $i ?>][comentarios]" style="min-height:3.5rem;"><?= htmlspecialchars(trim((string) ($detail->comments ?? '')), ENT_QUOTES, 'UTF-8') ?></textarea>
                                                </td>
                                                <td class="align-middle text-center">
                                                    <button type="button" class="btn btn-danger btn-sm btn-remove-product" title="<?= htmlspecialchars((string) ($pageTexts['tol-delete'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-detail-id="<?= (int) ($detail->detail_id ?? 0) ?>" data-row-index="<?= $i ?>">
                                                        <i class="fa fa-trash" aria-hidden="true"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php $i++; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                        <tr class="notranslate fv-order-total-row">
                                            <td colspan="7"><strong><?= htmlspecialchars((string) ($pageTexts['sub_total'] ?? 'Subtotal'), ENT_QUOTES, 'UTF-8') ?></strong></td>
                                            <td class="text-right"><strong><?= htmlspecialchars($moneda, ENT_QUOTES, 'UTF-8') ?><strong id="sub_total"><?= $fmtNum($order->subtotal ?? 0) ?></strong></strong></td>
                                            <td></td>
                                        </tr>
                                        <tr class="notranslate fv-order-total-row">
                                            <td colspan="7"><strong><?= htmlspecialchars((string) ($pageTexts['discount_total'] ?? ''), ENT_QUOTES, 'UTF-8') ?> (<span id="span_discount"><?= htmlspecialchars($discountLabel, ENT_QUOTES, 'UTF-8') ?></span>)</strong></td>
                                            <td class="text-right"><strong><?= htmlspecialchars($moneda, ENT_QUOTES, 'UTF-8') ?><strong id="total_descuento"><?= $fmtNum($order->descuento ?? 0) ?></strong></strong></td>
                                            <td></td>
                                        </tr>
                                        <tr class="notranslate fv-order-total-row fv-order-total-row--grand">
                                            <td colspan="7"><strong><?= htmlspecialchars((string) ($pageTexts['price_total'] ?? 'Total'), ENT_QUOTES, 'UTF-8') ?></strong></td>
                                            <td class="text-right"><strong><?= htmlspecialchars($moneda, ENT_QUOTES, 'UTF-8') ?><strong id="total_orden"><?= $fmtNum($order->total ?? 0) ?></strong></strong></td>
                                            <td></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="fv-order-notes row pl-2 pr-2">
                            <div class="col-md-6">
                                <label><?= htmlspecialchars((string) ($pageTexts['order_notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
                                <textarea class="form-control" rows="4" name="order_comments"><?= htmlspecialchars((string) ($orderNote ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label><?= htmlspecialchars((string) ($pageTexts['order_internal'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
                                <textarea class="form-control" rows="4" name="internal_comments"><?= htmlspecialchars((string) ($internalNote ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php $this->load->view('company/order/_order_form_actions', [
        'pageTexts' => $pageTexts,
        'mode' => 'edit',
        'planLimitBlocked' => false,
        'orderId' => (int) ($order->order_id ?? 0),
    ]); ?>
    <?php $this->load->view('company/order/modal_addproduct', ['pageTexts' => $pageTexts, 'moneda' => $moneda, 'format_numeric' => $fmt]); ?>
    <?php $this->load->view('company/order/modal_history', ['lang_order' => $pageTexts ?? []]); ?>
    <?php $this->load->view('company/order/modal_imprimirorder', ['pageTexts' => $pageTexts, 'order' => $order]); ?>
    <div id="order-edit-deleted-ids"></div>
<script>
window.order_id = '<?= (int) ($order->order_id ?? 0) ?>';
const order_id = window.order_id;

function printOrder() {
    $('#exampleModal').modal('show');
    return false;
}
$(document).on('click', '#btn-con', function () {
    $('#form-imprimir_orden #notas').val('con');
    $('#form-imprimir_orden').submit();
});
$(document).on('click', '#btn-sin', function () {
    $('#form-imprimir_orden #notas').val('sin');
    $('#form-imprimir_orden').submit();
});

$(document).on('show.bs.modal', '#modal-histoy', function (e) {
    var $trigger = $(e.relatedTarget);
    var proId = $trigger.data('id');
    var productName = $trigger.data('name-product') || '';
    $('#modal-histoy').find('.product-name-vendido').text(productName);
    $('#modal-histoy').find('.table tbody').html('');
    if (!proId) {
        return;
    }
    $.ajax({
        url: '<?= company_url('orders/getHistorial') ?>',
        type: 'post',
        data: {
            pro_id: proId,
            order_id: order_id
        },
        dataType: 'json',
        success: function (response) {
            $('#modal-histoy').find('.table tbody').html((response && response.tbody) ? response.tbody : '');
        }
    });
});

function copy_saleprice() {
    var salePrice = event.currentTarget.getAttribute('data-copy-price');
    if (typeof copiarAlPortapapeles === 'function') {
        copiarAlPortapapeles(salePrice);
    }
    if (typeof swal === 'function') {
        swal({
            title: <?= json_encode((string) ($pageTexts['copied'] ?? ''), JSON_UNESCAPED_UNICODE) ?>,
            icon: 'success',
            button: {
                text: 'OK',
                value: true,
                visible: true,
                className: 'btn btn-success'
            }
        });
    }
}
</script>

<script>
window.orderListUrl = <?= json_encode(company_url('orders')) ?>;
window.FORMAT_NUMERIC_ORDER = <?= json_encode([
    'digits' => (int) $fmt->digits,
    'radixPoint' => $fmt->radixPoint ?? ',',
    'groupSeparator' => $fmtGroup,
], JSON_UNESCAPED_UNICODE) ?>;
window.orderEditLang = <?= json_encode([
    'estas_seguro_eliminar' => $pageTexts['estas_seguro_eliminar'] ?? '',
    'eliminado_exito' => $pageTexts['eliminado_exito'] ?? '',
    'producto_eliminado' => $pageTexts['producto_eliminado'] ?? '',
    'cancel' => $pageTexts['cancel'] ?? '',
    'confirmar' => $pageTexts['confirmar'] ?? '',
    'tol-delete' => $pageTexts['tol-delete'] ?? '',
    'mensaje_success' => $pageTexts['mensaje_success'] ?? '',
    'error_actualizar' => $pageTexts['error_orden'] ?? '',
    'updating' => $pageTexts['updating'] ?? '',
]) ?>;

(function() {
    var lang = window.orderEditLang || {};
    function showSwal(opts) {
        if (typeof Swal !== 'undefined' && Swal.fire) {
            Swal.fire(opts);
        } else if (typeof swal === 'function') {
            swal(opts);
        } else {
            alert(opts.title || opts.text || '');
        }
    }

    $(document).on('click', '.btn-remove-product', function() {
        var $btn = $(this);
        var $row = $btn.closest('tr.item');
        var detailId = $btn.data('detail-id');
        function doRemove() {
            if (detailId) {
                $('#order-edit-deleted-ids').append($('<input type="hidden" name="detalle_eliminar[]">').val(detailId));
            }
            $row.remove();
            reindexOrderDetailRows();
            if (typeof window.calcularTotales === 'function') window.calcularTotales();
        }
        if (typeof Swal !== 'undefined' && Swal.fire) {
            Swal.fire({ title: lang.estas_seguro_eliminar, icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonText: lang.cancel, confirmButtonText: lang.confirmar })
                .then(function(result) { if (result && result.isConfirmed) doRemove(); });
        } else if (confirm(lang.estas_seguro_eliminar)) {
            doRemove();
        }
    });

    function reindexOrderDetailRows() {
        var idx = 1;
        $('.table tbody tr.item').each(function() {
            var $row = $(this);
            $row.find('.input-id').attr('name', 'detalle[' + idx + '][id]');
            $row.find('input[name*="[row]"]').attr('name', 'detalle[' + idx + '][row]').val(idx);
            $row.find('.input-product-id').attr('name', 'detalle[' + idx + '][id_producto]');
            $row.find('.input-qty').attr('name', 'detalle[' + idx + '][qty]');
            $row.find('.input-price').attr('name', 'detalle[' + idx + '][precio]');
            $row.find('.check-fixed-price').attr('name', 'detalle[' + idx + '][precio_fijo]').attr('id', 'fixed_price_' + idx);
            $row.find('.input-comment').attr('name', 'detalle[' + idx + '][comentarios]');
            $row.find('.row-index').text(idx);
            idx++;
        });
    }

    function parseNumFromInput(val) {
        return typeof window.fvParseLocaleNumber === 'function'
            ? window.fvParseLocaleNumber(val)
            : (parseFloat(String(val).replace(/[^\d.,-]/g, '').replace(/\./g, '').replace(',', '.')) || 0);
    }

    $('#order_form').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $('#send-form');
        var originalText = $btn.html();
        var detalle = [];
        $('.table tbody tr.item').each(function() {
            var $row = $(this);
            detalle.push({
                id: $row.find('.input-id').val() || null,
                id_producto: $row.find('.input-product-id').val(),
                qty: parseNumFromInput($row.find('.input-qty').val()),
                precio: parseNumFromInput($row.find('.input-price').val()),
                precio_fijo: $row.find('.check-fixed-price').is(':checked') ? 1 : 0,
                comentarios: $row.find('.input-comment').val() || ''
            });
        });
        var detalleEliminar = [];
        $('#order-edit-deleted-ids input[name="detalle_eliminar[]"]').each(function() {
            var v = $(this).val();
            if (v) detalleEliminar.push(v);
        });
        var payload = {
            customer_id: $form.find('[name="customer_id"]').val(),
            user_id: $form.find('[name="user_id"]').val(),
            seller_id: $form.find('[name="user_id"]').val(),
            discount_type: $form.find('[name="discount_type"]').val(),
            discount: parseNumFromInput($form.find('[name="discount"]').val()),
            order_comments: $form.find('[name="order_comments"]').val() || '',
            internal_comments: $form.find('[name="internal_comments"]').val() || '',
            internal_notes: $form.find('[name="internal_comments"]').val() || '',
            detalle: detalle,
            detalle_eliminar: detalleEliminar
        };
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status"></span> ' + (lang.updating || ''));
        $.ajax({
            url: $form.attr('action'),
            type: 'POST',
            data: { data: JSON.stringify(payload) },
            dataType: 'json'
        }).done(function(resp) {
            showSwal({ title: (resp && resp.message) ? resp.message : (lang.mensaje_success || ''), icon: 'success', timer: 1500, showConfirmButton: false });
            setTimeout(function() { window.location.href = window.orderListUrl || ''; }, 1600);
        }).fail(function(xhr) {
            var msg = lang.error_actualizar;
            try { var j = xhr.responseJSON; if (j && (j.message || j.error)) msg = j.message || j.error; } catch (e) {}
            showSwal({ title: msg, icon: 'error' });
        }).always(function() {
            $btn.prop('disabled', false).html(originalText);
        });
    });
})();
</script>
</div>

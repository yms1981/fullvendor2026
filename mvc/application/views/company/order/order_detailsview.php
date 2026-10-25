<?php
/**
 * Vista detalle de orden — layout moderno compatible con add/edit.
 *
 * @var object $order
 * @var array $pageTexts
 * @var array $orderFormConfig
 * @var object|null $seller
 * @var string $format_currency
 * @var object $format_numeric
 */
$order = $order ?? null;
$pageTexts = is_array($pageTexts ?? null) ? $pageTexts : [];
$moneda = !empty($format_currency) ? $format_currency : '$';
$fmt = $format_numeric ?? null;
if (!$fmt || !isset($fmt->digits)) {
    $fmt = (object) ['digits' => 2, 'radixPoint' => ',', 'groupSeparator' => '.'];
}

$address = $order->address ?? null;
$parts = [];
$shippingNote = null;
$orderNote = null;

if (!empty($order->notes) && is_array($order->notes)) {
    foreach ($order->notes as $note) {
        if (empty($note->type) || empty($note->note)) {
            continue;
        }
        if ($note->type === 'shipping' && !$shippingNote) {
            $shippingNote = $note->note;
        } elseif ($note->type === 'internal' && !$orderNote) {
            $orderNote = $note->note;
        }
        if ($shippingNote && $orderNote) {
            break;
        }
    }
}

if ($address) {
    $base = trim(($address->street ?? '') . ' ' . ($address->street_number ?? ''));
    if ($base !== '') {
        $parts[] = ($pageTexts['address_catalog'] ?? '') . ' ' . $base;
    }
    if (!empty($address->zone)) {
        $parts[] = ($pageTexts['zone'] ?? '') . ': ' . $address->zone;
    }
    if (!empty($address->zipcode)) {
        $parts[] = ($pageTexts['zipcode_catalog'] ?? '') . ' ' . $address->zipcode;
    }
    if (!empty($address->city->name)) {
        $parts[] = ($pageTexts['city_catalog'] ?? '') . ' ' . $address->city->name;
    }
    if (!empty($address->state->name)) {
        $parts[] = ($pageTexts['state_catalog'] ?? '') . ' ' . $address->state->name;
    }
    if (!empty($address->country->name)) {
        $parts[] = ($pageTexts['country'] ?? '') . ': ' . $address->country->name;
    }
    if ($shippingNote) {
        $parts[] = ($pageTexts['shipping_catalog'] ?? '') . ' ' . $shippingNote;
    }
}
if ($orderNote) {
    $orderNote = ($pageTexts['comments_catalog'] ?? '') . ' ' . $orderNote;
}
?>
<div id="app" class="fv-order-form-page fv-order-view-page">
    <div class="container-fluid page-body-wrapper pt-3">
        <div class="main-panel">
            <div class="container-fluid">
                <div class="page-header mb-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
                    <h3 class="page-title mb-0"><?= htmlspecialchars(fv_page_heading_text($pageTexts['titulo_view_previa_detail'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h3>
                </div>

                <div class="fv-order-form-card">
                    <?php $this->load->view('company/order/_order_view_toolbar', [
                        'order' => $order,
                        'pageTexts' => $pageTexts,
                    ]); ?>

                    <?php $this->load->view('company/order/_order_form_customer', [
                        'pageTexts' => $pageTexts,
                        'orderFormConfig' => $orderFormConfig ?? [],
                        'sellers' => [],
                        'seller' => $seller ?? null,
                        'mode' => 'view',
                        'order' => $order,
                    ]); ?>

                    <?php $this->load->view('company/order/_order_view_lines', [
                        'order' => $order,
                        'pageTexts' => $pageTexts,
                        'moneda' => $moneda,
                        'fmt' => $fmt,
                        'show_dispatched_info' => !empty($show_dispatched_info),
                        'dispatched_warehouse_display' => $dispatched_warehouse_display ?? null,
                    ]); ?>

                    <div class="fv-order-notes row pl-2 pr-2">
                        <div class="col-md-6">
                            <label><?= htmlspecialchars((string) ($pageTexts['order_notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
                            <div class="fv-order-readonly-notes"><?= !empty($parts) ? htmlspecialchars(implode(', ', $parts), ENT_QUOTES, 'UTF-8') : '—' ?></div>
                        </div>
                        <div class="col-md-6">
                            <label><?= htmlspecialchars((string) ($pageTexts['order_internal'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
                            <div class="fv-order-readonly-notes"><?= $orderNote !== null && $orderNote !== '' ? htmlspecialchars((string) $orderNote, ENT_QUOTES, 'UTF-8') : '—' ?></div>
                        </div>
                    </div>
                </div>

                <div class="fv-order-history mt-4">
                    <div class="page-header mb-3">
                        <h3 class="page-title"><?= htmlspecialchars(fv_page_heading_text($pageTexts['historial_orden'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h3>
                    </div>
                    <div class="fv-order-sheet fv-order-history__card">
                        <ul class="timeline pl-3 mb-0">
                            <?php $counter = 0; ?>
                            <?php foreach (($order->order_status_histories ?? []) as $h): ?>
                                <?php $counter++; ?>
                                <li class="timeline-item timeline-item-transparent">
                                    <span class="timeline-point-wrapper"><span class="timeline-point" style="background: <?= htmlspecialchars((string) ($h->status->color ?? '#94a3b8'), ENT_QUOTES, 'UTF-8') ?>;"></span></span>
                                    <div class="timeline-event">
                                        <div class="timeline-header mb-1">
                                            <h6 class="mb-0">
                                                <?= htmlspecialchars((string) ($pageTexts['orden_cambio'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                                <u><?= htmlspecialchars((string) ($h->status->name_status_spanish ?? ''), ENT_QUOTES, 'UTF-8') ?></u>
                                                &nbsp;&nbsp;<i class="bx bxs-user"></i>
                                                <?= htmlspecialchars((string) ($pageTexts['user'] ?? ''), ENT_QUOTES, 'UTF-8') ?>:
                                                <?= htmlspecialchars((string) ($h->user->name ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                            </h6>
                                            <h6 class="text-muted"><?= htmlspecialchars(fv_format_display_datetime($h->created_at ?? 'now'), ENT_QUOTES, 'UTF-8') ?></h6>
                                        </div>
                                    </div>
                                </li>
                                <?php if ($counter === count($order->order_status_histories ?? [])): ?>
                                    <li class="timeline-end-indicator"><i class="bx bx-check-circle"></i></li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$this->load->view('company/order/modal_deposito');
$this->load->view('company/order/modal_sendorder');
$this->load->view('company/order/modal_imprimirorder');
$this->load->view('company/order/modal_history', ['lang_order' => $pageTexts ?? []]);
$this->load->view('company/order/modal_change_status');
$this->load->view('company/order/modal_duplicate_order', [
    'order' => $order,
    'customers' => $customers ?? [],
    'pageTexts' => $pageTexts ?? [],
    'countryDataMap' => $countryDataMap ?? [],
]);
$this->load->view('company/order/modal_exportacion');
?>

<script>
window.order_id = '<?= (int) ($order->order_id ?? 0) ?>';
const order_id = window.order_id;
window.fvOrderExportId = <?= (int) ($order->order_id ?? 0) ?>;
window.fvOrderExportLang = <?= json_encode([
    'bnExport' => $pageTexts['bnExport'] ?? ($pageTexts['bnExport_modal'] ?? ''),
    'bnExport_modal' => $pageTexts['bnExport_modal'] ?? '',
    'alert_select_at_least_one' => $pageTexts['alert_select_at_least_one'] ?? '',
    'invalid_request' => $pageTexts['invalid_request'] ?? '',
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

function deleteOrderdetails(ordetlId) {
    swal({
        title: '<?= addslashes((string) ($pageTexts['estas_seguro_eliminar'] ?? '')) ?>',
        icon: 'warning',
        buttons: {
            cancel: { text: 'No', value: false, visible: true, className: 'sweet-danager', closeModal: true },
            confirm: { text: '<?= addslashes((string) ($pageTexts['order_yes'] ?? '')) ?>', value: true, visible: true, className: 'sweet-warning', closeModal: true }
        }
    }).then(function (willDelete) {
        if (willDelete) {
            window.location.href = '<?= company_url('orders/delete_orderOne/') ?>' + ordetlId;
        }
    });
}

$(document).ready(function () {
    if (typeof window.fvInitOrderImagePopovers === 'function') {
        window.fvInitOrderImagePopovers(document);
    } else {
        $('[data-toggle="popover-hover"]').popover({
            html: true,
            trigger: 'hover',
            placement: 'right',
            sanitize: false,
            content: function () {
                var $el = $(this);
                var img = $el.attr('data-img') || '';
                var $thumb = $el.find('img').first();
                if ($thumb.length) {
                    var liveSrc = $thumb.attr('src') || '';
                    if (liveSrc && liveSrc.indexOf('data:') !== 0) {
                        img = liveSrc;
                    }
                }
                return img ? '<img src="' + img + '" style="max-width:220px;" />' : '';
            }
        });
    }
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

function printOrder() {
    $('#exampleModal').modal('show');
    return true;
}

$(document).on('click', '#btn-con', function () {
    $('#form-imprimir_orden #notas').val('con');
    $('#form-imprimir_orden').submit();
});
$(document).on('click', '#btn-sin', function () {
    $('#form-imprimir_orden #notas').val('sin');
    $('#form-imprimir_orden').submit();
});

function ajaxRequest(options) {
    $.ajax({
        url: options.url,
        type: options.method ?? 'POST',
        data: options.data ?? {},
        dataType: 'json',
        beforeSend: function () {
            if (options.button) {
                $(options.button).prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm" role="status"></span> ' +
                    (options.loadingText ?? 'Loading...')
                );
            }
        },
        success: function (response) {
            if (options.button) {
                $(options.button).prop('disabled', false).html(options.defaultText ?? 'Send');
            }
            swal({
                title: response.message ?? 'Success',
                icon: 'success'
            }).then(function () {
                window.history.go(-1);
            });
        },
        error: function (response) {
            if (options.button) {
                $(options.button).prop('disabled', false).html(options.defaultText ?? 'Send');
            }
            var title = response.responseJSON?.message ?? 'Error';
            var errors = response.responseJSON?.errors;
            var errorsMessages = Object.values(errors || {}).flat().join('\n') || null;
            swal({ title: title, text: errorsMessages, icon: 'error' });
        }
    });
}

$(document).on('submit', '#form_enviar_orden', function (e) {
    e.preventDefault();
    ajaxRequest({
        url: $(this).attr('action'),
        data: $(this).serializeArray(),
        method: 'POST',
        button: '#btn-send-email',
        loadingText: '<?= addslashes((string) ($pageTexts['order_sending'] ?? '')) ?>',
        defaultText: '<?= addslashes((string) ($pageTexts['send'] ?? '')) ?>'
    });
});

$(document).on('click', '.btn-opciones_detalles', function (e) {
    e.preventDefault();
    ajaxRequest({ url: $(this).attr('href'), method: 'GET' });
});

$(document).on('submit', '#submit_order_asigned', function (e) {
    e.preventDefault();
    ajaxRequest({
        url: $(this).attr('action'),
        data: $(this).serializeArray(),
        method: 'POST'
    });
});
</script>

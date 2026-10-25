<?php

$ahora = DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));
$v_imagen = $ahora->format("YmdHisu");
$moneda = '$';

?>

<div class="container-fluid page-body-wrapper">
    <div class="main-panel">
        <div class="container-fluid pt-3">

            <div class="row">
                <div class="col-md-6">
                    <div class="page-header">
                        <h3 class="page-title"><?= $lang_cart['detail_cart'] ?></h3>
                    </div>
                </div>
                <div class="col-md-6 text-right">

                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="boxsrch">
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <button type="button" id="btn_copy_link" tabindex="-1"
                                        class="btn btn-success mt-4 mt-sm-0 btn-icon-text px-3 mb-1"
                                        onclick="copyToClip()">
                                        <i class="fa-solid fa-copy"></i>
                                        <?= htmlspecialchars((string) ($lang_cart['copy_link'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </button>
                                    <a href="<?= company_url('cart/convert_carttoorder/' . (int) ($cart_info->cart_id ?? 0)) ?>"
                                        id="btn_submit_order"
                                        class="btn btn-success mt-4 mt-sm-0 btn-icon-text px-3 mb-1 btn-submit-cart-order"
                                        data-has-items="<?= ! empty($details_car) ? '1' : '0' ?>"
                                        data-redirect="<?= company_url('cart/abandoned') ?>"
                                        data-loader="<?= htmlspecialchars((string) ($lang_cart['submiting_order'] ?? $lang_order['order_sending'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="fa-solid fa-paper-plane"></i>
                                        <?= htmlspecialchars((string) ($lang_cart['submit_order'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                    <button type="button" id="btn_notify" tabindex="-1"
                                        class="btn btn-success mt-4 mt-sm-0 btn-icon-text px-3 mb-1">
                                        <i class="fa-solid fa-envelope"></i>
                                        <?= htmlspecialchars((string) ($lang_cart['notify_customer'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </button>
                                    <button type="button" tabindex="-1"
                                        class="btn btn-success mt-4 mt-sm-0 btn-icon-text px-3 mb-1 btn-delete-cart"
                                        data-url="<?= company_url('cart/delete_cart/' . (int) ($cart_info->cart_id ?? 0)) ?>"
                                        data-redirect="<?= company_url('cart/abandoned') ?>">
                                        <i class="mdi mdi-delete"></i>
                                        <?= htmlspecialchars((string) ($lang_cart['delete_thiscart'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </button>
                                    <button type="button" tabindex="-1" class="btn btn-danger mt-4 mt-sm-0 btn-icon-text px-3 mb-1"
                                        onclick="javascript:history.go(-1)">
                                        <i class="fa-solid fa-arrow-left"></i>
                                        <?= htmlspecialchars((string) ($lang_cart['back'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </button>
                                </div>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <p class="mb-1"><strong><?=$lang_order['customer'] ?>:</strong><span
                                        class="notranslate"> <?=$cart_info->customer?></span></p>
                                <p class="mb-1"><strong><?= $lang_order['phone_order'] ?>:
                                    </strong><span class="notranslate"> <?=$cart_info->phone?></span></p>
                                <p class="mb-1"><strong><?= $lang_order['seller_order'] ?>:
                                    </strong><span class="notranslate"> <?=@$cart_info->seller ?></span></p>

                            </div>
                            <div class="col-lg-6 mb-3 text-right">
                                <p class="mb-1"><strong><?= $lang_cart['creado'] ?>:
                                    </strong><?=date($format_date->format_php,strtotime(@$cart_info->created))?></p>
                                <p class="mb-1"><strong><?= $lang_cart['cart'] ?> Id: </strong><?= $cart_info->cart_id ?>
                                </p>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th class="text-center"><?= $lang_order['image'] ?></th>
                                            <th width="30%" class="text-left"><?= $lang_order['product_name'] ?></th>
                                            <th width="15%">Sku</th>
                                            <th width="13%" class="text-center"><?= $lang_order['cantidad'] ?></th>
                                            <th width="10%" class="text-right"><?= $lang_order['sale_price'] ?></th>
                                            <th width="10%" class="text-right">Total</th>
                                            <!-- <th width="5%" class="text-center"><?= $lang_order['action'] ?></th> -->
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $i = 0;?>
                                        <?php foreach($details_car as $pro){ $i=$i+1; ?>
                                        <tr>
                                            <td><br><?= $i ?></td>
                                            <td class="text-center align-middle">
                                                <div class="position-relative d-inline-block">
                                                    <?php
                                                    $mostrar_estrella = false;
                                                    if (in_array($pro->product_id, array_column($productos_pedidos, 'product_id'))) {
                                                        $mostrar_estrella = true;
                                                    }
                                                    if ($mostrar_estrella) :
                                                    ?>
                                                    <a data-toggle="modal" data-id="<?= (int) $pro->product_id ?>"
                                                        data-target="#modal-histoy"
                                                        style="color: #ffc107;cursor: pointer; z-index: 2;"
                                                        class="position-absolute top-0 start-0"
                                                        data-toggle="tooltip"
                                                        data-placement="top" title="<?= htmlspecialchars((string) ($lang_catalogo['sold'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                                        <i class="fa-solid fa-star"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                    <?php $this->load->view('company/cart/partials/line_image', ['line' => $pro]); ?>
                                                </div>
                                            </td>
                                            <td class="text-left align-middle">
                                                <?= htmlspecialchars((string) ($pro->name ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                                <?= ($pro->status == 0) ? '<span class="badge badge-danger">' . htmlspecialchars((string) ($lang_order['producto_innactivo'] ?? ''), ENT_QUOTES, 'UTF-8') . '</span>' : '' ?>
                                            </td>
                                            <td style="vertical-align: top;">
                                                <br>
                                                <a href="<?= company_url('products/edit/'.(int) ($pro->product_id ?? 0)) ?>"
                                                    title="<?= htmlspecialchars((string) ($lang_order['edit_tol'] ?? $lang_order['edit'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($pro->sku ?? ''), ENT_QUOTES, 'UTF-8') ?></a>

                                                <?php if($pro->barcode != null || $pro->barcode != ''){?>
                                                <a title="Barcode: <?= $pro->barcode ?>"><img
                                                        src="<?= base_url('images/codbarra.png')?>"
                                                        alt="<?= $pro->barcode ?>" class="img-fluid img-thumbnail"
                                                        style="width: 25px; height: 25px;"></a>
                                                <?php }?>
                                            </td>
                                            <td class="text-center">
                                                <br><strong><span><?= $pro->qty ?></span></strong>
                                            </td>
                                            <td class="text-right">
                                                <br>
												<?php
													$precio = number_format($pro->precio_grupo, $format_numeric->digits, $format_numeric->radixPoint,$format_numeric->groupSeparator);
													$importe = number_format($pro->importe, $format_numeric->digits, $format_numeric->radixPoint,$format_numeric->groupSeparator);
												?>
                                                <strong><span>&nbsp;&nbsp;</span>
												<span><?= $moneda. $precio ?></span></strong>

                                            </td>
                                            <td class="text-right">
												<br>
                                                <strong><span>&nbsp;&nbsp;</span>
                                                    <?= $moneda. $importe ?>
                                                </strong>
                                            </td>
                                        </tr>

                                        <?php if ($pro->comment != null || $pro->comment != '') {
												?>
                                        <tr>
                                            <td colspan="4"
                                                style="border:none !important;border-bottom: 2px solid #e4e9f0 !important;">
                                            </td>
                                            <td colspan="3" class="text-wrap text-right"
                                                style="overflow-wrap: break-word; border:none !important;border-bottom: 2px solid #e4e9f0 !important;">
                                                <strong><?= $lang_order['comments'] ?>:</strong> <?= $pro->comment ?>
                                            </td>
                                        </tr>

                                        <?php }?>
                                        <?php } ?>
                                        <tr>
                                            <td colspan="6">
                                                <strong><?= htmlspecialchars((string) ($lang_order['sub_total'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                            </td>
                                            <td colspan="1" style="text-align: right;">
                                                <strong><?= $moneda . htmlspecialchars((string) ($details_total['sub_total'] ?? '0.00'), ENT_QUOTES, 'UTF-8') ?></strong>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="6">
                                                <strong><?= htmlspecialchars((string) ($lang_order['discount_total'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                            </td>
                                            <td colspan="1" style="text-align: right;">
                                                <strong><?= $moneda . htmlspecialchars((string) ($details_total['discount_total'] ?? '0.00'), ENT_QUOTES, 'UTF-8') ?></strong>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="6">
                                                <strong><?= htmlspecialchars((string) ($lang_order['total'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                            </td>
                                            <td colspan="1" style="text-align: right;">
                                                <strong><?= $moneda . htmlspecialchars((string) ($details_total['total_orden'] ?? '0.00'), ENT_QUOTES, 'UTF-8') ?></strong>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal history-->
<div class="modal fade" id="modal-histoy" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel"><?= $lang_order['product_sold'] ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th scope="col"><?= $lang_order['fecha_creacion_order'] ?></th>
                            <th scope="col"><?= $lang_order['modal-order'] ?> Id</th>
                            <th scope="col" class="text-right"><?= $lang_order['sale_price'] ?></th>
                        </tr>
                    </thead>
                    <tbody>


                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" data-dismiss="modal" aria-label="Close"
                    class="btn btn-danger"><?= $lang_order['cerrar'] ?></button>
            </div>
        </div>
    </div>
</div>
<?php
	$this->load->view('company/cart/modal_notifycustomer');
?>


<script>
window.fvOpenCartLang = <?= json_encode([
    'copied' => (string) ($lang_order['copied'] ?? ''),
    'copy_error' => (string) ($lang_cart['copy_error'] ?? ''),
    'link_expired' => (string) ($lang_cart['link_expired'] ?? ''),
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
    'notify_sent' => (string) ($lang_cart['notify_sent'] ?? ''),
    'notify_error' => (string) ($lang_cart['notify_error'] ?? ''),
    'notify_email_required' => (string) ($lang_cart['notify_email_required'] ?? ''),
], JSON_UNESCAPED_UNICODE) ?>;
const link = <?= json_encode((string) ($link ?? ''), JSON_UNESCAPED_UNICODE) ?>;
const cart_id = <?= json_encode((string) ($cart_info->cart_id ?? ''), JSON_UNESCAPED_UNICODE) ?>;

$(document).ready(function() {

    $('[data-toggle="popover-hover"]').popover({
        html: true,
        trigger: 'hover',
        placement: 'left',
        content: function() {
            return '<img src="' + $(this).data('img') + '" class="img-thumbnail" />';
        }
    });

    $(document).on('show.bs.modal', '#modal-histoy', function(e) {

        const pro_id = $(e.relatedTarget).data('id');

        $.ajax({
            url: '<?= company_url('orders/getHistorial') ?>',
            type: 'post',
            data: {
                pro_id: pro_id,
                cart_id: cart_id
            },
            dataType: 'json',
            success: function(response) {
                $('#modal-histoy').find('.table tbody').html(response.tbody)
            }
        })
    })

});

function copyToClip() {
    var copiedTitle = (window.fvOpenCartLang && window.fvOpenCartLang.copied) ? window.fvOpenCartLang.copied : '';
    var errorTitle = (window.fvOpenCartLang && window.fvOpenCartLang.copy_error) ? window.fvOpenCartLang.copy_error : '';
    var okText = (window.fvOpenCartLang && window.fvOpenCartLang.ok) ? window.fvOpenCartLang.ok : '';
    var value = (typeof link === 'string') ? link : '';

    function showCopyResult(ok, title, manualLink) {
        if (typeof swal !== 'function') {
            if (!ok && manualLink) {
                window.prompt(title || '', manualLink);
            }
            return;
        }
        swal({
            title: title || '',
            text: manualLink || '',
            icon: ok ? 'success' : 'error',
            button: {
                text: okText,
                value: true,
                visible: true,
                className: ok ? 'btn btn-success' : 'btn btn-danger'
            }
        });
        if (!ok && manualLink) {
            // En HTTP a veces el navegador bloquea el portapapeles: dejar el link seleccionable.
            setTimeout(function () {
                window.prompt(title || '', manualLink);
            }, 200);
        }
    }

    function fallbackCopy(text) {
        var input = document.createElement('input');
        input.type = 'text';
        input.value = text;
        input.setAttribute('readonly', 'readonly');
        input.style.cssText = 'position:fixed;top:0;left:0;width:2px;height:2px;padding:0;border:none;opacity:0;';
        document.body.appendChild(input);
        input.focus();
        input.select();
        input.setSelectionRange(0, input.value.length);
        var ok = false;
        try {
            ok = document.execCommand('copy');
        } catch (e) {
            ok = false;
        }
        document.body.removeChild(input);
        return ok;
    }

    if (!value) {
        showCopyResult(false, errorTitle);
        return;
    }

    // En HTTP ("No seguro") Clipboard API no está disponible; hay que copiar en el mismo click.
    if (window.isSecureContext && navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
        navigator.clipboard.writeText(value).then(function () {
            showCopyResult(true, copiedTitle);
        }).catch(function () {
            if (fallbackCopy(value)) {
                showCopyResult(true, copiedTitle);
            } else {
                showCopyResult(false, errorTitle, value);
            }
        });
        return;
    }

    if (fallbackCopy(value)) {
        showCopyResult(true, copiedTitle);
    } else {
        showCopyResult(false, errorTitle, value);
    }
}
</script>

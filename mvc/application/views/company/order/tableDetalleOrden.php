<?php
/**
 * @var $data Object Order info
 * @var $info_company Object Company info
 * @var $format_numeric object|null Formato numérico de la empresa (Company_Controller / API company/format-numeric)
 * @var $lang_json Array The lang translations for every translatable string
 */

$lang_order = isset($lang_json['cmp_orders']['lang']) ? $lang_json['cmp_orders']['lang'] : [];
$lang_catalogo = isset($lang_json['cmp_catalog']['lang']) ? $lang_json['cmp_catalog']['lang'] : [];

// Usar el format_numeric que determina la app (getDataForView / API) o el de la company si se pasó desde la orden
$format_numeric = isset($format_numeric) && (is_object($format_numeric) && isset($format_numeric->digits)) ? $format_numeric : ($info_company->format_numeric ?? $info_company->formatNumeric ?? null);
if (!$format_numeric || !isset($format_numeric->digits)) {
    $format_numeric = (object)['digits' => 2, 'radixPoint' => '.', 'groupSeparator' => ','];
}
$moneda = '$';
if ($info_company) {
    $moneda = (isset($info_company->currencies[0]) && isset($info_company->currencies[0]->symbol))
        ? $info_company->currencies[0]->symbol
        : ($info_company->currency->symbol ?? '$');
}
$orderDetails = $data->order_details ?? $data->orderDetails ?? [];

//TOTALS (valores numéricos formateados con format_numeric de la empresa)
$total_orden_raw = $data->total ?? $data->total_amount ?? 0;
$discount_total_raw = $data->descuento ?? $data->discount ?? 0;
$sub_total_raw = $data->subtotal ?? $data->amount ?? 0;
$total_orden = is_numeric($total_orden_raw) ? number_format((float)$total_orden_raw, $format_numeric->digits, $format_numeric->radixPoint, $format_numeric->groupSeparator) : $total_orden_raw;
$discount_total = is_numeric($discount_total_raw) ? number_format((float)$discount_total_raw, $format_numeric->digits, $format_numeric->radixPoint, $format_numeric->groupSeparator) : $discount_total_raw;
$sub_total = is_numeric($sub_total_raw) ? number_format((float)$sub_total_raw, $format_numeric->digits, $format_numeric->radixPoint, $format_numeric->groupSeparator) : $sub_total_raw;
?>
<div class="table-responsive bg-white">
    <table class="table">
        <thead>
            <tr>
                <th class="text-center" style="vertical-align: middle;">#</th>
                <th class="text-center" style="vertical-align: middle;"><?= $lang_order['image'] ?></th>
                <th style="vertical-align: middle;" width="30%"><?= $lang_order['product_name'] ?></th>
                <th style="vertical-align: middle;" width="15%">Sku</th>
                <th style="vertical-align: middle;" width="6.5%" class="text-center"><?= $lang_order['cantidad'] ?></th>

                <!-- CANTIDAD ASIGNADA -->
                <th style="vertical-align: middle;" width="10%" class="text-center">Stock</th>
                <th style="vertical-align: middle;" width="10%" class="text-center"><?= $lang_order['sale_price'] ?></th>
                <th style="vertical-align: middle;text-align: right !important;" width="10%" class="text-center"><?= $lang_order['total_pedido'] ?></th>

                <!-- TOTAL ASIGNADO -->
                <?php if(isset($mostrar_boton_eliminar) && $mostrar_boton_eliminar == true ): ?>
                    <th style="vertical-align: middle;" width="5%">
                        <?= $lang_order['action'] ?></th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php $i = 0; ?>
            <?php
            foreach ($orderDetails as $detail) {
                $i=$i+1;
                $style_border = '';
                if ($detail->comments != null || $detail->comments != '') { // OR??
                    $style_border = 'style="border: none !important;"';
                }
                ?>
                <tr class="notranslate" style="background: ">
                    <td class="text-center" style="vertical-align: middle;">
                        <?= $i ?>
                        <input class="currency form-control" type="text"
                               name="detalle[<?= $i ?>][id]" value="<?= $detail->detail_id ?>" hidden>
                        <input class="currency form-control" type="text"
                               name="detalle[<?= $i ?>][row]" value="<?= $i ?>" hidden>
                        <input class="currency form-control" type="text"
                               name="detalle[<?= $i ?>][id_producto]" value="<?= $detail->product_id ?? $detail->pro_id ?? '' ?>"
                               hidden>
                    </td>
                    <td class="text-center" style="vertical-align: middle;">
                        <div class="position-relative d-inline-block fv-order-line-thumb">
                            <?php if (!empty($detail->star)) : ?>
                                <a data-toggle="modal"
                                   data-target="#modal-histoy"
                                   data-sku-product="<?= htmlspecialchars((string) ($detail->product->sku ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                   data-name-product="<?= htmlspecialchars((string) ($detail->product->name ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                   data-id="<?= (int) ($detail->product->pro_id ?? 0) ?>"
                                   style="color: #ffc107; cursor: pointer; z-index: 2;"
                                   class="position-absolute top-0 start-0"
                                   title="<?= htmlspecialchars((string) ($lang_catalogo['sold'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="fa-solid fa-star"></i>
                                </a>
                            <?php endif; ?>
                            <?php $this->load->view('company/product/partials/thumbnail', ['listItem' => $detail->product ?? null]); ?>
                        </div>
                    </td>
                    <td style="vertical-align: middle;">
                        <?= strtoupper($detail->product->name) ?><br>
                        <?= ($detail->product->status == 0)?'<span class="badge badge-danger">'. $lang_order['producto_innactivo'] .'</span>':'' ?>
                    </td>
                    <td style="vertical-align: middle;">
                        <a href="<?= company_url('products/edit/'.$detail->product->pro_id)?>"
                           title="Edit"><?= $detail->product->sku ?></a>
                        <?php if($detail->product->barcode != null || $detail->product->barcode != ''){?>
                            <a title="Barcode: <?= $detail->product->barcode ?>"><img
                                    src="<?= base_url('images/codbarra.png')?>"
                                    alt="<?= $detail->product->barcode ?>" class="img-fluid img-thumbnail"
                                    style="width: 25px; height: 25px;">
                            </a>
                        <?php }?>
                    </td>
                    <!-- QTY -->
                    <td style="vertical-align: middle;" class="text-center">
                        <strong>
                            <span><?= number_format($detail->qty, $format_numeric->digits, $format_numeric->radixPoint,$format_numeric->groupSeparator) ?></span></strong>
                    </td>

                    <!-- stock -->
                    <td class="text-center" style="vertical-align: middle;"><strong><?= $detail->product->stock ?></strong></td>

                    <!-- precio cliente -->
                    <td style="vertical-align: middle;" class="text-center">
                        <strong>
                            <span>&nbsp;&nbsp;<?= $moneda . number_format($detail->sale_price,$format_numeric->digits, $format_numeric->radixPoint,$format_numeric->groupSeparator) ?></span>
                        </strong>
                        <span class="fixed-price-labels" style="display: none;">
                        <?php if($detail->modify_date!=null){?>
                            <br>
                            <span class="badge badge-pill"
                                  style="background: #ffc107;"><?= $lang_order['price_modified']?></span>
                            <?php if($detail->fixed_pricedate!=null){?>
                                <br>
                                <span class="badge badge-pill"
                                      style="background: #ffc107;"><?= $lang_order['price_fixedprice'] ?></span>
                            <?php }else{ ?>
                                <br>
                                <span class="badge badge-pill"
                                      style="background: #ffc107;"><?= $lang_order['price_notfixedprice'] ?></span>
                            <?php }?>
                        <?php }else{?>
                            <?php if($detail->fixed_pricedate!=null){?>
                                <br>
                                <span class="badge badge-pill"
                                      style="background: #ffc107;"><?= $lang_order['price_fixedprice'] ?>
                                                        </span>
                            <?php } ?>
                        <?php }?>
                        </span>
                    </td>
                    <!-- total pedido (total por línea): usar total_amount solo si es > 0; si es 0 o null, calcular precio × cantidad para que coincida con Sub Total -->
                    <?php
                    $total_amount_detail = $detail->total_amount ?? $detail->totalAmount ?? null;
                    $sale_price_detail = $detail->sale_price ?? $detail->salePrice ?? 0;
                    $qty_detail = $detail->qty ?? 0;
                    $computed_line = (float) $sale_price_detail * (float) $qty_detail;
                    $total_linea = (isset($total_amount_detail) && is_numeric($total_amount_detail) && (float) $total_amount_detail > 0)
                        ? (float) $total_amount_detail
                        : $computed_line;
                    ?>
                    <td class="text-right text-end" style="vertical-align: middle;">
                        <strong>
                            <span>&nbsp;&nbsp;<?= $moneda . number_format($total_linea, $format_numeric->digits, $format_numeric->radixPoint, $format_numeric->groupSeparator) ?></span>
                        </strong>

                    </td>

                    <?php if(isset($mostrar_boton_eliminar) && $mostrar_boton_eliminar == true ): ?>
                        <td class="text-right text-end" style="vertical-align: middle;">
                            <a title="<?= $lang_order['tol-delete'] ?>" data-toggle="tooltip"
                               data-group-default="" href="javascript:void(0)"
                               onclick="deleteOrderdetails('<?=$detail->detail_id?>')"
                               class="btn btn-danger btn-go-delete btn-sm"><i
                                    class="mdi mdi-delete"></i></a>
                        </td>
                    <?php endif; ?>
                </tr>

                <?php if ($detail->comment != null || $detail->comment != '') {?>
                    <tr>
                        <td colspan="5"
                            style="border:none !important;border-bottom: 2px solid #e4e9f0 !important;">
                        </td>
                        <td colspan="3"
                            class="text-wrap text-right text-end"
                            style="overflow-wrap: break-word; border:none !important;border-bottom: 2px solid #e4e9f0 !important;">
                            <strong><?= $lang_order['comments'] ?>:</strong>
                            <?= $detail->comment ?>
                        </td>
                    </tr>
                <?php }?>
            <?php }?>
            <!-- SUB TOTAL -->
            <tr class="notranslate">

                <td colspan="7">
                    <strong>Sub Total</strong>
                </td>

                <td class="text-right text-end">
                    <strong><?= $moneda  . $sub_total?></strong>
                </td>

                <?php if(isset($mostrar_boton_eliminar)): ?>

                <?php endif; ?>

            </tr>
            <!-- DESCUENTO -->
            <tr class="notranslate">
                <?php

                $descuento_verificable = $data->discount ?? $data->descuento ?? 0;
                $type = null;
                $discount_type = $data->discount_type ?? null;

                switch ($discount_type) {
                    case 1:
                        $type = (is_numeric($descuento_verificable) ? number_format((float)$descuento_verificable, $format_numeric->digits, $format_numeric->radixPoint, $format_numeric->groupSeparator) : $descuento_verificable) . '%';
                        break;
                    case 2:
                        $type = $moneda;
                        break;
                    default:
                        $type = '';
                        break;
                }
                ?>
                <td colspan="7">
                    <strong><?= $lang_order['discount_total']?> (<span
                            id="span_discount"><?= $type ?></span>)</strong>
                </td>
                <td class="text-right text-end">
                    <strong><?= $moneda .$discount_total ?></strong>
                </td>

                <?php if(isset($mostrar_boton_eliminar)): ?>
                <?php endif; ?>
            </tr>
            <!-- TOTAL -->
            <tr class="notranslate" style="background: #eee;">

                <td colspan="7" class="text-left">
                    <strong><?= $lang_order['price_total'] ?></strong>
                </td>
                <td class="text-right text-end">
                    <strong id="total_orden"><?= $moneda.$total_orden ?></strong>
                </td>
                <?php if(isset($mostrar_boton_eliminar)): ?>
                <?php endif; ?>
            </tr>
        </tbody>
    </table>
</div>

<script>
    $(document).ready(function () {
        if (typeof window.fvInitOrderImagePopovers === 'function') {
            window.fvInitOrderImagePopovers(document);
            return;
        }
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
                return img ? '<img src="' + img + '" style="width:200px;">' : '';
            }
        });
    });
</script>

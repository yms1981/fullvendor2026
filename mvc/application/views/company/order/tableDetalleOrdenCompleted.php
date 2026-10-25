<?php
/**
 * @var $data Object Order info
 * @var $info_company Object Company info
 * @var $lang_json Array The lang translations for every translatable string
 */

$lang_order = $lang_json['cmp_orders']['lang'];
$lang_catalogo = $lang_json['cmp_catalog']['lang'];

$format_numeric = $info_company->format_numeric;
$moneda = $info_company->currencies[0]->symbol;
$orderDetails = $data->order_details;

//TOTALS
$total_orden = number_format($data->total,$format_numeric->digits, $format_numeric->radixPoint,$format_numeric->groupSeparator);
$discount_total =  number_format($data->descuento,$format_numeric->digits, $format_numeric->radixPoint,$format_numeric->groupSeparator);
$sub_total =  number_format($data->subtotal,$format_numeric->digits, $format_numeric->radixPoint,$format_numeric->groupSeparator);

//TOTALS ASSIGNED
$total_orden_asignada = number_format($data->total_asignado,$format_numeric->digits, $format_numeric->radixPoint,$format_numeric->groupSeparator);
$discount_total_asignada = number_format($data->descuento_asignado,$format_numeric->digits, $format_numeric->radixPoint,$format_numeric->groupSeparator);
$sub_total_asignada = number_format($data->total_asignado,$format_numeric->digits, $format_numeric->radixPoint,$format_numeric->groupSeparator);
?>
<div class="table-responsive bg-white">
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th style="vertical-align: top;"><?= $lang_order['image'] ?></th>
                <th style="vertical-align: top;" width="30%"><?= $lang_order['product_name'] ?></th>
                <th style="vertical-align: top;" width="15%">Sku</th>
                <th style="vertical-align: top;" width="6.5%" class="text-center"><?= $lang_order['cantidad'] ?></th>
                <th style="vertical-align: top;text-align: center !important; " width="10%" class="text-center text-wrap text-success"><?= $lang_order['pack'] ?></th>

                <!-- CANTIDAD ASIGNADA -->
                <th style="vertical-align: top;" width="6.5%" class="text-center text-wrap text-success"><?= $lang_order['qty_asigned'] ?></th>
                <th style="vertical-align: top;" width="10%" class="text-center">Stock</th>
                <th style="vertical-align: top;" width="10%" class="text-center"><?= $lang_order['sale_price'] ?></th>
                <th style="vertical-align: top;text-align: right !important;" width="10%" class="text-center"><?= $lang_order['total_pedido'] ?></th>

                <!-- TOTAL ASIGNADO -->
                <th style="vertical-align: top;text-align: right !important; " width="10%" class="text-center text-wrap text-success"><?= $lang_order['total_asigned'] ?></th>

                <?php if(isset($mostrar_boton_eliminar) && $mostrar_boton_eliminar == true ): ?>
                    <th style="vertical-align: top;" width="5%">
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
                $fondo_available = null;

                if ($detail->pack < 0) {
                    $fondo_available = "#e9ecef";
                }
                ?>
                <tr class="notranslate"
                    <?= $fondo_available? 'style="background: '. $fondo_available .';"': '' ?>>
                    <td>
                        <br><br>
                        <?= $i ?>
                        <input class="currency form-control" type="text"
                               name="detalle[<?= $i ?>][id]" value="<?= $detail->detail_id ?>" hidden>
                        <input class="currency form-control" type="text"
                               name="detalle[<?= $i ?>][row]" value="<?= $i ?>" hidden>
                        <input class="currency form-control" type="text"
                               name="detalle[<?= $i ?>][id_producto]" value="<?= $detail->pro_id ?>"
                               hidden>
                    </td>
                    <td class="proimg text-center align-middle" style="vertical-align: top;">
                        <br><br>
                        <div class="position-relative d-inline-block fv-order-line-thumb">
                            <?php
                            $mostrar_estrella = $detail->star;
                            if ($mostrar_estrella) {
                                ?>
                                <a data-toggle="modal" data-sku-product="<?= $detail->product->sku ?>" data-name-product="<?= $detail->product->name ?>" data-id="<?= $detail->product->pro_id ?>"
                                   data-target="#modal-histoy" style="color: #ffc107;cursor: pointer;z-index:2;"
                                   class="position-absolute top-0 start-0"
                                   title="<?= $lang_catalogo['sold'] ?>">
                                    <i class="fa-solid fa-star"></i>
                                </a>
                            <?php } ?>
                            <?php $this->load->view('company/product/partials/thumbnail', ['listItem' => $detail->product ?? null]); ?>
                        </div>
                    </td>
                    <td style="vertical-align: top;"><br><br>
                        <?= strtoupper($detail->product->name) ?><br>
                        <?= ($detail->product->status == 0)?'<span class="badge badge-danger">'. $lang_order['producto_innactivo'] .'</span>':'' ?>
                    </td>
                    <td style="vertical-align: top;">
                        <br><br>
                        <a href="<?= company_url('product/edit/'.$detail->product->pro_id)?>"
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
                    <td style="vertical-align: top;" class="text-center">
                        <br><br>
                        <strong>
                            <span><?= number_format($detail->qty, $format_numeric->digits, $format_numeric->radixPoint,$format_numeric->groupSeparator) ?></span></strong>
                    </td>

                    <!-- Bultos -->
                    <td style="vertical-align: top;" class="text-center text-success">
                        <br><br>
                        <strong>
                            <span>
                                <?= $detail->pack < 0? $lang_order['no_disponible']  : number_format($detail->pack,$format_numeric->digits, $format_numeric->radixPoint,$format_numeric->groupSeparator) ?>
                            </span>
                        </strong>
                    </td>

                    <!-- qty asigned -->
                    <td style="vertical-align: top;" class="text-center text-success">
                        <br><br>
                        <strong>
                            <span>
                                <?= $detail->delivered_quantity < 0? $lang_order['no_disponible'] : number_format($detail->delivered_quantity,$format_numeric->digits, $format_numeric->radixPoint,$format_numeric->groupSeparator) ?>
                            </span>
                        </strong>
                    </td>

                    <!-- stock -->
                    <td class="text-center"><br><br><strong><?= $detail->product->stock ?></strong></td>

                    <!-- precio cliente -->
                    <td style="vertical-align: top;" class="text-center">
                        <br><br>
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
                    <!-- total pedido (total por línea formateado con format_numeric) -->
                    <?php $total_linea = isset($detail->total_amount) ? (float)$detail->total_amount : (float)($detail->sale_price * $detail->qty); ?>
                    <td class="text-right text-end" style="vertical-align: top;">
                        <br><br>
                        <strong>
                            <span>&nbsp;&nbsp;<?= $moneda . number_format($total_linea, $format_numeric->digits, $format_numeric->radixPoint, $format_numeric->groupSeparator) ?></span>
                        </strong>

                    </td>
                    <!-- total importe asignado -->
                    <td class="text-right text-end text-success" style="vertical-align: top;">
                        <br><br>
                        <strong><span>&nbsp;&nbsp;<?= $detail->total_amount_assigned<=0 ? '-': $moneda . number_format($detail->total_amount_assigned,$format_numeric->digits, $format_numeric->radixPoint,$format_numeric->groupSeparator) ?></span></strong>
                    </td>

                    <?php if(isset($mostrar_boton_eliminar) && $mostrar_boton_eliminar == true ): ?>
                        <td class="text-right text-end" style="vertical-align: top;">
                            <br><br>
                            <a title="<?= $lang_order['tol-delete'] ?>" data-toggle="tooltip"
                               data-group-default="" href="javascript:void(0)"
                               onclick="deleteOrderdetails('<?=$detail->detail_id?>')"
                               class="btn btn-danger btn-go-delete btn-sm"><i
                                    class="mdi mdi-delete"></i></a>
                        </td>
                    <?php endif; ?>
                </tr>

                <?php if ($detail->comments != null || $detail->comments != '') {?>
                    <tr>
                        <td colspan="9"
                            style="border:none !important;border-bottom: 2px solid #e4e9f0 !important;">
                        </td>
                        <td colspan="3"
                            class="text-wrap text-right text-end"
                            style="overflow-wrap: break-word; border:none !important;border-bottom: 2px solid #e4e9f0 !important;">
                            <strong><?= $lang_order['comments'] ?>:</strong>
                            <?= $detail->comments ?>
                        </td>
                    </tr>
                <?php }?>
            <?php }?>
            <!-- SUB TOTAL -->
            <tr class="notranslate">
                <td colspan="9">
                    <strong>Sub Total</strong>
                </td>

                <td class="text-right text-end">
                    <strong><?= $moneda  . $sub_total?></strong>
                </td>
                <td class="text-right text-end text-success">
                    <strong><?= $moneda . $sub_total_asignada?></strong>
                </td>

                <?php if(isset($mostrar_boton_eliminar)): ?>

                <?php endif; ?>

            </tr>
            <!-- DESCUENTO -->
            <tr class="notranslate">
                <?php

                $descuento_verificable = $data->discount;
                $type = null;

                switch ($data->discount_type) {
                    case 1:
                        $type = $descuento_verificable .'%';
                        break;
                    case 2:
                        $type = '$';
                        break;
                    default:
                        $type = '';
                        break;
                }
                ?>

                <td colspan="9">
                    <strong><?= $lang_order['discount_total']?> (<span
                            id="span_discount"><?= $type ?></span>)</strong>
                </td>
                <td class="text-right text-end">
                    <strong><?= $moneda .$discount_total ?></strong>
                </td>
                <td class="text-right text-end text-success">
                    <strong><?= $moneda . $discount_total_asignada?></strong>
                </td>

                <?php if(isset($mostrar_boton_eliminar)): ?>
                <?php endif; ?>
            </tr>
            <!-- TOTAL -->
            <tr class="notranslate" style="background: #eee;">
                <td colspan="9"
                    class="text-left"><strong><?= $lang_order['price_total'] ?></strong>
                </td>
                <td class="text-right text-end">
                    <strong id="total_orden"><?= $moneda.$total_orden ?></strong>
                </td>
                <td class="text-right text-end text-success">
                    <strong><?= $moneda.$total_orden_asignada?></strong>
                </td>
                <?php if(isset($mostrar_boton_eliminar)): ?>
                <?php endif; ?>
            </tr>
        </tbody>
    </table>
</div>

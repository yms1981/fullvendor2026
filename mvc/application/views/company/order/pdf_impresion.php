<?php
$compName=$this->session->userdata('name');

$ahora = DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));
$v_imagen = $ahora->format("YmdHisu");
$compId = $this->session->userdata('compId');
$moneda = $currency_symbol->Symbol;
$radixPoint = $format_numeric->radixPoint?$format_numeric->radixPoint:'';
$groupSeparator = $format_numeric->groupSeparator?$format_numeric->groupSeparator:'';

$col = isset($notas['include_locations'])? 5 : 4;
$show_productLocation = isset($notas['include_locations'])? true : false;


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css"
        integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <title>Order Invoice - <?=$orden->order_number?></title>

    <style type="text/css">
    body {
        padding: 0;
        font-family: sans-serif;
        font-size: 14px;
        line-height: 1.5em;
    }

    h1,
    h2,
    h3,
    h4,
    h5,
    h6 {
        padding: 0;
        font-family: sans-serif !important;
    }

    table {
        border-collapse: collapse;
    }

    .borde-address {
        border: 0.3px solid #6c757d;
        padding: 10px;
        border-radius: 10px;
        padding-top: 0;
    }

    ul {
        list-style: none;
        padding: 0;

    }

    ul li {
        border-bottom: 0.3px solid #6c757d;
        padding-bottom: 5px;
    }

    #table-detalle thead tr th,
    #table-detalle tbody tr td {
        border-bottom: 0.3px solid #6c757d !important;
    }

    #table-encabezado thead tr th,
    #table-encabezado tbody tr td {
        border: none;
    }

    .btn {
        display: inline-block;
        font-weight: 400;
        color: #212529;
        text-align: center;
        vertical-align: middle;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
        background-color: transparent;
        border: 1px solid transparent;
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        line-height: 1.5;
        border-radius: 0.25rem;
        transition: color .15s ease-in-out, background-color .15s ease-in-out, border-color .15s ease-in-out, box-shadow .15s ease-in-out;
    }

    .btn-success {

        color: #fff;
        background-color: #2ECC71;
        border-color: #28a745;
    }

    .btn-danger {
        color: #fff;
        background-color: #DA4453;
        border-color: #DA4453;
    }

    .badge {
        border-radius: 4px;
        font-size: 8px;
        font-weight: initial;
        line-height: 1;
        padding: 0.375rem 0.5625rem;
        font-family: sans-serif !important;
    }

    .badge-pill {
        padding-right: 0.6em;
        padding-left: 0.6em;
        border-radius: 10rem;
    }
    </style>
</head>

<body>
    <table id="table-encabezado" style="width: 100%">
        <tbody>
            <tr>
                <td style="width: 35%">
                    <div align="left">
                        <strong><?= $lang_order['customer'] ?>: </strong><?= $orden->business_name ?><br>
                        <strong><?= $lang_order['contact'] ?>: </strong><?= $orden->name ?><br>
                        <strong><?= $lang_order['seller'] ?>:
                        </strong><?= $seller->first_name.' '.$seller->last_name ?><br>
                        <strong><?= $lang_order['origin'] ?>: </strong><?= $orden->source ?><br>
                        <strong><?= $lang_order['address']?>: <?= $addres_commercial ?></strong>
                    </div>
                </td>
                <td style="width: 30%; vertical-align: top;">
                    <div align="center">
                        <?php if ($company->image) { ?>
                            <img src="<?= company_logo_url((string) $company->image) ?>" class="rounded-0"
                                 style="display: block;max-height: 75px;max-width: 200px;" alt="">
                        <?php } else{ ?>
                            <img src="<?= base_url('uploads/user.png') ?>" class="rounded-0"
                                 style="display: block;max-height: 75px;max-width: 200px;" alt="">
                        <?php } ?>

                    </div>
                </td>
                <td style="width: 35%; vertical-align:top;">
                    <table style="width: 100%;">
                        <tr>
                            <td><strong><?= $lang_order['fecha_creacion_order'] ?>: </strong></td>
                            <td><?= date($format_date->format_php,strtotime($orden->created)) ?></td>

                        </tr>
                        <tr>
                            <td><strong><?= $lang_order['order_orden_number'] ?>: </strong></td>
                            <td><?= $orden->order_number ?></td>
                            <!-- <td>Proforma</td> -->
                        </tr>
                        <?php if($orden->proforma): ?>
                            <!-- <tr>
                            <td></td>
                            <td>
                                <br>
                                <img src="<?= base_url('uploads/check-box.png') ?>" class="rounded-0"
                                    style="display: block; width: 12px; height: 12px;" alt="">
                                <strong style="vertical-align: top;">Proforma</strong>
                            </td>
                        </tr> -->
                        <?php endif; ?>
                    </table>

                </td>
            </tr>
        </tbody>
    </table>
    <br>
    <table border="0" style="width: 100%;border:none;">
        <tr>
            <td style="width:50%;">
                <div align="left" class="borde-address">

                    <br>
                    <strong style="font-size: 1.5em;"><?= $lang_order['dispatch'] ?></strong>
                    <ul>

                        <li><strong><?= $lang_order['address']?>: </strong> <?= $addres_distpatch ?></li>
                        <li><strong><?= $lang_order['phone'] ?>: </strong><?=  $orden->phone ?></li>
                        <li style="border:none;"><strong><?= $lang_order['cell_phone'] ?>:</strong><?= $orden->cell_phone ?></li>
                    </ul>
                </div>
            </td>
            <td style="width:50%;">

            </td>
        </tr>

    </table>
    <br>
    <table id="table-detalle" style="width: 100%;">
        <thead>
            <tr>
                <th>#</th>
                <th><?= $lang_order['image'] ?></th>
                <th width="25%"><?= $lang_order['product_name'] ?></th>
                <th width="15%" style="text-align:center;">Sku</th>
                <?php if($show_productLocation) :?>
                <th width="15%" style="text-align:center;"><?= $lang_order['locations'] ?></th>
                <?php endif; ?>
                <th width="13%" class="text-center"><?= $lang_order['cantidad'] ?></th>
                <?php if($show_values_asigned) :?>
                <th width="13%" class="text-center"><?= $lang_order['pack'] ?></th>
                <?php endif; ?>
                <th width="15%" class="text-right"><?= $lang_order['sale_price_impresion'] ?></th>
                <th width="15%" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 0;
            $totprc=0;
            $totqty=0;
            $total_orden = null;
            ?>

            <?php
            foreach ($detalle_list as $pro) {
            $i=$i+1;

            $style_border = '';
            if ($pro->comments != null || $pro->comments != '') {

                $style_border = 'style="border: none !important;"';
            }

            ?>
            <tr class="notranslate">
                <td <?= $style_border ?>><?= $i ?></td>
                <td <?= $style_border ?> style="vertical-align: top;" class="proimg text-center">
                    <?php $this->load->view('company/product/partials/thumbnail', ['listItem' => $pro]); ?>
                </td>
                <td <?= $style_border ?> style="vertical-align: top;"><br><?= $pro->name ?></td>
                <td <?= $style_border ?> style="vertical-align: top;" class="text-center"><br><?= $pro->sku ?></td>
                <!-- locations -->
                <?php if($show_productLocation) :?>
                <td <?= $style_border ?> style="vertical-align: top;" class="text-center"><br><?= $pro->locations ?>
                </td>
                <?php endif; ?>
                <?php
                    $precio = number_format($pro->precio_cliente, $format_numeric->digits, $radixPoint,$groupSeparator);
                    $qty = number_format( $show_values_asigned ? $pro->delivered_quantity : $pro->qty, $format_numeric->digits, $radixPoint,$groupSeparator);
                    if($show_values_asigned) {

                        $total_importe =  ($pro->total_importe_asignado <= 0) ? "-" :$moneda . number_format( $show_values_asigned ? $pro->total_importe_asignado : $pro->total_importe, $format_numeric->digits, $radixPoint,$groupSeparator);

                    }else{
                        $total_importe =  $moneda . number_format( $pro->total_importe, $format_numeric->digits, $radixPoint,$groupSeparator);
                    }
                ?>

                <td <?= $style_border ?> style="vertical-align: top;" class="text-center">
                    <br><?= $qty ?><br>
                </td>
                <?php if($show_values_asigned) :?>
                <td <?= $style_border ?> style="vertical-align: top;" class="text-center">
                    <br><?= $pro->pack < 0? $lang_order['no_disponible']  : number_format($pro->pack,$format_numeric->digits, $radixPoint,$groupSeparator) ?><br>
                </td>
                <?php endif; ?>
                <td <?= $style_border ?> style="vertical-align: top;" class="text-right">
                    <br>
                    &nbsp;&nbsp;<?= $moneda ?><?= $precio ?>
                    <br>
                </td>

                <td <?= $style_border ?> style="vertical-align: top;" class="text-right">
                    <br>
                    &nbsp;&nbsp;<?= $total_importe ?><br>
                </td>
            </tr>
            <?php if ($pro->comments != null || $pro->comments != '') {

        ?>
            <tr>
                <td colspan="<?= $col ?>" style="border:none !important;border-bottom: 1px solid #6c757d !important;">
                </td>
                <td colspan="3" class="text-wrap text-right"
                    style="overflow-wrap: break-word; border:none !important;border-bottom: 0.3px solid #6c757d !important;">
                    <strong><?= $lang_order['comments'] ?>:</strong> <?= $pro->comments ?>
                </td>
            </tr>

            <?php }?>
            <?php

                $total_orden += $pro->total_importe;
                $total_orden_asignada += $pro->total_importe_asignado;

                ?>

            <?php }?>

            <?php

            $sub_total = $total_orden;
            $sub_total_asignada = $total_orden_asignada;

            $discount_total = null;
            $discount_total_asignada = null;

            switch ($data->discount_type) {
                case 1:
                // en porcentaje
                $discount_total = $total_orden * ($data->discount / 100);
                $discount_total_asignada = $total_orden_asignada * ($data->discount / 100);

                $total_orden = $total_orden - $discount_total;
                $total_orden_asignada = $total_orden_asignada- $discount_total_asignada;

                break;
                case 2:
                //en el monto
                $discount_total = $data->discount;
                $discount_total_asignada = $data->discount;

                $total_orden = $total_orden - $discount_total;
                $total_orden_asignada = $total_orden_asignada - $discount_total_asignada;
                break;
                default:
                $discount_total = 0;
                $discount_total_asignada = 0;
                break;
            }

            $total_orden = number_format($total_orden, $format_numeric->digits, $radixPoint,$groupSeparator);
            $total_orden_asignada = $total_orden_asignada <= 0 ? "-" : $moneda. number_format($total_orden_asignada,$format_numeric->digits, $radixPoint,$groupSeparator);

            $discount_total = number_format($discount_total, $format_numeric->digits, $radixPoint,$groupSeparator);
            $discount_total_asignada = $discount_total <= 0 ? "-": $moneda . number_format($discount_total_asignada,$format_numeric->digits, $radixPoint,$groupSeparator);

            $sub_total = number_format($sub_total, $format_numeric->digits, $radixPoint,$groupSeparator);
            $sub_total_asignada = $sub_total_asignada <=0 ? "-" : $moneda . number_format($sub_total_asignada,$format_numeric->digits, $radixPoint,$groupSeparator);

            ?>
            <tr class="notranslate">
                <td colspan="<?= $col ?>">
                    <strong>Sub Total</strong>
                </td>
                <td colspan="<?= $show_values_asigned ? "4" : "3" ?>" class="text-right">
                    <?= $show_values_asigned? $sub_total_asignada : $moneda . $sub_total ?></strong>
                </td>
            </tr>
            <tr>
                <td colspan="<?= $col ?>">
                    <?php

                    $type = null;
                    $descuento_verificable = $data->discount;

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
                    <?= $lang_order['discount_total']?> (<span id="span_discount"><?= $type ?></span>)
                </td>
                <td colspan="<?= $show_values_asigned ? "4" : "3" ?>" class="text-right"><?= $show_values_asigned ? $discount_total_asignada : $moneda.$discount_total ?></td>

            </tr>
            <tr class="notranslate" style="background: #eee;">
                <td colspan="<?= $col ?>" class="text-left"><strong><?= $lang_order['price_total'] ?></strong></td>
                <td colspan="<?= $show_values_asigned ? "4" : "3" ?>" class="text-right">
                    <strong id="total_orden"><?=  $show_values_asigned ? $total_orden_asignada : $moneda.$total_orden ?></strong>
                </td>
            </tr>

        </tbody>
    </table>
    <br>
    <table border="0" style="width: 100%;border:none;">
        <?php

 if ($notas['notas']=='con') {?>
        <tr>
            <td style="width:100%;vertical-align: top;">
                <div align="left" class="borde-address">
                    <strong style="font-size: 0.9em;"><?= $lang_order['order_notes_pdf'] ?></strong>
                    <br>
                    <?= ($orden->order_comments != null || $orden->order_comments != '')?strtoupper(str_replace('\n','<br/>',$this->Helper->replace_comentario($orden->order_comments))):'-'?>
                </div>
            </td>
        </tr>
        <tr>
            <td style="width:100%;vertical-align: top;">
                <br>
                <div align="left" class="borde-address">
                    <strong style="font-size: 0.9em;"><?= $lang_order['notas_entrega'] ?></strong>
                    <br>
                    <?= ($notas['delivery_notes'] != null || $notas['delivery_notes'] != '')?strtoupper($notas['delivery_notes']):'-' ?>
                </div>
            </td>
        </tr>
        <?php }?>
    </table>
</body>

</html>

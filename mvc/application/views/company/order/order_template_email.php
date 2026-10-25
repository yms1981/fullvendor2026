<?php

$compName=$this->session->userdata('name');
$ahora = DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));
$v_imagen = $ahora->format("YmdHisu");
$compId = $this->session->userdata('compId');
$moneda = $currency_symbol->Symbol;
$radixPoint = $format_numeric->radixPoint?$format_numeric->radixPoint:'';
$groupSeparator = $format_numeric->groupSeparator?$format_numeric->groupSeparator:'';
$total_orden = null;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css"
        integrity="sha512-SzlrxWUlpfuzQ+pcUCosxcglQRNAq/DZjVsC0lE40xsADsfeQoEypE+enwcOiGjk/bSuGGKHEyjSoQ1zVisanQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <title>Order Invoice - <?=$orden->order_number?></title>

    <style type="text/css">
    body {
        padding: 0;
        font-family: sans-serif;
        font-size: 12px;
        line-height: 1.5em;
        color: #202124;

    }

    h1,
    h2,
    h3,
    h4,
    h5,
    h6 {
        padding: 0;
        font-family: sans-serif !important;
        color: #202124;
    }

    table {
        border-collapse: collapse;
    }

    .borde-address {
        border: 0.3px solid #6c757d;
        padding: 10px;
        border-radius: 10px;
        padding-top: 0;
        color: #202124;

    }

    ul {
        list-style: none;
        padding: 0;

    }

    ul li {
        border-bottom: 0.3px solid #6c757d;
        padding-bottom: 5px;
        color: #202124;

    }

    #table-detalle thead tr th {
        font-size: 14px;
    }

    #table-detalle thead tr th,
    #table-detalle tbody tr td {
        border-bottom: 0.3px solid #6c757d !important;
        color: #202124;
    }

    #table-encabezado thead tr th,
    #table-encabezado tbody tr td {
        border: none;
        color: #202124;
    }

    .border-si {
        border: none !important;
    }

    .text-center {
        text-align: center !important;
    }

    .text-right {
        text-align: right !important;
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
        background-color: #1EA910;
        border-color: #1EA910;
    }

    .btn-danger {
        color: #fff;
        background-color: #CF1C00;
        border-color: #CF1C00;
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

    strong {
        font-size: 14px !important;
    }

    .companyImg {
        float: right;
        max-height: 75px;
        max-width: 200px;
    }

    @media (max-width: 500px) {
        .companyImg {
            max-height: 50px;
            max-width: 125px;
        }
    }

    </style>

</head>

<body>
    <?php $fecha = date($format_date->format_php, strtotime($orden->date_expired_email));?>
    <div style="width: 100%;margin-left: 0;font-size: 16px;">
        <table id="table-encabezado" style="width: 100%;">
            <tbody>
                <tr>
                    <td style="width:50%;color: #202124 !important;">

                        <p style="color: inherit !important;"><?= $request['descriptions'] ?></p>
                        <p style="color: inherit !important;">
                            <strong><?= $lang_order['order_number'] . ': ' ?></strong><?= $orden->order_number ?></p>
                        <p style="color: inherit !important;">
                            <strong><?= $lang_order['dispatch'] . " " . $lang_order['address'] . ': ' ?></strong><?= $addres_distpatch ?>
                        </p>
                        <p style="color: inherit !important;">
                            <strong><?= $lang_order['valido_hasta'] . ': ' ?></strong><?= $lang_order['valido_hasta'] . ': '. $fecha ?>
                        </p>
                    </td>
                    <td style="width:50%; vertical-align: top">
                        <?php if ($company->image) { ?>
                        <img src="<?= company_logo_url((string) $company->image) ?>" class="rounded-0 companyImg" alt="">
                        <?php } else{ ?>
                        <img src="<?= base_url('uploads/user.png') ?>" class="rounded-0 companyImg" alt="">
                        <?php } ?>
                    </td>
                </tr>
            </tbody>
        </table>
        <br>
        <?php if(isset($request['show_information'])){ ?>
        <table border="0" style="width: 100%;border:none;">
            <tr>
                <td style="width:40%;color: #202124;">
                    <br><strong><?= $lang_order['dispatch_order_email'] ?></strong>
                </td>
                <td style="width:20%;color: #202124;"></td>
                <td style="width:40%;color: #202124;">
                    <br><strong style="width:50%;"><?= $lang_order['company_information'] ?></strong>
                </td>
            </tr>
            <tr>
                <td style="width:40%;color: #202124 !important;vertical-align: top;">
                    <ul>
                        <li style="border: none;">
                            <table border="0" style="width: 100%;border:none;">
                                <tr>
                                    <td style="width:30%;color: #202124;vertical-align: top;">
                                        <strong><?= $lang_order['customer'] ?>: </strong>
                                    </td>
                                    <td style="width:70%;color: #202124;vertical-align: top;">
                                        <?= $orden->business_name . '('. $orden->name .')'?>
                                    </td>
                                </tr>
                            </table>
                        </li>
                        <li style="border: none;">
                            <table border="0" style="width: 100%;border:none;">
                                <tr>
                                    <td style="width:30%;color: #202124;vertical-align: top;">
                                        <strong>Email: </strong>
                                    </td>
                                    <td style="width:70%;color: #202124;vertical-align: top;">
                                        <?= $orden->email ?>
                                    </td>
                                </tr>
                            </table>
                        </li>
                        <li style="border: none;">
                            <table border="0" style="width: 100%;border:none;">
                                <tr>
                                    <td style="width:30%;color: #202124;vertical-align: top;">
                                        <strong><?= $lang_order['phone'] ?>: </strong>
                                    </td>
                                    <td style="width:70%;color: #202124;vertical-align: top;">
                                        <?=  $orden->phone ?>
                                    </td>
                                </tr>
                            </table>
                        </li>
                        <li style="border: none;">
                            <table border="0" style="width: 100%;border:none;">
                                <tr>
                                    <td style="width:30%;color: #202124;vertical-align: top;">
                                        <strong><?= $lang_order['address']?>: </strong>
                                    </td>
                                    <td style="width:70%;color: #202124;vertical-align: top;">
                                        <?= $orden->commercial_address.'('.$orden->commercial_state. ') '.$orden->commercial_city . ' - ' . $orden->commercial_country ?>
                                    </td>
                                </tr>
                            </table>
                        </li>
                    </ul>
                </td>
                <td style="width:20%;">
                </td>
                <td style="width:40%;color: #202124;vertical-align: top;">
                    <ul>
                        <li style="border: none;">
                            <table border="0" style="width: 100%;border:none;">
                                <tr>
                                    <td style="width:30%;color: #202124;vertical-align: top;">
                                        <strong><?= $lang_order['company_order'] ?>: </strong>
                                    </td>
                                    <td style="width:70%;color: #202124;vertical-align: top;">
                                        <?= $company->name ?>
                                    </td>
                                </tr>
                            </table>
                        </li>
                        <li style="border: none;">
                            <table border="0" style="width: 100%;border:none;">
                                <tr>
                                    <td style="width:30%;color: #202124;vertical-align: top;">
                                        <strong>Email: </strong>
                                    </td>
                                    <td style="width:70%;color: #202124;vertical-align: top;">
                                        <?= $company->email ?>
                                    </td>
                                </tr>
                            </table>
                        </li>
                        <li style="border: none;">
                            <table border="0" style="width: 100%;border:none;">
                                <tr>
                                    <td style="width:30%;color: #202124;vertical-align: top;">
                                        <strong><?= $lang_order['phone'] ?>: </strong>
                                    </td>
                                    <td style="width:70%;color: #202124;vertical-align: top;">
                                        <?= $company->phone_number . ' - ' . $company->cell_number?>
                                    </td>
                                </tr>
                            </table>
                        </li>
                        <li style="border: none;">
                            <table border="0" style="width: 100%;border:none;">
                                <tr>
                                    <td style="width:30%;color: #202124;vertical-align: top;">
                                        <strong><?= $lang_order['address']?>: </strong>
                                    </td>
                                    <td style="width:70%;color: #202124;vertical-align: top;">
                                        <?= $company->address . '('.$company->state . ') ' . $company->city .' - '.$company->country ?>
                                    </td>
                                </tr>
                            </table>
                        </li>
                    </ul>
                </td>
            </tr>
        </table>
        <br>
        <?php } ?>
        <table border="0" style="width: 100%;border:none;">
            <tr>
                <td style="width:100%;color: #202124;vertical-align: top;">
                    <ul>
                        <li style="border: none;padding: 0;margin: 0;">
                            <strong><?= $lang_order['seller'] . ': ' ?></strong><?= $seller->first_name.' '.$seller->last_name . '('. $seller->phone_number .')' ?>
                        </li>
                    </ul>
                </td>
            </tr>
        </table>
        <br>
        <table id="table-detalle" style="width: 100%;">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?= $lang_order['image'] ?></th>
                    <th width="<?= (isset($request['hide_price'])?'35%':'25%') ?>"><?= $lang_order['product_name'] ?>
                    </th>
                    <th width="<?= (isset($request['hide_price'])?'25%':'15%') ?>" style="text-align:center;">Sku</th>
                    <th width="<?= (isset($request['hide_price'])?'23%':'13%') ?>"
                        class="<?= (isset($request['hide_price'])?'text-right':'text-center') ?>">
                        <?= $lang_order['cantidad'] ?></th>
                    <?php if(!isset($request['hide_price'])){?>
                    <th width="15%" class="text-right"><?= $lang_order['sale_price_impresion'] ?></th>
                    <th width="15%" class="text-right">Total</th>
                    <?php }?>
                </tr>
            </thead>
            <tbody>
                <?php $i = 0;
                  $totprc=0;
                  $totqty=0;
                  ?>
                <?php
                foreach ($detalle_list as $pro) {
                  $i=$i+1;
                  $style_border = '';

                  if ($pro->comments != null || $pro->comments != '') {

                    $style_border = ' border-si';
                    $style_border = 'border: none !important;';
                  }

                  ?>
                <tr class="notranslate">
                    <td style="<?= $style_border ?>"><?= $i ?></td>
                    <td style="vertical-align: top;<?= $style_border ?>" class="proimg text-center">
                        <?php $this->load->view('company/product/partials/thumbnail', ['listItem' => $pro]); ?>
                    </td>
                    <td style="vertical-align: top;<?= $style_border ?>"><br><?= $pro->name ?></td>
                    <td style="vertical-align: top;text-align: center;<?= $style_border ?>" class="text-center">
                        <br><?= $pro->sku ?>
                    </td>
                    <?php
                        $precio = number_format($pro->precio_cliente, $format_numeric->digits, $radixPoint,$groupSeparator);
                        $qty = number_format($pro->qty, $format_numeric->digits, $radixPoint,$groupSeparator);
                        $total_importe =number_format($pro->total_importe, $format_numeric->digits, $radixPoint,$groupSeparator);
                      ?>
                    <td class=" <?= (isset($request['hide_price'])?'text-right':'text-center') ?>"
                        style="vertical-align: top;<?= $style_border ?>">
                        <br>
                        <?= $qty ?>
                    </td>
                    <?php if(!isset($request['hide_price'])){?>
                    <td class="text-right" style="vertical-align: top;<?= $style_border ?>">
                        <br>
                        &nbsp;&nbsp;<?= $moneda ?><?= $precio ?>
                        <br>
                        <?php if($pro->modify_date!=null){?>
                        <br>
                        <span class="badge badge-pill"
                            style="background: #ffc107;"><?= $lang_order['price_modified']?></span>
                        <?php }?>
                    </td>

                    <td class="text-right" style="vertical-align: top;<?= $style_border ?>">
                        <br>
                        &nbsp;&nbsp;<?= $moneda ?><?= $total_importe ?>
                    </td>
                    <?php }?>
                </tr>
                <?php if ($pro->comments != null || $pro->comments != '') {
                ?>
                <tr>
                    <td colspan="<?= (isset($request['hide_price'])?'3':'4') ?>"
                        style="border:none !important;border-bottom: 1px solid #6c757d !important;"></td>
                    <td colspan="<?= (isset($request['hide_price'])?'2':'3') ?>" class="text-wrap text-right"
                        style="overflow-wrap: break-word; border:none !important;border-bottom: 0.3px solid #6c757d !important;">
                        <strong><?= $lang_order['comments'] ?>:</strong> <?= $pro->comments ?>
                    </td>
                </tr>
                <?php }?>
                <?php
                    $total_orden += $pro->total_importe;
                ?>
                <?php }?>

                <?php

                    $sub_total = $total_orden;
                    $discount_total = null;
                    switch ($data->discount_type) {
                    case 1:
                        // en porcentaje
                    $discount_total = $total_orden * ($data->discount / 100);
                    $total_orden = $total_orden - $discount_total;
                    break;
                    case 2:
                        //en el monto
                    $discount_total = $data->discount;
                    $total_orden = $total_orden - $discount_total;
                    break;
                    default:
                    $discount_total = 0;
                    break;
                    }

                    $total_orden = number_format($total_orden, $format_numeric->digits, $radixPoint,$groupSeparator);
                    $discount_total = number_format($discount_total, $format_numeric->digits, $radixPoint,$groupSeparator);
                    $sub_total = number_format($sub_total, $format_numeric->digits, $radixPoint,$groupSeparator);

                    ?>
                <tr class="notranslate">
                    <td colspan="<?= (isset($request['hide_price'])?'3':'4') ?>">
                        <strong>Sub Total</strong>
                    </td>
                    <td colspan="<?= (isset($request['hide_price'])?'2':'3') ?>" class="text-right">
                        <?= $moneda .$sub_total ?></strong>
                    </td>
                </tr>
                <tr>
                    <td colspan="<?= (isset($request['hide_price'])?'3':'4') ?>">
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
                    <td colspan="<?= (isset($request['hide_price'])?'2':'3') ?>" class="text-right">
                        <?= $moneda .$discount_total ?></td>

                </tr>
                <tr class="notranslate" style="background: #eee;">
                    <td colspan="<?= (isset($request['hide_price'])?'3':'4') ?>" class="text-left">
                        <strong><?= $lang_order['price_total'] ?></strong>
                    </td>
                    <td colspan="<?= (isset($request['hide_price'])?'2':'3') ?>" class="text-right">
                        <strong><?= $moneda ?></strong><strong id="total_orden"><?= $total_orden ?></strong>
                        <br>
                        <strong><?= $plusTaxesLabel??"" ?></strong>
                    </td>
                </tr>


            </tbody>
        </table>
        <br>
        <table id="table-detalle" style="width: 100%;">
            <tbody>
                <tr>
                    <td style="width: 30%;"></td>
                    <td style="width: 20%;text-align:center;padding: 5px;">
                        <?php if($show_buttons): ?>

                        <a clicktracking="off" href="<?= base_url('products/order_reject/'.$token) ?>" class=" btn btn-danger"
                            style="text-decoration:none; color: white;width:70%;"><?= $lang_order['rechazar'] ?>
                        </a>
                        <p style="font-size: 10px;"><?= $lang_order['rechazar_toda_orden'] ?></p>
                        <?php endif; ?>
                    </td>
                    <td style="width: 20%;text-align:center;padding: 5px;">

                        <?php if($show_buttons): ?>
                            <a clicktracking="off" href="<?= base_url('products/order_confirm/'.$token) ?>" class=" btn btn-success"
                                style="text-decoration:none; color: white;width:70%;"><?= $lang_order['confirmar'] ?></a>
                            <p style="font-size: 10px;"><?= $lang_order['confirmar_toda_orden'] ?></p>

                        <?php endif; ?>
                    </td>
                    <td style="width: 30%;"></td>
                </tr>
            </tbody>
        </table>

        <div style="color: #202124;">
            <?= $lang_order['thanks'] ?><br>
            <?= $company->name ?><br><br>
            <strong><?= $lang_order['notas_entrega'] ?></strong><br>
            <?= $dispatch_shipping_notes ?>

        </div>
    </div>
</body>

</html>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
    </style>
</head>

<body>
    <div style="width: 100%;margin-left: 0;font-size: 16px;">
        <table id="table-encabezado" style="width: 100%;">
            <tbody>
                <tr>
                    <!-- <td style="width:38%;color: #202124;">
                        <?= sprintf($lang_cart['hello'], $cart_info->customer) ?><br>
                    </td> -->

                    <td style="vertical-align: center;width:38%;color: #202124;">
                        <table>
                            <tr>
                                <td style="vertical-align: center;width:10%;">
                                    <div>
                                        <img src="<?= base_url('uploads/cart.png?'.$v_imagen) ?>" class="rounded-0"
                                            style="display: block; width: 100%; height: 100%; margin-right:auto;"
                                            alt="">
                                    </div>
                                </td>
                                <td style="vertical-align: center;width:50%;color: #202124;">
                                    <strong><?= sprintf($lang_cart['hello'], $cart_info->customer) ?></strong><br>
                                    <strong
                                        style="font-size: 19px !important;color: #202124;">¡<?= $lang_cart['cart_iswaiting'] ?></strong><br>
                                    <strong style="color: #202124;"><?= $lang_cart['cart_had'] ?></strong>
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td style="width:10%;">
                        <?php if ($company->image) { ?>
                        <img src="<?= company_logo_url((string) $company->image) ?>" class="rounded-0"
                            style="display: block; width: 9em; height: 8em;" alt="">
                        <?php } else{ ?>
                        <img src="<?= base_url('images/no_image_user.png') ?>" class="rounded-0"
                            style="display: block; width: 9em; height: 8em;" alt="">
                        <?php } ?>
                    </td>
                </tr>
            </tbody>
        </table>

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
                                        <?=$cart_info->customer?>
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
                                        <?=$cart_info->email_customer ?>
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
                                        <?=$cart_info->phone?>
                                    </td>
                                </tr>
                            </table>
                        </li>
                        <li style="border: none;">
                            <table border="0" style="width: 100%;border:none;">
                                <tr>
                                    <td style="width:30%;color: #202124;vertical-align: top;">
                                        <strong><?= $lang_order['seller']?>: </strong>
                                    </td>
                                    <td style="width:70%;color: #202124;vertical-align: top;">
                                        <?=$cart_info->seller ?>
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
        <table border="0" style="width: 100%;border:none;">
            <tr>
                <td style="width:20%;">
                    <a href="<?= htmlspecialchars((string) (! empty($link) ? $link : (base_url('catalogo/producto?token=' . rawurlencode((string) ($token ?? ''))))), ENT_QUOTES, 'UTF-8') ?>" class=" btn btn-success"
                        style="text-decoration:none; color: white;width:70%;"><?= htmlspecialchars((string) ($lang_order['confirmar'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                </td>
                <td style="width:30%;">

                </td>
                <td style="width:20%;">

                </td>
            </tr>
        </table>

        <table id="table-detalle" style="width: 100%;">
            <thead>
                <tr>
                    <th>#</th>
                    <th class="text-center"><?= $lang_order['image'] ?></th>
                    <th width="30%"><?= $lang_order['product_name'] ?></th>
                    <th width="15%">Sku</th>
                    <th width="13%" class="text-center"><?= $lang_order['cantidad'] ?></th>
                    <th width="10%" class="text-right"><?= $lang_order['sale_price'] ?></th>
                    <th width="10%" class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 0;?>
                <?php foreach($details_car as $pro){ 
                    $style_border = '';
                  
                    if ($pro->comment != null || $pro->comment != '') {

                        $style_border = ' border-si';
                        $style_border = 'border: none !important;';
                    }
                    
                    $i=$i+1; ?>

                <tr class="notranslate">
                    <td style="<?= $style_border ?>"><?= $i ?></td>
                    <td style="vertical-align: middle;<?= $style_border ?>" class="proimg text-center">
                        <?php $this->load->view('company/cart/partials/line_image', ['line' => $pro]); ?>
                    </td>
                    <td style="vertical-align: top;<?= $style_border ?>"><br><?= $pro->name ?></td>
                    <td style="vertical-align: top;text-align: center;<?= $style_border ?>" class="text-center">
                        <br><?= $pro->sku ?>
                    </td>

                    <td class="<?= (isset($request['hide_price'])?'text-right':'text-center') ?>"
                        style="vertical-align: top;<?= $style_border ?>">
                        <br>
                        <?= $pro->qty ?>
                    </td>

                    <td class="text-right" style="vertical-align: top;<?= $style_border ?>">
                        <br>
                        &nbsp;&nbsp;<?= $moneda . number_format($pro->precio_grupo, $format_numeric->digits, $format_numeric->radixPoint,$format_numeric->groupSeparator);?>
                        <br>
                    </td>
                    <td class="text-right" style="vertical-align: top;<?= $style_border ?>">
                        <br>
                        &nbsp;&nbsp;<?= $moneda . number_format($pro->importe, $format_numeric->digits, $format_numeric->radixPoint,$format_numeric->groupSeparator);?>
                    </td>
                </tr>
                <?php if ($pro->comment != null || $pro->comment != '') {
                ?>
                <tr>
                    <td colspan="<?= (isset($request['hide_price'])?'3':'4') ?>"
                        style="border:none !important;border-bottom: 1px solid #6c757d !important;"></td>
                    <td colspan="<?= (isset($request['hide_price'])?'2':'3') ?>" class="text-wrap text-right"
                        style="overflow-wrap: break-word; border:none !important;border-bottom: 0.3px solid #6c757d !important;">
                        <strong><?= $lang_order['comments'] ?>:</strong> <?= $pro->comment ?>
                    </td>

                    <?php }?>
                    <?php } ?>
                <tr>
                    <td colspan="6">
                        <strong><?= htmlspecialchars((string) ($lang_order['sub_total'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                    </td>
                    <td colspan="1" style="text-align: right;">
                        <?php
                        $totals = is_array($details_total ?? null)
                            ? $details_total
                            : (array) ($details_total ?? []);
                        $money = is_scalar($moneda ?? null) ? (string) $moneda : (string) (($moneda->symbol ?? null) ?: '$');
                        ?>
                        <strong><?= htmlspecialchars($money . (string) ($totals['sub_total'] ?? '0.00'), ENT_QUOTES, 'UTF-8') ?></strong>
                    </td>
                </tr>
                <tr>
                    <td colspan="6">
                        <strong><?= htmlspecialchars((string) ($lang_order['discount_total'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                    </td>
                    <td colspan="1" style="text-align: right;">
                        <strong><?= htmlspecialchars($money . (string) ($totals['discount_total'] ?? '0.00'), ENT_QUOTES, 'UTF-8') ?></strong>
                    </td>
                </tr>
                <tr>
                    <td colspan="6">
                        <strong><?= htmlspecialchars((string) ($lang_order['total'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                    </td>
                    <td colspan="1" style="text-align: right;">
                        <strong><?= htmlspecialchars($money . (string) ($totals['total_orden'] ?? '0.00'), ENT_QUOTES, 'UTF-8') ?></strong>
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
                        <a href="<?= base_url('catalogo/producto/carts?token='.$token) ?>" class=" btn btn-success"
                            style="text-decoration:none; color: white;width:70%;"><?= $lang_order['confirmar'] ?></a>
                       
                    </td>
                    <td style="width: 30%;"></td>
                </tr>
            </tbody>
        </table>
    </div>
</body>

</html>
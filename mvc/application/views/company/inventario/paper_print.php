<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css"
        integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <title>Order Inventory - <?=$order->order_number?></title>

    <?php  $this->load->view("company/inventario/css_impresion"); ?>

    <style>
    body {
        font-size: 12px !important;
    }

    .underline-text {
        text-decoration: underline;
    }
    </style>
</head>

<body>

    <table style="width: 100%;">
        <tbody>
            <tr>
                <th class="underline-text " style="text-align: center; width: 100%;">
                    <?= strtoupper($lang_order['titulo_inventario_impresion']) ?>
                </th>
            </tr>
        </tbody>
    </table>
    <br><br>
    <table id="table-encabezado" style="width: 100%;">
        <tbody>
            <tr>
                <td style="width:35%;vertical-align: top;">
                    <div align="left">
                        <strong><?= $lang_order['company_order'] ?>: </strong><?= $company_info->name ?><br>
                        <strong><?= $lang_order['warehouse_user'] ?>:</strong><?= $wuser->first_name.' '.$wuser->last_name ?><br>
                        <strong><?= $lang_order['origin'] ?>: </strong><?= $order->source ?>
                    </div>
                </td>
                <td style="width:30%;vertical-align: top;">
                    <div align="center">
                        <?php if ($company_info->image) { ?>
                        <img src="<?= company_logo_url((string) $company_info->image) ?>" class="rounded-0"
                            style="display: block; width: 9em; height: 8em;" alt="">
                        <?php } else{ ?>
                        <img src="<?= base_url('images/no_image_user.png') ?>" class="rounded-0"
                            style="display: block; width: 9em; height: 8em;" alt="">
                        <?php } ?>

                    </div>
                </td>
                <td style="width:35%;vertical-align:top;">
                    <table style="width: 100%;">
                        <tr>
                            <td><strong><?= $lang_order['fecha_creacion_order'] ?>: </strong></td>
                            <td><?= htmlspecialchars(fv_format_display_datetime($order->created), ENT_QUOTES, 'UTF-8') ?>hs</td>
                        </tr>
                        <tr>
                            <td><strong><?= $lang_order['order_orden_number'] ?>: </strong></td>
                            <td><?= $order->order_number ?></td>
                        </tr>

                    </table>

                </td>
            </tr>
        </tbody>
    </table>
    <br>
    <table id="table-detalle" style="width: 100%;">
        <thead class="table-secondary">
            <tr>
                <th style="width:5%;">#</th>
                <th style="width:12%;"><?= $lang_order['order_orden_number'] ?></th>
                <th style="width:10%;"><?= $lang_order['sku'] ?></th>
                <th style="width:10%; text-align: center;">
                    <?= $lang_order['image'] ?></th>
                <th style="width:20%;">
                    <?= $lang_order['product_name'] ?></th>
                <th style="width:13%;">
                    <?= $lang_order['category_view'] ?>
                </th>
                <th class="text-right"><?= htmlspecialchars($lang_order['stock_erp'] ?? '', ENT_QUOTES, 'UTF-8') ?></th>
                <th style="width:10%;text-align:right;"><?= $lang_order['cantidad'] ?></th>
                <th class="text-center"><?= $lang_order['comments'] ?></th>
            </tr>
        </thead>
        <tbody>
            <?php $cont = 1; ?>
            <?php foreach($detail as $pro):?>

            <tr>
                <td><?= $cont ?></td>
                <td class="text-wrap"><?= strtoupper($pro->sku) ?></td>
                <td>
                    <?php
                    $productImage = trim((string) ($pro->image ?? ''));
                    if ($productImage === '') {
                        $productImage = function_exists('catalog_public_no_image_url')
                            ? catalog_public_no_image_url()
                            : base_url('uploads/noimg.png');
                    }
                    $productImageSrc = $productImage;
                    if (strpos($productImageSrc, '?') === false && isset($version) && $version !== '') {
                        $productImageSrc .= '?v=' . rawurlencode((string) $version);
                    }
                    ?>
                    <figure
                        style="width: 4em; height: 4em; overflow:hidden; margin:auto;padding-top:4px;padding-bottom:4px;">
                        <span data-toggle="popover-hover" title="<?= htmlspecialchars((string) ($pro->sku ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                            data-img="<?= htmlspecialchars($productImageSrc, ENT_QUOTES, 'UTF-8') ?>"
                            data-original-title="<?= htmlspecialchars((string) ($pro->sku ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <img src="<?= htmlspecialchars($productImageSrc, ENT_QUOTES, 'UTF-8') ?>"
                                style="display: block; width: 100%; height: 100%; object-fit: cover;" class="rounded-0" alt="">
                        </span>
                    </figure>
                </td>
                <td class="text-wrap">
                    <a href="#"><?= $pro->name ?></a>
                </td>
                <td class="text-wrap pr-2">
                    <?= $pro->category_name ?>
                </td>
                <td class="text-right"><?= $pro->stock_ERP ?></td>
                <td class="text-wrap" style="text-align:right;">
                    <?php                     
                        
                        if ($pro->qty <= -1) {
                            $value = $lang_order["no_counted"];

                        }else{
                            $value = floatval($pro->qty);
                        }
                    ?>
                    <?= $value ?>
                </td>
                <td><?= $pro->comments ?></td>
            </tr>
            <?php $cont++; ?>
            <?php endforeach; ?>


        </tbody>
    </table>

</body>

</html>
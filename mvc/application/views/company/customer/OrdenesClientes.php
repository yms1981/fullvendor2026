<?php
/**
 * @var $lang_json Array The lang translations for every translatable string
 * @var $orders Object Order collection
 * @var $info_company Object Company info
 * @var $mostrarColumnaseller bool
 */

$langcliente = $lang_json['cmp_cliente']['lang'];
$currencySymbol = $info_company->currencies[0]->symbol;
?>
<div class="table-responsive bg-white">
    <table class="table w-100">
        <thead>
        <tr>
            <th class="ps-4" style="width:5%">Id</th>
            <?php if($mostrarColumnaseller): ?>
                <th style="width:30%"><?= $langcliente['seller']?></th>
            <?php endif;?>

            <th class="ps-4" style="width:10%"><?= $langcliente['orden_date'] ?></th>
            <th class="text-end w-25" ><?= $langcliente['total_amount'] ?></th>
            <th class="w-25 ps-5" style="width:30%"><?= $langcliente['status'] ?></th>
        </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $ord): ?>
                <tr>
                    <td class="p-4 text-nowrap">

                        <?php

                        $origin = null;
                        switch ($ord->source) {
                            case 'Catalog':
                                $origin= '<span class="fa-solid fa-globe text-success"></span>';
                                break;
                            case 'app':
                                $origin= '<span class="fa-solid fa-mobile text-success"></span>';
                                break;
                            case 'manual':
                                $origin= '<span class="fa-sharp fa-solid fa-house text-success"></span>';
                                break;
                        }

                        ?>

                        <?php
                        $detailHref = seller_url('history/order/') . $ord->order_id;
                        if (! empty($urlDetalleOrden)) {
                            $detailHref = rtrim((string) $urlDetalleOrden, '/') . '/' . (int) $ord->order_id;
                            if (! empty($catalog_token)) {
                                $detailHref .= '?token=' . rawurlencode((string) $catalog_token);
                            }
                        }
                        ?>

                        <a href="<?= $detailHref ?>"><?= $origin . ' ' . $ord->order_number ?></a>
                    </td>
                    <?php if($mostrarColumnaseller): ?>
                        <td class="p-4 text-wrap"><?= $ord->vendedor ?></td>
                    <?php endif;?>

                    <td class="p-4 text-nowrap"><?= fv_format_display_datetime($ord->created ?? null) ?></td>
                    <td class="p-4 text-end"><?= $currencySymbol.$ord->total?></td>
                    <td class="p-4 ps-5">
                        <div class="btn-groups" style="position: static;">
                            <?php
                                $color = $ord->status_order->color;
                                $name_status =$ord->status_order->name_status_english; //localize
                            ?>
                            <span class="bg-secondary p-2 pe-4 rounded text-white d-inline-flex align-items-center w-100">
                                <span class='roundblock notranslate' style='background: <?= $color ?>;'></span>
                                <span>&nbsp;<?= $name_status ?></span>
                            </span>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>


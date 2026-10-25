<?php
$compName=$this->session->userdata('name');

$ahora = DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));
if (! $ahora instanceof DateTime) {
    $ahora = new DateTime();
}
$v_imagen = $ahora->format("YmdHisu");
$compId = $this->session->userdata('compId');
$moneda = ! empty($cursumbol) ? (string) $cursumbol : '$';
if (! isset($format_numeric) || ! is_object($format_numeric)) {
    $format_numeric = (object) [];
}
$fnDigits = (int) ($format_numeric->digits ?? 2);
$fnRadix = (string) ($format_numeric->radixPoint ?? '.');
$fnGroup = (string) ($format_numeric->groupSeparator ?? ',');
$fvFmt = static function ($value) use ($fnDigits, $fnRadix, $fnGroup): string {
    return number_format((float) ($value ?? 0), $fnDigits, $fnRadix, $fnGroup);
};
$lang_order = is_array($lang_order ?? null) ? $lang_order : [];
$detalle_list = is_array($detalle_list ?? null) ? $detalle_list : [];
$statuslist = is_array($statuslist ?? null) ? $statuslist : [];
$productos_pedidos = is_array($productos_pedidos ?? null) ? $productos_pedidos : [];
$productList = is_array($productList ?? null) ? $productList : [];
$warehouseusers = is_array($warehouseusers ?? null) ? $warehouseusers : [];
$customer = is_object($customer ?? null) ? $customer : null;
$seller = is_object($seller ?? null) ? $seller : null;
$wuser = is_object($wuser ?? null) ? $wuser : null;
$data = is_object($data ?? null) ? $data : (object) [];
$orderDiscount = (float) ($data->discount ?? 0);
$orderDiscountType = (int) ($data->discount_type ?? 0);
$deliveryNotesText = (string) ($data->delivery_notes ?? '');
if ($deliveryNotesText === '' && $customer !== null) {
    $deliveryNotesText = (string) ($customer->dispatch_shipping_notes ?? '');
}
?>

<div class="container-fluid page-body-wrapper pt-3">
    <div class="main-panel">
        <div class="container-fluid contentHeight">
            <div class="row">
                <div class="col-md-6">
                    <div class="page-header">
                        <h3 class="page-title"> <?= $lang_order['titulo_view_previa_detail'] ?></h3>
                    </div>
                </div>
                <div class="col-md-6 text-right">

                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <div class="mt-2">
                                    <a href="javascript:void(0);" onclick="return printOrder();"
                                        class="btn btn-success"><i class="fa fa-print" aria-hidden="true"></i>
                                        <?= $lang_order['imprimir_orden'] ?>
                                    </a>

                                    <button data-toggle="modal" data-target="#modal_export" class="btn btn-success"><i
                                            class="fa fa-cloud-download" aria-hidden="true"></i>
                                        <?= $lang_order['download_csv']?> </button>


                                    <a href="javascript:void(0)" class="btn btn-success" data-toggle="modal"
                                        data-target="#exampleModalwarehouse" class="btn btn-success"><i
                                            class="fa fa-paper-plane-o" aria-hidden="true"></i>
                                        <?= $lang_order['almacen_asigned']?></a>

                                    <a class="btn btn-danger" href="javascript:void(0);"
                                        onclick="javascript:history.go(-1);">
                                        <i class="fa fa-arrow-left"></i> <?= $lang_order['atras']?></a>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6 mb-3">
                            <p class="mb-1"><strong><?=$lang_order['customer'] ?>:</strong>
                                <span><?= htmlspecialchars((string) (@$customer->business_name ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    <?php
                                    $customerContact = trim((string) (@$customer->name ?? ''));
                                    if ($customerContact !== '') :
                                    ?>
                                    <strong>(<?= htmlspecialchars(strtoupper($customerContact), ENT_QUOTES, 'UTF-8') ?>)</strong>
                                    <?php endif; ?>
                                </span>
                            </p>
                            <p class="mb-1"><strong><?= $lang_order['phone_order'] ?>:
                                </strong><span><?= htmlspecialchars((string) (@$customer->phone ?? ''), ENT_QUOTES, 'UTF-8') ?></span></p>
                            <p class="mb-1"><strong><?= $lang_order['seller_order'] ?>:
                                </strong><span><?php
                                    $sellerShow = '';
                                    if ($seller !== null && is_object($seller)) {
                                        $sellerShow = trim((string) ($seller->first_name ?? '') . ' ' . (string) ($seller->last_name ?? ''));
                                        if ($sellerShow === '' && isset($seller->name)) {
                                            $sellerShow = (string) $seller->name;
                                        }
                                    }
                                    echo htmlspecialchars($sellerShow, ENT_QUOTES, 'UTF-8');
                                ?></span>
                            </p>
                            <!-- <?php if(@$data->approved_by !=""){ ?>
                            <p class="mb-1 text-primary fs-13"><?= $lang_order['aprobado_por'] ?>: <span class="notranslate"> <?=@$compName?></span></p>
                            <?php } ?> -->
                            <p class="mb-1"><strong><?= htmlspecialchars((string) ($lang_order['origin'] ?? 'Origin'), ENT_QUOTES, 'UTF-8') ?>: </strong><span class="notranslate">
                                    <?= htmlspecialchars((string) (@$data->source ?? ''), ENT_QUOTES, 'UTF-8') ?></span></p>
                        </div>
                        <div class="col-lg-6  mb-3 text-right">
                            <p class="mb-1"><strong><?= $lang_order['fecha_creacion_order'] ?>:
                                </strong><?= ! empty($data->created) ? htmlspecialchars(fv_format_display_datetime((string) $data->created), ENT_QUOTES, 'UTF-8') . ' hs' : '' ?></p>
                            <p class="mb-1"><strong><?= $lang_order['order_orden_number'] ?>:
                                </strong><?= htmlspecialchars((string) (@$data->order_number ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="mb-1">
                                <?php
                                $estad_texto = '';
                                $color = '';
                                $statusMeta = $order_status_meta ?? null;
                                if (! is_object($statusMeta) && ! empty($statuslist) && isset($data->order_status)) {
                                    $orderStatusCod = (int) $data->order_status;
                                    foreach ($statuslist as $statusRow) {
                                        if (! is_object($statusRow)) {
                                            continue;
                                        }
                                        if ((int) ($statusRow->cod_status ?? 0) === $orderStatusCod) {
                                            $statusMeta = $statusRow;
                                            break;
                                        }
                                    }
                                }
                                if (is_object($statusMeta)) {
                                    $color = (string) ($statusMeta->color ?? '');
                                    $estad_texto = (string) ($statusMeta->name_status ?? $statusMeta->name_status_spanish ?? $statusMeta->name_status_english ?? '');
                                }
                                ?>
                                <span class="roundblock notranslate" style="background: <?= htmlspecialchars($color, ENT_QUOTES, 'UTF-8') ?>;"></span>
                                <?= htmlspecialchars($estad_texto, ENT_QUOTES, 'UTF-8') ?>
                            </p>

                            <?php if($data->warehouse_user_id && $wuser){ ?>
                            <p class="mb-1"><strong><?=@$wuser->first_name.' '.$wuser->last_name?></strong></p>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="form-group">
                        <?php $array_status = [9,11,12];?>

                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th style="vertical-align: top;" class="text-center"><?= $lang_order['image'] ?></th>
                                        <th style="vertical-align: top;" width="30%"><?= $lang_order['product_name'] ?>
                                        </th>
                                        <th style="vertical-align: top;" width="12%">Sku</th>
                                        <th style="vertical-align: top;" width="8%" class="text-center">
                                            <?= htmlspecialchars((string) ($lang_order['unit_type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                                        <th style="vertical-align: top;" width="6.5%" class="text-center">
                                            <?= $lang_order['cantidad'] ?></th>

                                        <!-- CANTIDAD ASIGNADA -->

                                        <?php if(isset($mostrar_asignados) && $mostrar_asignados == true): ?>
                                        <th style="vertical-align: top;" width="6.5%"
                                            class="text-center text-wrap text-success">Quantity Asigned</th>


                                        <?php endif; ?>
                                        <th style="vertical-align: top;" width="10%" class="text-center">Stock</th>
                                        <th style="vertical-align: top;" width="10%" class="text-center">
                                            <?= $lang_order['sale_price'] ?></th>
                                        <th style="vertical-align: top;" width="10%" class="text-center">Total Pedido
                                        </th>

                                        <!-- TOTAL ASIGNADO -->

                                        <?php if(isset($mostrar_asignados) && $mostrar_asignados == true): ?>
                                        <th style="vertical-align: top;" width="10%"
                                            class="text-center text-wrap text-success">Total Asignado</th>

                                        <?php endif; ?>
                                        <?php if(isset($mostrar_boton_eliminar)): ?>
                                        <th style="vertical-align: top;" width="5%" class="text-center">
                                            <?= $lang_order['action'] ?></th>
                                        <?php endif; ?>

                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i = 0;
                                            $totprc=0;
                                            $totqty=0;
                                            $total_orden = null;
                                            $total_orden_asignada = null;
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
                                        <td><br><br><?= $i ?></td>
                                        <td class="proimg text-center align-middle" style="vertical-align: top;">
                                            <br><br>
                                            <div class="position-relative d-inline-block fv-order-line-thumb">
                                                <?php
                                                $mostrar_estrella = false;
                                                if (in_array($pro->pro_id, array_column($productos_pedidos, 'product_id'))) {
                                                    $mostrar_estrella = true;
                                                }
                                                if ($mostrar_estrella) {
                                                    ?>
                                                <a data-toggle="modal" data-id="<?= $pro->pro_id ?>"
                                                    data-target="#modal-histoy" style="color: #ffc107;cursor: pointer;z-index:2;"
                                                    class="position-absolute top-0 start-0"
                                                    title="<?= $lang_catalogo['sold'] ?>">
                                                    <i class="fa-solid fa-star"></i>
                                                </a>
                                                <?php } ?>
                                                <?php $this->load->view('company/product/partials/thumbnail', ['listItem' => $pro]); ?>
                                            </div>
                                        </td>
                                        <td style="vertical-align: top;"><br><br>
                                            <?= $pro->name ?>
                                            <?= ($pro->status == 0)?'<span class="badge badge-danger">'. $lang_order['producto_innactivo'] .'</span>':'' ?>
                                        </td>
                                        <td style="vertical-align: top;">
                                            <br><br>
                                            <a href="<?= company_url('products/edit/'.(int) ($pro->pro_id ?? 0))?>"
                                                title="<?= htmlspecialchars((string) ($lang_order['edit'] ?? $lang_order['toltip_edit'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($pro->sku ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                                            <?php if($pro->barcode != null || $pro->barcode != ''){?>
                                            <a title="Barcode: <?= $pro->barcode ?>"><img
                                                    src="<?= base_url('images/codbarra.png')?>"
                                                    alt="<?= $pro->barcode ?>" class="img-fluid img-thumbnail"
                                                    style="width: 25px; height: 25px;">
                                            </a>
                                            <?php }?>
                                        </td>
                                        <?php
                                        $unitLabel = function_exists('fv_product_unit_label') ? fv_product_unit_label($pro) : '';
                                        $fmtQty = static function ($value) use ($pro, $fnRadix, $fnGroup): string {
                                            if (function_exists('fv_format_unit_quantity')) {
                                                return fv_format_unit_quantity($value, $pro, $fnRadix, $fnGroup);
                                            }

                                            return number_format((float) ($value ?? 0), 0, $fnRadix, $fnGroup);
                                        };
                                        ?>
                                        <td style="vertical-align: top;" class="text-center notranslate">
                                            <br><br>
                                            <strong><?= $unitLabel !== '' ? htmlspecialchars($unitLabel, ENT_QUOTES, 'UTF-8') : '—' ?></strong>
                                        </td>
                                        <td style="vertical-align: top;" class="text-center">
                                            <br><br>
                                            <strong>
                                                <span><?= $fmtQty($pro->qty ?? 0) ?></span></strong>
                                        </td>

                                        <?php if(isset($mostrar_asignados) && $mostrar_asignados == true): ?>
                                        <td style="vertical-align: top;" class="text-center text-success">
                                            <br><br>
                                            <strong>
                                                <span>
                                                    <?= $fmtQty($pro->delivered_quantity ?? 0) ?>
                                                </span>
                                            </strong>
                                        </td>

                                        <?php endif; ?>
                                        <!-- Stock -->
                                        <td class="text-center"><br><br><strong><?= $fmtQty($pro->stock ?? 0) ?></strong></td>
                                        <td style="vertical-align: top;" class="text-center">
                                            <br><br>
                                            <strong>
                                                <span><?= $moneda. $fvFmt($pro->precio_cliente ?? 0) ?></span></strong>
                                            <span class="fixed-price-labels" style="display: none;">
                                            <?php if(!empty($pro->modify_date)){?>
                                            <br>
                                            <span class="badge badge-pill"
                                                style="background: #ffc107;"><?= $lang_order['price_modified']?></span>
                                            <?php if(!empty($pro->fixed_pricedate)){?>
                                            <br>
                                            <span class="badge badge-pill"
                                                style="background: #ffc107;"><?= $lang_order['price_fixedprice'] ?></span>
                                            <?php }else{ ?>
                                            <br>
                                            <span class="badge badge-pill"
                                                style="background: #ffc107;"><?= $lang_order['price_notfixedprice'] ?></span>
                                            <?php }?>
                                            <?php }else{?>
                                            <?php if(!empty($pro->fixed_pricedate)){?>
                                            <br>
                                            <span class="badge badge-pill"
                                                style="background: #ffc107;"><?= $lang_order['price_fixedprice'] ?>
                                            </span>
                                            <?php } ?>
                                            <?php }?>
                                            </span>
                                        </td>

                                        <td class="text-right" style="vertical-align: top;">
                                            <br><br>
                                            <strong>
                                                <?php
                                                $total = $fvFmt($pro->total_importe ?? 0);
                                                ?>
                                                <span class="currency input"
                                                    data-name="detalle[<?= $i ?>][importe_total]"
                                                    value=""><?= $moneda. $total ?></span>
                                            </strong>

                                        </td>

                                        <?php if(isset($mostrar_asignados) && $mostrar_asignados == true): ?>
                                        <td class="text-right text-success" style="vertical-align: top;">
                                            <br><br>
                                            <strong><span>&nbsp;&nbsp;<?= $moneda ?>&nbsp;</span>

                                                <strong><span class="currency input"
                                                        data-name="detalle[<?= $i ?>][importe_total]"><?= $fvFmt($pro->total_importe_asignado ?? 0) ?></span></strong>

                                        </td>

                                        <?php endif; ?>

                                        <?php if(isset($mostrar_boton_eliminar)): ?>
                                        <td class="text-right" style="vertical-align: top;">
                                            <br><br>
                                            <a title="<?= $lang_order['tol-delete'] ?>" data-toggle="tooltip"
                                                data-group-default="" href="javascript:void(0)"
                                                onclick="deleteOrderdetails('<?=$pro->detail_id?>')"
                                                class="btn btn-danger btn-go-delete btn-sm"><i
                                                    class="mdi mdi-delete"></i></a>
                                        </td>
                                        <?php endif; ?>

                                    </tr>

                                    <?php if ($pro->comments != null || $pro->comments != '') {?>
                                    <tr>
                                        <td colspan="4"
                                            style="border:none !important;border-bottom: 2px solid #e4e9f0 !important;">
                                        </td>
                                        <td colspan="4" class="text-wrap text-right"
                                            style="overflow-wrap: break-word; border:none !important;border-bottom: 2px solid #e4e9f0 !important;">
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
                                        switch ($orderDiscountType) {
                                        case 1:
                                        // en porcentaje
                                        $discount_total = $total_orden * ($orderDiscount / 100);
                                        $discount_total_asignada = $total_orden_asignada * ($orderDiscount / 100);

                                        // total asignada
                                        $total_orden = $total_orden - $discount_total;
                                        $total_orden_asignada = $total_orden_asignada- $discount_total_asignada;
                                        break;
                                        case 2:
                                        //en el monto
                                        $discount_total = $orderDiscount;
                                        $discount_total_asignada = $orderDiscount;

                                        //total asignada
                                        $total_orden = $total_orden - $discount_total;
                                        $total_orden_asignada = $total_orden_asignada - $discount_total_asignada;
                                        break;
                                        default:
                                        $discount_total = 0;
                                        $discount_total_asignada = 0;
                                        break;
                                        }


                                        $total_orden = $fvFmt($total_orden);
                                        $discount_total =  $fvFmt($discount_total);

                                        $discount_total_asignada = $fvFmt($discount_total_asignada);

                                        $sub_total =  $fvFmt($sub_total);

                                        $sub_total_asignada = $fvFmt($sub_total_asignada);

                                        $total_orden_asignada =$fvFmt($total_orden_asignada);

                                        ?>
                                    <!-- SUB TOTAL -->
                                    <tr class="notranslate">

                                        <?php if(isset($mostrar_asignados) && $mostrar_asignados == true){ ?>
                                        <td
                                            colspan="<?= (in_array($data->order_status,$array_status) && $data->warehouse_user_id != null)?8:7 ?>">
                                            <strong>Sub Total</strong>
                                        </td>
                                        <?php }else{ ?>
                                        <td colspan="7">
                                            <strong>Sub Total</strong>
                                        </td>
                                        <?php } ?>

                                        <td class="text-right">
                                            <strong
                                                id="sub_total"><?= $moneda. $sub_total ?></strong>
                                        </td>
                                        <?php if(isset($mostrar_asignados) && $mostrar_asignados == true): ?>

                                        <td class="text-right text-success">
                                            <strong><?= $moneda .' '?></strong><strong><?= $sub_total_asignada ?></strong>
                                        </td>

                                        <?php endif; ?>

                                        <?php if(isset($mostrar_boton_eliminar)): ?>
                                        <td></td>
                                        <?php endif; ?>

                                    </tr>
                                    <!-- DESCUENTO -->
                                    <tr class="notranslate">
                                        <?php

                                        $descuento_verificable = $orderDiscount;
                                        $type = null;

                                        switch ($orderDiscountType) {
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

                                        <?php if(isset($mostrar_asignados) && $mostrar_asignados == true){ ?>
                                        <td
                                            colspan="<?= (in_array($data->order_status,$array_status) && $data->warehouse_user_id != null)?8:7 ?>">
                                            <strong><?= $lang_order['discount_total']?> (<span
                                                    id="span_discount"><?= $type ?></span>)</strong>
                                        </td>
                                        <?php }else{ ?>
                                        <td colspan="7">
                                            <strong><?= $lang_order['discount_total']?> (<span
                                                    id="span_discount"><?= $type ?></span>)</strong>
                                        </td>

                                        <?php } ?>

                                        <td class="text-right">
                                            <strong
                                                id="total_descuento"><?= $moneda. $discount_total ?></strong>
                                        </td>

                                        <?php if(isset($mostrar_asignados) && $mostrar_asignados == true): ?>
                                        <td class="text-right text-success">
                                            <strong><?= $moneda. $discount_total_asignada ?></strong>
                                        </td>

                                        <?php endif; ?>

                                        <?php if(isset($mostrar_boton_eliminar)): ?>
                                        <td></td>
                                        <?php endif; ?>
                                    </tr>
                                    <!-- TOTAL -->
                                    <tr class="notranslate" style="background: #eee;">

                                        <?php if(isset($mostrar_asignados) && $mostrar_asignados == true){ ?>
                                        <td colspan="<?= (in_array($data->order_status,$array_status) && $data->warehouse_user_id != null)?8:7 ?>"
                                            class="text-left"><strong><?= $lang_order['price_total'] ?></strong>
                                        </td>
                                        <?php }else{ ?>
                                        <td colspan="7" class="text-left text-nowrap">
                                            <strong><?= $lang_order['price_total'] ?></strong>
                                        </td>
                                        <?php } ?>
                                        <td class="text-right">
                                            <strong id="total_orden"><?=  $moneda. $total_orden ?></strong>
                                        </td>

                                        <?php if(isset($mostrar_asignados) && $mostrar_asignados == true): ?>
                                        <td class="text-right text-success">
                                            <strong><?= $moneda. $total_orden_asignada ?></strong>
                                        </td>
                                        <?php endif; ?>
                                        <?php if(isset($mostrar_boton_eliminar)): ?>
                                        <td></td>
                                        <?php endif; ?>
                                    </tr>
                                </tbody>
                            </table>
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
                                    <th scope="col" class="text-right"></th>
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
        <!-- MODAL IMPRIMIR ORDEN -->
        <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header pt-3 pb-3">
                        <h5 class="modal-title"><?= $lang_order['imprimir_orden'] ?></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="form-imprimir_orden" target="_blank" action="<?= company_url('orders/generatePdf')?>"
                            method="post">
                            <div class="mb-3">
                                <label class="fs-13"><?= $lang_order['nota_entrega'] ?></label>
                                <textarea name="delivery_notes" rows="6"
                                    class="form-control"><?= htmlspecialchars($deliveryNotesText, ENT_QUOTES, 'UTF-8') ?></textarea>
                                <input type="hidden" value="<?=@$data->order_id?>" class="form-control" name="order_id">
                                <input id="notas" name="notas" type="text" hidden>
                                <!-- <p><?= $lang_order['leyenda_nota'] ?></p> -->
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button id="btn-con" type="button"
                            class="btn btn-success"><?= $lang_order['imprinir_connotas'] ?></button>
                        <button id="btn-sin" type="button"
                            class="btn btn-success"><?= $lang_order['imprimir_sinnotas'] ?></button>
                    </div>
                </div>
            </div>
        </div>

        <!-- modal deposito -->
        <div class="modal fade" id="exampleModalwarehouse" tabindex="-1" role="dialog"
            aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header pt-3 pb-3">
                        <h5 class="modal-title"><?= $lang_order['asignar_orden_deposito']?></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="submit_order_asigned" action="<?= company_url('orders/assignWarehouse')?>"
                            method="post">
                            <div class="mb-3">
                                <label class="fs-13"><?= $lang_order['deposito'] ?></label>
                                <select name="warehouse_id" class="form-control notranslate" required>
                                    <option value selected><?= $lang_order['seleccionar_por']?></option>
                                    <?php foreach ($warehouseusers as $key => $v): ?>
                                    <option value="<?= $v->user_id ?>">
                                        <?= $v->first_name.' '.$v->last_name ?>
                                    </option>
                                    <?php endforeach ?>

                                </select>
                                <input type="hidden" value="<?=@$data->order_id?>" class="form-control" name="order_id">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">

                        <button form="submit_order_asigned" type="submit"
                            class="btn btn-success"><?= $lang_order['asignar'] ?></button>
                    </div>
                </div>
            </div>
        </div>


        <div class="modal fade" id="exampleModalAddItem" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header pt-3 pb-3">

                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form id="form-add-order-line" action="<?= company_url('orders/addOrderLine')?>" method="post">
                            <input type="hidden" name="redirect_warehouse" value="1">
                            <div class="mb-3">
                                <label class="fs-13">Choose Product</label>
                                <div class="select-list">
                                    <select class="select notranslate" name="product_id">
                                        <?php if(is_array($productList) && count($productList)>0){
                                        foreach ($productList as $pls) {?>
                                        <option value="<?=@$pls->pro_id?>"><?=@$pls->name?>(<?=@$pls->sku?>)</option>
                                        <?php } } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="fs-13">Quantity</label>
                                <input class="form-control" type="number" min="1" name="qty">
                                <input type="hidden" value="<?=@$data->order_id?>" class="form-control" name="order_id">
                            </div>
                            <div class="mb-3">
                                <input type="submit" value="Add" class="btn btn-success">
                            </div>
                    </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <!-- Modal Exportacion Excel -->
    <div class="modal fade" id="modal_export" tabindex="-1" role="dialog" aria-labelledby="modal_export_title"
        aria-hidden="true" data-fv-modal-icon="mdi-microsoft-excel">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal_export_title">
                        <?= htmlspecialchars(strtoupper((string) ($lang_order['order_details_export'] ?? $lang_order['titulo_exportacion'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                    </h5>
                    <button type="button"
                            class="close fv-modal-close-x"
                            data-dismiss="modal"
                            aria-label="<?= htmlspecialchars((string) ($lang_order['cerrar'] ?? $lang_order['cancel'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fa fa-times" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-0">
                        <label><?= htmlspecialchars((string) ($lang_order['excel_file'] ?? ''), ENT_QUOTES, 'UTF-8') ?>: <span class="color-red">*</span></label>
                        <div class="clearfix margin-top-10 m-b-10 f12" style="display: block;">
                            <span class="label label-danger"><?= htmlspecialchars((string) ($lang_order['format_excel'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <p class="text-muted mb-0 small">
                            <?= htmlspecialchars((string) ($lang_order['export_excel_hint'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= htmlspecialchars((string) ($lang_order['cancel'] ?? $langprods['btn-cancelar'] ?? ''), ENT_QUOTES, 'UTF-8') ?></button>
                    <button type="button" id="btn-export" class="btn btn-danger btn-icon-text">
                        <i class="mdi mdi-download" aria-hidden="true"></i>
                        <?= htmlspecialchars((string) ($lang_order['bnExport_modal'] ?? $lang_order['bnExport'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.order_id = '<?= (int) ($data->order_id ?? 0) ?>';
window.fvOrderExportId = <?= (int) ($data->order_id ?? 0) ?>;
const order_id = window.order_id;


function qtyonedit(detailId) {
    var qqty = $("#proqty" + detailId).text();
    var qsaleprc = $("#qsaleprc" + detailId).text();
    $.ajax({
            url: '<?=company_url("orders/updateOrderLine")?>',
            type: 'POST',
            dataType: 'JSON',
            data: {
                detailId: String(detailId),
                qqty: String(qqty),
                qsaleprc: String(qsaleprc),
            },
        })
        .done(function(datalist) {
            $("#proqty" + detailId).text(datalist.uqty);
            $("#qsaleprc" + detailId).text(datalist.usaleprice);
            $("#qtprice" + detailId).text(datalist.utprice);
            if (datalist.msg == 0) {
                swal({
                    title: "Order deleted",
                    type: 'success',
                    confirmButtonColor: '#A5DC86'
                });
            }
            window.location.reload();
        })

}

function create_custom_dropdowns() {
    $('.select').each(function(i, select) {
        if (!$(this).next().hasClass('dropdown-select')) {
            $(this).after('<div class="dropdown-select wide ' + ($(this).attr('class') || '') +
                '" tabindex="0"><span class="current"></span><div class="list"><ul></ul></div></div>');
            var dropdown = $(this).next();
            var options = $(select).find('option');
            var selected = $(this).find('option:selected');
            dropdown.find('.current').html(selected.data('display-text') || selected.text());
            options.each(function(j, o) {
                var display = $(o).data('display-text') || '';
                dropdown.find('ul').append('<li class="option ' + ($(o).is(':selected') ?
                        'selected' : '') + '" data-value="' + $(o).val() +
                    '" data-display-text="' + display + '">' + $(o).text() + '</li>');
            });
        }
    });

    $('.dropdown-select ul').before(
        '<div class="dd-search"><input id="txtSearchValue" autocomplete="off" onkeyup="filter()" class="dd-searchbox" type="text"></div>'
    );
}

// Event listeners

// Open/close
$(document).on('click', '.dropdown-select', function(event) {
    if ($(event.target).hasClass('dd-searchbox')) {
        return;
    }
    $('.dropdown-select').not($(this)).removeClass('open');
    $(this).toggleClass('open');
    if ($(this).hasClass('open')) {
        $(this).find('.option').attr('tabindex', 0);
        $(this).find('.selected').focus();
    } else {
        $(this).find('.option').removeAttr('tabindex');
        $(this).focus();
    }
});

// Close when clicking outside
$(document).on('click', function(event) {
    if ($(event.target).closest('.dropdown-select').length === 0) {
        $('.dropdown-select').removeClass('open');
        $('.dropdown-select .option').removeAttr('tabindex');
    }
    event.stopPropagation();
});

function filter() {
    var valThis = $('#txtSearchValue').val();
    $('.dropdown-select ul > li').each(function() {
        var text = $(this).text();
        (text.toLowerCase().indexOf(valThis.toLowerCase()) > -1) ? $(this).show(): $(this).hide();
    });
};
// Search

// Option click
$(document).on('click', '.dropdown-select .option', function(event) {
    $(this).closest('.list').find('.selected').removeClass('selected');
    $(this).addClass('selected');
    var text = $(this).data('display-text') || $(this).text();
    $(this).closest('.dropdown-select').find('.current').text(text);
    $(this).closest('.dropdown-select').prev('select').val($(this).data('value')).trigger('change');
});

// Keyboard events
$(document).on('keydown', '.dropdown-select', function(event) {
    var focused_option = $($(this).find('.list .option:focus')[0] || $(this).find('.list .option.selected')[
        0]);
    // Space or Enter
    //if (event.keyCode == 32 || event.keyCode == 13) {
    if (event.keyCode == 13) {
        if ($(this).hasClass('open')) {
            focused_option.trigger('click');
        } else {
            $(this).trigger('click');
        }
        return false;
        // Down
    } else if (event.keyCode == 40) {
        if (!$(this).hasClass('open')) {
            $(this).trigger('click');
        } else {
            focused_option.next().focus();
        }
        return false;
        // Up
    } else if (event.keyCode == 38) {
        if (!$(this).hasClass('open')) {
            $(this).trigger('click');
        } else {
            var focused_option = $($(this).find('.list .option:focus')[0] || $(this).find(
                '.list .option.selected')[0]);
            focused_option.prev().focus();
        }
        return false;
        // Esc
    } else if (event.keyCode == 27) {
        if ($(this).hasClass('open')) {
            $(this).trigger('click');
        }
        return false;
    }
});

$(document).ready(function() {

    $('#selecte2prueba').select2({
        insertTag: function(data, tag) {
            data.push(tag);
        }
    });

    create_custom_dropdowns();

    $('[data-toggle="popover-hover"]').popover({
        html: true,
        trigger: 'hover',
        placement: 'left',
        sanitize: false,
        content: function() {
            var $el = $(this);
            var img = $el.attr('data-img') || '';
            var $thumb = $el.find('img').first();
            if ($thumb.length) {
                var liveSrc = $thumb.attr('src') || '';
                if (liveSrc && liveSrc.indexOf('data:') !== 0) {
                    img = liveSrc;
                }
            }
            return img ? '<img src="' + img + '" class="img-thumbnail" />' : '';
        }
    });


    $(document).on('show.bs.modal', '#modal-histoy', function(e) {

        const pro_id = $(e.relatedTarget).data('id');
        const productName = $(e.relatedTarget).data('name-product') || '';
        $('#modal-histoy').find('.product-name-vendido').text(productName);
        $('#modal-histoy').find('.table tbody').html('');

        $.ajax({
            url: '<?= company_url('orders/getHistorial') ?>',
            type: 'post',
            data: {
                pro_id: pro_id,
                order_id: order_id
            },
            dataType: 'json',
            success: function(response) {
                $('#modal-histoy').find('.table tbody').html(response.tbody || '')
            }
        })
    })

});

function printOrder() {
    $('#exampleModal').modal('show');
    return true;
}


function copy_saleprice() {

    const sale_price = event.currentTarget.getAttribute('data-copy-price')
    copiarAlPortapapeles(sale_price);

    swal({
        title: '<?= $lang_order['copied'] ?>',
        icon: 'success',
        button: {
            text: "OK",
            value: true,
            visible: true,
            className: "btn btn-success"
        }
    })

}



$(document).on('click', '#btn-con', function(e) {
    $('#form-imprimir_orden #notas').val('con')
    $('#form-imprimir_orden').submit()
})
$(document).on('click', '#btn-sin', function(e) {


    $('#form-imprimir_orden #notas').val('sin')
    $('#form-imprimir_orden').submit()
})


$(document).on('submit', '#form_enviar_orden', function(e) {

    e.preventDefault()

    $.ajax({
        url: $(this).attr('action'),
        type: 'post',
        data: $(this).serializeArray(),
        dataType: 'json',
        beforeSend: function() {

            $('#btn-send-email').html(
                '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> <?= $lang_order['order_sending'] ?>'
            );
            $('#btn-send-email').prop('disabled', true);

        },
        success: function(response) {

            $('#btn-send-email').prop('disabled', false);
            $('#btn-send-email').html('<?= $lang_order['send'] ?>')
            $('#modalSendOrden').modal('hide')

            if (response == 'ok') {
                window.history.go(-1)
            }

        }

    })

})

//btn-opciones_detalles
$(document).on('click', '.btn-opciones_detalles', function(e) {
    e.preventDefault();
    $.ajax({
        url: $(this).attr('href'),
        dataType: 'json',
        type: 'get',
        success: function(response) {

            if (response == 'ok') {
                window.history.go(-1)
            }
        }

    })


})
//submit_order_asigned

$(document).on('submit', '#submit_order_asigned', function(e) {
    e.preventDefault()
    $.ajax({
        url: $(this).attr('action'),
        type: 'post',
        data: $(this).serializeArray(),
        dataType: 'json',
        success: function(response) {
            if (response && (response.success || response.message)) {
                window.history.go(-1)
            }
        }
    })
})

</script>

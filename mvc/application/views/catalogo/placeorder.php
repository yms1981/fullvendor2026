<?php
$catalogUriIndex = isset($catalog_uri_index) ? (string) $catalog_uri_index : 'catalogo/producto';
?>
<!--breadcrumbs area start-->
<div class="breadcrumbs_area">
    <div class="container">

        <div class="row">

            <div class="col-md-12 col-lg-6">
                <div class="breadcrumb_content">
                    <ul>
                        <li><a href="javascript:history.go(-1)"><?= $lang_catalogo['home'] ?></a></li>
                        <li><?= $lang_catalogo['my_order'] ?></li>
                    </ul>
                </div>
            </div>
            <div class="col-md-12 col-lg-6" style="text-align:right;">
                <?php
                $cancelOrderText = isset($lang_catalogo['cancel_order']) && $lang_catalogo['cancel_order'] !== '' ? $lang_catalogo['cancel_order'] : (isset($info_company->language_id) && (int)$info_company->language_id === 2 ? 'Cancelar orden' : 'Cancel Order');
                if (! empty($company->return_admin)) {
                    $cancelOrderHref = company_url('orders');
                } elseif (isset($company->type_cart) && $company->type_cart === 'to_customer') {
                    $cancelOrderHref = base_url($catalogUriIndex) . '?token=' . rawurlencode((string) ($company->token_kor ?? ''));
                } else {
                    $cancelOrderHref = company_url('orders');
                }
                ?>
                <a href="<?= $cancelOrderHref ?>" class="btn btn-danger" type="button"><?= $cancelOrderText ?></a>
            </div>
        </div>
    </div>
</div>
<div class="checkout_page_bg">

    <div class="container">

        <div class="Checkout_section">

            <div id="card-empty" class="row <?= $qty_car>0?'d-none':'' ?>">

                <div class="col-12">

                    <div class="user-actions">
                        <h3>
                            <i class="fa fa-file-o" aria-hidden="true"></i>
                            <?= $lang_catalogo['msg_unable'] ?> <a class="Returning"
                                href="javascript:history.go(-1)"><?= $lang_catalogo['here'] ?></a>
                            <?= $lang_catalogo['msg_unable_2']?>
                        </h3>
                    </div>
                </div>
            </div>

            <div id="order-complete" class="checkout_form <?= $qty_car<=0?'d-none':'' ?>">
                <div class="row">
                    <div class="col-lg-5 col-md-12 <?= ($company->type_cart)=='to_company_no'?'d-none':'' ?>">
                        <div class="checkout_form_left">
                            <form id="form-order" class="needs-validation" method="post"
                                action="<?= base_url('catalogo/producto/place_order') ?>" novalidate>
                                <h3 class="bg-success"><?= $lang_catalogo['send']?></h3>
                                <div class="row">
                                    <div class="col-12 mb-20">

                                        <div class="row">
                                            <div class="col-lg-6 col-md-12">
                                                <div class="card cart-address-selection-item  active">
                                                    <div class="card-body">
                                                        <a class="btn btn-sm btn-link float-end cart-address-btn-edit"
                                                            href="#" title="Edit">
                                                        </a>
                                                        <h5 class="card-title"><strong
                                                                class="text-danger">Principal</strong></h5>
                                                        <div class="card-text">
                                                            <?php
                                                            $adress = null;

                                                            if (isset($info_customer->commercial_address)) {
                                                                $adress .= $info_customer->commercial_address;
                                                            }

                                                            if (isset($info_customer->commercial_city)) {
                                                                $adress .= ', '.$info_customer->commercial_city;
                                                            }

                                                            if (isset($info_customer->commercial_country)) {
                                                                $adress .= ', '.$info_customer->commercial_country;
                                                            }
                                                            ?>
                                                            <span class="cart-address-read">
                                                                <?= $adress ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-12">
                                                <br>
                                                <?php if($show_proforma): ?>
                                                <div class="form-group">
                                                    <span>
                                                        <input id="proforma" class="" type="checkbox" name="proforma"
                                                            value="1" />
                                                    </span>
                                                    <label for="proforma">Proforma </label>
                                                </div>
                                                <?php endif; ?>
                                                <input id="address" type="checkbox" name="new_address" value="1" />
                                                <label class="righ_0 bg-danger"
                                                    for="address"><?= $lang_catalogo['new_address']?></label>
                                            </div>
                                        </div>

                                        <div id="collapsetwo" class="collapse one" data-parent="#accordion">
                                            <div class="row">
                                                <div class="col-12 mb-20">
                                                    <br>
                                                    <label><?= $lang_catalogo['delivery_address']?>
                                                        <span>*</span></label>
                                                    <input name="delivery_address" class="form-control validate"
                                                        type="text">
                                                    <div
                                                        class="invalid-feedback text-white bg-danger border rounded p-1 fw-bold">
                                                    </div>
                                                    <input hidden name="token" type="text"
                                                        value="<?= $company->token_kor ?>">
                                                </div>
                                                <div class="col-12 mb-20">
                                                    <label><?= $lang_catalogo['zip_code']?> <span>*</span></label>
                                                    <input class="form-control validate" name="zip_code" type="text">
                                                    <div
                                                        class="invalid-feedback text-white bg-danger border rounded p-1 fw-bold">
                                                    </div>
                                                </div>
                                                <div class="col-12 mb-20">
                                                    <label><?= $lang_catalogo['city']?> <span>*</span></label>
                                                    <input class="form-control validate" name="city" type="text">
                                                    <div
                                                        class="invalid-feedback text-white bg-danger border rounded p-1 fw-bold">
                                                    </div>
                                                </div>
                                                <div class="col-12 mb-20">
                                                    <label><?= $lang_catalogo['state']?> <span>*</span></label>
                                                    <input class="form-control validate" name="state" type="text">
                                                    <div
                                                        class="invalid-feedback text-white bg-danger border rounded p-1 fw-bold">
                                                    </div>
                                                </div>
                                                <div class="col-12 mb-20 order-notes">
                                                    <label><?= $lang_catalogo['shipment_notes']?></label>
                                                    <textarea class="form-control" style="height: auto;"
                                                        name="shipping_notes"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="order-notes">
                                            <label for="order_note"><?= $lang_catalogo['comments']?></label>
                                            <textarea class="form-control" name="comment_order" style="height: auto;"
                                                id="order_note"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="<?= ($company->type_cart)=='to_company_no'?'col-lg-12':'col-lg-7' ?> col-md-12">

                        <div class="checkout_form_right">

                            <h3 class="bg-success"><?= $lang_catalogo['your_order'] ?></h3>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th><?= $lang_catalogo['description'] ?></th>
                                            <th style="text-align: center;"><?= $lang_catalogo['cantidad'] ?></th>
                                            <th style="text-align: right;"><?= $lang_catalogo['sale_price'] ?></th>
                                            <th style="text-align: right;">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                        <?php foreach($carproducts as $carpro){ ?>

                                        <tr id="tr-<?= $carpro->product_id ?>">
                                            <td class="fv-catalog-thumb-cell text-center">
                                                <?php
                                                $noImgUrl = catalog_public_no_image_url();
                                                $img_src = ! empty($carpro->imagen_js)
                                                    ? catalog_public_image_url($carpro->imagen_js, $cart_token ?? '')
                                                    : catalog_public_image_url($carpro->images ?? null, $cart_token ?? '');
                                                ?>
                                                <figure class="product_thumb m-0 fv-catalog-thumb">
                                                    <span data-toggle="popover-hover" title="<?= htmlspecialchars($carpro->sku ?? '') ?>" data-img="<?= htmlspecialchars($img_src) ?>">
                                                        <img src="<?= htmlspecialchars($img_src) ?>"
                                                            class="img-list rounded-0" alt="<?= htmlspecialchars($carpro->name ?? '') ?>"
                                                            onerror="this.onerror=null; this.src='<?= htmlspecialchars($noImgUrl, ENT_QUOTES, 'UTF-8') ?>';">
                                                    </span>
                                                </figure>
                                            </td>
                                            <td><?= $carpro->name ?><br>SKU:
                                                <?= $carpro->sku ?>
                                            </td>
                                            <!-- <td class="product_remove" style="text-align: center;"><?= $carpro->qty ?>
                                                <a href="#"><i class="fa fa-trash-o"></i></a>
                                            </td> -->
                                            <td style="text-align: center;" class="product_edit">
                                                <div class="d-flex justify-content-center">
                                                    <p class="p-qty buttonpop-error"
                                                        id="qty-<?= $carpro->product_id ?>" data-qty-raw="<?= (int) $carpro->qty ?>"><?= isset($carpro->qtyFormatted) ? $carpro->qtyFormatted : $carpro->qty ?></p>
                                                    &nbsp;
                                                    <a href="javascript:void(0)" data-comment="<?= htmlspecialchars($carpro->comment ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                                        data-id="<?= $carpro->product_id ?>" class="buttonpop"><i
                                                            class="fa fa-pencil-square-o" aria-hidden="true"></i></a>
                                                </div>
                                            </td>
                                            <td style="text-align: right;">
                                               $<?= $carpro->salepriceDetail_Formatted ?><br>
                                            </td>
                                            <!-- importe total -->
                                            <td style="text-align: right;">
                                                $<span class="price"><?= $carpro->importeDetail_Formatted ?></span>
                                            </td>
                                        </tr>
                                        <?php }?>


                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="3" style="text-align: left;">Subtotal</th>
                                            <td colspan="2" style="text-align: right;">$<span
                                                    class="sub_total"><?= $totales['sub_total'] ?></span></td>
                                        </tr>
                                        <tr>
                                            <?php
                                        $discount = null;
                                        if (isset($info_customer) && is_object($info_customer) && isset($info_customer->discount) && (int)$info_customer->discount >= 1) {
                                            $discount = ' (' . $info_customer->discount . '%)';
                                        }
                                        ?>
                                            <th colspan="3" style="text-align: left;">
                                                <?= $lang_catalogo['discount'] ?><?= $discount ?></th>
                                            <td colspan="2" style="text-align: right;">$<span
                                                    class="discount"><?= $totales['discount_total'] ?></span></td>
                                        </tr>

                                        <tr>
                                            <th colspan="3" style="text-align: left;">Total <br>&nbsp;</th>
                                            <td colspan="2" style="text-align: right;">
                                                <strong>$<span
                                                        class="total-monto"><?= $totales['total_orden'] ?></span></strong>
                                                <br>
                                                <strong class="ps-2"><?= $plusTaxesLabel??"" ?></strong>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <div class="col-12 text-end">


                                <div class="form-group" style="text-align: right;">
                                    <button class="btn btn-success btn-placeorder" form="form-order" type="submit"><span
                                            class="spinner-border spinner-border-sm d-none" role="status"
                                            aria-hidden="true"></span> <?= $lang_catalogo['finish'] ?></button>

                                    <?php
                                    $cancelOrderText = isset($lang_catalogo['cancel_order']) && $lang_catalogo['cancel_order'] !== '' ? $lang_catalogo['cancel_order'] : (isset($info_company->language_id) && (int)$info_company->language_id === 2 ? 'Cancelar orden' : 'Cancel Order');
                                    if (!empty($company->return_admin)) { ?>
                                    <a href="<?= company_url('orders') ?>" class="btn btn-danger"><?= $cancelOrderText ?></a>
                                    <?php } else { ?>
                                        <?php $this->load->view("catalogo/boton-vaciarCarrito") ?>
                                    <a href="javascript:history.go(-1)"
                                        class="btn btn-secondary"><?= $lang_catalogo['back'] ?></a>
                                    <?php } ?>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

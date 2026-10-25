<?php
/**
 * @var $lang_json Array The lang translations for every translatable string
 * @var $info_customer
 * @var $company
 * @var $cart_info
 * @var $moneda
 * @var $plusTaxesLabel ???
 */

$product_detail = $cart_info->order_details;
$lang_catalogo = $lang_json['cmp_catalog']['lang'];
?>
<script>
    const shopping_order = <?= json_encode($product_detail) ?>;
</script>
<div class="checkout_page_bg">
    <div class="container">
        <div class="Checkout_section">
            <div id="card-empty" class="row d-none">
                <div class="col-12">
                    <div class="user-actions">
                        <h3>
                            <i class="fa fa-file-o" aria-hidden="true"></i>
                            <?= $lang_catalogo['msg_unable'] ?> <a class="Returning" href="javascript:history.go(-1)"><?= $lang_catalogo['here'] ?></a>
                            <?= $lang_catalogo['msg_unable_2'] ?>
                        </h3>
                    </div>
                </div>
            </div>
            <div id="order-complete" class="checkout_form d-none">
                <div class="row">
                    <div class="col-lg-5 col-md-12">
                        <div class="checkout_form_left">
                            <form id="form-order" class="needs-validation" method="post" action="<?= marketplace_url('shop/send_order') ?>" novalidate>
                                <h3 class="bg-success"><?= $lang_catalogo['send'] ?></h3>
                                <div class="row">
                                    <div class="col-12 mb-20">
                                        <div class="row">
                                            <div class="col-lg-6 col-md-12">
                                                <div class="card cart-address-selection-item  active">
                                                    <div class="card-body">
                                                        <a class="btn btn-sm btn-link float-end cart-address-btn-edit" href="#" title="Edit">
                                                        </a>
                                                        <h5 class="card-title"><strong class="text-danger">Principal</strong></h5>
                                                        <div class="card-text">
                                                            <?php

                                                            $adress = !empty($info_customer->commercial_address) ? $info_customer->commercial_address. "": "";
                                                            $adress .= !empty($info_customer->commercial_zone) ? ', '.$info_customer->commercial_zone : "";
                                                            $adress .= !empty($info_customer->commercial_zip_code) ? ', '.$info_customer->commercial_zip_code : "";
                                                            $adress .= !empty($info_customer->commercial_city) ? ', '.$info_customer->commercial_city : "";
                                                            $adress .= !empty($info_customer->commercial_state) ? ', '.$info_customer->commercial_state : "";
                                                            $adress .= !empty($info_customer->commercial_country) ? ', '.$info_customer->commercial_country : "";
                                                            ?>
                                                            <span class="cart-address-read"><?= $adress ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 col-md-12">
                                                <br>
                                                <input id="address" type="checkbox" name="sending[new_address]" value="1" />
                                                <label class="righ_0 bg-danger" for="address"><?= $lang_catalogo['new_address'] ?></label>
                                            </div>
                                        </div>

                                        <div id="collapsetwo" class="collapse one" data-parent="#accordion">
                                            <div class="row">
                                                <div class="col-12 mb-20">
                                                    <br>
                                                    <label><?= $lang_catalogo['delivery_address'] ?>
                                                        <span>*</span></label>
                                                    <input name="sending[delivery_address]" class="form-control validate" type="text">
                                                    <div class="invalid-feedback text-white bg-danger border rounded p-1 fw-bold">
                                                    </div>
                                                </div>
                                                <div class="col-12 mb-20">
                                                    <label><?= $lang_catalogo['zip_code'] ?> <span>*</span></label>
                                                    <input class="form-control validate" name="sending[zip_code]" type="text">
                                                    <div class="invalid-feedback text-white bg-danger border rounded p-1 fw-bold">
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-6 mb-20 position-relative">
                                                        <label><?= $lang_catalogo['city'] ?> <span>*</span></label>
                                                        <input type="text" class="form-control validate" id="city_name" name="sending[city]">
                                                        <div class="invalid-feedback text-white bg-danger border rounded p-1 fw-bold"></div>
                                                        <input type="hidden" id="city_id">
                                                        <div id="city_suggestions" class="suggestions-box"></div>
                                                    </div>
                                                    <div class="col-6 mb-20 position-relative">
                                                        <label><?= $lang_catalogo['country'] ?? "Pais" ?> <span>*</span></label>
                                                        <input type="text" class="form-control validate" id="country_name" name="sending[country]">
                                                        <div class="invalid-feedback text-white bg-danger border rounded p-1 fw-bold"></div>
                                                        <input type="hidden" id="country_id">
                                                        <div id="country_suggestions" class="suggestions-box"></div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-6 mb-20 position-relative">
                                                        <label><?= $lang_catalogo['state'] ?> <span>*</span></label>
                                                        <input type="text" class="form-control validate" id="state_name" name="sending[state]">
                                                        <div class="invalid-feedback text-white bg-danger border rounded p-1 fw-bold"></div>
                                                        <input type="hidden" id="state_id">
                                                        <div id="state_suggestions" class="suggestions-box"></div>
                                                    </div>
                                                    <div class="col-6 mb-20">
                                                        <label><?= $lang_catalogo['zona'] ?? "Zona"?> <span>*</span></label>
                                                        <input class="form-control validate" name="sending[zona]" type="text">
                                                        <div class="invalid-feedback text-white bg-danger border rounded p-1 fw-bold">
                                                        </div>
                                                    </div>

                                                </div>
                                                <div class="col-12 mb-20 order-notes">
                                                    <label><?= $lang_catalogo['shipment_notes'] ?></label>
                                                    <textarea class="form-control" style="height: auto;" name="sending[shipping_notes]"></textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="order-notes">
                                            <label for="order_note"><?= $lang_catalogo['comments'] ?></label>
                                            <textarea class="form-control" name="sending[comment_order]" style="height: auto;" id="order_note"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="col-lg-7 col-md-12">
                        <div class="checkout_form_right">

                            <h3 class="bg-success"><?= $lang_catalogo['your_order'] ?></h3>
                            <div class="table-responsive">
                                <table id="table_order" class="table">
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

                                        <?php foreach ($product_detail as $detail) { ?>

                                            <tr id="tr-<?= $detail->product_id ?>">
                                                <td>
                                                    <figure style="width: 6em; overflow:hidden; margin:auto;">

                                                        <?php if (isset($detail->product->default_image)) :
                                                            $imgUuid = $detail->product->default_image->uuid ?? '';
                                                            $imgSrc = function_exists('company_product_image_url')
                                                                ? company_product_image_url($detail->product, 0)
                                                                : company_url('image/'.$imgUuid);
                                                            ?>

                                                            <span data-toggle="popover-hover" title="<?= htmlspecialchars((string) $detail->product->sku, ENT_QUOTES, 'UTF-8') ?>" data-img="<?= htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') ?>">
                                                                <img src="<?= htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') ?>" style="display: block; width: 100%; height: 100px;object-fit: scale-down;" class="rounded-0" alt="" loading="lazy" onerror="this.onerror=null;this.src='<?= htmlspecialchars(catalog_public_no_image_url(), ENT_QUOTES, 'UTF-8') ?>';">
                                                            </span>
                                                        <?php elseif (! empty($detail->product->images) && count($detail->product->images) > 0):
                                                            $firstImg = $detail->product->images[0];
                                                            $imgSrc = function_exists('company_product_image_url')
                                                                ? company_product_image_url($firstImg, 0)
                                                                : company_url('image/'.($firstImg->uuid ?? ''));
                                                            ?>
                                                            <img src="<?= htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') ?>" class="rounded-0" style="display: block; width: 100%; height: 100%;" alt="" loading="lazy" onerror="this.onerror=null;this.src='<?= htmlspecialchars(catalog_public_no_image_url(), ENT_QUOTES, 'UTF-8') ?>';">

                                                        <?php else :?>
                                                            <img src="<?= htmlspecialchars(catalog_public_no_image_url(), ENT_QUOTES, 'UTF-8') ?>" class="rounded-0" style="display: block; width: 100%; height: 100%;" alt="">
                                                        <?php endif; ?>
                                                    </figure>
                                                </td>
                                                <td><?= $detail->product->name ?><br>SKU:
                                                    <?= $detail->product->sku ?>
                                                </td>
                                                <!-- <td class="product_remove" style="text-align: center;"><?= $detail->qty ?>
                                                <a href="#"><i class="fa fa-trash-o"></i></a>
                                            </td> -->
                                                <td style="text-align: center;" class="product_edit">
                                                    <div class="d-flex justify-content-center">
                                                        <p class="p-qty buttonpop-error" id="qty-<?= $detail->product_id ?>"><?= $detail->qty ?></p>
                                                        &nbsp;
                                                        <a href="javascript:void(0)" data-comment="<?= $detail->comments ?>" data-id="<?= $detail->product_id ?>" class="buttonpop"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a>
                                                    </div>
                                                </td>

                                                <?php
                                                $precio = $detail->product->sale_price;
                                                $importe_total = $precio * $detail->qty;

                                                ?>
                                                <td style="text-align: right;">
                                                    $<span class="prod_price"><?= $precio ?></span>
                                                </td>
                                                <!-- importe total -->
                                                <td style="text-align: right;">
                                                    $<span class="prod_total"><?= $importe_total ?></span>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="3" style="text-align: left;">Subtotal</th>
                                            <td colspan="2" style="text-align: right;">
                                                <?= $moneda ?><span id="order_subtotal" class="sub_total"><?= $cart_info->subtotal ?></span>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th colspan="3" style="text-align: left;">
                                                <?= $lang_catalogo['discount'] ?></th>
                                            <td colspan="2" style="text-align: right;">
                                                <?= $moneda ?><span class="discount" id="order_discount"><?= $cart_info->descuento ?></span>
                                            </td>
                                        </tr>

                                        <tr>
                                            <th colspan="3" style="text-align: left;">Total</th>
                                            <td colspan="2" style="text-align: right;">
                                                <strong>
                                                    <?= $moneda ?><span id="order_totalamount" class="total-monto"><?= $cart_info->total ?>
                                                    </span>
                                                </strong>
                                                <br>
                                                <strong class="ps-2"><?= $plusTaxesLabel??"" ?></strong>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                            <div class="col-12">
                                <div class="form-group" style="text-align: right;">
                                    <button class="btn btn-success btn-placeorder" form="form-order" type="submit"><span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span> <i class='bx bxs-save'></i> <?= $lang_catalogo['finish'] ?></button>
                                    <a id="button-clean-cart" data-href="<?= marketplace_url("shop/clean_cart") ?>" href="javascript:void(0)" onclick="cleanCart()" class="btn_cancel btn btn-danger d-none">
                                        <i class='bx bx-cart'></i> <?= $lang_catalogo['clean-cart'] ?>
                                    </a>
                                    <a href="javascript:history.go(-1)"
                                       class="btn btn-secondary"><?= $lang_catalogo['back'] ?></a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

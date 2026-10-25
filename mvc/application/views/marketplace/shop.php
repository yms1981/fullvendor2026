<?php
/**
 * @var $lang_json Array The lang translations for every translatable string
 * @var $base_url string
 */

$lang_catalogo = $lang_json['cmp_catalog']['lang'];
$lang_pop = $lang_json['popover_catalog']['lang'];
?>
<div class="shop_area" style="padding: 10px 0 133px !important;">
    <div class="container">
        <!-- MARKET TEXT PUBLICIDAD -->
        <h4 class="d-md-none"><?= strtoupper($lang_catalogo['customer']) ?>:
            <?= strtoupper($info_customer->business_name) . strtoupper(
                (isset($info_customer->name)) ? ' (' . $info_customer->name . ')' : ''
            )
            ?>
        </h4>
        <div class="row">
            <div class="col-lg-3 col-md-3 col-sm-12">
                <?php
                if ($user['role'] === 'seller'): ?>
                    <h4><?= strtoupper($lang_catalogo['seller'] . ': ' . $info_seller->user->name) ?>
                    </h4>
                    <h4><?= strtoupper($lang_catalogo['group'] . ': ' . $info_customer->customer_group->name) ?></h4>
                <?php
                endif ?>
            </div>
            <div class="col-lg-9 col-md-9 col-sm-12 ">
                <!--shop toolbar start-->
                <div class="shop_toolbar_wrapper">
                    <div class="shop_toolbar_btn">
                        <button data-role="grid_4"
                                type="button"
                                class="active btn-grid-4"
                                data-toggle="tooltip"
                                data-tippy-placement="top"
                                data-tippy-arrow="true"
                                data-tippy-inertia="false"
                                data-tippy="<?= $lang_catalogo['cuadricula'] ?>"
                                title="4"></button>
                        <button data-role="grid_list" type="button" class="btn-list" data-toggle="tooltip"
                                data-tippy-placement="top" data-tippy-arrow="true" data-tippy-inertia="false"
                                data-tippy="<?= $lang_catalogo['list'] ?>"
                                title="List"></button>
                    </div>
                    <div class="page_amount">
                        <p><?= $lang_catalogo['showing'] ?> 1–<?= $page->per_page ?> <?= $lang_catalogo['of'] ?>
                            <?= $page->total ?> <?= $lang_catalogo['results'] ?></p>
                    </div>
                    <div class="actions d-flex flex-row">
                        <?php
                        if ($user['role'] === 'seller' && $company->publish_price_list) :
                            $priceListLink = $base_url."/priceList";
                            $qs = http_build_query(
                                [
                                    'sort' => $this->input->get('sort'),
                                    'cat_id' => $this->input->get('cat_id'),
                                    'search' => $this->input->get('search')
                                ]
                            );
                            if (!empty($qs)) {
                                $priceListLink .= "?{$qs}";
                            }
                            ?>
                            <a href="<?= $priceListLink ?>" title="<?= $lang_catalogo['download_price_list'] ?>"
                               class="btn btn-outline-success"><i class="mdi mdi-file-excel"></i></a>
                        <?php
                        endif; ?>
                        <form class="form-filtro-buscar-nav" action="#">
                            <select name="orderby" id="short">
                                <option
                                    <?= ($this->input->get('sort') == null || $this->input->get('sort') == 'name_asc') ? 'selected' : '' ?> value="name_asc">
                                    <?= $lang_catalogo['name'] ?> A-Z
                                </option>
                                <option
                                    <?= ($this->input->get('sort') == 'name_desc') ? 'selected' : '' ?> value="name_desc">
                                    <?= $lang_catalogo['name'] ?> Z-A
                                </option>
                                <option
                                    <?= ($this->input->get('sort') == 'sku_asc') ? 'selected' : '' ?> value="sku_asc">
                                    SKU A-Z
                                </option>
                                <option
                                    <?= ($this->input->get('sort') == 'sku_desc') ? 'selected' : '' ?> value="sku_desc">
                                    SKU Z-A
                                </option>
                                <option
                                    <?= ($this->input->get('sort') == 'price_asc') ? 'selected' : '' ?> value="price_asc">
                                    <?= $lang_catalogo['precio_menor'] ?>
                                </option>
                                <option
                                    <?= ($this->input->get('sort') == 'price_desc') ? 'selected' : '' ?> value="price_desc">
                                    <?= $lang_catalogo['precio_mayor'] ?>
                                </option>
                            </select>
                        </form>
                    </div>
                </div>
                <!-- shop toolbar end -->
            </div>

        </div>
        <div class="row d-flex">
            <div class="mini_cart_wrapper d-block d-md-none">
                <a href="javascript:void(0)" onclick="open_seachSidebar()">
                                        <span class="label_sale bg-transparent"
                                              style="font-size: 25px;right: 50px;top: 10px;"
                                              tabindex="0">
                                            <i class='bx bx-menu'></i>
                                        </span>
                </a>
            </div>
            <div class="col-lg-3 col-md-3 d-none d-md-block order-lg-1 order-md-1">
                <!--sidebar widget start-->
                <aside class="sidebar_widget">
                    <div class="widget_list widget_categories">
                        <h3><?= $lang_catalogo['product_categories'] ?></h3>
                        <ul>
                            <li>
                                <a href="<?= $base_url ?>" class="d-flex">
                                    <span class="ps-2 <?= $this->input->get('cat_id') == null ? 'text-danger' : '' ?>">
                                        <i style="width: 30px;height: 30px;" class="fa fa-th-large"
                                           aria-hidden="true"></i><?= strtoupper($lang_catalogo["all"]) ?>
                                    </span>
                                </a>
                            </li>
                            <!-- categorias -->
                            <?php
                            if (count($categorias) > 0) {
                                foreach ($categorias as $cat) {
                                    if ($cat->qty_products > 0) {
                                        ?>
                                        <li id="cat-li-<?= $cat->cat_id ?>"
                                            <?= $this->input->get(
                                                'cat_id'
                                            ) == $cat->cat_id ? 'data-principal="TRUE"' : '' ?>
                                            class="<?= $this->input->get(
                                                'cat_id'
                                            ) == $cat->cat_id ? 'bg-secondary bg-opacity-25' : '' ?> px-1">
                                            <a href="<?= "{$base_url}?cat_id={$cat->cat_id}"?>"
                                               class="d-flex">
                                                <?php
                                                if ($cat->category_default != 1): ?>
                                                    <img style="width: 30px;height: 30px;"
                                                         src="<?= ($cat->images != null || $cat->images != '') ? base_url(
                                                                 'uploads/categories/' . @$company->company_id . '/' . $cat->images
                                                             ) . '?v=' . $version : base_url(
                                                                 'uploads/noimg.png'
                                                             ) . '?v=' . $version ?>">
                                                <?php
                                                endif; ?>
                                                <span
                                                    class="ps-2 <?= $this->input->get(
                                                        'cat_id'
                                                    ) == $cat->cat_id ? 'text-danger fw-bold' : '' ?>">
                                        <?php
                                        if ($cat->category_default == 1): ?>
                                            <i style="width: 30px;height: 30px;font-size: 19px;" class="fa fa-ban"
                                               aria-hidden="true"></i>
                                        <?php
                                        endif; ?>
                                                    <?= strtoupper(
                                                        $cat->category_default == 1 ? $lang_catalogo["sin_categoria"] : $cat->category_name
                                                    ) ?>
                                        <!-- &nbsp;<span class="badge bg-secondary"><?= $cat->qty_products ?></span> -->
                                    </span>
                                            </a>
                                        </li>
                                        <?php
                                    }
                                } ?>
                                <?php
                            } ?>
                        </ul>
                    </div>

                </aside>
            </div>
            <div class="col-lg-9 col-md-9 col-sm-12 order-1 order-lg-2 order-md-2">
                <!--shop banner area start-->

                <?php
                if (count($products) > 0) { ?>

                    <div id="tabla" class="row no-gutters shop_wrapper grid_4 bg-transparent border-0">

                        <?php
                        foreach ($products as $product) { ?>


                            <div data-categories="<?= $product->category_id ?>" id="card-<?= $product->pro_id ?>"
                                 data-product-id="<?= $product->pro_id ?>"
                                 class="col-lg-3 col-md-4 col-sm-6 p-2 card-product over-category">
                                <article id="figure-<?= $product->pro_id ?>" class="single_product">
                                    <figure class="w-100 animate__animated animate__zoomIn animate_02s">
                                        <div class="product_thumb w-100">
                                            <div id="images-product-<?= $product->pro_id ?>"
                                                 class="carousel slide" data-bs-ride="carousel"
                                                 data-bs-touch="true">

                                                <div class="carousel-indicators">

                                                    <?php
                                                    foreach ($product->images as $index => $img) {?>
                                                        <button style="height:15px;width: 15px;" type="button"
                                                                data-bs-target="#images-product-<?= $product->pro_id ?>"
                                                                data-bs-slide-to="<?= $index ?>"
                                                            <?= ($product->images == $img) ? 'class="active rounded-circle bg-secondary" aria-current="true"' : 'class="rounded-circle bg-secondary"' ?>
                                                                aria-label="Slide <?= $index + 1 ?>">
                                                        </button>
                                                        <?php
                                                    } ?>
                                                </div>
                                                <div class="carousel-inner">
                                                    <?php
                                                    $index = 0;

                                                    ?>
                                                    <?php
                                                    if (count($product->images) > 0) : ?>
                                                        <?php
                                                        foreach ($product->images as $key => $img): ?>
                                                            <!-- si no hay imagen por defecto colocamos la primera imagen  -->
                                                            <div data-bs-interval="<?= 5000 * 100 + $index % 8 ?>"
                                                                 class="carousel-item <?= $key == 0 ? 'active' : '' ?> img_contain"
                                                                 data-index="<?= $key ?>">
                                                                <a class="img-list-link" data-product-id="<?= $product->pro_id ?>"
                                                                   href="#"
                                                                   data-bs-toggle="modal"
                                                                   data-bs-target="#modal-product-<?= $product->pro_id ?>">
                                                                            <img class="img-list"
                                                                                 src="<?= htmlspecialchars(function_exists('company_product_image_url') ? company_product_image_url($img, 0) : company_url('image/'.$img->uuid), ENT_QUOTES, 'UTF-8') ?>"
                                                                                 alt="<?= htmlspecialchars($product->name . ' ' . ($img->name ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                                                 loading="lazy"
                                                                                 onerror="this.onerror=null;this.src='<?= htmlspecialchars(catalog_public_no_image_url(), ENT_QUOTES, 'UTF-8') ?>';"/>
                                                                </a>
                                                            </div>
                                                        <?php
                                                        endforeach; ?>
                                                    <?php
                                                    else: ?>
                                                    <div><img src="<?= company_url('image') ?>" alt="Este producto no tiene imagenes" /></div>
                                                    <?php
                                                    endif; ?>

                                                </div>
                                            </div>
                                            <div class="label_product">
                                                <?php
                                                if ($product->star) {
                                                    ?>
                                                    <a href="#">
                                            <span class="label_sale bg-danger text-white p-0 rounded"
                                                  style="font-size: 16px;">
                                                <p><span id="count">1</span>&nbsp;&nbsp;/<?= max(count($product->images),1) ?></p>
                                            </span>
                                                        <span class="label_sale bg-transparent text-warning"
                                                              style="font-size: 25px;right: 50px;top: 10px;"
                                                              data-tippy-placement="top" data-tippy-arrow="true"
                                                              data-tippy-inertia="false"
                                                              data-tippy="<?= $lang_catalogo['sold'] ?>">
                                                <i class="ion-android-star-outline"></i>
                                            </span>

                                                    </a>
                                                    <?php
                                                } else { ?>

                                                    <?php
                                                    if ( count($product->images) > 0 ) { ?>
                                                        <a href="#">
                                                        <span class="label_sale bg-danger text-white p-0 rounded"
                                                              style="font-size: 16px;">
                                                <p><span
                                                        id="#count">1</span>&nbsp;&nbsp;/<?= max(count($product->images),1) ?>
                                                </p>
                                            </span>
                                                        <?php
                                                    } ?>
                                                    </a>
                                                    <?php
                                                } ?>
                                            </div>
                                            <div class="action_links">
                                                <ul>
                                                    <!-- <li class="wishlist"><a href="wishlist.html" data-tippy-placement="top" data-tippy-arrow="true" data-tippy-inertia="true" data-tippy="Add to Wishlist"><i class="ion-android-favorite-outline"></i></a></li>
                                                        -->
                                                    <li id="catalog" class="compare">
                                                        <?php
                                                        if ($product->minimum_stock > 0) {
                                                            $minium_qty = intval($product->minimum_stock);
                                                        } else {
                                                            $minium_qty = 1;
                                                        }
                                                        ?>

                                                    </li>
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="product_content grid_content info-scroll-bar"
                                             style="text-align: left;">
                                            <div class="p-2 content-scroll-bar bar">
                                                <h4 class="product_name text-break"><a
                                                        data-product-id="<?= $product->pro_id ?>"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#modal-product-<?= $product->pro_id ?>"
                                                        href="#"><?= strtoupper($product->name) ?></a>
                                                </h4>


                                                <?php
                                                $tags = explode(",", $product->tags);
                                                ?>
                                                <p>
                                                    SKU: <?= strtoupper($product->sku) ?><br>
                                                    <?php
                                                    if ($product->minimum_stock != 0 && $product->minimum_stock != 1) { ?>
                                                        PACK: <?= intval($product->minimum_stock) ?>
                                                        <?php
                                                    } ?>
                                                    <br>
                                                    <?php
                                                    if ($product->tags != null): ?>
                                                        <?php
                                                        foreach ($tags as $key => $value) : ?>

                                                            <span class="badge text-bg-light text-danger"
                                                                  style="font-size: 14px;text-align: left;padding-left: 0;"><?= $value ?></span>

                                                        <?php
                                                        endforeach; ?>
                                                    <?php
                                                    endif; ?>
                                                </p>

                                            </div>
                                            <div class="p-2">
                                                <div class="price_box">
                                            <span
                                                class="current_price <?= $product->price_reduced <> 0 ? 'strikethrough text-dark' : '' ?>">$<?= $product->sale_price ?></span>
                                                </div>
                                                <?php
                                                if ($product->price_reduced <> 0): ?>
                                                    <div class="price_box">
                                                        <span class="current_price">$<?= $product->sale_price ?></span>
                                                    </div>
                                                <?php
                                                else: ?>
                                                    <div class="price_box">
                                                        <span class="current_price">&nbsp;</span>
                                                    </div>
                                                <?php
                                                endif; ?>


                                                <?php

                                                // red: #CF1C00
                                                $color_semaforo = $this->Helper->obtener_colorSemaforo(
                                                    $product,
                                                    $company
                                                );
                                                $leyenda_semaforo = $this->Helper->obtener_leyendaSemaforo(
                                                    $color_semaforo,
                                                    $company
                                                );

                                                ?>
                                                <div class="d-flex">
                                                    <?php
                                                    if ($company->show_inventory): ?>

                                                        <div class="price_box d-flex">
                                                <span class="badge text-bg-light text-danger"
                                                      style="font-size: 14px;text-align: left;padding-left: 0;">STOCK:
                                                    <?= $product->stock <= 0 ? 0 : number_format(
                                                        $product->stock,
                                                        0
                                                    ) ?></span>

                                                        </div>
                                                    <?php
                                                    else: ?>
                                                        <div class="price_box">
                                                <span class="badge text-bg-light text-danger"
                                                      style="font-size: 14px;text-align: left;padding-left: 0;">&nbsp;</span>
                                                        </div>
                                                    <?php
                                                    endif; ?>

                                                    <?php
                                                    if ($company->show_semaforo_stock)
                                                        $this->load->view('components/semaphore/semaphore',['info_company'=>$company,'product'=>$product])
                                                    ?>

                                                </div>


                                            </div>
                                            <div class="col-12 pb-2">
                                                <?php

                                                $qty_valor = $lang_catalogo['add'];
                                                $comments = null;

                                                if (in_array(
                                                    $product->pro_id,
                                                    array_column($product_detail, 'product_id')
                                                )) {
                                                    $index = array_search(
                                                        $product->pro_id,
                                                        array_column($product_detail, 'product_id')
                                                    );
                                                    $qty_valor = $product_detail[$index]['qty'];
                                                    $comments = $product_detail[$index]['comment'];
                                                }
                                                ?>
                                                <div class="text-center">
                                                    <div class="d-flex justify-content-between p-2">
                                                        <button data-comment="<?= $comments ?>"
                                                                data-id="<?= $product->pro_id ?>"
                                                                class="boton-add-car buttonpop">
                                                    <span class="spinner-border spinner-border-sm d-none" role="status"
                                                          aria-hidden="true"></span>

                                                            <span class="text"> <?= $qty_valor ?> </span>
                                                            <i class="fa fa-cart-plus" aria-hidden="true"></i></button>
                                                        <button data-minium-qty="<?= $minium_qty ?>" type="button"
                                                                data-id="<?= $product->pro_id ?>"
                                                                class="btn btn-light addqtyminium d-flex align-items-center">
                                                    <span class="spinner-border spinner-border-sm d-none" role="status"
                                                          aria-hidden="true"></span>
                                                            <span>+<?= $minium_qty ?></span></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="product_content list_content w-100">
                                            <!-- <h4 class="product_name"><a
                                            href="product-details.html"><?= strtoupper($product->name) ?></a></h4> -->
                                            <h4 class="product_name text-break"><a
                                                    data-product-id="<?= $product->pro_id ?>"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modal-product-<?= $product->pro_id ?>"
                                                    href="#"><?= strtoupper($product->name) ?></a>
                                            </h4>

                                            <div class="product_desc">
                                                <p>SKU: <?= strtoupper($product->sku) ?><br>
                                                    <?php
                                                    if ($product->minimum_stock != 0 && $product->minimum_stock != 1) { ?>
                                                        PACK: <?= intval($product->minimum_stock) ?>
                                                        <?php
                                                    } ?>
                                            </div>

                                            <div class="col-12 pb-2">
                                                <div class="row">
                                                    <div class="col-md-6 col-sm-12">
                                                        <div class="price_box d-flex align-items-center">
                                                <span
                                                    class="current_price <?= $product->price_reduced <> 0 ? 'strikethrough text-dark' : '' ?>">$<?= $product->sale_price ?></span>
                                                        </div>
                                                        <?php
                                                        if ($product->price_reduced <> 0): ?>
                                                            <div class="price_box d-flex align-items-center">
                                                                <span
                                                                    class="current_price">$<?= $$product->sale_price ?></span>
                                                            </div>
                                                        <?php
                                                        else: ?>
                                                            <div class="price_box">
                                                                <span class="current_price">&nbsp;</span>
                                                            </div>

                                                        <?php
                                                        endif; ?>
                                                        <div class="d-flex">
                                                            <?php
                                                            if ($company->show_inventory): ?>
                                                                <div class="price_box d-flex">
                                                    <span class="badge text-bg-light text-danger"
                                                          style="font-size: 14px;text-align: left;padding-left: 0;">STOCK:
                                                        <?= $product->stock <= 0 ? 0 : number_format(
                                                            $product->stock,
                                                            0
                                                        ) ?></span>

                                                                </div>
                                                            <?php
                                                            else: ?>
                                                                <div class="price_box">
                                                    <span class="badge text-bg-light text-danger"
                                                          style="font-size: 14px;text-align: left;padding-left: 0;">&nbsp;</span>
                                                                </div>
                                                            <?php
                                                            endif; ?>
                                                            <?php
                                                            if ($company->show_semaforo_stock)
                                                                $this->load->view('components/semaphore/semaphore',['info_company'=>$company,'product'=>$product])
                                                            ?>
                                                        </div>

                                                    </div>
                                                    <div class="col-md-6 col-sm-12">
                                                        <div class="text-center">
                                                            <div class="d-flex justify-content-between p-2">
                                                                <button data-comment="<?= $comments ?>"
                                                                        data-id="<?= $product->pro_id ?>"
                                                                        class="boton-add-car buttonpop">
                                                            <span class="spinner-border spinner-border-sm d-none"
                                                                  role="status" aria-hidden="true"></span>
                                                                    <span class="text"> <?= $qty_valor ?> </span>
                                                                    <i class="fa fa-cart-plus" aria-hidden="true"></i>
                                                                </button>
                                                                <button data-minium-qty="<?= $minium_qty ?>"
                                                                        type="button"
                                                                        data-id="<?= $product->pro_id ?>"
                                                                        class="btn btn-light addqtyminium d-flex align-items-center">
                                                            <span class="spinner-border spinner-border-sm d-none"
                                                                  role="status" aria-hidden="true"></span>
                                                                    <span>+<?= $minium_qty ?></span></button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                            </div>

                                            <div class="action_links">
                                                <ul>
                                                    <!-- <li class="wishlist"><a href="wishlist.html" title="Add to Wishlist"><i class="ion-android-favorite-outline"></i> Add to Wishlist</a></li>
                                                            <li class="compare"><a href="#" title="Add to Compare"><i class="ion-ios-settings-strong"></i> Compare</a></li> -->
                                                    <li class="quick_button"><a href="#"
                                                                                data-product-id="<?= $product->pro_id ?>"
                                                                                data-bs-toggle="modal"
                                                                                data-bs-target="#modal-product-<?= $product->pro_id ?>"
                                                                                title="<?= $lang_catalogo['quick_view'] ?>"><i
                                                                class="ion-ios-search-strong"></i>
                                                            <?= $lang_catalogo['quick_view'] ?></a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </figure>
                                </article>
                            </div>
                            <div class="modal fade modal_box_product" id="modal-product-<?= $product->pro_id ?>"
                                 tabindex="-1"
                                 role="dialog" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                        <div class="modal_body">
                                            <div class="container">
                                                <div class="row">
                                                    <div class="col-lg-5 col-md-5 col-sm-12">
                                                        <div class="modal_tab">
                                                            <div class="tab-content product-details-large" style="height: 70%">
                                                                <?php if (!empty($product->images)): ?>
                                                                    <?php foreach ($product->images as $cont => $img_obj): ?>
                                                                        <div class="tab-pane fade<?= (($cont+1) == 1) ? ' show active' : '' ?>" id="tab<?= ($cont+1) ?>" role="tabpanel">
                                                                            <div class="modal_tab_img">
                                                                                <a href="#">
                                                                                    <img style="height: 400px;width: 100%;object-fit: scale-down;"
                                                                                         src="<?= htmlspecialchars(function_exists('company_product_image_url') ? company_product_image_url($img_obj, 0) : company_url('image/'.$img_obj->uuid), ENT_QUOTES, 'UTF-8') ?>"
                                                                                         alt=""
                                                                                         loading="lazy"
                                                                                         onerror="this.onerror=null;this.src='<?= htmlspecialchars(catalog_public_no_image_url(), ENT_QUOTES, 'UTF-8') ?>';">
                                                                                </a>
                                                                            </div>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                <?php else: ?>
                                                                    <div class="tab-pane fade show active" id="tab1" role="tabpanel">
                                                                        <div class="modal_tab_img">
                                                                            <a href="#">
                                                                                <img style="height: 400px;width: 100%;object-fit: scale-down;"
                                                                                     src="<?= company_url('image')  ?>" alt="">
                                                                            </a>
                                                                        </div>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>

                                                            <div class="modal_tab_button" style="height: 30%">
                                                                <ul class="nav product_navactive owl-carousel" role="tablist">
                                                                    <?php if (!empty($product->images)): ?>
                                                                        <?php foreach ($product->images as $k => $img_obj): ?>
                                                                            <?php $cont = $k+1; ?>
                                                                            <li>

                                                                                <a class="nav-link<?= ($cont == 1) ? ' active' : '' ?>" data-toggle="tab"
                                                                                   href="#tab<?= $cont ?>" role="tab" aria-controls="tab<?= $cont ?>" aria-selected="false">
                                                                                    <img style="height: 100px;width: 100%;object-fit: scale-down;"
                                                                                         src="<?= htmlspecialchars(function_exists('company_product_image_url') ? company_product_image_url($img_obj, 0) : company_url('image/'.$img_obj->uuid), ENT_QUOTES, 'UTF-8') ?>"
                                                                                         alt=""
                                                                                         loading="lazy"
                                                                                         onerror="this.onerror=null;this.src='<?= htmlspecialchars(catalog_public_no_image_url(), ENT_QUOTES, 'UTF-8') ?>';">
                                                                                </a>
                                                                            </li>
                                                                        <?php endforeach; ?>
                                                                    <?php else: ?>
                                                                        <li>
                                                                            <a class="nav-link active" data-toggle="tab" href="#tab1" role="tab" aria-controls="tab1" aria-selected="false">
                                                                                <img style="height: 100px;width: 100%;object-fit: scale-down;"
                                                                                     src="<?= company_url('image')  ?>" alt="">
                                                                            </a>
                                                                        </li>
                                                                    <?php endif; ?>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-lg-7 col-md-7 col-sm-12">
                                                        <div class="modal_right">
                                                            <div class="modal_title mb-10">
                                                                <h2><?= strtoupper($product->name) ?></h2>
                                                                <p>SKU: <?= strtoupper($product->sku) ?></p>
                                                                <?php
                                                                if ($product->minimum_stock != 0 && $product->minimum_stock != 1) { ?>
                                                                    <p>PACK: <?= intval($product->minimum_stock) ?></p>
                                                                    <?php
                                                                } ?>
                                                                <p><?= strtoupper($lang_pop['unit']) ?> <?= strtoupper(
                                                                        $product->unit_type
                                                                    ) ?></p>
                                                            </div>
                                                        </div>
                                                        <div class="price_box d-flex align-items-center">
                                <span
                                    class="current_price <?= $product->price_reduced <> 0 ? 'strikethrough text-dark' : '' ?>">$<?= $product->sale_price ?></span>
                                                        </div>
                                                        <?php
                                                        if ($product->price_reduced <> 0): ?>
                                                            <div class="price_box d-flex align-items-center">
                                                                <span
                                                                    class="current_price">$<?= $product->sale_price ?></span>
                                                            </div>
                                                        <?php
                                                        else: ?>
                                                            <div class="price_box">
                                                                <span class="current_price">&nbsp;</span>
                                                            </div>

                                                        <?php
                                                        endif; ?>
                                                        <div class="d-flex">
                                                            <?php
                                                            if ($company->show_inventory): ?>
                                                                <div class="price_box d-flex">
                                    <span class="badge text-bg-light text-danger"
                                          style="font-size: 14px;text-align: left;padding-left: 0;">STOCK:
                                        <?= $product->stock <= 0 ? 0 : number_format($product->stock, 0) ?></span>

                                                                </div>
                                                            <?php
                                                            else: ?>
                                                                <div class="price_box">
                                    <span class="badge text-bg-light text-danger"
                                          style="font-size: 14px;text-align: left;padding-left: 0;">&nbsp;</span>
                                                                </div>
                                                            <?php
                                                            endif; ?>

                                                            <?php
                                                            if ($company->show_semaforo_stock)
                                                                $this->load->view('components/semaphore/semaphore',['info_company'=>$company,'product'=>$product])
                                                            ?>
                                                        </div>
                                                        <?php
                                                        if ($product->descriptions): ?>
                                                            <div class="modal_description mb-15 variants_size">
                                                                <h2><?= $lang_pop['detail'] ?></h2>
                                                                <p> <?= $product->descriptions ?></p>
                                                            </div>
                                                        <?php
                                                        endif; ?>
                                                        <div class="variants_selects">

                                                            <div class="modal_add_to_cart">

                                                                <form class="form-save-qty" novalidate
                                                                      action="<?= $base_url."/addProduct" ?>">
                                                                    <div class="variants_size">
                                                                        <h2><?= $lang_pop['comment'] ?></h2>
                                                                        <div class="order-notes">
                                                <textarea name="comment" style="height: auto;"
                                                          id="order_note"
                                                          placeholder="..."></textarea>
                                                                        </div>
                                                                    </div>
                                                                    <div class="variants_size">
                                                                        <h2><?= $lang_pop['qty'] ?></h2>
                                                                    </div>
                                                                    <input min="1" name="qty" type="number" value="0">
                                                                    <input hidden name="product_id" type="text"
                                                                           value="<?= $product->pro_id ?>">
                                                                    <button style="font-size: 15px;font-weight: 500;"
                                                                            id="m-btn-submit"
                                                                            class="boton-add-car"><span
                                                                            class="spinner-border spinner-border-sm d-none"
                                                                            role="status"
                                                                            aria-hidden="true"></span> <?= $lang_pop['add'] ?>
                                                                        <i
                                                                            class="fa fa-cart-plus"
                                                                            aria-hidden="true"></i></button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                        } ?>
                    </div>
                    <div class="shop_toolbar t_bottom">
                        <?php
                        $this->load->view('components/catalog/pagination');
                        if(!empty($modals))
                            foreach ($modals as $modalsView){
                                $this->load->view($modalsView);
                            }
                        ?>
                    </div>
                    <?php
                } else { ?>
                    <div class="col-12 text-center">
                        <img src="<?= base_url('uploads/vacio.PNG') ?>">
                        <h5>Sin información!</h5>
                    </div>
                    <?php
                } ?>
            </div>
        </div>
    </div>
    <div v-if="getSelectedProduct">
        <div
            ref="backdrop"
            class="modal-backdrop fade"
            :class="{ show: getSelectedProduct }"
            @click="closeModal"
        ></div>
        <div class="modal fade modal_box_product" id="selected-product-modal" ref="productModal"
             tabindex="-1"
             role="dialog" aria-hidden="true"
             style="display: block; z-index: 1050;">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <button type="button" class="close" @click.prevent="closeModal()" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    <div class="modal_body">
                        <div class="container">
                            <div class="row">
                                <div class="col-lg-5 col-md-5 col-sm-12">

                                    <div class="modal_tab">
                                        <div class="tab-content product-details-large" style="height: 70%">
                                            <template v-if="sortedImages.length > 0">
                                                <div
                                                    v-for="(img, index) in sortedImages"
                                                    :key="index"
                                                    class="tab-pane fade"
                                                    :class="{ 'show active': index === 0 }"
                                                    :id="`tab${index + 1}`"
                                                    role="tabpanel"
                                                >
                                                    <div class="modal_tab_img">
                                                        <a href="#">
                                                            <img
                                                                :src="getImageSrc(img)"
                                                                alt=""
                                                                style="height: 400px; width: 100%; object-fit: scale-down;"
                                                                @error="handleImageError($event)"
                                                            />
                                                        </a>
                                                    </div>
                                                </div>
                                            </template>

                                            <template v-else>
                                                <div class="tab-pane fade show active" id="tab1" role="tabpanel">
                                                    <div class="modal_tab_img">
                                                        <a href="#">
                                                            <img
                                                                :src="noImageSrc"
                                                                alt=""
                                                                style="height: 400px; width: 100%; object-fit: scale-down;"
                                                            />
                                                        </a>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>

                                        <div class="modal_tab_button" style="height: 30%">
                                            <ul id="selectedProductCarousel" class="nav product_navactive owl-carousel" role="tablist">
                                                <li v-if="sortedImages.length === 0">
                                                    <a class="nav-link active"
                                                   data-toggle="tab"
                                                       href="#tab1"
                                                       role="tab"
                                                       aria-controls="tab1"
                                                       aria-selected="true">
                                                        <img
                                                            style="height: 100px;width: 100%;object-fit: scale-down;"
                                                            :src="noImageSrc"
                                                        alt="">
                                                    </a>
                                                </li>
                                                <li v-for="(img, index) in sortedImages" :key="index">
                                                    <a class="nav-link"
                                                       :class="{ active: index === 0 }"
                                                       data-toggle="tab"
                                                       :href="'#tab' + (index + 1)"
                                                       role="tab"
                                                       :aria-controls="'tab' + (index + 1)"
                                                       :aria-selected="index === 0 ? 'true' : 'false'">
                                                        <img
                                                            style="height: 100px;width: 100%;object-fit: scale-down;"
                                                            :src="getImageSrc(img)"
                                                            @error="handleImageError($event)"
                                                            alt="">
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-7 col-md-7 col-sm-12">
                                    <div class="modal_right">
                                        <div class="modal_title mb-10">
                                            <h2>{{ getSelectedProduct.name.toUpperCase() }}</h2>
                                            <p>SKU: {{ getSelectedProduct.sku.toUpperCase() }}</p>
                                            <p v-if="getSelectedProduct.minimum_stock != 0 && getSelectedProduct.minimum_stock != 1">{{ parseInt(getSelectedProduct.minimum_stock) }}</p>
                                            <p v-else> UNITS {{ getSelectedProduct.unit_type }}</p>
                                        </div>
                                    </div>
                                    <div class="price_box d-flex align-items-center">
                                        <span :class="'current_price ' + (getSelectedProduct.price_reduced != 0 ? 'strikethrough text-dark' : '')">
                                          ${{ getSelectedProduct.sale_price }}
                                        </span>
                                    </div>
                                    <div v-if="getSelectedProduct.price_reduced != 0" class="price_box d-flex align-items-center">
                                        <span class="current_price">${{ getSelectedProduct.sale_price }}</span>
                                    </div>
                                    <div v-else class="price_box">
                                        <span class="current_price">&nbsp;</span>
                                    </div>
                                    <div class="d-flex">
                                        <?php
                                        if ($company->show_inventory): ?>
                                            <div class="price_box d-flex">
                                    <span class="badge text-bg-light text-danger"
                                          style="font-size: 14px;text-align: left;padding-left: 0;">STOCK: {{ getSelectedProduct.stock }}

                                            </div>
                                        <?php
                                        else: ?>
                                            <div class="price_box">
                                    <span class="badge text-bg-light text-danger"
                                          style="font-size: 14px;text-align: left;padding-left: 0;">&nbsp;</span>
                                            </div>
                                        <?php
                                        endif; ?>

                                        <?php
                                        if ($company->show_semaforo_stock)
                                        //                                                $this->load->view('components/semaphore/semaphore',['info_company'=>$company,'product'=>$product])
                                        ?>
                                    </div>
                                    <div v-if="getSelectedProduct.descriptions" class="modal_description mb-15 variants_size">
                                        <h2><?= $lang_pop['detail'] ?></h2>
                                        <p>{{ getSelectedProduct.descriptions }}</p>
                                    </div>
                                    <div class="variants_selects">

                                        <div class="modal_add_to_cart">

                                            <form class="form-save-qty" novalidate
                                                  action="<?= $base_url."/addProduct" ?>">
                                                <div class="variants_size">
                                                    <h2><?= $lang_pop['comment'] ?></h2>
                                                    <div class="order-notes">
                                                <textarea name="comment" style="height: auto;"
                                                          id="order_note"
                                                          placeholder="..."></textarea>
                                                    </div>
                                                </div>
                                                <div class="variants_size">
                                                    <h2><?= $lang_pop['qty'] ?></h2>
                                                </div>
                                                <input min="1" name="qty" type="number" value="0">
                                                <input hidden name="product_id" type="text"
                                                       :value="getSelectedProduct.pro_id">
                                                <button style="font-size: 15px;font-weight: 500;"
                                                        id="m-btn-submit"
                                                        class="boton-add-car"><span
                                                        class="spinner-border spinner-border-sm d-none"
                                                        role="status"
                                                        aria-hidden="true"></span> <?= $lang_pop['add'] ?>
                                                    <i
                                                        class="fa fa-cart-plus"
                                                        aria-hidden="true"></i></button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

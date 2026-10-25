<?php
$catalogShowPrices = ! isset($catalog_studio) || ! is_object($catalog_studio) || ! empty($catalog_studio->show_prices);
$cardColClass = (isset($catalog_studio) && is_object($catalog_studio) && ! empty($catalog_studio->card_col_class))
    ? (string) $catalog_studio->card_col_class
    : 'col-lg-3 col-md-4 col-sm-6';
?>
<div data-categories="<?= $product->category_id ?>" data-product-id="<?= $product->pro_id ?>"
     id="card-<?= $product->pro_id ?>" class="<?= htmlspecialchars($cardColClass, ENT_QUOTES, 'UTF-8') ?> p-2 card-product over-category">
    <article id="figure-<?= $product->pro_id ?>" class="single_product">

        <?php

        $imagenes_explode = ($product->implode_img == null || $product->implode_img === '') ? [] : explode(',', $product->implode_img);
        $nuevo_array = array();
        $primaryRef = ! empty($product->images) ? trim((string) $product->images) : null;

        foreach ($imagenes_explode as $img) {
            $img = trim((string) $img);
            if ($img !== '' && $primaryRef !== null && $img === $primaryRef) {
                array_push($nuevo_array, $img);
            }
        }

        foreach ($imagenes_explode as $img) {
            $img = trim((string) $img);
            if ($img !== '' && ($primaryRef === null || $img !== $primaryRef)) {
                array_push($nuevo_array, $img);
            }
        }

        $imagenes_explode = $nuevo_array;

        $cartTokenStr = ! empty($cart_token) ? (string) $cart_token : '';
        $noImgUrl = catalog_public_no_image_url();
        $carouselUrls = array();

        foreach ($imagenes_explode as $img) {
            if ($img !== null && $img !== '') {
                $carouselUrls[] = catalog_public_image_url($img, $cartTokenStr);
            }
        }

        if ($carouselUrls === [] && ! empty($product->imagen_js)) {
            $carouselUrls[] = catalog_public_image_url($product->imagen_js, $cartTokenStr);
        } elseif ($carouselUrls === [] && ! empty($product->images)) {
            $carouselUrls[] = catalog_public_image_url($product->images, $cartTokenStr);
        }

        if ($carouselUrls === []) {
            $carouselUrls[] = $noImgUrl;
        }

        $carouselUrls = array_values(array_unique($carouselUrls));
        $hasCarousel = count($carouselUrls) > 1;
        $previewUrl = (string) ($carouselUrls[0] ?? '');
        $enableImagePopover = ($previewUrl !== '' && $previewUrl !== $noImgUrl);

        ?>

        <figure class="w-100 animate__animated animate__zoomIn animate_02s">
            <div class="product_thumb w-100">
                <div class="fv-catalog-card-image"<?php if ($enableImagePopover) : ?>
                     data-toggle="popover-hover"
                     data-img="<?= htmlspecialchars($previewUrl, ENT_QUOTES, 'UTF-8') ?>"
                     aria-label="<?= htmlspecialchars((string) ($product->name ?? ''), ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>>
                    <?php if ($hasCarousel) { ?>
                        <div class="fv-catalog-card-slideshow" data-pro-id="<?= (int) $product->pro_id ?>">
                            <?php foreach ($carouselUrls as $key => $imgSrc) { ?>
                                <img class="fv-catalog-slide<?= ($key === 0) ? ' is-active' : '' ?>"
                                     src="<?= htmlspecialchars($imgSrc, ENT_QUOTES, 'UTF-8') ?>"
                                     data-index="<?= (int) ($key + 1) ?>"
                                     onerror="this.onerror=null; this.src='<?= htmlspecialchars($noImgUrl, ENT_QUOTES, 'UTF-8') ?>';"
                                     alt="<?= htmlspecialchars($product->name ?? '', ENT_QUOTES, 'UTF-8') ?>">
                            <?php } ?>
                        </div>
                    <?php } else { ?>
                        <div class="primary_img">
                            <img class="img-list"
                                 src="<?= htmlspecialchars($carouselUrls[0], ENT_QUOTES, 'UTF-8') ?>"
                                 onerror="this.onerror=null; this.src='<?= htmlspecialchars($noImgUrl, ENT_QUOTES, 'UTF-8') ?>';"
                                 alt="<?= htmlspecialchars($product->name ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    <?php } ?>
                </div>
                <div class="label_product">
                    <?php

                    $mostrar_estrella = ! empty($product->has_star_order);

                    if ($mostrar_estrella) {
                        ?>
                        <a href="#">
                        <span class="label_sale bg-danger text-white p-0 rounded" style="font-size: 16px;">
                            <p><span id="count">1</span>&nbsp;&nbsp;/<?= $product->cImg ?></p>
                        </span>
                            <span class="label_sale bg-transparent text-warning"
                                  style="font-size: 25px;right: 50px;top: 10px;" data-tippy-placement="top"
                                  data-tippy-arrow="true" data-tippy-inertia="false"
                                  data-tippy="<?= $lang_catalogo['sold'] ?>">
                            <i class="ion-android-star-outline"></i>
                        </span>

                        </a>
                    <?php
                    } else { ?>

                        <?php
                        if ($hasCarousel || count($imagenes_explode) > 0) { ?>
                            <a href="#">
                            <span class="label_sale bg-danger text-white p-0 rounded" style="font-size: 16px;">
                            <p><span
                                    id="count-<?= (int) $product->pro_id ?>"><?= ($product->cImg > 0) ? 1 : 0 ?></span>&nbsp;&nbsp;/<?= ($product->cImg > 0) ? (int) $product->cImg : 0 ?>
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

            <div class="product_content grid_content info-scroll-bar" style="text-align: left;">
                <div class="p-2 content-scroll-bar bar">
                    <h4 class="product_name text-break"><?= strtoupper($product->name) ?></h4>
                    <?php
                    $catalogRating = function_exists('company_product_rating_value')
                        ? company_product_rating_value($product)
                        : (isset($product->rating) ? (float) $product->rating : 3.5);
                    $catalogRatingAria = (string) ($lang_catalogo['product_rating_aria'] ?? '');
                    if ($catalogRatingAria !== '' && strpos($catalogRatingAria, '%s') !== false) {
                        $catalogRatingAria = sprintf($catalogRatingAria, number_format($catalogRating, 1, '.', ''));
                    }
                    if (function_exists('company_product_rating_stars_html')) {
                        echo '<div class="fv-catalog-product-rating mb-1">'
                            . company_product_rating_stars_html($catalogRating, $catalogRatingAria)
                            . '</div>';
                    }
                    ?>

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
                    <?php if ($catalogShowPrices) : ?>
                    <div class="price_box">
                        <span
                            class="current_price <?= $product->price_reduced <> 0 ? 'strikethrough text-dark' : '' ?>">$<?= $product->salePriceRecalculado ?></span>
                    </div>
                    <?php
                    if ($product->price_reduced <> 0): ?>
                        <div class="price_box">
                            <span class="current_price">$<?= $product->reducedprice_Formatted ?></span>
                        </div>
                    <?php
                    else: ?>
                        <div class="price_box">
                            <span class="current_price">&nbsp;</span>
                        </div>

                    <?php
                    endif; ?>
                    <?php else: ?>
                        <div class="price_box fv-catalog-price-hidden" aria-hidden="true">
                            <span class="current_price">&nbsp;</span>
                        </div>
                    <?php endif; ?>

                    <?php

                    $color_semaforo = $this->Helper->obtener_colorSemaforo($product, $info_company);
                    $leyenda_semaforo = $this->Helper->obtener_leyendaSemaforo($color_semaforo, $info_company);


                    ?>

                    <div class="d-flex">
                        <?php
                        if ($show_stock): ?>
                            <!-- Mostrar stock -->
                            <?php
                            if ($info_company->show_inventory): ?>
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
                                <br>
                            <?php
                            endif; ?>

                            <?php
                            if($info_company->show_semaforo_stock)
                                $this->load->view('components/semaphore/semaphore',['info_company'=>$info_company,'product'=>$product])
                            ?>
                        <?php
                        endif; ?>
                    </div>
                    <!-- Mostrar  -->


                </div>
                <div class="col-12 pb-2">
                    <?php

                    $qty_valor = $lang_catalogo['add'];
                    $comments = null;
                    if (in_array($product->pro_id, array_column($product_detail, 'product_id'))) {
                        $index = array_search($product->pro_id, array_column($product_detail, 'product_id'));
                        $qty_valor = $product_detail[$index]->qty;
                        $comments = $product_detail[$index]->comment;
                    }
                    ?>
                    <div class="text-center">
                        <div class="d-flex justify-content-between p-2">
                            <button data-comment="<?= $comments ?>" data-id="<?= $product->pro_id ?>"
                                    class="boton-add-car buttonpop">
                                <span class="spinner-border spinner-border-sm d-none" role="status"
                                      aria-hidden="true"></span>

                                <span class="text"> <?= $qty_valor ?> </span>
                                <i class="fa fa-cart-plus" aria-hidden="true"></i></button>
                            <button type="button" data-id="<?= $product->pro_id ?>"
                                    class="btn btn-light addqtyminium d-flex align-items-center">
                                <span class="spinner-border spinner-border-sm d-none" role="status"
                                      aria-hidden="true"></span>
                                <span>+<?= $minium_qty ?></span></button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="product_content list_content w-100">
                <h4 class="product_name text-break"><?= strtoupper($product->name) ?></h4>

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
                            <?php if ($catalogShowPrices) : ?>
                            <div class="price_box d-flex align-items-center">
                                <span
                                    class="current_price <?= $product->price_reduced <> 0 ? 'strikethrough text-dark' : '' ?>">$<?= $product->salePriceRecalculado ?></span>
                            </div>
                            <?php
                            if ($product->price_reduced <> 0): ?>
                                <div class="price_box d-flex align-items-center">
                                    <span class="current_price">$<?= $product->reducedprice_Formatted ?></span>
                                </div>
                            <?php
                            else: ?>
                                <div class="price_box">
                                    <span class="current_price">&nbsp;</span>
                                </div>

                            <?php
                            endif; ?>
                            <?php endif; ?>
                            <div class="d-flex">


                                <?php
                                if ($show_stock): ?>

                                <!-- Mostrar stock -->
                                <?php
                                if ($info_company->show_inventory): ?>
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
                                    <br>
                                <?php
                                endif; ?>

                                <?php
                                if ($info_company->show_semaforo_stock)
                                    $this->load->view('components/semaphore/semaphore',['info_company'=>$info_company,'product'=>$product])
                                ?>
                                <?php
                                endif;
                                ?>
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-12">
                            <div class="text-center">
                                <div class="d-flex justify-content-between p-2">
                                    <button data-comment="<?= $comments ?>" data-id="<?= $product->pro_id ?>"
                                            class="boton-add-car buttonpop">
                                        <span class="spinner-border spinner-border-sm d-none" role="status"
                                              aria-hidden="true"></span>
                                        <span class="text"> <?= $qty_valor ?> </span>
                                        <i class="fa fa-cart-plus" aria-hidden="true"></i></button>
                                    <button type="button" data-id="<?= $product->pro_id ?>"
                                            class="btn btn-light addqtyminium d-flex align-items-center">
                                        <span class="spinner-border spinner-border-sm d-none" role="status"
                                              aria-hidden="true"></span>
                                        <span>+<?= $minium_qty ?></span></button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </figure>
    </article>
</div>

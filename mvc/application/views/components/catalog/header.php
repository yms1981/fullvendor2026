<?php

/**
 * @var array $categories_order Ordered list in which categories should be shown
 * @var string $catalog_text The catalog name
 * @var object $company User/Session info
 * @var object $info_seller Seller object
 * @var object $info_customer Customer object
 * @var $lang_json Array The lang translations for every translatable string
 * @var array $totale Cart total $
 * @var $qtyProducts Cart qty products
 * @var $base_url string
 * @var $breadcrumb array
 * @var $showSellerButtons bool
 */

$lang_catalogo = $lang_json['cmp_catalog']['lang'];

if (isset($categorias)) {
    $categorias = count($categories_order) > 1 ? $categories_order : $categorias;
}?>
    <header>
        <div class="main_header header_padding">
            <div class="container">
                <!--header top start-->
                <div class="header_middle sticky-header">
                    <div class="row">
                        <div class="col-3">
                            <div class="logo">
                                <div class="row">
                                    <div class="col-md-12 col-lg-4">
                                        <a class="d-flex" id="url_shop"
                                           href="<?= seller_url('/') ?>">
                                            <img src="<?= company_logo_url((string) $company->image) ?>"
                                                 style="max-height: 70px;max-width: 140px; object-fit: scale-down;"
                                                 alt="<?= $company->name ?>">
                                            <h4 class="pt-2" style="text-align: left;"><?= strtoupper(
                                                    $company->name
                                                ) ?></h4>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <h3 class="text-center d-none d-sm-none d-md-none d-lg-none d-xl-block">
                                <?= $catalog_text ?>
                            </h3>
                            <h3 class="text-center d-none d-sm-none d-md-none d-lg-none d-xl-block">
                                <?= "{$lang_catalogo['welcome']} {$info_customer->name}"?>
                            </h3>
                            <h5 class="text-center d-none d-sm-none d-md-none d-lg-none d-xl-block">
                                <?= "({$info_customer->business_name})" ?>
                            </h5>
                        </div>
                        <div class="col-3">
                            <div class="d-flex justify-content-end">
                                <?php
                                    $currentUserName = $user['role'] === 'seller'
                                        ? $info_seller->user->name
                                        : $info_customer->name;
                                ?>
                                <div class="btn-group" style="margin-bottom:7px;" data-tippy-placement="top"
                                     data-tippy-arrow="true" data-tippy-inertia="false"
                                     data-tippy="<?= $currentUserName ?>">
                                    <button type="button" class="btn btn-danger dropdown-toggle rounded-circle"
                                            data-bs-toggle="dropdown" aria-expanded="false"
                                            style="width:50px;height:50px;">
                                        <i class="fa fa-user" aria-hidden="true"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="<?= base_url('logout') ?>"><i
                                                    class="fa fa-sign-out" aria-hidden="true"></i>
                                                <?= $lang_catalogo['logout'] ?></a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <?php
                                if ($company->login_seller == 1): ?>

                                <?php
                                endif; ?>

                                <div class="header_configure_area">

                                    <div class="mini_cart_wrapper d-block d-md-none">

                                        <a href="javascript:void(0)" onclick="open_seachSidebar()">
                                                <span class="label_sale bg-transparent"
                                                      style="font-size: 25px;right: 50px;top: 10px;"
                                                      data-tippy-placement="top" data-tippy-arrow="true"
                                                      data-tippy-inertia="false"
                                                      data-tippy="<?= $lang_catalogo['ver_carrito'] ?>">
                                                    <i class="ion-ios-search-strong"></i>
                                                </span>
                                        </a>
                                    </div>

                                    <div class="mini_cart_wrapper">
                                        <a href="<?= $base_url."/showOrder" ?>">
                                                <span class="label_sale bg-transparent"
                                                      style="font-size: 25px;right: 50px;top: 10px;"
                                                      data-tippy-placement="top" data-tippy-arrow="true"
                                                      data-tippy-inertia="false"
                                                      data-tippy="<?= $lang_catalogo['ver_carrito'] ?>">
                                                    <i class="fa fa-shopping-bag"></i>
                                                </span>

                                            <span class="cart_price">$<span
                                                    class="total-monto"><?= $total ?></span><i
                                                    class="ion-ios-arrow-down"></i></span>
                                            <span id="total_qtypro"
                                                  class="cart_count <?= ($qtyProducts < 1) ? 'd-none' : '' ?>"><?= $qtyProducts ?>
                                                </span>

                                        </a>
                                    </div>

                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                <!--header middel end-->

                <!--mini cart-->

                <!--mini cart end-->


                <!--header bottom satrt-->
                <?php
                if (isset($categorias)): ?>

                    <div class="header_bottom">

                        <div class="row align-items-center">
                            <div class="col-lg-1 col-md-12 d-none d-md-block"></div>
                            <div class="d-md-none">
                                <h4 class="d-none d-md-block">&nbsp;<h4>
                                        <div class="header_bigsale">
                                            <a href="#" style="cursor: default;">
                                                <img src="<?= base_url('images/logos/logo.png') ?>"
                                                     class="thumbnail rounded"
                                                     style="display:inline-block;width: 25px;margin-bottom:0;">
                                                <?= $info_catalog->catalog_name ?>
                                            </a>
                                        </div>
                            </div>
                            <div class="col-lg-9 col-md-6 col-sm-12 py-2">
                                <div class="">
                                    <form id="form-filtro-buscar-nav" class="form-filtro-buscar"
                                          action="catalog"
                                          method="get">
                                        <input hidden class="short-value" type="text" name="short">
                                        <div id="search_product" class="search_box border dropdown form-control">
                                            <input id="input-search-product" @keyup="handleSearch"
                                                   style="height:100%; border: none !important;"
                                                   aria-expanded="false"
                                                   tabindex="1" autocomplete="off" v-model="searchProduct" autofocus
                                                   name="search"
                                                   placeholder="<?= $lang_catalogo['seach_product'] ?>"
                                                   type="text"
                                                   @focus="handleFocus"
                                                   @blur="handleBlur">
                                            <!-- animate__animated animate__fadeInUp dropwdown__searproduct__vue -->
                                            <div
                                                ref="dropdownSearch"
                                                class="dropdown-menu sidebar_widget animate__animated animate__fadeInUp dropwdown__searproduct__vue"
                                                data-bs-reference="parent"
                                                :class="{show: isFocused && getProductbySearch.length > 0}">
                                                <div v-if="getProductbySearch.length === 0"
                                                     class="text-center pt-5 pb-5">
                                                    <h6><i class='bx bx-search-alt bx-sm'></i>...</h6>
                                                </div>
                                                <li v-for="pro in getProductbySearch" :key="pro.pro_id"
                                                    :value="pro.pro_id"
                                                    class="d-flex fullvendor-item-li"
                                                    @click.prevent="select_product(pro.pro_id)">
                                                    <div class="ps-2 pb-2 pt-2">
                                                        <figure class="figure-fullvendor-product-search">
                                                            <img class="img-full-vendor-product-search"
                                                                 :src="pro.images"
                                                                 alt="">
                                                        </figure>
                                                    </div>
                                                    <div class="d-flex align-items-center fullvendor-widget-list">
                                                        <h3 class="ps-3 text-wrap w-100 mark-uppercase">
                                                            <span v-if="pro.highlighted_name" v-html="pro.highlighted_name"></span>
                                                            <span v-else>{{ pro.completed_name }}</span>
                                                        </h3>
                                                    </div>
                                                </li>

                                            </div>
                                            <!-- <?= $lang_catalogo['search'] ?> -->
                                            <button id="btn_search_producto" type="submit"><i
                                                    class='bx bx-search-alt bx-sm'></i></button>

                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-6 d-none d-md-block">
                                <div class="header_bigsale">
                                    <a href="#" style="cursor: default;">
                                        <img src="<?= base_url('images/logos/logo.png') ?>"
                                             class="thumbnail rounded"
                                             style="display:inline-block;width: 25px;margin-bottom:0;">
                                        <?= $info_catalog->catalog_name ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--header bottom end-->
                <?php
                else: ?>
                    <div class="header_bottom">
                        <div class="row align-items-center">

                            <div class="col-12">
                                <h4 class="d-none d-md-block"><?= strtoupper($lang_catalogo['customer']) ?>:
                                    <?= strtoupper($info_customer->business_name) . strtoupper(
                                        (isset($info_customer->name)) ? ' (' . $info_customer->name . ')' : ''
                                    ) ?>
                                </h4>
                                <h4><?= strtoupper(
                                        $lang_catalogo['seller'] . ': ' . $info_seller->user->name
                                    ) ?>
                                </h4>
                                <h4><?= strtoupper($lang_catalogo['group'] . ': ' . $info_customer->customer_group->name) ?></h4>
                            </div>

                        </div>
                    </div>
                <?php
                endif; ?>

            </div>
        </div>
        <?php
        if ($user['role'] !== 'customer' && $info_customer): ?>
            <div class="breadcrumbs_area">
                <div class="container">
                    <div class="row">
                        <div class="col-md-12 col-lg-6">
                            <?php if ($breadcrumb): ?>
                                <div class="breadcrumb_content">
                                    <ul>
                                        <?php
                                        $trailCount = count($breadcrumb);
                                        $totalSteps = $trailCount + 1;
                                        ?>

                                        <li><a href="javascript:history.go(-<?= $totalSteps - 1 ?>)"><?= $lang_catalogo['home'] ?></a></li>

                                        <?php foreach ($breadcrumb as $index => $trail): ?>
                                            <li>
                                                <?php if ($index === $trailCount - 1): ?>
                                                    <?= $trail ?>
                                                <?php else: ?>
                                                    <a href="javascript:history.go(-<?= $trailCount - $index - 1 ?>)"><?= $trail ?></a>
                                                <?php endif; ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-12 col-lg-6" style="text-align:right;">
                            <?php if($user['role'] === 'admin'){ ?>
                                <a href="<?= company_url('orders') ?>" class="btn btn-danger" type="submit">Cancel Order</a>
                            <?php }

                            if($user['role'] === 'seller' && $showSellerButtons){ ?>
                                <a href="<?= seller_url('history') ?>"
                                   class="btn btn-success" type="submit"><?= "Historial de Ordenes" ?></a>
                                <a id="btn_createneworder" href="<?= seller_url('/home/selectCustomerView') ?>"
                                   class="btn btn-success" type="submit"><?= $lang_catalogo['create_neworder'] ?></a>
                            <?php } ?>

                        </div>
                    </div>
                </div>
            </div>
        <?php
        endif; ?>
    </header>
<!--header area end-->

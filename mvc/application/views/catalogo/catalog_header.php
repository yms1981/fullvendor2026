<?php

$ahora = DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));
$v_imagen = $ahora->format("YmdHisu");
if (isset($categorias) && isset($categories_order) && is_array($categories_order) && count($categories_order) > 1) {
    $categorias = $categories_order;
}
$catalogUriIndex = isset($catalog_uri_index) ? (string) $catalog_uri_index : 'catalogo/producto';
$catalogUriCarts = isset($catalog_uri_carts) ? (string) $catalog_uri_carts : 'catalogo/producto/carts';
?>

<!DOCTYPE html>

<html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title><?= SITENAME ?></title>
    <link rel="shortcut icon" href="<?= base_url('companyasset/images/favicon.png')?>" />

    <script>
    var UrlBase = '<?= base_url() ?>';
    var url_base = UrlBase;
    var Token = '<?= htmlspecialchars((string) ($company->token_kor ?? $this->input->get('token') ?? ''), ENT_QUOTES, 'UTF-8') ?>';
    var token = Token;
    var companyId = <?= isset($info_company) && is_object($info_company) ? (int)$info_company->company_id : 0 ?>;
    var version = '<?= $version ?? '' ?>';
    var env = {
        url: UrlBase,
        base_url: UrlBase
    };
    </script>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <!-- Plugins CSS -->
    <link rel="stylesheet" href="<?= base_url('companyasset/antomi/assets/css/plugins.css?v='.$v_imagen)?>">
    <!-- Main Style CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" integrity="sha512-MV7K8+y+gLIBoVD59lQIYicR65iaqukzvf/nwasF0nqhPay5w/9lJmVM2hMDcnK1OnMGCdVK+iQrJ7lzPJQd1w==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="<?= base_url('companyasset/css/boxicons.css?v='.$v_imagen) ?>" />
    <link rel="stylesheet" href="<?= base_url('companyasset/antomi/assets/css/style.css?v='.$v_imagen)?>">
    <link rel="stylesheet" href="<?= base_url('companyasset/css/animate.min.css?v='.$v_imagen)?>">
    <link rel="stylesheet" href="<?= base_url('companyasset/css/style_adicional_catalog.css?v='.$v_imagen)?>" />
    <link rel="stylesheet" href="<?= base_url('companyasset/antomi/assets/css/jquery.toast.css?v='.$v_imagen)?>">
    <link rel="stylesheet" href="<?= base_url('companyasset/antomi/assets/css/sweetalert2.min.css?v='.$v_imagen)?>">
    <link rel="stylesheet" href="<?= base_url('companyasset/css/preloader.css?v='.$v_imagen) ?>" />
    <link rel="stylesheet" href="<?= base_url('companyasset/css/fullvendor-stilos.css?v='.$v_imagen) ?>" />

    <?php
    if (! isset($catalog_studio) || ! is_object($catalog_studio)) {
        $catalog_studio = (object) [
            'logo_url' => '',
            'theme' => (object) [
                'banner_bg' => '#111827',
                'banner_text' => '#ffffff',
                'footer_bg' => '#111827',
                'footer_text' => '#e5e7eb',
                'accent' => '#b91c1c',
            ],
        ];
    }
    $studioTheme = isset($catalog_studio->theme) && is_object($catalog_studio->theme)
        ? $catalog_studio->theme
        : (object) [
            'banner_bg' => '#111827',
            'banner_text' => '#ffffff',
            'footer_bg' => '#111827',
            'footer_text' => '#e5e7eb',
            'accent' => '#b91c1c',
        ];
    ?>
    <style>
    :root {
        --fv-catalog-banner-bg: <?= htmlspecialchars((string) ($studioTheme->banner_bg ?? '#111827'), ENT_QUOTES, 'UTF-8') ?>;
        --fv-catalog-banner-text: <?= htmlspecialchars((string) ($studioTheme->banner_text ?? '#ffffff'), ENT_QUOTES, 'UTF-8') ?>;
        --fv-catalog-footer-bg: <?= htmlspecialchars((string) ($studioTheme->footer_bg ?? '#111827'), ENT_QUOTES, 'UTF-8') ?>;
        --fv-catalog-footer-text: <?= htmlspecialchars((string) ($studioTheme->footer_text ?? '#e5e7eb'), ENT_QUOTES, 'UTF-8') ?>;
        --fv-catalog-accent: <?= htmlspecialchars((string) ($studioTheme->accent ?? '#b91c1c'), ENT_QUOTES, 'UTF-8') ?>;
    }

    .strikethrough {
        text-decoration: line-through;
        text-decoration-thickness: 1.5px;
    }

    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
    });
    </script>
</head>

<body class="fv-catalog-studio-theme">
    <?php if (! empty($show_customer_portal_nav) && ! empty($portal_nav_catalog_url)) { ?>
    <nav class="customer-portal-catalog-nav" aria-label="<?= htmlspecialchars((string) ($lang_catalogo['portal_nav_aria'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="background:#111;color:#fff;padding:10px 16px;display:flex;gap:14px;flex-wrap:wrap;align-items:center;justify-content:center;font-size:.875rem;border-bottom:2px solid #dc3545;">
      <a style="color:#fff;font-weight:700;text-decoration:none;" href="<?= htmlspecialchars((string) $portal_nav_catalog_url, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($lang_catalogo['portal_nav_catalog'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
      <span style="opacity:.45" aria-hidden="true">|</span>
      <a style="color:#fff;font-weight:700;text-decoration:none;" href="<?= htmlspecialchars((string) $portal_nav_orders_url, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($lang_catalogo['portal_nav_orders'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
      <span style="opacity:.45" aria-hidden="true">|</span>
      <a style="color:#ffb3b3;font-weight:600;text-decoration:none;" href="<?= htmlspecialchars((string) $portal_nav_logout_url, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($lang_catalogo['portal_nav_logout'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
    </nav>
    <?php } ?>
    <?php
    $catalogHideCustomerBanner = ! empty($hide_catalog_header_customer_line ?? null)
        || ! empty($show_customer_portal_nav ?? null);
    $catalogShowCustomerLine = ! $catalogHideCustomerBanner;
    ?>
    <?php $this->load->view('company/layouts/loader'); ?>
    <!--Offcanvas menu area start-->
    <div class="off_canvars_overlay"></div>
    <div class="Offcanvas_menu">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="canvas_open">
                        <!-- <a href="javascript:void(0)"><i class="ion-navicon"></i></a> -->
                        <a id="seach_insidebar" hidden href="javascript:void(0)"><i
                                class="ion-ios-search-strong"></i></a>
                    </div>
                    <div class="Offcanvas_menu_wrapper">
                        <br><br>
                        <div class="search_container">
                            <form class="form-filtro-buscar" action="<?= base_url($catalogUriIndex) ?>" method="get">
                                <input hidden type="text" name="token" value="<?= $company->token_kor ?>">
                                <input hidden class="short-value" type="text" name="short" value="<?= htmlspecialchars((string) ($this->input->get('short') ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <div class="hover_category movil">
                                    <select class="select_option" name="cat_id">
                                        <option selected value><?= strtoupper($lang_catalogo['option_all']) ?></option>
                                        <?php
                                        $categorias = isset($categorias) && (is_array($categorias) || is_object($categorias)) ? $categorias : [];
                                        if (count($categorias) > 0) {
                                            foreach ($categorias as $cat) {
                                                if($cat->qty_products > 0) {
                                                ?>
                                        <option <?= $this->input->get('cat_id')==$cat->cat_id?'selected':'' ?>
                                            value="<?= $cat->cat_id ?>">
                                            <?= strtoupper($cat->category_default==1?$lang_catalogo["sin_categoria"]:$cat->category_name) ?>
                                        </option>
                                        <?php }} ?>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="search_box">
                                    <input name="search" value="<?= $this->input->get('search') ?>"
                                        placeholder="<?= $lang_catalogo['seach_product'] ?>" type="text">
                                    <button type="submit"><?= $lang_catalogo['search'] ?></button>
                                </div>
                            </form>
                        </div>
                        <div id="menu" class="text-start ">

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- catalog-header-v2: logo + cliente/vendedor + barra roja + carrito con badge (header fuera de #app_vue para que Vue no lo reemplace al montar) -->
    <header>
            <div class="main_header header_padding">
                <div class="container">
                    <!--header top start-->
                    <div class="header_middle sticky-header catalog-header-top">
                        <div class="row align-items-center">
                            <div class="col-7 col-md-8">
                                <div class="logo d-flex align-items-center">
                                    <a class="d-flex align-items-center" id="url_shop"
                                        href="<?= base_url($catalogUriIndex) ?>?token=<?= rawurlencode((string) $company->token_kor) ?>">
                                        <?php
                                        $studioLogo = trim((string) ($catalog_studio->logo_url ?? ''));
                                        $logo_src = $studioLogo !== ''
                                            ? $studioLogo
                                            : (! empty($info_company->image)
                                                ? company_logo_url((string) $info_company->image)
                                                : base_url('images/logos/logo.png'));
                                        ?>
                                        <img src="<?= htmlspecialchars($logo_src, ENT_QUOTES, 'UTF-8') ?>"
                                            style="max-height: 70px;max-width: 140px; object-fit: scale-down;"
                                            alt="<?= htmlspecialchars($info_company->name ?? 'Fullvendor') ?>"
                                            onerror="this.onerror=null; this.src='<?= base_url('images/logos/logo.png') ?>';">
                                        <h4 class="mb-0 ms-2 d-none d-sm-block" style="text-align: left;"><?= strtoupper($info_company->name ?? '') ?></h4>
                                    </a>
                                </div>
                            </div>
                            <div class="col-5 col-md-4 d-flex justify-content-end align-items-center">
                                <?php if($company->login_seller==1 && isset($info_seller)): ?>
                                <div class="btn-group me-2" data-tippy-placement="top" data-tippy-arrow="true"
                                    data-tippy-inertia="false" data-tippy="<?= htmlspecialchars(($info_seller->first_name ?? '') . ' ' . ($info_seller->last_name ?? '')) ?>">
                                    <button type="button" class="btn btn-danger dropdown-toggle rounded-circle" data-bs-toggle="dropdown" aria-expanded="false" style="width:45px;height:45px;">
                                        <i class="fa fa-user" aria-hidden="true"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="<?= seller_url('logout') ?>"><i class="fa fa-sign-out" aria-hidden="true"></i> <?= $lang_catalogo['logout'] ?? 'Salir' ?></a></li>
                                    </ul>
                                </div>
                                <?php endif; ?>
                                <div class="mini_cart_wrapper d-none d-md-block me-0">
                                    <a href="javascript:void(0)" class="d-flex align-items-center catalog-cart-link catalog-cart-toggle" role="button" aria-expanded="false" aria-controls="catalog-mini-cart">
                                        <span class="cart-icon-wrapper position-relative" data-tippy="<?= htmlspecialchars((string) ($lang_catalogo['ver_carrito'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="fa fa-shopping-bag"></i>
                                            <span id="total_qtypro" class="cart_count cart-count-total position-absolute top-0 start-100 translate-middle <?= ((int)($totales['cantidad_productos'] ?? 0) < 1) ? 'd-none' : '' ?>"><?= (int)($totales['cantidad_productos'] ?? 0) ?></span>
                                        </span>
                                        <span class="cart_price ms-2">$<span class="total-monto"><?= htmlspecialchars($totales['total_orden'] ?? '0.00') ?></span><i class="ion-ios-arrow-down ms-1"></i></span>
                                    </a>
                                </div>
                                <div class="mini_cart_wrapper d-block d-md-none">
                                    <a href="javascript:void(0)" class="d-flex align-items-center catalog-cart-link catalog-cart-toggle" role="button" aria-expanded="false" aria-controls="catalog-mini-cart">
                                        <span class="cart-icon-wrapper position-relative"><i class="fa fa-shopping-bag"></i>
                                        <span id="total_qtypro_mob" class="cart_count cart-count-total position-absolute top-0 start-100 translate-middle <?= ((int)($totales['cantidad_productos'] ?? 0) < 1) ? 'd-none' : '' ?>"><?= (int)($totales['cantidad_productos'] ?? 0) ?></span></span>
                                        <span class="cart_price ms-1 small">$<span class="total-monto"><?= htmlspecialchars($totales['total_orden'] ?? '0.00') ?></span></span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--header middel end-->

                    <!--mini cart-->
                    <div id="catalog-mini-cart" class="mini_cart catalog-mini-cart" aria-hidden="true"
                        data-no-image="<?= htmlspecialchars(catalog_public_no_image_url(), ENT_QUOTES, 'UTF-8') ?>"
                        data-label-qty="<?= htmlspecialchars((string) ($lang_catalogo['cantidad'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                        data-label-remove="<?= htmlspecialchars((string) ($lang_catalogo['cart_remove_item'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                        data-label-empty="<?= htmlspecialchars((string) ($lang_catalogo['cart_empty'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                        data-label-increase="<?= htmlspecialchars((string) ($lang_catalogo['cart_qty_increase'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                        data-label-decrease="<?= htmlspecialchars((string) ($lang_catalogo['cart_qty_decrease'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <div class="cart_close d-flex align-items-center justify-content-between">
                            <div class="cart_text">
                                <h3><?= htmlspecialchars((string) ($lang_catalogo['cart_sidebar_title'] ?? $lang_catalogo['your_order'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h3>
                            </div>
                            <div class="mini_cart_close">
                                <a href="javascript:void(0)" class="catalog-cart-close" aria-label="<?= htmlspecialchars((string) ($lang_catalogo['back'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="fa fa-times" aria-hidden="true"></i>
                                </a>
                            </div>
                        </div>
                        <div class="catalog-mini-cart__body">
                            <div id="catalog-mini-cart-items" class="cart_gallery catalog-mini-cart__items"></div>
                            <div id="catalog-mini-cart-empty" class="catalog-mini-cart__empty text-center py-4 d-none">
                                <i class="fa fa-shopping-bag fa-2x mb-2 text-muted" aria-hidden="true"></i>
                                <p class="mb-0"><?= htmlspecialchars((string) ($lang_catalogo['cart_empty'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                        </div>
                        <div class="mini_cart_table">
                            <div class="cart_total">
                                <span><?= htmlspecialchars((string) ($lang_catalogo['cart_subtotal'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="price">$<span id="catalog-mini-cart-subtotal" class="total-monto"><?= htmlspecialchars((string) ($totales['total_orden'] ?? '0.00'), ENT_QUOTES, 'UTF-8') ?></span></span>
                            </div>
                        </div>
                        <div class="mini_cart_footer">
                            <div class="cart_button">
                                <a class="active" href="<?= base_url($catalogUriCarts) ?>?token=<?= rawurlencode((string) $company->token_kor) ?>">
                                    <?= htmlspecialchars((string) ($lang_catalogo['cart_checkout'] ?? $lang_catalogo['finish'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </div>
                            <div class="cart_button">
                                <a href="<?= base_url($catalogUriCarts) ?>?token=<?= rawurlencode((string) $company->token_kor) ?>">
                                    <?= htmlspecialchars((string) ($lang_catalogo['cart_view_full'] ?? $lang_catalogo['ver_carrito'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    <!--mini cart end-->


                    <!--header bottom: opcional fila CUSTOMER (omitida en portal cliente: ya va en pedidos / no duplicar) | ALL CATEGORIES + búsqueda + FullVendor -->
                    <?php if(isset($categorias)):?>
                    <div class="header_bottom catalog-header-bottom-layout">
                        <?php if ($catalogShowCustomerLine) { ?>
                        <div class="row catalog-header-customer-row">
                            <div class="col-12">
                                <h4 class="mb-0"><?= strtoupper($lang_catalogo['customer'] ?? 'CUSTOMER') ?>: <?= strtoupper( (isset($info_customer->business_name) ? $info_customer->business_name : '') . (isset($info_customer->name) && $info_customer->name ? ' (' . $info_customer->name . ')' : '' ) ) ?></h4>
                            </div>
                        </div>
                        <?php } ?>
                        <div class="row catalog-header-search-row align-items-center">
                            <div class="col-12">
                                <div class="d-flex flex-wrap align-items-center gap-2 catalog-header-bar">
                                    <div class="d-flex flex-wrap align-items-center flex-grow-1 catalog-header-search-group">
                                        <form id="form-filtro-buscar-nav" class="form-filtro-buscar catalog-header-search-form d-flex flex-grow-1 flex-wrap align-items-center" action="<?= base_url($catalogUriIndex) ?>" method="get">
                                            <input type="hidden" name="token" value="<?= $company->token_kor ?>">
                                            <input type="hidden" class="short-value" name="short" value="<?= htmlspecialchars((string) ($this->input->get('short') ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                            <div class="hover_category catalog-header-cat-select">
                                                <select class="select_option form-select form-select-sm" name="cat_id">
                                                    <option value=""><?= strtoupper($lang_catalogo['option_all'] ?? 'ALL CATEGORIES') ?></option>
                                                    <?php if (!empty($categorias)) { foreach ($categorias as $cat) { if (isset($cat->qty_products) && $cat->qty_products > 0) { ?>
                                                    <option <?= $this->input->get('cat_id')==$cat->cat_id ? 'selected' : '' ?> value="<?= (int)$cat->cat_id ?>"><?= $cat->category_default==1 ? strtoupper($lang_catalogo["sin_categoria"] ?? 'WITHOUT CATEGORY') : strtoupper($cat->category_name) ?></option>
                                                    <?php }}} ?>
                                                </select>
                                            </div>
                                            <div class="search_box border dropdown catalog-header-search-input-wrap">
                                                <input id="input-search-product" class="dropdown-toggle form-control" aria-expanded="false" tabindex="1" autocomplete="off" name="search" value="<?= htmlspecialchars($this->input->get('search') ?? '') ?>" placeholder="<?= $lang_catalogo['seach_product'] ?? 'Search product...' ?>" type="text">
                                                <div id="app_vue" class="dropdown-menu sidebar_widget animate__animated animate__fadeInUp dropwdown__searproduct__vue" data-bs-reference="parent" :class="{show: getProductbySearch.length > 0}">
                                                    <div v-if="getProductbySearch.length === 0" class="text-center pt-5 pb-5"><h6><i class='bx bx-search-alt bx-sm'></i>...</h6></div>
                                                    <li v-for="pro in getProductbySearch" :key="pro.pro_id" :value="pro.pro_id" class="d-flex fullvendor-item-li" @click.prevent="select_product($event, pro)">
                                                        <div class="ps-2 pb-2 pt-2"><figure class="figure-fullvendor-product-search"><img class="img-full-vendor-product-search" :src="pro.images" alt=""></figure></div>
                                                        <div class="d-flex align-items-center fullvendor-widget-list"><h3 class="ps-3 text-wrap w-100"><span v-if="pro.highlighted_name" v-html="pro.highlighted_name"></span><span v-else>{{ pro.completed_name.toUpperCase() }}</span></h3></div>
                                                    </li>
                                                </div>
                                                <button id="btn_search_producto" type="submit" class="btn btn-danger btn-search-catalog"><i class="bx bx-search-alt bx-sm"></i></button>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="header_bigsale catalog-header-fullvendor">
                                        <a href="<?= base_url($catalogUriIndex) ?>?token=<?= rawurlencode((string) $company->token_kor) ?>" class="btn btn-danger text-white text-nowrap"><?php if(!empty($company->show_logofullvendor) || !empty($info_catalog->default_catalog)): ?><img src="<?= base_url('images/logos/logo.png') ?>" alt="Fullvendor" class="thumbnail rounded me-1" style="display:inline-block;width: 25px;margin-bottom:0;vertical-align:middle;"><?php endif; ?><?= isset($info_catalog->catalog_name) ? $info_catalog->catalog_name : ($lang_catalogo['catalog'] ?? 'FullVendor Catalog') ?></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php else:?>
                    <div id="app_vue" class="d-none"></div>
                    <div class="header_bottom">
                        <div class="row align-items-center">
                            <div class="col-12">
                                <?php if ($catalogShowCustomerLine) { ?>
                                <strong><?= strtoupper($lang_catalogo['customer'] ?? 'CUSTOMER') ?>: <?= strtoupper(($info_customer->business_name ?? '') . (isset($info_customer->name) && $info_customer->name ? ' (' . $info_customer->name . ')' : '')) ?></strong>
                                <?php } ?>
                                <?php if (isset($info_seller)) { ?><?php if ($catalogShowCustomerLine) { ?><br><?php } ?><strong><?= strtoupper($lang_catalogo['seller'] ?? 'SELLER') ?>: <?= strtoupper(($info_seller->first_name ?? '') . ' ' . ($info_seller->last_name ?? '')) ?></strong><?php } ?>
                                <?php if (isset($info_customer->group_name)) { ?><?php if ($catalogShowCustomerLine || isset($info_seller)) { ?><br><?php } ?><strong><?= strtoupper($lang_catalogo['group'] ?? 'GROUP') ?>: <?= strtoupper($info_customer->group_name) ?></strong><?php } ?>
                            </div>
                        </div>
                    </div>
                    <?php endif;?>

                </div>
            </div>
        </header>
    <!--header area end-->

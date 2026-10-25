<?php
    //$categorias = count($categories_order)>1?$categories_order:$categorias;
    $categorias = $categories_order;
    $catalogUriIndex = isset($catalog_uri_index) ? (string) $catalog_uri_index : 'catalogo/producto';
    //echo json_encode($categorias);
    $hideCatalogBreadcrumb = isset($company->type_cart) && $company->type_cart === 'to_customer';
    $hasCatalogBreadcrumbBar = ! empty($company->return_admin) || ! empty($company->login_seller);
    if (! isset($catalog_studio) || ! is_object($catalog_studio)) {
        $catalog_studio = (object) [
            'show_prices' => true,
            'shop_view' => 'grid_4',
            'card_col_class' => 'col-lg-3 col-md-4 col-sm-6',
            'message' => '',
            'template_key' => 'classic-grid',
            'cols' => 4,
            'layout' => 'grid',
            'theme' => (object) [
                'banner_bg' => '#111827',
                'banner_text' => '#ffffff',
                'footer_bg' => '#111827',
                'footer_text' => '#e5e7eb',
                'accent' => '#b91c1c',
            ],
        ];
    }
    $studioShopView = (string) ($catalog_studio->shop_view ?? 'grid_4');
    $studioShowPrices = ! empty($catalog_studio->show_prices);
    $studioMessage = trim((string) ($catalog_studio->message ?? ''));
?>

<?php if (! $hideCatalogBreadcrumb || $hasCatalogBreadcrumbBar) { ?>
<!-- Portal cliente (to_customer): sin «Home > Catalog list»; aquí solo botones si aplican -->
<div class="breadcrumbs_area">
    <div class="container">
        <div class="row align-items-center">
            <?php if (! $hideCatalogBreadcrumb) { ?>
            <div class="col-12 col-md-6">
                <div class="breadcrumb_content">
                    <ul>
                        <li><a href="<?= base_url($catalogUriIndex) ?>?token=<?= rawurlencode((string) $company->token_kor) ?>"><?= $lang_catalogo['home'] ?? 'Home' ?></a></li>
                        <li class="active"><?= $lang_catalogo['catalog_list'] ?? 'Catalog List' ?></li>
                    </ul>
                </div>
            </div>
            <?php } ?>
            <div class="col-12 <?= $hideCatalogBreadcrumb ? '' : 'col-md-6' ?> text-end mt-2 mt-md-0">
                <?php if(!empty($company->return_admin)){ ?>
                <a href="<?= company_url('orders') ?>" class="btn btn-danger btn-sm"><?= $lang_catalogo['cancel_order'] ?? 'Cancel Order' ?></a>
                <?php } elseif(!empty($company->login_seller)){ ?>
                <a href="<?= base_url('catalogo/producto/historial?token='.$company->token_kor) ?>" class="btn btn-success btn-sm"><?= $lang_catalogo['historial'] ?? 'Historial' ?></a>
                <a id="btn_createneworder" href="<?= seller_url('catalogo?token='.$company->token_kor) ?>" class="btn btn-success btn-sm"><?= $lang_catalogo['create_neworder'] ?? 'Create new order' ?></a>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
<?php } ?>
<!--shop  area start-->
<div class="shop_area">
    <div class="container">
        <!-- Seller / Group en caja gris como en imagen -->
        <?php if(!empty($company->login_seller) && isset($info_seller)): ?>
        <div class="catalog-seller-group-box bg-light rounded py-2 px-3 mb-3">
            <div class="row">
                <div class="col-12">
                    <strong class="text-uppercase"><?= $lang_catalogo['seller'] ?? 'SELLER' ?>: <?= strtoupper(($info_seller->first_name ?? '') . ' ' . ($info_seller->last_name ?? '')) ?></strong>
                    <?php if(!empty($info_customer->group_name)): ?>
                    <br><strong class="text-uppercase"><?= $lang_catalogo['group'] ?? 'GROUP' ?>: <?= strtoupper($info_customer->group_name) ?></strong>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="row">
            <?php
            $shortQs = $this->input->get('short');
            $shortQuery = ($shortQs !== null && $shortQs !== '') ? ('&amp;short=' . (int) $shortQs) : '';
            ?>
            <!-- Productos a la izquierda; en móvil primero -->
            <div class="col-lg-9 col-md-12 order-1 order-lg-1">
                <!--shop banner area start-->
                <!--shop banner area end-->
                <!--shop toolbar start-->
                <div class="shop_toolbar_wrapper">
                    <div class="shop_toolbar_btn">
                        <button data-role="grid_4" type="button" class="<?= $studioShopView === 'grid_4' ? 'active ' : '' ?>btn-grid-4"
                            data-toggle="tooltip"
                            data-tippy-placement="top"
                            data-tippy-arrow="true"
                            data-tippy-inertia="false"
                            data-tippy="<?= $lang_catalogo['cuadricula'] ?>"
                            title="4"></button>
                        <button data-role="grid_3" type="button" class="<?= $studioShopView === 'grid_3' ? 'active ' : '' ?>btn-grid-3"
                            data-toggle="tooltip"
                            data-tippy-placement="top"
                            data-tippy-arrow="true"
                            data-tippy-inertia="false"
                            data-tippy="<?= $lang_catalogo['cuadricula'] ?>"
                            title="3"></button>
                        <button data-role="grid_list" type="button" class="<?= $studioShopView === 'grid_list' ? 'active ' : '' ?>btn-list" data-toggle="tooltip"
                            data-tippy-placement="top" data-tippy-arrow="true" data-tippy-inertia="false"
                            data-tippy="<?= $lang_catalogo['list'] ?>"
                            title="List"></button>
                    </div>
                    <div class="shop_toolbar_sort">
                        <label for="short" class="sr-only"><?= htmlspecialchars((string) ($lang_catalogo['name'] ?? 'Sort'), ENT_QUOTES, 'UTF-8') ?></label>
                        <select name="short" id="short" class="fv-catalog-sort" aria-label="<?= htmlspecialchars((string) ($lang_catalogo['name'] ?? 'Sort'), ENT_QUOTES, 'UTF-8') ?>">
                            <option <?= ($this->input->get('short') == null || (string) $this->input->get('short') === '1') ? 'selected' : '' ?> value="1"><?= htmlspecialchars((string) ($lang_catalogo['name'] ?? 'Name'), ENT_QUOTES, 'UTF-8') ?> A-Z</option>
                            <option <?= ((string) $this->input->get('short') === '2') ? 'selected' : '' ?> value="2"><?= htmlspecialchars((string) ($lang_catalogo['name'] ?? 'Name'), ENT_QUOTES, 'UTF-8') ?> Z-A</option>
                            <option <?= ((string) $this->input->get('short') === '3') ? 'selected' : '' ?> value="3">SKU A-Z</option>
                            <option <?= ((string) $this->input->get('short') === '4') ? 'selected' : '' ?> value="4">SKU Z-A</option>
                            <?php if ($studioShowPrices) : ?>
                            <option <?= ((string) $this->input->get('short') === '5') ? 'selected' : '' ?> value="5"><?= htmlspecialchars((string) ($lang_catalogo['precio_menor'] ?? 'Price low to high'), ENT_QUOTES, 'UTF-8') ?></option>
                            <option <?= ((string) $this->input->get('short') === '6') ? 'selected' : '' ?> value="6"><?= htmlspecialchars((string) ($lang_catalogo['precio_mayor'] ?? 'Price high to low'), ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="page_amount">
                        <p><?= $lang_catalogo['showing'] ?? 'Showing' ?> 1–<?= $limit ?> <?= $lang_catalogo['of'] ?? 'of' ?>
                            <?= $total_filtro ?> <?= $lang_catalogo['results'] ?? 'results' ?></p>
                    </div>
                </div>
                <!--shop toolbar end-->

                <?php if ($studioMessage !== '') : ?>
                <div class="fv-catalog-featured-message mb-3" role="note">
                    <?= nl2br(htmlspecialchars($studioMessage, ENT_QUOTES, 'UTF-8')) ?>
                </div>
                <?php endif; ?>

                <!--shop wrapper start-->

                <?php
                $productSections = (isset($product_sections) && is_array($product_sections)) ? $product_sections : [];
                $useGroupedView = ! empty($grouped_catalog) && $productSections !== [];
                $hasProducts = $useGroupedView || count($products) > 0;
                ?>

                <?php if ($hasProducts) { ?>

                <div id="tabla" >
                    <div v-if="getProductbySearch.length === 0" id="grid-default" class="row no-gutters shop_wrapper <?= htmlspecialchars($studioShopView, ENT_QUOTES, 'UTF-8') ?> bg-transparent border-0 fv-catalog-grouped"
                         data-studio-template="<?= htmlspecialchars((string) ($catalog_studio->template_key ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                         data-studio-layout="<?= htmlspecialchars((string) ($catalog_studio->layout ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                         data-studio-cols="<?= (int) ($catalog_studio->cols ?? 0) ?>">
                        <?php if ($useGroupedView) :
                            foreach ($productSections as $section) :
                                $section = is_object($section) ? $section : (object) $section;
                                $catName = trim((string) ($section->category_name ?? ''));
                                if ($catName === '' && ! empty($section->category_default)) {
                                    $catName = (string) ($lang_catalogo['sin_categoria'] ?? '');
                                }
                                $catImage = trim((string) ($section->category_image ?? ''));
                                if ($catName !== '') :
                        ?>
                        <div class="col-12 fv-tax-header fv-tax-header--category<?= $catImage !== '' ? ' has-photo' : '' ?>">
                            <?php if ($catImage !== '') : ?>
                            <span class="fv-tax-photo"><img src="<?= htmlspecialchars($catImage, ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy" onerror="this.closest('.fv-tax-photo')&&this.closest('.fv-tax-photo').remove()"></span>
                            <?php endif; ?>
                            <span class="fv-tax-title"><?= htmlspecialchars(strtoupper($catName), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <?php
                                endif;
                                foreach ((array) ($section->groups ?? []) as $group) :
                                    $group = is_object($group) ? $group : (object) $group;
                                    $gName = trim((string) ($group->group_name ?? ''));
                                    $gImage = trim((string) ($group->group_image ?? ''));
                                    if ($gName !== '') :
                        ?>
                        <div class="col-12 fv-tax-header fv-tax-header--group<?= $gImage !== '' ? ' has-photo' : '' ?>">
                            <?php if ($gImage !== '') : ?>
                            <span class="fv-tax-photo"><img src="<?= htmlspecialchars($gImage, ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy" onerror="this.closest('.fv-tax-photo')&&this.closest('.fv-tax-photo').remove()"></span>
                            <?php endif; ?>
                            <span class="fv-tax-title"><?= htmlspecialchars(strtoupper($gName), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <?php
                                    endif;
                                    foreach ((array) ($group->subgroups ?? []) as $sub) :
                                        $sub = is_object($sub) ? $sub : (object) $sub;
                                        $sName = trim((string) ($sub->subgroup_name ?? ''));
                                        $sImage = trim((string) ($sub->subgroup_image ?? ''));
                                        if ($sName !== '') :
                        ?>
                        <div class="col-12 fv-tax-header fv-tax-header--subgroup<?= $sImage !== '' ? ' has-photo' : '' ?>">
                            <?php if ($sImage !== '') : ?>
                            <span class="fv-tax-photo"><img src="<?= htmlspecialchars($sImage, ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy" onerror="this.closest('.fv-tax-photo')&&this.closest('.fv-tax-photo').remove()"></span>
                            <?php endif; ?>
                            <span class="fv-tax-title"><?= htmlspecialchars(strtoupper($sName), ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                        <?php
                                        endif;
                                        foreach ((array) ($sub->products ?? []) as $product) :
                                            $this->load->view('catalogo/product_row', [
                                                'product' => $product,
                                                'info_company' => $info_company,
                                                'cart_token' => $company->token_kor ?? '',
                                                'catalog_studio' => $catalog_studio,
                                            ]);
                                        endforeach;
                                    endforeach;
                                    foreach ((array) ($group->products ?? []) as $product) :
                                        $this->load->view('catalogo/product_row', [
                                            'product' => $product,
                                            'info_company' => $info_company,
                                            'cart_token' => $company->token_kor ?? '',
                                            'catalog_studio' => $catalog_studio,
                                        ]);
                                    endforeach;
                                endforeach;
                                foreach ((array) ($section->products ?? []) as $product) :
                                    $this->load->view('catalogo/product_row', [
                                        'product' => $product,
                                        'info_company' => $info_company,
                                        'cart_token' => $company->token_kor ?? '',
                                        'catalog_studio' => $catalog_studio,
                                    ]);
                                endforeach;
                            endforeach;
                        else :
                            foreach ($products as $product) :
                                $this->load->view('catalogo/product_row', [
                                    'product' => $product,
                                    'info_company' => $info_company,
                                    'cart_token' => $company->token_kor ?? '',
                                    'catalog_studio' => $catalog_studio,
                                ]);
                            endforeach;
                        endif;
                        ?>
                    </div>
                </div>
                <div class="shop_toolbar t_bottom">
                    <div class="pagination">
                        <?=@$paginate?>
                    </div>
                </div>
                <?php } else { ?>
                <div class="col-12 text-center">
                    <img src="<?= base_url('uploads/vacio.PNG') ?>">
                    <h5><?= htmlspecialchars((string) ($lang_catalogo['no_products'] ?? $lang_catalogo['sin_informacion'] ?? 'Sin información!'), ENT_QUOTES, 'UTF-8') ?></h5>
                </div>
                <?php } ?>

            </div>
            <!-- Sidebar categorías a la derecha; en móvil debajo de productos -->
            <div class="col-lg-3 col-md-12 order-2 order-lg-2">
                <!--sidebar widget start-->
                <aside class="sidebar_widget catalog-sidebar">
                    <div class="widget_list widget_categories">
                        <h3><?= $lang_catalogo['product_categories'] ?></h3>
                        <ul>
                            <li>
                                <a href="<?= base_url($catalogUriIndex) ?>?token=<?= rawurlencode((string) $company->token_kor) ?><?= $shortQuery ?>"
                                    class="d-flex">
                                    <span class="ps-2 <?= $this->input->get('cat_id')==null?'text-danger':'' ?>">
                                        <i style="width: 30px;height: 30px;" class="fa fa-th-large"
                                            aria-hidden="true"></i><?= strtoupper($lang_catalogo["all"]) ?>
                                    </span>
                                </a>
                            </li>
                            <!-- categorias -->
                            <?php

                            if (count($categorias) > 0) {
                                foreach ($categorias as $cat) {
                                    if (isset($cat->qty_products) && (int) $cat->qty_products <= 0) {
                                        continue;
                                    }
                            ?>
                            <li id="cat-li-<?= $cat->cat_id ?>"
                                <?= $this->input->get('cat_id')==$cat->cat_id?'data-principal="TRUE"':'' ?>
                                class="<?= $this->input->get('cat_id')==$cat->cat_id?'bg-secondary bg-opacity-25':'' ?> px-1">
                                <a href="<?= base_url($catalogUriIndex) ?>?token=<?= rawurlencode((string) $company->token_kor) ?>&amp;cat_id=<?= (int) $cat->cat_id ?><?= $shortQuery ?>"
                                    class="d-flex">
                                    <?php  if($cat->category_default!=1): ?>
                                    <?php
                                    $cartTokenStr = ! empty($company->token_kor) ? (string) $company->token_kor : '';
                                    $cat_img_src = catalog_public_no_image_url();
                                    $cat_uuid = $cat->image_uuid ?? $cat->image ?? null;
                                    if (! empty($cat_uuid)) {
                                        $cat_img_src = catalog_public_image_url($cat_uuid, $cartTokenStr);
                                    } elseif (! empty($cat->images)) {
                                        $cat_img_src = base_url('uploads/categories/' . @$company->company_id . '/' . $cat->images) . '?v=' . $version;
                                    }
                                    ?>
                                    <img style="width: 30px;height: 30px;"
                                        src="<?= $cat_img_src ?>"
                                        alt="<?= htmlspecialchars($cat->category_name ?? '') ?>">
                                    <?php endif;?>
                                    <span
                                        class="ps-2 <?= $this->input->get('cat_id')==$cat->cat_id?'text-danger':'' ?>">
                                        <?php  if($cat->category_default==1): ?>
                                        <i style="width: 30px;height: 30px;font-size: 19px;" class="fa fa-ban"
                                            aria-hidden="true"></i>
                                        <?php endif;?>
                                        <?= strtoupper($cat->category_default==1?$lang_catalogo["sin_categoria"]:$cat->category_name) ?>
                                        <?php if (isset($cat->qty_products)): ?>
                                        <span class="badge bg-secondary ms-1"><?= (int)$cat->qty_products ?></span>
                                        <?php endif; ?>
                                    </span>
                                </a>
                            </li>
                            <?php }
                            } ?>

                        </ul>
                    </div>

                </aside>
                <!--sidebar widget end-->
            </div>
        </div>
    </div>
</div>
<!--shop  area end-->

<?php
defined('BASEPATH') or exit('No direct script access allowed');

$this->load->view('seller/partials/portal_head', [
    'pageTexts' => $pageTexts ?? [],
    'settings' => $settings ?? null,
    'url_home' => $url_home ?? site_url('sellers/customer'),
    'url_orders' => $url_orders ?? site_url('sellers/orders'),
    'url_catalog' => $url_catalog ?? site_url('sellers/catalog'),
    'url_logout' => $url_logout ?? site_url('sellers/logout'),
    'portal_company_name' => $portal_company_name ?? '',
    'html_lang' => $html_lang ?? 'en',
    'active_portal_tab' => $active_portal_tab ?? 'catalog',
    'portal_page_title' => $portal_page_title ?? '',
]);

$t = is_array($pageTexts ?? null) ? $pageTexts : [];
$e = static function (?string $s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
};
$money = is_array($orders_money ?? null) ? $orders_money : [];
$moneySymbol = (string) ($money['symbol'] ?? '$');
$moneyDigits = isset($money['digits']) ? (int) $money['digits'] : 2;
$moneyRadix = (string) ($money['radixPoint'] ?? '.');
$moneyGroup = (string) ($money['groupSeparator'] ?? ',');
$customerLabel = (string) ($catalog_customer_label ?? '');
$products = is_array($catalog_products ?? null) ? $catalog_products : [];
$categoriesFilter = is_array($catalog_categories_filter ?? null) ? $catalog_categories_filter : [];
$productsError = ! empty($catalog_products_error);
$flashKey = isset($catalog_flash) && is_string($catalog_flash) ? $catalog_flash : '';
$flashMin = isset($catalog_flash_min) && is_string($catalog_flash_min) ? $catalog_flash_min : '';
$cartCount = isset($catalog_cart_count) ? (int) $catalog_cart_count : 0;
$cartTotalAmount = isset($catalog_cart_total_amount) ? (float) $catalog_cart_total_amount : 0.0;
$cartTotalFmt = number_format($cartTotalAmount, max(0, $moneyDigits), $moneyRadix, $moneyGroup);
$allProductsTotal = isset($catalog_all_products_total) ? (int) $catalog_all_products_total : 0;
$marketingCatalogUrl = trim((string) ($catalog_marketing_catalog_url ?? ''));
$imgBase = rtrim((string) ($catalog_image_base ?? ''), '/');
$catalogNoimg = (string) ($catalog_noimg_url ?? base_url('uploads/noimg.png'));
$catalogImgVer = trim((string) ($catalog_image_version ?? ''));
$urlHome = (string) ($url_catalog_home ?? site_url('sellers/catalog'));
$urlCart = (string) ($url_catalog_cart ?? site_url('sellers/catalog/cart'));
$urlCheckout = (string) ($url_catalog_checkout ?? site_url('sellers/catalog/checkout'));
$urlProducts = (string) ($url_catalog_products ?? site_url('sellers/catalog/products'));
$postUrl = site_url('sellers/catalog/products');
$selCat = isset($catalog_filter_cat_id) && $catalog_filter_cat_id !== null ? (int) $catalog_filter_cat_id : 0;
$selSearch = isset($catalog_filter_search) ? (string) $catalog_filter_search : '';
$selShort = isset($catalog_filter_short) && $catalog_filter_short !== null ? (int) $catalog_filter_short : 0;
$shortSel = ($selShort >= 1 && $selShort <= 6) ? $selShort : 1;
$showStock = ! empty($catalog_show_stock);
$sellInv = ! empty($catalog_sell_with_inventory);
$settingsObj = isset($settings) && is_object($settings) ? $settings : null;
$logoFile = $settingsObj !== null ? trim((string) ($settingsObj->logo ?? '')) : '';
$fullvendorLogoSrc = $logoFile !== '' ? base_url('uploads/logos/' . $logoFile) : base_url('images/logos/logo.png');
$fullvendorLogoFallback = base_url('images/logos/isologo.png');
$resultsEnd = count($products);
$resultsTotal = $resultsEnd > 0 ? $resultsEnd : 0;

$catalogFilterQuery = static function (int $catId, string $search, int $short, string $baseUrl): string {
    $q = [];
    if ($catId > 0) {
        $q['cat_id'] = (string) $catId;
    }
    if ($search !== '') {
        $q['search'] = $search;
    }
    if ($short >= 1 && $short <= 6 && $short !== 1) {
        $q['short'] = (string) $short;
    }

    return $baseUrl . ($q !== [] ? ('?' . http_build_query($q)) : '');
};
?>
        <style>
          .catalog-pro-page .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
          }
          .catalog-pro-page {
            /* Rojo catálogo alineado con referencia comercial */
            --catalog-pro-red: #c10016;
            --catalog-pro-red-dark: #a50012;
            --catalog-pro-ink: #1a1a1a;
            --catalog-pro-muted: #525252;
            --catalog-pro-border: #d4d4d4;
            --catalog-pro-grey-bar: #ececec;
          }
          .catalog-pro-page.portal-welcome-card {
            padding: 0;
            overflow: hidden;
            max-width: 100%;
          }
          .catalog-pro-storehead {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px 20px;
            padding: 12px 20px;
            background: #ffffff;
            border-bottom: 1px solid var(--catalog-pro-border);
          }
          .catalog-pro-storehead-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
          }
          .catalog-pro-storehead-brand img {
            max-height: 48px;
            max-width: 200px;
            object-fit: contain;
          }
          .catalog-pro-cart-wrap {
            position: relative;
          }
          .catalog-pro-cart-summary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            background: #fafafa;
            border: 1px solid var(--catalog-pro-border);
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.0625rem;
            color: var(--catalog-pro-ink);
            cursor: pointer;
            list-style: none;
          }
          .catalog-pro-cart-summary::-webkit-details-marker { display: none; }
          .catalog-pro-cart-summary .catalog-pro-cart-ic { font-size: 1.25rem; line-height: 1; }
          .catalog-pro-cart-dd[open] .catalog-pro-cart-summary {
            border-color: var(--catalog-pro-red);
          }
          .catalog-pro-cart-panel {
            position: absolute;
            right: 0;
            top: calc(100% + 6px);
            min-width: 220px;
            padding: 12px 14px;
            background: #fff;
            border: 1px solid var(--catalog-pro-border);
            border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            z-index: 30;
          }
          .catalog-pro-cart-panel a {
            display: inline-block;
            margin-top: 8px;
            font-weight: 600;
            color: var(--catalog-pro-red) !important;
            text-decoration: none !important;
          }
          .catalog-pro-customer-row {
            display: grid;
            grid-template-columns: 1fr auto;
            align-items: center;
            gap: 12px 16px;
            padding: 12px 20px;
            background: #fff;
            border-bottom: 1px solid var(--catalog-pro-border);
          }
          .catalog-pro-customer-text {
            min-width: 0;
          }
          .catalog-pro-customer-actions {
            flex-shrink: 0;
          }
          .catalog-pro-customer-line {
            margin: 0;
            padding: 0;
            font-size: 0.8125rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: var(--catalog-pro-ink);
          }
          .catalog-pro-customer-line strong {
            font-weight: 800;
          }
          .catalog-pro-search-row {
            --catalog-pro-search-r: 10px;
            --catalog-pro-search-h: 58px;
            display: flex;
            flex-wrap: wrap;
            align-items: stretch;
            gap: 14px;
            background: #ffffff;
            padding: 14px 20px;
            border-bottom: 1px solid var(--catalog-pro-border);
            box-sizing: border-box;
          }
          .catalog-pro-search-leading {
            display: flex;
            flex: 1 1 280px;
            min-width: 0;
            align-items: stretch;
            filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.06));
          }
          .catalog-pro-search-combo {
            display: flex;
            flex: 1 1 160px;
            min-width: 0;
            align-items: stretch;
          }
          .catalog-pro-search-row .catalog-pro-cat {
            flex: 0 0 auto;
            display: flex;
            align-items: stretch;
          }
          .catalog-pro-search-row select.catalog-pro-cat-select {
            appearance: none;
            -webkit-appearance: none;
            min-height: var(--catalog-pro-search-h);
            height: 100%;
            padding: 0 40px 0 18px;
            font-size: 0.875rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #fff !important;
            -webkit-text-fill-color: #fff !important;
            background-color: var(--catalog-pro-red) !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='%23ffffff'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 14px center;
            border: 1px solid var(--catalog-pro-red);
            border-right: none;
            border-radius: var(--catalog-pro-search-r) 0 0 var(--catalog-pro-search-r);
            cursor: pointer;
            max-width: min(260px, 100%);
            box-sizing: border-box;
          }
          .catalog-pro-search-row select.catalog-pro-cat-select:focus {
            outline: 2px solid rgba(193, 0, 22, 0.35);
            outline-offset: 1px;
          }
          .catalog-pro-search-row select.catalog-pro-cat-select option {
            color: #1a1a1a;
            background: #fff;
          }
          .catalog-pro-search-input {
            flex: 1 1 auto;
            min-width: 0;
            min-height: var(--catalog-pro-search-h);
            height: 100%;
            padding: 0 16px;
            font-size: 1rem;
            border: 1px solid #c4c4c4;
            border-left: none;
            border-right: none;
            border-radius: 0;
            background: #fff !important;
            color: var(--catalog-pro-ink) !important;
            -webkit-text-fill-color: var(--catalog-pro-ink) !important;
            box-sizing: border-box;
            -webkit-appearance: none;
            appearance: none;
          }
          .catalog-pro-search-input::placeholder {
            color: #8a8a8a;
          }
          .catalog-pro-search-input:focus {
            outline: none;
            border-color: #a3a3a3;
            box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.04);
          }
          .catalog-pro-search-submit {
            flex: 0 0 var(--catalog-pro-search-h);
            width: var(--catalog-pro-search-h);
            min-height: var(--catalog-pro-search-h);
            height: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border: 1px solid var(--catalog-pro-red);
            border-left: none;
            border-radius: 0 var(--catalog-pro-search-r) var(--catalog-pro-search-r) 0;
            background-color: var(--catalog-pro-red) !important;
            color: #fff !important;
            -webkit-text-fill-color: #fff !important;
            cursor: pointer;
            box-sizing: border-box;
            -webkit-appearance: none;
            appearance: none;
          }
          .catalog-pro-search-submit:hover {
            filter: brightness(1.06);
            background-color: var(--catalog-pro-red-dark) !important;
          }
          .catalog-pro-search-submit:focus-visible {
            outline: 2px solid var(--catalog-pro-red);
            outline-offset: 2px;
          }
          .catalog-pro-fv-link {
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            min-height: var(--catalog-pro-search-h);
            padding: 0 18px 0 14px;
            font-size: 0.875rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            text-transform: none;
            text-decoration: none !important;
            color: #fff !important;
            -webkit-text-fill-color: #fff !important;
            background-color: var(--catalog-pro-red) !important;
            border: 1px solid var(--catalog-pro-red);
            border-radius: var(--catalog-pro-search-r);
            white-space: nowrap;
            box-sizing: border-box;
            filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.06));
          }
          .catalog-pro-fv-link:hover {
            filter: brightness(1.06) drop-shadow(0 1px 2px rgba(0, 0, 0, 0.06));
            color: #fff !important;
            -webkit-text-fill-color: #fff !important;
            background-color: var(--catalog-pro-red-dark) !important;
          }
          .catalog-pro-fv-logo {
            width: 34px;
            height: 34px;
            object-fit: contain;
            border-radius: 50%;
            flex-shrink: 0;
            background: #0a0a0a;
            padding: 2px;
            box-sizing: border-box;
          }
          .catalog-pro-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px 16px;
            padding: 10px 20px;
            background: var(--catalog-pro-grey-bar);
            border-bottom: 1px solid var(--catalog-pro-border);
          }
          .catalog-pro-view-toggle {
            display: inline-flex;
            border: 1px solid #c4c4c4;
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
          }
          .catalog-pro-view-toggle button {
            width: 48px;
            height: 42px;
            border: none;
            background: #fff;
            color: var(--catalog-pro-muted);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
          }
          .catalog-pro-view-toggle button + button {
            border-left: 1px solid #c4c4c4;
          }
          .catalog-pro-view-toggle button.is-active {
            background: #e5e5e5;
            color: var(--catalog-pro-ink);
          }
          .catalog-pro-toolbar-sort {
            flex: 1 1 200px;
            display: flex;
            justify-content: center;
            min-width: 0;
          }
          .catalog-pro-toolbar-sort select {
            min-width: 180px;
            max-width: 100%;
            height: 42px;
            padding: 0 14px;
            border-radius: 8px;
            border: 1px solid #c4c4c4;
            font-size: 1rem;
            font-weight: 600;
            color: var(--catalog-pro-ink);
            background: #fff;
          }
          .catalog-pro-results {
            margin: 0;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--catalog-pro-muted);
            text-align: right;
            flex: 1 1 160px;
          }
          .catalog-pro-body {
            display: grid;
            grid-template-columns: 1fr min(300px, 34vw);
            gap: 0;
            align-items: start;
            min-height: 200px;
          }
          @media (max-width: 900px) {
            .catalog-pro-body {
              grid-template-columns: 1fr;
            }
            .catalog-pro-sidebar {
              order: -1;
              border-right: none !important;
              border-bottom: 1px solid var(--catalog-pro-border);
            }
          }
          .catalog-pro-main {
            padding: 20px;
            border-right: 1px solid var(--catalog-pro-border);
          }
          @media (max-width: 900px) {
            .catalog-pro-main { border-right: none; }
          }
          .catalog-pro-sidebar {
            padding: 18px 16px 24px;
            background: #fafafa;
          }
          .catalog-pro-sidebar-title {
            margin: 0 0 14px;
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: var(--catalog-pro-ink);
            padding-bottom: 8px;
            border-bottom: 3px solid var(--catalog-pro-red);
          }
          .catalog-pro-cat-list {
            list-style: none;
            margin: 0;
            padding: 0;
          }
          .catalog-pro-cat-list li {
            border-bottom: 1px solid #e0e0e0;
          }
          .catalog-pro-cat-list a {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 12px 4px;
            text-decoration: none !important;
            color: var(--catalog-pro-ink) !important;
            font-size: 0.8125rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
          }
          .catalog-pro-cat-list a:hover {
            color: var(--catalog-pro-red) !important;
          }
          .catalog-pro-cat-list a.is-active {
            color: var(--catalog-pro-red) !important;
          }
          .catalog-pro-cat-list .catalog-pro-cat-ic {
            font-size: 1rem;
            opacity: 0.85;
            flex-shrink: 0;
          }
          .catalog-pro-cat-list .catalog-pro-cat-name {
            flex: 1;
            min-width: 0;
            line-height: 1.25;
          }
          .catalog-pro-cat-list .catalog-pro-cat-count {
            flex-shrink: 0;
            min-width: 1.75rem;
            height: 1.75rem;
            line-height: 1.75rem;
            text-align: center;
            font-size: 0.6875rem;
            font-weight: 700;
            color: var(--catalog-pro-muted);
            background: #e8e8e8;
            border-radius: 999px;
          }
          .catalog-pro-product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 22px;
          }
          @media (min-width: 1200px) {
            .catalog-pro-product-grid {
              grid-template-columns: repeat(4, 1fr);
            }
          }
          @media (min-width: 900px) and (max-width: 1199.98px) {
            .catalog-pro-product-grid {
              grid-template-columns: repeat(3, 1fr);
            }
          }
          @media (min-width: 560px) and (max-width: 899.98px) {
            .catalog-pro-product-grid {
              grid-template-columns: repeat(2, 1fr);
            }
          }
          .catalog-pro-main.is-list-view .catalog-pro-product-grid {
            grid-template-columns: 1fr;
            gap: 14px;
          }
          .catalog-pro-card {
            background: #fff;
            border: 1px solid var(--catalog-pro-border);
            border-radius: 10px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            position: relative;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
          }
          .catalog-pro-main.is-list-view .catalog-pro-card {
            flex-direction: row;
            align-items: stretch;
            gap: 16px;
            padding: 14px 16px;
          }
          .catalog-pro-card-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 2;
            padding: 3px 8px;
            font-size: 0.65rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #fff;
            background: var(--catalog-pro-red);
            border-radius: 4px;
          }
          .catalog-pro-img-wrap {
            position: relative;
            aspect-ratio: 1;
            min-height: 200px;
            background: #e8e8e8;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
          }
          .catalog-pro-main.is-list-view .catalog-pro-img-wrap {
            width: 112px;
            min-width: 112px;
            min-height: 0;
            aspect-ratio: 1;
            flex-shrink: 0;
          }
          .catalog-pro-img-wrap img {
            width: 100%;
            height: 100%;
            object-fit: contain;
          }
          .catalog-pro-img-ph {
            font-size: 0.8125rem;
            font-weight: 800;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #6b7280;
            text-align: center;
            padding: 12px;
            line-height: 1.35;
            max-width: 90%;
          }
          .catalog-pro-card-body {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
            padding-top: 10px;
          }
          .catalog-pro-main.is-list-view .catalog-pro-card-body {
            padding-top: 0;
            flex-direction: row;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px 16px;
          }
          .catalog-pro-name {
            font-size: 0.9375rem;
            font-weight: 800;
            line-height: 1.3;
            text-transform: uppercase;
            color: var(--catalog-pro-ink);
            margin-bottom: 6px;
          }
          .catalog-pro-main.is-list-view .catalog-pro-name {
            flex: 1 1 200px;
            margin-bottom: 0;
          }
          .catalog-pro-meta {
            font-size: 0.875rem;
            color: var(--catalog-pro-ink);
            margin-bottom: 6px;
          }
          .catalog-pro-price {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--catalog-pro-red);
            margin: 10px 0 12px;
          }
          .catalog-pro-main.is-list-view .catalog-pro-price {
            margin: 0;
            min-width: 5rem;
          }
          .catalog-pro-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            margin-top: auto;
          }
          .catalog-pro-main.is-list-view .catalog-pro-actions {
            margin-top: 0;
            margin-left: auto;
            flex-wrap: nowrap;
          }
          .catalog-pro-btn-add {
            flex: 1;
            min-width: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 16px;
            font-size: 1rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #fff !important;
            -webkit-text-fill-color: #fff !important;
            background: var(--catalog-pro-red) !important;
            border: 1px solid var(--catalog-pro-red) !important;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none !important;
          }
          .catalog-pro-btn-add:disabled {
            opacity: 0.45;
            cursor: not-allowed;
          }
          .catalog-pro-quick {
            font-size: 1.0625rem;
            font-weight: 800;
            color: var(--catalog-pro-red);
            white-space: nowrap;
          }
        </style>
        <section class="portal-welcome-card catalog-pro-page" aria-labelledby="catalog-products-heading">
          <div class="catalog-pro-storehead">
            <div class="catalog-pro-storehead-brand">
              <?php if ($logoFile !== '') { ?>
                <img src="<?= $e(base_url('uploads/logos/' . $logoFile)) ?>" alt="">
              <?php } else { ?>
                <img src="<?= $e(base_url('images/logos/isologo.png')) ?>" alt="">
              <?php } ?>
            </div>
            <details class="catalog-pro-cart-dd catalog-pro-cart-wrap">
              <summary class="catalog-pro-cart-summary" aria-label="<?= $e($t['catalog_cart_bag_aria'] ?? '') ?>">
                <span class="catalog-pro-cart-ic" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 7h15l-1.5 9h-12z"/><path d="M6 7H4.5L4 3H2"/><circle cx="9" cy="20" r="1.2" fill="currentColor" stroke="none"/><circle cx="18" cy="20" r="1.2" fill="currentColor" stroke="none"/></svg></span>
                <span><?= $e($moneySymbol) ?><?= $e($cartTotalFmt) ?></span>
                <span aria-hidden="true" style="font-size:0.65rem;opacity:0.8;">▼</span>
              </summary>
              <div class="catalog-pro-cart-panel">
                <div style="font-size:0.875rem;color:#525252;"><?= $e($t['catalog_cart_lines_in_bag'] ?? '') ?> <?= (string) $cartCount ?></div>
                <div style="font-size:1rem;font-weight:700;margin-top:4px;"><?= $e($moneySymbol) ?><?= $e($cartTotalFmt) ?></div>
                <a href="<?= $e($urlCart) ?>"><?= $e($t['catalog_cart_button'] ?? '') ?><?php if ($cartCount > 0) { ?> (<?= (string) $cartCount ?>)<?php } ?></a>
              </div>
            </details>
          </div>
          <div class="catalog-pro-customer-row">
            <div class="catalog-pro-customer-text">
              <?php if ($customerLabel !== '') { ?>
                <p class="catalog-pro-customer-line"><strong><?= $e($t['catalog_selected_customer_prefix'] ?? '') ?></strong> <?= $e($customerLabel) ?></p>
              <?php } ?>
            </div>
            <div class="catalog-pro-customer-actions">
              <a class="btn-portal-outline" href="<?= $e($urlHome) ?>?reset=1"><?= $e($t['catalog_change_customer'] ?? '') ?></a>
            </div>
          </div>
          <form class="catalog-pro-filter-form" method="get" action="<?= $e($urlProducts) ?>">
            <div class="catalog-pro-search-row">
              <div class="catalog-pro-search-leading">
                <div class="catalog-pro-cat">
                  <label class="sr-only" for="seller-cat-filter"><?= $e($t['catalog_filter_category'] ?? '') ?></label>
                  <select class="catalog-pro-cat-select" id="seller-cat-filter" name="cat_id" onchange="this.form.submit()">
                    <option value=""><?= $e($t['catalog_all_categories'] ?? '') ?></option>
                    <?php foreach ($categoriesFilter as $c) {
                        if (! is_array($c)) {
                            continue;
                        }
                        $cid = (int) ($c['cat_id'] ?? 0);
                        if ($cid <= 0) {
                            continue;
                        }
                        $cname = (string) ($c['name'] ?? '');
                        $cnt = (int) ($c['count'] ?? 0);
                        $sel = $cid === $selCat ? ' selected' : ''; ?>
                    <option value="<?= (string) $cid ?>"<?= $sel ?>><?= $e($cname) ?> (<?= (string) $cnt ?>)</option>
                    <?php } ?>
                  </select>
                </div>
                <div class="catalog-pro-search-combo">
                  <label class="sr-only" for="seller-search"><?= $e($t['catalog_search_label'] ?? '') ?></label>
                  <input class="catalog-pro-search-input" id="seller-search" type="search" name="search" value="<?= $e($selSearch) ?>" placeholder="<?= $e($t['catalog_products_search_placeholder'] ?? '') ?>" autocomplete="off">
                  <button type="submit" class="catalog-pro-search-submit" aria-label="<?= $e($t['catalog_search_submit_aria'] ?? '') ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.35-4.35"/></svg>
                  </button>
                </div>
              </div>
              <?php if ($marketingCatalogUrl !== '') { ?>
                <a class="catalog-pro-fv-link" href="<?= $e($marketingCatalogUrl) ?>" target="_blank" rel="noopener noreferrer">
                  <img class="catalog-pro-fv-logo" src="<?= $e($fullvendorLogoSrc) ?>" alt="" width="34" height="34" onerror="this.onerror=null;this.src='<?= $e($fullvendorLogoFallback) ?>';">
                  <span><?= $e($t['catalog_fullvendor_catalog'] ?? '') ?></span>
                </a>
              <?php } ?>
            </div>
            <div class="catalog-pro-toolbar">
              <div class="catalog-pro-view-toggle" role="group" aria-label="<?= $e($t['catalog_view_toggle_group'] ?? '') ?>">
                <button type="button" class="catalog-pro-view-btn is-active" data-catalog-view="grid" aria-pressed="true" title="<?= $e($t['catalog_view_grid'] ?? '') ?>">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 4h7v7H4V4zm9 0h7v7h-7V4zM4 13h7v7H4v-7zm9 0h7v7h-7v-7z"/></svg>
                </button>
                <button type="button" class="catalog-pro-view-btn" data-catalog-view="list" aria-pressed="false" title="<?= $e($t['catalog_view_list'] ?? '') ?>">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 6h16v2H4V6zm0 5h16v2H4v-2zm0 5h16v2H4v-2z"/></svg>
                </button>
              </div>
              <div class="catalog-pro-toolbar-sort">
                <label class="sr-only" for="seller-short"><?= $e($t['catalog_sort_label'] ?? '') ?></label>
                <select id="seller-short" name="short" onchange="this.form.submit()">
                  <option value="1"<?= $shortSel === 1 ? ' selected' : '' ?>><?= $e($t['catalog_sort_name_asc'] ?? '') ?></option>
                  <option value="2"<?= $shortSel === 2 ? ' selected' : '' ?>><?= $e($t['catalog_sort_name_desc'] ?? '') ?></option>
                  <option value="3"<?= $shortSel === 3 ? ' selected' : '' ?>><?= $e($t['catalog_sort_sku_asc'] ?? '') ?></option>
                  <option value="4"<?= $shortSel === 4 ? ' selected' : '' ?>><?= $e($t['catalog_sort_sku_desc'] ?? '') ?></option>
                  <option value="5"<?= $shortSel === 5 ? ' selected' : '' ?>><?= $e($t['catalog_sort_price_asc'] ?? '') ?></option>
                  <option value="6"<?= $shortSel === 6 ? ' selected' : '' ?>><?= $e($t['catalog_sort_price_desc'] ?? '') ?></option>
                </select>
              </div>
              <p class="catalog-pro-results" role="status">
                <?php if ($resultsEnd > 0) {
                    echo $e(sprintf((string) ($t['catalog_results_range'] ?? '%1$s–%2$s / %3$s'), '1', (string) $resultsEnd, (string) $resultsTotal));
                } else {
                    echo $e($t['catalog_results_empty'] ?? '');
                } ?>
              </p>
            </div>
          </form>
          <?php if ($flashKey !== '') {
              $rawTpl = (string) ($t[$flashKey] ?? $flashKey);
              $msg = ($flashKey === 'catalog_moq_invalid' && $flashMin !== '') ? sprintf($rawTpl, $flashMin) : $rawTpl;
              ?>
            <div class="catalog-flash catalog-flash--error mx-3 mt-2" role="alert" style="margin:12px 20px 0;"><?= $e($msg) ?></div>
          <?php } ?>
          <?php if ($productsError) { ?>
            <p class="catalog-empty mb-0 px-3 py-4" role="alert" style="padding:20px;"><?= $e($t['catalog_products_load_error'] ?? '') ?></p>
          <?php } else { ?>
          <div class="catalog-pro-body">
            <div class="catalog-pro-main" id="catalogProMain">
              <h2 class="sr-only" id="catalog-products-heading"><?= $e($t['catalog_products_title'] ?? '') ?></h2>
              <?php if ($products === []) { ?>
              <p class="catalog-empty mb-0" role="status"><?= $e($t['catalog_products_empty'] ?? '') ?></p>
              <?php } ?>
              <div class="catalog-pro-product-grid">
              <?php foreach ($products as $p) {
                  if (! is_array($p)) {
                      continue;
                  }
                  $pid = (int) ($p['pro_id'] ?? 0);
                  $orderable = ! empty($p['orderable']);
                  $price = (float) ($p['price'] ?? 0);
                  $priceFmt = number_format($price, max(0, $moneyDigits), $moneyRadix, $moneyGroup);
                  $imgPath = trim((string) ($p['img'] ?? ''));
                  $imgSrc = trim((string) ($p['image_url'] ?? ''));
                  if ($imgSrc === '') {
                      $imgSrc = $imgPath !== '' && strncmp($imgPath, 'http', 4) === 0 ? $imgPath : ($imgPath !== '' ? $imgBase . $imgPath : '');
                  }
                  if ($imgSrc === '') {
                      $imgSrc = $catalogNoimg;
                  }
                  $imgSrcSep = (strpos($imgSrc, '?') !== false) ? '&' : '?';
                  $imgSrcFinal = $catalogImgVer !== '' ? ($imgSrc . $imgSrcSep . 'v=' . rawurlencode($catalogImgVer)) : $imgSrc;
                  $minStock = (float) ($p['minimum_stock'] ?? 0);
                  $minStep = $minStock > 0 ? $minStock : 1.0;
                  $showPack = $minStock != 0.0 && abs($minStock - 1.0) > 0.00001;
                  $stockVal = isset($p['stock']) ? (float) $p['stock'] : 0.0;
                  $stockFmt = number_format(max(0, $stockVal), 0, $moneyRadix, $moneyGroup);
                  $stepStr = rtrim(rtrim(sprintf('%.8F', $minStep), '0'), '.');
                  $packBadge = '';
                  if ($showPack && $minStock >= 1) {
                      $packBadge = $e(sprintf((string) ($t['catalog_pack_badge'] ?? '%s'), (string) (int) $minStock));
                  }
                  ?>
                <article class="catalog-pro-card">
                  <?php if ($packBadge !== '') { ?>
                    <span class="catalog-pro-card-badge"><?= $packBadge ?></span>
                  <?php } ?>
                  <div class="catalog-pro-img-wrap">
                    <img src="<?= $e($imgSrcFinal) ?>" alt="" loading="lazy" data-catalog-noimg="<?= $e($catalogNoimg) ?>" onerror="this.onerror=null;var u=this.dataset.catalogNoimg;if(u)this.src=u;var w=this.closest('.catalog-pro-img-wrap');var p=w&&w.querySelector('.catalog-pro-img-ph');if(p){this.style.visibility='hidden';p.style.display='block';}">
                    <span class="catalog-pro-img-ph" style="display:none;"><?= $e($t['catalog_no_image'] ?? '') ?></span>
                  </div>
                  <div class="catalog-pro-card-body">
                    <div class="catalog-pro-name"><?= $e((string) ($p['name'] ?? '')) ?></div>
                    <div class="catalog-pro-meta">
                      <?= $e($t['column_sku'] ?? '') ?>: <?= $e((string) ($p['sku'] ?? '—')) ?>
                      <?php if ($showPack) { ?>
                        <br><?= $e($t['catalog_pack_label'] ?? '') ?>: <?= $e((string) (int) $minStock) ?>
                      <?php } ?>
                    </div>
                    <?php if ($showStock && $orderable) { ?>
                      <div class="catalog-pro-meta"><?= $e($t['catalog_stock_label'] ?? '') ?>: <?= $e($stockFmt) ?></div>
                    <?php } ?>
                    <?php if ($orderable) { ?>
                      <div class="catalog-pro-price"><?= $e($moneySymbol) ?> <?= $e($priceFmt) ?></div>
                      <div class="catalog-pro-actions">
                    <?php if ($sellInv && $stockVal <= 0) { ?>
                      <div class="catalog-pro-meta"><?= $e($t['catalog_out_of_stock'] ?? '') ?></div>
                      <button type="button" class="catalog-pro-btn-add" disabled><?= $e($t['catalog_product_add'] ?? '') ?></button>
                    <?php } else { ?>
                    <form class="catalog-pro-add-form" method="post" action="<?= $e($postUrl) ?>" style="flex:1;min-width:0;display:flex;align-items:center;gap:8px;">
                      <input type="hidden" name="pro_id" value="<?= (string) $pid ?>">
                      <input type="hidden" name="catalog_redirect_cat_id" value="<?= $selCat > 0 ? (string) $selCat : '' ?>">
                      <input type="hidden" name="catalog_redirect_search" value="<?= $e($selSearch) ?>">
                      <input type="hidden" name="catalog_redirect_short" value="<?= (string) $shortSel ?>">
                      <input type="hidden" name="qty" value="<?= $e($stepStr) ?>">
                      <button type="submit" class="catalog-pro-btn-add" style="width:100%;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></svg>
                        <?= $e($t['catalog_product_add'] ?? '') ?>
                      </button>
                    </form>
                    <form method="post" action="<?= $e($postUrl) ?>" style="display:inline;margin:0;">
                      <input type="hidden" name="pro_id" value="<?= (string) $pid ?>">
                      <input type="hidden" name="catalog_add_mode" value="quick">
                      <input type="hidden" name="catalog_redirect_cat_id" value="<?= $selCat > 0 ? (string) $selCat : '' ?>">
                      <input type="hidden" name="catalog_redirect_search" value="<?= $e($selSearch) ?>">
                      <input type="hidden" name="catalog_redirect_short" value="<?= (string) $shortSel ?>">
                      <button type="submit" class="catalog-pro-quick" style="border:none;background:none;cursor:pointer;padding:2px 4px;" title="<?= $e($t['catalog_quick_add_title'] ?? '') ?>">+<?= $e($stepStr) ?></button>
                    </form>
                    <?php } ?>
                      </div>
                    <?php } else { ?>
                      <div class="catalog-pro-meta"><?= $e($t['catalog_product_price_on_request'] ?? '') ?></div>
                      <button type="button" class="catalog-pro-btn-add" disabled><?= $e($t['catalog_product_add'] ?? '') ?></button>
                    <?php } ?>
                  </div>
                </article>
              <?php } ?>
              </div>
            </div>
            <aside class="catalog-pro-sidebar" aria-label="<?= $e($t['catalog_sidebar_categories_title'] ?? '') ?>">
              <h3 class="catalog-pro-sidebar-title"><?= $e($t['catalog_sidebar_categories_title'] ?? '') ?></h3>
              <ul class="catalog-pro-cat-list">
                <li>
                  <a href="<?= $e($catalogFilterQuery(0, $selSearch, $shortSel, $urlProducts)) ?>" class="<?= $selCat === 0 ? 'is-active' : '' ?>">
                    <span class="catalog-pro-cat-ic" aria-hidden="true">▣</span>
                    <span class="catalog-pro-cat-name"><?= $e($t['catalog_category_all'] ?? '') ?></span>
                    <span class="catalog-pro-cat-count"><?= (string) $allProductsTotal ?></span>
                  </a>
                </li>
                <?php foreach ($categoriesFilter as $c) {
                    if (! is_array($c)) {
                        continue;
                    }
                    $cid = (int) ($c['cat_id'] ?? 0);
                    if ($cid <= 0) {
                        continue;
                    }
                    $cname = (string) ($c['name'] ?? '');
                    $cnt = (int) ($c['count'] ?? 0);
                    ?>
                <li>
                  <a href="<?= $e($catalogFilterQuery($cid, $selSearch, $shortSel, $urlProducts)) ?>" class="<?= $cid === $selCat ? 'is-active' : '' ?>">
                    <span class="catalog-pro-cat-ic" aria-hidden="true">◇</span>
                    <span class="catalog-pro-cat-name"><?= $e($cname) ?></span>
                    <span class="catalog-pro-cat-count"><?= (string) $cnt ?></span>
                  </a>
                </li>
                <?php } ?>
              </ul>
            </aside>
          </div>
          <?php } ?>
        </section>
        <script>
        (function () {
          var main = document.getElementById('catalogProMain');
          if (!main) return;
          var key = 'sellerCatalogProductView';
          var btns = document.querySelectorAll('.catalog-pro-view-btn');
          function setView(mode) {
            var isList = mode === 'list';
            main.classList.toggle('is-list-view', isList);
            try { localStorage.setItem(key, mode); } catch (e) {}
            btns.forEach(function (b) {
              var on = b.getAttribute('data-catalog-view') === mode;
              b.classList.toggle('is-active', on);
              b.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
          }
          var saved = null;
          try { saved = localStorage.getItem(key); } catch (e) {}
          if (saved === 'list' || saved === 'grid') setView(saved);
          btns.forEach(function (b) {
            b.addEventListener('click', function () {
              setView(b.getAttribute('data-catalog-view') || 'grid');
            });
          });
          document.querySelectorAll('.catalog-pro-img-wrap img').forEach(function (img) {
            function syncImageState() {
              var wrap = img.closest('.catalog-pro-img-wrap');
              if (!wrap) return;
              var ph = wrap.querySelector('.catalog-pro-img-ph');
              if (!ph) return;
              if (img.complete && img.naturalWidth > 0) {
                img.style.display = '';
                img.style.visibility = '';
                ph.style.display = 'none';
              } else if (img.complete && img.naturalWidth === 0) {
                img.style.display = 'none';
                ph.style.display = 'block';
              }
            }
            img.addEventListener('load', syncImageState);
            syncImageState();
          });
        })();
        </script>
<?php
$catalogImageDebugPayload = [];
foreach ($products as $_pdbg) {
    if (! is_array($_pdbg)) {
        continue;
    }
    $urlRaw = (string) ($_pdbg['image_url'] ?? '');
    $urlForPrefix = $urlRaw;
    $qPos = strpos($urlForPrefix, '?');
    if ($qPos !== false) {
        $urlForPrefix = substr($urlForPrefix, 0, $qPos);
    }
    $cit = $_pdbg['catalog_images_table'] ?? null;
    $pt = $_pdbg['products_table'] ?? null;
    $catalogImageDebugPayload[] = [
        'pro_id' => (int) ($_pdbg['pro_id'] ?? 0),
        'products_table' => is_array($pt) ? $pt : null,
        'sku' => (string) ($_pdbg['sku'] ?? ''),
        'img' => (string) ($_pdbg['img'] ?? ''),
        'uuid_mvc_from_img_path' => $_pdbg['image_uuid'] ?? null,
        'image_resolve_mode' => (string) ($_pdbg['image_resolve_mode'] ?? ''),
        'image_url_length' => $urlRaw !== '' ? strlen($urlRaw) : 0,
        'image_url_path_prefix' => $urlForPrefix !== '' ? substr($urlForPrefix, 0, 96) : '',
        'uuid_api_images_table' => is_array($cit) ? ($cit['uuid'] ?? null) : null,
        'images_table' => is_array($cit) ? $cit : null,
    ];
}
$catalogImageDebugJson = json_encode(
    [
        'meta' => [
            'catalog_cart_token_present' => ! empty($catalog_image_token_present),
            'product_rows' => count($catalogImageDebugPayload),
        ],
        'items' => $catalogImageDebugPayload,
    ],
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
?>
<script>
(function () {
  try {
    var payload = <?= $catalogImageDebugJson ?>;
    console.log('[seller-catalog] UUID + datos tabla images (API) por producto', payload);
    if (payload.items && payload.items.length) {
      payload.items.forEach(function (row, i) {
        console.log('[seller-catalog] producto #' + i + ' pro_id=' + row.pro_id, 'uuid(images)=', row.uuid_api_images_table, 'images_table=', row.images_table, 'product_images_table=', row.product_images_table, '| uuid(parse Img)=', row.uuid_mvc_from_img_path);
      });
    }
  } catch (e) { console.warn('[seller-catalog] image debug parse', e); }
})();
</script>
<?php $this->load->view('seller/partials/portal_foot'); ?>

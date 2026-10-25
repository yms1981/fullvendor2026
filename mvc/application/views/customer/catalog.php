<?php
defined('BASEPATH') or exit('No direct script access allowed');

/** @var array<string, mixed> $pageTexts */
/** @var object|null $settings */
/** @var string $url_menu */
/** @var string $url_orders */
/** @var string $url_catalog */
/** @var string $url_logout */
/** @var string $active_portal_tab */
/** @var string $html_lang */
/** @var bool $catalog_order_available */
/** @var string $catalog_embed_default_url */
/** @var string $portal_company_name */
/** @var string $portal_customer_business */
/** @var string $portal_customer_contact */

$t = is_array($pageTexts ?? null) ? $pageTexts : [];
$e = static function (?string $s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
};
$urlMenu = (string) ($url_menu ?? site_url('customer/catalog'));
$urlOrders = (string) ($url_orders ?? site_url('customer/orders'));
$urlCatalog = (string) ($url_catalog ?? site_url('customer/catalog'));
$urlLogout = (string) ($url_logout ?? site_url('customer/logout'));
$activeTab = isset($active_portal_tab) ? (string) $active_portal_tab : 'catalog';
$isAvailable = ! empty($catalog_order_available);
$defaultUrl = (string) ($catalog_embed_default_url ?? ($catalog_embed_browse_url ?? ''));
$portalCompanyName = trim((string) ($portal_company_name ?? ''));
$portalCustomerBusiness = trim((string) ($portal_customer_business ?? ''));
$portalCustomerContact = trim((string) ($portal_customer_contact ?? ''));
$showPortalCustomer = $portalCustomerBusiness !== '' || $portalCustomerContact !== '';
?>
<!DOCTYPE html>
<html lang="<?= $e($html_lang ?? 'en') ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title><?= $e($t['catalog_page_title'] ?? $t['menu_catalog'] ?? '') ?></title>
  <link rel="icon" type="image/png" sizes="16x16" href="<?= $e(base_url('uploads/logos/' . (@$settings->favicon ?? ''))) ?>">
  <link rel="stylesheet" href="<?= $e(base_url('backend/vendors/css/vendor.bundle.base.css')) ?>">
  <link rel="stylesheet" href="<?= $e(base_url('backend/css/style.css')) ?>">
  <style>
    :root {
      --portal-red: #c91828;
      --portal-red-dark: #9a121f;
      --portal-black: #0a0a0a;
      --portal-white: #ffffff;
    }
    html, body.customer-portal-body {
      height: 100%;
      margin: 0;
      color: #212529;
      background: var(--portal-black);
    }
    .customer-portal-body .portal-app a {
      color: #495057;
    }
    .customer-portal-body .portal-header .portal-actions a {
      color: var(--portal-white);
    }
    .portal-app {
      min-height: 100vh;
      width: 100%;
      display: flex;
      flex-direction: column;
      background: var(--portal-black);
    }
    .portal-chrome {
      flex-shrink: 0;
      width: 100%;
      overflow: hidden;
      border-radius: 0 0 16px 16px;
      border: 2px solid var(--portal-black);
      box-shadow: 0 12px 40px rgba(0, 0, 0, 0.16);
    }
    .portal-header {
      flex-shrink: 0;
      width: 100%;
      background: linear-gradient(180deg, #d41f30 0%, var(--portal-red) 48%, var(--portal-red-dark) 100%) !important;
      border-bottom: 3px solid var(--portal-black);
      box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.18);
      position: relative;
      overflow: hidden;
    }
    .portal-header::before {
      content: "";
      position: absolute;
      left: 0;
      top: 0;
      right: 0;
      height: 4px;
      background: var(--portal-black);
      z-index: 1;
    }
    .customer-portal-body header.portal-header {
      display: block;
      box-sizing: border-box;
    }
    svg.portal-icon {
      width: 1.05rem;
      height: 1.05rem;
      vertical-align: -0.125em;
      flex-shrink: 0;
    }
    .portal-tab a .portal-icon {
      display: inline-block;
      margin-right: 6px;
      vertical-align: middle;
    }
    .portal-btn-logout .portal-icon {
      display: inline-block;
      margin-right: 5px;
      vertical-align: -0.2em;
    }
    .portal-btn-logout .mdi {
      display: inline-block;
      margin-right: 5px;
      vertical-align: -0.2em;
      font-size: 1.05rem;
    }
    .portal-header-inner {
      padding: 18px 20px 0;
      display: flex;
      flex-direction: column;
      align-items: stretch;
      gap: 0;
      position: relative;
      z-index: 2;
    }
    .portal-header-row {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 14px 20px;
      padding-bottom: 16px;
    }
    .portal-header-customer {
      margin-left: -20px;
      margin-right: -20px;
      padding: 14px 20px 16px;
      background: var(--portal-black);
      border-top: 1px solid rgba(255, 255, 255, 0.1);
      font-size: 0.8125rem;
      line-height: 1.5;
      color: var(--portal-white);
      box-shadow: inset 0 1px 0 rgba(201, 24, 40, 0.45);
    }
    .portal-header-customer-label {
      display: block;
      font-size: 0.65rem;
      font-weight: 700;
      letter-spacing: 0.2em;
      text-transform: uppercase;
      color: rgba(255, 255, 255, 0.5);
      margin-bottom: 6px;
    }
    .portal-header-customer-line {
      display: flex;
      flex-wrap: wrap;
      align-items: baseline;
      gap: 8px 12px;
    }
    .portal-header-customer-business {
      font-weight: 700;
      font-size: 1rem;
      letter-spacing: 0.02em;
      color: var(--portal-white);
    }
    .portal-header-customer-contact {
      font-weight: 500;
      font-size: 0.875rem;
      color: rgba(255, 255, 255, 0.88);
    }
    .portal-brand {
      display: flex;
      align-items: center;
      gap: 16px;
      min-width: 0;
    }
    .portal-brand img {
      max-height: 48px;
      max-width: 190px;
      object-fit: contain;
      background: var(--portal-white);
      border-radius: 12px;
      padding: 6px 10px;
      border: 2px solid rgba(255, 255, 255, 0.4);
      box-shadow: 0 4px 18px rgba(0, 0, 0, 0.22);
    }
    .portal-brand-text h1 {
      margin: 0;
      font-size: 1.2rem;
      font-weight: 800;
      color: var(--portal-white);
      text-transform: uppercase;
      letter-spacing: 0.06em;
      text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
    }
    .portal-brand-text p {
      margin: 4px 0 0;
      font-size: 0.72rem;
      font-weight: 600;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: rgba(255, 255, 255, 0.88);
    }
    .portal-actions {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 8px;
      font-size: 0.8125rem;
    }
    .portal-actions .btn.portal-btn-logout {
      margin-left: 0;
      border: 2px solid var(--portal-white);
      color: var(--portal-white);
      background: transparent;
      border-radius: 999px;
      padding: 0.5rem 1.2rem;
      font-weight: 600;
      letter-spacing: 0.04em;
      transition: background 0.2s ease, color 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }
    .portal-actions .btn.portal-btn-logout:hover {
      background: var(--portal-white);
      color: var(--portal-black);
      border-color: var(--portal-white);
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    }
    .portal-tabs-wrap {
      flex-shrink: 0;
      width: 100%;
      background: var(--portal-white);
      border-top: 2px solid var(--portal-black);
      border-bottom: none;
    }
    .portal-tabs {
      display: flex;
      width: 100%;
      margin: 0;
      padding: 0;
      list-style: none;
    }
    .portal-tab {
      flex: 1 1 0;
      min-width: 0;
      text-align: center;
    }
    .portal-tab a {
      display: block;
      padding: 13px 8px;
      margin: 6px 4px 0;
      font-size: 0.875rem;
      font-weight: 700;
      color: var(--portal-black);
      text-decoration: none;
      border-bottom: 3px solid transparent;
      border-radius: 10px 10px 0 0;
    }
    .portal-tab a:hover {
      color: var(--portal-red);
      background: rgba(201, 24, 40, 0.06);
      text-decoration: none;
    }
    .portal-tab.is-active a {
      color: var(--portal-red);
      border-bottom-color: var(--portal-red);
    }
    .portal-tab .mdi {
      display: inline;
      margin-right: 6px;
      vertical-align: middle;
      font-size: 1.05rem;
    }
    .portal-main {
      flex: 1 1 auto;
      width: 100%;
      max-width: none;
      margin: 0;
      padding: 12px 8px 20px;
      box-sizing: border-box;
      min-height: 0;
      display: flex;
      flex-direction: column;
    }
    @media (min-width: 576px) {
      .portal-main { padding-left: 16px; padding-right: 16px; }
    }
    @media (min-width: 1200px) {
      .portal-main { padding-left: 24px; padding-right: 24px; }
    }
    .portal-card.portal-catalog-card {
      flex: 1 1 auto;
      min-height: 0;
      display: flex;
      flex-direction: column;
      width: 100%;
      margin: 0;
      background: #fff;
      border: 1px solid #e2e6ea;
      border-radius: 16px;
      box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
      padding: 0;
      overflow: hidden;
    }
    .catalog-embed-wrap {
      flex: 1 1 auto;
      min-height: 420px;
      position: relative;
      background: #fff;
    }
    .catalog-embed-wrap iframe {
      position: absolute;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      border: 0;
      display: block;
    }
    .portal-main .alert {
      flex-shrink: 0;
      width: 100%;
      margin: 0;
      border-radius: 12px;
      border: 1px solid #d8dce0;
    }
  </style>
</head>
<body class="customer-portal-body">
  <div class="portal-app">
    <div class="portal-chrome">
      <header class="portal-header">
        <div class="portal-header-inner">
          <div class="portal-header-row">
            <div class="portal-brand">
              <?php if (! empty($settings->logo)) { ?>
                <img src="<?= $e(base_url('uploads/logos/' . $settings->logo)) ?>" alt="">
              <?php } else { ?>
                <img src="<?= $e(base_url('images/logos/isologo.png')) ?>" alt="">
              <?php } ?>
              <div class="portal-brand-text">
                <h1><?= $e($portalCompanyName !== '' ? $portalCompanyName : 'HOME VALUE LLC') ?></h1>
                <p><?= $e($t['menu_subtitle'] ?? '') ?></p>
              </div>
            </div>
            <nav class="portal-actions" aria-label="<?= $e($t['menu_title'] ?? '') ?>">
              <a href="<?= $e($urlLogout) ?>" class="btn btn-sm portal-btn-logout" aria-label="<?= $e($t['logout_button'] ?? '') ?>">
                <i class="mdi mdi-logout" aria-hidden="true"></i>
                <span><?= $e($t['logout_button'] ?? '') ?></span>
              </a>
            </nav>
          </div>
          <?php if ($showPortalCustomer) { ?>
            <div class="portal-header-customer" role="region" aria-label="<?= $e($t['header_customer_aria'] ?? '') ?>">
              <span class="portal-header-customer-label"><?= $e($t['header_customer_label'] ?? '') ?></span>
              <div class="portal-header-customer-line">
                <?php if ($portalCustomerBusiness !== '' && $portalCustomerContact !== '') { ?>
                  <span class="portal-header-customer-business"><?= $e($portalCustomerBusiness) ?></span>
                  <span class="portal-header-customer-contact">(<?= $e($portalCustomerContact) ?>)</span>
                <?php } elseif ($portalCustomerBusiness !== '') { ?>
                  <span class="portal-header-customer-business"><?= $e($portalCustomerBusiness) ?></span>
                <?php } elseif ($portalCustomerContact !== '') { ?>
                  <span class="portal-header-customer-business"><?= $e($portalCustomerContact) ?></span>
                <?php } ?>
              </div>
            </div>
          <?php } ?>
        </div>
      </header>

      <nav class="portal-tabs-wrap" aria-label="<?= $e($t['menu_tabs_nav_label'] ?? $t['menu_title'] ?? '') ?>">
        <ul class="portal-tabs">
          <li class="portal-tab <?= $activeTab === 'catalog' ? 'is-active' : '' ?>">
            <a href="<?= $e($urlCatalog) ?>"<?= $activeTab === 'catalog' ? ' aria-current="page"' : '' ?>>
              <svg class="portal-icon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M19 1H9c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-2-2-2zm0 14H9V3h10v12zM5 3H3v16c0 1.1.9 2 2 2h12v-2H5V3z"/></svg><?= $e($t['menu_catalog'] ?? '') ?>
            </a>
          </li>
          <li class="portal-tab <?= $activeTab === 'orders' ? 'is-active' : '' ?>">
            <a href="<?= $e($urlOrders) ?>"<?= $activeTab === 'orders' ? ' aria-current="page"' : '' ?>>
              <svg class="portal-icon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1 9.6 1.84 9.18 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm2 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg><?= $e($t['menu_orders'] ?? '') ?>
            </a>
          </li>
        </ul>
      </nav>
    </div>

    <main class="portal-main" id="main-content">
      <?php if ($isAvailable && $defaultUrl !== '') { ?>
        <div class="portal-card portal-catalog-card">
          <div class="catalog-embed-wrap">
            <iframe id="customer-catalog-frame" title="<?= $e($t['catalog_iframe_title'] ?? '') ?>" src="<?= $e($defaultUrl) ?>"></iframe>
          </div>
        </div>
      <?php } else { ?>
        <div class="alert alert-warning mb-0" role="status"><?= $e($t['catalog_order_unavailable'] ?? $t['catalog_unavailable'] ?? '') ?></div>
      <?php } ?>
    </main>
  </div>
</body>
</html>

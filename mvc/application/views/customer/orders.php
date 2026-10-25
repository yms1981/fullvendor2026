<?php
defined('BASEPATH') or exit('No direct script access allowed');

/** @var array<string, mixed> $pageTexts */
/** @var object|null $settings */
/** @var array<int, object> $orders */
/** @var string $url_menu */
/** @var string $url_orders */
/** @var string $url_catalog */
/** @var string $url_logout */
/** @var string $active_portal_tab */
/** @var int $lang_id */
/** @var string $html_lang */
/** @var string $portal_company_name */
/** @var array<string, mixed>|null $orders_money symbol, digits, radixPoint, groupSeparator */

$t = is_array($pageTexts ?? null) ? $pageTexts : [];
$e = static function (?string $s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
};
$orders = is_array($orders ?? null) ? $orders : [];
$urlMenu = (string) ($url_menu ?? site_url('customer/catalog'));
$urlOrders = (string) ($url_orders ?? site_url('customer/orders'));
$urlCatalog = (string) ($url_catalog ?? site_url('customer/catalog'));
$urlLogout = (string) ($url_logout ?? site_url('customer/logout'));
$activeTab = isset($active_portal_tab) ? (string) $active_portal_tab : 'orders';
$portalCompanyName = trim((string) ($portal_company_name ?? ''));
$portalCustomerBusiness = trim((string) ($portal_customer_business ?? ''));
$portalCustomerContact = trim((string) ($portal_customer_contact ?? ''));
$showPortalCustomer = $portalCustomerBusiness !== '' || $portalCustomerContact !== '';
$ordersMoney = is_array($orders_money ?? null) ? $orders_money : [];
$moneySymbol = (string) ($ordersMoney['symbol'] ?? '$');
$moneyDigits = isset($ordersMoney['digits']) ? (int) $ordersMoney['digits'] : 2;
$moneyRadix = (string) ($ordersMoney['radixPoint'] ?? '.');
$moneyGroup = (string) ($ordersMoney['groupSeparator'] ?? ',');
?>
<!DOCTYPE html>
<html lang="<?= $e($html_lang ?? 'en') ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title><?= $e($t['orders_page_title'] ?? '') ?></title>
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
      color: #212529;
      text-decoration: none;
      border-bottom: 3px solid transparent;
      border-radius: 10px 10px 0 0;
    }
    .portal-tab a:hover {
      color: #dc3545;
      background: #fff5f5;
      text-decoration: none;
    }
    .portal-tab.is-active a {
      color: #dc3545;
      border-bottom-color: #dc3545;
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
    }
    @media (min-width: 576px) {
      .portal-main { padding-left: 16px; padding-right: 16px; }
    }
    @media (min-width: 1200px) {
      .portal-main { padding-left: 24px; padding-right: 24px; }
    }
    .portal-card {
      width: 100%;
      margin: 0;
      background: #fff;
      border: 1px solid #e2e6ea;
      border-radius: 16px;
      box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
      padding: 0;
      overflow: hidden;
    }
    .portal-orders-toolbar {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 12px 16px;
      padding: 14px 16px;
      border-bottom: 1px solid #eceff2;
      background: #f8f9fa;
    }
    .portal-orders-toolbar h4 {
      margin: 0;
      font-size: 1.1rem;
      font-weight: 600;
      color: #212529;
    }
    .portal-orders-sub {
      margin: 6px 0 0;
      font-size: 0.875rem;
      color: #6c757d;
      line-height: 1.4;
      max-width: 48rem;
    }
    .portal-orders-toolbar .btn {
      border-color: #ced4da;
      color: #495057;
    }
    .portal-table-shell {
      width: 100%;
      border-top: none;
      border-radius: 0 0 10px 10px;
      overflow: hidden;
    }
    .portal-table-responsive {
      width: 100%;
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }
    table.portal-orders-table {
      width: 100%;
      min-width: 100%;
      border-collapse: collapse;
      margin: 0;
      font-size: 0.9375rem;
    }
    table.portal-orders-table thead th {
      text-align: right;
      padding: 11px 14px;
      font-weight: 600;
      font-size: 0.8125rem;
      color: #343a40;
      background: #f1f3f5;
      border: 1px solid #e8eaed;
      border-bottom-width: 1px;
      border-bottom-color: #dee2e6;
      white-space: nowrap;
    }
    table.portal-orders-table tbody td {
      text-align: right;
      padding: 12px 14px;
      border: 1px solid #eef0f3;
      vertical-align: middle;
      color: #212529;
    }
    table.portal-orders-table tbody tr:nth-child(even) {
      background: #fafafa;
    }
    table.portal-orders-table tbody tr:hover {
      background: #f1f3f5;
    }
    table.portal-orders-table tbody td.col-seller {
      text-transform: uppercase;
      letter-spacing: 0.02em;
    }
    .portal-order-num-cell {
      display: flex;
      align-items: center;
      gap: 0.45rem;
    }
    .portal-order-source-icon {
      width: 1.125rem;
      height: 1.125rem;
      flex-shrink: 0;
    }
    .portal-order-source-icon--app { color: #0d6efd; }
    .portal-order-source-icon--catalog { color: #0aa2c0; width: 1.28rem; height: 1.28rem; }
    .portal-order-source-icon--api { color: #6f42c1; }
    .portal-order-source-icon--manual { color: #495057; }
    .portal-order-source-icon--marketplace { color: #198754; }
    .portal-order-source-icon--unknown { color: #868e96; }
    .portal-order-source-icon--empty { color: #adb5bd; opacity: 0.45; }
    table.portal-orders-table thead th.col-align-left,
    table.portal-orders-table tbody td.col-align-left {
      text-align: left;
    }
    table.portal-orders-table thead th.col-status,
    table.portal-orders-table tbody td.col-status {
      text-align: center;
      padding: 12px 14px;
      vertical-align: middle;
      min-width: 13.5rem;
    }
    table.portal-orders-table thead th.col-status {
      padding: 11px 14px;
    }
    table.portal-orders-table .portal-order-status .portal-order-status-dot {
      flex-shrink: 0;
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background-color: #adb5bd;
      box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.08);
    }
    table.portal-orders-table .portal-order-status.portal-order-status--new .portal-order-status-dot { background-color: #0d6efd; }
    table.portal-orders-table .portal-order-status.portal-order-status--pending .portal-order-status-dot { background-color: #e0a800; }
    table.portal-orders-table .portal-order-status.portal-order-status--progress .portal-order-status-dot { background-color: #0a58ca; }
    table.portal-orders-table .portal-order-statusi.portal-order-status--done .portal-order-status-dot { background-color: #198754; }
    table.portal-orders-table .portal-order-status.portal-order-status--bad .portal-order-status-dot { background-color: #dc3545; }
    table.portal-orders-table .portal-order-status.portal-order-status--return .portal-order-status-dot { background-color: #6c757d; }
    table.portal-orders-table .portal-order-status.portal-order-status--muted .portal-order-status-dot { background-color: #868e96; }
    table.portal-orders-table .portal-order-status.portal-order-status--neutral .portal-order-status-dot { background-color: #adb5bd; }
    table.portal-orders-table .portal-order-status-label {
      flex: 0 1 auto;
      max-width: calc(12rem - 2.25rem);
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      text-align: center;
    }
    table.portal-orders-table thead th.col-total,
    table.portal-orders-table tbody td.col-total {
      text-align: right;
      font-variant-numeric: tabular-nums;
      white-space: nowrap;
    }
    .portal-orders-empty {
      margin: 0;
      padding: 36px 20px;
      text-align: center;
      border-top: 1px solid #eceff2;
      color: #6c757d;
      font-size: 0.9375rem;
    }
    .portal-order-status {
      display: inline-block;
      padding: 6px 12px;
      border-radius: 999px;
      font-size: 0.8125rem;
      font-weight: 700;
      line-height: 1.35;
      border: 1px solid transparent;
      max-width: 100%;
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.06);
    }
    table.portal-orders-table .portal-order-status {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.45rem;
      box-sizing: border-box;
      width: 12rem;
      min-width: 12rem;
      max-width: 12rem;
      min-height: 1.9rem;
      padding: 5px 10px;
      font-size: 0.72rem;
      font-weight: 600;
      line-height: 1.25;
      border-radius: 999px;
      box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
      vertical-align: middle;
    }
    /* Fondo casi blanco en la tabla; el matiz lo marca el borde suave y el punto */
    table.portal-orders-table .portal-order-status.portal-order-status--new {
      background: #f7f9ff;
      color: #052c65;
      border-color: #cfe2ff;
    }
    table.portal-orders-table .portal-order-status.portal-order-status--pending {
      background: #fffdf7;
      color: #523200;
      border-color: #ffe69c;
    }
    table.portal-orders-table .portal-order-status.portal-order-status--progress {
      background: #f7f9ff;
      color: #031633;
      border-color: #b6d4fe;
    }
    table.portal-orders-table .portal-order-status.portal-order-status--done {
      background: #f6fcf8;
      color: #032b1a;
      border-color: #a3cfbb;
    }
    table.portal-orders-table .portal-order-status.portal-order-status--bad {
      background: #fff8f8;
      color: #420f14;
      border-color: #f1aeb5;
    }
    table.portal-orders-table .portal-order-status.portal-order-status--return {
      background: #f8f9fa;
      color: #1a1e21;
      border-color: #dee2e6;
    }
    table.portal-orders-table .portal-order-status.portal-order-status--muted {
      background: #f8f9fa;
      color: #343a40;
      border-color: #dee2e6;
    }
    table.portal-orders-table .portal-order-status.portal-order-status--neutral {
      background: #f8f9fa;
      color: #343a40;
      border-color: #e9ecef;
    }
    .portal-order-status--new { background: #b6d4fe; color: #052c65; border-color: #0d6efd; }
    .portal-order-status--pending { background: #ffe083; color: #523200; border-color: #e0a800; }
    .portal-order-status--progress { background: #9ec5fe; color: #031633; border-color: #0a58ca; }
    .portal-order-status--done { background: #8ccdaa; color: #032b1a; border-color: #198754; }
    .portal-order-status--bad { background: #f1aeb5; color: #420f14; border-color: #dc3545; }
    .portal-order-status--return { background: #ced4da; color: #1a1e21; border-color: #6c757d; }
    .portal-order-status--muted { background: #ced4da; color: #343a40; border-color: #868e96; }
    .portal-order-status--neutral { background: #dee2e6; color: #343a40; border-color: #adb5bd; }
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

    <main class="portal-main">
      <div class="portal-card">
        <div class="portal-orders-toolbar">
          <div>
            <h4><?= $e($t['orders_page_title'] ?? '') ?></h4>
            <?php
            $ordersSub = trim((string) ($t['orders_page_subtitle'] ?? ''));
            if ($ordersSub !== '') { ?>
              <p class="portal-orders-sub"><?= $e($ordersSub) ?></p>
            <?php } ?>
          </div>
          <div class="d-flex align-items-center flex-shrink-0">
            <a href="<?= $e($urlMenu) ?>" class="btn btn-outline-secondary btn-sm"><?= $e($t['back_to_menu'] ?? '') ?></a>
          </div>
        </div>

        <?php if (count($orders) === 0) { ?>
          <p class="portal-orders-empty mb-0" role="status"><?= $e($t['empty_orders'] ?? '') ?></p>
        <?php } else { ?>
          <div class="portal-table-shell">
            <div class="portal-table-responsive">
              <table class="portal-orders-table">
                <thead>
                  <tr>
                    <th scope="col" class="col-align-left" id="portal-col-order-date"><?= $e($t['column_date'] ?? '') ?></th>
                    <th scope="col" class="col-align-left"><?= $e($t['column_order_number'] ?? '') ?></th>
                    <th scope="col" class="col-align-left"><?= $e($t['column_seller'] ?? '') ?></th>
                    <th scope="col" class="col-status"><?= $e($t['column_status'] ?? '') ?></th>
                    <th scope="col" class="col-total"><?= $e($t['column_total'] ?? '') ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($orders as $row) { ?>
                    <?php
                    $badgeClass = isset($row->status_badge_class) && (string) $row->status_badge_class !== ''
                      ? (string) $row->status_badge_class
                      : 'portal-order-status--neutral';
                    $amt = isset($row->total_amount) ? (float) $row->total_amount : 0.0;
                    $totalFmt = number_format($amt, max(0, $moneyDigits), $moneyRadix, $moneyGroup);
                    $sellerName = (isset($row->seller_name) && (string) $row->seller_name !== '') ? (string) $row->seller_name : '';
                    $srcRaw = trim((string) ($row->order_source ?? ''));
                    $norm = $srcRaw !== '' ? strtoupper($srcRaw) : '';
                    if ($srcRaw === '') {
                      $originKind = 'empty';
                      $originTooltip = '';
                    } elseif ($norm === 'APP') {
                      $originKind = 'app';
                      $originTooltip = (string) ($t['order_origin_app'] ?? '');
                    } elseif ($norm === 'CATALOG') {
                      $originKind = 'catalog';
                      $originTooltip = (string) ($t['order_origin_catalog'] ?? '');
                    } elseif ($norm === 'API') {
                      $originKind = 'api';
                      $originTooltip = (string) ($t['order_origin_api'] ?? '');
                    } elseif ($norm === 'MANUAL') {
                      $originKind = 'manual';
                      $originTooltip = (string) ($t['order_origin_manual'] ?? '');
                    } elseif ($norm === 'MARKETPLACE') {
                      $originKind = 'marketplace';
                      $originTooltip = (string) ($t['order_origin_marketplace'] ?? $srcRaw);
                    } else {
                      $originKind = 'unknown';
                      $originTooltip = $srcRaw;
                    }
                    if ($originTooltip === '' && $originKind !== 'empty') {
                      $originTooltip = $srcRaw !== '' ? $srcRaw : '';
                    }
                    ?>
                    <tr>
                      <td class="col-align-left" headers="portal-col-order-date"><?= $e(isset($row->created_date) ? (string) $row->created_date : '') ?></td>
                      <td class="col-align-left">
                        <span class="portal-order-num-cell">
                          <?php
                          $iconClass = 'portal-order-source-icon portal-order-source-icon--' . $originKind;
                          if ($originKind === 'app') { ?>
                            <svg class="<?= $e($iconClass) ?>" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"<?= $originTooltip !== '' ? ' title="' . $e($originTooltip) . '"' : '' ?> aria-hidden="true"><path fill="currentColor" d="M17 1H7c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-2-2zm0 18H7V5h10v14z"/></svg>
                          <?php } elseif ($originKind === 'catalog') { ?>
                            <svg class="<?= $e($iconClass) ?>" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" title="<?= $e($originTooltip) ?>" aria-hidden="true" focusable="false"><path fill="currentColor" d="M20 4H4v2h16V4zm1 10v-2l-1-5H4l-1 5v2h1v6h10v-6h4v6h2v-6h1zm-9 4H6v-4h6v4z"/></svg>
                          <?php } elseif ($originKind === 'api') { ?>
                            <svg class="<?= $e($iconClass) ?>" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" title="<?= $e($originTooltip) ?>" aria-hidden="true"><path fill="currentColor" d="M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6 1.4-1.4zm5.2 0l4.6-4.6-4.6-4.6L16 6l6 6-6 6-1.4-1.4z"/></svg>
                          <?php } elseif ($originKind === 'manual') { ?>
                            <svg class="<?= $e($iconClass) ?>" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" title="<?= $e($originTooltip) ?>" aria-hidden="true"><path fill="currentColor" d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a.996.996 0 000-1.41l-2.34-2.34a.996.996 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                          <?php } elseif ($originKind === 'marketplace') { ?>
                            <svg class="<?= $e($iconClass) ?>" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" title="<?= $e($originTooltip) ?>" aria-hidden="true"><path fill="currentColor" d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.29-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.04-.12L8.1 13h7.45c.75 0 1.41-.41 1.75-1.03L21.7 4H5.21l-.94-2H1zm16 16c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>
                          <?php } elseif ($originKind === 'empty') { ?>
                            <svg class="<?= $e($iconClass) ?>" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path fill="currentColor" d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                          <?php } else { ?>
                            <svg class="<?= $e($iconClass) ?>" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" title="<?= $e($originTooltip) ?>" aria-hidden="true"><path fill="currentColor" d="M14 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                          <?php } ?>
                          <span><?= $e($row->order_number ?? (string) ($row->order_id ?? '')) ?></span>
                        </span>
                      </td>
                      <td class="col-seller col-align-left"><?= $sellerName !== '' ? $e($sellerName) : '—' ?></td>
                      <td class="col-status">
                        <span class="portal-order-status <?= $e($badgeClass) ?>">
                          <span class="portal-order-status-dot" aria-hidden="true"></span>
                          <span class="portal-order-status-label"><?= $e($row->status_label ?? '') ?></span>
                        </span>
                      </td>
                      <td class="col-total"><?= $e($moneySymbol) ?> <?= $e($totalFmt) ?></td>
                    </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php } ?>
      </div>
    </main>
  </div>
</body>
</html>

<?php
defined('BASEPATH') or exit('No direct script access allowed');

/** @var array<string, mixed> $pageTexts */
/** @var object|null $settings */
/** @var string $url_home */
/** @var string $url_orders */
/** @var string $url_catalog */
/** @var string $url_logout */
/** @var string $portal_company_name */
/** @var int $lang_id */
/** @var string $html_lang */
/** @var string $active_portal_tab */
/** @var array<string, mixed>|null $orders_money */
/** @var bool $order_detail_error */
/** @var object|null $order_detail */
/** @var list<object> $order_lines */

$t = is_array($pageTexts ?? null) ? $pageTexts : [];
$settings = isset($settings) && is_object($settings) ? $settings : null;
$e = static function (?string $s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
};
$urlOrders = (string) ($url_orders ?? site_url('sellers/orders'));
$ordersMoney = is_array($orders_money ?? null) ? $orders_money : [];
$moneySymbol = (string) ($ordersMoney['symbol'] ?? '$');
$moneyDigits = isset($ordersMoney['digits']) ? (int) $ordersMoney['digits'] : 2;
$moneyRadix = (string) ($ordersMoney['radixPoint'] ?? '.');
$moneyGroup = (string) ($ordersMoney['groupSeparator'] ?? ',');
$orderDetailError = ! empty($order_detail_error);
$od = isset($order_detail) && is_object($order_detail) ? $order_detail : null;
$lines = is_array($order_lines ?? null) ? $order_lines : [];
$portalPageTitle = trim((string) ($portal_page_title ?? ''));
if ($portalPageTitle === '') {
    $portalPageTitle = (string) ($t['order_detail_title'] ?? $t['orders_page_title'] ?? $t['menu_orders'] ?? '');
}
$this->load->view('seller/partials/portal_head', [
    'pageTexts' => $pageTexts ?? [],
    'settings' => $settings ?? null,
    'url_home' => $url_home ?? site_url('sellers/customer'),
    'url_orders' => $url_orders ?? site_url('sellers/orders'),
    'url_catalog' => $url_catalog ?? site_url('sellers/catalog'),
    'url_logout' => $url_logout ?? site_url('sellers/logout'),
    'portal_company_name' => $portal_company_name ?? '',
    'html_lang' => $html_lang ?? 'en',
    'active_portal_tab' => $active_portal_tab ?? 'orders',
    'portal_page_title' => $portalPageTitle,
]);
?>
<style>
    .portal-detail-toolbar .btn-outline-secondary {
      color: var(--seller-ink) !important;
      border-color: var(--seller-btn-solid) !important;
      background: #fafafa;
    }
    .portal-detail-toolbar .btn-outline-secondary:hover {
      background: #f5f5f5 !important;
      border-color: var(--seller-btn-solid-hover) !important;
      color: var(--seller-ink) !important;
    }
    .portal-detail-toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 20px; }
    .portal-detail-toolbar h2 { margin: 0; font-size: 1.25rem; font-weight: 600; color: var(--seller-ink); }
    /* Cabecera detalle pedido: dos columnas como referencia de diseño */
    .seller-order-detail-header {
      display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between;
      gap: 28px 32px; margin-bottom: 20px; padding: 20px 22px;
      background: #ffffff; border-radius: 10px; border: 1px solid #e5e7eb;
      font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }
    .seller-order-detail-header__left { flex: 1 1 280px; min-width: 0; }
    .seller-order-detail-header__right {
      flex: 0 0 auto; text-align: right; min-width: 200px;
    }
    .seller-order-detail-header__row {
      display: flex; align-items: flex-start; gap: 10px; margin-bottom: 10px;
      font-size: 0.9375rem; line-height: 1.45; color: #212529;
    }
    .seller-order-detail-header__row:last-child { margin-bottom: 0; }
    .seller-order-detail-header__icon {
      flex-shrink: 0; width: 1.125rem; height: 1.125rem; margin-top: 3px; color: #868e96;
    }
    .seller-order-detail-header__text { flex: 1; min-width: 0; word-break: break-word; }
    .seller-order-detail-header__text--upper { text-transform: uppercase; letter-spacing: 0.02em; }
    .seller-order-detail-header__meta {
      margin-top: 14px; padding-top: 12px; border-top: 1px solid #f1f3f5;
    }
    .seller-order-detail-header__meta-line {
      display: flex; flex-wrap: wrap; align-items: center; gap: 8px 10px; margin-bottom: 8px;
      font-size: 0.9375rem; color: #212529;
    }
    .seller-order-detail-header__meta-line:last-child { margin-bottom: 0; }
    .seller-order-detail-header__meta-k { font-weight: 700; }
    .seller-order-detail-header__seller-badge {
      display: inline-block; padding: 5px 14px; border-radius: 6px;
      background: #6c757d; color: #ffffff; font-weight: 600; font-size: 0.8125rem;
      letter-spacing: 0.03em; text-transform: uppercase;
    }
    .seller-order-detail-header__seller-placeholder { color: #868e96; font-weight: 500; }
    .seller-order-detail-header__right-block { margin-bottom: 12px; }
    .seller-order-detail-header__right-block:last-child { margin-bottom: 0; }
    .seller-order-detail-header__right-k {
      font-weight: 700; color: #212529; font-size: 0.9375rem;
    }
    .seller-order-detail-header__right-v {
      font-size: 0.9375rem; color: #212529; margin-top: 2px;
    }
    .seller-order-detail-header__status {
      display: inline-flex; align-items: center; gap: 8px; margin-top: 6px;
      font-size: 0.9375rem; color: #212529;
    }
    .seller-order-detail-header__status-dot {
      width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0;
      background: #93c5fd;
    }
    .portal-lines-heading { font-size: 1rem; font-weight: 600; color: #0f172a; margin: 0 0 12px; }
    .portal-table-shell { border-radius: 10px; overflow: hidden; border: 1px solid #e2e8f0; }
    .portal-table-responsive { overflow-x: auto; -webkit-overflow-scrolling: touch; }
    table.portal-lines-table {
      width: 100%; border-collapse: collapse; font-size: 0.9375rem; background: #fff;
    }
    table.portal-lines-table th, table.portal-lines-table td {
      border-bottom: 1px solid #e2e8f0; padding: 12px 14px; text-align: left; vertical-align: middle; color: #1e293b;
    }
    table.portal-lines-table thead th { background: #f8fafc; font-weight: 600; color: #0f172a; }
    table.portal-lines-table .col-num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
    table.portal-lines-table.seller-order-lines thead th {
      background: linear-gradient(135deg, #b92333 0%, #d22e3e 100%);
      color: #fff;
      font-weight: 700;
      border-bottom: 1px solid rgba(255, 255, 255, 0.15);
      text-align: center;
      white-space: nowrap;
    }
    table.portal-lines-table.seller-order-lines thead th.col-product-name,
    table.portal-lines-table.seller-order-lines thead th.col-sku-h { text-align: left; }
    table.portal-lines-table.seller-order-lines thead th.col-money-h { text-align: right; }
    table.portal-lines-table.seller-order-lines tbody td.col-idx { text-align: center; font-weight: 700; width: 2.5rem; }
    table.portal-lines-table.seller-order-lines tbody td.col-thumb {
      text-align: center; width: 64px;
    }
    table.portal-lines-table.seller-order-lines .seller-order-line-thumb {
      width: 52px; height: 52px; object-fit: cover; border-radius: 6px; border: 1px solid #e2e8f0; vertical-align: middle;
      background: #f8fafc;
    }
    table.portal-lines-table.seller-order-lines span.seller-order-line-thumb {
      display: inline-block; box-sizing: border-box;
    }
    table.portal-lines-table.seller-order-lines tbody td.col-product-name {
      font-weight: 500; text-transform: uppercase; letter-spacing: 0.02em;
    }
    table.portal-lines-table.seller-order-lines tbody td.col-sku .seller-order-line-sku {
      color: #2563eb; text-decoration: underline; font-weight: 500; cursor: default;
    }
    table.portal-lines-table.seller-order-lines tbody td.col-qty-stock,
    table.portal-lines-table.seller-order-lines tbody td.col-money {
      font-weight: 700; font-variant-numeric: tabular-nums;
    }
    table.portal-lines-table.seller-order-lines tbody td.col-qty-stock { text-align: right; }
    table.portal-lines-table.seller-order-lines tbody td.col-money { text-align: right; }
    table.portal-lines-table.seller-order-lines tbody tr.seller-order-line-comments td {
      border-bottom: 1px solid #e5e7eb; padding-top: 6px; padding-bottom: 10px;
      background: #ffffff; font-size: 0.8125rem; color: #475569; text-align: right;
    }
    table.portal-lines-table.seller-order-lines tfoot tr.seller-order-lines-totals:first-child td {
      border-top: 1px solid #e5e7eb;
    }
    table.portal-lines-table.seller-order-lines tfoot tr.seller-order-lines-totals td {
      border-bottom: 1px solid #e5e7eb; padding: 12px 14px;
      font-weight: 700; font-size: 0.9375rem; color: #1e293b; background: #ffffff;
      font-variant-numeric: tabular-nums;
    }
    table.portal-lines-table.seller-order-lines tfoot tr.seller-order-lines-totals td.seller-order-lines-totals__label {
      text-align: left; border-right: none;
    }
    table.portal-lines-table.seller-order-lines tfoot tr.seller-order-lines-totals td.seller-order-lines-totals__val {
      text-align: right; white-space: nowrap;
    }
    table.portal-lines-table.seller-order-lines .seller-order-line-sku {
      text-transform: uppercase;
    }
    table.portal-lines-table.seller-order-lines tbody tr.seller-order-line-comments .seller-order-comments-label {
      font-weight: 600; margin-right: 6px;
    }
    .portal-detail-alert { padding: 16px; border-radius: 10px; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; font-size: 0.9375rem; }
  </style>
        <section class="portal-welcome-card" aria-labelledby="order-detail-heading">
          <div class="portal-detail-toolbar">
            <h2 id="order-detail-heading"><?= $e($t['order_detail_title'] ?? '') ?></h2>
            <div>
              <a href="<?= $e($urlOrders) ?>" class="btn btn-outline-secondary btn-sm"><?= $e($t['order_detail_back_list'] ?? '') ?></a>
            </div>
          </div>

          <?php if ($orderDetailError || $od === null) { ?>
            <p class="portal-detail-alert mb-0" role="alert"><?= $e($t['order_detail_not_found'] ?? '') ?></p>
          <?php } else { ?>
            <?php
            $sumAmt = isset($od->total_amount) ? (float) $od->total_amount : 0.0;
            $subAmt = isset($od->subtotal_amount) ? (float) $od->subtotal_amount : $sumAmt;
            $discAmt = isset($od->discount_amount) ? (float) $od->discount_amount : 0.0;
            $discPctLbl = isset($od->discount_percent_label) ? (float) $od->discount_percent_label : 0.0;
            $sumFmt = number_format($sumAmt, max(0, $moneyDigits), $moneyRadix, $moneyGroup);
            $subFmt = number_format($subAmt, max(0, $moneyDigits), $moneyRadix, $moneyGroup);
            $discFmt = number_format($discAmt, max(0, $moneyDigits), $moneyRadix, $moneyGroup);
            $discPctFmt = number_format($discPctLbl, 2, $moneyRadix, $moneyGroup);
            $discLabelFull = sprintf((string) ($t['order_detail_totals_discount'] ?? 'Discount (%s%%)'), $discPctFmt);
            $dotHex = isset($od->status_dot_color) ? trim((string) $od->status_dot_color) : '';
            $dotStyle = $dotHex !== '' ? 'background-color: ' . $e($dotHex) . ' !important' : '';
            $createdRaw = trim((string) ($od->created_date ?? ''));
            $createdDisplay = $createdRaw;
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $createdRaw, $dm)) {
                $createdDisplay = $dm[3] . '/' . $dm[2] . '/' . $dm[1];
            }
            $biz = trim((string) ($od->customer_business ?? ''));
            $contact = trim((string) ($od->customer_contact ?? ''));
            $email = trim((string) ($od->customer_email ?? ''));
            $phone1 = trim((string) ($od->customer_phone ?? ''));
            $phone2 = trim((string) ($od->customer_cell ?? ''));
            $addr = trim((string) ($od->customer_address ?? ''));
            $sellerLbl = trim((string) ($od->seller_label ?? ''));
            $emptyCell = (string) ($t['order_detail_comments_empty'] ?? '—');
            $originRaw = strtolower(trim((string) ($od->order_source ?? '')));
            $originMap = [
                'catalog' => (string) ($t['orders_origin_catalog'] ?? 'Catalog'),
                'app' => (string) ($t['orders_origin_app'] ?? 'App'),
                'manual' => (string) ($t['orders_origin_manual'] ?? 'Manual'),
                'marketplace' => (string) ($t['orders_origin_marketplace'] ?? 'Marketplace'),
                'web' => (string) ($t['orders_origin_web'] ?? 'Web'),
            ];
            $originDisplay = $originMap[$originRaw] ?? '';
            if ($originDisplay === '' && $originRaw !== '') {
                $originDisplay = ucfirst($originRaw);
            }
            if ($originDisplay === '') {
                $originDisplay = $emptyCell;
            }
            if ($originDisplay === $emptyCell && ! empty($od->portal_vendor)) {
                $originDisplay = (string) ($t['order_detail_origin_portal'] ?? '');
                if ($originDisplay === '') {
                    $originDisplay = $emptyCell;
                }
            }
            $up = static function (string $s): string {
                return function_exists('mb_strtoupper') ? mb_strtoupper($s, 'UTF-8') : strtoupper($s);
            };
            ?>
            <div class="seller-order-detail-header" aria-label="<?= $e($t['order_detail_header_aria'] ?? '') ?>">
              <div class="seller-order-detail-header__left">
                <div class="seller-order-detail-header__row">
                  <svg class="seller-order-detail-header__icon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm10 12h-8v-2h2v-4h-2v-2h4v6h2v2zm3-12h-2v2h2V5zm0 4h-2v2h2V9zm0 4h-2v4h2v-4z"/></svg>
                  <span class="seller-order-detail-header__text seller-order-detail-header__text--upper"><?= $biz !== '' ? $e($up($biz)) : $e($emptyCell) ?></span>
                </div>
                <div class="seller-order-detail-header__row">
                  <svg class="seller-order-detail-header__icon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                  <span class="seller-order-detail-header__text seller-order-detail-header__text--upper"><?= $contact !== '' ? $e($up($contact)) : $e($emptyCell) ?></span>
                </div>
                <div class="seller-order-detail-header__row">
                  <svg class="seller-order-detail-header__icon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                  <span class="seller-order-detail-header__text"><?= $email !== '' ? $e($email) : $e($emptyCell) ?></span>
                </div>
                <div class="seller-order-detail-header__row">
                  <svg class="seller-order-detail-header__icon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
                  <span class="seller-order-detail-header__text"><?= $phone1 !== '' ? $e($phone1) : $e($emptyCell) ?></span>
                </div>
                <div class="seller-order-detail-header__row">
                  <svg class="seller-order-detail-header__icon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M17 1.01L7 1c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-1.99-2-1.99zM17 19H7V5h10v14z"/></svg>
                  <span class="seller-order-detail-header__text"><?= $phone2 !== '' ? $e($phone2) : $e($emptyCell) ?></span>
                </div>
                <div class="seller-order-detail-header__row">
                  <svg class="seller-order-detail-header__icon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                  <span class="seller-order-detail-header__text"><?= $addr !== '' ? $e($up($addr)) : $e($emptyCell) ?></span>
                </div>
                <div class="seller-order-detail-header__meta">
                  <div class="seller-order-detail-header__meta-line">
                    <span class="seller-order-detail-header__meta-k"><?= $e($t['order_detail_header_seller'] ?? '') ?></span>
                    <?php if ($sellerLbl !== '') { ?>
                      <span class="seller-order-detail-header__seller-badge"><?= $e($up($sellerLbl)) ?></span>
                    <?php } else { ?>
                      <span class="seller-order-detail-header__seller-placeholder"><?= $e($emptyCell) ?></span>
                    <?php } ?>
                  </div>
                  <div class="seller-order-detail-header__meta-line">
                    <span class="seller-order-detail-header__meta-k"><?= $e($t['order_detail_header_origin'] ?? '') ?></span>
                    <span><?= $e($originDisplay) ?></span>
                  </div>
                </div>
              </div>
              <div class="seller-order-detail-header__right">
                <div class="seller-order-detail-header__right-block">
                  <div class="seller-order-detail-header__right-k"><?= $e($t['order_detail_header_order_date'] ?? '') ?></div>
                  <div class="seller-order-detail-header__right-v"><?= $e($createdDisplay) ?></div>
                </div>
                <div class="seller-order-detail-header__right-block">
                  <div class="seller-order-detail-header__right-k"><?= $e($t['order_detail_header_order_number'] ?? '') ?></div>
                  <div class="seller-order-detail-header__right-v"><?= $e((string) ($od->order_number ?? $od->order_id ?? '')) ?></div>
                </div>
                <div class="seller-order-detail-header__right-block">
                  <div class="seller-order-detail-header__status">
                    <span class="seller-order-detail-header__status-dot" aria-hidden="true"<?= $dotStyle !== '' ? ' style="' . $dotStyle . '"' : '' ?>></span>
                    <span><?= $e(trim((string) ($od->status_label ?? ''))) !== '' ? $e(trim((string) ($od->status_label ?? ''))) : $e($emptyCell) ?></span>
                  </div>
                </div>
              </div>
            </div>

            <h3 class="portal-lines-heading"><?= $e($t['order_detail_products_heading'] ?? '') ?></h3>
            <?php if (count($lines) === 0) { ?>
              <p class="mb-0" style="color:#64748b;font-size:0.9375rem;"><?= $e($t['order_detail_no_lines'] ?? '') ?></p>
            <?php } else { ?>
              <div class="portal-table-shell">
                <div class="portal-table-responsive">
                  <table class="portal-lines-table seller-order-lines">
                    <thead>
                      <tr>
                        <th scope="col" class="col-idx-h"><?= $e($t['order_detail_col_index'] ?? '#') ?></th>
                        <th scope="col" class="col-image-h"><?= $e($t['order_detail_col_image'] ?? '') ?></th>
                        <th scope="col" class="col-product-name"><?= $e($t['column_line_product'] ?? '') ?></th>
                        <th scope="col" class="col-sku-h"><?= $e($t['column_sku'] ?? '') ?></th>
                        <th scope="col" class="col-qty-h"><?= $e($t['order_detail_col_quantity'] ?? $t['column_qty'] ?? '') ?></th>
                        <th scope="col" class="col-stock-h"><?= $e($t['order_detail_col_stock'] ?? '') ?></th>
                        <th scope="col" class="col-money-h"><?= $e($t['order_detail_col_sale_price'] ?? '') ?></th>
                        <th scope="col" class="col-money-h"><?= $e($t['order_detail_col_total_order'] ?? '') ?></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      $lineIdx = 0;
                      foreach ($lines as $line) {
                          $lineIdx++;
                          $q = isset($line->qty) ? (float) $line->qty : 0.0;
                          $up = isset($line->unit_price) ? (float) $line->unit_price : 0.0;
                          $lt = isset($line->line_total) ? (float) $line->line_total : 0.0;
                          $qFmt = number_format($q, 2, $moneyRadix, $moneyGroup);
                          $upFmt = number_format($up, max(0, $moneyDigits), $moneyRadix, $moneyGroup);
                          $ltFmt = number_format($lt, max(0, $moneyDigits), $moneyRadix, $moneyGroup);
                          $pname = trim((string) ($line->product_name ?? ''));
                          $sku = trim((string) ($line->sku ?? ''));
                          $imgUrl = trim((string) ($line->image_url ?? ''));
                          $stockRaw = isset($line->stock) ? $line->stock : null;
                          $stock = ($stockRaw !== null && $stockRaw !== '') ? (float) $stockRaw : null;
                          if ($stock === null) {
                              $stockFmt = '—';
                          } elseif (abs($stock - round($stock)) < 1e-9) {
                              $stockFmt = number_format(round($stock), 0, $moneyRadix, $moneyGroup);
                          } else {
                              $stockFmt = number_format($stock, 2, $moneyRadix, $moneyGroup);
                          }
                          $comments = trim((string) ($line->line_comments ?? ''));
                          $commentsEmpty = (string) ($t['order_detail_comments_empty'] ?? '—');
                          ?>
                        <tr class="seller-order-line-main">
                          <td class="col-idx"><?= (int) $lineIdx ?></td>
                          <td class="col-thumb">
                            <?php if ($imgUrl !== '') { ?>
                              <img class="seller-order-line-thumb" src="<?= $e($imgUrl) ?>" alt="" width="52" height="52" loading="lazy">
                            <?php } else { ?>
                              <span class="seller-order-line-thumb" role="img" aria-hidden="true"></span>
                            <?php } ?>
                          </td>
                          <td class="col-product-name"><?= $pname !== '' ? $e(mb_strtoupper($pname, 'UTF-8')) : '—' ?></td>
                          <td class="col-sku"><?= $sku !== '' ? '<span class="seller-order-line-sku">' . $e($sku) . '</span>' : '—' ?></td>
                          <td class="col-qty-stock"><?= $e($qFmt) ?></td>
                          <td class="col-qty-stock"><?= $stock === null ? $e($commentsEmpty) : $e($stockFmt) ?></td>
                          <td class="col-money"><?= $e($moneySymbol) ?> <?= $e($upFmt) ?></td>
                          <td class="col-money"><?= $e($moneySymbol) ?> <?= $e($ltFmt) ?></td>
                        </tr>
                        <tr class="seller-order-line-comments">
                          <td colspan="8">
                            <span class="seller-order-comments-label"><?= $e($t['order_detail_comments_label'] ?? '') ?>:</span>
                            <?= $comments !== '' ? $e($comments) : $e($commentsEmpty) ?>
                          </td>
                        </tr>
                      <?php } ?>
                    </tbody>
                    <tfoot>
                      <tr class="seller-order-lines-totals">
                        <td colspan="7" class="seller-order-lines-totals__label"><?= $e($t['order_detail_totals_sub_total'] ?? '') ?></td>
                        <td class="seller-order-lines-totals__val"><?= $e($moneySymbol) ?> <?= $e($subFmt) ?></td>
                      </tr>
                      <tr class="seller-order-lines-totals">
                        <td colspan="7" class="seller-order-lines-totals__label"><?= $e($discLabelFull) ?></td>
                        <td class="seller-order-lines-totals__val"><?= $e($moneySymbol) ?> <?= $e($discFmt) ?></td>
                      </tr>
                      <tr class="seller-order-lines-totals">
                        <td colspan="7" class="seller-order-lines-totals__label"><?= $e($t['order_detail_totals_grand'] ?? $t['column_total'] ?? '') ?></td>
                        <td class="seller-order-lines-totals__val"><?= $e($moneySymbol) ?> <?= $e($sumFmt) ?></td>
                      </tr>
                    </tfoot>
                  </table>
                </div>
              </div>
            <?php } ?>
          <?php } ?>
        </section>
<?php $this->load->view('seller/partials/portal_foot'); ?>

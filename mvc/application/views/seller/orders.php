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
/** @var array<int, object> $orders */
/** @var array<string, mixed>|null $orders_money */
/** @var int|null $orders_list_all_count */
/** @var int|null $orders_list_total */
/** @var int|null $orders_list_page */
/** @var int|null $orders_list_per_page */
/** @var array<string, string>|null $orders_list_filters */
/** @var list<array{value: string, label: string}>|null $orders_list_status_options */
/** @var list<array{id: int, name: string}>|null $orders_list_seller_options */

$t = is_array($pageTexts ?? null) ? $pageTexts : [];
$settings = isset($settings) && is_object($settings) ? $settings : null;
$e = static function (?string $s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
};
$urlHome = (string) ($url_home ?? site_url('sellers/customer'));
$urlOrders = (string) ($url_orders ?? site_url('sellers/orders'));
$urlCatalog = (string) ($url_catalog ?? site_url('sellers/catalog'));
$urlLogout = (string) ($url_logout ?? site_url('sellers/logout'));
$activeTab = (string) ($active_portal_tab ?? '');
$portalCompanyName = trim((string) ($portal_company_name ?? ''));
$orders = is_array($orders ?? null) ? $orders : [];
$ordersMoney = is_array($orders_money ?? null) ? $orders_money : [];
$moneySymbol = (string) ($ordersMoney['symbol'] ?? '$');
$moneyDigits = isset($ordersMoney['digits']) ? (int) $ordersMoney['digits'] : 2;
$moneyRadix = (string) ($ordersMoney['radixPoint'] ?? '.');
$moneyGroup = (string) ($ordersMoney['groupSeparator'] ?? ',');
$emptyCell = (string) ($t['customer_cell_empty'] ?? '—');
$upperUi = static function (string $s): string {
    $s = trim($s);
    if ($s === '') {
        return '';
    }

    return function_exists('mb_strtoupper') ? mb_strtoupper($s, 'UTF-8') : strtoupper($s);
};
$gridLine = static function (string $iconHtml, string $innerHtml): string {
    return '<div class="catalog-grid-line">' . $iconHtml . '<span class="catalog-grid-line-txt">' . $innerHtml . '</span></div>';
};
$icBuilding = '<svg class="catalog-grid-ic" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg>';
$icPerson = '<svg class="catalog-grid-ic" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>';
$icMap = '<svg class="catalog-grid-ic" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>';
$icMail = '<svg class="catalog-grid-ic" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>';
$icPhone = '<svg class="catalog-grid-ic" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>';
$icMobile = '<svg class="catalog-grid-ic" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M17 1H7c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-2-2-2zm0 18H7V5h10v14z"/></svg>';
$icMoney = '<svg class="catalog-grid-ic" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M11.8 10.9c-2.27-.59-3-1.2-3-2.15 0-1.09 1.01-1.85 2.7-1.85 1.78 0 2.44.85 2.5 2.1h2.21c-.07-1.72-1.12-3.3-3.21-3.81V3h-3v2.16c-1.94.42-3.5 1.68-3.5 3.61 0 2.31 1.91 3.46 4.7 4.13 2.5.6 3 1.48 3 2.41 0 .69-.49 1.79-2.7 1.79-2.06 0-2.87-.92-2.98-2.1h-2.2c.12 2.19 1.76 3.42 3.68 3.83V21h3v-2.15c1.95-.37 3.5-1.5 3.5-3.55 0-2.84-2.43-3.81-4.7-4.4z"/></svg>';
$icStatus = '<svg class="catalog-grid-ic" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/></svg>';
$svgOrdStore = '<svg class="seller-order-src-ic" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M18.36 9l.6 3H5.04l.6-3h12.72M20 4H4v2h16V4zm0 3H4l-1 5v2h2v4h10v-4h8v-2l-1-5zM9 18v-2h6v2H9z"/></svg>';
$svgOrdMobile = '<svg class="seller-order-src-ic" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M17 1H7c-1.1 0-2 .9-2 2v18c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-2-2-2zm0 18H7V5h10v14z"/></svg>';
$svgOrdHouse = '<svg class="seller-order-src-ic" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>';
$svgOrdUser = '<svg class="seller-order-src-ic" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>';
$svgOrdGlobe = '<svg class="seller-order-src-ic seller-order-src-ic--ok" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>';
$svgOrdCart = '<svg class="seller-order-src-ic seller-order-src-ic--ok" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49c.08-.14.12-.31.12-.48 0-.55-.45-1-1-1H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/></svg>';
$formatOrderListDate = static function (string $ymd) use ($e): string {
    if (trim($ymd) === '') {
        return '';
    }
    $ts = strtotime($ymd);

    return $ts !== false ? fv_format_display_date($ts) : $e($ymd);
};
$ordersListAllCount = isset($orders_list_all_count) ? (int) $orders_list_all_count : -1;
if ($ordersListAllCount < 0) {
    $ordersListAllCount = count($orders);
}
$ordersListTotal = isset($orders_list_total) ? (int) $orders_list_total : count($orders);
$ordersListPage = isset($orders_list_page) ? (int) $orders_list_page : 1;
$ordersListPerPage = isset($orders_list_per_page) ? (int) $orders_list_per_page : 30;
$orderFilters = is_array($orders_list_filters ?? null) ? $orders_list_filters : [
    'date_from' => '', 'date_to' => '', 'filter_seller' => '', 'filter_origin' => '', 'filter_status' => '', 'q' => '',
];
$statusFilterOptions = is_array($orders_list_status_options ?? null) ? $orders_list_status_options : [['value' => '', 'label' => '']];
$sellerFilterOptions = is_array($orders_list_seller_options ?? null) ? $orders_list_seller_options : [['id' => 0, 'name' => '']];
$buildOrdersListUrl = static function (array $f, int $page) use ($urlOrders): string {
    $params = [];
    foreach (['date_from', 'date_to', 'filter_seller', 'filter_origin', 'filter_status', 'q'] as $k) {
        $v = isset($f[$k]) ? trim((string) $f[$k]) : '';
        if ($v !== '') {
            $params[$k] = $v;
        }
    }
    if ($page > 1) {
        $params['page'] = (string) $page;
    }
    $qs = http_build_query($params);

    return $urlOrders . ($qs !== '' ? '?' . $qs : '');
};
$ordersListMaxPage = max(1, (int) ceil(max(1, $ordersListTotal) / max(1, $ordersListPerPage)));
$ordersRowFrom = $ordersListTotal > 0 ? (($ordersListPage - 1) * $ordersListPerPage) + 1 : 0;
$ordersRowTo = min($ordersListTotal, $ordersListPage * $ordersListPerPage);
$svgMailOrder = '<svg class="seller-order-mail-ic" width="16" height="16" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>';
$svgOrderViewEye = '<svg width="22" height="22" viewBox="0 0 24 24" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"><path fill="#d32f2f" d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zm0 12c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>';
$svgSearchMag = '<svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"><path fill="currentColor" d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>';
$svgFilterFunnel = '<svg class="seller-orders-funnel-svg" width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"><path fill="currentColor" d="M10 18h4v-2h-4v2zM3 6v2h18V6H3zm3 7h12v-2H6v2z"/></svg>';
$originFilterKeys = ['', 'catalog', 'app', 'manual', 'marketplace', 'web'];
?>
<?php
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
    'portal_page_title' => $portal_page_title ?? '',
]);
?>
<style>
    table.portal-orders-table tbody td {
      color: #5c1822;
    }
    table.portal-orders-table thead th.col-status,
    table.portal-orders-table tbody td.col-status {
      text-align: center;
    }
    table.portal-orders-table thead th.col-total,
    table.portal-orders-table tbody td.col-total {
      text-align: right;
      font-variant-numeric: tabular-nums;
      white-space: nowrap;
    }
    table.portal-orders-table thead th.col-actions,
    table.portal-orders-table tbody td.col-actions {
      text-align: center;
      white-space: nowrap;
    }
    table.portal-orders-table .btn-seller-order-view-ic {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 40px;
      height: 40px;
      border-radius: 4px;
      background: #17a2b8;
      border: 1px solid #138496;
      text-decoration: none !important;
      transition: background 0.15s, filter 0.15s;
    }
    table.portal-orders-table .btn-seller-order-view-ic:hover {
      background: #1fc8e3;
      filter: brightness(1.05);
    }
    table.portal-orders-table .btn-seller-order-view-ic svg {
      display: block;
    }
    .seller-orders-filters-wrap {
      margin-bottom: 20px;
      padding: 16px 18px 18px;
      border: 1px solid #e5e5e5;
      border-radius: 8px;
      background: #fafafa;
    }
    .seller-orders-filters-row {
      display: flex;
      flex-wrap: wrap;
      gap: 12px 18px;
      align-items: flex-end;
    }
    .seller-orders-filters-row--primary {
      flex-wrap: nowrap;
      gap: 14px 20px;
    }
    @media (max-width: 1100px) {
      .seller-orders-filters-row--primary {
        flex-wrap: wrap;
      }
    }
    .seller-orders-filters-row .seller-orders-fld {
      display: flex;
      flex-direction: column;
      gap: 6px;
      min-width: 0;
    }
    .seller-orders-filters-row--primary .seller-orders-fld {
      flex: 0 0 auto;
    }
    .seller-orders-filters-row--primary .seller-orders-fld--date {
      min-width: 148px;
      max-width: 170px;
    }
    .seller-orders-filters-row--primary .seller-orders-fld--narrow {
      min-width: 118px;
      max-width: 150px;
    }
    .seller-orders-filters-row--primary .seller-orders-fld--search {
      margin-left: auto;
      flex: 1 1 200px;
      min-width: min(100%, 200px);
      max-width: 360px;
    }
    .seller-orders-filters-row .seller-orders-fld label {
      font-size: 0.8125rem;
      font-weight: 600;
      color: var(--seller-ink);
      line-height: 1.2;
    }
    .seller-orders-select-has-funnel {
      position: relative;
      display: block;
    }
    .seller-orders-select-has-funnel .seller-orders-funnel-ic {
      position: absolute;
      left: 10px;
      top: 50%;
      transform: translateY(-50%);
      display: flex;
      align-items: center;
      justify-content: center;
      pointer-events: none;
      color: #5c5c5c;
      z-index: 1;
    }
    .seller-orders-select-has-funnel select {
      width: 100%;
      padding-left: 36px;
      padding-right: 28px;
    }
    .seller-orders-funnel-svg {
      display: block;
    }
    .seller-orders-filters-row input[type="date"],
    .seller-orders-filters-row select,
    .seller-orders-filters-row input[type="search"] {
      padding: 8px 10px;
      border: 1px solid #c4c4c4;
      border-radius: 4px;
      font-size: 0.875rem;
      background: #fff;
      color: var(--seller-ink);
      min-height: 38px;
      box-sizing: border-box;
    }
    .seller-orders-search-inner {
      position: relative;
      display: block;
      width: 100%;
    }
    .seller-orders-search-inner input[type="search"] {
      width: 100%;
      padding-right: 44px;
      margin: 0;
    }
    .seller-orders-btn-search-inside {
      position: absolute;
      right: 2px;
      top: 50%;
      transform: translateY(-50%);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 36px;
      height: 32px;
      padding: 0;
      border: none;
      border-radius: 3px;
      background: transparent;
      cursor: pointer;
      color: #525252;
    }
    .seller-orders-btn-search-inside:hover {
      background: rgba(0, 0, 0, 0.06);
      color: var(--seller-ink);
    }
    .seller-orders-paging-bar {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 12px 16px;
      margin-bottom: 12px;
    }
    .seller-orders-paging-bar .seller-orders-paging-meta {
      margin: 0;
      font-size: 0.9375rem;
      color: var(--seller-ink-muted);
    }
    .seller-orders-paging-links {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 6px;
    }
    .seller-orders-paging-links a,
    .seller-orders-paging-links span {
      display: inline-flex;
      min-width: 2rem;
      height: 2rem;
      align-items: center;
      justify-content: center;
      padding: 0 8px;
      border-radius: 4px;
      font-size: 0.875rem;
      font-weight: 600;
      text-decoration: none !important;
      border: 1px solid #d4d4d4;
      color: var(--seller-ink);
      background: #fff;
    }
    .seller-orders-paging-links a:hover {
      border-color: var(--seller-btn-solid);
      color: var(--seller-btn-solid);
    }
    .seller-orders-paging-links span.is-active {
      background: var(--seller-btn-solid);
      border-color: var(--seller-btn-solid);
      color: #fff;
    }
    .seller-orders-paging-links .seller-orders-page-of {
      padding: 0 8px;
      font-size: 0.875rem;
      font-weight: 600;
      color: var(--seller-ink-muted);
      border: none;
      background: transparent;
    }
    .seller-orders-comments-cell {
      max-width: 240px;
      font-size: 0.8125rem;
      line-height: 1.35;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
      text-align: left;
    }
    .seller-order-mail-ic {
      color: #fd7e14;
      flex-shrink: 0;
      vertical-align: middle;
    }
    table.portal-orders-table.seller-catalog-customer-grid-table .col-seller-orders-pick {
      width: 2.75rem;
      text-align: center;
      vertical-align: middle !important;
    }
    table.portal-orders-table.seller-catalog-customer-grid-table td.col-customer-stack {
      text-align: left !important;
    }
    .portal-orders-empty {
      margin: 0;
      padding: 28px 16px;
      text-align: center;
      color: var(--seller-ink-muted);
    }
    .portal-order-status {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.4rem;
      padding: 5px 10px;
      border-radius: 999px;
      font-size: 0.72rem;
      font-weight: 600;
      max-width: 12rem;
      border: 1px solid transparent;
    }
    .portal-order-status-dot {
      flex-shrink: 0;
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: #737373;
    }
    .portal-order-status-label {
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    table.portal-orders-table .portal-order-status.portal-order-status--new { background: #f5f5f5; color: var(--seller-ink); border-color: #d4d4d4; }
    table.portal-orders-table .portal-order-status.portal-order-status--pending { background: #ebebeb; color: #171717; border-color: #a3a3a3; }
    table.portal-orders-table .portal-order-status.portal-order-status--progress { background: #e5e5e5; color: var(--seller-ink); border-color: #737373; }
    table.portal-orders-table .portal-order-status.portal-order-status--done { background: #fafafa; color: #5c1822; border-color: #a3a3a3; }
    table.portal-orders-table .portal-order-status.portal-order-status--bad { background: #d4d4d4; color: var(--seller-ink); border-color: var(--seller-btn-solid); }
    table.portal-orders-table .portal-order-status.portal-order-status--return { background: #f5f5f5; color: #404040; border-color: #d4d4d4; }
    table.portal-orders-table .portal-order-status.portal-order-status--muted { background: #f5f5f5; color: #525252; border-color: #e5e5e5; }
    table.portal-orders-table .portal-order-status.portal-order-status--neutral { background: #fafafa; color: #404040; border-color: #e5e5e5; }
    /* Punto de color acorde al estado (si la API no envía color en status_order) */
    table.portal-orders-table .portal-order-status.portal-order-status--new .portal-order-status-dot { background-color: #16a34a; }
    table.portal-orders-table .portal-order-status.portal-order-status--pending .portal-order-status-dot { background-color: #ca8a04; }
    table.portal-orders-table .portal-order-status.portal-order-status--progress .portal-order-status-dot { background-color: #2563eb; }
    table.portal-orders-table .portal-order-status.portal-order-status--done .portal-order-status-dot { background-color: #15803d; }
    table.portal-orders-table .portal-order-status.portal-order-status--bad .portal-order-status-dot { background-color: #dc2626; }
    table.portal-orders-table .portal-order-status.portal-order-status--return .portal-order-status-dot { background-color: #c2410c; }
    table.portal-orders-table .portal-order-status.portal-order-status--muted .portal-order-status-dot { background-color: #a3a3a3; }
    table.portal-orders-table .portal-order-status.portal-order-status--neutral .portal-order-status-dot { background-color: #737373; }
    /* Misma grilla visual que pestaña Catálogo (cabecera roja + bloques apilados con iconos) */
    table.portal-orders-table.seller-catalog-customer-grid-table thead th {
      background: linear-gradient(135deg, #b92333 0%, #d22e3e 100%) !important;
      background-color: #c41e3a !important;
      color: #ffffff !important;
      -webkit-text-fill-color: #ffffff !important;
      font-weight: 600 !important;
      vertical-align: middle !important;
      border: none !important;
    }
    table.portal-orders-table.seller-catalog-customer-grid-table .col-id {
      width: 4rem;
      font-variant-numeric: tabular-nums;
      white-space: nowrap;
      vertical-align: middle !important;
    }
    table.portal-orders-table.seller-catalog-customer-grid-table .col-actions {
      vertical-align: middle !important;
    }
    table.portal-orders-table.seller-catalog-customer-grid-table .catalog-grid-stack {
      display: flex;
      flex-direction: column;
      gap: 0.2rem;
      min-width: 140px;
      max-width: 400px;
      font-size: 0.8125rem;
      line-height: 1.35;
      color: var(--seller-ink);
    }
    table.portal-orders-table.seller-catalog-customer-grid-table .catalog-grid-line {
      display: flex;
      align-items: flex-start;
      gap: 0.35rem;
    }
    table.portal-orders-table.seller-catalog-customer-grid-table .catalog-grid-ic {
      flex-shrink: 0;
      margin-top: 2px;
      color: var(--seller-ink);
      opacity: 0.92;
    }
    table.portal-orders-table.seller-catalog-customer-grid-table .catalog-grid-line-txt {
      flex: 1;
      min-width: 0;
      word-break: break-word;
    }
    table.portal-orders-table.seller-catalog-customer-grid-table .catalog-grid-fake-link {
      text-decoration: underline;
      color: var(--seller-ink);
      font-weight: inherit;
    }
    table.portal-orders-table.seller-catalog-customer-grid-table .catalog-grid-em {
      font-weight: 700;
    }
    table.portal-orders-table.seller-catalog-customer-grid-table thead th.col-status-total,
    table.portal-orders-table.seller-catalog-customer-grid-table tbody td.col-status-total {
      text-align: left;
      vertical-align: middle !important;
    }
    table.portal-orders-table.seller-catalog-customer-grid-table td.col-status-total .portal-order-status {
      max-width: 100%;
    }
    /* Misma caja para todos los estados (pestaña Pedidos) */
    table.portal-orders-table.seller-catalog-customer-grid-table th.col-status-seller,
    table.portal-orders-table.seller-catalog-customer-grid-table td.status-cell {
      width: 10.75rem;
      min-width: 10.75rem;
      max-width: 10.75rem;
      padding-left: 10px;
      padding-right: 10px;
      vertical-align: middle !important;
    }
    table.portal-orders-table.seller-catalog-customer-grid-table td.status-cell .portal-order-status {
      width: 100%;
      max-width: none;
      min-width: 0;
      min-height: 2.4rem;
      box-sizing: border-box;
      padding: 6px 10px;
    }
    table.portal-orders-table.seller-catalog-customer-grid-table td.status-cell .portal-order-status-label {
      flex: 1 1 auto;
      min-width: 0;
      text-align: center;
    }
    .seller-order-src-ic {
      flex-shrink: 0;
      color: var(--seller-ink);
      opacity: 0.9;
      vertical-align: middle;
    }
    .seller-order-src-ic--ok {
      color: #198754;
    }
    .seller-orders-num-cell {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }
    .seller-orders-num-cell a {
      font-weight: 600;
    }
    .seller-orders-th-underline {
      text-decoration: underline;
      text-underline-offset: 2px;
    }
    .seller-orders-updated-badge {
      display: inline-block;
      margin-top: 6px;
      padding: 3px 8px;
      font-size: 0.6875rem;
      font-weight: 600;
      line-height: 1.3;
      color: #212529;
      background: #ffc107;
      border-radius: 4px;
    }
    table.portal-orders-table.seller-catalog-customer-grid-table thead th {
      text-decoration: underline;
      text-underline-offset: 3px;
    }

</style>
        <section class="portal-welcome-card" aria-labelledby="seller-orders-heading">
          <div class="portal-orders-toolbar">
            <div>
              <h2 id="seller-orders-heading"><?= $e($t['orders_page_title'] ?? '') ?></h2>
              <?php
              $ordersSub = trim((string) ($t['orders_page_subtitle'] ?? ''));
              if ($ordersSub !== '') { ?>
                <p class="portal-orders-sub"><?= $e($ordersSub) ?></p>
              <?php } ?>
            </div>
          </div>

          <div class="seller-orders-filters-wrap">
            <form class="seller-orders-filters-form" method="get" action="<?= $e($urlOrders) ?>">
              <div class="seller-orders-filters-row seller-orders-filters-row--primary">
                <div class="seller-orders-fld seller-orders-fld--date">
                  <label for="sellerOrdersDateFrom"><?= $e($t['orders_filter_date_from'] ?? '') ?></label>
                  <input id="sellerOrdersDateFrom" type="date" name="date_from" value="<?= $e((string) ($orderFilters['date_from'] ?? '')) ?>">
                </div>
                <div class="seller-orders-fld seller-orders-fld--date">
                  <label for="sellerOrdersDateTo"><?= $e($t['orders_filter_date_to'] ?? '') ?></label>
                  <input id="sellerOrdersDateTo" type="date" name="date_to" value="<?= $e((string) ($orderFilters['date_to'] ?? '')) ?>">
                </div>
                <div class="seller-orders-fld seller-orders-fld--narrow">
                  <label for="sellerOrdersFilterSeller"><?= $e($t['orders_filter_sellers'] ?? '') ?></label>
                  <select id="sellerOrdersFilterSeller" name="filter_seller">
                    <?php foreach ($sellerFilterOptions as $so) {
                        $sid = (int) ($so['id'] ?? 0);
                        $optLabel = $sid === 0 ? (string) ($t['orders_filter_all'] ?? '') : (string) ($so['name'] ?? '');
                        $sel = (string) ($orderFilters['filter_seller'] ?? '') === (string) $sid ? ' selected' : '';
                        ?>
                      <option value="<?= $sid > 0 ? $e((string) $sid) : '' ?>"<?= $sel ?>><?= $e($optLabel) ?></option>
                    <?php } ?>
                  </select>
                </div>
                <div class="seller-orders-fld seller-orders-fld--narrow">
                  <label for="sellerOrdersFilterOrigin"><?= $e($t['orders_filter_origin'] ?? '') ?></label>
                  <div class="seller-orders-select-has-funnel">
                    <span class="seller-orders-funnel-ic" aria-hidden="true"><?= $svgFilterFunnel ?></span>
                    <select id="sellerOrdersFilterOrigin" name="filter_origin">
                    <?php foreach ($originFilterKeys as $ok) {
                        $sel = strtolower((string) ($orderFilters['filter_origin'] ?? '')) === strtolower($ok) ? ' selected' : '';
                        if ($ok === '') {
                            $lab = (string) ($t['orders_filter_all'] ?? '');
                        } else {
                            $lab = (string) ($t['orders_origin_' . $ok] ?? $ok);
                        }
                        ?>
                      <option value="<?= $e($ok) ?>"<?= $sel ?>><?= $e($lab) ?></option>
                    <?php } ?>
                    </select>
                  </div>
                </div>
                <div class="seller-orders-fld seller-orders-fld--narrow">
                  <label for="sellerOrdersFilterStatus"><?= $e($t['orders_filter_status'] ?? '') ?></label>
                  <select id="sellerOrdersFilterStatus" name="filter_status">
                    <?php foreach ($statusFilterOptions as $st) {
                        $sv = (string) ($st['value'] ?? '');
                        $slab = $sv === '' ? (string) ($t['orders_filter_all'] ?? '') : (string) ($st['label'] ?? '');
                        $sel = (string) ($orderFilters['filter_status'] ?? '') === $sv ? ' selected' : '';
                        ?>
                      <option value="<?= $e($sv) ?>"<?= $sel ?>><?= $e($slab) ?></option>
                    <?php } ?>
                  </select>
                </div>
                <div class="seller-orders-fld seller-orders-fld--search" role="search">
                  <label for="sellerOrdersSearchQ"><?= $e($t['orders_filter_search_field_label'] ?? '') ?></label>
                  <div class="seller-orders-search-inner">
                    <input id="sellerOrdersSearchQ" type="search" name="q" autocomplete="off" placeholder="<?= $e((string) ($t['orders_filter_search_input_placeholder'] ?? '')) ?>" value="<?= $e((string) ($orderFilters['q'] ?? '')) ?>">
                    <button type="submit" class="seller-orders-btn-search-inside" aria-label="<?= $e($t['orders_filter_search_button'] ?? '') ?>"><?= $svgSearchMag ?></button>
                  </div>
                </div>
              </div>
            </form>
          </div>

          <?php if ($ordersListAllCount === 0) { ?>
            <p class="portal-orders-empty mb-0" role="status"><?= $e($t['empty_orders'] ?? '') ?></p>
          <?php } elseif ($ordersListTotal === 0) { ?>
            <p class="portal-orders-empty mb-0" role="status"><?= $e($t['orders_empty_filtered'] ?? '') ?></p>
          <?php } else { ?>
            <div class="seller-orders-paging-bar">
              <p class="seller-orders-paging-meta"><?php
                $pagingFmt = (string) ($t['orders_list_paging_meta'] ?? '');
                echo $pagingFmt !== '' ? $e(sprintf($pagingFmt, $ordersRowFrom, $ordersRowTo, $ordersListTotal)) : '';
              ?></p>
              <?php if ($ordersListMaxPage > 1) { ?>
              <nav class="seller-orders-paging-links" aria-label="<?= $e($t['orders_pagination_nav_label'] ?? '') ?>">
                <?php if ($ordersListPage > 1) { ?>
                  <a href="<?= $e($buildOrdersListUrl($orderFilters, $ordersListPage - 1)) ?>"><?= $e($t['orders_pagination_prev'] ?? '') ?></a>
                <?php } ?>
                <?php if ($ordersListMaxPage <= 30) {
                    for ($p = 1; $p <= $ordersListMaxPage; $p++) {
                        if ($p === $ordersListPage) { ?>
                  <span class="is-active" aria-current="page"><?= (string) $p ?></span>
                        <?php } else { ?>
                  <a href="<?= $e($buildOrdersListUrl($orderFilters, $p)) ?>"><?= (string) $p ?></a>
                        <?php }
                    }
                } else {
                    $pagingFmtPage = (string) ($t['orders_list_paging_page_of'] ?? '');
                    if ($pagingFmtPage !== '') {
                        echo '<span class="seller-orders-page-of">' . $e(sprintf($pagingFmtPage, $ordersListPage, $ordersListMaxPage)) . '</span>';
                    }
                }
                if ($ordersListPage < $ordersListMaxPage) { ?>
                  <a href="<?= $e($buildOrdersListUrl($orderFilters, $ordersListPage + 1)) ?>"><?= $e($t['orders_pagination_next'] ?? '') ?></a>
                <?php } ?>
              </nav>
              <?php } ?>
            </div>
            <div class="portal-table-shell">
              <div class="portal-table-responsive">
                <table class="portal-orders-table seller-catalog-customer-grid-table">
                  <thead>
                    <tr>
                      <th scope="col" class="col-seller-orders-pick text-center">
                        <div style="display:flex;flex-direction:column;align-items:center;gap:4px;">
                          <input type="checkbox" id="sellerOrdersSelectAll" aria-label="<?= $e($t['orders_select_all_aria'] ?? '') ?>">
                          <span class="seller-orders-th-underline" style="font-size:0.7rem;font-weight:600;"><?= $e($t['orders_select_all_short'] ?? '') ?></span>
                        </div>
                      </th>
                      <th scope="col" class="text-center seller-orders-th-underline"><?= $e($t['column_order_number'] ?? '') ?></th>
                      <th scope="col" class="text-center seller-orders-th-underline"><?= $e($t['orders_col_approved'] ?? '') ?></th>
                      <th scope="col" class="seller-orders-th-underline"><?= $e($t['column_customer'] ?? '') ?></th>
                      <th scope="col" class="text-center seller-orders-th-underline"><?= $e($t['orders_grid_col_seller'] ?? '') ?></th>
                      <th scope="col" class="text-center seller-orders-th-underline"><?= $e($t['orders_col_warehouse_user'] ?? '') ?></th>
                      <th scope="col" class="text-center seller-orders-th-underline"><?= $e($t['orders_grid_col_created'] ?? '') ?></th>
                      <th scope="col" class="text-center seller-orders-th-underline"><?= $e($t['column_total'] ?? '') ?></th>
                      <th scope="col" class="text-center seller-orders-th-underline"><?= $e($t['orders_col_comments'] ?? '') ?></th>
                      <th scope="col" class="text-center col-status-seller seller-orders-th-underline"><?= $e($t['column_status'] ?? '') ?></th>
                      <th scope="col" class="col-actions text-center seller-orders-th-underline"><?= $e($t['column_actions'] ?? '') ?></th>
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
                      $custName = (isset($row->customer_name) && (string) $row->customer_name !== '') ? (string) $row->customer_name : '';
                      $viewId = isset($row->order_id) && $row->order_id !== null && $row->order_id !== '' ? (int) $row->order_id : 0;
                      $viewUrl = $viewId > 0 ? site_url('sellers/orders/view/' . $viewId) : '';
                      $orderNumRaw = trim((string) ($row->order_number ?? ''));
                      if ($orderNumRaw === '' && $viewId > 0) {
                          $orderNumRaw = (string) $viewId;
                      }
                      $ost = isset($row->order_status) ? (int) $row->order_status : 0;
                      $src = strtolower(trim((string) ($row->order_source ?? '')));
                      $pv = ! empty($row->portal_vendor);
                      $srcIcons = '';
                      if ($ost === 15) {
                          $srcIcons .= $svgOrdHouse;
                      } else {
                          switch ($src) {
                              case 'catalog':
                                  $srcIcons .= $svgOrdStore;
                                  break;
                              case 'app':
                                  $srcIcons .= $svgOrdMobile;
                                  break;
                              case 'manual':
                                  $srcIcons .= $svgOrdHouse;
                                  break;
                          }
                      }
                      if ($pv) {
                          $srcIcons .= $svgOrdUser;
                      }
                      if ($src === 'marketplace') {
                          $srcIcons .= $svgOrdGlobe . $svgOrdCart;
                      }
                      $biz = isset($row->customer_business) ? trim((string) $row->customer_business) : '';
                      $contact = isset($row->customer_contact) ? trim((string) $row->customer_contact) : '';
                      $email = isset($row->customer_email) ? trim((string) $row->customer_email) : '';
                      $phone = isset($row->customer_phone) ? trim((string) $row->customer_phone) : '';
                      $cell = isset($row->customer_cell) ? trim((string) $row->customer_cell) : '';
                      $addr = isset($row->customer_address) ? trim((string) $row->customer_address) : '';
                      $stackCustomer = '';
                      if ($biz !== '') {
                          $stackCustomer .= $gridLine($icBuilding, '<span class="catalog-grid-fake-link catalog-grid-em">' . $e($upperUi($biz)) . '</span>');
                      }
                      if ($contact !== '') {
                          $stackCustomer .= $gridLine($icPerson, '<span class="catalog-grid-fake-link">' . $e($upperUi($contact)) . '</span>');
                      }
                      if ($phone !== '') {
                          $tel = preg_replace('/\s+/', '', $phone);
                          $stackCustomer .= $gridLine($icPhone, '<a class="catalog-grid-fake-link" href="tel:' . $e($tel) . '">' . $e($upperUi($phone)) . '</a>');
                      }
                      if ($cell !== '') {
                          $telm = preg_replace('/\s+/', '', $cell);
                          $stackCustomer .= $gridLine($icMobile, '<a class="catalog-grid-fake-link" href="tel:' . $e($telm) . '">' . $e($upperUi($cell)) . '</a>');
                      }
                      if ($email !== '') {
                          $stackCustomer .= $gridLine($icMail, '<a class="catalog-grid-fake-link" href="mailto:' . $e($email) . '">' . $e($upperUi($email)) . '</a>');
                      }
                      if ($addr !== '') {
                          $stackCustomer .= $gridLine($icMap, '<span class="catalog-grid-fake-link">' . $e($addr) . '</span>');
                      }
                      if ($stackCustomer === '' && $custName !== '') {
                          $stackCustomer = $gridLine($icBuilding, '<span class="catalog-grid-fake-link catalog-grid-em">' . $e($upperUi($custName)) . '</span>');
                      }
                      if ($stackCustomer === '') {
                          $stackCustomer = '<div class="catalog-grid-line"><span class="catalog-grid-line-txt">' . $e($emptyCell) . '</span></div>';
                      }
                      $createdYmd = trim((string) ($row->created_date ?? ''));
                      $updatedYmd = isset($row->updated_date) ? trim((string) $row->updated_date) : '';
                      $createdDisp = $formatOrderListDate($createdYmd);
                      $updatedDisp = $formatOrderListDate($updatedYmd);
                      $dotHex = isset($row->status_dot_color) ? trim((string) $row->status_dot_color) : '';
                      $dotAttr = $dotHex !== '' ? ' style="background-color: ' . $e($dotHex) . ' !important"' : '';
                      $statusHtml = '<span class="portal-order-status ' . $e($badgeClass) . '">'
                        . '<span class="portal-order-status-dot" aria-hidden="true"' . $dotAttr . '></span>'
                        . '<span class="portal-order-status-label">' . $e($upperUi((string) ($row->status_label ?? ''))) . '</span></span>';
                      $approvedRow = isset($row->approved) && $row->approved;
                      $apprLabel = $approvedRow ? (string) ($t['orders_approved_yes'] ?? '') : (string) ($t['orders_approved_no'] ?? '');
                      $whLabel = isset($row->warehouse_user_label) ? trim((string) $row->warehouse_user_label) : '';
                      $commPrev = isset($row->comments_preview) ? trim((string) $row->comments_preview) : '';
                      $emailSent = ! empty($row->email_sent);
                      $sellerDisp = isset($row->seller_label) ? trim((string) $row->seller_label) : '';
                      $rowCbAriaTpl = (string) ($t['orders_row_checkbox_aria'] ?? '');
                      $rowCbRef = $orderNumRaw !== '' ? $orderNumRaw : ($viewId > 0 ? (string) $viewId : '');
                      $rowCbAria = $rowCbAriaTpl !== '' && $rowCbRef !== '' ? sprintf($rowCbAriaTpl, $rowCbRef) : $rowCbAriaTpl;
                      ?>
                      <tr>
                        <td class="col-seller-orders-pick text-center align-middle">
                          <input type="checkbox" class="seller-order-row-cb" aria-label="<?= $e($rowCbAria) ?>">
                        </td>
                        <td class="text-center align-middle">
                          <div class="seller-orders-num-cell">
                            <?= $srcIcons ?>
                            <?php if ($viewUrl !== '' && $orderNumRaw !== '') { ?>
                              <a href="<?= $e($viewUrl) ?>"><?= $e($upperUi($orderNumRaw)) ?></a>
                            <?php } elseif ($orderNumRaw !== '') { ?>
                              <span><?= $e($upperUi($orderNumRaw)) ?></span>
                            <?php } else { ?>
                              <?= $e($emptyCell) ?>
                            <?php } ?>
                            <?php if ($emailSent) {
                                echo $svgMailOrder;
                            } ?>
                          </div>
                        </td>
                        <td class="text-center align-middle text-nowrap"><?= $e($apprLabel) ?></td>
                        <td class="align-middle col-customer-stack" style="font-size: 13px;">
                          <div class="catalog-grid-stack"><?= $stackCustomer ?></div>
                        </td>
                        <td class="text-center align-middle"><?= $sellerDisp !== '' ? $e($upperUi($sellerDisp)) : $e($emptyCell) ?></td>
                        <td class="text-center align-middle"><?= $whLabel !== '' ? $e($upperUi($whLabel)) : $e($emptyCell) ?></td>
                        <td class="text-center text-nowrap align-middle">
                          <?php if ($createdDisp !== '') { ?>
                            <span class="badge" style="background:#f5f5f5;color:var(--seller-ink);font-weight:600;"><?= $e($createdDisp) ?></span>
                          <?php } else { ?>
                            <?= $e($emptyCell) ?>
                          <?php } ?>
                          <?php if ($updatedDisp !== '' && ($updatedYmd === '' || $updatedYmd !== $createdYmd)) { ?>
                            <br><span class="seller-orders-updated-badge"><?= $e((string) ($t['orders_list_updated'] ?? '')) ?>: <?= $e($updatedDisp) ?></span>
                          <?php } ?>
                        </td>
                        <td class="text-center align-middle">
                          <span class="catalog-grid-fake-link catalog-grid-em" style="font-size:1rem;"><?= $e($moneySymbol) ?> <?= $e($totalFmt) ?></span>
                        </td>
                        <td class="align-middle seller-orders-comments-cell" title="<?= $e($commPrev) ?>"><?= $commPrev !== '' ? $e($commPrev) : $e($emptyCell) ?></td>
                        <td class="text-center align-middle status-cell col-status-seller"><?= $statusHtml ?></td>
                        <td class="col-actions text-center align-middle">
                          <?php if ($viewId > 0) { ?>
                            <a class="btn-seller-order-view-ic" href="<?= $e(site_url('sellers/orders/view/' . $viewId)) ?>" title="<?= $e($t['order_view'] ?? '') ?>" aria-label="<?= $e($t['order_view'] ?? '') ?>"><?= $svgOrderViewEye ?></a>
                          <?php } else { ?>
                            <?= $e($emptyCell) ?>
                          <?php } ?>
                        </td>
                      </tr>
                    <?php } ?>
                  </tbody>
                </table>
              </div>
            </div>
          <?php } ?>
        </section>
  <script>
    (function () {
      var m = document.getElementById('sellerOrdersSelectAll');
      if (!m) return;
      m.addEventListener('change', function () {
        document.querySelectorAll('.seller-order-row-cb').forEach(function (c) {
          c.checked = m.checked;
        });
      });
    })();
  </script>
<?php $this->load->view('seller/partials/portal_foot'); ?>


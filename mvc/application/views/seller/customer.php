<?php
defined('BASEPATH') or exit('No direct script access allowed');

/** @var array<string, mixed> $pageTexts */
/** @var list<array<string, mixed>> $seller_customers */
/** @var array<string, bool> $seller_customer_table_columns */
/** @var bool $seller_all_customers_mode */
/** @var string|null $seller_customer_flash */
/** @var string|null $seller_customer_flash_extra */
/** @var list<array{group_id: int, name: string}> $seller_customer_groups */
/** @var list<array{term_id: int, name: string}>|null $seller_create_terms */
/** @var list<array{id: int, name: string}>|null $seller_create_sellers */
/** @var list<array{id: int, name: string}>|null $seller_create_countries */
/** @var int|null $seller_create_current_seller_id */
/** @var string|null $seller_create_current_seller_name */
/** @var int|null $seller_create_default_term_id */
/** @var int|null $seller_create_default_group_id */
/** @var array<string, mixed>|null $seller_customer_filter */
/** @var int|null $seller_customers_total_filtered */
/** @var int|null $seller_customers_total_all */
/** @var int|null $seller_customers_page */
/** @var int|null $seller_customers_per_page */
/** @var int|null $seller_customers_total_pages */

$this->load->view('seller/partials/portal_head', [
    'pageTexts' => $pageTexts ?? [],
    'settings' => $settings ?? null,
    'url_home' => $url_home ?? site_url('sellers/customer'),
    'url_orders' => $url_orders ?? site_url('sellers/orders'),
    'url_catalog' => $url_catalog ?? site_url('sellers/catalog'),
    'url_logout' => $url_logout ?? site_url('sellers/logout'),
    'portal_company_name' => $portal_company_name ?? '',
    'html_lang' => $html_lang ?? 'en',
    'active_portal_tab' => $active_portal_tab ?? 'home',
    'portal_page_title' => $portal_page_title ?? '',
]);
?>
<link rel="stylesheet" href="<?= htmlspecialchars(base_url('backend/vendors/mdi/css/materialdesignicons.min.css'), ENT_QUOTES, 'UTF-8') ?>">
<?php

$t = is_array($pageTexts ?? null) ? $pageTexts : [];
$customers = is_array($seller_customers ?? null) ? $seller_customers : [];
$customerGroups = is_array($seller_customer_groups ?? null) ? $seller_customer_groups : [];
$sellersForFilter = is_array($seller_create_sellers ?? null) ? $seller_create_sellers : [];
$termsForFilter = is_array($seller_create_terms ?? null) ? $seller_create_terms : [];
$filter = is_array($seller_customer_filter ?? null) ? $seller_customer_filter : [
    'status' => '',
    'group_id' => 0,
    'seller_id' => 0,
    'term_id' => 0,
    'q' => '',
    'page' => 1,
];
$totalFiltered = (int) ($seller_customers_total_filtered ?? count($customers));
$totalAll = (int) ($seller_customers_total_all ?? $totalFiltered);
$page = max(1, (int) ($seller_customers_page ?? 1));
$perPage = max(1, (int) ($seller_customers_per_page ?? 30));
$totalPages = max(1, (int) ($seller_customers_total_pages ?? 1));
$rangeFrom = $totalFiltered === 0 ? 0 : (($page - 1) * $perPage + 1);
$rangeTo = $totalFiltered === 0 ? 0 : min($page * $perPage, $totalFiltered);
$urlCustomer = site_url('sellers/customer');
$filterQuery = static function (array $f, ?int $pageNum = null) {
    $p = [];
    if (($f['status'] ?? '') !== '') {
        $p['f_status'] = (string) $f['status'];
    }
    if ((int) ($f['group_id'] ?? 0) > 0) {
        $p['f_group'] = (string) (int) $f['group_id'];
    }
    if ((int) ($f['seller_id'] ?? 0) > 0) {
        $p['f_seller'] = (string) (int) $f['seller_id'];
    }
    if ((int) ($f['term_id'] ?? 0) > 0) {
        $p['f_term'] = (string) (int) $f['term_id'];
    }
    $q = trim((string) ($f['q'] ?? ''));
    if ($q !== '') {
        $p['q'] = $q;
    }
    $pg = $pageNum !== null ? $pageNum : (int) ($f['page'] ?? 1);
    if ($pg > 1) {
        $p['page'] = (string) $pg;
    }

    return $p === [] ? '' : ('?' . http_build_query($p));
};
$flashKey = isset($seller_customer_flash) && is_string($seller_customer_flash) ? $seller_customer_flash : '';
$flashExtra = isset($seller_customer_flash_extra) && is_string($seller_customer_flash_extra) ? trim($seller_customer_flash_extra) : '';
$e = static function (?string $s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
};
$emptyCell = (string) ($t['customer_cell_empty'] ?? '—');
$cell = static function ($v) use ($e, $emptyCell): string {
    $s = trim((string) $v);

    return $s !== '' ? $e($s) : $e($emptyCell);
};
?>
        <section class="portal-welcome-card seller-mgmt-card" aria-labelledby="seller-customers-heading">
          <h2 id="seller-customers-heading" class="seller-mgmt-page-title"><?= $e($t['customer_mgmt_heading'] ?? $t['customer_tab_title'] ?? '') ?></h2>
          <?php if ($flashKey !== '' && strpos($flashKey, '_success') === false) { ?>
            <div class="catalog-flash catalog-flash--error seller-mgmt-flash mb-3" role="alert">
              <?= $e($t[$flashKey] ?? '') ?>
              <?php if ($flashExtra !== '') { ?><br><small><?= $e($flashExtra) ?></small><?php } ?>
            </div>
          <?php } ?>
          <div class="seller-mgmt-top-bar">
            <a href="<?= $e(site_url('sellers/customer/new')) ?>" class="seller-mgmt-btn-add">
              <i class="mdi mdi-plus" aria-hidden="true"></i>
              <span><?= $e($t['customer_mgmt_add_button'] ?? $t['customer_create_open_modal'] ?? '') ?></span>
            </a>
            <p class="seller-mgmt-total mb-0"><?php $tfTpl = (string) ($t['customer_mgmt_total_found'] ?? ''); echo $tfTpl !== '' ? $e(sprintf($tfTpl, (string) $totalFiltered)) : $e((string) $totalFiltered); ?></p>
          </div>
          <?php
          $fStatus = (string) ($filter['status'] ?? '');
          $fGroup = (int) ($filter['group_id'] ?? 0);
          $fSeller = (int) ($filter['seller_id'] ?? 0);
          $fTerm = (int) ($filter['term_id'] ?? 0);
          $fQ = (string) ($filter['q'] ?? '');
          ?>
          <?php if ($totalAll > 0) { ?>
          <form class="seller-mgmt-filters" method="get" action="<?= $e($urlCustomer) ?>" role="search">
            <div class="seller-mgmt-filters-inner">
              <div class="seller-mgmt-field">
                <label for="seller_mgmt_f_status"><?= $e($t['customer_mgmt_filter_enabled'] ?? '') ?></label>
                <select id="seller_mgmt_f_status" name="f_status" onchange="this.form.submit()">
                  <option value=""<?= $fStatus === '' ? ' selected' : '' ?>><?= $e($t['customer_mgmt_filter_all'] ?? '') ?></option>
                  <option value="enabled"<?= $fStatus === 'enabled' ? ' selected' : '' ?>><?= $e($t['customer_mgmt_filter_status_enabled'] ?? '') ?></option>
                  <option value="disabled"<?= $fStatus === 'disabled' ? ' selected' : '' ?>><?= $e($t['customer_mgmt_filter_status_disabled'] ?? '') ?></option>
                </select>
              </div>
              <div class="seller-mgmt-field">
                <label for="seller_mgmt_f_group"><?= $e($t['customer_mgmt_filter_group'] ?? '') ?></label>
                <select id="seller_mgmt_f_group" name="f_group" onchange="this.form.submit()">
                  <option value="0"<?= $fGroup === 0 ? ' selected' : '' ?>><?= $e($t['customer_mgmt_filter_all'] ?? '') ?></option>
                  <?php foreach ($customerGroups as $g) {
                      if (! is_array($g)) {
                          continue;
                      }
                      $goid = (int) ($g['group_id'] ?? 0);
                      if ($goid <= 0) {
                          continue;
                      }
                      $gn = trim((string) ($g['name'] ?? '')); ?>
                  <option value="<?= (string) $goid ?>"<?= $fGroup === $goid ? ' selected' : '' ?>><?= $e($gn !== '' ? $gn : (string) $goid) ?></option>
                  <?php } ?>
                </select>
              </div>
              <div class="seller-mgmt-field">
                <label for="seller_mgmt_f_seller"><?= $e($t['customer_mgmt_filter_seller'] ?? '') ?></label>
                <select id="seller_mgmt_f_seller" name="f_seller" onchange="this.form.submit()">
                  <option value="0"<?= $fSeller === 0 ? ' selected' : '' ?>><?= $e($t['customer_mgmt_filter_all'] ?? '') ?></option>
                  <?php foreach ($sellersForFilter as $sv) {
                      if (! is_array($sv)) {
                          continue;
                      }
                      $sid = (int) ($sv['id'] ?? 0);
                      if ($sid <= 0) {
                          continue;
                      }
                      $sn = trim((string) ($sv['name'] ?? '')); ?>
                  <option value="<?= (string) $sid ?>"<?= $fSeller === $sid ? ' selected' : '' ?>><?= $e($sn !== '' ? $sn : (string) $sid) ?></option>
                  <?php } ?>
                </select>
              </div>
              <div class="seller-mgmt-field">
                <label for="seller_mgmt_f_term"><?= $e($t['customer_mgmt_filter_payment_terms'] ?? '') ?></label>
                <select id="seller_mgmt_f_term" name="f_term" onchange="this.form.submit()">
                  <option value="0"<?= $fTerm === 0 ? ' selected' : '' ?>><?= $e($t['customer_mgmt_filter_all'] ?? '') ?></option>
                  <?php foreach ($termsForFilter as $tm) {
                      if (! is_array($tm)) {
                          continue;
                      }
                      $tid = (int) ($tm['term_id'] ?? 0);
                      if ($tid <= 0) {
                          continue;
                      }
                      $tn = trim((string) ($tm['name'] ?? '')); ?>
                  <option value="<?= (string) $tid ?>"<?= $fTerm === $tid ? ' selected' : '' ?>><?= $e($tn !== '' ? $tn : (string) $tid) ?></option>
                  <?php } ?>
                </select>
              </div>
              <div class="seller-mgmt-field seller-mgmt-field--search">
                <label class="sr-only" for="seller_mgmt_q"><?= $e($t['customer_mgmt_search_placeholder'] ?? '') ?></label>
                <div class="seller-mgmt-search-wrap">
                  <input type="search" id="seller_mgmt_q" name="q" value="<?= $e($fQ) ?>" placeholder="<?= $e($t['customer_mgmt_search_placeholder'] ?? '') ?>" autocomplete="off">
                  <button type="submit" class="seller-mgmt-search-btn" aria-label="<?= $e($t['customer_mgmt_search_aria'] ?? '') ?>"><i class="mdi mdi-magnify" aria-hidden="true"></i></button>
                </div>
              </div>
            </div>
          </form>
          <?php } ?>
          <style>
            .sr-only {
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
            .seller-mgmt-card { padding-top: 1.25rem; }
            .seller-mgmt-page-title {
              margin: 0 0 1rem;
              font-size: 1.35rem;
              font-weight: 700;
              color: #1a1a1a !important;
              -webkit-text-fill-color: #1a1a1a !important;
            }
            .seller-mgmt-top-bar {
              display: flex;
              flex-wrap: wrap;
              align-items: center;
              justify-content: space-between;
              gap: 12px 16px;
              margin-bottom: 1.25rem;
            }
            .seller-mgmt-btn-add {
              display: inline-flex;
              align-items: center;
              gap: 8px;
              font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif !important;
              font-size: 0.9375rem !important;
              font-weight: 600 !important;
              line-height: 1.35 !important;
              color: #fff !important;
              -webkit-text-fill-color: #fff !important;
              background: linear-gradient(180deg, #34ce57 0%, #28a745 100%) !important;
              border: 1px solid #23963d !important;
              padding: 10px 18px !important;
              border-radius: 6px !important;
              cursor: pointer !important;
              box-shadow: 0 1px 3px rgba(0,0,0,0.12);
              -webkit-appearance: none !important;
              appearance: none !important;
            }
            .seller-mgmt-btn-add:hover { filter: brightness(1.05); }
            .seller-mgmt-btn-add .mdi { font-size: 1.15rem; line-height: 1; }
            .seller-mgmt-total {
              font-size: 0.9375rem;
              color: #333 !important;
              -webkit-text-fill-color: #333 !important;
            }
            .seller-mgmt-total strong { font-weight: 700; color: #111 !important; }
            .seller-mgmt-filters {
              margin-bottom: 1.25rem;
              padding: 16px 18px;
              border: 1px solid #d4d4d4;
              border-radius: 8px;
              background: #fafafa;
            }
            .seller-mgmt-filters-inner {
              display: grid;
              grid-template-columns: repeat(5, 1fr);
              gap: 14px 16px;
              align-items: end;
            }
            @media (max-width: 1100px) {
              .seller-mgmt-filters-inner { grid-template-columns: repeat(3, 1fr); }
            }
            @media (max-width: 720px) {
              .seller-mgmt-filters-inner { grid-template-columns: 1fr 1fr; }
            }
            @media (max-width: 480px) {
              .seller-mgmt-filters-inner { grid-template-columns: 1fr; }
            }
            .seller-mgmt-field label {
              display: block;
              font-size: 0.68rem;
              font-weight: 700;
              letter-spacing: 0.06em;
              text-transform: uppercase;
              color: #6b7280 !important;
              margin-bottom: 6px;
            }
            .seller-mgmt-field select,
            .seller-mgmt-field input[type="search"] {
              width: 100%;
              box-sizing: border-box;
              padding: 8px 10px;
              font-size: 0.875rem;
              border: 1px solid #c4c4c4;
              border-radius: 4px;
              background: #fff;
              color: #1a1a1a;
            }
            .seller-mgmt-field--search { grid-column: span 1; }
            @media (min-width: 1101px) {
              .seller-mgmt-field--search { grid-column: span 1; min-width: 0; }
            }
            .seller-mgmt-search-wrap {
              display: flex;
              align-items: stretch;
              border: 1px solid #c4c4c4;
              border-radius: 4px;
              overflow: hidden;
              background: #fff;
            }
            .seller-mgmt-search-wrap input[type="search"] {
              border: none;
              border-radius: 0;
              flex: 1;
              min-width: 0;
            }
            .seller-mgmt-search-btn {
              flex: 0 0 42px;
              border: none;
              background: #f3f4f6;
              color: #374151;
              cursor: pointer;
              display: inline-flex;
              align-items: center;
              justify-content: center;
              border-left: 1px solid #c4c4c4;
            }
            .seller-mgmt-search-btn:hover { background: #e5e7eb; }
            .seller-mgmt-pagination-bar {
              display: flex;
              flex-wrap: wrap;
              align-items: center;
              justify-content: space-between;
              gap: 10px 16px;
              margin-bottom: 10px;
              font-size: 0.875rem;
              color: #4b5563;
            }
            .seller-mgmt-pagination {
              display: inline-flex;
              flex-wrap: wrap;
              align-items: center;
              gap: 6px;
            }
            .seller-mgmt-pagination a,
            .seller-mgmt-pagination span {
              display: inline-flex;
              align-items: center;
              justify-content: center;
              min-width: 2rem;
              height: 2rem;
              padding: 0 8px;
              font-size: 0.8125rem;
              font-weight: 600;
              border-radius: 4px;
              text-decoration: none !important;
              border: 1px solid #d1d5db;
              color: #374151 !important;
              background: #fff;
            }
            .seller-mgmt-pagination a:hover { background: #f3f4f6; }
            .seller-mgmt-pagination .is-current {
              background: #e5e7eb;
              border-color: #9ca3af;
              color: #111 !important;
            }
            .seller-mgmt-pagination .is-disabled {
              opacity: 0.45;
              pointer-events: none;
            }
            /* Misma cabecera roja que company/users (list_with_tabs) */
            .seller-customer-detail-table {
              border-collapse: separate;
              border-spacing: 0;
              border: 1px solid #dee2e6;
              border-radius: 6px;
              overflow: hidden;
              background: #fff;
            }
            .seller-customer-detail-table th { white-space: nowrap; }
            .seller-customer-detail-table td { word-break: break-word; vertical-align: middle; }
            .seller-portal-body .portal-main table.portal-orders-table.seller-customer-detail-table thead th {
              background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
              background-color: #c82333 !important;
              color: #ffffff !important;
              -webkit-text-fill-color: #ffffff !important;
              font-weight: 600 !important;
              vertical-align: middle !important;
              border: none !important;
              border-bottom: 1px solid rgba(0, 0, 0, 0.08) !important;
              font-size: 0.78rem;
              letter-spacing: 0.02em;
              text-transform: uppercase;
              padding-top: 0.65rem !important;
              padding-bottom: 0.65rem !important;
            }
            .seller-portal-body .portal-main table.portal-orders-table.seller-customer-detail-table thead th:first-child {
              border-top-left-radius: 6px;
            }
            .seller-portal-body .portal-main table.portal-orders-table.seller-customer-detail-table thead th:last-child {
              border-top-right-radius: 6px;
            }
            .seller-portal-body .portal-main table.portal-orders-table.seller-customer-detail-table tbody td {
              border-top: 1px solid #e9ecef;
              color: #212529 !important;
              -webkit-text-fill-color: #212529 !important;
            }
            .seller-customer-detail-table tbody tr { background: #ffffff; }
            .seller-customer-detail-table tbody tr:nth-child(even) { background: #f2f3f5; }
            .seller-customer-detail-table .sc-col-check { width: 36px; text-align: center; }
            .seller-customer-detail-table .sc-col-erp { width: 72px; text-align: center; font-size: 0.8rem; }
            .seller-customer-detail-table .sc-col-business { min-width: 220px; max-width: 28%; }
            .seller-customer-detail-table .sc-col-email { min-width: 180px; max-width: 22%; }
            .seller-customer-detail-table .sc-col-group { width: 140px; }
            .seller-customer-detail-table .sc-col-seller { min-width: 120px; max-width: 14%; font-size: 0.8rem; }
            .seller-customer-detail-table .sc-col-toggle { width: 88px; text-align: center; }
            .seller-customer-detail-table .sc-col-actions { width: 100px; text-align: center; }
            .seller-customer-link-stack { display: flex; flex-direction: column; gap: 2px; align-items: flex-start; min-width: 0; }
            .seller-customer-detail-table .seller-customer-link-stack a.seller-customer-data-link {
              display: inline-flex;
              align-items: center;
              gap: 5px;
              color: #212529 !important;
              -webkit-text-fill-color: #212529 !important;
              font-size: 0.78rem;
              text-decoration: underline !important;
              max-width: 100%;
              white-space: nowrap;
              overflow: hidden;
              text-overflow: ellipsis;
            }
            .seller-customer-detail-table .seller-customer-link-stack a.seller-customer-data-link:hover {
              color: #000000 !important;
              -webkit-text-fill-color: #000000 !important;
            }
            .seller-customer-detail-table .seller-customer-link-stack a.seller-customer-data-link .mdi {
              font-size: 1rem;
              color: #212529 !important;
              -webkit-text-fill-color: #212529 !important;
              flex-shrink: 0;
            }
            .seller-customer-link-stack .sc-line-business { font-weight: 700; text-transform: uppercase; }
            .seller-customer-detail-table .seller-customer-group-select {
              width: 100%;
              max-width: 130px;
              font-size: 0.75rem;
              padding: 4px 6px;
              border: 1px solid #ced4da;
              border-radius: 4px;
              background: #fff;
              color: #212529 !important;
              -webkit-text-fill-color: #212529 !important;
            }
            .seller-customer-group-select:disabled {
              opacity: 1;
              cursor: default;
              background: #fff;
            }
            .seller-customer-detail-table .sc-col-check input[type="checkbox"] {
              width: 1.125rem;
              height: 1.125rem;
              cursor: pointer;
              accent-color: #d22e3e;
            }
            .seller-customer-status-switch {
              width: 34px;
              height: 18px;
              border-radius: 999px;
              background: #ced4da;
              display: inline-block;
              position: relative;
              vertical-align: middle;
              box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.08);
            }
            .seller-customer-status-switch::after {
              content: "";
              width: 14px;
              height: 14px;
              border-radius: 999px;
              background: #ffffff;
              position: absolute;
              top: 2px;
              left: 2px;
              box-shadow: 0 1px 3px rgba(0,0,0,.2);
            }
            /* ON: rosa/rojo como en la captura de referencia */
            .seller-customer-status-switch.is-on {
              background: linear-gradient(180deg, #f5a0ad 0%, #e85d6d 100%);
              box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.06);
            }
            .seller-customer-status-switch.is-on::after { left: 18px; }
            .seller-customer-actions { display: inline-flex; gap: 4px; flex-wrap: nowrap; justify-content: center; }
            .seller-customer-actions .seller-customer-act {
              width: 30px;
              height: 30px;
              border: none;
              border-radius: 4px;
              display: inline-flex;
              align-items: center;
              justify-content: center;
              padding: 0;
              cursor: pointer;
              text-decoration: none;
              color: #fff !important;
              -webkit-text-fill-color: #fff !important;
              font-size: 1rem;
              box-shadow: 0 1px 2px rgba(0, 0, 0, 0.12);
            }
            /* Botones como en la imagen: azul / verde / naranja-rojo */
            .seller-customer-actions .seller-customer-act--view {
              background: linear-gradient(180deg, #5aa7f0 0%, #3d8fd9 100%);
            }
            .seller-customer-actions .seller-customer-act--view:hover:not(:disabled) {
              filter: brightness(1.06);
            }
            .seller-customer-actions .seller-customer-act--edit {
              background: linear-gradient(180deg, #3dcd5c 0%, #28a745 100%);
            }
            .seller-customer-actions .seller-customer-act--del {
              background: linear-gradient(180deg, #f39a6b 0%, #e67e22 100%);
            }
            .seller-customer-actions .seller-customer-act:disabled {
              opacity: 0.5;
              cursor: not-allowed;
              box-shadow: none;
            }
          </style>
          <?php if ($totalAll === 0) { ?>
            <p class="catalog-empty mb-4" role="status"><?= $e($t['customer_list_empty'] ?? '') ?></p>
          <?php } elseif ($totalFiltered === 0) { ?>
            <p class="catalog-empty mb-4" role="status"><?= $e($t['customer_mgmt_no_filter_results'] ?? '') ?></p>
          <?php } else { ?>
            <?php
            $rangeText = sprintf((string) ($t['customer_mgmt_pagination_range'] ?? '%1$s–%2$s / %3$s'), (string) $rangeFrom, (string) $rangeTo, (string) $totalFiltered);
            $pNavAria = (string) ($t['customer_mgmt_pagination_nav_label'] ?? '');
            ?>
            <div class="seller-mgmt-pagination-bar">
              <span><?= $e($rangeText) ?></span>
              <?php if ($totalPages > 1) { ?>
              <nav class="seller-mgmt-pagination" aria-label="<?= $e($pNavAria) ?>">
                <?php
                $link = static function (int $p) use ($urlCustomer, $filterQuery, $filter): string {
                    return $urlCustomer . $filterQuery($filter, $p);
                };
                ?>
                <a href="<?= $e($link(1)) ?>" class="<?= $page <= 1 ? 'is-disabled' : '' ?>"><?= $e($t['customer_mgmt_pagination_first'] ?? '') ?></a>
                <a href="<?= $e($link(max(1, $page - 1))) ?>" class="<?= $page <= 1 ? 'is-disabled' : '' ?>"><?= $e($t['customer_mgmt_pagination_prev'] ?? '') ?></a>
                <?php
                $pLo = max(1, $page - 2);
                $pHi = min($totalPages, $page + 2);
                for ($pi = $pLo; $pi <= $pHi; $pi++) {
                    if ($pi === $page) { ?>
                <span class="is-current" aria-current="page"><?= (string) $pi ?></span>
                <?php } else { ?>
                <a href="<?= $e($link($pi)) ?>"><?= (string) $pi ?></a>
                <?php }
                    }
                ?>
                <a href="<?= $e($link(min($totalPages, $page + 1))) ?>" class="<?= $page >= $totalPages ? 'is-disabled' : '' ?>"><?= $e($t['customer_mgmt_pagination_next'] ?? '') ?></a>
                <a href="<?= $e($link($totalPages)) ?>" class="<?= $page >= $totalPages ? 'is-disabled' : '' ?>"><?= $e($t['customer_mgmt_pagination_last'] ?? '') ?></a>
              </nav>
              <?php } ?>
            </div>
            <div class="table-responsive mb-4">
              <table class="portal-orders-table seller-customer-detail-table">
                <thead>
                  <tr>
                    <th class="sc-col-check" scope="col">
                      <input type="checkbox" id="seller-cm-select-all" title="<?= $e($t['customer_mgmt_select_all'] ?? '') ?>" aria-label="<?= $e($t['customer_mgmt_select_all'] ?? '') ?>">
                    </th>
                    <th class="sc-col-erp" scope="col"><?= $e($t['customer_column_erp_id'] ?? ($t['customer_column_erp'] ?? '')) ?></th>
                    <th class="sc-col-business" scope="col"><?= $e($t['customer_column_business'] ?? '') ?></th>
                    <th class="sc-col-email" scope="col"><?= $e($t['customer_column_email'] ?? '') ?></th>
                    <th class="sc-col-group" scope="col"><?= $e($t['catalog_grid_col_group'] ?? '') ?></th>
                    <th class="sc-col-seller" scope="col"><?= $e($t['orders_grid_col_seller'] ?? '') ?></th>
                    <th class="sc-col-toggle" scope="col"><?= $e($t['customer_column_send_catalog'] ?? '') ?></th>
                    <th class="sc-col-toggle" scope="col"><?= $e($t['customer_column_enabled'] ?? '') ?></th>
                    <th class="sc-col-actions" scope="col"><?= $e($t['column_actions'] ?? '') ?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $viewAria = (string) ($t['customer_action_view'] ?? '');
                  $deleteConfirm = (string) ($t['customer_delete_confirm'] ?? '');
                  foreach ($customers as $c) {
                      if (! is_array($c)) {
                          continue;
                      }
                      $cid = (int) ($c['id'] ?? 0);
                      if ($cid <= 0) {
                          continue;
                      }
                      $st = (string) ($c['status'] ?? '');
                      $stText = $st === 'active' ? (string) ($t['customer_status_active'] ?? '') : ($st === 'inactive' ? (string) ($t['customer_status_inactive'] ?? '') : $emptyCell);
                      $isActive = $st === 'active';
                      $sendCatalog = ! empty($c['send_catalog']);
                      $business = trim((string) ($c['business'] ?? ''));
                      $contact = trim((string) ($c['contact'] ?? ''));
                      $phone = trim((string) ($c['phone'] ?? ''));
                      $mobile = trim((string) ($c['mobile'] ?? ''));
                      $email = trim((string) ($c['email'] ?? ''));
                      $addressFull = trim((string) ($c['address_full'] ?? ''));
                      $erpId = trim((string) ($c['erp_id'] ?? ''));
                      $groupId = (int) ($c['group_id'] ?? 0);
                      $assignedSeller = trim((string) ($c['assigned_seller_name'] ?? ''));
                      $mailHref = $email !== '' ? 'mailto:' . rawurlencode($email) : '';
                      $telPhone = preg_replace('/[^\d+]/', '', $phone);
                      $telMobile = preg_replace('/[^\d+]/', '', $mobile);
                      $phoneHref = $telPhone !== '' ? 'tel:' . $telPhone : '';
                      $mobileHref = $telMobile !== '' ? 'tel:' . $telMobile : '';
                      $lineBiz = $business !== '' ? $business : ($contact !== '' ? $contact : (string) $cid);
                      ?>
                    <tr id="seller-customer-row-<?= (int) $cid ?>">
                      <td class="sc-col-check"><input type="checkbox" class="seller-cm-row-check" value="<?= (string) $cid ?>" aria-label="<?= $e($t['catalog_grid_select_column'] ?? '') ?>"></td>
                      <td class="sc-col-erp"><?= $erpId !== '' ? $e($erpId) : $e($emptyCell) ?></td>
                      <td class="sc-col-business">
                        <div class="seller-customer-link-stack">
                          <?php if ($lineBiz !== '') { ?>
                          <a class="seller-customer-data-link sc-line-business" href="#seller-customer-row-<?= (int) $cid ?>" onclick="return false;">
                            <i class="mdi mdi-office-building" aria-hidden="true"></i><span><?= $e($lineBiz) ?></span>
                          </a>
                          <?php } ?>
                          <?php if ($contact !== '') { ?>
                          <a class="seller-customer-data-link" href="#seller-customer-row-<?= (int) $cid ?>" onclick="return false;">
                            <i class="mdi mdi-account-card-details" aria-hidden="true"></i><span><?= $e($contact) ?></span>
                          </a>
                          <?php } ?>
                          <?php if ($addressFull !== '') { ?>
                          <a class="seller-customer-data-link" href="#seller-customer-row-<?= (int) $cid ?>" onclick="return false;">
                            <i class="mdi mdi-map-marker" aria-hidden="true"></i><span><?= $e($addressFull) ?></span>
                          </a>
                          <?php } ?>
                        </div>
                      </td>
                      <td class="sc-col-email">
                        <div class="seller-customer-link-stack">
                          <?php if ($email !== '') { ?>
                          <a class="seller-customer-data-link" href="<?= $e($mailHref) ?>">
                            <i class="mdi mdi-email" aria-hidden="true"></i><span><?= $e($email) ?></span>
                          </a>
                          <?php } ?>
                          <?php if ($phone !== '') { ?>
                          <a class="seller-customer-data-link" href="<?= $phoneHref !== '' ? $e($phoneHref) : '#' ?>" <?= $phoneHref === '' ? 'onclick="return false;"' : '' ?>>
                            <i class="mdi mdi-phone" aria-hidden="true"></i><span><?= $e($phone) ?></span>
                          </a>
                          <?php } ?>
                          <?php if ($mobile !== '') { ?>
                          <a class="seller-customer-data-link" href="<?= $mobileHref !== '' ? $e($mobileHref) : '#' ?>" <?= $mobileHref === '' ? 'onclick="return false;"' : '' ?>>
                            <i class="mdi mdi-cellphone" aria-hidden="true"></i><span><?= $e($mobile) ?></span>
                          </a>
                          <?php } ?>
                          <?php if ($email === '' && $phone === '' && $mobile === '') { ?>
                          <?= $e($emptyCell) ?>
                          <?php } ?>
                        </div>
                      </td>
                      <td class="sc-col-group">
                        <select class="seller-customer-group-select" disabled aria-label="<?= $e($t['catalog_grid_col_group'] ?? '') ?>">
                          <?php if ($customerGroups === []) {
                              $gn = trim((string) ($c['customer_group_name'] ?? ''));
                              ?>
                          <option value=""><?= $gn !== '' ? $e($gn) : $e($emptyCell) ?></option>
                          <?php } else {
                              foreach ($customerGroups as $g) {
                                  if (! is_array($g)) {
                                      continue;
                                  }
                                  $gidOpt = (int) ($g['group_id'] ?? 0);
                                  $gname = trim((string) ($g['name'] ?? ''));
                                  if ($gidOpt <= 0) {
                                      continue;
                                  }
                                  $sel = $gidOpt === $groupId ? ' selected' : '';
                                  ?>
                          <option value="<?= (int) $gidOpt ?>"<?= $sel ?>><?= $e($gname !== '' ? $gname : (string) $gidOpt) ?></option>
                          <?php }
                              } ?>
                        </select>
                      </td>
                      <td class="sc-col-seller"><?= $assignedSeller !== '' ? $e($assignedSeller) : $e($emptyCell) ?></td>
                      <td class="sc-col-toggle" title="<?= $e($t['customer_column_send_catalog'] ?? '') ?>"><span class="seller-customer-status-switch<?= $sendCatalog ? ' is-on' : '' ?>"></span></td>
                      <td class="sc-col-toggle" title="<?= $e($stText) ?>"><span class="seller-customer-status-switch<?= $isActive ? ' is-on' : '' ?>"></span></td>
                      <td class="sc-col-actions">
                        <div class="seller-customer-actions">
                          <a href="<?= $e(site_url('sellers/customer/view/' . (string) $cid)) ?>" class="seller-customer-act seller-customer-act--view" title="<?= $e($viewAria) ?>" aria-label="<?= $e($viewAria) ?>"><i class="mdi mdi-eye-outline" aria-hidden="true"></i></a>
                          <a href="<?= $e(site_url('sellers/customer/edit/' . (string) $cid)) ?>" class="seller-customer-act seller-customer-act--edit" title="<?= $e($t['customer_action_edit'] ?? '') ?>" aria-label="<?= $e($t['customer_action_edit'] ?? '') ?>"><i class="mdi mdi-pencil" aria-hidden="true"></i></a>
                          <button type="button" class="seller-customer-act seller-customer-act--del js-seller-customer-delete" data-customer-id="<?= (string) $cid ?>" data-confirm="<?= $e($deleteConfirm) ?>" title="<?= $e($t['customer_action_delete'] ?? '') ?>" aria-label="<?= $e($t['customer_action_delete'] ?? '') ?>"><i class="mdi mdi-delete" aria-hidden="true"></i></button>
                        </div>
                      </td>
                    </tr>
                  <?php } ?>
                </tbody>
              </table>
            </div>
            <script>
            (function () {
              var master = document.getElementById('seller-cm-select-all');
              if (!master) return;
              master.addEventListener('change', function () {
                document.querySelectorAll('.seller-cm-row-check').forEach(function (c) { c.checked = master.checked; });
              });
            })();
            </script>
          <?php } ?>
        </section>
        <script>
        (function () {
          var postUrl = <?= json_encode(site_url('sellers/customer'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
          document.querySelectorAll('.js-seller-customer-delete').forEach(function (btn) {
            btn.addEventListener('click', function () {
              var msg = btn.getAttribute('data-confirm') || '';
              if (msg && !window.confirm(msg)) { return; }
              var id = btn.getAttribute('data-customer-id');
              if (!id || !postUrl) { return; }
              var f = document.createElement('form');
              f.method = 'post';
              f.action = postUrl;
              var a = document.createElement('input');
              a.type = 'hidden';
              a.name = 'seller_customer_action';
              a.value = 'delete';
              f.appendChild(a);
              var c = document.createElement('input');
              c.type = 'hidden';
              c.name = 'customer_id';
              c.value = id;
              f.appendChild(c);
              document.body.appendChild(f);
              f.submit();
            });
          });
        })();
        </script>
        <?php if ($flashKey !== '' && strpos($flashKey, '_success') !== false) { ?>
        <script>
        (function () {
          var title = <?= json_encode((string) ($t[$flashKey] ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
          var detail = <?= json_encode($flashExtra, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
          if (!title) { return; }
          if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
            Swal.fire({
              icon: 'success',
              title: title,
              text: detail || undefined,
              confirmButtonText: <?= json_encode((string) ($t['ok'] ?? 'OK'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>
            });
            return;
          }
          if (typeof swal === 'function') {
            swal({ title: title, text: detail || undefined, icon: 'success' });
            return;
          }
          window.alert(title + (detail ? '\n' + detail : ''));
        })();
        </script>
        <?php } ?>
<?php $this->load->view('seller/partials/portal_foot'); ?>

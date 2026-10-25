<?php
defined('BASEPATH') or exit('No direct script access allowed');

/** @var array<string, mixed> $pageTexts */
/** @var list<array<string, mixed>> $catalog_customers */
/** @var string|null $catalog_flash */
/** @var int $catalog_selected_customer_id */
/** @var string $url_catalog_products */
/** @var string $url_catalog_reset */
/** @var int|null $catalog_customers_all_count */
/** @var array<string, mixed>|null $catalog_customer_filter */
/** @var list<array{group_id: int, name: string}>|null $seller_customer_groups */
/** @var list<array{id: int, name: string}>|null $seller_create_sellers */
/** @var list<array{term_id: int, name: string}>|null $seller_create_terms */

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
?>
<link rel="stylesheet" href="<?= htmlspecialchars(base_url('backend/vendors/mdi/css/materialdesignicons.min.css'), ENT_QUOTES, 'UTF-8') ?>">
<?php

$t = is_array($pageTexts ?? null) ? $pageTexts : [];
$customers = is_array($catalog_customers ?? null) ? $catalog_customers : [];
$e = static function (?string $s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
};
$flashKey = isset($catalog_flash) && is_string($catalog_flash) ? $catalog_flash : '';
$selectedId = isset($catalog_selected_customer_id) ? (int) $catalog_selected_customer_id : 0;
$urlProducts = (string) ($url_catalog_products ?? site_url('sellers/catalog/products'));
$urlReset = (string) ($url_catalog_reset ?? site_url('sellers/catalog?reset=1'));
$urlCatalog = (string) ($url_catalog ?? site_url('sellers/catalog'));
$emptyCell = (string) ($t['customer_cell_empty'] ?? '—');
$catalogCustomersAllCount = (int) ($catalog_customers_all_count ?? count($customers));
$customerGroups = is_array($seller_customer_groups ?? null) ? $seller_customer_groups : [];
$sellersForFilter = is_array($seller_create_sellers ?? null) ? $seller_create_sellers : [];
$termsForFilter = is_array($seller_create_terms ?? null) ? $seller_create_terms : [];
$catalogFilter = is_array($catalog_customer_filter ?? null) ? $catalog_customer_filter : [
    'status' => '', 'group_id' => 0, 'seller_id' => 0, 'term_id' => 0, 'q' => '', 'page' => 1,
];

/** @return array{0: string, 1: string} [display, telHref] — misma lógica que pestaña Customer */
$sellerPortalFormatPhoneLink = static function (string $raw): array {
    $raw = trim($raw);
    if ($raw === '') {
        return ['', ''];
    }
    $digits = preg_replace('/\D+/', '', $raw);
    if ($digits === '') {
        return [$raw, ''];
    }
    $isNanp10 = strlen($digits) === 10;
    $isNanp11 = strlen($digits) === 11 && $digits[0] === '1';
    if ($isNanp10 || $isNanp11) {
        $core = $isNanp11 ? substr($digits, 1) : $digits;
        if (strlen($core) !== 10) {
            $tel = preg_replace('/[^\d+]/', '', $raw);

            return [$raw, $tel !== '' ? 'tel:' . $tel : ''];
        }
        $display = '+1 (' . substr($core, 0, 3) . ')' . substr($core, 3, 3) . '-' . substr($core, 6, 4);
        $e164 = '1' . $core;

        return [$display, 'tel:+' . $e164];
    }
    $tel = preg_replace('/[^\d+]/', '', $raw);

    return [$raw, $tel !== '' ? 'tel:' . $tel : ''];
};
$catalogPickCount = 0;
foreach ($customers as $_row) {
    if (is_array($_row) && (int) ($_row['id'] ?? 0) > 0) {
        $catalogPickCount++;
    }
}
?>
        <section class="portal-welcome-card seller-mgmt-card" aria-labelledby="catalog-customers-heading">
          <?php if ($flashKey !== '') { ?>
            <div class="catalog-flash catalog-flash--error seller-mgmt-flash mb-3" role="alert"><?= $e($t[$flashKey] ?? $flashKey) ?></div>
          <?php } ?>
          <?php if ($catalogCustomersAllCount === 0) { ?>
            <h2 id="catalog-customers-heading" class="seller-mgmt-page-title"><?= $e($t['catalog_select_customer_title'] ?? '') ?></h2>
            <p class="portal-orders-sub mb-3"><?= $e($t['catalog_select_customer_intro'] ?? '') ?></p>
            <p class="catalog-empty mb-0" role="status"><?= $e($t['catalog_no_customers_assigned'] ?? '') ?></p>
          <?php } else { ?>
            <?php
            $cfStatus = (string) ($catalogFilter['status'] ?? '');
            $cfGroup = (int) ($catalogFilter['group_id'] ?? 0);
            $cfSeller = (int) ($catalogFilter['seller_id'] ?? 0);
            $cfTerm = (int) ($catalogFilter['term_id'] ?? 0);
            $cfQ = (string) ($catalogFilter['q'] ?? '');
            ?>
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
              .seller-catalog-heading-row {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 12px 20px;
                margin-bottom: 10px;
              }
              .seller-catalog-heading-row .seller-mgmt-page-title {
                margin: 0;
                flex: 1 1 auto;
                min-width: 0;
              }
              .seller-catalog-heading-actions {
                flex-shrink: 0;
              }
              .seller-catalog-pick-total {
                margin: 0 0 10px;
                text-align: right;
                font-size: 0.9375rem;
                line-height: 1.35;
                color: #7a2a32;
              }
            </style>
            <div class="seller-catalog-heading-row">
              <h2 id="catalog-customers-heading" class="seller-mgmt-page-title"><?= $e($t['catalog_select_customer_title'] ?? '') ?></h2>
              <?php if (count($customers) > 0) { ?>
              <div class="seller-catalog-heading-actions">
                <button type="submit" form="catalogPickCustomerForm" formtarget="_blank" rel="noopener noreferrer" class="seller-mgmt-btn-add" id="catalogCreateOrderBtn"<?= $selectedId > 0 ? '' : ' disabled' ?>>
                  <i class="mdi mdi-plus" aria-hidden="true"></i>
                  <span><?= $e($t['catalog_new_order_button'] ?? '') ?></span>
                </button>
              </div>
              <?php } ?>
            </div>
            <p class="portal-orders-sub mb-3"><?= $e($t['catalog_select_customer_intro'] ?? '') ?></p>
            <p class="seller-catalog-pick-total seller-mgmt-total" role="status"><?php
                $pickTotalTpl = (string) ($t['catalog_pick_total_found'] ?? '');
                echo $pickTotalTpl !== '' ? $e(sprintf($pickTotalTpl, (string) $catalogPickCount)) : $e((string) $catalogPickCount);
            ?></p>
            <form class="seller-mgmt-filters mb-3" method="get" action="<?= $e($urlCatalog) ?>" role="search">
              <div class="seller-mgmt-filters-inner">
                <div class="seller-mgmt-field">
                  <label for="catalog_mgmt_f_status"><?= $e($t['customer_mgmt_filter_enabled'] ?? '') ?></label>
                  <select id="catalog_mgmt_f_status" name="f_status" onchange="this.form.submit()">
                    <option value=""<?= $cfStatus === '' ? ' selected' : '' ?>><?= $e($t['customer_mgmt_filter_all'] ?? '') ?></option>
                    <option value="enabled"<?= $cfStatus === 'enabled' ? ' selected' : '' ?>><?= $e($t['customer_mgmt_filter_status_enabled'] ?? '') ?></option>
                    <option value="disabled"<?= $cfStatus === 'disabled' ? ' selected' : '' ?>><?= $e($t['customer_mgmt_filter_status_disabled'] ?? '') ?></option>
                  </select>
                </div>
                <div class="seller-mgmt-field">
                  <label for="catalog_mgmt_f_group"><?= $e($t['customer_mgmt_filter_group'] ?? '') ?></label>
                  <select id="catalog_mgmt_f_group" name="f_group" onchange="this.form.submit()">
                    <option value="0"<?= $cfGroup === 0 ? ' selected' : '' ?>><?= $e($t['customer_mgmt_filter_all'] ?? '') ?></option>
                    <?php foreach ($customerGroups as $g) {
                        if (! is_array($g)) {
                            continue;
                        }
                        $goid = (int) ($g['group_id'] ?? 0);
                        if ($goid <= 0) {
                            continue;
                        }
                        $gn = trim((string) ($g['name'] ?? '')); ?>
                    <option value="<?= (string) $goid ?>"<?= $cfGroup === $goid ? ' selected' : '' ?>><?= $e($gn !== '' ? $gn : (string) $goid) ?></option>
                    <?php } ?>
                  </select>
                </div>
                <div class="seller-mgmt-field">
                  <label for="catalog_mgmt_f_seller"><?= $e($t['customer_mgmt_filter_seller'] ?? '') ?></label>
                  <select id="catalog_mgmt_f_seller" name="f_seller" onchange="this.form.submit()">
                    <option value="0"<?= $cfSeller === 0 ? ' selected' : '' ?>><?= $e($t['customer_mgmt_filter_all'] ?? '') ?></option>
                    <?php foreach ($sellersForFilter as $sv) {
                        if (! is_array($sv)) {
                            continue;
                        }
                        $sid = (int) ($sv['id'] ?? 0);
                        if ($sid <= 0) {
                            continue;
                        }
                        $sn = trim((string) ($sv['name'] ?? '')); ?>
                    <option value="<?= (string) $sid ?>"<?= $cfSeller === $sid ? ' selected' : '' ?>><?= $e($sn !== '' ? $sn : (string) $sid) ?></option>
                    <?php } ?>
                  </select>
                </div>
                <div class="seller-mgmt-field">
                  <label for="catalog_mgmt_f_term"><?= $e($t['customer_mgmt_filter_payment_terms'] ?? '') ?></label>
                  <select id="catalog_mgmt_f_term" name="f_term" onchange="this.form.submit()">
                    <option value="0"<?= $cfTerm === 0 ? ' selected' : '' ?>><?= $e($t['customer_mgmt_filter_all'] ?? '') ?></option>
                    <?php foreach ($termsForFilter as $tm) {
                        if (! is_array($tm)) {
                            continue;
                        }
                        $tid = (int) ($tm['term_id'] ?? 0);
                        if ($tid <= 0) {
                            continue;
                        }
                        $tn = trim((string) ($tm['name'] ?? '')); ?>
                    <option value="<?= (string) $tid ?>"<?= $cfTerm === $tid ? ' selected' : '' ?>><?= $e($tn !== '' ? $tn : (string) $tid) ?></option>
                    <?php } ?>
                  </select>
                </div>
                <div class="seller-mgmt-field seller-mgmt-field--search">
                  <label class="sr-only" for="catalog_mgmt_q"><?= $e($t['customer_mgmt_search_placeholder'] ?? '') ?></label>
                  <div class="seller-mgmt-search-wrap">
                    <input type="search" id="catalog_mgmt_q" name="q" value="<?= $e($cfQ) ?>" placeholder="<?= $e($t['customer_mgmt_search_placeholder'] ?? '') ?>" autocomplete="off">
                    <button type="submit" class="seller-mgmt-search-btn" aria-label="<?= $e($t['customer_mgmt_search_aria'] ?? '') ?>"><i class="mdi mdi-magnify" aria-hidden="true"></i></button>
                  </div>
                </div>
              </div>
            </form>
            <?php if ($customers === []) { ?>
            <p class="catalog-empty mb-0" role="status"><?= $e($t['catalog_no_customers_match_filters'] ?? '') ?></p>
            <?php } else { ?>
            <style>
              .catalog-pick-links {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 10px;
              }
              #catalogCreateOrderBtn:disabled {
                opacity: 0.55;
                cursor: not-allowed;
                filter: grayscale(0.15);
              }
              .seller-customer-detail-table .sc-col-check { width: 36px; text-align: center; }
              .seller-customer-detail-table .sc-col-erp { width: 72px; text-align: center; font-size: 0.8rem; }
              .seller-customer-detail-table .sc-col-business { min-width: 220px; max-width: 28%; }
              .seller-customer-detail-table .sc-col-email { min-width: 180px; max-width: 22%; }
              .seller-customer-detail-table .sc-col-group { width: 140px; }
              .seller-customer-detail-table .sc-col-seller { min-width: 120px; max-width: 14%; font-size: 0.8rem; }
              .seller-customer-detail-table .sc-col-toggle { width: 88px; text-align: center; }
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
              .seller-customer-status-switch.is-on {
                background: linear-gradient(180deg, #f5a0ad 0%, #e85d6d 100%);
                box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.06);
              }
              .seller-customer-status-switch.is-on::after { left: 18px; }
            </style>
            <form method="post" action="<?= $e(site_url('sellers/catalog')) ?>" id="catalogPickCustomerForm" novalidate>
              <input type="hidden" name="customer_id" id="catalogCustomerIdHidden" value="<?= $selectedId > 0 ? (string) $selectedId : '' ?>">
              <?php if ($selectedId > 0) { ?>
              <div class="catalog-pick-links mb-3">
                <a class="btn-portal-outline" href="<?= $e($urlProducts) ?>"><?= $e($t['catalog_continue_products'] ?? '') ?></a>
                <a class="btn-portal-outline" href="<?= $e($urlReset) ?>"><?= $e($t['catalog_change_customer'] ?? '') ?></a>
              </div>
              <?php } ?>
              <div class="table-responsive mb-4">
                <table class="portal-orders-table seller-portal-data-grid seller-customer-detail-table">
                  <thead>
                    <tr>
                      <th class="sc-col-check" scope="col">
                        <input type="checkbox" id="catalog-cm-select-all" title="<?= $e($t['customer_mgmt_select_all'] ?? '') ?>" aria-label="<?= $e($t['customer_mgmt_select_all'] ?? '') ?>">
                      </th>
                      <th class="sc-col-erp" scope="col"><?= $e($t['customer_column_erp_id'] ?? ($t['customer_column_erp'] ?? '')) ?></th>
                      <th class="sc-col-business" scope="col"><?= $e($t['customer_column_business'] ?? '') ?></th>
                      <th class="sc-col-email" scope="col"><?= $e($t['customer_column_email'] ?? '') ?></th>
                      <th class="sc-col-group" scope="col"><?= $e($t['catalog_grid_col_group'] ?? '') ?></th>
                      <th class="sc-col-seller" scope="col"><?= $e($t['orders_grid_col_seller'] ?? '') ?></th>
                      <th class="sc-col-toggle" scope="col"><?= $e($t['customer_column_send_catalog'] ?? '') ?></th>
                      <th class="sc-col-toggle" scope="col"><?= $e($t['customer_column_enabled'] ?? '') ?></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($customers as $c) {
                        if (! is_array($c)) {
                            continue;
                        }
                        $cid = (int) ($c['id'] ?? 0);
                        if ($cid <= 0) {
                            continue;
                        }
                        $isSel = $cid === $selectedId;
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
                        [$phoneDisplay, $phoneHref] = $sellerPortalFormatPhoneLink($phone);
                        [$mobileDisplay, $mobileHref] = $sellerPortalFormatPhoneLink($mobile);
                        $phoneShow = $phoneDisplay !== '' ? $phoneDisplay : $phone;
                        $mobileShow = $mobileDisplay !== '' ? $mobileDisplay : $mobile;
                        $lineBiz = $business !== '' ? $business : ($contact !== '' ? $contact : (string) $cid);
                        ?>
                    <tr id="catalog-customer-row-<?= (int) $cid ?>">
                      <td class="sc-col-check">
                        <input type="checkbox"
                          class="seller-cm-row-check js-catalog-customer-pick"
                          value="<?= (string) $cid ?>"
                          id="catalog-pick-<?= (string) $cid ?>"
                          aria-label="<?= $e($t['catalog_grid_select_column'] ?? '') ?> — <?= $e((string) ($c['label'] ?? ('#' . (string) $cid))) ?>"
                          <?= $isSel ? ' checked' : '' ?>>
                      </td>
                      <td class="sc-col-erp"><?= $erpId !== '' ? $e($erpId) : $e($emptyCell) ?></td>
                      <td class="sc-col-business">
                        <div class="seller-customer-link-stack">
                          <?php if ($lineBiz !== '') { ?>
                          <a class="seller-customer-data-link sc-line-business" href="#catalog-customer-row-<?= (int) $cid ?>" onclick="return false;">
                            <i class="mdi mdi-office-building" aria-hidden="true"></i><span><?= $e($lineBiz) ?></span>
                          </a>
                          <?php } ?>
                          <?php if ($contact !== '') { ?>
                          <a class="seller-customer-data-link" href="#catalog-customer-row-<?= (int) $cid ?>" onclick="return false;">
                            <i class="mdi mdi-account-card-details" aria-hidden="true"></i><span><?= $e($contact) ?></span>
                          </a>
                          <?php } ?>
                          <?php if ($addressFull !== '') { ?>
                          <a class="seller-customer-data-link" href="#catalog-customer-row-<?= (int) $cid ?>" onclick="return false;">
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
                            <i class="mdi mdi-phone" aria-hidden="true"></i><span><?= $e($phoneShow) ?></span>
                          </a>
                          <?php } ?>
                          <?php if ($mobile !== '') { ?>
                          <a class="seller-customer-data-link" href="<?= $mobileHref !== '' ? $e($mobileHref) : '#' ?>" <?= $mobileHref === '' ? 'onclick="return false;"' : '' ?>>
                            <i class="mdi mdi-cellphone" aria-hidden="true"></i><span><?= $e($mobileShow) ?></span>
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
                    </tr>
                    <?php } ?>
                  </tbody>
                </table>
              </div>
            </form>
            <script>
            (function () {
              var form = document.getElementById('catalogPickCustomerForm');
              if (!form) return;
              var hidden = document.getElementById('catalogCustomerIdHidden');
              var btn = document.getElementById('catalogCreateOrderBtn');
              var master = document.getElementById('catalog-cm-select-all');
              var cbs = form.querySelectorAll('input.js-catalog-customer-pick[type="checkbox"]');
              var serverHiddenId = hidden ? String(hidden.value || '').trim() : '';
              var userTouchedRowChecks = false;
              function sync() {
                var list = form.querySelectorAll('input.js-catalog-customer-pick[type="checkbox"]:checked');
                var fromGrid = list.length ? String(list[0].value).trim() : '';
                var effective = fromGrid;
                if (effective === '' && !userTouchedRowChecks && serverHiddenId !== '') {
                  effective = serverHiddenId;
                }
                if (hidden) hidden.value = effective;
                if (btn) {
                  if (effective !== '') {
                    btn.removeAttribute('disabled');
                    btn.disabled = false;
                  } else {
                    btn.disabled = true;
                    btn.setAttribute('disabled', 'disabled');
                  }
                }
              }
              if (master) {
                master.addEventListener('change', function () {
                  userTouchedRowChecks = true;
                  cbs.forEach(function (c) { c.checked = master.checked; });
                  sync();
                });
              }
              cbs.forEach(function (cb) {
                cb.addEventListener('change', function () {
                  userTouchedRowChecks = true;
                  if (cb.checked) {
                    cbs.forEach(function (o) {
                      if (o !== cb) o.checked = false;
                    });
                    if (master) master.checked = false;
                  }
                  sync();
                });
              });
              sync();
              form.addEventListener('submit', function (ev) {
                sync();
                if (!hidden || !String(hidden.value || '').trim()) {
                  ev.preventDefault();
                }
              });
            })();
            </script>
            <?php } ?>
          <?php } ?>
        </section>
<?php $this->load->view('seller/partials/portal_foot'); ?>

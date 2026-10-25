<?php
defined('BASEPATH') or exit('No direct script access allowed');

/** @var array<string, mixed> $pageTexts */
/** @var string $seller_customer_form_page_mode */
/** @var array<string, mixed>|null $seller_customer_form_page_seed */
/** @var string $seller_customer_form_back_url */
/** @var list<array{group_id: int, name: string}> $seller_customer_groups */
/** @var list<array{term_id: int, name: string}>|null $seller_create_terms */
/** @var list<array{id: int, name: string}>|null $seller_create_sellers */
/** @var list<array{id: int, name: string}>|null $seller_create_countries */
/** @var int|null $seller_create_current_seller_id */
/** @var string|null $seller_create_current_seller_name */
/** @var int|null $seller_create_default_term_id */
/** @var int|null $seller_create_default_group_id */
/** @var int|null $seller_create_default_country_id */

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
<link rel="stylesheet" href="<?= htmlspecialchars(base_url('companyasset/css/select2.min.css'), ENT_QUOTES, 'UTF-8') ?>">
<style>
  .seller-portal-body .seller-customer-form-page {
    width: 100%;
    max-width: 100%;
    margin: 0;
    box-sizing: border-box;
  }
  .seller-portal-body .seller-customer-form-header-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1rem 1.5rem;
    margin-bottom: 1.35rem;
  }
  .seller-portal-body .seller-customer-form-header-row .seller-mgmt-page-title {
    margin: 0;
    flex: 1 1 auto;
    min-width: 0;
    font-size: clamp(1.15rem, 2vw, 1.5rem);
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #111827;
  }
  .seller-portal-body .seller-customer-form-back--trailing {
    margin-left: auto;
    flex-shrink: 0;
    white-space: nowrap;
  }
  .seller-portal-body .seller-customer-form-page .seller-customer-create-form {
    margin-top: 0.25rem;
  }
  .seller-portal-body .seller-customer-form-page .seller-customer-form-section {
    margin-bottom: 1.75rem;
    padding-bottom: 1.25rem;
    border-bottom: 1px solid #e5e7eb;
  }
  .seller-portal-body .seller-customer-form-page .seller-customer-form-section:last-of-type {
    border-bottom: none;
  }
  .seller-portal-body .seller-customer-form-page .seller-customer-form-section-title {
    margin: 0 0 1.1rem 0;
    font-size: 0.875rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    color: #000000;
  }
  .seller-portal-body .seller-customer-form-page .seller-cf-row {
    display: grid;
    gap: 0.85rem 1.1rem;
    margin-bottom: 1rem;
  }
  .seller-portal-body .seller-customer-form-page .seller-cf-row:last-child {
    margin-bottom: 0;
  }
  .seller-portal-body .seller-customer-form-page .seller-cf-section-header--customer {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    justify-content: space-between;
    gap: 0.65rem 1.25rem;
    margin-bottom: 1rem;
  }
  .seller-portal-body .seller-customer-form-page .seller-customer-form-section-title--customer {
    margin-bottom: 0;
    flex: 1 1 auto;
    min-width: 0;
    line-height: 1.25;
  }
  .seller-portal-body .seller-customer-form-page .seller-cf-customer-erp-bar {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    margin-bottom: 0.5rem;
  }
  .seller-portal-body .seller-customer-form-page .seller-cf-section-header--customer .seller-cf-customer-erp-bar {
    flex: 0 0 auto;
    margin-bottom: 0;
  }
  .seller-portal-body .seller-customer-form-page .seller-cf-row--customer-1 {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
  @media (max-width: 900px) {
    .seller-portal-body .seller-customer-form-page .seller-cf-row--customer-1 {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }
  .seller-portal-body .seller-customer-form-page .seller-cf-erp-inline {
    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    align-items: center;
    gap: 0.45rem 0.55rem;
  }
  .seller-portal-body .seller-customer-form-page .seller-cf-erp-group-label {
    font-size: 0.75rem;
    font-weight: 700;
    color: #111827;
    white-space: nowrap;
  }
  .seller-portal-body .seller-customer-form-page .seller-cf-erp-inline-txt,
  .seller-portal-body .seller-customer-form-page .seller-cf-erp-inline-num {
    box-sizing: border-box;
    min-width: 0;
    height: 2.25rem;
    padding: 0.35rem 0.45rem;
    font-size: 0.8125rem;
    border: 1px solid #d1d5db;
    border-radius: 0.25rem;
    background: #fff;
  }
  .seller-portal-body .seller-customer-form-page .seller-cf-erp-inline-txt {
    width: 5.25rem;
    max-width: 100%;
  }
  .seller-portal-body .seller-customer-form-page .seller-cf-erp-inline-num {
    width: 4.75rem;
    max-width: 100%;
  }
  .seller-portal-body .seller-customer-form-page .seller-cf-row--customer-2 {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
  @media (max-width: 900px) {
    .seller-portal-body .seller-customer-form-page .seller-cf-row--customer-2 {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }
  .seller-portal-body .seller-customer-form-page .seller-cf-row--customer-3 {
    grid-template-columns: repeat(4, minmax(0, 1fr));
    align-items: start;
  }
  @media (max-width: 1000px) {
    .seller-portal-body .seller-customer-form-page .seller-cf-row--customer-3 {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }
  .seller-portal-body .seller-customer-form-page .seller-cf-row--commercial-1 {
    grid-template-columns: minmax(0, 1fr) minmax(0, 1.55fr) minmax(0, 1fr);
  }
  @media (max-width: 900px) {
    .seller-portal-body .seller-customer-form-page .seller-cf-row--commercial-1 {
      grid-template-columns: 1fr;
    }
  }
  .seller-portal-body .seller-customer-form-page .seller-cf-row--4eq {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
  @media (max-width: 900px) {
    .seller-portal-body .seller-customer-form-page .seller-cf-row--4eq {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }
  .seller-portal-body .seller-customer-form-page .seller-cf-row--delivery-mid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
  .seller-portal-body .seller-customer-form-page .seller-cf-row--delivery-bot {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
  @media (max-width: 720px) {
    .seller-portal-body .seller-customer-form-page .seller-cf-row--delivery-mid,
    .seller-portal-body .seller-customer-form-page .seller-cf-row--delivery-bot {
      grid-template-columns: 1fr;
    }
  }
  .seller-portal-body .seller-customer-form-page .seller-delivery-notes-hidden {
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
  .seller-portal-body .seller-customer-form-page .seller-customer-checkbox-label .seller-cf-same-as-label-text {
    color: #000000;
    font-weight: 700;
    text-decoration: none;
  }
  .seller-portal-body .seller-customer-form-page .seller-customer-form-grid-adv {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 1rem 1.25rem;
  }
  .seller-portal-body .seller-customer-form-page .seller-customer-form-grid-3 {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem 1.25rem;
  }
  .seller-portal-body .seller-customer-form-page .seller-customer-form-full-row {
    grid-column: 1 / -1;
  }
  .seller-portal-body .seller-customer-form-page label {
    display: block;
    font-size: 0.8125rem;
    font-weight: 700;
    color: #000000;
    margin-bottom: 0.35rem;
  }
  .seller-portal-body .seller-customer-form-page .seller-customer-req {
    color: #d22e3e;
    font-weight: 700;
  }
  .seller-portal-body .seller-customer-form-page input[type="text"],
  .seller-portal-body .seller-customer-form-page input[type="email"],
  .seller-portal-body .seller-customer-form-page input[type="tel"],
  .seller-portal-body .seller-customer-form-page input[type="number"],
  .seller-portal-body .seller-customer-form-page select,
  .seller-portal-body .seller-customer-form-page textarea {
    width: 100%;
    box-sizing: border-box;
    padding: 0.65rem 0.85rem;
    font-size: 0.9375rem;
    border: 1px solid #c4c4c4;
    border-radius: 4px;
    background: #fff;
    color: #111827;
  }
  .seller-portal-body .seller-customer-form-page .seller-customer-input-muted,
  .seller-portal-body .seller-customer-form-page .seller-customer-summary-readonly {
    background: #f3f4f6 !important;
    color: #374151 !important;
    border-color: #d1d5db !important;
  }
  .seller-portal-body .seller-customer-form-page .seller-phone-field-rec {
    display: flex;
    align-items: stretch;
    gap: 0;
    border: 1px solid #c4c4c4;
    border-radius: 4px;
    overflow: hidden;
    background: #fff;
  }
  .seller-portal-body .seller-customer-form-page .seller-phone-prefix {
    flex: 0 0 auto;
    min-width: 5.25rem;
    padding: 0 0.35rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #374151;
    background: #f3f4f6;
    border-right: 1px solid #c4c4c4;
    min-height: 2.75rem;
  }
  .seller-portal-body .seller-customer-form-page .seller-phone-prefix img {
    display: inline-block;
    width: 20px;
    height: 15px;
    object-fit: cover;
    vertical-align: middle;
    border: 1px solid #ddd;
    border-radius: 2px;
    flex-shrink: 0;
  }
  .seller-portal-body .seller-customer-form-page .seller-phone-prefix .seller-phone-prefix-code {
    font-weight: 700;
    white-space: nowrap;
  }
  .seller-portal-body .seller-customer-form-page .seller-phone-prefix .seller-phone-prefix-empty {
    font-size: 0.75rem;
    color: #6b7280;
    font-weight: 400;
  }
  .seller-portal-body .seller-customer-form-page .seller-phone-input {
    border: none !important;
    border-radius: 0 !important;
    flex: 1;
    min-width: 0;
  }
  .seller-portal-body .seller-customer-form-page .seller-customer-sellers-multi-wrap .select2-container {
    width: 100% !important;
    max-width: 100%;
  }
  .seller-portal-body .seller-customer-form-page .seller-customer-sellers-multi-wrap .select2-selection--multiple {
    min-height: 2.75rem;
    padding: 0.2rem 0.35rem;
    border: 1px solid #c4c4c4;
    border-radius: 4px;
    background: #fff;
  }
  .seller-portal-body .seller-customer-form-page .seller-customer-sellers-multi-wrap .select2-selection__choice {
    border-radius: 4px;
    border: 1px solid #d1d5db;
    background: #f3f4f6;
    color: #111827;
    font-weight: 600;
    font-size: 0.8125rem;
  }
  .seller-portal-body .seller-customer-form-page .seller-customer-sellers-multi-wrap .select2-search__field {
    margin-top: 0.15rem;
    font-size: 0.875rem;
  }
  .seller-portal-body .seller-customer-form-page .seller-customer-checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-top: 1rem;
    font-size: 0.875rem;
    font-weight: 700;
    color: #000000;
    cursor: pointer;
  }
  .seller-portal-body .seller-customer-form-page .seller-customer-checkbox-label input {
    width: auto;
    margin: 0;
  }
  .seller-portal-body .seller-customer-form-page .seller-customer-modal-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    align-items: center;
    justify-content: flex-end;
    margin-top: 1.75rem;
    padding-top: 1.25rem;
    border-top: 1px solid #e5e7eb;
  }
  .seller-portal-body .seller-customer-form-page .seller-customer-form-textarea {
    min-height: 6.5rem;
    resize: vertical;
  }
  .seller-portal-body .seller-customer-form-page .seller-customer-country-select + .select2-container {
    width: 100% !important;
  }
  .seller-portal-body .seller-customer-form-page .seller-customer-country-select + .select2-container .select2-selection--single {
    height: 2.75rem;
    border: 1px solid #c4c4c4;
    border-radius: 4px;
    background: #fff;
    display: flex;
    align-items: center;
  }
  .seller-portal-body .seller-customer-form-page .seller-customer-country-select + .select2-container .select2-selection__rendered {
    height: auto;
    line-height: 1.25rem !important;
    padding-left: 0.85rem;
    padding-right: 2rem;
    color: #111827;
    text-decoration: none !important;
    width: 100%;
  }
  .seller-portal-body .seller-customer-form-page .seller-customer-country-select + .select2-container .select2-selection__placeholder {
    color: #6b7280;
    display: inline-block;
    line-height: 1.25rem !important;
    margin: 0;
    text-decoration: none !important;
  }
  .seller-portal-body .seller-customer-form-page .seller-customer-country-select + .select2-container .select2-selection__arrow {
    height: 2.75rem;
    right: 6px;
  }
  /* Misma estructura que company Select2: span flex + img + texto del país (lista desplegable) */
  .seller-portal-body .select2-container .select2-results__option .seller-country-s2-line {
    display: inline-flex;
    align-items: center;
  }
  .seller-portal-body .select2-container .select2-results__option .seller-country-s2-line > img {
    display: inline-block;
    width: 20px;
    height: 15px;
    object-fit: cover;
    border: 1px solid #ddd;
    border-radius: 2px;
    margin-right: 8px;
    flex-shrink: 0;
  }
</style>
<?php
$t = is_array($pageTexts ?? null) ? $pageTexts : [];
$e = static function (?string $s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
};
$mode = trim((string) ($seller_customer_form_page_mode ?? 'create'));
$backUrl = trim((string) ($seller_customer_form_back_url ?? site_url('sellers/customer')));
$tForm = $t;
if ($mode === 'edit' && trim((string) ($tForm['customer_update_submit'] ?? '')) !== '') {
    $tForm['customer_create_submit'] = (string) $tForm['customer_update_submit'];
}
$formActionDefault = $mode === 'edit' ? 'update' : 'create';
$seedJson = json_encode($seller_customer_form_page_seed ?? null, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
$modeJson = json_encode($mode, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
$sellersMultiPlaceholderJson = json_encode(
    trim((string) ($tForm['customer_form_sellers_multi_placeholder'] ?? $tForm['customer_form_select'] ?? '')),
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
);
$customerFormCountryFlagsUrlJson = json_encode(
    rtrim((string) base_url(), '/') . '/images/flags/',
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
);
$countrySelectPlaceholderJson = json_encode(
    trim((string) ($tForm['customer_form_select_country'] ?? $tForm['customer_form_select'] ?? '')),
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
);
$sellerDefaultCountryIdJson = json_encode(
    (int) ($seller_create_default_country_id ?? 0),
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
);
?>
        <section class="portal-welcome-card seller-mgmt-card" aria-labelledby="seller-customer-form-heading">
          <div class="seller-customer-form-header-row">
            <h2 id="seller-customer-form-heading" class="seller-mgmt-page-title"><?= $e(trim((string) ($portal_page_title ?? ''))) ?></h2>
            <a href="<?= $e($backUrl !== '' ? $backUrl : site_url('sellers/customer')) ?>" class="btn-portal-outline seller-customer-form-back seller-customer-form-back--trailing">
              <i class="mdi mdi-arrow-left" aria-hidden="true"></i>
              <span><?= $e(trim((string) ($t['customer_form_back_to_list'] ?? ''))) ?></span>
            </a>
          </div>
          <div class="seller-customer-form-page">
          <?php
          $this->load->view('seller/partials/customer_create_form', [
              't' => $tForm,
              'e' => $e,
              'form_terms' => is_array($seller_create_terms ?? null) ? $seller_create_terms : [],
              'form_sellers' => is_array($seller_create_sellers ?? null) ? $seller_create_sellers : [],
              'form_countries' => is_array($seller_create_countries ?? null) ? $seller_create_countries : [],
              'form_groups' => is_array($seller_customer_groups ?? null) ? $seller_customer_groups : [],
              'current_seller_id' => (int) ($seller_create_current_seller_id ?? 0),
              'current_seller_name' => (string) ($seller_create_current_seller_name ?? ''),
              'default_term_id' => (int) ($seller_create_default_term_id ?? 0),
              'default_group_id' => (int) ($seller_create_default_group_id ?? 0),
              'default_country_id' => (int) ($seller_create_default_country_id ?? 0),
              'seller_customer_form_page_mode' => $mode,
              'seller_customer_form_action_default' => $formActionDefault,
              'seller_customer_form_back_url' => $backUrl,
          ]);
          ?>
          </div>
        </section>
        <script src="<?= htmlspecialchars(base_url('companyasset/js/jquery-3.6.0.min.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
        <script src="<?= htmlspecialchars(base_url('companyasset/vendors/inputmask/jquery.inputmask.min.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
        <script src="<?= htmlspecialchars(base_url('companyasset/js/select2.min.js'), ENT_QUOTES, 'UTF-8') ?>"></script>
        <script>
        (function () {
          var mode = <?= $modeJson ?>;
          var seed = <?= $seedJson ?>;
          var sellersMultiPlaceholder = <?= $sellersMultiPlaceholderJson ?>;
          /* Misma base que company/customer: base_url() (subcarpeta del CI si existe). host+/images/ rompe cuando la app no está en la raíz. */
          var countryFlagsBaseUrl = <?= $customerFormCountryFlagsUrlJson ?>;
          if (typeof window.console !== 'undefined' && typeof window.console.log === 'function') {
            window.console.log('[seller portal] countryFlagsBaseUrl', countryFlagsBaseUrl);
          }
          var selPlaceholder = <?= $countrySelectPlaceholderJson ?>;
          var countrySelectPlaceholder = <?= $countrySelectPlaceholderJson ?>;
          var companyDefaultCountryId = <?= $sellerDefaultCountryIdJson ?>;
          var form = document.getElementById('sellerCustomerCreateForm');
          if (!form) { return; }

          var actionEl = document.getElementById('seller_customer_action');
          if (actionEl && mode === 'edit') {
            actionEl.value = 'update';
          }

          function setVal(id, v) {
            var el = document.getElementById(id);
            if (!el || v === undefined || v === null) { return; }
            if (el.type === 'checkbox') {
              el.checked = v === true || v === 1 || v === '1' || v === 'on';
              return;
            }
            if (el.tagName === 'SELECT' && el.multiple && Array.isArray(v)) {
              var allowed = {};
              v.forEach(function (x) { allowed[String(x)] = true; });
              Array.prototype.forEach.call(el.options, function (opt) {
                opt.selected = allowed.hasOwnProperty(opt.value);
              });
              return;
            }
            el.value = String(v);
          }

          function applySeed(s) {
            if (!s || typeof s !== 'object') { return; }
            setVal('seller_form_customer_id', s.customer_id);
            setVal('seller_form_name', s.name);
            setVal('seller_form_business_name', s.business_name);
            setVal('seller_form_email', s.email);
            setVal('seller_form_phone', s.phone);
            setVal('seller_form_cell', s.cell_phone);
            setVal('seller_form_tax_id', s.tax_id);
            setVal('seller_form_id_kor', s.id_kor);
            setVal('seller_form_erp_code', s.erp_code);
            setVal('seller_form_discount', s.discount);
            setVal('seller_form_catalog_emails', s.catalog_emails);
            setVal('seller_form_assign_catalog', s.seller_assign_catalog);
            setVal('seller_form_term_id', s.term_id);
            setVal('seller_form_group_id', s.group_id);
            setVal('seller_form_commercial_address', s.commercial_address);
            setVal('seller_form_commercial_delivery_line', s.commercial_delivery_address);
            setVal('seller_form_commercial_country_id', s.commercial_country_id);
            setVal('seller_form_commercial_state', s.commercial_state);
            setVal('seller_form_commercial_city', s.commercial_city);
            setVal('seller_form_commercial_zip', s.commercial_zip_code);
            setVal('seller_form_commercial_zone', s.commercial_zone);
            setVal('seller_form_dispatch_delivery_full', s.dispatch_delivery_address);
            setVal('seller_form_dispatch_address', s.dispatch_address);
            setVal('seller_form_dispatch_country_id', s.dispatch_country_id);
            setVal('seller_form_dispatch_state', s.dispatch_state);
            setVal('seller_form_dispatch_city', s.dispatch_city);
            setVal('seller_form_dispatch_zip', s.dispatch_zip_code);
            setVal('seller_form_dispatch_zone', s.dispatch_zone);
            setVal('seller_form_notes', s.notes);
            setVal('seller_form_delivery_notes', s.delivery_notes);
            setVal('seller_same_as_commercial', s.same_as_commercial);
            if (Array.isArray(s.seller_ids)) {
              setVal('seller_form_sellers', s.seller_ids);
            }
          }

          function countryFlagHtml(sortname) {
            var sn = String(sortname || '').toLowerCase().replace(/[^a-z-]/g, '');
            if (sn.length < 2) {
              return '';
            }
            var src = countryFlagsBaseUrl + sn + '.png';
            return '<img src="' + src.replace(/&/g, '&amp;').replace(/"/g, '&quot;') + '" alt="" style="display:inline-block;width:20px;height:15px;object-fit:cover;vertical-align:middle;border:1px solid #ddd;border-radius:2px;margin-right:8px" onerror="this.style.display=\'none\'" />';
          }

          function optionSortname($opt) {
            return String($opt.data('sortname') || '').toLowerCase().replace(/[^a-z-]/g, '');
          }

          function logCountryListWithFlagImagesTable() {
            try {
              var $ = window.jQuery;
              var c = window.console;
              if (!$ || typeof c === 'undefined') {
                return;
              }
              var seen = {};
              var rows = [];
              ['#seller_form_commercial_country_id', '#seller_form_dispatch_country_id'].forEach(function (selector) {
                var $select = $(selector);
                if (!$select.length) {
                  return;
                }
                $select.find('option').each(function () {
                  var value = String($(this).attr('value') || '').trim();
                  if (!value || seen[value]) {
                    return;
                  }
                  seen[value] = true;
                  var sortname = optionSortname($(this));
                  var image = sortname.length >= 2 ? (countryFlagsBaseUrl + sortname + '.png') : '';
                  rows.push({
                    country_id: value,
                    country_name: $.trim($(this).text() || ''),
                    sortname: sortname,
                    flag_image_url: image
                  });
                });
              });
              var label = '[seller portal] Customer ' + String(mode || '') + ' — lista de paises con URL de bandera';
              if (typeof c.log === 'function') {
                c.log(label + ' (n=' + rows.length + ', base=' + String(countryFlagsBaseUrl || '') + ')', rows);
              }
              if (typeof c.groupCollapsed === 'function' && typeof c.groupEnd === 'function') {
                c.groupCollapsed(label + ' (tabla)');
                if (typeof c.table === 'function') {
                  c.table(rows);
                }
                c.groupEnd();
              } else if (typeof c.table === 'function') {
                c.table(rows);
              }
            } catch (err) {
              if (typeof window.console !== 'undefined' && typeof window.console.warn === 'function') {
                window.console.warn('[seller portal] logCountryListWithFlagImagesTable', err);
              }
            }
          }

          function initSellerCountrySelect(selector) {
            var $ = window.jQuery;
            if (!$ || typeof $.fn.select2 !== 'function') {
              return false;
            }
            var $el = $(selector);
            if (!$el.length) {
              return false;
            }
            if ($el.hasClass('select2-hidden-accessible')) {
              $el.select2('destroy');
            }
            $el.select2({
              placeholder: countrySelectPlaceholder || selPlaceholder,
              allowClear: true,
              width: '100%',
              minimumResultsForSearch: 0,
              dropdownParent: $(document.body),
              matcher: function (params, data) {
                if ($.trim(params.term || '') === '') {
                  return data;
                }
                if (typeof data.text === 'undefined' || data.children) {
                  return null;
                }
                var term = params.term.toLowerCase();
                var raw = (typeof data.text === 'string') ? data.text : '';
                var txt = $('<div/>').html(raw).text().toLowerCase();
                var el = data.element;
                var sortname = el ? String($(el).data('sortname') || '').toLowerCase() : '';
                if (txt.indexOf(term) > -1 || sortname.indexOf(term) > -1) {
                  return data;
                }
                return null;
              },
              templateResult: function (country) {
                if (!country.id) {
                  return country.text;
                }
                var $opt = $(country.element);
                var sortname = $opt.data('sortname');
                var labelUp = $('<span/>').text(($.trim($opt.text()) || '').toUpperCase()).html();
                var fh = countryFlagHtml(sortname);
                if (fh) {
                  return $('<span class="d-inline-flex align-items-center"/>').html(fh + labelUp);
                }
                return $('<span/>').html(labelUp);
              },
              templateSelection: function (country) {
                if (!country.id) {
                  return country.text;
                }
                var $opt = $(country.element);
                var sortname = $opt.data('sortname');
                var labelUp = $('<span/>').text(($.trim($opt.text()) || '').toUpperCase()).html();
                var fh = countryFlagHtml(sortname);
                if (fh) {
                  return $('<span class="d-inline-flex align-items-center"/>').html(fh + labelUp);
                }
                return $('<span/>').html(labelUp);
              },
              escapeMarkup: function (m) {
                return m;
              }
            });
            return true;
          }

          function tryInitSellerCountrySelects() {
            if (!initSellerCountrySelect('#seller_form_commercial_country_id')) {
              return false;
            }
            initSellerCountrySelect('#seller_form_dispatch_country_id');
            return true;
          }

          function selectedOptionData(sel) {
            if (!sel || sel.selectedIndex < 0) { return null; }
            var opt = sel.options[sel.selectedIndex];
            if (!opt || String(opt.value).trim() === '') { return null; }
            return {
              phonecode: String(opt.getAttribute('data-phonecode') || '').trim(),
              countryName: String(opt.getAttribute('data-country-name') || '').trim()
            };
          }

          /** Igual que company/customer: bandera + prefijo en teléfono y móvil (país comercial). */
          function updateSellerPhonePrefix() {
            var $ = window.jQuery;
            if (!$) { return; }
            var cid = $('#seller_form_commercial_country_id').val();
            if (!cid && companyDefaultCountryId) {
              cid = String(companyDefaultCountryId);
            }
            var $opt = cid ? $('#seller_form_commercial_country_id option[value="' + cid + '"]') : $();
            if (!$opt.length) {
              $('#seller_phone_country_prefix, #seller_cell_country_prefix').html('<span class="seller-phone-prefix-empty" aria-hidden="true">\u2014</span>');
              return;
            }
            var sortname = String($opt.data('sortname') || '').toLowerCase();
            var pc = parseInt($opt.data('phonecode'), 10);
            if (isNaN(pc)) {
              pc = 0;
            }
            var flag = countryFlagHtml(sortname);
            var html = flag + '<span class="seller-phone-prefix-code">+' + pc + '</span>';
            $('#seller_phone_country_prefix, #seller_cell_country_prefix').html(html);
          }

          /** Misma regla que company/customer: data-phonemask o máscara US si phonecode === 1. */
          function updateSellerPhoneInputMasks() {
            if (mode === 'view') {
              return true;
            }
            if (typeof Inputmask === 'undefined') {
              return false;
            }
            var $ = window.jQuery;
            if (!$) { return false; }
            var cid = $('#seller_form_commercial_country_id').val();
            if (!cid && companyDefaultCountryId) {
              cid = String(companyDefaultCountryId);
            }
            var $opt = cid ? $('#seller_form_commercial_country_id option[value="' + cid + '"]') : $();
            var maskPh = '';
            if ($opt.length) {
              maskPh = ($opt.attr('data-phonemask') || '').trim();
              if (!maskPh) {
                var pcMask = parseInt($opt.attr('data-phonecode'), 10);
                if (!isNaN(pcMask) && pcMask === 1) {
                  maskPh = '(###) ###-####';
                }
              }
            }
            var phoneEl = document.getElementById('seller_form_phone');
            var cellEl = document.getElementById('seller_form_cell');
            if (!phoneEl || !cellEl) {
              return true;
            }
            var rawPhone = (phoneEl.value || '').replace(/\D/g, '');
            var rawCell = (cellEl.value || '').replace(/\D/g, '');
            try { Inputmask.remove(phoneEl); } catch (e1) {}
            try { Inputmask.remove(cellEl); } catch (e2) {}
            if (!maskPh) {
              phoneEl.value = rawPhone;
              cellEl.value = rawCell;
              return true;
            }
            var imMask = maskPh.replace(/#/g, '9');
            var opts = {
              mask: imMask,
              placeholder: '_',
              showMaskOnHover: false,
              autoUnmask: true,
              clearIncomplete: false
            };
            new Inputmask(opts).mask(phoneEl);
            new Inputmask(opts).mask(cellEl);
            if (phoneEl.inputmask && typeof phoneEl.inputmask.setValue === 'function') {
              phoneEl.inputmask.setValue(rawPhone);
            } else {
              $(phoneEl).val(rawPhone).trigger('input');
            }
            if (cellEl.inputmask && typeof cellEl.inputmask.setValue === 'function') {
              cellEl.inputmask.setValue(rawCell);
            } else {
              $(cellEl).val(rawCell).trigger('input');
            }
            return true;
          }

          function runSellerPhoneMasksAfterCountrySelects() {
            updateSellerPhonePrefix();
            if (!updateSellerPhoneInputMasks()) {
              var masAttempts = 0;
              var masMax = 60;
              var imv = window.setInterval(function () {
                if (updateSellerPhoneInputMasks() || ++masAttempts >= masMax) {
                  window.clearInterval(imv);
                }
              }, 100);
            }
          }

          function joinNonEmpty(parts) {
            var out = [];
            parts.forEach(function (p) {
              var s = String(p == null ? '' : p).trim();
              if (s !== '') { out.push(s); }
            });
            return out.join(', ');
          }

          function commercialCountryName() {
            var sel = document.getElementById('seller_form_commercial_country_id');
            var d = selectedOptionData(sel);
            return d && d.countryName ? d.countryName : '';
          }

          function dispatchCountryName() {
            var sel = document.getElementById('seller_form_dispatch_country_id');
            var d = selectedOptionData(sel);
            return d && d.countryName ? d.countryName : '';
          }

          function buildCommercialDeliveryLine() {
            var out = document.getElementById('seller_form_commercial_delivery_line');
            if (!out) { return; }
            var a = document.getElementById('seller_form_commercial_address');
            var st = document.getElementById('seller_form_commercial_state');
            var city = document.getElementById('seller_form_commercial_city');
            var zone = document.getElementById('seller_form_commercial_zone');
            var zip = document.getElementById('seller_form_commercial_zip');
            out.value = joinNonEmpty([
              a ? a.value : '',
              st ? st.value : '',
              city ? city.value : '',
              commercialCountryName(),
              zip ? zip.value : '',
              zone ? zone.value : ''
            ]);
          }

          function buildDispatchDeliveryFull() {
            var out = document.getElementById('seller_form_dispatch_delivery_full');
            if (!out) { return; }
            var a = document.getElementById('seller_form_dispatch_address');
            var st = document.getElementById('seller_form_dispatch_state');
            var city = document.getElementById('seller_form_dispatch_city');
            var zone = document.getElementById('seller_form_dispatch_zone');
            var zip = document.getElementById('seller_form_dispatch_zip');
            out.value = joinNonEmpty([
              a ? a.value : '',
              st ? st.value : '',
              city ? city.value : '',
              dispatchCountryName(),
              zip ? zip.value : '',
              zone ? zone.value : ''
            ]);
          }

          function copyCommercialToDispatch() {
            var pairs = [
              ['seller_form_commercial_address', 'seller_form_dispatch_address'],
              ['seller_form_commercial_country_id', 'seller_form_dispatch_country_id'],
              ['seller_form_commercial_state', 'seller_form_dispatch_state'],
              ['seller_form_commercial_city', 'seller_form_dispatch_city'],
              ['seller_form_commercial_zip', 'seller_form_dispatch_zip'],
              ['seller_form_commercial_zone', 'seller_form_dispatch_zone']
            ];
            pairs.forEach(function (pr) {
              var src = document.getElementById(pr[0]);
              var dst = document.getElementById(pr[1]);
              if (!src || !dst) { return; }
              dst.value = src.value;
            });
            if (window.jQuery && window.jQuery('#seller_form_dispatch_country_id').length) {
              window.jQuery('#seller_form_dispatch_country_id').trigger('change.select2');
            }
          }

          function onSameAsCommercialChange() {
            var cb = document.getElementById('seller_same_as_commercial');
            if (!cb || !cb.checked) {
              buildDispatchDeliveryFull();
              return;
            }
            copyCommercialToDispatch();
            buildDispatchDeliveryFull();
          }

          function bindAddressAndPhoneAutofill() {
            if (mode === 'view') { return; }
            var commIds = [
              'seller_form_commercial_address',
              'seller_form_commercial_state',
              'seller_form_commercial_city',
              'seller_form_commercial_zone',
              'seller_form_commercial_zip'
            ];
            commIds.forEach(function (id) {
              var el = document.getElementById(id);
              if (!el) { return; }
              var handler = function () {
                buildCommercialDeliveryLine();
                var same = document.getElementById('seller_same_as_commercial');
                if (same && same.checked) {
                  copyCommercialToDispatch();
                  buildDispatchDeliveryFull();
                }
              };
              el.addEventListener('input', handler);
              el.addEventListener('change', handler);
            });
            var commCountry = document.getElementById('seller_form_commercial_country_id');
            if (commCountry) {
              commCountry.addEventListener('change', function () {
                runSellerPhoneMasksAfterCountrySelects();
                buildCommercialDeliveryLine();
                var same = document.getElementById('seller_same_as_commercial');
                if (same && same.checked) {
                  copyCommercialToDispatch();
                  buildDispatchDeliveryFull();
                }
              });
            }
            var dispIds = [
              'seller_form_dispatch_address',
              'seller_form_dispatch_state',
              'seller_form_dispatch_city',
              'seller_form_dispatch_zone',
              'seller_form_dispatch_zip'
            ];
            dispIds.forEach(function (id) {
              var el = document.getElementById(id);
              if (!el) { return; }
              el.addEventListener('input', function () { buildDispatchDeliveryFull(); });
              el.addEventListener('change', function () { buildDispatchDeliveryFull(); });
            });
            var dispCountry = document.getElementById('seller_form_dispatch_country_id');
            if (dispCountry) {
              dispCountry.addEventListener('change', function () { buildDispatchDeliveryFull(); });
            }
            var sameCb = document.getElementById('seller_same_as_commercial');
            if (sameCb) {
              sameCb.addEventListener('change', onSameAsCommercialChange);
            }
          }

          if (mode === 'edit' || mode === 'view') {
            applySeed(seed);
          }

          function logCountryFlagRowsOnDropdownOpen(selectEl) {
            try {
              var c = window.console;
              if (typeof c === 'undefined' || !selectEl || !selectEl.options) {
                return;
              }
              var rows = [];
              for (var i = 0; i < selectEl.options.length; i += 1) {
                var opt = selectEl.options[i];
                var id = String(opt && opt.value ? opt.value : '').trim();
                if (!id) {
                  continue;
                }
                var $opt = window.jQuery(opt);
                var sortname = optionSortname($opt);
                var src = sortname.length >= 2 ? (countryFlagsBaseUrl + sortname + '.png') : '';
                rows.push({
                  country_id: id,
                  country_name: String(opt.text || '').trim(),
                  sortname: sortname,
                  flag_src: src
                });
              }
              var title = '[seller portal] dropdown countries ' + (selectEl.id || '(sin-id)');
              if (typeof c.log === 'function') {
                c.log(title + ' (n=' + rows.length + ')', rows);
              }
              if (typeof c.table === 'function') {
                c.table(rows);
              }
            } catch (err) {
              if (typeof window.console !== 'undefined' && typeof window.console.warn === 'function') {
                window.console.warn('[seller portal] logCountryFlagRowsOnDropdownOpen', err);
              }
            }
          }

          function registerCountrySelect2OpenFocus() {
            if (!window.jQuery || registerCountrySelect2OpenFocus._done) {
              return;
            }
            registerCountrySelect2OpenFocus._done = true;
            window.jQuery(document).on('select2:open', '#seller_form_commercial_country_id, #seller_form_dispatch_country_id', function () {
              logCountryFlagRowsOnDropdownOpen(this);
              window.setTimeout(function () {
                var $f = window.jQuery('.select2-container--open .select2-search__field');
                if ($f.length && $f[0]) {
                  $f[0].focus();
                }
              }, 0);
            });
          }

          function initSellerAssignedSelect2() {
            var $ = window.jQuery;
            if (!$ || typeof $.fn.select2 !== 'function') {
              return false;
            }
            var $el = $('#seller_form_sellers');
            if (!$el.length) {
              return false;
            }
            if ($el.hasClass('select2-hidden-accessible')) {
              $el.select2('destroy');
            }
            $el.select2({
              placeholder: sellersMultiPlaceholder,
              allowClear: true,
              width: '100%',
              minimumResultsForSearch: 0,
              templateResult: function (opt) {
                if (!opt.id) {
                  return opt.text;
                }
                var txt = ($.trim($(opt.element).text()) || '').toUpperCase();
                return $('<span/>').text(txt);
              },
              templateSelection: function (opt) {
                if (!opt.id) {
                  return opt.text;
                }
                var txt = ($.trim($(opt.element).text()) || '').toUpperCase();
                return $('<span/>').text(txt);
              }
            });
            return true;
          }
          var attemptsS2 = 0;
          var tryInitSellerSelect2 = function () {
            if (initSellerAssignedSelect2() || attemptsS2 >= 50) {
              return;
            }
            attemptsS2 += 1;
            window.setTimeout(tryInitSellerSelect2, 100);
          };

          function runSellerFormInitAfterCountrySelects() {
            logCountryListWithFlagImagesTable();
            registerCountrySelect2OpenFocus();
            runSellerPhoneMasksAfterCountrySelects();
            if (window.jQuery) {
              window.jQuery(document)
                .off('change.sellerPhCountry', '#seller_form_commercial_country_id')
                .on('change.sellerPhCountry', '#seller_form_commercial_country_id', function () {
                  runSellerPhoneMasksAfterCountrySelects();
                });
            }
            buildCommercialDeliveryLine();
            var sameInit = document.getElementById('seller_same_as_commercial');
            if (sameInit && sameInit.checked) {
              if (mode !== 'view') {
                copyCommercialToDispatch();
              }
            }
            buildDispatchDeliveryFull();
            bindAddressAndPhoneAutofill();
            tryInitSellerSelect2();
          }

          var attemptsCtry = 0;
          var tryInitCountryLoop = function () {
            if (tryInitSellerCountrySelects()) {
              runSellerFormInitAfterCountrySelects();
              return;
            }
            if (attemptsCtry >= 50) {
              runSellerFormInitAfterCountrySelects();
              return;
            }
            attemptsCtry += 1;
            window.setTimeout(tryInitCountryLoop, 100);
          };
          tryInitCountryLoop();

          form.addEventListener('submit', function () {
            var notesEl = document.getElementById('seller_form_notes');
            var deliveryNotesEl = document.getElementById('seller_form_delivery_notes');
            if (notesEl && deliveryNotesEl) {
              deliveryNotesEl.value = notesEl.value;
            }
          });

          function applyViewModeReadOnly() {
            if (mode !== 'view') {
              return;
            }
            var submitBtn = document.getElementById('sellerCustomerFormSubmitBtn');
            if (submitBtn) {
              submitBtn.style.display = 'none';
            }
            form.querySelectorAll('input, select, textarea, button').forEach(function (node) {
              if (node.type === 'hidden') { return; }
              if (node.id === 'seller_customer_action') { return; }
              node.disabled = true;
            });
            if (window.jQuery) {
              ['#seller_form_sellers', '#seller_form_commercial_country_id', '#seller_form_dispatch_country_id'].forEach(function (sel) {
                var $n = window.jQuery(sel);
                if ($n.length && $n.data('select2')) {
                  $n.prop('disabled', true).trigger('change.select2');
                }
              });
            }
          }
          if (mode === 'view') {
            window.setTimeout(applyViewModeReadOnly, 180);
          }
        })();
        </script>
<?php $this->load->view('seller/partials/portal_foot'); ?>

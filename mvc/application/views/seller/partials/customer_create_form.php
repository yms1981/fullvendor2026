<?php
defined('BASEPATH') or exit('No direct script access allowed');
/**
 * @var array<string, mixed> $t
 * @var callable $e
 * @var list<array{term_id: int, name: string}> $form_terms
 * @var list<array{id: int, name: string}> $form_sellers
 * @var list<array{id: int, name: string}> $form_countries
 * @var list<array{group_id: int, name: string}> $form_groups
 * @var int $current_seller_id
 * @var string $current_seller_name
 * @var int $default_term_id
 * @var int $default_group_id
 * @var int $default_country_id
 * @var string $seller_customer_form_page_mode
 */
$form_terms = is_array($form_terms ?? null) ? $form_terms : [];
$form_sellers = is_array($form_sellers ?? null) ? $form_sellers : [];
$form_countries = is_array($form_countries ?? null) ? $form_countries : [];
$form_groups = is_array($form_groups ?? null) ? $form_groups : [];
$current_seller_id = (int) ($current_seller_id ?? 0);
$current_seller_name = (string) ($current_seller_name ?? '');
$default_term_id = (int) ($default_term_id ?? 0);
$default_group_id = (int) ($default_group_id ?? 0);
$default_country_id = (int) ($default_country_id ?? 0);
$form_page_mode = (string) ($seller_customer_form_page_mode ?? 'create');
$erpIdGroupLabel = trim((string) ($t['customer_form_erp_id'] ?? ''));
$req = ' <span class="seller-customer-req" aria-hidden="true">*</span>';
$formActionDefault = (string) ($seller_customer_form_action_default ?? 'create');
$submitCreateLabel = trim((string) ($t['customer_create_submit'] ?? ''));
$countryOptionAttrs = static function (array $ct) use ($e): string {
    /* Datos de país como company/customers/Add (API country + props); claves extra solo si existieran en el array */
    $sortRaw = (string) (
        $ct['sortname']
        ?? $ct['sortName']
        ?? $ct['sort_name']
        ?? $ct['alpha2']
        ?? $ct['iso']
        ?? $ct['iso2']
        ?? $ct['iso_3166_1_alpha2']
        ?? $ct['code']
        ?? $ct['country_code']
        ?? ''
    );
    $sn = strtolower(preg_replace('/[^a-z\-]/', '', $sortRaw));
    $cname = trim((string) ($ct['name'] ?? ''));
    $pcRaw = $ct['phonecode'] ?? '';
    $pc = is_numeric($pcRaw) ? (int) $pcRaw : (int) preg_replace('/\D+/', '', (string) $pcRaw);
    $mask = trim((string) ($ct['phonemask'] ?? ''));
    $iso = trim((string) ($ct['iso'] ?? ''));
    $iso2 = trim((string) ($ct['iso2'] ?? ''));
    $iso3166 = trim((string) ($ct['iso_3166_1_alpha2'] ?? ''));
    $code = trim((string) ($ct['code'] ?? ''));
    $countryCode = trim((string) ($ct['country_code'] ?? ''));
    $alpha2 = trim((string) ($ct['alpha2'] ?? ''));
    /* Igual que company/customer: base_url() + /images/flags/{sn}.png */
    $flagsBase = rtrim((string) base_url(), '/') . '/images/flags/';
    $flagAbsUrl = (strlen($sn) >= 2) ? ($flagsBase . $sn . '.png') : '';

    return ' data-sortname="' . $e($sn) . '"'
        . ' data-flag-url="' . $e($flagAbsUrl) . '"'
        . ' data-iso="' . $e(strtolower($iso)) . '"'
        . ' data-iso2="' . $e(strtolower($iso2)) . '"'
        . ' data-iso_3166_1_alpha2="' . $e(strtolower($iso3166)) . '"'
        . ' data-code="' . $e(strtolower($code)) . '"'
        . ' data-country_code="' . $e(strtolower($countryCode)) . '"'
        . ' data-alpha2="' . $e(strtolower($alpha2)) . '"'
        . ' data-country-name="' . $e($cname) . '"'
        . ' data-phonecode="' . $e((string) $pc) . '"'
        . ' data-phonemask="' . $e($mask) . '"';
};

/** Etiqueta de país (nombre); prefijo telefónico vía data-phonecode en customer_form.php */
$sellerPortalCountryOptionLabel = static function (array $ct) use ($e): string {
    $name = trim((string) ($ct['name'] ?? ''));
    if ($name !== '') {
        return $e($name);
    }
    $sn = strtolower(preg_replace('/[^a-z\-]/', '', (string) ($ct['sortname'] ?? '')));

    return $e(strlen($sn) >= 2 ? strtoupper(substr($sn, 0, 2)) : '');
};
?>
<form id="sellerCustomerCreateForm" method="post" action="<?= $e(site_url('sellers/customer')) ?>" class="seller-customer-create-form">
  <input type="hidden" name="seller_customer_action" id="seller_customer_action" value="<?= $e($formActionDefault) ?>">
  <input type="hidden" name="customer_id" id="seller_form_customer_id" value="0">
  <div class="seller-customer-form-section seller-customer-form-section--customer-fields">
    <div class="seller-cf-section-header seller-cf-section-header--customer">
      <h4 class="seller-customer-form-section-title seller-customer-form-section-title--customer"><?= $e($t['customer_form_section_customer_data'] ?? '') ?></h4>
      <div class="seller-cf-customer-erp-bar" role="group" aria-label="<?= $e($t['customer_form_erp_id'] ?? '') ?>">
        <div class="seller-cf-erp-inline">
          <span id="seller_erp_fields_label" class="seller-cf-erp-group-label"><?= $e($erpIdGroupLabel) ?><?= $erpIdGroupLabel !== '' && substr($erpIdGroupLabel, -1) !== ':' ? ':' : '' ?></span>
          <input id="seller_form_erp_code" type="text" name="erp_code" maxlength="50" autocomplete="off" class="seller-cf-erp-inline-txt" aria-labelledby="seller_erp_fields_label" aria-label="<?= $e(trim((string) ($t['customer_form_erp_code'] ?? ''))) ?>">
          <input id="seller_form_id_kor" type="number" name="id_kor" value="0" min="0" step="1" inputmode="numeric" class="seller-cf-erp-inline-num" aria-labelledby="seller_erp_fields_label" aria-label="<?= $e($erpIdGroupLabel) ?>">
        </div>
      </div>
    </div>
    <div class="seller-cf-row seller-cf-row--customer-1">
      <div>
        <label for="seller_form_name"><?= $e($t['customer_form_contact_name'] ?? '') ?></label>
        <input id="seller_form_name" type="text" name="name" maxlength="255" autocomplete="name">
      </div>
      <div>
        <label for="seller_form_business_name"><?= $e($t['customer_form_business_name'] ?? '') ?><?= $req ?></label>
        <input id="seller_form_business_name" type="text" name="business_name" maxlength="255" required autocomplete="organization">
      </div>
      <div>
        <label for="seller_form_tax_id"><?= $e($t['customer_form_tax_id'] ?? '') ?></label>
        <input id="seller_form_tax_id" type="text" name="tax_id" maxlength="40" autocomplete="off">
      </div>
      <div>
        <label for="seller_form_discount"><?= $e($t['customer_form_discount'] ?? '') ?></label>
        <input id="seller_form_discount" type="number" name="discount" value="0" min="0" step="0.01" inputmode="decimal">
      </div>
    </div>
    <div class="seller-cf-row seller-cf-row--customer-2">
      <div>
        <label for="seller_form_email"><?= $e($t['customer_form_email'] ?? '') ?><?= $req ?></label>
        <input id="seller_form_email" type="email" name="email" maxlength="255" required autocomplete="email">
      </div>
      <div>
        <label for="seller_form_phone"><?= $e($t['customer_form_phone'] ?? '') ?></label>
        <div class="seller-phone-field-rec">
          <span class="seller-phone-prefix" id="seller_phone_country_prefix" aria-hidden="true"></span>
          <input id="seller_form_phone" class="seller-phone-input" type="tel" name="phone" maxlength="40" autocomplete="tel-national">
        </div>
      </div>
      <div>
        <label for="seller_form_cell"><?= $e($t['customer_form_cellphone'] ?? '') ?></label>
        <div class="seller-phone-field-rec">
          <span class="seller-phone-prefix" id="seller_cell_country_prefix" aria-hidden="true"></span>
          <input id="seller_form_cell" class="seller-phone-input" type="tel" name="cell_phone" maxlength="40" autocomplete="tel-national">
        </div>
      </div>
      <div>
        <label for="seller_form_catalog_emails"><?= $e($t['customer_form_send_catalog'] ?? '') ?></label>
        <select id="seller_form_catalog_emails" name="catalog_emails">
          <option value="0"><?= $e($t['customer_form_no'] ?? '') ?></option>
          <option value="1"><?= $e($t['customer_form_yes'] ?? '') ?></option>
        </select>
      </div>
    </div>
    <div class="seller-cf-row seller-cf-row--customer-3">
      <div class="seller-customer-sellers-multi-wrap">
        <label for="seller_form_sellers"><?= $e($t['customer_form_seller_assigned'] ?? '') ?><?= $req ?></label>
        <select id="seller_form_sellers" class="seller-customer-sellers-multi" name="sellers[]" multiple="multiple">
          <?php foreach ($form_sellers as $sv) {
              if (! is_array($sv)) {
                  continue;
              }
              $sid = (int) ($sv['id'] ?? 0);
              if ($sid <= 0) {
                  continue;
              }
              $sel = ($sid === $current_seller_id) ? ' selected' : '';
              ?>
          <option value="<?= (int) $sid ?>"<?= $sel ?>><?= $e((string) ($sv['name'] ?? '')) ?></option>
          <?php } ?>
        </select>
      </div>
      <div>
        <label for="seller_form_assign_catalog"><?= $e($t['customer_form_assign_catalog_order'] ?? '') ?></label>
        <select id="seller_form_assign_catalog" name="seller_assign_catalog">
          <option value="0"><?= $e($t['customer_form_select'] ?? '') ?></option>
          <?php foreach ($form_sellers as $sv) {
              if (! is_array($sv)) {
                  continue;
              }
              $sid = (int) ($sv['id'] ?? 0);
              if ($sid <= 0) {
                  continue;
              }
              ?>
          <option value="<?= (int) $sid ?>"><?= $e((string) ($sv['name'] ?? '')) ?></option>
          <?php } ?>
        </select>
      </div>
      <div>
        <label for="seller_form_term_id"><?= $e($t['customer_form_payment_terms'] ?? '') ?></label>
        <select id="seller_form_term_id" name="term_id">
          <?php foreach ($form_terms as $tm) {
              if (! is_array($tm)) {
                  continue;
              }
              $tid = (int) ($tm['term_id'] ?? 0);
              if ($tid <= 0) {
                  continue;
              }
              $sel = ($default_term_id > 0 && $tid === $default_term_id) ? ' selected' : '';
              ?>
          <option value="<?= (int) $tid ?>"<?= $sel ?>><?= $e(trim((string) ($tm['name'] ?? '')) !== '' ? (string) $tm['name'] : (string) $tid) ?></option>
          <?php } ?>
        </select>
      </div>
      <div>
        <label for="seller_form_group_id"><?= $e($t['customer_form_group'] ?? '') ?><?= $req ?></label>
        <select id="seller_form_group_id" name="group_id">
          <?php foreach ($form_groups as $g) {
              if (! is_array($g)) {
                  continue;
              }
              $gid = (int) ($g['group_id'] ?? 0);
              if ($gid <= 0) {
                  continue;
              }
              $sel = ($default_group_id > 0 && $gid === $default_group_id) ? ' selected' : '';
              ?>
          <option value="<?= (int) $gid ?>"<?= $sel ?>><?= $e(trim((string) ($g['name'] ?? '')) !== '' ? (string) $g['name'] : (string) $gid) ?></option>
          <?php } ?>
        </select>
      </div>
    </div>
  </div>

  <div class="seller-customer-form-section">
    <h4 class="seller-customer-form-section-title"><?= $e($t['customer_form_section_commercial'] ?? '') ?></h4>
    <div class="seller-cf-row seller-cf-row--commercial-1">
      <div>
        <label for="seller_form_commercial_address"><?= $e($t['customer_form_address'] ?? '') ?></label>
        <input id="seller_form_commercial_address" type="text" name="commercial_address" maxlength="255" data-commercial-line="1" autocomplete="street-address">
      </div>
      <div>
        <label for="seller_form_commercial_delivery_line"><?= $e($t['customer_form_delivery_address'] ?? '') ?></label>
        <input id="seller_form_commercial_delivery_line" type="text" name="commercial_delivery_address" maxlength="255" readonly class="seller-customer-summary-readonly" tabindex="0" autocomplete="off" placeholder="<?= $e(trim((string) ($t['customer_form_commercial_delivery_auto_placeholder'] ?? ''))) ?>">
      </div>
      <div>
        <label for="seller_form_commercial_country_id"><?= $e($t['customer_form_country'] ?? '') ?></label>
        <select id="seller_form_commercial_country_id" name="commercial_country_id" class="seller-customer-country-select" data-commercial-line="1">
          <option value=""><?= $e($t['customer_form_select_country'] ?? '') ?></option>
          <?php foreach ($form_countries as $ct) {
              if (! is_array($ct)) {
                  continue;
              }
              $cid = (int) ($ct['id'] ?? 0);
              if ($cid <= 0) {
                  continue;
              }
              $cAttr = $countryOptionAttrs($ct);
              $selCommercial = ($form_page_mode === 'create' && $default_country_id > 0 && $cid === $default_country_id) ? ' selected' : '';
              ?>
          <option value="<?= (int) $cid ?>"<?= $cAttr ?><?= $selCommercial ?>><?= $sellerPortalCountryOptionLabel($ct) ?></option>
          <?php } ?>
        </select>
      </div>
    </div>
    <div class="seller-cf-row seller-cf-row--4eq">
      <div>
        <label for="seller_form_commercial_state"><?= $e($t['customer_form_state'] ?? '') ?></label>
        <input id="seller_form_commercial_state" type="text" name="commercial_state" maxlength="120" data-commercial-line="1" autocomplete="off">
      </div>
      <div>
        <label for="seller_form_commercial_city"><?= $e($t['customer_form_city'] ?? '') ?></label>
        <input id="seller_form_commercial_city" type="text" name="commercial_city" maxlength="120" class="seller-customer-input-muted" data-commercial-line="1" autocomplete="off">
      </div>
      <div>
        <label for="seller_form_commercial_zip"><?= $e($t['customer_form_zip'] ?? '') ?></label>
        <input id="seller_form_commercial_zip" type="text" name="commercial_zip_code" maxlength="40" class="seller-customer-input-muted" data-commercial-line="1" autocomplete="off">
      </div>
      <div>
        <label for="seller_form_commercial_zone"><?= $e($t['customer_form_zone'] ?? '') ?></label>
        <input id="seller_form_commercial_zone" type="text" name="commercial_zone" maxlength="80" class="seller-customer-input-muted" data-commercial-line="1" autocomplete="off">
      </div>
    </div>
    <label class="seller-customer-checkbox-label">
      <input type="checkbox" name="same_as_commercial" id="seller_same_as_commercial" value="1">
      <span class="seller-cf-same-as-label-text"><?= $e($t['customer_form_same_as_commercial'] ?? '') ?></span>
    </label>
  </div>

  <div class="seller-customer-form-section" id="seller_delivery_section">
    <h4 class="seller-customer-form-section-title"><?= $e($t['customer_form_section_delivery'] ?? '') ?></h4>
    <div class="seller-cf-row">
      <div class="seller-customer-form-full-row">
        <label for="seller_form_dispatch_delivery_full"><?= $e($t['customer_form_delivery_full'] ?? '') ?></label>
        <input id="seller_form_dispatch_delivery_full" type="text" name="dispatch_delivery_address" maxlength="255" readonly class="seller-customer-summary-readonly" tabindex="0" data-address-summary="dispatch" placeholder="<?= $e(trim((string) ($t['customer_form_delivery_auto_placeholder'] ?? ''))) ?>" autocomplete="off">
      </div>
    </div>
    <div class="seller-cf-row seller-cf-row--delivery-mid">
      <div>
        <label for="seller_form_dispatch_address"><?= $e($t['customer_form_address'] ?? '') ?></label>
        <input id="seller_form_dispatch_address" type="text" name="dispatch_address" maxlength="255" data-dispatch-line="1" autocomplete="off">
      </div>
      <div>
        <label for="seller_form_dispatch_country_id"><?= $e($t['customer_form_country'] ?? '') ?></label>
        <select id="seller_form_dispatch_country_id" name="dispatch_country_id" class="seller-customer-country-select" data-dispatch-line="1">
          <option value=""><?= $e($t['customer_form_select_country'] ?? '') ?></option>
          <?php foreach ($form_countries as $ct) {
              if (! is_array($ct)) {
                  continue;
              }
              $cid = (int) ($ct['id'] ?? 0);
              if ($cid <= 0) {
                  continue;
              }
              $cAttr = $countryOptionAttrs($ct);
              ?>
          <option value="<?= (int) $cid ?>"<?= $cAttr ?>><?= $sellerPortalCountryOptionLabel($ct) ?></option>
          <?php } ?>
        </select>
      </div>
      <div>
        <label for="seller_form_dispatch_state"><?= $e($t['customer_form_state'] ?? '') ?></label>
        <input id="seller_form_dispatch_state" type="text" name="dispatch_state" maxlength="120" data-dispatch-line="1" autocomplete="off">
      </div>
    </div>
    <div class="seller-cf-row seller-cf-row--delivery-bot">
      <div>
        <label for="seller_form_dispatch_city"><?= $e($t['customer_form_city'] ?? '') ?></label>
        <input id="seller_form_dispatch_city" type="text" name="dispatch_city" maxlength="120" class="seller-customer-input-muted" data-dispatch-line="1" autocomplete="off">
      </div>
      <div>
        <label for="seller_form_dispatch_zip"><?= $e($t['customer_form_zip'] ?? '') ?></label>
        <input id="seller_form_dispatch_zip" type="text" name="dispatch_zip_code" maxlength="40" class="seller-customer-input-muted" data-dispatch-line="1" autocomplete="off">
      </div>
      <div>
        <label for="seller_form_dispatch_zone"><?= $e($t['customer_form_zone'] ?? '') ?></label>
        <input id="seller_form_dispatch_zone" type="text" name="dispatch_zone" maxlength="80" class="seller-customer-input-muted" data-dispatch-line="1" autocomplete="off">
      </div>
    </div>
  </div>

  <div class="seller-customer-form-section seller-customer-form-section--notes">
    <h4 class="seller-customer-form-section-title"><?= $e($t['customer_form_section_notes'] ?? '') ?></h4>
    <div class="seller-cf-row">
      <div class="seller-customer-form-full-row">
        <label for="seller_form_notes"><?= $e($t['customer_form_notes'] ?? '') ?></label>
        <textarea id="seller_form_notes" name="notes" maxlength="50" rows="6" class="seller-customer-form-textarea" autocomplete="off"></textarea>
        <textarea id="seller_form_delivery_notes" name="delivery_notes" class="seller-delivery-notes-hidden" maxlength="50" rows="1" tabindex="-1" aria-hidden="true" autocomplete="off"></textarea>
      </div>
    </div>
  </div>

  <div class="seller-customer-modal-actions">
    <button type="submit" id="sellerCustomerFormSubmitBtn" class="btn-portal-primary"><span id="sellerCustomerFormSubmitLabel"><?= $e($submitCreateLabel) ?></span></button>
    <?php
    $sellerCustomerFormBackUrl = trim((string) ($seller_customer_form_back_url ?? ''));
    if ($sellerCustomerFormBackUrl === '') { ?>
    <button type="button" id="closeCreateCustomerModalBottom" class="btn-portal-outline"><?= $e(trim((string) ($t['customer_modal_close'] ?? ''))) ?></button>
    <?php } ?>
  </div>
</form>

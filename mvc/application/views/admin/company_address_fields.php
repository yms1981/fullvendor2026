<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$cfLang = isset($cfLang) && is_array($cfLang) ? $cfLang : [];
$cf = function (string $key, string $fallback = '') use ($cfLang): string {
	$v = trim((string) ($cfLang[$key] ?? ''));

	return $v !== '' ? $v : $fallback;
};
$countries = isset($countries) && is_array($countries) ? $countries : [];
$row = isset($companyAddressRow) && (is_object($companyAddressRow) || is_array($companyAddressRow))
	? $companyAddressRow
	: [];
if (is_array($row)) {
	$row = (object) $row;
}
$av = static function (string $k) use ($row): string {
	$v = $row->$k ?? '';

	return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
};
$countryVal = trim((string) ($row->country ?? ''));
?>
<div class="form-group row">
	<div class="col-md-8">
		<label><?= htmlspecialchars($cf('label_full_address', 'Full address'), ENT_QUOTES, 'UTF-8') ?>:</label>
		<input type="text" class="form-control bg-light" name="full_address" id="admin_company_full_address" autocomplete="off" readonly="readonly" value="<?= $av('full_address') ?>">
		<?php
		$fullAddressHint = $cf('hint_full_address_composed', 'Built automatically from address, country, state, city, postal code and zone.');
		if ($fullAddressHint !== '') { ?>
		<small class="form-text text-muted"><?= htmlspecialchars($fullAddressHint, ENT_QUOTES, 'UTF-8') ?></small>
		<?php } ?>
	</div>
	<div class="col-md-4">
		<label><?= htmlspecialchars($cf('label_address', 'Address'), ENT_QUOTES, 'UTF-8') ?>:</label>
		<input type="text" class="form-control cust-admin-addr-upper" name="address" id="admin_company_address" autocomplete="street-address"
			value="<?= $av('address') ?>">
	</div>
</div>
<div class="form-group row">
	<div class="col-md-6 col-lg-3">
		<label><?= htmlspecialchars($cf('label_country', 'Country'), ENT_QUOTES, 'UTF-8') ?>:</label>
		<div id="admin_company_country_select_wrap">
		<select class="form-control admin-company-country-select" name="country" id="admin_company_country_id"
			data-placeholder="<?= htmlspecialchars($cf('placeholder_select_country', 'Select country'), ENT_QUOTES, 'UTF-8') ?>">
			<option value=""><?= htmlspecialchars($cf('placeholder_select_country', 'Select country'), ENT_QUOTES, 'UTF-8') ?></option>
			<?php foreach ($countries as $country) :
				if (! is_object($country)) {
					$country = (object) $country;
				}
				$cid = (int) ($country->id ?? $country->country_id ?? 0);
				if ($cid < 1) {
					continue;
				}
				$sn = strtolower((string) ($country->sortname ?? ''));
				$pc = (int) ($country->phonecode ?? 0);
				$pm = (string) ($country->phonemask ?? $country->phone_mask ?? '');
				$cname = (string) ($country->name ?? '');
				$cnameDisp = function_exists('mb_strtoupper') ? mb_strtoupper($cname, 'UTF-8') : strtoupper($cname);
				$selected = $countryVal !== '' && (string) $cid === $countryVal ? ' selected' : '';
				?>
			<option value="<?= $cid ?>" data-sortname="<?= htmlspecialchars($sn, ENT_QUOTES, 'UTF-8') ?>" data-phonecode="<?= $pc ?>" data-phonemask="<?= htmlspecialchars($pm, ENT_QUOTES, 'UTF-8') ?>"<?= $selected ?>><?= htmlspecialchars($cnameDisp, ENT_QUOTES, 'UTF-8') ?></option>
			<?php endforeach; ?>
		</select>
		</div>
	</div>
	<div class="col-md-6 col-lg-3">
		<label><?= htmlspecialchars($cf('label_state', 'State / Province'), ENT_QUOTES, 'UTF-8') ?>:</label>
		<input type="text" class="form-control text-uppercase cust-admin-addr-upper" name="state" id="admin_company_state" autocomplete="address-level1" value="<?= $av('state') ?>">
	</div>
	<div class="col-md-6 col-lg-3">
		<label><?= htmlspecialchars($cf('label_city', 'City'), ENT_QUOTES, 'UTF-8') ?>:</label>
		<input type="text" class="form-control text-uppercase cust-admin-addr-upper" name="city" id="admin_company_city" autocomplete="address-level2" value="<?= $av('city') ?>">
	</div>
	<div class="col-md-6 col-lg-3">
		<label><?= htmlspecialchars($cf('label_zip_code', 'Zip code'), ENT_QUOTES, 'UTF-8') ?>:</label>
		<input type="text" class="form-control cust-admin-addr-upper" name="zip_code" id="admin_company_zip_code" value="<?= $av('zip_code') ?>">
	</div>
</div>

<?php
/** @var array<string, string> $pt */
/** @var object|null $d */
/** @var array<int|string, mixed>|object|null $language */
/** @var bool|null $hasLangSlots */
if (! isset($pt) || ! is_array($pt)) {
	$pt = function_exists('admin_plan_page_texts')
		? admin_plan_page_texts(null)
		: (function_exists('admin_lang_cmp_admin_plan_texts') ? admin_lang_cmp_admin_plan_texts() : []);
}
$pv = static function (string $key, $default = '') use ($d) {
	if (! is_object($d) || ! isset($d->{$key})) {
		return $default;
	}
	$v = $d->{$key};

	return $v === null ? $default : $v;
};
$pint = static function (string $key) use ($pv): int {
	return (int) $pv($key, 0);
};
$pstr = static function (string $key) use ($pv): string {
	return htmlspecialchars(trim((string) $pv($key, '')), ENT_QUOTES, 'UTF-8');
};
$pdec = static function (string $key, int $decimals = 2) use ($pv): string {
	return htmlspecialchars(number_format((float) $pv($key, 0), $decimals, '.', ''), ENT_QUOTES, 'UTF-8');
};
$bp = (string) $pv('billing_period', 'monthly');

// status puede venir como int 0/1, string "0"/"1", bool, o bit(1) → "\x00" / "\x01".
$rawStatus = $pv('status', 1);
if (is_bool($rawStatus)) {
	$intStatus = $rawStatus ? 1 : 0;
} elseif (is_int($rawStatus) || is_float($rawStatus)) {
	$intStatus = (int) $rawStatus !== 0 ? 1 : 0;
} elseif (is_string($rawStatus)) {
	if ($rawStatus === '' || $rawStatus === "\x00") {
		$intStatus = 0;
	} elseif (ctype_digit($rawStatus)) {
		$intStatus = (int) $rawStatus !== 0 ? 1 : 0;
	} elseif (strlen($rawStatus) === 1) {
		$intStatus = ord($rawStatus) > 0 ? 1 : 0;
	} else {
		$sl = strtolower(trim($rawStatus));
		$intStatus = in_array($sl, ['1', 'enabled', 'active', 'true'], true) ? 1 : 0;
	}
} else {
	$intStatus = 0;
}
$statusForm = $intStatus === 1 ? 'enabled' : 'disabled';

$neverChecked = (int) $pv('neverexpire', 0) === 1;

// ecommerce_permission es bit(1) en BD → mismo tratamiento que `status`.
$bitTo01 = static function ($raw): int {
	if (is_bool($raw)) {
		return $raw ? 1 : 0;
	}
	if (is_int($raw) || is_float($raw)) {
		return (int) $raw !== 0 ? 1 : 0;
	}
	if (is_string($raw)) {
		if ($raw === '' || $raw === "\x00") {
			return 0;
		}
		if (ctype_digit($raw)) {
			return (int) $raw !== 0 ? 1 : 0;
		}
		if (strlen($raw) === 1) {
			return ord($raw) > 0 ? 1 : 0;
		}

		return in_array(strtolower(trim($raw)), ['1', 'true', 'yes', 'on'], true) ? 1 : 0;
	}

	return 0;
};
$ecommerceChecked = $bitTo01($pv('ecommerce_permission', 0)) === 1;

$h = static function (string $k) use ($pt): string {
	return htmlspecialchars((string) ($pt[$k] ?? ''), ENT_QUOTES, 'UTF-8');
};

$isEditMode = is_object($d) && (
	(isset($d->plan_id) && (int) $d->plan_id > 0)
	|| (isset($d->subscription_id) && (int) $d->subscription_id > 0)
);
$showLangSlots = isset($hasLangSlots) ? (bool) $hasLangSlots : false;
?>
<h5 class="mb-3 mt-2"><?= $h('section_general') ?></h5>
<div class="form-group row">
  <div class="col-md-6">
    <label><?= $h('label_title') ?> <span class="text-danger">*</span></label>
    <input type="text" class="form-control" name="title" maxlength="150" required value="<?= $pstr('title') ?>" placeholder="<?= $h('placeholder_title') ?>">
  </div>
  <div class="col-md-6">
    <label><?= $h('label_price') ?> <span class="text-danger">*</span></label>
    <input type="number" class="form-control" name="price" step="0.01" min="0" required value="<?= $pdec('price') ?>" placeholder="<?= $h('placeholder_price') ?>">
  </div>
</div>
<div class="form-group row">
  <div class="col-md-12">
    <label><?= $h('label_description') ?></label>
    <input type="text" class="form-control" name="description" maxlength="80" value="<?= $pstr('description') ?>" placeholder="<?= $h('placeholder_description') ?>">
  </div>
</div>
<div class="form-group row">
  <div class="col-md-6">
    <label><?= $h('label_billing_period') ?> <span class="text-danger">*</span></label>
    <select class="form-control" name="billing_period" required>
      <option value="monthly" <?= $bp === 'monthly' ? 'selected' : '' ?>><?= $h('billing_monthly') ?></option>
      <option value="annual" <?= $bp === 'annual' ? 'selected' : '' ?>><?= $h('billing_annual') ?></option>
    </select>
  </div>
  <div class="col-md-6">
    <label><?= $h('label_status') ?> <span class="text-danger">*</span></label>
    <select class="form-control" name="status" required>
      <option value="enabled" <?= $statusForm === 'enabled' ? 'selected' : '' ?>><?= $h('plan_select_status_enabled') ?></option>
      <option value="disabled" <?= $statusForm === 'disabled' ? 'selected' : '' ?>><?= $h('plan_select_status_disabled') ?></option>
    </select>
  </div>
</div>

<h5 class="mb-3 mt-3"><?= $h('section_names') ?></h5>
<div class="form-group row">
  <div class="col-md-6">
    <label><?= $h('label_plan_name_spa') ?></label>
    <input type="text" class="form-control" name="plan_name_spa" maxlength="255" value="<?= $pstr('plan_name_spa') ?>">
  </div>
  <div class="col-md-6">
    <label><?= $h('label_plan_name_eng') ?></label>
    <input type="text" class="form-control" name="plan_name_eng" maxlength="255" value="<?= $pstr('plan_name_eng') ?>">
  </div>
</div>
<div class="form-group row">
  <div class="col-md-6">
    <label><?= $h('label_line1_spa') ?></label>
    <input type="text" class="form-control" name="line1_spa" maxlength="500" value="<?= $pstr('line1_spa') ?>">
  </div>
  <div class="col-md-6">
    <label><?= $h('label_line1_eng') ?></label>
    <input type="text" class="form-control" name="line1_eng" maxlength="500" value="<?= $pstr('line1_eng') ?>">
  </div>
</div>

<?php if ($showLangSlots) : ?>
<h5 class="mb-3 mt-3"><?= $h('section_languages') ?></h5>
<?php $this->load->view('admin/plan_language_fields', [
	'pt' => $pt,
	'language' => $language ?? [],
	'd' => $d,
]); ?>
<?php endif; ?>

<h5 class="mb-3 mt-3"><?= $h('section_limits') ?></h5>
<div class="form-group row">
  <div class="col-md-4">
    <label><?= $h('label_max_clients') ?></label>
    <input type="number" class="form-control" name="max_clients" min="0" value="<?= $pint('max_clients') ?>">
  </div>
  <div class="col-md-4">
    <label><?= $h('label_max_orders') ?></label>
    <input type="number" class="form-control" name="max_orders" min="0" value="<?= $pint('max_orders') ?>">
  </div>
  <div class="col-md-4">
    <label><?= $h('label_max_users') ?></label>
    <input type="number" class="form-control" name="max_users" min="0" value="<?= $pint('max_users') ?>">
  </div>
</div>
<div class="form-group row">
  <div class="col-md-6">
    <label><?= $h('label_max_customers') ?></label>
    <input type="number" class="form-control" name="max_Customers" min="0" value="<?= $pint('max_Customers') ?>">
  </div>
  <div class="col-md-6">
    <label><?= $h('label_days') ?></label>
    <input type="number" class="form-control" name="days" min="0" value="<?= $pint('days') ?>">
  </div>
</div>

<h5 class="mb-3 mt-3"><?= $h('section_payment') ?></h5>
<div class="form-group row">
  <div class="col-md-4">
    <label><?= $h('label_payment') ?></label>
    <input type="number" class="form-control" name="payment" step="0.01" min="0" value="<?= $pdec('payment') ?>">
  </div>
  <div class="col-md-4">
    <label><?= $h('label_payment_periodicity') ?></label>
    <input type="number" class="form-control" name="paymentperiocity" min="0" value="<?= $pint('paymentperiocity') ?>">
  </div>
  <div class="col-md-4">
    <label><?= $h('label_payment_text') ?></label>
    <input type="text" class="form-control" name="paymenttextp" maxlength="255" value="<?= $pstr('paymenttextp') ?>">
  </div>
</div>
<div class="form-group row">
  <div class="col-md-6">
    <input type="hidden" name="neverexpire" value="0">
    <div class="custom-control custom-checkbox">
      <input type="checkbox" class="custom-control-input" id="plan_neverexpire" name="neverexpire" value="1" <?= $neverChecked ? 'checked' : '' ?>>
      <label class="custom-control-label" for="plan_neverexpire"><?= $h('label_never_expire') ?></label>
    </div>
  </div>
  <div class="col-md-6">
    <input type="hidden" name="ecommerce_permission" value="0">
    <div class="custom-control custom-checkbox">
      <input type="checkbox" class="custom-control-input" id="plan_ecommerce_permission" name="ecommerce_permission" value="1" <?= $ecommerceChecked ? 'checked' : '' ?>>
      <label class="custom-control-label" for="plan_ecommerce_permission"><?= $h('label_ecommerce_permission') ?></label>
    </div>
  </div>
</div>

<?php if ($isEditMode) : ?>
<h5 class="mb-3 mt-3"><?= $h('section_stripe') ?></h5>
<div class="form-group row">
  <div class="col-md-6">
    <label><?= $h('label_stripe_id') ?></label>
    <input type="text" class="form-control" readonly value="<?= $pstr('stripeld') ?>">
  </div>
  <div class="col-md-6">
    <label><?= $h('label_stripe_name') ?></label>
    <input type="text" class="form-control" readonly value="<?= $pstr('stripeName') ?>">
  </div>
</div>
<div class="form-group row">
  <div class="col-md-6">
    <label><?= $h('label_stripe_status') ?></label>
    <input type="text" class="form-control" readonly value="<?= $pstr('stripe_status') ?>">
  </div>
  <div class="col-md-6">
    <label><?= $h('label_stripe_doc') ?></label>
    <input type="text" class="form-control" readonly value="<?= $pstr('stripe_doc') ?>">
  </div>
</div>
<div class="form-group row">
  <div class="col-md-6">
    <label><?= $h('label_stripe_url') ?></label>
    <?php $urlVal = trim((string) $pv('stripe_url', '')); ?>
    <?php if ($urlVal !== '') : ?>
      <input type="text" class="form-control" readonly value="<?= htmlspecialchars($urlVal, ENT_QUOTES, 'UTF-8') ?>">
      <a class="small d-inline-block mt-1" href="<?= htmlspecialchars($urlVal, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><i class="mdi mdi-open-in-new mr-1" aria-hidden="true"></i><?= $h('label_open_link') ?></a>
    <?php else : ?>
      <input type="text" class="form-control" readonly value="">
    <?php endif; ?>
  </div>
  <div class="col-md-6">
    <label><?= $h('label_stripe_pdf') ?></label>
    <?php $pdfVal = trim((string) $pv('stripe_pdf', '')); ?>
    <?php if ($pdfVal !== '') : ?>
      <input type="text" class="form-control" readonly value="<?= htmlspecialchars($pdfVal, ENT_QUOTES, 'UTF-8') ?>">
      <a class="small d-inline-block mt-1" href="<?= htmlspecialchars($pdfVal, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><i class="mdi mdi-file-pdf-box mr-1" aria-hidden="true"></i><?= $h('label_open_pdf') ?></a>
    <?php else : ?>
      <input type="text" class="form-control" readonly value="">
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

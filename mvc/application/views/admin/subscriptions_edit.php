<?php
/** @var array<string, string>|null $pageTexts */
/** @var object $data */
$pt = function_exists('admin_subscriptions_page_texts')
	? admin_subscriptions_page_texts(isset($pageTexts) && is_array($pageTexts) ? $pageTexts : null)
	: [];
$d = isset($data) ? $data : (object) [];
$rid = 0;
if (isset($d->subscription_row_id)) {
	$rid = (int) $d->subscription_row_id;
} elseif (isset($d->id)) {
	$rid = (int) $d->id;
} elseif (isset($d->subscription_id)) {
	$rid = (int) $d->subscription_id;
}
$rawStatus = isset($d->status) ? $d->status : 1;
$sv = strtolower(trim((string) $rawStatus));
$statusForm = 'enabled';
if (in_array($sv, ['0', 'disabled', 'cancelled', 'expired'], true)) {
	$statusForm = 'disabled';
} elseif ($sv !== '' && ctype_digit($sv) && (int) $sv === 0) {
	$statusForm = 'disabled';
}
$cid = isset($d->company_id) ? (int) $d->company_id : 0;
$pid = isset($d->plan_id) ? (int) $d->plan_id : 0;
$sd = null;
if (isset($d->start_period_date) && $d->start_period_date !== null && (string) $d->start_period_date !== '') {
	$sd = $d->start_period_date;
} elseif (isset($d->starts_at) && $d->starts_at !== null && (string) $d->starts_at !== '') {
	$sd = $d->starts_at;
}
$starts = $sd !== null ? htmlspecialchars(substr((string) $sd, 0, 10), ENT_QUOTES, 'UTF-8') : '';
$ed = null;
if (isset($d->end_period_date) && $d->end_period_date !== null && (string) $d->end_period_date !== '') {
	$ed = $d->end_period_date;
} elseif (isset($d->ends_at) && $d->ends_at !== null && (string) $d->ends_at !== '') {
	$ed = $d->ends_at;
}
$ends = $ed !== null ? htmlspecialchars(substr((string) $ed, 0, 10), ENT_QUOTES, 'UTF-8') : '';
$notes = isset($d->notes) ? (string) $d->notes : '';
$stripeFmtDt = static function ($v): string {
	if ($v === null) {
		return '';
	}
	$s = trim((string) $v);
	if ($s === '' || $s === '0000-00-00 00:00:00') {
		return '';
	}
	$ts = strtotime($s);
	if ($ts === false || $ts <= 0) {
		return $s;
	}

	return fv_format_display_datetime($ts);
};
$stripeFmtBool = static function ($v, string $yes, string $no): string {
	if ($v === null || $v === '') {
		return '';
	}
	if (is_bool($v)) {
		return $v ? $yes : $no;
	}
	if (is_string($v)) {
		$lower = strtolower(trim($v));
		if ($lower === '' || $lower === '0' || $lower === 'false' || $v === "\x00") {
			return $no;
		}
		if ($lower === '1' || $lower === 'true') {
			return $yes;
		}
	}

	return ((int) $v) === 1 ? $yes : $no;
};
$yesLabel = (string) ($pt['label_yes'] ?? 'Yes');
$noLabel = (string) ($pt['label_no'] ?? 'No');
$stripeSubId = isset($d->stripe_subscription_id) ? (string) $d->stripe_subscription_id : '';
$stripeHasSubId = trim($stripeSubId) !== '';
$stripeStatus = isset($d->stripe_status) ? (string) $d->stripe_status : '';
$stripePriceId = isset($d->stripe_price_id) ? (string) $d->stripe_price_id : '';
$stripeCurrency = isset($d->stripe_currency) ? strtoupper((string) $d->stripe_currency) : '';
$stripeCollMethod = isset($d->stripe_collection_method) ? (string) $d->stripe_collection_method : '';
$stripeCancelAtPeriodEnd = $stripeFmtBool($d->stripe_cancel_at_period_end ?? null, $yesLabel, $noLabel);
$stripeCurrentStart = $stripeFmtDt($d->stripe_current_period_start ?? null);
$stripeCurrentEnd = $stripeFmtDt($d->stripe_current_period_end ?? null);
$stripeTrialStart = $stripeFmtDt($d->stripe_trial_start ?? null);
$stripeTrialEnd = $stripeFmtDt($d->stripe_trial_end ?? null);
$stripeCancelAt = $stripeFmtDt($d->stripe_cancel_at ?? null);
$stripeCanceledAt = $stripeFmtDt($d->stripe_canceled_at ?? null);
$stripeEndedAt = $stripeFmtDt($d->stripe_ended_at ?? null);
$stripeLatestInvoice = isset($d->stripe_latest_invoice_id) ? (string) $d->stripe_latest_invoice_id : '';
$stripeDefaultPm = isset($d->stripe_default_payment_method_id) ? (string) $d->stripe_default_payment_method_id : '';
$stripeCreatedAt = $stripeFmtDt($d->stripe_created_at ?? null);
$this->load->view('admin/admin_grid_scope_styles');
?>
<div class="container-fluid page-body-wrapper admin-grid-scope">
  <div class="main-panel">
    <div class="p-4" style="min-height: 500px;">
      <div class="page-header">
        <h3 class="page-title"><?= htmlspecialchars($pt['form_edit_title'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>
      </div>
      <div class="card">
        <div class="card-body">
          <form class="forms-sample" action="<?= admin_url('subscriptions/update') ?>" method="post">
            <input type="hidden" name="subscription_row_id" value="<?= $rid ?>">
            <div class="form-group row">
              <div class="col-md-6">
                <label><?= htmlspecialchars($pt['label_company'] ?? '', ENT_QUOTES, 'UTF-8') ?> <span class="text-danger">*</span></label>
                <select class="form-control" name="company_id" id="company_id" required>
                  <option value=""><?= htmlspecialchars($pt['select_company'] ?? '', ENT_QUOTES, 'UTF-8') ?></option>
                  <?php if (! empty($companies)) : ?>
                    <?php foreach ($companies as $c) : ?>
                      <?php $coid = isset($c->company_id) ? (int) $c->company_id : 0; ?>
                      <?php if ($coid < 1) {
                        continue;
                      } ?>
                      <option value="<?= $coid ?>" <?= $coid === $cid ? 'selected' : '' ?>><?= htmlspecialchars(trim((string) ($c->name ?? '')), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label><?= htmlspecialchars($pt['label_plan'] ?? '', ENT_QUOTES, 'UTF-8') ?> <span class="text-danger">*</span></label>
                <select class="form-control" name="plan_id" id="plan_id" required>
                  <option value=""><?= htmlspecialchars($pt['select_plan'] ?? '', ENT_QUOTES, 'UTF-8') ?></option>
                  <?php if (! empty($plans)) : ?>
                    <?php foreach ($plans as $p) : ?>
                      <?php $psid = function_exists('admin_plan_row_id') ? admin_plan_row_id($p) : (int) (isset($p->plan_id) ? $p->plan_id : ($p->subscription_id ?? 0)); ?>
                      <?php if ($psid < 1) {
                        continue;
                      } ?>
                      <option value="<?= $psid ?>" <?= $psid === $pid ? 'selected' : '' ?>><?= htmlspecialchars(trim((string) ($p->title ?? '')), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </select>
              </div>
            </div>
            <div class="form-group row">
              <div class="col-md-6">
                <label><?= htmlspecialchars($pt['label_status'] ?? '', ENT_QUOTES, 'UTF-8') ?> <span class="text-danger">*</span></label>
                <select class="form-control" name="status" id="status" required>
                  <option value="enabled" <?= $statusForm === 'enabled' ? 'selected' : '' ?>><?= htmlspecialchars($pt['status_option_enabled'] ?? '', ENT_QUOTES, 'UTF-8') ?></option>
                  <option value="disabled" <?= $statusForm === 'disabled' ? 'selected' : '' ?>><?= htmlspecialchars($pt['status_option_disabled'] ?? '', ENT_QUOTES, 'UTF-8') ?></option>
                </select>
              </div>
              <div class="col-md-6">
                <label><?= htmlspecialchars($pt['label_notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <textarea class="form-control" name="notes" id="notes" rows="3" maxlength="65535" placeholder="<?= htmlspecialchars($pt['placeholder_notes'] ?? '', ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($notes, ENT_QUOTES, 'UTF-8') ?></textarea>
              </div>
            </div>
            <div class="form-group row">
              <div class="col-md-6">
                <label><?= htmlspecialchars($pt['label_starts_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="date" class="form-control" name="start_period_date" id="start_period_date" value="<?= $starts ?>">
              </div>
              <div class="col-md-6">
                <label><?= htmlspecialchars($pt['label_ends_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="date" class="form-control" name="end_period_date" id="end_period_date" value="<?= $ends ?>">
              </div>
            </div>
            <?php if ($rid > 0) : ?>
            <h5 class="mb-3 mt-4"><?= htmlspecialchars($pt['section_stripe_details'] ?? ($pt['section_stripe_actions'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h5>
            <p class="text-muted small mb-3"><?= htmlspecialchars($pt['stripe_readonly_notice'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
            <div class="form-group row">
              <div class="col-md-6">
                <label><?= htmlspecialchars($pt['label_stripe_subscription_id'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" readonly value="<?= htmlspecialchars($stripeSubId, ENT_QUOTES, 'UTF-8') ?>">
              </div>
              <div class="col-md-6">
                <label><?= htmlspecialchars($pt['label_stripe_status'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" readonly value="<?= htmlspecialchars($stripeStatus, ENT_QUOTES, 'UTF-8') ?>">
              </div>
            </div>
            <div class="form-group row">
              <div class="col-md-6">
                <label><?= htmlspecialchars($pt['label_stripe_price_id'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" readonly value="<?= htmlspecialchars($stripePriceId, ENT_QUOTES, 'UTF-8') ?>">
              </div>
              <div class="col-md-3">
                <label><?= htmlspecialchars($pt['label_stripe_currency'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" readonly value="<?= htmlspecialchars($stripeCurrency, ENT_QUOTES, 'UTF-8') ?>">
              </div>
              <div class="col-md-3">
                <label><?= htmlspecialchars($pt['label_stripe_collection_method'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" readonly value="<?= htmlspecialchars($stripeCollMethod, ENT_QUOTES, 'UTF-8') ?>">
              </div>
            </div>
            <div class="form-group row">
              <div class="col-md-6">
                <label><?= htmlspecialchars($pt['label_stripe_current_period_start'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" readonly value="<?= htmlspecialchars($stripeCurrentStart, ENT_QUOTES, 'UTF-8') ?>">
              </div>
              <div class="col-md-6">
                <label><?= htmlspecialchars($pt['label_stripe_current_period_end'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" readonly value="<?= htmlspecialchars($stripeCurrentEnd, ENT_QUOTES, 'UTF-8') ?>">
              </div>
            </div>
            <div class="form-group row">
              <div class="col-md-6">
                <label><?= htmlspecialchars($pt['label_stripe_trial_start'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" readonly value="<?= htmlspecialchars($stripeTrialStart, ENT_QUOTES, 'UTF-8') ?>">
              </div>
              <div class="col-md-6">
                <label><?= htmlspecialchars($pt['label_stripe_trial_end'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" readonly value="<?= htmlspecialchars($stripeTrialEnd, ENT_QUOTES, 'UTF-8') ?>">
              </div>
            </div>
            <div class="form-group row">
              <div class="col-md-3">
                <label><?= htmlspecialchars($pt['label_stripe_cancel_at_period_end'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" readonly value="<?= htmlspecialchars($stripeCancelAtPeriodEnd, ENT_QUOTES, 'UTF-8') ?>">
              </div>
              <div class="col-md-3">
                <label><?= htmlspecialchars($pt['label_stripe_cancel_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" readonly value="<?= htmlspecialchars($stripeCancelAt, ENT_QUOTES, 'UTF-8') ?>">
              </div>
              <div class="col-md-3">
                <label><?= htmlspecialchars($pt['label_stripe_canceled_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" readonly value="<?= htmlspecialchars($stripeCanceledAt, ENT_QUOTES, 'UTF-8') ?>">
              </div>
              <div class="col-md-3">
                <label><?= htmlspecialchars($pt['label_stripe_ended_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" readonly value="<?= htmlspecialchars($stripeEndedAt, ENT_QUOTES, 'UTF-8') ?>">
              </div>
            </div>
            <div class="form-group row">
              <div class="col-md-6">
                <label><?= htmlspecialchars($pt['label_stripe_latest_invoice_id'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" readonly value="<?= htmlspecialchars($stripeLatestInvoice, ENT_QUOTES, 'UTF-8') ?>">
              </div>
              <div class="col-md-6">
                <label><?= htmlspecialchars($pt['label_stripe_default_payment_method_id'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" readonly value="<?= htmlspecialchars($stripeDefaultPm, ENT_QUOTES, 'UTF-8') ?>">
              </div>
            </div>
            <div class="form-group row">
              <div class="col-md-6">
                <label><?= htmlspecialchars($pt['label_stripe_created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" readonly value="<?= htmlspecialchars($stripeCreatedAt, ENT_QUOTES, 'UTF-8') ?>">
              </div>
            </div>
            <h5 class="mb-3 mt-4"><?= htmlspecialchars($pt['section_stripe_actions'] ?? '', ENT_QUOTES, 'UTF-8') ?></h5>
            <div class="form-group row mb-3">
              <div class="col-md-12">
                <button
                  type="button"
                  class="btn btn-outline-primary mr-2 js-sub-stripe-action"
                  id="btnStripeCreateSubEdit"
                  data-action="create"
                  data-href="<?= htmlspecialchars(admin_url('subscriptions/stripe_create/' . $rid), ENT_QUOTES, 'UTF-8') ?>"
                  data-swal-title="<?= htmlspecialchars($pt['confirm_stripe_create_title'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                  data-swal-text="<?= htmlspecialchars($pt['confirm_stripe_create_text'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                  data-swal-icon="question"
                  data-swal-confirm-color="#16a34a"
                  data-swal-confirm-text="<?= htmlspecialchars($pt['confirm_stripe_yes_create'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                  data-swal-cancel-text="<?= htmlspecialchars($pt['confirm_stripe_no'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                >
                  <i class="mdi mdi-credit-card-plus" aria-hidden="true"></i> <?= htmlspecialchars($pt['btn_stripe_create'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </button>
                <button
                  type="button"
                  class="btn btn-outline-secondary mr-2 js-sub-stripe-action"
                  id="btnStripeSyncSubEdit"
                  data-action="sync"
                  data-href="<?= htmlspecialchars(admin_url('subscriptions/stripe_sync/' . $rid), ENT_QUOTES, 'UTF-8') ?>"
                  data-swal-title="<?= htmlspecialchars($pt['confirm_stripe_sync_title'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                  data-swal-text="<?= htmlspecialchars($pt['confirm_stripe_sync_text'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                  data-swal-icon="info"
                  data-swal-confirm-color="#2563eb"
                  data-swal-confirm-text="<?= htmlspecialchars($pt['confirm_stripe_yes_sync'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                  data-swal-cancel-text="<?= htmlspecialchars($pt['confirm_stripe_no'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                  <?= $stripeHasSubId ? '' : ' disabled' ?>
                  title="<?= htmlspecialchars($stripeHasSubId ? ($pt['tooltip_stripe_sync'] ?? '') : ($pt['tooltip_stripe_sync_requires_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                >
                  <i class="mdi mdi-sync" aria-hidden="true"></i> <?= htmlspecialchars($pt['btn_stripe_sync'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </button>
                <button
                  type="button"
                  class="btn btn-outline-warning js-sub-stripe-action"
                  id="btnStripeCancelSubEdit"
                  data-action="cancel"
                  data-href="<?= htmlspecialchars(admin_url('subscriptions/stripe_cancel/' . $rid), ENT_QUOTES, 'UTF-8') ?>"
                  <?= $stripeHasSubId ? '' : ' disabled' ?>
                  title="<?= htmlspecialchars($stripeHasSubId ? ($pt['btn_stripe_cancel'] ?? '') : ($pt['tooltip_stripe_cancel_requires_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                  data-swal-title="<?= htmlspecialchars($pt['confirm_stripe_cancel_title'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                  data-swal-text="<?= htmlspecialchars($pt['confirm_stripe_cancel_text'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                  data-swal-icon="warning"
                  data-swal-confirm-color="#d97706"
                  data-swal-confirm-text="<?= htmlspecialchars($pt['confirm_stripe_yes_cancel'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                  data-swal-cancel-text="<?= htmlspecialchars($pt['confirm_stripe_no'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                >
                  <i class="mdi mdi-close-circle-outline" aria-hidden="true"></i> <?= htmlspecialchars($pt['btn_stripe_cancel'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </button>
              </div>
            </div>
            <?php endif; ?>
            <div class="form-group">
              <button type="submit" class="btn btn-success mr-2"><?= htmlspecialchars($pt['btn_submit'] ?? '', ENT_QUOTES, 'UTF-8') ?></button>
              <a class="btn btn-danger mr-2" href="<?= admin_url('subscriptions/lists') ?>"><?= htmlspecialchars($pt['btn_cancel'] ?? '', ENT_QUOTES, 'UTF-8') ?></a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
  (function () {
    document.querySelectorAll('.js-sub-stripe-action').forEach(function (btn) {
      btn.addEventListener('click', function (ev) {
        ev.preventDefault();
        if (btn.disabled) {
          return;
        }
        var title = btn.getAttribute('data-swal-title') || '';
        var text = btn.getAttribute('data-swal-text') || '';
        var icon = btn.getAttribute('data-swal-icon') || 'question';
        var confirmColor = btn.getAttribute('data-swal-confirm-color') || '#16a34a';
        var confirmText = btn.getAttribute('data-swal-confirm-text') || 'Yes';
        var cancelText = btn.getAttribute('data-swal-cancel-text') || 'No';
        var href = btn.getAttribute('data-href') || '';

        if (typeof swal === 'undefined' || !swal || typeof swal.fire !== 'function') {
          if (href) window.location.href = href;
          return;
        }

        swal.fire({
          title: title,
          text: text,
          icon: icon,
          showCancelButton: true,
          confirmButtonColor: confirmColor,
          cancelButtonColor: '#6b7280',
          confirmButtonText: confirmText,
          cancelButtonText: cancelText,
        }).then(function (result) {
          if (result && result.isConfirmed && href) {
            window.location.href = href;
          }
        });
      });
    });
  })();
</script>

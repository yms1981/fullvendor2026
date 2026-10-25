<?php
/** @var array<string, string>|null $pageTexts */
/** @var object $data */
$pt = function_exists('admin_invoices_page_texts')
	? admin_invoices_page_texts(isset($pageTexts) && is_array($pageTexts) ? $pageTexts : null)
	: [];
$d = isset($data) ? $data : (object) [];
$iid = isset($d->invoice_id) ? (int) $d->invoice_id : 0;
$cid = isset($d->company_id) ? (int) $d->company_id : 0;
$sid = isset($d->subscription_id) ? (int) $d->subscription_id : 0;
$docNumber = isset($d->doc_number) ? (string) $d->doc_number : '';
$fmtDate = static function ($raw): string {
	if ($raw === null || (string) $raw === '') {
		return '';
	}
	$ts = strtotime((string) $raw);

	return $ts !== false ? fv_format_display_datetime($ts) : (string) $raw;
};
$dateInvText = $fmtDate(isset($d->date) ? $d->date : null);
$dateExText = $fmtDate(isset($d->date_expired) ? $d->date_expired : null);
$paidVal = '';
if (isset($d->paid) && $d->paid !== null && (string) $d->paid !== '') {
	if ((int) $d->paid === 1) {
		$paidVal = $pt['paid_option_yes'] ?? '';
	} elseif ((int) $d->paid === 0) {
		$paidVal = $pt['paid_option_no'] ?? '';
	} else {
		$paidVal = (string) $d->paid;
	}
}
$daysVal = isset($d->days) && $d->days !== null && (string) $d->days !== '' ? (int) $d->days : '';
$issuing = isset($d->issuing_entity) ? (string) $d->issuing_entity : '';
$addr = isset($d->address_entity) ? (string) $d->address_entity : '';
$country = isset($d->country_entity) ? (string) $d->country_entity : '';
$state = isset($d->state_entity) ? (string) $d->state_entity : '';
$city = isset($d->city_entity) ? (string) $d->city_entity : '';
$concept = isset($d->concept) ? (string) $d->concept : '';
$invPaid = isset($d->invoice_paid) && $d->invoice_paid !== null && (string) $d->invoice_paid !== '' ? (string) $d->invoice_paid : '';
$debt = isset($d->debt) && $d->debt !== null && (string) $d->debt !== '' ? (string) $d->debt : '';
$stripeId = isset($d->stripe_id) ? (string) $d->stripe_id : '';
$stripeDoc = isset($d->stripe_doc) ? (string) $d->stripe_doc : '';
$stripeUrl = isset($d->stripe_url) ? (string) $d->stripe_url : '';
$stripePdf = isset($d->stripe_pdf) ? (string) $d->stripe_pdf : '';
$stripeStatus = isset($d->stripe_status) ? (string) $d->stripe_status : '';

$companyName = '';
if ($cid > 0 && ! empty($companies)) {
	foreach ($companies as $c) {
		if (isset($c->company_id) && (int) $c->company_id === $cid) {
			$companyName = trim((string) ($c->name ?? ''));
			break;
		}
	}
}
$subscriptionLabel = '';
if ($sid > 0 && ! empty($subscriptionRefs)) {
	foreach ($subscriptionRefs as $sr) {
		if (isset($sr->ref_id) && (int) $sr->ref_id === $sid) {
			$subscriptionLabel = (string) ($sr->label ?? '');
			break;
		}
	}
}

$dash = trim((string) ($pt['paid_display_dash'] ?? '—'));
$valueOr = static function (string $value) use ($dash): string {
	return $value !== '' ? $value : $dash;
};

$this->load->view('admin/admin_grid_scope_styles');
?>
<div class="container-fluid page-body-wrapper admin-grid-scope">
  <div class="main-panel">
    <div class="p-4" style="min-height: 500px;">
      <div class="page-header d-flex flex-wrap align-items-center justify-content-between">
        <h3 class="page-title mb-0"><?= htmlspecialchars($pt['form_view_title'] ?? 'View invoice', ENT_QUOTES, 'UTF-8') ?></h3>
        <a class="btn btn-outline-secondary" href="<?= admin_url('invoices/lists') ?>">
          <i class="mdi mdi-arrow-left" aria-hidden="true"></i>
          <?= htmlspecialchars($pt['btn_back_to_list'] ?? ($pt['btn_cancel'] ?? 'Back'), ENT_QUOTES, 'UTF-8') ?>
        </a>
      </div>
      <div class="card mt-3">
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-md-3 col-lg-2"><?= htmlspecialchars($pt['label_doc_number'] ?? '', ENT_QUOTES, 'UTF-8') ?></dt>
            <dd class="col-md-9 col-lg-4"><?= htmlspecialchars($valueOr($docNumber), ENT_QUOTES, 'UTF-8') ?></dd>
            <dt class="col-md-3 col-lg-2"><?= htmlspecialchars($pt['label_days'] ?? '', ENT_QUOTES, 'UTF-8') ?></dt>
            <dd class="col-md-9 col-lg-4"><?= htmlspecialchars($daysVal !== '' ? (string) $daysVal : $dash, ENT_QUOTES, 'UTF-8') ?></dd>

            <dt class="col-md-3 col-lg-2"><?= htmlspecialchars($pt['label_company'] ?? '', ENT_QUOTES, 'UTF-8') ?></dt>
            <dd class="col-md-9 col-lg-4"><?= htmlspecialchars($valueOr($companyName !== '' ? ($companyName . ' (#' . $cid . ')') : ($cid > 0 ? ('#' . $cid) : '')), ENT_QUOTES, 'UTF-8') ?></dd>
            <dt class="col-md-3 col-lg-2"><?= htmlspecialchars($pt['label_subscription'] ?? '', ENT_QUOTES, 'UTF-8') ?></dt>
            <dd class="col-md-9 col-lg-4"><?= htmlspecialchars($valueOr($subscriptionLabel !== '' ? $subscriptionLabel : ($sid > 0 ? ('#' . $sid) : '')), ENT_QUOTES, 'UTF-8') ?></dd>

            <dt class="col-md-3 col-lg-2"><?= htmlspecialchars($pt['label_date'] ?? '', ENT_QUOTES, 'UTF-8') ?></dt>
            <dd class="col-md-9 col-lg-4"><?= htmlspecialchars($valueOr($dateInvText), ENT_QUOTES, 'UTF-8') ?></dd>
            <dt class="col-md-3 col-lg-2"><?= htmlspecialchars($pt['label_date_expired'] ?? '', ENT_QUOTES, 'UTF-8') ?></dt>
            <dd class="col-md-9 col-lg-4"><?= htmlspecialchars($valueOr($dateExText), ENT_QUOTES, 'UTF-8') ?></dd>

            <dt class="col-md-3 col-lg-2"><?= htmlspecialchars($pt['label_paid'] ?? '', ENT_QUOTES, 'UTF-8') ?></dt>
            <dd class="col-md-9 col-lg-4"><?= htmlspecialchars($valueOr((string) $paidVal), ENT_QUOTES, 'UTF-8') ?></dd>
            <dt class="col-md-3 col-lg-2"><?= htmlspecialchars($pt['label_issuing_entity'] ?? '', ENT_QUOTES, 'UTF-8') ?></dt>
            <dd class="col-md-9 col-lg-4"><?= htmlspecialchars($valueOr($issuing), ENT_QUOTES, 'UTF-8') ?></dd>

            <dt class="col-md-3 col-lg-2"><?= htmlspecialchars($pt['label_address_entity'] ?? '', ENT_QUOTES, 'UTF-8') ?></dt>
            <dd class="col-md-9 col-lg-10" style="white-space: pre-wrap;"><?= htmlspecialchars($valueOr($addr), ENT_QUOTES, 'UTF-8') ?></dd>

            <dt class="col-md-3 col-lg-2"><?= htmlspecialchars($pt['label_country_entity'] ?? '', ENT_QUOTES, 'UTF-8') ?></dt>
            <dd class="col-md-9 col-lg-2"><?= htmlspecialchars($valueOr($country), ENT_QUOTES, 'UTF-8') ?></dd>
            <dt class="col-md-3 col-lg-2"><?= htmlspecialchars($pt['label_state_entity'] ?? '', ENT_QUOTES, 'UTF-8') ?></dt>
            <dd class="col-md-9 col-lg-2"><?= htmlspecialchars($valueOr($state), ENT_QUOTES, 'UTF-8') ?></dd>
            <dt class="col-md-3 col-lg-2"><?= htmlspecialchars($pt['label_city_entity'] ?? '', ENT_QUOTES, 'UTF-8') ?></dt>
            <dd class="col-md-9 col-lg-2"><?= htmlspecialchars($valueOr($city), ENT_QUOTES, 'UTF-8') ?></dd>

            <dt class="col-md-3 col-lg-2"><?= htmlspecialchars($pt['label_concept'] ?? '', ENT_QUOTES, 'UTF-8') ?></dt>
            <dd class="col-md-9 col-lg-10" style="white-space: pre-wrap;"><?= htmlspecialchars($valueOr($concept), ENT_QUOTES, 'UTF-8') ?></dd>

            <dt class="col-md-3 col-lg-2"><?= htmlspecialchars($pt['label_invoice_paid'] ?? '', ENT_QUOTES, 'UTF-8') ?></dt>
            <dd class="col-md-9 col-lg-4"><?= htmlspecialchars($valueOr($invPaid), ENT_QUOTES, 'UTF-8') ?></dd>
            <dt class="col-md-3 col-lg-2"><?= htmlspecialchars($pt['label_debt'] ?? '', ENT_QUOTES, 'UTF-8') ?></dt>
            <dd class="col-md-9 col-lg-4"><?= htmlspecialchars($valueOr($debt), ENT_QUOTES, 'UTF-8') ?></dd>
          </dl>
        </div>
      </div>

      <div class="card mt-3">
        <div class="card-body">
          <h5 class="mb-3"><?= htmlspecialchars($pt['section_stripe_actions'] ?? 'Stripe', ENT_QUOTES, 'UTF-8') ?></h5>
          <dl class="row mb-0">
            <dt class="col-md-3 col-lg-2"><?= htmlspecialchars($pt['label_stripe_id'] ?? '', ENT_QUOTES, 'UTF-8') ?></dt>
            <dd class="col-md-9 col-lg-4" style="word-break: break-all;"><?= htmlspecialchars($valueOr($stripeId), ENT_QUOTES, 'UTF-8') ?></dd>
            <dt class="col-md-3 col-lg-2"><?= htmlspecialchars($pt['label_stripe_status'] ?? '', ENT_QUOTES, 'UTF-8') ?></dt>
            <dd class="col-md-9 col-lg-4"><?= htmlspecialchars($valueOr($stripeStatus), ENT_QUOTES, 'UTF-8') ?></dd>

            <dt class="col-md-3 col-lg-2"><?= htmlspecialchars($pt['label_stripe_doc'] ?? '', ENT_QUOTES, 'UTF-8') ?></dt>
            <dd class="col-md-9 col-lg-10" style="word-break: break-all;"><?= htmlspecialchars($valueOr($stripeDoc), ENT_QUOTES, 'UTF-8') ?></dd>

            <dt class="col-md-3 col-lg-2"><?= htmlspecialchars($pt['label_stripe_url'] ?? '', ENT_QUOTES, 'UTF-8') ?></dt>
            <dd class="col-md-9 col-lg-10" style="word-break: break-all;">
              <?php if ($stripeUrl !== '') : ?>
                <a href="<?= htmlspecialchars($stripeUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($stripeUrl, ENT_QUOTES, 'UTF-8') ?></a>
              <?php else : ?>
                <?= htmlspecialchars($dash, ENT_QUOTES, 'UTF-8') ?>
              <?php endif; ?>
            </dd>

            <dt class="col-md-3 col-lg-2"><?= htmlspecialchars($pt['label_stripe_pdf'] ?? '', ENT_QUOTES, 'UTF-8') ?></dt>
            <dd class="col-md-9 col-lg-10" style="word-break: break-all;">
              <?php if ($stripePdf !== '') : ?>
                <a href="<?= htmlspecialchars($stripePdf, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($stripePdf, ENT_QUOTES, 'UTF-8') ?></a>
              <?php else : ?>
                <?= htmlspecialchars($dash, ENT_QUOTES, 'UTF-8') ?>
              <?php endif; ?>
            </dd>
          </dl>

          <div class="mt-3">
            <button type="button" class="btn btn-outline-primary mr-2" id="btnStripeCreateView" data-invoice-id="<?= $iid ?>">
              <i class="mdi mdi-credit-card-plus" aria-hidden="true"></i> <?= htmlspecialchars($pt['btn_stripe_create'] ?? '', ENT_QUOTES, 'UTF-8') ?>
            </button>
            <button type="button" class="btn btn-outline-secondary mr-2" id="btnStripeSyncView" data-invoice-id="<?= $iid ?>">
              <i class="mdi mdi-sync" aria-hidden="true"></i> <?= htmlspecialchars($pt['btn_stripe_sync'] ?? '', ENT_QUOTES, 'UTF-8') ?>
            </button>
            <?php if ($stripeUrl !== '') : ?>
              <a class="btn btn-outline-info" href="<?= htmlspecialchars($stripeUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
                <i class="mdi mdi-open-in-new" aria-hidden="true"></i> <?= htmlspecialchars($pt['btn_open_stripe_url'] ?? '', ENT_QUOTES, 'UTF-8') ?>
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
  const invoiceStripeViewUi = <?= json_encode([
    'confirmStripeCreateTitle' => $pt['confirm_stripe_create_title'] ?? '',
    'confirmStripeCreateText' => $pt['confirm_stripe_create_text'] ?? '',
    'confirmStripeSyncTitle' => $pt['confirm_stripe_sync_title'] ?? '',
    'confirmStripeSyncText' => $pt['confirm_stripe_sync_text'] ?? '',
    'confirmYes' => $pt['delete_confirm_yes'] ?? '',
    'confirmNo' => $pt['delete_confirm_no'] ?? '',
  ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  const stripeCreateBaseView = <?= json_encode(admin_url('invoices/stripe_create/'), JSON_UNESCAPED_UNICODE) ?>;
  const stripeSyncBaseView = <?= json_encode(admin_url('invoices/stripe_sync/'), JSON_UNESCAPED_UNICODE) ?>;

  document.getElementById('btnStripeCreateView')?.addEventListener('click', function () {
    var id = this.getAttribute('data-invoice-id');
    swal.fire({
      title: invoiceStripeViewUi.confirmStripeCreateTitle || '',
      text: invoiceStripeViewUi.confirmStripeCreateText || '',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#16a34a',
      cancelButtonColor: '#6b7280',
      confirmButtonText: invoiceStripeViewUi.confirmYes || '',
      cancelButtonText: invoiceStripeViewUi.confirmNo || '',
    }).then(function (result) {
      if (result.isConfirmed && id) {
        window.location.href = stripeCreateBaseView + id;
      }
    });
  });

  document.getElementById('btnStripeSyncView')?.addEventListener('click', function () {
    var id = this.getAttribute('data-invoice-id');
    swal.fire({
      title: invoiceStripeViewUi.confirmStripeSyncTitle || '',
      text: invoiceStripeViewUi.confirmStripeSyncText || '',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#16a34a',
      cancelButtonColor: '#6b7280',
      confirmButtonText: invoiceStripeViewUi.confirmYes || '',
      cancelButtonText: invoiceStripeViewUi.confirmNo || '',
    }).then(function (result) {
      if (result.isConfirmed && id) {
        window.location.href = stripeSyncBaseView + id;
      }
    });
  });
</script>

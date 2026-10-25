<?php
/** @var array<string, string>|null $pageTexts */
/** @var array<int, string> $languagesById */
$pt = function_exists('admin_plan_page_texts')
	? admin_plan_page_texts(isset($pageTexts) && is_array($pageTexts) ? $pageTexts : null)
	: [];
$langMap = isset($languagesById) && is_array($languagesById) ? $languagesById : [];
$swalTitle = json_encode($pt['delete_confirm_title'] ?? 'Delete?', JSON_UNESCAPED_UNICODE);
$swalText = json_encode($pt['delete_confirm_text'] ?? '', JSON_UNESCAPED_UNICODE);
$swalYes = json_encode($pt['delete_confirm_yes'] ?? 'Yes', JSON_UNESCAPED_UNICODE);
$swalNo = json_encode($pt['delete_confirm_no'] ?? 'No', JSON_UNESCAPED_UNICODE);
$deleteBase = admin_url('plan/delete/');
$planStatusUrlJs = isset($planStatusUrl) ? (string) $planStatusUrl : admin_url('plan/changeStatus');

$planThemes = [
	['accent' => '#e53935', 'accentDark' => '#c62828', 'name' => 'red'],
	['accent' => '#1e88e5', 'accentDark' => '#1565c0', 'name' => 'blue'],
	['accent' => '#fb8c00', 'accentDark' => '#ef6c00', 'name' => 'orange'],
];
$listArr = [];
if (! empty($list)) {
	foreach ($list as $row) {
		$listArr[] = $row;
	}
}
$totalPlans = count($listArr);
?>
<style>
  .plan-cards-scope { --plan-header-bg: #141824; }
  .plan-cards-grid {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    align-items: stretch;
    gap: 1.5rem;
    padding: 0.25rem 0 1.5rem;
  }
  .plan-pricing-card {
    flex: 1 1 260px;
    max-width: 340px;
    min-width: 240px;
    background: #fff;
    border-radius: 0.35rem;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    position: relative;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }
  .plan-pricing-card--featured {
    transform: scale(1.04);
    z-index: 2;
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.18);
  }
  .plan-pricing-card--inactive {
    opacity: 0.78;
  }
  .plan-pricing-card__header {
    background: var(--plan-header-bg);
    text-align: center;
    padding: 1.75rem 1rem 0.5rem;
    position: relative;
  }
  .plan-pricing-card__title {
    margin: 0 0 0.5rem;
    font-size: 1.65rem;
    font-weight: 700;
    color: var(--plan-accent);
    letter-spacing: 0.02em;
  }
  .plan-pricing-card__price {
    color: #fff;
    font-size: 2.25rem;
    font-weight: 700;
    line-height: 1.1;
  }
  .plan-pricing-card__cycle {
    color: rgba(255, 255, 255, 0.88);
    font-size: 0.95rem;
    margin-top: 0.35rem;
    padding-bottom: 0.75rem;
  }
  .plan-pricing-card__chevron-wrap {
    overflow: hidden;
    height: 20px;
    margin-top: 0.25rem;
    display: flex;
    justify-content: center;
  }
  .plan-pricing-card__chevron-inner {
    width: 0;
    height: 0;
    border-left: 200px solid transparent;
    border-right: 200px solid transparent;
    border-top: 22px solid var(--plan-accent);
    margin: 0 auto;
  }
  .plan-pricing-card__body {
    flex: 1 1 auto;
    padding: 1.25rem 1.25rem 0.5rem;
    background: #fff;
  }
  .plan-pricing-card__features {
    list-style: none;
    margin: 0;
    padding: 0;
  }
  .plan-pricing-card__feature {
    display: flex;
    align-items: flex-start;
    gap: 0.6rem;
    padding: 0.55rem 0;
    font-size: 0.92rem;
    color: #374151;
    border-bottom: 1px solid #f0f0f0;
  }
  .plan-pricing-card__feature:last-child { border-bottom: 0; }
  .plan-pricing-card__icon {
    flex-shrink: 0;
    width: 1.25rem;
    height: 1.25rem;
    margin-top: 0.1rem;
    font-size: 1rem;
    line-height: 1.25rem;
    text-align: center;
  }
  .plan-pricing-card__icon--yes { color: #2e7d32; }
  .plan-pricing-card__icon--no { color: #c62828; font-weight: 700; }
  .plan-pricing-card__footer {
    padding: 1.1rem 1.25rem 1.35rem;
    text-align: center;
    background: #fff;
  }
  .plan-pricing-card__btn {
    display: block;
    width: 100%;
    max-width: none;
    margin: 0 auto;
    padding: 0.78rem 1.25rem;
    background: var(--plan-accent);
    color: #fff !important;
    font-weight: 700;
    letter-spacing: 0.12em;
    font-size: 0.82rem;
    border-radius: 0.35rem;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: filter 0.15s ease, transform 0.12s ease, box-shadow 0.15s ease;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
  }
  .plan-pricing-card__btn:hover {
    filter: brightness(1.06);
    color: #fff !important;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  }
  .plan-pricing-card__btn:active {
    transform: translateY(1px);
  }
  .plan-pricing-card__admin {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
    gap: 0.65rem;
    margin-top: 1rem;
    padding: 0.65rem 0.75rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 0.45rem;
    max-width: 100%;
  }
  .plan-pricing-card__switch-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 auto;
    padding: 0.2rem 0.5rem 0.2rem 0.15rem;
    min-height: 2.5rem;
  }
  .plan-pricing-card__switch-wrap .plan-list-status-switch {
    padding-left: 2.75rem;
    min-height: 1.5rem;
    margin: 0;
    padding-top: 0;
  }
  .plan-pricing-card__switch-wrap .custom-control-label::before {
    left: -2.75rem;
    width: 2.5rem;
    height: 1.35rem;
    border-radius: 0.7rem;
    border-color: #cbd5e1;
    background-color: #e2e8f0;
  }
  .plan-pricing-card__switch-wrap .custom-control-label::after {
    top: calc(0.25rem + 2px);
    left: calc(-2.75rem + 4px);
    width: calc(1.35rem - 4px);
    height: calc(1.35rem - 4px);
    border-radius: 50%;
    background-color: #fff;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.12);
  }
  .plan-pricing-card__switch-wrap .custom-control-input:checked ~ .custom-control-label::after {
    background-color: #fff;
    transform: translateX(1.05rem);
  }
  .plan-pricing-card__switch-wrap .custom-control-label {
    cursor: pointer;
    min-height: 1.35rem;
    padding-top: 0.15rem;
  }
  .plan-list-status-switch .custom-control-input:checked ~ .custom-control-label::before {
    background-color: var(--plan-accent);
    border-color: var(--plan-accentDark);
  }
  .plan-list-status-switch .custom-control-input:focus ~ .custom-control-label::before {
    box-shadow: 0 0 0 0.2rem rgba(30, 41, 59, 0.12);
  }
  .plan-pricing-card__btn-group {
    display: inline-flex;
    flex: 0 0 auto;
    border-radius: 0.35rem;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
  }
  .plan-pricing-card__btn-group .plan-pricing-card__btn-icon {
    border-radius: 0;
    border: none;
    width: 2.65rem;
    height: 2.5rem;
    box-shadow: none;
  }
  .plan-pricing-card__btn-group .plan-pricing-card__btn-icon:first-child {
    border-right: 1px solid #e2e8f0;
  }
  .plan-pricing-card__btn-group .plan-pricing-card__btn-icon--danger {
    border-color: transparent;
  }
  .plan-pricing-card__btn-group .plan-pricing-card__btn-icon:hover {
    background: #f1f5f9;
  }
  .plan-pricing-card__btn-group .plan-pricing-card__btn-icon--danger:hover {
    background: #fef2f2;
  }
  .plan-pricing-card__btn-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.65rem;
    height: 2.5rem;
    border-radius: 0.35rem;
    border: 1px solid #e2e8f0;
    background: #fff;
    color: #475569;
    text-decoration: none;
    font-size: 1.15rem;
    transition: background 0.15s ease, color 0.15s ease;
  }
  .plan-pricing-card__btn-icon:hover {
    background: #fff;
    color: #0f172a;
  }
  .plan-pricing-card__btn-icon--danger {
    border-color: #fecaca;
    color: #c62828;
  }
  .plan-pricing-card__btn-icon--danger:hover {
    background: #fef2f2;
    color: #991b1b;
  }
  .plan-pricing-card__badge-disabled {
    position: absolute;
    top: 0.65rem;
    right: 0.65rem;
    font-size: 0.65rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    padding: 0.2rem 0.45rem;
    border-radius: 0.2rem;
    background: rgba(255, 255, 255, 0.15);
    color: rgba(255, 255, 255, 0.9);
  }
  .plan-cards-empty {
    text-align: center;
    padding: 3rem 1.5rem;
    color: #6b7280;
    font-size: 1.05rem;
  }
  .plan-pricing-card__view-more {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    margin-top: 0.6rem;
    padding: 0.35rem 0.8rem;
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--plan-accent);
    background: transparent;
    border: 1px solid var(--plan-accent);
    border-radius: 0.3rem;
    cursor: pointer;
    transition: background 0.15s ease, color 0.15s ease;
  }
  .plan-pricing-card__view-more:hover {
    background: var(--plan-accent);
    color: #fff;
  }
  .plan-details-modal .modal-content {
    border-radius: 0.45rem;
    border: 0;
  }
  .plan-details-modal .modal-header {
    background: #141824;
    color: #fff;
    border-bottom: 0;
  }
  .plan-details-modal .modal-header .close {
    color: #fff;
    text-shadow: none;
    opacity: 0.85;
  }
  .plan-details-modal dl {
    margin: 0;
  }
  .plan-details-modal dt {
    color: #6b7280;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 0.15rem;
  }
  .plan-details-modal dd {
    color: #1f2937;
    margin: 0 0 0.85rem;
    font-size: 0.95rem;
    word-break: break-word;
  }
  .plan-details-modal dd:last-child { margin-bottom: 0; }
</style>
<?php $this->load->view('admin/admin_grid_scope_styles'); ?>
<div class="container-fluid pt-3 admin-grid-scope plan-cards-scope">
  <div class="row">
    <div class="col-md-6">
      <div class="page-header">
        <h3 class="page-title"><?= htmlspecialchars($pt['list_title'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>
      </div>
    </div>
    <div class="col-md-6 text-right">
      <a href="<?= admin_url('plan/add') ?>" class="btn admin-grid-page-cta mt-2 mt-sm-0">
        <i class="mdi mdi-plus-circle" aria-hidden="true"></i> <?= htmlspecialchars($pt['list_add_new'] ?? '', ENT_QUOTES, 'UTF-8') ?>
      </a>
    </div>
  </div>
  <div class="card border-0 shadow-sm">
    <div class="card-body">
      <?php if ($totalPlans === 0) : ?>
        <p class="plan-cards-empty mb-0"><?= htmlspecialchars($pt['plan_list_empty'] ?? '', ENT_QUOTES, 'UTF-8') ?></p>
      <?php else : ?>
        <div class="plan-cards-grid">
          <?php
          $cardIndex = 0;
          foreach ($listArr as $v) :
            $l1 = isset($v->language1_id) ? (int) $v->language1_id : 0;
            $l2 = isset($v->language2_id) ? (int) $v->language2_id : 0;
            if ($l1 === 0 && isset($v->language_id) && (int) $v->language_id > 0) {
              $l1 = (int) $v->language_id;
            }
            $noneLabel = (string) ($pt['plan_language_list_none'] ?? '');
            $lang1Label = $l1 > 0 ? ($langMap[$l1] ?? ('#' . $l1)) : $noneLabel;
            $lang2Label = $l2 > 0 ? ($langMap[$l2] ?? ('#' . $l2)) : $noneLabel;
            $lang1Line = htmlspecialchars((string) ($pt['label_language_1'] ?? ''), ENT_QUOTES, 'UTF-8') . ': ' . htmlspecialchars($lang1Label, ENT_QUOTES, 'UTF-8');
            $lang2Line = htmlspecialchars((string) ($pt['label_language_2'] ?? ''), ENT_QUOTES, 'UTF-8') . ': ' . htmlspecialchars($lang2Label, ENT_QUOTES, 'UTF-8');
            $bp = isset($v->billing_period) ? (string) $v->billing_period : '';
            $bpLabel = $bp === 'annual' ? ($pt['billing_annual'] ?? $bp) : ($pt['billing_monthly'] ?? $bp);
            $cycleSuffix = $bp === 'annual'
              ? (string) ($pt['plan_card_billing_suffix_annual'] ?? '')
              : (string) ($pt['plan_card_billing_suffix_monthly'] ?? '');
            $stRaw = isset($v->status) ? $v->status : 0;
            if (is_bool($stRaw)) {
              $isPlanEnabled = $stRaw;
            } elseif (is_int($stRaw) || is_float($stRaw)) {
              $isPlanEnabled = ((int) $stRaw) !== 0;
            } elseif (is_string($stRaw)) {
              $sv = strtolower(trim($stRaw));
              if ($sv === '' || $sv === "\x00") {
                $isPlanEnabled = false;
              } elseif (ctype_digit($sv)) {
                $isPlanEnabled = (int) $sv !== 0;
              } elseif (strlen($sv) === 1) {
                $isPlanEnabled = ord($sv) > 0;
              } else {
                $isPlanEnabled = in_array($sv, ['enabled', 'active', 'true'], true);
              }
            } else {
              $isPlanEnabled = false;
            }
            $sid = function_exists('admin_plan_row_id') ? admin_plan_row_id($v) : (int) (isset($v->plan_id) ? $v->plan_id : ($v->subscription_id ?? 0));
            $rowTitle = isset($v->title) ? trim((string) $v->title) : '';
            $priceVal = isset($v->price) ? (float) $v->price : 0.0;
            $priceFmt = htmlspecialchars(number_format($priceVal, 2, '.', ''), ENT_QUOTES, 'UTF-8');
            $currency = htmlspecialchars((string) ($pt['plan_card_currency_prefix'] ?? ''), ENT_QUOTES, 'UTF-8');
            $mc = isset($v->max_clients) ? (int) $v->max_clients : 0;
            $mo = isset($v->max_orders) ? (int) $v->max_orders : 0;
            $mu = isset($v->max_users) ? (int) $v->max_users : 0;
            $rawDesc = isset($v->description) ? trim((string) $v->description) : '';
            $descShort = $rawDesc;
            if ($rawDesc !== '') {
              if (function_exists('mb_strlen') && mb_strlen($rawDesc, 'UTF-8') > 120) {
                $descShort = mb_substr($rawDesc, 0, 120, 'UTF-8') . '…';
              } elseif (strlen($rawDesc) > 120) {
                $descShort = substr($rawDesc, 0, 120) . '...';
              }
            }
            $createdAt = isset($v->created_at) ? trim((string) $v->created_at) : '';
            $updatedAt = isset($v->updated_at) ? trim((string) $v->updated_at) : '';
            $stripePriceId = isset($v->stripeld) ? trim((string) $v->stripeld) : '';
            $stripeName = isset($v->stripeName) ? trim((string) $v->stripeName) : '';
            $stripeStatusVal = isset($v->stripe_status) ? trim((string) $v->stripe_status) : '';
            $stripeDocVal = isset($v->stripe_doc) ? trim((string) $v->stripe_doc) : '';
            $stripeUrlVal = isset($v->stripe_url) ? trim((string) $v->stripe_url) : '';
            $stripePdfVal = isset($v->stripe_pdf) ? trim((string) $v->stripe_pdf) : '';
            $planNameSpa = isset($v->plan_name_spa) ? trim((string) $v->plan_name_spa) : '';
            $planNameEng = isset($v->plan_name_eng) ? trim((string) $v->plan_name_eng) : '';
            $line1Spa = isset($v->line1_spa) ? trim((string) $v->line1_spa) : '';
            $line1Eng = isset($v->line1_eng) ? trim((string) $v->line1_eng) : '';
            $paymentVal = isset($v->payment) ? (float) $v->payment : null;
            $paymentText = isset($v->paymenttextp) ? trim((string) $v->paymenttextp) : '';
            $paymentPeriod = isset($v->paymentperiocity) ? (int) $v->paymentperiocity : null;
            $daysVal = isset($v->days) ? (int) $v->days : null;
            $maxCustomers = isset($v->max_Customers) ? (int) $v->max_Customers : null;
            $neverExpire = isset($v->neverexpire) ? (int) $v->neverexpire === 1 : false;
            $yesLabel = (string) ($pt['label_yes'] ?? 'Yes');
            $noLabel = (string) ($pt['label_no'] ?? 'No');
            $detailsPayload = [
              'id' => $sid,
              'title' => $rowTitle !== '' ? $rowTitle : ('#' . $sid),
              'description' => $rawDesc,
              'descEmpty' => (string) ($pt['plan_language_list_none'] ?? ''),
              'planIdLabel' => (string) ($pt['label_plan_id'] ?? ''),
              'descLabel' => (string) ($pt['label_description'] ?? ''),
              'stripeIdLabel' => (string) ($pt['label_stripe_id'] ?? ''),
              'stripeNameLabel' => (string) ($pt['label_stripe_name'] ?? ''),
              'stripeStatusLabel' => (string) ($pt['label_stripe_status'] ?? ''),
              'stripeDocLabel' => (string) ($pt['label_stripe_doc'] ?? ''),
              'stripeUrlLabel' => (string) ($pt['label_stripe_url'] ?? ''),
              'stripePdfLabel' => (string) ($pt['label_stripe_pdf'] ?? ''),
              'createdLabel' => (string) ($pt['label_created_at'] ?? ''),
              'updatedLabel' => (string) ($pt['label_updated_at'] ?? ''),
              'stripeId' => $stripePriceId,
              'stripeName' => $stripeName,
              'stripeStatus' => $stripeStatusVal,
              'stripeDoc' => $stripeDocVal,
              'stripeUrl' => $stripeUrlVal,
              'stripePdf' => $stripePdfVal,
              'createdAt' => $createdAt,
              'updatedAt' => $updatedAt,
              'planNameSpaLabel' => (string) ($pt['label_plan_name_spa'] ?? ''),
              'planNameEngLabel' => (string) ($pt['label_plan_name_eng'] ?? ''),
              'line1SpaLabel' => (string) ($pt['label_line1_spa'] ?? ''),
              'line1EngLabel' => (string) ($pt['label_line1_eng'] ?? ''),
              'paymentLabel' => (string) ($pt['label_payment'] ?? ''),
              'paymentTextLabel' => (string) ($pt['label_payment_text'] ?? ''),
              'paymentPeriodLabel' => (string) ($pt['label_payment_periodicity'] ?? ''),
              'daysLabel' => (string) ($pt['label_days'] ?? ''),
              'maxCustomersLabel' => (string) ($pt['label_max_customers'] ?? ''),
              'neverExpireLabel' => (string) ($pt['label_never_expire'] ?? ''),
              'planNameSpa' => $planNameSpa,
              'planNameEng' => $planNameEng,
              'line1Spa' => $line1Spa,
              'line1Eng' => $line1Eng,
              'payment' => $paymentVal !== null ? number_format($paymentVal, 2, '.', '') : '',
              'paymentText' => $paymentText,
              'paymentPeriod' => $paymentPeriod !== null ? (string) $paymentPeriod : '',
              'days' => $daysVal !== null ? (string) $daysVal : '',
              'maxCustomers' => $maxCustomers !== null ? (string) $maxCustomers : '',
              'neverExpire' => $neverExpire ? $yesLabel : $noLabel,
              'ecommercePermissionLabel' => (string) ($pt['label_ecommerce_permission'] ?? ''),
              'ecommercePermission' => $ecommPerm ? $yesLabel : $noLabel,
            ];
            $detailsJson = htmlspecialchars(
              json_encode($detailsPayload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT),
              ENT_QUOTES,
              'UTF-8'
            );
            $theme = $planThemes[$cardIndex % 3];
            $isFeatured = $totalPlans >= 3 && ($cardIndex % 3 === 1);
            $cardClasses = 'plan-pricing-card';
            if ($isFeatured) {
              $cardClasses .= ' plan-pricing-card--featured';
            }
            if (! $isPlanEnabled) {
              $cardClasses .= ' plan-pricing-card--inactive';
            }
            $cardIndex++;
            ?>
            <article
              class="<?= htmlspecialchars($cardClasses, ENT_QUOTES, 'UTF-8') ?>"
              style="--plan-accent: <?= htmlspecialchars($theme['accent'], ENT_QUOTES, 'UTF-8') ?>; --plan-accentDark: <?= htmlspecialchars($theme['accentDark'], ENT_QUOTES, 'UTF-8') ?>;"
            >
              <div class="plan-pricing-card__header">
                <?php if (! $isPlanEnabled) : ?>
                  <span class="plan-pricing-card__badge-disabled"><?= htmlspecialchars($pt['plan_card_disabled_badge'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                <h4 class="plan-pricing-card__title"><?= htmlspecialchars($rowTitle !== '' ? $rowTitle : ('#' . $sid), ENT_QUOTES, 'UTF-8') ?></h4>
                <div class="plan-pricing-card__price"><?= $currency ?><?= $priceFmt ?></div>
                <div class="plan-pricing-card__cycle"><?= htmlspecialchars($cycleSuffix, ENT_QUOTES, 'UTF-8') ?></div>
                <div class="plan-pricing-card__chevron-wrap" aria-hidden="true">
                  <div class="plan-pricing-card__chevron-inner"></div>
                </div>
              </div>
              <div class="plan-pricing-card__body">
                <ul class="plan-pricing-card__features">
                  <li class="plan-pricing-card__feature">
                    <span class="plan-pricing-card__icon plan-pricing-card__icon--yes mdi mdi-check" aria-hidden="true"></span>
                    <span><?= htmlspecialchars(($pt['col_billing_period'] ?? '') . ': ' . $bpLabel, ENT_QUOTES, 'UTF-8') ?></span>
                  </li>
                  <li class="plan-pricing-card__feature">
                    <span class="plan-pricing-card__icon <?= $mc > 0 ? 'plan-pricing-card__icon--yes mdi mdi-check' : 'plan-pricing-card__icon--no' ?>" aria-hidden="true"><?= $mc > 0 ? '' : '×' ?></span>
                    <span><?= htmlspecialchars(($pt['col_max_clients'] ?? '') . ': ' . (string) $mc, ENT_QUOTES, 'UTF-8') ?></span>
                  </li>
                  <li class="plan-pricing-card__feature">
                    <span class="plan-pricing-card__icon <?= $mo > 0 ? 'plan-pricing-card__icon--yes mdi mdi-check' : 'plan-pricing-card__icon--no' ?>" aria-hidden="true"><?= $mo > 0 ? '' : '×' ?></span>
                    <span><?= htmlspecialchars(($pt['col_max_orders'] ?? '') . ': ' . (string) $mo, ENT_QUOTES, 'UTF-8') ?></span>
                  </li>
                  <li class="plan-pricing-card__feature">
                    <span class="plan-pricing-card__icon <?= $mu > 0 ? 'plan-pricing-card__icon--yes mdi mdi-check' : 'plan-pricing-card__icon--no' ?>" aria-hidden="true"><?= $mu > 0 ? '' : '×' ?></span>
                    <span><?= htmlspecialchars(($pt['col_max_users'] ?? '') . ': ' . (string) $mu, ENT_QUOTES, 'UTF-8') ?></span>
                  </li>
                  <?php
                  $daysInt = isset($v->days) ? (int) $v->days : 0;
                  $ecommRaw = $v->ecommerce_permission ?? 0;
                  if (is_bool($ecommRaw)) {
                    $ecommPerm = $ecommRaw;
                  } elseif (is_int($ecommRaw) || is_float($ecommRaw)) {
                    $ecommPerm = (int) $ecommRaw !== 0;
                  } elseif (is_string($ecommRaw)) {
                    if ($ecommRaw === '' || $ecommRaw === "\x00") {
                      $ecommPerm = false;
                    } elseif (ctype_digit($ecommRaw)) {
                      $ecommPerm = (int) $ecommRaw !== 0;
                    } elseif (strlen($ecommRaw) === 1) {
                      $ecommPerm = ord($ecommRaw) > 0;
                    } else {
                      $ecommPerm = in_array(strtolower(trim($ecommRaw)), ['1', 'true', 'yes', 'on'], true);
                    }
                  } else {
                    $ecommPerm = false;
                  }
                  ?>
                  <li class="plan-pricing-card__feature">
                    <span class="plan-pricing-card__icon <?= $daysInt > 0 ? 'plan-pricing-card__icon--yes mdi mdi-check' : 'plan-pricing-card__icon--no' ?>" aria-hidden="true"><?= $daysInt > 0 ? '' : '×' ?></span>
                    <span><?= htmlspecialchars(($pt['label_days'] ?? '') . ': ' . (string) $daysInt, ENT_QUOTES, 'UTF-8') ?></span>
                  </li>
                  <li class="plan-pricing-card__feature">
                    <span class="plan-pricing-card__icon <?= $ecommPerm ? 'plan-pricing-card__icon--yes mdi mdi-check' : 'plan-pricing-card__icon--no' ?>" aria-hidden="true"><?= $ecommPerm ? '' : '×' ?></span>
                    <span><?= htmlspecialchars((string) ($pt['label_ecommerce_permission'] ?? '') . ': ' . ($ecommPerm ? (string) ($pt['label_yes'] ?? 'Yes') : (string) ($pt['label_no'] ?? 'No')), ENT_QUOTES, 'UTF-8') ?></span>
                  </li>
                  <?php if ($descShort !== '') : ?>
                    <li class="plan-pricing-card__feature">
                      <span class="plan-pricing-card__icon plan-pricing-card__icon--yes mdi mdi-check" aria-hidden="true"></span>
                      <span><?= htmlspecialchars(($pt['col_description'] ?? '') . ': ' . $descShort, ENT_QUOTES, 'UTF-8') ?></span>
                    </li>
                  <?php endif; ?>
                </ul>
              </div>
              <div class="plan-pricing-card__footer">
                <a class="plan-pricing-card__btn" href="<?= admin_url('plan/edit/' . $sid) ?>"><?= htmlspecialchars($pt['plan_card_cta_select'] ?? '', ENT_QUOTES, 'UTF-8') ?></a>
                <button type="button" class="plan-pricing-card__view-more" data-plan-details="<?= $detailsJson ?>">
                  <i class="mdi mdi-information-outline" aria-hidden="true"></i>
                  <?= htmlspecialchars($pt['btn_view_more'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </button>
                <div class="plan-pricing-card__admin" role="group" aria-label="<?= htmlspecialchars((string) ($pt['plan_card_admin_tools_aria'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                  <div class="plan-pricing-card__switch-wrap">
                    <div class="custom-control custom-switch plan-list-status-switch">
                      <input
                        type="checkbox"
                        class="custom-control-input plan-status-toggle"
                        id="planStatus<?= $sid ?>"
                        data-plan-id="<?= $sid ?>"
                        <?= $isPlanEnabled ? 'checked' : '' ?>
                        aria-label="<?= htmlspecialchars((string) ($pt['plan_status_switch_aria'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                      >
                      <label class="custom-control-label mb-0" for="planStatus<?= $sid ?>">
                        <span class="sr-only"><?= htmlspecialchars((string) ($pt['plan_status_switch_aria'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                      </label>
                    </div>
                  </div>
                  <div class="plan-pricing-card__btn-group">
                    <a href="<?= admin_url('plan/edit/' . $sid) ?>" class="plan-pricing-card__btn-icon" data-toggle="tooltip" title="<?= htmlspecialchars($pt['tooltip_edit'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                      <i class="mdi mdi-pencil" aria-hidden="true"></i>
                    </a>
                    <button type="button" class="plan-pricing-card__btn-icon plan-pricing-card__btn-icon--danger" data-toggle="tooltip" title="<?= htmlspecialchars($pt['tooltip_delete'] ?? '', ENT_QUOTES, 'UTF-8') ?>" onclick="deletePlan(<?= $sid ?>)">
                      <i class="mdi mdi-delete" aria-hidden="true"></i>
                    </button>
                  </div>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<div class="modal fade plan-details-modal" id="planDetailsModal" tabindex="-1" role="dialog" aria-labelledby="planDetailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="planDetailsModalLabel">
          <span data-plan-details-title><?= htmlspecialchars($pt['modal_title_plan_details'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="<?= htmlspecialchars($pt['modal_close'] ?? 'Close', ENT_QUOTES, 'UTF-8') ?>">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <dl class="row">
          <div class="col-12 col-md-6 mb-2">
            <dt data-detail-label="planId"></dt>
            <dd data-detail-value="planId"></dd>
          </div>
          <div class="col-12 col-md-6 mb-2">
            <dt data-detail-label="description"></dt>
            <dd data-detail-value="description"></dd>
          </div>
          <div class="col-12 col-md-6 mb-2">
            <dt data-detail-label="planNameSpa"></dt>
            <dd data-detail-value="planNameSpa"></dd>
          </div>
          <div class="col-12 col-md-6 mb-2">
            <dt data-detail-label="planNameEng"></dt>
            <dd data-detail-value="planNameEng"></dd>
          </div>
          <div class="col-12 col-md-6 mb-2">
            <dt data-detail-label="line1Spa"></dt>
            <dd data-detail-value="line1Spa"></dd>
          </div>
          <div class="col-12 col-md-6 mb-2">
            <dt data-detail-label="line1Eng"></dt>
            <dd data-detail-value="line1Eng"></dd>
          </div>
          <div class="col-12 col-md-6 mb-2">
            <dt data-detail-label="payment"></dt>
            <dd data-detail-value="payment"></dd>
          </div>
          <div class="col-12 col-md-6 mb-2">
            <dt data-detail-label="paymentPeriod"></dt>
            <dd data-detail-value="paymentPeriod"></dd>
          </div>
          <div class="col-12 col-md-6 mb-2">
            <dt data-detail-label="paymentText"></dt>
            <dd data-detail-value="paymentText"></dd>
          </div>
          <div class="col-12 col-md-6 mb-2">
            <dt data-detail-label="days"></dt>
            <dd data-detail-value="days"></dd>
          </div>
          <div class="col-12 col-md-6 mb-2">
            <dt data-detail-label="maxCustomers"></dt>
            <dd data-detail-value="maxCustomers"></dd>
          </div>
          <div class="col-12 col-md-6 mb-2">
            <dt data-detail-label="neverExpire"></dt>
            <dd data-detail-value="neverExpire"></dd>
          </div>
          <div class="col-12 col-md-6 mb-2">
            <dt data-detail-label="ecommercePermission"></dt>
            <dd data-detail-value="ecommercePermission"></dd>
          </div>
          <div class="col-12 col-md-6 mb-2">
            <dt data-detail-label="stripeId"></dt>
            <dd data-detail-value="stripeId"></dd>
          </div>
          <div class="col-12 col-md-6 mb-2">
            <dt data-detail-label="stripeName"></dt>
            <dd data-detail-value="stripeName"></dd>
          </div>
          <div class="col-12 col-md-6 mb-2">
            <dt data-detail-label="stripeStatus"></dt>
            <dd data-detail-value="stripeStatus"></dd>
          </div>
          <div class="col-12 col-md-6 mb-2">
            <dt data-detail-label="stripeDoc"></dt>
            <dd data-detail-value="stripeDoc"></dd>
          </div>
          <div class="col-12 mb-2">
            <dt data-detail-label="stripeUrl"></dt>
            <dd data-detail-value="stripeUrl" data-detail-link="stripeUrl"></dd>
          </div>
          <div class="col-12 mb-2">
            <dt data-detail-label="stripePdf"></dt>
            <dd data-detail-value="stripePdf" data-detail-link="stripePdf"></dd>
          </div>
          <div class="col-12 col-md-6 mb-2">
            <dt data-detail-label="createdAt"></dt>
            <dd data-detail-value="createdAt"></dd>
          </div>
          <div class="col-12 col-md-6 mb-2">
            <dt data-detail-label="updatedAt"></dt>
            <dd data-detail-value="updatedAt"></dd>
          </div>
        </dl>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= htmlspecialchars($pt['modal_close'] ?? 'Close', ENT_QUOTES, 'UTF-8') ?></button>
      </div>
    </div>
  </div>
</div>
<script>
const planListLang = <?= json_encode([
	'statusError' => (string) ($pt['plan_status_update_error'] ?? ''),
	'modalBaseTitle' => (string) ($pt['modal_title_plan_details'] ?? ''),
	'emptyDash' => (string) ($pt['plan_language_list_none'] ?? '—'),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
const planStatusUrl = <?= json_encode($planStatusUrlJs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

function deletePlan(id) {
  swal.fire({
    title: <?= $swalTitle ?>,
    text: <?= $swalText ?>,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#6b7280',
    confirmButtonText: <?= $swalYes ?>,
    cancelButtonText: <?= $swalNo ?>
  }).then((result) => {
    if (result.isConfirmed) {
      window.location.href = <?= json_encode($deleteBase, JSON_UNESCAPED_UNICODE) ?> + id;
    }
  });
}

$(function () {
  function fillDetailCell(key, label, value) {
    var safeLabel = label ? String(label) : '';
    var safeValue = value && String(value).trim() !== '' ? String(value) : (planListLang.emptyDash || '—');
    document.querySelectorAll('[data-detail-label="' + key + '"]').forEach(function (el) {
      el.textContent = safeLabel;
      el.style.display = safeLabel ? '' : 'none';
    });
    document.querySelectorAll('[data-detail-value="' + key + '"]').forEach(function (el) {
      el.textContent = safeValue;
      el.style.display = safeLabel ? '' : 'none';
    });
  }

  function fillDetailLink(key, label, value) {
    var safeLabel = label ? String(label) : '';
    var raw = value && String(value).trim() !== '' ? String(value).trim() : '';
    document.querySelectorAll('[data-detail-label="' + key + '"]').forEach(function (el) {
      el.textContent = safeLabel;
      el.style.display = safeLabel ? '' : 'none';
    });
    document.querySelectorAll('[data-detail-link="' + key + '"]').forEach(function (el) {
      el.innerHTML = '';
      el.style.display = safeLabel ? '' : 'none';
      if (raw === '') {
        el.textContent = planListLang.emptyDash || '—';
        return;
      }
      if (/^https?:\/\//i.test(raw)) {
        var a = document.createElement('a');
        a.href = raw;
        a.target = '_blank';
        a.rel = 'noopener noreferrer';
        a.textContent = raw;
        el.appendChild(a);
      } else {
        el.textContent = raw;
      }
    });
  }

  $(document).on('click', '.plan-pricing-card__view-more', function () {
    var raw = this.getAttribute('data-plan-details') || '{}';
    var info;
    try { info = JSON.parse(raw); } catch (e) { info = {}; }
    var baseTitle = planListLang.modalBaseTitle || '';
    var titleSpan = document.querySelector('[data-plan-details-title]');
    if (titleSpan) {
      titleSpan.textContent = info.title ? (baseTitle + ' · ' + info.title) : baseTitle;
    }
    fillDetailCell('planId', info.planIdLabel, info.id);
    fillDetailCell('description', info.descLabel, info.description);
    fillDetailCell('planNameSpa', info.planNameSpaLabel, info.planNameSpa);
    fillDetailCell('planNameEng', info.planNameEngLabel, info.planNameEng);
    fillDetailCell('line1Spa', info.line1SpaLabel, info.line1Spa);
    fillDetailCell('line1Eng', info.line1EngLabel, info.line1Eng);
    fillDetailCell('payment', info.paymentLabel, info.payment);
    fillDetailCell('paymentPeriod', info.paymentPeriodLabel, info.paymentPeriod);
    fillDetailCell('paymentText', info.paymentTextLabel, info.paymentText);
    fillDetailCell('days', info.daysLabel, info.days);
    fillDetailCell('maxCustomers', info.maxCustomersLabel, info.maxCustomers);
    fillDetailCell('neverExpire', info.neverExpireLabel, info.neverExpire);
    fillDetailCell('ecommercePermission', info.ecommercePermissionLabel, info.ecommercePermission);
    fillDetailCell('stripeId', info.stripeIdLabel, info.stripeId);
    fillDetailCell('stripeName', info.stripeNameLabel, info.stripeName);
    fillDetailCell('stripeStatus', info.stripeStatusLabel, info.stripeStatus);
    fillDetailCell('stripeDoc', info.stripeDocLabel, info.stripeDoc);
    fillDetailLink('stripeUrl', info.stripeUrlLabel, info.stripeUrl);
    fillDetailLink('stripePdf', info.stripePdfLabel, info.stripePdf);
    fillDetailCell('createdAt', info.createdLabel, info.createdAt);
    fillDetailCell('updatedAt', info.updatedLabel, info.updatedAt);
    if (typeof $('#planDetailsModal').modal === 'function') {
      $('#planDetailsModal').modal('show');
    }
  });

  $(document).on('change', '.plan-cards-scope .plan-status-toggle', function () {
    var $cb = $(this);
    var id = parseInt($cb.data('plan-id'), 10);
    var enabled = $cb.is(':checked') ? 1 : 0;
    var previous = enabled ? 0 : 1;
    $.ajax({
      url: planStatusUrl,
      method: 'POST',
      dataType: 'json',
      data: { plan_id: id, plan_enabled: enabled }
    }).done(function (arr) {
      if (Array.isArray(arr) && arr[1] === 'success') {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: arr[0],
            showConfirmButton: false,
            timer: 2000
          });
        }
        var $card = $cb.closest('.plan-pricing-card');
        if (enabled) {
          $card.removeClass('plan-pricing-card--inactive').find('.plan-pricing-card__badge-disabled').remove();
        } else {
          $card.addClass('plan-pricing-card--inactive');
          if ($card.find('.plan-pricing-card__badge-disabled').length === 0) {
            var badgeText = <?= json_encode((string) ($pt['plan_card_disabled_badge'] ?? ''), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
            $card.find('.plan-pricing-card__header').prepend(
              '<span class="plan-pricing-card__badge-disabled">' + (badgeText || '') + '</span>'
            );
          }
        }
      } else {
        $cb.prop('checked', !!previous);
        var msg = (Array.isArray(arr) && arr[0]) ? arr[0] : (planListLang.statusError || '');
        if (typeof Swal !== 'undefined') {
          Swal.fire({ icon: 'error', title: msg });
        }
      }
    }).fail(function () {
      $cb.prop('checked', !!previous);
      if (typeof Swal !== 'undefined') {
        Swal.fire({ icon: 'error', title: planListLang.statusError || '' });
      }
    });
  });
});
</script>

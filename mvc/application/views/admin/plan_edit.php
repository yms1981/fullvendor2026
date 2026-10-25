<?php
/** @var array<string, string>|null $pageTexts */
/** @var object $data */
$pt = function_exists('admin_plan_page_texts')
	? admin_plan_page_texts(isset($pageTexts) && is_array($pageTexts) ? $pageTexts : null)
	: [];
$d = isset($data) ? $data : (object) [];
$planPk = isset($planPk) && is_string($planPk) && $planPk !== '' ? $planPk : (function_exists('admin_plan_primary_key_column') ? admin_plan_primary_key_column() : 'plan_id');
$sid = function_exists('admin_plan_row_id') ? admin_plan_row_id($d) : (int) (isset($d->{$planPk}) ? $d->{$planPk} : ($d->plan_id ?? $d->subscription_id ?? 0));
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
          <form class="forms-sample" action="<?= admin_url('plan/update') ?>" method="post">
            <input type="hidden" name="plan_id" value="<?= (int) $sid ?>">
            <?php $this->load->view('admin/plan_form_fields', [
              'pt' => $pt,
              'd' => $d,
              'language' => $language ?? [],
              'hasLangSlots' => isset($hasLangSlots) ? (bool) $hasLangSlots : false,
            ]); ?>
            <div class="form-group mt-3">
              <button type="button" class="btn btn-outline-primary mr-2" id="btnStripeSyncPlanCatalog" data-plan-id="<?= (int) $sid ?>">
                <i class="mdi mdi-sync" aria-hidden="true"></i> <?= htmlspecialchars($pt['btn_stripe_sync_catalog'] ?? '', ENT_QUOTES, 'UTF-8') ?>
              </button>
              <button type="submit" class="btn btn-success mr-2"><?= htmlspecialchars($pt['btn_submit'] ?? '', ENT_QUOTES, 'UTF-8') ?></button>
              <a class="btn btn-danger mr-2" href="<?= admin_url('plan/lists') ?>"><?= htmlspecialchars($pt['btn_cancel'] ?? '', ENT_QUOTES, 'UTF-8') ?></a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
  const planStripeUi = <?= json_encode([
    'confirmTitle' => $pt['confirm_stripe_sync_catalog_title'] ?? '',
    'confirmText' => $pt['confirm_stripe_sync_catalog_text'] ?? '',
    'confirmYes' => $pt['btn_submit'] ?? 'OK',
    'confirmNo' => $pt['btn_cancel'] ?? 'Cancel',
  ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  const stripePlanSyncBase = <?= json_encode(admin_url('plan/stripe_sync_catalog/'), JSON_UNESCAPED_UNICODE) ?>;
  document.getElementById('btnStripeSyncPlanCatalog')?.addEventListener('click', function () {
    var id = this.getAttribute('data-plan-id');
    swal.fire({
      title: planStripeUi.confirmTitle || '',
      text: planStripeUi.confirmText || '',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#16a34a',
      cancelButtonColor: '#6b7280',
      confirmButtonText: planStripeUi.confirmYes || '',
      cancelButtonText: planStripeUi.confirmNo || '',
    }).then(function (result) {
      if (result.isConfirmed && id) {
        window.location.href = stripePlanSyncBase + id;
      }
    });
  });
</script>

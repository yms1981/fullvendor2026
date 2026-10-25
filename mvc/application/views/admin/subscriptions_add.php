<?php
/** @var array<string, string>|null $pageTexts */
$pt = function_exists('admin_subscriptions_page_texts')
	? admin_subscriptions_page_texts(isset($pageTexts) && is_array($pageTexts) ? $pageTexts : null)
	: [];
$this->load->view('admin/admin_grid_scope_styles');
?>
<div class="container-fluid page-body-wrapper admin-grid-scope">
  <div class="main-panel">
    <div class="p-4" style="min-height: 500px;">
      <div class="page-header">
        <h3 class="page-title"><?= htmlspecialchars($pt['form_add_title'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>
      </div>
      <div class="card">
        <div class="card-body">
          <form class="forms-sample" action="<?= admin_url('subscriptions/create') ?>" method="post">
            <div class="form-group row">
              <div class="col-md-6">
                <label><?= htmlspecialchars($pt['label_company'] ?? '', ENT_QUOTES, 'UTF-8') ?> <span class="text-danger">*</span></label>
                <select class="form-control" name="company_id" id="company_id" required>
                  <option value=""><?= htmlspecialchars($pt['select_company'] ?? '', ENT_QUOTES, 'UTF-8') ?></option>
                  <?php if (! empty($companies)) : ?>
                    <?php foreach ($companies as $c) : ?>
                      <?php $cid = isset($c->company_id) ? (int) $c->company_id : 0; ?>
                      <?php if ($cid < 1) {
                        continue;
                      } ?>
                      <option value="<?= $cid ?>"><?= htmlspecialchars(trim((string) ($c->name ?? '')), ENT_QUOTES, 'UTF-8') ?></option>
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
                      <?php $pid = function_exists('admin_plan_row_id') ? admin_plan_row_id($p) : (int) (isset($p->plan_id) ? $p->plan_id : ($p->subscription_id ?? 0)); ?>
                      <?php if ($pid < 1) {
                        continue;
                      } ?>
                      <option value="<?= $pid ?>"><?= htmlspecialchars(trim((string) ($p->title ?? '')), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </select>
              </div>
            </div>
            <div class="form-group row">
              <div class="col-md-6">
                <label><?= htmlspecialchars($pt['label_status'] ?? '', ENT_QUOTES, 'UTF-8') ?> <span class="text-danger">*</span></label>
                <select class="form-control" name="status" id="status" required>
                  <option value="enabled" selected><?= htmlspecialchars($pt['status_option_enabled'] ?? '', ENT_QUOTES, 'UTF-8') ?></option>
                  <option value="disabled"><?= htmlspecialchars($pt['status_option_disabled'] ?? '', ENT_QUOTES, 'UTF-8') ?></option>
                </select>
              </div>
              <div class="col-md-6">
                <label><?= htmlspecialchars($pt['label_notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <textarea class="form-control" name="notes" id="notes" rows="3" maxlength="65535" placeholder="<?= htmlspecialchars($pt['placeholder_notes'] ?? '', ENT_QUOTES, 'UTF-8') ?>"></textarea>
              </div>
            </div>
            <div class="form-group row">
              <div class="col-md-6">
                <label><?= htmlspecialchars($pt['label_starts_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="date" class="form-control" name="start_period_date" id="start_period_date" value="">
              </div>
              <div class="col-md-6">
                <label><?= htmlspecialchars($pt['label_ends_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="date" class="form-control" name="end_period_date" id="end_period_date" value="">
              </div>
            </div>
            <h5 class="mb-3 mt-4"><?= htmlspecialchars($pt['section_stripe_details'] ?? '', ENT_QUOTES, 'UTF-8') ?></h5>
            <p class="text-muted small mb-3"><?= htmlspecialchars($pt['stripe_readonly_notice_add'] ?? ($pt['stripe_readonly_notice'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            <div class="form-group row">
              <div class="col-md-6">
                <label><?= htmlspecialchars($pt['label_stripe_subscription_id'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" readonly disabled value="">
              </div>
              <div class="col-md-6">
                <label><?= htmlspecialchars($pt['label_stripe_status'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" readonly disabled value="">
              </div>
            </div>
            <div class="form-group row">
              <div class="col-md-6">
                <label><?= htmlspecialchars($pt['label_stripe_price_id'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" readonly disabled value="">
              </div>
              <div class="col-md-3">
                <label><?= htmlspecialchars($pt['label_stripe_currency'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" readonly disabled value="">
              </div>
              <div class="col-md-3">
                <label><?= htmlspecialchars($pt['label_stripe_collection_method'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" readonly disabled value="">
              </div>
            </div>
            <div class="form-group row">
              <div class="col-md-6">
                <label><?= htmlspecialchars($pt['label_stripe_current_period_start'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" readonly disabled value="">
              </div>
              <div class="col-md-6">
                <label><?= htmlspecialchars($pt['label_stripe_current_period_end'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" readonly disabled value="">
              </div>
            </div>
            <div class="form-group row">
              <div class="col-md-6">
                <label><?= htmlspecialchars($pt['label_stripe_trial_start'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" readonly disabled value="">
              </div>
              <div class="col-md-6">
                <label><?= htmlspecialchars($pt['label_stripe_trial_end'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" readonly disabled value="">
              </div>
            </div>
            <div class="form-group row">
              <div class="col-md-3">
                <label><?= htmlspecialchars($pt['label_stripe_cancel_at_period_end'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" readonly disabled value="">
              </div>
              <div class="col-md-3">
                <label><?= htmlspecialchars($pt['label_stripe_cancel_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" readonly disabled value="">
              </div>
              <div class="col-md-3">
                <label><?= htmlspecialchars($pt['label_stripe_canceled_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" readonly disabled value="">
              </div>
              <div class="col-md-3">
                <label><?= htmlspecialchars($pt['label_stripe_ended_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" readonly disabled value="">
              </div>
            </div>
            <div class="form-group row">
              <div class="col-md-6">
                <label><?= htmlspecialchars($pt['label_stripe_latest_invoice_id'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" readonly disabled value="">
              </div>
              <div class="col-md-6">
                <label><?= htmlspecialchars($pt['label_stripe_default_payment_method_id'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" readonly disabled value="">
              </div>
            </div>
            <div class="form-group row">
              <div class="col-md-6">
                <label><?= htmlspecialchars($pt['label_stripe_created_at'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" readonly disabled value="">
              </div>
            </div>
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

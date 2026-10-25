<?php
$pt = is_array($pageTexts ?? null) ? $pageTexts : [];
$h = static function (string $key, string $fallback = '') use ($pt): string {
	$v = trim((string) ($pt[$key] ?? ''));

	return $v !== '' ? $v : $fallback;
};
?>
<div class="container-fluid pb-0 pt-3">
  <div class="page-header">
    <h3 class="page-title"><?= htmlspecialchars((string) (@$title ?? ''), ENT_QUOTES, 'UTF-8') ?></h3>
  </div>
  <div class="card">
    <div class="card-body">
      <form class="forms-sample" action="<?= admin_url('change_password/update') ?>" method="post">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label><?= htmlspecialchars($h('label_current_password', 'Current password'), ENT_QUOTES, 'UTF-8') ?> <span class="color-red">*</span></label>
              <input type="password" class="form-control" name="c_password" id="c_password" autocomplete="off" required>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label><?= htmlspecialchars($h('label_new_password', 'New password'), ENT_QUOTES, 'UTF-8') ?> <span class="color-red">*</span></label>
              <input type="password" class="form-control" name="n_password_confirmation" id="n_password_confirmation" autocomplete="off" required>
            </div>
          </div>
          <div class="col-md-6">
            <div class="form-group">
              <label><?= htmlspecialchars($h('label_repeat_password', 'Repeat password'), ENT_QUOTES, 'UTF-8') ?> <span class="color-red">*</span></label>
              <input type="password" class="form-control" name="n_password" id="n_password" autocomplete="off" required>
            </div>
          </div>
        </div>
        <div class="form-group">
          <button type="submit" class="btn btn-success mr-2"><?= htmlspecialchars($h('btn_update', 'Update'), ENT_QUOTES, 'UTF-8') ?></button>
          <a class="btn btn-danger mr-2" href="<?= admin_url('profile') ?>"><?= htmlspecialchars($h('btn_back', 'Back'), ENT_QUOTES, 'UTF-8') ?></a>
        </div>
      </form>
    </div>
  </div>
</div>

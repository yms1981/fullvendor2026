<?php
/** @var array<string, string> $categoryFormLang */
/** @var object $data */
$cf = isset($categoryFormLang) && is_array($categoryFormLang) ? $categoryFormLang : [];
$cfGet = static function (array $lang, string $key, string $fb): string {
	$v = $lang[$key] ?? '';

	return is_string($v) && $v !== '' ? $v : $fb;
};
$d = isset($data) ? $data : (object) [];
$st = isset($d->category_status) ? (int) $d->category_status : 1;
$this->load->view('admin/admin_grid_scope_styles');
?>
<div class="container-fluid page-body-wrapper admin-grid-scope">
  <div class="main-panel">
    <div class="p-4" style="min-height: 500px;">
      <div class="page-header">
        <h3 class="page-title"><?= htmlspecialchars((string) ($title ?? ''), ENT_QUOTES, 'UTF-8') ?></h3>
      </div>
      <div class="card">
        <div class="card-body">
          <form class="forms-sample" action="<?= admin_url('category/update') ?>" method="post" enctype="multipart/form-data">
            <input type="hidden" name="cat_id" value="<?= (int) ($d->category_id ?? 0) ?>" required>
            <div class="form-group row">
              <div class="col-md-6">
                <label><?= htmlspecialchars($cfGet($cf, 'label_category_name', 'Category name'), ENT_QUOTES, 'UTF-8') ?> <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="category_name" id="category_name" autocomplete="off" value="<?= htmlspecialchars((string) ($d->category_name ?? ''), ENT_QUOTES, 'UTF-8') ?>" required placeholder="<?= htmlspecialchars($cfGet($cf, 'placeholder_category_name', 'Category name'), ENT_QUOTES, 'UTF-8') ?>">
              </div>
              <div class="col-md-6">
                <label><?= htmlspecialchars($cfGet($cf, 'label_status', 'Status'), ENT_QUOTES, 'UTF-8') ?> <span class="text-danger">*</span></label>
                <select class="form-control" name="category_status" id="category_status" required>
                  <option value="1" <?= $st === 1 ? 'selected' : '' ?>><?= htmlspecialchars($cfGet($cf, 'status_option_enable', 'Enabled'), ENT_QUOTES, 'UTF-8') ?></option>
                  <option value="0" <?= $st !== 1 ? 'selected' : '' ?>><?= htmlspecialchars($cfGet($cf, 'status_option_disable', 'Disabled'), ENT_QUOTES, 'UTF-8') ?></option>
                </select>
              </div>
            </div>
            <div class="form-group">
              <button type="submit" class="btn btn-success mr-2"><?= htmlspecialchars($cfGet($cf, 'btn_submit', 'Save'), ENT_QUOTES, 'UTF-8') ?></button>
              <a class="btn btn-danger mr-2" href="<?= admin_url('category/lists') ?>"><?= htmlspecialchars($cfGet($cf, 'btn_cancel', 'Cancel'), ENT_QUOTES, 'UTF-8') ?></a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

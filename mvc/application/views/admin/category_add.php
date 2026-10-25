<?php
/** @var array<string, string> $categoryFormLang */
$cf = isset($categoryFormLang) && is_array($categoryFormLang) ? $categoryFormLang : [];
$cfGet = static function (array $lang, string $key, string $fb): string {
	$v = $lang[$key] ?? '';

	return is_string($v) && $v !== '' ? $v : $fb;
};
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
          <form class="forms-sample" action="<?= admin_url('category/create') ?>" method="post">
            <div class="form-group row">
              <div class="col-md-6">
                <label><?= htmlspecialchars($cfGet($cf, 'label_category_name', 'Category name'), ENT_QUOTES, 'UTF-8') ?> <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="category_name" id="category_name" autocomplete="off" value="" required placeholder="<?= htmlspecialchars($cfGet($cf, 'placeholder_category_name', 'Category name'), ENT_QUOTES, 'UTF-8') ?>">
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

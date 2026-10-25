<?php
/** @var array<string, string>|null $pageTexts */
$pt = function_exists('admin_plan_page_texts')
	? admin_plan_page_texts(isset($pageTexts) && is_array($pageTexts) ? $pageTexts : null)
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
          <form class="forms-sample" action="<?= admin_url('plan/create') ?>" method="post">
            <?php $this->load->view('admin/plan_form_fields', [
              'pt' => $pt,
              'd' => null,
              'language' => $language ?? [],
              'hasLangSlots' => isset($hasLangSlots) ? (bool) $hasLangSlots : false,
            ]); ?>
            <div class="form-group mt-3">
              <button type="submit" class="btn btn-success mr-2"><?= htmlspecialchars($pt['btn_submit'] ?? '', ENT_QUOTES, 'UTF-8') ?></button>
              <a class="btn btn-danger mr-2" href="<?= admin_url('plan/lists') ?>"><?= htmlspecialchars($pt['btn_cancel'] ?? '', ENT_QUOTES, 'UTF-8') ?></a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

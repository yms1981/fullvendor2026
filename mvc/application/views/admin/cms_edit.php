<?php
/** @var array<string, string>|null $pageTexts */
/** @var object|null $data */
$pt = function_exists('admin_cms_page_texts')
	? admin_cms_page_texts(isset($pageTexts) && is_array($pageTexts) ? $pageTexts : null)
	: [];
$d = isset($data) ? $data : (object) [];
$pid = isset($d->pageId) ? (int) $d->pageId : 0;
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
          <form class="forms-sample" action="<?= admin_url('cms/update') ?>" method="post" enctype="multipart/form-data">
            <input type="hidden" name="pageId" value="<?= $pid ?>">
            <div class="form-group row">
              <div class="col-md-12">
                <label><?= htmlspecialchars($pt['label_page_title'] ?? '', ENT_QUOTES, 'UTF-8') ?> <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="pageTitle" id="pageTitle" maxlength="100" required value="<?= htmlspecialchars((string) ($d->pageTitle ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars($pt['placeholder_page_title'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
              </div>
            </div>
            <div class="form-group row">
              <div class="col-md-12">
                <label><?= htmlspecialchars($pt['label_page_text'] ?? '', ENT_QUOTES, 'UTF-8') ?> <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="pageText" id="pageText" maxlength="250" required value="<?= htmlspecialchars((string) ($d->pageText ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars($pt['placeholder_page_text'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
              </div>
            </div>
            <div class="form-group row">
              <div class="col-md-12">
                <label><?= htmlspecialchars($pt['label_link'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" name="link" id="link" maxlength="255" value="<?= htmlspecialchars((string) ($d->link ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars($pt['placeholder_link'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
              </div>
            </div>
            <div class="form-group">
              <label><?= htmlspecialchars($pt['label_content'] ?? '', ENT_QUOTES, 'UTF-8') ?> <span class="text-danger">*</span></label>
              <textarea name="content" class="form-control" id="summernoteExample"><?= $d->content ?? '' ?></textarea>
            </div>
            <div class="form-group">
              <button type="submit" class="btn btn-success mr-2"><?= htmlspecialchars($pt['btn_submit'] ?? '', ENT_QUOTES, 'UTF-8') ?></button>
              <a class="btn btn-danger mr-2" href="<?= admin_url('cms') ?>"><?= htmlspecialchars($pt['btn_cancel'] ?? '', ENT_QUOTES, 'UTF-8') ?></a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
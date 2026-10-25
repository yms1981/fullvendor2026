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
<style>
  .admin-cms-html-preview {
    max-height: 70vh;
    overflow: auto;
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: 12px;
    padding: 1.25rem;
    background: #fafafa;
  }
</style>
<div class="container-fluid page-body-wrapper admin-grid-scope">
  <div class="main-panel">
    <div class="p-4" style="min-height: 500px;">
      <div class="page-header d-flex flex-wrap justify-content-between align-items-center">
        <h3 class="page-title mb-2 mb-md-0"><?= htmlspecialchars($pt['form_view_title'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>
        <div class="admin-grid-actions">
          <a href="<?= admin_url('cms') ?>" class="btn admin-grid-btn admin-grid-btn--view" data-toggle="tooltip" title="<?= htmlspecialchars($pt['view_back'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <i class="mdi mdi-format-list-bulleted" aria-hidden="true"></i>
          </a>
          <?php if ($pid > 0) : ?>
            <a href="<?= admin_url('cms/edit/' . $pid) ?>" class="btn admin-grid-btn admin-grid-btn--edit" data-toggle="tooltip" title="<?= htmlspecialchars($pt['view_edit'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
              <i class="mdi mdi-pencil" aria-hidden="true"></i>
            </a>
          <?php endif; ?>
        </div>
      </div>
      <div class="card">
        <div class="card-body">
          <div class="form-group row">
            <div class="col-md-12">
              <label><?= htmlspecialchars($pt['label_page_title'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
              <p class="form-control-plaintext border rounded px-3 py-2 bg-white"><?= htmlspecialchars((string) ($d->pageTitle ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
          </div>
          <div class="form-group row">
            <div class="col-md-12">
              <label><?= htmlspecialchars($pt['label_page_text'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
              <p class="form-control-plaintext border rounded px-3 py-2 bg-white"><?= htmlspecialchars((string) ($d->pageText ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
          </div>
          <div class="form-group row">
            <div class="col-md-12">
              <label><?= htmlspecialchars($pt['label_link'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
              <p class="form-control-plaintext border rounded px-3 py-2 bg-white"><?php $lk = trim((string) ($d->link ?? '')); echo $lk !== '' ? htmlspecialchars($lk, ENT_QUOTES, 'UTF-8') : '—'; ?></p>
            </div>
          </div>
          <div class="form-group">
            <label><?= htmlspecialchars($pt['content_heading'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
            <div class="admin-cms-html-preview"><?= isset($d->content) ? $d->content : '' ?></div>
          </div>
          <div class="form-group mb-0">
            <a class="btn btn-danger mr-2" href="<?= admin_url('cms') ?>"><?= htmlspecialchars($pt['view_back'] ?? '', ENT_QUOTES, 'UTF-8') ?></a>
            <?php if ($pid > 0) : ?>
              <a class="btn btn-success mr-2" href="<?= admin_url('cms/edit/' . $pid) ?>"><?= htmlspecialchars($pt['view_edit'] ?? '', ENT_QUOTES, 'UTF-8') ?></a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

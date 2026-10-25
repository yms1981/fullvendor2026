<?php
/** @var array<string, string>|null $pageTexts */
$pt = function_exists('admin_cms_page_texts')
	? admin_cms_page_texts(isset($pageTexts) && is_array($pageTexts) ? $pageTexts : null)
	: [];
$clGet = static function (array $lang, string $key, string $fb): string {
	$v = $lang[$key] ?? '';

	return is_string($v) && $v !== '' ? $v : $fb;
};
$cmsDeleteUi = isset($cmsDeleteUi) && is_array($cmsDeleteUi) ? $cmsDeleteUi : [];
$deleteBase = admin_url('cms/delete/');
$this->load->view('admin/admin_grid_scope_styles');
?>
<div class="container-fluid pt-3 admin-grid-scope">
  <div class="row">
    <div class="col-md-6">
      <div class="page-header">
        <h3 class="page-title"><?= htmlspecialchars($clGet($pt, 'list_title', 'CMS pages'), ENT_QUOTES, 'UTF-8') ?></h3>
      </div>
    </div>
    <div class="col-md-6 text-right">
      <a href="<?= admin_url('cms/add') ?>" class="btn admin-grid-page-cta mt-2 mt-sm-0">
        <i class="mdi mdi-plus-circle" aria-hidden="true"></i> <?= htmlspecialchars($clGet($pt, 'list_add_new', 'Add page'), ENT_QUOTES, 'UTF-8') ?>
      </a>
    </div>
  </div>
  <div class="card">
    <div class="card-body">
      <table id="cms-data-table" class="table table-bordered w-100">
        <thead>
          <tr>
            <th style="width:3rem;"><?= htmlspecialchars($clGet($pt, 'col_num', '#'), ENT_QUOTES, 'UTF-8') ?></th>
            <th><?= htmlspecialchars($clGet($pt, 'col_page_title', ''), ENT_QUOTES, 'UTF-8') ?></th>
            <th><?= htmlspecialchars($clGet($pt, 'col_page_text', ''), ENT_QUOTES, 'UTF-8') ?></th>
            <th><?= htmlspecialchars($clGet($pt, 'col_link', ''), ENT_QUOTES, 'UTF-8') ?></th>
            <th><?= htmlspecialchars($clGet($pt, 'col_modified', ''), ENT_QUOTES, 'UTF-8') ?></th>
            <th><?= htmlspecialchars($clGet($pt, 'col_actions', ''), ENT_QUOTES, 'UTF-8') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php $i = 1; ?>
          <?php if (! empty($list)) : ?>
            <?php foreach ($list as $v) : ?>
              <?php
              $pid = isset($v->pageId) ? (int) $v->pageId : 0;
              $ptitle = isset($v->pageTitle) ? trim((string) $v->pageTitle) : '';
              $ptext = isset($v->pageText) ? trim((string) $v->pageText) : '';
              $ptextShort = $ptext;
              if ($ptext !== '' && function_exists('mb_strlen') && mb_strlen($ptext, 'UTF-8') > 64) {
                $ptextShort = mb_substr($ptext, 0, 64, 'UTF-8') . '…';
              } elseif ($ptext !== '' && strlen($ptext) > 64) {
                $ptextShort = substr($ptext, 0, 64) . '...';
              }
              $link = isset($v->link) ? trim((string) $v->link) : '';
              $mod = ! empty($v->modified) ? fv_format_display_datetime($v->modified) : '';
              ?>
              <tr>
                <td><?= (int) $i ?></td>
                <td><?= htmlspecialchars($ptitle, ENT_QUOTES, 'UTF-8') ?></td>
                <td><span class="text-muted small"><?= htmlspecialchars($ptextShort, ENT_QUOTES, 'UTF-8') ?></span></td>
                <td><?= $link !== '' ? htmlspecialchars($link, ENT_QUOTES, 'UTF-8') : '—' ?></td>
                <td><?= htmlspecialchars($mod, ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                  <div class="admin-grid-actions">
                    <a href="<?= admin_url('cms/view/' . $pid) ?>" class="btn admin-grid-btn admin-grid-btn--view" data-toggle="tooltip" title="<?= htmlspecialchars($clGet($pt, 'tooltip_view', ''), ENT_QUOTES, 'UTF-8') ?>">
                      <i class="mdi mdi-eye" aria-hidden="true"></i>
                    </a>
                    <a href="<?= admin_url('cms/edit/' . $pid) ?>" class="btn admin-grid-btn admin-grid-btn--edit" data-toggle="tooltip" title="<?= htmlspecialchars($clGet($pt, 'tooltip_edit', ''), ENT_QUOTES, 'UTF-8') ?>">
                      <i class="mdi mdi-pencil" aria-hidden="true"></i>
                    </a>
                    <button type="button" class="btn admin-grid-btn admin-grid-btn--delete" data-toggle="tooltip" title="<?= htmlspecialchars($clGet($pt, 'tooltip_delete', ''), ENT_QUOTES, 'UTF-8') ?>" onclick="deleteCmsPage(<?= $pid ?>)">
                      <i class="mdi mdi-delete" aria-hidden="true"></i>
                    </button>
                  </div>
                </td>
              </tr>
              <?php $i++; ?>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php
$dtLang = [
	'lengthMenu' => (string) ($pt['datatable_length_menu'] ?? ''),
	'searchPlaceholder' => (string) ($pt['datatable_search_placeholder'] ?? ''),
	'all' => (string) ($pt['datatable_all'] ?? ''),
	'zeroRecords' => (string) ($pt['datatable_zero_records'] ?? ''),
	'info' => (string) ($pt['datatable_info'] ?? ''),
	'infoEmpty' => (string) ($pt['datatable_info_empty'] ?? ''),
	'infoFiltered' => (string) ($pt['datatable_info_filtered'] ?? ''),
	'previous' => (string) ($pt['datatable_previous'] ?? ''),
	'next' => (string) ($pt['datatable_next'] ?? ''),
];
?>
<script>
window.__cmsDataTableLang = <?= json_encode($dtLang, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
const cmsDeleteUi = <?= json_encode([
	'title' => (string) ($cmsDeleteUi['title'] ?? ''),
	'text' => (string) ($cmsDeleteUi['text'] ?? ''),
	'yes' => (string) ($cmsDeleteUi['yes'] ?? ''),
	'no' => (string) ($cmsDeleteUi['no'] ?? ''),
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
const cmsDeleteBase = <?= json_encode($deleteBase, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;

function deleteCmsPage(id) {
  swal.fire({
    title: cmsDeleteUi.title || '',
    text: cmsDeleteUi.text || '',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc2626',
    cancelButtonColor: '#6b7280',
    confirmButtonText: cmsDeleteUi.yes || '',
    cancelButtonText: cmsDeleteUi.no || ''
  }).then(function (result) {
    if (result.isConfirmed) {
      window.location.href = cmsDeleteBase + id;
    }
  });
}
</script>

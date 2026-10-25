<?php
/**
 * Historial de importaciones (company).
 *
 * @var string $title
 * @var array $list
 * @var array $importListLang
 */
$pageLang = isset($importListLang) && is_array($importListLang) ? $importListLang : [];
$t = static function (string $key, string $fallback = '') use ($pageLang): string {
    $v = trim((string) ($pageLang[$key] ?? ''));

    return $v !== '' ? $v : $fallback;
};
$list = isset($list) && is_array($list) ? $list : [];
?>
<div class="container-fluid pt-3">
  <div class="row">
    <div class="col-md-6">
      <?php $this->load->view('components/crud/page_heading', [
          'pageTitle' => (string) ($title ?? $t('list_page_title', 'Import history')),
          'pageIcon' => 'mdi mdi-import',
      ]); ?>
    </div>
  </div>
  <div class="card contentHeight">
    <div class="card-body">
      <div class="row">
        <div class="col-12">
          <div class="table-responsive">
            <table id="order-listing" class="table table-bordered">
              <thead>
                <tr>
                  <th width="20"><?= htmlspecialchars($t('col_num', '#'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th><?= htmlspecialchars($t('col_imported_on', 'Imported on'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th><?= htmlspecialchars($t('col_file_imported', 'File imported'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th><?= htmlspecialchars($t('col_records_imported', 'Records imported'), ENT_QUOTES, 'UTF-8') ?></th>
                </tr>
              </thead>
              <tbody>
                <?php if ($list === []) : ?>
                  <tr class="text-center">
                    <td colspan="4"><?= htmlspecialchars($t('empty_no_data', 'No data available'), ENT_QUOTES, 'UTF-8') ?></td>
                  </tr>
                <?php else : ?>
                  <?php $i = 1; ?>
                  <?php foreach ($list as $v) : ?>
                    <?php $v = is_object($v) ? $v : (object) $v; ?>
                    <tr class="notranslate">
                      <td><?= $i ?></td>
                      <td><?= htmlspecialchars(! empty($v->import_date) ? fv_format_display_date((string) $v->import_date, fv_resolve_display_date_format() . ' H:i A') : '', ENT_QUOTES, 'UTF-8') ?></td>
                      <td><?= htmlspecialchars((string) ($v->importedfile ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                      <td><?= htmlspecialchars((string) ($v->records_imported ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <?php $i++; ?>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

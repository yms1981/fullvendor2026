<?php
$pageLang = isset($importListLang) && is_array($importListLang) ? $importListLang : [];
$t = static function (string $key, string $fallback = '') use ($pageLang): string {
	$v = trim((string) ($pageLang[$key] ?? ''));

	return $v !== '' ? $v : $fallback;
};
?>
<div class="container-fluid pt-3">
  <div class="row">
    <div class="col-md-6">
      <div class="page-header">
        <h3 class="page-title"> <?=@$title?></h3>
      </div>
    </div>
    <div class="col-md-6 text-right">
     
    </div>
  </div>
  <div class="card">
    <div class="card-body">
      <div class="row">
        <div class="col-12">
          <div class="table-responsive">
            <table id="order-listing" class="table table-bordered">
              <thead>
                <tr>
                  <th width=20><?= htmlspecialchars($t('col_num', '#'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th><?= htmlspecialchars($t('col_company_name', 'Company name'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th><?= htmlspecialchars($t('col_description', 'Description'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th><?= htmlspecialchars($t('col_total_records', 'Total records'), ENT_QUOTES, 'UTF-8') ?></th>
                  <th><?= htmlspecialchars($t('col_imported_on', 'Imported on'), ENT_QUOTES, 'UTF-8') ?></th>
                </tr>
              </thead>
              <tbody>
                <?php $i = 1; ?>
                <?php foreach ($list as $key => $v):
                  $companyName = '';
                  if (isset($v->company) && is_object($v->company)) {
                    $companyName = (string) ($v->company->name ?? '');
                  }
                   ?>
                  <tr>
                    <td><?=$i?></td>
                    <td><?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) (@$v->importedfile ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                      <td>
                       <?= htmlspecialchars((string) (@$v->records_imported ?? ''), ENT_QUOTES, 'UTF-8') ?>
                      </td>
                      <td><?= date('d-M-Y', strtotime(@$v->import_date)) ?></td>
                      
                    </tr>
                    <?php $i++; ?>
                  <?php endforeach ?>
                </tbody>
               </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

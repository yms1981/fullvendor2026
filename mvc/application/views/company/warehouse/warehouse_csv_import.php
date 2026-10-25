<?php
/**
 * @var array $pageTexts traducciones del módulo cmp-warehouse
 */
?>
<div class="container-fluid page-body-wrapper pt-3">
    <div class="main-panel">
        <div class="container-fluid">
            <div class="page-header">
                <h3 class="page-title"><?= htmlspecialchars((string) ($pageTexts['import_page_title'] ?? ($pageTexts['import'] ?? 'Import'))) ?></h3>
            </div>
            <div class="row mb-2">
                <div class="col-md-12 text-right">
                    <a href="<?= import_template_download_url('warehousecsv.xlsx') ?>" download class="btn btn-success mt-2 mt-sm-0 btn-icon-text">
                        <i class="mdi mdi-download"></i>
                        <?= htmlspecialchars((string) ($pageTexts['import_sample_format'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <form id="form_import" class="forms-sample" action="<?= company_url('warehouses/csvImportSuccess') ?>" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>
                                <?= htmlspecialchars((string) ($pageTexts['import_select_file'] ?? 'CSV file')) ?>
                                <span class="text-danger">*</span>
                            </label>
                            <input type="file" class="form-control-file" name="file" accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel" required>
                            <small class="form-text text-muted mt-2">
                                <?= htmlspecialchars((string) ($pageTexts['import_columns_note'] ?? 'Expected columns: Name, Street, Street number, Country, State, City')) ?>
                            </small>
                        </div>
                    </form>
                    <?php $this->load->view('components/crud/form_action_fab', [
                        'formId' => 'form_import',
                        'saveLabel' => (string) ($pageTexts['import_submit'] ?? ($pageTexts['import'] ?? '')),
                        'cancelLabel' => (string) ($pageTexts['back'] ?? ($pageTexts['cancelar'] ?? '')),
                        'cancelUrl' => company_url('warehouses'),
                    ]); ?>
                </div>
            </div>
        </div>
    </div>
</div>

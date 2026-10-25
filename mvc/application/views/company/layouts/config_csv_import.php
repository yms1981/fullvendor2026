<?php
/**
 * Importación Excel con previsualización — CRUD de configuración.
 *
 * @var array $pageTexts
 * @var string $importAction
 * @var string $confirmAction
 * @var string $listUrl
 * @var array|null $preview
 * @var string $moduleSlug
 */
$pageTexts = is_array($pageTexts ?? null) ? $pageTexts : [];
$preview = is_array($preview ?? null) ? $preview : null;
$columnLegend = is_array($columnLegend ?? null) ? $columnLegend : [];
$hasPreview = !empty($preview['rows']);
$stats = is_array($preview['stats'] ?? null) ? $preview['stats'] : ['total' => 0, 'valid' => 0, 'errors' => 0, 'duplicates' => 0];
$importLoadingText = (string) ($pageTexts['import_preview_loading'] ?? '');
$legendTitle = (string) ($pageTexts['import_columns_legend_title'] ?? '');
$importPageTitle = trim((string) ($pageTexts['import_page_title'] ?? ($pageTexts['bnImport'] ?? ($pageTexts['import'] ?? ''))));
$templateLabel = trim((string) ($pageTexts['import_sample_format'] ?? ($pageTexts['btn_download_template'] ?? ($pageTexts['sampledownload'] ?? ''))));
$templateDownloadUrl = trim((string) ($pageTexts['template_download_url'] ?? ''));
$hasTemplateDownload = $templateDownloadUrl !== '';
$uploadTitle = trim((string) ($pageTexts['import_upload_title'] ?? ($pageTexts['import_select_file'] ?? '')));
$uploadStep1 = trim((string) ($pageTexts['import_upload_step_1'] ?? ''));
$uploadStep2 = trim((string) ($pageTexts['import_upload_step_2'] ?? ''));
$uploadStep3 = trim((string) ($pageTexts['import_upload_step_3'] ?? ''));
$uploadClickLabel = trim((string) ($pageTexts['import_upload_click'] ?? ''));
$cancelLabel = trim((string) ($pageTexts['back'] ?? ($pageTexts['cancelar'] ?? ($pageTexts['btn-cancelar'] ?? ''))));
$confirmLabel = trim((string) ($pageTexts['import_preview_confirm'] ?? ($pageTexts['import_submit'] ?? '')));
?>
<div class="container-fluid page-body-wrapper pt-3 fv-config-import">
    <link rel="stylesheet" href="<?= dated_file_url('companyasset/css/fv-config-import.css') ?>">
    <div class="main-panel">
        <div class="container-fluid">
            <?php $this->load->view('components/crud/page_heading', [
                'pageTitle' => $importPageTitle,
                'pageIcon' => 'fa-solid fa-file-excel',
            ]); ?>
            <div class="fv-config-import__header">
                <div>
                    <p class="fv-config-import__subtitle mb-0"><?= htmlspecialchars((string) ($pageTexts['import_preview_subtitle'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <?php if ($hasTemplateDownload): ?>
                <a href="<?= htmlspecialchars($templateDownloadUrl, ENT_QUOTES, 'UTF-8') ?>"
                   download
                   class="btn btn-outline-success fv-config-import__template-btn"
                   title="<?= htmlspecialchars($templateLabel, ENT_QUOTES, 'UTF-8') ?>"
                   aria-label="<?= htmlspecialchars($templateLabel, ENT_QUOTES, 'UTF-8') ?>">
                    <i class="mdi mdi-download" aria-hidden="true"></i>
                    <?php if ($templateLabel !== ''): ?>
                    <span><?= htmlspecialchars($templateLabel, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </a>
                <?php endif; ?>
            </div>

            <?php if ($columnLegend !== []): ?>
            <div class="card fv-config-import__card fv-config-import__legend-card mb-3">
                <div class="card-body">
                    <h4 class="fv-config-import__legend-title"><?= htmlspecialchars($legendTitle, ENT_QUOTES, 'UTF-8') ?></h4>
                    <dl class="fv-config-import__legend-list mb-0">
                        <?php foreach ($columnLegend as $legendItem): ?>
                        <div class="fv-config-import__legend-item">
                            <dt><?= htmlspecialchars((string) ($legendItem['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></dt>
                            <dd><?= htmlspecialchars((string) ($legendItem['help'] ?? ''), ENT_QUOTES, 'UTF-8') ?></dd>
                        </div>
                        <?php endforeach; ?>
                    </dl>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!$hasPreview): ?>
            <div class="card fv-config-import__card">
                <div class="card-body">
                    <form id="form_import_preview" class="fv-config-import__upload-form" action="<?= htmlspecialchars((string) $importAction, ENT_QUOTES, 'UTF-8') ?>" method="post" enctype="multipart/form-data">
                        <input type="hidden" name="step" value="preview">
                        <label class="fv-config-import__dropzone" for="fv_config_import_file">
                            <span class="fv-config-import__dropzone-icon"><i class="mdi mdi-file-excel-outline" aria-hidden="true"></i></span>
                            <?php if ($uploadTitle !== ''): ?>
                            <span class="fv-config-import__dropzone-title"><?= htmlspecialchars($uploadTitle, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <?php if ($uploadStep1 !== '' || $uploadStep2 !== '' || $uploadStep3 !== ''): ?>
                            <ol class="fv-config-import__dropzone-steps">
                                <?php if ($uploadStep1 !== ''): ?><li><?= htmlspecialchars($uploadStep1, ENT_QUOTES, 'UTF-8') ?></li><?php endif; ?>
                                <?php if ($uploadStep2 !== ''): ?><li><?= htmlspecialchars($uploadStep2, ENT_QUOTES, 'UTF-8') ?></li><?php endif; ?>
                                <?php if ($uploadStep3 !== ''): ?><li><?= htmlspecialchars($uploadStep3, ENT_QUOTES, 'UTF-8') ?></li><?php endif; ?>
                            </ol>
                            <?php endif; ?>
                            <?php if (trim((string) ($pageTexts['import_drop_hint'] ?? '')) !== ''): ?>
                            <span class="fv-config-import__dropzone-hint"><?= htmlspecialchars((string) $pageTexts['import_drop_hint'], ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <?php if ($uploadClickLabel !== ''): ?>
                            <span class="fv-config-import__dropzone-cta"><i class="mdi mdi-upload" aria-hidden="true"></i> <?= htmlspecialchars($uploadClickLabel, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <input type="file" id="fv_config_import_file" class="fv-config-import__file-input" name="file" accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel" required>
                            <span class="fv-config-import__file-name" data-empty="1"></span>
                        </label>
                        <?php if (!empty($pageTexts['import_columns_note'])): ?>
                        <p class="fv-config-import__columns-note"><?= htmlspecialchars((string) $pageTexts['import_columns_note'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php endif; ?>
                    </form>
                    <?php
                    $fabArgs = [
                        'formId' => 'form_import_preview',
                        'cancelLabel' => $cancelLabel,
                        'cancelUrl' => (string) $listUrl,
                    ];
                    if ($hasTemplateDownload) {
                        $fabArgs['saveLabel'] = $templateLabel;
                        $fabArgs['saveAsLink'] = true;
                        $fabArgs['saveUrl'] = $templateDownloadUrl;
                        $fabArgs['saveIconClass'] = 'fa-solid fa-download';
                    } else {
                        $fabArgs['hideSaveButton'] = true;
                        $fabArgs['saveLabel'] = trim((string) ($pageTexts['import_preview_button'] ?? ''));
                    }
                    $this->load->view('components/crud/form_action_fab', $fabArgs);
                    ?>
                </div>
            </div>
            <?php else: ?>
            <div class="fv-config-import__stats fv-config-import__stats--4">
                <div class="fv-config-import__stat">
                    <span class="fv-config-import__stat-label"><?= htmlspecialchars((string) ($pageTexts['import_preview_total'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php $statTotal = (int) ($stats['total'] ?? 0); ?>
                    <?php if ($statTotal > 0): ?>
                    <button type="button" class="fv-config-import__stat-value fv-config-import__stat-link" data-filter="total" aria-haspopup="dialog" title="<?= htmlspecialchars((string) ($pageTexts['import_stats_show_details'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(fv_format_integer_count($statTotal, $format_numeric ?? null), ENT_QUOTES, 'UTF-8') ?></button>
                    <?php else: ?>
                    <span class="fv-config-import__stat-value"><?= htmlspecialchars(fv_format_integer_count($statTotal, $format_numeric ?? null), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>
                <div class="fv-config-import__stat fv-config-import__stat--ok">
                    <span class="fv-config-import__stat-label"><?= htmlspecialchars((string) ($pageTexts['import_preview_valid'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php $statValid = (int) ($stats['valid'] ?? 0); ?>
                    <?php if ($statValid > 0): ?>
                    <button type="button" class="fv-config-import__stat-value fv-config-import__stat-link" data-filter="valid" aria-haspopup="dialog" title="<?= htmlspecialchars((string) ($pageTexts['import_stats_show_details'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(fv_format_integer_count($statValid, $format_numeric ?? null), ENT_QUOTES, 'UTF-8') ?></button>
                    <?php else: ?>
                    <span class="fv-config-import__stat-value"><?= htmlspecialchars(fv_format_integer_count($statValid, $format_numeric ?? null), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>
                <div class="fv-config-import__stat fv-config-import__stat--error">
                    <span class="fv-config-import__stat-label"><?= htmlspecialchars((string) ($pageTexts['import_preview_errors'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php $statErrors = (int) ($stats['errors'] ?? 0); ?>
                    <?php if ($statErrors > 0): ?>
                    <button type="button" class="fv-config-import__stat-value fv-config-import__stat-link" data-filter="errors" aria-haspopup="dialog" title="<?= htmlspecialchars((string) ($pageTexts['import_stats_show_details'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(fv_format_integer_count($statErrors, $format_numeric ?? null), ENT_QUOTES, 'UTF-8') ?></button>
                    <?php else: ?>
                    <span class="fv-config-import__stat-value"><?= htmlspecialchars(fv_format_integer_count($statErrors, $format_numeric ?? null), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>
                <div class="fv-config-import__stat fv-config-import__stat--warn">
                    <span class="fv-config-import__stat-label"><?= htmlspecialchars((string) ($pageTexts['import_preview_duplicates'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php $statDuplicates = (int) ($stats['duplicates'] ?? 0); ?>
                    <?php if ($statDuplicates > 0): ?>
                    <button type="button" class="fv-config-import__stat-value fv-config-import__stat-link" data-filter="duplicates" aria-haspopup="dialog" title="<?= htmlspecialchars((string) ($pageTexts['import_stats_show_details'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(fv_format_integer_count($statDuplicates, $format_numeric ?? null), ENT_QUOTES, 'UTF-8') ?></button>
                    <?php else: ?>
                    <span class="fv-config-import__stat-value"><?= htmlspecialchars(fv_format_integer_count($statDuplicates, $format_numeric ?? null), ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($preview['filename'])): ?>
            <p class="fv-config-import__filename">
                <i class="mdi mdi-paperclip" aria-hidden="true"></i>
                <?= htmlspecialchars((string) $preview['filename'], ENT_QUOTES, 'UTF-8') ?>
                <a href="<?= htmlspecialchars((string) $importAction, ENT_QUOTES, 'UTF-8') ?>" class="fv-config-import__change-file">
                    <?= htmlspecialchars((string) ($pageTexts['import_preview_change_file'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </a>
            </p>
            <?php endif; ?>

            <div class="card fv-config-import__card">
                <div class="card-body p-0">
                    <div class="table-responsive fv-config-import__table-wrap">
                        <table class="table fv-config-import__table mb-0">
                            <thead>
                                <tr>
                                    <th class="fv-config-import__col-line">#</th>
                                    <?php foreach (($preview['columns'] ?? []) as $columnLabel): ?>
                                    <th><?= htmlspecialchars((string) $columnLabel, ENT_QUOTES, 'UTF-8') ?></th>
                                    <?php endforeach; ?>
                                    <th class="fv-config-import__col-status"><?= htmlspecialchars((string) ($pageTexts['import_preview_status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (($preview['rows'] ?? []) as $row): ?>
                                <?php
                                $rowClass = 'fv-config-import__row--ok';
                                if (!empty($row['duplicate'])) {
                                    $rowClass = 'fv-config-import__row--duplicate';
                                } elseif (!empty($row['skip'])) {
                                    $rowClass = 'fv-config-import__row--skip';
                                } elseif (empty($row['valid'])) {
                                    $rowClass = 'fv-config-import__row--error';
                                }
                                $errorIndexes = array_map('intval', (array) ($row['error_indexes'] ?? []));
                                ?>
                                <tr class="<?= $rowClass ?>">
                                    <td class="fv-config-import__col-line"><?= (int) ($row['line'] ?? 0) ?></td>
                                    <?php foreach (($row['cells'] ?? []) as $cellIndex => $cell): ?>
                                    <td class="<?= in_array((int) $cellIndex, $errorIndexes, true) ? 'fv-config-import__cell--error' : '' ?>">
                                        <?= htmlspecialchars((string) $cell, ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <?php endforeach; ?>
                                    <td class="fv-config-import__status-cell">
                                        <?php if (!empty($row['duplicate'])): ?>
                                            <span class="fv-config-import__badge fv-config-import__badge--warn"><?= htmlspecialchars((string) ($pageTexts['import_preview_status_duplicate'] ?? ($pageTexts['import_preview_duplicate_error'] ?? '')), ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php if (!empty($row['errors'])): ?>
                                            <small class="d-block fv-config-import__error-detail mt-1"><?= htmlspecialchars(implode(' · ', (array) $row['errors']), ENT_QUOTES, 'UTF-8') ?></small>
                                            <?php endif; ?>
                                        <?php elseif (!empty($row['skip'])): ?>
                                            <span class="fv-config-import__badge fv-config-import__badge--skip"><?= htmlspecialchars((string) ($pageTexts['import_preview_status_skipped'] ?? ($pageTexts['import_row_skipped_no_name'] ?? '')), ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php if (!empty($row['errors'])): ?>
                                            <small class="d-block fv-config-import__error-detail mt-1"><?= htmlspecialchars(implode(' · ', (array) $row['errors']), ENT_QUOTES, 'UTF-8') ?></small>
                                            <?php endif; ?>
                                        <?php elseif (!empty($row['valid'])): ?>
                                            <span class="fv-config-import__badge fv-config-import__badge--ok"><?= htmlspecialchars((string) ($pageTexts['import_preview_status_ok'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php else: ?>
                                            <span class="fv-config-import__badge fv-config-import__badge--error"><?= htmlspecialchars((string) ($pageTexts['import_preview_status_error'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php if (!empty($row['errors'])): ?>
                                            <small class="d-block fv-config-import__error-detail mt-1"><?= htmlspecialchars(implode(' · ', (array) $row['errors']), ENT_QUOTES, 'UTF-8') ?></small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($preview['rows'])): ?>
                                <tr>
                                    <td colspan="99" class="text-center text-muted py-4"><?= htmlspecialchars((string) ($pageTexts['import_preview_no_rows'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <form id="form_import_confirm" action="<?= htmlspecialchars((string) $confirmAction, ENT_QUOTES, 'UTF-8') ?>" method="post">
                <input type="hidden" name="step" value="confirm">
                <input type="hidden" name="import_token" value="<?= htmlspecialchars((string) ($preview['token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="import_sig" id="fv_import_sig" value="<?= htmlspecialchars((string) ($preview['sig'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </form>
            <?php if (!empty($preview['rows_json'])): ?>
            <textarea id="fv_import_rows_json" class="d-none" readonly hidden aria-hidden="true"><?= htmlspecialchars((string) $preview['rows_json'], ENT_NOQUOTES, 'UTF-8') ?></textarea>
            <?php endif; ?>
            <?php $this->load->view('components/crud/form_action_fab', [
                'formId' => 'form_import_confirm',
                'saveLabel' => $confirmLabel,
                'cancelLabel' => $cancelLabel,
                'cancelUrl' => (string) $listUrl,
                'saveIconClass' => 'fa-solid fa-file-import',
                'saveDisabled' => (int) ($stats['valid'] ?? 0) < 1,
            ]); ?>

            <div class="modal fade" id="fv-config-import-stats-modal" tabindex="-1" role="dialog" aria-labelledby="fv-config-import-stats-modal-title" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="fv-config-import-stats-modal-title"></h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="<?= htmlspecialchars((string) ($pageTexts['import_stats_modal_close'] ?? ($pageTexts['back'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body p-0">
                            <p class="fv-config-import-stats-modal__summary px-3 pt-3 mb-0"></p>
                            <div class="table-responsive fv-config-import-stats-modal__table-wrap">
                                <table class="table fv-config-import__table mb-0">
                                    <thead>
                                        <tr id="fv-config-import-stats-modal-head"></tr>
                                    </thead>
                                    <tbody id="fv-config-import-stats-modal-body"></tbody>
                                </table>
                            </div>
                            <p class="fv-config-import-stats-modal__empty text-center text-muted py-4 d-none"></p>
                        </div>
                        <div class="modal-footer justify-content-between">
                            <div class="fv-config-import-stats-modal__pager btn-group" role="group" aria-label="<?= htmlspecialchars((string) ($pageTexts['import_stats_modal_pagination'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= htmlspecialchars((string) ($pageTexts['import_stats_modal_close'] ?? ($pageTexts['back'] ?? '')), ENT_QUOTES, 'UTF-8') ?></button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php
$importPreviewCompactRows = [];
if ($hasPreview) {
    foreach (($preview['rows'] ?? []) as $row) {
        $importPreviewCompactRows[] = [
            'line' => (int) ($row['line'] ?? 0),
            'cells' => array_values(array_map('strval', (array) ($row['cells'] ?? []))),
            'valid' => !empty($row['valid']),
            'skip' => !empty($row['skip']),
            'duplicate' => !empty($row['duplicate']),
            'errors' => array_values(array_map('strval', (array) ($row['errors'] ?? []))),
            'error_indexes' => array_values(array_map('intval', (array) ($row['error_indexes'] ?? []))),
        ];
    }
}
?>
<script>
window.fvConfigImportPreview = <?= json_encode([
    'token' => (string) ($preview['token'] ?? ''),
    'sig' => (string) ($preview['sig'] ?? ''),
    'columns' => array_values(array_map('strval', (array) ($preview['columns'] ?? []))),
    'rows' => $importPreviewCompactRows,
    'statusOk' => (string) ($pageTexts['import_preview_status_ok'] ?? ''),
    'statusError' => (string) ($pageTexts['import_preview_status_error'] ?? ''),
    'statusSkipped' => (string) ($pageTexts['import_preview_status_skipped'] ?? ($pageTexts['import_row_skipped_no_name'] ?? '')),
    'statusDuplicate' => (string) ($pageTexts['import_preview_status_duplicate'] ?? ($pageTexts['import_preview_duplicate_error'] ?? '')),
    'colLine' => '#',
    'colStatus' => (string) ($pageTexts['import_preview_status'] ?? ''),
    'titles' => [
        'total' => (string) ($pageTexts['import_stats_modal_title_total'] ?? ($pageTexts['import_preview_total'] ?? '')),
        'valid' => (string) ($pageTexts['import_stats_modal_title_valid'] ?? ($pageTexts['import_preview_valid'] ?? '')),
        'errors' => (string) ($pageTexts['import_stats_modal_title_errors'] ?? ($pageTexts['import_preview_errors'] ?? '')),
        'duplicates' => (string) ($pageTexts['import_stats_modal_title_duplicates'] ?? ($pageTexts['import_preview_duplicates'] ?? '')),
    ],
    'empty' => (string) ($pageTexts['import_stats_modal_empty'] ?? ''),
    'summary' => (string) ($pageTexts['import_stats_modal_summary'] ?? ''),
    'pageLabel' => (string) ($pageTexts['import_stats_modal_page'] ?? ''),
    'prev' => (string) ($pageTexts['import_stats_modal_prev'] ?? ''),
    'next' => (string) ($pageTexts['import_stats_modal_next'] ?? ''),
    'groupSep' => fv_resolve_numeric_group_separator($format_numeric ?? null),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
window.fvConfigImportLang = <?= json_encode([
    'loading' => $importLoadingText,
    'confirmLoading' => (string) ($pageTexts['import_confirm_loading'] ?? $importLoadingText),
    'previewTitle' => (string) ($pageTexts['import_progress_title_preview'] ?? $importLoadingText),
    'confirmTitle' => (string) ($pageTexts['import_progress_title_confirm'] ?? ($pageTexts['import_confirm_loading'] ?? $importLoadingText)),
    'stepUpload' => (string) ($pageTexts['import_progress_step_upload'] ?? ''),
    'stepValidate' => (string) ($pageTexts['import_progress_step_validate'] ?? ''),
    'stepPrepare' => (string) ($pageTexts['import_progress_step_prepare'] ?? ''),
    'stepImport' => (string) ($pageTexts['import_progress_step_import'] ?? ''),
    'stepFinalize' => (string) ($pageTexts['import_progress_step_finalize'] ?? ''),
    'detail' => (string) ($pageTexts['import_progress_detail'] ?? ''),
    'importedDetail' => (string) ($pageTexts['import_progress_imported_detail'] ?? ''),
    'complete' => (string) ($pageTexts['import_progress_complete'] ?? ''),
    'error' => (string) ($pageTexts['import_progress_error'] ?? ($pageTexts['message_error_generic'] ?? '')),
    'ok' => (string) ($pageTexts['message_ok'] ?? ($pageTexts['aceptar'] ?? 'OK')),
    'importTotal' => (int) ($stats['total'] ?? 0),
    'listUrl' => (string) ($listUrl ?? ''),
    'groupSep' => fv_resolve_numeric_group_separator($format_numeric ?? null),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="<?= dated_file_url('companyasset/company/fv-import-progress.js') ?>"></script>
<script src="<?= dated_file_url('companyasset/company/fv-config-import-stats.js') ?>"></script>
<script src="<?= dated_file_url('companyasset/company/config-import.js') ?>"></script>

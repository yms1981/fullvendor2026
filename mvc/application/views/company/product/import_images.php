<?php
/**
 * @var array $pageTexts
 * @var string $uploadAuthToken
 * @var int $uploadCompanyId
 * @var string $uploadAuthMissingTitle
 * @var string $uploadAuthMissingDetail
 */
$pageTitle = (string) ($pageTexts['modal-importacion-imagenes'] ?? '');
$folderModeLabel = (string) ($pageTexts['import_images_folder_mode'] ?? '');
$folderNameLabel = (string) ($pageTexts['import_images_folder_name'] ?? '');
$uploadHintLabel = (string) ($pageTexts['import_images_upload_hint'] ?? '');
$queueTitleLabel = (string) ($pageTexts['import_images_queue_title'] ?? '');
$selectAllLabel = (string) ($pageTexts['import_images_select_all'] ?? '');
$queueEmptyLabel = (string) ($pageTexts['import_images_queue_empty'] ?? '');
$removeFileLabel = (string) ($pageTexts['dictRemoveFile'] ?? '');
$uploadClickLabel = (string) ($pageTexts['import_images_upload_click'] ?? ($pageTexts['seleccionar_archivo'] ?? ''));
$importLabel = (string) ($pageTexts['btn-modal-importar'] ?? '');
$cancelLabel = (string) ($pageTexts['btn-modal-cancelar'] ?? ($pageTexts['btn-cancelar'] ?? ''));
$uploadedLabel = (string) ($pageTexts['subidos'] ?? '');
$notUploadedLabel = (string) ($pageTexts['no_subidos'] ?? '');
$referenceInfo = (string) ($pageTexts['import_images_reference_info'] ?? '');
$skuSlashNote = (string) ($pageTexts['import_images_sku_slash_note'] ?? '');
$processingLabel = (string) ($pageTexts['processing_images'] ?? '');
$templateLabel = trim((string) ($pageTexts['import_sample_format'] ?? ($pageTexts['btn_download_template'] ?? ($pageTexts['sampledownload'] ?? ''))));
$templateDownloadUrl = trim((string) ($pageTexts['template_download_url'] ?? ''));
$templateNote = trim((string) ($pageTexts['import_images_template_note'] ?? ''));
$importProgressTitle = (string) ($pageTexts['import_progress_title_images'] ?? $processingLabel);
$importProgressStepImages = (string) ($pageTexts['import_progress_step_images'] ?? $processingLabel);
$importProgressDetail = (string) ($pageTexts['import_progress_detail'] ?? '');
$importProgressComplete = (string) ($pageTexts['import_progress_complete'] ?? '');
$importProgressError = (string) ($pageTexts['import_progress_error'] ?? ($pageTexts['error_importing'] ?? ''));
$importErrorNoSku = (string) ($pageTexts['no_sku_existente'] ?? '');
$importErrorFormat = (string) ($pageTexts['no_cumple_format'] ?? '');
$importErrorUpload = (string) ($pageTexts['upload_error'] ?? '');
$importErrorUnknown = (string) ($pageTexts['unknown_error'] ?? '');
$importErrorTitleNoSku = (string) ($pageTexts['import_images_error_title_no_sku'] ?? '');
$importErrorTitleFormat = (string) ($pageTexts['import_images_error_title_format'] ?? '');
$importErrorTitleLink = (string) ($pageTexts['import_images_error_title_link'] ?? '');
$importErrorTitleUpload = (string) ($pageTexts['import_images_error_title_upload'] ?? '');
$importErrorTitleUnknown = (string) ($pageTexts['import_images_error_title_unknown'] ?? '');
$importErrorHtml = (string) ($pageTexts['import_images_error_html_response'] ?? '');
$importResultSuccess = (string) ($pageTexts['import_images_result_success'] ?? '');
?>
<link rel="stylesheet" href="<?= dated_file_url('companyasset/css/fv-category-images-import.css') ?>">
<link rel="stylesheet" href="<?= dated_file_url('companyasset/css/fv-config-import.css') ?>">

<div class="container-fluid page-body-wrapper pt-3 fv-category-images-import">
    <div class="main-panel">
        <div class="container-fluid pt-3">
            <?php $this->load->view('components/crud/page_heading', [
                'pageTitle' => $pageTitle,
                'pageIcon' => 'fa-solid fa-images',
            ]); ?>

            <?php if ($templateDownloadUrl !== ''): ?>
            <div class="fv-category-images-import__header">
                <p class="fv-category-images-import__header-note mb-0">
                    <?= htmlspecialchars($templateNote !== '' ? $templateNote : $referenceInfo, ENT_QUOTES, 'UTF-8') ?>
                </p>
                <a href="<?= htmlspecialchars($templateDownloadUrl, ENT_QUOTES, 'UTF-8') ?>"
                   download
                   class="btn btn-outline-success fv-category-images-import__template-btn"
                   title="<?= htmlspecialchars($templateLabel, ENT_QUOTES, 'UTF-8') ?>"
                   aria-label="<?= htmlspecialchars($templateLabel, ENT_QUOTES, 'UTF-8') ?>">
                    <i class="mdi mdi-download" aria-hidden="true"></i>
                    <?php if ($templateLabel !== ''): ?>
                    <span><?= htmlspecialchars($templateLabel, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endif; ?>
                </a>
            </div>
            <?php endif; ?>

            <div class="card fv-category-images-import__card">
                <div class="card-body">
                    <div class="fv-category-images-import__intro">
                        <?= htmlspecialchars($referenceInfo, ENT_QUOTES, 'UTF-8') ?>
                        <?php if ($skuSlashNote !== ''): ?>
                        <br>
                        <?= htmlspecialchars($skuSlashNote, ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
                    </div>

                    <div class="fv-category-images-import__layout">
                        <div class="fv-category-images-import__dropzone-panel fv-prod-images-import__panel">
                            <label class="fv-prod-images-import__folder-toggle">
                                <input type="checkbox" id="fv-prod-images-folder-mode" checked>
                                <span><?= htmlspecialchars($folderModeLabel, ENT_QUOTES, 'UTF-8') ?></span>
                            </label>
                            <p id="fv-prod-images-folder-name" class="fv-prod-images-import__folder-name text-muted mb-0 d-none" data-template="<?= htmlspecialchars($folderNameLabel, ENT_QUOTES, 'UTF-8') ?>"></p>
                            <p id="fv-prod-images-folder-match-summary" class="fv-prod-images-import__folder-match-summary text-muted mb-0 d-none" aria-live="polite"></p>

                            <label class="fv-config-import__dropzone fv-prod-images-import__picker" for="fv-prod-images-file-input">
                                <span class="fv-config-import__dropzone-icon"><i class="mdi mdi-folder-image" aria-hidden="true"></i></span>
                                <span class="fv-config-import__dropzone-title" id="fv-prod-images-picker-title"><?= htmlspecialchars($uploadClickLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if ($uploadHintLabel !== '') : ?>
                                <span class="fv-config-import__dropzone-hint" id="fv-prod-images-picker-hint"><?= htmlspecialchars($uploadHintLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                                <span class="fv-config-import__dropzone-cta" id="fv-prod-images-picker-cta"><i class="mdi mdi-folder-open" aria-hidden="true"></i> <?= htmlspecialchars($uploadClickLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                <input type="file"
                                       id="fv-prod-images-file-input"
                                       class="fv-config-import__file-input"
                                       multiple
                                       webkitdirectory
                                       directory>
                            </label>

                            <div id="fv-prod-images-ingest-progress" class="fv-prod-images-import__ingest-progress d-none" aria-live="polite">
                                <p id="fv-prod-images-ingest-progress-detail" class="fv-prod-images-import__ingest-progress-detail mb-2"></p>
                                <div class="fv-prod-images-import__upload-progress-track">
                                    <div id="fv-prod-images-ingest-progress-bar" class="fv-prod-images-import__upload-progress-bar"></div>
                                </div>
                            </div>

                            <div id="fv-prod-images-upload-progress" class="fv-prod-images-import__upload-progress d-none" aria-live="polite">
                                <div class="fv-prod-images-import__upload-progress-head">
                                    <strong id="fv-prod-images-upload-progress-title"><?= htmlspecialchars((string) ($pageTexts['import_images_upload_progress_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                    <span id="fv-prod-images-upload-progress-count" class="fv-prod-images-import__upload-progress-count">0 / 0</span>
                                </div>
                                <div class="fv-prod-images-import__upload-progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                                    <div id="fv-prod-images-upload-progress-bar" class="fv-prod-images-import__upload-progress-bar"></div>
                                </div>
                                <p id="fv-prod-images-upload-progress-detail" class="fv-prod-images-import__upload-progress-detail mb-0"></p>
                            </div>

                            <div class="fv-prod-images-import__queue">
                                <div class="fv-prod-images-import__queue-head">
                                    <strong class="fv-prod-images-import__queue-title"><?= htmlspecialchars($queueTitleLabel, ENT_QUOTES, 'UTF-8') ?></strong>
                                    <label id="fv-prod-images-select-all-wrap" class="fv-prod-images-import__select-all">
                                        <input type="checkbox" id="fv-prod-images-select-all" checked>
                                        <span><?= htmlspecialchars($selectAllLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                    </label>
                                    <span class="fv-prod-images-import__queue-count" id="fv-prod-images-queue-count">0</span>
                                </div>
                                <div id="fv-prod-images-bulk-summary" class="fv-prod-images-import__bulk-summary d-none">
                                    <p id="fv-prod-images-bulk-summary-text" class="fv-prod-images-import__bulk-summary-text mb-2"></p>
                                    <div class="fv-prod-images-import__bulk-stats">
                                        <div class="fv-prod-images-import__bulk-stat">
                                            <span><?= htmlspecialchars((string) ($pageTexts['import_images_bulk_uploaded'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                            <strong id="fv-prod-images-bulk-uploaded">0</strong>
                                        </div>
                                        <div class="fv-prod-images-import__bulk-stat">
                                            <span><?= htmlspecialchars((string) ($pageTexts['import_images_bulk_pending'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                            <strong id="fv-prod-images-bulk-pending">0</strong>
                                        </div>
                                        <div class="fv-prod-images-import__bulk-stat fv-prod-images-import__bulk-stat--error">
                                            <span><?= htmlspecialchars((string) ($pageTexts['import_images_bulk_failed'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                            <strong id="fv-prod-images-bulk-failed">0</strong>
                                        </div>
                                    </div>
                                    <p class="fv-prod-images-import__bulk-note text-muted mb-0">
                                        <?= htmlspecialchars((string) ($pageTexts['import_images_bulk_list_hidden'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                        <span id="fv-prod-images-bulk-summary-count">0</span>
                                    </p>
                                    <div id="fv-prod-images-bulk-error-groups" class="fv-prod-images-import__bulk-error-groups d-none">
                                        <strong><?= htmlspecialchars((string) ($pageTexts['import_images_bulk_error_groups_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                        <ul id="fv-prod-images-bulk-error-groups-list" class="fv-prod-images-import__bulk-error-groups-list mb-0"></ul>
                                    </div>
                                    <div id="fv-prod-images-bulk-errors" class="fv-prod-images-import__bulk-errors d-none">
                                        <strong><?= htmlspecialchars((string) ($pageTexts['import_images_bulk_errors_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                        <ul id="fv-prod-images-bulk-errors-list" class="fv-prod-images-import__bulk-errors-list mb-0"></ul>
                                    </div>
                                    <div id="fv-prod-images-bulk-failed-list-wrap" class="fv-prod-images-import__bulk-failed-list-wrap d-none">
                                        <div class="fv-prod-images-import__bulk-failed-list-head">
                                            <strong><?= htmlspecialchars((string) ($pageTexts['import_images_bulk_failed_list_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                            <span id="fv-prod-images-bulk-failed-list-count" class="fv-prod-images-import__bulk-failed-list-count">0</span>
                                        </div>
                                        <ul id="fv-prod-images-bulk-failed-list" class="fv-prod-images-import__bulk-failed-list mb-0"></ul>
                                    </div>
                                </div>
                                <p id="fv-prod-images-queue-empty" class="fv-prod-images-import__queue-empty text-muted mb-0"><?= htmlspecialchars($queueEmptyLabel, ENT_QUOTES, 'UTF-8') ?></p>
                                <ul id="fv-prod-images-queue-list" class="fv-prod-images-import__queue-list"></ul>
                            </div>
                        </div>

                        <div class="fv-category-images-import__results" aria-live="polite">
                            <div class="fv-category-images-import__results-head">
                                <div class="fv-category-images-import__results-col-title fv-category-images-import__results-col-title--ok">
                                    <span><?= htmlspecialchars($uploadedLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="fv-category-images-import__count" id="fv-prod-img-count-ok">0</span>
                                </div>
                                <div class="fv-category-images-import__results-col-title fv-category-images-import__results-col-title--error">
                                    <span><?= htmlspecialchars($notUploadedLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="fv-category-images-import__count" id="fv-prod-img-count-error">0</span>
                                </div>
                            </div>
                            <div class="fv-category-images-import__results-body">
                                <div class="fv-category-images-import__results-list" id="resultado_subidos"></div>
                                <div class="fv-category-images-import__results-list" id="resultado_nosubidos"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<form action="<?= company_url('products/importImages/import') ?>" id="form-guardar-imagenes"></form>

<?php $this->load->view('components/crud/form_action_fab', [
    'formId' => 'form-guardar-imagenes',
    'saveLabel' => $importLabel,
    'saveButtonId' => 'btn-export',
    'saveIconClass' => 'fa-solid fa-cloud-arrow-up',
    'cancelLabel' => $cancelLabel,
    'cancelUrl' => company_url('products'),
]); ?>

<script src="<?= dated_file_url('companyasset/company/fv-import-progress.js') ?>"></script>
<script>
window.fvProdImagesImport = <?= json_encode([
    'uploadUrl' => company_url('products/images/uploadImage'),
    'uploadCredentialsUrl' => company_url('products/importImages/uploadCredentials'),
    'skuIndexUrl' => company_url('products/importImages/skuIndex'),
    'uploadToken' => (string) ($uploadAuthToken ?? ''),
    'companyId' => (int) ($uploadCompanyId ?? 0),
    'uploadAuthMissingTitle' => (string) ($uploadAuthMissingTitle ?? ''),
    'uploadAuthMissingDetail' => (string) ($uploadAuthMissingDetail ?? ''),
    'groupSep' => fv_resolve_numeric_group_separator($format_numeric ?? null),
    'bulkThreshold' => 100,
    'ingestChunkSize' => 300,
    'uploadConcurrency' => 1,
    'lang' => [
        'import_images_uploading' => (string) ($pageTexts['import_images_uploading'] ?? ''),
        'import_images_upload_pending' => (string) ($pageTexts['import_images_upload_pending'] ?? ''),
        'import_images_upload_done' => (string) ($pageTexts['import_images_upload_done'] ?? ''),
        'import_images_upload_failed' => (string) ($pageTexts['import_images_upload_failed'] ?? ''),
        'import_images_upload_progress_title' => (string) ($pageTexts['import_images_upload_progress_title'] ?? ''),
        'import_images_upload_progress_detail' => (string) ($pageTexts['import_images_upload_progress_detail'] ?? ''),
        'import_images_bulk_summary' => (string) ($pageTexts['import_images_bulk_summary'] ?? ''),
        'import_images_bulk_ingesting' => (string) ($pageTexts['import_images_bulk_ingesting'] ?? ''),
        'import_images_upload_failed_missing_uuid' => (string) ($pageTexts['import_images_upload_failed_missing_uuid'] ?? ''),
        'import_images_upload_failed_no_file' => (string) ($pageTexts['import_images_upload_failed_no_file'] ?? ''),
        'import_images_upload_failed_detail' => (string) ($pageTexts['import_images_upload_failed_detail'] ?? ''),
        'import_images_bulk_failed_more' => (string) ($pageTexts['import_images_bulk_failed_more'] ?? ''),
        'import_images_bulk_error_summary' => (string) ($pageTexts['import_images_bulk_error_summary'] ?? ''),
        'import_images_error_no_file_received' => (string) ($pageTexts['import_images_error_no_file_received'] ?? ''),
        'import_images_error_upload_error' => (string) ($pageTexts['import_images_error_upload_error'] ?? ''),
        'import_images_error_timeout' => (string) ($pageTexts['import_images_error_timeout'] ?? ''),
        'import_images_error_api_timeout' => (string) ($pageTexts['import_images_error_api_timeout'] ?? ''),
        'import_images_error_unauthenticated' => (string) ($pageTexts['import_images_error_unauthenticated'] ?? ''),
        'import_images_upload_auth_missing' => (string) ($pageTexts['import_images_upload_auth_missing'] ?? ''),
        'import_images_upload_auth_missing_detail' => (string) ($pageTexts['import_images_upload_auth_missing_detail'] ?? ''),
        'import_images_error_invalid_response' => (string) ($pageTexts['import_images_error_invalid_response'] ?? ''),
        'import_images_error_sku_index_failed' => (string) ($pageTexts['import_images_error_sku_index_failed'] ?? ''),
        'import_images_sku_filter_fallback' => (string) ($pageTexts['import_images_sku_filter_fallback'] ?? ''),
        'import_images_error_html_response' => (string) ($pageTexts['import_images_error_html_response'] ?? ''),
        'unknown_error' => (string) ($pageTexts['unknown_error'] ?? ''),
        'import_images_error_title_no_sku' => (string) ($pageTexts['import_images_error_title_no_sku'] ?? ''),
        'import_images_error_title_format' => (string) ($pageTexts['import_images_error_title_format'] ?? ''),
        'import_images_error_title_link' => (string) ($pageTexts['import_images_error_title_link'] ?? ''),
        'import_images_error_title_upload' => (string) ($pageTexts['import_images_error_title_upload'] ?? ''),
        'import_images_error_title_unknown' => (string) ($pageTexts['import_images_error_title_unknown'] ?? ''),
        'import_images_result_success' => (string) ($pageTexts['import_images_result_success'] ?? ''),
        'import_images_upload_click_folder' => (string) ($pageTexts['import_images_upload_click'] ?? ''),
        'import_images_upload_click_files' => (string) ($pageTexts['import_images_upload_click_files'] ?? ''),
        'import_images_upload_hint_folder' => (string) ($pageTexts['import_images_upload_hint'] ?? ''),
        'import_images_upload_hint_files' => (string) ($pageTexts['import_images_upload_hint_files'] ?? ''),
        'import_images_folder_name' => $folderNameLabel,
        'import_images_folder_skipped' => (string) ($pageTexts['import_images_folder_skipped'] ?? ''),
        'import_images_validating_skus' => (string) ($pageTexts['import_images_validating_skus'] ?? ''),
        'import_images_no_sku_matches' => (string) ($pageTexts['import_images_no_sku_matches'] ?? ''),
        'import_images_folder_skipped_no_sku' => (string) ($pageTexts['import_images_folder_skipped_no_sku'] ?? ''),
        'import_images_folder_matched_summary' => (string) ($pageTexts['import_images_folder_matched_summary'] ?? ''),
        'dictRemoveFile' => $removeFileLabel,
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="<?= dated_file_url('companyasset/company/product/product-images-import.js') ?>"></script>
<script>
var fvImportGroupSep = <?= json_encode(fv_resolve_numeric_group_separator($format_numeric ?? null), JSON_UNESCAPED_UNICODE) ?>;

function fvFormatImportIntegerCount(value) {
    var n = parseInt(value, 10) || 0;
    return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, fvImportGroupSep);
}

var arrayfiles = [];

function fvProdImgUpdateResultCounts() {
    var okCount = document.querySelectorAll('#resultado_subidos .fv-category-images-import__result-item').length;
    var errCount = document.querySelectorAll('#resultado_nosubidos .fv-category-images-import__result-item').length;
    $('#fv-prod-img-count-ok').text(fvFormatImportIntegerCount(okCount));
    $('#fv-prod-img-count-error').text(fvFormatImportIntegerCount(errCount));
}

window.fvProdImgClearUploadResults = function () {
    $('#resultado_subidos').html('');
    $('#resultado_nosubidos').html('');
    fvProdImgUpdateResultCounts();
};

function fvProdImgSetImportButtonLoading(isLoading) {
    var $btn = $('#btn-export');
    if (!$btn.length) {
        return;
    }
    if (isLoading) {
        $btn.prop('disabled', true).data('fv-original-html', $btn.html());
        $btn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
    } else {
        $btn.prop('disabled', false);
        var original = $btn.data('fv-original-html');
        if (original) {
            $btn.html(original);
        }
    }
}

function fvProdImgAppendResultItem($container, messageOrOpts, imagen, isOk) {
    var opts = {};
    if (typeof messageOrOpts === 'object' && messageOrOpts !== null && typeof messageOrOpts.jquery === 'undefined') {
        opts = messageOrOpts;
    } else {
        opts = {
            detail: messageOrOpts,
            fileName: imagen,
            isOk: !!isOk
        };
    }

    var fileName = String(opts.fileName || opts.imagen || '').trim();
    var success = !!opts.isOk;
    var title = fvProdImgCleanText(opts.title || '');
    var detail = fvProdImgCleanText(opts.detail || opts.reason || opts.message || '');

    if (fileName && detail.indexOf(fileName) === 0) {
        detail = detail.substring(fileName.length).replace(/^[\s:—–\-]+/, '').trim();
    }

    if (!title && opts.errorCode) {
        title = fvProdImgImportErrorLabels['title_' + opts.errorCode] || '';
    }

    if (!detail && opts.errorCode && fvProdImgImportErrorLabels[opts.errorCode]) {
        detail = fvProdImgFillTemplate(fvProdImgImportErrorLabels[opts.errorCode], fileName, opts.errorExtra || '');
    }

    if (success && !detail) {
        detail = fvProdImgFillTemplate(fvProdImgImportErrorLabels.success || '', fileName, '');
    }

    if (!detail) {
        detail = fvProdImgImportErrorLabels.unknown || fvProdImgProgressLang.error || '';
    }

    if (!success && !title) {
        title = fvProdImgImportErrorLabels.title_unknown || '';
    }

    var itemClass = success
        ? 'fv-category-images-import__result-item fv-category-images-import__result-item--ok'
        : 'fv-category-images-import__result-item fv-category-images-import__result-item--error';
    var iconClass = success ? 'mdi-check-circle' : 'mdi-alert-circle-outline';

    var html = '<div class="' + itemClass + '" data-imagen="' + $('<div>').text(fileName).html() + '">' +
        '<i class="mdi ' + iconClass + '" aria-hidden="true"></i>' +
        '<div class="fv-category-images-import__result-body">';

    if (fileName) {
        html += '<div class="fv-category-images-import__result-file">' + $('<div>').text(fileName).html() + '</div>';
    }
    if (!success && title) {
        html += '<div class="fv-category-images-import__result-title">' + $('<div>').text(title).html() + '</div>';
    }
    html += '<div class="fv-category-images-import__result-detail">' + $('<div>').text(detail).html() + '</div>';
    html += '</div></div>';

    $container.append(html);
}

function fvProdImgCleanText(text) {
    if (typeof window.fvProdImgSanitizeErrorText === 'function') {
        return window.fvProdImgSanitizeErrorText(text);
    }
    return String(text || '').trim();
}

function fvProdImgFillTemplate(template, fileName, extra) {
    var tpl = String(template || '');
    if (!tpl) {
        return '';
    }
    if (tpl.indexOf('%s') !== -1) {
        var pos = tpl.indexOf('%s');
        var rest = tpl.slice(pos + 2);
        if (rest.indexOf('%s') !== -1) {
            var pos2 = rest.indexOf('%s');
            return tpl.slice(0, pos) + fileName + rest.slice(0, pos2) + (extra || '') + rest.slice(pos2 + 2);
        }
        return tpl.slice(0, pos) + fileName + tpl.slice(pos + 2);
    }
    return tpl.replace('{file}', fileName).replace('{error}', extra || '');
}

function fvProdImgResolveImportResult(item) {
    var fileName = item.imagen || item.image || '';
    var code = item.error_code || '';
    var detail = fvProdImgCleanText(item.mensaje || '');
    var title = '';

    if (code) {
        title = fvProdImgImportErrorLabels['title_' + code] || '';
    }

    if (fileName && detail.indexOf(fileName) === 0) {
        detail = detail.substring(fileName.length).replace(/^[\s:—–\-]+/, '').trim();
    }

    if (!detail && code && fvProdImgImportErrorLabels[code]) {
        detail = fvProdImgFillTemplate(fvProdImgImportErrorLabels[code], fileName, item.error || '');
    }

    return {
        fileName: fileName,
        errorCode: code,
        title: title,
        detail: detail,
        isOk: !!item.success
    };
}

window.fvProdImgAppendResultItem = fvProdImgAppendResultItem;
window.fvProdImgUpdateResultCounts = fvProdImgUpdateResultCounts;

var fvProdImgProgressLang = <?= json_encode([
    'title' => $importProgressTitle,
    'stepImages' => $importProgressStepImages,
    'detail' => $importProgressDetail,
    'complete' => $importProgressComplete,
    'error' => $importProgressError,
    'groupSep' => fv_resolve_numeric_group_separator($format_numeric ?? null),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

var fvProdImgImportErrorLabels = <?= json_encode([
    'no_sku_existente' => $importErrorNoSku,
    'no_cumple_format' => $importErrorFormat,
    'upload_error' => $importErrorUpload,
    'unknown' => $importErrorUnknown,
    'html_response' => $importErrorHtml,
    'success' => $importResultSuccess,
    'title_no_sku_existente' => $importErrorTitleNoSku,
    'title_no_cumple_format' => $importErrorTitleFormat,
    'title_upload_error' => $importErrorTitleLink,
    'title_unknown' => $importErrorTitleUnknown,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

function fvProdImgResolveImportMessage(item) {
    return fvProdImgResolveImportResult(item).detail;
}

$(document).on('submit', '#form-guardar-imagenes', function(e) {
        e.preventDefault();

        $('#resultado_subidos').html('');
        $('#resultado_nosubidos').html('');
        fvProdImgUpdateResultCounts();

        var startImport = function () {
            arrayfiles = (window.fvProdImagesImportApi && typeof window.fvProdImagesImportApi.getImportableFiles === 'function')
                ? window.fvProdImagesImportApi.getImportableFiles()
                : [];

            if (arrayfiles.length === 0) {
                fvProdImgSetImportButtonLoading(false);
                var summary = (window.fvProdImagesImportApi && typeof window.fvProdImagesImportApi.getUploadSummary === 'function')
                    ? window.fvProdImagesImportApi.getUploadSummary()
                    : null;
                var noReadyMsg;
                var msgWait = <?= json_encode((string) ($pageTexts['import_images_wait_uploads'] ?? ''), JSON_UNESCAPED_UNICODE) ?>;
                var msgAllFailed = <?= json_encode((string) ($pageTexts['import_images_all_uploads_failed'] ?? ''), JSON_UNESCAPED_UNICODE) ?>;
                var msgNone = <?= json_encode((string) ($pageTexts['no_images_to_import'] ?? ''), JSON_UNESCAPED_UNICODE) ?>;
                if (summary && (summary.uploading > 0 || summary.pending > 0)) {
                    noReadyMsg = msgWait || msgAllFailed || msgNone;
                } else if (summary && summary.checked > 0 && summary.done === 0) {
                    noReadyMsg = msgAllFailed || msgNone || msgWait;
                } else {
                    noReadyMsg = msgNone || msgAllFailed || msgWait;
                }
                if (!noReadyMsg) {
                    noReadyMsg = <?= json_encode((string) ($pageTexts['import_images_error_title_unknown'] ?? 'Error'), JSON_UNESCAPED_UNICODE) ?>;
                }
                if (typeof swal === 'function') {
                    swal({ title: noReadyMsg, icon: 'warning' });
                } else {
                    alert(noReadyMsg);
                }
                return;
            }

            var baseUrl = (typeof options !== 'undefined' && options.url) ? options.url : '/company';
        var url = baseUrl + '/products/importImages/import';
        var filesToImport = arrayfiles.slice();
        var progress = window.FvImportProgress;

        if (!progress) {
            fvProdImgSetImportButtonLoading(false);
            return;
        }

        progress.configure({ groupSep: fvProdImgProgressLang.groupSep || ',' });
        progress.show({
            title: fvProdImgProgressLang.title,
            groupSep: fvProdImgProgressLang.groupSep || ',',
            steps: [{ key: 'images', label: fvProdImgProgressLang.stepImages }]
        });
        progress.setStep('images', 'active');

        progress.runBatches({
            items: filesToImport,
            batchSize: 5,
            onProgress: function (pct, current, total) {
                progress.setProgress(
                    pct,
                    progress.replaceTokens(fvProdImgProgressLang.detail, {
                        current: progress.formatCount(current),
                        total: progress.formatCount(total)
                    }),
                    fvProdImgProgressLang.stepImages
                );
            },
            processBatch: function (chunk) {
                return new Promise(function (resolve, reject) {
                    $.ajax({
                        url: url,
                        type: 'POST',
                        data: { product_images: chunk },
                        dataType: 'json'
                    }).done(function (response) {
                        if (!$.isArray(response)) {
                            resolve();
                            return;
                        }
                        response.forEach(function (item) {
                            var parsed = fvProdImgResolveImportResult(item);
                            fvProdImgAppendResultItem(parsed.isOk ? $('#resultado_subidos') : $('#resultado_nosubidos'), {
                                fileName: parsed.fileName,
                                title: parsed.title,
                                detail: parsed.detail,
                                errorCode: parsed.errorCode,
                                isOk: parsed.isOk
                            });
                        });
                        fvProdImgUpdateResultCounts();
                        resolve();
                    }).fail(function (xhr) {
                        reject(xhr);
                    });
                });
            },
            onComplete: function () {
                progress.setStep('images', 'done');
                progress.setProgress(100, fvProdImgProgressLang.complete, fvProdImgProgressLang.complete);
                fvProdImgSetImportButtonLoading(false);
                if (window.fvProdImagesImportApi && typeof window.fvProdImagesImportApi.clearQueue === 'function') {
                    window.fvProdImagesImportApi.clearQueue();
                }
                arrayfiles = [];
                setTimeout(function () {
                    progress.hide();
                }, 500);
            },
            onError: function (xhr) {
                fvProdImgSetImportButtonLoading(false);
                var errorMsg = fvProdImgProgressLang.error;
                if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (xhr && xhr.responseText) {
                    errorMsg = xhr.responseText;
                }
                errorMsg = fvProdImgCleanText(errorMsg);
                fvProdImgAppendResultItem($('#resultado_nosubidos'), {
                    title: fvProdImgImportErrorLabels.title_unknown || '',
                    detail: errorMsg || fvProdImgImportErrorLabels.unknown || '',
                    isOk: false
                });
                fvProdImgUpdateResultCounts();
                progress.setProgress(0, errorMsg, errorMsg);
                setTimeout(function () {
                    progress.hide();
                }, 1800);
            }
        });
        };

        fvProdImgSetImportButtonLoading(true);
        var startUploadsPromise = (window.fvProdImagesImportApi && typeof window.fvProdImagesImportApi.startUploads === 'function')
            ? window.fvProdImagesImportApi.startUploads()
            : Promise.resolve();

        Promise.resolve(startUploadsPromise).then(function () {
            return (window.fvProdImagesImportApi && typeof window.fvProdImagesImportApi.waitForUploadsComplete === 'function')
                ? window.fvProdImagesImportApi.waitForUploadsComplete()
                : Promise.resolve();
        }).then(function () {
            startImport();
        }).catch(function () {
            fvProdImgSetImportButtonLoading(false);
            var summary = (window.fvProdImagesImportApi && typeof window.fvProdImagesImportApi.getUploadSummary === 'function')
                ? window.fvProdImagesImportApi.getUploadSummary()
                : null;
            if (summary && summary.done > 0) {
                startImport();
                return;
            }
            var failMsg = <?= json_encode((string) ($pageTexts['import_images_all_uploads_failed'] ?? ''), JSON_UNESCAPED_UNICODE) ?>;
            if (!failMsg) {
                failMsg = <?= json_encode((string) ($pageTexts['import_images_upload_auth_missing_detail'] ?? ''), JSON_UNESCAPED_UNICODE) ?>;
            }
            if (!failMsg) {
                failMsg = <?= json_encode((string) ($pageTexts['import_images_error_title_unknown'] ?? 'Error'), JSON_UNESCAPED_UNICODE) ?>;
            }
            if (typeof swal === 'function') {
                swal({ title: failMsg, icon: 'warning' });
            } else {
                alert(failMsg);
            }
        });
    });
</script>

<?php
/**
 * @var array $pageTexts
 * @var array $categoryOptions
 */
$pageTitle = (string) ($pageTexts['title_import_category'] ?? '');
$dropzoneDefaultMessage = (string) ($pageTexts['dictDefaultMessage'] ?? '');
$importLabel = (string) ($pageTexts['btn-modal-importar'] ?? '');
$cancelLabel = (string) ($pageTexts['btn-modal-cancelar'] ?? ($pageTexts['btn-cancelar'] ?? ''));
$uploadedLabel = (string) ($pageTexts['subidos'] ?? '');
$notUploadedLabel = (string) ($pageTexts['no_subidos'] ?? '');
$linkedLabel = (string) ($pageTexts['import_category_linked'] ?? '');
$unlinkedLabel = (string) ($pageTexts['import_category_unlinked'] ?? '');
$noteLabel = (string) ($pageTexts['nota'] ?? '');
$categorySlashNote = (string) ($pageTexts['import_category_slash_note'] ?? '');
$processingLabel = (string) ($pageTexts['processing_images'] ?? '');
$importProgressTitle = (string) ($pageTexts['import_progress_title_images'] ?? $processingLabel);
$importProgressStepImages = (string) ($pageTexts['import_progress_step_images'] ?? $processingLabel);
$importProgressDetail = (string) ($pageTexts['import_progress_detail'] ?? '');
$importProgressComplete = (string) ($pageTexts['import_progress_complete'] ?? '');
$importProgressError = (string) ($pageTexts['import_progress_error'] ?? ($pageTexts['error_importing'] ?? ''));
$groupSep = fv_resolve_numeric_group_separator($format_numeric ?? null);
$categoryOptions = is_array($categoryOptions ?? null) ? $categoryOptions : [];

$fvCatImgLangKeys = [
    'no_images_to_import',
    'no_category_link',
    'import_category_linked',
    'import_category_unlinked',
    'import_category_select_category',
    'import_category_no_linked_to_import',
    'import_category_linked_auto',
    'import_category_linked_manual',
    'import_category_queue_empty',
    'import_category_unlinked_skipped',
    'import_category_confirm_partial_title',
    'import_category_confirm_partial_text',
];
$fvCatImgLang = [];
foreach ($fvCatImgLangKeys as $key) {
    $fvCatImgLang[$key] = (string) ($pageTexts[$key] ?? '');
}
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

            <div class="card fv-category-images-import__card">
                <div class="card-body">
                    <div class="fv-category-images-import__intro">
                        <?= htmlspecialchars((string) ($pageTexts['text_import_category'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        <br>
                        <strong><?= htmlspecialchars($noteLabel, ENT_QUOTES, 'UTF-8') ?>:</strong>
                        <?= htmlspecialchars($categorySlashNote, ENT_QUOTES, 'UTF-8') ?>
                        <br>
                        <?= htmlspecialchars((string) ($pageTexts['import_category_manual_link_hint'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </div>

                    <?php if ($categoryOptions === []) : ?>
                    <div class="alert alert-warning fv-category-images-import__no-categories" role="alert">
                        <?= htmlspecialchars((string) ($pageTexts['import_category_no_categories_available'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <?php endif; ?>

                    <div class="fv-category-images-import__layout">
                        <div class="fv-category-images-import__dropzone-panel">
                            <form action="<?= company_url('categories/importImages/uploadImage') ?>"
                                  class="dropzone fv-category-images-import__dropzone"
                                  id="product-images-dropzone"
                                  enctype="multipart/form-data"></form>
                        </div>

                        <div class="fv-category-images-import__side">
                            <div class="fv-category-images-import__queue" aria-live="polite">
                                <div class="fv-category-images-import__queue-head">
                                    <div class="fv-category-images-import__queue-col-title fv-category-images-import__queue-col-title--linked">
                                        <span><?= htmlspecialchars($linkedLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="fv-category-images-import__count" id="fv-cat-img-count-linked">0</span>
                                    </div>
                                    <div class="fv-category-images-import__queue-col-title fv-category-images-import__queue-col-title--unlinked">
                                        <span><?= htmlspecialchars($unlinkedLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="fv-category-images-import__count" id="fv-cat-img-count-unlinked">0</span>
                                    </div>
                                </div>
                                <div class="fv-category-images-import__queue-body">
                                    <div class="fv-category-images-import__queue-list" id="queue_linked"></div>
                                    <div class="fv-category-images-import__queue-list" id="queue_unlinked"></div>
                                </div>
                            </div>

                            <div class="fv-category-images-import__results" aria-live="polite">
                                <div class="fv-category-images-import__results-head">
                                    <div class="fv-category-images-import__results-col-title fv-category-images-import__results-col-title--ok">
                                        <span><?= htmlspecialchars($uploadedLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="fv-category-images-import__count" id="fv-cat-img-count-import-ok">0</span>
                                    </div>
                                    <div class="fv-category-images-import__results-col-title fv-category-images-import__results-col-title--error">
                                        <span><?= htmlspecialchars($notUploadedLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                        <span class="fv-category-images-import__count" id="fv-cat-img-count-import-error">0</span>
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
</div>

<form action="<?= company_url('categories/importImages/import') ?>" id="form-guardar-imagenes" onsubmit="return false;"></form>

<?php $this->load->view('components/crud/form_action_fab', [
    'formId' => 'form-guardar-imagenes',
    'saveLabel' => $importLabel,
    'saveButtonId' => 'btn-export',
    'saveButtonType' => 'button',
    'saveIconClass' => 'fa-solid fa-cloud-arrow-up',
    'cancelLabel' => $cancelLabel,
    'cancelUrl' => company_url('categories'),
]); ?>

<script src="<?= base_url('companyasset/js/dropzone.min.js') ?>"></script>
<script src="<?= dated_file_url('companyasset/company/fv-import-progress.js') ?>"></script>
<script>
window.fvCatImgImport = <?= json_encode([
    'categories' => $categoryOptions,
    'groupSep' => $groupSep,
    'importUrl' => company_url('categories/importImages/import'),
    'dropzoneDefaultMessage' => $dropzoneDefaultMessage,
    'dictFallbackMessage' => (string) ($pageTexts['dictFallbackMessage'] ?? ''),
    'dictFallbackText' => (string) ($pageTexts['dictFallbackText'] ?? ''),
    'dictFileTooBig' => (string) ($pageTexts['dictFileTooBig'] ?? ''),
    'dictInvalidFileType' => (string) ($pageTexts['dictInvalidFileType'] ?? ''),
    'dictResponseError' => (string) ($pageTexts['dictResponseError'] ?? ''),
    'dictCancelUpload' => (string) ($pageTexts['dictCancelUpload'] ?? ''),
    'dictCancelUploadConfirmation' => (string) ($pageTexts['dictCancelUploadConfirmation'] ?? ''),
    'dictRemoveFile' => (string) ($pageTexts['dictRemoveFile'] ?? ''),
    'dictMaxFilesExceeded' => (string) ($pageTexts['dictMaxFilesExceeded'] ?? ''),
    'progressTitle' => $importProgressTitle,
    'progressStepImages' => $importProgressStepImages,
    'progressDetail' => $importProgressDetail,
    'progressComplete' => $importProgressComplete,
    'progressError' => $importProgressError,
    'lang' => $fvCatImgLang,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="<?= dated_file_url('companyasset/company/categories/category-images-import.js') ?>"></script>

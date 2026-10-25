<?php
/**
 * Modal de vista previa del catálogo (mismo render que el Studio).
 *
 * @var object $v
 * @var array  $langcatalog
 * @var array  $lang_modal
 */
$langcatalog = (isset($langcatalog) && is_array($langcatalog)) ? $langcatalog : [];
$lang_modal = (isset($lang_modal) && is_array($lang_modal)) ? $lang_modal : [];

$catalogId = (int) ($v->catalog_id ?? 0);
$closeLabel = (string) ($lang_modal['close'] ?? $langcatalog['close'] ?? '');
$editLabel = (string) ($langcatalog['edit'] ?? $langcatalog['open_edit'] ?? '');
$previewLabel = (string) ($langcatalog['preview'] ?? $langcatalog['studio_live_preview'] ?? '');
$loadingLabel = (string) ($langcatalog['studio_loading_preview'] ?? '');
$emptyLabel = (string) ($langcatalog['studio_empty_preview'] ?? $langcatalog['no_products_preview'] ?? '');
$errorLabel = (string) ($langcatalog['studio_preview_error'] ?? $langcatalog['filter_error'] ?? $langcatalog['update_error'] ?? '');
$catalogName = (string) ($v->catalog_name ?? '');
?>
<div class="modal fade previewModal fv-catalog-modal fv-catalog-live-preview-modal"
     id="previewModal<?= $catalogId ?>"
     tabindex="-1"
     aria-labelledby="previewModalLabel<?= $catalogId ?>"
     aria-hidden="true"
     data-preview-api="<?= htmlspecialchars(company_url('catalog/studioPreviewCatalog/' . $catalogId), ENT_QUOTES, 'UTF-8') ?>"
     data-templates-base="<?= htmlspecialchars(base_url('companyasset/company/catalog/templates'), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-loading="<?= htmlspecialchars($loadingLabel, ENT_QUOTES, 'UTF-8') ?>"
     data-msg-empty="<?= htmlspecialchars($emptyLabel, ENT_QUOTES, 'UTF-8') ?>"
     data-msg-error="<?= htmlspecialchars($errorLabel, ENT_QUOTES, 'UTF-8') ?>">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel<?= $catalogId ?>">
                    <span class="modal-header__avatar" aria-hidden="true">
                        <i class="mdi mdi-eye-outline"></i>
                    </span>
                    <span><?= htmlspecialchars($previewLabel, ENT_QUOTES, 'UTF-8') ?>
                        <?php if ($catalogName !== '') : ?>
                            — <?= htmlspecialchars($catalogName, ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
                    </span>
                </h5>
                <button type="button" class="close fv-modal-close-x" data-dismiss="modal" data-bs-dismiss="modal"
                        aria-label="<?= htmlspecialchars($closeLabel, ENT_QUOTES, 'UTF-8') ?>">
                    <i class="mdi mdi-close" aria-hidden="true"></i>
                </button>
            </div>
            <div class="modal-body p-0">
                <div class="fv-catalog-live-preview__status" data-preview-status></div>
                <iframe class="fv-catalog-live-preview__frame"
                        data-catalog-preview-frame
                        title="<?= htmlspecialchars($previewLabel, ENT_QUOTES, 'UTF-8') ?>"
                        scrolling="yes"></iframe>
            </div>
            <div class="modal-footer">
                <a href="<?= company_url('catalog/edit/' . $catalogId) ?>" class="btn fv-studio-btn fv-studio-btn--primary btn-danger">
                    <?= htmlspecialchars($editLabel, ENT_QUOTES, 'UTF-8') ?>
                </a>
                <button type="button" data-dismiss="modal" data-bs-dismiss="modal"
                        aria-label="<?= htmlspecialchars($closeLabel, ENT_QUOTES, 'UTF-8') ?>"
                        class="btn btn-outline-secondary"><?= htmlspecialchars($closeLabel, ENT_QUOTES, 'UTF-8') ?></button>
            </div>
        </div>
    </div>
</div>

<?php
/**
 * Modal descarga: preview en vivo del catálogo + exportar PDF.
 *
 * @var object $v
 * @var array $group_list
 * @var array $lang_optionbasic
 * @var array $langcatalog
 * @var array $lang_modal
 */
$lang_optionbasic = (isset($lang_optionbasic) && is_array($lang_optionbasic)) ? $lang_optionbasic : [];
$langcatalog = (isset($langcatalog) && is_array($langcatalog)) ? $langcatalog : [];
$lang_modal = (isset($lang_modal) && is_array($lang_modal)) ? $lang_modal : [];
$group_list = (isset($group_list) && is_array($group_list)) ? $group_list : [];
$catalogId = (int) ($v->catalog_id ?? 0);
$catalogName = (string) ($v->catalog_name ?? '');
$downloadTitle = (string) ($langcatalog['descargar'] ?? $lang_optionbasic['descargar'] ?? $langcatalog['download'] ?? '');
$closeLabel = (string) ($lang_modal['close'] ?? $langcatalog['close'] ?? '');
$nothingSelected = (string) ($lang_optionbasic['nothing_selected'] ?? $langcatalog['nothing_selected'] ?? '');
$exportLabel = (string) ($langcatalog['studio_export_pdf'] ?? $langcatalog['aceptar'] ?? '');
$loadingLabel = (string) ($langcatalog['studio_loading_preview'] ?? '');
$emptyLabel = (string) ($langcatalog['studio_empty_preview'] ?? '');
$errorLabel = (string) ($langcatalog['studio_preview_error'] ?? $langcatalog['filter_error'] ?? '');
$generatingLabel = (string) ($langcatalog['studio_generating_pdf'] ?? $langcatalog['aceptando'] ?? '');
$previewHint = (string) ($langcatalog['studio_download_uses_preview'] ?? '');
?>
<div class="modal fade fv-catalog-modal fv-catalog-live-preview-modal fv-catalog-download-modal"
     id="downloadModal_<?= $catalogId ?>"
     tabindex="-1"
     aria-labelledby="downloadModalLabel<?= $catalogId ?>"
     aria-hidden="true"
     data-catalog-id="<?= $catalogId ?>"
     data-catalog-name="<?= htmlspecialchars($catalogName, ENT_QUOTES, 'UTF-8') ?>"
     data-preview-api="<?= htmlspecialchars(company_url('catalog/studioPreviewCatalog/' . $catalogId), ENT_QUOTES, 'UTF-8') ?>"
     data-download-api="<?= htmlspecialchars(company_url('catalog/download/' . $catalogId), ENT_QUOTES, 'UTF-8') ?>"
     data-export-pdf-api="<?= htmlspecialchars(company_url('catalog/studioExportPdf'), ENT_QUOTES, 'UTF-8') ?>"
     data-templates-base="<?= htmlspecialchars(base_url('companyasset/company/catalog/templates'), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-loading="<?= htmlspecialchars($loadingLabel, ENT_QUOTES, 'UTF-8') ?>"
     data-msg-empty="<?= htmlspecialchars($emptyLabel, ENT_QUOTES, 'UTF-8') ?>"
     data-msg-error="<?= htmlspecialchars($errorLabel, ENT_QUOTES, 'UTF-8') ?>"
     data-msg-generating="<?= htmlspecialchars($generatingLabel, ENT_QUOTES, 'UTF-8') ?>"
     data-msg-generating-pct="<?= htmlspecialchars((string) ($langcatalog['studio_generating_pdf_pct'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-pdf-error="<?= htmlspecialchars((string) ($langcatalog['studio_export_pdf_error'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="downloadModalLabel<?= $catalogId ?>">
                    <span class="modal-header__avatar" aria-hidden="true"><i class="mdi mdi-download"></i></span>
                    <span><?= htmlspecialchars($downloadTitle, ENT_QUOTES, 'UTF-8') ?>
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
                <div class="fv-catalog-download-toolbar">
                    <form class="download-modal-form fv-catalog-download-toolbar__form" onsubmit="return false;">
                        <div class="fv-catalog-download-toolbar__field">
                            <label for="downloadGroup<?= $catalogId ?>"><?= htmlspecialchars($lang_optionbasic['select_group'] ?? $langcatalog['select_group'] ?? '', ENT_QUOTES, 'UTF-8') ?></label>
                            <select id="downloadGroup<?= $catalogId ?>" name="group" class="form-control selectpicker"
                                    data-none-selected-text="<?= htmlspecialchars($nothingSelected, ENT_QUOTES, 'UTF-8') ?>">
                                <option value="" disabled><?= htmlspecialchars($nothingSelected, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php
                                $hasDefaultGroup = false;
                                foreach ($group_list as $group) {
                                    if ((int) ($group->default ?? 0) === 1) {
                                        $hasDefaultGroup = true;
                                        break;
                                    }
                                }
                                $firstGroupUsed = false;
                                foreach ($group_list as $group) :
                                    $isSelected = ((int) ($group->default ?? 0) === 1)
                                        || (!$hasDefaultGroup && !$firstGroupUsed);
                                    if ($isSelected) {
                                        $firstGroupUsed = true;
                                    }
                                    ?>
                                    <option <?= $isSelected ? 'selected' : '' ?>
                                            value="<?= (int) ($group->id ?? 0) ?>">
                                        <?= htmlspecialchars(ucwords((string) ($group->name ?? '')), ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <label class="fv-catalog-hide-price fv-catalog-download-toolbar__check" for="hide_price-<?= $catalogId ?>">
                            <input id="hide_price-<?= $catalogId ?>" type="checkbox" value="1" name="hide_price">
                            <?= htmlspecialchars((string) ($langcatalog['hide_price'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </label>
                    </form>
                    <?php if ($previewHint !== '') : ?>
                        <p class="fv-catalog-download-toolbar__hint"><?= htmlspecialchars($previewHint, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </div>
                <div class="fv-catalog-live-preview__status" data-preview-status></div>
                <div class="fv-catalog-download-preview-wrap">
                    <iframe class="fv-catalog-live-preview__frame"
                            data-catalog-preview-frame
                            title="<?= htmlspecialchars($downloadTitle, ENT_QUOTES, 'UTF-8') ?>"
                            scrolling="yes"></iframe>
                    <div class="fv-catalog-download-loader" data-download-loader hidden>
                        <div class="fv-catalog-download-loader__card" role="status" aria-live="polite">
                            <span class="fv-catalog-download-loader__spinner" aria-hidden="true"></span>
                            <p class="fv-catalog-download-loader__text" data-download-loader-text><?= htmlspecialchars($generatingLabel, ENT_QUOTES, 'UTF-8') ?></p>
                            <div class="fv-catalog-download-loader__bar" aria-hidden="true"><span data-download-loader-bar></span></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button"
                        class="btn bg-success fv-catalog-download-export"
                        data-download-export
                        data-download-id="downloadModal_<?= $catalogId ?>">
                    <i class="mdi mdi-file-pdf-box" aria-hidden="true"></i>
                    <?= htmlspecialchars($exportLabel, ENT_QUOTES, 'UTF-8') ?>
                </button>
                <button type="button" data-dismiss="modal" data-bs-dismiss="modal"
                        aria-label="<?= htmlspecialchars($closeLabel, ENT_QUOTES, 'UTF-8') ?>"
                        class="btn btn-danger"><?= htmlspecialchars($closeLabel, ENT_QUOTES, 'UTF-8') ?></button>
            </div>
        </div>
    </div>
</div>

<?php
/**
 * Catalog Studio — crear / editar catálogo (página completa).
 *
 * @var array  $pageTexts
 * @var array  $langcatalog
 * @var array  $category
 * @var array  $templates
 * @var object|null $catalog
 * @var array  $selectedProducts
 * @var array  $selectedCategories
 * @var string $mode create|edit
 * @var bool   $isDefault
 * @var string $saveUrl
 * @var string $templatesBaseUrl
 * @var string $productSearchUrl
 * @var string $backUrl
 */

$pageTexts = (isset($pageTexts) && is_array($pageTexts)) ? $pageTexts : [];
$langcatalog = (isset($langcatalog) && is_array($langcatalog)) ? $langcatalog : $pageTexts;
$category = (isset($category) && is_array($category)) ? $category : [];
$templates = (isset($templates) && is_array($templates)) ? $templates : [];
$selectedProducts = (isset($selectedProducts) && is_array($selectedProducts)) ? $selectedProducts : [];
$selectedCategories = (isset($selectedCategories) && is_array($selectedCategories)) ? $selectedCategories : [];
$mode = ($mode ?? 'create') === 'edit' ? 'edit' : 'create';
$isDefault = !empty($isDefault);
$productSource = (!empty($selectedProducts) && !$isDefault) ? 'skus' : 'categories';
$catalog = $catalog ?? null;
$catalogId = (int) ($catalog->catalog_id ?? 0);
$catalogName = (string) ($catalog->catalog_name ?? '');
$cabeza = (string) ($catalog->cabeza_pagina ?? '');
$status = (int) ($catalog->status ?? 1);
$templateKey = (string) ($templateKey ?? 'classic-grid');
$showPrices = array_key_exists('showPrices', get_defined_vars()) ? (bool) $showPrices : true;
$colorPresets = (isset($colorPresets) && is_array($colorPresets)) ? $colorPresets : [];
$themeColors = (isset($themeColors) && is_array($themeColors)) ? $themeColors : [
    'banner_bg' => '#111827',
    'banner_text' => '#FFFFFF',
    'footer_bg' => '#111827',
    'footer_text' => '#E5E7EB',
    'accent' => '#B91C1C',
];
$companyName = (string) ($companyName ?? 'FullVendor');
$companyLogoUrl = (string) ($companyLogoUrl ?? '');
$catalogLogoUuid = (string) ($catalogLogoUuid ?? '');
$catalogLogoUrl = (string) ($catalogLogoUrl ?? '');
$suggestMessageUrl = (string) ($suggestMessageUrl ?? company_url('catalog/suggestFeaturedMessage'));

$t = static function (string $key, string $fallback = '') use ($langcatalog, $pageTexts): string {
    $v = $langcatalog[$key] ?? $pageTexts[$key] ?? $fallback;
    return is_string($v) ? $v : $fallback;
};

$studioTitle = $mode === 'edit'
    ? $t('studio_edit_title', $t('edit_Catalog', ''))
    : $t('studio_create_title', $t('add_Catalog', ''));
?>
<link rel="stylesheet" href="<?= dated_file_url('companyasset/company/catalog/catalog-studio.css') ?>">

    <div class="fv-catalog-studio<?= $isDefault ? ' fv-catalog-studio--default' : '' ?>" id="fvCatalogStudio"
     data-mode="<?= htmlspecialchars($mode, ENT_QUOTES, 'UTF-8') ?>"
     data-catalog-id="<?= $catalogId ?>"
     data-is-default="<?= $isDefault ? '1' : '0' ?>"
     data-save-url="<?= htmlspecialchars((string) $saveUrl, ENT_QUOTES, 'UTF-8') ?>"
     data-save-categories-url="<?= htmlspecialchars((string) ($saveCategoriesUrl ?? company_url('catalog/studioSaveCategories')), ENT_QUOTES, 'UTF-8') ?>"
     data-back-url="<?= htmlspecialchars((string) $backUrl, ENT_QUOTES, 'UTF-8') ?>"
     data-msg-categories-saved="<?= htmlspecialchars($t('studio_categories_order_saved', ''), ENT_QUOTES, 'UTF-8') ?>"
     data-templates-base="<?= htmlspecialchars((string) $templatesBaseUrl, ENT_QUOTES, 'UTF-8') ?>"
     data-product-search-url="<?= htmlspecialchars((string) $productSearchUrl, ENT_QUOTES, 'UTF-8') ?>"
     data-order-search-url="<?= htmlspecialchars((string) ($orderSearchUrl ?? company_url('catalog/studioSearchOrders')), ENT_QUOTES, 'UTF-8') ?>"
     data-order-products-url="<?= htmlspecialchars((string) ($orderProductsUrl ?? company_url('catalog/studioOrderProducts')), ENT_QUOTES, 'UTF-8') ?>"
     data-excel-import-url="<?= htmlspecialchars((string) ($excelImportUrl ?? company_url('catalog/studioImportSkus')), ENT_QUOTES, 'UTF-8') ?>"
     data-excel-template-url="<?= htmlspecialchars((string) ($excelTemplateUrl ?? company_url('catalog/studioSkuTemplate')), ENT_QUOTES, 'UTF-8') ?>"
     data-preview-url="<?= htmlspecialchars((string) ($previewUrl ?? company_url('catalog/studioPreview')), ENT_QUOTES, 'UTF-8') ?>"
     data-export-pdf-url="<?= htmlspecialchars((string) ($exportPdfUrl ?? company_url('catalog/studioExportPdf')), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-export-pdf-empty="<?= htmlspecialchars($t('studio_export_pdf_empty', ''), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-export-pdf-error="<?= htmlspecialchars($t('studio_export_pdf_error', ''), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-generating-pdf="<?= htmlspecialchars($t('studio_generating_pdf', ''), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-generating-pdf-pct="<?= htmlspecialchars($t('studio_generating_pdf_pct', ''), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-order-loading="<?= htmlspecialchars($t('studio_order_loading', ''), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-order-loaded="<?= htmlspecialchars($t('studio_order_loaded', ''), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-order-empty="<?= htmlspecialchars($t('studio_order_empty', ''), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-order-required="<?= htmlspecialchars($t('studio_order_required', ''), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-excel-loading="<?= htmlspecialchars($t('studio_excel_loading', ''), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-excel-loaded="<?= htmlspecialchars($t('studio_excel_loaded', ''), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-excel-empty="<?= htmlspecialchars($t('studio_excel_empty', ''), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-excel-not-found="<?= htmlspecialchars($t('studio_excel_not_found', ''), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-excel-required="<?= htmlspecialchars($t('studio_excel_required', ''), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-excel-error="<?= htmlspecialchars($t('studio_excel_error', ''), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-products-count="<?= htmlspecialchars($t('studio_products_count', '{count}'), ENT_QUOTES, 'UTF-8') ?>"
     data-company-name="<?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?>"
     data-company-logo="<?= htmlspecialchars($companyLogoUrl, ENT_QUOTES, 'UTF-8') ?>"
     data-catalog-logo="<?= htmlspecialchars($catalogLogoUrl, ENT_QUOTES, 'UTF-8') ?>"
     data-company-email="<?= htmlspecialchars((string) ($companyEmail ?? ''), ENT_QUOTES, 'UTF-8') ?>"
     data-company-phone="<?= htmlspecialchars((string) ($companyPhone ?? ''), ENT_QUOTES, 'UTF-8') ?>"
     data-company-cell="<?= htmlspecialchars((string) ($companyCell ?? ''), ENT_QUOTES, 'UTF-8') ?>"
     data-company-website="<?= htmlspecialchars((string) ($companyWebsite ?? ''), ENT_QUOTES, 'UTF-8') ?>"
     data-company-address="<?= htmlspecialchars((string) ($companyAddress ?? ''), ENT_QUOTES, 'UTF-8') ?>"
     data-company-city="<?= htmlspecialchars((string) ($companyCity ?? ''), ENT_QUOTES, 'UTF-8') ?>"
     data-company-state="<?= htmlspecialchars((string) ($companyState ?? ''), ENT_QUOTES, 'UTF-8') ?>"
     data-company-country="<?= htmlspecialchars((string) ($companyCountry ?? ''), ENT_QUOTES, 'UTF-8') ?>"
     data-company-zip="<?= htmlspecialchars((string) ($companyZip ?? ''), ENT_QUOTES, 'UTF-8') ?>"
     data-suggest-message-url="<?= htmlspecialchars($suggestMessageUrl, ENT_QUOTES, 'UTF-8') ?>"
     data-msg-required="<?= htmlspecialchars($t('name_required', ''), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-saving="<?= htmlspecialchars($t('saving', ''), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-empty-preview="<?= htmlspecialchars($t('studio_empty_preview', ''), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-loading-preview="<?= htmlspecialchars($t('studio_loading_preview', ''), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-save-error="<?= htmlspecialchars($t('update_error', $t('create_error', '')), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-save-ok="<?= htmlspecialchars($mode === 'edit' ? $t('updated_success', '') : $t('added_success', ''), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-default-only="<?= htmlspecialchars($t('catalogo_default_reorder_only', ''), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-move-up="<?= htmlspecialchars($t('studio_move_up', ''), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-move-down="<?= htmlspecialchars($t('studio_move_down', ''), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-ai-loading="<?= htmlspecialchars($t('studio_featured_ai_loading', ''), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-ai-error="<?= htmlspecialchars($t('studio_featured_ai_error', ''), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-ai-unavailable="<?= htmlspecialchars($t('studio_featured_ai_unavailable', ''), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-ai-quota="<?= htmlspecialchars($t('studio_featured_ai_quota', ''), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-logo-company="<?= htmlspecialchars($t('studio_logo_company_fallback', ''), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-logo-help="<?= htmlspecialchars($t('studio_logo_help', ''), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-logo-placeholder="<?= htmlspecialchars($t('studio_logo_placeholder', ''), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-categories-count="<?= htmlspecialchars($t('studio_categories_count', '{count}'), ENT_QUOTES, 'UTF-8') ?>"
     data-msg-categories-none="<?= htmlspecialchars($t('studio_categories_none', ''), ENT_QUOTES, 'UTF-8') ?>">

    <header class="fv-catalog-studio__top">
        <div class="fv-catalog-studio__top-main">
            <a class="fv-catalog-studio__back" href="<?= htmlspecialchars((string) $backUrl, ENT_QUOTES, 'UTF-8') ?>">
                <i class="mdi mdi-arrow-left" aria-hidden="true"></i>
                <span><?= htmlspecialchars($t('studio_back_to_list', $t('catalogs', '')), ENT_QUOTES, 'UTF-8') ?></span>
            </a>
            <h1 class="fv-catalog-studio__title"><?= htmlspecialchars($studioTitle, ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="fv-catalog-studio__subtitle"><?= htmlspecialchars(
                $isDefault ? $t('catalogo_default_reorder_hint', $t('studio_subtitle', '')) : $t('studio_subtitle', ''),
                ENT_QUOTES,
                'UTF-8'
            ) ?></p>
        </div>
        <div class="fv-catalog-studio__top-actions">
            <button type="button" class="btn fv-studio-btn fv-studio-btn--ghost" id="fvStudioCancel">
                <?= htmlspecialchars($t('cancel', ''), ENT_QUOTES, 'UTF-8') ?>
            </button>
            <button type="button" class="btn fv-studio-btn fv-studio-btn--primary" id="fvStudioSave">
                <i class="mdi mdi-content-save-outline" aria-hidden="true"></i>
                <?= htmlspecialchars($t('save', $t('aceptar', '')), ENT_QUOTES, 'UTF-8') ?>
            </button>
        </div>
    </header>

    <div class="fv-catalog-studio__workspace">
        <aside class="fv-catalog-studio__sidebar" aria-label="<?= htmlspecialchars($studioTitle, ENT_QUOTES, 'UTF-8') ?>">
            <form id="fvStudioForm" class="fv-catalog-studio__form" autocomplete="off">
                <?php if ($mode === 'edit') : ?>
                    <input type="hidden" name="catalog_id" value="<?= $catalogId ?>">
                <?php endif; ?>

                <section class="fv-studio-section">
                    <label class="fv-studio-label" for="fvStudioName"><?= htmlspecialchars($t('nombre_catalogo', ''), ENT_QUOTES, 'UTF-8') ?></label>
                    <?php if ($isDefault) : ?>
                        <input type="hidden" name="catalog_name" value="<?= htmlspecialchars($catalogName, ENT_QUOTES, 'UTF-8') ?>">
                        <input id="fvStudioName" class="fv-studio-input is-locked" type="text"
                               value="<?= htmlspecialchars($catalogName, ENT_QUOTES, 'UTF-8') ?>"
                               disabled
                               aria-disabled="true"
                               placeholder="<?= htmlspecialchars($t('nombre_catalogo', ''), ENT_QUOTES, 'UTF-8') ?>">
                    <?php else : ?>
                        <input id="fvStudioName" class="fv-studio-input" type="text" name="catalog_name"
                               value="<?= htmlspecialchars($catalogName, ENT_QUOTES, 'UTF-8') ?>"
                               required
                               placeholder="<?= htmlspecialchars($t('nombre_catalogo', ''), ENT_QUOTES, 'UTF-8') ?>">
                    <?php endif; ?>
                </section>

                <section class="fv-studio-section">
                    <span class="fv-studio-label"><?= htmlspecialchars($t('studio_logo', ''), ENT_QUOTES, 'UTF-8') ?></span>
                    <div class="fv-studio-logo" id="fvStudioLogoPicker">
                        <div class="fv-studio-logo__preview">
                            <?php
                            $logoPreview = $catalogLogoUrl !== '' ? $catalogLogoUrl : $companyLogoUrl;
                            $usingCompanyLogo = $catalogLogoUrl === '' && $companyLogoUrl !== '';
                            ?>
                            <?php if ($logoPreview !== '') : ?>
                                <img id="fvStudioLogoImg" src="<?= htmlspecialchars($logoPreview, ENT_QUOTES, 'UTF-8') ?>" alt="">
                            <?php else : ?>
                                <img id="fvStudioLogoImg" src="" alt="" hidden>
                                <span class="fv-studio-logo__placeholder" id="fvStudioLogoPlaceholder"><?= htmlspecialchars($t('studio_logo_placeholder', ''), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="fv-studio-logo__actions">
                            <label class="fv-studio-btn fv-studio-btn--ghost fv-studio-logo__file-btn">
                                <i class="mdi mdi-image-plus" aria-hidden="true"></i>
                                <span><?= htmlspecialchars($t('studio_logo_choose', ''), ENT_QUOTES, 'UTF-8') ?></span>
                                <input type="file" id="fvStudioLogoInput" name="logo" accept="image/*" hidden>
                            </label>
                            <button type="button" class="fv-studio-btn fv-studio-btn--ghost" id="fvStudioLogoClear" <?= ($catalogLogoUrl === '' && !$usingCompanyLogo) ? 'hidden' : '' ?>>
                                <?= htmlspecialchars($t('studio_logo_clear', ''), ENT_QUOTES, 'UTF-8') ?>
                            </button>
                            <p class="fv-studio-help" id="fvStudioLogoHint">
                                <?= htmlspecialchars(
                                    $catalogLogoUrl !== ''
                                        ? $t('studio_logo_help', '')
                                        : ($usingCompanyLogo ? $t('studio_logo_company_fallback', '') : $t('studio_logo_help', '')),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </p>
                        </div>
                        <input type="hidden" name="logo_uuid" id="fvStudioLogoUuid" value="<?= htmlspecialchars($catalogLogoUuid, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="clear_logo" id="fvStudioClearLogo" value="0">
                    </div>
                </section>

                <?php if (!$isDefault) : ?>
                <section class="fv-studio-section">
                    <label class="fv-studio-label" for="fvStudioStatus"><?= htmlspecialchars($t('status', ''), ENT_QUOTES, 'UTF-8') ?></label>
                    <select id="fvStudioStatus" class="fv-studio-input" name="status">
                        <option value="1" <?= $status === 1 ? 'selected' : '' ?>><?= htmlspecialchars($t('enabled', ''), ENT_QUOTES, 'UTF-8') ?></option>
                        <option value="0" <?= $status === 0 ? 'selected' : '' ?>><?= htmlspecialchars($t('disabled', ''), ENT_QUOTES, 'UTF-8') ?></option>
                    </select>
                </section>
                <?php else : ?>
                    <input type="hidden" name="status" value="1">
                    <div class="fv-studio-note"><?= htmlspecialchars($t('default_badge', ''), ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($t('catalogo_default_reorder_hint', ''), ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>

                <section class="fv-studio-section<?= $isDefault ? ' is-locked' : '' ?>">
                    <span class="fv-studio-label"><?= htmlspecialchars($t('studio_product_source', ''), ENT_QUOTES, 'UTF-8') ?></span>
                    <div class="fv-studio-source" role="radiogroup" <?= $isDefault ? 'aria-disabled="true"' : '' ?>>
                        <label class="fv-studio-source__card<?= $isDefault ? ' is-locked' : '' ?>">
                            <input type="radio" name="product_source" value="categories" <?= $productSource === 'categories' ? 'checked' : '' ?> <?= $isDefault ? 'disabled' : '' ?>>
                            <span class="fv-studio-source__body">
                                <strong><?= htmlspecialchars($t('studio_source_categories', ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                <small><?= htmlspecialchars($t('studio_source_categories_help', ''), ENT_QUOTES, 'UTF-8') ?></small>
                            </span>
                        </label>
                        <label class="fv-studio-source__card<?= $isDefault ? ' is-locked' : '' ?>">
                            <input type="radio" name="product_source" value="skus" <?= $productSource === 'skus' ? 'checked' : '' ?> <?= $isDefault ? 'disabled' : '' ?>>
                            <span class="fv-studio-source__body">
                                <strong><?= htmlspecialchars($t('studio_source_skus', ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                <small><?= htmlspecialchars($t('studio_source_skus_help', ''), ENT_QUOTES, 'UTF-8') ?></small>
                            </span>
                        </label>
                        <?php if (!$isDefault) : ?>
                        <label class="fv-studio-source__card">
                            <input type="radio" name="product_source" value="order">
                            <span class="fv-studio-source__body">
                                <strong><?= htmlspecialchars($t('studio_source_order', ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                <small><?= htmlspecialchars($t('studio_source_order_help', ''), ENT_QUOTES, 'UTF-8') ?></small>
                            </span>
                        </label>
                        <label class="fv-studio-source__card">
                            <input type="radio" name="product_source" value="excel">
                            <span class="fv-studio-source__body">
                                <strong><?= htmlspecialchars($t('studio_source_excel', ''), ENT_QUOTES, 'UTF-8') ?></strong>
                                <small><?= htmlspecialchars($t('studio_source_excel_help', ''), ENT_QUOTES, 'UTF-8') ?></small>
                            </span>
                        </label>
                        <?php endif; ?>
                    </div>
                    <?php if ($isDefault) : ?>
                        <input type="hidden" name="product_source" value="categories">
                    <?php endif; ?>
                </section>

                <section class="fv-studio-section fv-studio-panel is-active" data-source-panel="categories">
                    <?php
                    $selectedIds = [];
                    $selectedRows = [];
                    $byId = [];
                    foreach ($category as $cat) {
                        $cat = is_object($cat) ? $cat : (object) $cat;
                        $cid = (int) ($cat->cat_id ?? $cat->category_id ?? 0);
                        if ($cid > 0) {
                            $byId[$cid] = $cat;
                        }
                    }
                    foreach ($selectedCategories as $sel) {
                        if (is_object($sel) || is_array($sel)) {
                            $sel = (object) $sel;
                            $cid = (int) ($sel->cat_id ?? $sel->category_id ?? 0);
                            $cname = strtoupper((string) ($sel->category_name ?? ($byId[$cid]->category_name ?? $cid)));
                        } else {
                            $cid = (int) $sel;
                            $cname = strtoupper((string) ($byId[$cid]->category_name ?? $cid));
                        }
                        if ($cid <= 0) {
                            continue;
                        }
                        $selectedIds[] = $cid;
                        $selectedRows[] = ['id' => $cid, 'name' => $cname];
                    }
                    $categoryPool = [];
                    foreach ($byId as $cid => $cat) {
                        $categoryPool[] = [
                            'id' => (int) $cid,
                            'name' => strtoupper((string) ($cat->category_name ?? $cid)),
                        ];
                    }
                    $editCatsLabel = $t('studio_edit_categories', $t('studio_categories_modal_title', ''));
                    ?>
                    <div class="fv-studio-label-row">
                        <span class="fv-studio-label"><?= htmlspecialchars($t('studio_selected_order', $t('categories', '')), ENT_QUOTES, 'UTF-8') ?></span>
                        <button type="button"
                                class="fv-studio-icon-btn fv-studio-cats-edit"
                                id="fvStudioCatsEditBtn"
                                title="<?= htmlspecialchars($editCatsLabel, ENT_QUOTES, 'UTF-8') ?>"
                                aria-label="<?= htmlspecialchars($editCatsLabel, ENT_QUOTES, 'UTF-8') ?>">
                            <i class="mdi mdi-pencil" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="fv-studio-cats-summary" id="fvStudioCatsSummary"
                         data-empty="<?= htmlspecialchars($t('studio_categories_none', ''), ENT_QUOTES, 'UTF-8') ?>">
                        <?php if ($selectedRows === []) : ?>
                            <p class="fv-studio-cats-summary__empty"><?= htmlspecialchars($t('studio_categories_none', ''), ENT_QUOTES, 'UTF-8') ?></p>
                        <?php else : ?>
                            <ol class="fv-studio-cats-summary__list">
                                <?php foreach ($selectedRows as $idx => $row) : ?>
                                    <li data-id="<?= (int) $row['id'] ?>">
                                        <span class="fv-studio-cats-summary__order"><?= (int) ($idx + 1) ?></span>
                                        <span class="fv-studio-cats-summary__name"><?= htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8') ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        <?php endif; ?>
                    </div>
                    <p class="fv-studio-help" id="fvStudioCatsCount">
                        <?= htmlspecialchars(
                            str_replace(
                                '{count}',
                                (string) count($selectedRows),
                                $t('studio_categories_count', '{count}')
                            ),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>
                    <div id="fvStudioCatsHidden">
                        <?php foreach ($selectedRows as $idx => $row) : ?>
                            <input type="hidden" name="categ_selected[<?= (int) $idx ?>]" value="<?= (int) $row['id'] ?>">
                        <?php endforeach; ?>
                    </div>
                    <script type="application/json" id="fvStudioCatsPoolJson"><?= json_encode($categoryPool, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
                    <script type="application/json" id="fvStudioCatsSelectedJson"><?= json_encode(array_column($selectedRows, 'id'), JSON_UNESCAPED_UNICODE) ?></script>
                </section>

                <?php if (!$isDefault) : ?>
                <?php /* Inputs de productos cargados desde orden/Excel (el Select2 de SKUs vive solo en su panel). */ ?>
                <div id="fvStudioExternalProducts" hidden></div>

                <section class="fv-studio-section fv-studio-panel" data-source-panel="skus" hidden>
                    <label class="fv-studio-label" for="fvStudioProducts"><?= htmlspecialchars($t('studio_sku_label', $t('studio_source_skus', '')), ENT_QUOTES, 'UTF-8') ?></label>
                    <select id="fvStudioProducts" class="fv-studio-input js-example" name="products[]" multiple="multiple"
                            data-search-url="<?= htmlspecialchars((string) $productSearchUrl, ENT_QUOTES, 'UTF-8') ?>"
                            data-placeholder="<?= htmlspecialchars($t('studio_sku_placeholder', ''), ENT_QUOTES, 'UTF-8') ?>"
                            data-msg-searching="<?= htmlspecialchars($t('studio_sku_searching', ''), ENT_QUOTES, 'UTF-8') ?>"
                            data-msg-no-results="<?= htmlspecialchars($t('studio_sku_no_results', ''), ENT_QUOTES, 'UTF-8') ?>"
                            data-msg-input-too-short="<?= htmlspecialchars($t('studio_sku_placeholder', ''), ENT_QUOTES, 'UTF-8') ?>">
                        <?php foreach ($selectedProducts as $prod) :
                            $prod = is_object($prod) ? $prod : (object) $prod;
                            $pid = (int) ($prod->pro_id ?? $prod->product_id ?? $prod->id ?? 0);
                            $label = trim((string) (($prod->sku ?? '') . ' | ' . ($prod->name ?? $prod->text ?? '')));
                            if ($pid <= 0) {
                                continue;
                            }
                            ?>
                            <option value="<?= $pid ?>" selected><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="fv-studio-help"><?= htmlspecialchars($t('studio_source_skus_help', ''), ENT_QUOTES, 'UTF-8') ?></p>
                </section>

                <section class="fv-studio-section fv-studio-panel" data-source-panel="order" hidden>
                    <label class="fv-studio-label" for="fvStudioOrder"><?= htmlspecialchars($t('studio_order_label', ''), ENT_QUOTES, 'UTF-8') ?></label>
                    <select id="fvStudioOrder" class="fv-studio-input"
                            data-search-url="<?= htmlspecialchars((string) ($orderSearchUrl ?? company_url('catalog/studioSearchOrders')), ENT_QUOTES, 'UTF-8') ?>"
                            data-placeholder="<?= htmlspecialchars($t('studio_order_placeholder', ''), ENT_QUOTES, 'UTF-8') ?>">
                        <option value=""></option>
                    </select>
                    <button type="button" class="btn btn-outline-secondary btn-sm fv-studio-source-action fv-btn-with-label" id="fvStudioOrderLoad">
                        <i class="mdi mdi-cart-arrow-down" aria-hidden="true"></i>
                        <span class="fv-studio-source-action__label"><?= htmlspecialchars($t('studio_order_load', ''), ENT_QUOTES, 'UTF-8') ?></span>
                    </button>
                    <p class="fv-studio-help" id="fvStudioOrderStatus"><?= htmlspecialchars($t('studio_source_order_help', ''), ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="fv-studio-loaded-products" id="fvStudioOrderProductsBox" hidden>
                        <span class="fv-studio-label"><?= htmlspecialchars($t('studio_loaded_products', ''), ENT_QUOTES, 'UTF-8') ?></span>
                        <ul class="fv-studio-loaded-list" id="fvStudioOrderProductsList"></ul>
                    </div>
                </section>

                <section class="fv-studio-section fv-studio-panel" data-source-panel="excel" hidden>
                    <label class="fv-studio-label" for="fvStudioExcelFile"><?= htmlspecialchars($t('studio_excel_label', ''), ENT_QUOTES, 'UTF-8') ?></label>
                    <a class="btn btn-outline-primary btn-sm fv-studio-source-action fv-btn-with-label"
                       id="fvStudioExcelTemplate"
                       href="<?= htmlspecialchars((string) ($excelTemplateUrl ?? company_url('catalog/studioSkuTemplate')), ENT_QUOTES, 'UTF-8') ?>"
                       download>
                        <i class="mdi mdi-download" aria-hidden="true"></i>
                        <span class="fv-studio-source-action__label"><?= htmlspecialchars($t('studio_excel_template_download', ''), ENT_QUOTES, 'UTF-8') ?></span>
                    </a>
                    <input type="file"
                           id="fvStudioExcelFile"
                           class="fv-studio-input"
                           accept=".xlsx,.xls,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv">
                    <button type="button" class="btn btn-outline-secondary btn-sm fv-studio-source-action fv-btn-with-label" id="fvStudioExcelImport">
                        <i class="mdi mdi-file-excel" aria-hidden="true"></i>
                        <span class="fv-studio-source-action__label"><?= htmlspecialchars($t('studio_excel_import', ''), ENT_QUOTES, 'UTF-8') ?></span>
                    </button>
                    <p class="fv-studio-help" id="fvStudioExcelStatus"><?= htmlspecialchars($t('studio_source_excel_help', ''), ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="fv-studio-loaded-products" id="fvStudioExcelProductsBox" hidden>
                        <span class="fv-studio-label"><?= htmlspecialchars($t('studio_loaded_products', ''), ENT_QUOTES, 'UTF-8') ?></span>
                        <ul class="fv-studio-loaded-list" id="fvStudioExcelProductsList"></ul>
                    </div>
                </section>
                <?php endif; ?>

                <section class="fv-studio-section">
                    <div class="fv-studio-label-row">
                        <label class="fv-studio-label" for="fvStudioMessage"><?= htmlspecialchars($t('studio_featured_message', ''), ENT_QUOTES, 'UTF-8') ?></label>
                        <button type="button" class="fv-studio-ai-help" id="fvStudioSuggestMessage"
                                title="<?= htmlspecialchars($t('studio_featured_ai_title', ''), ENT_QUOTES, 'UTF-8') ?>"
                                aria-label="<?= htmlspecialchars($t('studio_featured_ai_title', ''), ENT_QUOTES, 'UTF-8') ?>">
                            <i class="mdi mdi-help-circle-outline" aria-hidden="true"></i>
                        </button>
                    </div>
                    <textarea id="fvStudioMessage" class="fv-studio-input fv-studio-textarea" name="cabeza_pagina" rows="3"
                              placeholder="<?= htmlspecialchars($t('studio_featured_placeholder', ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($cabeza, ENT_QUOTES, 'UTF-8') ?></textarea>
                    <p class="fv-studio-help"><?= htmlspecialchars($t('studio_featured_help', ''), ENT_QUOTES, 'UTF-8') ?></p>
                </section>

                <section class="fv-studio-section">
                    <label class="fv-studio-check">
                        <input type="checkbox" id="fvStudioShowPrices" name="show_prices" value="1" <?= $showPrices ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars($t('studio_show_prices', ''), ENT_QUOTES, 'UTF-8') ?></span>
                    </label>
                </section>

                <section class="fv-studio-section fv-studio-section--theme">
                    <span class="fv-studio-label"><?= htmlspecialchars($t('studio_theme_colors', ''), ENT_QUOTES, 'UTF-8') ?></span>
                    <p class="fv-studio-help"><?= htmlspecialchars($t('studio_theme_colors_help', ''), ENT_QUOTES, 'UTF-8') ?></p>
                    <?php if ($colorPresets !== []) : ?>
                        <div class="fv-studio-color-presets" id="fvStudioColorPresets" role="list">
                            <?php foreach ($colorPresets as $preset) :
                                $pkey = (string) ($preset['key'] ?? '');
                                $pname = (string) ($preset['name'] ?? $pkey);
                                if ($pkey === '') {
                                    continue;
                                }
                                ?>
                                <button type="button"
                                        class="fv-studio-color-preset"
                                        data-preset="<?= htmlspecialchars($pkey, ENT_QUOTES, 'UTF-8') ?>"
                                        data-banner-bg="<?= htmlspecialchars((string) ($preset['banner_bg'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-banner-text="<?= htmlspecialchars((string) ($preset['banner_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-footer-bg="<?= htmlspecialchars((string) ($preset['footer_bg'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-footer-text="<?= htmlspecialchars((string) ($preset['footer_text'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        data-accent="<?= htmlspecialchars((string) ($preset['accent'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                        title="<?= htmlspecialchars($pname, ENT_QUOTES, 'UTF-8') ?>"
                                        aria-label="<?= htmlspecialchars($pname, ENT_QUOTES, 'UTF-8') ?>">
                                    <span style="background:<?= htmlspecialchars((string) ($preset['banner_bg'] ?? '#111'), ENT_QUOTES, 'UTF-8') ?>"></span>
                                    <span style="background:<?= htmlspecialchars((string) ($preset['accent'] ?? '#c00'), ENT_QUOTES, 'UTF-8') ?>"></span>
                                    <span style="background:<?= htmlspecialchars((string) ($preset['footer_bg'] ?? '#333'), ENT_QUOTES, 'UTF-8') ?>"></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="fv-studio-theme-grid">
                        <label class="fv-studio-theme-field">
                            <span><?= htmlspecialchars($t('studio_theme_banner_bg', ''), ENT_QUOTES, 'UTF-8') ?></span>
                            <input type="color" id="fvThemeBannerBg" name="theme_banner_bg" value="<?= htmlspecialchars((string) ($themeColors['banner_bg'] ?? '#111827'), ENT_QUOTES, 'UTF-8') ?>">
                        </label>
                        <label class="fv-studio-theme-field">
                            <span><?= htmlspecialchars($t('studio_theme_banner_text', ''), ENT_QUOTES, 'UTF-8') ?></span>
                            <input type="color" id="fvThemeBannerText" name="theme_banner_text" value="<?= htmlspecialchars((string) ($themeColors['banner_text'] ?? '#FFFFFF'), ENT_QUOTES, 'UTF-8') ?>">
                        </label>
                        <label class="fv-studio-theme-field">
                            <span><?= htmlspecialchars($t('studio_theme_footer_bg', ''), ENT_QUOTES, 'UTF-8') ?></span>
                            <input type="color" id="fvThemeFooterBg" name="theme_footer_bg" value="<?= htmlspecialchars((string) ($themeColors['footer_bg'] ?? '#111827'), ENT_QUOTES, 'UTF-8') ?>">
                        </label>
                        <label class="fv-studio-theme-field">
                            <span><?= htmlspecialchars($t('studio_theme_footer_text', ''), ENT_QUOTES, 'UTF-8') ?></span>
                            <input type="color" id="fvThemeFooterText" name="theme_footer_text" value="<?= htmlspecialchars((string) ($themeColors['footer_text'] ?? '#E5E7EB'), ENT_QUOTES, 'UTF-8') ?>">
                        </label>
                        <label class="fv-studio-theme-field">
                            <span><?= htmlspecialchars($t('studio_theme_accent', ''), ENT_QUOTES, 'UTF-8') ?></span>
                            <input type="color" id="fvThemeAccent" name="theme_accent" value="<?= htmlspecialchars((string) ($themeColors['accent'] ?? '#B91C1C'), ENT_QUOTES, 'UTF-8') ?>">
                        </label>
                    </div>
                    <script type="application/json" id="fvStudioTemplateDefaultsJson"><?= json_encode(
                        array_reduce($templates, static function ($carry, $tpl) {
                            $key = (string) ($tpl['key'] ?? '');
                            if ($key === '') {
                                return $carry;
                            }
                            $d = is_array($tpl['defaults'] ?? null) ? $tpl['defaults'] : [];
                            $carry[$key] = [
                                'banner_bg' => (string) ($d['banner_bg'] ?? '#111827'),
                                'banner_text' => (string) ($d['banner_text'] ?? '#FFFFFF'),
                                'footer_bg' => (string) ($d['footer_bg'] ?? '#111827'),
                                'footer_text' => (string) ($d['footer_text'] ?? '#E5E7EB'),
                                'accent' => (string) ($d['accent'] ?? '#B91C1C'),
                            ];
                            return $carry;
                        }, []),
                        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
                    ) ?></script>
                </section>

                <section class="fv-studio-section fv-studio-section--templates">
                    <span class="fv-studio-label"><?= htmlspecialchars($t('studio_template', ''), ENT_QUOTES, 'UTF-8') ?></span>
                    <input type="search" id="fvStudioTemplateFilter" class="fv-studio-input" placeholder="<?= htmlspecialchars($t('studio_template_search', ''), ENT_QUOTES, 'UTF-8') ?>">
                    <div class="fv-studio-templates" id="fvStudioTemplates" role="radiogroup">
                        <?php foreach ($templates as $tpl) :
                            $key = (string) ($tpl['key'] ?? '');
                            $name = (string) ($tpl['name'] ?? $key);
                            $desc = (string) ($tpl['description'] ?? '');
                            $layoutHint = (string) ($tpl['layout'] ?? 'grid');
                            $cols = (int) ($tpl['cols'] ?? 3);
                            $defaults = is_array($tpl['defaults'] ?? null) ? $tpl['defaults'] : [];
                            if ($key === '') {
                                continue;
                            }
                            $checked = ($key === $templateKey) ? 'checked' : '';
                            $previewClass = 'fv-studio-template__preview fv-studio-template__preview--' . preg_replace('/[^a-z0-9\-]/', '', strtolower($layoutHint));
                            if ($layoutHint === 'grid' && $cols > 0) {
                                $previewClass .= ' is-cols-' . min(4, max(2, $cols));
                            }
                            $swatch = (string) ($defaults['banner_bg'] ?? '#111827');
                            ?>
                            <label class="fv-studio-template" data-name="<?= htmlspecialchars(strtolower($name . ' ' . $key . ' ' . $desc), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="radio" name="template_key" value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>" <?= $checked ?>>
                                <span class="<?= htmlspecialchars($previewClass, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true" style="--tpl-swatch:<?= htmlspecialchars($swatch, ENT_QUOTES, 'UTF-8') ?>">
                                    <span></span><span></span><span></span><span></span>
                                </span>
                                <span class="fv-studio-template__meta">
                                    <strong><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></strong>
                                    <?php if ($desc !== '') : ?>
                                        <small><?= htmlspecialchars($desc, ENT_QUOTES, 'UTF-8') ?></small>
                                    <?php endif; ?>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </section>
            </form>
        </aside>

        <section class="fv-catalog-studio__preview">
            <div class="fv-catalog-studio__preview-bar">
                <span><?= htmlspecialchars($t('studio_live_preview', ''), ENT_QUOTES, 'UTF-8') ?></span>
                <div class="fv-catalog-studio__preview-actions">
                    <button type="button" class="fv-studio-icon-btn" id="fvStudioExportPdf" title="<?= htmlspecialchars($t('studio_export_pdf', ''), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars($t('studio_export_pdf', ''), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="mdi mdi-file-pdf-box" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
            <iframe id="fvStudioPreviewFrame"
                    class="fv-catalog-studio__frame"
                    title="<?= htmlspecialchars($t('studio_live_preview', ''), ENT_QUOTES, 'UTF-8') ?>"
                    scrolling="yes"></iframe>
        </section>
    </div>
</div>

<div class="modal fade fv-studio-cats-modal" id="fvStudioCatsModal" tabindex="-1" aria-labelledby="fvStudioCatsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="fvStudioCatsModalLabel">
                    <?= htmlspecialchars($t('studio_categories_modal_title', ''), ENT_QUOTES, 'UTF-8') ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= htmlspecialchars($t('cancel', ''), ENT_QUOTES, 'UTF-8') ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="fv-studio-help mb-3">
                    <?= htmlspecialchars($t('studio_source_categories_help', ''), ENT_QUOTES, 'UTF-8') ?>
                </p>
                <div class="fv-studio-dual fv-studio-dual--picker" id="fvStudioCatsDual">
                    <div class="fv-studio-dual__col">
                        <div class="fv-studio-dual__head"><?= htmlspecialchars($t('studio_available', ''), ENT_QUOTES, 'UTF-8') ?></div>
                        <ul id="fvStudioCatsLeft" class="fv-studio-dual__list" data-list="available"></ul>
                    </div>
                    <div class="fv-studio-dual__actions">
                        <button type="button" class="fv-studio-icon-btn" id="fvCatAdd" title="<?= htmlspecialchars($t('add', ''), ENT_QUOTES, 'UTF-8') ?>"><i class="mdi mdi-chevron-right"></i></button>
                        <button type="button" class="fv-studio-icon-btn" id="fvCatRemove" title="<?= htmlspecialchars($t('delete', ''), ENT_QUOTES, 'UTF-8') ?>"><i class="mdi mdi-chevron-left"></i></button>
                        <button type="button" class="fv-studio-icon-btn" id="fvCatUp" title="<?= htmlspecialchars($t('studio_move_up', ''), ENT_QUOTES, 'UTF-8') ?>"><i class="mdi mdi-chevron-up"></i></button>
                        <button type="button" class="fv-studio-icon-btn" id="fvCatDown" title="<?= htmlspecialchars($t('studio_move_down', ''), ENT_QUOTES, 'UTF-8') ?>"><i class="mdi mdi-chevron-down"></i></button>
                    </div>
                    <div class="fv-studio-dual__col">
                        <div class="fv-studio-dual__head"><?= htmlspecialchars($t('studio_selected_order', ''), ENT_QUOTES, 'UTF-8') ?></div>
                        <ul id="fvStudioCatsRight" class="fv-studio-dual__list" data-list="selected"></ul>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn fv-studio-btn fv-studio-btn--ghost" data-dismiss="modal">
                    <?= htmlspecialchars($t('cancel', ''), ENT_QUOTES, 'UTF-8') ?>
                </button>
                <button type="button" class="btn fv-studio-btn fv-studio-btn--primary" id="fvStudioCatsApply">
                    <?= htmlspecialchars($t('studio_categories_modal_apply', $t('aceptar', '')), ENT_QUOTES, 'UTF-8') ?>
                </button>
            </div>
        </div>
    </div>
</div>

<?php /* catalog-studio.js se carga vía addJsFile (después de select2). */ ?>

<?php
/**
 * @var array<string, int> $productListQuality
 * @var array<string, string> $pageTexts
 * @var string|null $activeQualityFilter
 * @var int|null $listCompanyId
 * @var string|null $apiToken
 * @var string|null $apiBaseUrl
 */
$quality = is_array($productListQuality ?? null) ? $productListQuality : [];
$duplicateProducts = (int) ($quality['duplicate_products'] ?? 0);
$zeroPrice = (int) ($quality['zero_price'] ?? 0);
$stockFixed = (int) ($quality['negative_stock_fixed'] ?? 0);
$unitsFixed = (int) ($quality['units_fixed'] ?? 0);
if ($duplicateProducts < 1 && $zeroPrice < 1 && $stockFixed < 1 && $unitsFixed < 1) {
    return;
}

$fill = static function (string $template, int $count): string {
    return str_replace('{count}', (string) $count, $template);
};

$activeQuality = strtolower(trim((string) ($activeQualityFilter ?? '')));
$baseListUrl = company_url('products');
$companyId = (int) ($listCompanyId ?? 0);

$buildUrl = static function (string $qualityKey) use ($baseListUrl): string {
    return $baseListUrl . '?' . http_build_query([
        'quality' => $qualityKey,
        'prdstatus' => '3',
        'sort' => 'modified_on',
        'sort_direction' => 'desc',
    ]);
};

$clearUrl = $baseListUrl . '?' . http_build_query([
    'reset_filters' => '1',
]);

$items = [];
if ($duplicateProducts > 0) {
    $template = (string) ($pageTexts['quality_duplicate_barcodes'] ?? '');
    if ($template !== '') {
        $items[] = [
            'key' => 'duplicate_barcode',
            'label' => $fill($template, $duplicateProducts),
            'modal' => true,
        ];
    }
}
if ($zeroPrice > 0) {
    $template = (string) ($pageTexts['quality_zero_price'] ?? '');
    if ($template !== '') {
        $items[] = [
            'key' => 'zero_price',
            'label' => $fill($template, $zeroPrice),
            'modal' => false,
        ];
    }
}
if ($stockFixed > 0) {
    $template = (string) ($pageTexts['quality_stock_fixed'] ?? '');
    if ($template !== '') {
        $items[] = [
            'key' => 'stock_fixed',
            'label' => $fill($template, $stockFixed),
            'modal' => false,
        ];
    }
}
if ($unitsFixed > 0) {
    $template = (string) ($pageTexts['quality_units_fixed'] ?? '');
    if ($template !== '') {
        $items[] = [
            'key' => 'units_fixed',
            'label' => $fill($template, $unitsFixed),
            'modal' => false,
        ];
    }
}
if ($items === []) {
    return;
}

$title = (string) ($pageTexts['quality_alert_title'] ?? '');
$clearLabel = (string) ($pageTexts['quality_clear_filter'] ?? $pageTexts['columnAll'] ?? '');
$modalTitle = (string) ($pageTexts['quality_duplicate_barcodes_modal_title'] ?? '');
$colSku = (string) ($pageTexts['field_sku'] ?? $pageTexts['sku'] ?? '');
$colName = (string) ($pageTexts['field_name'] ?? $pageTexts['name'] ?? '');
$colBarcode = (string) ($pageTexts['f_barcode'] ?? $pageTexts['barcode'] ?? '');
$colImage = (string) ($pageTexts['field_productimage'] ?? $pageTexts['image'] ?? $pageTexts['foto'] ?? '');
$colActions = (string) ($pageTexts['acciones'] ?? $pageTexts['actions'] ?? '');
$btnSave = (string) ($pageTexts['guardar'] ?? $pageTexts['btn_save'] ?? '');
$btnClose = (string) ($pageTexts['cerrar'] ?? $pageTexts['cancelar'] ?? '');
$btnEdit = (string) ($pageTexts['editar'] ?? '');
$msgSaved = (string) ($pageTexts['quality_barcode_saved'] ?? '');
$msgError = (string) ($pageTexts['error_occurred'] ?? '');
$msgEmpty = (string) ($pageTexts['quality_duplicate_barcodes_empty'] ?? '');
$msgLoading = (string) ($pageTexts['quality_duplicate_barcodes_loading'] ?? '');
$groupLabel = (string) ($pageTexts['quality_duplicate_barcode_group'] ?? '');
$noImageUrl = function_exists('catalog_public_no_image_url')
    ? (string) catalog_public_no_image_url()
    : base_url('images/no-image.png');
?>
<div class="alert alert-warning mb-3 fv-product-quality-alert"
     id="fv-product-quality-alert"
     data-company-id="<?= $companyId ?>"
     data-api-base="<?= htmlspecialchars(rtrim((string) ($apiBaseUrl ?? '/api'), '/'), ENT_QUOTES, 'UTF-8') ?>"
     data-api-token="<?= htmlspecialchars((string) ($apiToken ?? ''), ENT_QUOTES, 'UTF-8') ?>"
     data-edit-url-base="<?= htmlspecialchars(company_url('products/edit/'), ENT_QUOTES, 'UTF-8') ?>"
     data-no-image-url="<?= htmlspecialchars($noImageUrl, ENT_QUOTES, 'UTF-8') ?>"
     data-msg-saved="<?= htmlspecialchars($msgSaved, ENT_QUOTES, 'UTF-8') ?>"
     data-msg-error="<?= htmlspecialchars($msgError, ENT_QUOTES, 'UTF-8') ?>"
     data-msg-empty="<?= htmlspecialchars($msgEmpty, ENT_QUOTES, 'UTF-8') ?>"
     data-msg-loading="<?= htmlspecialchars($msgLoading, ENT_QUOTES, 'UTF-8') ?>"
     data-group-label="<?= htmlspecialchars($groupLabel, ENT_QUOTES, 'UTF-8') ?>"
     data-btn-save="<?= htmlspecialchars($btnSave, ENT_QUOTES, 'UTF-8') ?>"
     data-btn-edit="<?= htmlspecialchars($btnEdit, ENT_QUOTES, 'UTF-8') ?>"
     role="alert">
    <?php if ($title !== '') : ?>
        <strong><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></strong>
    <?php endif; ?>
    <ul class="mb-0<?= $title !== '' ? ' mt-2' : '' ?>">
        <?php foreach ($items as $item) :
            $isActive = ($activeQuality === $item['key']);
            $href = $buildUrl($item['key']);
            ?>
            <li class="<?= $isActive ? 'fv-product-quality-alert__active' : '' ?>">
                <?php if (! empty($item['modal'])) : ?>
                    <a href="#"
                       class="fv-product-quality-alert__link alert-link js-fv-open-duplicate-barcodes"
                       data-toggle="modal"
                       data-target="#fv-duplicate-barcodes-modal">
                        <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php elseif ($isActive) : ?>
                    <span class="font-weight-bold"><?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php else : ?>
                    <a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"
                       class="fv-product-quality-alert__link alert-link">
                        <?= htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <?php if ($activeQuality !== '' && $clearLabel !== '') : ?>
        <div class="mt-2">
            <a href="<?= htmlspecialchars($clearUrl, ENT_QUOTES, 'UTF-8') ?>" class="small alert-link">
                <?= htmlspecialchars($clearLabel, ENT_QUOTES, 'UTF-8') ?>
            </a>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="fv-duplicate-barcodes-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= htmlspecialchars($modalTitle !== '' ? $modalTitle : $title, ENT_QUOTES, 'UTF-8') ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= htmlspecialchars($btnClose, ENT_QUOTES, 'UTF-8') ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="fv-duplicate-barcodes-loading" class="text-muted py-3"><?= htmlspecialchars($msgLoading, ENT_QUOTES, 'UTF-8') ?></div>
                <div id="fv-duplicate-barcodes-empty" class="text-muted py-3 d-none"><?= htmlspecialchars($msgEmpty, ENT_QUOTES, 'UTF-8') ?></div>
                <div id="fv-duplicate-barcodes-groups" class="fv-duplicate-barcodes-groups"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= htmlspecialchars($btnClose, ENT_QUOTES, 'UTF-8') ?></button>
            </div>
        </div>
    </div>
</div>

<template id="fv-duplicate-barcodes-group-tpl">
    <div class="card mb-3 fv-duplicate-barcode-group">
        <div class="card-header d-flex justify-content-between align-items-center py-2">
            <strong class="js-fv-group-title"></strong>
            <span class="badge badge-warning js-fv-group-count"></span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                    <tr>
                        <th style="width:3.5rem"><?= htmlspecialchars($colImage, ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars($colSku, ENT_QUOTES, 'UTF-8') ?></th>
                        <th><?= htmlspecialchars($colName, ENT_QUOTES, 'UTF-8') ?></th>
                        <th style="min-width:14rem"><?= htmlspecialchars($colBarcode, ENT_QUOTES, 'UTF-8') ?></th>
                        <th class="text-right"><?= htmlspecialchars($colActions, ENT_QUOTES, 'UTF-8') ?></th>
                    </tr>
                    </thead>
                    <tbody class="js-fv-group-body"></tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<template id="fv-duplicate-barcodes-row-tpl">
    <tr>
        <td class="fv-duplicate-barcode-thumb-cell">
            <img class="js-fv-row-thumb fv-duplicate-barcode-thumb" alt="" loading="lazy" width="48" height="48">
        </td>
        <td class="js-fv-row-sku text-nowrap"></td>
        <td class="js-fv-row-name"></td>
        <td>
            <input type="text" class="form-control form-control-sm js-fv-row-barcode" autocomplete="off">
        </td>
        <td class="text-right text-nowrap">
            <button type="button" class="btn btn-sm btn-primary js-fv-row-save"></button>
            <a class="btn btn-sm btn-outline-secondary js-fv-row-edit" target="_blank" rel="noopener"></a>
        </td>
    </tr>
</template>

<style>
.fv-product-quality-alert__link {
    text-decoration: underline;
    font-weight: 600;
}
.fv-product-quality-alert__active {
    font-weight: 700;
}
.fv-product-list-product__barcode {
    word-break: break-all;
}
.fv-duplicate-barcodes-groups {
    max-height: 70vh;
    overflow: auto;
}
.fv-duplicate-barcode-thumb-cell {
    width: 3.5rem;
    vertical-align: middle;
}
.fv-duplicate-barcode-thumb {
    width: 48px;
    height: 48px;
    object-fit: cover;
    border-radius: 6px;
    background: #f3f4f6;
    display: block;
}
</style>

<?php
/**
 * @var object $listItem the product item form the list for a given record
 * @var array $filterData The current filter data for each item of this filter form
 * @var mixed $value the field value for the listItem
 * @var array|null $pageTexts
 */
$sku = trim((string) ($listItem->sku ?? ''));
$name = (string) ($value ?? $listItem->name ?? '');
$terms = is_array($filterData['terms'] ?? null) ? $filterData['terms'] : [];
$editUrl = company_url('products/edit/' . $listItem->pro_id);
$pageTexts = is_array($pageTexts ?? null) ? $pageTexts : [];

$highlightedSku = $sku;
foreach ($terms as $term) {
    if ($term === '') {
        continue;
    }
    $highlightedSku = highlight_phrase($highlightedSku, $term);
}

$highlightedName = $name;
foreach ($terms as $term) {
    if ($term === '') {
        continue;
    }
    $highlightedName = highlight_phrase($highlightedName, $term);
}
if (substr_count($highlightedName, '<mark>') < count($terms)) {
    $highlightedName = $name;
}

$groupLabels = fv_product_category_group_labels(
    $listItem->categoryGroups ?? $listItem->category_groups ?? [],
    $listItem->categorySubgroups ?? $listItem->category_subgroups ?? []
);
$taxonomy = fv_product_list_taxonomy_payload(
    $listItem,
    $format_numeric ?? null,
    is_string($format_currency ?? null) ? $format_currency : null
);
$payloadJson = htmlspecialchars(
    json_encode($taxonomy, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT),
    ENT_QUOTES,
    'UTF-8'
);
$groupsEditLabel = (string) ($pageTexts['inline_taxonomy_edit_groups'] ?? ($pageTexts['btn_select_category_groups'] ?? ''));
$groupsNone = (string) ($pageTexts['category_groups_none'] ?? '');
?>

<div class="fv-product-list-product">
    <?php if ($sku !== '') : ?>
        <div class="fv-product-list-product__sku">
            <a href="<?= $editUrl ?>" title="Edit"><strong><?= $highlightedSku ?></strong></a>
            <?php if ($listItem->barcode != null && $listItem->barcode != '') : ?>
                <a title="Barcode: <?= htmlspecialchars((string) $listItem->barcode, ENT_QUOTES, 'UTF-8') ?>">
                    <img src="<?= base_url('images/codbarra.png') ?>"
                         alt="<?= htmlspecialchars((string) $listItem->barcode, ENT_QUOTES, 'UTF-8') ?>"
                         class="img-fluid img-thumbnail"
                         style="width: 20px; height: 20px;">
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <div class="fv-product-list-product__name">
        <a href="<?= $editUrl ?>" title="Edit"><?= $highlightedName ?></a>
    </div>
    <?php
    $rating = function_exists('company_product_rating_value')
        ? company_product_rating_value($listItem)
        : 3.5;
    $ratingAria = (string) ($pageTexts['product_rating_aria'] ?? '');
    if ($ratingAria !== '' && strpos($ratingAria, '%s') !== false) {
        $ratingAria = sprintf($ratingAria, number_format($rating, 1, '.', ''));
    }
    ?>
    <?php if (function_exists('company_product_rating_stars_html')) : ?>
        <div class="fv-product-list-rating">
            <?= company_product_rating_stars_html($rating, $ratingAria) ?>
        </div>
    <?php endif; ?>
    <button type="button"
            class="fv-list-taxonomy-trigger fv-product-list-groups-trigger"
            data-fv-list-taxonomy="<?= $payloadJson ?>"
            data-fv-list-taxonomy-focus="groups"
            title="<?= htmlspecialchars($groupsEditLabel, ENT_QUOTES, 'UTF-8') ?>"
            aria-label="<?= htmlspecialchars($groupsEditLabel, ENT_QUOTES, 'UTF-8') ?>">
        <?php if ($groupLabels !== []) : ?>
            <div class="fv-product-list-groups" title="<?= htmlspecialchars((string) ($pageTexts['f_category_groups'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                <?php foreach ($groupLabels as $label) : ?>
                    <span class="fv-product-list-groups__chip"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                <?php endforeach; ?>
                <i class="mdi mdi-pencil fv-list-taxonomy-edit-hint" aria-hidden="true"></i>
            </div>
        <?php else : ?>
            <span class="fv-list-taxonomy-empty">
                <?= htmlspecialchars($groupsNone !== '' ? $groupsNone : $groupsEditLabel, ENT_QUOTES, 'UTF-8') ?>
                <i class="mdi mdi-pencil fv-list-taxonomy-edit-hint" aria-hidden="true"></i>
            </span>
        <?php endif; ?>
    </button>
</div>

<?php
/**
 * @var object $listItem
 * @var array|null $pageTexts
 */
$pageTexts = is_array($pageTexts ?? null) ? $pageTexts : [];
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
$editLabel = (string) ($pageTexts['inline_taxonomy_edit_category'] ?? ($pageTexts['bnAssignCat'] ?? ''));
$withoutCategory = (string) ($pageTexts['without_category'] ?? '—');
$hasCategories = ($taxonomy['category_ids'] ?? []) !== [];
?>
<button type="button"
        class="fv-list-taxonomy-trigger"
        data-fv-list-taxonomy="<?= $payloadJson ?>"
        data-fv-list-taxonomy-focus="category"
        title="<?= htmlspecialchars($editLabel, ENT_QUOTES, 'UTF-8') ?>"
        aria-label="<?= htmlspecialchars($editLabel, ENT_QUOTES, 'UTF-8') ?>">
    <?php if ($hasCategories) : ?>
        <?php
        $this->load->view('company/product/list/category_avatars', [
            'categories' => $listItem->categories ?? [],
            'layout' => 'inline',
        ]);
        ?>
    <?php else : ?>
        <span class="fv-list-taxonomy-empty"><?= htmlspecialchars($withoutCategory, ENT_QUOTES, 'UTF-8') ?></span>
    <?php endif; ?>
    <i class="mdi mdi-pencil fv-list-taxonomy-edit-hint" aria-hidden="true"></i>
</button>
<input type="hidden" name="categoryName[]"
       value="<?= htmlspecialchars(implode(' | ', $taxonomy['category_names'] ?? []), ENT_QUOTES, 'UTF-8') ?>"
       class="checkBoxcategory<?= (int) ($listItem->pro_id ?? 0) ?>">

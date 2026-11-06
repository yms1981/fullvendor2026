<?php
/**
 * @var object $listItem the product item form the list for a given record
 * @var mixed $value the field value for the listItem
 * @var array|null $pageTexts
 */
$pageTexts = is_array($pageTexts ?? null) ? $pageTexts : [];
$tags = function_exists('fv_product_tags_with_stock_status')
    ? fv_product_tags_with_stock_status($value, $listItem ?? null, $pageTexts)
    : array_values(array_filter(array_map('trim', explode(',', (string) $value))));
?>
<?php foreach ($tags as $tag): ?>
    <?php
    $isStockStatus = function_exists('fv_product_is_stock_status_tag') && fv_product_is_stock_status_tag($tag);
    $stockQty = function_exists('fv_product_stock_quantity')
        ? fv_product_stock_quantity($listItem ?? null)
        : (float) ($listItem->stock ?? 0);
    $labelClass = 'label label-default';
    if ($isStockStatus) {
        $labelClass = $stockQty > 0 ? 'label label-success' : 'label label-danger';
    }
    ?>
    <span class="<?= htmlspecialchars($labelClass, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $tag, ENT_QUOTES, 'UTF-8') ?></span>
<?php endforeach; ?>

<?php
/**
 * @var object $listItem the catalog item from the list
 */
$itemIdField = $itemIdField ?? 'catalog_id';
$catalogId = $listItem->{$itemIdField} ?? '';

$isDefault = isset($listItem->default_catalog) && $listItem->default_catalog == 1;
?>
<?php if ($isDefault): ?>
    <img src="<?= base_url('images/logos/logo.png') ?>"
         class="thumbnail rounded"
         style="display:inline-block;width: 22px;height: 22px;"
         title="Default Catalog">
<?php else: ?>
    <span style="display:inline-block;width: 22px;height: 22px;"></span>
<?php endif; ?>


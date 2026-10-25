<?php
/**
 * @var object $listItem
 * @var string $itemIdField
 * @var array $pageTexts
 */
$itemIdField = $itemIdField ?? 'catalog_id';
$isDefault = isset($listItem->default_catalog) && (int) $listItem->default_catalog === 1;
$title = (string) ($pageTexts['catalogo_default'] ?? 'Default catalog');
?>
<div class="fv-catalog-list-icon">
    <?php if ($isDefault) : ?>
        <img src="<?= base_url('images/logos/logo.png') ?>"
             alt="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>"
             class="fv-catalog-list-icon__img"
             title="<?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?>">
    <?php else : ?>
        <span class="fv-catalog-list-icon__placeholder" aria-hidden="true">
            <i class="mdi mdi-book-open-page-variant"></i>
        </span>
    <?php endif; ?>
</div>

<?php
/**
 * Nombre de catálogo con icono (misma fila, columna única).
 *
 * @var object $listItem
 * @var mixed $value
 * @var string $itemIdField
 * @var array $pageTexts
 */
$itemIdField = $itemIdField ?? 'catalog_id';
$catalogId = (int) ($listItem->{$itemIdField} ?? 0);
$name = trim((string) ($listItem->catalog_name ?? $value ?? ''));
$isDefaultCatalog = isset($listItem->default_catalog) && (int) $listItem->default_catalog === 1;
$editLabel = (string) ($pageTexts['toltip_edit'] ?? $pageTexts['edit'] ?? '');
$defaultTitle = (string) ($pageTexts['catalogo_default'] ?? '');
$defaultBadge = (string) ($pageTexts['default_badge'] ?? $defaultTitle);
$editUrl = company_url('catalog/edit/' . $catalogId);
?>
<div class="fv-catalog-name-row">
    <div class="fv-catalog-list-icon" aria-hidden="true">
        <?php if ($isDefaultCatalog) : ?>
            <img src="<?= base_url('images/logos/logo.png') ?>"
                 alt=""
                 class="fv-catalog-list-icon__img"
                 title="<?= htmlspecialchars($defaultTitle, ENT_QUOTES, 'UTF-8') ?>">
        <?php else : ?>
            <span class="fv-catalog-list-icon__placeholder">
                <i class="mdi mdi-book-open-page-variant"></i>
            </span>
        <?php endif; ?>
    </div>
    <div class="fv-catalog-name-row__text">
        <a href="<?= htmlspecialchars($editUrl, ENT_QUOTES, 'UTF-8') ?>"
           class="fv-crud-cell-link"
           title="<?= htmlspecialchars($editLabel, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>
        </a>
        <?php if ($isDefaultCatalog && $defaultBadge !== '') : ?>
            <span class="fv-catalog-default-badge" title="<?= htmlspecialchars($defaultTitle, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($defaultBadge, ENT_QUOTES, 'UTF-8') ?>
            </span>
        <?php endif; ?>
    </div>
</div>

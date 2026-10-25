<?php
/**
 * Botón para editar catálogo
 * @var object $listItem
 * @var array $itemAction
 * @var string $itemIdField
 */
$catalogId = $listItem->$itemIdField;
?>
<button type="button"
        class="btn btn-success btn-sm"
        title="<?= $itemAction['label'] ?>"
        data-toggle="modal"
        data-target="#modalEditCatalog<?= $catalogId ?>"
        data-catalog-name="<?= htmlspecialchars($listItem->catalog_name ?? '', ENT_QUOTES, 'UTF-8') ?>"
        onclick="console.log('🔘 Botón Editar clickeado - Catalog ID: <?= $catalogId ?>'); if(typeof loadEditCatalogData === 'function') { loadEditCatalogData(<?= $catalogId ?>); } else { console.error('❌ loadEditCatalogData no está definida'); }">
    <i class="<?= $itemAction['iconClass'] ?>"></i>
</button>


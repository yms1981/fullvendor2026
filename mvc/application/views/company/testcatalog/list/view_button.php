<?php
/**
 * Botón de vista previa de catálogo
 * @var object $listItem
 * @var array $itemAction
 * @var string $itemIdField
 */
$itemIdField = $itemIdField ?? 'catalog_id';
$catalogId = $listItem->{$itemIdField} ?? '';
?>
<a class="btn btn-danger btn-sm"
   href="javascript:void(0);"
   title="<?= $itemAction['label'] ?? 'View' ?>"
   data-toggle="modal"
   data-target="#previewModal<?= $catalogId ?>"
   data-bs-toggle="modal"
   data-bs-target="#previewModal<?= $catalogId ?>">
    <i class="<?= $itemAction['iconClass'] ?? 'mdi mdi-eye' ?>"></i>
</a>


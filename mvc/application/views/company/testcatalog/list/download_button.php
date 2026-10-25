<?php
/**
 * Botón de download de catálogo
 * @var object $listItem
 * @var array $itemAction
 * @var string $itemIdField
 */
$itemIdField = $itemIdField ?? 'catalog_id';
$catalogId = $listItem->{$itemIdField} ?? '';
?>
<a class="btn btn-success btn-sm download-buttons"
   href="javascript:void(0);"
   title="<?= $itemAction['label'] ?>"
   data-toggle="modal"
   data-target="#downloadModal_<?= $catalogId ?>"
   data-bs-toggle="modal"
   data-bs-target="#downloadModal_<?= $catalogId ?>">
    <i class="<?= $itemAction['iconClass'] ?>"></i>
    <?= $itemAction['label'] ?>
</a>


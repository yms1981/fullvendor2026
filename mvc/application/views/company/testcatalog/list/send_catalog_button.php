<?php
/**
 * Botón para enviar catálogo
 * @var object $listItem
 * @var array $itemAction
 * @var string $itemIdField
 */
$catalogId = $listItem->$itemIdField;

// En producción se verifica: $v->status == "0" || $v->status == null
// También se verifica si tiene categorías o productos, pero como la API no devuelve estos campos,
// solo verificamos el status por ahora
$isDisabled = ($listItem->status == "0" || $listItem->status == null);
$msg = $isDisabled ? ($langcatalog['no_send_catalog'] ?? 'El catálogo debe estar activo para poder enviarlo') : '';
?>
<a data-msg-error="<?= $msg ?>" 
   class="btn btn-success btn-sm btn-send-catalog <?= $isDisabled ? 'disabled' : '' ?>" 
   href="javascript:void(0);"
   title="<?= $itemAction['label'] ?>"
   data-modal="#sendcatalogModal<?= $catalogId ?>">
    <i class="<?= $itemAction['iconClass'] ?>"></i>
    <?= $itemAction['label'] ?>
</a>

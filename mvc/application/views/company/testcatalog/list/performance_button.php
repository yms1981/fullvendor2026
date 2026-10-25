<?php
/**
 * Botón de performance de catálogo
 * @var object $listItem
 * @var array $itemAction
 * @var string $itemIdField
 */
$itemIdField = $itemIdField ?? 'catalog_id';
$catalogId = $listItem->{$itemIdField} ?? '';
?>
<a class="btn btn-success btn-sm btn-performance"
   href="javascript:void(0);"
   title="<?= $itemAction['label'] ?>"
   data-toggle="modal"
   data-target="#performanceModal<?= $catalogId ?>"
   data-bs-toggle="modal"
   data-bs-target="#performanceModal<?= $catalogId ?>"
   onclick="console.log('🔵 CLICK EN BOTÓN PERFORMANCE - Catalog ID: <?= $catalogId ?>'); console.log('Target modal: #performanceModal<?= $catalogId ?>'); var modal = document.querySelector('#performanceModal<?= $catalogId ?>'); console.log('Modal encontrado en DOM:', modal ? 'SÍ' : 'NO'); if (typeof jQuery !== 'undefined' && jQuery('#performanceModal<?= $catalogId ?>').length > 0) { console.log('Modal existe en jQuery'); jQuery('#performanceModal<?= $catalogId ?>').modal('show'); } else { console.error('Modal NO existe o jQuery no disponible'); } return false;">
    <i class="<?= $itemAction['iconClass'] ?>"></i>
    <?= $itemAction['label'] ?>
</a>



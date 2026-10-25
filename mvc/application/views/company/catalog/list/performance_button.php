<?php
/**
 * Botón de performance de catálogo
 *
 * @var object $listItem
 * @var array $itemAction
 * @var string $itemIdField
 */
$itemIdField = $itemIdField ?? 'catalog_id';
$catalogId = $listItem->{$itemIdField} ?? '';
$rowBtnClass = fv_crud_row_btn_class($itemAction ?? []);
?>
<a class="<?= htmlspecialchars($rowBtnClass, ENT_QUOTES, 'UTF-8') ?> btn-performance"
   href="javascript:void(0);"
   title="<?= htmlspecialchars((string) ($itemAction['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
   data-toggle="modal"
   data-target="#performanceModal<?= (int) $catalogId ?>"
   data-bs-toggle="modal"
   data-bs-target="#performanceModal<?= (int) $catalogId ?>">
    <i class="<?= htmlspecialchars((string) ($itemAction['iconClass'] ?? 'mdi mdi-chart-line'), ENT_QUOTES, 'UTF-8') ?>"></i>
</a>

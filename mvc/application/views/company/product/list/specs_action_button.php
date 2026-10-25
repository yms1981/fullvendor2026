<?php
/**
 * Botón de acción: abrir modal de especificaciones.
 *
 * @var object $listItem
 * @var array $itemAction
 * @var string $itemIdField
 */
$proId = (int) ($listItem->{$itemIdField} ?? $listItem->pro_id ?? 0);
$label = (string) ($itemAction['label'] ?? '');
$rowBtnClass = function_exists('fv_crud_row_btn_class')
    ? fv_crud_row_btn_class($itemAction ?? [])
    : ('btn fv-crud-btn fv-crud-btn--row btn-' . ($itemAction['buttonClass'] ?? 'info') . ' btn-sm');
?>
<button type="button"
        class="<?= htmlspecialchars($rowBtnClass, ENT_QUOTES, 'UTF-8') ?> fv-product-specs-btn"
        title="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>"
        aria-label="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>"
        data-pro-id="<?= $proId ?>"
        data-toggle="modal"
        data-target="#modal_product_specs">
    <i class="<?= htmlspecialchars((string) ($itemAction['iconClass'] ?? 'mdi mdi-clipboard-text-outline'), ENT_QUOTES, 'UTF-8') ?>"
       aria-hidden="true"></i>
</button>

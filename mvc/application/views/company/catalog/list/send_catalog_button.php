<?php
/**
 * Botón para enviar catálogo
 *
 * @var object $listItem
 * @var array $itemAction
 * @var string $itemIdField
 * @var array $langcatalog
 */
$catalogId = $listItem->$itemIdField;
$langcatalog = (isset($langcatalog) && is_array($langcatalog)) ? $langcatalog : [];
$isDisabled = ($listItem->status == '0' || $listItem->status == null);
$msg = $isDisabled
    ? (string) ($langcatalog['catalog_inactive_no_send'] ?? $langcatalog['no_send_catalog'] ?? '')
    : '';
$rowBtnClass = fv_crud_row_btn_class($itemAction ?? [], 'btn-send-catalog' . ($isDisabled ? ' disabled' : ''));
?>
<a data-msg-error="<?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?>"
   class="<?= htmlspecialchars($rowBtnClass, ENT_QUOTES, 'UTF-8') ?>"
   href="javascript:void(0);"
   title="<?= htmlspecialchars((string) ($itemAction['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
   data-modal="#sendcatalogModal<?= (int) $catalogId ?>">
    <i class="<?= htmlspecialchars((string) ($itemAction['iconClass'] ?? 'mdi mdi-send'), ENT_QUOTES, 'UTF-8') ?>"></i>
</a>

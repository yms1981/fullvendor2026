<?php
/**
 * Botón Editar orden en grilla moderna.
 *
 * @var object $listItem
 * @var array $itemAction
 * @var string $itemIdField
 */
$statusOrder = $listItem->status_order ?? $listItem->statusOrder ?? null;
$cannotEdit = is_object($statusOrder) && isset($statusOrder->cannot_edit) && (int) $statusOrder->cannot_edit === 1;
?>
<a href="<?= $itemAction['action'] ?><?= $listItem->$itemIdField ?>"
   class="fv-grid-action fv-grid-action--edit fv-crud-btn fv-crud-btn--row<?= $cannotEdit ? ' disabled' : '' ?>"
   title="<?= htmlspecialchars($itemAction['label'] ?? 'Editar', ENT_QUOTES, 'UTF-8') ?>"
   <?= $cannotEdit ? 'aria-disabled="true" tabindex="-1"' : '' ?>>
    <i class="<?= htmlspecialchars($itemAction['iconClass'] ?? 'mdi mdi-pencil', ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
</a>

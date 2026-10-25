<?php
/**
 * Botón Ver orden en grilla moderna.
 *
 * @var object $listItem
 * @var array $itemAction
 * @var string $itemIdField
 */
?>
<a href="<?= $itemAction['action'] ?><?= $listItem->$itemIdField ?>"
   class="fv-grid-action fv-grid-action--view fv-crud-btn fv-crud-btn--row"
   title="<?= htmlspecialchars($itemAction['label'] ?? 'Ver', ENT_QUOTES, 'UTF-8') ?>">
    <i class="<?= htmlspecialchars($itemAction['iconClass'] ?? 'mdi mdi-eye', ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
</a>

<?php
/**
 * @var object $listItem the group item from the list
 * @var array $itemAction the action configuration
 * @var string $itemIdField the field name that contains the item ID
 */
$btnClass = (strpos($itemAction['buttonClass'] ?? '', 'btn-') === 0) ? $itemAction['buttonClass'] : 'btn-' . ($itemAction['buttonClass'] ?? 'primary');
?>
<button type="button"
        class="btn <?= $btnClass ?> btn-sm fv-crud-btn fv-crud-btn--row go-delete"
        title="<?= $itemAction['label'] ?>"
        data-delete-url="<?= $itemAction['action'] ?><?= $listItem->$itemIdField ?>">
    <i class="<?=$itemAction['iconClass']?>"></i>
</button>

<?php
/**
 * @var object $listItem the profile item from the list
 * @var array $itemAction the action configuration
 * @var string $itemIdField the field name that contains the item ID
 */
?>
<button type="button"
        class="btn btn-<?= $itemAction['buttonClass'] ?? 'primary' ?> btn-sm fv-crud-btn fv-crud-btn--row"
        title="<?= htmlspecialchars((string) ($itemAction['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
        data-toggle="modal"
        data-target="#modal_edit_profile"
        data-profile-id="<?= htmlspecialchars((string) ($listItem->$itemIdField ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    <i class="<?= $itemAction['iconClass'] ?? '' ?>"></i>
</button>

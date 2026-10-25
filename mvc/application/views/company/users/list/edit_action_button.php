<?php
/**
 * Botón editar usuario → modal #modalEditUser.
 *
 * @var object $listItem
 * @var array $itemAction
 * @var string $itemIdField
 */
$userId = (int) ($listItem->{$itemIdField} ?? 0);
$label = (string) ($itemAction['label'] ?? '');
$icon = (string) ($itemAction['iconClass'] ?? 'mdi mdi-pencil');
$rowBtnClass = (strpos($itemAction['buttonClass'] ?? '', 'btn-') === 0)
    ? (string) $itemAction['buttonClass']
    : 'btn-' . (string) ($itemAction['buttonClass'] ?? 'primary');
?>
<button type="button"
        class="btn fv-crud-btn fv-crud-btn--row <?= htmlspecialchars($rowBtnClass, ENT_QUOTES, 'UTF-8') ?> btn-sm js-user-edit-modal"
        title="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>"
        data-toggle="modal"
        data-target="#modalEditUser"
        data-user-id="<?= $userId ?>">
    <i class="<?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>"></i>
</button>

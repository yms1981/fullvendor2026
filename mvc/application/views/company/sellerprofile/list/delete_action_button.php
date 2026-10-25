<?php
/**
 * @var object $listItem the profile item from the list
 * @var array $itemAction the action configuration
 * @var string $itemIdField the field name that contains the item ID
 * @var array|null $pageTexts
 */
$btnClass = (strpos($itemAction['buttonClass'] ?? '', 'btn-') === 0)
    ? $itemAction['buttonClass']
    : 'btn-' . ($itemAction['buttonClass'] ?? 'primary');
$deleteTitle = trim((string) ($pageTexts['delete_profile_seller'] ?? ''));
$profileName = trim((string) ($listItem->profile_name ?? ''));
$confirmTitle = $deleteTitle !== '' && $profileName !== ''
    ? $deleteTitle . ' ' . $profileName . '?'
    : $deleteTitle;
?>
<button type="button"
        class="btn <?= htmlspecialchars($btnClass, ENT_QUOTES, 'UTF-8') ?> btn-sm fv-crud-btn fv-crud-btn--row go-delete"
        title="<?= htmlspecialchars((string) ($itemAction['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
        data-delete-url="<?= htmlspecialchars((string) (($itemAction['action'] ?? '') . ($listItem->$itemIdField ?? '')), ENT_QUOTES, 'UTF-8') ?>"
        <?php if ($confirmTitle !== '') : ?>
        data-delete-title="<?= htmlspecialchars($confirmTitle, ENT_QUOTES, 'UTF-8') ?>"
        <?php endif; ?>>
    <i class="<?= $itemAction['iconClass'] ?? '' ?>"></i>
</button>

<?php
/**
 * @var object $listItem the category item from the list
 * @var array $itemAction the action configuration
 * @var string $itemIdField the field name that contains the item ID
 * @var array $pageTexts
 */
$isDefaultCategory = (int) ($listItem->category_default ?? 0) === 1;
$lockTitle = (string) ($pageTexts['error_edit'] ?? $itemAction['label'] ?? '');
?>
<?php if ($isDefaultCategory): ?>
<span class="btn fv-crud-btn fv-crud-btn--row btn-secondary btn-sm disabled"
      title="<?= htmlspecialchars($lockTitle, ENT_QUOTES, 'UTF-8') ?>"
      aria-disabled="true">
    <i class="mdi mdi-lock"></i>
</span>
<?php else: ?>
<button type="button"
        class="btn btn-<?= htmlspecialchars((string) ($itemAction['buttonClass'] ?? 'primary'), ENT_QUOTES, 'UTF-8') ?> btn-sm"
        title="<?= htmlspecialchars((string) ($itemAction['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
        data-toggle="modal"
        data-target="#modal_edit_category"
        data-category-id="<?= htmlspecialchars((string) ($listItem->$itemIdField ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    <i class="<?= htmlspecialchars((string) ($itemAction['iconClass'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></i>
</button>
<?php endif; ?>

<?php
/**
 * Botón de acción: gestionar grupos/subgrupos de la categoría.
 *
 * @var object $listItem
 * @var array $itemAction
 * @var string $itemIdField
 * @var array $pageTexts
 */
$isDefaultCategory = (int) ($listItem->category_default ?? 0) === 1;
$catId = (int) ($listItem->{$itemIdField} ?? 0);
$label = (string) ($itemAction['label'] ?? ($pageTexts['define_groups'] ?? ''));
$lockTitle = (string) ($pageTexts['error_edit'] ?? $label);
$url = rtrim((string) ($itemAction['action'] ?? ''), '/') . '/' . $catId;
?>
<?php if ($isDefaultCategory || $catId < 1): ?>
<span class="btn fv-crud-btn fv-crud-btn--row btn-secondary btn-sm disabled"
      title="<?= htmlspecialchars($lockTitle, ENT_QUOTES, 'UTF-8') ?>"
      aria-disabled="true">
    <i class="mdi mdi-lock"></i>
</span>
<?php else: ?>
<a href="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>"
   class="btn fv-crud-btn fv-crud-btn--row btn-<?= htmlspecialchars((string) ($itemAction['buttonClass'] ?? 'info'), ENT_QUOTES, 'UTF-8') ?> btn-sm"
   title="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>">
    <i class="<?= htmlspecialchars((string) ($itemAction['iconClass'] ?? 'mdi mdi-folder-multiple'), ENT_QUOTES, 'UTF-8') ?>"></i>
</a>
<?php endif; ?>

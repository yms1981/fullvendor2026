<?php
/**
 * Indicador visual de registro por defecto (solo lectura; el cambio se hace en el modal de edición).
 *
 * @var object $listItem item from the list for a given record
 * @var string|null $itemIdField
 * @var array|null $pageTexts
 */
$itemIdField = $itemIdField ?? 'id';
$pageTexts = is_array($pageTexts ?? null) ? $pageTexts : [];

$itemId = $listItem->{$itemIdField} ?? '';
if ($itemId === '' || $itemId === null) {
    return;
}

$isDefault = !empty($listItem->isDefault) || !empty($listItem->is_default)
    || (isset($listItem->default) && (int) $listItem->default === 1);

if (!$isDefault) {
    return;
}

$label = trim((string) (
    $pageTexts['default_indicator_label']
    ?? ($pageTexts['fields']['isDefault'] ?? '')
    ?? ($pageTexts['default-column'] ?? '')
    ?? ($pageTexts['defecto'] ?? '')
    ?? ($pageTexts['default'] ?? '')
));
?>
<div class="fv-config-crud-default-mark text-center">
    <span class="fv-config-crud-default-mark__badge"
          title="<?= htmlspecialchars($label) ?>"
          aria-label="<?= htmlspecialchars($label) ?>">
        <i class="mdi mdi-check-decagram" aria-hidden="true"></i>
    </span>
</div>

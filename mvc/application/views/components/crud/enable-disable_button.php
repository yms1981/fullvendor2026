<?php
/**
 * @var object|array|null $listItem the list item that has state changing capabilities
 * @var string $itemStatusField the status field name of the list item
 * @var string $itemIdField the identifying field name of the list item
 * @var string $controllerUrl the URL of the crud controller
 * @var array $pageTexts The page standard texts for the entity module listing
 */
$itemStatusField = $itemStatusField ?? 'status';
$itemIdField = $itemIdField ?? 'id';
$controllerUrl = $controllerUrl ?? '';
if (empty($controllerUrl) || !isset($listItem)) {
    return;
}

$readListItemValue = static function ($item, string $field) {
    if (is_array($item)) {
        return $item[$field] ?? null;
    }
    if (is_object($item)) {
        return $item->{$field} ?? null;
    }

    return null;
};

// Para deleted_at: null = enabled, tiene valor = disabled
// Para otros campos como status: 1 = enabled, 0 = disabled
$statusValue = $readListItemValue($listItem, $itemStatusField);
if ($itemStatusField === 'deleted_at') {
    $isEnabled = ($statusValue == null || $statusValue == '');
} else {
    $isEnabled = ((int) $statusValue === 1);
}

$itemId = (int) $readListItemValue($listItem, $itemIdField);
if ($itemId <= 0) {
    return;
}

$itemId = (string) $itemId;
$enableUrl = "{$controllerUrl}/enable/{$itemId}";
$disableUrl = "{$controllerUrl}/disable/{$itemId}";
$isDefaultCategory = (int) $readListItemValue($listItem, 'category_default') === 1;
$switchTitle = $isDefaultCategory
    ? (string) (($pageTexts['error_disabled'] ?? '') ?: '')
    : '';
?>
<style>
.custom-switch-red .custom-control-input:checked ~ .custom-control-label::before {
    background-color: #dc3545;
    border-color: #dc3545;
}
.custom-switch-red .custom-control-input:focus ~ .custom-control-label::before {
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}
.custom-switch-red .custom-control-input:checked:focus ~ .custom-control-label::before {
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}
</style>
<div class="custom-control custom-switch custom-switch-red"<?= $switchTitle !== '' ? ' title="' . htmlspecialchars($switchTitle, ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
    <input type="checkbox"
           class="custom-control-input status-switch"
           id="status-switch-<?= htmlspecialchars($itemId) ?>"
           data-item-id="<?= htmlspecialchars($itemId) ?>"
           data-status-field="<?= htmlspecialchars($itemStatusField) ?>"
           data-enable-url="<?= htmlspecialchars($enableUrl) ?>"
           data-disable-url="<?= htmlspecialchars($disableUrl) ?>"
           <?= $isEnabled ? 'checked' : '' ?>
           <?= $isDefaultCategory ? 'disabled' : '' ?>>
    <label class="custom-control-label" for="status-switch-<?= htmlspecialchars($itemId) ?>"></label>
</div>

<?php
/**
 * @var object $listItem item from the list for a given record
 * @var mixed $value the field value for the listItem
 * @var string|null $fieldName
 * @var string|null $itemIdField
 * @var string|null $controllerUrl
 * @var array|null $pageTexts
 */
$fieldName = trim((string) ($fieldName ?? ''));
$itemIdField = $itemIdField ?? 'profile_id';
$controllerUrl = trim((string) ($controllerUrl ?? ''));
$pageTexts = is_array($pageTexts ?? null) ? $pageTexts : [];

if ($fieldName === '' || $controllerUrl === '') {
    return;
}

$itemId = $listItem->{$itemIdField} ?? '';
if ($itemId === '' || $itemId === null) {
    return;
}

$isTruthy = ($value == '1' || $value === 1 || $value === true);
$toggleUrl = rtrim($controllerUrl, '/') . '/toggleField/' . rawurlencode((string) $itemId) . '/' . rawurlencode($fieldName);
$switchId = 'bool-switch-' . preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $itemId) . '-' . preg_replace('/[^a-zA-Z0-9_-]/', '', $fieldName);
$ariaLabel = trim((string) ($pageTexts['fields'][$fieldName] ?? ($pageTexts[$fieldName] ?? $fieldName)));
?>
<div class="custom-control custom-switch custom-switch-info fv-boolean-field-switch-wrap">
    <input type="checkbox"
           class="custom-control-input fv-boolean-field-switch"
           id="<?= htmlspecialchars($switchId, ENT_QUOTES, 'UTF-8') ?>"
           data-item-id="<?= htmlspecialchars((string) $itemId, ENT_QUOTES, 'UTF-8') ?>"
           data-field-name="<?= htmlspecialchars($fieldName, ENT_QUOTES, 'UTF-8') ?>"
           data-toggle-url="<?= htmlspecialchars($toggleUrl, ENT_QUOTES, 'UTF-8') ?>"
           <?= $isTruthy ? 'checked' : '' ?>
           aria-label="<?= htmlspecialchars($ariaLabel, ENT_QUOTES, 'UTF-8') ?>">
    <label class="custom-control-label" for="<?= htmlspecialchars($switchId, ENT_QUOTES, 'UTF-8') ?>"></label>
</div>

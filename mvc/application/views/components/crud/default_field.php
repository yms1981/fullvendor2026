<?php
/**
 * Indicador / switch de registro por defecto.
 * Si $listItem->setDefaultUrl está definido, muestra un switch exclusivo.
 * Si no, solo marca visual el registro por defecto (solo lectura).
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

$label = trim((string) (
    $pageTexts['default_indicator_label']
    ?? ($pageTexts['fields']['isDefault'] ?? '')
    ?? ($pageTexts['default-column'] ?? '')
    ?? ($pageTexts['defecto'] ?? '')
    ?? ($pageTexts['default'] ?? '')
));

$setDefaultUrl = trim((string) ($listItem->setDefaultUrl ?? ''));
if ($setDefaultUrl === '') {
    if (!$isDefault) {
        return;
    }
    ?>
<div class="fv-config-crud-default-mark text-center">
    <span class="fv-config-crud-default-mark__badge"
          title="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>"
          aria-label="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>">
        <i class="mdi mdi-check-decagram" aria-hidden="true"></i>
    </span>
</div>
    <?php
    return;
}

$switchId = 'fv-default-switch-' . preg_replace('/[^A-Za-z0-9_-]/', '', (string) $itemId);
?>
<style>
.fv-default-switch-wrap .custom-control-input:checked ~ .custom-control-label::before {
    background-color: #dc3545;
    border-color: #dc3545;
}
.fv-default-switch-wrap .custom-control-input:focus ~ .custom-control-label::before {
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}
.fv-default-switch-wrap .custom-control-input:checked:focus ~ .custom-control-label::before {
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}
</style>
<div class="fv-config-crud-default-mark text-center fv-default-switch-wrap">
    <div class="custom-control custom-switch custom-switch-red d-inline-flex justify-content-center"
         title="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>">
        <input type="checkbox"
               class="custom-control-input fv-default-switch"
               id="<?= htmlspecialchars($switchId, ENT_QUOTES, 'UTF-8') ?>"
               data-set-default-url="<?= htmlspecialchars($setDefaultUrl, ENT_QUOTES, 'UTF-8') ?>"
               <?= $isDefault ? 'checked' : '' ?>
               aria-label="<?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>">
        <label class="custom-control-label" for="<?= htmlspecialchars($switchId, ENT_QUOTES, 'UTF-8') ?>"></label>
    </div>
</div>

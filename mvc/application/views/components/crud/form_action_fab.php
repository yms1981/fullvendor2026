<?php
/**
 * Botones flotantes Guardar / Cancelar para formularios CRUD (no modales).
 *
 * @var string $formId ID del formulario a enviar
 * @var string $saveLabel Etiqueta del botón guardar
 * @var string $cancelLabel Etiqueta del botón cancelar
 * @var string|null $cancelUrl URL de cancelación (si no se usa historial)
 * @var bool|null $cancelUseHistory Si true, cancelar vuelve atrás con history.go(-1)
 * @var bool|null $saveDisabled Deshabilita el botón guardar
 * @var string|null $saveButtonId ID opcional del botón guardar
 * @var string|null $saveButtonExtraClass Clases extra para el botón guardar
 * @var string|null $saveButtonType Tipo del botón guardar (submit|button)
 * @var bool|null $saveAsLink Si true, guardar se renderiza como enlace
 * @var string|null $saveUrl URL cuando saveAsLink es true
 * @var string|null $saveIconClass Clase del icono guardar (default fa-floppy-disk)
 */
$formId = trim((string) ($formId ?? ''));
$saveLabel = trim((string) ($saveLabel ?? ''));
$cancelLabel = trim((string) ($cancelLabel ?? ''));
$cancelUrl = trim((string) ($cancelUrl ?? ''));
$cancelUseHistory = !empty($cancelUseHistory);
$saveDisabled = !empty($saveDisabled);
$saveButtonId = trim((string) ($saveButtonId ?? ''));
$saveButtonExtraClass = trim((string) ($saveButtonExtraClass ?? ''));
$saveButtonType = trim((string) ($saveButtonType ?? 'submit'));
$saveAsLink = !empty($saveAsLink);
$saveUrl = trim((string) ($saveUrl ?? ''));
$saveIconClass = trim((string) ($saveIconClass ?? 'fa-solid fa-floppy-disk'));
$hideSaveButton = !empty($hideSaveButton);

$hasCancel = $cancelUseHistory || $cancelUrl !== '';
$hasSave = !$hideSaveButton && ($saveLabel !== '' || ($saveAsLink && $saveUrl !== ''));

if ($formId === '' || (!$hasSave && !$hasCancel)) {
    return;
}

if ($cancelLabel === '' && $saveLabel !== '') {
    $cancelLabel = $saveLabel;
}

if ($saveButtonType !== 'button') {
    $saveButtonType = 'submit';
}
?>
<link rel="stylesheet" href="<?= dated_file_url('companyasset/company/config-crud-form-fab.css') ?>" />
<div class="fv-crud-form-fab" role="toolbar" aria-label="<?= htmlspecialchars($saveLabel !== '' ? $saveLabel : $cancelLabel, ENT_QUOTES, 'UTF-8') ?>">
    <?php if ($cancelUseHistory) : ?>
        <button type="button"
                class="fv-crud-form-fab__btn fv-crud-form-fab__btn--cancel"
                title="<?= htmlspecialchars($cancelLabel, ENT_QUOTES, 'UTF-8') ?>"
                aria-label="<?= htmlspecialchars($cancelLabel, ENT_QUOTES, 'UTF-8') ?>"
                onclick="javascript:history.go(-1);">
            <i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>
        </button>
    <?php else : ?>
        <a href="<?= htmlspecialchars($cancelUrl !== '' ? $cancelUrl : 'javascript:void(0);', ENT_QUOTES, 'UTF-8') ?>"
           class="fv-crud-form-fab__btn fv-crud-form-fab__btn--cancel"
           title="<?= htmlspecialchars($cancelLabel, ENT_QUOTES, 'UTF-8') ?>"
           aria-label="<?= htmlspecialchars($cancelLabel, ENT_QUOTES, 'UTF-8') ?>">
            <i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>
        </a>
    <?php endif; ?>
    <?php if (!$hideSaveButton) : ?>
    <?php if ($saveAsLink && $saveUrl !== '') : ?>
        <a href="<?= htmlspecialchars($saveUrl, ENT_QUOTES, 'UTF-8') ?>"
           class="fv-crud-form-fab__btn fv-crud-form-fab__btn--save<?= $saveButtonExtraClass !== '' ? ' ' . htmlspecialchars($saveButtonExtraClass, ENT_QUOTES, 'UTF-8') : '' ?>"
           <?php if ($saveButtonId !== '') : ?>id="<?= htmlspecialchars($saveButtonId, ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>
           title="<?= htmlspecialchars($saveLabel, ENT_QUOTES, 'UTF-8') ?>"
           aria-label="<?= htmlspecialchars($saveLabel, ENT_QUOTES, 'UTF-8') ?>">
            <i class="<?= htmlspecialchars($saveIconClass, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
        </a>
    <?php else : ?>
    <button type="<?= htmlspecialchars($saveButtonType, ENT_QUOTES, 'UTF-8') ?>"
            form="<?= htmlspecialchars($formId, ENT_QUOTES, 'UTF-8') ?>"
            class="fv-crud-form-fab__btn fv-crud-form-fab__btn--save<?= $saveButtonExtraClass !== '' ? ' ' . htmlspecialchars($saveButtonExtraClass, ENT_QUOTES, 'UTF-8') : '' ?>"
            <?php if ($saveButtonId !== '') : ?>id="<?= htmlspecialchars($saveButtonId, ENT_QUOTES, 'UTF-8') ?>"<?php endif; ?>
            title="<?= htmlspecialchars($saveLabel, ENT_QUOTES, 'UTF-8') ?>"
            aria-label="<?= htmlspecialchars($saveLabel, ENT_QUOTES, 'UTF-8') ?>"<?= $saveDisabled ? ' disabled' : '' ?>>
        <i class="<?= htmlspecialchars($saveIconClass, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
    </button>
    <?php endif; ?>
    <?php endif; ?>
</div>

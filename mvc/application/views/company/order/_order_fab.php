<?php
/**
 * Botones flotantes (FAB) para formulario de orden.
 *
 * @var array $pageTexts
 * @var string $mode add|edit
 * @var bool $planLimitBlocked
 */
$pageTexts = is_array($pageTexts ?? null) ? $pageTexts : [];
$mode = ($mode ?? 'add') === 'edit' ? 'edit' : 'add';
$planLimitBlocked = !empty($planLimitBlocked);
$saveLabel = $pageTexts['guardar'] ?? $pageTexts['save'] ?? ($pageTexts['update'] ?? ($pageTexts['boton-save'] ?? ''));
?>
<div class="fv-order-fab" role="toolbar" aria-label="<?= htmlspecialchars((string) ($pageTexts['action'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    <button type="button"
            class="fv-order-fab__btn fv-order-fab__btn--cancel"
            title="<?= htmlspecialchars((string) ($pageTexts['cancel'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
            aria-label="<?= htmlspecialchars((string) ($pageTexts['cancel'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
            onclick="javascript:history.go(-1);">
        <i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>
    </button>
    <button type="submit"
            form="order_form"
            id="send-form"
            class="fv-order-fab__btn fv-order-fab__btn--save btn-update"
            title="<?= htmlspecialchars((string) $saveLabel, ENT_QUOTES, 'UTF-8') ?>"
            aria-label="<?= htmlspecialchars((string) $saveLabel, ENT_QUOTES, 'UTF-8') ?>"<?= $planLimitBlocked ? ' disabled' : '' ?>>
        <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
    </button>
    <button type="button"
            class="fv-order-fab__btn fv-order-fab__btn--add"
            id="fv_order_fab_add_product"
            data-toggle="modal"
            data-target="#add-new"
            title="<?= htmlspecialchars((string) ($pageTexts['add_product'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
            aria-label="<?= htmlspecialchars((string) ($pageTexts['add_product'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <i class="fa-solid fa-cart-plus" aria-hidden="true"></i>
    </button>
</div>

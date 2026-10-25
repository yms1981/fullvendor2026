<?php
/**
 * Widgets flotantes de acciones del formulario de orden (alta / edición).
 *
 * @var array $pageTexts
 * @var string $mode add|edit
 * @var bool $planLimitBlocked
 * @var int|null $orderId
 */
$pageTexts = is_array($pageTexts ?? null) ? $pageTexts : [];
$mode = ($mode ?? 'add') === 'edit' ? 'edit' : 'add';
$planLimitBlocked = !empty($planLimitBlocked);
$orderId = isset($orderId) ? (int) $orderId : 0;
$canPrint = $mode === 'edit' && $orderId > 0;
$saveLabel = $pageTexts['boton-save']
    ?? $pageTexts['guardar']
    ?? $pageTexts['save']
    ?? $pageTexts['update']
    ?? '';
$printLabel = (string) ($pageTexts['imprimir_orden'] ?? '');
$printRequiresSave = (string) ($pageTexts['order_print_requires_save'] ?? $printLabel);
$cancelLabel = (string) ($pageTexts['cancel'] ?? '');
$addLabel = (string) ($pageTexts['add_product'] ?? '');
?>
<div class="fv-order-fab" role="toolbar" aria-label="<?= htmlspecialchars((string) ($pageTexts['action'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    <button type="button"
            class="fv-order-fab__btn fv-order-fab__btn--cancel"
            title="<?= htmlspecialchars($cancelLabel, ENT_QUOTES, 'UTF-8') ?>"
            aria-label="<?= htmlspecialchars($cancelLabel, ENT_QUOTES, 'UTF-8') ?>"
            onclick="javascript:history.go(-1);">
        <i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>
    </button>

    <button type="button"
            class="fv-order-fab__btn fv-order-fab__btn--print"
            id="fv_order_btn_print"
            title="<?= htmlspecialchars($printLabel, ENT_QUOTES, 'UTF-8') ?>"
            aria-label="<?= htmlspecialchars($printLabel, ENT_QUOTES, 'UTF-8') ?>"
            <?= $canPrint
                ? 'onclick="return printOrder();"'
                : 'onclick="return fvOrderPrintRequiresSave();" data-print-msg="' . htmlspecialchars($printRequiresSave, ENT_QUOTES, 'UTF-8') . '"'
            ?>>
        <i class="fa-solid fa-print" aria-hidden="true"></i>
    </button>

    <button type="button"
            class="fv-order-fab__btn fv-order-fab__btn--add"
            id="fv_order_fab_add_product"
            data-toggle="modal"
            data-target="#add-new"
            title="<?= htmlspecialchars($addLabel, ENT_QUOTES, 'UTF-8') ?>"
            aria-label="<?= htmlspecialchars($addLabel, ENT_QUOTES, 'UTF-8') ?>">
        <i class="fa-solid fa-cart-plus" aria-hidden="true"></i>
    </button>

    <button type="submit"
            form="order_form"
            id="send-form"
            class="fv-order-fab__btn fv-order-fab__btn--save btn-update"
            title="<?= htmlspecialchars((string) $saveLabel, ENT_QUOTES, 'UTF-8') ?>"
            aria-label="<?= htmlspecialchars((string) $saveLabel, ENT_QUOTES, 'UTF-8') ?>"
            <?= $planLimitBlocked ? 'disabled' : '' ?>>
        <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
    </button>
</div>
<script>
function fvOrderPrintRequiresSave() {
    var btn = document.getElementById('fv_order_btn_print');
    var msg = (btn && btn.getAttribute('data-print-msg')) || '';
    if (typeof Swal !== 'undefined' && Swal.fire) {
        Swal.fire({ icon: 'info', text: msg });
    } else if (typeof swal === 'function') {
        swal({ icon: 'info', text: msg });
    } else if (msg) {
        alert(msg);
    }
    return false;
}
</script>

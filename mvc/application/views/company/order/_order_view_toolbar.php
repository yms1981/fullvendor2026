<?php
/**
 * Barra de acciones — vista detalle de orden.
 *
 * @var object $order
 * @var array $pageTexts
 */
$pageTexts = is_array($pageTexts ?? null) ? $pageTexts : [];
$order = $order ?? null;
$orderStatus = $order->status_order ?? $order->statusOrder ?? null;
$cannotEdit = ($orderStatus->cannot_edit ?? 0) == 1;
?>
<div class="fv-order-view-toolbar">
    <div class="fv-order-view-toolbar__group" role="group" aria-label="Acciones sobre la orden">
        <a href="<?= company_url('orders/edit/' . (int) ($order->order_id ?? 0)) ?>"
           class="fv-order-view-btn fv-order-view-btn--primary<?= $cannotEdit ? ' disabled' : '' ?>">
            <i class="fa fa-pencil" aria-hidden="true"></i>
            <span><?= htmlspecialchars((string) ($pageTexts['quick_order_edit'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
        </a>
        <a href="<?= company_url('orders/approve/' . (int) ($order->order_id ?? 0)) ?>"
           class="fv-order-view-btn fv-order-view-btn--primary btn-opciones_detalles">
            <i class="fa fa-check" aria-hidden="true"></i>
            <span><?= htmlspecialchars((string) ($pageTexts['mask_aproved'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
        </a>
        <button type="button" class="fv-order-view-btn fv-order-view-btn--primary" data-toggle="modal" data-target="#modal_duplicate_order">
            <i class="fa fa-files-o" aria-hidden="true"></i>
            <span><?= htmlspecialchars((string) ($pageTexts['duplicate'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
        </button>
        <button type="button" class="fv-order-view-btn fv-order-view-btn--primary"
                data-toggle="modal" data-target="#exampleModalwarehouse"
            <?= !empty($order->warehouse_id) ? ' disabled' : '' ?>>
            <i class="fa fa-paper-plane-o" aria-hidden="true"></i>
            <span><?= htmlspecialchars((string) ($pageTexts['almacen_asigned'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
        </button>
        <button type="button" class="fv-order-view-btn fv-order-view-btn--primary"
                data-toggle="modal" data-target="#modal_changestatus"
            <?= $cannotEdit ? ' disabled' : '' ?>>
            <span><?= htmlspecialchars((string) ($pageTexts['modal_change_status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
        </button>
    </div>
    <div class="fv-order-view-toolbar__group" role="group" aria-label="Acciones externas">
        <a href="javascript:void(0);" onclick="return printOrder();" class="fv-order-view-btn fv-order-view-btn--secondary">
            <i class="fa fa-print" aria-hidden="true"></i>
            <span><?= htmlspecialchars((string) ($pageTexts['imprimir_orden'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
        </a>
        <button type="button" class="fv-order-view-btn fv-order-view-btn--secondary" data-toggle="modal" data-target="#modalSendOrden">
            <i class="fa fa-paper-plane-o" aria-hidden="true"></i>
            <span><?= htmlspecialchars((string) ($pageTexts['enviar_orden'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
        </button>
        <button type="button" class="fv-order-view-btn fv-order-view-btn--secondary" data-toggle="modal" data-target="#modal_export">
            <i class="fa-solid fa-file-excel" aria-hidden="true"></i>
            <span><?= htmlspecialchars((string) ($pageTexts['order_details_export'] ?? $pageTexts['download_csv'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
        </button>
    </div>
    <div class="fv-order-view-toolbar__group" role="group" aria-label="Volver">
        <a class="fv-order-view-btn fv-order-view-btn--danger" href="javascript:void(0);" onclick="javascript:history.go(-1);">
            <i class="fa fa-arrow-left" aria-hidden="true"></i>
            <span><?= htmlspecialchars((string) ($pageTexts['atras'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
        </a>
    </div>
</div>

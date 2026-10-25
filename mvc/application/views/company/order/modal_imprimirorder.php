<?php
/**
 * @var $order object the order data
 * @var $pageTexts array the translated string conglomerated for this view
 */

$notePart = [];

if (empty($parts) && !empty($order->customer->dispatch_addresses)) {

    $d = $order->customer->dispatch_addresses[0];

    $base = trim(($d->street ?? '') . ' ' . ($d->street_number ?? ''));
    if ($base !== '') {
        $notePart[] = $pageTexts['address_catalog'] . ' ' . $base;
    }

    if (!empty($d->zone)) {
        $notePart[] = $pageTexts['zone'] . ': ' . $d->zone;
    }

    if (!empty($d->zipcode)) {
        $notePart[] = $pageTexts['zipcode_catalog'] . ' ' . $d->zipcode;
    }

    if (!empty($shippingNote)) {
        $notePart[] = $pageTexts['shipping_catalog'] . ' ' . $shippingNote;
    }
}

?>
<!-- MODAL IMPRIMIR ORDEN -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered fv-order-print-modal__dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel"><?= htmlspecialchars((string) ($pageTexts['imprimir_orden'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h5>
                <button type="button"
                        class="close fv-modal-close-x"
                        data-dismiss="modal"
                        aria-label="<?= htmlspecialchars((string) ($pageTexts['cerrar'] ?? $pageTexts['close'] ?? $pageTexts['cancel'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <i class="fa fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="form-imprimir_orden" target="_blank" action="<?= company_url('orders/generatePdf')?>"
                    method="post">
                    <div class="form-group mb-0">
                        <label class="fs-13"><?= htmlspecialchars((string) ($pageTexts['nota_entrega'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
                        <textarea name="delivery_notes" rows="5" class="form-control"><?= !empty($parts)
                            ? htmlspecialchars(implode(', ', $parts))
                            : (!empty($notePart)
                                ? htmlspecialchars(implode(', ', $notePart))
                                : ''
                            )
                            ?></textarea>
                        <input type="hidden" value="<?=@$order->order_id?>" class="form-control" name="order_id">
                        <input id="notas" name="notas" type="text" hidden>
                        <label class="mb-0 mt-3 d-inline-flex align-items-center" style="cursor: pointer; color:#007bff;"
                            for="include_locations"><input id="include_locations" type="checkbox" value="1"
                                name="include_locations" class="mr-2">&nbsp;<?= htmlspecialchars((string) ($pageTexts['incluir_ubication_products'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
                    </div>
                </form>
            </div>
            <div class="modal-footer fv-order-print-modal__footer">
                <button id="btn-con" type="button" class="btn btn-danger fv-order-print-modal__btn">
                    <i class="fa fa-print" aria-hidden="true"></i>
                    <?= htmlspecialchars((string) ($pageTexts['imprinir_connotas'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </button>
                <button id="btn-sin" type="button" class="btn btn-danger fv-order-print-modal__btn">
                    <i class="fa fa-print" aria-hidden="true"></i>
                    <?= htmlspecialchars((string) ($pageTexts['imprimir_sinnotas'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </button>
                <button type="button" class="btn btn-danger fv-order-print-modal__btn fv-order-print-modal__btn--close" data-dismiss="modal">
                    <i class="fa fa-times" aria-hidden="true"></i>
                    <?= htmlspecialchars((string) ($pageTexts['cerrar'] ?? $pageTexts['close'] ?? $pageTexts['cancel'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </button>
            </div>
        </div>
    </div>
</div>

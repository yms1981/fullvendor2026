<?php
$lang_order = $lang_order ?? [];
?>
<style>
#modal-histoy .fv-sold-modal {
    max-width: 760px;
    width: calc(100vw - 2rem);
}
#modal-histoy .product-name-vendido {
    font-size: 14px;
    font-weight: 600;
    line-height: 1.3;
    margin: 2px 0 0;
}
#modal-histoy .modal-body {
    padding: 0 !important;
    overflow-x: auto;
}
#modal-histoy .fv-sold-table {
    width: 100%;
    margin: 0;
}
#modal-histoy .fv-sold-table th,
#modal-histoy .fv-sold-table td {
    white-space: nowrap;
    vertical-align: middle;
    border-top: 0;
}
#modal-histoy .fv-sold-table thead th {
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .03em;
    text-transform: uppercase;
    background: #f8fafc;
    border-bottom: 1px solid #e8edf3;
    padding: 12px 16px;
}
#modal-histoy .fv-sold-table tbody td {
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
}
#modal-histoy .fv-sold-status {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
#modal-histoy .fv-sold-table .btn {
    line-height: 1;
}
</style>
<div class="modal fade" id="modal-histoy" tabindex="-1" role="dialog" aria-labelledby="fvSoldModalTitle" aria-hidden="true">
    <div class="modal-dialog fv-sold-modal" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="fvSoldModalTitle"><?= htmlspecialchars((string) ($lang_order['product_sold'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h5>
                    <p class="product-name-vendido mb-0"></p>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= htmlspecialchars((string) ($lang_order['cerrar'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table class="table fv-sold-table">
                    <thead>
                        <tr>
                            <th scope="col"><?= htmlspecialchars((string) ($lang_order['fecha_creacion_order'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                            <th scope="col"><?= htmlspecialchars((string) (($lang_order['modal-order'] ?? '') . ' Id'), ENT_QUOTES, 'UTF-8') ?></th>
                            <th scope="col"><?= htmlspecialchars((string) ($lang_order['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                            <th scope="col" class="text-right"><?= htmlspecialchars((string) ($lang_order['sale_price'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" data-dismiss="modal" class="btn btn-danger"><?= htmlspecialchars((string) ($lang_order['cerrar'] ?? ''), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
        </div>
    </div>
</div>

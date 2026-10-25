<?php
/**
 * @var $order object the order data
 * @var $warehouse_assign_users array users rol warehouse (user_id, name, email) — el user_id se envía como warehouse_id
 * @var $pageTexts array the translated string conglomerated for this view
 */
?>
<!-- modal deposito -->
<div class="modal fade" id="exampleModalwarehouse" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header pt-3 pb-3">
                <h5 class="modal-title"><?= htmlspecialchars($pageTexts['asignar_orden_deposito'] ?? '') ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="submit_order_asigned" action="<?= company_url('orders/assignWarehouse')?>" method="post">
                    <div class="mb-3">
                        <label class="fs-13"><?= htmlspecialchars($pageTexts['warehouse_user'] ?? '') ?></label>
                        <select name="warehouse_id" class="form-control notranslate" required>
                            <option value=""><?= htmlspecialchars($pageTexts['seleccionar_por'] ?? '') ?></option>
                            <?php foreach ($warehouse_assign_users ?? [] as $wu):
                                $uid = is_object($wu) ? ($wu->user_id ?? '') : ($wu['user_id'] ?? '');
                                $label = is_object($wu) ? trim((string)($wu->name ?? '')) : trim((string)($wu['name'] ?? ''));
                                if ($label === '') {
                                    $label = is_object($wu) ? trim((string)($wu->email ?? '')) : trim((string)($wu['email'] ?? ''));
                                }
                                if ($label === '') {
                                    $label = (string) $uid;
                                }
                            ?>
                            <option value="<?= htmlspecialchars((string) $uid) ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach ?>
                        </select>
                        <input type="hidden" value="<?= htmlspecialchars((string) (@$order->order_id ?? '')) ?>" name="order_id">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button form="submit_order_asigned" type="submit"
                    class="btn btn-success"><?= htmlspecialchars($pageTexts['asignar'] ?? '') ?></button>
            </div>
        </div>
    </div>
</div>

<?php
/**
 * @var Object $order
 * @var $orderStatuses
 * @var array $pageTexts
 */
?>
<!-- Modal -->
<div class="modal fade" id="modal_changestatus" tabindex="-1" aria-labelledby="modal_changestatusLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel"><?= $pageTexts['modal_change_status'] ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="changeStatus" class="form_send" action="<?= company_url('orders/changeStatus') ?>" method="POST">
                <div class="modal-body">
                    <?php $currentOrderStatusId = $order->statusOrder->id_status ?? $order->status_order->id_status ?? $order->status_id ?? null; $currentCodStatus = (int)($order->order_status ?? $order->statusOrder->cod_status ?? $order->status_order->cod_status ?? 0); ?>
                    <input type="hidden" name="order_id" value="<?= $order->order_id ?>">

                    <select class="form-control selectpicker" name="cod_status" data-show-content="true">
                        <?php foreach($orderStatuses as $status): ?>
                        <?php
                        $codStatus = (int)($status->cod_status ?? 0);
                        $isSelected = ($currentOrderStatusId && (int)$currentOrderStatusId === (int)($status->id_status ?? 0)) || ($currentCodStatus && $currentCodStatus === $codStatus);
                        $label = $status->name_status_english ?? $status->name_status_spanish ?? $status->name_status ?? (string)$codStatus;
                        $color = !empty($status->color) ? htmlspecialchars($status->color) : '#ccc';
                        $content = "<span class='d-inline-block rounded-circle me-2' style='width:10px;height:10px;background-color:" . $color . ";vertical-align:middle'></span>" . htmlspecialchars($label);
                        $contentAttr = str_replace('"', '&quot;', $content);
                        ?>
                        <option value="<?= $codStatus ?>" <?= ($status->disabled == 1) ? 'disabled' : '' ?>
                            <?= $isSelected ? 'selected' : '' ?> data-content="<?= $contentAttr ?>">
                            <?= htmlspecialchars($label) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success"><?= $pageTexts['agregar_add'] ?></button>
                    <button type="button" data-dismiss="modal" aria-label="Close"
                        class="btn btn-warning"><?= $pageTexts['cerrar'] ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).on('submit', '#changeStatus', function (e) {
        e.preventDefault()

        const form = document.getElementById('changeStatus');
        const url = form.getAttribute('action');
        const formData = new FormData(form);

        $.ajax({
            url: url,
            type: 'post',
            dataType: 'json',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                let title = response.message ?? 'Status changed successfully';
                swal({
                    title: title,
                    icon: 'success'
                }).then(() => {
                    location.reload();
                });
            },
            error: function(response) {
                let title = response.responseJSON?.message ?? 'Error';
                const errors = response.responseJSON.errors;

                const errorsMessages = Object.values(errors || {})
                    .reduce((acc, curr) => acc.concat(curr), [])
                    .join('\n') || null;

                swal({
                    title: title,
                    text: errorsMessages,
                    icon: 'error'
                });
            }
        });
    });
</script>

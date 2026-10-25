<div class="modal fade" id="modal-notify" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><?= $lang_order['send_orden'] ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="form_send" action="<?= company_url('cart/notify/'. $cart_info->cart_id) ?>" method="post">
                    <div class="form-group">
                        <label for="">Email</label>
                        <input type="email" name="email" class="form-control" required value="<?= $cart_info->email_customer ?>">
                    </div>
                    <?php if (! empty($cart_expired)): ?>
                        <input type="text" hidden name="confirm" class="form-control" value="TRUE">
                    <?php endif;?>
                </form>
            </div>
                <div class="modal-footer">
                <button id="btn-send-email" form="form_send" type="submit" class="btn btn-success"
                    data-enviando="<?= $lang_order['order_sending'] ?>">
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    <?= $lang_order['send'] ?>
                </button>
                <button type="button" data-dismiss="modal" aria-label="Close"
                    class="btn btn-danger"><?= $lang_order['cerrar'] ?></button>
            </div>
        </div>
    </div>
</div>
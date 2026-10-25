<?php
/**
 * @var $order object the order data
 * @var $pageTexts array the translated string conglomerated for this view
 */
?>
<!-- MODAL ENVIAR ORDEN -->
<div class="modal fade" id="modalSendOrden" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header pt-3 pb-3">
                <h5 class="modal-title"><?= $pageTexts['send_orden'] ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="form_enviar_orden" action="<?= company_url('orders/sendOrder')?>" method="post">
                    <div class="form-group">
                        <label for="send-order-email"><?= htmlspecialchars($pageTexts['email'] ?? '') ?></label>
                        <input id="send-order-email" type="email" name="email" class="form-control" value="<?= $order->customer->email?>" required>
                        <input type="text" name="order_id" value="<?= $order->order_id ?>" hidden>
                    </div>
                    <div class="form-group">
                        <?php
                            $cliente = $order->customer->name. '('.$order->customer->business_name .')';
                            $fecha = fv_format_display_date($order->created ?? null);
                            $description_default = sprintf($pageTexts['description_defecto_order'], $cliente,$fecha);
                            $companyOrderSendTime = $order->company->ordersend_time;
                        ?>

                        <label for=""><?= $pageTexts['descripcion_order']?></label>
                        <textarea id="description_default" name="descriptions" class="form-control mb-2" cols="30"
                            rows="5"><?= $description_default ?></textarea>
                        <br>
                        <!-- expiration -->
                        <label for=""><?= $pageTexts['time_order_sendmail'] ?? 'Tiempo (días)' ?></label>
                        <!-- <input type="number"
                            value="<?= ($order->expiration_days != null)?$order->expiration_days:$companyOrderSendTime ?>"
                            name="expiration_days" class="form-control text-center"> -->

                        <!-- <input type="number"
                            value="<?= ($order->expiration_days != null)?$order->expiration_days:$companyOrderSendTime ?>"
                            name="expiration_days" class="form-control text-center"> -->
                        <input type="number"
                            value="<?= $companyOrderSendTime ?? '15' ?>"
                            name="expiration_days" class="form-control text-center">
                        <br>
                        <!--  -->
                        <label style="cursor: pointer; padding: 0; margin: 0;color:#007bff;"
                            for="show_information_customer">
                            <input id="show_information_customer" type="checkbox" value="1"
                                name="show_information">&nbsp;<?= $pageTexts['information_customer']?></label>
                        <br>
                        <label class="mb-2" style="cursor: pointer; padding: 0; margin: 0;color:#007bff;"
                            for="hide_price"><input id="hide_price" type="checkbox" value="1"
                                name="hide_price">&nbsp;<?= $pageTexts['hide_price']?></label>
                    </div>
                </form>

            </div>
            <div class="modal-footer">
                <button id="btn-send-email" form="form_enviar_orden" type="submit" class="btn btn-success"
                    data-enviando="<?= $pageTexts['order_sending'] ?>"><?= $pageTexts['send'] ?></button>
                <button type="button" data-dismiss="modal" aria-label="Close"
                    class="btn btn-danger"><?= $pageTexts['cerrar'] ?></button>
            </div>
        </div>
    </div>
</div>

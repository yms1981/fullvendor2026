<div class="container-fluid page-body-wrapper pt-3">
    <div class="main-panel">
        <div class="container-fluid">
            <div class="page-header">
                <h3 class="page-title"> <?= @$title ?></h3>
            </div>
            <div class="card">
                <div class="card-body">
                    <form id="customerAdd" class="forms-sample"
                        action="<?= company_url('userprofile/update') ?><?= ($this->input->get('redirectto'))? '?redirectto='.$this->input->get('redirectto') : ''; ?>"
                        method="post" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $lang_userprofile['nombre_perfil'] ?>: <span class="color-red">*</span></label>
                                    <input type="text" class="form-control" name="profile_name"
                                        value="<?=@$data->profile_name?>" required="">
                                    <input type="hidden" name="profile_id" value="<?=@$data->profile_id?>">
                                    <span class="error"><?php echo form_error('profile_name'); ?></span>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $lang_userprofile['neto_pedido'] ?>: </label>
                                    <select class="form-control notranslate" name="order_discount">
                                        <option value="1" <?= @$data->order_discount=="1"? "selected" : "" ?>><?= $lang_vendor['si'] ?></option>
                                        <option value="0" <?= @$data->order_discount=="0"? "selected" : "" ?>>No</option>
                                    </select>
                                </div>
                                <?php if(@$data->order_disc_price !=""){ ?>
                                <div class="form-group" id="order_discount_prc">
                                    <label><?= $lang_vendor['allowed_discount'] ?>: </label>
                                    <input type="text" name="order_disc_price" value="<?= $data->order_disc_price ?>"
                                        class="form-control">
                                </div>
                                <?php } ?>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $lang_userprofile['neto_pedido'] ?>: </label>
                                    <select class="form-control notranslate" name="order_net_discount">
                                        <option value="1"<?= @$data->order_net_discount=="1" ?"selected":"" ?>><?= $lang_vendor['si'] ?></option>
                                        <option value="0"<?= @$data->order_net_discount=="0" ?"selected":"" ?>>No</option>
                                    </select>
                                </div>
                                <?php if(@$data->order_net_disc_price !=""){ ?>
                                <div class="form-group" id="order_netdiscount_prc">
                                    <label><?= $lang_vendor['allowed_discount_neto'] ?>: </label>
                                    <input type="text" name="order_net_disc_price"
                                        value="<?= $data->order_net_disc_price ?>" class="form-control">
                                </div>
                                <?php } ?>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $lang_userprofile['cambiar_precio'] ?>: </label>
                                    <select class="form-control notranslate" name="can_change_price">
                                        <option value="1"<?= @$data->can_change_price=="1"?"selected":"" ?>><?= $lang_vendor['si'] ?></option>
                                        <option value="0"<?= @$data->can_change_price=="0"?"selected":"" ?>>No</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $lang_userprofile['enviar_catalogo'] ?>: </label>
                                    <select class="form-control notranslate" name="can_send_catalog">
                                        <option value="1"<?= @$data->can_send_catalog=="1"?"selected":"" ?>><?= $lang_vendor['si'] ?></option>
                                        <option value="0"<?= @$data->can_send_catalog=="0"?"selected":"" ?>>No</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $lang_userprofile['crear_cliente_movil'] ?>: </label>
                                    <select class="form-control notranslate" name="can_create_customer">
                                        <option value="1"<?= @$data->can_create_customer=="1"?"selected":"" ?>><?= $lang_vendor['si'] ?>
                                        </option>
                                        <option value="0"<?= @$data->can_create_customer=="0"?"selected":"" ?>>No
                                        </option>
                                    </select>
                                </div>
                            </div>

                        </div>
                        <div class="form-group text-right">
                            <button type="submit" class="btn btn-success mr-2"><?= $lang_vendor['guardar'] ?? $lang_vendor['save'] ?? $lang_vendor['update'] ?? '' ?></button>
                            <a class="btn btn-danger mr-2" href="javascript:void(0);"
                                onclick="javascript:history.go(-1);">
                                <?= $lang_userprofile['atras'] ?>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
function getOrderStatus(ordnewStatus) {
    if (ordnewStatus == 1) {
        $("#order_discount_prc").css("display", "block");
    } else {
        $("#order_discount_prc").css("display", "none");
    }

}

function getOrdernetStatus(ordnetdscStatus) {
    if (ordnetdscStatus == 1) {
        $("#order_netdiscount_prc").css("display", "block");
    } else {
        $("#order_netdiscount_prc").css("display", "none");
    }
}
</script>
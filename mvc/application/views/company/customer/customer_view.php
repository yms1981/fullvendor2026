<form id="customerAdd"
    action="<?= company_url('customer/update') ?><?= ($this->input->get('redirectto'))? '?redirectto='.$this->input->get('redirectto') : ''; ?>"
    method="post" enctype="multipart/form-data">
    <div class="container-fluid page-body-wrapper pt-3">
        <div class="main-panel">
            <div class="container-fluid">
                <div class="col-12">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="page-header">
                                <h3 class="page-title"> <?= strtoupper($langcliente['titulo_vista']) ?></h3>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group text-right">
                                <a href="javascript:void(0)" onclick="openurl('<?= addslashes(company_url('customers/edit/'.@$data->customer_id))?>'); return false;"
                                    class="btn btn-success mr-2"><?= $langcliente['editar'] ?></a>
                                <a class="btn btn-danger mr-2" href="javascript:void(0);"
                                    onclick="javascript:history.go(-1);">
                                    <?= $langcliente['cancelar'] ?>
                                </a>
                            </div>
                        </div>
                    </div>


                </div>
                <div class="col-12">
                    <div class="row pr-2">
                        <div class="col-8">
                            <label><strong><?= strtoupper($langcliente['dato_empresa']) ?></strong></label>
                        </div>
                        <div class="col-2 text-right">
                            <div class="form-group d-flex">
                                <label class="pt-1 text-nowrap">ID:&nbsp;&nbsp;&nbsp;</label>
                                <input class="form-control" tabindex="0" type="text" class="form-control" disabled
                                    value="<?=@$data->customer_id ?>">
                            </div>
                        </div>
                        <div class="col-2 text-right">
                            <div class="form-group d-flex">
                                <label class="pt-1 text-nowrap">ID ERP:&nbsp;&nbsp;&nbsp;</label>
                                <input class="form-control" tabindex="0" type="text" class="form-control" disabled
                                    value="<?=@$data->cust_id_kor ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $langcliente['nombre'] ?>: </label>
                                    <input tabindex="0" type="text" class="form-control" disabled name="name"
                                        value="<?=@$data->name?>">
                                    <input type="hidden" name="customerId" value="<?=@$data->customer_id?>">
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $langcliente['nombre_empresa'] ?>: <span
                                            class="color-red">*</span></label>
                                    <input tabindex="0" type="text" class="form-control" disabled name="business_name"
                                        value="<?=@$data->business_name?>" required="">
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label>Tax ID: </label>
                                            <input tabindex="0" type="text" class="form-control" disabled name="tax_id"
                                                value="<?=@$data->tax_id?>">
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label><?= $langcliente['status_enable'] ?>: </label>
                                            <select disabled name="customer_status" class="form-control">
                                                <option selected disabled><?= $langcliente['seleccionar'] ?></option>
                                                <option value="1"
                                                    <?= ($data->customer_status == 1)? 'selected' : ''; ?>>
                                                    <?= $langcliente['si'] ?></option>
                                                <option value="0"
                                                    <?= ($data->customer_status == 0)? 'selected' : ''; ?>>No</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label>% <?= $langcliente['descuento'] ?>: </label>
                                    <input tabindex="0" type="number" class="form-control" disabled name="discount"
                                        min="0" max="100" value="<?=@$data->discount?>">
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $langcliente['termino_pago'] ?>: </label>
                                    <select tabindex="0" class="form-control notranslate" disabled name="term_id">
                                        <?php foreach ($termofsales as $tsale) { ?>
                                        <option value="<?=$tsale->term_id?>"
                                            <?php if(@$data->term_id==$tsale->term_id) { echo "selected";} ?>>
                                            <?=$tsale->name?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $langcliente['group'] ?>: </label>

                                    <select class="form-control notranslate" disabled name="group_id">
                                        <option selected disabled><?= $langcliente['seleccionar'] ?></option>
                                        <?php foreach ($groups as $cgroups) { ?>
                                        <option value="<?=$cgroups->group_id?>"
                                            <?php if(@$data->group_id==$cgroups->group_id) { echo "selected";} ?>>
                                            <?=$cgroups->name?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $langcliente['email'] ?>: <span class="color-red">*</span></label>
                                    <input type="email" class="form-control" disabled readonly=""
                                        value="<?=@$data->email?>">
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label>Email: <span class="color-red"
                                            id="demail"><?= $langcliente['cambio_email'] ?></span></label>
                                    <input type="email" class="form-control" disabled onblur="getDuplicateEmail();"
                                        name="newemail" id="email">
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $langcliente['seller_asigned'] ?>: </label>
                                    <?php   $users = explode(",", $data->user_id);  ?>
                                    <select class="form-control" disabled name="user_id[]" id="seller_asigned"
                                        multiple="multiple" required="" placeholder="Enter">
                                        <?php foreach ($sellers as $seller => $vc) { ?>

                                        <?php
                      if(in_array($vc->user_id, $users)) {

                        $str_flag = "selected";
                      }
                      else { $str_flag="n"; };
                      ?>
                                        <option value="<?= $vc->user_id ?>" <?php echo $str_flag; ?>>
                                            <?= $vc->first_name . ' ' . $vc->last_name ?>
                                        </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $langcliente['cell_phone'] ?>: </label>
                                    <input type="text" class="form-control" disabled name="cell_phone"
                                        value="<?=@$data->cell_phone?>">
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $langcliente['phone'] ?>: </label>
                                    <input type="tel" class="form-control" disabled name="phone"
                                        value="<?=@$data->phone?>" autocomplete="off">
                                </div>

                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $langcliente['catalogue_emails'] ?>: </label>
                                    <select class="form-control notranslate" disabled name="catalog_emails">
                                        <option value="1" <?php if(@$data->catalog_emails=='1') { echo "selected";} ?>>
                                            Yes</option>
                                        <option value="0" <?php if(@$data->catalog_emails=='0') { echo "selected";} ?>>
                                            No</option>
                                    </select>
                                </div>

                            </div>


                        </div>

                    </div>

                </div>
                <br>
                <label><strong><?= strtoupper($langcliente['dato_comercial']) ?></strong></label>
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $langcliente['commercial_address'] ?>: </label>
                                    <input type="text" class="form-control" disabled name="commercial_address"
                                        value="<?=@$data->commercial_address?>">
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $langcliente['commercial_delivery_address'] ?>: </label>
                                    <input type="text" class="form-control" disabled name="commercial_delivery_address"
                                        value="<?=@$data->commercial_delivery_address?>">

                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $langcliente['commercial_country'] ?>:</label>
                                    <?php

                  $country = $company_country->name;
                  if(in_array($data->commercial_country, array_column($countrylist,'name'))){
                    $country = $data->commercial_country;
                  }
                  ?>
                                    <select class="form-control notranslate" disabled name="commercial_country">
                                        <?php foreach ($countrylist as $clist) { ?>
                                        <option value="<?=$clist->name?>"
                                            <?= ($country == $clist->name)?'selected':'' ?>>
                                            <?=$clist->name ?>
                                        </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $langcliente['commercial_state'] ?>: </label>
                                    <input type="text" class="form-control" disabled name="commercial_state"
                                        value="<?=@$data->commercial_state?>">
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="row">
                                    <div class="col-7">
                                        <div class="form-group">
                                            <label><?= $langcliente['commercial_city'] ?>: </label>
                                            <input disabled type="text" class="form-control" name="commercial_city"
                                                value="<?=@$data->commercial_city?>">
                                        </div>
                                    </div>
                                    <div class="col-5">
                                        <div class="form-group">
                                            <label><?= $langcliente['commercial_zip_code'] ?>: </label>
                                            <input disabled type="text" class="form-control" name="commercial_zip_code"
                                                value="<?=@$data->commercial_zip_code?>">
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $langcliente['commercial_zone'] ?>: </label>
                                    <input type="text" class="form-control" disabled name="commercial_zone"
                                        value="<?=@$data->commercial_zone?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <br>
                <label><strong><?= strtoupper($langcliente['dato_entrega']) ?></strong></label>
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $langcliente['dispatch_delivery_address'] ?>: </label>
                                    <input type="text" class="form-control" disabled name="dispatch_address"
                                        value="<?=@$data->dispatch_address?>">
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $langcliente['dispatch_state'] ?>: </label>
                                    <input type="text" class="form-control" disabled name="dispatch_state"
                                        value="<?=@$data->dispatch_state?>">
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $langcliente['dispatch_country'] ?>: </label>
                                    <select class="form-control notranslate" disabled name="dispatch_country"
                                        id="dis_country">
                                        <?php foreach ($countrylist as $clist) { ?>
                                        <option value="<?=$clist->name?>"
                                            <?= ($country == $clist->name)?'selected':'' ?>>
                                            <?=$clist->name?>
                                        </option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $langcliente['dispatch_city'] ?>: </label>
                                    <input type="text" class="form-control" disabled name="dispatch_city"
                                        value="<?=@$data->dispatch_city?>">
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $langcliente['dispatch_zipcode'] ?>: </label>
                                    <input type="text" class="form-control" disabled name="dispatch_zip_code"
                                        value="<?=@$data->dispatch_zip_code?>">
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $langcliente['dispatch_zone'] ?>: </label>
                                    <input type="text" class="form-control" disabled name="dispatch_zone"
                                        value="<?=@$data->dispatch_zone?>">
                                </div>
                            </div>
                            <!-- <div class="col-md-6 col-lg-4">
                <div class="form-group">
                  <label><?= $langcliente['dispatch_shipping_notes'] ?>: </label>
                  <textarea class="form-control" rows="5" disabled name="dispatch_shipping_notes" ><?=@$data->dispatch_shipping_notes?></textarea>
                </div>
              </div> -->
                            <div class="col-12">
                                <div class="form-group">
                                    <label><?= $langcliente['notes'] ?>: </label>
                                    <textarea class="form-control" rows="5" disabled
                                        name="notes"><?=@$data->dispatch_shipping_notes?></textarea>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group text-right">
                                    <a href="javascript:void(0)" onclick="openurl('<?= addslashes(company_url('customers/edit/'.@$data->customer_id))?>'); return false;"
                                        class="btn btn-success mr-2"><?= $langcliente['editar'] ?></a>
                                    <a class="btn btn-danger mr-2" href="javascript:void(0);"
                                        onclick="javascript:history.go(-1);">
                                        <?= $langcliente['cancelar'] ?>
                                    </a>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                <br>

                <?php $this->load->view('company/customer/ultimosPedidosycatalogos', $data); ?>

            </div>
        </div>
</form>


<!-- Modal -->
<div class="modal fade" id="modal-showlink" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Link to customer's catalog</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

                <p id="link-par" class="py-2"></p>
                <p>
                    <small class="text-muted">
                        <em>
                            (*) This is the original link received by the customer. If the link changes, FullVendor will
                            consider it as an action from the customer. </em>
                    </small>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">Accept</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
window.openurl = function(url) {
    var w = 1500;
    var h = 1200;
    var left = (screen.width / 2) - (w / 2);
    var top = (screen.height / 2) - (h / 2);

    var childWindow = window.open(url, 'NewWindow33', 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=no, width=' + w + ', height=' + h + ', top=' + top + ', left=' + left);

    if (childWindow) {
        // Enfocar la ventana
        childWindow.focus();
        // Manejar evento cuando la ventana hijo se cierre
        childWindow.onbeforeunload = function() {
            // Refrescar la ventana padre
            location.reload();
        };
    } else {
        alert('Por favor, permite ventanas emergentes para este sitio.');
    }
};

// Alias para compatibilidad
function openurl(url) {
    window.openurl(url);
}

$(document).ready(function() {
    $('#seller_asigned').select2({
        placeholder: '<?= $langcliente['seleccionar'] ?>'
    });

})
</script>

<?php $this->load->view('company/customer_scriptjs', $data); ?>

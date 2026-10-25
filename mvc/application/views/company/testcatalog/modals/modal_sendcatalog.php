<div class="modal fade modal-send-catalog" id="sendcatalogModal<?=$v->catalog_id?>" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true"> 
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header p-3">
                <h5 class="modal-title" id="exampleModalLabel"><?= $lang_optionbasic['modal_sendcatalog_title'] ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-3">
                
                <form id="form_sendcatalogbyemail-<?=$v->catalog_id?>"
                    action="<?= company_url('catalog/send_by/'.$v->catalog_id)?>" method="post" class="form_send">
                    <div class="form-group">
                        <label><?= $lang_optionbasic["select_group"] ?></label>
                        <select class="form-control selector_group">
                            <option selected="" value="todo"><?= $lang_optionbasic["all"] ?></option>
                            <!-- <option value="vacio"><?= $lang_optionbasic['empty'] ?></option> -->
                            <?php foreach($group_list as $group): ?>
                            <option value="<?= $group->id ?>"><?= ucwords($group->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="d-block d-flex justify-content-between">
                            <span><?= $lang_optionbasic["select_customer"] ?></span>
                            <span class="spinner-border spinner-border-sm text-right d-none" role="status"
                                aria-hidden="true"></span>
                        </label>
                        <select id="select_<?= $v->catalog_id ?>" name="customerIds[]" class="selectpicker"
                            data-live-search="true" multiple 
                            data-actions-box="true"
                            data-size="7"
                            data-container="modal-body"
                            data-select-all-text="<?= $lang_optionbasic['select_todoestatdos'] ?>"
                            data-deselect-all-text="<?= $lang_optionbasic['deselect_todos'] ?>"
                            data-none-selected-text="<?= $lang_optionbasic['nothing_selected'] ?>"
                            data-width="100%"
                            >

                            <?php if(is_array($customerlist) && count($customerlist)>0){
                            foreach ($customerlist as $cus) {?>
                            <option value="<?= $cus->customer_id?>">
                                <?=  strtoupper($cus->business_name  . " (". $cus->name .")")?>
                            </option>
                            <?php } } ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?= $lang_optionbasic["time_expiration"] ?></label>
                        <input type="number" value="<?= $company->catalogsend_time ? $company->catalogsend_time : '15' ?>" name="expiration_days" class="form-control text-center">
                    </div>
                    <div class="form-group">
                        <label for=""><?= $lang_optionbasic["by_emailorsms"] ?></label>
                        <div class="btn-group w-100">
                            <input id="email_<?=$v->catalog_id?>" hidden="" type="radio" name="type_<?=$v->catalog_id?>"
                                class="btn-check" value="email" checked="">
                            <label for="email_<?=$v->catalog_id?>" class="especial_label border w-50 text-center"><i
                                    class="fa-solid fa-envelope"></i>
                                Email</label>
                            <input id="sms_<?=$v->catalog_id?>" hidden="" type="radio" name="type_<?=$v->catalog_id?>"
                                class="btn-check" value="sms">
                            <label for="sms_<?=$v->catalog_id?>" class="especial_label border w-50 text-center"><i
                                    class="fa-solid fa-mobile"></i>
                                SMS</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?= $lang_optionbasic["tittle_catalogtext"] ?></label>
                        <textarea class="form-control" rows="3" name="catalog_text"></textarea>
                    </div>
                    <div class="form-group">
                        <label><?= $lang_optionbasic["email_text"] ?></label>
                        <textarea class="form-control" rows="3" name="email_text"></textarea>
                    </div>
                    <input type="hidden" value="<?=$v->catalog_id?>" name="catlogIds">
                </form>
            </div>
            <div class="modal-footer">
                <button form="form_sendcatalogbyemail-<?=$v->catalog_id?>" type="submit"
                    class="btn btn-success"><?= $langcatalog['send_catalog'] ?></button>

                <button type="button" class="btn btn-danger"
                    data-dismiss="modal"><?= $langcatalog['cancel'] ?></button>
            </div>
        </div>
    </div>
</div>
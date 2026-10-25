<div class="modal fade" id="downloadModal_<?=$v->catalog_id?>" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog ">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel"><?= $lang_optionbasic["descargar"] ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form>
                    <div class="form-group">
                        <label><?= $lang_optionbasic["select_group"] ?></label>
                        <select name="group" class="form-control selectpicker"
                        data-none-selected-text="<?= $lang_optionbasic['nothing_selected'] ?>" 
                        >
                            <option value="" selected disabled><?= $lang_optionbasic['nothing_selected'] ?></option>
                            <?php foreach($group_list as $group): ?>
                            <option <?= $group->default==1?'selected':'' ?> value="<?= $group->id ?>"><?= ucwords($group->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="mb-2" style="cursor: pointer; padding: 0; margin: 0;color:#007bff;"
                            for="hide_price-<?= $v->catalog_id ?>"><input id="hide_price-<?= $v->catalog_id ?>"
                                type="checkbox" value="1" name="hide_price">&nbsp;<?= $langcatalog['hide_price']?></label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <a onclick="descargar_conpdfMake(event,'<?= $v->catalog_id ?>')" href="javascript:void(0)"
                    class="btn bg-success" data-loader="<?= $langcatalog['aceptando'] ?>"
                    data-download-id="downloadModal_<?=$v->catalog_id?>"><?= $langcatalog['aceptar'] ?></a>
                <button type="button" data-dismiss="modal" aria-label="Close"
                    class="btn btn-danger"><?= $lang_modal['close'] ?></button>
            </div>
        </div>
    </div>
</div>
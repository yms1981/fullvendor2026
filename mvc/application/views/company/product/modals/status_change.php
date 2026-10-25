<?php
/**
 * @var array $categorylist
 * @var array $pageTexts
 */

?>
<div class="modal fade" id="modalchangestatus" tabindex="-1" aria-labelledby="modalchangestatusLabel"
     aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form class="col-12 form-asign-remove-seller" id="form-change-status"
                  action="<?= company_url('products/bulkChangeStatus') ?>" method="post">
                <div class="modal-header pt-2 pb-2">
                    <h5 class="modal-title " id="modalchangestatusLabel"><?= $pageTexts['change_status'] ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p><?= $pageTexts['change_status_description'] ?></p>

                    <input type="hidden" name="products" value=""/>
                    <select name="status_toupdate" class="form-control notranslate" id="multipleStatus">
                        <!-- <option selected disabled><?= $pageTexts['option_select'] ?></option> -->
                        <option value="1"><?= $pageTexts['select_actives'] ?></option>
                        <option value="0"><?= $pageTexts['select_desactives'] ?></option>
                    </select>

                </div>
                <div class="modal-footer">
                    <button id="btn-changestatus-category" form="form-change-status" type="submit"
                            class="btn btn-primary">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        <?= $pageTexts['btn-modal-aceptar'] ?>
                    </button>
                    <button type="button" class="btn btn-danger"
                            data-dismiss="modal"><?= $pageTexts['btn-modal-cancelar'] ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

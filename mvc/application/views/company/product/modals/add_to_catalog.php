<?php
/**
 * @var array $cataloglist
 * @var array $pageTexts
 */

?>
<div class="modal fade" id="modal_addToCatalog" tabindex="-1" role="dialog" aria-labelledby="addToCatalogLabel"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="form-add-catalog" class="form-asign-remove-seller"
                  action="<?= company_url('products/bulkAddToCatalog') ?>" method="post">
                <div class="modal-header">
                    <h5 class="modal-title " id="addToCatalogLabel"><?= $pageTexts['addToCatalog'] ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">


                    <input type="hidden" name="products" value=""/>
                    <div class="form-group">
                        <label for="select_mullcategory"><?= $pageTexts["catalogo"] ?></label>
                        <br>
                        <div id="contain_selectmultple_asign">
                            <select name="catalogs[]" id="selectmultple_asign" multiple>
                                <?php
                                foreach ($cataloglist as $cat) : ?>
                                    <option value="<?= $cat->catalog_id ?>"><?= $cat->catalog_name ?></option>
                                <?php
                                endforeach; ?>
                            </select>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button id="btn-asigncatalog" type="submit" class="btn btn-primary"><span
                            class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        <?= $pageTexts['assign'] ?></button>
                    <button type="button" class="btn btn-danger"
                            data-dismiss="modal"><?= $pageTexts['btn-modal-cancelar'] ?? 'Cancel' ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

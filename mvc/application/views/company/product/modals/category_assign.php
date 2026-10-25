<?php
/**
 * @var array $categorylist
 * @var array $pageTexts
 */
?>
<div class="modal fade" id="modalasigncategory" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title " id="exampleModalLabel"><?= $pageTexts['category_list'] ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="form-asign-category" class="form-asign-remove-seller" action="<?=company_url('products/bulkAssignCategory')?>"
                    method="post">
                    <input type="hidden" name="products" value=""/>
                    <div class="form-group">
                        <label for="select_mullcategory"><?= $pageTexts["select_category"] ?></label>
                        <br>
                        <div id="contain_selectmultple_asign">
                            <select name="categories_toasigned[]" id="selectmultple_asign" multiple>
                                <?php foreach ($categorylist as $cat) : ?>
                                <?php if (empty($cat->category_default)): ?>
                                <option value="<?=$cat->cat_id?>"><?=$cat->category_name?></option>
                                <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button id="btn-asigncategory" type="submit" form="form-asign-category" class="btn btn-primary"><span
                        class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    <?= $pageTexts['assign'] ?></button>
                <button type="button" class="btn btn-danger"
                    data-dismiss="modal"><?= $pageTexts['btn-modal-cancelar'] ?? 'Cancel' ?></button>
            </div>
        </div>
    </div>
</div>

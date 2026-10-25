<?php
/**
 * @var array $categorylist
 * @var array $pageTexts
 */
?>
<div class="modal fade" id="modalremovecategory" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalremovecategoryLabel"><?= htmlspecialchars((string) ($pageTexts['category_list_remove'] ?? $pageTexts['btn-delete-categoria'] ?? $pageTexts['remove'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= htmlspecialchars((string) ($pageTexts['btn-modal-cancelar'] ?? 'Close'), ENT_QUOTES, 'UTF-8') ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form class="col-12 form-asign-remove-seller" id="form-remove-category"
                    action="<?= company_url('products/bulkRemoveCategory') ?>" method="post">
                    <input type="hidden" name="products" value=""/>
                    <div class="form-group">
                        <label for="selectmultple_remove"><?= htmlspecialchars((string) ($pageTexts['select_category'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
                        <br>
                        <div id="contain_selectmultple_remove">
                            <select name="categories[]" id="selectmultple_remove" multiple>
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
                <button id="btn-removecategory" type="submit" form="form-remove-category" class="btn btn-primary">
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    <?= htmlspecialchars((string) ($pageTexts['remove'] ?? $pageTexts['btn_remove'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </button>
                <button type="button" class="btn btn-danger" data-dismiss="modal"><?= htmlspecialchars((string) ($pageTexts['btn-modal-cancelar'] ?? ''), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-5">
        <?php $tab_index = 1; ?>
        <?php if (isset($category)):?>
        <div id="example2Left" class="fullvendor-select-order-categories sortable-list list-group rounded border-bottom"
            style="height: 400px; overflow-y: scroll; background-color: #e9ecef;">


            <?php foreach ($category as $categ) { ?>
            <?php if(!in_array($categ->cat_id, array_column($category_selected_order,'cat_id'))): ?>
            <div tabindex="<?= $tab_index ?>" class="list-group-item fullvendor-cursor-pointer"
                value="<?=$categ->cat_id?>" selected>
                <div class="d-flex align-items-center">
                    <span
                        class="css-order-item d-flex justify-content-center align-items-center animate__animated animate__zoomIn selected-category-to-catalog hidden-important"></span>
                    <span>&nbsp;<?= $categ->category_name?strtoupper($categ->category_name):"&nbsp;" ?></span>
                </div>
                <input hidden type="text" value="<?= $categ->cat_id ?>">
            </div>
            <?php endif; ?>
            <?php $tab_index = $tab_index + 1; ?>
            <?php } ?>
        </div>
        <?php else: ?>
        <div class="rounded border-bottom"
            style="height: 400px; overflow-y: scroll; background-color: #e9ecef;">
        </div>

        <?php endif; ?>
    </div>
    <div class="col-2 d-flex align-items-center justify-content-center">

        <div class="d-flex flex-column">
            <button <?= isset($data) && $data->default_catalog ? 'disabled' : '' ?> id="btn_addall" type="button" class="m-1 btn btn-success"><i class="fa-solid fa-calendar-plus"></i>
                <?= $lang_prods['btn_addall']?></button>
            <button disabled id="btn_addone" type="button" class="m-1 btn btn-success"><i class="fa-solid fa-plus"></i>
                <?= $lang_prods['add_one']?></button>
            <button disabled id="btn_removeone" type="button" class="m-1 btn btn-danger"><i
                    class="fa-solid fa-trash"></i> <?= $lang_prods['btn_remove']?></button>
            <button <?= isset($data) && $data->default_catalog ? 'disabled' : '' ?> id="btn_removeall" type="button" class="m-1 btn btn-danger"><i
                    class="fa-sharp fa-solid fa-broom"></i>
                <?= $lang_prods['btn_clean']?></button>
        </div>


    </div>
    <div class="col-5">
        <div id="example2Right"
            class="fullvendor-select-order-categories sortable-list list-group rounded border-bottom"
            style="height: 400px; overflow-y: scroll;background-color: #e9ecef;">

            <?php foreach ($category_selected_order as $key => $categ) { ?>
            <?php  $key = $key + 1; ?>
            <div data-toggle="tooltip" data-placement="top" title="Tooltip on top" tabindex="<?= $tab_index ?>"
                class="list-group-item fullvendor-cursor-pointer"
                <?= $categ->qty_products >=1? '': 'data-movable="true"' ?> value="<?=$categ->cat_id?>"
                <?= isset($data) && $data->default_catalog ? 'no_removed="true"' : '' ?>>
                <div class="d-flex align-items-center">
                    <span
                        class="css-order-item d-flex justify-content-center align-items-center animate__animated animate__zoomIn selected-category-to-catalog"><?= $key ?></span>
                    <span>&nbsp;<?= $categ->category_name?strtoupper($categ->category_name):"&nbsp;" ?></span>
                </div>
                <input hidden type="text" name="categ_selected[]" value="<?= $categ->cat_id ?>">
            </div>
            <?php $tab_index = $tab_index + 1; ?>
            <?php } ?>
        </div>
    </div>
</div>

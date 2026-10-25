<div style="height: 100%;" class="hover_category">
    <input hidden class="short-value" type="text" name="short">
    <select class="select_option" name="cat_id">
        <option selected value><?= strtoupper($lang_catalogo['option_all']) ?>
        </option>
        <?php 

    if (count($categorias) >0) {
        foreach ($categorias as $cat) { 
            if($cat->qty_products>0){
            ?>
        <option <?= $this->input->get('cat_id')==$cat->cat_id?'selected':'' ?>
            value="<?= $cat->cat_id ?>">
            <?= strtoupper($cat->category_default==1?$lang_catalogo["sin_categoria"]:$cat->category_name) ?>
        </option>
        <?php }} ?>
        <?php } ?>
    </select>
</div>
<div class="column1 col-lg-3 col-md-6">
    <h4 class="d-none d-md-block">&nbsp;<h4>
            <div class="categories_menu categories_three">
                <div class="categories_title">
                    <h2 class="categori_toggle">
                        <?= strtoupper($lang_catalogo['all_catagories']) ?></h2>
                </div>
                <div class="categories_menu_toggle">
                    <ul>
                        <li>
                            <a href="<?= marketplace_url() ?>" class="d-flex">
                                <span
                                    class="ps-2 <?= $this->input->get('cat_id')==null?'text-danger':'' ?>">
                                    <i style="width: 30px;height: 30px;" class="fa fa-th-large"
                                        aria-hidden="true"></i><?= strtoupper($lang_catalogo["all"]) ?>
                                </span>
                            </a>
                        </li>
                        <!-- categorias -->
                        <?php

                if (count($categorias) >0) {
                    $cont=0;
                    foreach ($categorias as $cat) { 
                        if($cat->qty_products >0){
                        $cont=$cont+1;
                        ?>
                        <li class="<?= ($cont >6)?'hidden':'' ?>">
                            <a href="<?= marketplace_url('shop?cat_id='.$cat->cat_id) ?>"
                                class="d-flex">

                                <?php  if($cat->category_default!=1): ?>
                                <img style="width: 30px;height: 30px;"
                                    src="<?= ($cat->images!= null || $cat->images!= '')?base_url('uploads/categories/'.@$info_company->company_id.'/'.$cat->images).'?v='.$version:base_url('uploads/noimg.png') .'?v=' . $version ?>">
                                <?php endif;?>

                                <span
                                    class="ps-2 <?= $this->input->get('cat_id')==$cat->cat_id?'text-danger':'' ?>">
                                    <?php  if($cat->category_default==1): ?>
                                    <i style="width: 30px;height: 30px;font-size: 19px;"
                                        class="fa fa-ban" aria-hidden="true"></i>
                                    <?php endif;?>
                                    <?= strtoupper($cat->category_default==1?$lang_catalogo["sin_categoria"]:$cat->category_name) ?>
                                </span>
                            </a>
                        </li>
                        <?php }} ?>
                        <?php } ?>

                        <li><a href="#" id="more-btn"><i class="fa fa-plus"
                                    aria-hidden="true"></i>
                                <?= $lang_catalogo['more_categories'] ?></a></li>
                    </ul>
                </div>
            </div>
</div>
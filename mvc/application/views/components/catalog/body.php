<?php
    //$categorias = count($categories_order)>1?$categories_order:$categorias;
    $categorias = $categories_order;
    //echo json_encode($categorias);
?>

<!--breadcrumbs area start-->
<div class="breadcrumbs_area">
    <div class="container">
        <div class="row">
            <div class="col-md-12 col-lg-6">
                <div class="breadcrumb_content">
                    <ul>
                        <li><a href="javascript:history.go(-1)"><?= $lang_catalogo['home'] ?></a></li>
                        <li><?= $lang_catalogo['catalog_list'] ?></li>
                    </ul>
                </div>
            </div>
            <div class="col-md-12 col-lg-6" style="text-align:right;">

                <?php if($company->return_admin != null){ ?>
                <a href="<?= company_url('orders') ?>" class="btn btn-danger" type="submit">Cancel Order</a>
                <?php }else if($company->login_seller ==1){ ?>

                <a href="<?= base_url('catalogo/producto/historial?token='.$token) ?>"
                   class="btn btn-success" type="submit"><?= $lang_catalogo['historial'] ?></a>
                <a id="btn_createneworder" href="<?= seller_url('catalogo?token='.$token) ?>"
                    class="btn btn-success" type="submit"><?= $lang_catalogo['create_neworder'] ?></a>
                <?php } ?>

            </div>
        </div>
    </div>
</div>
<!--breadcrumbs area end-->
<!--shop  area start-->
<div class="shop_area">
    <div class="container">
        <h4 class="d-sm-block d-md-block d-lg-none d-xl-none"><?= strtoupper($catalog_text) ?></h4>

        <h4 class="d-md-none"><?= strtoupper($lang_catalogo['customer']) ?>:
            <?= strtoupper($info_customer->business_name) . strtoupper((isset($info_customer->name))?' ('.$info_customer->name.')':'') ?>
        </h4>

        <div class="row">
            <h4><?= strtoupper( $lang_catalogo['seller'] .': '. $info_seller->first_name . ' ' . $info_seller->last_name) ?>
            </h4>
            <h4><?= strtoupper( $lang_catalogo['group'] .': '. $info_customer->group_name) ?></h4>
        </div>

        <div class="row">

            <div class="col-lg-9 col-md-12">
                <!--shop banner area start-->
                <!--shop banner area end-->
                <!--shop toolbar start-->
                <div class="shop_toolbar_wrapper">
                    <div class="shop_toolbar_btn">
                        <button data-role="grid_4" type="button" class=" active btn-grid-4"
                            data-toggle="tooltip"
                            data-tippy-placement="top"
                            data-tippy-arrow="true"
                            data-tippy-inertia="false"
                            data-tippy="<?= $lang_catalogo['cuadricula'] ?>"
                            title="4"></button>
                        <button data-role="grid_list" type="button" class="btn-list" data-toggle="tooltip"
                            data-tippy-placement="top" data-tippy-arrow="true" data-tippy-inertia="false"
                            data-tippy="<?= $lang_catalogo['list'] ?>"
                            title="List"></button>
                    </div>
                    <div class="">
                        <form class="" action="#">
                            <select name="orderby" id="short">
                                <option
                                    <?= ($this->input->get('short')==null || $this->input->get('short') ==1)?'selected':'' ?>
                                    value="1"><a href=""><?= $lang_catalogo['name'] ?> A-Z</a></option>
                                <option <?= ($this->input->get('short')==2)?'selected':'' ?> value="2">
                                    <?= $lang_catalogo['name'] ?> Z-A</option>
                                <option <?= ($this->input->get('short')==3)?'selected':'' ?> value="3">SKU A-Z</option>
                                <option <?= ($this->input->get('short')==4)?'selected':'' ?> value="4">SKU Z-A</option>
                                <option <?= ($this->input->get('short') == 5) ? 'selected' : '' ?>
                                    value="5"><?= $lang_catalogo['precio_menor'] ?></option>
                                <option <?= ($this->input->get('short') == 6) ? 'selected' : '' ?>
                                    value="6"><?= $lang_catalogo['precio_mayor'] ?></option>
                            </select>
                        </form>
                    </div>
                    <div class="page_amount">
                        <p><?= $lang_catalogo['showing'] ?> 1–<?= $limit ?> <?= $lang_catalogo['of'] ?>
                            <?= $total_filtro ?> <?= $lang_catalogo['results'] ?></p>
                    </div>
                </div>
                <!--shop toolbar end-->

                <!--shop wrapper start-->

                <?php if(count($products)>0){?>

                <div id="tabla" >
                    <div v-if="getProductbySearch.length === 0" id="grid-default" class="row no-gutters shop_wrapper grid_4 bg-transparent border-0">
                        <?php foreach($products as $product) :?>
                        <?php $this->load->view('catalogo/product_row', ["product" => $product,'info_company'=>$info_company]); ?>
                        <?php endforeach;?>
                    </div>
                </div>
                <div class="shop_toolbar t_bottom">
                    <div class="pagination">
                        <?=@$paginate?>
                    </div>
                </div>
                <?php }else{?>
                <div class="col-12 text-center">
                    <img src="<?= base_url('uploads/vacio.PNG') ?>">
                    <h5>Sin información!</h5>
                </div>
                <?php }?>


            </div>
            <div class="col-lg-3 col-md-12">
                <!--sidebar widget start-->
                <aside class="sidebar_widget">
                    <div class="widget_list widget_categories">
                        <h3><?= $lang_catalogo['product_categories'] ?></h3>
                        <ul>
                            <li>
                                <a href="<?= base_url('catalogo/producto?token=' . $token) ?>"
                                    class="d-flex">
                                    <span class="ps-2 <?= $this->input->get('cat_id')==null?'text-danger':'' ?>">
                                        <i style="width: 30px;height: 30px;" class="fa fa-th-large"
                                            aria-hidden="true"></i><?= strtoupper($lang_catalogo["all"]) ?>
                                    </span>
                                </a>
                            </li>
                            <!-- categorias -->
                            <?php

                            if (count($categorias) >0) {
                                foreach ($categorias as $cat) {
                                    // echo $cat->category_name. ": $cat->qty_products <br>";
                                       if($cat->qty_products > 0) {
                                    ?>
                            <li id="cat-li-<?= $cat->cat_id ?>"
                                <?= $this->input->get('cat_id')==$cat->cat_id?'data-principal="TRUE"':'' ?>
                                class="<?= $this->input->get('cat_id')==$cat->cat_id?'bg-secondary bg-opacity-25':'' ?> px-1">
                                <a href="<?= base_url('catalogo/producto?token=' . $token .'&cat_id='.$cat->cat_id) ?>"
                                    class="d-flex">
                                    <?php  if($cat->category_default!=1): ?>
                                    <img style="width: 30px;height: 30px;"
                                        src="<?= ($cat->images!= null || $cat->images!= '')?base_url('uploads/categories/'.@$company_id.'/'.$cat->images).'?v='.$version:base_url('uploads/noimg.png') .'?v=' . $version ?>">
                                    <?php endif;?>
                                    <span
                                        class="ps-2 <?= $this->input->get('cat_id')==$cat->cat_id?'text-danger':'' ?>">
                                        <?php  if($cat->category_default==1): ?>
                                        <i style="width: 30px;height: 30px;font-size: 19px;" class="fa fa-ban"
                                            aria-hidden="true"></i>
                                        <?php endif;?>
                                        <?= strtoupper($cat->category_default==1?$lang_catalogo["sin_categoria"]:$cat->category_name) ?>
                                        <!-- &nbsp;<span class="badge bg-secondary"><?= $cat->qty_products ?></span> -->
                                    </span>
                                </a>
                            </li>
                            <?php }} ?>
                            <?php } ?>

                        </ul>
                    </div>

                </aside>
                <!--sidebar widget end-->
            </div>
        </div>
    </div>
</div>
<!--shop  area end-->

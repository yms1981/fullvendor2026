<div class="container-fluid page-body-wrapper pt-3">
    <div class="main-panel">
        <div class="container-fluid">
            <div class="page-header">
                <h3 class="page-title"> <?= $lang_prods['add_new_catalog'] ?></h3>
            </div>
            <div class="card contentHeight">
                <div class="card-body">
                    <form id="catalogAdd" novalidate class="forms-sample" action="<?= company_url('catalog/create') ?>"
                        method="post" enctype="multipart/form-data">
                        <div class="row">

                            <div class="col-md-6 col-lg-3">
                                <div class="form-group">
                                    <label><?= $lang_prods['catalog_name'] ?>:</label>
                                    <input type="text" class="form-control" name="catalog_name"
                                        value="<?php echo set_value('catalog_name'); ?>" required="">
                                    <span class="error"><?php echo form_error('catalog_name'); ?></span>
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-2">
                                <div id="myselect2" class="form-group">
                                    <label><?= $lang_optionbasic['label_activo'] ?>: </label>
                                    <select class="form-control" name="status">
                                        <option selected disabled><?= $lang_optionbasic['select'] ?></option>
                                        <option selected value="1"><?= $lang_optionbasic['activo'] ?></option>
                                        <option value="0"><?= $lang_optionbasic['desactivo'] ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="form-group">
                                    <label><?= $lang_prods['cabeza_pagina'] ?>:</label>
                                    <input type="text" class="form-control" name="cabeza_pagina">
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-4">
                                <div id="myselect2" class="form-group">
                                    <label><?= $lang_prods['product_name'] ?>: </label>
                                    <select class="form-control js-example" name="products[]" multiple="multiple"
                                        style="visibility: hidden;"
                                        data-placeholder="<?= $lang_optionbasic['nothing_selected'] ?>">
                                        <?php foreach ($product as $prod) { ?>
                                        <option value="<?=$prod->pro_id?>">
                                            <?= strtoupper($prod->sku . ' | '. $prod->name)?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12">
                                <select id="kt_dual_listbox_1" name="categories[]" class="dual-listbox"
                                    multiple="multiple" style="visibility: hidden;">
                                    <?php foreach ($category as $categ) { ?>
                                    <option value="<?=$categ->cat_id?>">
                                        <?= $categ->category_name?strtoupper($categ->category_name):"&nbsp;" ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group text-right">
                            <button type="submit" class="btn btn-success mr-2"><?= $lang_labels['save']?></button>
                            <a class="btn btn-danger mr-2" href="<?= company_url('catalog') ?>">
                                <?= $lang_labels['f_cancelar']?>
                            </a>
                        </div>
                         <!-- Ordenador de categorias (Select)  -->
                       <?php $this->load->view('company/catalog/ordenador_de_categorias/dualist-box-edit') ?>                       
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php  $this->load->view('company/catalog/script-add-edit',$lang_prods); ?>
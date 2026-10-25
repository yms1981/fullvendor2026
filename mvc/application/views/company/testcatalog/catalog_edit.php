<style>
.filter-option {
    white-space: normal !important;
}
</style>

<div class="container-fluid page-body-wrapper pt-3">
    <div class="main-panel">
        <div class="container-fluid">
            <div class="page-header">
                <h3 class="page-title"> <?= strtoupper($langcatalog['edit_catalog']) ?></h3>
            </div>
            <div class="card contentHeight">
                <div class="card-body">
                    <form novalidate id="catalogAdd" class="forms-sample"
                        action="<?= company_url('catalog/update/' . $data->catalog_id) ?>" method="post"
                        enctype="multipart/form-data">
                        <div class="row">

                            <div class="col-md-6 col-lg-3">
                                <!-- nombre de catalogo -->
                                <div class="form-group">
                                    <label><?= $langcatalog['nombre_catalogo'] ?>:</label>
                                    <input type="text" class="form-control" name="catalog_name"
                                        value="<?=@$data->catalog_name ?>" required="">
                                    <input type="hidden" name="catalog_id" value="<?=@$data->catalog_id ?>">
                                    <span class="error"><?php echo form_error('catalog_name'); ?></span>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-2">
                                <!-- nombre de catalogo -->
                                <div class="form-group">
                                    <label><?= $lang_optionbasic['label_activo'] ?>:</label>
                                    <select class="form-control" name="status">
                                        <option selected disabled><?= $lang_optionbasic['select'] ?></option>
                                        <option <?= ($data->status == 1)?'selected':'' ?> value="1">
                                            <?= $lang_optionbasic['activo'] ?></option>
                                        <option <?= ($data->status == 0)?'selected':'' ?> value="0">
                                            <?= $lang_optionbasic['desactivo'] ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="form-group">
                                    <label><?= $lang_prods['cabeza_pagina'] ?>:</label>
                                    <input type="text" class="form-control" value="<?= $data->cabeza_pagina ?>"
                                        name="cabeza_pagina">
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div id="myselect2" class="form-group">
                                    <!-- nombre de producto -->
                                    <label><?= $langcatalog['nombre_producto'] ?>: </label>
                                    <select class="form-control js-example" name="product_name[]" multiple="multiple"
                                        style="visibility: hidden;">
                                        <?php   $product_name = explode(",", $data->product_name);  ?>
                                        <?php if (count($product) > 0): ?>
                                        <?php foreach ($product as $key => $prod): ?>
                                        <?php 

                                        if(in_array($prod->pro_id, $product_name)) {

                                            $str_flagp = "selected";
                                        }
                                        else { $str_flagp="n"; }; 
                                        ?>
                                        <option value="<?= $prod->pro_id ?>" <?php echo $str_flagp; ?>>
                                            <?= strtoupper($prod->sku.' | '.$prod->name)?>
                                        </option>
                                        <?php endforeach ?>
                                        <?php endif ?>
                                    </select>
                                </div>
                            </div>

                            <select id="kt_dual_listbox_1" name="categories[]" class="dual-listbox"
                                multiple="multiple" style="visibility: hidden;">                                
                            </select>
                            <!-- <div class="col-12">
                                <div class="card-body">
                                </div>
                            </div> -->
                        </div>
                        
                        <div class="form-group text-right">
                            <button type="submit" class="btn btn-success mr-2"><?= $lang_labels['f_submit']?></button>
                            <a class="btn btn-danger mr-2" href="<?= company_url('catalog') ?>"  data-toggle="tooltip" data-placement="top" title="Tooltip on top">
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
<?php  $this->load->view('company/catalog/script-add-edit'); ?>
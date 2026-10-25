<div class="modal fade previewModal" id="previewModal<?=$v->catalog_id?>" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog ">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel"><?= $langcatalog['preview'] ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 col-lg-12">
                        <div class="form-group">
                            <label><?= $langcatalog['nombre_catalogo'] ?>:</label>
                            <input type="text" class="form-control" disabled value="<?= $v->catalog_name ?>">
                        </div>
                        <div class="form-group">
                            <label><?= $langcatalog['status'] ?></label>
                            <input disabled name="status" class="form-control" type="text" value="<?= $v->status == 1?$langcatalog['enabled']:$langcatalog['disabled'] ?>">
                        </div>
                        <div class="form-group">
                            <label><?= $langcatalog['header_page'] ?>:</label>
                            <input type="text" class="form-control" value="<?= $v->cabeza_pagina ?>" name="cabeza_pagina">
                        </div>
                        <div class="form-group">
                            <label><?= $langcatalog['nombre_producto'] ?>:</label>
                            <select class="form-control js-example" multiple="multiple" disabled>
                                <?php foreach($v->products as $pro): ?>
                                    <option selected value="<?= $pro->sku ?>"><?= $pro->sku .' '. $pro->name ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><?= $langcatalog['category_list'] ?>:</label>
                            <ul class="list-group border rounded" style="height: 238px;overflow: auto;">
                                <?php if($v->categories): ?>
                                <?php foreach($v->categories as $cat): ?>   
                                <li class="list-group-item">
                                    <div class="d-flex align-items-center">
                                        <span class="roundblock-category"><?= $cat->order ?></span>
                                        <span>&nbsp;&nbsp;<?= strtoupper($cat->category_name) ?></span>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                                <?php else: ?>
                                <li class="h-100 d-flex justify-content-center align-items-center"><?= $langcatalog['emtpy'] ?></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="<?= company_url('catalog/edit/'. $v->catalog_id) ?>" class="btn bg-success"><?= $langcatalog['edit'] ?></a>
                <button type="button" data-dismiss="modal" aria-label="Close"
                    class="btn btn-danger"><?= $lang_modal['close'] ?></button>
            </div>
        </div>
    </div>
</div>
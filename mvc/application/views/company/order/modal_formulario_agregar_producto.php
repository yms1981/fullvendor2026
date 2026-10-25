<!-- Modal -->
<div class="modal fade" id="add-new" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel"><?= $lang_order['nuevo'] ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

                <select class="js-data-example-ajax w-100"
                    data-placeholder="<?= $lang_order['buscar_por_skuandName'] ?>"></select>
                <br>
                <br>
                <br>
                <br>
                <div id="formulario_agregar col-12">
                    <div class="row">
                        <div class="col-4">
                            <div class="row">
                                <div class="col-12 pr-5 pl-4">
                                    <div class="row border">
                                        <label class="bg-success text-white pl-2 pr-2 rounded"
                                            style="position: relative;top: -0.7em;display: inline;background-color:white;font-weight: 600;padding-right: 0.5em;padding-left: 0.5em;margin-left: 0.5em;font-size:1.1em !important;"><?= $lang_order['foto'] ?></label>
                                        <div id="imagen_add" class="col-12">
                                            <br><br><br><br><br><br><br><br><br>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-8">
                            <div class="row">
                                <div class="col-12 pr-4">
                                    <div class="row border">
                                        <label class="bg-success text-white pl-2 pr-2 rounded"
                                            style="position: relative;top: -0.7em;display: inline;background-color:white;font-weight: 600;padding-right: 0.5em;padding-left: 0.5em;margin-left: 0.5em;font-size:1.1em !important;"><?= $lang_order['datos_producto'] ?></label>
                                        <div id="datos_productadd" class="col-12">
                                            <br><br><br><br><br><br><br><br><br>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button id="btn-add" style="display: none;"
                    class="btn btn-success"><?= $lang_order['agregar_add'] ?></button>
                <button type="button" class="btn btn-danger"
                    data-dismiss="modal"><?= $lang_order['cancelar_add'] ?></button>
            </div>
        </div>
    </div>
</div>
<?php
/**
 * @var array $pageTexts
 */
?>
<div class="modal fade" id="modal_export" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">
                    <?= strtoupper(isset($title_modal)?$title_modal:$pageTexts['titulo_exportacion']) ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="form-exportar">
                    <div class="form-group">
                        <label class="text-bold"><?= $pageTexts['csv_file'] ?>: </label><br>
                    </div>
                    <div class="accordion list-group table-hover" id="accordionExample">
                        <div class="card list-group-item p-0">
                            <div id="headingOne">
                                <h2 class="mb-0">
                                    <div class="form-group p-0 mb-0">
                                        <input id="file_excel" hidden type="radio" name="type_file" class="btn-check"
                                               value="excel" checked>
                                        <label for="file_excel"
                                               class="btn d-block especial_label w-100 mb-0 p-3 text-left" type="button"
                                               data-toggle="collapse" data-target="#collapseOne" aria-expanded="false"
                                               aria-controls="collapseOne">
                                            <span class="text-success"><i class="fa-solid fa-file-excel"></i></span>
                                            <?= $pageTexts['excel_format'] ?? 'Excel' ?>
                                        </label>
                                    </div>
                                </h2>
                            </div>
                            <div id="collapseOne" class="collapse" aria-labelledby="headingOne"
                                 data-parent="#accordionExample">
                                <!--
                                <div class="card-body">
                                </div>
                                -->
                            </div>
                        </div>
                        <div class="card list-group-item p-0">
                            <div id="headingTwo">
                                <h2 class="mb-0">
                                    <div class="form-group p-0 mb-0">
                                        <input id="file_pdf" hidden type="radio" name="type_file" class="btn-check"
                                               value="pdf">
                                        <label for="file_pdf"
                                               class="btn d-block especial_label w-100 mb-0 p-3 text-left" type="button"
                                               data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false"
                                               aria-controls="collapseTwo">
                                            <span class="text-danger"><i class="fa-solid fa-file-excel"></i></span>
                                            <?= $pageTexts['pdf_format'] ?? 'Pdf' ?>
                                        </label>
                                    </div>
                                </h2>
                            </div>
                            <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo"
                                 data-parent="#accordionExample">
                                <!-- <div class="card-body">

                                </div> -->
                            </div>
                        </div>
                        <div class="card list-group-item p-0">
                            <div id="headingThree">
                                <h2 class="mb-0">
                                    <div class="form-group p-0 mb-0">
                                        <input id="file_csv" hidden type="radio" name="type_file" class="btn-check"
                                               value="csv">
                                        <label for="file_csv"
                                               class="btn d-block especial_label w-100 mb-0 p-3 text-left collapsed"
                                               type="button" data-toggle="collapse" data-target="#collapseThree"
                                               aria-expanded="false" aria-controls="collapseThree">
                                            <span><i class="fa-solid fa-file"></i></span> <?= $pageTexts['csv_format'] ?? 'Csv' ?>
                                        </label>
                                    </div>
                                </h2>
                            </div>
                            <div id="collapseThree" class="collapse" aria-labelledby="headingThree"
                                 data-parent="#accordionExample">
                                <div class="card-body">
                                    <div class="form-group pt-3">
                                        <label><?= $pageTexts['separador_campo'] ?>: <span
                                                class="color-red">*</span></label><br>
                                        <div style="display: flex; width: fit-content;">
                                            <div class="fieldsep_op d-flex align-items-center">
                                                <input type="radio" id="sepComma" name="separator" value=",">
                                                <label class="pt-2" for="sepComma"
                                                       style="cursor:pointer;"><?= $pageTexts['coma']?></label>
                                            </div>
                                            <div style="width: 7px;"></div>
                                            <div class="fieldsep_op d-flex align-items-center">
                                                <input type="radio" id="sepSemicolon" name="separator"
                                                       value=";" checked>
                                                <label class="pt-2" for="sepSemicolon"
                                                       style="cursor:pointer;"><?= $pageTexts['punto_coma']?>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php if(isset($options_order)): ?>
                        <div class="form-group">
                            <br>
                            <label style="cursor: pointer; padding: 0; margin: 0;color:#007bff;" for="show_stock_erp">
                                <input id="show_stock_erp" type="checkbox" value="1" name="show_stock_erp">&nbsp;<?= $pageTexts['show_stock_erp']?>
                            </label>
                            <br>
                            <label class="mb-2" style="cursor: pointer; padding: 0; margin: 0;color:#007bff;" for="show_comment">
                                <input id="show_comment" type="checkbox" value="1" name="show_comment">&nbsp;<?= $pageTexts['show_comments']?>
                            </label>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
            <div class="modal-footer">
                <button onclick='export_product()' id="btn-export"
                        class="btn btn-primary mt-2 mt-sm-0 btn-icon-text"><i
                        class="mdi mdi-download"></i><?= $pageTexts['bnExport'] ?></button>
                <button id="btn_exportGlobla" type="button" class="btn btn-danger"
                        data-dismiss="modal"><?= $pageTexts['btn_cancelar'] ?></button>
            </div>
        </div>
    </div>
</div>

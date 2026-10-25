<?php
    $lang_modal = $this->HelperLanguage->modal_exportLayout();
    if (! is_array($lang_modal)) {
        $lang_modal = [];
    }
    $labelFileType = (string) ($lang_modal['export_file_type'] ?? $lang_modal['excel_file'] ?? $lang_modal['csv_file'] ?? '');
    $labelExcel = (string) ($lang_modal['format_excel_option'] ?? $lang_modal['format_excel'] ?? 'Excel');
    $labelPdf = (string) ($lang_modal['format_pdf_option'] ?? 'Pdf');
    $closeLabel = (string) ($lang_modal['cerrar'] ?? $lang_modal['btn_cancelar'] ?? '');
?>

<div class="modal fade" id="modal_export" tabindex="-1" role="dialog" aria-labelledby="modal_export_title"
    aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal_export_title">
                    <?= htmlspecialchars(strtoupper((string) (isset($title_modal) ? $title_modal : ($lang_modal['titulo_exportacion'] ?? ''))), ENT_QUOTES, 'UTF-8') ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal"
                    aria-label="<?= htmlspecialchars($closeLabel, ENT_QUOTES, 'UTF-8') ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="form-exportar">
                    <div class="form-group">
                        <label class="text-bold"><?= htmlspecialchars($labelFileType, ENT_QUOTES, 'UTF-8') ?>: </label><br>
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
                                            <?= htmlspecialchars($labelExcel, ENT_QUOTES, 'UTF-8') ?>
                                        </label>
                                    </div>
                                </h2>
                            </div>
                            <div id="collapseOne" class="collapse" aria-labelledby="headingOne"
                                data-parent="#accordionExample">
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
                                            <span class="text-danger"><i class="fa-solid fa-file-pdf"></i></span>
                                            <?= htmlspecialchars($labelPdf, ENT_QUOTES, 'UTF-8') ?>
                                        </label>
                                    </div>
                                </h2>
                            </div>
                            <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo"
                                data-parent="#accordionExample">
                            </div>
                        </div>
                    </div>
                    <?php if (isset($options_order)): ?>
                    <div class="form-group">
                        <br>
                        <label style="cursor: pointer; padding: 0; margin: 0;color:#007bff;" for="show_stock_erp">
                            <input id="show_stock_erp" type="checkbox" value="1" name="show_stock_erp">&nbsp;<?= htmlspecialchars((string) ($lang_modal['show_stock_erp'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </label>
                        <br>
                        <label class="mb-2" style="cursor: pointer; padding: 0; margin: 0;color:#007bff;" for="show_comment">
                            <input id="show_comment" type="checkbox" value="1" name="show_comment">&nbsp;<?= htmlspecialchars((string) ($lang_modal['show_comments'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </label>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
            <div class="modal-footer">
                <button onclick='export_file("<?= htmlspecialchars((string) ($id_formFilter ?? ''), ENT_QUOTES, 'UTF-8') ?>","<?= htmlspecialchars((string) ($url_send ?? ''), ENT_QUOTES, 'UTF-8') ?>")' id="btn-export"
                    class="btn btn-success mt-2 mt-sm-0 btn-icon-text"><i
                        class="mdi mdi-download"></i><?= htmlspecialchars((string) ($lang_modal['bnExport'] ?? ''), ENT_QUOTES, 'UTF-8') ?></button>
                <button id="btn_exportGlobla" type="button" class="btn btn-danger"
                    data-dismiss="modal"><?= htmlspecialchars((string) ($lang_modal['btn_cancelar'] ?? ''), ENT_QUOTES, 'UTF-8') ?></button>
            </div>
        </div>
    </div>
</div>

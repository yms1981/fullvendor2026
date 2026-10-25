<?php
if (!isset($sep)) $sep=1;
if (!isset($langid)) $langid = 1;
if (!isset($errflag)) $errflag = 0;
if (!isset($errmsg)) $errmsg = "";

?>
<style>
.app_progbar {
    position: absolute;
    width: 5rem;
    height: 5rem;
    left: 45%;
    top: 32%;
    opacity: 0.56;
    background: url("<?= base_url('images/logos/isologo.png'); ?>");
    background-size: 5px 5px;
    background-position: center;
    background-repeat: no-repeat;
    background-size: contain;
    z-index: 21001;
}

.app_progbargif {
    margin-left: -1.2em;
    margin-top: -0.85em;
    width: 7.4rem;
    height: 6rem;
    opacity: 0.4;
}

.app_progbarbk {
    position: absolute;
    width: 100%;
    height: 100%;
    border-radius: 9%;
    opacity: 0.225;
    background-color: red;
}

.iinfotable_row {
    background-color: #fff5f5 !important;
    border-left: 4px solid #dc3545 !important;
    cursor: pointer;
    transition: all 0.2s ease;
}

.iinfotable_row:hover {
    background-color: #ffe5e5 !important;
    transform: translateX(2px);
}

.iinfodlgback {
    color: white;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-bottom: none;
}

.iinfosum_grayline {
    color: #495057;
    font-weight: 500;
    padding: 8px 0;
}

.iinfosum_redline {
    color: #dc3545;
    font-weight: 600;
    padding: 8px 0;
}

.iinfosum_greenline {
    color: #28a745;
    font-weight: 600;
    padding: 8px 0;
}

.error_bg {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
    border: 2px solid #dc3545;
    border-radius: 8px;
    padding: 12px;
    transition: all 0.3s ease;
}

.error_bg_orange {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%) !important;
    border: 2px solid #ff9800;
    border-radius: 8px;
    padding: 12px;
    transition: all 0.3s ease;
}

.fieldsep_op {
    cursor: pointer;
    width: fit-content;
    display: flex;
    align-items: center;
    padding: 8px 16px;
    margin: 0 5px;
    border: 2px solid #dee2e6;
    border-radius: 8px;
    background: #f8f9fa;
    transition: all 0.3s ease;
}

.fieldsep_op:hover {
    background: #e9ecef;
    border-color: #007bff;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.fieldsep_op input[type="radio"] {
    margin-right: 8px;
    cursor: pointer;
}

.langbox {
    display: flex;
    justify-content: end;
    column-gap: 10px;
    margin-left: auto;
    font-size: 12px;
    margin-top: 15px;
    margin-right: 10px;
}

.lang_selec {
    border: 3px solid #d6d6d6;
    border-radius: 9px;
    padding: 1px;
}

.lang_frame {
    cursor: pointer;
}

.error_msgbox {
    margin: 15px 0;
    border-radius: 10px;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    width: 100%;
    height: fit-content;
    padding: 15px 20px;
    color: white;
    font-weight: 600;
    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
    border-left: 5px solid #dc3545;
}

.show_msgbox {
    visibility: visible;
    opacity: 1;
    animation: slideDown 0.3s ease;
}

.hide_msgbox {
    visibility: hidden;
    opacity: 0;
    height: 0;
    padding: 0;
    margin: 0;
    overflow: hidden;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.previewbox {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-top: 15px;
}

.pbar {
    background: linear-gradient(90deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
    width: 0px;
    height: 8px;
    border-radius: 10px;
    animation: progres 4s infinite linear;
    box-shadow: 0 2px 10px rgba(102, 126, 234, 0.4);
}

.pbarbox {
    flex: 1;
    max-width: 300px;
    padding: 8px;
    background: #f8f9fa;
    border-radius: 10px;
    border: 1px solid #dee2e6;
}

.pbarbox2 {
    width: 200px;
    padding: 8px;
    background: #f8f9fa;
    border-radius: 10px;
    border: 1px solid #dee2e6;
    margin-left: 15px;
}

@keyframes progres {
    0% {
        width: 0%;
    }
    25% {
        width: 50%;
    }
    50% {
        width: 75%;
    }
    75% {
        width: 85%;
    }
    100% {
        width: 100%;
    }
}

/* Mejoras adicionales */
#icsvvisor {
    margin-top: 25px;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

#infoTable {
    margin-bottom: 0;
}

#infoTable thead {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
}

#infoTable thead th {
    border: none;
    padding: 15px 10px;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 0.5px;
}

#infoTable tbody tr {
    transition: all 0.2s ease;
}

#infoTable tbody tr:hover {
    background-color: #f8f9fa;
}

#infoTable tbody td {
    padding: 12px 10px;
    vertical-align: middle;
    border-bottom: 1px solid #e9ecef;
}

#ivalidsum .card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}

#ivalidsum .blockquote {
    border-left: 4px solid #667eea;
    padding-left: 20px;
    margin: 0;
}

.form-group label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 8px;
}

.form-control {
    border-radius: 8px;
    border: 1px solid #ced4da;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.btn {
    border-radius: 8px;
    padding: 10px 20px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

fieldset {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 20px;
    margin: 20px 0;
    background: #f8f9fa;
}

fieldset legend {
    font-weight: 600;
    color: #495057;
    padding: 0 15px;
    font-size: 16px;
}

#iformDlgEdit .modal-content {
    border-radius: 15px;
    border: none;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

#iformDlgEdit .modal-body {
    padding: 25px;
}

#iformDlgEdit .form-group {
    margin-bottom: 20px;
}

#ifdlg_err {
    border-radius: 8px;
    margin-top: 15px;
}
</style>
<div id="errmsgBox" class="error_msgbox hide_msgbox">
    <div></div>
</div>

<div class="container-fluid page-body-wrapper">
    <div class="main-panel">
        <div class="container-fluid pt-3">
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="page-header">
                        <h3 class="page-title mb-0"><?= $langdef['title'] ?></h3>
                    </div>
                </div>
                <div class="col-md-6 text-right d-flex align-items-center justify-content-end">
                    <a href="<?= import_template_download_url('productcsv.xlsx') ?>" download
                        class="btn btn-success btn-icon-text shadow-sm">
                        <i class="mdi mdi-download"></i>
                        <?= $langdef['sampledownload'] ?>
                    </a>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div>
                        <form id="frmcsvfile" action="<?= company_url('products/csvImport') ?>" method="post"
                            enctype="multipart/form-data">
                            <div class="form-group">
                                <label class="mb-3"><strong><?= htmlspecialchars($langdef['import_select_file_excel'] ?? ($langdef['import_select_file'] ?? ''), ENT_QUOTES, 'UTF-8') ?>:</strong> <span class="color-red">*</span></label>
                                <div class="fileinput fileinput-new mb-3" data-provides="fileinput">
                                    <?php if (isset($filename)): ?>
                                        <div class="alert alert-info">
                                            <i class="mdi mdi-file-check"></i> <?= $filename ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="mb-3">
                                    <span class="badge badge-danger p-2">
                                        <i class="mdi mdi-information"></i> <?= htmlspecialchars($langdef['format_excel'] ?? ($langdef['formatcsv'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </div>

                                <fieldset class="mb-4">
                                    <legend><?= $langdef['fieldsep'] ?>:</legend>
                                    <div class="d-flex flex-wrap gap-3">
                                        <div class="fieldsep_op" onclick="sepchanged(0)">
                                            <input type="radio" id="sepComma" name="fsep" value="0"
                                                <?php echo ($sep==0)?'checked':'' ?>>
                                            <label for="sepComma" class="mb-0 ml-2" style="cursor: pointer;">
                                                <?= $langdef['commatip'] ?>
                                            </label>
                                        </div>
                                        <div class="fieldsep_op" onclick="sepchanged(1)">
                                            <input type="radio" id="sepSemicolon" name="fsep" value="1"
                                                <?php echo ($sep==1)?'checked':'' ?>>
                                            <label for="sepSemicolon" class="mb-0 ml-2" style="cursor: pointer;">
                                                <?= $langdef['semicolontip'] ?>
                                            </label>
                                        </div>
                                    </div>
                                </fieldset>
                                <div class="previewbox">
                                    <button type="button" class="btn btn-outline-primary btn-lg" onclick="pickFileClick()">
                                        <i class="mdi mdi-file-upload"></i> <?= $langdef['choosetip'] ?>
                                    </button>
                                    <div id="appprogbar1" class="pbarbox" style="visibility: hidden;">
                                        <div class="pbar"></div>
                                    </div>
                                </div>
                            </div>
                            <input id="ifilepicker" style="visibility: hidden;" type="file" name="csvfile"
                                accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel" required onchange="ifilepickerChanged(this.value)">
                        </form>
                        <div>
                            <?php if ($withdata>0) : ?>
                            <div id="icsvvisor" class="table-responsive">
                                <!-- act 4 -->
                                <table id="infoTable" class="table">
                                    <thead>
                                        <tr>
                                            <th style="width: 150px;" class="text-nowrap"><?= $langdef['product_name'] ?></th>
                                            <th class="text-nowrap"><?= $langdef['category_list'] ?></th>
                                            <th class="text-nowrap"><?= $langdef['sku'] ?></th>
                                            <th class="text-nowrap"><?= $langdef['barcode'] ?></th>
                                            <th class="text-nowrap"><?= $langdef['unit_type'] ?></th>
                                            <th class="text-nowrap"><?= $langdef['sale_price'] ?></th>
                                            <th class="text-nowrap"><?= $langdef['fob_price'] ?></th>
                                            <th class="text-nowrap"><?= $langdef['purchase_price'] ?></th>
                                            <th class="text-nowrap"><?= $langdef['stock'] ?></th>
                                            <th class="text-nowrap"><?= $langdef['moq'] ?></th>
                                            <th class="text-nowrap"><?= $langdef['notify_moq'] ?></th>
                                            <th class="text-nowrap"><?= $langdef['tags'] ?></th>
                                            <th class="text-nowrap"><?= $langdef['location'] ?></th>
                                            <th class="text-nowrap"><?= $langdef['descriptions'] ?></th>
                                            <th class="text-nowrap"><?= $langdef['status'] ?></th>
                                            <th class="text-nowrap">errflag</th>
                                            <th class="text-nowrap">rawindex</th>
                                            <th class="text-nowrap">sscol</th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                        <?php foreach ($datalines as $line):?>
                                        <tr <?= 'id="row'.trim($line[16]).'"' ?>
                                            class="<?php echo ($line[15]=='0' ? 'iinfotable_row' : ''); ?>">
                                            <td><?php echo $line[0];?></td>
                                            <td><?= $line[1] ?></td>
                                            <td><?= $line[2] ?></td>
                                            <td><?= $line[3] ?></td>
                                            <td><?= (is_array($line[4]) && !empty($line[4]['default'])) ? '' : (is_array($line[4]) ? ($line[4]['unit'] ?? '') : (string)$line[4]) ?></td>
                                            <td style="text-align: right;"><?= $line[5] ?></td>
                                            <td style="text-align: right;"><?= $line[6] ?></td>
                                            <td style="text-align: right;"><?= $line[7] ?></td>
                                            <td style="text-align: center;"><?= $line[8] ?></td>
                                            <td><?= $line[9] ?></td>
                                            <td><?= $line[10] ?></td>
                                            <td><?= $line[11] ?></td>
                                            <td><?= $line[12] ?></td>
                                            <td><?= $line[13] ?></td>
                                            <td><?= $line[14] ?></td>
                                            <td><?= $line[15] ?></td>
                                            <td><?= $line[16] ?></td>
                                            <td><?= $line[17] ?></td>
                                        </tr>
                                        <?php endforeach;?>
                                    </tbody>
                                </table>
                            </div>
                            <div id="ivalidsum" class="mt-4">
                                <div class="card p-4 mb-4 shadow-sm">
                                    <h5 class="card-title mb-4">
                                        <i class="mdi mdi-chart-box"></i> Resumen de Importación
                                    </h5>
                                    <div class="row mb-2">
                                        <div class="col-md-8">
                                            <strong><?= $langdef['tlscanned'] ?>:</strong>
                                        </div>
                                        <div id="tsl_line" class="col-md-4 text-right font-weight-bold">
                                            <?= $statlines['tsl'] ?>
                                        </div>
                                    </div>
                                    <div class="row mb-2 iinfosum_redline">
                                        <div class="col-md-8">
                                            <strong><?= $langdef['tlerrors'] ?>:</strong>
                                        </div>
                                        <div id="tel_line" class="col-md-4 text-right font-weight-bold">
                                            <?= $statlines['tel'] ?>
                                        </div>
                                    </div>
                                    <div class="row iinfosum_greenline">
                                        <div class="col-md-8">
                                            <strong><?= $langdef['tlready'] ?>:</strong>
                                        </div>
                                        <div id="tal_line" class="col-md-4 text-right font-weight-bold">
                                            <?= $statlines['tal'] ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <form id="iformimport" class="forms-sample"
                                action="<?= company_url('products/csvImport/success') ?>" method="post"
                                enctype="multipart/form-data">
                                <div class="d-flex align-items-center justify-content-between mt-4 pt-3 border-top">
                                    <div class="d-flex gap-2">
                                        <button id="btn-import" type="submit"
                                            class="btn btn-success btn-lg shadow-sm">
                                            <i class="mdi mdi-upload"></i> <?= $langdef['bnimport'] ?>
                                        </button>
                                        <a class="btn btn-danger btn-lg shadow-sm"
                                            href="<?= company_url('products') ?>">
                                            <i class="mdi mdi-close"></i> <?= $langdef['bncancel'] ?>
                                        </a>
                                    </div>
                                    <div id="appprogbar2" class="pbarbox2" style="visibility: hidden;">
                                        <div class="pbar"></div>
                                    </div>
                                </div>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="iformDlgEdit" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header iinfodlgback">
                        <h5 class="modal-title font-weight-bold" id="exampleModalLongTitle">
                            <i class="mdi mdi-pencil"></i> <?= $langdef['editTitle'] ?>
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <!-- act 5 -->
                        <form id="form-validate" action="<?= company_url('products/csvImport/validate') ?>" method="post">
                            <div class="row">
                                <!-- first row -->
                                <div class="col">
                                    <div id="ifg_product_name" class="form-group">
                                        <label for="ic_product_name"><?= $langdef['product_name'] ?></label>
                                        <textarea name="product_name" class="form-control p-2" id="ic_product_name"
                                            placeholder="Product name" required rows="2"></textarea>
                                    </div>
                                </div>
                                <div class="col">
                                    <div id="ifg_categ_list" class="form-group">
                                        <label for="ic_categ_list"><?= $langdef['category_list'] ?></label>
                                        <input name="categ_list" type="text" class="form-control" id="ic_categ_list"
                                            placeholder="Category list" required>
                                    </div>
                                </div>
                            </div><!-- end of first row -->
                            <div class="row">
                                <!-- second row -->
                                <div class="col">
                                    <div id="ifg_sku" class="form-group">
                                        <label for="ic_sku"><?= $langdef['sku'] ?></label>
                                        <input name="sku" type="text" class="form-control" id="ic_sku" placeholder="Sku"
                                            required>
                                    </div>
                                </div>
                                <div class="col">
                                    <div id="ifg_barcode" class="form-group">
                                        <label for="ic_barcode"><?= $langdef['barcode'] ?></label>
                                        <input name="barcode" type="text" class="form-control" id="ic_barcode"
                                            placeholder="Barcode" required>
                                    </div>
                                </div>
                                <div class="col">
                                    <div id="ifg_unit_type" class="form-group">
                                        <label for="ic_unit_type"><?= $langdef['unit_type'] ?></label>
                                        <input name="unit_type" type="text" class="form-control" id="ic_unit_type"
                                            placeholder="Unit type" required>
                                    </div>
                                </div>
                            </div><!-- end of second row -->
                            <div class="row">
                                <!-- third row -->
                                <div class="col">
                                    <div id="ifg_sale_price" class="form-group">
                                        <label for="ic_sale_price"
                                            class="pt-1 pl-1"><?= $langdef['sale_price'] ?></label>
                                        <input name="sale_price" type="text" class="form-control inputmask_decimal"
                                            id="ic_sale_price" placeholder="Sale price" maxlength="15" required>
                                    </div>
                                </div>
                                <div class="col">
                                    <div id="ifg_fob_price" class="form-group">
                                        <label for="ic_fob_price" class="pt-1 pl-1"><?= $langdef['fob_price'] ?></label>
                                        <input name="fob_price" type="text" class="form-control inputmask_decimal"
                                            id="ic_fob_price" placeholder="Fob price" maxlength="15" required>
                                    </div>
                                </div>
                                <div class="col">
                                    <div id="ifg_purchase_price" class="form-group">
                                        <label for="ic_purchase_price"
                                            class="pt-1 pl-1"><?= $langdef['purchase_price'] ?></label>
                                        <input name="purchase_price" type="text" class="form-control inputmask_decimal"
                                            id="ic_purchase_price" placeholder="Purchase price" maxlength="15" required>
                                    </div>
                                </div>
                            </div><!-- end of third row -->
                            <div class="row">
                                <!-- fourth row -->
                                <div class="col">
                                    <div id="ifg_stock" class="form-group">
                                        <label for="ic_stock"><?= $langdef['stock'] ?></label>
                                        <input name="stock" type="number" value="0" class="form-control" id="ic_stock"
                                            placeholder="" maxlength="10" required>
                                    </div>
                                </div>
                                <div class="col">
                                    <div id="ifg_moq" class="form-group">
                                        <label for="ic_moq"><?= $langdef['moq'] ?></label>
                                        <input name="moq" type="number" value="0" class="form-control" id="ic_moq"
                                            placeholder="" maxlength="10" required>
                                    </div>
                                </div>
                                <div class="col">
                                    <div id="ifg_notimoq" class="form-group">
                                        <label for="ic_notimoq"><?= $langdef['notify_moq'] ?></label>
                                        <input name="notimoq" type="number" value="0" class="form-control"
                                            id="ic_notimoq" placeholder="" maxlength="10" required>
                                    </div>
                                </div>
                            </div><!-- end of fourth row -->
                            <div class="row">
                                <!-- fifth row -->
                                <div class="col">
                                    <div id="ifg_tags" class="form-group">
                                        <label for="ic_tags"><?= $langdef['tags'] ?></label>
                                        <input name="tags" type="text" class="form-control" id="ic_tags" placeholder=""
                                            maxlength="80" required>
                                    </div>
                                </div>
                                <div class="col">
                                    <div id="ifg_location" class="form-group">
                                        <label for="ic_location"><?= $langdef['location'] ?></label>
                                        <input name="location" type="text" class="form-control" id="ic_location"
                                            placeholder="" maxlength="45" required>
                                    </div>
                                </div>
                                <div class="col">
                                    <div id="ifg_descrips" class="form-group">
                                        <label for="ic_descrips"><?= $langdef['descriptions'] ?></label>
                                        <input name="descrips" type="text" class="form-control" id="ic_descrips"
                                            placeholder="" maxlength="80" required>
                                    </div>
                                </div>
                            </div><!-- end of fifth row -->
                            <div class="row">
                                <div class="col">
                                    <div id="ifg_status" class="form-group">
                                        <label for="ic_status"><?= $langdef['status'] ?></label>
                                        <input name="status" type="text" class="form-control error_gb" id="ic_status"
                                            placeholder="" maxlength="5" required>
                                    </div>
                                </div>
                                <div class="col">
                                </div>
                                <div class="col">
                                </div>
                            </div>
                        </form>
                        <div id="ifdlg_err" class="alert alert-danger" role="alert">
                            <div id="ifdlg_msg"></div>
                        </div>
                    </div>
                    <div class="modal-footer border-top">
                        <button type="button" class="btn btn-success shadow-sm"
                            onclick="iformdlgSaveChanges()">
                            <i class="mdi mdi-content-save"></i> <?= $langdef['editSaveBtn'] ?>
                        </button>
                        <button type="button" class="btn btn-danger shadow-sm"
                            data-dismiss="modal">
                            <i class="mdi mdi-close"></i> <?= $langdef['editCloseBtn'] ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$csvLang = isset($langdef) && is_array($langdef) ? $langdef : (array) ($langdef ?? []);
?>
<!-- Modal de resultados de importación (textos: company-csvf en api/lang) -->
<div class="modal fade" id="modalImportResults" tabindex="-1" role="dialog" aria-labelledby="modalImportResultsLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="modalImportResultsLabel">
                    <i class="mdi mdi-check-circle"></i> <?= htmlspecialchars($csvLang['import_results_title'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="<?= htmlspecialchars($csvLang['import_results_aria_close'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row text-center mb-4">
                    <div class="col-md-4">
                        <div class="card border-primary">
                            <div class="card-body">
                                <h3 class="text-primary" id="totalRegistros">0</h3>
                                <p class="mb-0"><strong><?= htmlspecialchars($csvLang['import_results_total_records'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-success">
                            <div class="card-body">
                                <h3 class="text-success" id="registrosCargados">0</h3>
                                <p class="mb-0"><strong><?= htmlspecialchars($csvLang['import_results_records_loaded'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-danger">
                            <div class="card-body">
                                <h3 class="text-danger" id="registrosNoCargados">0</h3>
                                <p class="mb-0"><strong><?= htmlspecialchars($csvLang['import_results_records_not_loaded'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="errorsList" style="display: none;">
                    <h6 class="text-danger"><i class="mdi mdi-alert-circle"></i> <?= htmlspecialchars($csvLang['import_results_errors_heading'] ?? '', ENT_QUOTES, 'UTF-8') ?></h6>
                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th><?= htmlspecialchars($csvLang['import_results_th_row'] ?? '', ENT_QUOTES, 'UTF-8') ?></th>
                                    <th><?= htmlspecialchars($csvLang['import_results_th_error'] ?? '', ENT_QUOTES, 'UTF-8') ?></th>
                                </tr>
                            </thead>
                            <tbody id="errorsTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= htmlspecialchars($csvLang['import_results_close'] ?? '', ENT_QUOTES, 'UTF-8') ?></button>
                <a href="<?= company_url('products') ?>" class="btn btn-primary">
                    <i class="mdi mdi-arrow-left"></i> <?= htmlspecialchars($csvLang['import_results_back_products'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </a>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">

var tbl;
var fldsep = ';';
var selerrcolss = ''; //selected error columns ss
var selrowdata;
var selrowobj;
var langid = <?= $langid ?>;
var errflag = <?= $errflag ?>;
var errmsg = "<?= $errmsg ?>";
const text_importar = "<?=  isset($langdef['bnimport'])?$langdef['bnimport']:''?>"
// Nombres de columnas para el detalle de errores (índice -> etiqueta)
var errorColumnLabels = {
    0: "<?= isset($langdef['product_name']) ? addslashes($langdef['product_name']) : 'Nombre producto' ?>",
    1: "<?= isset($langdef['category_list']) ? addslashes($langdef['category_list']) : 'Categoría' ?>",
    2: "<?= isset($langdef['sku']) ? addslashes($langdef['sku']) : 'SKU' ?>",
    3: "<?= isset($langdef['barcode']) ? addslashes($langdef['barcode']) : 'Código de barras' ?>",
    4: "<?= isset($langdef['unit_type']) ? addslashes($langdef['unit_type']) : 'Unidad' ?>",
    5: "<?= isset($langdef['sale_price']) ? addslashes($langdef['sale_price']) : 'Precio venta' ?>",
    6: "<?= isset($langdef['fob_price']) ? addslashes($langdef['fob_price']) : 'FOB' ?>",
    7: "<?= isset($langdef['purchase_price']) ? addslashes($langdef['purchase_price']) : 'Precio compra' ?>",
    8: "<?= isset($langdef['stock']) ? addslashes($langdef['stock']) : 'Stock' ?>",
    9: "<?= isset($langdef['moq']) ? addslashes($langdef['moq']) : 'MOQ' ?>",
    10: "<?= isset($langdef['notify_moq']) ? addslashes($langdef['notify_moq']) : 'Notif. MOQ' ?>",
    11: "<?= isset($langdef['tags']) ? addslashes($langdef['tags']) : 'Etiquetas' ?>",
    12: "<?= isset($langdef['location']) ? addslashes($langdef['location']) : 'Ubicación' ?>",
    13: "<?= isset($langdef['descriptions']) ? addslashes($langdef['descriptions']) : 'Descripción' ?>",
    14: "<?= isset($langdef['status']) ? addslashes($langdef['status']) : 'Estado' ?>"
};

console.log(errmsg)

$(document).ready(function() {

    tbl = $('#infoTable').DataTable({
        "ordering": false,
        "language": language
    });
    showpbar(false);
    showpbar2(false);
    setTimeout(() => {
        showpbar(false);
    }, 3000);

    // act 2
    $('#infoTable tbody').on('click', 'tr', function() {
        let trowidx = tbl.row(this).index(); //zero based
        selrowobj = tbl.row(this);
        selrowdata = tbl.row(this).data();
        itRowClick(trowidx, selrowdata);

    });
    show_system_error();
});

function show_system_error() {
    if (errflag == 1) {
        errmsg_show(true, errmsg);
    }
}


function ifilepickerChanged(any) {
    let finput = document.getElementById('ifilepicker');
    if (any.length < 1) return;
    let fpath = finput.value;
    var okfiletypes = /(.xlsx|.XLSX|.xls|.XLS)$/i;
    if (!okfiletypes.exec(fpath)) {
        finput.value = "";
        let mspa = "¡Debe seleccionar un archivo con extensión CSV !";
        let meng = "You must choose a file with CSV extension !";
        errmsg_show(true, (lang_id == 1 ? meng : mspa));
        return false;
    } else {
        if (finput.files && finput.files[0]) {
            let frm = document.getElementById('frmcsvfile');
            frm.submit();
            showpbar(true);
            setTimeout(() => {
                showpbar(false);
            }, 2000);
        }
    }
    return true;
}

//action import products
$(document).on('submit', '#iformimport', function(e) {
    e.preventDefault();
    var $table = $('#infoTable');
    var tblApi = null;
    // Priorizar la variable global tbl (se asigna en document.ready cuando la grilla existe)
    if (typeof tbl !== 'undefined' && tbl) {
        tblApi = tbl;
    } else if ($table.length && $.fn.DataTable && typeof $.fn.DataTable.isDataTable === 'function' && $.fn.DataTable.isDataTable($table[0])) {
        tblApi = $table.DataTable();
    }
    var temparray = [];
    var boton_import = $("#btn-import");

    var errorDetails = [];
    if (tblApi) {
        var tdataRaw = tblApi.rows({ page: 'all' }).data();
        var tdata = (tdataRaw && typeof tdataRaw.toArray === 'function') ? tdataRaw.toArray() : tdataRaw;
        for (var rw = 0; rw < (tdata ? tdata.length : 0); rw++) {
            var row = Array.isArray(tdata) ? tdata[rw] : (tdata && tdata[rw]);
            if (row && row[15] == '1') {
                temparray.push(row);
            } else if (row && row[15] == '0' && row[17]) {
                errorDetails.push({ line: row[16], name: (row[0] || '').toString().substring(0, 30), cols: row[17] });
            }
        }
    }
    if (temparray.length === 0 && $table.length && $table.find('tbody tr').length > 0) {
        $table.find('tbody tr').each(function() {
            var row = [];
            $(this).find('td').each(function() { row.push($(this).text().trim()); });
            if (row.length >= 16 && row[15] == '1') {
                temparray.push(row);
            } else if (row.length >= 18 && row[15] == '0' && row[17]) {
                errorDetails.push({ line: row[16], name: (row[0] || '').toString().substring(0, 30), cols: row[17] });
            }
        });
    }

    if (temparray.length === 0 && (!$table.length || $table.find('tbody tr').length === 0)) {
        if (typeof alert_func === 'function') {
            alert_func(["Cargue primero un archivo CSV para importar.", "error", "#A5DC86"]);
        } else {
            alert("Cargue primero un archivo CSV para importar.");
        }
        return;
    }

    if (temparray.length === 0) {
        var noValidMsg = "No hay registros válidos para importar. Corrija los errores en la grilla e intente de nuevo.";
        if (errorDetails.length > 0) {
            var lines0 = [];
            for (var i = 0; i < errorDetails.length; i++) {
                var d0 = errorDetails[i];
                var colIndices0 = (d0.cols || '').toString().split(';').filter(Boolean);
                var colNames0 = colIndices0.map(function(idx) { return errorColumnLabels[idx] || "Columna " + idx; });
                lines0.push("Fila " + d0.line + (d0.name ? " (\"" + d0.name + "\")" : "") + ": " + colNames0.join(", "));
            }
            noValidMsg += "\n\nDetalle:\n" + lines0.join("\n");
        }
        if (typeof alert_func === 'function') {
            alert_func([noValidMsg, "error", "#A5DC86"]);
        } else {
            alert(noValidMsg);
        }
        return;
    }

    var input = document.createElement("input");
    input.setAttribute("type", "hidden");
    input.setAttribute("name", "rawdata");
    input.setAttribute("value", JSON.stringify(temparray));

    var cantidad_errores = parseInt($('#tel_line').text(), 10) || 0;
    if (cantidad_errores !== 0) {
        var infoMsg = "Se importarán solo los " + temparray.length + " registro(s) válidos. " + cantidad_errores + " fila(s) con errores no se importarán.";
        if (errorDetails.length > 0) {
            var lines = [];
            for (var i = 0; i < errorDetails.length; i++) {
                var d = errorDetails[i];
                var colIndices = (d.cols || '').toString().split(';').filter(Boolean);
                var colNames = colIndices.map(function(idx) { return errorColumnLabels[idx] || "Columna " + idx; });
                lines.push("Fila " + (d.line) + (d.name ? " (\"" + d.name + "\")" : "") + ": " + colNames.join(", "));
            }
            infoMsg += "\n\nFilas omitidas:\n" + lines.join("\n");
        }
        if (typeof alert_func === 'function') {
            alert_func([infoMsg, "warning", "#f0ad4e"]);
        } else {
            alert(infoMsg);
        }
    }

    var form = $('#iformimport');
    form.append(input);
    $.ajax({
        url: form.attr('action'),
        data: form.serializeArray(),
        type: 'post',
        dataType: 'json',
        beforeSend: function() {
            boton_import.prop('disabled', true);
            boton_import.html(
                `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ${text_importar}`
            );
        },
        success: function(response) {
            boton_import.html(text_importar);
            boton_import.prop('disabled', false);
            if (response.success) {
                $('#totalRegistros').text(response.total || 0);
                $('#registrosCargados').text(response.loaded || 0);
                $('#registrosNoCargados').text(response.notLoaded || 0);

                if (response.errors && response.errors.length > 0) {
                    var errorsHtml = '';
                    response.errors.forEach(function(error) {
                        var rowLabel = (error.row || '');
                        if (error.sku) {
                            rowLabel += ' (' + error.sku + ')';
                        }
                        errorsHtml += '<tr><td>' + rowLabel + '</td><td>' + (error.error || '') + '</td></tr>';
                    });
                    $('#errorsTableBody').html(errorsHtml);
                    $('#errorsList').show();
                } else {
                    $('#errorsTableBody').html('');
                    $('#errorsList').hide();
                }

                $('#modalImportResults').modal('show');
            } else if (typeof alert_func === 'function') {
                alert_func([(response.msg || ''), 'error', '#A5DC86']);
            }
        },
        error: function(xhr) {
            boton_import.html(text_importar);
            boton_import.prop('disabled', false);
            var errorMsg = '';
            if (xhr.responseJSON && xhr.responseJSON.msg) {
                errorMsg = xhr.responseJSON.msg;
            }
            if (typeof alert_func === 'function') {
                alert_func([errorMsg, 'error', '#A5DC86']);
            }
        }
    });
})


function pickFileClick() {
    document.getElementById('ifilepicker').click();
}

function sepchanged(opidx) {
    if (opidx == 0) {
        document.getElementById('sepComma').click();
        fldsep = ',';
    } else {
        document.getElementById('sepSemicolon').click();
        fldsep = ';';
    }
}

function showpbar(bval) {
    document.getElementById('appprogbar1').style.visibility = bval ? 'visible' : 'hidden';
}

function showpbar2(bval) {
    let ctl = document.getElementById('appprogbar2');
    if (ctl != null) {
        ctl.style.visibility = bval ? 'visible' : 'hidden';
    }
}

function errmsg_show(bval, anymsg) {
    $('#errmsgBox').removeClass('hide_msgbox');
    $('#errmsgBox').addClass('show_msgbox');
    $('#errmsgBox').text(anymsg);
}

// act 3
// clases de errores para el modal
function ifg_reset_css() {
    $('#ifg_product_name').removeClass('error_bg');
    $('#ifg_categ_list').removeClass('error_bg');
    $('#ifg_sku').removeClass('error_bg');
    $('#ifg_barcode').removeClass('error_bg');
    $('#ifg_unit_type').removeClass('error_bg');
    $('#ifg_sale_price').removeClass('error_bg_orange');
    $('#ifg_fob_price').removeClass('error_bg_orange');
    $('#ifg_purchase_price').removeClass('error_bg_orange');
    $('#ifg_stock').removeClass('error_bg');
    $('#ifg_notimoq').removeClass('error_bg');
    $('#ifg_tags').removeClass('error_bg');
    $('#ifg_location').removeClass('error_bg');
    $('#ifg_descrips').removeClass('error_bg');
    $('#ifg_status').removeClass('error_bg');
}

//funcion al presionar click en la tabla act 1
function itRowClick(tblrowidx, rowinfo) {
    console.log(rowinfo)
    if (rowinfo[15] == '1') return;
    ifg_reset_css();
    selerrcolss = rowinfo[17]; //al ultima fila muestra la columnas con errores en el datatable como los vacios
    let errcols = rowinfo[17].split(';');

    for (let i = 0; i < errcols.length; i++) {
        let colidx = errcols[i];
        switch (colidx) {
            case '0':
                $('#ifg_product_name').addClass('error_bg');
                break;
            case '1':
                $('#ifg_categ_list').addClass('error_bg');
                break;
            case '2':
                $('#ifg_sku').addClass('error_bg');
                break;
            case '3':
                $('#ifg_barcode').addClass('error_bg');
                break;
            case '4':
                $('#ifg_unit_type').addClass('error_bg');
                break;
            case '5':
                $('#ifg_sale_price').addClass('error_bg_orange');
                break;
            case '6':
                $('#ifg_fob_price').addClass('error_bg_orange');
                break;
            case '7':
                $('#ifg_purchase_price').addClass('error_bg_orange');
                break;
            case '8':
                $('#ifg_stock').addClass('error_bg');
                break;
            case '9':
                $('#ifg_moq').addClass('error_bg');
                break;
            case '10':
                $('#ifg_notimoq').addClass('error_bg');
                break;
            case '11':
                $('#ifg_tags').addClass('error_bg');
                break;
            case '12':
                $('#ifg_location').addClass('error_bg');
                break;
            case '13':
                $('#ifg_descrips').addClass('error_bg');
                break;
            case '14':
                $('#ifg_status').addClass('error_bg');
                break;
        }
    }

    let unit_defecto = '<?= (isset($unit_defecto) && is_object($unit_defecto) && isset($unit_defecto->unit)) ? addslashes($unit_defecto->unit) : '' ?>'
    console.log(unit_defecto)
    // reemplazamos los valores que vienen de la fila de la tabla al modal
    $('#ic_product_name').val(rowinfo[0]);
    $('#ic_categ_list').val(rowinfo[1]);
    $('#ic_sku').val(rowinfo[2]);
    $('#ic_barcode').val(rowinfo[3]);
    var u4 = rowinfo[4];
    if (u4 && typeof u4 === 'object' && u4.unit !== undefined) u4 = u4.unit;
    $('#ic_unit_type').val(u4 == '' || u4 == null ? unit_defecto : u4);
    $('#ic_sale_price').val(rowinfo[5] == '' ? '0.00' : rowinfo[5]);
    $('#ic_fob_price').val(rowinfo[6] == '' ? '0.00' : rowinfo[6]);
    $('#ic_purchase_price').val(rowinfo[7] == '' ? '0.00' : rowinfo[7]);
    $('#ic_stock').val((rowinfo[8]) ? rowinfo[8] : 0);
    $('#ic_moq').val((rowinfo[9]) ? rowinfo[9] : 0);
    $('#ic_notimoq').val((rowinfo[10]) ? rowinfo[10] : 0);
    $('#ic_tags').val(rowinfo[11]);
    $('#ic_location').val(rowinfo[12]);
    $('#ic_descrips').val(rowinfo[13]);
    $('#ic_status').val(rowinfo[14]);
    $("#iformDlgEdit").modal('show');
    $('#iformDlgEdit .inputmask_decimal').inputmask('decimal', {
        rightAlign: false
    });
    $('#ifdlg_err').hide();
}

function isNumeric(str) {
    if (typeof str != "string") return false;
    return !isNaN(str) && !isNaN(parseFloat(str));
}

//VALIDA LOS CAMBIOS DEL FORMULARIO ANTES DE ENVIARLO
function showAlert(errormsg) {
    $('#ifdlg_err').show();
    $('#ifdlg_msg').text(errormsg);
    setTimeout(() => {
        $('#ifdlg_err').hide();
    }, 3000);
    return;
}


function iformdlgSaveChanges() {
    const form = $('#form-validate')
    let errcols = selerrcolss.split(';');
    let errmsg = "";

    $.ajax({
        url: form.attr('action'),
        data: form.serializeArray(),
        type: 'post',
        dataType: 'json',
        success: function(response) {

            if (!response.success) {

                showAlert(response.msg)
                $('#' + response.input_name).addClass('error_bg')
                console.log($('#' + response.input_name))
                return
            }

            selrowdata[0] = $('#ic_product_name').val();
            selrowdata[1] = $('#ic_categ_list').val();
            selrowdata[2] = $('#ic_sku').val();
            selrowdata[3] = $('#ic_barcode').val();
            selrowdata[4] = $('#ic_unit_type').val();
            selrowdata[5] = $('#ic_sale_price').val();
            selrowdata[6] = $('#ic_fob_price').val();
            selrowdata[7] = $('#ic_purchase_price').val();
            selrowdata[8] = $('#ic_stock').val();
            selrowdata[9] = $('#ic_moq').val();
            selrowdata[11] = $('#ic_tags').val();
            selrowdata[12] = $('#ic_location').val();
            selrowdata[13] = $('#ic_descrips').val();
            selrowdata[14] = $('#ic_status').val();
            selrowdata[15] = '1'; //ok

            $("#iformDlgEdit").modal('hide');
            selrowobj.data(selrowdata).draw();
            let selx = '#row' + selrowdata[16];
            $(selx).removeClass('iinfotable_row');

            let ln_tsl = parseInt($('#tsl_line').text());
            let ln_tel = parseInt($('#tel_line').text());
            let ln_tal = parseInt($('#tal_line').text());

            $('#tel_line').text(ln_tel - 1);
            $('#tal_line').text(ln_tsl - (ln_tel - 1));

        }
    })

}


function changeLang(idx) {

    let ctEng = document.getElementById('langf-en');
    let ctSpa = document.getElementById('langf-spa');
    if (idx == 0) {
        ctSpa.classList.remove('lang_selec');
        ctEng.classList.add('lang_selec');
    } else {
        ctEng.classList.remove('lang_selec');
        ctSpa.classList.add('lang_selec');
    }

    let frm = document.getElementById('frmChangeLang');
    let langinput = document.createElement('input');
    langinput.setAttribute("type", "hidden");
    langinput.setAttribute("name", "languageid");
    langinput.setAttribute("value", idx);
    frm.appendChild(langinput);
    let usertypeinput = document.createElement('input');
    usertypeinput.setAttribute("type", "hidden");
    usertypeinput.setAttribute("name", "usertype");
    usertypeinput.setAttribute("value", "anonymous");
    frm.appendChild(usertypeinput);
    frm.submit();

}
</script>

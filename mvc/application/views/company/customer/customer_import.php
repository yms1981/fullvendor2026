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
    color: red;
    cursor: pointer;
}

.iinfodlgback {
    color: white;
    background: rgb(79, 10, 10);
    background: linear-gradient(90deg, rgba(79, 10, 10, 1) 0%, rgba(207, 33, 23, 1) 41%, rgba(252, 8, 38, 1) 98%);
}

.iinfosum_grayline {
    color: black;
}

.iinfosum_redline {
    color: red;
}

.iinfosum_greenline {
    color: darkgreen;
}

/* act  5 */
.error_bg {
    color: white;
    border-radius: 9px;
    background: rgb(255, 2, 2);
    background: linear-gradient(90deg, rgba(255, 2, 2, 1) 0%, rgba(247, 113, 113, 1) 41%, rgba(255, 220, 209, 1) 98%);
}


.error_bg_orange {
    color: white;
    border-radius: 9px;
    background: linear-gradient(108.5deg, rgba(231, 69, 54, 0.82) 11.2%, rgba(255, 181, 17, 0.82) 68%);

}

.fieldsep_op {
    cursor: pointer;
    width: fit-content;
    display: flex;
    /* background-position: center;
    background-size: contain; */
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
    margin: 12px;
    border-radius: 9px;
    background-color: #ce2128;
    width: 98%;
    height: fit-content;
    padding: 5px;
    color: white;
    font-weight: 550;
}

.show_msgbox {
    visibility: visible;
}

.hide_msgbox {
    visibility: hidden;
}

.previewbox {
    display: flex;
    width: 25%;
}

.pbar {
    background: rgb(125, 33, 129);
    background: linear-gradient(90deg, rgba(125, 33, 129, 1) 0%, rgba(189, 115, 64, 1) 35%, rgba(244, 76, 4, 1) 100%);
    width: 0px;
    height: 16px;
    border-radius: 15px;
    animation: progres 4s infinite linear;
}

.pbarbox {
    width: 50%;
    padding-left: 4px;
    padding-top: 9px;
}

.pbarbox2 {
    width: 8rem;
    padding-left: 4px;
    padding-top: 9px;
}

a.nav-link {
    color: #212529;
    text-decoration: none;
    background-color: transparent;
}

.nav-pills .nav-link.active,
.nav-pills .show>.nav-link {
    color: #fff;
    background-color: #dc3545;
}

.form-group label {
    padding: 5px;
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

;
</style>
<div id="errmsgBox" class="error_msgbox hide_msgbox">
    <div></div>
</div>

<div class="container-fluid page-body-wrapper">
    <div class="main-panel">
        <div class="container-fluid pt-3">
            <div class="row">
                <div class="col-md-6">
                    <div class="page-header">
                        <h3 class="page-title"> <?= $langdef['import-customer'] ?> </h3>
                    </div>
                </div>
                <div class="col-md-6 text-right">
                    <a href="<?= import_template_download_url('customercsv.xlsx') ?>" download
                        class="btn btn-success mt-2 mt-sm-0 btn-icon-text"><i class="mdi mdi-download"></i>
                        <?= $langdef['sampledownload'] ?></a>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div>
                        <form id="frmcsvfile" action="<?= company_url('customers/csvImport') ?>" method="post"
                            enctype="multipart/form-data">
                            <div class="form-group">
                                <label><?= htmlspecialchars($langdef['import_select_file_excel'] ?? ($langdef['import_select_file'] ?? ''), ENT_QUOTES, 'UTF-8') ?>: <span class="color-red">*</span></label><br />
                                <div class="fileinput fileinput-new" data-provides="fileinput">
                                    <?php if (isset($filename)) echo $filename; ?></div>
                                <div class="clearfix margin-top-10 m-b-20 f12" style="display: block;">
                                    <span class="label label-danger"><?= htmlspecialchars($langdef['format_excel'] ?? ($langdef['formatcsv'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                </div>

                                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px; flex-wrap: wrap;">
                                    <label style="font-size: 15px; font-weight: 600; margin: 0;"><?= $langdef['fieldsep'] ?>:</label>
                                    <div style="display: flex; gap: 15px; align-items: center;">
                                        <div class="fieldsep_op" onclick="sepchanged(0)">
                                            <input type="radio" id="sepComma" name="fsep" value="0" <?php echo ($sep==0)?'checked':'' ?>>
                                            <label for="sepComma" style="margin-left: 5px; margin-bottom: 0; cursor: pointer;"><?= $langdef['commatip'] ?></label>
                                        </div>
                                        <div class="fieldsep_op" onclick="sepchanged(1)">
                                            <input type="radio" id="sepSemicolon" name="fsep" value="1" <?php echo ($sep==1)?'checked':'' ?>>
                                            <label for="sepSemicolon" style="margin-left: 5px; margin-bottom: 0; cursor: pointer;"><?= $langdef['semicolontip'] ?></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="previewbox">
                                    <div style="width: 50%;" class="btn btn-outline-primary" onclick="pickFileClick()">
                                        <?= $langdef['choosetip'] ?></div>
                                    <div id="appprogbar1" class="pbarbox">
                                        <div class="pbar"></div>
                                    </div>
                                </div>
                            </div>
                            <input id="ifilepicker" style="visibility: hidden;" type="file" name="csvfile"
                                accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel" required onchange="ifilepickerChanged(this.value)">
                        </form>
                        <div>
                            <?php if (isset($withdata) && $withdata > 0) : ?>
                            <?php if (isset($datalines) && !empty($datalines)) : ?>
                            <div id="icsvvisor" class="table-responsive">
                                <!-- act 4 -->
                                <table id="infoTable" class="table">
                                    <thead>
                                        <tr>
                                            <?php if (isset($csvHeaders) && !empty($csvHeaders)): ?>
                                                <?php foreach ($csvHeaders as $header): ?>
                                                    <th class="text-nowrap"><?= htmlspecialchars($header) ?></th>
                                                <?php endforeach; ?>
                                                <!-- Columnas de control -->
                                                <th class="text-nowrap">errflag</th>
                                                <th class="text-nowrap">rawindex</th>
                                                <th class="text-nowrap">sscol</th>
                                            <?php else: ?>
                                                <!-- Fallback si no hay cabeceras -->
                                                <th class="text-nowrap">Id ERP</th>
                                                <th class="text-nowrap">Name</th>
                                                <th class="text-nowrap">Businessname</th>
                                                <th class="text-nowrap">Tax ID</th>
                                                <th class="text-nowrap">Discount</th>
                                                <th class="text-nowrap">Payment Terms</th>
                                                <th class="text-nowrap">Group</th>
                                                <th class="text-nowrap">Email</th>
                                                <th class="text-nowrap">Phone</th>
                                                <th class="text-nowrap">Cell Phone</th>
                                                <th class="text-nowrap">Seller Asigned</th>
                                                <th class="text-nowrap">Notes</th>
                                                <th class="text-nowrap">Commercial Address</th>
                                                <th class="text-nowrap">Commercial Delivery Address</th>
                                                <th class="text-nowrap">Commercial Country</th>
                                                <th class="text-nowrap">Commercial State</th>
                                                <th class="text-nowrap">Commercial City</th>
                                                <th class="text-nowrap">Commercial Zone</th>
                                                <th class="text-nowrap">Commercial Zip Code</th>
                                                <th class="text-nowrap">Dispatch Address</th>
                                                <th class="text-nowrap">Dispatch Delivery Address</th>
                                                <th class="text-nowrap">Dispatch Country</th>
                                                <th class="text-nowrap">Dispatch State</th>
                                                <th class="text-nowrap">Dispatch City</th>
                                                <th class="text-nowrap">Dispatch Zone</th>
                                                <th class="text-nowrap">Dispatch Zip Code</th>
                                                <th class="text-nowrap">Dispatch Notes</th>
                                                <th class="text-nowrap">Email(Catalog)</th>
                                                <th class="text-nowrap">Customer Status</th>
                                                <th class="text-nowrap">errflag</th>
                                                <th class="text-nowrap">rawindex</th>
                                                <th class="text-nowrap">sscol</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($datalines as $line):?>
                                        <?php 
                                            // Obtener el índice de rawindex (penúltimo elemento)
                                            $rawIndex = isset($line[count($line) - 2]) ? $line[count($line) - 2] : '';
                                            // Obtener errflag (antepenúltimo elemento)
                                            $errflag = isset($line[count($line) - 3]) ? $line[count($line) - 3] : '';
                                            // Obtener sscol (último elemento)
                                            $sscol = isset($line[count($line) - 1]) ? $line[count($line) - 1] : '';
                                            // Obtener solo los datos (sin los 3 últimos elementos de control)
                                            $dataColumns = array_slice($line, 0, -3);
                                        ?>
                                        <tr id="row<?= trim($rawIndex) ?>" 
                                            class="<?php echo ($errflag == '0' ? 'iinfotable_row' : ''); ?>">
                                            <?php foreach ($dataColumns as $colValue): ?>
                                                <td><?= htmlspecialchars($colValue) ?></td>
                                            <?php endforeach; ?>
                                            <!-- Columnas de control -->
                                            <td><?= htmlspecialchars($errflag) ?></td>
                                            <td><?= htmlspecialchars($rawIndex) ?></td>
                                            <td><?= htmlspecialchars($sscol) ?></td>
                                        </tr>
                                        <?php endforeach;?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                            <?php if (isset($statlines)): ?>
                            <div id="ivalidsum">
                                <div class="card p-3 mb-3 indigo">
                                    <blockquote class="blockquote mb-0 card-body">
                                        <div class="row iinfosum_grayline">
                                            <div class="col"><?= isset($langdef['tlscanned']) ? $langdef['tlscanned'] : 'Total Escaneadas' ?>:</div>
                                            <div id="tsl_line" class="col text-right"><?= isset($statlines['tsl']) ? $statlines['tsl'] : 0 ?></div>
                                            <div class="col"></div>
                                        </div>
                                        <div class="row iinfosum_redline">
                                            <div class="col"><?= isset($langdef['tlerrors']) ? $langdef['tlerrors'] : 'Total Errores' ?>:</div>
                                            <div id="tel_line" class="col text-right"><?= isset($statlines['tel']) ? $statlines['tel'] : 0 ?></div>
                                            <div class="col"></div>
                                        </div>
                                        <div class="row iinfosum_greenline">
                                            <div class="col"><?= isset($langdef['tlready']) ? $langdef['tlready'] : 'Total Listas' ?>:</div>
                                            <div id="tal_line" class="col text-right"><?= isset($statlines['tal']) ? $statlines['tal'] : 0 ?></div>
                                            <div class="col"></div>
                                        </div>
                                    </blockquote>
                                </div>
                            </div>
                            <?php endif; ?>
                            <form id="iformimport" class="forms-sample"
                                action="<?= company_url('customers/csvImportSuccess') ?>" method="post"
                                enctype="multipart/form-data">
                                <div class="d-flex align-items-center justify-content-between mt-4 pt-3 border-top">
                                    <div class="d-flex" style="gap: 10px;">
                                        <button id="btn-import" type="submit"
                                            class="btn btn-success btn-lg shadow-sm">
                                            <i class="mdi mdi-upload"></i> <?= $langid == 1 ? 'Import customers' : 'Importar clientes' ?>
                                        </button>
                                        <a class="btn btn-danger btn-lg shadow-sm"
                                            href="<?= company_url('customers') ?>">
                                            <i class="mdi mdi-close"></i> <?= isset($langdef['bncancel']) ? $langdef['bncancel'] : 'Cancelar' ?>
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
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header iinfodlgback">
                        <h5 class="modal-title" id="exampleModalLongTitle"><?= $langdef['editTitle'] ?></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="min-height: 500px;">
                        <!-- Formulario -->
                        <form id="form-validate" action="<?= company_url('customer/validate') ?>">
                            <ul class="nav nav-pills mb-3 border-bottom rounded" id="pills-tab" role="tablist">
                                <li class="nav-item">
                                    <a id="section1" class="nav-link active" id="pills-home-tab" data-toggle="pill"
                                        href="#pills-home" role="tab" aria-controls="pills-home"
                                        aria-selected="true"><strong><?= strtoupper($langcliente['dato_empresa']) ?></strong></a>
                                </li>
                                <li class="nav-item">
                                    <a id="section2" class="nav-link" id="pills-profile-tab" data-toggle="pill"
                                        href="#pills-profile" role="tab" aria-controls="pills-profile"
                                        aria-selected="false"><strong><?= strtoupper($langcliente['dato_comercial']) ?></strong></a>
                                </li>
                                <li class="nav-item">
                                    <a id="section3" class="nav-link" id="pills-contact-tab" data-toggle="pill"
                                        href="#pills-contact" role="tab" aria-controls="pills-contact"
                                        aria-selected="false"><strong><?= strtoupper($langcliente['dato_entrega']) ?></strong></a>
                                </li>
                            </ul>

                            <div class="tab-content border rounded p-2" id="pills-tabContent">
                                <!-- Datos de Empresa -->
                                <div class="tab-pane fade show active" id="pills-home" role="tabpanel"
                                    aria-labelledby="pills-home-tab">
                                    <div class="row">
                                        <div class="col-4">
                                            <div id="ifg_customer_name" class="form-group">
                                                <label for="ic_customer_name">Customer name</label>
                                                <textarea class="form-control p-2" name="customer_name"
                                                    id="ic_customer_name" placeholder="Customer name"
                                                    rows="2"></textarea>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div id="ifg_business_name" class="form-group">
                                                <label for="ic_business_name">Business Name</label>
                                                <input name="business_name" type="text" class="form-control"
                                                    id="ic_business_name" placeholder="Business Name">
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div id="ifg_tax_id" class="form-group">
                                                <label for="ic_tax_id">Tax ID</label>
                                                <input name="tax_id" type="text" class="form-control" id="ic_tax_id"
                                                    placeholder="Tax">
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div id="ifg_discount" class="form-group">
                                                <label for="ic_discount">Discount</label>
                                                <input name="discount" type="text" class="form-control" id="ic_discount"
                                                    placeholder="Discount">
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div id="ifg_terms_of_sales" class="form-group">
                                                <label for="ic_terms_of_sales">Terms of sales</label>
                                                <input name="term" type="text" class="form-control"
                                                    id="ic_terms_of_sales" placeholder="Terms of sales">
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div id="ifg_group_id" class="form-group">
                                                <label for="ic_group_id">Grupo</label>
                                                <input name="group" type="text" class="form-control" id="ic_group_id"
                                                    placeholder="Grupo">
                                            </div>
                                        </div>

                                        <div class="col-4">
                                            <div id="ifg_email" class="form-group">
                                                <label for="ic_email">Email</label>
                                                <input name="email" type="email" class="form-control" id="ic_email"
                                                    placeholder="Email">
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div id="ifg_phone" class="form-group">
                                                <label for="ic_phone">Phone</label>
                                                <input name="phone" type="text" class="form-control" id="ic_phone"
                                                    placeholder="Phone">
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div id="ifg_cell_phone" class="form-group">
                                                <label for="ic_cell_phone">Cell Phone</label>
                                                <input name="cell_phone" type="text" class="form-control"
                                                    id="ic_cell_phone" placeholder="Cell Phone">
                                            </div>
                                        </div>

                                        <div class="col-4">
                                            <div id="ifg_seller_assigned" class="form-group">
                                                <label for="ic_seller_assigned">Seller Assigned</label>
                                                <input name="seller_asigned" type="text" value="0" class="form-control"
                                                    id="ic_seller_assigned" placeholder="Seller Assigned">
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div id="ifg_catalog_emails" class="form-group">
                                                <label for="ic_catalog_emails">Catalog Emails (1=Yes 0=No)</label>
                                                <input type="number" value="0" class="form-control"
                                                    id="ic_catalog_emails" placeholder="Catalog Emails">
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div id="ifg_customer_status" class="form-group">
                                                <label for="ic_customer_status">Customer Status (1=enable
                                                    0=disable)</label>
                                                <input type="number" value="0" class="form-control"
                                                    id="ic_customer_status" placeholder="Customer Status">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Datos Comerciales -->
                                <div class="tab-pane fade" id="pills-profile" role="tabpanel"
                                    aria-labelledby="pills-profile-tab">
                                    <div class="row">
                                        <div class="col-4">
                                            <div id="ifg_commercial_address" class="form-group">
                                                <label for="ic_commercial_address">Commercial Address</label>
                                                <input type="text" value="0" class="form-control"
                                                    id="ic_commercial_address" placeholder="Commercial Address">
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div id="ifg_commercial_delivery_address" class="form-group">
                                                <label for="ic_commercial_delivery_address">Commercial Delivery
                                                    Address</label>
                                                <input type="text" value="0" class="form-control"
                                                    id="ic_commercial_delivery_address"
                                                    placeholder="Commercial Delivery Address">
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div id="ifg_commercial_country" class="form-group">
                                                <label for="ic_commercial_country">Commercial Country</label>
                                                <input type="text" value="0" class="form-control"
                                                    id="ic_commercial_country" placeholder="Commercial Country">
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div id="ifg_commercial_state" class="form-group">
                                                <label for="ic_commercial_state">Commercial State</label>
                                                <input type="text" value="0" class="form-control"
                                                    id="ic_commercial_state" placeholder="Commercial State">
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div id="ifg_commercial_city" class="form-group">
                                                <label for="ic_commercial_city">Commercial City</label>
                                                <input type="text" value="0" class="form-control"
                                                    id="ic_commercial_city" placeholder="Commercial City">
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div id="ifg_commercial_zone" class="form-group">
                                                <label for="ic_commercial_zone">Commercial Zone</label>
                                                <input type="text" value="0" class="form-control"
                                                    id="ic_commercial_zone" placeholder="Commercial Zone">
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div id="ifg_commercial_zip_code" class="form-group">
                                                <label for="ic_commercial_zip_code">Commercial Zip Code</label>
                                                <input type="text" value="0" class="form-control"
                                                    id="ic_commercial_zip_code" placeholder="Commercial Zip Code">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Datos de Entrega -->
                                <div class="tab-pane fade" id="pills-contact" role="tabpanel"
                                    aria-labelledby="pills-contact-tab">
                                    <div class="row">
                                        <div class="col-4">
                                            <div id="ifg_notes" class="form-group">
                                                <label for="ic_notes">Notes</label>
                                                <input type="text" value="0" class="form-control" id="ic_notes"
                                                    placeholder="Notes">
                                            </div>
                                        </div>

                                        <div class="col-4">
                                            <div id="ifg_dispatch_address" class="form-group">
                                                <label for="ic_dispatch_address">Dispatch Address</label>
                                                <input type="text" value="0" class="form-control"
                                                    id="ic_dispatch_address" placeholder="Dispatch Address">
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div id="ifg_dispatch_delivery_address" class="form-group">
                                                <label for="ic_dispatch_delivery_address">Dispatch Delivery
                                                    Address</label>
                                                <input type="text" value="0" class="form-control"
                                                    id="ic_dispatch_delivery_address"
                                                    placeholder="Dispatch Delivery Address">
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div id="ifg_dispatch_country" class="form-group">
                                                <label for="ic_dispatch_country">Dispatch Country</label>
                                                <input type="text" value="0" class="form-control"
                                                    id="ic_dispatch_country" placeholder="Dispatch Country">
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div id="ifg_dispatch_state" class="form-group">
                                                <label for="ic_dispatch_state">Dispatch State</label>
                                                <input type="text" value="0" class="form-control" id="ic_dispatch_state"
                                                    placeholder="Dispatch State">
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div id="ifg_dispatch_city" class="form-group">
                                                <label for="ic_dispatch_city">Dispatch City</label>
                                                <input type="text" value="0" class="form-control" id="ic_dispatch_city"
                                                    placeholder="Dispatch City">
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div id="ifg_dispatch_zone" class="form-group">
                                                <label for="ic_dispatch_zone">Dispatch Zone</label>
                                                <input type="text" value="0" class="form-control" id="ic_dispatch_zone"
                                                    placeholder="Dispatch Zone">
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div id="ifg_dispatch_zip_code" class="form-group">
                                                <label for="ic_dispatch_zip_code">Dispatch Zip Code</label>
                                                <input type="text" value="0" class="form-control"
                                                    id="ic_dispatch_zip_code" placeholder="Dispatch Zip Code">
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div id="ifg_dispatch_shipping_notes" class="form-group">
                                                <label for="ic_dispatch_shipping_notes">Dispatch Shipping Notes</label>
                                                <textarea type="text" value="0" class="form-control"
                                                    id="ic_dispatch_shipping_notes"
                                                    placeholder="Dispatch Shipping Notes"></textarea>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </form>
                        <!-- act 5 -->
                        <!-- <form>
                            <div class="row">



                            </div>
                        </form> -->
                        <div id="ifdlg_err" class="alert alert-danger" role="alert">
                            <div id="ifdlg_msg"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-success"
                            onclick="iformdlgSaveChanges()"><?= $langdef['editSaveBtn'] ?></button>
                        <button type="button" class="btn btn-danger"
                            data-dismiss="modal"><?= $langdef['editCloseBtn'] ?></button>
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
                <a href="<?= company_url('customers') ?>" class="btn btn-primary">
                    <i class="mdi mdi-arrow-left"></i> <?= htmlspecialchars($csvLang['import_results_back_customers'] ?? '', ENT_QUOTES, 'UTF-8') ?>
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
var lang_id = <?= $langid ?>;
var errflag = <?= $errflag ?>;
var errmsg = "<?= $errmsg ?>";
const btn_import = "<?= $langid == 1 ? 'Import customers' : (isset($lang_cliente['importar_clientes']) ? addslashes($lang_cliente['importar_clientes']) : 'Importar clientes') ?>"
// Mensajes de validación según idioma (1 = English, 2 = Spanish). Solo obligatorios: business_name y email
const msg_required_fields = <?= $langid == 1 ? '"Required fields: business name and email."' : '"Campos obligatorios: razón social (business name) y email."' ?>;
const msg_import_has_errors = <?= $langid == 1 ? '"Import has validation errors. Required fields: business name and email. Please correct the rows with errors."' : '"La importación presenta errores. Campos obligatorios: razón social (business name) y email. Corrija las filas con errores."' ?>;
const msg_no_data_to_import = <?= $langid == 1 ? '"Error: No data found to import"' : '"Error: No se encontraron datos para importar"' ?>;
const msg_import_error = <?= $langid == 1 ? '"Import error"' : '"Error en la importación"' ?>;
const msg_error_processing = <?= $langid == 1 ? '"Error processing import"' : '"Error al procesar la importación"' ?>;
// Etiquetas para detalle por fila/campo (1 = English, 2 = Spanish)
const label_row = <?= $langid == 1 ? '"Row"' : '"Fila"' ?>;
const label_missing = <?= $langid == 1 ? '"missing"' : '"falta"' ?>;
const label_business_name = <?= $langid == 1 ? '"business name"' : '"razón social (business name)"' ?>;
const label_email = <?= $langid == 1 ? '"email"' : '"email"' ?>;
// Lista explícita de campos obligatorios para el modal de validación
const label_required_fields_list = <?= $langid == 1
    ? json_encode("Required fields (all mandatory):\n• business name\n• email")
    : json_encode("Campos obligatorios (todos son obligatorios):\n• razón social (business name)\n• email") ?>;
// Explicación del error: qué falló, qué campos son obligatorios y qué hacer
const label_validation_errors = <?= $langid == 1
    ? json_encode("Import has validation errors.\n\nWhat's wrong: Every row must have the following fields filled in. Empty cells or invalid email format are not allowed.\n\nThe following rows have missing or invalid data:")
    : json_encode("La importación tiene errores de validación.\n\nQué falla: Cada fila debe tener los siguientes campos completos. No se permiten celdas vacías ni email inválido.\n\nLas siguientes filas tienen datos faltantes o inválidos:") ?>;

// Fallback si alert_func no está definido (p. ej. por orden de carga del layout)
if (typeof alert_func === 'undefined') {
    function alert_func(data) {
        var msg = Array.isArray(data) ? data[0] : data;
        if (typeof swal === 'function') {
            swal({ title: msg, text: '', icon: (data[1] || 'info'), button: { text: "OK", value: true, visible: true, className: "btn btn-primary" } });
        } else {
            alert(msg);
        }
    }
}

console.log(errmsg)
$(document).ready(function() {
    // Solo inicializar DataTable si la tabla existe
    if ($('#infoTable').length > 0) {
        tbl = $('#infoTable').DataTable({
            "ordering": false,
            "language": language
        });

        $('#infoTable tbody').on('click', 'tr', function() {
            let trowidx = tbl.row(this).index(); //zero based
            selrowobj = tbl.row(this);
            selrowdata = tbl.row(this).data();
            itRowClick(trowidx, selrowdata);
        });
    }
    
    showpbar(false);
    showpbar2(false);
    setTimeout(() => {
        showpbar(false);
    }, 3000);
    
    show_system_error();
});

function show_system_error() {
    if (errflag == 1) {
        errmsg_show(true, errmsg);
    }
}

// Construye mensaje de validación: fila y campo(s) faltante(s). Solo campos obligatorios: business_name (col 2), email (col 7)
function buildValidationDetailMessage() {
    if (typeof tbl === 'undefined' || !tbl || $('#infoTable').length === 0) return msg_import_has_errors;
    var lines = [];
    var tdata = tbl.rows().data();
    var colLabels = { '2': label_business_name, '7': label_email };
    for (var rw = 0; rw < tdata.length; rw++) {
        var row = tdata.rows(rw).data()[0];
        if (!row || row[29] == '1') continue; // fila sin errores
        var errcols = (row[31] || '').toString().split(';');
        var missing = [];
        for (var i = 0; i < errcols.length; i++) {
            if (colLabels[errcols[i]]) missing.push(colLabels[errcols[i]]);
        }
        if (missing.length > 0) {
            var rowNum = (parseInt(row[30], 10) || rw) + 1;
            lines.push(label_row + ' ' + rowNum + ': ' + label_missing + ' ' + missing.join(', '));
        }
    }
    if (lines.length === 0) return msg_import_has_errors;
    return label_validation_errors + '\n\n' + label_required_fields_list + '\n\n' + lines.join('\n');
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

$(document).on('submit', '#iformimport', function(e) {
    e.preventDefault()
    const boton_import = $("#btn-import")
    const html_before = boton_import.html()
    const form = $('#iformimport')
    const errores = $('#tel_line').text()
    $("#btn-import").prop('disabled', true);

    // Asegurar referencia a la tabla (por si tbl no se definió en document.ready)
    var tableToUse = tbl;
    if (typeof tableToUse === 'undefined' && $('#infoTable').length > 0) {
        try {
            tableToUse = $('#infoTable').DataTable();
        } catch (err) {
            console.warn('Import: DataTable no disponible', err);
        }
    }

    console.log('Import submit:', {
        tbl_defined: typeof tbl !== 'undefined',
        tableToUse_defined: typeof tableToUse !== 'undefined',
        infoTable_length: $('#infoTable').length,
        errores_count: errores
    });

    if (errores != '0') {
        boton_import.html(`${btn_import}`)
        boton_import.prop('disabled', false);
        return alert_func([buildValidationDetailMessage(), "error", "#A5DC86"]);
    }

    // Verificar que la tabla y DataTable existan
    if (typeof tableToUse === 'undefined' || !tableToUse || $('#infoTable').length === 0) {
        console.error('Import: No hay tabla. tbl=', typeof tbl, 'tableToUse=', typeof tableToUse, '#infoTable=', $('#infoTable').length);
        alert_func([msg_no_data_to_import, "error", "#A5DC86"]);
        boton_import.prop('disabled', false);
        boton_import.html(html_before);
        return false;
    }

    let tdata = tableToUse.rows().data();
    console.log('Import: total filas en tabla', tdata.length, '— muestra primera fila:', tdata.length ? tdata.rows(0).data()[0] : null);

    const temparray = [];
    let contador = 0;
    array_grupo = [];
    array_todosGrupos = [];
    const qty = 27
    for (let rw = 0; rw < tdata.length; rw++) {
        let row = tdata.rows(rw).data()[0];
        if (row[29] == '1' || row[29] === 1) {
            temparray.push(row);
        }
    }

    // Datos a importar: solo obligatorios business_name (col 2) y email (col 7) para el log
    var datosAImportar = temparray.map(function(row, idx) {
        return { fila: idx + 1, business_name: row[2], email: row[7], row_full: row };
    });
    console.log('Import: filas válidas (row[29]==1) a importar:', temparray.length, '— datos (business_name, email):', datosAImportar);

    for (var i = 0; i < temparray.length; i++) {

        contador = contador + 1;

        if (contador >= qty) {
            array_grupo.push(temparray[i]);
            array_todosGrupos.push(array_grupo)
            array_grupo = []
            contador = 0;

        } else {
            array_grupo.push(temparray[i]);
        }
    }

    if (array_todosGrupos.length == 0 || array_grupo.length > 0) {
        array_todosGrupos.push(array_grupo)
    }

    if (temparray.length === 0) {
        console.warn('Import: no hay filas válidas (todas tienen errores o la tabla está vacía). Total filas:', tdata.length);
        boton_import.prop('disabled', false);
        boton_import.html(html_before);
        return alert_func([msg_no_data_to_import, "error", "#A5DC86"]);
    }

    console.log('Import: lotes a enviar (array_todosGrupos):', array_todosGrupos.length, array_todosGrupos);
    const totalElementos = array_todosGrupos.length;


    $.each(array_todosGrupos, function(index, el) {

        const formData = new FormData();
        if (index === totalElementos - 1) {
            formData.append("last_register", true)
        }
        console.log(el)
        $.each(el, function(index, value) {
            // formData.append(`rawdata[${index}]`, JSON.stringify(value));
            $.each(value, function(ind, val) {
                formData.append(`rawdata[${index}][${ind}]`, val);
            })
        })

        $.ajax({
            url: form.attr('action'),
            type: 'post',
            data: formData,
            dataType: 'json',
            processData: false,
            contentType: false,
            beforeSend: function() {
                boton_import.prop('disabled', true);
                boton_import.html(
                    `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ${btn_import}`
                )
            },
            success: function(data) {
                if (data.success) {
                    if (index === totalElementos - 1) {
                        boton_import.prop('disabled', false);
                        boton_import.html(html_before);
                        
                        // Mostrar modal con resultados
                        $('#totalRegistros').text(data.total || 0);
                        $('#registrosCargados').text(data.loaded || 0);
                        $('#registrosNoCargados').text(data.notLoaded || 0);
                        
                        // Mostrar errores si los hay
                        if (data.errors && data.errors.length > 0) {
                            var errorsHtml = '';
                            data.errors.forEach(function(error) {
                                errorsHtml += '<tr><td>' + error.row + '</td><td>' + error.error + '</td></tr>';
                            });
                            $('#errorsTableBody').html(errorsHtml);
                            $('#errorsList').show();
                        } else {
                            $('#errorsList').hide();
                        }
                        
                        $('#modalImportResults').modal('show');
                    }
                } else {
                    boton_import.prop('disabled', false);
                    boton_import.html(html_before);
                    alert_func([msg_import_error, "error", "#A5DC86"]);
                }
            },
            error: function(xhr) {
                boton_import.prop('disabled', false);
                boton_import.html(html_before);
                var errorMsg = msg_error_processing;
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                }
                alert_func([errorMsg, "error", "#A5DC86"]);
            }
        })
    })
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
    $('#ifg_customer_name').removeClass('error_bg');
    $('#ifg_business_name').removeClass('error_bg');
    $('#ifg_tax_id').removeClass('error_bg');
    $('#ifg_discount').removeClass('error_bg');
    $('#ifg_terms_of_sales').removeClass('error_bg');
    $('#ifg_group_id').removeClass('error_bg');
    $('#ifg_email').removeClass('error_bg');
    $('#ifg_phone').removeClass('error_bg');
    $('#ifg_cell_phone').removeClass('error_bg');
    $('#ifg_seller_assigned').removeClass('error_bg');
    $('#ifg_notes').removeClass('error_bg');
    $('#ifg_commercial_address').removeClass('error_bg');

    $('#ifg_commercial_delivery_address').removeClass('error_bg');
    $('#ifg_commercial_country').removeClass('error_bg');
    $('#ifg_commercial_state').removeClass('error_bg');
    $('#ifg_commercial_city').removeClass('error_bg');
    $('#ifg_commercial_zone').removeClass('error_bg');
    $('#ifg_commercial_zip_code').removeClass('error_bg');
    $('#ifg_dispatch_address').removeClass('error_bg');
    $('#ifg_dispatch_delivery_address').removeClass('error_bg');
    $('#ifg_dispatch_country').removeClass('error_bg');

    $('#ifg_dispatch_state').removeClass('error_bg');
    $('#ifg_dispatch_city').removeClass('error_bg');
    $('#ifg_dispatch_zone').removeClass('error_bg');
    $('#ifg_dispatch_zip_code').removeClass('error_bg');
    $('#ifg_dispatch_shipping_notes').removeClass('error_bg');

    $('#ifg_catalog_emails').removeClass('error_bg');
    $('#ifg_customer_status').removeClass('error_bg');

}

//funcion al presionar click en la tabla act 1
function itRowClick(tblrowidx, rowinfo) {


    if (rowinfo[29] == '1') return;
    ifg_reset_css();
    selerrcolss = rowinfo[31]; //al ultima fila muestra la columnas con errores en el datatable como los vacios
    let errcols = rowinfo[31].split(';');
    console.log(errcols)
    for (let i = 0; i < errcols.length; i++) {
        let colidx = errcols[i];
        switch (colidx) {
            case '2':
                $('#ifg_business_name').addClass('error_bg');
                break;
            case '7':
                $('#ifg_email').addClass('error_bg');
                break;
        }
    }

    // reemplazamos los valores que vienen de la fila de la tabla al modal
    $('#ic_customer_name').val(rowinfo[1]);
    $('#ic_business_name').val(rowinfo[2]);
    $('#ic_tax_id').val(rowinfo[3]);
    $('#ic_discount').val(rowinfo[4]);
    $('#ic_terms_of_sales').val(rowinfo[5]);
    $('#ic_group_id').val(rowinfo[6]);
    $('#ic_email').val(rowinfo[7]);
    $('#ic_phone').val(rowinfo[8]);
    $('#ic_cell_phone').val(rowinfo[9]);
    $('#ic_seller_assigned').val(rowinfo[10]);
    $('#ic_notes').val(rowinfo[11]);
    $('#ic_commercial_address').val(rowinfo[12]);

    $('#ic_commercial_delivery_address').val(rowinfo[13]);
    $('#ic_commercial_country').val(rowinfo[14]);
    $('#ic_commercial_state').val(rowinfo[15]);
    $('#ic_commercial_city').val(rowinfo[16]);
    $('#ic_commercial_zone').val(rowinfo[17]);
    $('#ic_commercial_zip_code').val(rowinfo[18]);
    $('#ic_dispatch_address').val(rowinfo[19]);
    $('#ic_dispatch_delivery_address').val(rowinfo[20]);
    $('#ic_dispatch_country').val(rowinfo[21]);
    $('#ic_dispatch_state').val(rowinfo[22]);
    $('#ic_dispatch_city').val(rowinfo[23]);
    $('#ic_dispatch_zone').val(rowinfo[24]);
    $('#ic_dispatch_zip_code').val(rowinfo[25]);
    $('#ic_dispatch_shipping_notes').val(rowinfo[26]);

    $('#ic_catalog_emails').val(rowinfo[27]);
    $('#ic_customer_status').val(rowinfo[28]);

    $("#iformDlgEdit").modal('show');
    $('#ifdlg_err').hide();
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

//VALIDA LOS CAMBIOS DEL FORMULARIO ANTES DE ENVIARLO
function iformdlgSaveChanges() {

    const form = $('#form-validate')
    let errcheck = false;
    let errmsg = "";
    let errcols = selerrcolss.split(';');

    $.ajax({
        url: form.attr('action'),
        data: form.serializeArray(),
        type: 'post',
        dataType: 'json',
        success: function(response) {

            if (!response.success) {

                showAlert(response.msg)
                $('#' + response.input_name).addClass('error_bg')
                $(response.section).click()
                return
            }


            selrowdata[1] = $('#ic_customer_name').val();
            selrowdata[2] = $('#ic_business_name').val();
            selrowdata[3] = $('#ic_tax_id').val();
            selrowdata[4] = $('#ic_discount').val();
            selrowdata[5] = $('#ic_terms_of_sales').val();
            selrowdata[6] = $('#ic_group_id').val();
            selrowdata[7] = $('#ic_email').val();
            selrowdata[8] = $('#ic_phone').val();
            selrowdata[9] = $('#ic_cell_phone').val();
            selrowdata[10] = $('#ic_seller_assigned').val();

            selrowdata[11] = $('#ic_notes').val();
            selrowdata[12] = $('#ic_commercial_address').val();
            selrowdata[13] = $('#ic_commercial_delivery_address').val();
            selrowdata[14] = $('#ic_commercial_country').val();
            selrowdata[15] = $('#ic_commercial_state').val();
            selrowdata[16] = $('#ic_commercial_city').val();
            selrowdata[17] = $('#ic_commercial_zone').val();
            selrowdata[18] = $('#ic_commercial_zip_code').val();
            selrowdata[19] = $('#ic_dispatch_address').val();
            selrowdata[20] = $('#ic_dispatch_delivery_address').val();
            selrowdata[21] = $('#ic_dispatch_country').val();
            selrowdata[22] = $('#ic_dispatch_state').val();
            selrowdata[23] = $('#ic_dispatch_city').val();
            selrowdata[24] = $('#ic_dispatch_zone').val();
            selrowdata[25] = $('#ic_dispatch_zip_code').val();
            selrowdata[26] = $('#ic_dispatch_shipping_notes').val();
            selrowdata[27] = $('#ic_catalog_emails').val();
            selrowdata[28] = $('#ic_customer_status').val();
            selrowdata[29] = 1;

            $("#iformDlgEdit").modal('hide');
            selrowobj.data(selrowdata).draw();
            let selx = '#row' + selrowdata[30];
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

function myParamName() {
    return "files";
}
</script>

<?php
/**
 * @var array $pageTexts
 */
if (!isset($sep)) $sep = 1;
?>

<style>
    .fieldsep_op {
        cursor: pointer;
        width: fit-content;
        display: flex;
    }

    .previewbox {
        display: flex;
        width: 25%;
    }

    .pbar {
        background: linear-gradient(90deg, rgba(125, 33, 129, 1) 0%, rgba(189, 115, 64, 1) 35%, rgba(244, 76, 4, 1) 100%);
        width: 0;
        height: 16px;
        border-radius: 15px;
        animation: progres 4s infinite linear;
    }

    .pbarbox, {
        padding-left: 4px;
        padding-top: 9px;
    }

    .pbarbox { width: 50%; }

    @keyframes progres {
        0% { width: 0; }
        25% { width: 50%; }
        50% { width: 75%; }
        75% { width: 85%; }
        100% { width: 100%; }
    }
</style>

<div class="container-fluid page-body-wrapper">
    <div class="main-panel">
        <div class="container-fluid pt-3">
            <!-- Encabezado -->
            <div class="row">
                <div class="col-md-6">
                    <div class="page-header">
                        <h3 class="page-title"><?= $pageTexts['import-categories'] ?></h3>
                    </div>
                </div>
                <div class="col-md-6 text-right">
                    <a href="<?= import_template_download_url('categorycsv.xlsx') ?>" download
                       class="btn btn-success mt-2 mt-sm-0 btn-icon-text">
                        <i class="mdi mdi-download"></i>
                        <?= $pageTexts['sampledownload'] ?>
                    </a>
                </div>
            </div>

            <!-- Card principal -->
            <div class="card">
                <div class="card-body">
                    <form id="frmcsvfile" class="forms-sample"
                          action="<?= company_url('categories/CSVImportSuccess') ?>"
                          method="post" enctype="multipart/form-data">

                        <!-- Selector de archivo -->
                        <div class="form-group">
                            <label>CSV file: <span class="color-red">*</span></label><br>
                            <div class="fileinput fileinput-new" data-provides="fileinput">
                                <?php if (isset($filename)) echo $filename; ?>
                            </div>

                            <div class="clearfix margin-top-10 m-b-20 f12">
                                <span class="label label-danger"><?= $pageTexts['formatcsv'] ?></span>&nbsp;csv
                            </div>

                            <!-- Separador de campos -->
                            <fieldset>
                                <legend style="font-size:15px;"><?= $pageTexts['fieldsep'] ?>:</legend>
                                <div class="fieldsep_op">
                                    <div class="m-3">
                                        <input type="radio" id="sepComma" name="fsep" value="0" <?= $sep==0 ? 'checked' : '' ?>>
                                        <div><?= $pageTexts['commatip'] ?></div>
                                    </div>
                                    <div class="m-3">
                                        <input type="radio" id="sepSemicolon" name="fsep" value="1" <?= $sep==1 ? 'checked' : '' ?>>
                                        <div><?= $pageTexts['semicolontip'] ?></div>
                                    </div>
                                </div>
                            </fieldset>

                            <!-- Botón para elegir archivo -->
                            <div class="previewbox">
                                <div id="btnPickFile" style="width:50%;" class="btn btn-outline-primary">
                                    <?= $pageTexts['choosetip'] ?>
                                </div>
                                <div id="appprogbar1" class="pbarbox">
                                    <div class="pbar"></div>
                                </div>
                            </div>
                        </div>

                        <input id="ifilepicker" type="file" name="csvfile"
                               style="visibility:hidden;"
                               accept="text/csv" required>
                    </form>
                </div>

                <div class="container">
                    <div class="mx-auto w-90">
                    <!-- Buscador -->
                        <div class="d-flex justify-content-end mb-2">
                            <input type="text" id="searchInput" class="form-control w-25"
                                   placeholder="Buscar categoría...">
                        </div>

                        <!-- Tabla de previsualización -->
                        <div class="table-responsive">
                            <table id="csvTable" class="table table-striped">
                                <thead>
                                <tr>
                                    <th>Categoría</th>
                                </tr>
                                </thead>
                                <tbody>
                                <!-- Se llenará dinámicamente -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Botón de importar -->
                        <div class="text-right m-3">
                            <button id="btnImport" class="btn btn-success">Importar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

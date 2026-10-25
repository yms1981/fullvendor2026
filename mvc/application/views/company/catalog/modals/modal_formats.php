<?php
/**
 * Modal para gestionar formatos de catálogo
 * @var array $lang_optionbasic Textos de idioma
 * @var array $langcatalog Textos de catálogo
 * @var array $lang_modal Textos de modal
 * @var array $list Lista de catálogos (opcional, para seleccionar catálogo)
 */
$lang_optionbasic = (isset($lang_optionbasic) && is_array($lang_optionbasic)) ? $lang_optionbasic : [];
$langcatalog = (isset($langcatalog) && is_array($langcatalog)) ? $langcatalog : [];
$lang_modal = (isset($lang_modal) && is_array($lang_modal)) ? $lang_modal : [];
$formatsTitle = (string) ($langcatalog['formats'] ?? $lang_optionbasic['formats'] ?? '');
$closeLabel = (string) ($lang_modal['close'] ?? $langcatalog['close'] ?? '');
$addLabel = (string) ($langcatalog['add'] ?? $langcatalog['add_format'] ?? '');
$deleteLabel = (string) ($langcatalog['delete'] ?? $langcatalog['delete_format'] ?? '');
?>
<div class="modal fade fv-catalog-modal" id="formatsModal" tabindex="-1" aria-labelledby="formatsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" style="max-width: 95%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="formatsModalLabel">
                    <span class="modal-header__avatar" aria-hidden="true"><i class="mdi mdi-file-document-edit-outline"></i></span>
                    <span><?= htmlspecialchars($formatsTitle, ENT_QUOTES, 'UTF-8') ?></span>
                </h5>
                <button type="button" class="close fv-modal-close-x" data-dismiss="modal"
                        aria-label="<?= htmlspecialchars($closeLabel, ENT_QUOTES, 'UTF-8') ?>">
                    <i class="mdi mdi-close" aria-hidden="true"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="formatsForm">
                    <!-- Lista de formatos con select -->
                    <div class="form-group">
                        <label class="font-weight-bold"><?= $langcatalog['format_list'] ?? 'Lista de Formatos' ?></label>
                        <select class="form-control" id="formatSelect" name="format_select">
                            <option value=""><?= $langcatalog['select_format'] ?? 'Seleccione un formato...' ?></option>
                            <!-- Los formatos se cargarán aquí dinámicamente -->
                        </select>
                    </div>

                    <!-- Botones de acción (solo iconos) -->
                    <div class="form-group d-flex align-items-center fv-format-actions">
                        <button type="button" class="btn btn-success btn-sm fv-btn-with-label fv-format-icon-btn" id="btnAddFormat" title="<?= htmlspecialchars($addLabel, ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars($addLabel, ENT_QUOTES, 'UTF-8') ?>">
                            <i class="mdi mdi-plus" aria-hidden="true"></i>
                        </button>
                        <button type="button" class="btn btn-danger btn-sm fv-btn-with-label fv-format-icon-btn" id="btnDeleteFormat" title="<?= htmlspecialchars($deleteLabel, ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars($deleteLabel, ENT_QUOTES, 'UTF-8') ?>">
                            <i class="mdi mdi-delete" aria-hidden="true"></i>
                        </button>
                    </div>
                    <style>
                        #formatsModal .fv-format-actions {
                            gap: 0.5rem;
                            flex-wrap: wrap;
                        }
                        #formatsModal .fv-format-actions .fv-format-icon-btn {
                            width: 2.35rem !important;
                            min-width: 2.35rem !important;
                            height: 2.35rem !important;
                            min-height: 2.35rem !important;
                            padding: 0 !important;
                            border-radius: 50% !important;
                            overflow: hidden !important;
                            display: inline-flex !important;
                            align-items: center !important;
                            justify-content: center !important;
                        }
                        #formatsModal .fv-format-actions .fv-format-icon-btn i {
                            font-size: 1.15rem;
                            line-height: 1;
                            margin: 0;
                        }
                    </style>

                        <!-- Campos de nombre y descripción (siempre visibles) -->
                        <div id="formatDetails">
                            <div class="form-group">
                                <label class="font-weight-bold"><?= $langcatalog['format_name'] ?? 'Nombre del Formato' ?> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="formatNameInput" name="format_name" 
                                       placeholder="<?= $langcatalog['enter_format_name'] ?? 'Ingrese el nombre del formato' ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="font-weight-bold"><?= $langcatalog['format_description'] ?? 'Descripción' ?></label>
                                <textarea class="form-control" id="formatDescriptionInput" name="format_description" rows="2" 
                                          placeholder="<?= $langcatalog['enter_format_description'] ?? 'Ingrese una descripción del formato (opcional)' ?>"></textarea>
                            </div>
                        </div>

                    <!-- Selector de elementos del PDF -->
                    <div class="form-group">
                        <label class="font-weight-bold"><?= $langcatalog['pdf_elements'] ?? '' ?></label>
                        <select class="form-control" id="pdfElementSelect" style="max-width: 300px;">
                            <option value=""><?= $langcatalog['select_pdf_element'] ?? '' ?></option>
                            <option value="##id##"><?= $langcatalog['element_id'] ?? 'ID' ?> (##id##)</option>
                            <option value="##name##"><?= $langcatalog['element_name'] ?? 'Nombre' ?> (##name##)</option>
                            <option value="##price##"><?= $langcatalog['element_price'] ?? 'Precio' ?> (##price##)</option>
                            <option value="##description##"><?= $langcatalog['element_description'] ?? 'Descripción' ?> (##description##)</option>
                            <option value="##sku##"><?= $langcatalog['element_sku'] ?? 'SKU' ?> (##sku##)</option>
                            <option value="##category##"><?= $langcatalog['element_category'] ?? 'Categoría' ?> (##category##)</option>
                            <option value="##image##"><?= $langcatalog['element_image'] ?? 'Imagen' ?> (##image##)</option>
                            <option value="##quantity##"><?= $langcatalog['element_quantity'] ?? 'Cantidad' ?> (##quantity##)</option>
                            <option value="##unit##"><?= $langcatalog['element_unit'] ?? 'Unidad' ?> (##unit##)</option>
                        </select>
                        <small class="form-text text-muted">
                            <?= $langcatalog['pdf_element_help'] ?? '' ?>
                        </small>
                    </div>

                    <!-- Editor tipo Word para el formato -->
                    <div class="form-group">
                        <label class="font-weight-bold"><?= $langcatalog['format_template'] ?? 'Plantilla de Formato' ?></label>
                        
                        <!-- Barra de herramientas del editor -->
                        <div class="format-toolbar border rounded-top p-2 bg-light" style="border-bottom: none;">
                            <div class="btn-toolbar" role="toolbar">
                                <!-- Formato de texto -->
                                <div class="btn-group mr-2" role="group">
                                    <button type="button" class="btn btn-sm btn-secondary" data-command="bold" title="<?= $langcatalog['format_toolbar_bold'] ?? 'Bold' ?>">
                                        <i class="mdi mdi-format-bold"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-secondary" data-command="italic" title="<?= $langcatalog['format_toolbar_italic'] ?? 'Italic' ?>">
                                        <i class="mdi mdi-format-italic"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-secondary" data-command="underline" title="<?= $langcatalog['format_toolbar_underline'] ?? 'Underline' ?>">
                                        <i class="mdi mdi-format-underline"></i>
                                    </button>
                                </div>
                                
                                <!-- Alineación -->
                                <div class="btn-group mr-2" role="group">
                                    <button type="button" class="btn btn-sm btn-secondary" data-command="justifyLeft" title="<?= $langcatalog['format_toolbar_align_left'] ?? 'Align left' ?>">
                                        <i class="mdi mdi-format-align-left"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-secondary" data-command="justifyCenter" title="<?= $langcatalog['format_toolbar_align_center'] ?? 'Center' ?>">
                                        <i class="mdi mdi-format-align-center"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-secondary" data-command="justifyRight" title="<?= $langcatalog['format_toolbar_align_right'] ?? 'Align right' ?>">
                                        <i class="mdi mdi-format-align-right"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-secondary" data-command="justifyFull" title="<?= $langcatalog['format_toolbar_justify'] ?? 'Justify' ?>">
                                        <i class="mdi mdi-format-align-justify"></i>
                                    </button>
                                </div>
                                
                                <!-- Listas -->
                                <div class="btn-group mr-2" role="group">
                                    <button type="button" class="btn btn-sm btn-secondary" data-command="insertUnorderedList" title="<?= $langcatalog['format_toolbar_bullet_list'] ?? 'Bullet list' ?>">
                                        <i class="mdi mdi-format-list-bulleted"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-secondary" data-command="insertOrderedList" title="<?= $langcatalog['format_toolbar_numbered_list'] ?? 'Numbered list' ?>">
                                        <i class="mdi mdi-format-list-numbered"></i>
                                    </button>
                                </div>
                                
                                <!-- Tamaño de fuente -->
                                <div class="btn-group mr-2" role="group">
                                    <select class="form-control form-control-sm" id="fontSizeSelect" style="width: auto;">
                                        <option value="1">8pt</option>
                                        <option value="2">10pt</option>
                                        <option value="3" selected>12pt</option>
                                        <option value="4">14pt</option>
                                        <option value="5">16pt</option>
                                        <option value="6">18pt</option>
                                        <option value="7">24pt</option>
                                    </select>
                                </div>
                                
                                <!-- Color de texto -->
                                <div class="btn-group mr-2" role="group">
                                    <input type="color" class="form-control form-control-sm" id="textColorPicker" 
                                           style="width: 40px; height: 30px; padding: 2px;" title="<?= $langcatalog['format_toolbar_text_color'] ?? 'Text color' ?>">
                                </div>
                                
                                <!-- Deshacer/Rehacer -->
                                <div class="btn-group mr-2" role="group">
                                    <button type="button" class="btn btn-sm btn-secondary" data-command="undo" title="<?= $langcatalog['format_toolbar_undo'] ?? 'Undo' ?>">
                                        <i class="mdi mdi-undo"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-secondary" data-command="redo" title="<?= $langcatalog['format_toolbar_redo'] ?? 'Redo' ?>">
                                        <i class="mdi mdi-redo"></i>
                                    </button>
                                </div>
                                
                                <!-- Insertar tabla (tipo Word) -->
                                <div class="btn-group mr-2" role="group">
                                    <div class="btn-group" role="group">
                                        <button type="button" class="btn btn-sm btn-secondary" id="btnInsertTable" title="<?= $langcatalog['format_toolbar_insert_table'] ?? 'Insert Table' ?>">
                                            <i class="mdi mdi-table"></i> <?= $langcatalog['format_toolbar_insert_table'] ?? 'Tabla' ?>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-secondary dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                            <span class="sr-only">Toggle Dropdown</span>
                                        </button>
                                        <div class="dropdown-menu" id="tableGridSelector" style="padding: 10px;">
                                            <div id="tableGrid" style="display: grid; grid-template-columns: repeat(10, 20px); gap: 2px; cursor: pointer;"></div>
                                            <div style="margin-top: 10px; text-align: center; font-size: 12px; color: #666;" id="tableSizeLabel">0 x 0</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Color de fondo de celda de tabla -->
                                <div class="btn-group mr-2" role="group" id="tableCellBgColorGroup" style="display: none;">
                                    <button type="button" class="btn btn-sm btn-secondary" id="btnTableCellBgColor" title="<?= $langcatalog['format_toolbar_table_cell_bg'] ?? 'Cell Background Color' ?>">
                                        <i class="mdi mdi-format-color-fill"></i> <?= $langcatalog['format_toolbar_table_cell_bg'] ?? 'Cell BG' ?>
                                    </button>
                                    <input type="color" class="d-none" id="tableCellBgColorPicker" value="#ffffff">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Área de edición -->
                        <div class="border rounded-bottom" style="min-height: 500px;">
                            <div id="formatEditor" contenteditable="true" 
                                 style="min-height: 500px; padding: 15px; background-color: #fff; font-family: 'Times New Roman', serif; font-size: 12pt; line-height: 1.5; outline: none; overflow-y: auto;"
                                 class="format-editor">
                                <p><?= $langcatalog['format_editor_placeholder'] ?? 'Escriba aquí el formato de impresión...' ?></p>
                            </div>
                        </div>
                        <small class="form-text text-muted">
                            <?= $langcatalog['format_editor_help'] ?? 'Edite el formato como en un procesador de texto. Use la barra de herramientas para formatear el texto. Puede insertar encabezado y pie de página dentro del formato.' ?>
                        </small>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
$formatsFabSaveLabel = trim((string) ($lang_modal['save'] ?? ''));
$formatsFabCloseLabel = trim((string) ($lang_modal['close'] ?? ''));
?>
<link rel="stylesheet" href="<?= dated_file_url('companyasset/company/config-crud-form-fab.css') ?>" />
<div id="formatsModalFab"
     class="fv-formats-modal-fab"
     role="toolbar"
     aria-label="<?= htmlspecialchars($formatsFabSaveLabel !== '' ? $formatsFabSaveLabel : $formatsFabCloseLabel, ENT_QUOTES, 'UTF-8') ?>"
     hidden>
    <button type="button"
            id="btnCloseFormatsModal"
            class="fv-crud-form-fab__btn fv-crud-form-fab__btn--cancel"
            title="<?= htmlspecialchars($formatsFabCloseLabel, ENT_QUOTES, 'UTF-8') ?>"
            aria-label="<?= htmlspecialchars($formatsFabCloseLabel, ENT_QUOTES, 'UTF-8') ?>">
        <i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>
    </button>
    <button type="button"
            id="btnSaveFormats"
            class="fv-crud-form-fab__btn fv-crud-form-fab__btn--save"
            title="<?= htmlspecialchars($formatsFabSaveLabel, ENT_QUOTES, 'UTF-8') ?>"
            aria-label="<?= htmlspecialchars($formatsFabSaveLabel, ENT_QUOTES, 'UTF-8') ?>">
        <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
    </button>
</div>
<style>
.fv-formats-modal-fab {
    position: fixed;
    right: var(--fv-fab-right, 22px);
    bottom: calc(var(--fv-fab-base, 22px) + var(--fv-fab-step, 70px));
    z-index: 20050;
    display: none;
    flex-direction: column;
    align-items: center;
    gap: var(--fv-fab-gap, 10px);
}
.fv-formats-modal-fab.is-visible {
    display: flex;
}
body.fv-formats-modal-open #fvChatBtn {
    bottom: calc(var(--fv-fab-base, 22px) + 3 * var(--fv-fab-step, 70px)) !important;
}
body.fv-formats-modal-open #fvGpsBtn {
    bottom: calc(var(--fv-fab-base, 22px) + 4 * var(--fv-fab-step, 70px)) !important;
}
body.fv-formats-modal-open #fvAssistantPanel {
    bottom: calc(var(--fv-fab-base, 22px) + 3 * var(--fv-fab-step, 70px)) !important;
}
</style>

<style>
.format-editor {
    outline: none;
}

.format-editor:focus {
    border: 2px solid #007bff;
}

.format-editor p {
    margin: 0 0 10px 0;
}

.format-editor ul, .format-editor ol {
    margin: 10px 0;
    padding-left: 30px;
}

.format-editor h1, .format-editor h2, .format-editor h3 {
    margin: 15px 0 10px 0;
}

.format-checkbox-item {
    padding: 8px;
    border-bottom: 1px solid #e0e0e0;
    cursor: pointer;
    transition: background-color 0.2s;
}

.format-checkbox-item:hover {
    background-color: #f5f5f5;
}

.format-checkbox-item:last-child {
    border-bottom: none;
}

.format-checkbox-item input[type="checkbox"] {
    margin-right: 10px;
}

.format-checkbox-item.selected {
    background-color: #e3f2fd;
}

/* Estilos para tablas tipo Word */
.format-editor table {
    width: 100%;
    border-collapse: collapse;
    margin: 10px 0;
    background-color: #fff;
}

.format-editor table th,
.format-editor table td {
    border: 1px solid #000;
    padding: 8px;
    min-height: 30px;
    vertical-align: top;
    min-width: 50px;
}

.format-editor table th {
    background-color: #f5f5f5;
    font-weight: bold;
    text-align: left;
}

/* Estilos para celdas redimensionables */
.format-editor table th.resizable-cell,
.format-editor table td.resizable-cell {
    position: relative;
}

.format-editor .resize-handle {
    position: absolute;
    bottom: 0;
    right: 0;
    width: 10px;
    height: 10px;
    background: #007bff;
    cursor: nwse-resize;
    opacity: 0;
    transition: opacity 0.2s;
    z-index: 10;
}

.format-editor table th:hover .resize-handle,
.format-editor table td:hover .resize-handle {
    opacity: 0.5;
}

.format-editor table th.resizing,
.format-editor table td.resizing {
    border: 2px solid #007bff;
    user-select: none;
}

/* Selector de tabla tipo Word */
#tableGridSelector {
    min-width: 250px;
}

#tableGrid {
    display: grid;
    grid-template-columns: repeat(10, 20px);
    gap: 2px;
    cursor: pointer;
    margin: 0 auto;
}

#tableGrid .grid-cell {
    width: 20px;
    height: 20px;
    border: 1px solid #ddd;
    background-color: #fff;
    transition: background-color 0.1s;
}

#tableGrid .grid-cell:hover {
    background-color: #e3f2fd;
}

#tableGrid .grid-cell.selected {
    background-color: #2196F3;
    border-color: #1976D2;
}

/* Estilos para elementos arrastrables */
.format-editor [draggable="true"] {
    cursor: move;
    position: relative;
    margin: 10px 0;
    transition: all 0.2s;
}

.format-editor [draggable="true"]:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    transform: translateY(-1px);
}

.format-editor [draggable="true"].dragging {
    opacity: 0.5;
    border: 2px dashed #007bff !important;
}

.format-editor .format-header,
.format-editor .format-footer,
.format-editor .draggable-box {
    position: relative;
    padding-left: 30px !important;
}

.format-editor .drag-handle {
    position: absolute;
    left: 5px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 18px;
    color: #666;
    opacity: 0.5;
    cursor: move;
    user-select: none;
}

.format-editor .drag-handle:hover {
    opacity: 1;
    color: #007bff;
}

/* Indicador de posición al arrastrar */
.format-editor .drag-over {
    border-top: 3px solid #007bff !important;
    margin-top: 5px !important;
}

.format-editor .drag-over::before {
    content: "";
    display: block;
    height: 3px;
    background-color: #007bff;
    margin-bottom: 5px;
}
</style>

<script>
(function() {
    var modalId = '#formatsModal';
    var formatSelectId = '#formatSelect';
    var editorId = '#formatEditor';
    var btnAddId = '#btnAddFormat';
    var btnDeleteId = '#btnDeleteFormat';
    var btnSaveId = '#btnSaveFormats';
    var btnInsertTableId = '#btnInsertTable';
    var tableGridId = '#tableGrid';
    var tableSizeLabelId = '#tableSizeLabel';
    
    // Lista de formatos (se cargará desde el servidor)
    var formatsList = [];
    var selectedFormatId = null;
    var isSaving = false; // Flag para prevenir guardado duplicado
    var draggedElement = null; // Elemento que se está arrastrando
    var dragInitTimeout = null; // Timeout para debounce de inicialización
    
    function waitForJQuery(callback, maxAttempts) {
        maxAttempts = maxAttempts || 100;
        var attempts = 0;
        
        function check() {
            attempts++;
            if (typeof jQuery !== 'undefined' && typeof window.jQuery !== 'undefined') {
                if (typeof window.$ === 'undefined') {
                    window.$ = jQuery;
                }
                callback();
            } else if (attempts < maxAttempts) {
                setTimeout(check, 100);
            } else {
                callback();
            }
        }
        check();
    }
    
        waitForJQuery(function() {
        var $ = jQuery;
        
        // Esperar a que showSaveSuccess esté disponible (si se carga desde save-success-alert.js)
        function waitForSaveSuccess(callback, maxAttempts) {
            maxAttempts = maxAttempts || 50;
            var attempts = 0;
            
            function check() {
                attempts++;
                if (typeof showSaveSuccess !== 'undefined' && typeof showSaveSuccess === 'function') {
                    callback();
                } else if (attempts < maxAttempts) {
                    setTimeout(check, 100);
                } else {
                    // Si no está disponible después de varios intentos, continuar sin ella
                    console.warn('showSaveSuccess no está disponible, se usará alert como fallback');
                    callback();
                }
            }
            check();
        }
        
        // Inicializar barra de herramientas
        initFormatToolbar();
        
        // Cargar formatos cuando se abra el modal
        $(modalId).on('shown.bs.modal', function() {
            $('#formatsModalFab').addClass('is-visible').prop('hidden', false);
            $('body').addClass('fv-formats-modal-open');
            // Cargar formatos al abrir el modal
            loadFormats();
            // Limpiar campos al abrir el modal
            $('#formatNameInput').val('');
            $('#formatDescriptionInput').val('');
            $(editorId).html('<p><?= $langcatalog['format_editor_placeholder'] ?? 'Escriba aquí el formato de impresión...' ?></p>');
            selectedFormatId = null;
            // Inicializar elementos arrastrables después de limpiar
            setTimeout(function() {
                initDraggableElements();
            }, 100);
        });

        $(modalId).on('hidden.bs.modal', function() {
            $('#formatsModalFab').removeClass('is-visible').prop('hidden', true);
            $('body').removeClass('fv-formats-modal-open');
        });

        $('#btnCloseFormatsModal').off('click').on('click', function() {
            $(modalId).modal('hide');
        });
        
        // Función para cargar formatos desde el servidor
        function loadFormats() {
            // Obtener URL base
            var baseUrl = typeof companyUrl !== 'undefined' ? companyUrl : '/company';
            
            // Hacer petición AJAX para cargar formatos
            $.ajax({
                url: baseUrl + '/catalog/getFormats',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.formats) {
                        formatsList = response.formats;
                        selectedFormatId = null;
                        renderFormatsList();
                    } else {
                        formatsList = [];
                        selectedFormatId = null;
                        renderFormatsList();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error al cargar formatos:', error);
                    formatsList = [];
                    selectedFormatId = null;
                    renderFormatsList();
                }
            });
        }
        
        // Función para renderizar la lista de formatos en el select
        function renderFormatsList() {
            var html = '<option value=""><?= $langcatalog['select_format'] ?? 'Seleccione un formato...' ?></option>';
            if (formatsList.length > 0) {
                formatsList.forEach(function(format) {
                    var isSelected = selectedFormatId && (parseInt(selectedFormatId) === parseInt(format.id));
                    html += '<option value="' + format.id + '"' + (isSelected ? ' selected' : '') + '>' + format.name + '</option>';
                });
            }
            $(formatSelectId).html(html);
            
            // Re-registrar el evento change después de actualizar el HTML
            $(formatSelectId).off('change').on('change', function() {
                var formatId = parseInt($(this).val());
                console.log('=== SELECT CAMBIADO ===');
                console.log('Valor seleccionado:', $(this).val());
                console.log('Format ID parseado:', formatId);
                
                if (formatId && formatId > 0) {
                    selectedFormatId = formatId;
                    loadFormatTemplate(formatId);
                } else {
                    // Si se selecciona la opción vacía, limpiar todo
                    console.log('Selección limpiada');
                    selectedFormatId = null;
                    $('#formatNameInput').val('');
                    $('#formatDescriptionInput').val('');
                    $(editorId).html('<p><?= $langcatalog['format_editor_placeholder'] ?? 'Escriba aquí el formato de impresión...' ?></p>');
                }
            });
        }
        
        // El evento change se registra dentro de renderFormatsList() para asegurar que funcione después de cada actualización
        
        // Función para cargar la plantilla de un formato
        function loadFormatTemplate(formatId) {
            console.log('=== CARGANDO FORMATO ===');
            console.log('Format ID recibido:', formatId, 'Tipo:', typeof formatId);
            console.log('FormatsList disponible:', formatsList.length, 'formatos');
            console.log('FormatsList completa:', formatsList);
            
            // Convertir formatId a número para comparación
            var searchId = parseInt(formatId);
            
            // Buscar el formato por ID (comparar como número)
            var format = formatsList.find(function(f) { 
                var fId = parseInt(f.id);
                console.log('Comparando:', fId, 'con', searchId, 'Resultado:', fId === searchId);
                return fId === searchId; 
            });
            
            console.log('Formato encontrado:', format);
            
            if (format) {
                // Cargar nombre y descripción
                $('#formatNameInput').val(format.name || '');
                $('#formatDescriptionInput').val(format.description || '');
                console.log('✓ Nombre cargado:', format.name);
                console.log('✓ Descripción cargada:', format.description ? format.description.substring(0, 50) + '...' : '(vacía)');
                
                // Cargar template (que incluye header y footer si fueron insertados)
                if (format.template) {
                    $(editorId).html(format.template);
                    console.log('✓ Template cargado, longitud:', format.template.length, 'caracteres');
                    // Inicializar elementos arrastrables después de cargar
                    setTimeout(function() {
                        initDraggableElements();
                        initTableResize();
                        checkIfInTableCell();
                    }, 100);
                } else {
                    $(editorId).html('<p><?= $langcatalog['format_editor_placeholder'] ?? 'Escriba aquí el formato de impresión...' ?></p>');
                    console.log('⚠ Template vacío, usando placeholder');
                }
            } else {
                console.error('❌ ERROR: Formato no encontrado con ID:', formatId);
                console.error('IDs disponibles en la lista:', formatsList.map(f => f.id));
                alert('<?= $langcatalog['error_format_not_found'] ?? 'Error: Could not find the selected format. ID: ' ?>' + formatId);
            }
        }
        
        // Inicializar barra de herramientas del editor
        function initFormatToolbar() {
            // Botones de comando para el editor principal
            $('.format-toolbar button[data-command]').on('click', function(e) {
                e.preventDefault();
                var command = $(this).data('command');
                $(editorId).focus();
                document.execCommand(command, false, null);
            });
            
            // Selector de elementos del PDF
            $('#pdfElementSelect').on('change', function() {
                var elementTag = $(this).val();
                if (elementTag) {
                    insertPdfElement(elementTag);
                    // Resetear el select
                    $(this).val('');
                }
            });
            
            // Inicializar selector de tabla tipo Word
            initTableSelector();
            
            // Selector de tamaño de fuente
            $('#fontSizeSelect').on('change', function() {
                var size = $(this).val();
                document.execCommand('fontSize', false, size);
                $(editorId).focus();
            });
            
            // Selector de color
            $('#textColorPicker').on('change', function() {
                var color = $(this).val();
                document.execCommand('foreColor', false, color);
                $(editorId).focus();
            });
            
            // Actualizar estado de botones cuando cambia la selección
            $(editorId).on('mouseup keyup', function() {
                updateToolbarState();
                checkIfInTableCell();
            });
            
            // Función para verificar si el cursor está dentro de una celda de tabla
            function checkIfInTableCell() {
                var selection = window.getSelection();
                if (selection.rangeCount === 0) {
                    $('#tableCellBgColorGroup').hide();
                    return;
                }
                
                var range = selection.getRangeAt(0);
                var node = range.commonAncestorContainer;
                var editor = $(editorId)[0];
                var isInTableCell = false;
                
                // Buscar hacia arriba en el DOM para encontrar si estamos en una celda de tabla
                while (node && node !== editor) {
                    if (node.nodeName === 'TD' || node.nodeName === 'TH') {
                        isInTableCell = true;
                        break;
                    }
                    node = node.parentNode;
                }
                
                if (isInTableCell) {
                    $('#tableCellBgColorGroup').show();
                } else {
                    $('#tableCellBgColorGroup').hide();
                }
            }
            
            // Evento para el botón de color de fondo de celda
            $('#btnTableCellBgColor').on('click', function() {
                $('#tableCellBgColorPicker').click();
            });
            
            // Aplicar color de fondo cuando se selecciona un color
            $('#tableCellBgColorPicker').on('change', function() {
                var color = $(this).val();
                applyCellBackgroundColor(color);
                $(editorId).focus();
            });
            
            // Función para aplicar color de fondo a las celdas seleccionadas
            function applyCellBackgroundColor(color) {
                var selection = window.getSelection();
                if (selection.rangeCount === 0) return;
                
                var range = selection.getRangeAt(0);
                var cells = [];
                
                // Obtener el nodo común
                var container = range.commonAncestorContainer;
                var editor = $(editorId)[0];
                
                // Si el contenedor es directamente una celda
                if (container.nodeName === 'TD' || container.nodeName === 'TH') {
                    cells.push(container);
                } else {
                    // Buscar hacia arriba para encontrar la celda
                    var node = container;
                    while (node && node !== editor) {
                        if (node.nodeName === 'TD' || node.nodeName === 'TH') {
                            if (cells.indexOf(node) === -1) {
                                cells.push(node);
                            }
                            break;
                        }
                        node = node.parentNode;
                    }
                }
                
                // Si hay múltiples celdas seleccionadas (selección extendida)
                if (range.startContainer !== range.endContainer) {
                    var startNode = range.startContainer;
                    var endNode = range.endContainer;
                    
                    // Buscar todas las celdas entre start y end
                    var walker = document.createTreeWalker(
                        range.commonAncestorContainer,
                        NodeFilter.SHOW_ELEMENT,
                        function(node) {
                            if (node.nodeName === 'TD' || node.nodeName === 'TH') {
                                return NodeFilter.FILTER_ACCEPT;
                            }
                            return NodeFilter.FILTER_SKIP;
                        },
                        false
                    );
                    
                    var node;
                    while (node = walker.nextNode()) {
                        if (cells.indexOf(node) === -1) {
                            cells.push(node);
                        }
                    }
                }
                
                // Aplicar el color a todas las celdas encontradas
                cells.forEach(function(cell) {
                    $(cell).css('background-color', color);
                });
            }
            
            // Inicializar elementos arrastrables y redimensionamiento al cargar
            setTimeout(function() {
                initDraggableElements();
                initTableResize();
            }, 200);
            
            // Reinicializar cuando se modifica el contenido del editor (usando MutationObserver para mejor rendimiento)
            if (typeof MutationObserver !== 'undefined') {
                var observer = new MutationObserver(function(mutations) {
                    clearTimeout(dragInitTimeout);
                    dragInitTimeout = setTimeout(function() {
                        initDraggableElements();
                        initTableResize();
                    }, 300);
                });
                
                var editor = $(editorId)[0];
                if (editor) {
                    observer.observe(editor, {
                        childList: true,
                        subtree: true
                    });
                }
            }
        }
        
        // Inicializar selector de tabla tipo Word
        function initTableSelector() {
            var grid = $(tableGridId);
            var maxRows = 8;
            var maxCols = 10;
            var selectedRows = 0;
            var selectedCols = 0;
            var isInserting = false; // Flag para prevenir inserción duplicada
            
            // Limpiar eventos anteriores para evitar duplicados
            grid.off('mouseenter mouseleave click');
            
            // Crear grid de celdas solo si está vacío
            if (grid.children().length === 0) {
                for (var row = 0; row < maxRows; row++) {
                    for (var col = 0; col < maxCols; col++) {
                        var cell = $('<div class="grid-cell"></div>');
                        cell.data('row', row);
                        cell.data('col', col);
                        grid.append(cell);
                    }
                }
            }
            
            // Eventos del grid
            grid.on('mouseenter', '.grid-cell', function() {
                var row = $(this).data('row') + 1;
                var col = $(this).data('col') + 1;
                
                // Resaltar área seleccionada
                grid.find('.grid-cell').removeClass('selected');
                grid.find('.grid-cell').each(function() {
                    var r = $(this).data('row');
                    var c = $(this).data('col');
                    if (r < row && c < col) {
                        $(this).addClass('selected');
                    }
                });
                
                selectedRows = row;
                selectedCols = col;
                $(tableSizeLabelId).text(row + ' x ' + col);
            });
            
            grid.on('mouseleave', function() {
                grid.find('.grid-cell').removeClass('selected');
                $(tableSizeLabelId).text('0 x 0');
                selectedRows = 0;
                selectedCols = 0;
            });
            
            grid.on('click', '.grid-cell', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Prevenir inserción duplicada
                if (isInserting) {
                    return false;
                }
                
                isInserting = true;
                var rows = $(this).data('row') + 1;
                var cols = $(this).data('col') + 1;
                
                insertTable(rows, cols);
                
                // Cerrar dropdown
                setTimeout(function() {
                    $(btnInsertTableId).next('.dropdown-toggle').dropdown('hide');
                    $('.dropdown-menu').removeClass('show');
                    isInserting = false;
                }, 100);
                
                return false;
            });
        }
        
        // Función para insertar elemento del PDF en el editor
        function insertPdfElement(elementTag) {
            var editor = $(editorId)[0];
            if (!editor) return;
            
            editor.focus();
            
            // Obtener la selección actual
            var selection = window.getSelection();
            var range = selection.rangeCount > 0 ? selection.getRangeAt(0) : null;
            
            if (!range) {
                // Si no hay selección, crear un rango al final del contenido
                range = document.createRange();
                range.selectNodeContents(editor);
                range.collapse(false);
            }
            
            // Crear un nodo de texto con la etiqueta
            var textNode = document.createTextNode(elementTag);
            
            // Insertar el texto en la posición del cursor
            range.deleteContents();
            range.insertNode(textNode);
            
            // Mover el cursor después del texto insertado
            range.setStartAfter(textNode);
            range.collapse(true);
            selection.removeAllRanges();
            selection.addRange(range);
            
            // Enfocar el editor
            editor.focus();
        }
        
        // Función para insertar tabla
        function insertTable(rows, cols) {
            if (!rows || !cols || rows < 1 || cols < 1) {
                rows = 3;
                cols = 3;
            }
            
            // Asegurarse de que el editor tenga foco
            var editor = $(editorId)[0];
            if (!editor) return;
            
            editor.focus();
            
            // Obtener la selección actual
            var selection = window.getSelection();
            var range = selection.getRangeAt(0);
            
            // Crear la tabla usando DOM en lugar de insertHTML para mayor control
            var table = document.createElement('table');
            table.setAttribute('border', '1');
            table.setAttribute('cellpadding', '5');
            table.setAttribute('cellspacing', '0');
            table.style.width = '100%';
            table.style.borderCollapse = 'collapse';
            table.style.margin = '10px 0';
            
            // Crear filas
            for (var r = 0; r < rows; r++) {
                var tr = document.createElement('tr');
                for (var c = 0; c < cols; c++) {
                    var cell;
                    // Primera fila como encabezado
                    if (r === 0) {
                        cell = document.createElement('th');
                        cell.style.border = '1px solid #000';
                        cell.style.padding = '8px';
                        cell.style.backgroundColor = '#f5f5f5';
                        cell.style.fontWeight = 'bold';
                    } else {
                        cell = document.createElement('td');
                        cell.style.border = '1px solid #000';
                        cell.style.padding = '8px';
                    }
                    cell.innerHTML = '&nbsp;';
                    tr.appendChild(cell);
                }
                table.appendChild(tr);
            }
            
            // Insertar la tabla en el editor
            try {
                range.insertNode(table);
                // Mover el cursor después de la tabla
                range.setStartAfter(table);
                range.collapse(true);
                selection.removeAllRanges();
                selection.addRange(range);
                
                // Inicializar redimensionamiento de las celdas después de insertar
                setTimeout(function() {
                    $(table).find('th, td').each(function() {
                        makeCellResizable(this);
                    });
                }, 50);
            } catch (e) {
                // Fallback a insertHTML si falla
                console.log('Error insertando tabla con DOM, usando insertHTML:', e);
                var tableHtml = table.outerHTML;
                document.execCommand('insertHTML', false, tableHtml);
                
                // Inicializar redimensionamiento después de insertar con insertHTML
                setTimeout(function() {
                    initTableResize();
                }, 100);
            }
        }
        
        // Función para hacer una celda redimensionable
        function makeCellResizable(cell) {
            if (!cell || $(cell).hasClass('resizable-cell')) return;
            
            $(cell).addClass('resizable-cell');
            $(cell).css('position', 'relative');
            
            // Crear handle de redimensionamiento
            var resizeHandle = $('<div class="resize-handle"></div>');
            resizeHandle.css({
                'position': 'absolute',
                'bottom': '0',
                'right': '0',
                'width': '10px',
                'height': '10px',
                'background': '#007bff',
                'cursor': 'nwse-resize',
                'opacity': '0',
                'transition': 'opacity 0.2s',
                'z-index': '10'
            });
            $(cell).append(resizeHandle);
            
            // Mostrar handle al hacer hover
            $(cell).on('mouseenter', function() {
                resizeHandle.css('opacity', '0.5');
            });
            
            $(cell).on('mouseleave', function() {
                resizeHandle.css('opacity', '0');
            });
            
            // Funcionalidad de redimensionamiento
            var isResizing = false;
            var startX, startY, startWidth, startHeight;
            
            resizeHandle.on('mousedown', function(e) {
                e.preventDefault();
                e.stopPropagation();
                isResizing = true;
                startX = e.pageX;
                startY = e.pageY;
                startWidth = $(cell).outerWidth();
                startHeight = $(cell).outerHeight();
                $(cell).addClass('resizing');
                
                $(document).on('mousemove.resize', function(e) {
                    if (!isResizing) return;
                    var width = startWidth + (e.pageX - startX);
                    var height = startHeight + (e.pageY - startY);
                    $(cell).css({
                        'width': Math.max(50, width) + 'px',
                        'height': Math.max(30, height) + 'px'
                    });
                });
                
                $(document).on('mouseup.resize', function() {
                    isResizing = false;
                    $(cell).removeClass('resizing');
                    $(document).off('mousemove.resize mouseup.resize');
                });
            });
        }
        
        // Inicializar redimensionamiento de tablas
        function initTableResize() {
            var editor = $(editorId)[0];
            if (!editor) return;
            
            // Hacer todas las celdas redimensionables
            $(editor).find('table th, table td').each(function() {
                if (!$(this).hasClass('resizable-cell')) {
                    makeCellResizable(this);
                }
            });
        }
        
        // Inicializar elementos arrastrables
        function initDraggableElements() {
            var editor = $(editorId)[0];
            if (!editor) return;
            
            // Hacer todos los elementos con draggable="true" arrastrables
            $(editor).find('[draggable="true"]').each(function() {
                var element = this;
                
                // Remover listeners anteriores para evitar duplicados
                $(element).off('dragstart dragend dragover dragenter dragleave drop');
                
                // Drag start
                $(element).on('dragstart', function(e) {
                    e.originalEvent.dataTransfer.effectAllowed = 'move';
                    e.originalEvent.dataTransfer.setData('text/html', element.outerHTML);
                    $(element).addClass('dragging');
                    draggedElement = element;
                });
                
                // Drag end
                $(element).on('dragend', function(e) {
                    $(element).removeClass('dragging');
                    $(editor).find('.drag-over').removeClass('drag-over');
                    draggedElement = null;
                });
                
                // Drag over
                $(element).on('dragover', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.originalEvent.dataTransfer.dropEffect = 'move';
                    
                    // Mostrar indicador de posición
                    if (draggedElement && draggedElement !== element) {
                        var rect = element.getBoundingClientRect();
                        var y = e.originalEvent.clientY - rect.top;
                        
                        $(element).removeClass('drag-over');
                        if (y < rect.height / 2) {
                            $(element).addClass('drag-over');
                        } else {
                            $(element).next().addClass('drag-over');
                        }
                    }
                });
                
                // Drag enter
                $(element).on('dragenter', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                });
                
                // Drag leave
                $(element).on('dragleave', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                });
                
                // Drop
                $(element).on('drop', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    if (draggedElement && draggedElement !== element) {
                        var rect = element.getBoundingClientRect();
                        var y = e.originalEvent.clientY - rect.top;
                        
                        if (y < rect.height / 2) {
                            // Insertar antes
                            $(element).before(draggedElement);
                        } else {
                            // Insertar después
                            $(element).after(draggedElement);
                        }
                        
                        // Reinicializar elementos arrastrables después de mover
                        initDraggableElements();
                    }
                });
            });
            
            // Permitir drop en el editor mismo
            $(editor).off('dragover dragenter dragleave drop');
            $(editor).on('dragover', function(e) {
                e.preventDefault();
                e.stopPropagation();
            });
            
            $(editor).on('dragenter', function(e) {
                e.preventDefault();
                e.stopPropagation();
            });
            
            $(editor).on('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                if (draggedElement) {
                    // Si no hay elementos arrastrables, insertar al final
                    var draggables = $(editor).find('[draggable="true"]');
                    if (draggables.length === 0 || draggedElement === draggables[0]) {
                        $(editor).append(draggedElement);
                    }
                    
                    initDraggableElements();
                }
            });
        }
        
        // Actualizar estado visual de los botones de la barra de herramientas
        function updateToolbarState() {
            $('.format-toolbar button[data-command]').each(function() {
                var command = $(this).data('command');
                var isActive = document.queryCommandState(command);
                $(this).toggleClass('active', isActive);
            });
        }
        
        // Botón agregar formato
        $(btnAddId).off('click').on('click', function() {
            // Limpiar selección anterior
            selectedFormatId = null;
            $(formatSelectId).val('');
            
            // Limpiar campos y editor
            $('#formatNameInput').val('');
            $('#formatDescriptionInput').val('');
            $(editorId).html('<p><?= $langcatalog['format_editor_placeholder'] ?? 'Escriba aquí el formato de impresión...' ?></p>');
            
            // Enfocar en el campo de nombre
            $('#formatNameInput').focus();
        });
        
        // Botón eliminar formato
        $(btnDeleteId).on('click', function() {
            if (!selectedFormatId) {
                alert('<?= $langcatalog['select_format_to_delete'] ?? 'Por favor, seleccione un formato para eliminar' ?>');
                return;
            }
            
            if (confirm('<?= $lang_modal['confirm_delete'] ?? '¿Está seguro de eliminar este formato?' ?>')) {
                var baseUrl = typeof companyUrl !== 'undefined' ? companyUrl : '/company';
                
                $.ajax({
                    url: baseUrl + '/catalog/deleteFormat/' + selectedFormatId,
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                    if (response.success) {
                        // Recargar la lista de formatos
                        loadFormats();
                        selectedFormatId = null;
                        $(formatSelectId).val('');
                        $('#formatNameInput').val('');
                        $('#formatDescriptionInput').val('');
                        $(editorId).html('<p><?= $langcatalog['format_editor_placeholder'] ?? 'Escriba aquí el formato de impresión...' ?></p>');
                        alert('<?= $lang_modal['deleted_successfully'] ?? 'Formato eliminado correctamente' ?>');
                        } else {
                            alert(response.msg || '<?= $langcatalog['error_deleting_format'] ?? 'Error deleting format. Please try again.' ?>');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error al eliminar formato:', error);
                        alert('<?= $langcatalog['error_deleting_format'] ?? 'Error deleting format. Please try again.' ?>');
                    }
                });
            }
        });
        
        // Botón guardar - prevenir duplicados
        $(btnSaveId).off('click').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Prevenir guardado duplicado
            if (isSaving) {
                console.log('Ya se está guardando, ignorando click duplicado');
                return false;
            }
            
            // Validar que haya un nombre
            var formatName = $('#formatNameInput').val().trim();
            if (!formatName) {
                alert('<?= $langcatalog['format_name_required'] ?? 'El nombre del formato es requerido' ?>');
                $('#formatNameInput').focus();
                return false;
            }
            
            // Marcar como guardando
            isSaving = true;
            
            // Obtener valores del formulario
            var formatDescription = $('#formatDescriptionInput').val().trim();
            // El template incluye header y footer si fueron insertados
            var template = $(editorId).html();
            
            // Si hay un formato seleccionado, actualizar; si no, crear nuevo
            var format = selectedFormatId ? formatsList.find(function(f) { return f.id === selectedFormatId; }) : null;
            
            // Preparar datos para guardar
            var baseUrl = typeof companyUrl !== 'undefined' ? companyUrl : '/company';
            var saveData = {
                format_id: selectedFormatId || '',
                format_name: formatName,
                format_description: formatDescription,
                template: template,
                order: format ? (format.order || 0) : formatsList.length,
                is_default: format ? (format.is_default || 0) : 0
            };
            
            console.log('Guardando formato:', saveData);
            console.log('Template length:', template.length);
            
            // Deshabilitar botón mientras se guarda
            var $btn = $(btnSaveId);
            var $icon = $btn.find('i').first();
            var originalIconClass = $icon.attr('class') || 'fa-solid fa-floppy-disk';
            $btn.prop('disabled', true);
            $icon.attr('class', 'mdi mdi-loading mdi-spin');
            
            $.ajax({
                url: baseUrl + '/catalog/saveFormat',
                method: 'POST',
                data: saveData,
                dataType: 'json',
                success: function(response) {
                    console.log('Respuesta del servidor:', response);
                    isSaving = false;
                    $btn.prop('disabled', false);
                    $icon.attr('class', originalIconClass);
                    
                    if (response.success) {
                        // Usar la alerta genérica de guardar con éxito
                        var successMessage = response.message || response.msg || '<?= $lang_modal['saved_successfully'] ?? 'Formato guardado correctamente' ?>';
                        
                        // Función para ejecutar después de guardar
                        function afterSave() {
                            // Recargar la lista para asegurar sincronización
                            loadFormats();
                            // Si era un formato nuevo, seleccionarlo después de recargar
                            if (!selectedFormatId && response.format_id) {
                                setTimeout(function() {
                                    // Buscar el formato recién creado y seleccionarlo
                                    loadFormats();
                                    setTimeout(function() {
                                        $(formatSelectId).val(response.format_id).trigger('change');
                                    }, 300);
                                }, 300);
                            } else if (selectedFormatId) {
                                // Si se actualizó, mantener la selección
                                $(formatSelectId).val(selectedFormatId);
                            }
                        }
                        
                        // Verificar si showSaveSuccess está disponible (puede tardar en cargar)
                        if (typeof showSaveSuccess !== 'undefined' && typeof showSaveSuccess === 'function') {
                            console.log('✅ Usando showSaveSuccess');
                            showSaveSuccess(successMessage, {
                                onClose: function() {
                                    afterSave();
                                }
                            });
                        } else {
                            // Si no está disponible, esperar un poco y volver a intentar
                            console.log('⚠️ showSaveSuccess no disponible inmediatamente, esperando...');
                            var attempts = 0;
                            var maxAttempts = 10;
                            var checkInterval = setInterval(function() {
                                attempts++;
                                if (typeof showSaveSuccess !== 'undefined' && typeof showSaveSuccess === 'function') {
                                    clearInterval(checkInterval);
                                    console.log('✅ showSaveSuccess ahora disponible');
                                    showSaveSuccess(successMessage, {
                                        onClose: function() {
                                            afterSave();
                                        }
                                    });
                                } else if (attempts >= maxAttempts) {
                                    clearInterval(checkInterval);
                                    console.log('⚠️ showSaveSuccess no disponible después de esperar, usando alert');
                                    alert(successMessage);
                                    afterSave();
                                }
                            }, 100);
                        }
                    } else {
                        alert(response.msg || '<?= $langcatalog['error_saving_format'] ?? 'Error saving format. Please try again.' ?>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error al guardar formato:', error);
                    console.error('Status:', status);
                    console.error('Response:', xhr.responseText);
                    isSaving = false;
                    $btn.prop('disabled', false);
                    $icon.attr('class', originalIconClass);
                    
                    var errorMsg = '<?= $langcatalog['error_saving_format'] ?? 'Error saving format. Please try again.' ?>';
                    try {
                        var errorResponse = JSON.parse(xhr.responseText);
                        if (errorResponse.msg) {
                            errorMsg = errorResponse.msg;
                        }
                    } catch (e) {
                        // Si no se puede parsear, usar el mensaje por defecto
                    }
                    alert(errorMsg);
                }
            });
            
            return false;
        });
    });
})();
</script>


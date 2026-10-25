<?php
/**
 * @var array $pageTexts The translated string conglomerated for this view
 */
?>
<script>
window.fvCategoryFormLang = <?= json_encode([
    'category_name_already_exists' => $pageTexts['category_name_already_exists'] ?? '',
    'category_exists' => $pageTexts['category_exists'] ?? '',
    'category_save_error_generic' => $pageTexts['category_save_error_generic'] ?? '',
    'category_saved' => $pageTexts['category_saved'] ?? '',
    'category_success' => $pageTexts['category_success'] ?? '',
    'ok' => $pageTexts['ok'] ?? '',
    'loading' => $pageTexts['loading'] ?? '',
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>

<form id="form_add_category_modal" class="forms-sample" action="<?= company_url('categories/save') ?>" method="post" enctype="multipart/form-data">
    <div class="form-group">
        <label><?= $pageTexts['nombre_categoria'] ?? 'Nombre de Categoría' ?> <span class="color-red">*</span> </label>
        <input id="category_name_add_modal" class="form-control" type="text" name="category_name" required>
        <div class="container_alert"></div>
    </div>

    <div class="form-group">
        <label><?= $pageTexts['imagen'] ?? 'Imagen' ?> </label>
        <div class="d-flex flex-column">
            <div class="mb-3">
                <div class="fileinput fileinput-new text-center" data-provides="fileinput">
                    <div class="fileinput-new thumbnail" style="width: 100%; max-width: 300px; height: 200px; margin: 0 auto;">
                        <img id="main-category-image-add-modal"
                             src="<?= company_url("image") ?>"
                             class="rounded-0"
                             style="display: block; width: 100%; height: 100%; object-fit: contain;"
                             alt="Vista previa">
                    </div>
                    <div class="fileinput-preview fileinput-exists thumbnail" style="max-width: 300px; max-height: 200px; margin: 0 auto;"></div>
                    <div class="text-center mt-2">
                        <span class="btn btn-default btn-file">
                            <span class="fileinput-new"><?= $pageTexts['select_imagen'] ?? 'Seleccionar imagen' ?></span>
                            <span class="fileinput-exists"><?= $pageTexts['boton_cambiar'] ?? 'Cambiar' ?></span>
                            <input type="file" 
                                   id="category_image_input_add_modal" 
                                   name="category_image" 
                                   accept="image/jpeg,image/jpg,image/png"
                                   data-max-size="10485760">
                        </span>
                        <a href="javascript:void(0)" class="btn btn-danger fileinput-exists" data-dismiss="fileinput">
                            <?= $pageTexts['boton_eliminar'] ?? 'Eliminar' ?>
                        </a>
                    </div>
                </div>
            </div>
            <div class="text-center">
                <small class="text-muted">
                    <span class="label label-info"><?= $pageTexts['span_format'] ?? 'Formatos permitidos' ?></span>: JPEG, JPG, PNG<br>
                    <span class="label label-info"><?= $pageTexts['max_upload'] ?? 'Tamaño máximo' ?></span>: 10 MB
                </small>
                <div id="image_error_message" class="text-danger mt-2" style="display: none;"></div>
            </div>
        </div>
    </div>
    
    <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal">
            <?= $pageTexts['boton_cancelar'] ?? 'Cancelar' ?>
        </button>
        <button id="boton_enviar_add_modal" type="submit" class="btn btn-success">
            <?= $pageTexts['btn_save'] ?? 'Guardar' ?>
        </button>
    </div>
</form>


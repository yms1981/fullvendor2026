<?php
/**
 * @var array $pageTexts The translated string conglomerated for this view
 */
$image = company_url("image");
?>
<div class="modal fade" id="modal_imagenes_add" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel"><?= $pageTexts['span_imagen'] ?? 'Imagen' ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="fileinput fileinput-new text-center" data-provides="fileinput">
                    <div class="fileinput-new thumbnail" style="width: 250px; height: 150px;">
                        <img src="<?= $image ?>" alt="">
                    </div>
                    <div class="fileinput-preview fileinput-exists thumbnail" style="max-width: 250px; max-height: 150px;"></div>
                    <div class="text-center">
                        <span class="btn btn-default btn-file">
                            <span class="fileinput-new"><?= $pageTexts['select_imagen'] ?? 'Seleccionar' ?></span>
                            <span class="fileinput-exists"><?= $pageTexts['boton_cambiar'] ?? 'Cambiar' ?></span>
                            <input type="file" id="category_image_input_add_modal" name="category_image" accept="image/*">
                        </span>
                        <a href="javascript:void(0)" class="btn btn-danger fileinput-exists" data-dismiss="fileinput">
                            <?= $pageTexts['boton_eliminar'] ?? 'Eliminar' ?>
                        </a>
                    </div>
                </div>
                <div class="clearfix margin-top-10 m-b-20 f12" style="display: block;">
                    <span class="label label-danger"><?= $pageTexts['span_format'] ?? 'Formato' ?></span>&nbsp;jpg, jpeg, png<br><br>
                    <span class="label label-danger"><?= $pageTexts['max_upload'] ?? 'Tamaño máximo' ?></span>&nbsp;10mb
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">
                    <?= $pageTexts['boton_cancelar'] ?? 'Cancelar' ?>
                </button>
                <button type="button" id="btn-apply-image-add-modal" class="btn btn-success" data-dismiss="modal">
                    <?= $pageTexts['boton_aplicar'] ?? 'Aplicar' ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        let selectedImageURL = null;

        $('#category_image_input_add_modal').on('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                selectedImageURL = URL.createObjectURL(file);
                $('#modal_imagenes_add .fileinput-preview').html('<img src="' + selectedImageURL + '" style="max-width:100%; max-height:150px;">');
            }
        });

        $('#btn-apply-image-add-modal').on('click', function() {
            if (selectedImageURL) {
                // Actualizar la imagen en el formulario de edición o de agregar
                $('#main-category-image, #main-category-image-add-modal').attr('src', selectedImageURL);
            }
            $('#modal_imagenes_add').modal('hide');
        });
    });
</script>


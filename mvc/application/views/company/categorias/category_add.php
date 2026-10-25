<?php
/**
 * @var array $pageTexts The translated string conglomerated for this view
 */
?>

<form id="form_add" class="forms-sample" action="<?= company_url('categories/save') ?>" method="post" enctype="multipart/form-data">
    <div class="container-fluid page-body-wrapper pt-3">
        <div class="main-panel">
            <div class="container-fluid">
                <div class="page-header">
                    <h3 class="page-title"><?= strtoupper($pageTexts['titulo_agregar'] ?? 'AGREGAR CATEGORÍA') ?> </h3>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label><?= $pageTexts['nombre_categoria'] ?? 'Nombre de Categoría' ?> <span class="color-red">*</span> </label>
                                    <input id="category_name" class="form-control" type="text" name="category_name">
                                    <div class="container_alert"></div>
                                </div>

                                <div class="form-group d-flex justify-content-between align-items-center">
                                    <figure style="width: 14em; overflow: hidden; margin: 0;" class="rounded">
                                        <img id="main-category-image"
                                             src="<?= company_url("image"); ?>"
                                             class="rounded-0"
                                             style="display: block; width: 100%;"
                                             alt="">
                                    </figure>

                                    <button title="<?= $pageTexts['toltip_subir']?>" type="button"
                                            class="btn btn-success ml-3"
                                            data-toggle="modal" data-target="#modal_imagenes">
                                        <i class="fa-solid fa-edit"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php $this->load->view('components/crud/form_action_fab', [
        'formId' => 'form_add',
        'saveLabel' => $pageTexts['btn_save'] ?? '',
        'cancelLabel' => $pageTexts['boton_cancelar'] ?? '',
        'cancelUrl' => company_url('categories'),
        'saveButtonId' => 'boton_enviar_first',
    ]); ?>
    <!-- Load image modal -->
    <?php $this->load->view('company/categorias/modals/images'); ?>
</form>

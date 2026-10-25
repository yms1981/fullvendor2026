<?php
/**
 * @var $pageTexts array the translated string conglomerated for this view
 */
?>
<form id="form_add_rol_modal" class="forms-sample" action="<?= company_url('rols/save') ?>" method="post">
    <div class="form-group row">
        <div class="col-md-6 col-lg-6">
            <label><?= $pageTexts['type'] ?? 'Tipo' ?>: <span class="color-red">*</span></label>
            <input type="text" class="form-control" name="type" id="rol_type_add_modal" autocomplete="off" required="" placeholder="<?= $pageTexts['type'] ?? 'Tipo de Rol' ?>">
            <div class="container_alert"></div>
        </div>
    </div>
    
    <div class="form-group row">
        <div class="col-md-6 col-lg-6">
            <label><?= $pageTexts['desc_es'] ?? 'Descripción Español' ?>: <span class="color-red">*</span></label>
            <input type="text" class="form-control" name="desc_es" id="rol_desc_es_add_modal" autocomplete="off" required="" placeholder="<?= $pageTexts['desc_es'] ?? 'Descripción en Español' ?>">
            <div class="container_alert"></div>
        </div>
        <div class="col-md-6 col-lg-6">
            <label><?= $pageTexts['desc_en'] ?? 'Descripción Inglés' ?>: <span class="color-red">*</span></label>
            <input type="text" class="form-control" name="desc_en" id="rol_desc_en_add_modal" autocomplete="off" required="" placeholder="<?= $pageTexts['desc_en'] ?? 'Description in English' ?>">
            <div class="container_alert"></div>
        </div>
    </div>
    
    <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal">
            <?= $pageTexts['cancel'] ?? 'Cancelar' ?>
        </button>
        <button id="boton_enviar_add_rol_modal" type="submit" class="btn btn-success">
            <?= $pageTexts['save'] ?? 'Guardar' ?>
        </button>
    </div>
</form>


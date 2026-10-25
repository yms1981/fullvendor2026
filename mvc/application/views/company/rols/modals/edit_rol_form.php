<?php
/**
 * @var object $rol The editing rol
 * @var array $pageTexts The translated string conglomerated for this view
 */
?>
<form id="form_edit_rol_modal" class="forms-sample" action="<?= company_url('rols/update/'.$rol->rol_id) ?>" method="post">
    <input type="hidden" name="rol_id" value="<?= $rol->rol_id ?>">
    
    <div class="form-group row">
        <div class="col-md-6 col-lg-6">
            <label><?= $pageTexts['type'] ?? 'Tipo' ?>: <span class="color-red">*</span></label>
            <input type="text" class="form-control" name="type" id="rol_type_edit_modal" autocomplete="off" required="" placeholder="<?= $pageTexts['type'] ?? 'Tipo de Rol' ?>" value="<?= htmlspecialchars($rol->type ?? '') ?>">
            <div class="container_alert"></div>
        </div>
    </div>
    
    <div class="form-group row">
        <div class="col-md-6 col-lg-6">
            <label><?= $pageTexts['desc_es'] ?? 'Descripción Español' ?>: <span class="color-red">*</span></label>
            <input type="text" class="form-control" name="desc_es" id="rol_desc_es_edit_modal" autocomplete="off" required="" placeholder="<?= $pageTexts['desc_es'] ?? 'Descripción en Español' ?>" value="<?= htmlspecialchars($rol->desc_es ?? '') ?>">
            <div class="container_alert"></div>
        </div>
        <div class="col-md-6 col-lg-6">
            <label><?= $pageTexts['desc_en'] ?? 'Descripción Inglés' ?>: <span class="color-red">*</span></label>
            <input type="text" class="form-control" name="desc_en" id="rol_desc_en_edit_modal" autocomplete="off" required="" placeholder="<?= $pageTexts['desc_en'] ?? 'Description in English' ?>" value="<?= htmlspecialchars($rol->desc_en ?? '') ?>">
            <div class="container_alert"></div>
        </div>
    </div>
    
    <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal">
            <?= $pageTexts['cancel'] ?? 'Cancelar' ?>
        </button>
        <button id="boton_enviar_edit_rol_modal" type="submit" class="btn btn-success">
            <?= $pageTexts['guardar'] ?? $pageTexts['save'] ?? '' ?>
        </button>
    </div>
</form>


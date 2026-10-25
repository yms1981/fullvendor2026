<?php
/**
 * @var $defaultTerm object|null default Term of sale
 * @var $pageTexts array the translated string conglomerated for this view
 */
?>

<script>
    window.defaultEntity = <?= json_encode($defaultTerm) ?>;
</script>

<form id="form_add_term_modal" class="forms-sample" action="<?= company_url('terms/save') ?>" method="post">
    <div class="form-group row">
        <div class="col-md-6 col-lg-6">
            <label><?= $pageTexts['name'] ?? 'Nombre' ?>: <span class="color-red">*</span></label>
            <input type="text" class="form-control" name="name" id="term_name_add_modal" autocomplete="off" required="" placeholder="<?= $pageTexts['name'] ?? 'Nombre del Término' ?>">
            <div class="container_alert"></div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="form-group">
                <label><?= $pageTexts['default'] ?? 'Por Defecto' ?>:</label><br>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input style="height: 55px;position:absolute;top:16px;" type="checkbox" name="checkBoxDefault" id="term-default-modal">
            </div>
        </div>
    </div>
    
    <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal">
            <?= $pageTexts['cancel'] ?? 'Cancelar' ?>
        </button>
        <button id="boton_enviar_add_term_modal" type="submit" class="btn btn-success">
            <?= $pageTexts['guardar'] ?? 'Guardar' ?>
        </button>
    </div>
</form>


<?php
/**
 * @var object $term The editing term
 * @var array $pageTexts The translated string conglomerated for this view
 */
$isTermDefault = !empty($term->isDefault) || !empty($term->is_default)
    || (isset($term->default) && (int) $term->default === 1);
?>

<form id="form_edit_term_modal" class="forms-sample" action="<?= company_url('terms/update/'.$term->term_id) ?>" method="post">
    <input type="hidden" name="term_id" value="<?= $term->term_id ?>">
    
    <div class="form-group row">
        <div class="col-md-6 col-lg-6">
            <label><?= $pageTexts['name'] ?? 'Nombre' ?>: <span class="color-red">*</span></label>
            <input type="text" class="form-control" name="name" id="term_name_edit_modal" autocomplete="off" required="" placeholder="<?= $pageTexts['name'] ?? 'Nombre del Término' ?>" value="<?= htmlspecialchars($term->name ?? '') ?>">
            <div class="container_alert"></div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="form-group">
                <label><?= $pageTexts['default'] ?? 'Por Defecto' ?>:</label><br>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input style="height: 55px;position:absolute;top:16px;" type="checkbox" name="checkBoxDefault" id="term-default-edit-modal" <?= $isTermDefault ? 'checked="checked"' : '' ?>>
            </div>
        </div>
    </div>
    
    <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal">
            <?= $pageTexts['cancel'] ?? 'Cancelar' ?>
        </button>
        <button id="boton_enviar_edit_term_modal" type="submit" class="btn btn-success">
            <?= $pageTexts['guardar'] ?? $pageTexts['save'] ?? '' ?>
        </button>
    </div>
</form>


<?php
/**
 * @var object $group The editing group
 * @var array $pageTexts The translated string conglomerated for this view
 */
?>
<script>
    window.defaultEntity = <?= json_encode($group) ?>;
    window.fvGroupFormLang = <?= json_encode([
        'percentage_increase_option' => $pageTexts['percentage_increase_option'] ?? '',
        'percentage_decrease_option' => $pageTexts['percentage_decrease_option'] ?? '',
        'percentage_increase_label' => $pageTexts['percentage_increase_label'] ?? '',
        'percentage_decrease_label' => $pageTexts['percentage_decrease_label'] ?? '',
        'group_name_required' => $pageTexts['group_name_required'] ?? '',
        'group_save_error_generic' => $pageTexts['group_save_error_generic'] ?? '',
        'group_existente' => $pageTexts['group_existente'] ?? '',
        'group_name_already_exists' => $pageTexts['group_name_already_exists'] ?? '',
        'group_updated' => $pageTexts['group_updated'] ?? '',
        'ok' => $pageTexts['ok'] ?? '',
    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<form id="form_edit_group_modal" class="forms-sample" action="<?= company_url('groups/update/'.$group->group_id) ?>" method="post" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?= $group->group_id ?>">
    
    <div class="form-group row">
        <div class="col-md-6 col-lg-3">
            <label>
                <?= $pageTexts['nombre'] ?>: <span class="color-red">*</span>
            </label>
            <input type="text"
                   name="name"
                   id="group_name_edit_modal"
                   class="form-control"
                   autocomplete="off"
                   required
                   placeholder="<?= $pageTexts['nombre'] ?? 'Nombre del Grupo' ?>"
                   value="<?= htmlspecialchars($group->name ?? '') ?>">
            <div class="container_alert"></div>
        </div>

        <div class="col-md-6 col-lg-3">
            <label>
                <?= $pageTexts['porcentaje'] ?>:
            </label>
            <select class="form-control notranslate"
                    name="percentage_on_price"
                    id="percentage_on_price_edit_modal">
                <option value="" <?= ($group->percentage_on_price === null || $group->percentage_on_price === '' || $group->percentage_on_price === 'nothing') ? 'selected' : '' ?>>
                    <?= $pageTexts['ninguno'] ?>
                </option>
                <option value="Increase on Price List %" <?= ($group->percentage_on_price === 'Increase on Price List %') ? 'selected' : '' ?>>
                    <?= htmlspecialchars($pageTexts['percentage_increase_option'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </option>
                <option value="Decrease on Price List %" <?= ($group->percentage_on_price === 'Decrease on Price List %') ? 'selected' : '' ?>>
                    <?= htmlspecialchars($pageTexts['percentage_decrease_option'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </option>
            </select>
        </div>

        <div class="col-md-6 col-lg-3" id="price-adjustment-field-edit-modal" style="display: none;">
            <label id="price-adjustment-label-edit-modal"></label>
            <input name="percent_price_amount"
                   id="percent_price_amount_edit_modal"
                   class="form-control mask text-left"
                   value="<?= htmlspecialchars($group->percent_price_amount ?? '') ?>">
        </div>

        <div class="col-md-6 col-lg-3">
            <label>
                <?= $pageTexts['default'] ?? 'Por Defecto' ?>:
            </label>
            <div class="d-flex align-items-center" style="min-height: 38px;">
                <input type="checkbox"
                       name="checkBoxDefault"
                       id="group-default-edit-modal"
                       class="mt-0"
                       value="1"
                       <?= (isset($group->isDefault) && $group->isDefault) ? 'checked' : '' ?>>
            </div>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal">
            <?= $pageTexts['cancelar'] ?? 'Cancelar' ?>
        </button>
        <button id="boton_enviar_edit_group_modal" type="submit" class="btn btn-success">
            <?= $pageTexts['guardar'] ?? $pageTexts['save'] ?? '' ?>
        </button>
    </div>
</form>


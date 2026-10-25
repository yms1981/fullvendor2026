<?php
/**
 * @var $unit object the current editing unit
 * @var $defaultUnit object|null default Unit of sale
 * @var $pageTexts array the translated string conglomerated for this view
 */
?>

<script>
    window.defaultEntity = <?= json_encode($defaultUnit) ?>;
    window.currentUnitId = <?= json_encode($unit->unit_id) ?>;
    window.currentUnitIsDefault = <?= json_encode($unit->isDefault ?? false) ?>;
</script>

<form id="form_edit_unit_modal" class="forms-sample"
      action="<?= company_url('units/update/' . $unit->unit_id) ?>" method="post">
    <input type="hidden" name="unit_id" value="<?= $unit->unit_id ?>">
    
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label><?= $pageTexts['name'] ?? 'Nombre' ?>: <span class="color-red">*</span></label>
                <input class="form-control" name="unit" id="unit_name_edit_modal" value="<?= $unit->unit ?>" required>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label><?= $pageTexts['description'] ?? 'Descripción' ?>: <span class="color-red">*</span></label>
                <input type="text" class="form-control" name="description" id="unit_description_edit_modal"
                       value="<?= $unit->description ?>" required>
            </div>
        </div>
    </div>
    
    <?php
    $isDecimalFormat = ($unit->formato ?? '') === 'decimal';
    $decimalsValue = $isDecimalFormat ? (int) ($unit->decimales ?? 2) : (int) ($unit->decimales ?? 2);
    if ($decimalsValue < 0 || $decimalsValue > 9) {
        $decimalsValue = 2;
    }
    ?>
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label><?= htmlspecialchars((string) ($pageTexts['formato_unidad'] ?? ''), ENT_QUOTES, 'UTF-8') ?>: <span class="color-red">*</span></label>
                <br>
                <div class="btn-group fv-unit-format">
                    <input id="entero_edit_modal" hidden type="radio" name="formato"
                           class="btn-check" value="entero"
                        <?= ($unit->formato == 'entero') ? 'checked' : '' ?>>
                    <label for="entero_edit_modal"
                           class="especial_label border"><?= htmlspecialchars((string) ($pageTexts['entero'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
                    <input id="decimal_edit_modal" hidden type="radio" name="formato"
                           class="btn-check" value="decimal"
                        <?= ($unit->formato == 'decimal') ? 'checked' : '' ?>>
                    <label for="decimal_edit_modal" class="especial_label border"><?= htmlspecialchars((string) ($pageTexts['decimal'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label><?= htmlspecialchars((string) ($pageTexts['default'] ?? ''), ENT_QUOTES, 'UTF-8') ?>: <span class="color-red">*</span></label>
                <br>
                <input id="unit-default-edit-modal" type="checkbox" name="checkBoxDefault"
                    <?= ($unit->isDefault ?? false) ? 'checked="true"' : '' ?>>
            </div>
        </div>
    </div>
    <div class="row" id="unit-decimals-row-edit" style="<?= $isDecimalFormat ? '' : 'display:none;' ?>">
        <div class="col-md-6">
            <div class="form-group">
                <label for="unit_decimales_edit_modal"><?= htmlspecialchars((string) ($pageTexts['decimales'] ?? ''), ENT_QUOTES, 'UTF-8') ?>: <span class="color-red">*</span></label>
                <input type="number" class="form-control" name="decimales" id="unit_decimales_edit_modal"
                       min="0" max="9" step="1" inputmode="numeric"
                       value="<?= $decimalsValue ?>"
                       <?= $isDecimalFormat ? 'required' : '' ?>>
            </div>
        </div>
    </div>
    <style>
    .fv-unit-format .especial_label {
        display: inline-block;
        min-width: 6.5rem;
        margin: 0;
        text-align: center;
    }
    </style>
    <script>
    (function () {
        var entero = document.getElementById('entero_edit_modal');
        var decimal = document.getElementById('decimal_edit_modal');
        var row = document.getElementById('unit-decimals-row-edit');
        var input = document.getElementById('unit_decimales_edit_modal');
        function toggleDecimals() {
            var on = decimal && decimal.checked;
            if (row) {
                row.style.display = on ? '' : 'none';
            }
            if (input) {
                input.required = !!on;
                if (on && input.value === '') {
                    input.value = '2';
                }
            }
        }
        if (entero) {
            entero.addEventListener('change', toggleDecimals);
        }
        if (decimal) {
            decimal.addEventListener('change', toggleDecimals);
        }
        toggleDecimals();
    })();
    </script>
    
    <div class="modal-footer">
        <button type="button" class="btn btn-danger" data-dismiss="modal">
            <?= $pageTexts['cancel'] ?? 'Cancelar' ?>
        </button>
        <button id="boton_enviar_edit_unit_modal" type="submit" class="btn btn-success">
            <?= $pageTexts['guardar'] ?? $pageTexts['save'] ?? '' ?>
        </button>
    </div>
</form>


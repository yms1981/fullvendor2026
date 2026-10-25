<?php
/**
 * @var $defaultUnit object|null default Unit of sale
 * @var $pageTexts array the translated string conglomerated for this view
 */
?>

<script>
    window.defaultEntity = <?= json_encode($defaultUnit) ?>;
</script>

<form id="form_add_unit_modal" class="forms-sample" action="<?= company_url('units/save') ?>" method="post">
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label><?= $pageTexts['name'] ?? 'Nombre' ?>: <span class="color-red">*</span></label>
                <input type="text" class="form-control" name="unit" id="unit_name_modal" value="" required>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label><?= $pageTexts['description'] ?? 'Descripción' ?>: <span class="color-red">*</span></label>
                <input type="text" class="form-control" name="description" id="unit_description_modal" value="" required>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label><?= htmlspecialchars((string) ($pageTexts['formato_unidad'] ?? ''), ENT_QUOTES, 'UTF-8') ?>: <span class="color-red">*</span></label>
                <br>
                <div class="btn-group fv-unit-format">
                    <input id="entero_modal" hidden type="radio" name="formato"
                           class="btn-check" value="entero" checked>
                    <label for="entero_modal"
                           class="especial_label border"><?= htmlspecialchars((string) ($pageTexts['entero'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
                    <input id="decimal_modal" hidden type="radio" name="formato"
                           class="btn-check" value="decimal">
                    <label for="decimal_modal" class="especial_label border"><?= htmlspecialchars((string) ($pageTexts['decimal'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label><?= htmlspecialchars((string) ($pageTexts['default'] ?? ''), ENT_QUOTES, 'UTF-8') ?>: <span class="color-red">*</span></label>
                <br>
                <input type="checkbox" name="checkBoxDefault" id="unit-default-modal">
            </div>
        </div>
    </div>
    <div class="row" id="unit-decimals-row-add" style="display:none;">
        <div class="col-md-6">
            <div class="form-group">
                <label for="unit_decimales_modal"><?= htmlspecialchars((string) ($pageTexts['decimales'] ?? ''), ENT_QUOTES, 'UTF-8') ?>: <span class="color-red">*</span></label>
                <input type="number" class="form-control" name="decimales" id="unit_decimales_modal"
                       min="0" max="9" step="1" inputmode="numeric" value="2">
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
        var entero = document.getElementById('entero_modal');
        var decimal = document.getElementById('decimal_modal');
        var row = document.getElementById('unit-decimals-row-add');
        var input = document.getElementById('unit_decimales_modal');
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
        <button id="boton_enviar_add_unit_modal" type="submit" class="btn btn-success">
            <?= $pageTexts['guardar'] ?? 'Guardar' ?>
        </button>
    </div>
</form>


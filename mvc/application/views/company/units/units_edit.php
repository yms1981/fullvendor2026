<?php

/**
 * @var $unit object the current editing unit
 * @var $pageTexts array the translated string conglomerated for this view
 */
?>

<div class="container-fluid page-body-wrapper pt-3">
    <div class="main-panel">
        <div class="container-fluid">
            <div class="page-header">
                <h3 class="page-title"> <?= $pageTexts['titulo_edit'] ?></h3>
            </div>
            <div class="card contentHeight">
                <div class="card-body">
                    <form id="form_edit" class="forms-sample"
                          action="<?= company_url('units/update/' . $unit->unit_id) ?>" method="post">
                        <div class="row">

                            <div class="col-3">
                                <div class="form-group">
                                    <label><?= $pageTexts['name'] ?>: <span class="color-red">*</span></label>
                                    <input class="form-control" name="unit" value="<?= $unit->unit ?>" required>
                                    <input type="hidden" class="form-control" name="unit_id"
                                           value="<?= $unit->unit_id ?>" required="">
                                </div>

                            </div>
                            <div class="col-3">
                                <div class="form-group">
                                    <label><?= $pageTexts['description'] ?>: <span class="color-red">*</span></label>
                                    <input type="text" class="form-control" name="description"
                                           value="<?= $unit->description ?>">
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label><?= $pageTexts['formato_unidad'] ?>: <span
                                                    class="color-red">*</span></label>
                                            <br>
                                            <div class="btn-group">
                                                <input id="entero" hidden type="radio" name="formato"
                                                       class="btn-check" value="entero"
                                                    <?= ($unit->formato == 'entero') ? 'checked' : '' ?>>
                                                <label for="entero"
                                                       class="especial_label border"><?= $pageTexts['entero'] ?></label>
                                                <input id="decimal" hidden type="radio" name="formato"
                                                       class="btn-check" value="decimal"
                                                    <?= ($unit->formato == 'decimal') ? 'checked' : '' ?>>
                                                <label for="decimal" class="especial_label border"><?= htmlspecialchars((string) ($pageTexts['decimal'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label><?= $pageTexts['default'] ?>: <span
                                                    class="color-red">*</span></label>
                                            <br>
                                            <input id="unit-default" type="checkbox" name="checkBoxDefault"
                                                <?= ($unit->isDefault != 0) ? 'checked="true"' : '' ?>>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-3" id="unit-decimals-row-page" style="<?= (($unit->formato ?? '') === 'decimal') ? '' : 'display:none;' ?>">
                                <div class="form-group">
                                    <label for="unit_decimales"><?= htmlspecialchars((string) ($pageTexts['decimales'] ?? ''), ENT_QUOTES, 'UTF-8') ?>: <span class="color-red">*</span></label>
                                    <input type="number" class="form-control" name="decimales" id="unit_decimales"
                                           min="0" max="9" step="1" inputmode="numeric"
                                           value="<?= (int) ($unit->decimales ?? 2) ?>">
                                </div>
                            </div>

                        </div>
                    </form>
                    <script>
                    (function () {
                        var decimal = document.getElementById('decimal');
                        var entero = document.getElementById('entero');
                        var row = document.getElementById('unit-decimals-row-page');
                        var input = document.getElementById('unit_decimales');
                        function toggleDecimals(fromUser) {
                            var on = decimal && decimal.checked;
                            if (row) row.style.display = on ? '' : 'none';
                            if (input) {
                                input.required = !!on;
                                input.disabled = !on;
                                if (!on) input.value = '0';
                                if (on && fromUser && (input.value === '' || input.value === '0')) input.value = '2';
                            }
                        }
                        if (entero) entero.addEventListener('change', function () { toggleDecimals(true); });
                        if (decimal) decimal.addEventListener('change', function () { toggleDecimals(true); });
                        toggleDecimals(false);
                    })();
                    </script>
                    <?php $this->load->view('components/crud/form_action_fab', [
                        'formId' => 'form_edit',
                        'saveLabel' => $pageTexts['guardar'] ?? $pageTexts['save'] ?? '',
                        'cancelLabel' => $pageTexts['cancel'] ?? '',
                        'cancelUrl' => company_url('units'),
                    ]); ?>
                </div>
            </div>
        </div>
    </div>
</div>

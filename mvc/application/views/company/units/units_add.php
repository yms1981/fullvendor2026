<?php
/**
 * @var $defaultUnit object|null default Unit of sale
 * @var $pageTexts array the translated string conglomerated for this view
 */
?>

<script>
    window.defaultEntity = <?= json_encode($defaultUnit) ?>;
</script>

<div class="container-fluid page-body-wrapper pt-3">
    <div class="main-panel">
        <div class="container-fluid">
            <div class="page-header">
                <h3 class="page-title"> <?= $pageTexts['titulo_add'] ?></h3>
            </div>
            <div class="card contentHeight">
                <div class="card-body">
                    <form id="form_add" class="forms-sample" action="<?= company_url('units/save') ?>" method="post">
                        <div class="row">
                            <div class="col-3">
                                <div class="form-group">
                                    <label><?= $pageTexts['name'] ?>: <span class="color-red">*</span></label>
                                    <input type="text" class="form-control" name="unit"  value="" required="">
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="form-group">
                                    <label><?= $pageTexts['description'] ?>: <span class="color-red">*</span></label>
                                    <input type="text" class="form-control" name="description"
                                           value="<?php echo set_value('description'); ?>">
                                </div>

                            </div>
                            <div class="col-3">

                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label><?= $pageTexts['formato_unidad'] ?>: <span class="color-red">*</span></label>
                                            <br>
                                            <div class="btn-group">
                                                <input id="entero" hidden type="radio" name="formato"
                                                       class="btn-check" value="entero" checked>
                                                <label for="entero"
                                                       class="especial_label border"><?= $pageTexts['entero'] ?></label>
                                                <input id="decimal" hidden type="radio" name="formato"
                                                       class="btn-check" value="decimal">
                                                <label for="decimal" class="especial_label border"><?= htmlspecialchars((string) ($pageTexts['decimal'] ?? ''), ENT_QUOTES, 'UTF-8') ?></label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">

                                        <div class="form-group">
                                            <label><?= $pageTexts['default'] ?>: <span
                                                    class="color-red">*</span></label>
                                            <br>
                                            <input type="checkbox" name="checkBoxDefault" id="unit-default">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-3" id="unit-decimals-row-page" style="display:none;">
                                <div class="form-group">
                                    <label for="unit_decimales"><?= htmlspecialchars((string) ($pageTexts['decimales'] ?? ''), ENT_QUOTES, 'UTF-8') ?>: <span class="color-red">*</span></label>
                                    <input type="number" class="form-control" name="decimales" id="unit_decimales"
                                           min="0" max="9" step="1" inputmode="numeric" value="2" disabled>
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
                        function toggleDecimals() {
                            var on = decimal && decimal.checked;
                            if (row) row.style.display = on ? '' : 'none';
                            if (input) {
                                input.required = !!on;
                                input.disabled = !on;
                                if (on && input.value === '') input.value = '2';
                            }
                        }
                        if (entero) entero.addEventListener('change', toggleDecimals);
                        if (decimal) decimal.addEventListener('change', toggleDecimals);
                    })();
                    </script>
                    <?php $this->load->view('components/crud/form_action_fab', [
                        'formId' => 'form_add',
                        'saveLabel' => $pageTexts['guardar'] ?? '',
                        'cancelLabel' => $pageTexts['cancel'] ?? '',
                        'cancelUrl' => company_url('units'),
                    ]); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
/**
 * @var $defaultGroup integer|null default group id
 * @var $pageTexts array the translated string conglomerated for this view
 */
?>

<script>
    window.defaultEntity = <?= json_encode($defaultGroup) ?>;
</script>

<div class="container-fluid page-body-wrapper">
    <div class="main-panel">
        <div class="container-fluid pt-3">
            <div class="page-header">
                <h3 class="page-title">
                    <?= strtoupper($pageTexts['titulo_add']) ?>
                </h3>
            </div>

            <div class="card">
                <div class="card-body">
                    <form id="form_add"
                          class="forms-sample"
                          action="<?= company_url('groups/save') ?>"
                          method="post"
                          enctype="multipart/form-data">

                        <div class="form-group row">
                            <div class="col-3">
                                <label>
                                    <?= $pageTexts['nombre'] ?>: <span class="color-red">*</span>
                                </label>
                                <input type="text"
                                       name="name"
                                       class="form-control"
                                       autocomplete="off"
                                       required
                                       placeholder="Name of Group">
                            </div>

                            <div class="col-3">
                                <label>
                                    <?= $pageTexts['porcentaje'] ?>: <span class="color-red">*</span>
                                </label>
                <select class="form-control notranslate"
                        name="percentage_on_price">
                    <option value="" selected><?= $pageTexts['ninguno'] ?></option>
                    <option value="Increase on Price List %">Increase on Price List %</option>
                    <option value="Decrease on Price List %">Decrease on Price List %</option>
                </select>
                            </div>

                            <div class="col-3" id="price-adjustment-field" style="display: none;">
                                <label id="price-adjustment-label"></label>
                                <input name="percent_price_amount"
                                       class="form-control mask text-left">
                            </div>

                            <div class="col-3">
                                <div class="form-group d-flex">
                                    <label>
                                        <?= $pageTexts['default'] ?>: <span class="color-red">*</span>
                                    </label>
                                    <span class="ml-2"></span>
                                    <input type="checkbox"
                                           name="checkBoxDefault"
                                           id="group-default"
                                           value="1">
                                </div>
                            </div>
                        </div>

                    </form>
                    <?php $this->load->view('components/crud/form_action_fab', [
                        'formId' => 'form_add',
                        'saveLabel' => $pageTexts['guardar'] ?? '',
                        'cancelLabel' => $pageTexts['cancelar'] ?? '',
                        'cancelUrl' => company_url('groups'),
                    ]); ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
/**
 * @var $group Object editing group
 * @var $pageTexts array the translated string conglomerated for this view
 */
?>

<div class="container-fluid page-body-wrapper">
    <div class="main-panel">
        <div class="container-fluid pt-3">
            <div class="page-header">
                <h3 class="page-title">
                    <?= strtoupper($pageTexts['titulo_edit']) ?>
                </h3>
            </div>

            <div class="card">
                <div class="card-body">
                    <form id="form_edit"
                          class="forms-sample"
                          action="<?= company_url('groups/update/'.$group->group_id) ?>"
                          method="post"
                          enctype="multipart/form-data">

                        <input type="hidden" name="id" value="<?= $group->group_id ?>">

                        <div class="form-group row">
                            <div class="col-3">
                                <label>
                                    <?= $pageTexts['nombre'] ?>: <span class="color-red">*</span>
                                </label>
                                <input type="text"
                                       name="name"
                                       class="form-control"
                                       autocomplete="off"
                                       value="<?= $group->name ?>"
                                       required
                                       placeholder="Name of Group">
                            </div>

                            <div class="col-3">
                                <label>
                                    <?= $pageTexts['porcentaje'] ?>: <span class="color-red">*</span>
                                </label>
                                <select class="form-control notranslate"
                                        name="percentage_on_price">
                                    <option value="" <?= ($group->percentage_on_price === null || $group->percentage_on_price === '' || $group->percentage_on_price === 'nothing') ? 'selected' : '' ?>>
                                        <?= $pageTexts['ninguno'] ?>
                                    </option>
                                    <option value="Increase on Price List %" <?= $group->percentage_on_price === 'Increase on Price List %' ? 'selected' : '' ?>>
                                        Increase on Price List %
                                    </option>
                                    <option value="Decrease on Price List %" <?= $group->percentage_on_price === 'Decrease on Price List %' ? 'selected' : '' ?>>
                                        Decrease on Price List %
                                    </option>
                                </select>
                            </div>

                            <div class="col-3" id="price-adjustment-field" style="display: none;">
                                <label id="price-adjustment-label"></label>
                                <input name="percent_price_amount"
                                       value="<?= $group->percent_price_amount ?>""
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
                                           value="1"
                                        <?= $group->isDefault ? 'checked' : '' ?>>
                                </div>
                            </div>
                        </div>

                    </form>
                    <?php $this->load->view('components/crud/form_action_fab', [
                        'formId' => 'form_edit',
                        'saveLabel' => $pageTexts['guardar'] ?? '',
                        'cancelLabel' => $pageTexts['cancelar'] ?? '',
                        'cancelUrl' => company_url('groups'),
                    ]); ?>
                </div>
            </div>
        </div>
    </div>
</div>

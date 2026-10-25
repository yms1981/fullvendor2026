<?php
/**
 * @var $pageTexts array the translated string conglomerated for this view
 */
?>

<div class="container-fluid page-body-wrapper">
    <div class="main-panel">
        <div class="container-fluid pt-3">
            <div class="page-header">
                <h3 class="page-title"> AGREGAR NUEVO PAIS </h3>
            </div>
            <div class="card">
                <div class="card-body">
                    <form id="form_add" class="forms-sample" action="<?= company_url('countries/save') ?>"
                          method="post">
                        <div class="form-group row">
                            <div class="col-md-6 col-lg-6">
                                <label><?= $pageTexts['name'] ?>: <span class="color-red">*</span></label>
                                <input type="text" class="form-control" name="name" autocomplete="off" required=""
                                       placeholder="Country name">
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-3 col-lg-3">
                                <label><?= $pageTexts['code'] ?? 'Sort name' ?>: <span class="color-red">*</span></label>
                                <input type="text" class="form-control" name="sortname" autocomplete="off" required=""
                                       placeholder="Country code">
                            </div>
                            <div class="col-md-3 col-lg-3">
                                <label><?= $pageTexts['phone_code'] ?? 'Phone code' ?>: <span class="color-red">*</span></label>
                                <input type="text" class="form-control" name="phonecode" autocomplete="off" required=""
                                       placeholder="Country phone code">
                            </div>
                        </div>
                    </form>
                    <?php $this->load->view('components/crud/form_action_fab', [
                        'formId' => 'form_add',
                        'saveLabel' => (string) ($pageTexts['guardar'] ?? ''),
                        'cancelLabel' => (string) ($pageTexts['cancel'] ?? ''),
                        'cancelUrl' => company_url('countries'),
                    ]); ?>
                </div>
            </div>
        </div>
    </div>
</div>

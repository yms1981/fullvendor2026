<?php
/**
 * @var $pageTexts array the translated string conglomerated for this view
 */
?>

<div class="container-fluid page-body-wrapper">
    <div class="main-panel">
        <div class="container-fluid pt-3">
            <div class="page-header">
                <h3 class="page-title"> AGREGAR NUEVA CIUDAD </h3>
            </div>
            <div class="card">
                <div class="card-body">
                    <form id="form_add" class="forms-sample" action="<?= company_url('cities/save') ?>"
                          method="post">
                        <div class="form-group row">
                            <div class="col-md-6 col-lg-6">
                                <label><?= $pageTexts['name'] ?>: <span class="color-red">*</span></label>
                                <input type="text" class="form-control" name="name" autocomplete="off" required=""
                                       placeholder="City name">
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-6 mb-20 position-relative">
                                <label><?= $pageTexts['state'] ?? "Estado" ?> <span>*</span></label>
                                <input type="text" class="form-control validate" id="state_name">
                                <div class="invalid-feedback text-white bg-danger border rounded p-1 fw-bold"></div>
                                <input type="hidden" id="state_id" name="state_id">
                                <div id="state_suggestions" class="suggestions-box"></div>
                            </div>
                        </div>
                    </form>
                    <?php $this->load->view('components/crud/form_action_fab', [
                        'formId' => 'form_add',
                        'saveLabel' => (string) ($pageTexts['guardar'] ?? ''),
                        'cancelLabel' => (string) ($pageTexts['cancel'] ?? ''),
                        'cancelUrl' => company_url('cities'),
                    ]); ?>
                </div>
            </div>
        </div>
    </div>
</div>

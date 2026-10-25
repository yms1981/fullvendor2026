<?php
/**
 * @var $state object the current editing state
 * @var $pageTexts array the translated string conglomerated for this view
 */
?>

<script>
    countrySelected = true;
</script>

<div class="container-fluid page-body-wrapper">
    <div class="main-panel">
        <div class="container-fluid pt-3">
            <div class="page-header">
                <h3 class="page-title"> EDITAR ESTADO </h3>
            </div>
            <div class="card">
                <div class="card-body">
                    <form id="form_edit" class="forms-sample" action="<?= company_url('states/update/'.$state->id) ?>"
                          method="post">
                        <input type="hidden" name="id" value="<?= $state->id ?>">
                        <div class="form-group row">
                            <div class="col-md-6 col-lg-6">
                                <label><?= $pageTexts['name'] ?>: <span class="color-red">*</span></label>
                                <input type="text" class="form-control" name="name" autocomplete="off" required=""
                                       placeholder="State name" value="<?= $state->name ?>">
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-6 col-lg-6 mb-10 position-relative">
                                <label><?= $pageTexts['country'] ?? "Pais" ?> <span>*</span></label>
                                <input type="text" class="form-control validate" id="country_name" value="<?= $state->country->name ?>">
                                <div class="invalid-feedback text-white bg-danger border rounded p-1 fw-bold"></div>
                                <input type="hidden" id="country_id" name="country_id" value="<?= $state->country_id ?>">
                                <div id="country_suggestions" class="suggestions-box"></div>
                            </div>
                        </div>
                    </form>
                    <?php $this->load->view('components/crud/form_action_fab', [
                        'formId' => 'form_edit',
                        'saveLabel' => (string) ($pageTexts['guardar'] ?? ''),
                        'cancelLabel' => (string) ($pageTexts['cancel'] ?? ''),
                        'cancelUrl' => company_url('states'),
                    ]); ?>
                </div>
            </div>
        </div>
    </div>
</div>

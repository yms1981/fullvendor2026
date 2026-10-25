<?php

/**
 * @var $term object the current editing term
 * @var $pageTexts array the translated string conglomerated for this view
 */
$isTermDefault = !empty($term->isDefault) || !empty($term->is_default)
    || (isset($term->default) && (int) $term->default === 1);
?>

<div class="container-fluid page-body-wrapper">
    <div class="main-panel">
        <div class="container-fluid pt-3">
            <div class="page-header">
                <h3 class="page-title"> <?= strtoupper($pageTexts['titulo_editar']) ?></h3>
            </div>
            <div class="card">
                <div class="card-body">
                    <form id="form_edit" class="forms-sample"
                        action="<?= company_url('terms/update/'. $term->term_id) ?>" method="post">
                        <input type="hidden" name="term_id" value="<?= $term->term_id ?>">
                        <div class="form-group row">
                            <div class="col-md-6 col-lg-6">
                                <label><?= $pageTexts['name'] ?>: <span class="color-red">*</span></label>
                                <input type="text" class="form-control" name="name" autocomplete="off" required=""
                                    placeholder="Name of Group" value="<?= $term->name ?>">
                                <input type="hidden" name="term_id" value="<?= $term->term_id ?>" required="">
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-group">
                                    <label><?= $pageTexts['default'] ?>: <span class="color-red">*</span></label><br>
                                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input style="height: 55px;position:absolute;top:16px;" id="term-default"
                                        type="checkbox" name="checkBoxDefault"
                                        <?= $isTermDefault ? 'checked="checked"' : '' ?>
                                    >
                                </div>
                            </div>
                        </div>
                    </form>
                    <?php $this->load->view('components/crud/form_action_fab', [
                        'formId' => 'form_edit',
                        'saveLabel' => $pageTexts['guardar'] ?? $pageTexts['save'] ?? '',
                        'cancelLabel' => $pageTexts['cancel'] ?? '',
                        'cancelUseHistory' => true,
                    ]); ?>
                </div>
            </div>
        </div>
    </div>
</div>

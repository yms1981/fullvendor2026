<?php
/**
 * @var $defaultTerm object|null default Term of sale
 * @var $pageTexts array the translated string conglomerated for this view
 */
?>

<script>
    window.defaultEntity = <?= json_encode($defaultTerm) ?>;
</script>

<div class="container-fluid page-body-wrapper">
  <div class="main-panel">
    <div class="container-fluid pt-3">
      <div class="page-header">
        <h3 class="page-title"> <?= strtoupper($pageTexts['titulo_add']) ?></h3>
      </div>
      <div class="card">
        <div class="card-body">
          <form id="form_add" class="forms-sample" action="<?= company_url('terms/save') ?>" method="post">
            <div class="form-group row">
             <div class="col-md-6 col-lg-6">
              <label><?= $pageTexts['name'] ?>: <span class="color-red">*</span></label>
              <input type="text" class="form-control" name="name" autocomplete="off" required="" placeholder="Name of Term">
            </div>
            <div class="col-md-6 col-lg-4">
              <div class="form-group">
                <label><?= $pageTexts['default'] ?>: <span class="color-red">*</span></label><br>
                &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input style="height: 55px;position:absolute;top:16px;" type="checkbox" name="checkBoxDefault" id="term-default">
              </div>
            </div>
          </div>
        </form>
        <?php $this->load->view('components/crud/form_action_fab', [
            'formId' => 'form_add',
            'saveLabel' => $pageTexts['guardar'] ?? '',
            'cancelLabel' => $pageTexts['cancel'] ?? '',
            'cancelUrl' => company_url('terms'),
        ]); ?>
      </div>
    </div>
  </div>

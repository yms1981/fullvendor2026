<div class="container-fluid page-body-wrapper pt-3">
  <div class="main-panel">
    <div class="container-fluid">
      <div class="page-header">
        <h3 class="page-title"> <?= @$title ?></h3>
      </div>
      <div class="card contentHeight">
        <div class="card-body">
          <form id="catalogAdd" class="forms-sample" action="<?= company_url('currencies/create') ?>" method="post">
            <div class="row">
               <div class="col-md-6 col-lg-4"> 
                <div class="form-group">
                <label>Country: <span class="color-red">*</span></label>
                <select class="form-control" name="country_id"  required="">
                  <?php foreach ($countrylist as $clist) { ?>
                  <option value="<?=$clist->name?>">
                    <?=$clist->name?>
                  </option>
                <?php } ?>
                </select>
              </div>
              </div>
              <div class="col-md-6 col-lg-4">
                <div class="form-group">
                  <label>Currency Name: <span class="color-red">*</span></label>
                  <input type="text" class="form-control" name="currency"  value="<?php echo set_value('currency'); ?>" required="">
                </div>
              </div>
              <div class="col-md-6 col-lg-4">
                 <div class="form-group">
                  <label>Code: <span class="color-red">*</span></label>
                  <input type="text" class="form-control" name="code"  value="<?php echo set_value('code'); ?>" required="">
                  
                </div>
              </div>
             
              <div class="col-md-6 col-lg-4">
                 <div class="form-group">
                  <label>Symbol: <span class="color-red">*</span></label>
                  <input type="text" class="form-control" name="symbol"  value="<?php echo set_value('symbol'); ?>" required="">
                </div>
              </div>
            </div>
            <div class="form-group">
              <button type="submit" class="btn btn-success mr-2">Submit</button>
              <a class="btn btn-danger mr-2" href="<?= company_url('currencies/lists') ?>">
                Back
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>

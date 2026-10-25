<div class="container-fluid page-body-wrapper">
  <div class="main-panel">
    <div class="container-fluid pt-3">
      <div class="page-header">
        <h3 class="page-title"> <?=@$title?></h3>
      </div>
      <div class="card">
        <div class="card-body">
          <form class="forms-sample" action="<?= company_url('twiliosms/sendSms') ?>" method="post">
            <div class="form-group row">
               <div class="col-md-6 col-lg-6">
                <label>Phone Number: <span class="color-red">*</span></label>
                <input type="tel" class="form-control" id="phoneNumber" name="sms_recipient" autocomplete="off"  required="" placeholder="Phone Number">
              </div>
            </div>
             <div class="form-group row">
               <div class="col-md-6 col-lg-6">
                <label>Message: <span class="color-red">*</span></label>
                <textarea class="form-control" name="sms_message" rows="10" required=""></textarea>
              </div>
            </div>
            <div class="form-group">
              <button type="submit" class="btn btn-success mr-2">Submit</button>
             <a class="btn btn-danger mr-2" href="<?= company_url('catalog/requestLists') ?>">
                    Cancel
                  </a>
            </div>
          </form>
        </div>
      </div>
    </div>
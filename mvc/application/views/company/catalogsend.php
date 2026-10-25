<div class="container-fluid page-body-wrapper">
  <div class="main-panel">
    <div class="container-fluid pt-3">
      <div class="page-header">
        <h3 class="page-title"> <?=@$title?></h3>
      </div>
      <div class="card">
        <div class="card-body">
          <form class="forms-sample" action="<?= company_url('catalog/send_catalog/'.$requestId) ?>" method="post" >
             <div class="row">
              <div class="col-md-6 col-lg-4">
                <div class="form-group">
                <label>Select Catalog: <span class="color-red">*</span></label>
                 <select class="form-control" name="catalog_id"  required="">
                  <?php foreach ($catalogs as $catlog) { ?>
                  <option value="<?=$catlog->catalog_id?>"><?=$catlog->catalog_name?></option>
                <?php } ?>
                
                </select>
              </div>
            </div>
             <div class="col-md-6 col-lg-4">
                <div class="form-group">
                <label>Text Catalog : <span class="color-red">*</span></label>
               <input type="text" class="form-control" name="category_text" autocomplete="off" value="" required="" placeholder="Text of Catalog">
              </div>
            </div>
              <div class="col-md-6 col-lg-4">
                <div class="form-group">
                <label>Hide Prices : <span class="color-red">*</span></label>
               <input type="checkbox" name="hide_price" value="1" >
              </div>
            </div>
                <div class="col-md-6 col-lg-4">
                <div class="form-group">
                <label>Catalog Type: <span class="color-red">*</span></label>
                 <select class="form-control" name="catlog_type"  required="">
                  <option value="Download Catalog">Download Catalog</option>
                  <option value="Send by Email">Send by Email</option>
                </select>
              </div>
            </div>
             <div class="col-md-6 col-lg-4">
                <div class="form-group">
                <label>Email Text (body email text) : <span class="color-red">*</span></label>
               <textarea name="catalog_email_text" rows="6" class="form-control"></textarea>
              </div>
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


    <!-- SELECT od.order_id,od.product_id,od.qty,od.sale_price,ord.order_id,ord.company_id,ord.payment_status FROM `order_details` AS od JOIN orders AS ord ON ord.order_id=od.order_id WHERE od.product_id='3' AND ord.company_id='15' AND ord.payment_status='1' -->
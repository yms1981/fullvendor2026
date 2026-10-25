<div class="container-fluid page-body-wrapper">
  <div class="main-panel">
    <div class="container-fluid pt-3">
     <div class="row">
        <div class="col-md-6">
          <div class="page-header">
            <h3 class="page-title"> <?= @$title ?> </h3>
          </div>
        </div>
        <div class="col-md-6 text-right">
          <a href="<?= import_template_download_url('warehousecsv.xlsx') ?>" download class="btn btn-success mt-2 mt-sm-0 btn-icon-text"><i class="mdi mdi-download"></i> Download Sample format</a>
        </div>
      </div>
      <div class="card">
        <div class="card-body">
          <form class="forms-sample" action="<?= company_url('warehouse/csvImportSuccess') ?>" method="post" enctype="multipart/form-data">
            <div class="form-group">
              <label>CSV file: <span class="color-red">*</span></label><br/>
              <div class="fileinput fileinput-new" data-provides="fileinput">
               
             
                <div>
                  <span class="btn btn-default btn-file">
                    <span class="fileinput-new">Select file</span>
                    <input type="file" name="file" accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel" required="">
                  </span>
                  <a href="javascript:void(0)" class="btn btn-danger fileinput-exists" data-dismiss="fileinput">Remove</a>
                </div>
              </div>
              <div class="clearfix margin-top-10 m-b-20 f12" style="display: block;">
                <span class="label label-danger">Format</span>&nbsp;csv
               
              </div>
            </div>
            <div class="form-group">
              <button type="submit" class="btn btn-success mr-2">Submit</button>
             <a class="btn btn-danger mr-2" href="<?= company_url('warehouse/lists') ?>">
                    Cancel
                  </a>
            </div>
          </form>
        </div>
      </div>
    </div>

<!-- partial -->
<div class="container-fluid page-body-wrapper pt-3">
  <div class="main-panel">
    <div class="container-fluid contentHeight">
      <div class="row">
        <div class="col-md-4">
          <div class="page-header">
            <h3 class="page-title"> <?= @$title ?></h3>
          </div>
        </div>
        <div class="col-md-8 text-right">
          <a href="<?= company_url('terms/lists')?>" class="btn btn-success"><?= $lang_unit['btn_terminos_pago'] ?></a>
          <a href="<?= company_url('userprofile/lists')?>" class="btn btn-success"><?= $lang_unit['btn_perfil_usuario'] ?></a>
          <a href="<?= company_url('groups')?>" class="btn btn-success"><?= $lang_unit['btn_grupo'] ?></a>
          <a href="<?= company_url('currencies/lists')?>" class="btn btn-success active">Currencies</a>
          <a href="<?=company_url('currencies/add')?>" class="btn btn-success"> <i class="mdi mdi-plus-circle"></i> Add New Currency</a>
        </div>
      </div>
      <div class="card">
        <div class="card-body">
          <div class="row">
            <div class="col-12">
              <div class="table-responsive">
                <table id="order-listing" class="table table-striped">
                  <thead>
                    <tr>
                      <th>Country ID</th>
                      <th>Name</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php $i = 0; ?>
                    <?php foreach ($list as $key => $v): ?>
                    <tr  class="notranslate">             
                      <td><?= @$v->id ?> </td>                    
                      <td><?= @$v->name ?> </td>  
                    </tr>
                     <?php $i++; ?>
                    <?php endforeach ?>
                 
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
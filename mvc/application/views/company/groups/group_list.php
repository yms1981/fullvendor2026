<?php 
if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')   
  $url = "https://";   
else  
  $url = "http://";  
$url.= $_SERVER['HTTP_HOST'];

$url.= $_SERVER['REQUEST_URI'];
?>
<style>
.fuente_16px {
    font-size: 16px !important;
}
.table thead {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
    color: white !important;
}
.table thead th {
    color: white !important;
    border: none !important;
    font-weight: 600 !important;
    vertical-align: middle !important;
}
.table tbody tr.disabled {
    opacity: 0.6;
}
.table tbody tr.fondo-default {
    background-color: #e3f2fd;
}
.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0, 0, 0, 0.125);
}
.card-body {
    padding: 1.5rem;
}
.table-responsive {
    display: block;
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.table {
    width: 100%;
    margin-bottom: 1rem;
    color: #212529;
}
.table td, .table th {
    padding: 0.75rem;
    vertical-align: top;
    border-top: 1px solid #dee2e6;
}
#module-actions {
    display: flex;
    flex-wrap: wrap;
    width: 100%;
}
#module-actions .col-auto {
    margin-left: auto;
}
</style>
<!-- partial -->
<div class="container-fluid page-body-wrapper">
  <div class="main-panel">
    <div class="container-fluid pt-3">
      <div class="row">
        <div class="col-md-12">
          <div class="page-header">
            <h3 class="page-title"> <?= strtoupper($lang_group['list_of_group'])?></h3>
          </div>
        </div>
      </div>
      <div class="card">
        <div class="card-body">
          <div class="boxsrch">
            <div class="row align-items-center" id="module-actions">
              <div class="col">
                <div class="form-group d-flex flex-wrap align-items-center gap-2 mb-0">
                  <a href="<?= company_url('terms/lists')?>" class="btn btn-success btn-icon-text px-3 mb-1" style="white-space: nowrap;"><?= $lang_boton['terms_of_sale']?></a>
                  <a href="<?= company_url('userprofile/lists')?>" class="btn btn-success btn-icon-text px-3 mb-1" style="white-space: nowrap;"><?= $lang_unit['btn_perfil_usuario']?></a>
                </div>
              </div>
              <div class="col-auto text-right text-end" style="margin-left: auto;">
                <a href="<?=company_url('group/add')?>" class="btn btn-success btn-icon-text px-3 mb-1" style="white-space: nowrap;"> <i class="mdi mdi-plus-circle"></i> <?= $lang_group['add_new_grupo']?></a>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-12 position-relative">
              <div class="table-responsive" id="list">
                <table id="infoTable" class="table w-100">
                <thead>
                  <tr>
                    <th class="fuente_16px"><?= $lang_group['name']?></th>
                    <th class="fuente_16px"><?= $lang_group['on_price']?></th>
                    <th class="fuente_16px"><?= $lang_group['%']?></th>
                    <th class="fuente_16px"><?= $lang_group['defecto']?></th>
                    <th class="fuente_16px"><?= $lang_group['created']?></th>
                    <th class="fuente_16px"><?= $lang_group['status']?></th>
                    <th class="text-center"><?= $lang_group['actions']?></th>
                  </tr>
                </thead>
                <tbody>
                  <?php $i = 1; ?>
                  <?php foreach ($list as $key => $v): ?>
                    <tr class="<?= ($v->default != 0) ? 'fondo-default' : '' ?> notranslate <?= ($v->group_status == 0) ? 'disabled' : '' ?>">
                      <td class="fuente_16px"><?= @$v->name ?> </td>
                      <td class="fuente_16px"><?= @$v->percentage_on_price ?></td>
                      <?php 
                        $percent_price_amount = number_format($v->percent_price_amount, $format_numeric->digits, $format_numeric->radixPoint,$format_numeric->groupSeparator)
                      ?>
                      <td class="fuente_16px"><?= $percent_price_amount ?></td>
                      <td class="fuente_16px">
                        <?php if ($v->default != 0) { ?>
                          <span class="mdi mdi-check-circle text-info"></span>
                        <?php }else{ ?>
                          <input data-url_cambiar_defecto="<?= company_url('group/cambiar_unit_default/'.$v->group_id ) ?>" type="checkbox" name="group_default" class="input-default" disabled>
                        <?php } ?>
                        
                      </td>

                      <td class="fuente_16px"><?= fv_format_display_date(@$v->created_at) ?></td>
                      <td style="vertical-align: top;" width="170px">
                        <?php
                        if($v->group_status == "1"){ 
                          echo '<a href="'. company_url('group/deshabilitar/'.$v->group_id) .'" class="btn btn-warning btn-sm w-100 boton_estado" data-loader="'. $langenabled['loader-disabling'] .'">'.$langenabled['option_desactive'].'</a>'; 

                        } elseif($v->group_status == "0"){ 

                          echo '<a href="'. company_url('group/habilitar/'.$v->group_id) .'" class="btn btn-success btn-sm w-100 boton_estado" data-loader="'. $langenabled['loader-enabling'] .'">'.$langenabled['option_active'].'</a>'; 
                        } ?>
                      </td>
                      <td style="vertical-align: top;">
                        <div class="form-group text-nowrap">
                          <a href="<?= company_url('group/edit/'.$v->group_id) ?>?redirectto=<?=urlencode($url)?>" class="btn btn-primary btn-sm" title="Editar"><i class="mdi mdi-pencil"></i></a>
                          <a data-group-default="<?= $v->default ?>" href="<?= company_url('group/delete/'. $v->group_id) ?> " class="btn btn-warning btn-sm btn-go-delete" title="Eliminar"><i class="mdi mdi-delete"></i></a>
                        </div>
                     </td>
                   </tr>
                   <?php $i++; ?>
                 <?php endforeach ?>
                 
               </tbody>
             </table>
             <div class="paginate">
               <?=@$paginate?>
             </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
   <script type="text/javascript">

    $(document).ready(function (){

      activar_tabla();

    });

    $(document).on('click', '.btn-go-delete', function(e){

      let is_unitdefault = $(this).data('group-default')
      e.preventDefault()
      swal({
        title: 'Eliminar  Grupo',
        text: "Esta seguro?",
        icon: 'error',
        buttons: {
          cancel: {
            text: "Cancelar",
            value: false,
            visible: true,
            className: "sweet-danager",
            closeModal: true,
          },
          confirm: {
            text: "Eliminar",
            value: true,
            visible: true,
            className: "sweet-warning",
            closeModal: true
          },
        },
      }).then((willDelete) => {

        if (willDelete) {
          if (is_unitdefault == 1) {
            swal({
              title: 'El Grupo por defecto no se puede eliminar',
              text: "Seleccione otro Grupo por defecto",
              icon: 'error',
              buttons: {
                cancel: {
                  text: "Ok",
                  value: false,
                  visible: true,
                  className: "sweet-danager",
                  closeModal: true,
                },
              },
            })
          }else{
            location.href = $(this).attr('href')
          }

        }
      })
    })

    $(document).on('click','.boton_estado', function(e){

      e.preventDefault();
      let url = $(this).attr('href');
      let loader_text =  $(this).data('loader');
      let boton = $(this)        
      let boton_html = boton.parent().html()
      $.ajax({      
        url: url,       
        type: 'get',
        beforeSend: function(){

          let contenido_boton = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>  '+ loader_text

          boton.html(contenido_boton)        
        },
        success: function(response){
          let columna_boton = boton.parent()
          columna_boton.html(response)
        },
        error: function (response) {
          swal({
            title: '<?= $lang_group['defecto_nopuede_desactivar']?>',
            icon: 'error'
          })

          let columna_boton = boton.parent()
          columna_boton.html(boton_html)
        }

      })

    })

  </script>

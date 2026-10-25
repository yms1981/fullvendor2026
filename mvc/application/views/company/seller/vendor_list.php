<?php
if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
  $url = "https://";
else
  $url = "http://";


$url.= $_SERVER['HTTP_HOST'];
$url.= $_SERVER['REQUEST_URI'];
$ahora = DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));
$v_imagen = $ahora->format("YmdHisu");

?>
<div class="container-fluid page-body-wrapper pt-3">
    <div class="main-panel">
        <div class="container-fluid contentHeight">
            <div class="row">
                <div class="col-md-6">
                    <div class="page-header">
                        <h3 class="page-title"><?= strtoupper($lang_seller['list_of_seller'])?></h3>
                    </div>
                </div>
                <div class="col-md-6 text-right">
                    <a href="<?= company_url('sellers/add') ?>" class="btn btn-success"><i
                            class="mdi mdi-plus-circle"></i> <?= $lang_seller['add_new_seller']?></a>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">
                            <div class="table-responsive">
                                <table id="infoTable" class="table">
                                    <thead>
                                        <tr>
                                            <th style="width: 5%;">Id</th>
                                            <th style="width: 10%;text-align: center;"> Image</th>
                                            <th style="width: 10%;"> <?= $lang_seller['name']?></th>
                                            <th style="width: 10%;"><?= $lang_seller['email']?></th>
                                            <th style="width: 10%;"><?= $lang_seller['username']?></th>
                                            <th style="width: 10%;"><?= $lang_seller['phone']?></th>
                                            <th style="width: 10%;"><?= $lang_seller['defecto']?></th>
                                            <th style="width: 10%;"><?= $lang_seller['alta']?></th>
                                            <th style="width: 10%;"><?= $lang_seller['activar_desactivar']?></th>
                                            <th style="width: 5%;"><?= $lang_seller['acciones']?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $i=1; ?>
                                        <?php foreach ($list as $key => $v): ?>
                                        <tr class="<?= ($v->default != 0) ? 'fondo-default' : '' ?> notranslate ">
                                            <td>
                                                <a href="<?= company_url('sellers/edit/'.$v->user_id) ?>"
                                                    data-toggle="tooltip" title="Edit">
                                                    <?= $v->user_id ?>
                                                </a>
                                            </td>
                                            <td>
                                                <figure style="width: 4em; height: 4em; overflow:hidden; margin:auto;">
                                                    <?php if (@$v->profile_image && file_exists('./uploads/users/'.@$v->profile_image)) { ?>
                                                    <span data-toggle="popover-hover"
                                                        title="<?= $v->first_name . ' ' . $v->last_name?>"
                                                        data-img="<?= base_url('uploads/users/'.@$v->profile_image. '?v=' . $v_imagen) ?>">
                                                        <img src="<?= base_url('uploads/users/'.@$v->profile_image. '?v=' . $v_imagen) ?>"
                                                            alt="" style="display: block; width: 100%; height: 100%;"
                                                            class="rounded-0">
                                                    </span>
                                                    <?php } else { ?>
                                                    <img src="<?= base_url('uploads/noimg.png'. '?v=' . $v_imagen) ?>"
                                                        alt="" style="display: block; width: 100%; height: 100%;"
                                                        class="rounded-0">
                                                    <?php } ?>
                                                </figure>
                                            </td>
                                            <td class="text-wrap">
                                                <a href="<?= company_url('sellers/edit/'.$v->user_id) ?>"
                                                    data-toggle="tooltip" title="Edit">
                                                    <?= $v->first_name . ' ' . $v->last_name?>
                                                </a>
                                            </td>
                                            <td>
                                                <div class="text-wrap break-100px">
                                                    <a href="http://localhost:8080/fullvendor/company/customer/view/2241"
                                                        data-toggle="tooltip" title="View Income">
                                                        <u><?= @$v->email ?></u>
                                                    </a>
                                                </div>

                                            </td>
                                            <td>
                                                <div class="text-wrap break-120px">
                                                    <?= @$v->username ?>
                                                </div>
                                            </td>
                                            <td><?= @$v->phone_number ?></td>
                                            <td>
                                                <?php if ($v->default != 0) { ?>
                                                <span class="mdi mdi-check-circle text-info"></span>

                                                <?php }else{ ?>
                                                <input
                                                    data-url_cambiar_defecto="<?= company_url('sellers/cambiar_unit_default/'.$v->user_id ) ?>"
                                                    type="checkbox" name="unit_default" class="input-default" disabled>
                                                <?php } ?>

                                            </td>
                                            <td><?= fv_format_display_date(@$v->created) ?></td>
                                            <td width="150px">
                                                <?php
                                                  if($v->status == "1"){
                                                    echo '<a href="'. company_url('sellers/deshabilitar/'.$v->user_id) .'" class="btn btn-warning btn-sm w-100 boton_estado" data-loader="'. $langenabled['loader-disabling'] .'">'.$langenabled['option_desactive'].'</a>';

                                                  } elseif($v->status == "0"){

                                                    echo '<a href="'. company_url('sellers/habilitar/'.$v->user_id) .'" class="btn btn-success btn-sm w-100 boton_estado" data-loader="'. $langenabled['loader-enabling'] .'"><font color="White">'.$langenabled['option_active'].'</font></a>';
                                                  }
                                                  ?>

                                            </td>
                                            <td class="d-flex">
                                                <a href="<?= company_url('sellers/edit/'.$v->user_id) ?>?redirectto=<?=urlencode($url)?>"
                                                    class="btn btn-success btn-sm ml-2" data-toggle="tooltip"
                                                    title="Edit">
                                                    <i class="mdi mdi-pencil"></i>
                                                </a>
                                                <a data-unit-default="<?= $v->default ?>"
                                                    href="<?= company_url('sellers/delete/'. $v->user_id) ?> "
                                                    class="btn btn-danger btn-go-delete btn-sm ml-2"><i
                                                        class="mdi mdi-delete"></i></a>
                                            </td>
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

        <script>
        $(document).on('click', '.boton_estado', function(e) {

            e.preventDefault();
            let url = $(this).attr('href');
            let loader_text = $(this).data('loader');
            let boton = $(this)
            let boton_html = boton.parent().html()

            $.ajax({
                url: url,
                type: 'get',
                beforeSend: function() {

                    let contenido_boton =
                        '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>  ' +
                        loader_text

                    boton.html(contenido_boton)
                },
                success: function(response) {
                    let columna_boton = boton.parent()
                    columna_boton.html(response)
                },
                error: function(response) {
                    swal({
                        title: '<?= $lang_seller['defecto_nopuede_desactivar'] ?>',
                        icon: 'error'
                    })
                    let columna_boton = boton.parent()
                    columna_boton.html(boton_html)
                }
            })

        })

        $(document).ready(function() {

            activar_tabla();
            activar_popover_imagenes()
        });

        $(document).on('click', '.btn-go-delete', function(e) {

            let is_unitdefault = $(this).data('unit-default')
            e.preventDefault()
            swal({
                title: "<?= $lang_seller['eliminar_vendedor']?>",
                text: "<?= $lang_seller['esta_seguro']?>",
                icon: 'error',
                buttons: {
                    confirm: {
                        text: '<?= $lang_seller['eliminar']?>',
                        value: true,
                        visible: true,
                        className: "sweet-warning",
                        closeModal: true
                    },
                    cancel: {
                        text: '<?= $lang_seller['cancelar']?>',
                        value: false,
                        visible: true,
                        className: "sweet-danager",
                        closeModal: true,
                    },

                },
            }).then((willDelete) => {

                if (willDelete) {
                    if (is_unitdefault == 1) {
                        swal({
                            title: '<?= $lang_seller['no_puede_eliminar']?>',
                            text: '<?= $lang_seller['seleccione_otro']?>',
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
                    } else {
                        location.href = $(this).attr('href')
                    }

                }
            })
        })
        </script>

<?php
/**
 * @var array $terms list of Terms
 * @var array $lang_json translations of the module
 */

if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
  $url = "https://";
else
  $url = "http://";
$url.= $_SERVER['HTTP_HOST'];

$url.= $_SERVER['REQUEST_URI'];

$lang_term = $lang_json['cmp_terms']['lang'];
$langenabled = $lang_json['cmp_enabled']['lang'];
$lang_boton = $lang_json['cmp_botones']['lang'];
$lang_unit = $lang_json['unit_languaje']['lang'];
?>
<!-- partial -->
<div class="container-fluid page-body-wrapper pt-3">
    <div class="main-panel">
        <div class="container-fluid contentHeight">
            <div class="row">
                <div class="col-md-6">
                    <div class="page-header">
                        <h3 class="page-title"><?= strtoupper($lang_term['titulo']) ?></h3>
                    </div>
                </div>
                <div class="col-md-6 text-right">
                    <a href="<?= company_url('sellerprofile/lists')?>"
                        class="btn btn-success"><?= $lang_unit['btn_perfil_usuario']?></a>
                    <a href="<?= company_url('groups')?>" class="btn btn-success"><?= $lang_boton['group']?></a>
                    <a href="<?=company_url('terms/add')?>" class="btn btn-success"> <i class="mdi mdi-plus-circle"></i>
                        <?= $lang_term['add_new_term']?></a>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-12">

                            <table id="infoTable" class="table">
                                <thead>
                                    <tr>
                                        <th width="40">Id</th>
                                        <th><?= $lang_term['name']?></th>
                                        <th><?= $lang_term['defecto']?></th>
                                        <th><?= $lang_term['alta']?></th>
                                        <th><?= $lang_term['status']?></th>
                                        <th width="60"><?= $lang_term['actions']?></th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <?php foreach ($terms as $key => $term): ?>
                                    <tr class="<?= ($term->default != 0) ? 'fondo-default' : '' ?> notranslate ">

                                        <td><?= $term->term_id ?></td>
                                        <td><?= @$term->name ?> </td>
                                        <td>
                                            <?php if ($term->default != 0) { ?>
                                            <span class="mdi mdi-check-circle text-info"></span>
                                            <?php }else{ ?>
                                            <input
                                                data-url_cambiar_defecto="<?= company_url('terms/cambiar_unit_default/'.$term->term_id ) ?>"
                                                type="checkbox" name="term_default" class="input-default" disabled>
                                            <?php } ?>


                                        </td>
                                        <td><?= fv_format_display_date(@$term->created_at) ?></td>
                                        <td width="170px">
                                            <?php
                                              if($term->term_status == "1"){
                                                echo '<a href="'. company_url('terms/disable/'.$term->term_id) .'" class="btn btn-warning btn-sm w-100 boton_estado" data-loader="'. $langenabled['loader-disabling'] .'">'.$langenabled['option_disable'].'</a>';

                                              } elseif($term->term_status == "0"){
                                                echo '<a href="'. company_url('terms/enable/'.$term->term_id) .'" class="btn btn-success btn-sm w-100 boton_estado" data-loader="'. $langenabled['loader-enabling'] .'"><font color="White">'.$langenabled['option_enable'].'</font></a>';
                                              } ?>
                                        </td>
                                        <td>
                                            <div class="form-group text-nowrap">
                                                <a href="<?= company_url('terms/edit/'.$term->term_id) ?>"
                                                    class="btn btn-success"><i class="mdi mdi-pencil"></i></a>

                                                <a data-term-default="<?= $term->default ?>"
                                                    href="<?= company_url('terms/delete/'. $term->term_id) ?> "
                                                    class="btn btn-danger btn-go-delete"><i
                                                        class="mdi mdi-delete"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach ?>

                                </tbody>
                            </table>
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
                error: function(xhr) {
                    let errorMsg = 'Ocurrió un error inesperado';

                    // Intentar extraer el mensaje de error de la respuesta
                    if (xhr.responseText) {
                        // Si la respuesta contiene HTML con un div de alerta, extraer el texto
                        let $temp = $('<div>').html(xhr.responseText);
                        let alertText = $temp.find('.alert-danger').text().trim();
                        if (alertText) {
                            errorMsg = alertText;
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMsg = xhr.responseJSON.message;
                        }
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }

                    swal({
                        title: errorMsg,
                        icon: 'error'
                    });

                    let columna_boton = boton.parent();
                    columna_boton.html(boton_html);
                }
            })

        })

        function changeTermStatus(termId, newStatus) {
            $.ajax({
                    url: '<?= company_url("terms/changeStatus")?>',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        termId: String(termId),
                        status: String(newStatus)
                    },
                })
                .done(function(data) {
                    alert_func(data);
                })
                .fail(function(data) {
                    console.log(data);
                });
        }


        $(document).ready(function() {

            activar_tabla();

        });

        $(document).on('click', '.btn-go-delete', function(e) {

            let is_unitdefault = $(this).data('term-default')
            e.preventDefault()
            swal({
                title: '<?= $lang_term['eliminar_termino']?>',
                text: "<?= $lang_term['esta_seguro']?>",
                icon: 'error',
                buttons: {
                    cancel: {
                        text: '<?= $lang_term['cancelar']?>',
                        value: false,
                        visible: true,
                        className: "sweet-danager",
                        closeModal: true,
                    },
                    confirm: {
                        text: '<?= $lang_term['eliminar']?>',
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
                            title: '<?= $lang_term['no_puede_eliminar']?>',
                            text: '<?= $lang_term['seleccione_otro']?>',
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

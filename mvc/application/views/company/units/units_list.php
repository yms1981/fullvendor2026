<div class="container-fluid page-body-wrapper pt-3">
    <div class="main-panel">
        <div class="container-fluid contentHeight">
            <div class="row">
                <div class="col-md-4">
                    <div class="page-header">
                        <h3 class="page-title"> <?= $langunit['list_tipo_unidades'] ?></h3>
                    </div>
                </div>
                <div class="col-md-8 text-right">
                    <a href="<?= company_url('terms/lists')?>"
                        class="btn btn-success"><?= $langunit['btn_terminos_pago'] ?></a>
                    <a href="<?= company_url('userprofile/lists')?>"
                        class="btn btn-success"><?= $langunit['btn_perfil_usuario'] ?></a>
                    <a href="<?= company_url('groups')?>" class="btn btn-success"><?= $langunit['btn_grupo'] ?></a>
                    <a href="<?=company_url('units/add')?>" class="btn btn-success"> <i
                            class="mdi mdi-plus-circle"></i><?= $langunit['btn_agregar_unidad'] ?></a>
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
                                            <th width="20">#</th>
                                            <th>Id</th>
                                            <th><?= $langunit['unidad_nombre'] ?></th>
                                            <th><?= $langunit['unidad_descripcion'] ?></th>
                                            <th><?= $langunit['default-column'] ?></th>
                                            <th><?= $langunit['unidad_alta'] ?></th>
                                            <th width="40"><?= $langunit['unidad_estado'] ?></th>
                                            <th width="60"><?= $langunit['unidad_acciones'] ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $i = 1; ?>
                                        <?php foreach ($list as $key => $v): ?>
                                        <tr class="<?= ($v->default != 0) ? 'fondo-default' : '' ?> notranslate ">
                                            <td><?= $i ?></td>
                                            <td><?= @$v->unit_id ?> </td>
                                            <td><?= @$v->unit ?> </td>
                                            <td><?= @$v->description ?> </td>
                                            <td>
                                                <?php if ($v->default != 0) { ?>
                                                <span class="mdi mdi-check-circle text-info"></span>

                                                <?php }else{ ?>

                                                <input
                                                    data-url_cambiar_defecto="<?= company_url('units/cambiar_unit_default/'.$v->unit_id ) ?>"
                                                    type="checkbox" name="unit_default" class="input-default" disabled>
                                                <?php } ?>

                                            </td>
                                            <td><?= fv_format_display_date(@$v->created) ?></td>
                                            <td width="200px">
                                                <?php
                                                  if($v->status == "1"){ 
                                                    echo '<a href="'. company_url('units/deshabilitar/'.$v->unit_id) .'" class="btn btn-warning btn-sm w-100 boton_estado" data-loader="'. $langunit['loader-disabling'] .'">'.$langunit['option_desactive'].'</a>'; 
                            
                                                  } elseif($v->status == "0"){ 
                            
                                                    echo '<a href="'. company_url('units/habilitar/'.$v->unit_id) .'" class="btn btn-success btn-sm w-100 boton_estado" data-loader="'. $langunit['loader-enabling'] .'"><font color="White">'.$langunit['option_active'].'</font></a>'; 
                                                  } ?>
                                            </td>
                                            <td>
                                                <div class="form-group text-nowrap">
                                                    <a href="<?= company_url('units/edit/'.$v->unit_id ) ?>"
                                                        class="btn btn-success"><i class="mdi mdi-pencil"></i></a>
                                                    <a data-unit-default="<?= $v->default ?>"
                                                        href="<?= company_url('units/delete/'. $v->unit_id) ?> "
                                                        class="btn btn-danger btn-go-delete"><i
                                                            class="mdi mdi-delete"></i></a>
                                                </div>
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
                        title: '<?= $langunit['defecto_nopuede_desactivar'] ?>',
                        icon: 'error'
                    })

                    let columna_boton = boton.parent()
                    columna_boton.html(boton_html)
                }
            })

        })

        $(document).ready(function() {

            activar_tabla();
           
        });

        $(document).on('click', '.btn-go-delete', function(e) {

            let is_unitdefault = $(this).data('unit-default')
            e.preventDefault()
            swal({
                title: "<?= $langunit['eliminar_unidad']?>",
                text: "<?= $langunit['esta_seguro']?>",
                icon: 'error',
                buttons: {
                    confirm: {
                        text: '<?= $langunit['eliminar']?>',
                        value: true,
                        visible: true,
                        className: "sweet-warning",
                        closeModal: true
                    },
                    cancel: {
                        text: '<?= $langunit['cancelar']?>',
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
                            title: '<?= $langunit['no_puede_eliminar']?>',
                            text: '<?= $langunit['seleccione_otro']?>',
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
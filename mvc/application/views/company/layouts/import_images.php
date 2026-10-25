<?php 

$compId = $this->session->userdata('compId');
$company = $this->mymodel->get('companies', true, 'company_id', $this->session->userdata('compId'));

$langid = $company->language_id;
$dropzone['dropzone'] = $this->langmodel->getMdl('cmp-dropzone', $langid);

?>

<div class="container-fluid page-body-wrapper pt-3">
    <div class="main-panel">
        <div class="container-fluid">
            <div class="page-header">
                <h3 class="page-title"><?= isset($categ_tittle)?strtoupper($categ_tittle):strtoupper($langprods['modal-importacion-imagenes']) ?></h3>
            </div>
            <div class="card contentHeight">
                <div class="card-body">
                    <div class="row">
                        <div class="col-9">                                                        
                            <p><?= $text ?></p>
                        </div>
                        <div class="col-3">
                            <div class="form-group text-right">
                                <!-- <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= $langprods['btn-modal-cancelar'] ?></button> -->
                                <button type="submit" id="btn-export" form="form-guardar-imagenes"
                                    class="btn btn-success"><i
                                        class="mdi mdi-download"></i><?= $langprods['btn-modal-importar'] ?></button>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-7">
                         
                            <form action="<?= isset($url)?company_url($url):company_url('product/importar_imagenes') ?>"
                                class="card-body border dropzone" id="my-great-dropzone">
                    
                            </form>

                        </div>
                        <div class="col-5">
                            <div class="row">
                                <div class="col-6">
                                    <h4><?= $langprods['subidos'] ?></h4>
                                </div>
                                <div class="col-6">
                                    
                                    <h4><?= $langprods['no_subidos'] ?></h4>
                                </div>
                            </div>
                            <hr>
                            <div style="height:40em; overflow-y: auto;" class="row">
                                <div class="col-6">
                                    <div id="resultado_subidos">

                                    </div>
                                </div>
                                <div class="col-6">
                                    <div id="resultado_nosubidos">

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <br>

                </div>
            </div>

        </div>

    </div>
</div>
<form action="<?= isset($url)?company_url($url):company_url('product/importar_imagenes') ?>" 
        id="form-guardar-imagenes" enctype="multipart/form-data">
    
</form>

<script>
var arrayfiles = []

$(document).ready(function(e) {

    $(document).on("submit", "#form-guardar-imagenes", function(e) {

        $('#resultado_subidos').html('')
        $('#resultado_nosubidos').html('')
        e.preventDefault()
        let evento = e.target
        let url = $(this).attr('action')
        console.log(arrayfiles)

        if (arrayfiles.length > 0) {

            let contador = 0;
            array_grupo = [];
            array_todosGrupos = [];

            for (var i = 0; i < arrayfiles.length; i++) {

                contador = contador + 1;

                if (contador >= 5) {
                    array_grupo.push(arrayfiles[i]);
                    array_todosGrupos.push(array_grupo)
                    array_grupo = []
                    contador = 0;

                } else {

                    array_grupo.push(arrayfiles[i]);

                }
            }

            if (array_todosGrupos.length == 0 || array_grupo.length > 0) {
                array_todosGrupos.push(array_grupo)
            }

            console.log(array_todosGrupos)

            $.each(array_todosGrupos, function(index, el) {

                let formData = new FormData(evento);
                $.each(el, function(index, value) {

                    formData.append("files[" + index + "]", value)

                })

                $.ajax({
                    url: url,
                    type: 'post',
                    data: formData,
                    dataType: 'json',
                    processData: false,
                    contentType: false,
                    beforeSend: function() {
                        $('#btn-export').prop('disabled', true);
                        $('#btn-export').html(
                            '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Importando'
                        );
                    },
                    success: function(data) {

                        console.log(data)
                        $.each(data, function(index, el) {
                            setTimeout(function() {
                                if (el.success) {

                                    $('#resultado_subidos').append(
                                        `<a data-clase="border-success" data-imagen="${el.imagen}" href="javascript:void(0)" class="text-success link-result"><i class="fa-solid fa-thumbs-up"></i> ${el.mensaje}</a><br>`
                                    )
                                } else {
                                    $('#resultado_nosubidos')
                                        .append(
                                            `<a data-clase="border-danger" data-imagen="${el.imagen}" href="javascript:void(0)" class="text-danger link-result"><i class="fa-solid fa-circle-exclamation"></i> ${el.mensaje}</a><br>`
                                        )
                                }
                            }, 100 * (index + 1));
                        });
                        $('#btn-export').prop('disabled', false);
                        $('#btn-export').html(
                            '<i class="mdi mdi-download"></i> Importar')
                        //location.reload()
                        //window.location.href = data
                    },
                })
            });

        }
    })

    $(document).on("mouseover", ".link-result", function(e) {

        let imagen = $(this).data('imagen')
        let clase = $(this).data('clase')
        let dropzone_imagen = $('#mydropzone .dz-preview:contains("' + imagen + '")')
        dropzone_imagen.addClass('border ' + clase)

    })

    $(document).on("mouseout", ".link-result", function(e) {
        let dropzone_imagen = $('#mydropzone .dz-preview')
        dropzone_imagen.removeClass('border border-danger')
    })
})
</script>
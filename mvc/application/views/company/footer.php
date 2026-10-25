<?php

$compId = $this->session->userdata('compId');
$company = get_instance()->getObjectFromApi('company/' . $compId);

$langid = is_object($company) ? ($company->language_id ?? null) : null;

$dropzone['dropzone'] = $this->langmodel->getMdl('cmp-dropzone', $langid);
$lang_general = $this->langmodel->getMdl('company-csvf', $langid);
$lang_datatable = $this->langmodel->getMdl('company-datatable', $langid);

$langprods = $this->langmodel->getMdl('cmp-products', $langid);
$ahora = DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));
$v_imagen = $ahora->format("YmdHisu");
$v_imagen = substr(hash('md5', $v_imagen), 0, 5);

$fvIsProd = ! defined('ENVIRONMENT') || ENVIRONMENT !== 'development';
$vueCdn = $fvIsProd
	? 'https://unpkg.com/vue@3/dist/vue.global.prod.js'
	: 'https://unpkg.com/vue@3/dist/vue.global.js';
$vuexCdn = $fvIsProd
	? 'https://unpkg.com/vuex@4/dist/vuex.global.prod.js'
	: 'https://unpkg.com/vuex@4/dist/vuex.global.js';
$chartCdn = $fvIsProd
	? 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js'
	: 'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.js';

?>
<script>
window.fvLang = window.fvLang || {};
window.fvLang.no_comma_semicolon = <?= json_encode(
    is_array($langprods) ? ($langprods['no_comma_semicolon'] ?? '') : '',
    JSON_UNESCAPED_UNICODE
) ?>;
</script>
</div>
</div>
</div>
</div>
<footer class="footer">
    <div class="container">
        <div class="d-sm-flex justify-content-center justify-content-sm-between p-5">
            <span class="text-muted text-center text-sm-left d-block d-sm-inline-block">Copyright &copy; <?= date('Y')?>
                All rights reserved.</span>
            <span class="float-none float-sm-right d-block mt-1 mt-sm-0 text-center">Designed & Developed by
                <img src="<?= base_url('images/logos/logo.png') ?>" class="thumbnail rounded"
                    style="display:inline-block;width: 25px;margin-bottom:0;/*margin-right:29px;*/">
                <a class="text-primary" href="https://www.fullvendor.com/" target="_blank">Fullvendor</a>
            </span>
            <span><?php echo 'Version '.APP_VERSION; ?></span>
        </div>
    </div>
</footer>
</body>

<!-- Esperar a que jQuery esté disponible antes de cargar scripts dependientes -->
<script>
window.fvDataTableLanguage = {
    info: <?= json_encode($lang_datatable['info'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
    infoEmpty: <?= json_encode($lang_datatable['infoEmpty'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
    lengthMenu: <?= json_encode($lang_datatable['lengthMenu'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
    loadingRecords: <?= json_encode($lang_datatable['loadingRecords'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
    search: <?= json_encode($lang_datatable['search'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
    zeroRecords: <?= json_encode($lang_datatable['zeroRecords'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
    infoFiltered: <?= json_encode($lang_datatable['infoFiltered'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
    paginate: {
        first: <?= json_encode($lang_datatable['first'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
        last: <?= json_encode($lang_datatable['last'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
        next: <?= json_encode($lang_datatable['next'] ?? '', JSON_UNESCAPED_UNICODE) ?>,
        previous: <?= json_encode($lang_datatable['previous'] ?? '', JSON_UNESCAPED_UNICODE) ?>
    }
};
</script>
<!-- Usa CoreLoader para evitar duplicación de código -->
<script>
// Usar CoreLoader para esperar jQuery y cargar scripts base
if (typeof CoreLoader !== 'undefined') {
    CoreLoader.waitForJQuery(function() {
        console.log('jQuery cargado, cargando scripts dependientes...');

        // Redirigir a login cuando la API devuelve Unauthenticated (401)
        $(document).ajaxComplete(function(event, xhr, settings) {
            if (xhr.status === 401) {
                var reqUrl = settings && settings.url ? String(settings.url) : '';
                if (/uploadImage/i.test(reqUrl)) {
                    return;
                }
                var redirect = null;
                try {
                    var r = xhr.responseJSON || (xhr.responseText ? JSON.parse(xhr.responseText) : null);
                    if (r && r.redirect) redirect = r.redirect;
                } catch (e) {}
                if (redirect) {
                    window.location.href = redirect;
                }
            }
        });

        // Scripts base que dependen de jQuery
        // NOTA: select2 ya se carga en el header si está en $jsFiles, 
        // así que aquí solo cargamos los que no están en el header
        var baseScripts = [
            '<?= base_url('companyasset/js/bootstrap.bundle.min.js')?>',
            '<?= base_url('companyasset/vendors/sweetalert/sweetalert.min.js')?>',
            '<?= base_url('companyasset/js/jasny-bootstrap.js?v=' . $v_imagen) ?>',
            '<?= base_url('companyasset/vendors/bootstrap-maxlength/bootstrap-maxlength.min.js')?>',
            '<?= base_url('companyasset/vendors/flot/jquery.flot.js')?>',
            '<?= base_url('companyasset/vendors/datatables.net/jquery.dataTables.js')?>',
            '<?= base_url('companyasset/company/fv-display-date.js?v=' . $v_imagen)?>',
            '<?= base_url('companyasset/company/crud-modern.js?v=' . $v_imagen)?>',
            '<?= base_url('companyasset/company/fv-company-modals.js?v=' . $v_imagen)?>',
            '<?= base_url('companyasset/vendors/inputmask/jquery.inputmask.min.js')?>',
            '<?= base_url('companyasset/js/bootstrap-tagsinput.js')?>',
            '<?= base_url('companyasset/js/moment.min.js') ?>',
            // select2 se carga en el header si está en $jsFiles, sino aquí
            '<?= base_url('companyasset/js/select2.min.js?v=' . $v_imagen)?>',
            '<?= base_url('companyasset/js/bootstrap-datepicker.js')?>',
            '<?= base_url('companyasset/vendors/summernote/dist/summernote-bs4.min.js')?>',
            '<?= base_url('companyasset/js/dual-listbox.min.js?v='. $v_imagen)?>',
            '<?= base_url('companyasset/js/bootstrap-multiselect.js') ?>',
            '<?= base_url('companyasset/js/bootstrap-select.min.js?v='.$v_imagen)?>',
            '<?= base_url('companyasset/js/intlTelInput-p.js')?>',
            // jQuery UI debe ir después de bootstrap-datepicker para exponer $.datepicker (UI)
            '<?= base_url('companyasset/js/jquery-ui.js?v='. $v_imagen)?>',
            // Datepickers del perfil: API jQuery UI + format_js de la empresa
            '<?= base_url('companyasset/company/fv-company-datepicker.js?v=' . $v_imagen)?>'
        ];
        
        // Cargar scripts base de forma secuencial usando CoreLoader
        CoreLoader.loadScripts(baseScripts, function() {
            console.log('CoreLoader: Todos los scripts base cargados');
            if (window.FVCrudModern && typeof window.FVCrudModern.init === 'function') {
                window.FVCrudModern.init();
            }
            if (typeof window.fvInitCompanyDatepickers === 'function') {
                window.fvInitCompanyDatepickers(document);
            }
        });
    });
} else {
    console.error('CoreLoader no está disponible. Verificar que core-loader.js se cargue antes del footer.');
}
</script>

<!-- Scripts que dependen de jQuery se cargan dinámicamente arriba -->
<!-- Scripts que NO dependen de jQuery se cargan aquí -->

<script src="<?= base_url('companyasset/js/canvas.js?v' . $v_imagen) ?>"></script>
<script src="<?= base_url('companyasset/js/vfs_fonts.js') ?>"
    integrity="sha512-pAoMgvsSBQTe8P3og+SAnjILwnti03Kz92V3Mxm0WOtHuA482QeldNM5wEdnKwjOnQ/X11IM6Dn3nbmvOz365g=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<!-- <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script> -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<!-- Importacion de Vue -->
<script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
<!-- Importa Vuex después de Vue desde un CDN -->
<script src="https://unpkg.com/vuex@4"></script>

<?php if (! empty($js_vue) && is_array($js_vue)): ?>
<?php foreach ($js_vue as $vue_js): ?>
<?php if (! is_string($vue_js) || $vue_js === '') {
    continue;
} ?>
<script src="<?= base_url('companyasset/company/'.$vue_js. '?v='.$v_imagen)?>"></script>
<?php endforeach; ?>
<?php endif; ?>


<script>
// Verificar que jQuery esté disponible antes de ejecutar scripts
(function() {
    function initJQueryScripts() {
        if (typeof jQuery === 'undefined') {
            console.error('jQuery not loaded!');
            return;
        }
        
        var $ = jQuery;
        
        //$(".preloader").fadeOut("slow");
        $(window).on("load", function() {
            $(".preloader").fadeOut("slow");
        });

        var language = window.fvDataTableLanguage;

        window.alert_func = function alert_func(data) {
            var raw = (data && data[0] != null) ? String(data[0]) : '';
            var lines = raw.split(/\r?\n/).filter(function (line) {
                return String(line).trim() !== '';
            });
            var title = lines.length ? lines[0] : raw;
            var body = lines.length > 1 ? lines.slice(1).join('\n') : '';
            swal({
                title: title,
                text: body,
                icon: data[1],
                button: {
                    text: "OK",
                    value: true,
                    visible: true,
                    className: "btn btn-primary"
                }
            });
        };
        
        // Continuar con el resto de los scripts jQuery


<?php

$langId= $this->session->userdata('language_id');
if($langId==1){
  $lmsgh= "en";
}elseif($langId==2){
  $lmsgh= "es";
}


?>

<?php if (!empty($this->session->flashdata('msg'))): ?>
<?php 
$flash_msg = $this->session->flashdata('msg');
// En CodeIgniter 3 el flashdata se descarta automáticamente al leerlo; no existe unset_flashdata
if ($flash_msg == 'error') { ?>
<script>
alert_func(["Some error occured, Please try again!", "error", "#DD6B55"]);
</script>
<?php } else {
	// Acepta payload JSON (array) o texto plano; evita romper JS con set_flash mal formado.
	$flashPayload = null;
	if (is_string($flash_msg)) {
		$decodedFlash = json_decode($flash_msg, true);
		if (json_last_error() === JSON_ERROR_NONE && is_array($decodedFlash)) {
			$flashPayload = $decodedFlash;
		} else {
			$flashPayload = [$flash_msg, 'success', '#A5DC86'];
		}
	} elseif (is_array($flash_msg)) {
		$flashPayload = $flash_msg;
	}
	if (is_array($flashPayload) && isset($flashPayload[0])) {
?>
<script>
alert_func(<?= json_encode($flashPayload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>);
</script>
<?php
	}
} ?>
<?php endif ?>




<script>
var langid = <?= $langid ?>;

//input phone number with country code
const input = document.querySelectorAll(".telInput");
input.forEach(function(element, key, parent) {

    window.intlTelInput(element, {
        utilsScript: "<?= base_url('companyasset/js/utils.js')?>"
    });
});


if (window.history.replaceState) { // verificamos disponibilidad
    window.history.replaceState(null, null, window.location.href);
}
var version_foto = '<?= $v_imagen ?>'
//$("input[type=tel]").intlTelInput();

        $('.dropdown-container').on('click', '.dropdown-button', function() {

            $(this).siblings('.dropdown-list').toggle();


        }).on('input', '.dropdown-search', function() {
            var target = $(this);
            var dropdownList = target.closest('.dropdown-list');
            var search = target.val().toLowerCase();

            if (!search) {
                dropdownList.find('li').show();
                return false;
            }

            dropdownList.find('li').each(function() {
                var text = $(this).text().toLowerCase();
                var match = text.indexOf(search) > -1;
                $(this).toggle(match);
            });
        })
        .on('change', '[type="checkbox"]', function() {
            var container = $(this).closest('.dropdown-container');
            var numChecked = container.find('[type="checkbox"]:checked').length;
            container.find('.quantity').text(numChecked || '<?= $langprods['any'] ?>');
        });

        // JSON of States for demo purposes
        var usStates = [];

        $('body').append('<div style="" id="loadingDiv"><div class="loader">Loading...</div></div>');
        $(window).on("load", function() {
            setTimeout(removeLoader, 2000); //wait for page load PLUS two seconds.
            // googleTranslateElementInit();
        });

        function googleTranslateElementInit() {
            new google.translate.TranslateElement({
                pageLanguage: 'en',
                layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
                includedLanguages: "<?=@$lmsgh?>"
            }, 'google_translate_element');
        }

        function removeLoader() {
            $("#loadingDiv").fadeOut(500, function() {
                // fadeOut complete. Remove the loading div
                $("#loadingDiv").remove(); //makes page more lightweight
            });
        }

        $(document).on('click', '.toggle-password', function() {
            $(this).toggleClass("fa-eye fa-eye-slash");
            var input = $("#password");
            input.attr('type') === 'password' ? input.attr('type', 'text') : input.attr('type', 'password')
        });
        /***Add Website links **/
        $('.add-web').on('click', function(e) {

            e.preventDefault();
            var id = $(this).attr('href'),
                clone = $(id).find('.webfield-clone').html();
            $(id).find('.weblink-list').append(clone);
        });
        $(".weblink-list").bind("DOMSubtreeModified", function() {
            $('.remove-weblink').on('click', function(e) {
                e.preventDefault();
                $(this).parents('.boxInput').remove();
            });
        });

        var companyUrl = $('#companyUrl').val();
        var urlbase_js = '<?= base_url() ?>';

        $('#add-more-image').on('click', function(event) {

            console.log($('#add-more-image'))
            event.preventDefault();

            $('#image-group').append(
                `<br>
                <div class="fileinput fileinput-new p-1" data-provides="fileinput">
                <div class="fileinput-filename"></div>
                <div class="fileinput-new thumbnail" style="width: 200px; height: 150px;">

                <img src="${urlbase_js}uploads/noimg.png" alt="">
                </div>

                <div class="fileinput-preview fileinput-exists thumbnail" style="max-width: 200px; max-height: 180px;">

                </div>
                <div class="text-center">
                <span class="btn btn-default btn-file">
                <span class="fileinput-new">
                <i class="fa-solid fa-upload"></i>
                </span>
                <span class="fileinput-exists">
                <i class="fa-solid fa-pen-to-square"></i>
                </span>
                <input type="file" name="images[]" accept="image/*" required="">
                </span>
                <a href="#" class="btn btn-danger fileinput-exists" data-dismiss="fileinput">
                <i class="fa-sharp fa-solid fa-trash"></i>
                </a>
                <span class="btn btn-danger delete-image-input">&times;</span>
                </div>
                </div>`
            );
        });


        $(document).on('click', '.delete-image-input', function(event) {
            console.log('eliminado')
            $(this).closest('.fileinput').remove();
        });

        // Inicializar Summernote solo si el elemento existe y Summernote está disponible
        if (typeof $.fn.summernote !== 'undefined' && $("#summernoteExample").length) {
            $('#summernoteExample').summernote({
                height: 250,
                tabsize: 1
            });
        }

        //$(".taginput").tagsinput('items');


        if ($('#my-great-dropzone')) {

        }



        function changeVendorStatus(userId, newStatus) {
            $.ajax({
                    url: `${options.url}/sellers/changeStatus`,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        userId: String(userId),
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

        function changeWarehouseStatus(userId, newStatus) {
            $.ajax({
                    url: `${options.url}/warehouse/changeStatus`,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        userId: String(userId),
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

        function changeCatalogStatus(catalogId, newStatus) {
            $.ajax({

                    url: `${options.url}/catalog/changeStatus`,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        catalog_id: String(catalogId),
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

            // El navbar usa position: sticky (company-shell.css); no aplicar fixed-top al hacer scroll.

            // Excluir multiselects que se inicializan en sus vistas (no reinicializar aquí o se pierde "múltiple"):
            // - #form_add #category, #form_edit #category, #form_view #category = categorías producto (varias por producto)
            // - #form_add #seller_asigned, #form_edit #seller_asigned = vendedores asignados al cliente (varios por cliente)
            $('.js-example-basic-multiple').not('#form_add #category').not('#form_edit #category').not('#form_view #category').not('#form_add #seller_asigned').not('#form_edit #seller_asigned').select2();
        });

        $("#ckbCheckAll").click(function() {
            $(".checkBoxClass").prop('checked', $(this).prop('checked'))
        });

        $(function() {
            $('.checkBoxClass').on('click', function() {
                $(this).closest('fieldset').find(':checkbox').prop('checked', this.checked);
            });
        });


        $(document).ready(function() {
            $(".selectcheckall").click(function() {
                if (this.checked) {
                    $('.catalogcustomer').each(function() {
                        $(".catalogcustomer").prop('checked', true);
                    })
                } else {
                    $('.catalogcustomer').each(function() {
                        $(".catalogcustomer").prop('checked', false);
                    })
                }
            });

        });

function deleteProduct(cId) {
    swal({
        title: 'Are you sure want to delete this?',
        text: 'Once deleted, You wont be able to revert this! ⚠️',
        icon: "warning",
        buttons: {
            cancel: {
                text: "No, cancel !",
                value: false,
                visible: true,
                className: "sweet-danager",
                closeModal: true,
            },
            confirm: {
                text: "Yes, delete it!",
                value: true,
                visible: true,
                className: "sweet-warning",
                closeModal: true
            },
        },
    }).then((willDelete) => {
        if (willDelete) {
            window.location.href = '<?= company_url('product/delete/') ?>' + cId
        }
    });
}

function eliminarMultiplesClientes() {


    var valorproductId = [];
    let todos = $('#input-todos').val();

    $('input.checkBoxClass[type=checkbox]').each(function() {
        if (this.checked)
            valorproductId.push($(this).val());
    });
    if (valorproductId.length == 0) {
        let mensaje = '<?= $langprods['seleccione_producto']?>';
        alert_func([mensaje, "error", "#DD6B55"]);
        return false;
    }

    $.ajax({
        url: `${options.url}/product/changeMultipleStatus`,
        type: 'POST',
        dataType: 'json',
        data: {
            productIds: String(valorproductId),
            prstatus: String(multipleStatus),
            statusvalue: String(statusvalue),
            prdcatvalue: String(prdcatvalue),
            todos: todos
        },
        success: function(response) {


        }
    })


}


//autoactivar los popovers de las imagenes (mismo estilo que lista de productos)
function activar_popover_imagenes() {
    if (typeof window.fvInitOrderImagePopovers === 'function') {
        window.fvInitOrderImagePopovers(document);
        return;
    }
    if (typeof jQuery === 'undefined' || typeof jQuery.fn.popover !== 'function') {
        return;
    }
    var $els = jQuery('[data-toggle="popover-hover"]');
    if (!$els.length) {
        return;
    }
    $els.each(function() {
        var $t = jQuery(this);
        if ($t.data('bs.popover')) {
            try {
                $t.popover('dispose');
            } catch (e) { /* ignore */ }
        }
    });
    var popoverImgTpl = '<div class="popover fv-popover-img-preview" role="tooltip"><div class="arrow"></div><div class="popover-body"></div></div>';
    $els.popover({
        html: true,
        trigger: 'hover focus',
        placement: 'right',
        container: 'body',
        template: popoverImgTpl,
        title: '',
        sanitize: false,
        content: function() {
            var $el = jQuery(this);
            var img = String($el.attr('data-img') || '').trim();
            if (!img || img.indexOf('data:') === 0) {
                var $thumb = $el.find('img').first();
                if ($thumb.length) {
                    var liveSrc = String($thumb.attr('src') || '').trim();
                    if (liveSrc && liveSrc.indexOf('data:') !== 0) {
                        img = liveSrc;
                    }
                }
            }
            if (!img) {
                return '';
            }
            var $wrap = jQuery('<div class="fv-popover-img-preview__inner"/>');
            $wrap.append(jQuery('<img>', {
                src: img,
                alt: '',
                class: 'fv-popover-img-preview__photo',
                draggable: false
            }));
            return $wrap;
        }
    }).on('inserted.bs.popover', function () {
        var inst = jQuery(this).data('bs.popover');
        if (inst && typeof inst.getTipElement === 'function') {
            var tip = inst.getTipElement();
            if (tip) {
                tip.classList.add('fv-popover-img-preview');
            }
        }
    });
}


function myParamName() {
    return "files";
}

$(document).ready(function() {

    $("body").scrollTop(50)

})


var orden_global = null




//devuelve en array los parametro de la url
function obtener_filtros() {
    var in_url = [];
    var in_url_str = window.location.search.replace('?', '').split('&');
    $.each(in_url_str, function(kay, val) {
        var v = val.split('=');
        in_url[v[0]] = v[1];

    });

    return in_url;
}

// FUNCIONES DE LENGUAJE PARA DATATABLE

function activar_tabla() {
    var $table = $('#infoTable');
    if (!$table.length || $table.attr('data-fv-server-list') === '1') {
        return;
    }

    tbl = $table.DataTable({
        "ordering": false,
        "language": {
            "info": "<?= $lang_datatable['info'] ?>",
            "infoEmpty": "<?= $lang_datatable['infoEmpty'] ?>",
            "lengthMenu": "<?= $lang_datatable['lengthMenu'] ?>",
            "loadingRecords": "<?= $lang_datatable['loadingRecords'] ?>",
            "search": "<?= $lang_datatable['search'] ?>",
            "zeroRecords": "<?= $lang_datatable['zeroRecords'] ?>",
            "infoFiltered": "<?= $lang_datatable['infoFiltered'] ?>",
            "paginate": {
                "first": "<?= $lang_datatable['first'] ?>",
                "last": "<?= $lang_datatable['last'] ?>",
                "next": "<?= $lang_datatable['next'] ?>",
                "previous": "<?= $lang_datatable['previous'] ?>"
            }
        }
    });
}

function validar_campo(event, input) {

    let $input = $(input);
    let nombre = $input.val() || '';
    let inputId = ($input.attr('id') || '').toLowerCase();
    let inputName = ($input.attr('name') || '').toLowerCase();
    // Ubicación admite varias entradas separadas por coma / punto y coma.
    if (inputId === 'location' || inputName === 'locations') {
        return;
    }
    if (nombre.indexOf(',') > -1 || nombre.indexOf(';') > -1) {

        nombre = nombre.replace(/,/g, '').replace(/;/g, '');

        $input.val(nombre)
        let msg = (window.fvLang && window.fvLang.no_comma_semicolon)
            ? window.fvLang.no_comma_semicolon
            : '';
        if (msg) {
            $input.next().html('<div class="error_msgbox show_msgbox">' + msg + '</div>')
            setTimeout(() => {
                $input.next().html('')
            }, "2000")
        }

    }
}

function copiarAlPortapapeles(texto) {
    var value = texto == null ? '' : String(texto);
    if (!value) {
        return Promise.reject(new Error('empty'));
    }

    function fallbackCopy() {
        return new Promise(function(resolve, reject) {
            var textarea = document.createElement('textarea');
            textarea.value = value;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'fixed';
            textarea.style.top = '0';
            textarea.style.left = '0';
            textarea.style.width = '1px';
            textarea.style.height = '1px';
            textarea.style.padding = '0';
            textarea.style.border = 'none';
            textarea.style.outline = 'none';
            textarea.style.boxShadow = 'none';
            textarea.style.background = 'transparent';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();
            textarea.setSelectionRange(0, textarea.value.length);
            var ok = false;
            try {
                ok = document.execCommand('copy');
            } catch (e) {
                ok = false;
            }
            document.body.removeChild(textarea);
            if (ok) {
                resolve(true);
            } else {
                reject(new Error('copy_failed'));
            }
        });
    }

    if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function' && window.isSecureContext) {
        return navigator.clipboard.writeText(value).catch(function() {
            return fallbackCopy();
        });
    }

    return fallbackCopy();
}

// group selector
function changeCurrencyStatus(event, currId, newStatus) {

    $.ajax({
            url: event.srcElement.getAttribute("data-url"),
            type: 'POST',
            dataType: 'json',
            data: {
                currId: String(currId),
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

function matchStart(params, data) {
    // If there are no search terms, return all of the data
    if ($.trim(params.term) === '') {
        return data;
    }

    // Skip if there is no 'children' property
    if (typeof data.children === 'undefined') {
        return null;
    }

    // `data.children` contains the actual options that we are matching against
    var filteredChildren = [];
    $.each(data.children, function(idx, child) {
        if (child.text.toUpperCase().indexOf(params.term.toUpperCase()) == 0) {
            filteredChildren.push(child);
        }
    });

    // If we matched any of the timezone group's children, then set the matched children on the group
    // and return the group object
    if (filteredChildren.length) {
        var modifiedData = $.extend({}, data, true);
        modifiedData.children = filteredChildren;

        // You can return modified objects from here
        // This includes matching the `children` how you want in nested data sets
        return modifiedData;
    }

    // Return `null` if the term should not be displayed
    return null;
    }
    
    // Intentar ejecutar los scripts cuando jQuery esté disponible
    if (typeof jQuery !== 'undefined') {
        initJQueryScripts();
    } else {
        // Si jQuery no está disponible, esperar a que se cargue
        var checkJQuery = setInterval(function() {
            if (typeof jQuery !== 'undefined') {
                clearInterval(checkJQuery);
                initJQueryScripts();
            }
        }, 50);
        
        // Timeout después de 5 segundos
        setTimeout(function() {
            clearInterval(checkJQuery);
            if (typeof jQuery === 'undefined') {
                console.error('jQuery not loaded after 5 seconds!');
            }
        }, 5000);
    }
})();
</script>

<script src="<?= base_url('companyasset/company/app.js?v=' . $v_imagen) ?>"></script>
<script src="<?= base_url('companyasset/company/installation.js?v=' . $v_imagen) ?>"></script>
<script src="<?= base_url('companyasset/company/navigation-loader.js?v=' . $v_imagen) ?>"></script>


<?php if (! empty($js) && is_array($js)): ?>
<?php foreach ($js as $companyScript): ?>
<?php if (! is_string($companyScript) || $companyScript === '') {
    continue;
} ?>
<script src="<?= base_url('companyasset/company/'.$companyScript. '?v='.$v_imagen)?>"></script>
<?php endforeach; ?>
<?php endif; ?>

</body>

</html>

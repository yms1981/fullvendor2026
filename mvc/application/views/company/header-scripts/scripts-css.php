<?php
/**
 * @var $lang_json object The lang translations for every translatable string
 * @var string $v_imagen optional cache buster for assets
 */
if (!isset($v_imagen)) {
    $v_imagen = (string) time();
}
?>

<link rel="stylesheet" href="<?= dated_file_url('companyasset/css/core.css') ?>" />
<link rel="stylesheet" href="<?= dated_file_url('companyasset/css/company-shell.css') ?>" />
<link rel="stylesheet" href="<?= dated_file_url('companyasset/css/company-crud-modern.css') ?>" />
<link rel="stylesheet" href="<?= dated_file_url('companyasset/css/fv-company-modals.css') ?>" />
<link rel="stylesheet" href="<?= dated_file_url('companyasset/css/product-list-views.css') ?>" />
<link rel="stylesheet" href="<?= dated_file_url('companyasset/css/boxicons.css?') ?>" />
<link rel="stylesheet" href="<?= dated_file_url('companyasset/css/bootstrap.min.css?')?>" />
<link rel="stylesheet" href="<?= base_url('companyasset/css/sweetalert2.min.css') ?>">
<link rel="stylesheet" href="<?= dated_file_url('companyasset/css/style_adicional.css?')?>" />
<link rel="stylesheet" href="<?= dated_file_url('companyasset/css/fileinput.css?')?>" />
<link rel="stylesheet" href="<?= dated_file_url('companyasset/css/style-basic.css?')?>" />
<link rel="stylesheet" href="<?= dated_file_url('companyasset/css/dropzone.min.css?') ?>" type="text/css" />
<link rel="stylesheet" href="<?= base_url('companyasset/vendors/mdi/css/materialdesignicons.min.css')?>" />
<link rel="stylesheet" href="<?= base_url('companyasset/vendors/flag-icon-css/css/flag-icon.min.css')?>" />
<link rel="stylesheet" href="<?= base_url('companyasset/css/datepicker.css')?>" />
<link rel="stylesheet" href="<?= base_url('companyasset/vendors/datatables.net-bs4/dataTables.bootstrap4.css')?>" />
<link rel="stylesheet" href="<?= base_url('companyasset/vendors/jquery-bar-rating/css-stars.css')?>" />
<link rel="stylesheet" href="<?= base_url('companyasset/css/jquery-ui.css')?>" />

<link rel="stylesheet" href="<?= base_url('companyasset/css/product_import.css')?>" />
<link rel="shortcut icon" href="<?= base_url('companyasset/images/favicon.png')?>" />
<link rel="stylesheet" href="<?= base_url('companyasset/css/bootstrap-tagsinput.css')?>">

<link rel="stylesheet" type="text/css" href="<?= base_url('companyasset/css/dataTables.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('companyasset/css/bootstrap-multiselect.css') ?>">

<link rel="stylesheet" href="<?= base_url('companyasset/css/bootstrap-select.min.css') ?>">
<link rel="stylesheet" href="<?= base_url('companyasset/css/animate.min.css') ?>" />

<link rel="stylesheet" type="text/css" href="<?= dated_file_url('companyasset/css/dual-listbox.css?')?>">
<link href="<?= dated_file_url('companyasset/css/select2.min.css?') ?>" rel="stylesheet" />
<link rel="stylesheet" href="<?= base_url('companyasset/css/intlTelInput-r.css')?>" />
<link rel="stylesheet" href="<?= dated_file_url('companyasset/css/preloader.css?') ?>" />
<link rel="stylesheet" href="<?= dated_file_url('companyasset/css/fv-modern-grid.css') ?>" />
<link rel="stylesheet" href="<?= dated_file_url('companyasset/css/fv-seller-assign-modal.css') ?>" />
<link rel="stylesheet" href="<?= dated_file_url('companyasset/company/config-crud-datagrid.css') ?>" />


<script type="text/javascript" src="<?= dated_file_url('companyasset/js/jquery-3.5.1.js?') ?>"></script>
<!-- Core Loader - Sistema centralizado para carga de dependencias -->
<script type="text/javascript" src="<?= dated_file_url('companyasset/js/core-loader.js?') ?>"></script>
<?php
$fvUiLangId = function_exists('fv_resolve_company_language_id') ? fv_resolve_company_language_id() : 1;
$fvUiLang = array_merge(
    function_exists('fv_lang_json_flat_section') ? fv_lang_json_flat_section($fvUiLangId, 'cmp-toltip') : [],
    function_exists('fv_lang_json_flat_section') ? fv_lang_json_flat_section($fvUiLangId, 'config-crud-tools') : [],
    function_exists('fv_lang_json_flat_section') ? fv_lang_json_flat_section($fvUiLangId, 'errores_unit') : []
);
?>
<script>
window.fvUiLang = <?= json_encode($fvUiLang, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
window.fvUiText = function (key) {
    var sources = [window.fvUiLang, window.deleteLang, window.validationLang, window.saveLang];
    for (var i = 0; i < sources.length; i++) {
        var value = sources[i] && sources[i][key];
        if (typeof value === 'string' && value.trim() !== '') {
            return value;
        }
    }
    return '';
};
</script>
<script>
// Configurar CoreLoader con la URL de jQuery para fallback
if (typeof CoreLoader !== 'undefined') {
    CoreLoader.config({
        jQueryUrl: '<?= dated_file_url('companyasset/js/jquery-3.5.1.js?') ?>'
    });
    
    // Inicializar jQuery después de que se cargue
    CoreLoader.initJQuery();
}

// Esperar a que jQuery esté disponible para definir variables globales y cargar scripts de módulos
CoreLoader.waitForJQuery(function() {
    // Definir variables globales después de que jQuery esté disponible
    window.options = {
        url: '<?= rtrim(company_url(), '/') ?>',
        company_id: <?= (int) ($company_id ?? get_instance()->session->userdata('compId') ?? 0) ?>,
        text_loader: '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ',
        lang: <?= json_encode($lang_json ?? []) ?>,
    };
    
    // Separar scripts base (dependencias) de scripts de módulo
    <?php if (isset($jsFiles) && !empty($jsFiles)): ?>
    <?php 
    // Asegurar que $jsFiles sea un array indexado numéricamente
    $jsFilesArray = is_array($jsFiles) ? array_values($jsFiles) : [];
    // Filtrar valores vacíos o null
    $jsFilesArray = array_filter($jsFilesArray, function($file) {
        return !empty($file) && is_string($file);
    });
    
    // Separar scripts base (select2, etc.) de scripts de módulo
    $baseScripts = [];
    $moduleScripts = [];
    
    foreach ($jsFilesArray as $file) {
        // Scripts base que deben cargarse primero (solo dependencias críticas)
        // Bootstrap se carga en el footer, no aquí
        if (strpos($file, 'select2') !== false || 
            strpos($file, 'jquery-ui') !== false) {
            $baseScripts[] = $file;
        } else {
            $moduleScripts[] = $file;
        }
    }
    ?>
    
    // Primero cargar scripts base (dependencias como select2)
    var baseScripts = <?= json_encode(array_values($baseScripts), JSON_UNESCAPED_SLASHES) ?>;
    var moduleScripts = <?= json_encode(array_values($moduleScripts), JSON_UNESCAPED_SLASHES) ?>;
    
    // Cargar primero las dependencias base, luego los scripts de módulo
    if (baseScripts.length > 0) {
        CoreLoader.loadScripts(baseScripts, function() {
            console.log('CoreLoader: Scripts base cargados');
            // Después de cargar las dependencias, cargar los scripts de módulo
            if (moduleScripts.length > 0) {
                CoreLoader.loadScripts(moduleScripts, function() {
                    console.log('CoreLoader: Todos los scripts de módulos cargados');
                });
            }
        });
    } else if (moduleScripts.length > 0) {
        // Si no hay scripts base, cargar directamente los de módulo
        CoreLoader.loadScripts(moduleScripts, function() {
            console.log('CoreLoader: Todos los scripts de módulos cargados');
        });
    }
    <?php endif; ?>
});
</script>

<link rel="manifest" href="<?= base_url('manifest.json?v=' . $v_imagen) ?>">
<meta name="theme-color" content="#e60000">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="FullVendor">
<link rel="apple-touch-icon" href="<?= base_url('companyasset/images/pwa/icon-192.png') ?>">
<link rel="icon" type="image/png" sizes="192x192" href="<?= base_url('companyasset/images/pwa/icon-192.png') ?>">
<link rel="icon" type="image/png" sizes="512x512" href="<?= base_url('companyasset/images/pwa/icon-512.png') ?>">

<style>
    /* No forzar z-index bajo en todos los popovers: oculta el preview de imágenes. */
    .popover {
        z-index: 1060;
    }

    /* Hover imagen producto: Bootstrap fija .popover ~276px; esta regla va después de bootstrap.min.css */
    .popover.fv-popover-img-preview {
        max-width: min(340px, calc(100vw - 2rem)) !important;
        z-index: 20050 !important;
        border: 1px solid rgba(0, 0, 0, 0.12);
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.18);
        background: #fff;
    }
    .popover.fv-popover-img-preview .popover-body {
        padding: 0.5rem !important;
        text-align: center;
    }
    .popover.fv-popover-img-preview .fv-popover-img-preview__photo,
    .popover.fv-popover-img-preview .fv-popover-img-preview__inner > img {
        width: 300px !important;
        max-width: min(300px, calc(100vw - 3rem)) !important;
        height: auto !important;
        display: block !important;
        margin: 0 auto;
        border: 0 !important;
        padding: 0 !important;
        background: #fff !important;
        object-fit: contain;
    }

    .dropwdown__customized__vue {

        --animate-duration: 0.4s;
        margin-top: 40px !important;
        z-index: 1;
        max-height: 180px;
        overflow-y: scroll;
        cursor: pointer;
    }

    .selected-category-to-catalog {
        --animate-duration: 0.4s;

    }

    .fullvendor-cursor-pointer {
        cursor: pointer;
    }

    .dropwdown__searproduct__vue {

        --animate-duration: 0.1s;
        margin-top: 40px !important;
        z-index: 1;
        max-height: 220px;
        overflow-y: scroll;
        cursor: pointer;
    }

    /* margin-top pequeño: un hueco grande entre el toggle y el menú hace que al mover el ratón se dispare
       mouseleave y el dropdown se cierre antes de llegar a los ítems */
    .dropwdown__customized {

        --animate-duration: 0.4s;
        margin-top: 2px !important;
        z-index: 999 !important;
        max-height: 300px;
        cursor: pointer;
    }

    .fv-company-nav .dropwdown__customized.fv-nav-dropdown {
        white-space: nowrap;
        width: max-content;
        min-width: max-content;
    }

    .fv-company-nav .dropwdown__customized.fv-nav-dropdown .dropdown-item {
        white-space: nowrap !important;
    }

    .text-purple {
        color: #7D3C98;
    }

    .timeline {
        position: relative;
        height: 100%;
        width: 100%;
        padding: 0;
        list-style: none;
    }

    .cuadro {
        width: 20px;
        /* Reducido a 20px */
        height: 20px;
        /* Reducido a 20px */
        border: 5px solid #ced4da;
        /* Bordes sólidos de 5 píxeles de color negro */
    }

    .custom-file-input:lang(en) ~ .custom-file-label::after {
        content: "\f15b";
        /* Código del icono de archivo de Font Awesome */
        font-family: 'Font Awesome 5 Free';
        font-weight: 900;
        margin-left: 5px;
    }

    .figure-fullvendor-product-search {
        width: 3em;
        overflow: hidden;
        /* margin: auto; */
    }

    .img-full-vendor-product-search {
        display: block;
        width: 100%;
        height: 50px;
        object-fit: scale-down;
    }

    .css-order-item {

        background-color: #FFF633;
        border-radius: 100%;
        width: 35px !important;
        height: 35px;
        text-align: center;
        /* padding: 20px; */
        /* margin-right: 10px; */
    }


    .hidden-important {
        display: none !important;
    }

    .fullvendor-select-order-categories .active-selected-category {
        /* Estilos por defecto */
        transition: background-color 0.15s ease, font-weight 0.15s ease, border 0.15s ease;
    }


    .fullvendor-select-order-categories .active-selected-category,
    .fullvendor-select-order-categories .active-selected-category:focus {
        background-color: #D8DFD7;
        font-weight: 800;
        border: 3px solid black;
        /* border-radius: 5px; */

    }

</style>

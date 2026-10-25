<?php
/**
 * @var string $main_content the content template path
 * @var array $lang_catalogo translations of the catalog module
 */
?>
<?php
$fvHtmlLang = 'en';
if (function_exists('fv_resolve_company_language_id') && function_exists('fv_language_id_to_locale')) {
    $fvHtmlLang = fv_language_id_to_locale((int) fv_resolve_company_language_id());
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($fvHtmlLang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <?php
    $this->load->view('common/head');
    $this->load->view('company/header-scripts/scripts-css');
    $fvDatePhp = (isset($format_date) && is_object($format_date) && ! empty($format_date->format_php))
        ? (string) $format_date->format_php
        : (function_exists('fv_resolve_display_date_format') ? fv_resolve_display_date_format() : 'm/d/Y');
    // Siempre derivar del format_php del perfil para que inputs y listados coincidan.
    $fvDateJs = function_exists('fv_date_format_jquery_ui')
        ? fv_date_format_jquery_ui($fvDatePhp)
        : (function_exists('fv_display_date_format_js') ? fv_display_date_format_js() : 'mm/dd/yy');
    $fvDateBootstrap = function_exists('fv_date_format_bootstrap')
        ? fv_date_format_bootstrap($fvDatePhp)
        : 'mm/dd/yyyy';
    ?>
    <script>
    window.fvCompanyDateFormatPhp = <?= json_encode($fvDatePhp, JSON_UNESCAPED_UNICODE) ?>;
    window.fvCompanyDateFormatJs = <?= json_encode($fvDateJs, JSON_UNESCAPED_UNICODE) ?>;
    window.fvCompanyDateFormatBootstrap = <?= json_encode($fvDateBootstrap, JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <style>
    /* Estilos globales del panel: ver company-shell.css */
    </style>
</head>
<body>
<?php
// Cargar el loader elegante reutilizable
$this->load->view('components/loader');
?>
<?php
$this->load->view('company/layouts/loader');
?>
<!--Offcanvas menu area start-->
<div class="off_canvars_overlay"></div>
<div class="Offcanvas_menu">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="canvas_open">
                    <!-- <a href="javascript:void(0)"><i class="ion-navicon"></i></a> -->
                    <a id="seach_insidebar" hidden href="javascript:void(0)"><i
                            class="ion-ios-search-strong"></i></a>
                </div>
                <div class="Offcanvas_menu_wrapper">
                    <br><br>
                    <div class="search_container">
                        <?php
                        $lang_catalogo = $lang_catalogo ?? [];
                        $categorias = $categorias ?? [];
                        $categorias = is_array($categorias) ? $categorias : [];
                        ?>
                        <form class="form-filtro-buscar" action="<?= base_url('catalogo/producto') ?>" method="get">
                            <input hidden class="short-value" type="text" name="short">
                            <div class="hover_category movil">
                                <select class="select_option" name="cat_id">
                                    <option selected value><?= strtoupper($lang_catalogo['option_all'] ?? 'Todas') ?></option>
                                    <?php
                                    if (count($categorias) > 0) {
                                        foreach ($categorias as $cat) {
                                            $cat = is_array($cat) ? (object) $cat : $cat;
                                            if (isset($cat->qty_products) && $cat->qty_products > 0) {
                                                ?>
                                                <option <?= $this->input->get('cat_id') == ($cat->cat_id ?? null) ? 'selected' : '' ?>
                                                    value="<?= $cat->cat_id ?? '' ?>">
                                                    <?= strtoupper(
                                                        isset($cat->category_default) && $cat->category_default == 1
                                                            ? ($lang_catalogo['sin_categoria'] ?? 'Sin categoría')
                                                            : ($cat->category_name ?? '')
                                                    ) ?>
                                                </option>
                                                <?php
                                            }
                                        }
                                    } ?>
                                </select>
                            </div>
                            <div class="search_box">
                                <input name="search" value="<?= $this->input->get('search') ?>"
                                       placeholder="<?= $lang_catalogo['seach_product'] ?? 'Buscar producto' ?>" type="text">
                                <button type="submit"><?= $lang_catalogo['search'] ?? 'Buscar' ?></button>
                            </div>
                        </form>
                    </div>
                    <div id="menu" class="text-start ">

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="app_vue">
    <?php
    $this->load->view('company/header'); ?>

    <section id="main-content">
        <?php
        $this->load->view($main_content, get_defined_vars()); ?>
    </section>
</div>
<footer>
    <?php
    $this->load->view('company/footer');
    ?>
</footer>
<script>
// Esperar a que jQuery esté disponible antes de ejecutar este script
(function() {
    function waitForJQueryAndRun(callback, maxAttempts) {
        maxAttempts = maxAttempts || 50;
        var attempts = 0;
        
        function check() {
            attempts++;
            if (typeof jQuery !== 'undefined') {
                callback();
            } else if (attempts < maxAttempts) {
                setTimeout(check, 100);
            } else {
                console.warn('jQuery not available for format_numeric logging');
            }
        }
        check();
    }
    
    waitForJQueryAndRun(function() {
<?php if (defined('ENVIRONMENT') && ENVIRONMENT === 'development'): ?>
        // Mostrar valores del API en la consola del navegador
        <?php if (isset($format_numeric)): ?>
        console.group('🔢 Format Numeric (API Response)');
        console.log('format_numeric:', <?= json_encode($format_numeric) ?>);
        <?php if (is_object($format_numeric) || (is_array($format_numeric) && !isset($format_numeric['error']))): ?>
            console.log('✅ format_numeric recibido correctamente');
            <?php if (is_object($format_numeric)): ?>
            console.log('  - format_id:', <?= isset($format_numeric->format_id) ? $format_numeric->format_id : 'null' ?>);
            console.log('  - numeric_format:', <?= isset($format_numeric->numeric_format) ? json_encode($format_numeric->numeric_format) : 'null' ?>);
            console.log('  - radixPoint:', <?= isset($format_numeric->radixPoint) ? json_encode($format_numeric->radixPoint) : 'null' ?>);
            console.log('  - groupSeparator:', <?= isset($format_numeric->groupSeparator) ? json_encode($format_numeric->groupSeparator) : 'null' ?>);
            console.log('  - digits:', <?= isset($format_numeric->digits) ? $format_numeric->digits : 'null' ?>);
            <?php endif; ?>
        <?php else: ?>
            console.error('❌ Error en format_numeric:', <?= json_encode($format_numeric) ?>);
        <?php endif; ?>
        console.groupEnd();
        <?php else: ?>
        console.warn('⚠️ format_numeric no está definido en $dataForView');
        <?php endif; ?>

        <?php 
        // Verificar si las variables existen, incluso si son null
        $format_currency_exists = array_key_exists('format_currency', get_defined_vars()) || isset($format_currency);
        $code_currency_exists = array_key_exists('code_currency', get_defined_vars()) || isset($code_currency);
        ?>
        
        <?php if ($format_currency_exists): ?>
        console.group('💰 Currency Symbol (API Response)');
        console.log('format_currency existe:', true);
        console.log('format_currency valor:', <?= isset($format_currency) ? json_encode($format_currency) : 'null' ?>);
        console.log('format_currency tipo:', <?= isset($format_currency) ? json_encode(gettype($format_currency)) : '"undefined"' ?>);
        <?php if (isset($format_currency) && $format_currency !== null): ?>
            <?php if (!is_array($format_currency) || !isset($format_currency['error'])): ?>
                console.log('✅ format_currency recibido correctamente');
                console.log('  - Valor:', <?= json_encode($format_currency) ?>);
            <?php else: ?>
                console.error('❌ Error en format_currency:', <?= json_encode($format_currency) ?>);
            <?php endif; ?>
        <?php else: ?>
            console.warn('⚠️ format_currency es null o undefined');
        <?php endif; ?>
        console.groupEnd();
        <?php else: ?>
        console.error('❌ format_currency NO está definido en $dataForView');
        <?php endif; ?>

        <?php if ($code_currency_exists): ?>
        console.group('💵 Currency Code (API Response)');
        console.log('code_currency existe:', true);
        console.log('code_currency valor:', <?= isset($code_currency) ? json_encode($code_currency) : 'null' ?>);
        console.log('code_currency tipo:', <?= isset($code_currency) ? json_encode(gettype($code_currency)) : '"undefined"' ?>);
        <?php if (isset($code_currency) && $code_currency !== null): ?>
            <?php if (!is_array($code_currency) || !isset($code_currency['error'])): ?>
                console.log('✅ code_currency recibido correctamente');
                console.log('  - Valor:', <?= json_encode($code_currency) ?>);
            <?php else: ?>
                console.error('❌ Error en code_currency:', <?= json_encode($code_currency) ?>);
            <?php endif; ?>
        <?php else: ?>
            console.warn('⚠️ code_currency es null o undefined');
        <?php endif; ?>
        console.groupEnd();
        <?php else: ?>
        console.error('❌ code_currency NO está definido en $dataForView');
        <?php endif; ?>
<?php endif; ?>
    });
})();
</script>
<?php
// Asistente local (voz + ayuda contextual + análisis de formularios). Sin IA externa.
$this->load->view('company/assistant/widget');
// GPS sellers: rutas del día en mapa.
$this->load->view('company/gps/widget');
// Chat empresarial (Sellers / Warehouse / Managers): texto, foto y audio.
$this->load->view('company/chat/widget');
?>
<script src="<?= base_url('companyasset/company/assistant.js?v=' . (defined('APP_VERSION') ? APP_VERSION : '1')) ?>"></script>
</body>
</html>

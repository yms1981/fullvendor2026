</div>
<?php
    // Leer flash una sola vez (CI3 lo descarta al leerlo).
    $flashSuccess = $this->session->flashdata('success');
    $flashErrorQty = $this->session->flashdata('error_qty_notempty');
    $flashMsgCartCleared = $this->session->flashdata('msg_cart_cleared');
    $flashMsg = $this->session->flashdata('msg');

    $icon = !empty($flashSuccess) ? 'success' : 'error';
    $error_qty = !empty($flashErrorQty);

    // Normalizar mensaje: texto plano del catálogo público.
    // Ignorar payloads JSON del panel company (["Email enviado...","success","#A5DC86"]).
    $flashAlertText = '';
    $flashAlertIcon = $icon;
    if (!empty($flashMsgCartCleared) && is_string($flashMsgCartCleared)) {
        $flashAlertText = $flashMsgCartCleared;
        $flashAlertIcon = 'success';
    } elseif (!empty($flashMsg)) {
        if ($flashMsg === 'error') {
            $flashAlertText = 'Some error occured, Please try again!';
            $flashAlertIcon = 'error';
        } elseif (is_string($flashMsg)) {
            $decodedFlash = json_decode($flashMsg, true);
            $isCompanyJsonFlash = json_last_error() === JSON_ERROR_NONE
                && is_array($decodedFlash)
                && isset($decodedFlash[0], $decodedFlash[1]);
            if ($isCompanyJsonFlash) {
                // No mostrar avisos del panel company al abrir el catálogo por email.
                $flashAlertText = '';
            } else {
                $flashAlertText = $flashMsg;
            }
        } elseif (is_array($flashMsg) && isset($flashMsg[0], $flashMsg[1])) {
            // Payload company ya decodificado: no mostrar en catálogo público.
            $flashAlertText = '';
        }
    }
?>

<!-- modal area start-->
<div class="modal fade" id="modal_box" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content rounded">
            <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
            <div class="modal_body">
                <div class="container">
                    <div class="row">

                        <!-- <div id="loader_modal" style="height: 500px;">
                            <div class="loading-center d-flex align-items-center justify-content-center">
                                <div class="d-flex flex-column justify-content-center align-items-center">
                                    <img width="100px" src="<?= base_url('images/logo.png') ?>" alt="">
                                    <div>
                                        <div class="object object_one"></div>
                                        <div class="object object_two"></div>
                                        <div class="object object_three"></div>
                                    </div>
                                </div>
                            </div>
                        </div> -->

                        <div class="col-lg-5 col-md-5 col-sm-12">
                            <div class="modal_tab">

                            </div>
                        </div>
                        <div class="col-lg-7 col-md-7 col-sm-12">
                            <div class="modal_right">

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!--footer area start-->
<footer class="footer_widgets">

    <div class="footer_top">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-5 col-sm-7">
                    <div class="widgets_container contact_us">
                        <h4><?= strtoupper($lang_catalogo['contact']) ?></h4>
                        <div class="aff_content">

                            <p><?= $info_company->name ?><br>
                                <?= $info_company->email ?><br>
                                <?= $info_company->phone_number ?>
                            </p>

                            <?php $catalogUriIndex = isset($catalog_uri_index) ? (string) $catalog_uri_index : 'catalogo/producto'; ?>
                            <a href="<?= base_url($catalogUriIndex) ?>?token=<?= rawurlencode((string) $company->token_kor) ?>">
                                <?php $footer_logo = !empty($info_company->image) ? company_logo_url((string) $info_company->image) : base_url('images/logos/logo.png'); ?>
                                <img src="<?= $footer_logo ?>"
                                    style="height: 70px;max-width: 150px; object-fit: scale-down;"
                                    alt="<?= $info_company->name ?>"
                                    onerror="this.onerror=null; this.src='<?= base_url('images/logos/logo.png') ?>';">
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-2 col-md-3 col-sm-5">

                </div>
                <div class="col-lg-2 col-md-4 col-sm-6">

                </div>
                <div class="col-lg-2 col-md-5 col-sm-6">

                </div>
                <div class="col-lg-3 col-md-7 col-sm-12">

                </div>
            </div>
        </div>
    </div>
    <div class="footer_bottom">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-12 col-md-12">
                    <div class="copyright_area d-flex justify-content-around flex-wrap">
                        <p>Copyright © <?= date("Y") ?> <?= $lang_footer["all_rigths"] ?></p>
                        <p><?= $lang_footer["develop_by"] ?><a id="last-index" tabindex="9999"
                                href="https://fullvendor.net/" target="_new" role="button"
                                title="go to www.fullvendor.net/"><img src="<?= base_url('uploads/logos/logo1.png') ?>"
                                    class="thumbnail rounded"
                                    style="display:inline-block;width: 25px;margin-bottom:0;/*margin-right:29px;*/">
                                Fullvendor</a></p>
                        <p><?= $lang_footer["version"] ?> <?= APP_VERSION ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>
<!--footer area end-->
<!-- modal area end-->
<!-- Plugins JS -->
<script>
// window.onload = function() {
//     console.log(window.location.href);
//     console.log(window.location.pathname);
//     alert("¡Hola onload!");
//     return
// };

// document.addEventListener('DOMContentLoaded', function() {

//     alert("¡Hola DOMContentLoaded!");
// });
</script>

<script src="<?= base_url('companyasset/antomi/assets/js/plugins.js'.'?v='.$version)?>"></script>
<!-- Main JS -->
<script src="<?= base_url('companyasset/antomi/assets/js/main.js'.'?v='.$version)?>"></script>
<script src="<?= base_url('companyasset/antomi/assets/js/jquery.toast.js'.'?v='.$version)?>"></script>
<script src="<?= base_url('companyasset/antomi/assets/js/sweetalert2.all.min.js'.'?v='.$version)?>"></script>

<?php
$fvIsProd = ! defined('ENVIRONMENT') || ENVIRONMENT !== 'development';
$vueCdn = $fvIsProd ? 'https://unpkg.com/vue@3/dist/vue.global.prod.js' : 'https://unpkg.com/vue@3/dist/vue.global.js';
$vuexCdn = $fvIsProd ? 'https://unpkg.com/vuex@4/dist/vuex.global.prod.js' : 'https://unpkg.com/vuex@4/dist/vuex.global.js';
?>
<script src="<?= htmlspecialchars($vueCdn, ENT_QUOTES, 'UTF-8') ?>"></script>
<script src="<?= htmlspecialchars($vuexCdn, ENT_QUOTES, 'UTF-8') ?>"></script>

<?php if(isset($js_vue)): ?>
<?php foreach($js_vue as $vue_js): ?>
<script src="<?= base_url('companyasset/catalog/'.$vue_js. '?v='.$version)?>"></script>
<?php endforeach;?>
<?php endif;?>

<script type="text/javascript">
<?php
$isCatalogSpanish = (isset($info_company) && is_object($info_company) && isset($info_company->language_id) && (int)$info_company->language_id === 2);
$cleanCartLang = $isCatalogSpanish ? [
    'confirm' => isset($lang_catalogo['limpiar_carrito']) ? $lang_catalogo['limpiar_carrito'] : '¿Limpiar carrito?',
    'yes' => isset($lang_catalogo['yes']) ? $lang_catalogo['yes'] : 'Sí',
    'no' => 'No',
    'error_url' => 'No se pudo obtener la URL para vaciar el carrito.',
    'error_default' => 'Error al vaciar el carrito',
] : [
    'confirm' => 'Clear cart?',
    'yes' => 'Yes',
    'no' => 'No',
    'error_url' => 'Could not get URL to clear cart.',
    'error_default' => 'Error clearing cart',
];
$popoverLang = $isCatalogSpanish ? [
    'choose_qty' => 'Cantidad',
    'qty' => 'Cant.',
    'save' => 'Guardar',
    'comment' => 'Comentario',
    'remove_fromcart' => 'Quitar del carrito',
] : [
    'choose_qty' => 'Quantity',
    'qty' => 'Qty',
    'save' => 'Save',
    'comment' => 'Comment',
    'remove_fromcart' => 'Remove from cart',
];
?>
var url_base = '<?= base_url() ?>'
var qty_car = <?= isset($qty_car) ? (int)$qty_car : 0 ?>;
var token = '<?= $company->token_kor ?>',
    more_categories = '<?= $lang_catalogo['more_categories'] ?>',
    less_categories = '<?= $lang_catalogo['less_categorias'] ?>',
    add_product = '<?= $lang_catalogo['add'] ?>',
    clean_cart = '<?= $lang_catalogo['clean_cart'] ?>',
    yes = '<?= $lang_catalogo['yes'] ?>';
var cleanCartConfirmText = <?= json_encode($cleanCartLang['confirm']) ?>;
var cleanCartYes = <?= json_encode($cleanCartLang['yes']) ?>;
var cleanCartNo = <?= json_encode($cleanCartLang['no']) ?>;
var cleanCartErrorUrl = <?= json_encode($cleanCartLang['error_url']) ?>;
var cleanCartErrorDefault = <?= json_encode($cleanCartLang['error_default']) ?>;
var popoverChooseQty = <?= json_encode($popoverLang['choose_qty']) ?>;
var popoverQty = <?= json_encode($popoverLang['qty']) ?>;
var popoverSave = <?= json_encode($popoverLang['save']) ?>;
var popoverComment = <?= json_encode($popoverLang['comment']) ?>;
var popoverRemoveFromCart = <?= json_encode($popoverLang['remove_fromcart']) ?>;

const swalalert = Swal.mixin({
    customClass: {
        confirmButton: 'btn btn-success',
        cancelButton: 'btn btn-danger'
    },
    buttonsStyling: false,
    width: '330'
})

$(window).on("load", function() {
    $(".preloader").fadeOut("slow");
});

function alert_func(msg, icon) {
    var alertIcon = icon || <?= json_encode($flashAlertIcon ?: $icon, JSON_UNESCAPED_UNICODE) ?>;
    swalalert.fire('', msg, alertIcon);
}
</script>

<?php foreach($js as $js){ ?>
<script src="<?= base_url('companyasset/catalog/'.$js .'?v='.$version) ?>"></script>
<?php }?>

<script>
<?php if ($flashAlertText !== ''): ?>
alert_func(
    <?= json_encode($flashAlertText, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    <?= json_encode($flashAlertIcon, JSON_UNESCAPED_UNICODE) ?>
);
<?php endif ?>

<?php if($error_qty): ?>
Swal.fire({
    text: clean_cart,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#28A745',
    confirmButtonText: yes,
    cancelButtonColor: '#d33',
    cancelButtonText: 'No',
    reverseButtons: true

}).then((result) => {
    if (result.isConfirmed) {
        const form = new FormData()
        form.append("token", token)
        fetch(url_base + "catalogo/producto/create_neworder", {
                method: "POST",
                body: form
            })
            .then(response => response.json())
            .then(data => {

                if (!data.success) {
                    swalalert.fire('', data.msg, 'error')
                    return
                }
                location.href = url_base + '/salesman/catalogo'
            });
    }
})

<?php endif; ?>
</script>



</body>

</html>
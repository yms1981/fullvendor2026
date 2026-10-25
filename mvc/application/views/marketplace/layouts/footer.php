<?php
    $icon = !empty($this->session->flashdata('success'))?$this->session->flashdata('success'):'error' ;
    $error_qty = !empty($this->session->flashdata('error_qty_notempty'))?true:false;

?>

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

                            <a href="<?= marketplace_url() ?>"><img
                                    src="<?= company_logo_url((string) $info_company->image) ?>"
                                    style="max-width: 150px; object-fit: scale-down;"
                                    alt="<?=  $info_company->name ?>"></a>
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
                                href="https://fullvendor.com/" target="_new" role="button"
                                title="go to www.fullvendor.com/"><img src="<?= base_url('images/logos/logo.png') ?>"
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
<script src="<?= base_url('companyasset/marketplace/'.$vue_js. '?v='.$version)?>"></script>
<?php endforeach;?>
<?php endif;?>

<script type="text/javascript">
var url_base = '<?= base_url() ?>'
var more_categories = '<?= $lang_catalogo['more_categories'] ?>',
less_categories = '<?= $lang_catalogo['less_categorias'] ?>',
add_product = '<?= $lang_catalogo['add'] ?>',
clean_cart = '<?= $lang_catalogo['clean_cart'] ?>',
yes = '<?= $lang_catalogo['yes'] ?>'

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

function alert_func(msg) {

    swalalert.fire('', msg, '<?=$icon?>')
}


</script>

<?php foreach($js as $js){ ?>
<script src="<?= base_url('companyasset/'.$js .'?v='.$version) ?>"></script>
<?php }?>

<script>
<?php if (!empty($this->session->flashdata('msg'))): ?>
<?php if ($this->session->flashdata('msg') == 'error') { ?>

alert_func("Some error occured, Please try again!");

<?php } else { ?>
console.log("alert")
alert_func('<?= $this->session->flashdata('msg') ?>');

//btn_createneworder.click()
<?php } ?>
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
            form.append("token",token)
            fetch(url_base + "catalogo/producto/create_neworder",{
                method: "POST",
                body: form
            })
            .then(response => response.json())
            .then(data => {

                if (!data.success) {
                    swalalert.fire('',data.msg,'error')
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

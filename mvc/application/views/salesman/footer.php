    <!--footer area start-->
    <footer class="footer_widgets">

        <div class="footer_bottom">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-12 col-md-12">
                        <div class="copyright_area d-flex justify-content-around flex-wrap">
                            <p>Copyright © <?= date("Y") ?> <?= $lang_footer["all_rigths"] ?></p>
                            <p><?= $lang_footer["develop_by"] ?><a id="last-index" tabindex="9999" href="https://fullvendor.com/" target="_new" role="button" title="go to www.fullvendor.net/"><img src="<?= base_url('images/logos/logo.png') ?>" class="thumbnail rounded" style="display:inline-block;width: 25px;margin-bottom:0;/*margin-right:29px;*/"> Fullvendor</a></p>
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
    <script src="<?= base_url('companyasset/antomi/assets/js/jquery.quicksearch.js'.'?v='.$version)?>"></script>
    <script src="<?= base_url('companyasset/antomi/assets/js/sweetalert2.all.min.js'.'?v='.$version)?>"></script>

    <script>
        var version = "?v=<?=$version?>"
        const swalalert = Swal.mixin({
            customClass: {
                confirmButton: 'btn btn-success',
                cancelButton: 'btn btn-danger'
            },
            buttonsStyling: false,
            width : '330'
        })

        <?php $icon = !empty($this->session->flashdata('success'))?'success':'error' ?>

        function alert_func(msg) {
            swalalert.fire('',msg,'<?=$icon?>')
        }

        <?php if (!empty($this->session->flashdata('msg'))): ?>
            <?php if ($this->session->flashdata('msg') == 'error') { ?>

                alert_func("Some error occured, Please try again!");

            <?php } else { ?>
                alert_func('<?= $this->session->flashdata('msg') ?>');
            <?php } ?>
        <?php endif ?>

    </script>

    <?php foreach($js as $js){ ?>
        <script src="<?= base_url('companyasset/'.$js .'?v='.$version) ?>"></script>
    <?php }?>

</body>
</html>

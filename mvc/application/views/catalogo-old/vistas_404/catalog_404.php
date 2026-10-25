 <!--error section area start-->
 <div class="error_page_bg">
    <div class="container">
        <div class="error_section">
            <div class="row">
                <div class="col-12">
                    <div class="error_form">
                        <h1>
                            <i class="fa fa-frown-o" aria-hidden="true"></i>
                        </h1>
                        <h2>
                            <?= (isset($lang_catalog))?$lang_catalog['catalog_expired']:'The requested address does not exist'?>
                        </h2>

                        <p><?= (isset($lang_catalog)) ? $lang_catalog['catalog_expired_description'] : 'Unable to access requested address: "' . (isset($requested_url) ? htmlspecialchars($requested_url) : '"/catalogo/producto?token=..."') . '"' ?>
                    </p>
                    <form action="#">
                        <a href="<?= base_url() ?>"><?= (isset($lang_catalog))?$lang_catalog['back_fullvendor']:'Back to'?>  <img src="<?= base_url('images/logos/logo.png') ?>" class="thumbnail rounded" style="display:inline-block;width: 25px;margin-bottom:0;/*margin-right:29px;*/">  Fullvendor</a></a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<!--error section area end-->

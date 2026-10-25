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

                        <p><?php
                        if (isset($lang_catalog['catalog_expired_description'])) {
                            echo $lang_catalog['catalog_expired_description'];
                        } else {
                            $url = isset($requested_url) ? htmlspecialchars((string) $requested_url, ENT_QUOTES, 'UTF-8') : '';
                            echo 'Unable to access requested address: "' . $url . '"';
                        }
                        ?></p>
                    <form action="#">
                        <a href="<?= base_url() ?>"><?= (isset($lang_catalog))?$lang_catalog['back_fullvendor']:'Back to'?>  <img src="<?= base_url('uploads/logos/logo1.png') ?>" class="thumbnail rounded" style="display:inline-block;width: 25px;margin-bottom:0;/*margin-right:29px;*/">  Fullvendor</a></a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<!--error section area end-->
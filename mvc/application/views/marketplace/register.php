<?php 

$ahora = DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));
$v_imagen = $ahora->format("YmdHisu");
$token_captcha = $this->config->item('recaptcha_token');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php $this->load->view("marketplace/layouts/css", ["v_imagen"=>$v_imagen]); ?>
    <title>MarketPlace | Register</title>
    <script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit" async defer>
    </script>
    
    <script>
    var url_base = '<?= marketplace_url() ?>'
    var onloadCallback = function() {
        grecaptcha.render('html_element', {
            'sitekey': '<?= $token_captcha ?>',
            'hl': '<?= $language ?>',
            'callback': onRecaptchaClick
        });
    };

    function onRecaptchaClick() {
        // La función que se ejecutará cuando el usuario haga clic en el reCAPTCHA
        console.log('Se hizo clic en el reCAPTCHA');
        $('.alert.not_robot').hide(200)
        // Puedes realizar otras acciones aquí, como habilitar un botón, etc.
    }
    </script>

</head>

<body>

    <div class="langbox">
        <a href="<?= marketplace_url('login/register_customer?len=en') ?>" id="langf-en"
            class="btn btn-light <?= $language == 'en'? 'active':'' ?> lang_frame text-dark text-decoration-none">
            <img src="<?= base_url('images/american.png') ?>" width="20px"
                alt="">&nbsp;<?= $lang_validate['ingles']?></a>

        <a href="<?= marketplace_url('login/register_customer?len=es') ?>" id="langf-spa"
            class="btn btn-light <?= $language == 'es'? 'active':'' ?> lang_frame text-dark text-decoration-none">
            <img src="<?= base_url('images/spanish_flag.png') ?>" width="20px"
                alt="">&nbsp;<?= $lang_validate['español']?></a>
    </div>

    <div class="container-scroller">
        <div class="page-body-wrapper full-page-wrapper">
            <div class="d-flex align-items-center auth">
                <div class="row flex-grow">
                    <div class="col-lg-4 mx-auto">
                        <div class="auth-form-light text-left p-5">
                            <div class="brand-logo text-center">
                                <?php if (@$settings->logo) { ?>
                                    <img src="<?= base_url('uploads/logos/'.@$settings->logo. '?v='. $v_imagen) ?>" width="100px" alt="">
                                <?php } ?>
                            </div>
                            <section>
                                <!-- Registrar Password -->
                                <div id="card-form">
                                    <div class="text-center">
                                        <h4><?= $langdef['welcomemsg']?></h4>
                                        <h6 class="font-weight-light"><?= 'Registrate para Continuar'?></h6>
                                    </div>
                                    <form id="register" autocomplete="off" novalidate class="pt-3"
                                        action="<?= marketplace_url('login/forgot_password') ?>" method="post">

                                        <div id="select_options_customer" style="display: none;">
                                            <!-- Company -->
                                            <div id="selector_company" class="form-group">
                                                <label for=""><?= $lang_validate['supplier'] ?></label>
                                                <select disabled name="company"
                                                    class="selectpicker w-100 selector_company"
                                                    title="<?= $lang_validate['choose'] ?>">
                                                </select>
                                            </div>

                                            <div id="alert_company" class="alert company alert-danger mt-1" role="alert"
                                                style="display: none;">
                                            </div>

                                            <!-- Customer -->
                                            <div id="select_customer" class="form-group">
                                                <label for=""><?= $lang_validate['customer_name'] ?></label>
                                                <select disabled id="selector_customer" name="customer"
                                                    class="selectpicker w-100 selector_customer"
                                                    title="<?= $lang_validate['choose'] ?>">
                                                </select>
                                            </div>
                                            <div id="alert_customer" class="alert customer alert-danger mt-1"
                                                role="alert" style="display: none;">
                                            </div>
                                        </div>
                                        <!-- Email -->
                                        <div class="form-group">
                                            <label for="">Email</label>
                                            <input autocomplete="off" type="text" name="username"
                                                class="form-control form-control-lg rounded"
                                                placeholder="<?= $langdef['emailph']?>">

                                            <div class="alert username alert-danger mt-1" role="alert"
                                                style="display: none;">
                                            </div>
                                        </div>

                                        <!-- Language -->
                                        <input type="text" name="langId" value="<?= $language ?>" hidden>

                                        <!-- Password -->
                                        <div class="form-group">
                                            <label for=""><?= $lang_validate['password'] ?></label>
                                            <div class="passwodeye">
                                                <span toggle="#password-field"
                                                    class="fa fa-fw fa-eye field_icon toggle-password"
                                                    style="top: 18px;"></span>
                                                <input autocomplete="off" placeholder="<?= $langdef['passph']?>"
                                                    type="password" class="form-control form-control-lg rounded"
                                                    name="password" autocomplete="off" required>

                                                <div class="alert password alert-danger mt-1" role="alert"
                                                    style="display: none;">
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Repeat Password -->
                                        <div class="form-group">
                                            <label for=""><?= $lang_validate['password_repeat'] ?></label>
                                            <div class="passwodeye">
                                                <span toggle="#password-field"
                                                    class="fa fa-fw fa-eye field_icon toggle-password"
                                                    style="top: 18px;"></span>
                                                <input autocomplete="off" placeholder="<?= $langdef['passph']?>"
                                                    type="password" class="form-control form-control-lg rounded"
                                                    name="password_repeat" autocomplete="off" required>

                                                <div class="alert password_repeat alert-danger mt-1" role="alert"
                                                    style="display: none;">
                                                </div>
                                            </div>
                                        </div>
                                        <!-- captcha no soy un robot -->
                                        <div>
                                            <div id="html_element" class="d-flex justify-content-center"></div>
                                            <div class="alert not_robot alert-danger mt-1" role="alert"
                                                style="display: none;">
                                            </div>
                                        </div>

                                        <!-- send -->
                                        <div class="mt-3">
                                            <button type="submit"
                                                class="btn btn-block btn-success btn-lg font-weight-semibold auth-form-btn"><?= 'Submit' ?></button>
                                        </div>

                                        <div class="my-2">
                                            <a href="<?= marketplace_url('login') ?>"
                                                class="btn btn-block info-white btn-lg btn-outline-info font-weight-semibold auth-form-btn"><i
                                                    class="fa-solid fa-right-to-bracket"></i> Login</a>
                                        </div>

                                        <div class="my-2">
                                            <a href="<?= marketplace_url('login/forgot_password') ?>"
                                                class="btn btn-block btn-lg secondary-white btn-outline-secondary font-weight-semibold auth-form-btn">
                                                <?= $langdef['forgottext']?> <i class="fa-solid fa-question"></i></a>
                                        </div>
                                    </form>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php $this->load->view("marketplace/layouts/scripts", ["v_imagen"=>$v_imagen]); ?>
    <!-- <script src="<?= base_url('companyasset/login_marketplace/index.js?v='. $v_imagen)?>"></script> -->

    <script src="<?= base_url('companyasset/marketplace/togglePassword.js?v='. $v_imagen)?>"></script>
    <script src="<?= base_url('companyasset/marketplace/hide_select_companyandcustomers.js?v='. $v_imagen)?>"></script>
    <script src="<?= base_url('companyasset/marketplace/register_customer.js?v='. $v_imagen)?>"></script>

</body>

</html>
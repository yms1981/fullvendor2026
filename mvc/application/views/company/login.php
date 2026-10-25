<?php

$ahora = DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));
$v_imagen = $ahora->format("YmdHisu");

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title> <?= $langdef['title'] ?>| <?= SITENAME ?></title>
    <link rel="stylesheet" href="<?= base_url('companyasset/css/style.css?v='.$v_imagen)?>" />
    <link rel="stylesheet" href="<?= base_url('companyasset/css/style_adicional.css?v='.$v_imagen)?>" />
    <link rel="stylesheet" href="<?= base_url('companyasset/vendors/mdi/css/materialdesignicons.min.css')?>">
    <link rel="stylesheet" href="<?= base_url('companyasset/vendors/flag-icon-css/css/flag-icon.min.css')?>">
    <link rel="stylesheet" href="<?= base_url('companyasset/vendors/css/vendor.bundle.base.css')?>">
    <link rel="shortcut icon" href="<?= base_url('images/logos/'.@$settings->favicon) ?>" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css"
        integrity="sha512-MV7K8+y+gLIBoVD59lQIYicR65iaqukzvf/nwasF0nqhPay5w/9lJmVM2hMDcnK1OnMGCdVK+iQrJ7lzPJQd1w=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />

</head>
<style text="text/css">
.langbox {
    display: flex;
    justify-content: end;
    column-gap: 10px;
    margin-left: auto;
    font-size: 12px;
    margin-top: 15px;
    margin-right: 10px;
}

#language-selector {
    border: 2px solid #d6d6d6;
    border-radius: 5px;
    padding: 5px 10px;
    background-color: white;
    cursor: pointer;
    font-size: 14px;
}

#language-selector:hover {
    border-color: #999;
}

.lang_selec {
    border: 3px solid #d6d6d6;
    border-radius: 9px;
    padding: 1px;
}

.lang_frame {
    cursor: pointer;
}
</style>

<body>
<?php
// Cargar el loader elegante reutilizable
$this->load->view('components/loader');
?>
    <div class="container-scroller">
        <div class="container-fluid page-body-wrapper full-page-wrapper">
            <div class="content-wrapper d-flex align-items-center auth">
                <div class="row flex-grow">
                    <div class="col-lg-4 mx-auto">
                        <div class="auth-form-light text-left p-5">
                            <div class="brand-logo text-center">
                                <img src="<?= base_url('images/logos/isologo.png') ?>" width="100px" alt="Logo" style="max-width: 100px; height: auto;">                                
                            </div>
                            <div class="text-center">                            
                                <h4 id="login-welcomemsg"><?= $langdef['welcomemsg']?></h4>
                                <h6 class="font-weight-light" id="login-msg1"><?= $langdef['msg1']?></h6>
                            </div>
                            <form id="login-form" class="pt-3 forms-sample"
                                action="<?= company_url('login') ?><?= ($this->input->get('redirectto'))? '?redirectto='.$this->input->get('redirectto') : ''; ?>"
                                method="post">
                                <?php if (!empty($msg)): ?>
                                <?= $msg ?>
                                <?php endif ?>
                                <div class="form-group">
                                    <label style="font-size: 14px; margin-bottom: 5px; display: block; font-weight: 500;">Idioma / Language:</label>
                                    <select id="language-selector" class="form-control form-control-lg" style="font-size: 14px;">
                                        <option value="1" data-flag="🇺🇸" <?= (isset($langid) && $langid == 1) ? 'selected' : '' ?>>🇺🇸 Inglés</option>
                                        <option value="2" data-flag="🇪🇸" <?= (isset($langid) && $langid == 2) ? 'selected' : '' ?>>🇪🇸 Español</option>
                                        <option value="3" data-flag="🇵🇹" <?= (isset($langid) && $langid == 3) ? 'selected' : '' ?>>🇵🇹 Portugués</option>
                                        <option value="4" data-flag="🇮🇹" <?= (isset($langid) && $langid == 4) ? 'selected' : '' ?>>🇮🇹 Italiano</option>
                                        <option value="5" data-flag="🇷🇺" <?= (isset($langid) && $langid == 5) ? 'selected' : '' ?>>🇷🇺 Ruso</option>
                                        <option value="6" data-flag="🇩🇪" <?= (isset($langid) && $langid == 6) ? 'selected' : '' ?>>🇩🇪 Alemán</option>
                                        <option value="7" data-flag="🇨🇳" <?= (isset($langid) && $langid == 7) ? 'selected' : '' ?>>🇨🇳 Chino</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <input autocomplete="off" type="email" name="username" id="login-email"
                                        class="form-control form-control-lg" placeholder="<?= $langdef['emailph']?>">
                                </div>

                                <div class="form-group passwodeye">

                                    <input autocomplete="off" id="password" placeholder="<?= $langdef['passph']?>"
                                        type="password" class="form-control form-control-lg" name="password"
                                        autocomplete="off" required>
                                    <span toggle="#password-field" class="fa fa-fw fa-eye field_icon toggle-password"
                                        style="top: 18px;"></span>

                                </div>
                                <div class="mt-3">
                                    <button type="submit" id="login-submit"
                                        class="btn btn-block btn-success btn-lg font-weight-semibold auth-form-btn"><?= $langdef['okbntext']?></button>
                                </div>
                                <div class="my-2 d-flex justify-content-between align-items-center">
                                    <div class="form-check">
                                        <label class="form-check-label text-muted">
                                            <input type="checkbox"
                                                class="form-check-input"><span id="login-checktext"><?= $langdef['checktext']?></span></label>
                                    </div>
                                    <a href="<?=company_url('forgetpassword')?>"
                                        class="auth-link text-black" id="login-forgottext"><?= $langdef['forgottext']?></a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript" src="https://code.jquery.com/jquery-3.5.1.js"></script>
    <script src="<?= base_url('companyasset/vendors/jquery-validation/jquery.validate.min.js')?>"></script>
    <script src="<?= base_url('companyasset/js/form-validation.js')?>"></script>
    <script src="<?= base_url('companyasset/vendors/js/vendor.bundle.base.js')?>"></script>
    <script src="<?= base_url('companyasset/js/off-canvas.js')?>"></script>
    <script src="<?= base_url('companyasset/js/hoverable-collapse.js')?>"></script>
    <script src="<?= base_url('companyasset/js/misc.js')?>"></script>
    <script src="<?= base_url('companyasset/js/settings.js')?>"></script>
    <script src="<?= base_url('companyasset/js/todolist.js')?>"></script>


    <script text="text/javascript">
    // Mapeo de language_id a locale para la API
    const languageLocaleMap = {
        1: 'en',  // Inglés
        2: 'es',  // Español
        3: 'pt',  // Portugués
        4: 'it',  // Italiano
        5: 'ru',  // Ruso
        6: 'de',  // Alemán
        7: 'zh'   // Chino
    };
    
    // Traducciones del login por idioma
    const loginTranslations = {
        1: { // Inglés
            welcomemsg: "Hello! let's get started",
            msg1: "Sign in to continue",
            emailph: "Email",
            passph: "Password",
            okbntext: "Sign in",
            checktext: "Keep me signed in",
            forgottext: "Forgot password?"
        },
        2: { // Español
            welcomemsg: "¡Hola, empecemos!",
            msg1: "Registrarse para continuar",
            emailph: "Correo electrónico",
            passph: "Clave de acceso",
            okbntext: "Ingresar",
            checktext: "Mantener mi ingreso",
            forgottext: "¿Olvidó su clave?"
        },
        3: { // Portugués
            welcomemsg: "Olá! Vamos começar",
            msg1: "Entre para continuar",
            emailph: "E-mail",
            passph: "Senha",
            okbntext: "Entrar",
            checktext: "Manter-me conectado",
            forgottext: "Esqueceu a senha?"
        },
        4: { // Italiano
            welcomemsg: "Ciao! Iniziamo",
            msg1: "Accedi per continuare",
            emailph: "Email",
            passph: "Password",
            okbntext: "Accedi",
            checktext: "Ricordami",
            forgottext: "Password dimenticata?"
        },
        5: { // Ruso
            welcomemsg: "Привет! Давайте начнем",
            msg1: "Войдите, чтобы продолжить",
            emailph: "Электронная почта",
            passph: "Пароль",
            okbntext: "Войти",
            checktext: "Запомнить меня",
            forgottext: "Забыли пароль?"
        },
        6: { // Alemán
            welcomemsg: "Hallo! Lass uns anfangen",
            msg1: "Anmelden, um fortzufahren",
            emailph: "E-Mail",
            passph: "Passwort",
            okbntext: "Anmelden",
            checktext: "Angemeldet bleiben",
            forgottext: "Passwort vergessen?"
        },
        7: { // Chino
            welcomemsg: "你好！让我们开始吧",
            msg1: "登录以继续",
            emailph: "电子邮件",
            passph: "密码",
            okbntext: "登录",
            checktext: "保持登录状态",
            forgottext: "忘记密码？"
        }
    };
    
    function changeLang(langId) {
        // Traducir el formulario inmediatamente
        translateLoginForm(langId);
        
        // Guardar el idioma en la sesión (sin recargar la página)
        if (typeof jQuery !== 'undefined') {
            $.ajax({
                url: '<?= base_url('company/language/changelanguage') ?>',
                type: 'POST',
                data: {
                    languageid: langId,
                    usertype: 'anonymous'
                },
                success: function() {
                    // Idioma guardado en sesión
                }
            });
        } else {
            // Si jQuery no está disponible, usar fetch
            fetch('<?= base_url('company/language/changelanguage') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'languageid=' + langId + '&usertype=anonymous'
            });
        }
    }
    
    function translateLoginForm(langId) {
        const translations = loginTranslations[langId];
        if (!translations) return;
        
        // Actualizar los textos del formulario
        const welcomemsgEl = document.getElementById('login-welcomemsg');
        const msg1El = document.getElementById('login-msg1');
        const emailInput = document.getElementById('login-email');
        const passwordInput = document.getElementById('password');
        const submitBtn = document.getElementById('login-submit');
        const checktextEl = document.getElementById('login-checktext');
        const forgottextEl = document.getElementById('login-forgottext');
        
        if (welcomemsgEl) welcomemsgEl.textContent = translations.welcomemsg;
        if (msg1El) msg1El.textContent = translations.msg1;
        if (emailInput) emailInput.placeholder = translations.emailph;
        if (passwordInput) passwordInput.placeholder = translations.passph;
        if (submitBtn) submitBtn.textContent = translations.okbntext;
        if (checktextEl) checktextEl.textContent = translations.checktext;
        if (forgottextEl) forgottextEl.textContent = translations.forgottext;
    }

    // Esperar a que jQuery esté cargado
    (function() {
        function initLogin() {
            if (typeof jQuery === 'undefined') {
                setTimeout(initLogin, 100);
                return;
            }
            
            // Event listener para el selector de idioma
            $('#language-selector').on('change', function() {
                var langId = parseInt($(this).val());
                changeLang(langId);
            });
            
            $(document).on('click', '.toggle-password', function() {
                $(this).toggleClass("fa-eye fa-eye-slash");
                var input = $("#password");
                input.attr('type') === 'password' ? input.attr('type', 'text') : input.attr('type', 'password')
            });

            // Mostrar loader inmediatamente al hacer click en el botón de login
            $(document).ready(function() {
                var loginForm = $('#login-form');
                var submitButton = loginForm.find('button[type="submit"]');
                var loader = document.getElementById('login-loader');
                
                // Capturar el click en el botón - PRIORIDAD MÁXIMA
                submitButton.on('mousedown touchstart', function(e) {
                    var username = $('input[name="username"]').val().trim();
                    var password = $('input[name="password"]').val().trim();
                    
                    // Mostrar loader inmediatamente si hay datos
                    if (username && password && typeof AppLoader !== 'undefined') {
                        AppLoader.show('Iniciando sesión...');
                        submitButton.prop('disabled', true);
                    }
                });
                
                // También capturar el click normal
                submitButton.on('click', function(e) {
                    var username = $('input[name="username"]').val().trim();
                    var password = $('input[name="password"]').val().trim();
                    
                    if (username && password && typeof AppLoader !== 'undefined') {
                        AppLoader.show('Iniciando sesión...');
                        submitButton.prop('disabled', true);
                    }
                });
                
                // Capturar el submit del formulario - como respaldo
                loginForm.on('submit', function(e) {
                    var username = $('input[name="username"]').val().trim();
                    var password = $('input[name="password"]').val().trim();
                    
                    // Si el formulario tiene datos, asegurar que el loader esté visible
                    if (username && password && typeof AppLoader !== 'undefined') {
                        // Verificar si el formulario es válido (si tiene validación)
                        if (typeof loginForm.valid === 'function') {
                            if (loginForm.valid()) {
                                AppLoader.show('Iniciando sesión...');
                            }
                        } else {
                            // Si no hay validación, mostrar loader directamente
                            AppLoader.show('Iniciando sesión...');
                        }
                        submitButton.prop('disabled', true);
                    }
                });
            });
        }
        
        // Iniciar cuando el DOM esté listo
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initLogin);
        } else {
            initLogin();
        }
    })();
    </script>
</body>

</html>

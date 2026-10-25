<?php
$ahora = DateTime::createFromFormat('U.u', number_format(microtime(true), 6, '.', ''));
$v_imagen = $ahora->format('YmdHisu');
$token_captcha = $this->config->item('recaptcha_token');
$mp = (isset($mp) && is_array($mp)) ? $mp : [];
$t = static function (string $key, string $fallback = '') use ($mp): string {
    $v = trim((string) ($mp[$key] ?? ''));

    return $v !== '' ? $v : $fallback;
};
$locale = isset($landingLocale) ? (string) $landingLocale : ((isset($language) && is_string($language)) ? $language : 'es');
$locale = strtolower(substr($locale, 0, 2));
$langs = [
    'es' => ['label' => 'Español', 'flag' => 'es'],
    'en' => ['label' => 'English', 'flag' => 'gb'],
    'pt' => ['label' => 'Português', 'flag' => 'pt'],
    'it' => ['label' => 'Italiano', 'flag' => 'it'],
    'de' => ['label' => 'Deutsch', 'flag' => 'de'],
    'ru' => ['label' => 'Русский', 'flag' => 'ru'],
    'zh' => ['label' => '中文', 'flag' => 'cn'],
];
$currentLang = $langs[$locale] ?? $langs['es'];
$langValidate = (isset($lang_validate) && is_array($lang_validate)) ? $lang_validate : [];
$langDef = (isset($langdef) && is_array($langdef)) ? $langdef : [];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($locale, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= htmlspecialchars($t('login_meta_title', 'FullVendor Market Place | Login'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="shortcut icon" href="<?= base_url('companyasset/images/favicon.png') ?>" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Sora:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="<?= base_url('companyasset/css/bootstrap-select.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('frontend/css/marketplace-login.css?v=' . $v_imagen) ?>">
    <script src="<?= base_url('companyasset/js/jquery-3.5.1.js?v=' . $v_imagen) ?>"></script>
    <script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit" async defer></script>
    <script>
    var url_base = '<?= marketplace_url() ?>';
    var onloadCallback = function () {
        grecaptcha.render('html_element', {
            'sitekey': '<?= $token_captcha ?>',
            'hl': '<?= htmlspecialchars($locale, ENT_QUOTES, 'UTF-8') ?>',
            'callback': onRecaptchaClick
        });
    };
    function onRecaptchaClick() {
        $('.alert.not_robot').hide(200);
    }
    </script>
</head>
<body class="fv-mpl-body">
<div class="fv-mpl">
  <section class="fv-mpl-hero">
    <a class="fv-mpl-brand" href="<?= htmlspecialchars(base_url('marketplaces?lang=' . rawurlencode($locale)), ENT_QUOTES, 'UTF-8') ?>">
      <span class="fv-mpl-brand__mark">F</span>
      <span class="fv-mpl-brand__text">FULLVENDOR<small>MARKET PLACE</small></span>
    </a>
    <div class="fv-mpl-hero__copy">
      <h1><?= $t('login_hero_title_html', 'Todo lo que <em>necesitas</em> en <em>un solo lugar</em>') ?></h1>
      <p><?= htmlspecialchars($t('login_hero_lead'), ENT_QUOTES, 'UTF-8') ?></p>
      <ul class="fv-mpl-features">
        <li><i class="fa-solid fa-store" aria-hidden="true"></i><span><?= htmlspecialchars($t('login_feat_brands'), ENT_QUOTES, 'UTF-8') ?></span></li>
        <li><i class="fa-solid fa-shield-halved" aria-hidden="true"></i><span><?= htmlspecialchars($t('login_feat_secure'), ENT_QUOTES, 'UTF-8') ?></span></li>
        <li><i class="fa-solid fa-truck" aria-hidden="true"></i><span><?= htmlspecialchars($t('login_feat_ship'), ENT_QUOTES, 'UTF-8') ?></span></li>
        <li><i class="fa-solid fa-headset" aria-hidden="true"></i><span><?= htmlspecialchars($t('login_feat_support'), ENT_QUOTES, 'UTF-8') ?></span></li>
      </ul>
    </div>
    <div class="fv-mpl-hero__glow" aria-hidden="true"></div>
    <div class="fv-mpl-hero__visual" aria-hidden="true">
      <img src="<?= htmlspecialchars(base_url('frontend/img/marketplace/hero-products.jpg'), ENT_QUOTES, 'UTF-8') ?>" alt="" width="960" height="540">
    </div>
  </section>

  <section class="fv-mpl-panel">
    <div class="fv-mpl-lang" data-mpl-lang>
      <button type="button" class="fv-mpl-lang__btn" id="fvMplLangBtn" aria-expanded="false" aria-haspopup="listbox"
              aria-label="<?= htmlspecialchars($t('lang_label', 'Idioma'), ENT_QUOTES, 'UTF-8') ?>">
        <img src="https://flagcdn.com/w40/<?= htmlspecialchars($currentLang['flag'], ENT_QUOTES, 'UTF-8') ?>.png" width="22" height="16" alt="">
        <span><?= htmlspecialchars($currentLang['label'], ENT_QUOTES, 'UTF-8') ?></span>
        <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
      </button>
      <ul class="fv-mpl-lang__menu" id="fvMplLangMenu" role="listbox" hidden>
        <?php foreach ($langs as $code => $meta) : ?>
          <li role="option" <?= $locale === $code ? 'aria-selected="true"' : '' ?>>
            <a class="<?= $locale === $code ? 'is-active' : '' ?>"
               href="<?= htmlspecialchars(marketplace_url('login?len=' . rawurlencode($code)), ENT_QUOTES, 'UTF-8') ?>"
               hreflang="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>">
              <img src="https://flagcdn.com/w40/<?= htmlspecialchars($meta['flag'], ENT_QUOTES, 'UTF-8') ?>.png" width="22" height="16" alt="">
              <span><?= htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8') ?></span>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div class="fv-mpl-card" id="card-form">
      <div class="fv-mpl-card__brand">
        <span class="fv-mpl-brand">
          <span class="fv-mpl-brand__mark">F</span>
          <span class="fv-mpl-brand__text">FULLVENDOR<small>MARKET PLACE</small></span>
        </span>
      </div>
      <h2><?= htmlspecialchars($t('login_welcome', (string) ($langDef['welcomemsg'] ?? '¡Bienvenido!')), ENT_QUOTES, 'UTF-8') ?></h2>
      <p class="fv-mpl-card__lead"><?= htmlspecialchars($t('login_subtitle', (string) ($langDef['msg1'] ?? 'Inicia sesión para continuar')), ENT_QUOTES, 'UTF-8') ?></p>

      <form id="sign_in" autocomplete="off" novalidate action="<?= marketplace_url('login/sign_in') ?>" method="post">
        <div id="select_options_customer" style="display: none;">
          <div id="selector_company" class="fv-mpl-field">
            <label for="company_select"><i class="fa-solid fa-building"></i> <?= htmlspecialchars((string) ($langValidate['supplier'] ?? $t('login_supplier')), ENT_QUOTES, 'UTF-8') ?></label>
            <select disabled name="company" data-size="7" data-live-search="true"
                    class="selectpicker w-100 selector_company"
                    title="<?= htmlspecialchars((string) ($langValidate['choose'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </select>
            <div id="alert_company" class="alert company alert-danger" role="alert" style="display: none;"></div>
          </div>
          <div id="select_customer" class="fv-mpl-field">
            <label for="customer_select"><i class="fa-solid fa-user"></i> <?= htmlspecialchars((string) ($langValidate['customer_name'] ?? $t('login_customer')), ENT_QUOTES, 'UTF-8') ?></label>
            <select disabled id="selector_customer" name="customer" data-size="7"
                    data-live-search="true" data-width="100%"
                    class="selectpicker selector_customer"
                    title="<?= htmlspecialchars((string) ($langValidate['choose'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </select>
            <div id="alert_customer" class="alert customer alert-danger" role="alert" style="display: none;"></div>
          </div>
        </div>

        <div class="fv-mpl-field">
          <div class="fv-mpl-field__control">
            <i class="fa-solid fa-envelope" aria-hidden="true"></i>
            <input id="email" autocomplete="username" type="email" name="username" class="keyup"
                   placeholder="<?= htmlspecialchars($t('login_email_ph', (string) ($langDef['emailph'] ?? 'Correo electrónico')), ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="alert username alert-danger" role="alert" style="display: none;"></div>
        </div>

        <div class="fv-mpl-field">
          <div class="fv-mpl-field__control">
            <i class="fa-solid fa-lock" aria-hidden="true"></i>
            <span class="fa fa-fw field_icon toggle-password fa-eye fv-mpl-field__toggle" style="order:3;margin-right:0.75rem;margin-left:0;"></span>
            <input id="password" autocomplete="current-password"
                   placeholder="<?= htmlspecialchars($t('login_pass_ph', (string) ($langDef['passph'] ?? 'Contraseña')), ENT_QUOTES, 'UTF-8') ?>"
                   type="password" class="keyup" name="password" required style="order:2;">
          </div>
          <div class="alert password alert-danger" role="alert" style="display: none;"></div>
        </div>

        <div class="fv-mpl-captcha">
          <div id="html_element"></div>
        </div>
        <div class="alert not_robot alert-danger" role="alert" style="display: none;"></div>

        <input id="langId" type="hidden" name="langId" value="<?= htmlspecialchars($locale, ENT_QUOTES, 'UTF-8') ?>">

        <button type="submit" class="fv-mpl-btn fv-mpl-btn--primary">
          <?= htmlspecialchars($t('login_submit', (string) ($langDef['okbntext'] ?? 'Iniciar sesión')), ENT_QUOTES, 'UTF-8') ?>
          <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </button>

        <a class="fv-mpl-btn fv-mpl-btn--ghost" href="<?= marketplace_url('login/register_customer?len=' . rawurlencode($locale)) ?>">
          <i class="fa-solid fa-user-plus" aria-hidden="true"></i>
          <?= htmlspecialchars($t('login_register'), ENT_QUOTES, 'UTF-8') ?>
        </a>

        <div class="fv-mpl-sep"><?= htmlspecialchars($t('login_or_continue'), ENT_QUOTES, 'UTF-8') ?></div>
        <div class="fv-mpl-social">
          <button type="button" class="fv-mpl-social__google" disabled title="<?= htmlspecialchars($t('login_social_soon'), ENT_QUOTES, 'UTF-8') ?>">
            <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true"><path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.3 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3 0 5.8 1.1 7.9 3l5.7-5.7C34.2 6.1 29.4 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.2-.1-2.3-.4-3.5z"/><path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 16 19 12 24 12c3 0 5.8 1.1 7.9 3l5.7-5.7C34.2 6.1 29.4 4 24 4 16.3 4 9.6 8.3 6.3 14.7z"/><path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.3 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-7.9l-6.5 5C9.5 39.6 16.2 44 24 44z"/><path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.3-2.3 4.2-4.2 5.6l6.2 5.2C39.4 36.1 44 30.7 44 24c0-1.2-.1-2.3-.4-3.5z"/></svg>
            Google
          </button>
          <button type="button" class="fv-mpl-social__ms" disabled title="<?= htmlspecialchars($t('login_social_soon'), ENT_QUOTES, 'UTF-8') ?>">
            <svg width="16" height="16" viewBox="0 0 23 23" aria-hidden="true"><path fill="#f25022" d="M1 1h10v10H1z"/><path fill="#00a4ef" d="M12 1h10v10H12z"/><path fill="#7fba00" d="M1 12h10v10H1z"/><path fill="#ffb900" d="M12 12h10v10H12z"/></svg>
            Microsoft
          </button>
        </div>

        <a class="fv-mpl-forgot" href="<?= marketplace_url('login/forgot_password?len=' . rawurlencode($locale)) ?>">
          <?= htmlspecialchars($t('login_forgot', (string) ($langDef['forgottext'] ?? '¿Olvidaste tu contraseña?')), ENT_QUOTES, 'UTF-8') ?>
        </a>
      </form>
    </div>
  </section>
</div>

<?php $this->load->view('marketplace/layouts/scripts', ['v_imagen' => $v_imagen]); ?>
<script src="<?= base_url('companyasset/marketplace/togglePassword.js?v=' . $v_imagen) ?>"></script>
<script src="<?= base_url('companyasset/marketplace/hide_select_companyandcustomers.js?v=' . $v_imagen) ?>"></script>
<script src="<?= base_url('companyasset/login_marketplace/index.js?v=' . $v_imagen) ?>"></script>
<script>
(function () {
  var root = document.querySelector('[data-mpl-lang]');
  if (!root) return;
  var btn = root.querySelector('#fvMplLangBtn');
  var menu = root.querySelector('#fvMplLangMenu');
  if (!btn || !menu) return;
  btn.addEventListener('click', function (e) {
    e.stopPropagation();
    var open = menu.hasAttribute('hidden');
    if (open) { menu.removeAttribute('hidden'); btn.setAttribute('aria-expanded', 'true'); }
    else { menu.setAttribute('hidden', ''); btn.setAttribute('aria-expanded', 'false'); }
  });
  document.addEventListener('click', function () {
    menu.setAttribute('hidden', '');
    btn.setAttribute('aria-expanded', 'false');
  });
})();
</script>
</body>
</html>

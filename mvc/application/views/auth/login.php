<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * @var array<string, string>           $login_page_texts
 * @var array<int, array<string, string>> $login_translations_by_lang
 * @var int                              $login_current_lang_id
 */

$lp = is_array($login_page_texts ?? null) ? $login_page_texts : [];
$translationsByLang = is_array($login_translations_by_lang ?? null) ? $login_translations_by_lang : [];
$currentLangId = isset($login_current_lang_id) ? (int) $login_current_lang_id : 1;
if ($currentLangId < 1 || $currentLangId > 7) {
    $currentLangId = 1;
}

$e = static function (?string $s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
};
$t = static function (string $key, string $default = '') use ($lp, $translationsByLang, $currentLangId): string {
    $val = $lp[$key] ?? null;
    if (! is_string($val) || $val === '') {
        $val = $translationsByLang[$currentLangId][$key] ?? null;
    }
    if (! is_string($val) || $val === '') {
        $val = $translationsByLang[1][$key] ?? $default;
    }

    return (string) $val;
};

$urlCustomerPortal = (string) ($url_customer_portal ?? site_url('customer'));
$urlSellerPortal = (string) ($url_seller_portal ?? site_url('sellers'));
$loginQuery = [];
if ($this->input->get('redirectto')) {
    $loginQuery['redirectto'] = (string) $this->input->get('redirectto');
}
if ($this->input->get('lang')) {
    $loginQuery['lang'] = (string) $this->input->get('lang');
}
$loginBase = parse_url(base_url('login'));
$loginFormAction = (is_array($loginBase) ? (string) ($loginBase['path'] ?? '/login') : '/login')
    . ($loginQuery !== [] ? '?' . http_build_query($loginQuery) : '');
$currentLangCode = ['en', 'es', 'pt', 'it', 'ru', 'de', 'zh'][$currentLangId - 1] ?? 'en';
$langSuffix = '?lang=' . rawurlencode($currentLangCode);
$assetV = date('YmdHis');

$languageOptions = [
    1 => ['code' => 'en', 'flag' => 'us', 'label' => 'English'],
    2 => ['code' => 'es', 'flag' => 'pe', 'label' => 'Español'],
    3 => ['code' => 'pt', 'flag' => 'pt', 'label' => 'Português'],
    4 => ['code' => 'it', 'flag' => 'it', 'label' => 'Italiano'],
    5 => ['code' => 'ru', 'flag' => 'ru', 'label' => 'Русский'],
    6 => ['code' => 'de', 'flag' => 'de', 'label' => 'Deutsch'],
    7 => ['code' => 'zh', 'flag' => 'cn', 'label' => '中文'],
];
$currentLangMeta = $languageOptions[$currentLangId] ?? $languageOptions[1];

$heroTitleHtml = $t('hero_title_html', 'Gestiona tu negocio <em>en un solo lugar</em>');
?>
<!DOCTYPE html>
<html lang="<?= $e($currentLangCode) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title><?= $e($t('title', 'Login Panel')) ?> | <?= $e(SITENAME) ?></title>
  <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('uploads/logos/isologo.png') ?>">
  <link rel="shortcut icon" href="<?= base_url('backend/images/favicon.png') ?>" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Sora:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= base_url('backend/vendors/mdi/css/materialdesignicons.min.css') ?>">
  <link rel="stylesheet" href="<?= base_url('backend/vendors/css/vendor.bundle.base.css') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css"
        integrity="sha512-MV7K8+y+gLIBoVD59lQIYicR65iaqukzvf/nwasF0nqhPay5w/9lJmVM2hMDcnK1OnMGCdVK+iQrJ7lzPJQd1w=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
  <link rel="stylesheet" href="<?= base_url('frontend/css/company-login.css?v=' . $assetV) ?>">
  <script>
    let userId = <?= ! empty($userId) ? json_encode($userId) : 'null' ?>;
    window.loginTranslations = <?= json_encode($translationsByLang, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    window.loginCurrentLangId = <?= (int) $currentLangId ?>;
  </script>
</head>
<body class="fv-cl-body">
  <div class="fv-cl__bg" aria-hidden="true"></div>

  <div id="login-loader" class="login-overlay" aria-hidden="true">
    <div class="box">
      <div class="spinner"></div>
      <div class="label" id="login-loader-text"><?= $e($t('loading_text', 'Loading...')) ?></div>
    </div>
  </div>

  <div class="fv-cl" id="login-container">
    <section class="fv-cl-hero">
      <a class="fv-cl-brand" href="<?= base_url() ?>">
        <span class="fv-cl-brand__mark">F</span>
        <span class="fv-cl-brand__text">FULLVENDOR<small>COMPANY</small></span>
      </a>
      <div class="fv-cl-hero__copy">
        <h1 id="login-hero-title"><?= $heroTitleHtml ?></h1>
        <p id="login-hero-lead"><?= $e($t('hero_lead', '')) ?></p>
        <ul class="fv-cl-features">
          <li><span class="fv-cl-features__ico" aria-hidden="true"><i class="fa-solid fa-box"></i></span><span id="login-feat-1"><?= $e($t('feat_products', '')) ?></span></li>
          <li><span class="fv-cl-features__ico" aria-hidden="true"><i class="fa-solid fa-chart-column"></i></span><span id="login-feat-4"><?= $e($t('feat_reports', '')) ?></span></li>
          <li><span class="fv-cl-features__ico" aria-hidden="true"><i class="fa-solid fa-cart-shopping"></i></span><span id="login-feat-2"><?= $e($t('feat_orders', '')) ?></span></li>
          <li><span class="fv-cl-features__ico" aria-hidden="true"><i class="fa-solid fa-store"></i></span><span id="login-feat-5"><?= $e($t('feat_marketplace', '')) ?></span></li>
          <li><span class="fv-cl-features__ico" aria-hidden="true"><i class="fa-solid fa-users"></i></span><span id="login-feat-3"><?= $e($t('feat_customers', '')) ?></span></li>
          <li><span class="fv-cl-features__ico" aria-hidden="true"><i class="fa-solid fa-gear"></i></span><span id="login-feat-6"><?= $e($t('feat_settings', '')) ?></span></li>
        </ul>
      </div>
      <figure class="fv-cl-hero__visual">
        <img src="<?= base_url('frontend/img/company/login-dashboard.jpg?v=' . $assetV) ?>"
             alt=""
             width="1100"
             height="700"
             loading="eager"
             decoding="async">
      </figure>
    </section>

    <section class="fv-cl-panel">
      <div class="fv-cl-lang" data-cl-lang>
        <button type="button" class="fv-cl-lang__btn" id="fvClLangBtn" aria-expanded="false" aria-haspopup="listbox"
                aria-label="<?= $e($t('language_label', 'Language')) ?>">
          <img src="https://flagcdn.com/w40/<?= $e($currentLangMeta['flag']) ?>.png" width="22" height="16" alt="" id="fvClLangFlag">
          <span id="fvClLangLabel"><?= $e($currentLangMeta['label']) ?></span>
          <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
        </button>
        <ul class="fv-cl-lang__menu" id="fvClLangMenu" role="listbox" hidden>
          <?php foreach ($languageOptions as $id => $info): ?>
            <li role="option" <?= $currentLangId === (int) $id ? 'aria-selected="true"' : '' ?>>
              <a href="#" class="fv-cl-lang__option<?= $currentLangId === (int) $id ? ' is-active' : '' ?>"
                 data-lang-id="<?= (int) $id ?>"
                 data-flag="<?= $e($info['flag']) ?>"
                 data-label="<?= $e($info['label']) ?>">
                <img src="https://flagcdn.com/w40/<?= $e($info['flag']) ?>.png" width="22" height="16" alt="">
                <span><?= $e($info['label']) ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
        <label for="login-language-selector" class="sr-only"><?= $e($t('language_label', 'Language')) ?></label>
        <select id="login-language-selector" class="fv-cl-lang__select" aria-hidden="true" tabindex="-1">
          <?php foreach ($languageOptions as $id => $info): ?>
            <option value="<?= (int) $id ?>" <?= $currentLangId === (int) $id ? 'selected' : '' ?>>
              <?= $e($info['label']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="fv-cl-card" id="login-card">
        <div class="fv-cl-card__brand">
          <span class="fv-cl-brand">
            <span class="fv-cl-brand__mark">F</span>
            <span class="fv-cl-brand__text">FULLVENDOR<small>COMPANY</small></span>
          </span>
          <p class="fv-cl-card__panel-label" id="login-panel-label"><?= $e($t('panel_label', '')) ?></p>
        </div>

        <nav class="fv-cl-tabs" aria-label="<?= $e($t('entry_portal_nav_label', 'Customer and seller portals')) ?>">
          <a class="is-active" href="<?= $e($urlCustomerPortal . $langSuffix) ?>">
            <i class="fa-regular fa-user" aria-hidden="true"></i>
            <span id="login-entry-customer"><?= $e($t('entry_customer_site', 'Cliente')) ?></span>
          </a>
          <a href="<?= $e($urlSellerPortal . $langSuffix) ?>">
            <i class="fa-solid fa-store" aria-hidden="true"></i>
            <span id="login-entry-seller"><?= $e($t('entry_seller_site', 'Vendedor')) ?></span>
          </a>
        </nav>

        <form id="login-form" class="fv-cl-form login-form" action="<?= $e($loginFormAction) ?>" method="post" autocomplete="off">
          <input type="hidden" name="lang" id="login-lang" value="<?= $e($currentLangCode) ?>">
          <?php if ($this->input->get('redirectto')): ?>
            <input type="hidden" name="redirectto" value="<?= $e((string) $this->input->get('redirectto')) ?>">
          <?php endif; ?>
          <?php if (! empty($msg)): ?>
            <div class="mb-3" id="login-server-msg"><?= $msg ?></div>
          <?php endif; ?>
          <div class="login-error-banner" id="login-error-banner" role="alert"></div>

          <div class="form-group">
            <label class="fv-cl-label" for="login-username" id="login-email-label"><?= $e($t('email_label', $t('emailph', 'Email'))) ?></label>
            <div class="fv-cl-field">
              <i class="fa-regular fa-envelope" aria-hidden="true"></i>
              <input type="text" name="username" id="login-username" class="form-control form-control-lg"
                     required placeholder="<?= $e($t('emailph', 'Enter your email')) ?>" autocomplete="username">
            </div>
          </div>
          <div class="form-group">
            <label class="fv-cl-label" for="login-password" id="login-password-label"><?= $e($t('password_label', $t('passph', 'Password'))) ?></label>
            <div class="fv-cl-field password-wrapper">
              <i class="fa-solid fa-lock" aria-hidden="true"></i>
              <input type="password" name="password" id="login-password" class="form-control form-control-lg"
                     required placeholder="<?= $e($t('passph', 'Enter your password')) ?>" autocomplete="current-password">
              <span class="fa fa-fw fa-eye toggle-password" id="login-toggle-password" role="button" tabindex="0"
                    aria-label="<?= $e($t('toggle_password', 'Show password')) ?>"></span>
            </div>
          </div>

          <div class="fv-cl-options login-options">
            <div class="form-check">
              <label class="form-check-label">
                <input type="checkbox" class="form-check-input" checked>
                <span id="login-checktext"><?= $e($t('checktext', 'Keep me signed in')) ?></span>
              </label>
            </div>
            <a href="<?= base_url('forgetpassword') ?>" class="fv-cl-forgot" id="login-forgottext">
              <?= $e($t('forgottext', 'Forgot password?')) ?>
            </a>
          </div>

          <button type="submit" id="login-submit" class="fv-cl-btn">
            <span id="login-submit-text"><?= $e($t('okbntext', 'Sign in')) ?></span>
            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
          </button>

          <div class="fv-cl-footer">
            <span id="login-dontaccount"><?= $e($t('dont_have_account', "Don't have an account?")) ?></span>
            <a href="<?= base_url('signup') ?>" class="fv-cl-signup">
              <span id="login-signup-link"><?= $e($t('sign_up_link', 'Sign up')) ?></span>
              <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
          </div>
        </form>
      </div>
    </section>

    <div id="login-success-splash" class="login-success-splash" aria-hidden="true">
      <div class="login-success-inner">
        <div class="fv-login-brand-loader" aria-hidden="true">
          <div class="fv-ring"></div>
          <img src="<?= base_url('images/logos/logo.png') ?>" alt="<?= $e(SITENAME) ?>">
        </div>
        <h2 id="splash-welcome-text"><?= $e($t('splash_welcome', 'Welcome back!')) ?></h2>
        <p class="splash-preparing" id="splash-loading-text"><?= $e($t('splash_loading', 'Loading your workspace...')) ?></p>
        <p class="splash-preparing" id="splash-preparing-text" style="margin-top:-12px;margin-bottom:28px;"><?= $e($t('splash_preparing', 'We are getting everything ready for you')) ?></p>
        <div class="login-promo-carousel" id="login-promo-carousel" aria-live="polite">
          <?php for ($pi = 1; $pi <= 4; $pi++): ?>
            <div class="login-promo-slide<?= $pi === 1 ? ' is-visible' : '' ?>" data-promo-index="<?= $pi ?>">
              <h3 id="splash-promo-<?= $pi ?>-title"><?= $e($t('promo_' . $pi . '_title', '')) ?></h3>
              <p id="splash-promo-<?= $pi ?>-text"><?= $e($t('promo_' . $pi . '_text', '')) ?></p>
            </div>
          <?php endfor; ?>
        </div>
        <div class="login-progress-track" aria-hidden="true">
          <div class="login-progress-fill" id="login-progress-fill"></div>
        </div>
      </div>
    </div>
  </div>

  <?php if (! empty($companies)) : ?>
    <div id="companyModal" class="modal fade" tabindex="0" role="dialog">
      <form id="companyForm" action="<?= base_url('login/selectCompany') ?><?= $this->input->get('redirectto') ? '?' . http_build_query(array_filter(['redirectto' => (string) $this->input->get('redirectto'), 'lang' => (string) ($this->input->get('lang') ?? '')])) : '' ?>" method="post">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><?= $e($t('select_company_title', 'Select a company')) ?></h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="<?= $e($t('close', 'Close')) ?>">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <select id="companySelect" name="company_id" class="form-control">
                <option value=""><?= $e($t('select_option', 'Select an option')) ?></option>
                <?php foreach ($companies as $key => $company) :
                  $cid = is_array($company) ? ($company['company_id'] ?? '') : ($company->company_id ?? '');
                  $cname = is_array($company) ? ($company['name'] ?? '') : ($company->name ?? '');
                ?>
                  <option value="<?= (int) $cid ?>" data-company='<?= $e(json_encode($company, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'>
                    <?= $e((string) $cname) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <input type="hidden" id="companyData" name="company_data">
              <input type="hidden" id="userId" name="userId">
              <button type="submit" class="btn btn-primary mt-2"><?= $e($t('select_button', 'Select')) ?></button>
            </div>
          </div>
        </div>
      </form>
    </div>
  <?php endif; ?>
  <?php if (! empty($applications)) : ?>
    <div id="applicationModal" class="modal fade" tabindex="0" role="dialog">
      <form id="applicationForm" action="<?= base_url('login/selectApplication') ?><?= $this->input->get('redirectto') ? '?' . http_build_query(array_filter(['redirectto' => (string) $this->input->get('redirectto'), 'lang' => (string) ($this->input->get('lang') ?? '')])) : '' ?>" method="post">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title"><?= $e($t('select_application_title', 'Select an application')) ?></h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="<?= $e($t('close', 'Close')) ?>">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <select id="applicationSelect" name="application" class="form-control">
                <option value=""><?= $e($t('select_option', 'Select an option')) ?></option>
                <?php foreach ($applications as $app) : ?>
                  <option value="<?= $app; ?>"><?= $app; ?></option>
                <?php endforeach; ?>
              </select>
              <input type="hidden" id="userId" name="userId">
              <button type="submit" class="btn btn-primary mt-2"><?= $e($t('enter_button', 'Enter')) ?></button>
            </div>
          </div>
        </div>
      </form>
    </div>
  <?php endif; ?>

  <script src="<?= base_url('backend/vendors/js/vendor.bundle.base.js') ?>"></script>
  <script src="<?= base_url('backend/js/off-canvas.js') ?>"></script>
  <script src="<?= base_url('backend/js/hoverable-collapse.js') ?>"></script>
  <script src="<?= base_url('backend/js/misc.js') ?>"></script>
  <script src="<?= base_url('backend/js/settings.js') ?>"></script>
  <script src="<?= base_url('backend/js/todolist.js') ?>"></script>
  <script src="<?= base_url('backend/js/centralizedLogin.js') ?>"></script>
  <script>
  (function () {
    'use strict';

    const codeByLangId = { 1: 'en', 2: 'es', 3: 'pt', 4: 'it', 5: 'ru', 6: 'de', 7: 'zh' };
    const langmdlUrl = '<?= $e(rtrim(base_url(), '/')) ?>/api/v2/langmdl/company-login';
    const translationsCache = (window.loginTranslations && typeof window.loginTranslations === 'object') ? window.loginTranslations : {};

    function applyTranslations(langId) {
      const t = translationsCache[langId] || translationsCache[1] || {};
      if (!t) return;
      const setText = function (id, value) {
        if (value === undefined || value === null || value === '') return;
        const el = document.getElementById(id);
        if (el) el.textContent = value;
      };
      const setHtml = function (id, value) {
        if (value === undefined || value === null || value === '') return;
        const el = document.getElementById(id);
        if (el) el.innerHTML = value;
      };
      const setPlaceholder = function (id, value) {
        if (!value) return;
        const el = document.getElementById(id);
        if (el) el.setAttribute('placeholder', value);
      };
      setHtml('login-hero-title', t.hero_title_html);
      setText('login-hero-lead', t.hero_lead);
      setText('login-feat-1', t.feat_products);
      setText('login-feat-2', t.feat_orders);
      setText('login-feat-3', t.feat_customers);
      setText('login-feat-4', t.feat_reports);
      setText('login-feat-5', t.feat_marketplace);
      setText('login-feat-6', t.feat_settings);
      setText('login-panel-label', t.panel_label);
      setText('login-submit-text', t.okbntext);
      setText('login-checktext', t.checktext);
      setText('login-forgottext', t.forgottext);
      setText('login-dontaccount', t.dont_have_account);
      setText('login-signup-link', t.sign_up_link);
      setText('login-entry-customer', t.entry_customer_site);
      setText('login-entry-seller', t.entry_seller_site);
      setText('login-loader-text', t.loading_text);
      setText('login-email-label', t.email_label || t.emailph);
      setText('login-password-label', t.password_label || t.passph);
      setPlaceholder('login-username', t.emailph);
      setPlaceholder('login-password', t.passph);
      if (t.title) {
        document.title = t.title + ' | <?= $e(SITENAME) ?>';
      }
      const html = document.documentElement;
      if (html && codeByLangId[langId]) {
        html.setAttribute('lang', codeByLangId[langId]);
      }
    }

    async function fetchTranslations(langId) {
      if (translationsCache[langId]) return translationsCache[langId];
      const code = codeByLangId[langId] || 'en';
      try {
        const resp = await fetch(langmdlUrl + '?locale=' + encodeURIComponent(code), {
          headers: { 'Accept': 'application/json', 'Accept-Language': code },
          credentials: 'omit',
        });
        if (resp.ok) {
          const data = await resp.json();
          if (data && typeof data === 'object') {
            translationsCache[langId] = data;
            return data;
          }
        }
      } catch (e) { /* keep current */ }
      return null;
    }

    function persistLanguageChoice(langId) {
      const code = codeByLangId[langId] || 'en';
      const url = new URL(window.location.href);
      url.searchParams.set('lang', code);
      window.history.replaceState({}, '', url.toString());
      const form = document.getElementById('login-form');
      if (form) {
        const action = new URL(form.getAttribute('action'), window.location.origin);
        action.searchParams.set('lang', code);
        form.setAttribute('action', action.pathname + action.search);
      }
      const langInput = document.getElementById('login-lang');
      if (langInput) langInput.value = code;
      document.querySelectorAll('.fv-cl-tabs a').forEach(function (a) {
        try {
          const u = new URL(a.getAttribute('href'), window.location.origin);
          u.searchParams.set('lang', code);
          a.setAttribute('href', u.pathname + u.search);
        } catch (err) { /* ignore */ }
      });
    }

    function setupLanguageSelector() {
      const sel = document.getElementById('login-language-selector');
      const root = document.querySelector('[data-cl-lang]');
      const btn = document.getElementById('fvClLangBtn');
      const menu = document.getElementById('fvClLangMenu');
      if (!sel) return;

      sel.addEventListener('change', async function () {
        const langId = parseInt(this.value, 10) || 1;
        window.loginCurrentLangId = langId;
        await fetchTranslations(langId);
        applyTranslations(langId);
        persistLanguageChoice(langId);
      });

      if (btn && menu && root) {
        btn.addEventListener('click', function (e) {
          e.stopPropagation();
          const open = menu.hasAttribute('hidden');
          if (open) {
            menu.removeAttribute('hidden');
            btn.setAttribute('aria-expanded', 'true');
          } else {
            menu.setAttribute('hidden', '');
            btn.setAttribute('aria-expanded', 'false');
          }
        });
        menu.querySelectorAll('.fv-cl-lang__option').forEach(function (opt) {
          opt.addEventListener('click', function (e) {
            e.preventDefault();
            const langId = parseInt(opt.getAttribute('data-lang-id'), 10) || 1;
            const flag = opt.getAttribute('data-flag') || 'us';
            const label = opt.getAttribute('data-label') || '';
            sel.value = String(langId);
            const flagEl = document.getElementById('fvClLangFlag');
            const labelEl = document.getElementById('fvClLangLabel');
            if (flagEl) flagEl.src = 'https://flagcdn.com/w40/' + flag + '.png';
            if (labelEl) labelEl.textContent = label;
            menu.querySelectorAll('.fv-cl-lang__option').forEach(function (o) {
              o.classList.toggle('is-active', o === opt);
            });
            menu.setAttribute('hidden', '');
            btn.setAttribute('aria-expanded', 'false');
            sel.dispatchEvent(new Event('change'));
          });
        });
        document.addEventListener('click', function () {
          menu.setAttribute('hidden', '');
          btn.setAttribute('aria-expanded', 'false');
        });
      }
    }

    function setupPasswordToggle() {
      const btn = document.getElementById('login-toggle-password');
      const input = document.getElementById('login-password');
      if (!btn || !input) return;
      btn.addEventListener('click', function () {
        const isPwd = input.getAttribute('type') === 'password';
        input.setAttribute('type', isPwd ? 'text' : 'password');
        btn.classList.toggle('fa-eye');
        btn.classList.toggle('fa-eye-slash');
      });
    }

    function setupSubmitLoader() {
      const form = document.getElementById('login-form');
      const overlay = document.getElementById('login-loader');
      const submitBtn = document.getElementById('login-submit');
      const loginCard = document.getElementById('login-card');
      const splash = document.getElementById('login-success-splash');
      const errorBanner = document.getElementById('login-error-banner');
      const progressFill = document.getElementById('login-progress-fill');
      if (!form || !submitBtn) return;

      let promoTimer = null;
      let redirectTimer = null;
      let progressTimer = null;

      function sameOriginUrl(url) {
        try {
          const target = new URL(url, window.location.href);
          return window.location.protocol + '//' + window.location.host + target.pathname + target.search + target.hash;
        } catch (ignore) {
          return window.location.pathname + window.location.search;
        }
      }

      function showInlineError(message) {
        if (overlay) {
          overlay.classList.remove('is-active');
          overlay.setAttribute('aria-hidden', 'true');
        }
        if (errorBanner) {
          errorBanner.textContent = message || '';
          errorBanner.classList.toggle('is-visible', !!message);
        }
        submitBtn.removeAttribute('disabled');
      }

      function applySplashTexts(texts) {
        if (!texts || typeof texts !== 'object') return;
        const setText = function (id, key) {
          if (!texts[key]) return;
          const el = document.getElementById(id);
          if (el) el.textContent = texts[key];
        };
        setText('splash-welcome-text', 'splash_welcome');
        setText('splash-loading-text', 'splash_loading');
        setText('splash-preparing-text', 'splash_preparing');
        for (let i = 1; i <= 4; i++) {
          setText('splash-promo-' + i + '-title', 'promo_' + i + '_title');
          setText('splash-promo-' + i + '-text', 'promo_' + i + '_text');
        }
      }

      function startPromoRotation() {
        const slides = Array.prototype.slice.call(document.querySelectorAll('.login-promo-slide'));
        if (!slides.length) return;
        let current = 0;
        promoTimer = window.setInterval(function () {
          slides[current].classList.remove('is-visible');
          current = (current + 1) % slides.length;
          slides[current].classList.add('is-visible');
        }, 2800);
      }

      function startProgressBar(durationMs) {
        if (!progressFill) return;
        const started = Date.now();
        progressFill.style.width = '0%';
        progressTimer = window.setInterval(function () {
          const elapsed = Date.now() - started;
          const pct = Math.min(100, Math.round((elapsed / durationMs) * 100));
          progressFill.style.width = pct + '%';
          if (pct >= 100) window.clearInterval(progressTimer);
        }, 60);
      }

      function playSuccessSplash(payload) {
        applySplashTexts(payload.texts || {});
        if (overlay) {
          overlay.classList.remove('is-active');
          overlay.setAttribute('aria-hidden', 'true');
        }
        if (loginCard) loginCard.classList.add('is-leaving');
        window.setTimeout(function () {
          if (splash) {
            splash.classList.add('is-active');
            splash.setAttribute('aria-hidden', 'false');
          }
          startPromoRotation();
          const splashDuration = 4200;
          startProgressBar(splashDuration);
          redirectTimer = window.setTimeout(function () {
            window.location.href = sameOriginUrl(payload.redirect || '<?= $e(company_url('dashboard')) ?>');
          }, splashDuration);
        }, 520);
      }

      form.addEventListener('submit', async function (e) {
        const usernameEl = document.getElementById('login-username');
        const passwordEl = document.getElementById('login-password');
        const username = usernameEl ? usernameEl.value.trim() : '';
        const password = passwordEl ? passwordEl.value.trim() : '';
        if (username === '' || password === '') return;

        e.preventDefault();
        if (errorBanner) {
          errorBanner.classList.remove('is-visible');
          errorBanner.textContent = '';
        }
        if (overlay) {
          overlay.classList.add('is-active');
          overlay.setAttribute('aria-hidden', 'false');
        }
        submitBtn.setAttribute('disabled', 'disabled');

        try {
          const body = new FormData(form);
          const response = await fetch(sameOriginUrl(form.getAttribute('action') || window.location.href), {
            method: 'POST',
            body: body,
            headers: {
              'X-Requested-With': 'XMLHttpRequest',
              'Accept': 'application/json',
            },
            credentials: 'same-origin',
          });

          let data = null;
          try {
            data = await response.json();
          } catch (parseErr) {
            window.location.reload();
            return;
          }

          if (data && data.success) {
            playSuccessSplash(data);
            return;
          }
          if (data && data.requiresSelection) {
            window.location.reload();
            return;
          }

          const langId = parseInt(window.loginCurrentLangId, 10) || 1;
          const fallbackMsg = (translationsCache[langId] || translationsCache[1] || {}).loading_text || 'Error';
          showInlineError((data && data.message) ? data.message : fallbackMsg);
        } catch (err) {
          showInlineError((translationsCache[parseInt(window.loginCurrentLangId, 10) || 1] || {}).loading_text || 'Error');
        }
      });
    }

    function init() {
      const initialLang = parseInt(window.loginCurrentLangId, 10) || 1;
      applyTranslations(initialLang);
      setupLanguageSelector();
      setupPasswordToggle();
      setupSubmitLoader();
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', init);
    } else {
      init();
    }
  })();
  </script>
</body>
</html>

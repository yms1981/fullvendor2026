<?php
defined('BASEPATH') or exit('No direct script access allowed');

/** @var array<string, mixed> $pageTexts */
/** @var object|null $settings */
/** @var string $authenticate_path */
/** @var string $menu_path */
/** @var int $lang_id */
/** @var string $html_lang */

$t = is_array($pageTexts ?? null) ? $pageTexts : [];
$e = static function (?string $s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
};
$hasCompanyLogo = isset($settings) && is_object($settings) && ! empty($settings->logo);

$errorMap = [
    'seller_login_email_not_found' => (string) ($t['error_email_not_found'] ?? ''),
    'seller_login_invalid_password' => (string) ($t['error_invalid_password'] ?? ''),
    'seller_login_invalid_selection' => (string) ($t['error_invalid_selection'] ?? ''),
    'seller_login_network_error' => (string) ($t['error_network'] ?? ''),
    'seller_login_too_many_attempts' => (string) ($t['error_too_many_attempts'] ?? ''),
    'seller_login_validation_required' => (string) ($t['error_validation_required'] ?? ''),
    'seller_login_validation_email' => (string) ($t['error_validation_email'] ?? ''),
    'seller_login_server_error' => (string) ($t['error_server'] ?? ''),
    'seller_login_not_seller' => (string) ($t['error_not_seller'] ?? ''),
    'seller_login_select_company' => (string) ($t['select_company_help'] ?? ''),
];
?>
<!DOCTYPE html>
<html lang="<?= $e($html_lang ?? 'en') ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title><?= $e($t['page_title'] ?? '') ?></title>
  <?php
    $faviconFile = isset($settings) && is_object($settings) ? trim((string) ($settings->favicon ?? '')) : '';
    $faviconHref = $faviconFile !== '' ? base_url('uploads/logos/' . $faviconFile) : base_url('images/logos/isologo.png');
  ?>
  <link rel="icon" type="image/png" sizes="16x16" href="<?= $e($faviconHref) ?>">
  <link rel="stylesheet" href="<?= $e(base_url('backend/vendors/mdi/css/materialdesignicons.min.css')) ?>">
  <link rel="stylesheet" href="<?= $e(base_url('backend/vendors/css/vendor.bundle.base.css')) ?>">
  <link rel="stylesheet" href="<?= $e(base_url('backend/css/style.css')) ?>">
  <style>
    /* Misma base visual que customer/login: fondo, tarjeta, logo de compañía / isologo */
    html, body.seller-portal-login-body {
      margin: 0;
      color: #212529;
    }
    .seller-portal-login-body .login-card,
    .seller-portal-login-body .login-card label,
    .seller-portal-login-body .login-card .form-label {
      color: #333;
    }
    .seller-portal-login-body .login-card a {
      color: #555;
    }
    .seller-login-wrap {
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #0a0a0a;
      background-image:
        linear-gradient(rgba(0, 0, 0, 0.75), rgba(0, 0, 0, 0.85)),
        url('https://images.unsplash.com/photo-1449824913935-59a10b8d2000?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
      background-size: cover;
      background-position: center;
      padding: 20px;
      position: relative;
      overflow: hidden;
      box-sizing: border-box;
    }
    .seller-portal-login-body .login-card {
      width: 100%;
      max-width: 420px;
      background: rgba(255, 255, 255, 0.98);
      border-radius: 18px;
      border: 1px solid rgba(255, 255, 255, 0.35);
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.45), 0 2px 12px rgba(0, 0, 0, 0.12);
      padding: 40px;
      position: relative;
      z-index: 1;
      box-sizing: border-box;
    }
    .seller-portal-login-body .login-logo {
      display: flex;
      justify-content: center;
      margin-bottom: 24px;
    }
    .seller-portal-login-body .login-logo img {
      max-width: 200px;
      max-height: 120px;
      object-fit: contain;
    }
    .seller-portal-login-body .login-title {
      text-align: center;
      margin-bottom: 24px;
    }
    .seller-portal-login-body .login-title h4 {
      font-size: 22px;
      font-weight: 600;
      color: #333;
      margin: 0;
    }
    .seller-portal-login-body .login-title h5 {
      font-size: 15px;
      color: #666;
      font-weight: 400;
      margin: 8px 0 0;
    }
    .seller-portal-login-body .form-group {
      margin-bottom: 1rem;
    }
    .seller-portal-login-body .login-card label {
      display: block;
      margin-bottom: 6px;
      font-size: 13px;
      font-weight: 600;
      color: #555;
    }
    .login-btn {
      width: 100%;
      padding: 14px;
      font-size: 16px;
      font-weight: 600;
      border-radius: 6px;
      margin-top: 10px;
      background: #dc3545;
      border: none;
      color: white;
    }
    .login-btn:hover { background: #c82333; color: white; }
    .login-btn:disabled { opacity: 0.65; cursor: not-allowed; }
    .portal-alert { display: none; margin-bottom: 16px; }
    .selection-panel { display: none; margin-top: 16px; }
    .selection-panel .help-select { text-align: left; margin: 0 0 10px; font-size: 15px; color: #666; font-weight: 400; }
    .selection-panel label { margin-top: 14px; }
  </style>
</head>
<body class="seller-portal-login-body">
  <div class="seller-login-wrap">
    <div class="login-card">
      <div class="login-logo">
        <a href="<?= $e(base_url()) ?>">
          <?php if ($hasCompanyLogo) { ?>
            <img src="<?= $e(base_url('uploads/logos/' . $settings->logo)) ?>" alt="">
          <?php } else { ?>
            <img src="<?= $e(base_url('images/logos/isologo.png')) ?>" alt="">
          <?php } ?>
        </a>
      </div>
      <div class="login-title">
        <h4><?= $e($t['page_title'] ?? '') ?></h4>
        <h5><?= $e($t['subtitle'] ?? '') ?></h5>
      </div>

      <div id="portalAlert" class="alert alert-danger portal-alert" role="alert"></div>

      <form id="sellerPortalForm" class="login-form" method="post" action="#" autocomplete="on" novalidate>
        <div class="form-group">
          <label class="form-label" for="portalEmail"><?= $e($t['label_email'] ?? '') ?></label>
          <input type="email" class="form-control form-control-lg" id="portalEmail" name="email" required autocomplete="username"
            placeholder="<?= $e($t['email_placeholder'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label" for="portalPassword"><?= $e($t['label_password'] ?? '') ?></label>
          <input type="password" class="form-control form-control-lg" id="portalPassword" name="password" required autocomplete="current-password"
            placeholder="<?= $e($t['password_placeholder'] ?? '') ?>">
        </div>

        <div id="selectionPanel" class="selection-panel">
          <p class="help-select"><?= $e($t['select_company_help'] ?? '') ?></p>
          <div class="form-group">
            <label class="form-label" for="portalCompany"><?= $e($t['label_company'] ?? '') ?></label>
            <select id="portalCompany" class="form-control"></select>
          </div>
          <div class="form-group">
            <label class="form-label" for="portalSeller"><?= $e($t['label_seller_account'] ?? '') ?></label>
            <select id="portalSeller" class="form-control"></select>
          </div>
        </div>

        <button type="submit" class="btn login-btn" id="portalSubmit"><?= $e($t['submit'] ?? '') ?></button>
      </form>
    </div>
  </div>

  <script>
(function () {
  var authenticatePath = <?= json_encode($authenticate_path ?? '/sellers/login') ?>;
  var menuPath = <?= json_encode($menu_path ?? '/sellers/customer') ?>;
  var errorMap = <?= json_encode($errorMap, JSON_UNESCAPED_UNICODE) ?>;
  var selectCode = 'seller_login_select_company';

  function sameOriginPath(urlOrPath) {
    if (!urlOrPath) return '';
    var s = String(urlOrPath).trim();
    if (s.charAt(0) === '/') return s;
    try {
      var u = new URL(s, window.location.origin);
      if (u.origin === window.location.origin) {
        return u.pathname + u.search + u.hash;
      }
    } catch (e) { /* ignore */ }
    return s;
  }

  var form = document.getElementById('sellerPortalForm');
  var alertEl = document.getElementById('portalAlert');
  var emailEl = document.getElementById('portalEmail');
  var passEl = document.getElementById('portalPassword');
  var selPanel = document.getElementById('selectionPanel');
  var companySel = document.getElementById('portalCompany');
  var sellerSel = document.getElementById('portalSeller');
  var submitBtn = document.getElementById('portalSubmit');
  var sellersPayload = [];

  function showError(msg) {
    if (!msg) {
      alertEl.style.display = 'none';
      alertEl.textContent = '';
      return;
    }
    alertEl.textContent = msg;
    alertEl.style.display = 'block';
  }

  function msgFor(code) {
    if (!code) return '';
    var k = String(code);
    if (errorMap[k]) return errorMap[k];
    return k;
  }

  form.addEventListener('submit', function (ev) {
    ev.preventDefault();
    showError('');
    var email = (emailEl.value || '').trim();
    var password = passEl.value || '';
    var payload = { email: email, password: password };

    if (selPanel.style.display === 'block' && companySel.value && sellerSel.value) {
      payload.company_id = parseInt(companySel.value, 10);
      payload.seller_id = parseInt(sellerSel.value, 10);
    }

    submitBtn.disabled = true;

    fetch(authenticatePath, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(payload),
      credentials: 'same-origin'
    }).then(function (r) { return r.json().then(function (j) { return { status: r.status, body: j }; }); })
    .then(function (res) {
      submitBtn.disabled = false;
      var b = res.body || {};
      if (b.success && b.authenticated && b.redirect) {
        window.location.href = sameOriginPath(b.redirect);
        return;
      }
      if (b.code === 'seller_login_select_company' && b.companies && b.sellers) {
        selPanel.style.display = 'block';
        companySel.innerHTML = '';
        b.companies.forEach(function (c) {
          var o = document.createElement('option');
          o.value = String(c.company_id);
          o.textContent = c.company_name || ('#' + c.company_id);
          companySel.appendChild(o);
        });
        sellersPayload = b.sellers;
        filterSellers();
        showError(msgFor(b.code));
        return;
      }
      showError(msgFor(b.code) || (b.message ? String(b.message) : ''));
    }).catch(function () {
      submitBtn.disabled = false;
      showError(errorMap['seller_login_network_error'] || '');
    });
  });

  function filterSellers() {
    var cid = parseInt(companySel.value, 10);
    sellerSel.innerHTML = '';
    sellersPayload.filter(function (s) { return s.company_id === cid; }).forEach(function (s) {
      var o = document.createElement('option');
      o.value = String(s.seller_id);
      o.textContent = s.label || ('#' + s.seller_id);
      sellerSel.appendChild(o);
    });
  }
  if (companySel) companySel.addEventListener('change', filterSellers);
})();
  </script>
</body>
</html>

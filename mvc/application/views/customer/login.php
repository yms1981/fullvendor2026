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

$errorMap = [
    'customer_login_email_not_found' => (string) ($t['error_email_not_found'] ?? ''),
    'customer_login_invalid_password' => (string) ($t['error_invalid_password'] ?? ''),
    'customer_login_invalid_selection' => (string) ($t['error_invalid_selection'] ?? ''),
    'customer_login_no_sellers_assigned' => (string) ($t['error_no_sellers_assigned'] ?? ''),
    'customer_login_network_error' => (string) ($t['error_network'] ?? ''),
    'customer_login_too_many_attempts' => (string) ($t['error_too_many_attempts'] ?? ''),
    'customer_login_method_not_allowed' => (string) ($t['error_method_not_allowed'] ?? ''),
    'customer_login_validation_required' => (string) ($t['error_validation_required'] ?? ''),
    'customer_login_validation_email' => (string) ($t['error_validation_email'] ?? ''),
    'customer_login_server_error' => (string) ($t['error_server'] ?? ''),
    'customer_login_not_customer' => (string) ($t['error_not_customer'] ?? ''),
    'customer_login_user_not_allowed' => (string) ($t['error_user_not_allowed'] ?? ''),
];
?>
<!DOCTYPE html>
<html lang="<?= $e($html_lang ?? 'en') ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title><?= $e($t['page_title'] ?? '') ?></title>
  <link rel="icon" type="image/png" sizes="16x16" href="<?= $e(base_url('uploads/logos/' . (@$settings->favicon ?? ''))) ?>">
  <link rel="stylesheet" href="<?= $e(base_url('backend/vendors/mdi/css/materialdesignicons.min.css')) ?>">
  <link rel="stylesheet" href="<?= $e(base_url('backend/vendors/css/vendor.bundle.base.css')) ?>">
  <link rel="stylesheet" href="<?= $e(base_url('backend/css/style.css')) ?>">
  <style>
    /* Texto visible aunque style.css del backend afecte body/enlaces */
    html, body.customer-portal-login-body {
      margin: 0;
      color: #212529;
    }
    .customer-portal-login-body .login-card,
    .customer-portal-login-body .login-card label,
    .customer-portal-login-body .login-card .form-label {
      color: #333;
    }
    .customer-portal-login-body .login-card a {
      color: #555;
    }
    .login-container {
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
    }
    .login-card {
      width: 100%;
      max-width: 420px;
      background: rgba(255, 255, 255, 0.98);
      border-radius: 18px;
      border: 1px solid rgba(255, 255, 255, 0.35);
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.45), 0 2px 12px rgba(0, 0, 0, 0.12);
      padding: 40px;
      position: relative;
      z-index: 1;
    }
    .login-logo { display: flex; justify-content: center; margin-bottom: 24px; }
    .login-logo img { max-width: 200px; max-height: 120px; object-fit: contain; }
    .login-title { text-align: center; margin-bottom: 24px; }
    .login-title h4 { font-size: 22px; font-weight: 600; color: #333; }
    .login-title h5 { font-size: 15px; color: #666; font-weight: 400; margin-top: 8px; }
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
    .form-label {
      display: block;
      margin-bottom: 6px;
      font-size: 13px;
      font-weight: 600;
      color: #555;
    }
  </style>
</head>
<body class="customer-portal-login-body">
  <div class="login-container">
    <div class="login-card">
      <div class="login-logo">
        <a href="<?= $e(base_url()) ?>">
          <?php if (! empty($settings->logo)) { ?>
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

      <form id="customerPortalForm" class="login-form" novalidate>
        <div class="form-group">
          <label class="form-label" for="portalEmail"><?= $e($t['label_email'] ?? '') ?></label>
          <input type="email" id="portalEmail" class="form-control form-control-lg" required
            autocomplete="email"
            placeholder="<?= $e($t['email_placeholder'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label" for="portalPassword"><?= $e($t['label_password'] ?? '') ?></label>
          <input type="password" id="portalPassword" class="form-control form-control-lg" required
            autocomplete="current-password"
            placeholder="<?= $e($t['password_placeholder'] ?? '') ?>">
        </div>

        <div id="selectionPanel" class="selection-panel">
          <p class="text-muted small"><?= $e($t['select_company_help'] ?? '') ?></p>
          <div class="form-group">
            <label for="portalCompany"><?= $e($t['label_company'] ?? '') ?></label>
            <select id="portalCompany" class="form-control"></select>
          </div>
          <div class="form-group">
            <label for="portalCustomer"><?= $e($t['label_account'] ?? '') ?></label>
            <select id="portalCustomer" class="form-control"></select>
          </div>
        </div>

        <button type="submit" class="btn login-btn" id="portalSubmit"><?= $e($t['submit'] ?? '') ?></button>
      </form>
    </div>
  </div>

  <script>
(function () {
  var authenticatePath = <?= json_encode($authenticate_path ?? '/customer/login') ?>;
  var menuPath = <?= json_encode($menu_path ?? '/customer/catalog') ?>;
  var errorMap = <?= json_encode($errorMap, JSON_UNESCAPED_UNICODE) ?>;
  var selectCode = 'customer_login_select_company';

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

  var form = document.getElementById('customerPortalForm');
  var alertEl = document.getElementById('portalAlert');
  var emailEl = document.getElementById('portalEmail');
  var passEl = document.getElementById('portalPassword');
  var selPanel = document.getElementById('selectionPanel');
  var companySel = document.getElementById('portalCompany');
  var customerSel = document.getElementById('portalCustomer');
  var submitBtn = document.getElementById('portalSubmit');
  var customersPayload = [];

  function showError(msg) {
    if (!msg) {
      alertEl.style.display = 'none';
      alertEl.textContent = '';
      return;
    }
    alertEl.textContent = msg;
    alertEl.style.display = 'block';
  }

  function msgForCode(code) {
    if (code && errorMap[code]) return errorMap[code];
    return errorMap['customer_login_network_error'] || '';
  }

  function fillCompanies(companies) {
    companySel.innerHTML = '';
    companies.forEach(function (c) {
      var opt = document.createElement('option');
      opt.value = String(c.company_id);
      opt.textContent = c.company_name || ('#' + c.company_id);
      companySel.appendChild(opt);
    });
  }

  function filterCustomersForCompany(companyId) {
    customerSel.innerHTML = '';
    var list = customersPayload.filter(function (x) {
      return String(x.company_id) === String(companyId);
    });
    list.forEach(function (c) {
      var opt = document.createElement('option');
      opt.value = String(c.customer_id);
      var label = (c.name || '') + (c.business_name ? ' — ' + c.business_name : '');
      opt.textContent = label || ('#' + c.customer_id);
      customerSel.appendChild(opt);
    });
  }

  companySel.addEventListener('change', function () {
    filterCustomersForCompany(companySel.value);
  });

  form.addEventListener('submit', function (ev) {
    ev.preventDefault();
    showError('');
    submitBtn.disabled = true;

    var body = {
      email: emailEl.value.trim(),
      password: passEl.value
    };

    if (selPanel.style.display === 'block') {
      body.company_id = parseInt(companySel.value, 10) || null;
      body.customer_id = parseInt(customerSel.value, 10) || null;
    }

    fetch(authenticatePath, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(body),
      credentials: 'same-origin'
    }).then(function (r) {
      return r.text().then(function (text) {
        var j = null;
        if (text) {
          try {
            j = JSON.parse(text);
          } catch (e) {
            j = null;
          }
        }
        return { ok: r.ok, status: r.status, json: j };
      });
    }).then(function (res) {
      var j = res.json || {};
      var authOk = j.success === true || j.success === 'true' || j.authenticated === true || j.authenticated === 'true';
      var dest = sameOriginPath(j.redirect && String(j.redirect).trim()) || (authOk ? menuPath : '');

      function logAuth(ok, detail) {
        if (typeof console === 'undefined' || !console.log) {
          return;
        }
        var msg = ok
          ? '[Customer portal] Autenticación: correcta.'
          : '[Customer portal] Autenticación: incorrecta.';
        if (detail !== undefined && detail !== null) {
          console.log(msg, detail);
        } else {
          console.log(msg);
        }
      }

      if (authOk && dest) {
        logAuth(true, { redirect: dest });
        window.location.replace(dest);
        return;
      }

      submitBtn.disabled = false;

      if (j.code === selectCode && j.companies && j.customers) {
        if (typeof console !== 'undefined' && console.log) {
          console.log('[Customer portal] Autenticación: pendiente (elegir empresa y cuenta).', { code: j.code });
        }
        customersPayload = j.customers;
        fillCompanies(j.companies);
        if (j.companies.length) {
          filterCustomersForCompany(j.companies[0].company_id);
        }
        selPanel.style.display = 'block';
        showError('');
        return;
      }

      logAuth(false, { httpStatus: res.status, code: j.code, message: j.message });
      showError(msgForCode(j.code) || (j.message && String(j.message)) || msgForCode('customer_login_network_error'));
    }).catch(function (err) {
      submitBtn.disabled = false;
      if (typeof console !== 'undefined' && console.log) {
        console.log('[Customer portal] Autenticación: incorrecta.', { reason: 'network_or_parse', error: err });
      }
      showError(msgForCode('customer_login_network_error'));
    });
  });
})();
  </script>
</body>
</html>

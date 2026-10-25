<?php
defined('BASEPATH') OR exit('No direct script access allowed');
if (! isset($adminLoginLang) || ! is_array($adminLoginLang)) {
	$adminLoginLang = [];
}
$t = static function (array $lang, string $key): string {
	$v = $lang[$key] ?? '';

	return is_string($v) ? $v : '';
};
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(function_exists('admin_shell_html_lang') ? admin_shell_html_lang() : 'en', ENT_QUOTES, 'UTF-8') ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title><?php
    $sn = defined('SITENAME') ? (string) SITENAME : '';
    $titlePart = trim($t($adminLoginLang, 'login_page_title'));
    $pt = $titlePart !== '' ? ($titlePart . ($sn !== '' ? ' | ' . $sn : '')) : ('Login' . ($sn !== '' ? ' | ' . $sn : ''));
    echo htmlspecialchars($pt, ENT_QUOTES, 'UTF-8');
  ?></title>
  <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('uploads/logos/isologo.png') ?>">
  <link rel="stylesheet" href="<?= base_url('backend/vendors/mdi/css/materialdesignicons.min.css')?>">
  <style>
    /* Login admin — fondo claro, sin scroll de página, bordes suaves */
    *, *::before, *::after { box-sizing: border-box; }

    html {
      height: 100%;
      overflow: hidden;
    }
    body {
      margin: 0;
      height: 100%;
      overflow: hidden;
    }

    #admin-login-root {
      height: 100%;
      max-height: 100%;
      margin: 0;
      position: relative;
      overflow-x: hidden;
      overflow-y: auto;
      -webkit-overflow-scrolling: touch;
      font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
      font-size: 16px;
      line-height: 1.5;
      color: #1a1a1a;
      background: linear-gradient(165deg, #f5f5f7 0%, #ececf0 45%, #f0eeef 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: max(0.75rem, 2vmin);
    }

    .admin-login-canvas {
      position: absolute;
      inset: 0;
      pointer-events: none;
      z-index: 0;
      overflow: hidden;
    }
    .admin-login-canvas__plane {
      position: absolute;
      background: #dc2626;
      border-radius: 24px;
    }
    .admin-login-canvas__plane--1 {
      width: min(36vw, 380px);
      height: 95%;
      max-height: 100%;
      left: -10%;
      top: 2%;
      opacity: 0.08;
      transform: skewX(-9deg);
    }
    .admin-login-canvas__plane--2 {
      width: 50%;
      height: 38%;
      max-height: 45vh;
      right: -12%;
      bottom: 5%;
      opacity: 0.07;
      transform: skewX(6deg);
      background: #b91c1c;
      border-radius: 20px;
    }
    .admin-login-canvas__plane--3 {
      width: 55%;
      height: 24%;
      max-height: 30vh;
      right: -3%;
      top: 6%;
      opacity: 0.06;
      transform: skewX(-3deg);
      background: #ef4444;
      border-radius: 18px;
    }
    .admin-login-canvas__line {
      position: absolute;
      background: rgba(220, 38, 38, 0.28);
      border-radius: 1px;
    }
    .admin-login-canvas__line--1 {
      width: 1px;
      height: 78%;
      max-height: 90vh;
      left: 8%;
      top: 10%;
    }
    .admin-login-canvas__line--2 {
      width: 1px;
      height: 55%;
      max-height: 70vh;
      right: 14%;
      bottom: 12%;
    }
    .admin-login-canvas__line--3 {
      height: 1px;
      width: min(55%, 520px);
      left: 10%;
      top: 24%;
      transform: rotate(-2.5deg);
    }
    .admin-login-canvas__line--4 {
      height: 1px;
      width: min(38%, 360px);
      right: 10%;
      bottom: 26%;
      transform: rotate(1.5deg);
    }
    .admin-login-canvas__frame {
      position: absolute;
      inset: max(0.75rem, 2vmin);
      border: 1px solid rgba(220, 38, 38, 0.12);
      border-radius: 16px;
    }
    .admin-login-canvas__frame::before {
      content: "";
      position: absolute;
      inset: 4px;
      border: 1px solid rgba(0, 0, 0, 0.06);
      border-radius: 14px;
    }

    #admin-login-root .admin-login-card {
      position: relative;
      z-index: 1;
      width: 100%;
      max-width: 432px;
      flex-shrink: 0;
      background: #ffffff;
      border-radius: 16px;
      border: 1px solid rgba(0, 0, 0, 0.08);
      box-shadow:
        0 4px 24px rgba(0, 0, 0, 0.06),
        0 0 0 1px rgba(220, 38, 38, 0.12);
    }
    #admin-login-root .admin-login-card__accent {
      height: 3px;
      border-radius: 16px 16px 0 0;
      background: linear-gradient(90deg, rgba(26, 26, 26, 0.35) 0%, rgba(26, 26, 26, 0.35) 10%, #dc2626 10%, #dc2626 90%, rgba(26, 26, 26, 0.35) 90%, rgba(26, 26, 26, 0.35) 100%);
    }
    #admin-login-root .admin-login-card__inner {
      padding: 1.75rem 1.75rem 1.9rem;
    }

    #admin-login-root .admin-login-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.4rem 1rem;
      border-radius: 999px;
      font-size: 0.68rem;
      font-weight: 700;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: #ffffff;
      background: #262626;
      border: 1px solid rgba(0, 0, 0, 0.06);
      box-shadow: inset 0 -2px 0 rgba(220, 38, 38, 0.85);
    }
    #admin-login-root .admin-login-badge-icon.mdi::before {
      font: normal normal normal 1.1rem/1 "Material Design Icons" !important;
      color: #fca5a5 !important;
    }

    #admin-login-root .admin-login-brand-panel {
      background: #fafafa;
      border: 1px solid rgba(0, 0, 0, 0.08);
      border-radius: 12px;
      padding: 1rem 1.1rem 1.15rem;
      margin: 1.1rem 0 1.15rem;
      position: relative;
    }
    #admin-login-root .admin-login-brand-panel::after {
      content: "";
      position: absolute;
      left: 12px;
      right: 12px;
      bottom: 8px;
      height: 2px;
      border-radius: 2px;
      background: linear-gradient(90deg, transparent, rgba(220, 38, 38, 0.45) 35%, rgba(220, 38, 38, 0.45) 65%, transparent);
      opacity: 0.7;
    }
    #admin-login-root .admin-login-brand { text-align: center; margin-bottom: 0.6rem; }
    #admin-login-root .admin-login-brand img { max-width: 180px; height: auto; vertical-align: middle; }
    #admin-login-root .admin-login-brand-secondary { text-align: center; }
    #admin-login-root .admin-login-brand-secondary img { max-width: 200px; width: 100%; height: auto; vertical-align: middle; }

    #admin-login-root .admin-login-headline { text-align: center; margin-bottom: 1.35rem; }
    #admin-login-root .admin-login-headline h1 {
      margin: 0;
      font-size: 1.5rem;
      font-weight: 700;
      color: #1a1a1a;
      letter-spacing: -0.03em;
    }
    #admin-login-root .admin-login-headline p {
      margin: 0.5rem 0 0;
      font-size: 0.9rem;
      color: #404040;
      font-weight: 400;
    }

    #admin-login-root .admin-login-field { margin-bottom: 1.05rem; }
    #admin-login-root .admin-login-field-label {
      display: block;
      margin-bottom: 0.4rem;
      font-size: 0.75rem;
      font-weight: 700;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      color: #262626;
    }
    #admin-login-root .admin-login-input {
      display: block;
      width: 100%;
      padding: 0.78rem 1rem;
      font-size: 1rem;
      line-height: 1.4;
      color: #1a1a1a !important;
      -webkit-text-fill-color: #1a1a1a !important;
      background: #ffffff !important;
      border: 1px solid rgba(0, 0, 0, 0.12) !important;
      border-radius: 10px;
      outline: none;
      transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    #admin-login-root .admin-login-input:focus {
      border-color: rgba(220, 38, 38, 0.55) !important;
      box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.15);
      background: #fff !important;
    }
    #admin-login-root .admin-login-input::placeholder {
      color: #737373 !important;
      opacity: 1 !important;
    }
    #admin-login-root .admin-login-input::-webkit-input-placeholder {
      color: #737373 !important;
      opacity: 1 !important;
    }
    #admin-login-root .admin-login-input::-moz-placeholder {
      color: #737373 !important;
      opacity: 1 !important;
    }

    #admin-login-root .admin-login-submit {
      display: block;
      width: 100%;
      margin-top: 0.35rem;
      padding: 0.9rem 1rem;
      font-size: 0.95rem;
      font-weight: 700;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: #ffffff !important;
      -webkit-text-fill-color: #ffffff !important;
      background: #dc2626 !important;
      border: 1px solid rgba(0, 0, 0, 0.08);
      border-radius: 10px;
      cursor: pointer;
      transition: background 0.2s ease, box-shadow 0.15s ease, transform 0.15s ease;
    }
    #admin-login-root .admin-login-submit:hover {
      background: #1f1f1f !important;
      box-shadow: inset 0 -3px 0 rgba(220, 38, 38, 0.9);
    }
    #admin-login-root .admin-login-submit:active {
      transform: translateY(1px);
    }

    #admin-login-root .admin-login-meta {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      align-items: center;
      gap: 0.85rem;
      margin-top: 1.25rem;
      padding-top: 1.15rem;
      border-top: 1px solid rgba(10, 10, 10, 0.12);
      font-size: 0.86rem;
      color: #262626;
    }
    #admin-login-root .admin-login-meta-check {
      display: flex;
      align-items: flex-start;
      gap: 0.5rem;
    }
    #admin-login-root .admin-login-meta-check input {
      width: 1.05rem;
      height: 1.05rem;
      margin-top: 0.12rem;
      accent-color: #dc2626;
      flex-shrink: 0;
    }
    #admin-login-root .admin-login-meta-check label {
      color: #262626 !important;
      -webkit-text-fill-color: #262626 !important;
      font-weight: 500;
      cursor: pointer;
      line-height: 1.35;
    }
    #admin-login-root .admin-login-meta a {
      color: #1a1a1a;
      font-weight: 700;
      text-decoration: none;
      border-bottom: 1px solid rgba(220, 38, 38, 0.45);
      padding-bottom: 1px;
    }
    #admin-login-root .admin-login-meta a:hover {
      color: #dc2626;
      border-bottom-color: rgba(26, 26, 26, 0.35);
    }

    #admin-login-root .admin-login-alert-wrap .alert {
      margin-bottom: 1rem;
      padding: 0.7rem 0.9rem;
      border-radius: 10px;
      font-size: 0.88rem;
      border: 1px solid rgba(0, 0, 0, 0.1);
    }
    #admin-login-root .admin-login-alert-wrap .alert-danger {
      background: #fff5f5;
      color: #7f1d1d;
      border-color: rgba(220, 38, 38, 0.45);
    }
    #admin-login-root .admin-login-alert-wrap .alert-success {
      background: #fafafa;
      color: #1a1a1a;
      border-color: rgba(0, 0, 0, 0.12);
    }
    #admin-login-root .admin-login-alert-wrap .close {
      float: right;
      font-weight: 700;
      opacity: 0.65;
      text-decoration: none;
      color: inherit;
    }

    @media (max-width: 480px) {
      #admin-login-root .admin-login-card__inner { padding: 1.35rem 1.2rem 1.45rem; }
      .admin-login-canvas__frame { inset: max(0.5rem, 1.5vmin); }
    }
  </style>
</head>
<body>
  <div id="admin-login-root">
    <div class="admin-login-canvas" aria-hidden="true">
      <div class="admin-login-canvas__plane admin-login-canvas__plane--1"></div>
      <div class="admin-login-canvas__plane admin-login-canvas__plane--2"></div>
      <div class="admin-login-canvas__plane admin-login-canvas__plane--3"></div>
      <div class="admin-login-canvas__line admin-login-canvas__line--1"></div>
      <div class="admin-login-canvas__line admin-login-canvas__line--2"></div>
      <div class="admin-login-canvas__line admin-login-canvas__line--3"></div>
      <div class="admin-login-canvas__line admin-login-canvas__line--4"></div>
      <div class="admin-login-canvas__frame"></div>
    </div>
    <div class="admin-login-card">
      <div class="admin-login-card__accent"></div>
      <div class="admin-login-card__inner">
        <div style="text-align:center;margin-bottom:0.5rem;">
          <span class="admin-login-badge">
            <i class="mdi mdi-shield-lock-outline admin-login-badge-icon" aria-hidden="true"></i>
            <span><?= htmlspecialchars($t($adminLoginLang, 'login_badge_admin'), ENT_QUOTES, 'UTF-8') ?></span>
          </span>
        </div>
        <div class="admin-login-brand-panel">
          <div class="admin-login-brand">
            <a href="<?= base_url() ?>">
              <img src="<?= base_url('uploads/logos/fullvendor-login-brand.png') ?>" alt="FullVendor">
            </a>
          </div>
        </div>
        <div class="admin-login-headline">
          <h1><?= htmlspecialchars($t($adminLoginLang, 'login_welcome_title'), ENT_QUOTES, 'UTF-8') ?></h1>
          <p><?= htmlspecialchars($t($adminLoginLang, 'login_welcome_subtitle'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <?php
        $adminLoginRedirectto = isset($admin_login_redirectto) ? (string) $admin_login_redirectto : '';
        $adminLoginLangCode = isset($admin_login_lang) ? (string) $admin_login_lang : 'en';
        $adminLoginFormAction = admin_url('login');
        if ($adminLoginRedirectto !== '') {
            $adminLoginFormAction .= '?redirectto=' . rawurlencode($adminLoginRedirectto);
        }
        ?>
        <form action="<?= htmlspecialchars($adminLoginFormAction, ENT_QUOTES, 'UTF-8') ?>" method="post" autocomplete="on">
          <input type="hidden" name="lang" value="<?= htmlspecialchars($adminLoginLangCode, ENT_QUOTES, 'UTF-8') ?>">
          <?php if ($adminLoginRedirectto !== ''): ?>
            <input type="hidden" name="redirectto" value="<?= htmlspecialchars($adminLoginRedirectto, ENT_QUOTES, 'UTF-8') ?>">
          <?php endif; ?>
          <?php if (! empty($msg)): ?>
            <div class="admin-login-alert-wrap"><?= $msg ?></div>
          <?php endif ?>

          <div class="admin-login-field">
            <label class="admin-login-field-label" for="admin-login-username"><?= htmlspecialchars($t($adminLoginLang, 'label_username'), ENT_QUOTES, 'UTF-8') ?></label>
            <input id="admin-login-username" class="admin-login-input" type="text" name="username" required autocomplete="username"
              placeholder="<?= htmlspecialchars($t($adminLoginLang, 'label_username'), ENT_QUOTES, 'UTF-8') ?>"
              title="<?= htmlspecialchars($t($adminLoginLang, 'label_username'), ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <div class="admin-login-field">
            <label class="admin-login-field-label" for="admin-login-password"><?= htmlspecialchars($t($adminLoginLang, 'label_password'), ENT_QUOTES, 'UTF-8') ?></label>
            <input id="admin-login-password" class="admin-login-input" type="password" name="password" required autocomplete="current-password"
              placeholder="<?= htmlspecialchars($t($adminLoginLang, 'label_password'), ENT_QUOTES, 'UTF-8') ?>"
              title="<?= htmlspecialchars($t($adminLoginLang, 'label_password'), ENT_QUOTES, 'UTF-8') ?>">
          </div>
          <button type="submit" class="admin-login-submit"><?= htmlspecialchars($t($adminLoginLang, 'login_sign_in'), ENT_QUOTES, 'UTF-8') ?></button>

          <div class="admin-login-meta">
            <div class="admin-login-meta-check">
              <input type="checkbox" id="admin-login-remember" name="remember" value="1">
              <label for="admin-login-remember"><?= htmlspecialchars($t($adminLoginLang, 'login_keep_signed_in'), ENT_QUOTES, 'UTF-8') ?></label>
            </div>
            <a href="<?= admin_url('forgetpassword') ?>"><?= htmlspecialchars($t($adminLoginLang, 'login_forgot_password'), ENT_QUOTES, 'UTF-8') ?></a>
          </div>
        </form>
      </div>
    </div>
  </div>
</body>
</html>

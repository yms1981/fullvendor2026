<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (! isset($forgetPasswordLang) || ! is_array($forgetPasswordLang)) {
    $forgetPasswordLang = [];
}
$t = static function (string $key, string $fallback = '') use ($forgetPasswordLang): string {
    $v = trim((string) ($forgetPasswordLang[$key] ?? ''));

    return $v !== '' ? $v : $fallback;
};
$e = static function (?string $s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
};

$pageTitle = $t('page_title_forget', 'Forgot password');
$siteName = defined('SITENAME') ? (string) SITENAME : '';
$formAction = base_url('forgetpassword');
if ($this->input->get('redirectto')) {
    $formAction .= '?redirectto=' . rawurlencode((string) $this->input->get('redirectto'));
}
$loginUrl = base_url('login');
if ($this->input->get('redirectto')) {
    $loginUrl .= '?redirectto=' . rawurlencode((string) $this->input->get('redirectto'));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title><?= $e($pageTitle . ($siteName !== '' ? ' | ' . $siteName : '')) ?></title>
  <link rel="icon" type="image/png" sizes="16x16" href="<?= base_url('uploads/logos/' . @$settings->favicon) ?>">
  <link rel="stylesheet" href="<?= base_url('backend/vendors/mdi/css/materialdesignicons.min.css') ?>">
  <link rel="stylesheet" href="<?= base_url('backend/vendors/css/vendor.bundle.base.css') ?>">
  <link rel="stylesheet" href="<?= base_url('backend/css/style.css') ?>">
  <link rel="shortcut icon" href="<?= base_url('uploads/logos/' . @$settings->favicon) ?>" />
  <style>
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
      background-repeat: no-repeat;
      background-attachment: fixed;
      padding: 20px;
      position: relative;
      overflow: hidden;
    }
    .login-container::before {
      content: '';
      position: absolute;
      inset: 0;
      background-image: url('https://images.unsplash.com/photo-1449824913935-59a10b8d2000?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80');
      background-size: cover;
      background-position: center;
      filter: blur(8px) brightness(0.4);
      transform: scale(1.1);
      z-index: 0;
    }
    .login-container::after {
      content: '';
      position: absolute;
      inset: 0;
      background:
        linear-gradient(135deg, rgba(0, 0, 0, 0.8) 0%, rgba(26, 0, 0, 0.9) 100%),
        radial-gradient(circle at 30% 40%, rgba(220, 53, 69, 0.2) 0%, transparent 60%);
      z-index: 0;
      pointer-events: none;
    }
    .login-card {
      width: 100%;
      max-width: 420px;
      background: rgba(255, 255, 255, 0.98);
      border-radius: 12px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(220, 53, 69, 0.1);
      padding: 40px;
      position: relative;
      z-index: 1;
      backdrop-filter: blur(10px);
    }
    .login-logo {
      display: flex;
      justify-content: center;
      align-items: center;
      margin-bottom: 18px;
    }
    .login-logo img {
      max-width: 200px;
      max-height: 120px;
      object-fit: contain;
    }
    .login-welcome {
      text-align: center;
      margin-bottom: 24px;
    }
    .login-welcome h4 {
      font-size: 22px;
      font-weight: 600;
      color: #333;
      margin: 0 0 6px;
    }
    .login-welcome p {
      font-size: 14px;
      color: #666;
      margin: 0;
    }
    .login-form .form-group { margin-bottom: 16px; }
    .login-form .form-control-lg {
      padding: 12px 16px;
      font-size: 16px;
      border-radius: 6px;
      border: 1px solid #ddd;
      transition: all 0.3s ease;
      width: 100%;
    }
    .login-form .form-control-lg:focus {
      border-color: #dc3545;
      box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
      outline: none;
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
      color: #fff;
      transition: background-color 0.2s ease, transform 0.2s ease;
      cursor: pointer;
    }
    .login-btn:hover {
      background: #c82333;
      transform: translateY(-1px);
      box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
      color: #fff;
    }
    .login-options {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 16px;
      flex-wrap: wrap;
      gap: 10px;
    }
    .auth-link { color: #333; text-decoration: none; transition: color 0.2s ease; }
    .auth-link:hover { color: #dc3545; text-decoration: none; }
    .login-alert {
      margin-bottom: 16px;
      padding: 12px 14px;
      border-radius: 8px;
      font-size: 14px;
    }
    .login-alert--danger {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }
    .login-alert--success {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }
    @media (max-width: 576px) {
      .login-card { padding: 30px 20px; }
      .login-options { flex-direction: column; align-items: flex-start; }
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="login-card">
      <div class="login-logo">
        <a href="<?= base_url('login') ?>">
          <?php if (@$settings->logo) { ?>
            <img src="<?= base_url('uploads/logos/' . @$settings->logo) ?>" alt="<?= $e($siteName) ?>">
          <?php } else { ?>
            <img src="<?= base_url('images/logos/isologo.png') ?>" alt="<?= $e($siteName) ?>" style="max-width: 250px; height: auto;">
          <?php } ?>
        </a>
      </div>
      <div class="login-welcome">
        <h4><?= $e($t('welcome_title', "Hello! Let's get started")) ?></h4>
        <p><?= $e($t('welcome_subtitle_email', 'Enter your email to continue.')) ?></p>
      </div>
      <form class="login-form" action="<?= $e($formAction) ?>" method="post" autocomplete="off">
        <?php if (! empty($msg)): ?>
          <div class="login-alert <?= (is_string($msg) && strpos($msg, 'alert-success') !== false) ? 'login-alert--success' : 'login-alert--danger' ?>">
            <?= strip_tags((string) $msg, '<strong><br><a>') ?>
          </div>
        <?php endif; ?>
        <div class="form-group">
          <input type="email"
                 name="username"
                 class="form-control form-control-lg"
                 placeholder="<?= $e($t('placeholder_email', 'Email')) ?>"
                 autocomplete="username"
                 required>
        </div>
        <button type="submit" class="btn login-btn"><?= $e($t('btn_submit', 'Submit')) ?></button>
        <div class="login-options">
          <span class="text-muted" style="font-size: 14px;"><?= $e($t('label_already_have_account', 'If you already have an account')) ?></span>
          <a href="<?= $e($loginUrl) ?>" class="auth-link"><?= $e($t('link_login', 'Sign in')) ?></a>
        </div>
      </form>
    </div>
  </div>
</body>
</html>

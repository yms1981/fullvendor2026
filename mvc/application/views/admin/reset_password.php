<?php
defined('BASEPATH') OR exit('No direct script access allowed');
if (! isset($forgetPasswordLang) || ! is_array($forgetPasswordLang)) {
	$forgetPasswordLang = [];
}
$t = static function (array $lang, string $key, string $fallback = ''): string {
	$v = trim((string) ($lang[$key] ?? ''));

	return $v !== '' ? $v : $fallback;
};
$adminHtmlLang = function_exists('admin_shell_html_lang') ? admin_shell_html_lang() : 'en';
$pageTitle = $t($forgetPasswordLang, 'page_title_reset', 'Reset password');
$siteName = defined('SITENAME') ? (string) SITENAME : '';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($adminHtmlLang, ENT_QUOTES, 'UTF-8') ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title><?= htmlspecialchars($pageTitle . ($siteName !== '' ? ' | ' . $siteName : ''), ENT_QUOTES, 'UTF-8') ?></title>
  <link rel="stylesheet" href="<?= base_url('companyasset/vendors/mdi/css/materialdesignicons.min.css')?>">
  <link rel="stylesheet" href="<?= base_url('companyasset/vendors/flag-icon-css/css/flag-icon.min.css')?>">
  <link rel="stylesheet" href="<?= base_url('companyasset/vendors/css/vendor.bundle.base.css')?>">
  <link rel="stylesheet" href="<?= base_url('companyasset/css/style.css')?>">
  <link rel="shortcut icon" href="<?= base_url('uploads/logos/isologo.png') ?>" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@9.17.2/dist/sweetalert2.min.css">
  <style>
    :root {
      --admin-accent: #dc2626;
      --admin-border: rgba(0, 0, 0, 0.08);
      --admin-page-bg: linear-gradient(165deg, #f5f5f7 0%, #ececf0 45%, #f0eeef 100%);
    }
    body { background: var(--admin-page-bg); color: #1a1a1a; }
    .auth .auth-form-light {
      background: #fff !important;
      border-radius: 16px;
      border: 1px solid var(--admin-border);
      box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06), 0 0 0 1px rgba(220, 38, 38, 0.12);
    }
    .auth .form-control {
      border-radius: 10px;
      border-color: rgba(0, 0, 0, 0.12);
    }
    .auth .form-control:focus {
      border-color: rgba(220, 38, 38, 0.45);
      box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12);
    }
    .auth .btn-success {
      background: var(--admin-accent) !important;
      border-color: rgba(0, 0, 0, 0.08) !important;
      border-radius: 10px;
    }
    .auth .btn-success:hover {
      background: #1f1f1f !important;
      box-shadow: inset 0 -2px 0 rgba(220, 38, 38, 0.85);
    }
  </style>
</head>
<body>
  <div class="container-scroller">
    <div class="container-fluid page-body-wrapper full-page-wrapper">
      <div class="content-wrapper d-flex align-items-center auth">
        <div class="row flex-grow">
          <div class="col-lg-6 mx-auto">
            <div class="auth-form-light text-left p-5">
              <div class="brand-logo text-center">
                  <img src="<?= base_url('uploads/logos/fullvendor-login-brand.png') ?>" width="100" alt="FullVendor">
              </div>
              <div class="text-center">
                <h4><?= htmlspecialchars($t($forgetPasswordLang, 'welcome_title', 'Hello! Let\'s get started'), ENT_QUOTES, 'UTF-8') ?></h4>
              </div>
              <form class="pt-3"  action="<?= admin_url('forgetpassword/updatepassword')?>" method="post">
                <?php if (!empty($msg)): ?>
                  <?= $msg ?>
                <?php endif ?>
                <div class="form-group">
                  <label><?= htmlspecialchars($t($forgetPasswordLang, 'label_new_password', 'New password'), ENT_QUOTES, 'UTF-8') ?></label>
                  <input type="password" name="n_password" class="form-control form-control-lg" placeholder="<?= htmlspecialchars($t($forgetPasswordLang, 'placeholder_new_password', 'New password'), ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
                  <input type="hidden" value="<?=@$useremail?>" name="forget_email">
                </div>
                <div class="form-group">
                  <label><?= htmlspecialchars($t($forgetPasswordLang, 'label_confirm_password', 'Confirm password'), ENT_QUOTES, 'UTF-8') ?></label>
                  <input type="password" name="c_password" class="form-control form-control-lg"  placeholder="<?= htmlspecialchars($t($forgetPasswordLang, 'placeholder_confirm_password', 'Confirm password'), ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
                </div>
                <div class="mt-3">
                  <button type="submit" class="btn btn-block btn-success btn-lg font-weight-semibold auth-form-btn"><?= htmlspecialchars($t($forgetPasswordLang, 'btn_reset', 'Reset'), ENT_QUOTES, 'UTF-8') ?></button>
                </div>
                
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
     </div>
    <script src="<?= base_url('companyasset/vendors/js/vendor.bundle.base.js')?>"></script>
    <script src="<?= base_url('companyasset/js/off-canvas.js')?>"></script>
    <script src="<?= base_url('companyasset/js/hoverable-collapse.js')?>"></script>
    <script src="<?= base_url('companyasset/js/misc.js')?>"></script>
    <script src="<?= base_url('companyasset/js/settings.js')?>"></script>
    <script src="<?= base_url('companyasset/js/todolist.js')?>"></script>
    <script src="<?= base_url('companyasset/vendors/sweetalert/sweetalert.min.js')?>"></script>
    
  </body>
  </html>

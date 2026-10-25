<?php
defined('BASEPATH') OR exit('No direct script access allowed');
$setting = get_instance()->getPlatformSettings();
$pageLang = isset($registerEmailLang) && is_array($registerEmailLang) ? $registerEmailLang : [];
$t = static function (string $key, string $fallback = '') use ($pageLang): string {
	$v = trim((string) ($pageLang[$key] ?? ''));

	return $v !== '' ? $v : $fallback;
};
$companyNameSafe = htmlspecialchars((string) (@$companyName ?? ''), ENT_QUOTES, 'UTF-8');
$emailDataSafe = htmlspecialchars((string) (@$emailData ?? ''), ENT_QUOTES, 'UTF-8');
$passwordSafe = htmlspecialchars((string) (@$password ?? ''), ENT_QUOTES, 'UTF-8');
?>
<!Doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($t('email_register_page_title', 'Registered successfully'), ENT_QUOTES, 'UTF-8') ?></title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
<div style="max-width:700px;
    margin:auto;
    border:1px solid #eee;
    box-shadow:0 0 10px rgba(0, 0, 0, .15);
    line-height:17px;
    font-size:13px;
    box-sizing:border-box; -webkit-print-color-adjust: exact;font-family: 'Poppins', sans-serif; ">
    <table cellpadding="10" cellspacing="0" width="100%">
      <tbody>
        <tr class="top">
          <td colspan="2">
            <table width="100%">
              <tbody>
                <tr>
                  <td class="title" style="vertical-align:middle; text-align: center;">
                    <a href="#"><img src="<?= base_url('uploads/logos/fullvendor-login-brand.png') ?>" alt="FullVendor" style="width:100%; max-width:100px;"></a>
                  </td>
                </tr>
              </tbody>
            </table>
          </td>
        </tr>
      </tbody>
    </table>
    <div style="width: 100%; padding: 30px; box-sizing: border-box; background: #eee;">
      <div style="width: 640px;background: #ffffffd1;margin: auto;padding: 15px; box-sizing: border-box;">
        <h2 style="text-align: center; font-size: 15px; font-weight: 500;margin-bottom: 30px; color: #008f2c;"><?= htmlspecialchars($t('email_register_heading', 'You have successfully registered with Full Vendor!'), ENT_QUOTES, 'UTF-8') ?></h2>
        <p style="font-size: 14px;line-height: 23px;font-weight: 400;text-align: center;margin-bottom: 30px;"><?= htmlspecialchars($t('email_register_intro', 'We\'re excited to have you get started.'), ENT_QUOTES, 'UTF-8') ?></p>
        <div style="text-align: center;">
          <p><strong><?= htmlspecialchars($t('email_register_label_name', 'Name'), ENT_QUOTES, 'UTF-8') ?>:</strong> <?= $companyNameSafe ?></p>
          <p><strong><?= htmlspecialchars($t('email_register_label_email', 'Email'), ENT_QUOTES, 'UTF-8') ?>:</strong> <?= $emailDataSafe ?></p>
          <p><strong><?= htmlspecialchars($t('email_register_label_password', 'Password'), ENT_QUOTES, 'UTF-8') ?>:</strong> <?= $passwordSafe ?></p>
        </div>
      </div>
    </div>
    <div style="background: #000; color: #fff;">
        <table style="text-align:left;" width="100%" cellpadding="8" cellspacing="0">
          <tbody>
            <tr>  
              <td colspan="2" style="padding: 10px 10px; text-align: center; font-size: 11px; border-top: 1px solid #595959; border-bottom: 1px solid #595959;">
                <?= htmlspecialchars($t('email_register_auto_response', 'This is an automated message. Please do not reply.'), ENT_QUOTES, 'UTF-8') ?>
              </td>
            </tr>
            <tr>
              <td style="padding:20px 20px 20px 20px;">
                <strong><?= htmlspecialchars($t('email_register_signoff_sincerely', 'Sincerely'), ENT_QUOTES, 'UTF-8') ?></strong><br/>
                <?= htmlspecialchars($t('email_register_signoff_brand', 'Full Vendor'), ENT_QUOTES, 'UTF-8') ?><br/>
                <a style="color:#eee; text-decoration: none;" href="https://fullvendor.com/">www.fullvendor.com</a>

              </td>
            </tr>
          </tbody>
        </table>
    </div> 
  </div>
</body>
</html>

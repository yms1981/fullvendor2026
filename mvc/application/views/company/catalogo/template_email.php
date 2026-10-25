<?php
    if (!function_exists('_catalog_email_str')) {
        function _catalog_email_str($v) {
            if ($v === null || $v === '') return '';
            if (is_scalar($v)) return (string) $v;
            if (is_object($v) && method_exists($v, '__toString')) return (string) $v->__toString();
            return '';
        }
    }
    $lang = isset($lang) && is_array($lang) ? $lang : [];
    $company = isset($company) ? $company : (object) [];
    $customer_info = isset($customer_info) ? $customer_info : (object) [];
    $format_date = isset($format_date) ? $format_date : fv_default_format_date_object();
    $customer_name = _catalog_email_str($customer_info->business_name ?? '') . ' (' . _catalog_email_str($customer_info->name ?? '') . ')';
    $format_php = (isset($format_date->format_php) && is_string($format_date->format_php)) ? $format_date->format_php : fv_display_date_format();
    $fecha_expired_str = is_string($fecha_expired) ? $fecha_expired : (is_scalar($fecha_expired) ? (string) $fecha_expired : '');
    $fecha_ex = $fecha_expired_str !== '' ? date($format_php, strtotime($fecha_expired_str)) : '';
    $token_str = _catalog_email_str($token ?? '');
?>
<body style='background-color:#e2e1e0;font-family:Open Sans,sans-serif;font-size:100%;font-weight:400;line-height:1.4;color:#000;'>
    <table style='max-width:600px;margin:20px auto 10px;background-color:#fff;padding:20px;border-radius:3px;border-top:solid 10px #000000;'>
        <thead>
            <tr>
                <th style='text-align:left;'>
                    <?php if (_catalog_email_str($company->image ?? '') !== '') { ?>
                    <img style='max-width: 150px;' src="<?= company_logo_url(_catalog_email_str($company->image)) ?>"
                        alt="<?= htmlspecialchars(_catalog_email_str($company->name ?? '')) ?>">
                    <?php } ?>
                </th>
                <th style='text-align:right;font-weight:400;'>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan="2" style="height:2px;background:#232323"></td>
            </tr>
            <tr>
                <td colspan="2" style="padding:20px 0">
                    <h3><?= _catalog_email_str($lang['saludo'] ?? '') . ' ' . $customer_name ?></h3>
                    <?php if(_catalog_email_str($email_text ?? '') !== ''): ?>
                    <p style="color: #000;"><?= htmlspecialchars(_catalog_email_str($email_text)) ?></p>
                    <?php endif; ?>
                    <p style="color: #000;"><?= _catalog_email_str($lang['asi_son'] ?? '') ?></p>
                    <p style="color: #000;"><?= _catalog_email_str($lang['podras'] ?? '') ?>
                        <a href="<?= catalog_public_browse_url($token_str) ?>"><?= _catalog_email_str($lang['enlace'] ?? '') ?></a>
                    </p>
                </td>
            </tr>
            <tr>
                <td style='width:50%;padding:0;vertical-align:top'>
                    <br><?= _catalog_email_str($lang['gracias'] ?? '') ?><br>
                    <?php if($seller && is_object($seller)): ?>
                    <?php
                        $seller_name = trim(_catalog_email_str($seller->first_name ?? '') . ' ' . _catalog_email_str($seller->last_name ?? ''));
                        if ($seller_name === '' && isset($seller->user) && is_object($seller->user)) {
                            $seller_name = _catalog_email_str($seller->user->name ?? '');
                        }
                        $seller_phone = _catalog_email_str($seller->phone_number ?? (isset($seller->user) && is_object($seller->user) ? ($seller->user->phone ?? '') : ''));
                        $seller_email = _catalog_email_str($seller->email ?? (isset($seller->user) && is_object($seller->user) ? ($seller->user->email ?? '') : ''));
                    ?>
                    <strong><?= _catalog_email_str($lang['seller'] ?? '') ?>: </strong><?= $seller_name ?><br>
                    <strong><?= _catalog_email_str($lang['phone'] ?? '') ?>: </strong><?= $seller_phone ?><br>
                    <strong>Email: </strong><?= $seller_email ?><br><br>
                    <?php endif; ?>
                    <strong><?= _catalog_email_str($lang['valido_hasta'] ?? '') . ': ' ?></strong><?= $fecha_ex ?>

                </td>
            </tr>
            <tr>
                <td colspan='2'
                    style='padding: 10px 10px; text-align: center; font-size: 11px; border-top: 1px solid #595959; border-bottom: 1px solid #595959;'>
                    <?= _catalog_email_str($lang['mensaje_automatizado'] ?? '') ?>
                </td>
            </tr>
        </tbody>
    </table>
</body>

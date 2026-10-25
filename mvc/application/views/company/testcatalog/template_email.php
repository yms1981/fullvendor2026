<?php
    $customer_name = (isset($customer_info->business_name) && is_scalar($customer_info->business_name) ? $customer_info->business_name : '') . ' (' . (isset($customer_info->name) && is_scalar($customer_info->name) ? $customer_info->name : '') . ')';
    $format_php = (isset($format_date->format_php) && is_string($format_date->format_php)) ? $format_date->format_php : fv_display_date_format();
    $fecha_expired_str = is_string($fecha_expired) ? $fecha_expired : (is_scalar($fecha_expired) ? (string) $fecha_expired : '');
    $fecha_ex = $fecha_expired_str !== '' ? date($format_php, strtotime($fecha_expired_str)) : '';
?>
<body style='background-color:#e2e1e0;font-family:Open Sans,sans-serif;font-size:100%;font-weight:400;line-height:1.4;color:#000;'>
    <table style='max-width:600px;margin:20px auto 10px;background-color:#fff;padding:20px;border-radius:3px;border-top:solid 10px #000000;'>
        <thead>
            <tr>
                <th style='text-align:left;'>
                    <?php if (!empty($company->image) && is_scalar($company->image)) { ?>
                    <img style='max-width: 150px;' src="<?= company_logo_url((string) $company->image) ?>"
                        alt="<?= isset($company->name) && is_scalar($company->name) ? htmlspecialchars((string)$company->name) : '' ?>">
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
                    <h3><?= $lang['saludo'] . ' ' . $customer_name?></h3>
                    <?php if($email_text): ?>
                    <p style="color: #000;"><?= $email_text ?></p>
                    <?php endif; ?>
                    <p style="color: #000;"><?= $lang['asi_son'] ?></p>
                    <p style="color: #000;"><?= $lang['podras'] ?>
                        <a href="<?= catalog_public_browse_url(is_string($token) ? $token : (is_scalar($token) ? (string) $token : '')) ?>"><?= $lang['enlace'] ?></a>
                    </p>
                </td>
            </tr>
            <tr>
                <td style='width:50%;padding:0;vertical-align:top'>
                    <br><?= $lang['gracias'] ?><br>
                    <?php if($seller && is_object($seller)): ?>
                    <?php
                        $seller_name = (isset($seller->first_name) && is_scalar($seller->first_name) ? $seller->first_name : '') . ' ' . (isset($seller->last_name) && is_scalar($seller->last_name) ? $seller->last_name : '');
                        if (trim($seller_name) === '' && isset($seller->user) && is_object($seller->user) && isset($seller->user->name) && is_scalar($seller->user->name)) {
                            $seller_name = $seller->user->name;
                        }
                        $seller_phone = isset($seller->phone_number) && is_scalar($seller->phone_number) ? $seller->phone_number : (isset($seller->user) && is_object($seller->user) && isset($seller->user->phone) && is_scalar($seller->user->phone) ? $seller->user->phone : '');
                        $seller_email = isset($seller->email) && is_scalar($seller->email) ? $seller->email : (isset($seller->user) && is_object($seller->user) && isset($seller->user->email) && is_scalar($seller->user->email) ? $seller->user->email : '');
                    ?>
                    <strong><?= $lang['seller'] ?>: </strong><?= $seller_name ?><br>
                    <strong><?= $lang['phone'] ?>: </strong><?= $seller_phone ?><br>
                    <strong>Email: </strong><?= $seller_email ?><br><br>
                    <?php endif; ?>
                    <strong><?= $lang['valido_hasta'] . ': ' ?></strong><?= $fecha_ex ?>

                </td>
            </tr>
            <tr>
                <td colspan='2'
                    style='padding: 10px 10px; text-align: center; font-size: 11px; border-top: 1px solid #595959; border-bottom: 1px solid #595959;'>
                    <?= $lang['mensaje_automatizado'] ?>
                </td>
            </tr>
        </tbody>
    </table>
</body>

<?php $customer_name = $customer->business_name. ' ('. $customer->name . ')';?>

<body style='background-color:#e2e1e0;font-family:Open Sans,sans-serif;font-size:100%;font-weight:400;line-height:1.4;color:#000;'>
    <table style='max-width:600px;margin:20px auto 10px;background-color:#fff;padding:20px;border-radius:3px;border-top:solid 10px #000000;'>
        <thead>
            <tr>
                <th style='text-align:left;'>
                    <?php if ($company->image) { ?>
                    <img style='max-width: 150px;' src="<?= company_logo_url((string) $company->image) ?>"
                        alt="<?= $company->name ?>">
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
                    <h3><?= $lang_validate['hello_customer'] . ' ' . $customer_name?></h3>
                    <p style="color: #000;"><?= $lang_validate['aviso'] ?></p>
                    
                    <p style="color: #000;">
                        <?= $lang_validate['msg_name_user'] . ": " . $customer->email ?>
                    </p>
                    <p style="color: #000;">
                        <?= $lang_validate['msg_password'] . ": " . $new_password ?>
                    </p>
                    
                </td>
            </tr>
            <tr>
                <td style='width:50%;padding:0;vertical-align:top'>
                    <br><?= $lang_validate['msg_thanks'] ?><br>


                    <!--               
                    <strong>: </strong><br>
                    <strong>: </strong><br>
                    <strong>Email: </strong><br><br>
                     -->
                </td>
            </tr>
            <tr>
                <td colspan='2' style='padding: 10px 10px; text-align: center; font-size: 11px; border-top: 1px solid #595959; border-bottom: 1px solid #595959;'>
                </td>
            </tr>
        </tbody>
    </table>
</body>
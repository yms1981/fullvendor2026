<?php
/**
 * @var $format_numeric object the format numeric of the company or the default if company doesn't have one configured
 */
$pageDocTitle = trim((string) ($title ?? $pageTitle ?? ''));
?>
<meta charset="utf-8">
<meta http-equiv="x-ua-compatible" content="ie=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta http-equiv="Expires" content="0">
<meta http-equiv="Last-Modified" content="0">
<meta http-equiv="Cache-Control" content="no-cache, mustrevalidate">
<meta http-equiv="Pragma" content="no-cache">

<title><?= ($pageDocTitle !== '') ? htmlspecialchars($pageDocTitle, ENT_QUOTES, 'UTF-8') . ' | ' : ''; ?><?= SITENAME ?></title>
<link rel="shortcut icon" href="<?= base_url('companyasset/images/favicon.png') ?>"/>

<script>
    var UrlBase = '<?= base_url() ?>';
</script>
<meta content="width=device-width, initial-scale=1.0" name="viewport">
<!-- Plugins CSS -->
<link rel="stylesheet" href="<?= dated_file_url('companyasset/antomi/assets/css/plugins.css') ?>">
<!-- Main Style CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.2.1/css/all.min.css"
      integrity="sha512-MV7K8+y+gLIBoVD59lQIYicR65iaqukzvf/nwasF0nqhPay5w/9lJmVM2hMDcnK1OnMGCdVK+iQrJ7lzPJQd1w=="
      crossorigin="anonymous" referrerpolicy="no-referrer"/>

<link rel="stylesheet" href="<?= dated_file_url('companyasset/css/boxicons.css') ?>"/>
<link rel="stylesheet" href="<?= dated_file_url('companyasset/antomi/assets/css/style.css') ?>">
<link rel="stylesheet" href="<?= dated_file_url('companyasset/css/animate.min.css') ?>">
<link rel="stylesheet" href="<?= dated_file_url('companyasset/antomi/assets/css/jquery.toast.css') ?>">
<link rel="stylesheet" href="<?= dated_file_url('companyasset/antomi/assets/css/sweetalert2.min.css') ?>">
<link rel="stylesheet" href="<?= dated_file_url('companyasset/css/preloader.css') ?>"/>
<link rel="stylesheet" href="<?= dated_file_url('companyasset/css/fullvendor-stilos.css') ?>"/>
<link rel="stylesheet" href="<?= dated_file_url('companyasset/css/bootstrap-select.min.css') ?>"/>

<!-- Catalog CSS -->
<link rel="stylesheet" href="<?= dated_file_url('companyasset/css/style_adicional_catalog.css') ?>"/>
<link rel="stylesheet" href="<?= dated_file_url('companyasset/vendors/mdi/css/materialdesignicons.min.css') ?>"/>

<?php
function format_currency($value, $currency_type=null) {
    global $format_numeric;
    return number_format($value, 2, $format_numeric->radixPoint, $format_numeric->groupSeparator);
}

<?php
/**
 * Layout storefront Marketplace (independiente de la landing FullVendor).
 *
 * @var string $main_content
 * @var string|null $head_content
 * @var string|null $header_content
 * @var string|null $footer_content
 * @var string|null $landingLocale
 */
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars((string) ($landingLocale ?? 'es'), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <?php $this->load->view($head_content ?? 'common/public-head-marketplace'); ?>
</head>
<body class="fv-mp-store-body">
    <?php $this->load->view($header_content ?? 'marketplace/partials/storefront_header'); ?>
    <?php $this->load->view($main_content); ?>
    <?php $this->load->view($footer_content ?? 'marketplace/partials/storefront_footer'); ?>
</body>
</html>

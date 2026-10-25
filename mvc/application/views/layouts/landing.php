<?php
/**
 * Layout landing pública (sin wrappers duplicados).
 *
 * @var string $main_content
 * @var string|null $head_content
 * @var string|null $header_content
 * @var string|null $footer_content
 */
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars((string) ($landingLocale ?? 'es'), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <?php $this->load->view($head_content ?? 'common/public-head-landing'); ?>
</head>
<body class="fv-landing-body">
    <?php $this->load->view($header_content ?? 'header'); ?>
    <?php $this->load->view($main_content); ?>
    <?php $this->load->view($footer_content ?? 'footer'); ?>
</body>
</html>

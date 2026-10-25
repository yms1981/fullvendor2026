<?php
/**
 * Head público para la landing (tipografías + CSS/JS dedicados).
 *
 * @var array $landing
 * @var string $title
 */
$L = (isset($landing) && is_array($landing)) ? $landing : [];
$metaTitle = trim((string) ($title ?? ($L['meta_title'] ?? 'Fullvendor')));
$metaDesc = trim((string) ($L['meta_description'] ?? ''));
?>
<meta charset="utf-8">
<meta http-equiv="x-ua-compatible" content="ie=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title><?= htmlspecialchars($metaTitle, ENT_QUOTES, 'UTF-8') ?></title>
<?php if ($metaDesc !== '') : ?>
<meta name="description" content="<?= htmlspecialchars($metaDesc, ENT_QUOTES, 'UTF-8') ?>">
<?php endif; ?>
<link rel="shortcut icon" href="<?= base_url('companyasset/images/favicon.png') ?>" />
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Sora:wght@500;600;700&display=swap" rel="stylesheet">
<link href="<?= base_url('frontend/') ?>css/bootstrap.min.css" rel="stylesheet">
<link href="<?= base_url('frontend/') ?>css/font-awesome.min.css" rel="stylesheet">
<link href="<?= base_url('frontend/') ?>css/landing.css?v=<?= time() ?>" rel="stylesheet">
<script src="<?= base_url('frontend/') ?>js/landing.js?v=<?= time() ?>" defer></script>

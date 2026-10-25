<?php
/**
 * @var string $main_content the content template path
*/
?>
<!DOCTYPE html>

<html>

<head>
    <?php $this->load->view($head_content ?? 'common/public-head'); ?>
</head>
<body>
<header>
    <?php $this->load->view($header_content ?? 'header'); ?>
</header>
<section id="main-content">
    <?php $this->load->view($main_content); ?>
</section>
<footer>
    <?php
    $this->load->view($footer_content ?? 'footer');
    ?>
</footer>
</body>
</html>

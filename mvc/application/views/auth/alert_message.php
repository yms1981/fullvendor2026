<?php
/**
 * @var $alertType string
 * @var $msg string
 */
?>
<div class="alert alert-<?= $alertType ?> alert-dismissable">
    <a href="javascript:void(0)" class="close" data-dismiss="alert" aria-label="close">&times;</a>
    <strong>
        <i class="fa fa-check-circle-o"></i>
    </strong>
    <?= $msg ?>
</div>

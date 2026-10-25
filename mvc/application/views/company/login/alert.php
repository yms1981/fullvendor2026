<div class="alert alert-<?= $type??'info' ?> alert-dismissable">
    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
    <strong>
        <i class="fa fa-check-circle-o"></i>
    </strong>
    <?php if (!empty($msg)): ?>
    <?= $msg /* escapado en getDangerAlert / Company_Controller */ ?>
    <?php endif ?>
</div>

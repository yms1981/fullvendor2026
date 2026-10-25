<?php
/**
 * @var array $pageTexts
 * @var object $listItem item from the list for a given record
 */
?>
<?php if (!empty($listItem->send_email_order)): ?>
    <?php
    $color = '#ff5730'; // rojo
    $mmsg  = $pageTexts['en_espera'];

    if (!empty($listItem->date_confirmed)) {
        $color = '#1EA910'; // verde
        $mmsg  = $pageTexts['aprobado_cliente'];
    } elseif (!empty($listItem->date_rejected_email)) {
        $color = '#ffc107'; // amarillo
        $mmsg  = $pageTexts['rechazado_cliente'];
    }
    ?>
    <span title="<?= $mmsg ?>" style="color: <?= $color ?>; display:inline-block; margin-top:4px;">
        <i class="fa-solid fa-envelope"></i>
    </span>
<?php endif; ?>

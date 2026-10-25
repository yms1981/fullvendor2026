<?php
/** @var Helper $color_semaforo */
$color_semaforo = $this->Helper->obtener_colorSemaforo($product, $info_company);
$leyenda_semaforo = $this->Helper->obtener_leyendaSemaforo($color_semaforo, $info_company);
?>
<div class="semaphore">
    <?php
    if ($leyenda_semaforo):
        ?>


        <span class="badge text-bg-light text-danger" style="font-size: 14px;text-align: left;padding-left: 0;">
        <?= $leyenda_semaforo ?>
    </span>
    <?php
    else:
        ?>
        <span class="badge text-bg-light text-dark" style="font-size: 14px;text-align: left;padding-left: 0;">
        <span class="roundblock notranslate" style="background: <?= $color_semaforo ?>;"></span>
    </span>
    <?php
    endif;
    ?>
</div>

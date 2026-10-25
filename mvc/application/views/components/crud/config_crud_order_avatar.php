<?php
/**
 * Numeración de fila en CRUD de configuración (avatar circular).
 *
 * @var int $orderNumber
 * @var bool|null $isDefaultRow
 */
$orderNumber = isset($orderNumber) ? (int) $orderNumber : 1;
$isDefaultRow = !empty($isDefaultRow);
?>
<div class="fv-config-crud-order-avatar mx-auto<?= $isDefaultRow ? ' fv-config-crud-order-avatar--default' : '' ?>"
     aria-hidden="true">
    <span class="fv-config-crud-order-avatar__number"><?= (int) $orderNumber ?></span>
</div>

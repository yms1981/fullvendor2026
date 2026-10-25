<?php
/**
 * @var object $listItem item from the list for a given record
 */
?>
<?= $listItem->user->cellphone ?? $listItem->phone_number ?? "Sin Telefono"; ?>

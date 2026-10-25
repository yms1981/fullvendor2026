<?php
/**
 * @var object $listItem the product item form the list for a given record
 * @var mixed $value the field value for the listItem
 */
$radixPoint = $format_numeric->radixPoint ?? '.';
$groupSeparator = $format_numeric->groupSeparator ?? ',';
$stock = fv_format_unit_quantity($value, $listItem ?? null, (string) $radixPoint, (string) $groupSeparator);
$unitLabel = fv_product_unit_label($listItem);
?>

<?php if ($unitLabel !== '') : ?>
<strong><?= htmlspecialchars($unitLabel, ENT_QUOTES, 'UTF-8') ?>: </strong>
<?php endif; ?>
<?= $stock ?>

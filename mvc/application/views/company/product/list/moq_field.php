<?php
/**
 * @var object $listItem the product item form the list for a given record
 * @var mixed $value the field value for the listItem
 */
$radixPoint = $format_numeric->radixPoint ?? '.';
$groupSeparator = $format_numeric->groupSeparator ?? ',';
$moq = fv_format_unit_quantity($value, $listItem ?? null, (string) $radixPoint, (string) $groupSeparator);
$unitLabel = fv_product_unit_label($listItem);
?>
<?= $moq ?><?php if ($unitLabel !== '') : ?> <?= htmlspecialchars($unitLabel, ENT_QUOTES, 'UTF-8') ?><?php endif; ?>

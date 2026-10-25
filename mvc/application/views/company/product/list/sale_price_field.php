<?php
/**
 * @var object $listItem the product item form the list for a given record
 * @var mixed $value the field value for the listItem
 * @var object $format_numeric the configuration for the numeric format of the current company
 * @var array $pageTexts the translated texts useful for the current module
 */
$radixPoint = $format_numeric->radixPoint ?? '';
$groupSeparator = $format_numeric->groupSeparator ?? '';
$price = number_format($value, $format_numeric->digits, $radixPoint,$groupSeparator);
$currencySymbol = $format_currency ?? '';
?>
<span class="text-success">
<?= $currencySymbol . " " . $price ?>
</span>

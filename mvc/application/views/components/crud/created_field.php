<?php
/**
 * @var object $listItem item from the list for a given record
 * @var object|null $format_date
 */

$date = $listItem->created_at ?? $listItem->created;
$datePhpFormat = (isset($format_date) && is_object($format_date) && ! empty($format_date->format_php))
    ? (string) $format_date->format_php
    : null;
?>
<?= htmlspecialchars(fv_format_display_date($date, $datePhpFormat), ENT_QUOTES, 'UTF-8') ?>

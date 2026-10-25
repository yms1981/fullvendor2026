<?php
/**
 * @var object $listItem
 */
$createdDate = $listItem->created ?? null;
$display = '—';
if ($createdDate) {
    $display = fv_format_display_date($createdDate);
}
?>
<span class="fv-catalog-date-cell"><?= htmlspecialchars($display, ENT_QUOTES, 'UTF-8') ?></span>

<?php
/**
 * @var object $listItem
 */
$createdDate = $listItem->created ?? null;
if ($createdDate) {
    echo htmlspecialchars(fv_format_display_date($createdDate), ENT_QUOTES, 'UTF-8');
}

<?php
/**
 * @var array $pageTexts
 * @var object $listItem item from the list for a given record
 */

$address = $listItem->address;
$parts = [];

$shippingNote = null;
$orderNote = null;

if (!empty($listItem->notes) && is_array($listItem->notes)) {
    foreach ($listItem->notes as $note) {
        if (empty($note->type) || empty($note->note)) continue;

        if ($note->type === 'shipping' && !$shippingNote) {
            $shippingNote = $note->note;
        } elseif ($note->type === 'internal' && !$orderNote) {
            $orderNote = $note->note;
        }

        if ($shippingNote && $orderNote) break;
    }
}

if ($address) {
    $base = trim($address->street . ' ' . ($address->street_number ?? ''));
    if ($base !== '') {
        $parts[] = $pageTexts['address_catalog'] . ' ' . $base;
    }

    if (!empty($address->zone)) {
        $parts[] = $pageTexts['zone'] . ': ' . $address->zone;
    }

    if (!empty($address->zipcode)) {
        $parts[] = $pageTexts['zipcode_catalog'] . ' ' . $address->zipcode;
    }

    if (!empty($address->city->name)) {
        $parts[] = $pageTexts['city_catalog'] . ' ' . $address->city->name;
    }

    if (!empty($address->state->name)) {
        $parts[] = $pageTexts['state_catalog'] . ' ' . $address->state->name;
    }

    if (!empty($address->country->name)) {
        $parts[] = $pageTexts['country'] . ': ' . $address->country->name;
    }

    if ($shippingNote) {
        $parts[] = $pageTexts['shipping_catalog'] . ' ' . $shippingNote;
    }

    if ($orderNote) {
        $parts[] = $pageTexts['comments_catalog'] . ' ' . $orderNote;
    }

} else {
    if ($orderNote) {
        $parts[] = $pageTexts['comments_catalog'] . ' ' . $orderNote;
    }
}

if (!empty($parts)) {
    echo '<div style="
        max-width: 250px;
        white-space: normal;
        overflow-wrap: anywhere;
        word-break: break-word;
        hyphens: auto;
        line-height: 1.4;
        text-align: center;
        display: inline-block;
        vertical-align: top;
    ">' . htmlspecialchars(implode(', ', $parts)) . '</div>';
}
?>

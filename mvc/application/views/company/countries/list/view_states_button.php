<?php
/**
 * @var object $listItem the country item from the list
 * @var array $itemAction the action configuration
 * @var string $itemIdField the field name for the item ID
 */

$countryId = $listItem->{$itemIdField} ?? '';
$countryName = trim($listItem->name ?? '');
?>
<button type="button"
        class="btn btn-sm <?= $itemAction['buttonClass'] ?? 'btn-info' ?> view-states-btn"
        data-country-id="<?= htmlspecialchars($countryId) ?>"
        data-country-name="<?= htmlspecialchars($countryName) ?>"
        data-toggle="modal"
        data-target="#modalStates"
        title="<?= $itemAction['label'] ?? 'Ver Estados' ?>">
    <i class="<?= $itemAction['iconClass'] ?? 'mdi mdi-map-marker' ?>"></i>
</button>


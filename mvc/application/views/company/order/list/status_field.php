<?php
/**
 * Mismo procedimiento que el modal "Change Status" en order details (View).
 * Una sola fuente: orderStatuses ya normalizada y deduplicada por cod_status.
 * Misma etiqueta y mismo círculo de color que el modal.
 * @var array $orderStatuses (lista única por cod_status, desde normalizeAndDeduplicateOrderStatuses)
 * @var string $changeStatusUrl URL to POST order_id + cod_status (orders/changeStatus)
 * @var object $listItem item from the list for a given record
 * @var array $statusLabelMap cod_status => etiqueta traducida (idioma del usuario)
 */
$orderStatuses = $orderStatuses ?? [];
$changeStatusUrl = $changeStatusUrl ?? '';
$statusLabelMap = $statusLabelMap ?? [];

// Deduplicar de nuevo por cod_status para evitar repetidos (misma lógica que View + modal)
$byCodStatus = [];
foreach ($orderStatuses as $status) {
    if (!is_object($status)) continue;
    $cod = (int)($status->cod_status ?? 0);
    if (!array_key_exists($cod, $byCodStatus)) {
        $byCodStatus[$cod] = $status;
    }
}
$orderStatuses = array_values($byCodStatus);
usort($orderStatuses, static function ($a, $b) use ($statusLabelMap) {
    $labelA = (string) ($statusLabelMap[(int) ($a->cod_status ?? 0)] ?? ($a->name_status_english ?? ''));
    $labelB = (string) ($statusLabelMap[(int) ($b->cod_status ?? 0)] ?? ($b->name_status_english ?? ''));
    return strcasecmp($labelA, $labelB);
});

$uniqueId = 'statusSelect_' . $listItem->order_id;
$currentStatusId = $listItem->status_id ?? null;
$so = $listItem->statusOrder ?? $listItem->status_order ?? null;
// API expone order_status = cod_status; fallback a relación statusOrder
$currentCodStatus = (int)($listItem->order_status ?? ($so && isset($so->cod_status) ? $so->cod_status : null) ?? 0);

// Reglas de estados:
// - Estado 9 o 10: combo desactivado (no se puede cambiar)
// - Estado 11: solo se puede cambiar a 14 o 7
$selectDisabled = in_array($currentCodStatus, [9, 10], true);
$onlyAllowed = ($currentCodStatus === 11) ? [7, 14] : null;
?>
<div class="fv-order-status-select-wrap<?= $selectDisabled ? ' is-disabled' : '' ?>">
<select class="fv-order-status-select order-status-select selectpicker"
        id="<?= $uniqueId ?>"
        name="cod_status[<?= $listItem->order_id ?>]"
        data-order-id="<?= $listItem->order_id ?>"
        data-change-status-url="<?= htmlspecialchars($changeStatusUrl) ?>"
        data-current-status="<?= $currentCodStatus ?>"
        data-show-content="true"
        data-container="body"
        data-width="fit"
        data-size="10"
        title="<?= htmlspecialchars($statusLabelMap[$currentCodStatus] ?? '', ENT_QUOTES, 'UTF-8') ?>"
        <?= $selectDisabled ? 'disabled' : '' ?>>
    <?php foreach ($orderStatuses as $status):
        $codStatus = (int)($status->cod_status ?? 0);
        $label = isset($statusLabelMap[$codStatus]) && $statusLabelMap[$codStatus] !== ''
            ? $statusLabelMap[$codStatus]
            : ($status->name_status_english ?? $status->name_status_spanish ?? $status->name_status ?? (string)$codStatus);
        $color = !empty($status->color) ? htmlspecialchars($status->color) : '#94a3b8';
        $isSelected = ($currentStatusId && (int)$currentStatusId === (int)($status->id_status ?? 0)) || ($currentCodStatus && $currentCodStatus === $codStatus);
        // Deshabilitar opción si hay restricción y no está en la lista permitida (excepto el estado actual)
        $optionDisabled = ($status->disabled == 1);
        if ($onlyAllowed !== null && !$isSelected && !in_array($codStatus, $onlyAllowed, true)) {
            $optionDisabled = true;
        }
    ?>
        <?php
        $dot = '<span class="fv-status-dot" style="background-color:' . $color . '"></span>';
        $content = $dot . htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
        ?>
        <option value="<?= $codStatus ?>" <?= $optionDisabled ? 'disabled' : '' ?>
                <?= $isSelected ? 'selected' : '' ?>
                data-color="<?= $color ?>"
                data-content="<?= htmlspecialchars($content, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($label) ?>
        </option>
    <?php endforeach; ?>
</select>
</div>

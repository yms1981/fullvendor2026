<?php

/**
 * Columna TOTAL del listado de órdenes.
 * Si hay importe asignado, muestra el solicitado y el asignado.
 *
 * @var object $listItem item from the list for a given record
 * @var object|array|null $format_numeric format (digits, radixPoint, groupSeparator)
 * @var string|null $format_currency currency symbol from company config
 * @var array|null $pageTexts
 */
$pageTexts = is_array($pageTexts ?? null) ? $pageTexts : [];

// Preferir total calculado; si viniera negativo (qty/cabecera mal), forzar positivo en este listado.
$rawTotal = $listItem->total
    ?? $listItem->totsale
    ?? $listItem->total_amount
    ?? $listItem->amount
    ?? 0;

$totalRequested = is_numeric($rawTotal) ? (float) $rawTotal : 0.0;
if ($totalRequested < 0) {
    $totalRequested = abs($totalRequested);
}

$totalDispatched = $listItem->total_asignado
    ?? $listItem->total_delivered
    ?? $listItem->amount_delivered
    ?? null;

$so = $listItem->statusOrder ?? $listItem->status_order ?? null;
$codStatus = (int) ($listItem->order_status
    ?? ($so && isset($so->cod_status) ? $so->cod_status : 0)
    ?? 0);

// 3 Dispatched, 4 Delivered, 10 Partially Completed, 11 Warehouse Completed, 12 Completed
$statusDispatched = in_array($codStatus, [3, 4, 10, 11, 12], true);
$hasDispatchedAmount = $totalDispatched !== null && (float) $totalDispatched > 0;
$showDispatched = $statusDispatched || $hasDispatchedAmount;

$symbol = null;
if (is_string($format_currency ?? null) && trim($format_currency) !== '') {
    $symbol = trim($format_currency);
} elseif (isset($listItem->currency_symbol->Symbol)) {
    $symbol = (string) $listItem->currency_symbol->Symbol;
} elseif (isset($listItem->currency_symbol) && is_string($listItem->currency_symbol)) {
    $symbol = trim($listItem->currency_symbol);
}

$fmtMoney = static function ($amount) use ($format_numeric, $symbol) {
    if (function_exists('fv_format_money')) {
        return fv_format_money($amount, $format_numeric ?? null, $symbol);
    }

    return (string) $amount;
};

$labelRequested = (string) ($pageTexts['amount_requested'] ?? $pageTexts['importe_solicitado'] ?? $pageTexts['total_pedido'] ?? 'Total');
$labelDispatched = (string) ($pageTexts['amount_assigned'] ?? $pageTexts['total_asigned'] ?? '');
?>
<?php if ($showDispatched): ?>
    <div class="text-center fv-order-list-totals">
        <div class="fv-order-list-totals__row">
            <span class="fv-order-list-totals__label d-block small text-muted"><?= htmlspecialchars($labelRequested, ENT_QUOTES, 'UTF-8') ?></span>
            <strong><?= htmlspecialchars($fmtMoney($totalRequested), ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <div class="fv-order-list-totals__row mt-1">
            <span class="fv-order-list-totals__label d-block small text-success"><?= htmlspecialchars($labelDispatched, ENT_QUOTES, 'UTF-8') ?></span>
            <strong class="text-success"><?= htmlspecialchars($fmtMoney($totalDispatched ?? 0), ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
    </div>
<?php else: ?>
    <div class="text-center"><?= htmlspecialchars($fmtMoney($totalRequested), ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

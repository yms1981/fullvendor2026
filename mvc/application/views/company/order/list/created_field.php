<?php
/**
 * Fecha de creación + última modificación (columna ordenada por `updated`).
 *
 * @var object $listItem
 * @var array $pageTexts
 * @var object|null $format_date
 */
$createdRaw = $listItem->created ?? null;
$updatedRaw = $listItem->updated ?? null;
$modifiedLabel = (string) ($pageTexts['updated'] ?? $pageTexts['orders_list_updated'] ?? 'Modified');
$datePhpFormat = (isset($format_date) && is_object($format_date) && ! empty($format_date->format_php))
    ? (string) $format_date->format_php
    : fv_resolve_display_date_format();
// data-order: valor real de updated (ISO) para que cualquier sort cliente no use el texto visible (created).
$sortKey = '';
if ($updatedRaw) {
    try {
        $sortKey = (new DateTimeImmutable((string) $updatedRaw))->format('Y-m-d H:i:s');
    } catch (Throwable $e) {
        $sortKey = (string) $updatedRaw;
    }
} elseif ($createdRaw) {
    try {
        $sortKey = (new DateTimeImmutable((string) $createdRaw))->format('Y-m-d H:i:s');
    } catch (Throwable $e) {
        $sortKey = (string) $createdRaw;
    }
}
?>
<div class="fv-crud-date-cell" data-order="<?= htmlspecialchars($sortKey, ENT_QUOTES, 'UTF-8') ?>">
    <?php if ($createdRaw) : ?>
        <span class="fv-crud-date-cell__primary">
            <?= htmlspecialchars(fv_format_display_date($createdRaw, $datePhpFormat), ENT_QUOTES, 'UTF-8') ?>
        </span>
    <?php endif; ?>
    <?php if ($updatedRaw) : ?>
        <span class="fv-crud-date-cell__badge">
            <?= htmlspecialchars($modifiedLabel, ENT_QUOTES, 'UTF-8') ?>
            <?= htmlspecialchars(fv_format_display_date($updatedRaw, $datePhpFormat), ENT_QUOTES, 'UTF-8') ?>
        </span>
    <?php endif; ?>
</div>

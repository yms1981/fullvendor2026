<?php
$t = static function (string $key, string $fallback = '') use ($lang_dashboard): string {
    $value = $lang_dashboard[$key] ?? $fallback;
    return is_string($value) ? $value : $fallback;
};
$hasMonthlyData = is_array($data_forchart['all'] ?? null) && count($data_forchart['all']) > 0;
?>

<div class="col-xl-12 col-lg-12 p-2">
    <div class="fv-dash-panel fv-dash-panel--chart h-100">
        <div class="fv-dash-panel__header">
            <h2 class="fv-dash-panel__title"><?= htmlspecialchars(strtoupper($t('ordenes_por_mes'))) ?></h2>
        </div>
        <div class="fv-chart-wrap fv-chart-wrap--tall">
            <?php if ($hasMonthlyData) : ?>
                <canvas id="myChart"></canvas>
            <?php else : ?>
                <div class="fv-empty-state"><?= htmlspecialchars($t('no_data')) ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>

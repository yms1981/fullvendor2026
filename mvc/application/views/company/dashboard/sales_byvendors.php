<?php
$t = static function (string $key, string $fallback = '') use ($lang_dashboard): string {
    $value = $lang_dashboard[$key] ?? $fallback;
    return is_string($value) ? $value : $fallback;
};
$fmtNum = static function ($value) use ($format_numeric): string {
    return number_format(
        (float) $value,
        $format_numeric->digits,
        $format_numeric->radixPoint,
        $format_numeric->groupSeparator
    );
};
$fmtMoney = static function ($value) use ($format_numeric, $moneda): string {
    if (function_exists('fv_format_money')) {
        return fv_format_money($value, $format_numeric, $moneda);
    }

    return $moneda . number_format(
        (float) $value,
        (int) ($format_numeric->digits ?? 2),
        (string) ($format_numeric->radixPoint ?? '.'),
        (string) ($format_numeric->groupSeparator ?? ',')
    );
};
$sellerAvatarHtml = $sellerAvatarHtml ?? static function ($ven): string {
    $name = is_object($ven) ? (string) ($ven->vendor ?? '') : '';
    $initial = mb_strtoupper(mb_substr(trim($name), 0, 1));

    return '<span class="fv-progress-list__avatar">' . htmlspecialchars($initial !== '' ? $initial : 'S', ENT_QUOTES, 'UTF-8') . '</span>';
};
?>

<div class="col-xl-6 col-lg-12 p-2">
    <div class="fv-dash-panel h-100">
        <div class="fv-dash-panel__header">
            <h2 class="fv-dash-panel__title"><?= htmlspecialchars($t('ordenes_por_vendedor') . $titulo_filtrado) ?></h2>
            <button type="button" class="btn btn-secondary btn-sm fv-dash-btn-icon" data-toggle="modal" data-target="#modal-salesman" title="<?= htmlspecialchars($t('show'), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars($t('show'), ENT_QUOTES, 'UTF-8') ?>">
                <i class="mdi mdi-eye" aria-hidden="true"></i>
            </button>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="table-responsive">
                    <table class="table fv-dash-table mb-0">
                        <thead>
                            <tr>
                                <th><?= htmlspecialchars($t('seller')) ?></th>
                                <th class="text-right"><?= htmlspecialchars($t('monto')) ?></th>
                                <th class="text-right">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (is_array($sales_byvendor['all'] ?? null) ? $sales_byvendor['all'] : [] as $key => $ven) : ?>
                                <?php if ($key < 5) : ?>
                                    <tr>
                                        <td>
                                            <span class="fv-dash-table__cat">
                                                <?= $sellerAvatarHtml($ven) ?>
                                                <span><?= htmlspecialchars((string) $ven->vendor) ?></span>
                                            </span>
                                        </td>
                                        <td class="text-right text-success font-weight-bold"><?= $fmtMoney($ven->total) ?></td>
                                        <td class="text-right"><?= $fmtNum($ven->percent_amount) ?>%</td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>

                            <?php if (count(is_array($sales_byvendor['all'] ?? null) ? $sales_byvendor['all'] : []) > 5) : ?>
                                <tr>
                                    <td>
                                        <span class="fv-legend-dot" style="background-color: <?= $colores[5] ?>;"></span>
                                        <?= htmlspecialchars($t('otros')) ?>
                                    </td>
                                    <td class="text-right text-success font-weight-bold"><?= $fmtMoney($sales_byvendor['otros_mount'] ?? 0) ?></td>
                                    <td class="text-right"><?= $fmtNum($sales_byvendor['otros_mount_porcent'] ?? 0) ?>%</td>
                                </tr>
                            <?php endif; ?>

                            <?php if (count(is_array($sales_byvendor['all'] ?? null) ? $sales_byvendor['all'] : []) > 0) : ?>
                                <tr class="fv-total-row">
                                    <td><?= htmlspecialchars($t('total')) ?></td>
                                    <td class="text-right"><?= $fmtMoney($sales_byvendor['total'] ?? 0) ?></td>
                                    <td class="text-right">100%</td>
                                </tr>
                            <?php else : ?>
                                <tr><td colspan="3" class="text-center text-muted py-3"><?= htmlspecialchars($t('no_data')) ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-md-6">
                <div class="fv-chart-wrap">
                    <?php if (count(is_array($sales_byvendor['all'] ?? null) ? $sales_byvendor['all'] : []) > 0) : ?>
                        <canvas id="myChart_salesbyVendor"></canvas>
                    <?php else : ?>
                        <div class="fv-empty-state"><?= htmlspecialchars($t('no_data')) ?></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

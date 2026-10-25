<?php
$t = static function (string $key, string $fallback = '') use ($lang_dashboard): string {
    $value = $lang_dashboard[$key] ?? $fallback;
    return is_string($value) ? $value : $fallback;
};
$fmtNum = static function ($value) use ($format_numeric): string {
    return number_format(
        (float) $value,
        (int) ($format_numeric->digits ?? 2),
        (string) ($format_numeric->radixPoint ?? '.'),
        function_exists('fv_resolve_numeric_group_separator')
            ? fv_resolve_numeric_group_separator($format_numeric)
            : (string) ($format_numeric->groupSeparator ?? ',')
    );
};
$fmtMoney = static function ($value) use ($format_numeric, $moneda): string {
    if (function_exists('fv_format_money')) {
        return fv_format_money($value, $format_numeric, $moneda);
    }

    return $moneda . $fmtNum($value);
};
$categoryAvatarHtml = $categoryAvatarHtml ?? static function ($cat): string {
    $name = is_object($cat) ? (string) ($cat->category_name ?? '') : '';
    $urls = function_exists('fv_category_image_urls') ? fv_category_image_urls($cat) : ['thumb' => '', 'has_image' => false, 'fallbacks' => []];
    if (! empty($urls['has_image']) && ! empty($urls['thumb'])) {
        $src = htmlspecialchars((string) $urls['thumb'], ENT_QUOTES, 'UTF-8');
        $alt = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

        return '<span class="fv-dash-avatar fv-dash-avatar--img" data-toggle="popover-hover" data-img="' . $src . '" title="' . $alt . '" aria-label="' . $alt . '">'
            . '<img src="' . $src . '" alt="' . $alt . '" loading="lazy">'
            . '</span>';
    }
    $initial = mb_strtoupper(mb_substr(trim($name), 0, 1));

    return '<span class="fv-dash-avatar">' . htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') . '</span>';
};
?>

<div class="col-12">
    <div class="row">
        <div class="col-xl-6 col-lg-12 p-2">
            <div class="fv-dash-panel h-100">
                <div class="fv-dash-panel__header">
                    <h2 class="fv-dash-panel__title"><?= htmlspecialchars($t('ventas_por_categoria_count') . $titulo_filtrado) ?></h2>
                    <button type="button" class="btn btn-secondary btn-sm fv-dash-btn-icon" data-toggle="modal" data-target="#modal-allsalesByCategoryCount" title="<?= htmlspecialchars($t('show'), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars($t('show'), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="mdi mdi-eye" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="table-responsive">
                            <table class="table fv-dash-table mb-0">
                                <thead>
                                    <tr>
                                        <th><?= htmlspecialchars($t('category')) ?></th>
                                        <th><?= htmlspecialchars($t('qty')) ?></th>
                                        <th class="text-right">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (is_array($sales_bycategoryQty ?? null) ? $sales_bycategoryQty : [] as $key => $value) : ?>
                                        <?php if ($key < 5) : ?>
                                            <tr>
                                                <td>
                                                    <span class="fv-dash-table__cat">
                                                        <?= $categoryAvatarHtml($value) ?>
                                                        <span><?= htmlspecialchars((string) $value->category_name) ?></span>
                                                    </span>
                                                </td>
                                                <td class="text-success font-weight-bold"><?= (int) $value->qty_items ?></td>
                                                <td class="text-right"><?= $fmtNum($value->percent_qty) ?>%</td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>

                                    <?php if (count(is_array($sales_bycategoryQty ?? null) ? $sales_bycategoryQty : []) > 5) : ?>
                                        <tr>
                                            <td>
                                                <span class="fv-dash-table__cat">
                                                    <span class="fv-dash-avatar" style="background: <?= $colores[5] ?>;">+</span>
                                                    <span><?= htmlspecialchars($t('otros')) ?></span>
                                                </span>
                                            </td>
                                            <td class="text-success font-weight-bold"><?= (int) ($sales_bycategoryQty_all['otros']['otros_qty'] ?? 0) ?></td>
                                            <td class="text-right"><?= $fmtNum($sales_bycategoryQty_all['otros']['otros_qty_porcent'] ?? 0) ?>%</td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php if (count(is_array($sales_bycategoryQty ?? null) ? $sales_bycategoryQty : []) > 0) : ?>
                                        <tr class="fv-total-row">
                                            <td><?= htmlspecialchars($t('total')) ?></td>
                                            <td><?= (int) ($sales_bycategoryQty_all['total_qty'] ?? 0) ?></td>
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
                            <?php if (count(is_array($sales_bycategoryQty ?? null) ? $sales_bycategoryQty : []) > 0) : ?>
                                <canvas id="myChart_salesbycategoryQty"></canvas>
                            <?php else : ?>
                                <div class="fv-empty-state"><?= htmlspecialchars($t('no_data')) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 col-lg-12 p-2">
            <div class="fv-dash-panel h-100">
                <div class="fv-dash-panel__header">
                    <h2 class="fv-dash-panel__title"><?= htmlspecialchars($t('ventas_por_categoria_mount') . $titulo_filtrado) ?></h2>
                    <button type="button" class="btn btn-secondary btn-sm fv-dash-btn-icon" data-toggle="modal" data-target="#modal-allsalesByCategoryMount" title="<?= htmlspecialchars($t('show'), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars($t('show'), ENT_QUOTES, 'UTF-8') ?>">
                        <i class="mdi mdi-eye" aria-hidden="true"></i>
                    </button>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="table-responsive">
                            <table class="table fv-dash-table mb-0">
                                <thead>
                                    <tr>
                                        <th><?= htmlspecialchars($t('category')) ?></th>
                                        <th class="text-right"><?= htmlspecialchars($t('amount')) ?></th>
                                        <th class="text-right">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (is_array($sales_bycategoryMount ?? null) ? $sales_bycategoryMount : [] as $key => $value) : ?>
                                        <?php if ($key < 5) : ?>
                                            <tr>
                                                <td>
                                                    <span class="fv-dash-table__cat">
                                                        <?= $categoryAvatarHtml($value) ?>
                                                        <span><?= htmlspecialchars((string) $value->category_name) ?></span>
                                                    </span>
                                                </td>
                                                <td class="text-right text-success font-weight-bold"><?= $fmtMoney($value->amount) ?></td>
                                                <td class="text-right"><?= $fmtNum($value->percent_amount) ?>%</td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>

                                    <?php if (count(is_array($sales_bycategoryMount ?? null) ? $sales_bycategoryMount : []) > 5) : ?>
                                        <tr>
                                            <td>
                                                <span class="fv-dash-table__cat">
                                                    <span class="fv-dash-avatar" style="background: <?= $colores[5] ?>;">+</span>
                                                    <span><?= htmlspecialchars($t('otros')) ?></span>
                                                </span>
                                            </td>
                                            <td class="text-right text-success font-weight-bold"><?= $fmtMoney($sales_bycategoryQty_all['otros']['otros_mount'] ?? 0) ?></td>
                                            <td class="text-right"><?= $fmtNum($sales_bycategoryQty_all['otros']['otros_mount_porcent'] ?? 0) ?>%</td>
                                        </tr>
                                    <?php endif; ?>

                                    <?php if (count(is_array($sales_bycategoryMount ?? null) ? $sales_bycategoryMount : []) > 0) : ?>
                                        <tr class="fv-total-row">
                                            <td><?= htmlspecialchars($t('total')) ?></td>
                                            <td class="text-right"><?= $fmtMoney($sales_bycategoryQty_all['total_mount'] ?? 0) ?></td>
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
                            <?php if (count(is_array($sales_bycategoryMount ?? null) ? $sales_bycategoryMount : []) > 0) : ?>
                                <canvas id="myChart_salesbycategoryMount"></canvas>
                            <?php else : ?>
                                <div class="fv-empty-state"><?= htmlspecialchars($t('no_data')) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

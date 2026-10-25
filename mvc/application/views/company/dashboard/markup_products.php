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
$productAvatarHtml = $productAvatarHtml ?? static function ($pro): string {
    $name = is_object($pro) ? (string) ($pro->name ?? '') : '';
    $sku = is_object($pro) ? (string) ($pro->sku ?? '') : '';
    $uuid = is_object($pro) ? trim((string) ($pro->image_uuid ?? '')) : '';
    $caption = htmlspecialchars(trim($sku !== '' ? $sku . ' · ' . $name : $name), ENT_QUOTES, 'UTF-8');
    if ($uuid !== '' && function_exists('fv_list_image_urls')) {
        $list = fv_list_image_urls($uuid);
        $thumb = (string) ($list['primary'] ?? '');
        if ($thumb !== '') {
            $src = htmlspecialchars($thumb, ENT_QUOTES, 'UTF-8');

            return '<span class="fv-rank-list__thumb fv-rank-list__thumb--img" data-toggle="popover-hover" data-img="' . $src . '" title="' . $caption . '" aria-label="' . $caption . '">'
                . '<img src="' . $src . '" alt="' . $caption . '" loading="lazy">'
                . '</span>';
        }
    }

    return '<span class="fv-rank-list__thumb"><i class="mdi mdi-package-variant"></i></span>';
};
?>

<div class="col-12 p-2">
    <div class="fv-dash-panel">
        <div class="fv-dash-panel__header">
            <h2 class="fv-dash-panel__title"><?= htmlspecialchars($t('products_report') . $titulo_filtrado) ?></h2>
            <button type="button" class="btn btn-secondary btn-sm fv-dash-btn-icon" data-toggle="modal" data-target="#modal-top10" title="<?= htmlspecialchars($t('show'), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars($t('show'), ENT_QUOTES, 'UTF-8') ?>">
                <i class="mdi mdi-eye" aria-hidden="true"></i>
            </button>
        </div>
        <div class="table-responsive">
            <table class="table fv-dash-table mb-0">
                <thead>
                    <tr>
                        <th></th>
                        <th><?= htmlspecialchars($t('label_sku')) ?></th>
                        <th><?= htmlspecialchars($t('label_product')) ?></th>
                        <th><?= htmlspecialchars($t('qty')) ?></th>
                        <th class="text-right"><?= htmlspecialchars($t('label_purchase_price')) ?></th>
                        <th class="text-right"><?= htmlspecialchars($t('label_average_ticket')) ?></th>
                        <th class="text-right"><?= htmlspecialchars($t('label_total_sales')) ?></th>
                        <th class="text-right"><?= htmlspecialchars($t('label_markup')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (is_array($top10_productos ?? null) ? $top10_productos : [] as $key => $pro) : ?>
                        <?php if ($key < 10) : ?>
                            <tr>
                                <td class="fv-dash-table__avatar-cell"><?= $productAvatarHtml($pro) ?></td>
                                <td><?= htmlspecialchars((string) $pro->sku) ?></td>
                                <td><?= htmlspecialchars((string) $pro->name) ?></td>
                                <td><?= $fmtNum($pro->qty_items) ?></td>
                                <td class="text-right text-success font-weight-bold"><?= $fmtMoney($pro->purchase_price) ?></td>
                                <td class="text-right text-success font-weight-bold"><?= $fmtMoney($pro->average_ticket) ?></td>
                                <td class="text-right text-success font-weight-bold"><?= $fmtMoney($pro->total) ?></td>
                                <td class="text-right font-weight-bold <?= ($pro->mark_up ?? 0) <= 0 ? 'text-danger' : 'text-success' ?>">
                                    <?= $fmtNum($pro->mark_up) ?>%
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php if (count(is_array($top10_productos ?? null) ? $top10_productos : []) === 0) : ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4"><?= htmlspecialchars($t('no_data')) ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

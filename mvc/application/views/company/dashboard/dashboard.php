<link rel="stylesheet" href="<?= dated_file_url('companyasset/css/dashboard-modern.css') ?>">

<style>
#modal_personalizado .modal-body .form-group-picker {
    position: relative !important;
    width: 100%;
}

#modal_personalizado .modal-body .form-group-picker .ui-datepicker {
    position: absolute !important;
    width: inherit !important;
    top: 60px !important;
    left: 0 !important;
}
</style>

<?php
$moneda = '';
if (isset($currency_symbol) && is_object($currency_symbol)) {
    $moneda = (string) ($currency_symbol->symbol ?? $currency_symbol->Symbol ?? '');
} elseif (isset($currency_symbol) && is_string($currency_symbol)) {
    $moneda = $currency_symbol;
}
$get_defaultDate = $this->input->get('fecha_desde_pordefecto');
$desde_fecha = $this->input->get('desde_fecha');
$hasta_fecha = $this->input->get('hasta_fecha') ? $this->input->get('hasta_fecha') : date('Y-m-d');

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

    return $moneda . number_format(
        (float) $value,
        (int) ($format_numeric->digits ?? 2),
        (string) ($format_numeric->radixPoint ?? '.'),
        (string) ($format_numeric->groupSeparator ?? ',')
    );
};

$initials = static function (string $name): string {
    $parts = preg_split('/\s+/u', trim($name), 2);
    $first = mb_substr((string) ($parts[0] ?? ''), 0, 1);
    $second = mb_substr((string) ($parts[1] ?? ''), 0, 1);
    return mb_strtoupper($first . $second);
};

$dashImgAvatar = static function (
    string $thumb,
    array $fallbacks,
    string $initialsText,
    string $css = 'fv-dash-avatar',
    string $popoverTitle = ''
): string {
    $cssEsc = htmlspecialchars($css, ENT_QUOTES, 'UTF-8');
    $initialsEsc = htmlspecialchars($initialsText, ENT_QUOTES, 'UTF-8');
    $titleEsc = htmlspecialchars($popoverTitle !== '' ? $popoverTitle : $initialsText, ENT_QUOTES, 'UTF-8');
    $initialsHtml = '<span class="' . $cssEsc . '__initials">' . $initialsEsc . '</span>';

    if ($thumb === '') {
        return '<span class="' . $cssEsc . '" title="' . $titleEsc . '">' . $initialsHtml . '</span>';
    }

    $src = htmlspecialchars($thumb, ENT_QUOTES, 'UTF-8');
    $fbJson = htmlspecialchars(json_encode(array_values($fallbacks)), ENT_QUOTES, 'UTF-8');
    $onError = "(function(el){var f=[];try{f=JSON.parse(el.getAttribute('data-fallbacks')||'[]');}catch(e){}var i=+el.getAttribute('data-fi')||0;var wrap=el.parentNode;if(f[i]){el.setAttribute('data-fi',String(i+1));el.src=f[i];if(wrap){wrap.setAttribute('data-img',f[i]);}return;}el.style.display='none';if(wrap){wrap.classList.remove('" . $cssEsc . "--img');wrap.removeAttribute('data-toggle');wrap.removeAttribute('data-img');wrap.removeAttribute('data-popover-caption');}var ini=wrap&&wrap.querySelector('." . $cssEsc . "__initials');if(ini){ini.style.display='inline-flex';}})(this)";

    return '<span class="' . $cssEsc . ' ' . $cssEsc . '--img"'
        . ' data-toggle="popover-hover"'
        . ' data-img="' . $src . '"'
        . ' data-popover-caption="' . $titleEsc . '"'
        . ' title="' . $titleEsc . '"'
        . ' aria-label="' . $titleEsc . '">'
        . '<img src="' . $src . '" alt="' . $titleEsc . '" loading="lazy" data-fallbacks="' . $fbJson . '" data-fi="0" onerror="' . $onError . '">'
        . '<span class="' . $cssEsc . '__initials" style="display:none">' . $initialsEsc . '</span>'
        . '</span>';
};

$categoryAvatarHtml = static function ($cat) use ($dashImgAvatar, $initials): string {
    $name = is_object($cat) ? (string) ($cat->category_name ?? '') : '';
    $urls = function_exists('fv_category_image_urls') ? fv_category_image_urls($cat) : ['thumb' => '', 'fallbacks' => [], 'has_image' => false];
    $thumb = ! empty($urls['has_image']) ? (string) ($urls['thumb'] ?? '') : '';
    $fallbacks = is_array($urls['fallbacks'] ?? null) ? $urls['fallbacks'] : [];

    return $dashImgAvatar($thumb, $fallbacks, $initials($name), 'fv-dash-avatar', $name);
};

$productAvatarHtml = static function ($pro) use ($dashImgAvatar, $initials): string {
    $name = is_object($pro) ? (string) ($pro->name ?? '') : '';
    $sku = is_object($pro) ? (string) ($pro->sku ?? '') : '';
    $uuid = is_object($pro) ? trim((string) ($pro->image_uuid ?? '')) : '';
    $thumb = '';
    $fallbacks = [];
    if ($uuid !== '' && function_exists('fv_list_image_urls')) {
        $list = fv_list_image_urls($uuid);
        $thumb = (string) ($list['primary'] ?? '');
        $fallbacks = is_array($list['fallbacks'] ?? null) ? $list['fallbacks'] : [];
    }
    $label = $initials($name);
    $caption = trim($sku !== '' ? $sku . ' · ' . $name : $name);

    return $dashImgAvatar($thumb, $fallbacks, $label !== '' ? $label : 'P', 'fv-rank-list__thumb', $caption);
};

$sellerAvatarHtml = static function ($ven) use ($dashImgAvatar, $initials): string {
    $name = is_object($ven) ? (string) ($ven->vendor ?? '') : '';
    $image = '';
    if (is_object($ven)) {
        $image = trim((string) ($ven->image ?? $ven->profile_image ?? ''));
    }
    $thumb = '';
    $fallbacks = [];
    if ($image !== '' && function_exists('fv_user_avatar_image_urls')) {
        $list = fv_user_avatar_image_urls((object) [
            'image' => $image,
            'profile_image' => $image,
            'name' => $name,
        ]);
        $thumb = (string) ($list['primary'] ?? '');
        $fallbacks = is_array($list['fallbacks'] ?? null) ? $list['fallbacks'] : [];
    }
    $label = $initials($name);

    return $dashImgAvatar($thumb, $fallbacks, $label !== '' ? $label : 'S', 'fv-progress-list__avatar', $name);
};

$barPct = static function ($part, $total): float {
    $part = (float) $part;
    $total = (float) $total;
    return $total > 0 ? min(100, round(($part / $total) * 100, 1)) : 0.0;
};

$titulo_filtrado = '';
switch ($get_defaultDate) {
    case 'personalizado':
        $titulo_filtrado = date($format_date->format_php, strtotime($desde_fecha)) . ' - ' . date($format_date->format_php, strtotime($hasta_fecha));
        break;
    case date('Y-m-d'):
        $titulo_filtrado = $t('hoy');
        break;
    case date('Y-m-01'):
        $titulo_filtrado = $t('mes_actual');
        break;
    case date('Y-m-01', strtotime('first day of last month')):
        $titulo_filtrado = $t('mes_anterior');
        break;
    case date('Y-m-01', strtotime('-6 months')):
        $titulo_filtrado = $t('ultimos_6_meses');
        break;
    case date('Y-m-01', strtotime('-12 months')):
        $titulo_filtrado = $t('ultimos_12_meses');
        break;
    default:
        $titulo_filtrado = $t('mes_actual');
        break;
}

$titulo_filtrado_suffix = ' (' . $titulo_filtrado . ')';

$appSales = (float) (($total_type['app'][0] ?? (object) ['total' => 0])->total ?? 0);
$webSales = (float) (($total_type['web'][0] ?? (object) ['total' => 0])->total ?? 0);
$manualSales = (float) (($total_type['manual'][0] ?? (object) ['total' => 0])->total ?? 0);

$appOrders = (int) (($total_type['app'][0] ?? (object) ['qty_orders' => 0])->qty_orders ?? 0);
$webOrders = (int) (($total_type['web'][0] ?? (object) ['qty_orders' => 0])->qty_orders ?? 0);
$manualOrders = (int) (($total_type['manual'][0] ?? (object) ['qty_orders' => 0])->qty_orders ?? 0);

$catalogConfirmed = (int) (($send_catalogs_confirmed[0] ?? (object) ['total_send_catalog' => 0])->total_send_catalog ?? 0);
$catalogReads = (int) (($send_catalogs_reads[0] ?? (object) ['total_send_catalog' => 0])->total_send_catalog ?? 0);
$catalogNoRead = (int) (($send_catalogs_noread[0] ?? (object) ['total_send_catalog' => 0])->total_send_catalog ?? 0);
$catalogOpen = (int) (($send_catalogs_open[0] ?? (object) ['total_send_catalog' => 0])->total_send_catalog ?? 0);

$enviroment = [
    'titulo_filtrado' => $titulo_filtrado_suffix,
    'format_numeric' => $format_numeric,
    'moneda' => $moneda,
    'lang_dashboard' => $lang_dashboard,
    'colores' => $colores,
    'format_date' => $format_date,
    'sales_bycustomer' => $sales_bycustomer,
    'sales_bycategoryQty' => $sales_bycategoryQty,
    'sales_bycategoryMount' => $sales_bycategoryMount,
    'sales_bycategoryQty_all' => $sales_bycategoryQty_all,
    'sales_byvendor' => $sales_byvendor,
    'top10_productos' => $top10_productos,
    'data_forchart' => $data_forchart,
    'categoryAvatarHtml' => $categoryAvatarHtml,
    'productAvatarHtml' => $productAvatarHtml,
    'sellerAvatarHtml' => $sellerAvatarHtml,
];

$sparkSales = [];
$sparkOrders = [];
foreach (array_slice(is_array($data_forchart['all'] ?? null) ? $data_forchart['all'] : [], -8) as $monthRow) {
    $filter = $monthRow->filter ?? (object) [];
    $salesRow = $filter->sales_total[0] ?? (object) ['total' => 0, 'qty_orders' => 0];
    $sparkSales[] = (float) ($salesRow->total ?? 0);
    $sparkOrders[] = (int) ($salesRow->qty_orders ?? 0);
}
$maxSparkSales = max($sparkSales ?: [1]);
$maxSparkOrders = max($sparkOrders ?: [1]);

$avgTicket = $qty_orders > 0 ? ($total_amount_sales / $qty_orders) : 0.0;
$topProductMax = 0.0;
foreach (is_array($top10_productos ?? null) ? $top10_productos : [] as $pro) {
    $topProductMax = max($topProductMax, (float) ($pro->total ?? 0));
}
$categoryMountTotal = (float) ($sales_bycategoryQty_all['total_mount'] ?? 0);
?>

<form id="form-filter" action="<?= company_url('dashboard/index') ?>" method="get">
    <div class="container-fluid page-body-wrapper pt-3 fv-dashboard">
        <div class="main-panel w-100">
            <div class="container-fluid pb-0">
                <div class="fv-dash-header">
                    <div>
                        <h1 class="fv-dash-header__title"><?= htmlspecialchars($t('dashboard')) ?></h1>
                        <p class="fv-dash-header__subtitle">
                            <?= htmlspecialchars($t('informe_ventas_completadas_sinforma')) ?>
                            <?= htmlspecialchars($titulo_filtrado_suffix) ?>
                        </p>
                        <?php if ($get_defaultDate === 'personalizado') : ?>
                            <p class="fv-dash-header__subtitle mb-0">
                                <?= htmlspecialchars(sprintf(
                                    $t('informe_ventas_completadas'),
                                    date($format_date->format_php, strtotime($desde_fecha)),
                                    date($format_date->format_php, strtotime($hasta_fecha))
                                )) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="fv-dash-filter">
                        <label for="select_filter"><?= htmlspecialchars($t('filtro_periodo')) ?></label>
                        <select id="select_filter" onchange="filter_dashboard(event)" class="selectpicker w-100"
                            name="fecha_desde_pordefecto"
                            data-none-selected-text="<?= htmlspecialchars($get_defaultDate === 'personalizado' ? $t('personalizado') : $t('filtro_periodo')) ?>"
                            onfocus="this.selectedIndex = -1;">
                            <option <?= $get_defaultDate == date('Y-m-d') ? 'selected' : '' ?> value="<?= date('Y-m-d') ?>">
                                <?= htmlspecialchars($t('hoy')) ?>
                            </option>
                            <?php if (isset($_GET['fecha_desde_pordefecto'])) : ?>
                                <option <?= $get_defaultDate == date('Y-m-01') ? 'selected' : '' ?> value="<?= date('Y-m-01') ?>">
                                    <?= htmlspecialchars($t('mes_actual')) ?>
                                </option>
                            <?php else : ?>
                                <option selected value="<?= date('Y-m-01') ?>"><?= htmlspecialchars($t('mes_actual')) ?></option>
                            <?php endif; ?>
                            <option <?= $get_defaultDate == date('Y-m-01', strtotime('first day of last month')) ? 'selected' : '' ?>
                                value="<?= date('Y-m-01', strtotime('first day of last month')) ?>">
                                <?= htmlspecialchars($t('mes_anterior')) ?>
                            </option>
                            <option <?= $get_defaultDate == date('Y-m-01', strtotime('-6 months')) ? 'selected' : '' ?>
                                value="<?= date('Y-m-01', strtotime('-6 months')) ?>">
                                <?= htmlspecialchars($t('ultimos_6_meses')) ?>
                            </option>
                            <option <?= $get_defaultDate == date('Y-m-01', strtotime('-12 months')) ? 'selected' : '' ?>
                                value="<?= date('Y-m-01', strtotime('-12 months')) ?>">
                                <?= htmlspecialchars($t('ultimos_12_meses')) ?>
                            </option>
                            <option <?= $get_defaultDate === 'personalizado' ? 'selected' : '' ?> value="personalizado">
                                <?= htmlspecialchars($t('personalizado')) ?>
                            </option>
                        </select>
                    </div>
                </div>

                <div class="fv-dash-stats-row">
                    <article class="fv-stat-card fv-stat-card--sales">
                        <div class="fv-stat-card__head">
                            <p class="fv-stat-card__label"><?= htmlspecialchars($t('sales')) ?></p>
                            <span class="fv-stat-card__badge"><?= htmlspecialchars($titulo_filtrado) ?></span>
                        </div>
                        <p class="fv-stat-card__value"><?= $fmtMoney($total_amount_sales) ?></p>
                        <div class="fv-stat-card__footer">
                            <div class="fv-sparkline" aria-hidden="true">
                                <?php foreach ($sparkSales as $val) : ?>
                                    <span class="fv-sparkline__bar" style="height: <?= $maxSparkSales > 0 ? max(8, round(($val / $maxSparkSales) * 100)) : 8 ?>%;"></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </article>

                    <article class="fv-stat-card fv-stat-card--orders">
                        <div class="fv-stat-card__head">
                            <p class="fv-stat-card__label"><?= htmlspecialchars($t('orders')) ?></p>
                            <span class="fv-stat-card__badge fv-stat-card__badge--soft"><?= (int) $qty_orders ?></span>
                        </div>
                        <p class="fv-stat-card__value"><?= (int) $qty_orders ?></p>
                        <div class="fv-stat-card__footer">
                            <div class="fv-sparkline" aria-hidden="true">
                                <?php foreach ($sparkOrders as $val) : ?>
                                    <span class="fv-sparkline__bar" style="height: <?= $maxSparkOrders > 0 ? max(8, round(($val / $maxSparkOrders) * 100)) : 8 ?>%;"></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </article>

                    <article class="fv-stat-card fv-stat-card--channels">
                        <div class="fv-stat-card__head">
                            <p class="fv-stat-card__label"><?= htmlspecialchars($t('channel_breakdown')) ?></p>
                        </div>
                        <div class="fv-stat-card__footer fv-stat-card__footer--channels">
                            <?php if ($total_amount_sales > 0) : ?>
                                <div class="fv-stat-card__mini-chart">
                                    <canvas id="myChart_channels"></canvas>
                                </div>
                                <div class="fv-stat-card__channel-legend">
                                    <div><span class="fv-legend-dot" style="background: <?= $colores[0] ?>;"></span><?= htmlspecialchars($t('channel_app')) ?>: <?= $fmtMoney($appSales) ?></div>
                                    <div><span class="fv-legend-dot" style="background: <?= $colores[1] ?>;"></span><?= htmlspecialchars($t('channel_web')) ?>: <?= $fmtMoney($webSales) ?></div>
                                    <div><span class="fv-legend-dot" style="background: <?= $colores[2] ?>;"></span><?= htmlspecialchars($t('channel_manual')) ?>: <?= $fmtMoney($manualSales) ?></div>
                                </div>
                            <?php else : ?>
                                <p class="fv-stat-card__label mb-0"><?= htmlspecialchars($t('no_data')) ?></p>
                            <?php endif; ?>
                        </div>
                    </article>

                    <article class="fv-stat-card fv-stat-card--catalog">
                        <div class="fv-stat-card__head">
                            <p class="fv-stat-card__label"><?= htmlspecialchars($t('catalogos')) ?></p>
                            <span class="fv-stat-card__badge fv-stat-card__badge--soft"><?= htmlspecialchars($t('ticket_promedio')) ?></span>
                        </div>
                        <p class="fv-stat-card__value"><?= (int) $total_send_catalog ?></p>
                        <p class="fv-stat-card__subvalue"><?= $fmtMoney($avgTicket) ?></p>
                    </article>
                </div>

                <div class="row fv-dash-row">
                    <div class="col-xl-5 col-lg-12 p-2">
                        <div class="fv-dash-panel fv-dash-panel--vendors h-100">
                            <div class="fv-dash-panel__header">
                                <h2 class="fv-dash-panel__title"><?= htmlspecialchars($t('ordenes_por_vendedor') . $titulo_filtrado_suffix) ?></h2>
                                <button type="button" class="btn btn-secondary btn-sm fv-dash-btn-icon" data-toggle="modal" data-target="#modal-salesman" title="<?= htmlspecialchars($t('show'), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars($t('show'), ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="mdi mdi-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                            <div class="fv-progress-list">
                                <?php foreach (is_array($sales_byvendor['all'] ?? null) ? $sales_byvendor['all'] : [] as $key => $ven) : ?>
                                    <?php if ($key < 6) : ?>
                                        <div class="fv-progress-list__item">
                                            <?= $sellerAvatarHtml($ven) ?>
                                            <div class="fv-progress-list__body">
                                                <div class="fv-progress-list__title">
                                                    <?= htmlspecialchars((string) $ven->vendor) ?>
                                                    <span class="fv-progress-list__pct"><?= $fmtNum($ven->percent_amount) ?>%</span>
                                                </div>
                                                <div class="fv-progress-list__bar">
                                                    <span style="width: <?= min(100, (float) ($ven->percent_amount ?? 0)) ?>%;"></span>
                                                </div>
                                            </div>
                                            <span class="fv-progress-list__amount"><?= $fmtMoney($ven->total) ?></span>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <?php if (count(is_array($sales_byvendor['all'] ?? null) ? $sales_byvendor['all'] : []) === 0) : ?>
                                    <div class="fv-empty-state"><?= htmlspecialchars($t('no_data')) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-7 col-lg-12 p-2">
                        <?php $this->load->view('company/dashboard/sales_bymonth', $enviroment); ?>
                    </div>
                </div>

                <div class="row fv-dash-row">
                    <div class="col-xl-8 col-lg-12 p-2">
                        <div class="fv-dash-panel fv-dash-panel--categories h-100">
                            <div class="fv-dash-panel__header">
                                <h2 class="fv-dash-panel__title"><?= htmlspecialchars($t('ventas_por_categoria_mount') . $titulo_filtrado_suffix) ?></h2>
                                <button type="button" class="btn btn-secondary btn-sm fv-dash-btn-icon" data-toggle="modal" data-target="#modal-allsalesByCategoryMount" title="<?= htmlspecialchars($t('show'), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars($t('show'), ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="mdi mdi-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                            <?php if ($categoryMountTotal > 0) : ?>
                                <p class="fv-dash-panel__total mb-1"><?= $fmtMoney($categoryMountTotal) ?></p>
                                <p class="fv-stat-card__label mb-0"><?= htmlspecialchars($t('ventas_totales')) ?></p>
                                <div class="fv-segment-bar">
                                    <?php foreach (is_array($sales_bycategoryMount ?? null) ? $sales_bycategoryMount : [] as $key => $cat) : ?>
                                        <?php if ($key < 5) : ?>
                                            <span class="fv-segment-bar__part" style="width: <?= $barPct($cat->amount ?? 0, $categoryMountTotal) ?>%; background-color: <?= $colores[$key] ?>;"></span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    <?php if (count(is_array($sales_bycategoryMount ?? null) ? $sales_bycategoryMount : []) > 5) : ?>
                                        <span class="fv-segment-bar__part" style="width: <?= $barPct($sales_bycategoryQty_all['otros']['otros_mount'] ?? 0, $categoryMountTotal) ?>%; background-color: <?= $colores[5] ?>;"></span>
                                    <?php endif; ?>
                                </div>
                                <div class="fv-progress-list fv-progress-list--categories mt-3">
                                    <?php foreach (is_array($sales_bycategoryMount ?? null) ? $sales_bycategoryMount : [] as $key => $cat) : ?>
                                        <?php if ($key < 6) : ?>
                                            <div class="fv-progress-list__item">
                                                <?= $categoryAvatarHtml($cat) ?>
                                                <div class="fv-progress-list__body">
                                                    <div class="fv-progress-list__title">
                                                        <?= htmlspecialchars((string) $cat->category_name) ?>
                                                        <span class="fv-progress-list__pct"><?= $fmtNum($cat->percent_amount ?? 0) ?>%</span>
                                                    </div>
                                                    <div class="fv-progress-list__bar">
                                                        <span style="width: <?= min(100, (float) ($cat->percent_amount ?? 0)) ?>%; background: <?= $colores[$key % count($colores)] ?>;"></span>
                                                    </div>
                                                </div>
                                                <span class="fv-progress-list__amount"><?= $fmtMoney($cat->amount) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php else : ?>
                                <div class="fv-empty-state"><?= htmlspecialchars($t('no_data')) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="col-xl-4 col-lg-12 p-2">
                        <div class="fv-promo-card h-100">
                            <h3 class="fv-promo-card__title"><?= htmlspecialchars($t('catalogos')) ?></h3>
                            <p class="fv-promo-card__text"><?= htmlspecialchars($t('informe_ventas_completadas_sinforma')) ?></p>
                            <div class="fv-promo-card__stats">
                                <div class="fv-promo-card__stat">
                                    <strong><?= $catalogConfirmed ?></strong>
                                    <span><?= htmlspecialchars($t('completado')) ?></span>
                                </div>
                                <div class="fv-promo-card__stat">
                                    <strong><?= $catalogReads ?></strong>
                                    <span><?= htmlspecialchars($t('leido')) ?></span>
                                </div>
                                <div class="fv-promo-card__stat">
                                    <strong><?= $catalogNoRead ?></strong>
                                    <span><?= htmlspecialchars($t('noleido')) ?></span>
                                </div>
                                <div class="fv-promo-card__stat">
                                    <strong><?= $catalogOpen ?></strong>
                                    <span><?= htmlspecialchars($t('open')) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row fv-dash-row">
                    <?php $this->load->view('company/dashboard/sales_bycategory', $enviroment); ?>
                </div>

                <div class="row fv-dash-row">
                    <div class="col-xl-7 col-lg-12 p-2">
                        <div class="fv-dash-panel fv-dash-panel--products h-100">
                            <div class="fv-dash-panel__header">
                                <h2 class="fv-dash-panel__title"><?= htmlspecialchars($t('top_10') . $titulo_filtrado_suffix) ?></h2>
                                <button type="button" class="btn btn-secondary btn-sm fv-dash-btn-icon" data-toggle="modal" data-target="#modal-top10" title="<?= htmlspecialchars($t('show'), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars($t('show'), ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="mdi mdi-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                            <div class="fv-rank-list">
                                <?php foreach (is_array($top10_productos ?? null) ? $top10_productos : [] as $key => $pro) : ?>
                                    <?php if ($key < 6) : ?>
                                        <div class="fv-rank-list__item">
                                            <?= $productAvatarHtml($pro) ?>
                                            <div>
                                                <p class="fv-rank-list__name"><?= htmlspecialchars((string) $pro->name) ?></p>
                                                <p class="fv-rank-list__meta"><?= htmlspecialchars((string) $pro->sku) ?> · <?= $fmtNum($pro->qty_items) ?> <?= htmlspecialchars($t('qty')) ?></p>
                                                <div class="fv-rank-list__bar-wrap">
                                                    <div class="fv-rank-list__bar">
                                                        <span style="width: <?= $topProductMax > 0 ? $barPct($pro->total ?? 0, $topProductMax) : 0 ?>%;"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <span class="fv-rank-list__value"><?= $fmtMoney($pro->total) ?></span>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <?php if (count(is_array($top10_productos ?? null) ? $top10_productos : []) === 0) : ?>
                                    <div class="fv-empty-state"><?= htmlspecialchars($t('no_data')) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-5 col-lg-12 p-2">
                        <div class="fv-dash-panel fv-dash-panel--customers h-100">
                            <div class="fv-dash-panel__header">
                                <h2 class="fv-dash-panel__title"><?= htmlspecialchars($t('ventas_por_cliente') . $titulo_filtrado_suffix) ?></h2>
                                <button type="button" class="btn btn-secondary btn-sm fv-dash-btn-icon" data-toggle="modal" data-target="#modal-allsalesbycustomers" title="<?= htmlspecialchars($t('show'), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars($t('show'), ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="mdi mdi-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                            <div class="fv-rank-list">
                                <?php foreach (is_array($sales_bycustomer['all'] ?? null) ? $sales_bycustomer['all'] : [] as $key => $sales) : ?>
                                    <?php if ($key < 6) : ?>
                                        <div class="fv-rank-list__item">
                                            <span class="fv-rank-list__thumb"><i class="mdi mdi-account"></i></span>
                                            <div>
                                                <p class="fv-rank-list__name"><?= htmlspecialchars((string) $sales->customer) ?></p>
                                                <p class="fv-rank-list__meta">
                                                    <?= htmlspecialchars((string) ($sales->seller ?? '')) ?>
                                                    · <?= date($format_date->format_php, strtotime($sales->last_created)) ?>
                                                </p>
                                            </div>
                                            <span class="fv-rank-list__value"><?= $fmtMoney($sales->total) ?></span>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <?php if (count(is_array($sales_bycustomer['all'] ?? null) ? $sales_bycustomer['all'] : []) === 0) : ?>
                                    <div class="fv-empty-state"><?= htmlspecialchars($t('no_data')) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row fv-dash-row">
                    <?php $this->load->view('company/dashboard/markup_products', $enviroment); ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modal_personalizado" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><?= htmlspecialchars($t('filter')) ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="<?= htmlspecialchars($t('close')) ?>">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group-picker picker_desde">
                        <div class="form-group">
                            <label for="desde" class="form-label"><?= htmlspecialchars($t('desde')) ?></label>
                            <?php
                            $dashDesdeIso = $this->input->get('desde_fecha') ?: date('Y-m-01');
                            $dashHastaIso = $this->input->get('hasta_fecha') ?: date('Y-m-d');
                            ?>
                            <input name="desde_fecha_texto" readonly id="desde" tabindex="1"
                                value="<?= htmlspecialchars(fv_format_display_date($dashDesdeIso), ENT_QUOTES, 'UTF-8') ?>"
                                autocomplete="off"
                                class="form-control datepicker datepicker-desde"
                                type="text">
                            <input hidden id="desde_fecha_iso"
                                value="<?= htmlspecialchars((string) $dashDesdeIso, ENT_QUOTES, 'UTF-8') ?>"
                                autocomplete="off" type="text" name="desde_fecha">
                        </div>
                    </div>
                    <div class="form-group-picker picker_hasta">
                        <div class="form-group">
                            <label for="hasta" class="form-label"><?= htmlspecialchars($t('hasta')) ?></label>
                            <input name="hasta_fecha_texto" readonly id="hasta" tabindex="1"
                                value="<?= htmlspecialchars(fv_format_display_date($dashHastaIso), ENT_QUOTES, 'UTF-8') ?>"
                                autocomplete="off"
                                class="form-control datepicker datepicker-hasta"
                                type="text">
                            <input hidden id="hasta_fecha_iso"
                                value="<?= htmlspecialchars((string) $dashHastaIso, ENT_QUOTES, 'UTF-8') ?>"
                                autocomplete="off" type="text" name="hasta_fecha">
                        </div>
                    </div>
                    <br><br><br><br><br><br>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success"><?= htmlspecialchars($t('aplicar_periodo')) ?></button>
                    <button type="button" class="btn btn-warning" data-dismiss="modal"><?= htmlspecialchars($t('cancelar')) ?></button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php
$this->load->view('company/dashboard/modal_allsalesbycustomers', $enviroment);
$this->load->view('company/dashboard/modal_allsalesbycategory_count', $enviroment);
$this->load->view('company/dashboard/modal_allsalesbycategory_mount', $enviroment);
$this->load->view('company/dashboard/modal_salesman', $enviroment);
$this->load->view('company/dashboard/modal_top10products', $enviroment);
$jsonString = json_encode(is_array($top10_productos ?? null) ? $top10_productos : []);
?>

<script>
var data_forchart = JSON.parse('<?= json_encode($data_forchart) ?>');
var sales_bycustomer = JSON.parse('<?= json_encode($sales_bycustomer, JSON_HEX_APOS) ?>');
var ventas_totales = <?= json_encode($t('ventas_totales') . $titulo_filtrado_suffix) ?>;
var ventas_month = <?= json_encode($t('ventas_totales')) ?>;
var amount_text = <?= json_encode($t('monto')) ?>;
var ventas_qty = <?= json_encode($t('qty')) ?>;
var labelOtros = <?= json_encode($t('otros')) ?>;
var labelChannelApp = <?= json_encode($t('channel_app')) ?>;
var labelChannelWeb = <?= json_encode($t('channel_web')) ?>;
var labelChannelManual = <?= json_encode($t('channel_manual')) ?>;

var sales_bycategoryMount = JSON.parse('<?= json_encode($sales_bycategoryMount) ?>');
var sales_bycategoryQty = JSON.parse('<?= json_encode($sales_bycategoryQty) ?>');
var sales_byvendors = JSON.parse('<?= json_encode($sales_byvendor) ?>');
var sales_bycategoryQty_all = JSON.parse('<?= json_encode($sales_bycategoryQty_all) ?>');

const _separator = <?= json_encode($format_numeric->groupSeparator) ?>;
const _decimalSeparator = <?= json_encode($format_numeric->radixPoint) ?>;
const _moneda = <?= json_encode($moneda) ?>;
window._chartColors = <?= json_encode(array_values($colores)) ?>;
window.channelChartData = {
    app: <?= json_encode($appSales) ?>,
    web: <?= json_encode($webSales) ?>,
    manual: <?= json_encode($manualSales) ?>
};

<?php
$jsonString = str_replace("'", "\'", $jsonString);
$jsonString = addslashes($jsonString);
?>
var top10_productos = JSON.parse('<?= $jsonString ?>');
var max_chart = 5;

$(document).ready(function() {
    $('#modal_personalizado').on('shown.bs.modal', function() {
        active_datepicker('.datepicker-desde', '.picker_desde');
        active_datepicker('.datepicker-hasta', '.picker_hasta');
    });

    function active_datepicker(datepicker, container) {
        var $el = $(`#modal_personalizado .modal-body ${datepicker}`);
        if (!$el.length || typeof $.fn.datepicker !== 'function' || !$.datepicker) {
            return;
        }
        try { $el.datepicker('destroy'); } catch (e) { /* ignore */ }
        $el.data('fvCompanyDatepickerReady', false);
        $el.datepicker({
            beforeShow: function(input, inst) {
                inst.dpDiv.appendTo(`#modal_personalizado .modal-body .form-group-picker${container}`);
            },
            dateFormat: (window.fvCompanyDateFormatJs || <?= json_encode(fv_display_date_format_js(), JSON_UNESCAPED_UNICODE) ?>),
            changeMonth: true,
            changeYear: true,
            dayNames: [<?= json_encode($lang_meses['domingo']) ?>, <?= json_encode($lang_meses['lunes']) ?>, <?= json_encode($lang_meses['martes']) ?>, <?= json_encode($lang_meses['miercoles']) ?>, <?= json_encode($lang_meses['jueves']) ?>, <?= json_encode($lang_meses['viernes']) ?>, <?= json_encode($lang_meses['sabado']) ?>],
            dayNamesMin: [<?= json_encode($lang_meses['dom']) ?>, <?= json_encode($lang_meses['lun']) ?>, <?= json_encode($lang_meses['mar']) ?>, <?= json_encode($lang_meses['mie']) ?>, <?= json_encode($lang_meses['jue']) ?>, <?= json_encode($lang_meses['vie']) ?>, <?= json_encode($lang_meses['sab']) ?>],
            monthNames: [<?= json_encode($lang_meses['enero']) ?>, <?= json_encode($lang_meses['febrero']) ?>, <?= json_encode($lang_meses['marzo']) ?>, <?= json_encode($lang_meses['abril']) ?>, <?= json_encode($lang_meses['mayo']) ?>, <?= json_encode($lang_meses['junio']) ?>, <?= json_encode($lang_meses['julio']) ?>, <?= json_encode($lang_meses['agosto']) ?>, <?= json_encode($lang_meses['septiembre']) ?>, <?= json_encode($lang_meses['octubre']) ?>, <?= json_encode($lang_meses['noviembre']) ?>, <?= json_encode($lang_meses['diciembre']) ?>],
            monthNamesShort: [<?= json_encode($lang_meses['ene']) ?>, <?= json_encode($lang_meses['feb']) ?>, <?= json_encode($lang_meses['mar']) ?>, <?= json_encode($lang_meses['abr']) ?>, <?= json_encode($lang_meses['may']) ?>, <?= json_encode($lang_meses['jun']) ?>, <?= json_encode($lang_meses['jul']) ?>, <?= json_encode($lang_meses['ago']) ?>, <?= json_encode($lang_meses['sep']) ?>, <?= json_encode($lang_meses['oct']) ?>, <?= json_encode($lang_meses['nov']) ?>, <?= json_encode($lang_meses['dic']) ?>],
            onSelect: function() {
                var currentDate = $(this).datepicker('getDate');
                var $hidden = $(this).next('input[type="hidden"], input[hidden], input[name="desde_fecha"], input[name="hasta_fecha"]');
                if (currentDate && $hidden.length) {
                    var y = currentDate.getFullYear();
                    var m = ('0' + (currentDate.getMonth() + 1)).slice(-2);
                    var d = ('0' + currentDate.getDate()).slice(-2);
                    $hidden.val(y + '-' + m + '-' + d);
                }
            },
            inline: true
        });
        $el.data('fvCompanyDatepickerReady', true);
        var iso = $el.next('input').val();
        if (iso && $.datepicker.parseDate) {
            try {
                var parts = String(iso).split('-');
                if (parts.length === 3) {
                    $el.datepicker('setDate', new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10)));
                }
            } catch (e2) { /* ignore */ }
        }
    }

    if (typeof window.fvInitOrderImagePopovers === 'function') {
        window.fvInitOrderImagePopovers(document);
    } else if (typeof activar_popover_imagenes === 'function') {
        activar_popover_imagenes();
    }

    $('#modal-salesman, #modal-top10, #modal-allsalesByCategoryMount, #modal-allsalesByCategoryCount').on('shown.bs.modal', function() {
        if (typeof window.fvInitOrderImagePopovers === 'function') {
            window.fvInitOrderImagePopovers(this);
        } else if (typeof activar_popover_imagenes === 'function') {
            activar_popover_imagenes();
        }
    });
});
</script>

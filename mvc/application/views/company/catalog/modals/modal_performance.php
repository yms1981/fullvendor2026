<?php
/**
 * Modal de rendimiento del catálogo.
 *
 * @var object $v
 * @var array  $lang_optionbasic
 * @var array  $lang_modal
 * @var array  $langcatalog
 * @var mixed  $format_numeric
 * @var string $moneda
 * @var int|float $sending
 * @var int|float $customer_count
 * @var int|float $total_import
 */
$lang_optionbasic = (isset($lang_optionbasic) && is_array($lang_optionbasic)) ? $lang_optionbasic : [];
$lang_modal = (isset($lang_modal) && is_array($lang_modal)) ? $lang_modal : [];
$langcatalog = (isset($langcatalog) && is_array($langcatalog)) ? $langcatalog : [];

$t = static function (array $primary, array $fallback, string $key, string $default = ''): string {
    $value = $primary[$key] ?? $fallback[$key] ?? $default;
    return is_string($value) ? $value : $default;
};

$sending = (float) ($sending ?? ($v->sending ?? 0));
$customer_count = (float) ($customer_count ?? ($v->customer_count ?? 0));
$total_import = (float) ($total_import ?? ($v->total ?? 0));
$ticket_average = ($sending > 0 && $total_import > 0) ? ($total_import / $sending) : 0.0;

$fn = is_object($format_numeric) ? $format_numeric : (object) [];
$digits = isset($fn->digits) ? (int) $fn->digits : 2;
$radix = isset($fn->radixPoint) ? (string) $fn->radixPoint : '.';
$group = isset($fn->groupSeparator) ? (string) $fn->groupSeparator : ',';
$currency = isset($moneda) ? (string) $moneda : '';

$catalogId = (int) ($v->catalog_id ?? 0);
$catalogName = trim((string) ($v->catalog_name ?? ''));
$periodFrom = date('M-d-Y', strtotime(date('Y-01-01')));
$periodTo = date('M-d-Y', strtotime(date('Y-m-d')));
$hasMetrics = ($sending > 0 || $total_import > 0 || $customer_count > 0);

$title = $t($langcatalog, $lang_optionbasic, 'view_performance', '');
$closeLabel = $t($lang_modal, $langcatalog, 'close', '');
?>
<div class="modal fade fv-catalog-modal" id="performanceModal<?= $catalogId ?>" tabindex="-1"
     aria-labelledby="performanceModalLabel<?= $catalogId ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="performanceModalLabel<?= $catalogId ?>">
                    <span class="modal-header__avatar" aria-hidden="true">
                        <i class="mdi mdi-chart-line"></i>
                    </span>
                    <span><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></span>
                </h5>
                <button type="button" class="close fv-modal-close-x" data-dismiss="modal"
                        aria-label="<?= htmlspecialchars($closeLabel, ENT_QUOTES, 'UTF-8') ?>">
                    <i class="mdi mdi-close" aria-hidden="true"></i>
                </button>
            </div>
            <div class="modal-body">
                <?php if ($catalogName !== '') : ?>
                    <p class="fv-catalog-perf__name mb-2"><?= htmlspecialchars($catalogName, ENT_QUOTES, 'UTF-8') ?></p>
                <?php endif; ?>

                <div class="fv-catalog-perf__period mb-3">
                    <span class="fv-catalog-perf__period-label">
                        <?= htmlspecialchars($t($langcatalog, $lang_optionbasic, 'performance_period', ''), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <span>
                        <?= htmlspecialchars($t($langcatalog, $lang_optionbasic, 'from', ''), ENT_QUOTES, 'UTF-8') ?>
                        <?= htmlspecialchars($periodFrom, ENT_QUOTES, 'UTF-8') ?>
                        ·
                        <?= htmlspecialchars($t($langcatalog, $lang_optionbasic, 'to', ''), ENT_QUOTES, 'UTF-8') ?>
                        <?= htmlspecialchars($periodTo, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>

                <?php if (!$hasMetrics) : ?>
                    <div class="fv-catalog-perf__empty mb-3">
                        <?= htmlspecialchars($t($langcatalog, $lang_optionbasic, 'performance_empty_hint', ''), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                <?php endif; ?>

                <div class="fv-catalog-perf__grid">
                    <div class="fv-catalog-perf__card fv-catalog-perf__card--orders">
                        <div class="fv-catalog-perf__icon" aria-hidden="true"><i class="mdi mdi-clipboard-text-outline"></i></div>
                        <div class="fv-catalog-perf__meta">
                            <span class="fv-catalog-perf__label"><?= htmlspecialchars($t($langcatalog, $lang_optionbasic, 'total_order_quantity', ''), ENT_QUOTES, 'UTF-8') ?></span>
                            <strong class="fv-catalog-perf__value"><?= htmlspecialchars((string) (int) $sending, ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                    </div>
                    <div class="fv-catalog-perf__card fv-catalog-perf__card--amount">
                        <div class="fv-catalog-perf__icon" aria-hidden="true"><i class="mdi mdi-cart-outline"></i></div>
                        <div class="fv-catalog-perf__meta">
                            <span class="fv-catalog-perf__label"><?= htmlspecialchars($t($langcatalog, $lang_optionbasic, 'total_order_amount', ''), ENT_QUOTES, 'UTF-8') ?></span>
                            <strong class="fv-catalog-perf__value">
                                <?= htmlspecialchars($currency, ENT_QUOTES, 'UTF-8') ?>
                                <?= htmlspecialchars(number_format($total_import, $digits, $radix, $group), ENT_QUOTES, 'UTF-8') ?>
                            </strong>
                        </div>
                    </div>
                    <div class="fv-catalog-perf__card fv-catalog-perf__card--ticket">
                        <div class="fv-catalog-perf__icon" aria-hidden="true"><i class="mdi mdi-tag-outline"></i></div>
                        <div class="fv-catalog-perf__meta">
                            <span class="fv-catalog-perf__label"><?= htmlspecialchars($t($langcatalog, $lang_optionbasic, 'ticket_average', ''), ENT_QUOTES, 'UTF-8') ?></span>
                            <strong class="fv-catalog-perf__value">
                                <?= htmlspecialchars($currency, ENT_QUOTES, 'UTF-8') ?>
                                <?= htmlspecialchars(number_format($ticket_average, $digits, $radix, $group), ENT_QUOTES, 'UTF-8') ?>
                            </strong>
                        </div>
                    </div>
                    <div class="fv-catalog-perf__card fv-catalog-perf__card--customers">
                        <div class="fv-catalog-perf__icon" aria-hidden="true"><i class="mdi mdi-account-multiple-outline"></i></div>
                        <div class="fv-catalog-perf__meta">
                            <span class="fv-catalog-perf__label"><?= htmlspecialchars($t($langcatalog, $lang_optionbasic, 'total_customers', ''), ENT_QUOTES, 'UTF-8') ?></span>
                            <strong class="fv-catalog-perf__value"><?= htmlspecialchars((string) (int) $customer_count, ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" data-dismiss="modal" aria-label="<?= htmlspecialchars($closeLabel, ENT_QUOTES, 'UTF-8') ?>"
                        class="btn btn-danger"><?= htmlspecialchars($closeLabel, ENT_QUOTES, 'UTF-8') ?></button>
            </div>
        </div>
    </div>
</div>

<?php
/**
 * @var array $pageTexts
 * @var array $groups
 * @var object|null $product
 */
$groupsList = [];
if (is_array($groups ?? null)) {
    foreach ($groups as $groupRow) {
        if (is_array($groupRow) || is_object($groupRow)) {
            $groupsList[] = is_object($groupRow) ? $groupRow : (object) $groupRow;
        }
    }
} elseif (is_object($groups ?? null)) {
    foreach ($groups as $groupRow) {
        if (is_array($groupRow) || is_object($groupRow)) {
            $groupsList[] = is_object($groupRow) ? $groupRow : (object) $groupRow;
        }
    }
}
$currencySymbol = $format_currency ?? '';
?>
<?php if ($groupsList === []) : ?>
    <p class="fv-product-price-groups__empty text-muted small mb-0">
        <?= htmlspecialchars((string) ($pageTexts['price_groups_empty'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
    </p>
<?php else : ?>
    <?php foreach ($groupsList as $grupo) :
        $grupo = is_object($grupo) ? $grupo : (object) (array) $grupo;
        $groupId = (int) ($grupo->group_id ?? 0);
        $groupName = trim((string) ($grupo->name ?? ''));
        $percentageLabel = trim((string) ($grupo->labelTipoPorcentaje ?? ''));
        ?>
        <div class="fv-product-price-group-item" data-group-id="<?= $groupId ?>">
            <div class="fv-product-price-group-item__label">
                <span class="fv-product-price-group-item__name">
                    <?= htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8') ?>
                </span>
                <?php if ($percentageLabel !== '') : ?>
                    <span class="fv-product-price-group-item__pct">
                        <?= htmlspecialchars($percentageLabel, ENT_QUOTES, 'UTF-8') ?>
                    </span>
                <?php endif; ?>
            </div>
            <div class="fv-product-price-group-item__value">
                <span class="fv-product-currency__symbol"><?= htmlspecialchars((string) $currencySymbol, ENT_QUOTES, 'UTF-8') ?></span>
                <span class="fv-product-price-group-item__amount"
                      data-group-id="<?= $groupId ?>"
                      data-name="<?= htmlspecialchars($groupName, ENT_QUOTES, 'UTF-8') ?>">
                    <?= format_currency($grupo->sale_price_recalculado ?? 0, isset($product) ? $product->currency_type : null) ?>
                </span>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

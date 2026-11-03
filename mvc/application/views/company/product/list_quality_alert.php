<?php
/**
 * @var array<string, int> $productListQuality
 * @var array<string, string> $pageTexts
 */
$quality = is_array($productListQuality ?? null) ? $productListQuality : [];
$duplicateProducts = (int) ($quality['duplicate_products'] ?? 0);
$zeroPrice = (int) ($quality['zero_price'] ?? 0);
$stockFixed = (int) ($quality['negative_stock_fixed'] ?? 0);
if ($duplicateProducts < 1 && $zeroPrice < 1 && $stockFixed < 1) {
    return;
}

$fill = static function (string $template, int $count): string {
    return str_replace('{count}', (string) $count, $template);
};

$lines = [];
if ($duplicateProducts > 0) {
    $template = (string) ($pageTexts['quality_duplicate_barcodes'] ?? '');
    if ($template !== '') {
        $lines[] = $fill($template, $duplicateProducts);
    }
}
if ($zeroPrice > 0) {
    $template = (string) ($pageTexts['quality_zero_price'] ?? '');
    if ($template !== '') {
        $lines[] = $fill($template, $zeroPrice);
    }
}
if ($stockFixed > 0) {
    $template = (string) ($pageTexts['quality_stock_fixed'] ?? '');
    if ($template !== '') {
        $lines[] = $fill($template, $stockFixed);
    }
}
if ($lines === []) {
    return;
}

$title = (string) ($pageTexts['quality_alert_title'] ?? '');
?>
<div class="alert alert-warning mb-3" role="alert">
    <?php if ($title !== '') : ?>
        <strong><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></strong>
    <?php endif; ?>
    <ul class="mb-0<?= $title !== '' ? ' mt-2' : '' ?>">
        <?php foreach ($lines as $line) : ?>
            <li><?= htmlspecialchars($line, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endforeach; ?>
    </ul>
</div>

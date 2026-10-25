<?php
/**
 * Número de orden con icono según origen (app, catalog/web, manual, marketplace).
 *
 * @var object|array<string,mixed>|null $listItem
 * @var array<string,mixed>|null $pageTexts
 */
$item = $listItem ?? null;
$source = '';
if (is_object($item)) {
    $source = trim((string) ($item->source ?? $item->order_source ?? $item->orderSource ?? ''));
} elseif (is_array($item)) {
    $source = trim((string) ($item['source'] ?? $item['order_source'] ?? $item['orderSource'] ?? ''));
}
$sourceKey = strtolower($source);
$texts = is_array($pageTexts ?? null) ? $pageTexts : [];

$iconMdi = 'mdi-store';
$colorClass = 'text-info';
$sourceTitle = (string) ($texts['origin_web'] ?? $texts['order_origin_catalog'] ?? 'Web');

if (in_array($sourceKey, ['app', 'mobile', 'android', 'ios'], true)) {
    $iconMdi = 'mdi-cellphone-android';
    $colorClass = 'text-primary';
    $sourceTitle = (string) ($texts['origin_app'] ?? $texts['order_origin_app'] ?? 'App');
} elseif (in_array($sourceKey, ['manual'], true)) {
    $iconMdi = 'mdi-pencil-box-outline';
    $colorClass = 'text-secondary';
    $sourceTitle = (string) ($texts['origin_manual'] ?? $texts['order_origin_manual'] ?? 'Manual');
} elseif (in_array($sourceKey, ['catalog', 'web', 'company_panel', 'catalogo'], true)) {
    $iconMdi = 'mdi-store';
    $colorClass = 'text-info';
    $sourceTitle = (string) ($texts['origin_web'] ?? $texts['order_origin_catalog'] ?? 'Web');
} elseif (in_array($sourceKey, ['marketplace', 'seller_portal'], true)) {
    $iconMdi = 'mdi-cart';
    $colorClass = 'text-success';
    $sourceTitle = (string) ($texts['origin_marketplace'] ?? $texts['order_origin_marketplace'] ?? $texts['ecommerce'] ?? 'E-Commerce');
} elseif ($sourceKey === '') {
    $iconMdi = 'mdi-store';
    $colorClass = 'text-info';
    $sourceTitle = (string) ($texts['origin_web'] ?? $texts['order_origin_catalog'] ?? 'Web');
}

$orderId = 0;
$orderNumRaw = '';
if (is_object($item)) {
    $orderId = (int) ($item->order_id ?? 0);
    $orderNumRaw = (string) ($item->order_number ?? '');
} elseif (is_array($item)) {
    $orderId = (int) ($item['order_id'] ?? 0);
    $orderNumRaw = (string) ($item['order_number'] ?? '');
}
$viewUrl = $orderId > 0 ? company_url('orders/view/' . $orderId) : '';
$orderNum = htmlspecialchars($orderNumRaw, ENT_QUOTES, 'UTF-8');
$sourceTitleEsc = htmlspecialchars($sourceTitle, ENT_QUOTES, 'UTF-8');
?>
<?php if ($viewUrl !== '') : ?>
    <a href="<?= htmlspecialchars($viewUrl, ENT_QUOTES, 'UTF-8') ?>" class="fv-grid-order-link" title="<?= $sourceTitleEsc ?>">
        <i class="mdi <?= htmlspecialchars($iconMdi, ENT_QUOTES, 'UTF-8') ?> fv-grid-order-link__icon <?= htmlspecialchars($colorClass, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
        <span><?= $orderNum ?></span>
    </a>
<?php else : ?>
    <span class="fv-grid-order-link">
        <i class="mdi <?= htmlspecialchars($iconMdi, ENT_QUOTES, 'UTF-8') ?> fv-grid-order-link__icon <?= htmlspecialchars($colorClass, ENT_QUOTES, 'UTF-8') ?>" title="<?= $sourceTitleEsc ?>" aria-hidden="true"></i>
        <span><?= $orderNum ?></span>
    </span>
<?php endif; ?>

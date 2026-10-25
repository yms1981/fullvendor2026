<?php
/**
 * Cards de producto storefront / tienda.
 *
 * @var array $productList
 * @var array $mp
 * @var object|null $company
 * @var bool|null $storeMode
 */
$mp = (isset($mp) && is_array($mp)) ? $mp : [];
$t = static function (string $key, string $fallback = '') use ($mp): string {
    $v = trim((string) ($mp[$key] ?? ''));

    return $v !== '' ? $v : $fallback;
};
$products = (isset($productList) && is_array($productList)) ? $productList : [];
$storeMode = ! empty($storeMode);
$cname = is_object($company ?? null) ? (string) ($company->name ?? '') : '';
$cslug = is_object($company ?? null) ? (string) ($company->slug_url ?? '') : '';
$clogo = is_object($company ?? null) && function_exists('company_logo_url')
    ? company_logo_url((string) ($company->image ?? ''))
    : base_url('uploads/noimg.png');
$loginHref = base_url('marketplace/login');
$productImg = static function ($product): string {
    $uuid = trim((string) ($product->image_uuid ?? ''));
    if ($uuid !== '' && function_exists('company_product_image_url')) {
        return company_product_image_url($uuid, 0);
    }
    $name = trim((string) ($product->productImage ?? ''));
    if ($name !== '') {
        return base_url('uploads/products/' . ltrim($name, '/'));
    }

    return function_exists('catalog_public_no_image_url')
        ? catalog_public_no_image_url()
        : base_url('uploads/noimg.png');
};
$formatPrice = static function ($product): string {
    $amount = (float) ($product->sale_price ?? 0);
    $symbol = trim((string) ($product->currency_symbol ?? $product->currency_type ?? ''));
    if ($symbol === '' && ! empty($product->currency_code)) {
        $symbol = (string) $product->currency_code;
    }
    if (function_exists('fv_format_money')) {
        return fv_format_money($amount, null, $symbol !== '' ? $symbol : null);
    }

    return ($symbol !== '' ? $symbol . ' ' : '') . number_format($amount, 2, '.', ',');
};

if ($products === []) {
    echo '<div class="fv-sf-empty" style="grid-column:1/-1;">' . htmlspecialchars($t('products_empty'), ENT_QUOTES, 'UTF-8') . '</div>';

    return;
}

foreach ($products as $i => $p) {
    $pname = (string) ($p->name ?? '');
    $href = $cslug !== ''
        ? base_url('marketplaces/details/' . rawurlencode($cslug))
        : '#';
    $cardClass = $storeMode ? 'fv-sf-card fv-sf-card--store' : 'fv-sf-card';
    ?>
  <article class="<?= htmlspecialchars($cardClass, ENT_QUOTES, 'UTF-8') ?>" data-name="<?= htmlspecialchars($pname, ENT_QUOTES, 'UTF-8') ?>">
    <?php if ($i === 0) : ?>
      <span class="fv-sf-card__badge"><?= htmlspecialchars($t('badge_new'), ENT_QUOTES, 'UTF-8') ?></span>
    <?php endif; ?>
    <span class="fv-sf-card__fav" aria-hidden="true"><i class="fa fa-heart-o"></i></span>
    <a class="fv-sf-card__media" href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>">
      <img src="<?= htmlspecialchars($productImg($p), ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($pname, ENT_QUOTES, 'UTF-8') ?>" loading="lazy" width="280" height="280">
    </a>
    <div class="fv-sf-card__body">
      <a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"><strong><?= htmlspecialchars($pname, ENT_QUOTES, 'UTF-8') ?></strong></a>
      <span class="fv-sf-card__price"><?= htmlspecialchars($formatPrice($p), ENT_QUOTES, 'UTF-8') ?></span>
      <?php if (! $storeMode && $cname !== '') : ?>
        <span class="fv-sf-card__company-chip">
          <img src="<?= htmlspecialchars($clogo, ENT_QUOTES, 'UTF-8') ?>" alt="" width="20" height="20" loading="lazy">
          <?= htmlspecialchars($cname, ENT_QUOTES, 'UTF-8') ?>
        </span>
      <?php endif; ?>
      <?php if ($storeMode) : ?>
        <a class="fv-sf-card__cart" href="<?= htmlspecialchars($loginHref, ENT_QUOTES, 'UTF-8') ?>">
          <i class="fa fa-shopping-cart" aria-hidden="true"></i>
          <?= htmlspecialchars($t('store_add_to_cart'), ENT_QUOTES, 'UTF-8') ?>
        </a>
      <?php endif; ?>
    </div>
  </article>
    <?php
}

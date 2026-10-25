<?php
defined('BASEPATH') or exit('No direct script access allowed');

/** @var array<string, mixed> $pageTexts */
/** @var bool $catalog_order_available */
/** @var string $url_catalog_order_browse */
/** @var string $url_catalog_order_cart */
/** @var string $url_catalog_order_checkout */

$t = is_array($pageTexts ?? null) ? $pageTexts : [];
$e = static function (?string $s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
};
$orderOk = ! empty($catalog_order_available);
$urlBrowse = (string) ($url_catalog_order_browse ?? '');
$urlCart = (string) ($url_catalog_order_cart ?? '');
$urlCheckout = (string) ($url_catalog_order_checkout ?? '');
?>
<section class="portal-catalog-order-card" aria-labelledby="portal-catalog-order-heading">
  <h2 id="portal-catalog-order-heading" class="portal-catalog-order-title"><?= $e($t['catalog_order_section_title'] ?? '') ?></h2>
  <p class="portal-catalog-order-intro"><?= $e($t['catalog_order_intro'] ?? '') ?></p>
  <?php if ($orderOk) { ?>
    <ul class="portal-catalog-order-actions">
      <li>
        <a class="btn btn-danger btn-block portal-catalog-order-btn" href="<?= $e($urlBrowse) ?>">
          <i class="mdi mdi-cart-plus" aria-hidden="true"></i><?= $e($t['catalog_order_btn_browse'] ?? '') ?>
        </a>
      </li>
      <li>
        <a class="btn btn-outline-secondary btn-block portal-catalog-order-btn" href="<?= $e($urlCart) ?>">
          <i class="mdi mdi-cart-outline" aria-hidden="true"></i><?= $e($t['catalog_order_btn_cart'] ?? '') ?>
        </a>
      </li>
      <li>
        <a class="btn btn-success btn-block portal-catalog-order-btn" href="<?= $e($urlCheckout) ?>">
          <i class="mdi mdi-send-check-outline" aria-hidden="true"></i><?= $e($t['catalog_order_btn_checkout'] ?? '') ?>
        </a>
      </li>
    </ul>
  <?php } else { ?>
    <p class="portal-catalog-order-unavailable text-muted mb-0" role="status"><?= $e($t['catalog_order_unavailable'] ?? '') ?></p>
  <?php } ?>
</section>

<?php
/**
 * Footer storefront Marketplace.
 *
 * @var array $mp
 * @var array $landing
 * @var string $landingLocale
 */
$mp = (isset($mp) && is_array($mp)) ? $mp : ((isset($landing) && is_array($landing)) ? $landing : []);
$t = static function (string $key, string $fallback = '') use ($mp): string {
    $v = trim((string) ($mp[$key] ?? ''));

    return $v !== '' ? $v : $fallback;
};
$locale = isset($landingLocale) ? (string) $landingLocale : 'es';
?>
<footer class="fv-sf-footer">
  <div class="fv-sf-wrap fv-sf-footer__inner">
    <div>
      <strong>FULL<span>VENDOR</span> MARKET PLACE</strong>
      <p><?= htmlspecialchars($t('footer_tagline', $t('hero_lead')), ENT_QUOTES, 'UTF-8') ?></p>
    </div>
    <div class="fv-sf-footer__links">
      <a href="<?= htmlspecialchars(base_url('marketplaces?lang=' . rawurlencode($locale)), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('breadcrumb_marketplaces', 'Marketplaces'), ENT_QUOTES, 'UTF-8') ?></a>
      <a href="<?= htmlspecialchars(base_url('?lang=' . rawurlencode($locale)), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('footer_back_fullvendor', 'FullVendor home'), ENT_QUOTES, 'UTF-8') ?></a>
      <a href="<?= htmlspecialchars(function_exists('company_url') ? company_url() : base_url('company'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('promo_seller_cta'), ENT_QUOTES, 'UTF-8') ?></a>
    </div>
  </div>
</footer>

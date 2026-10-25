<?php
/**
 * Tienda de empresa — layout mockup storefront.
 *
 * @var object|null $company
 * @var object|null $data
 * @var array $mp
 * @var array $categoryList
 * @var array $productList
 * @var int $activeCategoryId
 * @var string $paginate
 * @var string $landingLocale
 * @var int|null $productTotal
 */
$mp = (isset($mp) && is_array($mp)) ? $mp : [];
$t = static function (string $key, string $fallback = '') use ($mp): string {
    $v = trim((string) ($mp[$key] ?? ''));

    return $v !== '' ? $v : $fallback;
};
$company = (isset($company) && is_object($company)) ? $company : ((isset($data) && is_object($data)) ? $data : null);
$locale = isset($landingLocale) ? (string) $landingLocale : 'es';
$langQ = '?lang=' . rawurlencode($locale);
$categories = (isset($categoryList) && is_array($categoryList)) ? $categoryList : [];
$products = (isset($productList) && is_array($productList)) ? $productList : [];
$activeCat = (int) ($activeCategoryId ?? 0);
$productTotal = (int) ($productTotal ?? count($products));
$slug = is_object($company) ? (string) ($company->slug_url ?? '') : '';
$companyId = is_object($company) ? (int) ($company->company_id ?? 0) : 0;
$detailsBase = $slug !== '' ? base_url('marketplaces/details/' . rawurlencode($slug) . $langQ) : base_url('marketplaces' . $langQ);

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

$locationParts = [];
if (is_object($company)) {
    foreach (['city', 'state', 'country'] as $locKey) {
        $v = trim((string) ($company->{$locKey} ?? ''));
        if ($v !== '') {
            $locationParts[] = $v;
        }
    }
}
$location = implode(', ', $locationParts);
$tagline = '';
if (is_object($company)) {
    $short = trim((string) ($company->short_name ?? ''));
    $name = trim((string) ($company->name ?? ''));
    if ($short !== '' && strcasecmp($short, $name) !== 0) {
        $tagline = $short;
    }
}
if ($tagline === '') {
    $tagline = $t('store_default_tagline');
}
$logo = is_object($company) && function_exists('company_logo_url')
    ? company_logo_url((string) ($company->image ?? ''))
    : base_url('uploads/noimg.png');
$promoImg = $products !== [] ? $productImg($products[0]) : base_url('frontend/img/marketplace/promo-deals.jpg');
$catIcons = ['fa-th-large', 'fa-plug', 'fa-cutlery', 'fa-home', 'fa-lightbulb-o', 'fa-wrench', 'fa-bath', 'fa-cube', 'fa-snowflake-o', 'fa-ellipsis-h'];
?>
<main class="fv-sf fv-sf-store" id="fvStore">
  <?php if (! $company) : ?>
    <section class="fv-sf-section">
      <div class="fv-sf-wrap">
        <div class="fv-sf-empty"><?= htmlspecialchars($t('company_not_found'), ENT_QUOTES, 'UTF-8') ?></div>
      </div>
    </section>
  <?php else : ?>

  <div class="fv-sf-wrap">
    <nav class="fv-sf-breadcrumb" aria-label="breadcrumb">
      <a href="<?= htmlspecialchars(base_url('marketplaces' . $langQ), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('breadcrumb_marketplaces'), ENT_QUOTES, 'UTF-8') ?></a>
      <span aria-hidden="true">›</span>
      <span><?= htmlspecialchars((string) ($company->name ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
    </nav>
  </div>

  <section class="fv-sf-store-hero">
    <div class="fv-sf-store-hero__bg" aria-hidden="true">
      <img src="<?= htmlspecialchars(base_url('frontend/img/marketplace/promo-business.jpg'), ENT_QUOTES, 'UTF-8') ?>" alt="">
    </div>
    <div class="fv-sf-wrap fv-sf-store-hero__inner">
      <div class="fv-sf-store-hero__profile">
        <span class="fv-sf-store-hero__logo">
          <img src="<?= htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') ?>" alt="" width="88" height="88">
        </span>
        <div>
          <h1>
            <?= htmlspecialchars((string) ($company->name ?? ''), ENT_QUOTES, 'UTF-8') ?>
            <i class="fa fa-check-circle fv-sf-store-hero__verified" title="<?= htmlspecialchars($t('store_verified'), ENT_QUOTES, 'UTF-8') ?>" aria-label="<?= htmlspecialchars($t('store_verified'), ENT_QUOTES, 'UTF-8') ?>"></i>
          </h1>
          <p class="fv-sf-store-hero__tagline"><?= htmlspecialchars($tagline, ENT_QUOTES, 'UTF-8') ?></p>
          <ul class="fv-sf-store-hero__meta">
            <?php if (! empty($company->phone_number)) : ?>
              <li><i class="fa fa-phone" aria-hidden="true"></i><?= htmlspecialchars((string) $company->phone_number, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endif; ?>
            <?php if (! empty($company->email)) : ?>
              <li><i class="fa fa-envelope" aria-hidden="true"></i><?= htmlspecialchars((string) $company->email, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endif; ?>
            <?php if ($location !== '') : ?>
              <li><i class="fa fa-map-marker" aria-hidden="true"></i><?= htmlspecialchars($location, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
      <ul class="fv-sf-store-hero__trust">
        <li><i class="fa fa-truck" aria-hidden="true"></i><span><?= htmlspecialchars($t('store_trust_ship'), ENT_QUOTES, 'UTF-8') ?></span></li>
        <li><i class="fa fa-shield" aria-hidden="true"></i><span><?= htmlspecialchars($t('store_trust_quality'), ENT_QUOTES, 'UTF-8') ?></span></li>
        <li><i class="fa fa-headphones" aria-hidden="true"></i><span><?= htmlspecialchars($t('store_trust_support'), ENT_QUOTES, 'UTF-8') ?></span></li>
        <li><i class="fa fa-check" aria-hidden="true"></i><span><?= htmlspecialchars($t('store_trust_verified'), ENT_QUOTES, 'UTF-8') ?></span></li>
      </ul>
    </div>
  </section>

  <div class="fv-sf-store-tabsbar">
    <div class="fv-sf-wrap fv-sf-store-tabsbar__inner">
      <div class="fv-sf-store-tabs" role="tablist">
        <a class="is-active" href="#store-products" data-store-tab="products"><?= htmlspecialchars($t('store_tab_products'), ENT_QUOTES, 'UTF-8') ?></a>
        <a href="#store-info" data-store-tab="info"><?= htmlspecialchars($t('store_tab_info'), ENT_QUOTES, 'UTF-8') ?></a>
        <a href="#store-contact" data-store-tab="contact"><?= htmlspecialchars($t('store_tab_contact'), ENT_QUOTES, 'UTF-8') ?></a>
      </div>
      <form class="fv-sf-store-search" action="#" method="get" role="search" onsubmit="return false;">
        <label class="sr-only" for="fvStoreSearch"><?= htmlspecialchars($t('store_search_label'), ENT_QUOTES, 'UTF-8') ?></label>
        <input id="fvStoreSearch" type="search" placeholder="<?= htmlspecialchars($t('store_search_placeholder'), ENT_QUOTES, 'UTF-8') ?>" autocomplete="off" data-store-filter>
        <button type="button" aria-label="<?= htmlspecialchars($t('search_button'), ENT_QUOTES, 'UTF-8') ?>"><i class="fa fa-search" aria-hidden="true"></i></button>
      </form>
    </div>
  </div>

  <section class="fv-sf-section" id="store-products">
    <div class="fv-sf-wrap fv-sf-store-layout">
      <aside class="fv-sf-store-cats">
        <h2><?= htmlspecialchars($t('categories_title'), ENT_QUOTES, 'UTF-8') ?></h2>
        <?php if ($categories === []) : ?>
          <p class="fv-sf-store-cats__empty"><?= htmlspecialchars($t('categories_empty'), ENT_QUOTES, 'UTF-8') ?></p>
        <?php else : ?>
          <ul>
            <?php foreach ($categories as $i => $cat) :
                $cid = (int) ($cat->cat_id ?? $cat->category_id ?? 0);
                $cname = (string) ($cat->category_name ?? $cat->name ?? '');
                $isActive = ($activeCat === $cid) || ($activeCat < 1 && $i === 0);
                $href = $detailsBase . ($cid > 0 ? '&cat=' . $cid : '');
                $icon = $catIcons[$i % count($catIcons)];
                ?>
              <li>
                <a class="<?= $isActive ? 'is-active' : '' ?>"
                   href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"
                   data-store-cat="<?= $cid ?>"
                   onclick="event.preventDefault(); productSearch(<?= $cid ?>, <?= $companyId ?>); document.querySelectorAll('.fv-sf-store-cats a').forEach(function(a){a.classList.remove('is-active')}); this.classList.add('is-active'); history.replaceState(null,'','<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>');">
                  <i class="fa <?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
                  <?= htmlspecialchars($cname, ENT_QUOTES, 'UTF-8') ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </aside>

      <div class="fv-sf-store-main">
        <article class="fv-sf-store-banner">
          <div class="fv-sf-store-banner__copy">
            <h3><?= htmlspecialchars($t('store_banner_title'), ENT_QUOTES, 'UTF-8') ?></h3>
            <p><?= htmlspecialchars($t('store_banner_lead'), ENT_QUOTES, 'UTF-8') ?></p>
            <a class="fv-sf-btn fv-sf-btn--primary" href="#store-catalog"><?= htmlspecialchars($t('store_banner_cta'), ENT_QUOTES, 'UTF-8') ?> →</a>
          </div>
          <img src="<?= htmlspecialchars($promoImg, ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy" width="420" height="220">
        </article>

        <div class="fv-sf-store-catalog" id="store-catalog">
          <div class="fv-sf-store-catalog__head">
            <div>
              <h2><?= htmlspecialchars($t('store_products_title'), ENT_QUOTES, 'UTF-8') ?></h2>
              <p><?= htmlspecialchars($t('store_products_lead'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
          </div>
          <div class="fv-sf-store-grid productfilter_data">
            <?php
            $this->load->view('marketplace/partials/product_cards', [
                'productList' => $products,
                'mp' => $mp,
                'company' => $company,
                'storeMode' => true,
            ]);
            ?>
          </div>
          <?php if (! empty($paginate)) : ?>
            <nav class="fv-sf-pager" aria-label="<?= htmlspecialchars($t('pagination_label'), ENT_QUOTES, 'UTF-8') ?>"><?= $paginate ?></nav>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <section class="fv-sf-section fv-sf-section--fog" id="store-info">
    <div class="fv-sf-wrap">
      <h2><?= htmlspecialchars($t('store_tab_info'), ENT_QUOTES, 'UTF-8') ?></h2>
      <div class="fv-sf-store-info">
        <p><strong><?= htmlspecialchars((string) ($company->name ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></p>
        <?php if (! empty($company->website)) : ?>
          <p><a href="<?= htmlspecialchars((string) $company->website, ENT_QUOTES, 'UTF-8') ?>" rel="noopener noreferrer" target="_blank"><?= htmlspecialchars((string) $company->website, ENT_QUOTES, 'UTF-8') ?></a></p>
        <?php endif; ?>
        <?php if (! empty($company->address)) : ?>
          <p><?= htmlspecialchars((string) $company->address, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <?php if ($location !== '') : ?>
          <p><?= htmlspecialchars($location, ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <section class="fv-sf-section" id="store-contact">
    <div class="fv-sf-wrap">
      <h2><?= htmlspecialchars($t('store_tab_contact'), ENT_QUOTES, 'UTF-8') ?></h2>
      <ul class="fv-sf-store-contact">
        <?php if (! empty($company->phone_number)) : ?>
          <li><i class="fa fa-phone" aria-hidden="true"></i> <?= htmlspecialchars((string) $company->phone_number, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endif; ?>
        <?php if (! empty($company->email)) : ?>
          <li><i class="fa fa-envelope" aria-hidden="true"></i> <?= htmlspecialchars((string) $company->email, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endif; ?>
        <?php if ($location !== '') : ?>
          <li><i class="fa fa-map-marker" aria-hidden="true"></i> <?= htmlspecialchars($location, ENT_QUOTES, 'UTF-8') ?></li>
        <?php endif; ?>
      </ul>
    </div>
  </section>

  <?php endif; ?>
</main>
<script>
function productSearch(categoryId, companyId) {
  var grid = document.querySelector('.productfilter_data');
  if (!grid) { return; }
  grid.classList.add('is-loading');
  var body = new URLSearchParams();
  body.set('categoryId', categoryId);
  body.set('companyId', companyId);
  body.set('companyName', <?= json_encode((string) ($company->name ?? ''), JSON_UNESCAPED_UNICODE) ?>);
  body.set('companySlug', <?= json_encode((string) ($company->slug_url ?? ''), JSON_UNESCAPED_UNICODE) ?>);
  body.set('companyImage', <?= json_encode((string) ($company->image ?? ''), JSON_UNESCAPED_UNICODE) ?>);
  body.set('storeMode', '1');
  fetch('<?= base_url('marketplaces/productfilterSearchbyCategory') ?>', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', 'X-Requested-With': 'XMLHttpRequest' },
    body: body.toString()
  }).then(function (r) { return r.json(); }).then(function (data) {
    if (data && data.product_list) {
      grid.innerHTML = data.product_list;
    }
  }).catch(function () {}).finally(function () {
    grid.classList.remove('is-loading');
  });
}
(function () {
  var input = document.querySelector('[data-store-filter]');
  if (!input) return;
  input.addEventListener('input', function () {
    var q = (input.value || '').toLowerCase().trim();
    document.querySelectorAll('.productfilter_data .fv-sf-card').forEach(function (card) {
      var name = (card.getAttribute('data-name') || card.textContent || '').toLowerCase();
      card.style.display = (!q || name.indexOf(q) !== -1) ? '' : 'none';
    });
  });
})();
</script>
<style>.sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);border:0}</style>

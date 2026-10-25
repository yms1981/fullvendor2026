<?php
/**
 * Storefront Marketplace — tema oscuro mockup + datos reales.
 *
 * @var array $mp
 * @var array $companyList
 * @var array $companyPropositions
 * @var array $categoryCarousel
 * @var array $promoProducts
 * @var array $featuredProducts
 * @var string $paginate
 * @var string $landingLocale
 */
$mp = (isset($mp) && is_array($mp)) ? $mp : [];
$t = static function (string $key, string $fallback = '') use ($mp): string {
    $v = trim((string) ($mp[$key] ?? ''));

    return $v !== '' ? $v : $fallback;
};
$locale = isset($landingLocale) ? (string) $landingLocale : 'es';
$langQ = '?lang=' . rawurlencode($locale);
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
$categoryCover = static function ($cat): string {
    $uuid = trim((string) ($cat->image_uuid ?? $cat->cover_image_uuid ?? ''));
    if ($uuid !== '' && function_exists('company_product_image_url')) {
        return company_product_image_url($uuid, 0);
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

$companies = (isset($companyList) && is_array($companyList)) ? $companyList : [];
$props = (isset($companyPropositions) && is_array($companyPropositions)) ? $companyPropositions : [];
$categoryCarousel = (isset($categoryCarousel) && is_array($categoryCarousel)) ? $categoryCarousel : [];
$promos = (isset($promoProducts) && is_array($promoProducts)) ? $promoProducts : [];
$featured = (isset($featuredProducts) && is_array($featuredProducts)) ? $featuredProducts : [];
$promoRight = $promos[0] ?? ($featured[0] ?? null);
$companyHref = function_exists('company_url') ? company_url() : base_url('company');
$heroTitle = (string) $t('hero_title');
$heroHighlight = (string) $t('hero_title_highlight');
if ($heroHighlight !== '' && str_contains($heroTitle, $heroHighlight)) {
    $heroTitleHtml = htmlspecialchars(str_replace($heroHighlight, '[[H]]', $heroTitle), ENT_QUOTES, 'UTF-8');
    $heroTitleHtml = str_replace('[[H]]', '<em>' . htmlspecialchars($heroHighlight, ENT_QUOTES, 'UTF-8') . '</em>', $heroTitleHtml);
} else {
    $heroTitleHtml = htmlspecialchars($heroTitle, ENT_QUOTES, 'UTF-8');
}
?>
<main class="fv-sf" id="fvMarketplace">
  <section class="fv-sf-hero">
    <div class="fv-sf-wrap fv-sf-hero__grid">
      <div class="fv-sf-hero__copy">
        <p class="fv-sf-hero__kicker"><?= htmlspecialchars($t('hero_kicker'), ENT_QUOTES, 'UTF-8') ?></p>
        <h1><?= $heroTitleHtml ?></h1>
        <p class="fv-sf-hero__lead"><?= htmlspecialchars($t('hero_lead'), ENT_QUOTES, 'UTF-8') ?></p>
        <div class="fv-sf-hero__actions">
          <a class="fv-sf-btn fv-sf-btn--primary" href="#productos"><?= htmlspecialchars($t('cta_explore'), ENT_QUOTES, 'UTF-8') ?> →</a>
          <a class="fv-sf-btn fv-sf-btn--ghost" href="<?= htmlspecialchars(base_url('?lang=' . rawurlencode($locale)), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('cta_about'), ENT_QUOTES, 'UTF-8') ?></a>
        </div>
      </div>
      <div class="fv-sf-hero__stage" aria-hidden="true">
        <div class="fv-sf-hero__glow"></div>
        <img class="fv-sf-hero__collage"
             src="<?= htmlspecialchars(base_url('frontend/img/marketplace/hero-products.jpg'), ENT_QUOTES, 'UTF-8') ?>"
             alt=""
             width="720"
             height="420"
             loading="eager">
      </div>
      <ul class="fv-sf-hero__values">
        <li><i class="fa fa-truck" aria-hidden="true"></i><span><?= htmlspecialchars($t('value_ship_title'), ENT_QUOTES, 'UTF-8') ?></span></li>
        <li><i class="fa fa-shield" aria-hidden="true"></i><span><?= htmlspecialchars($t('value_trust_title'), ENT_QUOTES, 'UTF-8') ?></span></li>
        <li><i class="fa fa-building" aria-hidden="true"></i><span><?= htmlspecialchars($t('value_local_title'), ENT_QUOTES, 'UTF-8') ?></span></li>
        <li><i class="fa fa-headphones" aria-hidden="true"></i><span><?= htmlspecialchars($t('value_support_title'), ENT_QUOTES, 'UTF-8') ?></span></li>
      </ul>
    </div>
    <div class="fv-sf-hero__dots" aria-hidden="true"><span class="is-active"></span><span></span><span></span></div>
  </section>

  <section class="fv-sf-section" id="categorias">
    <div class="fv-sf-wrap">
      <div class="fv-sf-section__head">
        <div>
          <h2><?= htmlspecialchars($t('categories_title'), ENT_QUOTES, 'UTF-8') ?></h2>
          <p><?= htmlspecialchars($t('categories_carousel_lead'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <?php if (count($categoryCarousel) > 1) : ?>
        <div class="fv-mp-carousel__nav" data-mp-carousel-nav="categories">
          <button type="button" class="fv-mp-carousel__btn" data-mp-carousel-prev aria-label="<?= htmlspecialchars($t('carousel_prev'), ENT_QUOTES, 'UTF-8') ?>"><i class="fa fa-chevron-left" aria-hidden="true"></i></button>
          <button type="button" class="fv-mp-carousel__btn" data-mp-carousel-next aria-label="<?= htmlspecialchars($t('carousel_next'), ENT_QUOTES, 'UTF-8') ?>"><i class="fa fa-chevron-right" aria-hidden="true"></i></button>
        </div>
        <?php endif; ?>
      </div>
      <?php if ($categoryCarousel === []) : ?>
        <div class="fv-sf-empty"><?= htmlspecialchars($t('categories_empty'), ENT_QUOTES, 'UTF-8') ?></div>
      <?php else : ?>
        <div class="fv-mp-carousel fv-sf-catcarousel" data-mp-carousel="categories">
          <div class="fv-mp-carousel__track" data-mp-carousel-track>
            <?php foreach ($categoryCarousel as $cat) :
                $catName = (string) ($cat->category_name ?? '');
                $catId = (int) ($cat->cat_id ?? 0);
                $cslug = (string) ($cat->company_slug ?? '');
                $cname = (string) ($cat->company_name ?? '');
                $href = $cslug !== ''
                    ? base_url('marketplaces/details/' . rawurlencode($cslug)) . $langQ . '&cat=' . $catId
                    : '#empresas';
                ?>
              <a class="fv-mp-carousel__item fv-mp-catslide" href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars(trim($catName . ' · ' . $cname), ENT_QUOTES, 'UTF-8') ?>">
                <span class="fv-mp-catslide__media">
                  <img src="<?= htmlspecialchars($categoryCover($cat), ENT_QUOTES, 'UTF-8') ?>" alt="" width="72" height="72" loading="lazy" decoding="async">
                </span>
                <span class="fv-mp-catslide__body">
                  <strong><?= htmlspecialchars($catName, ENT_QUOTES, 'UTF-8') ?></strong>
                  <?php if ($cname !== '') : ?><em><?= htmlspecialchars($cname, ENT_QUOTES, 'UTF-8') ?></em><?php endif; ?>
                </span>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <section class="fv-sf-section fv-sf-section--tight" id="promos">
    <div class="fv-sf-wrap fv-sf-promos">
      <article class="fv-sf-promo fv-sf-promo--dark">
        <img src="<?= htmlspecialchars(base_url('frontend/img/marketplace/promo-business.jpg'), ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy" width="700" height="320">
        <div class="fv-sf-promo__body">
          <h3><?= htmlspecialchars($t('promo_seller_title'), ENT_QUOTES, 'UTF-8') ?></h3>
          <p><?= htmlspecialchars($t('promo_seller_body'), ENT_QUOTES, 'UTF-8') ?></p>
          <ul>
            <li><?= htmlspecialchars($t('promo_seller_b1'), ENT_QUOTES, 'UTF-8') ?></li>
            <li><?= htmlspecialchars($t('promo_seller_b2'), ENT_QUOTES, 'UTF-8') ?></li>
            <li><?= htmlspecialchars($t('promo_seller_b3'), ENT_QUOTES, 'UTF-8') ?></li>
          </ul>
          <a class="fv-sf-btn fv-sf-btn--primary" href="<?= htmlspecialchars($companyHref, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('promo_seller_cta'), ENT_QUOTES, 'UTF-8') ?> →</a>
        </div>
      </article>
      <?php
      $dealsHref = '#productos';
      $dealsCompany = '';
      $dealsImg = base_url('frontend/img/marketplace/promo-deals.jpg');
      if (is_object($promoRight)) {
          $pSlug = (string) ($promoRight->company_slug ?? '');
          $dealsCompany = (string) ($promoRight->company_name ?? '');
          $dealsHref = $pSlug !== '' ? base_url('marketplaces/details/' . rawurlencode($pSlug) . $langQ) : '#productos';
          $dealsImg = $productImg($promoRight);
      }
      ?>
      <a class="fv-sf-promo fv-sf-promo--light" href="<?= htmlspecialchars($dealsHref, ENT_QUOTES, 'UTF-8') ?>">
        <img src="<?= htmlspecialchars($dealsImg, ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy" width="700" height="320">
        <div class="fv-sf-promo__body">
          <?php if ($dealsCompany !== '') : ?>
            <p class="fv-sf-promo__company"><?= htmlspecialchars($dealsCompany, ENT_QUOTES, 'UTF-8') ?></p>
          <?php endif; ?>
          <h3><?= htmlspecialchars($t('promo_deals_title'), ENT_QUOTES, 'UTF-8') ?></h3>
          <p><?= htmlspecialchars($t('promo_deals_body'), ENT_QUOTES, 'UTF-8') ?></p>
          <span class="fv-sf-btn fv-sf-btn--primary"><?= htmlspecialchars($t('promo_deals_cta'), ENT_QUOTES, 'UTF-8') ?> →</span>
        </div>
      </a>
    </div>
  </section>

  <section class="fv-sf-section" id="empresas">
    <div class="fv-sf-wrap">
      <div class="fv-sf-section__head">
        <div>
          <h2><?= htmlspecialchars($t('companies_title'), ENT_QUOTES, 'UTF-8') ?></h2>
          <p><?= htmlspecialchars($t('companies_lead'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <a class="fv-sf-section__link" href="#empresas"><?= htmlspecialchars($t('view_all_companies'), ENT_QUOTES, 'UTF-8') ?> →</a>
      </div>
      <?php if ($companies === []) : ?>
        <div class="fv-sf-empty"><?= htmlspecialchars($t('companies_empty'), ENT_QUOTES, 'UTF-8') ?></div>
      <?php else : ?>
        <div class="fv-sf-companies">
          <?php foreach ($companies as $clist) :
              $name = (string) ($clist->name ?? '');
              $cat = (string) ($clist->marketplace_category_name ?? '');
              $slug = (string) ($clist->slug_url ?? '');
              $logo = function_exists('company_logo_url') ? company_logo_url((string) ($clist->image ?? '')) : base_url('uploads/noimg.png');
              ?>
            <a class="fv-sf-company" href="<?= htmlspecialchars(base_url('marketplaces/details/' . rawurlencode($slug) . $langQ), ENT_QUOTES, 'UTF-8') ?>">
              <span class="fv-sf-company__logo"><img src="<?= htmlspecialchars($logo, ENT_QUOTES, 'UTF-8') ?>" alt="" width="72" height="72" loading="lazy"></span>
              <strong><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></strong>
              <em><?= htmlspecialchars($cat !== '' ? $cat : $t('company_default_category'), ENT_QUOTES, 'UTF-8') ?></em>
              <span class="fv-sf-company__cta"><?= htmlspecialchars($t('view_store'), ENT_QUOTES, 'UTF-8') ?></span>
            </a>
          <?php endforeach; ?>
        </div>
        <?php if (! empty($paginate)) : ?>
          <nav class="fv-sf-pager" aria-label="<?= htmlspecialchars($t('pagination_label'), ENT_QUOTES, 'UTF-8') ?>"><?= $paginate ?></nav>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </section>

  <?php
  $hasProps = false;
  foreach ($props as $block) {
      if (! empty($block['products'])) {
          $hasProps = true;
          break;
      }
  }
  if ($hasProps) :
      ?>
  <section class="fv-sf-section" id="propositions">
    <div class="fv-sf-wrap">
      <div class="fv-sf-section__head">
        <div>
          <h2><?= htmlspecialchars($t('propositions_title'), ENT_QUOTES, 'UTF-8') ?></h2>
          <p><?= htmlspecialchars($t('propositions_lead'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
      </div>
      <?php foreach ($props as $block) :
          $company = $block['company'] ?? null;
          $products = (isset($block['products']) && is_array($block['products'])) ? $block['products'] : [];
          if (! is_object($company) || $products === []) {
              continue;
          }
          $cname = (string) ($company->name ?? '');
          $cslug = (string) ($company->slug_url ?? '');
          $clogo = function_exists('company_logo_url') ? company_logo_url((string) ($company->image ?? '')) : base_url('uploads/noimg.png');
          ?>
        <article class="fv-sf-prop">
          <div class="fv-sf-prop__head">
            <div class="fv-sf-prop__brand">
              <img src="<?= htmlspecialchars($clogo, ENT_QUOTES, 'UTF-8') ?>" alt="" width="40" height="40" loading="lazy">
              <div>
                <h3><?= htmlspecialchars($cname, ENT_QUOTES, 'UTF-8') ?></h3>
                <span><?= htmlspecialchars($t('view_catalog'), ENT_QUOTES, 'UTF-8') ?></span>
              </div>
            </div>
            <a class="fv-sf-btn fv-sf-btn--ghost-dark" href="<?= htmlspecialchars(base_url('marketplaces/details/' . rawurlencode($cslug) . $langQ), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('view_store'), ENT_QUOTES, 'UTF-8') ?></a>
          </div>
          <div class="fv-sf-grid">
            <?php foreach ($products as $pi => $p) :
                $pname = (string) ($p->name ?? '');
                ?>
              <a class="fv-sf-card" href="<?= htmlspecialchars(base_url('marketplaces/details/' . rawurlencode($cslug) . $langQ), ENT_QUOTES, 'UTF-8') ?>">
                <?php if ($pi === 0) : ?>
                  <span class="fv-sf-card__badge"><?= htmlspecialchars($t('badge_new'), ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                <span class="fv-sf-card__fav" aria-hidden="true"><i class="fa fa-heart-o"></i></span>
                <span class="fv-sf-card__media"><img src="<?= htmlspecialchars($productImg($p), ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy" width="280" height="280"></span>
                <span class="fv-sf-card__body">
                  <strong><?= htmlspecialchars($pname, ENT_QUOTES, 'UTF-8') ?></strong>
                  <span class="fv-sf-card__price"><?= htmlspecialchars($formatPrice($p), ENT_QUOTES, 'UTF-8') ?></span>
                  <span class="fv-sf-card__company-chip">
                    <img src="<?= htmlspecialchars($clogo, ENT_QUOTES, 'UTF-8') ?>" alt="" width="20" height="20" loading="lazy">
                    <?= htmlspecialchars($cname, ENT_QUOTES, 'UTF-8') ?>
                  </span>
                </span>
              </a>
            <?php endforeach; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <section class="fv-sf-section fv-sf-section--fog" id="productos">
    <div class="fv-sf-wrap">
      <div class="fv-sf-section__head">
        <div>
          <h2><?= htmlspecialchars($t('featured_title'), ENT_QUOTES, 'UTF-8') ?></h2>
          <div class="fv-sf-tabs" style="margin-top:0.65rem;">
            <span class="is-active"><?= htmlspecialchars($t('tab_all'), ENT_QUOTES, 'UTF-8') ?></span>
            <a href="#productos"><?= htmlspecialchars($t('tab_offers'), ENT_QUOTES, 'UTF-8') ?></a>
            <a href="#productos"><?= htmlspecialchars($t('tab_bestsellers'), ENT_QUOTES, 'UTF-8') ?></a>
            <a href="#productos"><?= htmlspecialchars($t('tab_new'), ENT_QUOTES, 'UTF-8') ?></a>
          </div>
        </div>
      </div>
      <?php if ($featured === []) : ?>
        <div class="fv-sf-empty"><?= htmlspecialchars($t('products_empty'), ENT_QUOTES, 'UTF-8') ?></div>
      <?php else : ?>
        <div class="fv-sf-grid">
          <?php foreach ($featured as $fi => $p) :
              $pname = (string) ($p->name ?? '');
              $pcompany = (string) ($p->company_name ?? '');
              $pslug = (string) ($p->company_slug ?? '');
              $clogo = function_exists('company_logo_url') ? company_logo_url((string) ($p->company_image ?? '')) : base_url('uploads/noimg.png');
              $href = $pslug !== '' ? base_url('marketplaces/details/' . rawurlencode($pslug) . $langQ) : '#';
              ?>
            <a class="fv-sf-card" href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>">
              <?php if ($fi < 2) : ?>
                <span class="fv-sf-card__badge"><?= htmlspecialchars($t('badge_new'), ENT_QUOTES, 'UTF-8') ?></span>
              <?php endif; ?>
              <span class="fv-sf-card__fav" aria-hidden="true"><i class="fa fa-heart-o"></i></span>
              <span class="fv-sf-card__media"><img src="<?= htmlspecialchars($productImg($p), ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy" width="280" height="280"></span>
              <span class="fv-sf-card__body">
                <strong><?= htmlspecialchars($pname, ENT_QUOTES, 'UTF-8') ?></strong>
                <span class="fv-sf-card__price"><?= htmlspecialchars($formatPrice($p), ENT_QUOTES, 'UTF-8') ?></span>
                <span class="fv-sf-card__company-chip">
                  <img src="<?= htmlspecialchars($clogo, ENT_QUOTES, 'UTF-8') ?>" alt="" width="20" height="20" loading="lazy">
                  <?= htmlspecialchars($pcompany, ENT_QUOTES, 'UTF-8') ?>
                </span>
              </span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </section>
</main>

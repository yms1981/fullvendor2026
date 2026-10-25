<?php
/**
 * Landing pública Fullvendor (raíz del proyecto).
 * Textos desde api/lang (sección public-landing; origen fullvendor.com).
 *
 * @var array $landing
 * @var object|null $setting
 */
$L = (isset($landing) && is_array($landing)) ? $landing : [];
$t = static function (string $key, string $fallback = '') use ($L): string {
    $v = trim((string) ($L[$key] ?? ''));

    return $v !== '' ? $v : $fallback;
};
$img = static function (string $file) {
    return base_url('frontend/img/landing/' . ltrim($file, '/'));
};
$features = [
    ['img' => 'feature-catalog.png', 'title' => 'feat_catalog_title', 'body' => 'feat_catalog_body'],
    ['img' => 'feature-sales-app.png', 'title' => 'feat_sales_title', 'body' => 'feat_sales_body'],
    ['img' => 'feature-warehouse.png', 'title' => 'feat_warehouse_title', 'body' => 'feat_warehouse_body'],
    ['img' => 'feature-dashboard.png', 'title' => 'feat_dashboard_title', 'body' => 'feat_dashboard_body'],
    ['img' => 'feature-marketplace.png', 'title' => 'feat_marketplace_title', 'body' => 'feat_marketplace_body'],
    ['img' => 'feature-catalog.png', 'title' => 'feat_ecommerce_title', 'body' => 'feat_ecommerce_body'],
    ['img' => 'feature-warehouse.png', 'title' => 'feat_inventory_title', 'body' => 'feat_inventory_body'],
    ['img' => 'feature-sales-app.png', 'title' => 'feat_orders_title', 'body' => 'feat_orders_body'],
    ['img' => 'feature-dashboard.png', 'title' => 'feat_multichannel_title', 'body' => 'feat_multichannel_body'],
];
$benefits = [
    ['ben_1_title', 'ben_1_body'],
    ['ben_2_title', 'ben_2_body'],
    ['ben_3_title', 'ben_3_body'],
    ['ben_4_title', 'ben_4_body'],
    ['ben_5_title', 'ben_5_body'],
    ['ben_6_title', 'ben_6_body'],
    ['ben_7_title', 'ben_7_body'],
];
$whatsapp = '';
if (isset($setting) && is_object($setting) && ! empty($setting->phone)) {
    $digits = preg_replace('/\D+/', '', (string) $setting->phone);
    if ($digits !== '') {
        $whatsapp = 'https://wa.me/' . $digits;
    }
}
if ($whatsapp === '') {
    $whatsapp = 'https://www.fullvendor.com/';
}
$playUrl = 'https://play.google.com/store/apps/details?id=com.application.fullvendor';
$orbitItems = [
    ['key' => 'orbit_sales', 'icon' => 'fa-mobile'],
    ['key' => 'orbit_dashboard', 'icon' => 'fa-sliders'],
    ['key' => 'orbit_warehouse', 'icon' => 'fa-building'],
    ['key' => 'orbit_catalog', 'icon' => 'fa-th-large'],
    ['key' => 'orbit_ecommerce', 'icon' => 'fa-shopping-cart'],
    ['key' => 'orbit_marketplace', 'icon' => 'fa-shopping-bag'],
    ['key' => 'orbit_inventory', 'icon' => 'fa-cubes'],
];
$orbitCount = count($orbitItems);
?>
<main class="fv-landing">
  <!-- HERO -->
  <section class="fv-hero" id="inicio">
    <div class="fv-hero__media" aria-hidden="true">
      <img class="fv-hero__bg" src="<?= htmlspecialchars($img('hero-atmosphere.png'), ENT_QUOTES, 'UTF-8') ?>" alt="">
      <div class="fv-hero__veil"></div>
    </div>
    <div class="fv-hero__grid">
      <div class="fv-hero__content">
        <p class="fv-hero__eyebrow fv-reveal"><?= htmlspecialchars($t('hero_eyebrow'), ENT_QUOTES, 'UTF-8') ?></p>
        <h1 class="fv-hero__brand fv-reveal"><?= htmlspecialchars($t('hero_brand', 'FULLVENDOR'), ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="fv-hero__headline fv-reveal"><?= htmlspecialchars($t('hero_headline'), ENT_QUOTES, 'UTF-8') ?></p>
        <p class="fv-hero__sub fv-reveal"><?= htmlspecialchars($t('hero_sub'), ENT_QUOTES, 'UTF-8') ?></p>
        <div class="fv-hero__actions fv-reveal">
          <a class="fv-btn fv-btn--primary" href="#contacto"><?= htmlspecialchars($t('hero_cta_primary'), ENT_QUOTES, 'UTF-8') ?></a>
          <a class="fv-btn fv-btn--ghost" href="#funciones"><?= htmlspecialchars($t('hero_cta_secondary'), ENT_QUOTES, 'UTF-8') ?></a>
        </div>
      </div>

        <div class="fv-orbit fv-reveal" style="--d: 2; --orbit-n: <?= (int) $orbitCount ?>" aria-label="<?= htmlspecialchars($t('orbit_aria'), ENT_QUOTES, 'UTF-8') ?>">
        <div class="fv-orbit__core">
          <img src="<?= base_url('frontend/img/landing/logo-mark-f.png') ?>"
               alt="Fullvendor"
               width="140"
               height="140"
               class="fv-orbit__mark">
        </div>
        <div class="fv-orbit__ring" data-fv-orbit-ring>
          <?php foreach ($orbitItems as $i => $item) : ?>
            <div class="fv-orbit__item" style="--i: <?= (int) $i ?>; --n: <?= (int) $orbitCount ?>;">
              <div class="fv-orbit__card" data-fv-orbit-card>
                <span class="fv-orbit__icon" aria-hidden="true"><i class="fa <?= htmlspecialchars($item['icon'], ENT_QUOTES, 'UTF-8') ?>"></i></span>
                <span class="fv-orbit__label"><?= htmlspecialchars($t($item['key']), ENT_QUOTES, 'UTF-8') ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div class="fv-hero__scroll" aria-hidden="true"><span></span></div>
  </section>

  <!-- PAIN -->
  <section class="fv-section fv-pain" id="que-es">
    <div class="fv-wrap">
      <h2 class="fv-section__title fv-reveal"><?= htmlspecialchars($t('pain_title'), ENT_QUOTES, 'UTF-8') ?></h2>
      <div class="fv-pain__grid">
        <article class="fv-pain__card fv-reveal"><p><?= htmlspecialchars($t('pain_1'), ENT_QUOTES, 'UTF-8') ?></p></article>
        <article class="fv-pain__card fv-reveal"><p><?= htmlspecialchars($t('pain_2'), ENT_QUOTES, 'UTF-8') ?></p></article>
        <article class="fv-pain__card fv-reveal"><p><?= htmlspecialchars($t('pain_3'), ENT_QUOTES, 'UTF-8') ?></p></article>
      </div>
      <p class="fv-pain__solve fv-reveal"><?= htmlspecialchars($t('pain_solve'), ENT_QUOTES, 'UTF-8') ?></p>
      <div class="fv-what fv-reveal">
        <h3><?= htmlspecialchars($t('what_title'), ENT_QUOTES, 'UTF-8') ?></h3>
        <p><?= htmlspecialchars($t('what_body'), ENT_QUOTES, 'UTF-8') ?></p>
      </div>
    </div>
  </section>

  <!-- REEL (presentación animada tipo video) -->
  <section class="fv-section fv-reel" id="presentacion" aria-label="<?= htmlspecialchars($t('reel_title'), ENT_QUOTES, 'UTF-8') ?>">
    <div class="fv-wrap">
      <h2 class="fv-section__title fv-section__title--light fv-reveal"><?= htmlspecialchars($t('reel_title'), ENT_QUOTES, 'UTF-8') ?></h2>
    </div>
    <div class="fv-reel__stage" data-fv-reel>
      <div class="fv-reel__slide is-active" data-caption="<?= htmlspecialchars($t('reel_caption_1'), ENT_QUOTES, 'UTF-8') ?>">
        <img src="<?= htmlspecialchars($img('reel-sales.png'), ENT_QUOTES, 'UTF-8') ?>" alt="">
      </div>
      <div class="fv-reel__slide" data-caption="<?= htmlspecialchars($t('reel_caption_2'), ENT_QUOTES, 'UTF-8') ?>">
        <img src="<?= htmlspecialchars($img('reel-warehouse.png'), ENT_QUOTES, 'UTF-8') ?>" alt="">
      </div>
      <div class="fv-reel__slide" data-caption="<?= htmlspecialchars($t('reel_caption_3'), ENT_QUOTES, 'UTF-8') ?>">
        <img src="<?= htmlspecialchars($img('feature-catalog.png'), ENT_QUOTES, 'UTF-8') ?>" alt="">
      </div>
      <div class="fv-reel__slide" data-caption="<?= htmlspecialchars($t('reel_caption_4'), ENT_QUOTES, 'UTF-8') ?>">
        <img src="<?= htmlspecialchars($img('feature-dashboard.png'), ENT_QUOTES, 'UTF-8') ?>" alt="">
      </div>
      <div class="fv-reel__overlay">
        <p class="fv-reel__caption" data-fv-reel-caption><?= htmlspecialchars($t('reel_caption_1'), ENT_QUOTES, 'UTF-8') ?></p>
        <div class="fv-reel__progress" aria-hidden="true"><i data-fv-reel-progress></i></div>
      </div>
    </div>
  </section>

  <!-- FEATURES / POSTS -->
  <section class="fv-section fv-features" id="funciones">
    <div class="fv-wrap">
      <h2 class="fv-section__title fv-reveal"><?= htmlspecialchars($t('features_title'), ENT_QUOTES, 'UTF-8') ?></h2>
      <p class="fv-section__intro fv-reveal"><?= htmlspecialchars($t('features_intro'), ENT_QUOTES, 'UTF-8') ?></p>
      <div class="fv-posts">
        <?php foreach ($features as $i => $feat) : ?>
          <article class="fv-post fv-reveal" style="--d: <?= (int) $i ?>">
            <div class="fv-post__media">
              <img src="<?= htmlspecialchars($img($feat['img']), ENT_QUOTES, 'UTF-8') ?>"
                   alt="<?= htmlspecialchars($t($feat['title']), ENT_QUOTES, 'UTF-8') ?>"
                   loading="lazy">
            </div>
            <div class="fv-post__body">
              <h3><?= htmlspecialchars($t($feat['title']), ENT_QUOTES, 'UTF-8') ?></h3>
              <p><?= htmlspecialchars($t($feat['body']), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- BENEFITS -->
  <section class="fv-section fv-benefits" id="beneficios">
    <div class="fv-wrap">
      <h2 class="fv-section__title fv-section__title--light fv-reveal"><?= htmlspecialchars($t('benefits_title'), ENT_QUOTES, 'UTF-8') ?></h2>
      <div class="fv-benefits__grid">
        <?php foreach ($benefits as $i => [$tk, $bk]) : ?>
          <article class="fv-benefit fv-reveal" style="--d: <?= (int) $i ?>">
            <span class="fv-benefit__num" aria-hidden="true"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
            <h3><?= htmlspecialchars($t($tk), ENT_QUOTES, 'UTF-8') ?></h3>
            <p><?= htmlspecialchars($t($bk), ENT_QUOTES, 'UTF-8') ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- APPS -->
  <section class="fv-section fv-apps" id="apps">
    <div class="fv-wrap fv-apps__inner fv-reveal">
      <h2 class="fv-section__title"><?= htmlspecialchars($t('apps_title'), ENT_QUOTES, 'UTF-8') ?></h2>
      <div class="fv-apps__badges">
        <a href="<?= htmlspecialchars($playUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
          <img src="<?= base_url('frontend/img/google_play_icon.png') ?>" alt="<?= htmlspecialchars($t('apps_google'), ENT_QUOTES, 'UTF-8') ?>">
        </a>
        <a href="#" aria-label="<?= htmlspecialchars($t('apps_apple'), ENT_QUOTES, 'UTF-8') ?>">
          <img src="<?= base_url('frontend/img/appstore.png') ?>" alt="<?= htmlspecialchars($t('apps_apple'), ENT_QUOTES, 'UTF-8') ?>">
        </a>
      </div>
    </div>
  </section>

  <!-- ACCESS + CTA -->
  <section class="fv-section fv-cta" id="acceso">
    <div class="fv-wrap">
      <div class="fv-cta__grid">
        <div class="fv-cta__access fv-reveal">
          <h2><?= htmlspecialchars($t('access_title'), ENT_QUOTES, 'UTF-8') ?></h2>
          <a class="fv-btn fv-btn--primary" href="<?= base_url('login') ?>"><?= htmlspecialchars($t('nav_login_portal'), ENT_QUOTES, 'UTF-8') ?></a>
          <a class="fv-btn fv-btn--ghost" href="<?= base_url('login') ?>"><?= htmlspecialchars($t('nav_login_seller'), ENT_QUOTES, 'UTF-8') ?></a>
        </div>
        <div class="fv-cta__box fv-reveal" id="contacto">
          <h2><?= htmlspecialchars($t('cta_title'), ENT_QUOTES, 'UTF-8') ?></h2>
          <p><?= htmlspecialchars($t('cta_body'), ENT_QUOTES, 'UTF-8') ?></p>
          <div class="fv-cta__actions">
            <a class="fv-btn fv-btn--primary" href="<?= htmlspecialchars($whatsapp, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($t('cta_whatsapp'), ENT_QUOTES, 'UTF-8') ?></a>
            <?php if (! empty($setting->email)) : ?>
              <a class="fv-btn fv-btn--ghost" href="mailto:<?= htmlspecialchars((string) $setting->email, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('cta_contact'), ENT_QUOTES, 'UTF-8') ?></a>
            <?php else : ?>
              <a class="fv-btn fv-btn--ghost" href="https://www.fullvendor.com/" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($t('cta_contact'), ENT_QUOTES, 'UTF-8') ?></a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </section>
</main>

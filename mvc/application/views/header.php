<?php
/**
 * Header público / landing.
 *
 * @var array $landing
 * @var string $landingLocale
 * @var string $title
 * @var string|null $landingNavPage home|privacy|…
 */
$L = (isset($landing) && is_array($landing)) ? $landing : [];
$t = static function (string $key, string $fallback = '') use ($L): string {
    $v = trim((string) ($L[$key] ?? ''));

    return $v !== '' ? $v : $fallback;
};
$locale = isset($landingLocale) ? (string) $landingLocale : 'es';
$langs = [
    'es' => ['label' => 'Español', 'flag' => 'es'],
    'en' => ['label' => 'English', 'flag' => 'gb'],
    'pt' => ['label' => 'Português', 'flag' => 'pt'],
    'it' => ['label' => 'Italiano', 'flag' => 'it'],
    'de' => ['label' => 'Deutsch', 'flag' => 'de'],
    'ru' => ['label' => 'Русский', 'flag' => 'ru'],
    'zh' => ['label' => '中文', 'flag' => 'cn'],
];
$currentLang = $langs[$locale] ?? $langs['es'];
$isLanding = ! empty($isPublicLanding);
$navPage = isset($landingNavPage) ? (string) $landingNavPage : ($isLanding ? 'home' : '');
$onHome = $navPage === 'home' || ($isLanding && $navPage === '');
$homeUrl = base_url('?lang=' . rawurlencode($locale));
$section = static function (string $hash) use ($onHome, $homeUrl): string {
    return $onHome ? $hash : ($homeUrl . $hash);
};
$companyHref = function_exists('company_url') ? company_url() : base_url('company');
$langSwitchBase = $navPage === 'privacy' ? 'policy' : ($navPage === 'marketplace' ? 'marketplaces' : '');
?>
<nav class="fv-nav<?= $isLanding ? ' fv-nav--landing' : '' ?>" id="fvNav" aria-label="Fullvendor">
  <div class="fv-nav__inner">
    <a class="fv-nav__brand" href="<?= htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8') ?>">
      <?php if ($isLanding) : ?>
        <img src="<?= base_url('frontend/img/landing/logo-on-dark.png') ?>"
             alt="Fullvendor"
             width="180"
             height="32"
             class="fv-nav__logo">
      <?php else : ?>
        <img src="<?= base_url('frontend/logo.png') ?>" alt="Fullvendor" width="160" height="40">
      <?php endif; ?>
    </a>
    <?php if ($isLanding) : ?>
    <button type="button" class="fv-nav__toggle" id="fvNavToggle" aria-expanded="false" aria-controls="fvNavMenu">
      <span></span><span></span><span></span>
    </button>
    <div class="fv-nav__menu" id="fvNavMenu">
      <a class="fv-nav__link" href="<?= htmlspecialchars($section('#que-es'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('nav_what'), ENT_QUOTES, 'UTF-8') ?></a>
      <a class="fv-nav__link" href="<?= htmlspecialchars($section('#funciones'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('nav_features'), ENT_QUOTES, 'UTF-8') ?></a>
      <a class="fv-nav__link" href="<?= htmlspecialchars($section('#beneficios'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('nav_benefits'), ENT_QUOTES, 'UTF-8') ?></a>
      <a class="fv-nav__link" href="<?= htmlspecialchars($section('#acceso'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('nav_access'), ENT_QUOTES, 'UTF-8') ?></a>
      <a class="fv-nav__link" href="<?= htmlspecialchars($section('#contacto'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('nav_contact'), ENT_QUOTES, 'UTF-8') ?></a>
      <a class="fv-nav__link<?= $navPage === 'privacy' ? ' is-active' : '' ?>" href="<?= htmlspecialchars(base_url('policy?lang=' . rawurlencode($locale)), ENT_QUOTES, 'UTF-8') ?>">
        <?= htmlspecialchars($t('nav_privacy', $t('footer_privacy', 'Privacy Policy')), ENT_QUOTES, 'UTF-8') ?>
      </a>
      <a class="fv-nav__link fv-nav__link--company" href="<?= htmlspecialchars($companyHref, ENT_QUOTES, 'UTF-8') ?>">
        <?= htmlspecialchars($t('nav_company', 'Company'), ENT_QUOTES, 'UTF-8') ?>
      </a>

      <div class="fv-lang" id="fvLang">
        <button type="button"
                class="fv-lang__btn"
                id="fvLangBtn"
                aria-expanded="false"
                aria-haspopup="listbox"
                aria-label="<?= htmlspecialchars($t('lang_label', 'Language'), ENT_QUOTES, 'UTF-8') ?>">
          <img class="fv-lang__flag"
               src="https://flagcdn.com/w40/<?= htmlspecialchars($currentLang['flag'], ENT_QUOTES, 'UTF-8') ?>.png"
               width="22"
               height="16"
               alt=""
               loading="lazy">
          <span class="fv-lang__code"><?= htmlspecialchars(strtoupper($locale), ENT_QUOTES, 'UTF-8') ?></span>
          <svg class="fv-lang__chev" width="12" height="12" viewBox="0 0 12 12" aria-hidden="true"><path d="M2.5 4.5L6 8l3.5-3.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
        </button>
        <ul class="fv-lang__menu" id="fvLangMenu" role="listbox" hidden>
          <?php foreach ($langs as $code => $meta) : ?>
            <?php
            $langHref = $langSwitchBase !== ''
                ? base_url($langSwitchBase . '?lang=' . rawurlencode($code))
                : base_url('?lang=' . rawurlencode($code));
            ?>
            <li role="option" <?= $locale === $code ? 'aria-selected="true"' : '' ?>>
              <a class="fv-lang__option<?= $locale === $code ? ' is-active' : '' ?>"
                 href="<?= htmlspecialchars($langHref, ENT_QUOTES, 'UTF-8') ?>"
                 hreflang="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>">
                <img src="https://flagcdn.com/w40/<?= htmlspecialchars($meta['flag'], ENT_QUOTES, 'UTF-8') ?>.png"
                     width="22"
                     height="16"
                     alt=""
                     loading="lazy">
                <span><?= htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8') ?></span>
              </a>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <a class="fv-nav__cta<?= $navPage === 'marketplace' ? ' is-active' : '' ?>" href="<?= htmlspecialchars(base_url('marketplaces?lang=' . rawurlencode($locale)), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t('nav_marketplace'), ENT_QUOTES, 'UTF-8') ?></a>
    </div>
    <?php else : ?>
    <ul class="topmenu">
      <li class="<?= (! empty($title) && $title == 'Our Marketplaces') ? 'active' : '' ?>">
        <a href="<?= base_url('marketplaces') ?>"><?= htmlspecialchars($t('nav_marketplace', 'Marketplace'), ENT_QUOTES, 'UTF-8') ?></a>
      </li>
    </ul>
    <?php endif; ?>
  </div>
</nav>

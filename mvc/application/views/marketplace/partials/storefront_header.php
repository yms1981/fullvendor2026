<?php
/**
 * Chrome propio del Marketplace (mockup: negro + rojo, link a inicio, idiomas con bandera).
 *
 * @var array $mp
 * @var array $landing
 * @var array $categoryCarousel
 * @var string $landingLocale
 * @var string $searchQuery
 * @var bool|null $isMarketplaceHome
 * @var object|null $company
 */
$mp = (isset($mp) && is_array($mp)) ? $mp : [];
$landing = (isset($landing) && is_array($landing)) ? $landing : [];
$t = static function (string $key, string $fallback = '') use ($mp, $landing): string {
    $v = trim((string) ($mp[$key] ?? $landing[$key] ?? ''));

    return $v !== '' ? $v : $fallback;
};
$locale = isset($landingLocale) ? (string) $landingLocale : 'es';
$langQ = '?lang=' . rawurlencode($locale);
$homeUrl = base_url('marketplaces' . $langQ);
$fullvendorHome = base_url('?lang=' . rawurlencode($locale));
$isHome = ! empty($isMarketplaceHome);
$query = isset($searchQuery) ? (string) $searchQuery : '';
$cats = (isset($categoryCarousel) && is_array($categoryCarousel)) ? $categoryCarousel : [];
$navCats = array_slice($cats, 0, 7);
$companyHref = function_exists('company_url') ? company_url() : base_url('company');
$loginHref = base_url('marketplace/login');
$anchor = static function (string $hash) use ($isHome, $homeUrl): string {
    return $isHome ? ('#' . ltrim($hash, '#')) : ($homeUrl . '#' . ltrim($hash, '#'));
};
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
$storeCompany = (isset($company) && is_object($company)) ? $company : null;
$navIcons = ['fa-laptop', 'fa-home', 'fa-shopping-bag', 'fa-futbol-o', 'fa-heart', 'fa-wrench', 'fa-car'];
?>
<header class="fv-sf-header">
  <div class="fv-sf-mainbar">
    <div class="fv-sf-wrap fv-sf-mainbar__inner">
      <div class="fv-sf-brand-block">
        <a class="fv-sf-home-link" href="<?= htmlspecialchars($fullvendorHome, ENT_QUOTES, 'UTF-8') ?>">
          <i class="fa fa-arrow-left" aria-hidden="true"></i>
          <?= htmlspecialchars($t('footer_back_fullvendor'), ENT_QUOTES, 'UTF-8') ?>
        </a>
        <a class="fv-sf-brand" href="<?= htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8') ?>">
          <span class="fv-sf-brand__mark">F</span>
          <span class="fv-sf-brand__text">FULLVENDOR<small>MARKET PLACE</small></span>
        </a>
      </div>

      <form class="fv-sf-search" action="<?= htmlspecialchars(base_url('marketplaces/search'), ENT_QUOTES, 'UTF-8') ?>" method="post" role="search">
        <input type="hidden" name="lang" value="<?= htmlspecialchars($locale, ENT_QUOTES, 'UTF-8') ?>">
        <label class="sr-only" for="fvSfSearch"><?= htmlspecialchars($t('search_label'), ENT_QUOTES, 'UTF-8') ?></label>
        <input id="fvSfSearch" type="search" name="company_name" value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>" placeholder="<?= htmlspecialchars($t('search_placeholder'), ENT_QUOTES, 'UTF-8') ?>" autocomplete="off">
        <button type="submit" aria-label="<?= htmlspecialchars($t('search_button'), ENT_QUOTES, 'UTF-8') ?>">
          <i class="fa fa-search" aria-hidden="true"></i>
        </button>
      </form>

      <div class="fv-sf-actions">
        <div class="fv-sf-lang" id="fvSfLang" data-sf-lang>
          <button type="button"
                  class="fv-sf-lang__btn"
                  id="fvSfLangBtn"
                  aria-expanded="false"
                  aria-haspopup="listbox"
                  aria-label="<?= htmlspecialchars($t('lang_label', 'Language'), ENT_QUOTES, 'UTF-8') ?>">
            <img class="fv-sf-lang__flag"
                 src="https://flagcdn.com/w40/<?= htmlspecialchars($currentLang['flag'], ENT_QUOTES, 'UTF-8') ?>.png"
                 width="22"
                 height="16"
                 alt=""
                 loading="lazy">
            <span class="fv-sf-lang__code"><?= htmlspecialchars(strtoupper($locale), ENT_QUOTES, 'UTF-8') ?></span>
            <i class="fa fa-chevron-down" aria-hidden="true"></i>
          </button>
          <ul class="fv-sf-lang__menu" id="fvSfLangMenu" role="listbox" hidden>
            <?php foreach ($langs as $code => $meta) : ?>
              <li role="option" <?= $locale === $code ? 'aria-selected="true"' : '' ?>>
                <a class="fv-sf-lang__option<?= $locale === $code ? ' is-active' : '' ?>"
                   href="<?= htmlspecialchars(base_url('marketplaces?lang=' . rawurlencode($code)), ENT_QUOTES, 'UTF-8') ?>"
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
        <a href="<?= htmlspecialchars($loginHref, ENT_QUOTES, 'UTF-8') ?>">
          <i class="fa fa-user-o" aria-hidden="true"></i>
          <span><?= htmlspecialchars($t('nav_account'), ENT_QUOTES, 'UTF-8') ?></span>
        </a>
        <a href="<?= htmlspecialchars($anchor('empresas'), ENT_QUOTES, 'UTF-8') ?>">
          <i class="fa fa-heart-o" aria-hidden="true"></i>
          <span class="fv-sf-actions__badge">0</span>
          <span><?= htmlspecialchars($t('nav_favorites'), ENT_QUOTES, 'UTF-8') ?></span>
        </a>
        <a href="<?= htmlspecialchars($anchor('productos'), ENT_QUOTES, 'UTF-8') ?>">
          <i class="fa fa-shopping-cart" aria-hidden="true"></i>
          <span class="fv-sf-actions__badge">0</span>
          <span><?= htmlspecialchars($t('nav_cart'), ENT_QUOTES, 'UTF-8') ?></span>
        </a>
      </div>
    </div>
  </div>

  <nav class="fv-sf-catnav" aria-label="<?= htmlspecialchars($t('categories_title'), ENT_QUOTES, 'UTF-8') ?>">
    <div class="fv-sf-wrap fv-sf-catnav__inner">
      <a class="fv-sf-catnav__all" href="<?= htmlspecialchars($anchor('categorias'), ENT_QUOTES, 'UTF-8') ?>">
        <i class="fa fa-bars" aria-hidden="true"></i>
        <?= htmlspecialchars($t('nav_all_categories'), ENT_QUOTES, 'UTF-8') ?>
        <i class="fa fa-chevron-down" aria-hidden="true"></i>
      </a>
      <div class="fv-sf-catnav__links">
        <?php if ($storeCompany && ! empty($storeCompany->name)) : ?>
          <a href="#"><i class="fa fa-building" aria-hidden="true"></i><?= htmlspecialchars((string) $storeCompany->name, ENT_QUOTES, 'UTF-8') ?></a>
        <?php else : ?>
          <a href="<?= htmlspecialchars($anchor('productos'), ENT_QUOTES, 'UTF-8') ?>"><i class="fa fa-tag" aria-hidden="true"></i><?= htmlspecialchars($t('tab_offers'), ENT_QUOTES, 'UTF-8') ?></a>
          <a href="<?= htmlspecialchars($anchor('productos'), ENT_QUOTES, 'UTF-8') ?>"><i class="fa fa-bolt" aria-hidden="true"></i><?= htmlspecialchars($t('tab_new'), ENT_QUOTES, 'UTF-8') ?></a>
          <a href="<?= htmlspecialchars($anchor('productos'), ENT_QUOTES, 'UTF-8') ?>"><i class="fa fa-line-chart" aria-hidden="true"></i><?= htmlspecialchars($t('tab_bestsellers'), ENT_QUOTES, 'UTF-8') ?></a>
          <a href="<?= htmlspecialchars($anchor('empresas'), ENT_QUOTES, 'UTF-8') ?>"><i class="fa fa-building" aria-hidden="true"></i><?= htmlspecialchars($t('nav_companies'), ENT_QUOTES, 'UTF-8') ?></a>
        <?php endif; ?>
        <?php foreach ($navCats as $i => $cat) :
            $cslug = (string) ($cat->company_slug ?? '');
            $catId = (int) ($cat->cat_id ?? ($cat->category_id ?? 0));
            $href = $cslug !== ''
                ? base_url('marketplaces/details/' . rawurlencode($cslug)) . $langQ . '&cat=' . $catId
                : $anchor('categorias');
            $label = (string) ($cat->category_name ?? $cat->name ?? '');
            if ($label === '') {
                continue;
            }
            $icon = $navIcons[$i % count($navIcons)];
            ?>
          <a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>"><i class="fa <?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></a>
        <?php endforeach; ?>
        <?php if (count($cats) > 7) : ?>
          <a href="<?= htmlspecialchars($anchor('categorias'), ENT_QUOTES, 'UTF-8') ?>"><i class="fa fa-th" aria-hidden="true"></i><?= htmlspecialchars($t('nav_more'), ENT_QUOTES, 'UTF-8') ?></a>
        <?php endif; ?>
      </div>
    </div>
  </nav>
</header>
<style>.sr-only{position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);border:0}</style>

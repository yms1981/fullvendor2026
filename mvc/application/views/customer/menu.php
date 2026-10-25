<?php
defined('BASEPATH') or exit('No direct script access allowed');

/** @var array<string, mixed> $pageTexts */
/** @var object|null $settings */
/** @var string $url_menu */
/** @var string $url_orders */
/** @var string $url_catalog */
/** @var string $url_logout */
/** @var int $lang_id */
/** @var string $html_lang */
/** @var string $active_portal_tab */

$t = is_array($pageTexts ?? null) ? $pageTexts : [];
$e = static function (?string $s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
};
$urlMenu = (string) ($url_menu ?? site_url('customer/catalog'));
$urlOrders = (string) ($url_orders ?? '');
$urlCatalog = (string) ($url_catalog ?? base_url());
$urlLogout = (string) ($url_logout ?? site_url('customer/logout'));
$activeTab = (string) ($active_portal_tab ?? '');
?>
<!DOCTYPE html>
<html lang="<?= $e($html_lang ?? 'en') ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title><?= $e($t['menu_title'] ?? '') ?></title>
  <link rel="icon" type="image/png" sizes="16x16" href="<?= $e(base_url('uploads/logos/' . (@$settings->favicon ?? ''))) ?>">
  <link rel="stylesheet" href="<?= $e(base_url('backend/vendors/css/vendor.bundle.base.css')) ?>">
  <link rel="stylesheet" href="<?= $e(base_url('backend/css/style.css')) ?>">
  <style>
    /* Aislar del tema admin (style.css): color y enlaces visibles */
    html, body.customer-portal-body {
      height: 100%;
      margin: 0;
      color: #212529;
      background: #f0f2f5;
    }
    .customer-portal-body .portal-app a {
      color: #495057;
    }
    .portal-app {
      min-height: 100vh;
      width: 100%;
      display: flex;
      flex-direction: column;
      background: #f0f2f5;
    }
    .portal-header {
      flex-shrink: 0;
      width: 100%;
      background: #fff;
      border-bottom: 1px solid #dee2e6;
      box-shadow: 0 1px 0 rgba(0,0,0,0.04);
    }
    .portal-header-inner {
      max-width: 100%;
      margin: 0 auto;
      padding: 12px 20px;
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 12px 20px;
    }
    .portal-brand {
      display: flex;
      align-items: center;
      gap: 16px;
      min-width: 0;
    }
    .portal-brand img {
      max-height: 48px;
      max-width: 200px;
      object-fit: contain;
    }
    .portal-brand-text h1 {
      margin: 0;
      font-size: 1.25rem;
      font-weight: 600;
      color: #212529;
      line-height: 1.25;
    }
    .portal-brand-text p {
      margin: 4px 0 0;
      font-size: 0.875rem;
      color: #6c757d;
    }
    .portal-lang {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: flex-end;
      gap: 4px 8px;
      font-size: 0.8125rem;
    }
    .portal-lang a {
      color: #495057;
      white-space: nowrap;
    }
    .portal-lang a:hover {
      color: #dc3545;
    }
    .portal-tabs-wrap {
      flex-shrink: 0;
      width: 100%;
      background: #fff;
      border-bottom: 1px solid #dee2e6;
    }
    .portal-tabs {
      display: flex;
      width: 100%;
      margin: 0;
      padding: 0;
      list-style: none;
    }
    .portal-tab {
      flex: 1 1 0;
      min-width: 0;
      text-align: center;
    }
    .portal-tab a {
      display: block;
      padding: 14px 10px;
      font-size: 0.9375rem;
      font-weight: 600;
      color: #495057;
      text-decoration: none;
      border-bottom: 3px solid transparent;
      transition: color 0.15s, border-color 0.15s, background 0.15s;
    }
    .portal-tab a:hover {
      color: #dc3545;
      background: #fff5f5;
      text-decoration: none;
    }
    .portal-tab.is-active a {
      color: #dc3545;
      border-bottom-color: #dc3545;
      background: #fff;
    }
    svg.portal-icon {
      width: 1.05rem;
      height: 1.05rem;
      vertical-align: -0.125em;
      flex-shrink: 0;
    }
    .portal-tab a .portal-icon {
      display: inline-block;
      margin-right: 6px;
      vertical-align: middle;
    }
    .portal-main {
      flex: 1 1 auto;
      width: 100%;
      padding: 24px 20px 32px;
      box-sizing: border-box;
    }
    .portal-main-inner {
      width: 100%;
      max-width: 100%;
      margin: 0 auto;
    }
    .portal-welcome-card {
      background: #fff;
      border-radius: 10px;
      border: 1px solid #e9ecef;
      padding: 24px 28px;
      box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    }
    .portal-welcome-card h2 {
      margin: 0 0 8px;
      font-size: 1.125rem;
      font-weight: 600;
      color: #212529;
    }
    .portal-welcome-card > p {
      margin: 0 0 20px;
      color: #6c757d;
      font-size: 0.9375rem;
      line-height: 1.5;
    }
    .portal-cards {
      display: grid;
      grid-template-columns: 1fr;
      gap: 16px;
    }
    @media (min-width: 768px) {
      .portal-cards {
        grid-template-columns: repeat(2, 1fr);
      }
    }
    .portal-card-link {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      gap: 8px;
      padding: 20px;
      border-radius: 10px;
      background: #f8f9fa;
      border: 1px solid #e9ecef;
      text-decoration: none;
      color: #212529;
      height: 100%;
      box-sizing: border-box;
      transition: background 0.2s, border-color 0.2s;
    }
    .portal-card-link:hover {
      background: #fff5f5;
      border-color: #f5c2c7;
      color: #c82333;
      text-decoration: none;
    }
    .portal-card-link .mdi {
      font-size: 2rem;
      color: #dc3545;
    }
    .portal-card-link:hover .mdi {
      color: #c82333;
    }
    .portal-card-link strong {
      font-size: 1rem;
    }
    .portal-card-link span.desc {
      font-size: 0.875rem;
      color: #6c757d;
      line-height: 1.4;
    }
    .portal-card-link:hover span.desc {
      color: #495057;
    }
  </style>
</head>
<body class="customer-portal-body">
  <div class="portal-app">
    <header class="portal-header">
      <div class="portal-header-inner">
        <div class="portal-brand">
          <?php if (! empty($settings->logo)) { ?>
            <img src="<?= $e(base_url('uploads/logos/' . $settings->logo)) ?>" alt="">
          <?php } else { ?>
            <img src="<?= $e(base_url('images/logos/isologo.png')) ?>" alt="">
          <?php } ?>
          <div class="portal-brand-text">
            <h1><?= $e($t['menu_title'] ?? '') ?></h1>
            <p><?= $e($t['menu_subtitle'] ?? '') ?></p>
          </div>
        </div>
        <nav class="portal-lang" aria-label="<?= $e($t['lang_switcher_label'] ?? '') ?>">
          <?php
          $portalLangs = [
              ['param' => 'en', 'key' => 'lang_en'],
              ['param' => 'es', 'key' => 'lang_es'],
              ['param' => 'pt', 'key' => 'lang_pt'],
              ['param' => 'it', 'key' => 'lang_it'],
              ['param' => 'ru', 'key' => 'lang_ru'],
              ['param' => 'de', 'key' => 'lang_de'],
              ['param' => 'zh', 'key' => 'lang_zh'],
          ];
          foreach ($portalLangs as $i => $pl) {
              if ($i > 0) {
                  echo '<span aria-hidden="true">|</span>';
              }
              $href = $urlMenu;
              $href .= (strpos($href, '?') !== false ? '&' : '?') . 'lang=' . $pl['param'];
              echo '<a href="' . $e($href) . '">' . $e($t[$pl['key']] ?? '') . '</a>';
          }
          ?>
          <a href="<?= $e($urlLogout) ?>" class="btn btn-outline-danger btn-sm"><?= $e($t['logout_button'] ?? '') ?></a>
        </nav>
      </div>
    </header>

    <nav class="portal-tabs-wrap" aria-label="<?= $e($t['menu_tabs_nav_label'] ?? $t['menu_title'] ?? '') ?>">
      <ul class="portal-tabs">
        <li class="portal-tab <?= $activeTab === 'catalog' ? 'is-active' : '' ?>">
          <a href="<?= $e($urlCatalog) ?>"<?= $activeTab === 'catalog' ? ' aria-current="page"' : '' ?>>
            <svg class="portal-icon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M19 1H9c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-2-2-2zm0 14H9V3h10v12zM5 3H3v16c0 1.1.9 2 2 2h12v-2H5V3z"/></svg><?= $e($t['menu_catalog'] ?? '') ?>
          </a>
        </li>
        <li class="portal-tab <?= $activeTab === 'orders' ? 'is-active' : '' ?>">
          <a href="<?= $e($urlOrders) ?>"<?= $activeTab === 'orders' ? ' aria-current="page"' : '' ?>>
            <svg class="portal-icon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1 9.6 1.84 9.18 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm2 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg><?= $e($t['menu_orders'] ?? '') ?>
          </a>
        </li>
      </ul>
    </nav>

    <main class="portal-main" id="main-content">
      <div class="portal-main-inner">
        <section class="portal-welcome-card" aria-labelledby="portal-welcome-heading">
          <h2 id="portal-welcome-heading"><?= $e($t['menu_welcome_heading'] ?? '') ?></h2>
          <p><?= $e($t['menu_welcome_text'] ?? '') ?></p>
          <div class="portal-cards">
            <a class="portal-card-link" href="<?= $e($urlCatalog) ?>">
              <svg class="portal-icon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M19 1H9c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h10c1.1 0 2-.9 2-2V3c0-1.1-.9-2-2-2zm0 14H9V3h10v12zM5 3H3v16c0 1.1.9 2 2 2h12v-2H5V3z"/></svg>
              <strong><?= $e($t['menu_catalog'] ?? '') ?></strong>
              <span class="desc"><?= $e($t['menu_catalog_desc'] ?? '') ?></span>
            </a>
            <a class="portal-card-link" href="<?= $e($urlOrders) ?>">
              <svg class="portal-icon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1 9.6 1.84 9.18 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm2 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/></svg>
              <strong><?= $e($t['menu_orders'] ?? '') ?></strong>
              <span class="desc"><?= $e($t['menu_orders_desc'] ?? '') ?></span>
            </a>
          </div>
        </section>
      </div>
    </main>
  </div>
</body>
</html>

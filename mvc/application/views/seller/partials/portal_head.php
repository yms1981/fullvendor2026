<?php
defined('BASEPATH') or exit('No direct script access allowed');

/** @var array<string, mixed> $pageTexts */
/** @var object|null $settings */
/** @var string $url_home */
/** @var string $url_orders */
/** @var string $url_catalog */
/** @var string $url_logout */
/** @var string $portal_company_name */
/** @var string $html_lang */
/** @var string $active_portal_tab */
/** @var string $portal_page_title */

$t = is_array($pageTexts ?? null) ? $pageTexts : [];
$settings = isset($settings) && is_object($settings) ? $settings : null;
$e = static function (?string $s): string {
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
};
$urlHome = (string) ($url_home ?? site_url('sellers/customer'));
$urlOrders = (string) ($url_orders ?? site_url('sellers/orders'));
$urlCatalog = (string) ($url_catalog ?? site_url('sellers/catalog'));
$urlLogout = (string) ($url_logout ?? site_url('sellers/logout'));
$activeTab = (string) ($active_portal_tab ?? '');
$portalCompanyName = trim((string) ($portal_company_name ?? ''));
$pageTitle = trim((string) ($portal_page_title ?? ''));
if ($pageTitle === '') {
    $pageTitle = (string) ($t['menu_title'] ?? '');
}
?>
<!DOCTYPE html>
<html lang="<?= $e($html_lang ?? 'en') ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title><?= $e($pageTitle) ?></title>
  <?php
    $faviconFile = ($settings !== null) ? trim((string) ($settings->favicon ?? '')) : '';
    $faviconHref = $faviconFile !== '' ? base_url('uploads/logos/' . $faviconFile) : base_url('images/logos/isologo.png');
  ?>
  <link rel="icon" type="image/png" sizes="16x16" href="<?= $e($faviconHref) ?>">
  <link rel="stylesheet" href="<?= $e(base_url('backend/vendors/css/vendor.bundle.base.css')) ?>">
  <link rel="stylesheet" href="<?= $e(base_url('backend/css/style.css')) ?>">
  <style>
    :root {
      --seller-bg: #ffffff;
      /* Portal vendedor: cabecera y pestañas en rojo sobre fondo blanco */
      --seller-surface: #d22e3e;
      --seller-surface-2: #b92333;
      --seller-accent: #ffffff;
      --seller-accent-dim: #ff2d4d;
      --seller-text: #ffffff;
      --seller-text-muted: #f5b8c2;
      --seller-ink: #5c0f18;
      --seller-ink-muted: #7a2a32;
      --seller-btn-solid: #d22e3e;
      --seller-btn-solid-hover: #e84555;
      /* Pestañas (Customer / Catalog / Orders): blanco con matiz muy suave */
      --seller-tab-fg: rgba(255, 252, 253, 0.92);
      --seller-tab-hover-bg: rgba(255, 255, 255, 0.14);
      --seller-tab-active-bg: rgba(0, 0, 0, 0.12);
      --seller-tab-active-underline: rgba(255, 255, 255, 0.55);
    }
    html, body.seller-portal-body {
      height: 100%;
      margin: 0;
      color: var(--seller-ink);
      background: var(--seller-bg);
      background-attachment: fixed;
    }
    .seller-portal-body .portal-app {
      min-height: 100vh;
      width: 100%;
      display: flex;
      flex-direction: column;
      background: var(--seller-bg);
    }
    .seller-portal-body .portal-main a {
      color: var(--seller-ink);
      text-decoration: underline;
      text-underline-offset: 2px;
    }
    .seller-portal-body .portal-main a:hover {
      color: var(--seller-btn-solid-hover);
    }
    .seller-portal-body .portal-main a.btn-portal-primary,
    .seller-portal-body .portal-main a.btn-portal-outline {
      text-decoration: none;
    }
    .portal-header {
      flex-shrink: 0;
      width: 100%;
      background: var(--seller-surface);
      border-bottom: 1px solid rgba(255, 255, 255, 0.12);
      box-shadow: none;
    }
    .portal-header-inner {
      max-width: 100%;
      margin: 0 auto;
      padding: 8px 18px;
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 8px 16px;
    }
    .portal-brand {
      display: flex;
      align-items: center;
      gap: 12px;
      min-width: 0;
    }
    .portal-brand img {
      max-height: 40px;
      max-width: 200px;
      object-fit: contain;
      filter: drop-shadow(0 1px 2px rgba(0,0,0,0.3));
    }
    .portal-brand-text h1 {
      margin: 0;
      font-size: 1.2rem;
      font-weight: 600;
      color: var(--seller-text);
      line-height: 1.2;
    }
    .portal-brand-text p {
      margin: 2px 0 0;
      font-size: 0.875rem;
      color: var(--seller-text-muted);
    }
    .portal-brand-text .portal-company-line {
      margin-top: 4px;
      font-size: 0.8125rem;
      color: var(--seller-text-muted);
    }
    .portal-header-actions {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      gap: 10px;
    }
    .portal-header-actions .btn-logout {
      display: inline-block;
      padding: 6px 14px;
      font-size: 0.8125rem;
      font-weight: 600;
      color: #ffffff !important;
      background: transparent;
      border: 1px solid rgba(255, 255, 255, 0.85);
      border-radius: 8px;
      text-decoration: none;
      transition: background 0.2s, border-color 0.2s, color 0.2s;
    }
    .portal-header-actions .btn-logout:hover {
      background: #fef2f2;
      border-color: #fecaca;
      color: var(--seller-ink) !important;
    }
    .portal-tabs-wrap {
      flex-shrink: 0;
      width: 100%;
      background: var(--seller-surface-2);
      border-bottom: none;
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
      padding: 11px 10px;
      font-size: 1rem;
      font-weight: 600;
      color: var(--seller-tab-fg);
      text-decoration: none;
      border-bottom: 3px solid transparent;
      transition: color 0.15s, border-color 0.15s, background 0.15s;
    }
    .portal-tab a:hover {
      color: #ffffff;
      background: var(--seller-tab-hover-bg);
      text-decoration: none;
    }
    .portal-tab.is-active a {
      color: #ffffff;
      border-bottom-color: var(--seller-tab-active-underline);
      background: var(--seller-tab-active-bg);
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
      padding: 6px 24px 48px;
      box-sizing: border-box;
      background: var(--seller-bg);
      color: var(--seller-ink);
      border-top: none;
    }
    .portal-main-inner {
      width: 100%;
      max-width: 100%;
      margin: 0 auto;
      font-size: 1.125rem;
      line-height: 1.55;
    }
    .portal-welcome-card {
      background: #ffffff;
      border-radius: 12px;
      border: 1px solid #d4d4d4;
      padding: 32px 36px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
      color: var(--seller-ink);
    }
    .portal-welcome-card h2 {
      margin: 0 0 8px;
      font-size: 1.125rem;
      font-weight: 600;
      color: var(--seller-ink);
    }
    .portal-welcome-card > p, .portal-welcome-card p.intro {
      margin: 0 0 16px;
      color: var(--seller-ink-muted);
      font-size: 0.9375rem;
      line-height: 1.5;
    }
    .portal-orders-toolbar {
      display: flex;
      flex-wrap: wrap;
      align-items: flex-start;
      justify-content: space-between;
      gap: 12px 16px;
      margin-bottom: 18px;
    }
    .portal-orders-toolbar h2 {
      margin: 0 0 6px;
      font-size: 1.25rem;
      font-weight: 600;
      color: var(--seller-ink);
    }
    .portal-orders-sub {
      margin: 0;
      color: var(--seller-ink-muted);
      font-size: 1rem;
    }
    .portal-table-shell {
      border-radius: 10px;
      overflow: hidden;
      border: 1px solid #d4d4d4;
    }
    .portal-table-responsive {
      overflow-x: auto;
      -webkit-overflow-scrolling: touch;
    }
    table.portal-orders-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.875rem;
      background: #fff;
    }
    table.portal-orders-table th,
    table.portal-orders-table td {
      border-bottom: 1px solid #e5e5e5;
      padding: 10px 12px;
      vertical-align: middle;
    }
    table.portal-orders-table thead th {
      background: #f5f5f5;
      font-weight: 600;
      color: var(--seller-ink);
      text-align: left;
    }
    table.portal-orders-table tbody td {
      color: #262626;
    }
    table.portal-orders-table thead th.col-total,
    table.portal-orders-table tbody td.col-total {
      text-align: right;
      font-variant-numeric: tabular-nums;
      white-space: nowrap;
    }
    table.portal-orders-table thead th.col-actions,
    table.portal-orders-table tbody td.col-actions {
      text-align: center;
      white-space: nowrap;
    }
    .catalog-toolbar {
      display: flex;
      flex-wrap: wrap;
      align-items: flex-start;
      justify-content: space-between;
      gap: 12px 16px;
      margin-bottom: 16px;
    }
    .catalog-flash {
      padding: 12px 14px;
      border-radius: 8px;
      margin-bottom: 14px;
      font-size: 1rem;
    }
    .catalog-flash--error {
      background: #f5f5f5;
      color: var(--seller-ink);
      border: 1px solid var(--seller-btn-solid);
    }
    .catalog-flash--ok {
      background: #fafafa;
      color: var(--seller-ink);
      border: 1px solid #737373;
    }
    .catalog-product-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 16px;
    }
    @media (min-width: 600px) {
      .catalog-product-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }
    @media (min-width: 1024px) {
      .catalog-product-grid {
        grid-template-columns: repeat(3, 1fr);
      }
    }
    .catalog-product-card {
      background: #fff;
      border: 1px solid #d4d4d4;
      border-radius: 10px;
      padding: 14px;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .catalog-product-card .catalog-product-img-wrap {
      background: #f5f5f5;
      border-radius: 8px;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 140px;
    }
    .catalog-product-card img {
      max-width: 100%;
      max-height: 140px;
      object-fit: contain;
    }
    .catalog-product-name {
      font-weight: 600;
      color: var(--seller-ink);
      font-size: 0.9375rem;
      line-height: 1.35;
    }
    .catalog-product-meta {
      font-size: 0.8125rem;
      color: var(--seller-ink-muted);
    }
    .catalog-product-price {
      font-size: 1rem;
      font-weight: 600;
      color: var(--seller-ink);
      font-variant-numeric: tabular-nums;
    }
    .btn-portal-primary {
      display: inline-block;
      padding: 10px 18px;
      font-size: 1rem;
      font-weight: 600;
      color: #ffffff !important;
      background: var(--seller-btn-solid);
      border: 1px solid var(--seller-btn-solid);
      border-radius: 8px;
      text-decoration: none;
      cursor: pointer;
      border-style: solid;
    }
    .btn-portal-primary:hover {
      background: var(--seller-btn-solid-hover);
      border-color: var(--seller-btn-solid-hover);
      color: #ffffff !important;
    }
    .btn-portal-primary:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }
    .btn-portal-outline {
      display: inline-block;
      padding: 8px 14px;
      font-size: 0.875rem;
      font-weight: 600;
      color: var(--seller-ink) !important;
      background: #fafafa;
      border: 1px solid var(--seller-btn-solid);
      border-radius: 8px;
      text-decoration: none;
    }
    .btn-portal-outline:hover {
      background: #e5e5e5;
      color: var(--seller-ink) !important;
    }
    .catalog-qty-input {
      width: 80px;
      padding: 8px 10px;
      border-radius: 6px;
      border: 1px solid #a3a3a3;
      font-size: 1rem;
    }
    .catalog-form-row {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 8px;
      margin-top: auto;
    }
    .catalog-customer-select {
      max-width: 100%;
      width: min(420px, 100%);
      padding: 10px 12px;
      border-radius: 8px;
      border: 1px solid #a3a3a3;
      font-size: 0.9375rem;
      color: var(--seller-ink);
      background: #fff;
    }
    .catalog-empty {
      margin: 0;
      padding: 20px 12px;
      text-align: center;
      color: var(--seller-ink-muted);
      font-size: 1.0625rem;
    }
    .catalog-checkout-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 20px;
    }
    .catalog-filter-toolbar {
      padding: 14px 16px;
      background: #f5f5f5;
      border: 1px solid #d4d4d4;
      border-radius: 10px;
    }
    .catalog-filter-row {
      display: flex;
      flex-wrap: wrap;
      align-items: flex-end;
      gap: 10px 14px;
    }
    .catalog-filter-field {
      display: flex;
      flex-direction: column;
      align-items: stretch;
      min-width: 0;
    }
    .catalog-filter-field--grow {
      flex: 1 1 200px;
    }
    .catalog-filter-field--btn .catalog-filter-label {
      visibility: hidden;
    }
    .catalog-filter-label {
      font-size: 0.75rem;
      font-weight: 600;
      color: var(--seller-ink-muted);
      display: block;
      margin-bottom: 4px;
    }
    .catalog-filter-select,
    .catalog-filter-search {
      min-width: 140px;
      padding: 10px 12px;
      border-radius: 8px;
      border: 1px solid #a3a3a3;
      font-size: 1rem;
      color: var(--seller-ink);
      background: #fff;
    }
    .catalog-filter-search {
      width: 100%;
      min-width: 220px;
    }

    /* Tarjeta clara: forzar texto oscuro en botones (vence reglas globales del backend) */
    body.seller-portal-body .portal-welcome-card button.btn-portal-primary,
    body.seller-portal-body .portal-welcome-card button.btn-portal-outline,
    body.seller-portal-body .portal-welcome-card .btn-portal-primary,
    body.seller-portal-body .portal-welcome-card .btn-portal-outline {
      -webkit-appearance: none;
      appearance: none;
      font-family: inherit;
    }
    body.seller-portal-body .portal-welcome-card button.btn-portal-primary,
    body.seller-portal-body .portal-welcome-card .btn-portal-primary {
      color: #ffffff !important;
      -webkit-text-fill-color: #ffffff !important;
      background: var(--seller-btn-solid) !important;
      border: 1px solid var(--seller-btn-solid) !important;
      font-size: 1rem !important;
      padding: 10px 18px !important;
    }
    body.seller-portal-body .portal-welcome-card button.btn-portal-outline,
    body.seller-portal-body .portal-welcome-card .btn-portal-outline {
      color: var(--seller-ink) !important;
      -webkit-text-fill-color: var(--seller-ink) !important;
      background: #fafafa !important;
      border: 1px solid var(--seller-btn-solid) !important;
      font-size: 1rem !important;
      padding: 10px 18px !important;
    }

    /* Modal crear cliente: por encima de cabecera/pestañas y con UI clara legible */
    .seller-portal-modal-root {
      position: fixed;
      inset: 0;
      z-index: 10050;
      padding: 16px;
      overflow: auto;
      box-sizing: border-box;
      background: rgba(0, 0, 0, 0.55);
      flex-direction: row;
      align-items: flex-start;
      justify-content: center;
    }
    .seller-portal-modal-root[hidden] {
      display: none !important;
    }
    .seller-portal-modal-root:not([hidden]) {
      display: flex !important;
    }
    .seller-portal-modal-dialog {
      width: 100%;
      max-width: 760px;
      margin: 40px auto;
      background: #fff !important;
      color: var(--seller-ink) !important;
      border-radius: 12px;
      border: 1px solid #d4d4d4;
      box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2);
    }
    body.seller-portal-body .seller-portal-modal-dialog,
    body.seller-portal-body .seller-portal-modal-dialog label,
    body.seller-portal-body .seller-portal-modal-dialog .seller-portal-modal-body,
    body.seller-portal-body .seller-portal-modal-dialog .seller-portal-modal-header h3 {
      color: var(--seller-ink) !important;
      -webkit-text-fill-color: var(--seller-ink) !important;
    }
    .seller-portal-modal-dialog .seller-portal-modal-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      padding: 14px 18px;
      border-bottom: 1px solid #e5e5e5;
    }
    .seller-portal-modal-dialog .seller-portal-modal-header h3 {
      margin: 0;
      font-size: 1rem;
      font-weight: 600;
      color: var(--seller-ink);
    }
    .seller-portal-modal-dialog .seller-portal-modal-body {
      padding: 16px 18px 18px;
    }
    .seller-portal-modal-dialog .form-control {
      color: var(--seller-ink) !important;
      -webkit-text-fill-color: var(--seller-ink) !important;
      background-color: #fff !important;
      border-color: #a3a3a3 !important;
    }
    .seller-portal-modal-dialog .form-control:focus {
      color: var(--seller-ink) !important;
      -webkit-text-fill-color: var(--seller-ink) !important;
      background-color: #fff !important;
    }
    .seller-portal-modal-dialog .form-control::placeholder {
      color: #737373;
      opacity: 1;
    }
    .seller-portal-modal-dialog button.btn-portal-primary {
      -webkit-appearance: none;
      appearance: none;
      color: #ffffff !important;
      -webkit-text-fill-color: #ffffff !important;
      background: var(--seller-btn-solid) !important;
      border: 1px solid var(--seller-btn-solid) !important;
      font-size: 1rem !important;
      padding: 10px 18px !important;
    }
    .seller-portal-modal-dialog button.btn-portal-outline {
      -webkit-appearance: none;
      appearance: none;
      color: var(--seller-ink) !important;
      -webkit-text-fill-color: var(--seller-ink) !important;
      background: #fafafa !important;
      border: 1px solid var(--seller-btn-solid) !important;
      font-size: 1rem !important;
      padding: 10px 18px !important;
    }

    .seller-customer-create-hint {
      margin: 0;
      font-size: 0.9375rem;
      line-height: 1.4;
      color: var(--seller-ink-muted);
      max-width: 280px;
      text-align: right;
    }

    /* Gestión clientes + catálogo: barra superior, filtros y paginación (pestaña Customer) */
    .seller-mgmt-card { padding-top: 1.25rem; }
    .seller-mgmt-page-title {
      margin: 0 0 1rem;
      font-size: 1.35rem;
      font-weight: 700;
      color: #1a1a1a !important;
      -webkit-text-fill-color: #1a1a1a !important;
    }
    .seller-mgmt-top-bar {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 12px 16px;
      margin-bottom: 1.25rem;
    }
    .seller-mgmt-btn-add {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      text-decoration: none !important;
      font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif !important;
      font-size: 0.9375rem !important;
      font-weight: 600 !important;
      line-height: 1.35 !important;
      color: #fff !important;
      -webkit-text-fill-color: #fff !important;
      background: linear-gradient(180deg, #34ce57 0%, #28a745 100%) !important;
      border: 1px solid #23963d !important;
      padding: 10px 18px !important;
      border-radius: 6px !important;
      cursor: pointer !important;
      box-shadow: 0 1px 3px rgba(0,0,0,0.12);
      -webkit-appearance: none !important;
      appearance: none !important;
    }
    .seller-mgmt-btn-add:hover { filter: brightness(1.05); }
    .seller-mgmt-btn-add .mdi { font-size: 1.15rem; line-height: 1; }
    .seller-mgmt-total {
      font-size: 0.9375rem;
      color: #333 !important;
      -webkit-text-fill-color: #333 !important;
    }
    .seller-mgmt-total strong { font-weight: 700; color: #111 !important; }
    .seller-mgmt-filters {
      margin-bottom: 1.25rem;
      padding: 16px 18px;
      border: 1px solid #d4d4d4;
      border-radius: 8px;
      background: #fafafa;
    }
    .seller-mgmt-filters-inner {
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 14px 16px;
      align-items: end;
    }
    @media (max-width: 1100px) {
      .seller-mgmt-filters-inner { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 720px) {
      .seller-mgmt-filters-inner { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 480px) {
      .seller-mgmt-filters-inner { grid-template-columns: 1fr; }
    }
    .seller-mgmt-field label {
      display: block;
      font-size: 0.68rem;
      font-weight: 700;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      color: #6b7280 !important;
      margin-bottom: 6px;
    }
    .seller-mgmt-field select,
    .seller-mgmt-field input[type="search"] {
      width: 100%;
      box-sizing: border-box;
      padding: 8px 10px;
      font-size: 0.875rem;
      border: 1px solid #c4c4c4;
      border-radius: 4px;
      background: #fff;
      color: #1a1a1a;
    }
    .seller-mgmt-field--search { grid-column: span 1; }
    @media (min-width: 1101px) {
      .seller-mgmt-field--search { grid-column: span 1; min-width: 0; }
    }
    .seller-mgmt-search-wrap {
      display: flex;
      align-items: stretch;
      border: 1px solid #c4c4c4;
      border-radius: 4px;
      overflow: hidden;
      background: #fff;
    }
    .seller-mgmt-search-wrap input[type="search"] {
      border: none;
      border-radius: 0;
      flex: 1;
      min-width: 0;
    }
    .seller-mgmt-search-btn {
      flex: 0 0 42px;
      border: none;
      background: #f3f4f6;
      color: #374151;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-left: 1px solid #c4c4c4;
    }
    .seller-mgmt-search-btn:hover { background: #e5e7eb; }
    .seller-mgmt-pagination-bar {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 10px 16px;
      margin-bottom: 10px;
      font-size: 0.875rem;
      color: #4b5563;
    }
    .seller-mgmt-pagination {
      display: inline-flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 6px;
    }
    .seller-mgmt-pagination a,
    .seller-mgmt-pagination span {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 2rem;
      height: 2rem;
      padding: 0 8px;
      font-size: 0.8125rem;
      font-weight: 600;
      border-radius: 4px;
      text-decoration: none !important;
      border: 1px solid #d1d5db;
      color: #374151 !important;
      background: #fff;
    }
    .seller-mgmt-pagination a:hover { background: #f3f4f6; }
    .seller-mgmt-pagination .is-current {
      background: #e5e7eb;
      border-color: #9ca3af;
      color: #111 !important;
    }
    .seller-mgmt-pagination .is-disabled {
      opacity: 0.45;
      pointer-events: none;
    }

    /* Grilla tabular unificada (clientes y productos catálogo) */
    .seller-portal-data-grid {
      border-collapse: separate;
      border-spacing: 0;
      border: 1px solid #dee2e6;
      border-radius: 6px;
      overflow: hidden;
      background: #fff;
    }
    .seller-portal-data-grid th { white-space: nowrap; }
    .seller-portal-data-grid td { word-break: break-word; vertical-align: middle; }
    .seller-portal-body .portal-main table.portal-orders-table.seller-portal-data-grid thead th {
      background: linear-gradient(135deg, #dc3545 0%, #c82333 100%) !important;
      background-color: #c82333 !important;
      color: #ffffff !important;
      -webkit-text-fill-color: #ffffff !important;
      font-weight: 600 !important;
      vertical-align: middle !important;
      border: none !important;
      border-bottom: 1px solid rgba(0, 0, 0, 0.08) !important;
      font-size: 0.78rem;
      letter-spacing: 0.02em;
      text-transform: uppercase;
      padding-top: 0.65rem !important;
      padding-bottom: 0.65rem !important;
    }
    .seller-portal-body .portal-main table.portal-orders-table.seller-portal-data-grid thead th:first-child {
      border-top-left-radius: 6px;
    }
    .seller-portal-body .portal-main table.portal-orders-table.seller-portal-data-grid thead th:last-child {
      border-top-right-radius: 6px;
    }
    .seller-portal-body .portal-main table.portal-orders-table.seller-portal-data-grid tbody td {
      border-top: 1px solid #e9ecef;
      color: #212529 !important;
      -webkit-text-fill-color: #212529 !important;
    }
    .seller-portal-data-grid tbody tr { background: #ffffff; }
    .seller-portal-data-grid tbody tr:nth-child(even) { background: #f2f3f5; }
  </style>
</head>
<body class="seller-portal-body">
  <div class="portal-app">
    <header class="portal-header">
      <div class="portal-header-inner">
        <div class="portal-brand">
          <?php
            $logoFile = ($settings !== null) ? trim((string) ($settings->logo ?? '')) : '';
          ?>
          <?php if ($logoFile !== '') { ?>
            <img src="<?= $e(base_url('uploads/logos/' . $logoFile)) ?>" alt="">
          <?php } else { ?>
            <img src="<?= $e(base_url('images/logos/isologo.png')) ?>" alt="">
          <?php } ?>
          <div class="portal-brand-text">
            <h1><?= $e($t['menu_title'] ?? '') ?></h1>
            <p><?= $e($t['menu_subtitle'] ?? '') ?></p>
            <?php if ($portalCompanyName !== '') { ?>
              <p class="portal-company-line"><?= $e($portalCompanyName) ?></p>
            <?php } ?>
          </div>
        </div>
        <div class="portal-header-actions">
          <a href="<?= $e($urlLogout) ?>" class="btn-logout"><?= $e($t['logout_button'] ?? '') ?></a>
        </div>
      </div>
    </header>

    <nav class="portal-tabs-wrap" aria-label="<?= $e($t['menu_tabs_nav_label'] ?? $t['menu_title'] ?? '') ?>">
      <ul class="portal-tabs">
        <li class="portal-tab <?= $activeTab === 'home' ? 'is-active' : '' ?>">
          <a href="<?= $e($urlHome) ?>"<?= $activeTab === 'home' ? ' aria-current="page"' : '' ?>>
            <svg class="portal-icon" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg><?= $e($t['menu_tab_home'] ?? '') ?>
          </a>
        </li>
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

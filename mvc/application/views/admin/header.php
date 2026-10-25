<?php $setting = get_instance()->getPlatformSettings();
$adminShell = function_exists('admin_shell_lang_section') ? admin_shell_lang_section() : [];
$sh = function (string $key, string $fallback) use ($adminShell): string {
    $v = trim((string) ($adminShell[$key] ?? ''));

    return $v !== '' ? $v : $fallback;
};
$navBrandAlt = $sh('navbar_brand_alt_fullvendor', 'FullVendor');
$navRoleSuper = $sh('navbar_role_super_admin', 'Super Admin');
$navOnline = $sh('navbar_status_online', 'Online');
$navSignOut = $sh('navbar_sign_out', 'Sign out');
$navProfile = $sh('navbar_profile', 'My profile');
$navChangePassword = $sh('navbar_change_password', 'Change password');
$sidebarMenuDashboard = $sh('sidebar_menu_dashboard', 'Dashboard');
$sidebarMenuCompaniesCategories = $sh('sidebar_menu_companies_categories', 'Company categories');
$sidebarMenuCompanies = $sh('sidebar_menu_companies', 'Companies');
$sidebarMenuPlan = $sh('sidebar_menu_plan', 'Plan');
$sidebarMenuSubscriptions = $sh('sidebar_menu_subscriptions', 'Subscriptions');
$sidebarMenuInvoices = $sh('sidebar_menu_invoices', 'Invoices');
$sidebarMenuCms = $sh('sidebar_menu_cms', 'CMS');
$sidebarMenuStripeSettings = $sh('sidebar_menu_stripe_settings', 'Stripe settings');
$sidebarMenuImportHistory = $sh('sidebar_menu_import_history', 'Import history');
$sidebarMenuLogs = $sh('sidebar_menu_logs', 'Logs');
$sidebarMenuSettings = $sh('sidebar_menu_settings', 'Settings');
$sidebarMenuSiteSettings = $sh('sidebar_menu_site_settings', 'Site settings');
$sidebarAriaLabel = $sh('sidebar_aria_label', 'Admin menu');
$adminHtmlLang = function_exists('admin_shell_html_lang') ? admin_shell_html_lang() : 'en';
$adminBrandLogo = base_url('uploads/logos/fullvendor-login-brand.png');
$adminFavicon = base_url('uploads/logos/isologo.png');
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($adminHtmlLang, ENT_QUOTES, 'UTF-8') ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  
  <title><?= (!empty($title) && $title != '')? $title.' | ' : ''; ?><?= SITENAME ?></title>
  <link rel="icon" type="image/png" sizes="16x16" href="<?= htmlspecialchars($adminFavicon, ENT_QUOTES, 'UTF-8') ?>">
  <link rel="stylesheet" href="<?= base_url('backend/vendors/mdi/css/materialdesignicons.min.css')?>">
  <link rel="stylesheet" href="<?= base_url('backend/vendors/flag-icon-css/css/flag-icon.min.css')?>">
  <link rel="stylesheet" href="<?= base_url('backend/vendors/css/vendor.bundle.base.css')?>">
  <link rel="stylesheet" href="<?= base_url('backend/vendors/simplemde/simplemde.min.css')?>">
  <link rel="stylesheet" href="<?= base_url('backend/vendors/summernote/dist/summernote-bs4.css')?>">  
  <link rel="stylesheet" href="<?= base_url('backend/vendors/datatables.net-bs4/dataTables.bootstrap4.css')?>">  
  <link rel="stylesheet" href="<?= base_url('backend/css/bootstrap-tagsinput.css')?>">  
  <link rel="stylesheet" href="<?= base_url('backend/vendors/jquery-bar-rating/css-stars.css')?>">
  <link rel="stylesheet" href="<?= base_url('companyasset/css/font-awesome.min.css')?>"/>
  <link rel="stylesheet" href="<?= base_url('backend/css/style.css')?>">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@9.17.2/dist/sweetalert2.min.css">
  <link rel="stylesheet" href="<?= base_url('companyasset/css/datepicker.css')?>"/>
  <link href="<?= base_url('companyasset/css/select2.min.css') ?>" rel="stylesheet" />
  <script type="text/javascript" src="<?= base_url('companyasset/js/jquery-3.5.1.js') ?>"></script>


  <style>

    .form-group label {
      font-size: 14px;
      line-height: 1;      
      vertical-align: top;
      margin-bottom: 0.5rem;
      font-weight: 600;
     
    }
    .form-group label.label-especial {
      height: 2.875rem;
    }

    .especial_label {
      padding: 10px;
      cursor: pointer;
    }

    .btn-check:checked + .especial_label {
      background-color: #e2e6ea;
    }

    /* Admin shell — misma línea visual que login (claro, rojo #dc2626, bordes suaves) */
    :root {
      --admin-accent: #dc2626;
      --admin-accent-muted: rgba(220, 38, 38, 0.45);
      --admin-accent-soft: rgba(220, 38, 38, 0.12);
      --admin-accent-bg: rgba(220, 38, 38, 0.07);
      --admin-border: rgba(0, 0, 0, 0.08);
      --admin-border-input: rgba(0, 0, 0, 0.12);
      --admin-text: #1a1a1a;
      --admin-radius-lg: 16px;
      --admin-radius-md: 12px;
      --admin-radius-sm: 10px;
      --admin-page-bg: linear-gradient(165deg, #f5f5f7 0%, #ececf0 45%, #f0eeef 100%);
    }

    body {
      color: var(--admin-text);
      background: var(--admin-page-bg);
    }

    .container-scroller {
      background: var(--admin-page-bg);
    }

    .horizontal-menu {
      position: relative;
      z-index: 1030;
    }

    .horizontal-menu .top-navbar {
      position: relative;
      overflow: visible;
      background:
        linear-gradient(165deg, rgba(255, 255, 255, 0.98) 0%, rgba(248, 248, 250, 0.98) 42%, rgba(252, 250, 251, 0.99) 100%),
        radial-gradient(120% 85% at 0% -10%, rgba(220, 38, 38, 0.11), transparent 52%),
        radial-gradient(95% 75% at 100% 0%, rgba(185, 28, 28, 0.08), transparent 48%),
        radial-gradient(70% 55% at 92% 105%, rgba(239, 68, 68, 0.07), transparent 50%) !important;
      border-bottom: 1px solid var(--admin-border) !important;
      box-shadow: 0 4px 24px rgba(0, 0, 0, 0.055);
    }
    .horizontal-menu .top-navbar::before {
      content: "";
      position: absolute;
      left: -6%;
      top: 8%;
      width: min(320px, 34vw);
      height: 120%;
      max-height: 140px;
      background: linear-gradient(165deg, #ef4444, #b91c1c);
      opacity: 0.07;
      transform: skewX(-8deg);
      border-radius: 22px;
      pointer-events: none;
      z-index: 0;
    }
    .horizontal-menu .top-navbar .container-fluid {
      position: relative;
      z-index: 1;
    }
    .horizontal-menu .top-navbar .navbar-menu-wrapper {
      position: relative;
      z-index: 2;
    }
    .horizontal-menu .top-navbar::after {
      content: "";
      position: absolute;
      left: 0;
      right: 0;
      bottom: 0;
      height: 3px;
      pointer-events: none;
      background: linear-gradient(
        90deg,
        rgba(26, 26, 26, 0.2) 0%,
        rgba(26, 26, 26, 0.2) 6%,
        var(--admin-accent) 6%,
        var(--admin-accent) 94%,
        rgba(26, 26, 26, 0.2) 94%,
        rgba(26, 26, 26, 0.2) 100%
      );
      opacity: 0.85;
      z-index: 0;
    }

    .horizontal-menu .top-navbar .navbar-menu-wrapper .navbar-nav .nav-item.nav-profile.dropdown {
      position: relative;
    }

    .horizontal-menu .top-navbar .navbar-menu-wrapper .navbar-nav .nav-item.dropdown .dropdown-menu.navbar-dropdown {
      position: absolute !important;
      top: calc(100% + 0.35rem) !important;
      right: 0 !important;
      left: auto !important;
      margin-top: 0;
      min-width: 11.5rem;
      z-index: 5;
      border-radius: var(--admin-radius-md);
      border: 1px solid var(--admin-border);
      box-shadow: 0 8px 28px rgba(0, 0, 0, 0.12);
    }

    @media (max-width: 991px) {
      .horizontal-menu .top-navbar .navbar-menu-wrapper .navbar-nav .nav-item.dropdown .dropdown-menu.navbar-dropdown {
        right: 0 !important;
        left: auto !important;
        width: auto;
        min-width: 11.5rem;
      }
    }

    .horizontal-menu .top-navbar .navbar-brand-wrapper.admin-navbar-brand-shell {
      flex: 0 0 auto;
      justify-content: flex-start !important;
      padding: 0.35rem 0.75rem 0.35rem 0.5rem;
      margin: 0.35rem 0.5rem 0.35rem 0.25rem;
      background: #fafafa;
      border: 1px solid rgba(0, 0, 0, 0.08);
      border-radius: var(--admin-radius-md);
      box-shadow: inset 0 0 0 1px rgba(220, 38, 38, 0.06);
    }
    .horizontal-menu .top-navbar .navbar-brand.admin-navbar-brand-logo-wrap {
      display: inline-flex;
      align-items: center;
      gap: 0.65rem;
      margin: 0;
      padding: 0.2rem 0.35rem;
    }
    .horizontal-menu .top-navbar .admin-navbar-site-logo-mark {
      width: 38px;
      height: 38px;
      object-fit: contain;
      border-radius: 10px;
      border: 1px solid rgba(0, 0, 0, 0.06);
      background: #fff;
      box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
    }
    .horizontal-menu .top-navbar .admin-navbar-fullvendor-mark {
      height: 34px;
      width: auto;
      max-width: min(200px, 38vw);
      object-fit: contain;
      vertical-align: middle;
    }
    .horizontal-menu .top-navbar .brand-logo-mini.admin-navbar-brand-logo-wrap .admin-navbar-fullvendor-mini {
      height: 30px;
      width: auto;
      max-width: 120px;
      object-fit: contain;
    }

    .horizontal-menu .top-navbar .nav-item.nav-profile .nav-link.admin-navbar-profile-trigger {
      display: flex;
      align-items: center;
      padding: 0.45rem 0.65rem;
      border-radius: var(--admin-radius-md);
      border: 1px solid rgba(0, 0, 0, 0.06);
      background: linear-gradient(180deg, #ffffff 0%, #fafafa 100%);
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
      transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .horizontal-menu .top-navbar .nav-item.nav-profile .nav-link.admin-navbar-profile-trigger:hover {
      border-color: rgba(220, 38, 38, 0.25);
      box-shadow: 0 4px 16px rgba(220, 38, 38, 0.08);
    }
    .horizontal-menu .top-navbar .admin-navbar-profile-block {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      text-align: left;
      padding-left: 0.1rem;
    }
    .horizontal-menu .top-navbar .admin-navbar-role-badge {
      display: inline-flex;
      align-items: center;
      padding: 0.28rem 0.75rem;
      margin-bottom: 0.28rem;
      font-size: 0.65rem;
      font-weight: 800;
      letter-spacing: 0.11em;
      text-transform: uppercase;
      color: #ffffff;
      background: linear-gradient(135deg, #2a2a2a 0%, #171717 100%);
      border: 1px solid rgba(0, 0, 0, 0.12);
      border-radius: 999px;
      box-shadow:
        inset 0 -2px 0 rgba(220, 38, 38, 0.88),
        0 2px 8px rgba(220, 38, 38, 0.15);
    }
    .horizontal-menu .top-navbar .admin-navbar-online-line {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      font-size: 0.78rem;
      font-weight: 600;
      color: #3f3f46;
    }
    .horizontal-menu .top-navbar .admin-navbar-online-dot {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: #22c55e;
      box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.25);
      flex-shrink: 0;
    }
    .horizontal-menu .top-navbar .admin-navbar-chevron {
      font-size: 1rem;
      color: var(--admin-accent);
      opacity: 0.85;
    }
    .horizontal-menu .top-navbar .navbar-dropdown .admin-navbar-dropdown-icon {
      color: var(--admin-accent) !important;
    }

    .admin-page-body-wrapper {
      display: flex;
      flex-direction: row;
      align-items: stretch;
      width: 100%;
      flex: 1;
      min-height: 0;
      border-top: 1px solid var(--admin-accent-soft);
      background: transparent;
    }

    #admin-sidebar {
      width: 250px;
      flex: 0 0 250px;
      overflow-y: auto;
      overflow-x: hidden;
      background: #ffffff;
      border-right: 1px solid var(--admin-border);
      box-shadow: 4px 0 24px rgba(0, 0, 0, 0.03);
    }
    #admin-sidebar > nav > .nav > .nav-item {
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
      padding: 0.15rem 0.5rem;
    }
    #admin-sidebar > nav > .nav > .nav-item:last-child {
      border-bottom: none;
    }
    #admin-sidebar .nav-link {
      color: #3f3f46;
      font-weight: 500;
      padding: 0.55rem 0.85rem;
      margin: 0.1rem 0;
      border-radius: var(--admin-radius-sm);
      border-left: 3px solid transparent;
    }
    #admin-sidebar .nav-link:hover {
      background: var(--admin-accent-bg);
      border-left-color: var(--admin-accent-muted);
      color: var(--admin-text);
    }
    #admin-sidebar .nav-link.active {
      background: var(--admin-accent-bg);
      border-left-color: var(--admin-accent);
      color: var(--admin-text);
    }
    #admin-sidebar .nav.flex-column.pl-3 {
      border-left: 1px solid var(--admin-accent-soft);
      margin-left: 0.65rem;
      padding-top: 0.25rem;
      padding-bottom: 0.35rem;
    }
    #admin-sidebar .nav.flex-column.pl-3 .nav-link {
      border-radius: 8px;
      padding-left: 0.65rem;
    }
    #admin-sidebar .nav.flex-column.pl-3 .nav-item + .nav-item {
      border-top: 1px solid rgba(0, 0, 0, 0.05);
    }
    #admin-sidebar .nav-link .menu-icon {
      margin-right: 0.5rem;
      font-size: 1.1rem;
      vertical-align: middle;
      color: var(--admin-accent);
      opacity: 0.9;
    }

    .admin-main-panel-flex {
      flex: 1 1 auto;
      min-width: 0;
      width: 100%;
      background: transparent;
    }

    .container-scroller .main-panel .content-wrapper,
    .container-scroller .main-panel > .container-fluid {
      background: transparent;
    }

    .container-scroller .main-panel .page-header .page-title {
      color: var(--admin-text);
      font-weight: 700;
      letter-spacing: -0.02em;
    }

    .container-scroller .main-panel .card {
      border-radius: var(--admin-radius-md);
      border: 1px solid var(--admin-border);
      box-shadow: 0 4px 24px rgba(0, 0, 0, 0.05);
      overflow: hidden;
    }

    /* DataTables Bootstrap: márgenes negativos de .row + overflow:hidden del card recortaban buscador y pie */
    .container-scroller .main-panel .card .dataTables_wrapper.dt-bootstrap4 > .row {
      margin-left: 0;
      margin-right: 0;
    }
    .container-scroller .main-panel .card .card-title,
    .container-scroller .main-panel .card h3,
    .container-scroller .main-panel .card h4 {
      color: var(--admin-text);
    }

    .container-scroller .main-panel .table {
      border-collapse: separate;
      border-spacing: 0;
    }
    .container-scroller .main-panel .table thead th {
      border-bottom: 1px solid var(--admin-border);
      color: #52525b;
      font-weight: 600;
      font-size: 0.8rem;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }
    .container-scroller .main-panel .table td {
      border-color: rgba(0, 0, 0, 0.05);
      vertical-align: middle;
    }

    .container-scroller .main-panel .form-control {
      border-radius: var(--admin-radius-sm);
      border-color: var(--admin-border-input);
    }
    .container-scroller .main-panel .form-control:focus {
      border-color: rgba(220, 38, 38, 0.45);
      box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.12);
    }

    .container-scroller .main-panel .btn {
      border-radius: var(--admin-radius-sm);
    }
    .container-scroller .main-panel .btn-success {
      background-color: var(--admin-accent) !important;
      border-color: rgba(0, 0, 0, 0.08) !important;
    }
    .container-scroller .main-panel .btn-success:hover {
      background-color: #1f1f1f !important;
      border-color: rgba(0, 0, 0, 0.12) !important;
      box-shadow: inset 0 -2px 0 rgba(220, 38, 38, 0.85);
    }
    .container-scroller .main-panel .btn-primary {
      background-color: var(--admin-accent) !important;
      border-color: rgba(0, 0, 0, 0.08) !important;
    }
    .container-scroller .main-panel .btn-primary:hover {
      background-color: #1f1f1f !important;
      border-color: rgba(0, 0, 0, 0.12) !important;
    }

    .container-scroller .main-panel .alert {
      border-radius: var(--admin-radius-sm);
      border-width: 1px;
    }
    .container-scroller .main-panel .modal-content {
      border-radius: var(--admin-radius-lg);
      border: 1px solid var(--admin-border);
      box-shadow: 0 12px 48px rgba(0, 0, 0, 0.12);
    }

    .container-scroller .footer {
      border-top: 1px solid var(--admin-border);
      background: rgba(255, 255, 255, 0.85);
      backdrop-filter: blur(6px);
    }

    @media (max-width: 991px) {
      #admin-sidebar {
        position: fixed;
        left: 0;
        top: 70px;
        bottom: 0;
        z-index: 1029;
        transform: translateX(-100%);
        transition: transform 0.22s ease;
        border-radius: 0 var(--admin-radius-md) var(--admin-radius-md) 0;
        box-shadow: 8px 0 32px rgba(0, 0, 0, 0.1);
        border-right: 1px solid var(--admin-border);
      }
      #admin-sidebar.admin-sidebar-open {
        transform: translateX(0);
      }
    }

  </style>
</head>
<body>
  <div class="container-scroller">
    <div class="horizontal-menu">
      <nav class="navbar top-navbar col-lg-12 col-12 p-0">
        <div class="container-fluid">
          <div class="text-center navbar-brand-wrapper d-flex align-items-center admin-navbar-brand-shell">
            <a class="navbar-brand brand-logo admin-navbar-brand-logo-wrap" href="<?= admin_url() ?>">
              <img src="<?= htmlspecialchars($adminBrandLogo, ENT_QUOTES, 'UTF-8') ?>" class="admin-navbar-fullvendor-mark" alt="<?= htmlspecialchars($navBrandAlt, ENT_QUOTES, 'UTF-8') ?>" />
            </a>
            <a class="navbar-brand brand-logo-mini admin-navbar-brand-logo-wrap" href="<?= admin_url() ?>">
              <img src="<?= htmlspecialchars($adminBrandLogo, ENT_QUOTES, 'UTF-8') ?>" class="admin-navbar-fullvendor-mini" alt="<?= htmlspecialchars($navBrandAlt, ENT_QUOTES, 'UTF-8') ?>" />
            </a>
          </div>
          <div class="navbar-menu-wrapper d-flex align-items-center justify-content-end">
            <ul class="navbar-nav navbar-nav-right">
              <li class="nav-item nav-profile dropdown">
                <a class="nav-link admin-navbar-profile-trigger" id="profileDropdown" href="javascript:void(0)" data-toggle="dropdown" aria-expanded="false">
                  <div class="nav-profile-text admin-navbar-profile-block">
                    <span class="admin-navbar-role-badge"><?= htmlspecialchars($navRoleSuper, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="admin-navbar-online-line">
                      <span class="admin-navbar-online-dot" aria-hidden="true"></span>
                      <?= htmlspecialchars($navOnline, ENT_QUOTES, 'UTF-8') ?>
                      <i class="mdi mdi-chevron-down admin-navbar-chevron" aria-hidden="true"></i>
                    </span>
                  </div>
                </a>
                <div class="dropdown-menu navbar-dropdown" aria-labelledby="profileDropdown">
                  <a class="dropdown-item" href="<?= admin_url('profile') ?>">
                    <i class="mdi mdi-account-outline mr-2 admin-navbar-dropdown-icon"></i> <?= htmlspecialchars($navProfile, ENT_QUOTES, 'UTF-8') ?>
                  </a>
                  <a class="dropdown-item" href="<?= admin_url('change_password') ?>">
                    <i class="mdi mdi-lock-outline mr-2 admin-navbar-dropdown-icon"></i> <?= htmlspecialchars($navChangePassword, ENT_QUOTES, 'UTF-8') ?>
                  </a>
                  <div class="dropdown-divider"></div>
                  <a class="dropdown-item" href="<?= admin_url('logout')?>">
                    <i class="mdi mdi-logout mr-2 admin-navbar-dropdown-icon"></i> <?= htmlspecialchars($navSignOut, ENT_QUOTES, 'UTF-8') ?> </a>
                  </div>
                </li>
              </ul>
              <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-toggle="horizontal-menu-toggle">
                <span class="mdi mdi-menu"></span>
              </button>
            </div>
          </div>
        </nav>
        </div>
        <div class="container-fluid page-body-wrapper admin-page-body-wrapper p-0">
          <aside id="admin-sidebar" class="admin-sidebar" aria-label="<?= htmlspecialchars($sidebarAriaLabel, ENT_QUOTES, 'UTF-8') ?>">
            <nav class="pt-3 pb-4">
              <ul class="nav flex-column">
              <li class="nav-item">
                <a class="nav-link <?= (!empty($page) && $page == 'dashboard')? 'active' : ''; ?>" href="<?= admin_url('dashboard')?>">
                  <i class="mdi mdi-compass-outline menu-icon"></i>
                  <span class="menu-title"><?= htmlspecialchars($sidebarMenuDashboard, ENT_QUOTES, 'UTF-8') ?></span>
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link <?= (!empty($page) && $page == 'category')? 'active' : ''; ?>" href="<?= admin_url('category/lists')?>">
                  <i class="mdi mdi-shape-outline menu-icon"></i>
                  <span class="menu-title"><?= htmlspecialchars($sidebarMenuCompaniesCategories, ENT_QUOTES, 'UTF-8') ?></span>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= admin_url('company/lists') ?>" class="nav-link <?= (!empty($page) && $page == 'company')? 'active' : ''; ?>">
                  <i class="mdi mdi-office-building menu-icon"></i>
                  <span class="menu-title"><?= htmlspecialchars($sidebarMenuCompanies, ENT_QUOTES, 'UTF-8') ?></span>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= admin_url('subscriptions/lists') ?>" class="nav-link <?= (!empty($page) && $page == 'subscriptions')? 'active' : ''; ?>">
                  <i class="mdi mdi-link-variant menu-icon" aria-hidden="true"></i>
                  <span class="menu-title"><?= htmlspecialchars($sidebarMenuSubscriptions, ENT_QUOTES, 'UTF-8') ?></span>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= admin_url('invoices/lists') ?>" class="nav-link <?= (!empty($page) && $page == 'invoices')? 'active' : ''; ?>">
                  <i class="mdi mdi-receipt menu-icon" aria-hidden="true"></i>
                  <span class="menu-title"><?= htmlspecialchars($sidebarMenuInvoices, ENT_QUOTES, 'UTF-8') ?></span>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= admin_url('plan/lists') ?>" class="nav-link <?= (!empty($page) && $page == 'plan')? 'active' : ''; ?>">
                  <i class="mdi mdi-credit-card-multiple menu-icon" aria-hidden="true"></i>
                  <span class="menu-title"><?= htmlspecialchars($sidebarMenuPlan) ?></span>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= admin_url('stripesettings') ?>" class="nav-link <?= (!empty($page) && $page == 'stripe_settings')? 'active' : ''; ?>">
                  <i class="mdi mdi-settings menu-icon" aria-hidden="true"></i>
                  <span class="menu-title"><?= htmlspecialchars($sidebarMenuStripeSettings, ENT_QUOTES, 'UTF-8') ?></span>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= admin_url('cms') ?>" class="nav-link <?= (!empty($page) && $page == 'cms')? 'active' : ''; ?>">
                  <i class="mdi mdi-file-document-outline menu-icon"></i>
                  <span class="menu-title"><?= htmlspecialchars($sidebarMenuCms) ?></span>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= admin_url('import/lists') ?>" class="nav-link <?= (!empty($page) && $page == 'import')? 'active' : ''; ?>">
                  <i class="mdi mdi-import menu-icon"></i>
                  <span class="menu-title"><?= htmlspecialchars($sidebarMenuImportHistory, ENT_QUOTES, 'UTF-8') ?></span>
                </a>
              </li>
              <li class="nav-item">
                <a href="<?= admin_url('logs/lists') ?>" class="nav-link <?= (!empty($page) && $page == 'logs')? 'active' : ''; ?>">
                  <i class="mdi mdi-database menu-icon"></i>
                  <span class="menu-title"><?= htmlspecialchars($sidebarMenuLogs, ENT_QUOTES, 'UTF-8') ?></span>
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link d-flex justify-content-between align-items-center <?= (!empty($page) && $page == 'settings')? 'active' : ''; ?>" data-toggle="collapse" href="#adminSettingsSubmenu" role="button" aria-expanded="<?= (!empty($page) && $page == 'settings') ? 'true' : 'false' ?>" aria-controls="adminSettingsSubmenu">
                  <span><i class="mdi mdi-cogs menu-icon"></i><span class="menu-title"><?= htmlspecialchars($sidebarMenuSettings, ENT_QUOTES, 'UTF-8') ?></span></span>
                  <i class="mdi mdi-chevron-down small"></i>
                </a>
                <div class="collapse <?= (!empty($page) && $page == 'settings') ? 'show' : '' ?>" id="adminSettingsSubmenu">
                  <ul class="nav flex-column pl-3 border-0">
                    <li class="nav-item"><a class="nav-link py-2 small" href="<?= admin_url('settings/site_settings') ?>"><?= htmlspecialchars($sidebarMenuSiteSettings, ENT_QUOTES, 'UTF-8') ?></a></li>
                  </ul>
                </div>
              </li>
              </ul>
            </nav>
          </aside>
          <div class="main-panel admin-main-panel-flex">

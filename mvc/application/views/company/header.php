<?php

$modulos_user = $this->Helper->get_modules();
$user_have_rol = count($modulos_user) > 1 ? true : false;
$status_user = $this->Helper->get_status_user_access();

/**
 * @var $company object the current company
 * @var $user array the current authenticated user
 * @var $setting object general app settings
 * @var $commonTexts array
 * @var $langdef array
 * @var $main_navigation array The navigation array
 * @var $config_menu array The configuration menu definition array
 */
$langdef = $langdef ?? [];

$navUriLooksLikeProductsChild = static function (): bool {
    if (! function_exists('get_instance')) {
        return false;
    }
    $CI = get_instance();
    if (! isset($CI->uri) || ! is_object($CI->uri)) {
        return false;
    }
    $s2 = (string) $CI->uri->segment(2);
    if ($s2 === 'categories') {
        return true;
    }
    if ($s2 !== 'products') {
        return false;
    }
    $s3 = (string) $CI->uri->segment(3);

    return $s3 === '' || $s3 === 'inventory' || in_array($s3, ['view', 'edit', 'add'], true);
};

$navUriLooksLikeSettingsChild = static function (): bool {
    if (! function_exists('get_instance')) {
        return false;
    }
    $CI = get_instance();
    if (! isset($CI->uri) || ! is_object($CI->uri)) {
        return false;
    }
    $s2 = (string) $CI->uri->segment(2);
    if ($s2 === '') {
        return false;
    }
    $settingsPrefixes = ['units', 'terms', 'sellerprofile', 'currencies', 'groups', 'users', 'userprofile', 'warehouses', 'sendtemplate'];
    if (in_array($s2, $settingsPrefixes, true)) {
        return true;
    }
    if ($s2 === 'warehouse') {
        $s3 = (string) $CI->uri->segment(3);
        $warehouseOrderRoutes = ['orders', 'warehouse_orderview', 'orderlists'];

        return ! in_array($s3, $warehouseOrderRoutes, true);
    }

    return false;
};

$navUriLooksLikeOrdersChild = static function (): bool {
    if (! function_exists('get_instance')) {
        return false;
    }
    $CI = get_instance();
    if (! isset($CI->uri) || ! is_object($CI->uri)) {
        return false;
    }
    $s2 = (string) $CI->uri->segment(2);
    $s3 = (string) $CI->uri->segment(3);
    if (in_array($s2, ['orders', 'order', 'cart'], true)) {
        return true;
    }
    if ($s2 === 'warehouse' && in_array($s3, ['orders', 'warehouse_orderview', 'orderlists'], true)) {
        return true;
    }

    return $s2 === 'inventory';
};

$router = $this->router;
$whClass = strtolower((string) ($router->class ?? ''));
$whMethod = strtolower((string) ($router->method ?? ''));
$isWarehouseOrdersRoute = ($whClass === 'warehouse' && in_array($whMethod, ['orders', 'orderlists', 'warehouse_orderview'], true));

$navUrlIsWarehouseOrders = static function ($url): bool {
    $u = trim((string) $url, '/');
    if ($u === '') {
        return false;
    }

    return $u === 'warehouse/orders' || strpos($u, 'warehouse/orders/') === 0;
};

// Si no se pasó main_navigation (ej. controlador Order), usar navegación por defecto para que los enlaces a otros módulos funcionen
if (!isset($main_navigation) || !is_array($main_navigation)) {
    $main_navigation = [
        'dashboard' => ['url' => 'dashboard', 'label' => $langdef['dashboard'] ?? 'Dashboard', 'icon' => 'fa-solid fa-house'],
        'settings' => [
            'url' => 'null',
            'label' => $langdef['settings'] ?? 'Settings',
            'icon' => 'fa-solid fa-gear',
            'subitems' => [
                'units' => ['url' => 'units/lists', 'label' => $langdef['nav_units'] ?? 'Units', 'icon' => 'fa-solid fa-ruler-combined'],
                'payment_terms' => ['url' => 'terms/lists', 'label' => $langdef['nav_payment_terms'] ?? 'Payment terms', 'icon' => 'fa-solid fa-file-invoice-dollar'],
                'sellers_profile' => ['url' => 'sellerprofile/lists', 'label' => $langdef['nav_sellers_profile'] ?? 'Sellers profile', 'icon' => 'fa-solid fa-id-card'],
                'currency' => ['url' => 'currencies/lists', 'label' => $langdef['nav_currency'] ?? 'Currency', 'icon' => 'fa-solid fa-coins'],
                'customer_groups' => ['url' => 'groups', 'label' => $langdef['nav_customer_groups'] ?? 'Customer groups', 'icon' => 'fa-solid fa-users'],
                'users' => ['url' => 'users', 'label' => $langdef['user'] ?? 'Users', 'icon' => 'fa-solid fa-user-lock'],
                'warehouses' => ['url' => 'warehouses', 'label' => $langdef['warehouse'] ?? 'Warehouse', 'icon' => 'fa-solid fa-house-user'],
                'send_templates' => ['url' => 'sendtemplate', 'label' => $langdef['nav_send_templates'] ?? 'Send templates', 'icon' => 'fa-solid fa-envelope-open-text'],
            ],
        ],
        'products' => [
            'url' => 'null',
            'label' => $langdef['products'] ?? 'Products',
            'icon' => 'fa-solid fa-box',
            'subitems' => [
                'manage_products' => [
                    'url' => 'products',
                    'label' => $langdef['nav_manage_products'] ?? 'Manage Products',
                    'icon' => 'fa-solid fa-boxes-stacked',
                ],
                'product_categories' => [
                    'url' => 'categories',
                    'label' => $langdef['nav_categories'] ?? 'Categories',
                    'icon' => 'fa-solid fa-folder-tree',
                ],
                'product_inventory' => [
                    'url' => 'products/inventory',
                    'label' => $langdef['nav_product_inventory'] ?? 'Inventory',
                    'icon' => 'fa-solid fa-clipboard-list',
                ],
            ],
        ],
        'customer' => ['url' => 'customers', 'label' => $langdef['customer'] ?? 'Customers', 'icon' => 'fa-solid fa-user-group'],
        'order' => [
            'url' => 'null',
            'label' => $langdef['order'] ?? 'Orders',
            'icon' => 'fa-solid fa-cart-shopping',
            'subitems' => [
                'order' => ['url' => 'orders', 'label' => $langdef['order'] ?? 'Orders', 'icon' => 'fa-solid fa-paper-plane'],
                'warehouseorders' => ['url' => 'warehouse/orders', 'label' => $langdef['warehouseorders'] ?? 'Warehouse Orders', 'icon' => 'fa-solid fa-paste'],
                'inventory' => ['url' => 'inventory/order', 'label' => $langdef['inventory'] ?? 'Inventory', 'icon' => 'fa-solid fa-warehouse'],
                'opencart' => ['url' => 'cart/abandoned', 'label' => $langdef['opencart'] ?? 'Open Cart', 'icon' => 'fa-solid fa-cart-shopping'],
            ],
        ],
        'catalog' => ['url' => 'catalog', 'label' => $langdef['mnucatalog'] ?? ($langdef['catalog'] ?? 'Catalog'), 'icon' => 'fa-solid fa-table-cells'],
    ];
}
if (!isset($config_menu) || !is_array($config_menu)) {
    $config_menu = [
        'url' => '',
        'label' => $langdef['profile'] ?? 'Profile',
        'icon' => 'fa fa-cog fa-fw fa-lg',
        'subitems' => [
            'profile' => ['url' => 'profile', 'label' => $langdef['profile'] ?? 'Profile', 'icon' => 'fa fa-cog fa-fw fa-lg'],
            'import' => ['url' => 'import/lists', 'label' => $langdef['import'] ?? 'Import', 'icon' => 'mdi mdi-import'],
            'logs' => ['url' => 'logs/lists', 'label' => $langdef['logs'] ?? 'Logs', 'icon' => 'mdi mdi-database'],
        ]
    ];
}
if (!isset($company) && !empty($this->Helper)) {
    $company = $this->Helper->get_company_info();
}
if (!isset($user)) {
    $user = null;
    if (!empty($this->session)) {
        $user = $this->session->userdata('user');
        if ($user === null || $user === false || $user === '') {
            $user = $this->session->userdata('user_logueado_data');
        }
    }
}
if (!is_array($user) && !is_object($user)) {
    $user = ['name' => '', 'email' => ''];
}
$navUserName = trim((string) (is_object($user) ? ($user->name ?? '') : ($user['name'] ?? '')));
if (!isset($commonTexts)) {
    $commonTexts = ['bnprofile' => 'Profile', 'bnsignout' => 'Sign out'];
}
$navUserPillLabel = $navUserName !== ''
    ? $navUserName
    : (string) ($commonTexts['bnprofile'] ?? '');
$navUserEmail = trim((string) (is_object($user) ? ($user->email ?? '') : ($user['email'] ?? '')));
$navUserRole = '';
foreach (['profile_name', 'rol_name', 'role_name', 'position'] as $navRoleKey) {
    $navRoleValue = is_object($user) ? ($user->{$navRoleKey} ?? null) : ($user[$navRoleKey] ?? null);
    if (is_string($navRoleValue) && trim($navRoleValue) !== '') {
        $navUserRole = trim($navRoleValue);
        break;
    }
}
if ($navUserRole === '') {
    $navUserRole = $navUserEmail;
}
$navMenuLabel = '';
$navNotifyLabel = '';
$navNotifyEmpty = '';
$navThemeLabel = '';
if (function_exists('fv_lang_json_flat_section') && function_exists('fv_resolve_company_language_id')) {
    $navLangFlat = fv_lang_json_flat_section(fv_resolve_company_language_id(), 'company-navigation');
    $navMenuLabel = (string) ($navLangFlat['nav_menu'] ?? '');
    $navNotifyLabel = (string) ($navLangFlat['nav_notifications'] ?? '');
    $navNotifyEmpty = (string) ($navLangFlat['nav_no_notifications'] ?? '');
    $navThemeLabel = (string) ($navLangFlat['nav_theme'] ?? '');
}
$navBrandName = trim((string) (defined('SITENAME') ? SITENAME : ''));

?>
<?php
$this->load->view('company/layouts/loader');

$globalSearchLang = is_array($globalSearchLang ?? null) ? $globalSearchLang : [];
$gs = static function (string $key, string $fallback = '') use ($globalSearchLang): string {
    $value = $globalSearchLang[$key] ?? $fallback;
    return is_string($value) ? $value : $fallback;
};
$navActiveClass = 'fv-nav-link fv-nav-link--active';
$navLinkClass = 'fv-nav-link';
if (empty($page) && function_exists('fv_company_nav_active_key')) {
    $resolvedNavPage = fv_company_nav_active_key();
    if ($resolvedNavPage !== null && $resolvedNavPage !== '') {
        $page = $resolvedNavPage;
    }
}
?>
<script>window.fvGlobalSearchLang = <?= json_encode($globalSearchLang, JSON_UNESCAPED_UNICODE) ?>;</script>

<header class="fv-company-nav navbar-fijo navbar navbar-expand-lg">
    <div class="fv-company-nav__shell">
    <div class="fv-company-nav__inner">
        <a class="fv-company-nav__brand navbar-brand" href="<?= company_url('dashboard') ?>" title="<?= htmlspecialchars($navBrandName, ENT_QUOTES, 'UTF-8') ?>">
            <span class="fv-nav-logo-wrap">
                <img src="<?= base_url('images/logomoderno.png') ?>"
                     alt="<?= htmlspecialchars($navBrandName, ENT_QUOTES, 'UTF-8') ?>">
            </span>
        </a>

        <nav class="fv-company-nav__menu collapse navbar-collapse" id="fvCompanyNav">
                <?php
                if (!$user_have_rol): ?>
                    <ul class="navbar-nav fv-company-nav__links d-flex flex-nowrap align-items-center">
                        <?php
                        $i = 0; ?>
                        <?php
                        foreach ($main_navigation as $navName => $nav):
                            $i++;
                            $active = ! empty($page) && $page == $navName;
                            if (! $active && $navName === 'order' && $navUriLooksLikeOrdersChild()) {
                                $active = true;
                            }
                            if (! $active && $navName === 'settings' && $navUriLooksLikeSettingsChild()) {
                                $active = true;
                            }
                            if (! $active && $navName === 'products' && $navUriLooksLikeProductsChild()) {
                                $active = true;
                            }
                            ?>

                            <?php
                            if (isset($nav['subitems'])) : ?>
                                <li class="nav-item dropdown <?= ($active) ? 'active' : ''; ?>">
                                    <a class="nav-link dropdown-toggle p-2 rounded <?= ($active) ? $navActiveClass : $navLinkClass; ?>"
                                       id="navbarDropdown-<?= $i ?>" href="#" data-toggle="dropdown"
                                       aria-haspopup="true"
                                       aria-expanded="false">
                                        <i class="<?= $nav['icon'] ?>"></i> <?= $nav['label'] ?>
                                    </a>
                                    <ul class="dropdown-menu dropwdown__customized fv-nav-dropdown"
                                        aria-labelledby="navbarDropdown-<?= $i ?>">
                                        <?php
                                        foreach ($nav['subitems'] as $navHijoName => $nav_hijo): ?>
                                            <li>
                                                <a class="dropdown-item"
                                                   href="<?= company_url($nav_hijo['url']) ?>">
                                                    <i class="<?= $nav_hijo['icon'] ?>"></i> <?= $nav_hijo['label'] ?>
                                                </a>
                                            </li>
                                        <?php
                                        endforeach; ?>
                                    </ul>
                                </li>
                            <?php
                            else: ?>
                                <li class="nav-item <?= ($active) ? 'active' : ''; ?>">
                                    <a class="nav-link p-2 rounded <?= ($active) ? $navActiveClass : $navLinkClass; ?>"
                                       href="<?= company_url($nav['url']) ?>"><i class="<?= $nav['icon'] ?>"></i>
                                        <?= $nav['label'] ?></a>
                                </li>
                            <?php
                            endif; ?>
                        <?php
                        endforeach; ?>
                    </ul>
                <?php
                else: ?>
                    <!-- Modulos Personalizados -->
                    <ul class="navbar-nav fv-company-nav__links d-flex flex-nowrap align-items-center">
                        <?php
                        foreach ($modulos_user as $key => $modul): ?>
                            <?php
                            if ($modul->config == null) : ?>
                                <?php
                                if (isset($modul->children)): ?>
                                    <li class="nav-item dropdown <?= (!empty($page) && $page == $modul->identify) ? 'active' : ''; ?>">
                                        <a class="nav-link dropdown-toggle p-2 rounded <?= (!empty($page) && $page == $modul->identify) ? $navActiveClass : $navLinkClass; ?>"
                                           id="navbarDropdown-<?= $key ?>" href="#" data-toggle="dropdown"
                                           aria-haspopup="true"
                                           aria-expanded="false">
                                            <i class="<?= $modul->icon ?>"></i> <?= $modul->modul_name ?>
                                        </a>
                                        <ul class="dropdown-menu dropwdown__customized fv-nav-dropdown"
                                            aria-labelledby="navbarDropdown-<?= $key ?>">

                                            <?php
                                            foreach ($modul->children as $chil_key => $chil): ?>
                                                <li>
                                                    <a class="dropdown-item"
                                                       href="<?= company_url($chil->url_nav) ?>">
                                                        <i class="<?= $chil->icon ?>"></i> <?= $chil->modul_name ?>
                                                    </a>
                                                </li>
                                            <?php
                                            endforeach; ?>
                                        </ul>
                                    </li>
                                <?php
                                else: ?>
                                    <?php
                                    $flatModActive = ! empty($page) && $page == $modul->identify;
                                    if (! $flatModActive && $isWarehouseOrdersRoute) {
                                        $flatModActive = $navUrlIsWarehouseOrders($modul->url_nav ?? '');
                                    }
                                    ?>
                                    <li class="nav-item <?= $flatModActive ? 'active' : ''; ?>">
                                        <a class="nav-link p-2 rounded <?= $flatModActive ? $navActiveClass : $navLinkClass; ?>"
                                           href="<?= company_url($modul->url_nav) ?>"><i
                                                class="<?= $modul->icon ?>"></i>
                                            <?= $modul->modul_name ?></a>
                                    </li>
                                <?php
                                endif; ?>
                            <?php
                            endif; ?>
                        <?php
                        endforeach; ?>
                    </ul>
                <?php
                endif; ?>
        </nav>

        <div class="fv-company-nav__aside">
        <div class="fv-company-nav__search" title="<?= htmlspecialchars($gs('search_placeholder', 'Buscar'), ENT_QUOTES, 'UTF-8') ?>">
            <i class="mdi mdi-magnify fv-company-nav__search-icon" aria-hidden="true"></i>
            <input type="search"
                id="fv-global-search-input"
                class="fv-company-nav__search-input"
                data-search-url="<?= htmlspecialchars(rtrim(company_url('search'), '/'), ENT_QUOTES, 'UTF-8') ?>"
                data-detail-url="<?= htmlspecialchars(rtrim(company_url('search/detail'), '/'), ENT_QUOTES, 'UTF-8') ?>"
                placeholder="<?= htmlspecialchars($gs('search_short', $gs('search_placeholder', ''))) ?>"
                autocomplete="off"
                aria-label="<?= htmlspecialchars($gs('search_placeholder', '')) ?>">
            <span class="fv-company-nav__search-kbd"><?= htmlspecialchars($gs('search_shortcut', 'Ctrl + K'), ENT_QUOTES, 'UTF-8') ?></span>
            <div id="fv-global-search-results" class="fv-global-search-dropdown is-hidden" role="listbox"></div>
        </div>

        <div class="fv-company-nav__actions">
                <ul class="navbar-nav d-flex flex-nowrap align-items-center mb-0 fv-company-nav__action-list">
                    <li class="nav-item fv-company-nav__action-item fv-company-nav__action-item--install">
                        <a href="#" role="button" id="install-button" class="fv-company-nav__icon-btn nav-link p-0" style="display: none;" hidden
                           title="<?= htmlspecialchars((string) ($langdef['install_app'] ?? $langdef['install'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                           aria-label="<?= htmlspecialchars((string) ($langdef['install_app'] ?? $langdef['install'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                           aria-hidden="true">
                            <i class="fa-solid fa-cloud-arrow-down" aria-hidden="true"></i>
                            <span class="sr-only"><?= htmlspecialchars((string) ($langdef['install_app'] ?? $langdef['install'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        </a>
                    </li>

                    <li class="nav-item dropdown fv-company-nav__action-item">
                        <button type="button" data-toggle="dropdown" class="fv-company-nav__icon-btn dropdown-toggle" aria-label="<?= htmlspecialchars($navNotifyLabel, ENT_QUOTES, 'UTF-8') ?>">
                            <i class="mdi mdi-bell-outline" aria-hidden="true"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-right dropwdown__customized fv-nav-dropdown">
                            <li><span class="dropdown-item fv-company-nav__notify-empty"><?= htmlspecialchars($navNotifyEmpty, ENT_QUOTES, 'UTF-8') ?></span></li>
                        </ul>
                    </li>
                    <li class="nav-item fv-company-nav__action-item">
                        <button type="button" class="fv-company-nav__icon-btn" id="fv-nav-theme" aria-label="<?= htmlspecialchars($navThemeLabel, ENT_QUOTES, 'UTF-8') ?>">
                            <i class="mdi mdi-white-balance-sunny" aria-hidden="true"></i>
                        </button>
                    </li>
                    <?php
                    if (!$user_have_rol) : ?>

                        <li class="nav-item dropdown fv-company-nav__action-item">
                            <button type="button" data-toggle="dropdown" class="fv-company-nav__icon-btn dropdown-toggle" aria-label="<?= htmlspecialchars((string) ($config_menu['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <i class="mdi mdi-view-grid-outline" aria-hidden="true"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-right dropwdown__customized fv-nav-dropdown">
                                <?php
                                foreach ($config_menu['subitems'] as $navName=> $nav): ?>
                                    <li>
                                        <a class="dropdown-item" href="<?= company_url($nav['url']) ?>">
                                            <i class="<?= $nav['icon'] ?>"></i> <?= $nav['label'] ?>
                                        </a>
                                    </li>
                                <?php
                                endforeach; ?>
                            </ul>
                        </li>
                    <?php else: ?>
                        <?php foreach ($modulos_user as $key => $modul): ?>
                            <?php if ($modul->config): ?>
                                <li class="nav-item dropdown fv-company-nav__action-item">
                                    <button type="button" data-toggle="dropdown" class="fv-company-nav__icon-btn dropdown-toggle" aria-label="<?= htmlspecialchars((string) ($modul->modul_name ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="mdi mdi-view-grid-outline" aria-hidden="true"></i>
                                    </button>
                                    <?php if (isset($modul->children)): ?>
                                        <ul class="dropdown-menu dropdown-menu-right dropwdown__customized fv-nav-dropdown">
                                            <?php foreach ($modul->children as $pk => $nav): ?>
                                                <li>
                                                    <a class="dropdown-item" href="<?= company_url($nav->url_nav) ?>">
                                                        <i class="<?= $nav->icon ?>"></i> <?= $nav->modul_name ?>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <li class="nav-item dropdown fv-company-nav__action-item">
                        <button type="button" data-toggle="dropdown" class="fv-company-nav__user-toggle dropdown-toggle" aria-label="<?= htmlspecialchars($navUserName !== '' ? $navUserName : $navUserPillLabel, ENT_QUOTES, 'UTF-8') ?>">
                            <?php $this->load->view('components/user_nav_avatar', ['user' => $user, 'size' => 36]); ?>
                            <span class="fv-company-nav__user-meta">
                                <span class="fv-company-nav__user-name"><?= htmlspecialchars($navUserName !== '' ? $navUserName : $navUserPillLabel, ENT_QUOTES, 'UTF-8') ?></span>
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-right mt-1 dropwdown__customized fv-nav-dropdown">
                            <li>
                                <a class="dropdown-item" href="<?= company_url('profile') ?>">
                                    <i class="mdi mdi-cached mr-2 text-success"></i> <?= $commonTexts['bnprofile'] ?>
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?= company_url('logout') ?>">
                                    <i class="mdi mdi-logout mr-2 text-primary"></i><?= $commonTexts['bnsignout'] ?>
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
        </div>
        </div>
        <span class="fv-company-nav__edge" aria-hidden="true"></span>
    </div>
    </div>
</header>
<script>
(function () {
    var key = 'fv-theme';
    var root = document.documentElement;
    var button = document.getElementById('fv-nav-theme');
    if (!button) {
        return;
    }
    function paint(theme) {
        if (theme === 'dark') {
            root.setAttribute('data-fv-theme', 'dark');
        } else {
            root.removeAttribute('data-fv-theme');
        }
        var icon = button.querySelector('i');
        if (icon) {
            icon.className = theme === 'dark' ? 'mdi mdi-weather-night' : 'mdi mdi-white-balance-sunny';
        }
    }
    var saved = '';
    try {
        saved = window.localStorage.getItem(key) || '';
    } catch (e) {
        saved = '';
    }
    paint(saved);
    button.addEventListener('click', function () {
        var next = root.getAttribute('data-fv-theme') === 'dark' ? 'light' : 'dark';
        try {
            window.localStorage.setItem(key, next);
        } catch (e) {}
        paint(next);
    });
})();
</script>

<div class="modal fade" id="fv-global-search-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content fv-global-search-modal">
            <div class="modal-header">
                <h5 class="modal-title" id="fv-global-search-modal-title"><?= htmlspecialchars($gs('modal_title', 'Detalle')) ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= htmlspecialchars($gs('modal_close', 'Cerrar')) ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="fv-global-search-modal-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= htmlspecialchars($gs('modal_close', 'Cerrar')) ?></button>
                <a href="#" id="fv-global-search-modal-open" class="btn btn-primary"><?= htmlspecialchars($gs('modal_open_record', 'Abrir registro')) ?></a>
            </div>
        </div>
    </div>
</div>

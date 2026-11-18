<?php
/**
 * Manifest PWA.
 * Usa REQUEST_URI (no SCRIPT_NAME) para no duplicar /mvc/mvc cuando el docroot es anidado.
 */
header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');

$reqPath = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/pwa-manifest.php'), PHP_URL_PATH) ?: '/pwa-manifest.php');
$reqPath = str_replace('\\', '/', $reqPath);
$basePath = rtrim(dirname($reqPath), '/');
if ($basePath === '/' || $basePath === '.' || $basePath === '\\') {
    $basePath = '';
}

// Evitar /mvc/mvc si quedó un alias anidado en SCRIPT_NAME histórico.
if (preg_match('#(/[^/]+)/\1$#', $basePath, $m)) {
    $basePath = $m[1];
}

$root = ($basePath === '') ? '/' : ($basePath.'/');

$manifest = [
    'id' => $root,
    'name' => 'FullVendor',
    'short_name' => 'FullVendor',
    'description' => 'FullVendor company panel',
    'start_url' => $root.'company/',
    'scope' => $root,
    'display' => 'standalone',
    'background_color' => '#ffffff',
    'theme_color' => '#e60000',
    'orientation' => 'any',
    'lang' => 'en',
    'dir' => 'ltr',
    'prefer_related_applications' => false,
    'categories' => ['business', 'productivity'],
    'icons' => [
        [
            'src' => $root.'companyasset/images/pwa/icon-192.png',
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'any',
        ],
        [
            'src' => $root.'companyasset/images/pwa/icon-512.png',
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'any',
        ],
        [
            'src' => $root.'companyasset/images/pwa/icon-maskable-192.png',
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'maskable',
        ],
        [
            'src' => $root.'companyasset/images/pwa/icon-maskable-512.png',
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'maskable',
        ],
    ],
];

echo json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

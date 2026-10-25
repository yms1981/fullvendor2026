<?php

use Illuminate\Http\Request;

$fvUri = $_SERVER['REQUEST_URI'] ?? '/';
$fvPath = parse_url($fvUri, PHP_URL_PATH);
$fvQuery = parse_url($fvUri, PHP_URL_QUERY);
if (is_string($fvPath) && preg_match('#^/mvc/api(?P<rest>/.*)?$#', $fvPath, $fvMatch)) {
    $fvPath = '/api'.($fvMatch['rest'] ?? '');
    $_SERVER['REQUEST_URI'] = ($fvQuery !== null && $fvQuery !== '') ? $fvPath.'?'.$fvQuery : $fvPath;
}
$fvScript = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
if (
    preg_match('#/(?:mvc/)?api(?:-front)?(?:/index)?\.php$#', $fvScript)
    || (is_string($fvPath) && str_starts_with($fvPath, '/api/'))
) {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['PHP_SELF'] = '/index.php';
}
if (empty($_SERVER['HTTP_AUTHORIZATION']) && function_exists('apache_request_headers')) {
    $fvHeaders = apache_request_headers();
    if (is_array($fvHeaders)) {
        foreach ($fvHeaders as $fvName => $fvValue) {
            if (strcasecmp((string) $fvName, 'Authorization') === 0 && is_string($fvValue) && $fvValue !== '') {
                $_SERVER['HTTP_AUTHORIZATION'] = $fvValue;
                break;
            }
        }
    }
}
unset($fvUri, $fvPath, $fvQuery, $fvMatch, $fvScript, $fvHeaders, $fvName, $fvValue);

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());

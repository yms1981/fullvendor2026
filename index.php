<?php

/**
 * Front controller público de http://localhost/mvc/
 * CodeIgniter vive en ./mvc; esta entrada evita mostrar esa carpeta en la URL.
 */
$appDir = __DIR__ . DIRECTORY_SEPARATOR . 'mvc';
chdir($appDir);

$script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
$base = rtrim(dirname($script), '/');
if ($base === '/' || $base === '.' || $base === '') {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
} else {
    $_SERVER['SCRIPT_NAME'] = $base . '/index.php';
}
$_SERVER['SCRIPT_FILENAME'] = __FILE__;

require $appDir . DIRECTORY_SEPARATOR . 'index.php';

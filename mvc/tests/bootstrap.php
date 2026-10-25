<?php
/**
 * Bootstrap para pruebas PHPUnit en el MVC (CodeIgniter).
 * Se ejecuta desde el directorio mvc/ (raíz del proyecto MVC).
 */
$mvcRoot = dirname(__DIR__);

// Definir constantes mínimas para entornos que carguen CodeIgniter
if (!defined('FCPATH')) {
    define('FCPATH', $mvcRoot . DIRECTORY_SEPARATOR);
}
if (!defined('BASEPATH')) {
    $systemPath = $mvcRoot . DIRECTORY_SEPARATOR . 'system';
    define('BASEPATH', is_dir($systemPath) ? $systemPath . DIRECTORY_SEPARATOR : $mvcRoot . DIRECTORY_SEPARATOR);
}
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', getenv('ENVIRONMENT') ?: 'testing');
}

// Autoload de Composer si existe
$autoload = $mvcRoot . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if (file_exists($autoload)) {
    require $autoload;
}

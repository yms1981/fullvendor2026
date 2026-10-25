<?php
/**
 * Script para verificar si la tabla catalog_cart existe en la BD que usa el MVC.
 * Ejecutar desde la raíz del proyecto MVC: php verify_catalog_cart.php
 * O desde el navegador: http://tu-dominio/mvc/verify_catalog_cart.php (solo en desarrollo)
 * To access the JSON data just write: json
 */
$base = dirname(__FILE__);
if (!file_exists($base . '/application/config/database.php')) {
    die(json_encode(['ok' => false, 'error' => 'No se encontró config de BD. Ejecutar desde la carpeta mvc.']));
}

define('BASEPATH', $base . '/system/');
define('APPPATH', $base . '/application/');
define('ENVIRONMENT', getenv('CI_ENV') ?: 'development');

$_SERVER['CI_ENV'] = ENVIRONMENT;
if (getenv('MYSQL_DATABASE')) {
    $_ENV['MYSQL_DATABASE'] = getenv('MYSQL_DATABASE');
}

require $base . '/application/config/database.php';
$cfg = $db['default'];
$host = $cfg['hostname'] ?: 'localhost';
$user = $cfg['username'] ?: '';
$pass = $cfg['password'] ?: '';
$name = $cfg['database'] ?: '';

if (empty($name)) {
    die(json_encode([
        'ok' => false,
        'error' => 'Base de datos no configurada (MYSQL_DATABASE o database vacío).',
        'hint' => 'Revisa .env o variables de entorno.'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$conn = @new mysqli($host, $user, $pass, $name);
if ($conn->connect_error) {
    die(json_encode([
        'ok' => false,
        'error' => 'Conexión fallida: ' . $conn->connect_error,
        'database' => $name
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$conn->set_charset('utf8');

$table = 'catalog_cart';
$result = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($table) . "'");
$exists = $result && $result->num_rows > 0;

$out = [
    'ok' => true,
    'database' => $name,
    'table' => $table,
    'exists' => $exists,
];

if ($exists) {
    $cols = $conn->query("SHOW COLUMNS FROM `" . $conn->real_escape_string($table) . "`");
    $columns = [];
    $columnNames = [];
    if ($cols) {
        while ($row = $cols->fetch_assoc()) {
            $columns[] = $row['Field'] . ' (' . $row['Type'] . ')';
            $columnNames[] = $row['Field'];
        }
    }
    $out['columns'] = $columns;
    $out['has_token_kor'] = in_array('token_kor', $columnNames, true);
    $countResult = $conn->query("SELECT COUNT(*) AS n FROM `" . $conn->real_escape_string($table) . "`");
    $out['row_count'] = $countResult ? (int) $countResult->fetch_assoc()['n'] : null;
} else {
    $out['message'] = 'La tabla no existe. Créala con el SQL que está en docs o en el mensaje anterior.';
}

$conn->close();

header('Content-Type: application/json; charset=utf-8');
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

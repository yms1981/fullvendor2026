<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Helper para cargar variables de entorno desde archivo .env
 */
if (!function_exists('load_env_file')) {
    /**
     * Carga variables de entorno desde un archivo .env
     * 
     * @param string $envPath Ruta al archivo .env
     * @return void
     */
    function load_env_file($envPath = null) {
        if ($envPath === null) {
            // Intentar múltiples ubicaciones posibles
            $possiblePaths = [
                __DIR__ . '/../../.env',  // Raíz del proyecto MVC (desde helpers/)
                defined('FCPATH') ? FCPATH . '.env' : null,  // Si FCPATH está definido
                defined('APPPATH') ? APPPATH . '../.env' : null,  // Relativo a application
            ];
            
            $envPath = null;
            foreach ($possiblePaths as $path) {
                if ($path !== null && file_exists($path)) {
                    $envPath = $path;
                    break;
                }
            }
            
            if ($envPath === null) {
                return; // No se encontró ningún archivo .env
            }
        }
        
        if (!file_exists($envPath)) {
            return;
        }
        
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Ignorar comentarios
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Separar clave y valor
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remover comillas si existen
                $value = trim($value, '"\'');
                
                // Establecer en $_ENV y $_SERVER si no existe
                if (!isset($_ENV[$key])) {
                    $_ENV[$key] = $value;
                }
                if (!isset($_SERVER[$key])) {
                    $_SERVER[$key] = $value;
                }
                // También establecer con putenv para compatibilidad con getenv()
                if (getenv($key) === false) {
                    putenv("$key=$value");
                }
            }
        }
    }
}


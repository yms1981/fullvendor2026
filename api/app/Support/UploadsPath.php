<?php

namespace App\Support;

/**
 * Rutas bajo el volumen compartido MVC uploads (images_local / mvc_uploads).
 *
 * Estructura:
 *   uploads/products/{company_id}/
 *   uploads/categories/{company_id}/
 *   uploads/documents/{company_id}/.mpdf-tmp/
 *   uploads/documents/{company_id}/orders/
 *
 * Por defecto la raíz se **detecta sola** a partir de la ubicación de `api/`
 * (hermano `mvc/uploads`). No hace falta IMAGES_LOCAL_ROOT en .env salvo override.
 */
class UploadsPath
{
    private static ?string $resolvedRoot = null;

    /**
     * Raíz de uploads para lectura/escritura.
     * Override opcional: IMAGES_LOCAL_ROOT o MVC_UPLOADS_PATH (solo si el path existe
     * y parece un árbol de uploads, o IMAGES_LOCAL_ROOT_FORCE=true).
     */
    public static function root(): string
    {
        if (self::$resolvedRoot !== null) {
            return self::$resolvedRoot;
        }

        $force = filter_var(env('IMAGES_LOCAL_ROOT_FORCE', false), FILTER_VALIDATE_BOOLEAN);
        foreach ([env('IMAGES_LOCAL_ROOT'), env('MVC_UPLOADS_PATH')] as $envPath) {
            if (! is_string($envPath) || trim($envPath) === '') {
                continue;
            }
            $envPath = self::normalize($envPath);
            if ($force && $envPath !== '') {
                self::ensureDirectory($envPath);
                self::$resolvedRoot = $envPath;

                return self::$resolvedRoot;
            }
            if (self::looksLikeUploadsRoot($envPath)) {
                self::$resolvedRoot = $envPath;

                return self::$resolvedRoot;
            }
        }

        self::$resolvedRoot = self::detectRoot();

        return self::$resolvedRoot;
    }

    /**
     * Detecta automáticamente la carpeta uploads del MVC CodeIgniter.
     * Layout típico: {repo}/api + {repo}/mvc/uploads
     */
    public static function detectRoot(): string
    {
        $scored = [];
        foreach (self::candidateRoots() as $candidate) {
            $path = self::normalize((string) $candidate);
            if ($path === '' || ! is_dir($path)) {
                continue;
            }
            $scored[] = ['path' => $path, 'score' => self::scoreUploadsRoot($path)];
        }

        usort($scored, static fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        if ($scored !== [] && $scored[0]['score'] > 0) {
            return $scored[0]['path'];
        }

        // Crear el path canónico junto a api/: ../mvc/uploads
        $canonical = self::normalize(dirname(base_path()).DIRECTORY_SEPARATOR.'mvc'.DIRECTORY_SEPARATOR.'uploads');
        self::ensureDirectory($canonical);
        self::ensureDirectory($canonical.DIRECTORY_SEPARATOR.'products');

        return $canonical;
    }

    /**
     * Valor para config/filesystems.php (sin cachear estático de request).
     */
    public static function diskRoot(): string
    {
        return self::root();
    }

    /**
     * @return list<string>
     */
    public static function candidateRoots(): array
    {
        $api = base_path(); // .../mvc/api o .../api
        $repo = dirname($api); // .../mvc (monorepo) o padre del api

        $list = [
            // Docker compose habitual
            '/var/www/mvc_uploads',
            // XAMPP / monorepo anidado: .../mvc/api → .../mvc/mvc/uploads (preferido)
            $repo.DIRECTORY_SEPARATOR.'mvc'.DIRECTORY_SEPARATOR.'uploads',
            $api.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'mvc'.DIRECTORY_SEPARATOR.'uploads',
            // Deploy Linux típico: /home/.../public_html/api + public_html/mvc/uploads
            $repo.DIRECTORY_SEPARATOR.'mvc'.DIRECTORY_SEPARATOR.'uploads',
            // Algunos deploys montan uploads al lado del api
            $repo.DIRECTORY_SEPARATOR.'uploads',
            $api.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'uploads',
        ];

        // Subir hasta 4 niveles buscando mvc/uploads con products/
        $cursor = $repo;
        for ($i = 0; $i < 4; $i++) {
            $list[] = $cursor.DIRECTORY_SEPARATOR.'mvc'.DIRECTORY_SEPARATOR.'uploads';
            $list[] = $cursor.DIRECTORY_SEPARATOR.'uploads';
            $parent = dirname($cursor);
            if ($parent === $cursor) {
                break;
            }
            $cursor = $parent;
        }

        $normalized = [];
        foreach ($list as $path) {
            $path = self::normalize($path);
            if ($path !== '') {
                $normalized[$path] = $path;
            }
        }

        return array_values($normalized);
    }

    public static function looksLikeUploadsRoot(string $path): bool
    {
        $path = self::normalize($path);
        if ($path === '' || ! is_dir($path)) {
            return false;
        }

        return self::scoreUploadsRoot($path) >= 10;
    }

    private static function scoreUploadsRoot(string $path): int
    {
        $score = 0;
        if (! is_dir($path)) {
            return 0;
        }
        $score += 1;

        // Sin escritura el worker falla y puede colgar PHP-FPM reintentando.
        if (! is_writable($path)) {
            return 0;
        }
        $score += 20;

        $productsDir = $path.DIRECTORY_SEPARATOR.'products';
        if (is_dir($productsDir)) {
            $score += 30;
            if (is_writable($productsDir)) {
                $score += 20;
            }
            // Preferir el árbol que ya tiene fotos (no un uploads vacío recién creado).
            $sample = @scandir($productsDir);
            if (is_array($sample) && count($sample) > 2) {
                $score += 40;
            }
        }

        foreach (['categories', 'category', 'logos', 'users', 'documents'] as $sub) {
            if (is_dir($path.DIRECTORY_SEPARATOR.$sub)) {
                $score += 8;
            }
        }

        // Preferir .../mvc/uploads o .../mvc/mvc/uploads
        if (preg_match('#[\\\\/]mvc[\\\\/](?:mvc[\\\\/])?uploads$#', $path) === 1) {
            $score += 10;
        }
        // Layout anidado XAMPP (.../mvc/mvc/uploads) suele ser el real del CodeIgniter
        if (preg_match('#[\\\\/]mvc[\\\\/]mvc[\\\\/]uploads$#', $path) === 1) {
            $score += 15;
        }
        if ($path === '/var/www/mvc_uploads') {
            $score += 8;
        }

        return $score;
    }

    private static function normalize(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }
        $real = realpath($path);
        if ($real !== false) {
            return str_replace('\\', '/', $real);
        }

        return rtrim(str_replace('\\', '/', $path), '/');
    }

    /**
     * documents/{companyId} (o documents/_system si companyId <= 0).
     */
    public static function companyDocumentsDir(int $companyId): string
    {
        $segment = $companyId > 0 ? (string) $companyId : '_system';

        return self::ensureDirectory(self::root().'/documents/'.$segment);
    }

    /**
     * Directorio temporal escribible para mPDF (por compañía).
     */
    public static function mpdfTempDir(int $companyId = 0): string
    {
        return self::ensureDirectory(self::companyDocumentsDir($companyId).'/.mpdf-tmp');
    }

    /**
     * Carpeta de PDF de órdenes: documents/{companyId}/orders
     */
    public static function companyOrdersPdfDir(int $companyId): string
    {
        return self::ensureDirectory(self::companyDocumentsDir($companyId).'/orders');
    }

    /**
     * Ruta absoluta para guardar un PDF de orden.
     */
    public static function companyOrderPdfFile(int $companyId, string $fileName): string
    {
        $safe = preg_replace('/[^A-Za-z0-9._-]+/', '-', $fileName) ?: 'order.pdf';
        if (! str_ends_with(strtolower($safe), '.pdf')) {
            $safe .= '.pdf';
        }

        return self::companyOrdersPdfDir($companyId).'/'.$safe;
    }

    public static function ensureDirectory(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        if (! is_dir($path)) {
            @mkdir($path, 0775, true);
        }

        // Evitar listado público si el virtual host sirve /uploads (Apache).
        $htaccess = rtrim($path, '/').'/.htaccess';
        if (str_contains($path, '/documents') && ! is_file($htaccess)) {
            @file_put_contents($htaccess, "Require all denied\nDeny from all\n");
        }

        return rtrim($path, '/');
    }
}

<?php

namespace App\Support;

/**
 * Rutas bajo el volumen compartido MVC uploads (images_local / mvc_uploads).
 *
 * Estructura:
 *   uploads/documents/{company_id}/.mpdf-tmp/   → temporales mPDF
 *   uploads/documents/{company_id}/orders/      → PDF de órdenes generados
 */
class UploadsPath
{
    public static function root(): string
    {
        $candidates = [
            config('filesystems.disks.images_local.root'),
            config('filesystems.disks.legacy.root'),
            env('IMAGES_LOCAL_ROOT'),
            env('MVC_UPLOADS_PATH'),
            is_dir('/var/www/mvc_uploads') ? '/var/www/mvc_uploads' : null,
            dirname(base_path()).'/mvc/uploads',
        ];

        foreach ($candidates as $candidate) {
            $root = is_string($candidate) ? rtrim($candidate, '/') : '';
            if ($root !== '' && is_dir($root) && is_writable($root)) {
                return $root;
            }
        }

        foreach ($candidates as $candidate) {
            $root = is_string($candidate) ? rtrim($candidate, '/') : '';
            if ($root !== '' && is_dir($root)) {
                return $root;
            }
        }

        $fallback = storage_path('app/uploads');
        self::ensureDirectory($fallback);

        return $fallback;
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

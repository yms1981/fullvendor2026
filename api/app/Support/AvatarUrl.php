<?php

namespace App\Support;

/**
 * Resuelve URL pública de avatar de usuario o logo de compañía
 * a partir del valor guardado (UUID, UUID.ext, path legacy o URL absoluta).
 */
final class AvatarUrl
{
    public static function forUser(?string $image): ?string
    {
        return self::resolve($image, 'users');
    }

    public static function forCompany(?string $image): ?string
    {
        return self::resolve($image, 'company');
    }

    private static function resolve(?string $image, string $legacyFolder): ?string
    {
        $image = trim((string) $image);
        if ($image === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $image)) {
            return $image;
        }

        $base = rtrim((string) config('fullvendor.web_url', config('app.url', 'http://localhost')), '/');

        // UUID o UUID.ext → endpoint público de imágenes
        if (preg_match('/^([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})(?:\.([a-z0-9]+))?$/i', $image, $m)) {
            $path = isset($m[2]) && $m[2] !== ''
                ? '/api/v2/images/' . $m[1] . '.' . strtolower($m[2])
                : '/api/v2/images/' . $m[1];

            return $base . $path;
        }

        $file = ltrim($image, '/');
        if (str_starts_with($file, 'uploads/')) {
            return $base . '/' . $file;
        }
        if (str_starts_with($file, 'users/') || str_starts_with($file, 'user/')
            || str_starts_with($file, 'company/') || str_starts_with($file, 'companies/')) {
            return $base . '/uploads/' . $file;
        }

        return $base . '/uploads/' . $legacyFolder . '/' . $file;
    }
}

<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

/**
 * Columna de nombre en `categories`: canónica `category_name` (FullVendor).
 * Algunas BDs legacy solo tienen `name`.
 */
final class CategoryNameColumn
{
    private static ?string $resolved = null;

    public static function name(): string
    {
        if (self::$resolved !== null) {
            return self::$resolved;
        }

        if (Schema::hasColumn('categories', 'category_name')) {
            return self::$resolved = 'category_name';
        }

        if (Schema::hasColumn('categories', 'name')) {
            return self::$resolved = 'name';
        }

        return self::$resolved = 'category_name';
    }

    public static function clearCache(): void
    {
        self::$resolved = null;
    }
}

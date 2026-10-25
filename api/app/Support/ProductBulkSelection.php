<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Resuelve el listado de IDs (`pro_id`) para acciones masivas sobre productos.
 *
 * Soporta tres formas de entrada en `products`:
 *  - Array de IDs (envío directo desde API o JSON ya decodificado).
 *  - JSON-string con un array de IDs (envío clásico desde el MVC).
 *  - String literal "todos" (modo "Use all records") con filtros + `excluded_ids` opcional.
 *
 * En modo "todos" aplica los filtros (mismo modelo que el listado) y excluye los IDs
 * que el usuario haya desmarcado manualmente (persistidos por el frontend).
 */
class ProductBulkSelection
{
    /**
     * @return Collection<int,int>
     */
    public static function resolveIds(?Request $request = null): Collection
    {
        $request = $request ?? request();
        $raw = $request->input('products');

        if (is_array($raw)) {
            return collect(self::normalizeIdList($raw));
        }

        if (is_string($raw)) {
            $trimmed = trim($raw);
            if ($trimmed === 'todos' || $trimmed === '"todos"') {
                return self::filteredIds($request);
            }
            $decoded = json_decode($trimmed, true);
            if (is_array($decoded)) {
                return collect(self::normalizeIdList($decoded));
            }
            if ($decoded === 'todos') {
                return self::filteredIds($request);
            }
        }

        return collect([]);
    }

    /**
     * @return Collection<int,int>
     */
    private static function filteredIds(Request $request): Collection
    {
        $excluded = self::excludedIds($request);
        $query = Product::filter($request->all())->select('pro_id');
        if (! empty($excluded)) {
            $query->whereNotIn('pro_id', $excluded);
        }
        return $query->pluck('pro_id');
    }

    /**
     * @return array<int,int>
     */
    public static function excludedIds(Request $request): array
    {
        $raw = $request->input('excluded_ids');
        if (is_string($raw)) {
            $trimmed = trim($raw);
            if ($trimmed === '') {
                return [];
            }
            $decoded = json_decode($trimmed, true);
            $raw = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($raw)) {
            return [];
        }
        return self::normalizeIdList($raw);
    }

    /**
     * @param  array<int,mixed>  $ids
     * @return array<int,int>
     */
    private static function normalizeIdList(array $ids): array
    {
        $out = [];
        foreach ($ids as $v) {
            if (is_numeric($v)) {
                $iv = (int) $v;
                if ($iv > 0) {
                    $out[] = $iv;
                }
            }
        }
        return $out;
    }
}

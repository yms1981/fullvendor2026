<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class CustomerCatalogCartService
{
    public function hasPendingCarts(int $companyId, int $customerId): bool
    {
        if (! Schema::hasTable('catalog_cart')) {
            return false;
        }

        return DB::table('catalog_cart')
            ->where('company_id', $companyId)
            ->where('customer_id', $customerId)
            ->whereIn('order_sent', [1, 2])
            ->where('expired_date', '>', now())
            ->exists();
    }

    public function expiredCartsCount(int $companyId, int $customerId): int
    {
        if (! Schema::hasTable('catalog_cart')) {
            return 0;
        }

        return (int) DB::table('catalog_cart')
            ->where('company_id', $companyId)
            ->where('customer_id', $customerId)
            ->whereIn('order_sent', [1, 2])
            ->where('expired_date', '<', now())
            ->count();
    }

    /**
     * Últimos envíos de catálogo del cliente (tabla catalog_cart).
     *
     * @return list<object{
     *     cart_id:int,
     *     catalog_id:int|null,
     *     catalog_name:string,
     *     default_catalog:int,
     *     created:string|null,
     *     expired_date:string|null,
     *     token_kor:string,
     *     view:int
     * }>
     */
    public function recentCatalogs(int $companyId, int $customerId, int $limit = 5): array
    {
        if ($companyId < 1 || $customerId < 1 || ! Schema::hasTable('catalog_cart')) {
            return [];
        }

        $limit = max(1, min(20, $limit));

        $rows = DB::table('catalog_cart as c')
            ->leftJoin('catalog as cg', 'cg.catalog_id', '=', 'c.catalog_id')
            ->where('c.company_id', $companyId)
            ->where('c.customer_id', $customerId)
            ->whereNull('c.deleted_at')
            ->whereNotNull('c.token_kor')
            ->where('c.token_kor', '<>', '')
            ->orderByDesc('c.created')
            ->orderByDesc('c.cart_id')
            ->limit($limit)
            ->get([
                'c.cart_id',
                'c.catalog_id',
                'c.created',
                'c.expired_date',
                'c.token_kor',
                'c.view',
                DB::raw('COALESCE(cg.catalog_name, \'\') as catalog_name'),
                DB::raw('COALESCE(cg.default_catalog, 0) as default_catalog'),
            ]);

        return $rows->map(static function ($row) {
            return (object) [
                'cart_id' => (int) ($row->cart_id ?? 0),
                'catalog_id' => isset($row->catalog_id) ? (int) $row->catalog_id : null,
                'catalog_name' => (string) ($row->catalog_name ?? ''),
                'default_catalog' => (int) ($row->default_catalog ?? 0),
                'created' => $row->created ?? null,
                'expired_date' => $row->expired_date ?? null,
                'token_kor' => (string) ($row->token_kor ?? ''),
                'view' => (int) ($row->view ?? 0),
            ];
        })->all();
    }

    /**
     * @return array{success: bool}
     */
    public function expireCart(int $companyId, int $cartId): array
    {
        if ($companyId < 1 || $cartId < 1 || ! Schema::hasTable('catalog_cart')) {
            return ['success' => false];
        }

        $updated = DB::table('catalog_cart')
            ->where('company_id', $companyId)
            ->where('cart_id', $cartId)
            ->whereNull('deleted_at')
            ->update([
                'expired_date' => Carbon::now()->subDay()->format('Y-m-d H:i:s'),
            ]);

        return ['success' => $updated > 0];
    }
}

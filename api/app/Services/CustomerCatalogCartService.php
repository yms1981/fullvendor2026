<?php

namespace App\Services;

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
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class ProductOpenCartLinesService
{
    /**
     * Líneas en carritos abiertos que contienen el producto (equivalente a ProductModel::haver_carts).
     *
     * @return array<int, object>
     */
    public function lines(int $companyId, int $productId): array
    {
        if (! Schema::hasTable('detail_car') || ! Schema::hasTable('catalog_cart')) {
            return [];
        }

        return DB::table('detail_car as det')
            ->join('catalog_cart as c', 'c.cart_id', '=', 'det.cart_id')
            ->where('c.company_id', $companyId)
            ->where('det.product_id', $productId)
            ->select([
                'c.company_id',
                'c.cart_id',
                'det.product_id',
                'det.qty',
            ])
            ->orderBy('det.product_id')
            ->get()
            ->all();
    }
}

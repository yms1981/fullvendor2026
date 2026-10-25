<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class GetImagesProductsQuery
{
    public static function getQuery(): \Illuminate\Database\Query\Builder
    {
        return DB::table('product_images as p')
            ->select([
                'p.company_id',
                'p.product_id',
                DB::raw('COUNT(p.product_id) AS qty_images'),
                DB::raw("GROUP_CONCAT(CONCAT(p.images) ORDER BY p.img_order ASC SEPARATOR ',') AS group_images")
            ])
            ->whereNotNull('p.company_id')
            ->whereNotNull('p.product_id')
            ->groupBy('p.company_id', 'p.product_id')
            ->orderBy('p.img_id', 'DESC');
    }
}

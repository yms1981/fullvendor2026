<?php
namespace App\SortOptions;

class ProductSortOptions
{
    public static function all(): array
    {
        return [
            'name_asc' => ['column' => 'name', 'direction' => 'asc'],
            'name_desc' => ['column' => 'name', 'direction' => 'desc'],
            'sku_asc' => ['column' => 'sku', 'direction' => 'asc'],
            'sku_desc' => ['column' => 'sku', 'direction' => 'desc'],
            'price_asc' => ['column' => 'sale_price', 'direction' => 'asc'],
            'price_desc' => ['column' => 'sale_price', 'direction' => 'desc'],
        ];
    }
}

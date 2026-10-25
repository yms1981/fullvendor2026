<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class MontoCartQuery
{
    /**
     * Obtiene la query usando la vista monto directamente
     * La vista monto está basada en la tabla detail y se relaciona con customers y catalog
     */
    public static function getQuery(): \Illuminate\Database\Query\Builder
    {
        return DB::table('monto');
    }

    /**
     * Obtiene la query construida manualmente (alternativa si la vista no existe)
     * Usa la tabla detail en lugar de detail_car y se relaciona directamente con catalog
     */
    public static function getQueryManual(): \Illuminate\Database\Query\Builder
    {
        return DB::table('detail as d')
            ->join('customers as c', 'c.customer_id', '=', 'd.customer_id')
            ->leftJoin('catalog as cat', 'cat.catalog_id', '=', 'd.catalog_id')
            ->selectRaw('
                d.detail_id AS detail_id,
                d.created AS created,
                CONCAT(c.name, " ", c.business_name) AS customer,
                CONCAT(c.discount, "%") AS discount,
                SUM(d.sale_price * d.qty) AS sub_total,
                SUM(d.sale_price * d.qty) * c.discount / 100 AS discount_total,
                SUM(d.sale_price * d.qty) - (SUM(d.sale_price * d.qty) * c.discount / 100) AS total,
                d.catalog_id AS catalog_id,
                d.company_id AS company_id,
                cat.catalog_name AS catalog_name,
                d.product_id AS product_id,
                d.customer_id AS customer_id
            ')
            ->groupBy(
                'd.token_car',
                'd.detail_id',
                'd.created',
                'c.name',
                'c.business_name',
                'c.discount',
                'd.catalog_id',
                'd.company_id',
                'cat.catalog_name',
                'd.product_id',
                'd.customer_id'
            );
    }
}

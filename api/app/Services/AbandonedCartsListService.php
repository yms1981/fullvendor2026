<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class AbandonedCartsListService
{
    /**
     * Lista carritos abandonados (equivalente a company/Cart::abandoned en MVC).
     *
     * @return array{
     *     data: array<int, object>,
     *     current_page: int,
     *     last_page: int,
     *     per_page: int,
     *     total: int,
     *     from: int|null,
     *     to: int|null,
     *     count_total: int,
     *     count_filtered: int,
     *     purged_empty: int
     * }
     */
    public function list(int $companyId, Request $request): array
    {
        $purgedEmpty = $this->purgeEmptyCarts($companyId);

        $desde = (string) ($request->input('desde_fecha') ?: date('Y-m-01'));
        $hasta = (string) ($request->input('hasta_fecha') ?: date('Y-m-d'));
        $estado = (string) ($request->input('estado', 'all'));
        $search = trim((string) ($request->input('search', '')));
        $orderSort = (string) ($request->input('sort', ''));
        $orderType = strtoupper((string) ($request->input('order_type', 'DESC')));
        $orderType = $orderType === 'ASC' ? 'ASC' : 'DESC';
        $page = max(1, (int) $request->input('page', 1));
        $perPage = max(1, min(100, (int) $request->input('per_page', 25)));
        $offset = ($page - 1) * $perPage;

        $hasOrdersCartId = Schema::hasTable('orders') && Schema::hasColumn('orders', 'cart_id');
        $hasOrderSource = $hasOrdersCartId && Schema::hasColumn('orders', 'source');
        $totalExpr = $this->detailTotalSqlExpression();
        $hasLinesExpr = $this->hasDetailLinesSqlExpression();
        $sourceExpr = $this->sourceSqlExpression($hasOrderSource, false);
        $sourceExprAgg = $this->sourceSqlExpression($hasOrderSource, true);
        $typeCartAgg = 'MAX(c.type_cart)';
        $catalogIdAgg = 'MAX(c.catalog_id)';

        $applyFilters = function ($qb) use (
            $companyId,
            $hasOrdersCartId,
            $estado,
            $search,
            $desde,
            $hasta,
            $hasLinesExpr
        ) {
            $qb->from('catalog_cart as c')
                ->leftJoin('customers as cus', 'cus.customer_id', '=', 'c.customer_id')
                ->leftJoin('status_cart as st', 'st.id_status', '=', 'c.status_cart')
                ->where('c.company_id', $companyId)
                ->whereNull('c.deleted_at')
                ->where(function ($q) {
                    $q->whereNull('c.status_cart')
                        ->orWhere('c.status_cart', '!=', 4);
                })
                ->whereIn('c.order_sent', [1, 2])
                ->whereDate('c.created', '>=', $desde)
                ->whereDate('c.created', '<=', $hasta)
                ->whereRaw('(' . $hasLinesExpr . ')');

            if ($hasOrdersCartId) {
                $qb->leftJoin('orders as o', function ($join) {
                    $join->on('o.cart_id', '=', 'c.cart_id')
                        ->whereColumn('o.company_id', 'c.company_id');
                });
            }

            if ($estado !== '' && $estado !== 'all') {
                $qb->where('st.cod', (int) $estado);
            }

            if ($search !== '') {
                $qb->where(function ($q) use ($search, $hasOrdersCartId) {
                    $q->where('cus.business_name', 'like', '%' . $search . '%')
                        ->orWhere('cus.name', 'like', '%' . $search . '%')
                        ->orWhere('cus.email', 'like', '%' . $search . '%')
                        ->orWhere('c.cart_number', 'like', '%' . $search . '%');
                    if ($hasOrdersCartId) {
                        $q->orWhere('o.order_number', 'like', '%' . $search . '%');
                    }
                });
            }

            return $qb;
        };

        $countFilteredRow = $applyFilters(DB::query())
            ->selectRaw('COUNT(DISTINCT c.cart_id) AS qty')
            ->first();
        $countFiltered = (int) ($countFilteredRow->qty ?? 0);

        $countTotalRow = DB::table('catalog_cart as c')
            ->where('c.company_id', $companyId)
            ->whereNull('c.deleted_at')
            ->where(function ($q) {
                $q->whereNull('c.status_cart')
                    ->orWhere('c.status_cart', '!=', 4);
            })
            ->whereIn('c.order_sent', [1, 2])
            ->whereRaw('(' . $hasLinesExpr . ')')
            ->selectRaw('COUNT(DISTINCT c.cart_id) AS qty')
            ->first();
        $countTotal = (int) ($countTotalRow->qty ?? 0);

        $listQ = $applyFilters(DB::query());

        if ($hasOrdersCartId) {
            $listQ
                ->selectRaw('c.cart_id')
                ->selectRaw('MAX(c.created) AS created')
                ->selectRaw($catalogIdAgg . ' AS catalog_id')
                ->selectRaw($typeCartAgg . ' AS type_cart')
                ->selectRaw('(' . $sourceExprAgg . ') AS source')
                ->selectRaw('MAX(COALESCE(o.order_number, c.cart_number)) AS order_number')
                ->selectRaw('MAX(o.order_id) AS order_id')
                ->selectRaw('MAX(COALESCE(st.cod, c.status_cart)) AS status_cart')
                ->selectRaw(
                    "MAX(CONCAT(COALESCE(cus.business_name, ''), IF(cus.name IS NULL OR cus.name = '', '', CONCAT(' (', cus.name, ')'))) ) AS customer"
                )
                ->selectRaw('MAX((' . $totalExpr . ')) AS total')
                ->groupBy('c.cart_id');
        } else {
            $listQ
                ->select('c.cart_id', 'c.created', 'c.catalog_id', 'c.type_cart')
                ->selectRaw('(' . $sourceExpr . ') AS source')
                ->selectRaw('c.cart_number AS order_number')
                ->selectRaw('NULL AS order_id')
                ->selectRaw('COALESCE(st.cod, c.status_cart) AS status_cart')
                ->selectRaw(
                    "CONCAT(COALESCE(cus.business_name, ''), IF(cus.name IS NULL OR cus.name = '', '', CONCAT(' (', cus.name, ')'))) AS customer"
                )
                ->selectRaw('(' . $totalExpr . ') AS total');
        }

        switch ($orderSort) {
            case 'id':
                $listQ->orderBy('order_number', $orderType);
                break;
            case 'customer':
                $listQ->orderBy($hasOrdersCartId ? 'customer' : 'cus.business_name', $orderType);
                break;
            case 'monto':
                $listQ->orderBy('total', $orderType);
                break;
            case 'created':
                $listQ->orderBy($hasOrdersCartId ? 'created' : 'c.created', $orderType);
                break;
            default:
                $listQ->orderBy('c.cart_id', 'DESC');
        }

        $rows = $listQ
            ->offset($offset)
            ->limit($perPage)
            ->get();

        $lastPage = $countFiltered > 0 ? (int) ceil($countFiltered / $perPage) : 1;
        $from = $countFiltered > 0 ? $offset + 1 : null;
        $to = $countFiltered > 0 ? min($offset + $perPage, $countFiltered) : null;

        return [
            'data' => $rows->all(),
            'current_page' => $page,
            'last_page' => $lastPage,
            'per_page' => $perPage,
            'total' => $countFiltered,
            'from' => $from,
            'to' => $to,
            'count_total' => $countTotal,
            'count_filtered' => $countFiltered,
            'purged_empty' => $purgedEmpty,
        ];
    }

    /**
     * Soft-delete automático de carritos sin ítems / sin importe.
     */
    public function purgeEmptyCarts(int $companyId): int
    {
        if ($companyId < 1 || ! Schema::hasTable('catalog_cart')) {
            return 0;
        }

        $now = Carbon::now()->format('Y-m-d H:i:s');
        $hasDetail = Schema::hasTable('detail_car');
        $hasLinesExpr = $this->hasDetailLinesSqlExpression();

        $query = DB::table('catalog_cart as c')
            ->where('c.company_id', $companyId)
            ->whereNull('c.deleted_at')
            ->where(function ($q) {
                $q->whereNull('c.status_cart')
                    ->orWhere('c.status_cart', '!=', 4);
            })
            ->whereIn('c.order_sent', [1, 2]);

        if ($hasDetail) {
            // Sin líneas con cantidad/importe > 0
            $query->whereRaw('NOT (' . $hasLinesExpr . ')');
        }

        $ids = $query->pluck('c.cart_id')
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn ($id) => $id > 0)
            ->values()
            ->all();

        if ($ids === []) {
            return 0;
        }

        return (int) DB::table('catalog_cart')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->whereIn('cart_id', $ids)
            ->update(['deleted_at' => $now]);
    }

    private function detailTotalSqlExpression(): string
    {
        if (! Schema::hasTable('detail_car')) {
            return '0';
        }

        return 'SELECT COALESCE(SUM(dc.qty * dc.sale_price), 0)
            FROM detail_car dc
            WHERE dc.cart_id = c.cart_id
               OR (
                    c.token_kor IS NOT NULL
                    AND c.token_kor <> \'\'
                    AND dc.token_car = c.token_kor
               )';
    }

    private function hasDetailLinesSqlExpression(): string
    {
        if (! Schema::hasTable('detail_car')) {
            return '0';
        }

        return 'EXISTS (
            SELECT 1
            FROM detail_car dc
            WHERE (
                dc.cart_id = c.cart_id
                OR (
                    c.token_kor IS NOT NULL
                    AND c.token_kor <> \'\'
                    AND dc.token_car = c.token_kor
                )
            )
            AND (dc.qty > 0 OR (dc.qty * dc.sale_price) > 0)
        )';
    }

    /**
     * Origen: app / catalog / manual / marketplace (alineado a pedidos).
     */
    private function sourceSqlExpression(bool $hasOrderSource, bool $aggregate = false): string
    {
        if ($hasOrderSource) {
            $orderSource = $aggregate
                ? 'LOWER(TRIM(COALESCE(MAX(o.source), \'\')))'
                : 'LOWER(TRIM(COALESCE(o.source, \'\')))';
        } else {
            $orderSource = '\'\'';
        }

        $typeCart = $aggregate ? 'MAX(c.type_cart)' : 'c.type_cart';
        $catalogId = $aggregate ? 'MAX(c.catalog_id)' : 'c.catalog_id';

        return "CASE
            WHEN {$orderSource} IN ('app', 'mobile', 'android', 'ios') THEN 'app'
            WHEN {$orderSource} IN ('catalog', 'web', 'catalogo') THEN 'catalog'
            WHEN {$orderSource} IN ('manual') THEN 'manual'
            WHEN {$orderSource} IN ('marketplace', 'seller_portal') THEN 'marketplace'
            WHEN {$typeCart} = 'to_company' THEN 'manual'
            WHEN {$typeCart} IN ('to_customer', 'to_portal_seller') THEN 'catalog'
            WHEN {$catalogId} IS NOT NULL AND {$catalogId} > 0 THEN 'catalog'
            ELSE 'catalog'
        END";
    }
}

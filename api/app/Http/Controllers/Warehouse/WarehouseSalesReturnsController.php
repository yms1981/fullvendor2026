<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

/**
 * Ventas y devoluciones (NC) de los últimos 6 meses para FullVendor Warehouse.
 * Agrupado por cliente + orden + producto — sync offline de notas de crédito.
 */
#[OA\Tag(
    name: 'WarehouseSalesReturns',
    description: 'Ventas y devoluciones últimos 6 meses (agrupadas) para sync warehouse CN'
)]
class WarehouseSalesReturnsController extends Controller
{
    private const DEFAULT_PER_PAGE = 500;

    private const MAX_PER_PAGE = 1000;

    /** Ventana de sync CN: últimos N meses. */
    private const MONTHS_BACK = 6;

    #[OA\Get(
        path: 'api/v3/warehouse/sales/last-year',
        summary: 'Ventas (tipo_d=D) de los últimos 6 meses agrupadas por cliente/orden/producto',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseSalesReturns']
    )]
    public function salesLastYear(Request $request): JsonResponse
    {
        return $this->paginatedMovements($request, 'D');
    }

    #[OA\Get(
        path: 'api/v3/warehouse/returns/last-year',
        summary: 'Devoluciones / NC (tipo_d=C) de los últimos 6 meses agrupadas por cliente/orden/producto',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseSalesReturns']
    )]
    public function returnsLastYear(Request $request): JsonResponse
    {
        return $this->paginatedMovements($request, 'C');
    }

    private function paginatedMovements(Request $request, string $tipoD): JsonResponse
    {
        $companyId = $this->resolveCompanyId($request);
        if ($companyId === null) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo determinar la compañía. Envíe company_id o asocie el usuario a una compañía.',
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        [$page, $perPage] = $this->pagination($request);
        set_time_limit(0);

        $since = Carbon::now()->subMonths(self::MONTHS_BACK)->startOfDay();
        $tipo = strtoupper($tipoD);

        // qty: entregado si existe; si no ABS(qty). Precio: promedio ponderado.
        $qtyExpr = 'CASE WHEN COALESCE(d.delivered_quantity, 0) > 0 '
            .'THEN d.delivered_quantity ELSE ABS(d.qty) END';

        $query = DB::table('orders as o')
            ->join('order_details as d', 'd.order_id', '=', 'o.order_id')
            ->leftJoin('products as p', 'p.pro_id', '=', 'd.product_id')
            ->where('o.company_id', $companyId)
            ->whereRaw('UPPER(COALESCE(o.tipo_d, \'D\')) = ?', [$tipo])
            ->where('o.created', '>=', $since)
            ->where('o.customer_id', '>', 0)
            ->where('d.product_id', '>', 0)
            ->whereNull('d.deleted_at')
            ->groupBy(
                'o.company_id',
                'o.customer_id',
                'o.order_id',
                'o.order_number',
                'o.created',
                'd.product_id',
                'p.sku',
                'p.name'
            )
            ->orderBy('o.customer_id')
            ->orderByDesc('o.created')
            ->orderBy('o.order_id')
            ->orderBy('p.sku')
            ->select([
                'o.company_id',
                'o.customer_id',
                'o.order_id',
                'o.order_number',
                'o.created as order_date',
                'd.product_id',
                DB::raw('COALESCE(p.sku, \'\') as sku'),
                DB::raw('COALESCE(p.name, \'\') as product_name'),
                DB::raw("SUM({$qtyExpr}) as qty"),
                DB::raw(
                    "CASE WHEN SUM({$qtyExpr}) > 0 "
                    ."THEN SUM(COALESCE(d.sale_price, 0) * ({$qtyExpr})) / SUM({$qtyExpr}) "
                    .'ELSE AVG(COALESCE(d.sale_price, 0)) END as price'
                ),
            ])
            ->havingRaw("SUM({$qtyExpr}) > 0");

        // Sin paginate()/COUNT(*) sobre el GROUP BY: en tablas grandes
        // el COUNT envuelve toda la agregación y retrasa CADA página varios segundos.
        // Paginación simple: LIMIT/OFFSET + “hay más” (last_page = page+1).
        $rows = (clone $query)->forPage($page, $perPage)->get();
        $count = $rows->count();
        $hasMore = $count >= $perPage;
        $lastPage = $hasMore ? ($page + 1) : $page;
        $from = $count > 0 ? (($page - 1) * $perPage + 1) : null;
        $to = $count > 0 ? (($page - 1) * $perPage + $count) : null;
        // Total aproximado para la barra de progreso (sin COUNT real).
        $approxTotal = $hasMore
            ? ($page * $perPage + 1)
            : (($page - 1) * $perPage + $count);

        $data = $rows->map(static function ($row) {
            return [
                'company_id' => (int) $row->company_id,
                'customer_id' => (int) $row->customer_id,
                'order_id' => (int) $row->order_id,
                'order_number' => (string) ($row->order_number ?? ''),
                'order_date' => $row->order_date
                    ? (string) $row->order_date
                    : null,
                'product_id' => (int) $row->product_id,
                'sku' => (string) ($row->sku ?? ''),
                'product_name' => (string) ($row->product_name ?? ''),
                'qty' => round((float) $row->qty, 4),
                'price' => round((float) $row->price, 4),
            ];
        })->values()->all();

        return response()->json([
            'success' => true,
            'company_id' => $companyId,
            'tipo_d' => $tipo,
            'since' => $since->toIso8601String(),
            'data' => $data,
            'current_page' => $page,
            'last_page' => $lastPage,
            'per_page' => $perPage,
            'total' => $approxTotal,
            'from' => $from,
            'to' => $to,
            'count' => $count,
            'has_more' => $hasMore,
        ]);
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function pagination(Request $request): array
    {
        $page = max(1, (int) $request->input('page', 1));
        $perPage = (int) $request->input('per_page', self::DEFAULT_PER_PAGE);
        if ($perPage < 1) {
            $perPage = self::DEFAULT_PER_PAGE;
        }
        if ($perPage > self::MAX_PER_PAGE) {
            $perPage = self::MAX_PER_PAGE;
        }

        return [$page, $perPage];
    }

    private function resolveCompanyId(Request $request): ?int
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        $companyId = $request->input('company_id') ?? $request->input('company');
        if (! $companyId) {
            $userCompany = $user->companiesForRole('manager')->first()
                ?? $user->companies()->first();
            $companyId = $userCompany?->company_id;
        }

        $companyId = (int) $companyId;

        return $companyId > 0 ? $companyId : null;
    }
}

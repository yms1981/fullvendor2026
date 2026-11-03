<?php

namespace App\Services\AppSalesman;

use Illuminate\Support\Facades\DB;

/**
 * Cantidades solicitadas (SUM qty) por compañía / cliente / producto (órdenes tipo_d = D).
 */
class AppSalesmanRequestedService
{
    /**
     * @return array{
     *   status: string,
     *   company_id?: int,
     *   requested?: list<array<string, mixed>>,
     *   current_page?: int,
     *   last_page?: int,
     *   per_page?: int,
     *   total?: int,
     *   from?: int|null,
     *   to?: int|null,
     *   error?: string
     * }
     */
    public function listForCompany(
        int $companyId,
        ?int $page = null,
        ?int $perPage = null,
    ): array {
        if ($companyId < 1) {
            return [
                'status' => '0',
                'error' => 'invalid_company_id',
            ];
        }

        $wantsPagination = $page !== null || $perPage !== null;
        $pageNum = max(1, (int) ($page ?? 1));
        $perPageNum = (int) ($perPage ?? 500);
        if ($perPageNum < 1) {
            $perPageNum = 500;
        }
        if ($perPageNum > 1000) {
            $perPageNum = 1000;
        }

        $base = DB::table('order_details as a')
            ->join('orders as b', 'b.order_id', '=', 'a.order_id')
            ->join('products as c', 'c.pro_id', '=', 'a.product_id')
            ->where('b.tipo_d', 'D')
            ->where('b.company_id', $companyId)
            ->groupBy('b.company_id', 'a.product_id', 'b.customer_id', 'c.sku')
            ->select([
                'b.company_id',
                'b.customer_id',
                'a.product_id',
                'c.sku',
                // tipo_d = D: qty negativa se cuenta en positivo
                DB::raw('SUM(ABS(a.qty)) AS qty'),
            ]);

        if (! $wantsPagination) {
            $rows = (clone $base)
                ->orderBy('b.customer_id')
                ->orderBy('c.sku')
                ->get();

            if ($rows->isEmpty()) {
                return [
                    'status' => '0',
                    'error' => 'no_requested_found',
                ];
            }

            return [
                'status' => '1',
                'company_id' => $companyId,
                'requested' => $this->mapRows($rows),
            ];
        }

        $total = (int) DB::query()
            ->fromSub(clone $base, 'requested_agg')
            ->count();

        if ($total === 0) {
            return [
                'status' => '0',
                'error' => 'no_requested_found',
                'company_id' => $companyId,
                'current_page' => $pageNum,
                'last_page' => 1,
                'per_page' => $perPageNum,
                'total' => 0,
                'from' => null,
                'to' => null,
                'requested' => [],
            ];
        }

        $lastPage = (int) max(1, (int) ceil($total / $perPageNum));
        if ($pageNum > $lastPage) {
            $pageNum = $lastPage;
        }

        $rows = (clone $base)
            ->orderBy('b.customer_id')
            ->orderBy('c.sku')
            ->forPage($pageNum, $perPageNum)
            ->get();

        $from = $total === 0 ? null : (($pageNum - 1) * $perPageNum) + 1;
        $to = $total === 0 ? null : min($pageNum * $perPageNum, $total);

        return [
            'status' => '1',
            'company_id' => $companyId,
            'requested' => $this->mapRows($rows),
            'current_page' => $pageNum,
            'last_page' => $lastPage,
            'per_page' => $perPageNum,
            'total' => $total,
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * Resumen liviano: 1 fila por customer_id + sku (SUM qty).
     * Incluye product_id representativo (MAX) para la app salesman.
     *
     * @return array{
     *   status: string,
     *   company_id?: int,
     *   requested?: list<array<string, mixed>>,
     *   current_page?: int,
     *   last_page?: int,
     *   per_page?: int,
     *   total?: int,
     *   from?: int|null,
     *   to?: int|null,
     *   error?: string
     * }
     */
    public function listSummaryForCompany(
        int $companyId,
        ?int $page = null,
        ?int $perPage = null,
        ?int $customerId = null,
    ): array {
        if ($companyId < 1) {
            return [
                'status' => '0',
                'error' => 'invalid_company_id',
            ];
        }

        $wantsPagination = $page !== null || $perPage !== null;
        $pageNum = max(1, (int) ($page ?? 1));
        $perPageNum = (int) ($perPage ?? 500);
        if ($perPageNum < 1) {
            $perPageNum = 500;
        }
        if ($perPageNum > 1000) {
            $perPageNum = 1000;
        }

        // Agrupa solo por cliente + SKU (menos filas que product_id × customer).
        $base = DB::table('order_details as a')
            ->join('orders as b', 'b.order_id', '=', 'a.order_id')
            ->join('products as c', 'c.pro_id', '=', 'a.product_id')
            ->where('b.tipo_d', 'D')
            ->where('b.company_id', $companyId)
            ->whereNotNull('c.sku')
            ->where('c.sku', '!=', '')
            ->groupBy('b.company_id', 'b.customer_id', 'c.sku')
            ->select([
                'b.company_id',
                'b.customer_id',
                'c.sku',
                DB::raw('MAX(a.product_id) AS product_id'),
                DB::raw('SUM(ABS(a.qty)) AS qty'),
            ]);

        // App salesman: pedir solo 1 cliente (payload pequeño)
        if ($customerId !== null && $customerId > 0) {
            $base->where('b.customer_id', $customerId);
        }

        if (! $wantsPagination) {
            $rows = (clone $base)
                ->orderBy('b.customer_id')
                ->orderBy('c.sku')
                ->get();

            if ($rows->isEmpty()) {
                return [
                    'status' => '0',
                    'error' => 'no_requested_found',
                ];
            }

            return [
                'status' => '1',
                'company_id' => $companyId,
                'mode' => 'summary',
                'requested' => $this->mapSummaryRows($rows),
            ];
        }

        $total = (int) DB::query()
            ->fromSub(clone $base, 'requested_summary_agg')
            ->count();

        if ($total === 0) {
            return [
                'status' => '0',
                'error' => 'no_requested_found',
                'company_id' => $companyId,
                'mode' => 'summary',
                'current_page' => $pageNum,
                'last_page' => 1,
                'per_page' => $perPageNum,
                'total' => 0,
                'from' => null,
                'to' => null,
                'requested' => [],
            ];
        }

        $lastPage = (int) max(1, (int) ceil($total / $perPageNum));
        if ($pageNum > $lastPage) {
            $pageNum = $lastPage;
        }

        $rows = (clone $base)
            ->orderBy('b.customer_id')
            ->orderBy('c.sku')
            ->forPage($pageNum, $perPageNum)
            ->get();

        $from = (($pageNum - 1) * $perPageNum) + 1;
        $to = min($pageNum * $perPageNum, $total);

        return [
            'status' => '1',
            'company_id' => $companyId,
            'mode' => 'summary',
            'requested' => $this->mapSummaryRows($rows),
            'current_page' => $pageNum,
            'last_page' => $lastPage,
            'per_page' => $perPageNum,
            'total' => $total,
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     * @return list<array<string, mixed>>
     */
    private function mapRows($rows): array
    {
        return $rows->map(static function ($row): array {
            return [
                'company_id' => (int) $row->company_id,
                'customer_id' => (int) $row->customer_id,
                'product_id' => (int) $row->product_id,
                'sku' => (string) ($row->sku ?? ''),
                'qty' => round((float) $row->qty, 4),
            ];
        })->values()->all();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, object>  $rows
     * @return list<array<string, mixed>>
     */
    private function mapSummaryRows($rows): array
    {
        return $rows->map(static function ($row): array {
            return [
                'company_id' => (int) $row->company_id,
                'customer_id' => (int) $row->customer_id,
                'sku' => (string) ($row->sku ?? ''),
                'product_id' => (int) ($row->product_id ?? 0),
                'qty' => round((float) $row->qty, 4),
            ];
        })->values()->all();
    }
}

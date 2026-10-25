<?php

namespace App\Services\AppSalesman;

use Illuminate\Support\Facades\DB;

/**
 * Cantidades solicitadas (SUM qty) por compañía / cliente / producto (órdenes tipo_d = D).
 */
class AppSalesmanRequestedService
{
    /**
     * @return array{status: string, company_id?: int, requested?: list<array<string, mixed>>, error?: string}
     */
    public function listForCompany(int $companyId): array
    {
        if ($companyId < 1) {
            return [
                'status' => '0',
                'error' => 'invalid_company_id',
            ];
        }

        $rows = DB::table('order_details as a')
            ->join('orders as b', 'b.order_id', '=', 'a.order_id')
            ->join('products as c', 'c.pro_id', '=', 'a.product_id')
            ->where('b.tipo_d', 'D')
            ->where('b.company_id', $companyId)
            ->groupBy('b.company_id', 'a.product_id', 'b.customer_id', 'c.sku')
            ->orderBy('b.customer_id')
            ->orderBy('c.sku')
            ->select([
                'b.company_id',
                'b.customer_id',
                'a.product_id',
                'c.sku',
                // tipo_d = D: qty negativa se cuenta en positivo
                DB::raw('SUM(ABS(a.qty)) AS qty'),
            ])
            ->get();

        if ($rows->isEmpty()) {
            return [
                'status' => '0',
                'error' => 'no_requested_found',
            ];
        }

        $requested = $rows->map(static function ($row): array {
            return [
                'company_id' => (int) $row->company_id,
                'customer_id' => (int) $row->customer_id,
                'product_id' => (int) $row->product_id,
                'sku' => (string) ($row->sku ?? ''),
                'qty' => round((float) $row->qty, 4),
            ];
        })->values()->all();

        return [
            'status' => '1',
            'company_id' => $companyId,
            'requested' => $requested,
        ];
    }
}

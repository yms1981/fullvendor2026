<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /**
     * Métricas globales de la plataforma para el dashboard del panel admin.
     * Sustituye las consultas SQL directas que hacía el MVC (admin/Home + admin/footer).
     */
    public function adminStats()
    {
        if (! Schema::hasTable('orders') || ! Schema::hasTable('order_details')) {
            return response()->json([
                'total_revenue' => 0,
                'total_sales_qty' => 0,
                'avg_value_per_month' => 0,
                'sales_by_month' => $this->emptyMonthlySeries(),
            ]);
        }

        $paidDetails = DB::table('order_details as od')
            ->join('orders as ord', 'ord.order_id', '=', 'od.order_id')
            ->where('ord.payment_status', '1');

        $totalRevenue = (float) (clone $paidDetails)
            ->sum(DB::raw('od.sale_price * od.qty'));
        $totalSalesQty = (int) (clone $paidDetails)->sum('od.qty');

        $perMonth = DB::table('order_details as od')
            ->join('orders as ord', 'ord.order_id', '=', 'od.order_id')
            ->where('ord.payment_status', '1')
            ->select(DB::raw('SUM(od.sale_price * od.qty) as total'))
            ->groupBy(DB::raw('YEAR(ord.created)'), DB::raw('MONTH(ord.created)'))
            ->pluck('total');

        $avgValuePerMonth = 0;
        if ($perMonth->count() > 0) {
            $acc = 0.0;
            foreach ($perMonth as $total) {
                $acc += ((float) $total) / 12;
            }
            $avgValuePerMonth = (int) round($acc);
        }

        return response()->json([
            'total_revenue' => (int) round($totalRevenue),
            'total_sales_qty' => $totalSalesQty,
            'avg_value_per_month' => $avgValuePerMonth,
            'sales_by_month' => $this->salesByMonth(),
        ]);
    }

    /**
     * Cantidad vendida por mes (últimos 12 meses del año en curso), alineado con el gráfico del footer admin.
     *
     * @return array{labels: array<int, string>, qty: array<int, int>}
     */
    private function salesByMonth(): array
    {
        $months = [];
        for ($i = 0; $i < 12; $i++) {
            $months[] = (int) date('m', strtotime("-$i month"));
        }
        sort($months);

        $labels = [];
        $qty = [];
        foreach ($months as $month) {
            $labels[] = date('F', strtotime('01-' . str_pad((string) $month, 2, '0', STR_PAD_LEFT) . '-' . date('Y')));
            $qty[] = (int) DB::table('order_details as od')
                ->join('orders as ord', 'ord.order_id', '=', 'od.order_id')
                ->where('ord.payment_status', '1')
                ->whereRaw('MONTH(ord.created) = ?', [$month])
                ->sum('od.qty');
        }

        return ['labels' => $labels, 'qty' => $qty];
    }

    /**
     * @return array{labels: array<int, string>, qty: array<int, int>}
     */
    private function emptyMonthlySeries(): array
    {
        $labels = [];
        $qty = [];
        $months = [];
        for ($i = 0; $i < 12; $i++) {
            $months[] = (int) date('m', strtotime("-$i month"));
        }
        sort($months);
        foreach ($months as $month) {
            $labels[] = date('F', strtotime('01-' . str_pad((string) $month, 2, '0', STR_PAD_LEFT) . '-' . date('Y')));
            $qty[] = 0;
        }

        return ['labels' => $labels, 'qty' => $qty];
    }
}

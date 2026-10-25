<?php

namespace App\Services;

use App\Enums\OrderStatusCodes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class CompanyDashboardStatsService
{
    private const COMPLETED_COD = 12;

    /**
     * Datos crudos del dashboard company (equivalente a DashboardModel::get_filter vía SQL).
     *
     * @return array<string, mixed>
     */
    public function filterData(int $companyId, Request $request): array
    {
        if (! Schema::hasTable('orders')) {
            return $this->emptyPayload();
        }

        return [
            'sales_total' => $this->getSalesTotalRow($companyId, $request, null, true),
            'sales_type' => [
                'web' => $this->getSalesTotalRow($companyId, $request, 'Catalog', true),
                'app' => $this->getSalesTotalRow($companyId, $request, 'app', true),
                'manual' => $this->getSalesTotalRow($companyId, $request, 'manual', true),
                'erp_import' => $this->getSalesTotalRow($companyId, $request, 'erp_import', true),
            ],
            'sales_by_customer' => $this->salesByCustomer($companyId, $request),
            'sales_by_category' => $this->salesByCategory($companyId, $request),
            'sales_by_vendor' => $this->salesByVendor($companyId, $request),
            'top10_products' => $this->topProducts($companyId, $request),
            'monthly_sales' => $this->monthlySales($companyId, $request),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyPayload(): array
    {
        $zero = [['total' => 0, 'qty_orders' => 0]];

        return [
            'sales_total' => $zero,
            'sales_type' => ['web' => $zero, 'app' => $zero, 'manual' => $zero, 'erp_import' => $zero],
            'sales_by_customer' => [],
            'sales_by_category' => [],
            'sales_by_vendor' => [],
            'top10_products' => [],
            'monthly_sales' => [],
        ];
    }

    /**
     * @return list<array{total: float, qty_orders: int}>
     */
    private function getSalesTotalRow(int $companyId, Request $request, ?string $source, bool $mesAnterior): array
    {
        $query = DB::table('orders as o')
            ->where('o.company_id', $companyId);

        $this->applyCompletedStatus($query, 'o');
        $this->applyOrderDateFilters($query, $request, 'o.created', $mesAnterior);

        if ($source !== null && $source !== '') {
            $query->where('o.source', $source);
        }

        $row = $query->selectRaw('COALESCE(SUM(o.total_amount), 0) as total, COUNT(o.order_id) as qty_orders')->first();

        return [[
            'total' => (float) ($row->total ?? 0),
            'qty_orders' => (int) ($row->qty_orders ?? 0),
        ]];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function salesByCustomer(int $companyId, Request $request): array
    {
        $sub = DB::table('orders as o')
            ->join('customers as c', 'c.customer_id', '=', 'o.customer_id')
            ->join('order_details as od', 'od.order_id', '=', 'o.order_id')
            ->where('o.company_id', $companyId);

        $this->applyCompletedStatus($sub, 'o');
        $this->applyOrderDateFilters($sub, $request, 'o.created', false);

        $sub->select([
            'o.order_id',
            'o.customer_id',
            DB::raw('c.business_name as customer'),
            DB::raw('o.total_amount as total_amount'),
            DB::raw('COUNT(od.detail_id) as items'),
            DB::raw('o.created as created'),
        ])->groupBy('o.order_id', 'o.customer_id', 'c.business_name', 'o.total_amount', 'o.created');

        $rows = DB::query()
            ->fromSub($sub, 'sales')
            ->select([
                'sales.customer',
                DB::raw('SUM(sales.total_amount) as total'),
                DB::raw('SUM(sales.items) as items'),
                DB::raw('MAX(sales.order_id) as last_order_id'),
                DB::raw('MAX(sales.created) as last_created'),
                DB::raw('SUM(sales.total_amount) / NULLIF(SUM(sales.items), 0) as ticket'),
            ])
            ->groupBy('sales.customer_id', 'sales.customer')
            ->orderByDesc('total')
            ->get();

        $orderIds = $rows->pluck('last_order_id')->filter()->unique()->values()->all();
        $sellersByOrder = $this->sellersByOrderIds($companyId, $orderIds);

        return $rows->map(function ($row) use ($sellersByOrder) {
            $orderId = (int) ($row->last_order_id ?? 0);

            return [
                'customer' => (string) ($row->customer ?? ''),
                'total' => (float) ($row->total ?? 0),
                'items' => (int) ($row->items ?? 0),
                'last_order_id' => $orderId,
                'last_created' => (string) ($row->last_created ?? ''),
                'ticket' => (float) ($row->ticket ?? 0),
                'seller' => $sellersByOrder[$orderId] ?? null,
            ];
        })->values()->all();
    }

    /**
     * @param list<int> $orderIds
     * @return array<int, string>
     */
    private function sellersByOrderIds(int $companyId, array $orderIds): array
    {
        if ($orderIds === [] || ! Schema::hasTable('sellers')) {
            return [];
        }

        $query = DB::table('orders as o')->where('o.company_id', $companyId)->whereIn('o.order_id', $orderIds);

        if (Schema::hasColumn('orders', 'seller_id')) {
            $query->join('sellers as v', 'v.id', '=', 'o.seller_id');
        } elseif (Schema::hasColumn('orders', 'user_id')) {
            $query->join('sellers as v', 'v.user_id', '=', 'o.user_id');
        } else {
            return [];
        }

        if (Schema::hasTable('users')) {
            $query->leftJoin('users as u', 'u.id', '=', 'v.user_id');
            $sellerExpr = "TRIM(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')))";
        } else {
            $sellerExpr = "''";
        }

        $map = [];
        foreach ($query->select(['o.order_id', DB::raw("{$sellerExpr} as seller")])->get() as $row) {
            $seller = trim((string) ($row->seller ?? ''));
            if ($seller !== '') {
                $map[(int) $row->order_id] = $seller;
            }
        }

        return $map;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function salesByCategory(int $companyId, Request $request): array
    {
        if (! Schema::hasTable('categories') || ! Schema::hasTable('products')) {
            return [];
        }

        $sub = DB::table('orders as o')
            ->join('order_details as d', 'd.order_id', '=', 'o.order_id')
            ->join('products as p', 'p.pro_id', '=', 'd.product_id')
            ->join('categories as cat', function ($join) {
                $join->whereRaw('FIND_IN_SET(cat.cat_id, p.category_id)');
            })
            ->where('o.company_id', $companyId);

        $this->applyCompletedStatus($sub, 'o');
        $this->applyOrderDateFilters($sub, $request, 'o.created', false);

        $hasCatImage = Schema::hasColumn('categories', 'image');
        $catSelect = [
            'cat.category_name',
            'cat.cat_id',
            'o.order_id',
            DB::raw('SUM(d.sale_price * d.qty) as amount'),
            DB::raw('COUNT(d.product_id) as qty_items'),
        ];
        $catGroup = ['o.order_id', 'cat.cat_id', 'cat.category_name'];
        if ($hasCatImage) {
            $catSelect[] = 'cat.image';
            $catGroup[] = 'cat.image';
        }

        $sub->select($catSelect)->groupBy($catGroup);

        $outerSelect = [
            'sub_categ.category_name',
            'sub_categ.cat_id',
            DB::raw('COUNT(sub_categ.cat_id) as qty_items'),
            DB::raw('SUM(sub_categ.amount) as amount'),
        ];
        $outerGroup = ['sub_categ.cat_id', 'sub_categ.category_name'];
        if ($hasCatImage) {
            $outerSelect[] = 'sub_categ.image';
            $outerGroup[] = 'sub_categ.image';
        }

        return DB::query()
            ->fromSub($sub, 'sub_categ')
            ->select($outerSelect)
            ->groupBy($outerGroup)
            ->orderByDesc('amount')
            ->get()
            ->map(fn ($row) => [
                'category_name' => (string) ($row->category_name ?? ''),
                'cat_id' => (int) ($row->cat_id ?? 0),
                'image_uuid' => $hasCatImage ? (string) ($row->image ?? '') : '',
                'qty_items' => (int) ($row->qty_items ?? 0),
                'amount' => (float) ($row->amount ?? 0),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function salesByVendor(int $companyId, Request $request): array
    {
        if (! Schema::hasTable('sellers')) {
            return [];
        }

        $sub = DB::table('orders as o')
            ->join('order_details as d', 'd.order_id', '=', 'o.order_id')
            ->where('o.company_id', $companyId);

        if (Schema::hasColumn('orders', 'seller_id')) {
            $sub->join('sellers as v', 'v.id', '=', 'o.seller_id');
            $vendorUserCol = 'v.user_id';
            $vendorIdCol = 'v.id';
        } elseif (Schema::hasColumn('orders', 'user_id')) {
            $sub->join('sellers as v', 'v.user_id', '=', 'o.user_id');
            $vendorUserCol = 'v.user_id';
            $vendorIdCol = 'v.user_id';
        } else {
            return [];
        }

        if (Schema::hasTable('users')) {
            $sub->leftJoin('users as u', 'u.id', '=', $vendorUserCol);
            $vendorExpr = "TRIM(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')))";
            $hasUserImage = Schema::hasColumn('users', 'image');
        } else {
            $vendorExpr = "''";
            $hasUserImage = false;
        }

        $this->applyCompletedStatus($sub, 'o');
        $this->applyOrderDateFilters($sub, $request, 'o.created', false);

        $subSelect = [
            DB::raw("{$vendorExpr} as vendor"),
            DB::raw("{$vendorUserCol} as user_id"),
            DB::raw('o.total_amount as total_amount'),
            DB::raw('COUNT(d.product_id) as items'),
        ];
        $subGroup = ['o.order_id', DB::raw($vendorUserCol), DB::raw($vendorExpr), 'o.total_amount'];
        if ($hasUserImage) {
            $subSelect[] = 'u.image';
            $subGroup[] = 'u.image';
        }

        $sub->select($subSelect)->groupBy($subGroup);

        $outerSelect = [
            'sales_vendor.vendor',
            'sales_vendor.user_id',
            DB::raw('SUM(sales_vendor.total_amount) as total'),
        ];
        $outerGroup = ['sales_vendor.user_id', 'sales_vendor.vendor'];
        if ($hasUserImage) {
            $outerSelect[] = 'sales_vendor.image';
            $outerGroup[] = 'sales_vendor.image';
        }

        return DB::query()
            ->fromSub($sub, 'sales_vendor')
            ->select($outerSelect)
            ->groupBy($outerGroup)
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row) => [
                'vendor' => (string) ($row->vendor ?? ''),
                'user_id' => (int) ($row->user_id ?? 0),
                'image' => $hasUserImage ? (string) ($row->image ?? '') : '',
                'total' => (float) ($row->total ?? 0),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function topProducts(int $companyId, Request $request): array
    {
        $query = DB::table('orders as o')
            ->join('order_details as d', 'd.order_id', '=', 'o.order_id')
            ->join('products as p', 'p.pro_id', '=', 'd.product_id')
            ->where('o.company_id', $companyId);

        $this->applyCompletedStatus($query, 'o');
        $this->applyOrderDateFilters($query, $request, 'o.created', false);

        $hasDefaultImage = Schema::hasColumn('products', 'default_image_id');
        $select = [
            'p.pro_id',
            'p.sku',
            'p.name',
            'p.purchase_price',
            DB::raw('SUM(d.qty) as qty_items'),
            DB::raw('SUM(d.sale_price * d.qty) as total'),
            DB::raw('SUM(d.sale_price * d.qty) / NULLIF(SUM(d.qty), 0) as average_ticket'),
            DB::raw('(((SUM(d.sale_price * d.qty) / NULLIF(SUM(d.qty), 0)) / NULLIF(p.purchase_price, 0)) - 1) * 100 as mark_up'),
            DB::raw('100 - (((SUM(d.sale_price * d.qty) / NULLIF(SUM(d.qty), 0)) / NULLIF(p.purchase_price, 0)) - 1) as percent'),
        ];
        $groupBy = ['p.pro_id', 'p.sku', 'p.name', 'p.purchase_price'];
        if ($hasDefaultImage) {
            $select[] = 'p.default_image_id';
            $groupBy[] = 'p.default_image_id';
        }

        return $query
            ->select($select)
            ->groupBy($groupBy)
            ->orderByDesc('qty_items')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'pro_id' => (int) ($row->pro_id ?? 0),
                'sku' => (string) ($row->sku ?? ''),
                'name' => (string) ($row->name ?? ''),
                'image_uuid' => $hasDefaultImage ? (string) ($row->default_image_id ?? '') : '',
                'purchase_price' => (float) ($row->purchase_price ?? 0),
                'qty_items' => (float) ($row->qty_items ?? 0),
                'total' => (float) ($row->total ?? 0),
                'average_ticket' => (float) ($row->average_ticket ?? 0),
                'mark_up' => (float) ($row->mark_up ?? 0),
                'percent' => (float) ($row->percent ?? 0),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{start_date: string, end_date: string, total: float, qty_orders: int}>
     */
    private function monthlySales(int $companyId, Request $request): array
    {
        $months = $this->buildMonthlyDateRanges();
        $out = [];

        foreach ($months as $month) {
            $monthRequest = clone $request;
            $monthRequest->merge([
                'fecha_desde_pordefecto' => 'personalizado',
                'desde_fecha' => $month['start_date'],
                'hasta_fecha' => $month['end_date'],
            ]);

            $row = $this->getSalesTotalRow($companyId, $monthRequest, null, false)[0] ?? ['total' => 0, 'qty_orders' => 0];
            $out[] = [
                'start_date' => $month['start_date'],
                'end_date' => $month['end_date'],
                'total' => (float) ($row['total'] ?? 0),
                'qty_orders' => (int) ($row['qty_orders'] ?? 0),
            ];
        }

        return $out;
    }

    /**
     * @return list<array{start_date: string, end_date: string}>
     */
    private function buildMonthlyDateRanges(): array
    {
        $fecha = date('Y-m-01');
        $fechaObj = new \DateTime($fecha);
        $fechaObj->modify('-12 months');
        $fechaActual = new \DateTime();
        $meses = [];
        $cont = 1;
        $fechaInicial = $fechaObj;

        while ($fechaInicial <= $fechaActual) {
            $dateInitial = $cont === 1 ? $fechaInicial->format('Y-m-d') : $fechaInicial->format('Y-m-01');
            $ultimoDiaMes = $fechaInicial->format('Y-m-t');
            $fechaInicial->modify('+1 month');
            $endDate = $fechaInicial > $fechaActual ? $fechaActual->format('Y-m-d') : $ultimoDiaMes;
            $meses[] = ['start_date' => $dateInitial, 'end_date' => $endDate];
            $cont++;
        }

        return $meses;
    }

    private function applyCompletedStatus($query, string $alias): void
    {
        if (Schema::hasColumn('orders', 'status_id')) {
            $statusId = $this->completedStatusId();
            if ($statusId !== null) {
                $query->where("{$alias}.status_id", $statusId);
            }

            return;
        }

        if (Schema::hasColumn('orders', 'order_status')) {
            $query->where("{$alias}.order_status", self::COMPLETED_COD);
        }
    }

    private function completedStatusId(): ?int
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        if (! Schema::hasTable('status_orders')) {
            $cached = self::COMPLETED_COD;

            return $cached;
        }

        $id = DB::table('status_orders')->where('cod_status', self::COMPLETED_COD)->value('id_status');
        $cached = $id !== null ? (int) $id : self::COMPLETED_COD;

        return $cached;
    }

    private function applyOrderDateFilters($query, Request $request, string $column, bool $mesAnterior): void
    {
        $fechaDesdePordefecto = $request->input('fecha_desde_pordefecto');
        $lastMonthStart = date('Y-m-01', strtotime('first day of last month'));
        $currentMonthStart = date('Y-m-01');

        if ($fechaDesdePordefecto !== null && $fechaDesdePordefecto !== '') {
            if ($fechaDesdePordefecto === 'personalizado') {
                $desdeFecha = (string) $request->input('desde_fecha', '');
                $hastaFecha = (string) $request->input('hasta_fecha', '');

                if ($desdeFecha !== '' && $desdeFecha !== 'Invalid date') {
                    $desde = date('Y-m-d', strtotime($desdeFecha));
                    $query->where($column, '>=', $desde);
                }

                if ($hastaFecha !== '' && $hastaFecha !== 'Invalid date') {
                    $hasta = date('Y-m-d', strtotime($hastaFecha . ' + 1 days'));
                    $query->where($column, '<', $hasta);
                }
            } else {
                $fecha = date('Y-m-d', strtotime((string) $fechaDesdePordefecto));
                $query->where($column, '>=', $fecha);

                // "Mes anterior": acotar al mes completo en todos los widgets.
                if ((string) $fechaDesdePordefecto === $lastMonthStart) {
                    $query->where($column, '<', $currentMonthStart);
                }
            }
        } else {
            // Por defecto: últimos 12 meses (órdenes importadas / históricas visibles al entrar).
            $query->where($column, '>=', date('Y-m-01', strtotime('-12 months')));
        }
    }
}

<?php

namespace App\Services;

use App\Enums\OrderStatusCodes;
use App\Exceptions\CreditReturnExceedsSalesException;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Una nota de crédito no puede devolver más cantidad de la que ese cliente compró del producto.
 */
class CreditReturnOverSalesService
{
    private const QTY_EPS = 0.00005;

    /**
     * @param  list<int>|null  $productIds
     * @return list<array<string, mixed>>
     */
    public function violations(int $companyId, ?int $customerId = null, ?array $productIds = null): array
    {
        if ($companyId < 1) {
            return [];
        }

        $skipStatus = [
            OrderStatusCodes::CANCELED->value,
            OrderStatusCodes::REJECTED->value,
            OrderStatusCodes::SHOPPING->value,
        ];

        $sold = "SUM(CASE WHEN UPPER(TRIM(o.tipo_d)) = 'D' THEN ABS(od.qty) ELSE 0 END)";
        $credited = "SUM(CASE WHEN UPPER(TRIM(o.tipo_d)) = 'C' THEN ABS(od.qty) ELSE 0 END)";

        $query = DB::table('order_details as od')
            ->join('orders as o', 'o.order_id', '=', 'od.order_id')
            ->leftJoin('status_orders as s', 's.id_status', '=', 'o.status_id')
            ->leftJoin('customers as c', 'c.customer_id', '=', 'o.customer_id')
            ->leftJoin('products as p', 'p.pro_id', '=', 'od.product_id')
            ->where('o.company_id', $companyId)
            ->whereNull('od.deleted_at')
            ->whereRaw("UPPER(TRIM(o.tipo_d)) IN ('D', 'C')")
            ->where(function ($status) use ($skipStatus) {
                $status->whereNull('s.cod_status')
                    ->orWhereNotIn('s.cod_status', $skipStatus);
            })
            ->when($customerId !== null && $customerId > 0, function ($q) use ($customerId) {
                $q->where('o.customer_id', $customerId);
            })
            ->when(is_array($productIds) && $productIds !== [], function ($q) use ($productIds) {
                $q->whereIn('od.product_id', $productIds);
            })
            ->groupBy('o.customer_id', 'od.product_id')
            ->havingRaw($credited . ' > ' . $sold . ' + ?', [self::QTY_EPS])
            ->select([
                'o.customer_id',
                'od.product_id',
                DB::raw('MAX(c.business_name) as business_name'),
                DB::raw('MAX(c.name) as customer_name'),
                DB::raw('MAX(p.sku) as sku'),
                DB::raw('MAX(p.name) as product_name'),
                DB::raw($sold . ' as qty_sold'),
                DB::raw($credited . ' as qty_credited'),
            ])
            ->orderBy('customer_name')
            ->orderBy('sku');

        $items = [];
        foreach ($query->get() as $row) {
            $soldQty = round((float) $row->qty_sold, 4);
            $creditedQty = round((float) $row->qty_credited, 4);
            $business = trim((string) ($row->business_name ?? ''));
            $name = trim((string) ($row->customer_name ?? ''));
            $items[] = [
                'customer_id' => (int) $row->customer_id,
                'customer_name' => $business !== '' ? $business : $name,
                'product_id' => (int) $row->product_id,
                'sku' => trim((string) ($row->sku ?? '')),
                'product_name' => trim((string) ($row->product_name ?? '')),
                'qty_sold' => $soldQty,
                'qty_credited' => $creditedQty,
                'qty_excess' => round($creditedQty - $soldQty, 4),
                'image_uuid' => '',
            ];
        }

        return $this->attachImages($items);
    }

    /**
     * Resumen de excesos de un cliente y las notas de crédito que los contienen.
     *
     * @return array{customer_id: int, customer_name: string, products: list<array<string, mixed>>, credit_notes: list<array<string, mixed>>}|null
     */
    public function customerReport(int $companyId, int $customerId): ?array
    {
        if ($companyId < 1 || $customerId < 1) {
            return null;
        }

        $products = $this->violations($companyId, $customerId);
        if ($products === []) {
            return null;
        }

        $productIds = [];
        $byProduct = [];
        foreach ($products as $product) {
            $productId = (int) ($product['product_id'] ?? 0);
            if ($productId < 1) {
                continue;
            }
            $productIds[] = $productId;
            $byProduct[$productId] = $product;
        }
        if ($productIds === []) {
            return null;
        }

        $skipStatus = [
            OrderStatusCodes::CANCELED->value,
            OrderStatusCodes::REJECTED->value,
            OrderStatusCodes::SHOPPING->value,
        ];

        $rows = DB::table('order_details as od')
            ->join('orders as o', 'o.order_id', '=', 'od.order_id')
            ->leftJoin('status_orders as s', 's.id_status', '=', 'o.status_id')
            ->leftJoin('products as p', 'p.pro_id', '=', 'od.product_id')
            ->where('o.company_id', $companyId)
            ->where('o.customer_id', $customerId)
            ->whereNull('od.deleted_at')
            ->whereRaw("UPPER(TRIM(o.tipo_d)) = 'C'")
            ->whereIn('od.product_id', $productIds)
            ->where(function ($status) use ($skipStatus) {
                $status->whereNull('s.cod_status')
                    ->orWhereNotIn('s.cod_status', $skipStatus);
            })
            ->orderBy('o.created')
            ->orderBy('o.order_id')
            ->orderBy('od.detail_id')
            ->get([
                'o.order_id',
                'o.order_number',
                'o.created',
                'od.product_id',
                'od.qty',
                'od.sale_price',
                'p.sku',
                'p.name as product_name',
            ]);

        $notes = [];
        foreach ($rows as $row) {
            $orderId = (int) $row->order_id;
            if (! isset($notes[$orderId])) {
                $created = trim((string) ($row->created ?? ''));
                $notes[$orderId] = [
                    'order_id' => $orderId,
                    'order_number' => trim((string) ($row->order_number ?? '')),
                    'date' => strlen($created) >= 10 ? substr($created, 0, 10) : $created,
                    'lines' => [],
                ];
            }
            $productId = (int) $row->product_id;
            $product = $byProduct[$productId] ?? [];
            $notes[$orderId]['lines'][] = [
                'product_id' => $productId,
                'sku' => trim((string) ($row->sku ?? '')),
                'product_name' => trim((string) ($row->product_name ?? '')),
                'qty' => round(abs((float) $row->qty), 4),
                'price' => round((float) $row->sale_price, 4),
                'qty_sold' => (float) ($product['qty_sold'] ?? 0),
                'qty_credited' => (float) ($product['qty_credited'] ?? 0),
                'qty_excess' => (float) ($product['qty_excess'] ?? 0),
            ];
        }

        return [
            'customer_id' => $customerId,
            'customer_name' => (string) ($products[0]['customer_name'] ?? ''),
            'products' => $products,
            'credit_notes' => array_values($notes),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function attachImages(array $items): array
    {
        $ids = [];
        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            if ($productId > 0) {
                $ids[$productId] = $productId;
            }
        }
        if ($ids === []) {
            return $items;
        }

        $idList = array_values($ids);
        $byProduct = [];
        $defaults = DB::table('products')
            ->whereIn('pro_id', $idList)
            ->pluck('default_image_id', 'pro_id');
        foreach ($defaults as $productId => $uuid) {
            $uuid = trim((string) $uuid);
            if ($uuid !== '') {
                $byProduct[(int) $productId] = $uuid;
            }
        }

        $missing = array_values(array_diff($idList, array_keys($byProduct)));
        if ($missing !== []) {
            $rows = DB::table('product_images')
                ->whereIn('product_id', $missing)
                ->whereNull('deleted_at')
                ->orderBy('img_order')
                ->orderBy('img_id')
                ->get(['product_id', 'image_id']);
            foreach ($rows as $row) {
                $productId = (int) $row->product_id;
                if (isset($byProduct[$productId])) {
                    continue;
                }
                $uuid = trim((string) ($row->image_id ?? ''));
                if ($uuid !== '') {
                    $byProduct[$productId] = $uuid;
                }
            }
        }

        foreach ($items as $index => $item) {
            $items[$index]['image_uuid'] = $byProduct[(int) ($item['product_id'] ?? 0)] ?? '';
        }

        return $items;
    }

    public function assertOrderWithinSales(Order $order): void
    {
        if (strtoupper((string) ($order->tipo_d ?? '')) !== 'C') {
            return;
        }

        $order->loadMissing('order_details');
        $productIds = [];
        foreach ($order->order_details ?? [] as $detail) {
            $productId = (int) ($detail->product_id ?? 0);
            if ($productId > 0) {
                $productIds[$productId] = $productId;
            }
        }
        if ($productIds === []) {
            return;
        }

        $items = $this->violations(
            (int) $order->company_id,
            (int) $order->customer_id,
            array_values($productIds)
        );
        if ($items !== []) {
            throw new CreditReturnExceedsSalesException($items);
        }
    }
}

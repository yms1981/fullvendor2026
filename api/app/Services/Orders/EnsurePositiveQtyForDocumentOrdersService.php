<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Órdenes normales (tipo_d = D):
 * - qty siempre positiva
 * - eliminar líneas con qty = 0
 * - eliminar órdenes sin líneas
 * - recalcular montos de líneas y cabecera
 *
 * No aplica a inventario (tipo_d = I).
 */
class EnsurePositiveQtyForDocumentOrdersService
{
    private const QTY_EPSILON = 0.0001;

    /**
     * Normaliza qty en memoria antes de persistir un OrderDetail (evento saving).
     * Regla: tipo_d = D ⇒ qty = abs(qty) si venía negativa.
     */
    public function normalizeDetailQty(OrderDetail $detail): void
    {
        $qty = (float) ($detail->getAttributes()['qty'] ?? $detail->qty ?? 0);
        if ($qty >= 0) {
            return;
        }

        $orderId = (int) ($detail->order_id ?? 0);
        if ($orderId < 1) {
            return;
        }

        if ($this->resolveTipoD($detail, $orderId) === 'D') {
            $detail->setAttribute('qty', abs($qty));
        }
    }

    /**
     * Corrige qty negativas de una orden D (sin borrar ceros ni recalcular).
     */
    public function repairOrder(int $orderId): int
    {
        $order = Order::query()
            ->select(['order_id', 'tipo_d'])
            ->where('order_id', $orderId)
            ->first();

        if (! $order || strtoupper((string) $order->tipo_d) !== 'D') {
            return 0;
        }

        return $this->absNegativeQtyForOrderIds([$orderId]);
    }

    /**
     * Fuerza ABS(qty) en todas las líneas de órdenes tipo_d = D (incluye soft-deleted).
     * Si $companyId es null, aplica a todas las compañías.
     */
    public function forceAbsNegativeQty(?int $companyId = null): int
    {
        $sql = 'UPDATE order_details od
             INNER JOIN orders o ON o.order_id = od.order_id
             SET od.qty = ABS(od.qty)
             WHERE o.tipo_d = ?
               AND od.qty < 0';
        $bindings = ['D'];

        if ($companyId !== null && $companyId > 0) {
            $sql .= ' AND o.company_id = ?';
            $bindings[] = $companyId;
        }

        return (int) DB::update($sql, $bindings);
    }

    /**
     * Solo ABS de qty negativas globales (comando / backfill).
     *
     * @return array{found: int, updated: int}
     */
    public function repairAll(bool $dryRun = false): array
    {
        $found = (int) DB::table('order_details as od')
            ->join('orders as o', 'o.order_id', '=', 'od.order_id')
            ->where('o.tipo_d', 'D')
            ->where('od.qty', '<', 0)
            ->count();

        if ($dryRun || $found === 0) {
            return ['found' => $found, 'updated' => 0];
        }

        $updated = $this->forceAbsNegativeQty(null);

        return ['found' => $found, 'updated' => $updated];
    }

    /**
     * Al entrar al listado de órdenes: sanitiza todas las D de la compañía que lo necesiten.
     *
     * @return array{qty_fixed: int, zero_items_deleted: int, orders_deleted: int, recalculated: int, orders_touched: int}
     */
    public function sanitizeCompanyDocumentOrders(int $companyId): array
    {
        $empty = [
            'qty_fixed' => 0,
            'zero_items_deleted' => 0,
            'orders_deleted' => 0,
            'recalculated' => 0,
            'orders_touched' => 0,
        ];

        if ($companyId < 1) {
            return $empty;
        }

        // Detectar ANTES de mutar (si ABS de cabecera va primero, se pierde el recálculo real).
        $orderIds = $this->findOrderIdsNeedingSanitize($companyId);

        $bulkFixed = $this->forceAbsNegativeQty($companyId);

        // Cabeceras negativas: incluir sí o sí para recalcular desde líneas (no solo ABS).
        $negHeaderIds = Order::query()
            ->where('company_id', $companyId)
            ->where('tipo_d', 'D')
            ->where(function ($q) {
                $q->where('amount', '<', 0)->orWhere('total_amount', '<', 0);
            })
            ->pluck('order_id')
            ->map(fn ($id) => (int) $id)
            ->all();
        $orderIds = array_values(array_unique(array_merge($orderIds, $negHeaderIds)));

        if ($orderIds === [] && $bulkFixed === 0) {
            return $empty;
        }

        $stats = $empty;
        $stats['qty_fixed'] = $bulkFixed;

        foreach ($orderIds as $orderId) {
            $result = $this->sanitizeDocumentOrder($orderId, skipBulkAbs: true);
            if (! ($result['touched'] ?? false)) {
                // Aun sin cambios de líneas, forzar recálculo si cabecera estaba mal.
                $order = Order::query()->where('order_id', $orderId)->where('tipo_d', 'D')->first();
                if ($order && ((float) ($order->amount ?? 0) < 0 || (float) ($order->total_amount ?? 0) < 0 || $this->headerLooksStale($order))) {
                    $this->recalculateOrderTotals($order);
                    $stats['recalculated']++;
                    $stats['orders_touched']++;
                }
                continue;
            }
            $stats['orders_touched']++;
            $stats['qty_fixed'] += (int) ($result['qty_fixed'] ?? 0);
            $stats['zero_items_deleted'] += (int) ($result['zero_items_deleted'] ?? 0);
            $stats['orders_deleted'] += (int) ($result['order_deleted'] ?? 0);
            $stats['recalculated'] += (int) ($result['recalculated'] ?? 0);
        }

        // Red de seguridad: ABS cabeceras que sigan negativas tras el loop.
        $headerFixed = $this->bulkAbsNegativeHeaderAmountsForCompany($companyId);
        if ($headerFixed > 0) {
            $stats['orders_touched'] = max(1, $stats['orders_touched']);
        }

        if ($stats['orders_touched'] > 0 || $stats['qty_fixed'] > 0 || $headerFixed > 0) {
            Log::info('Document orders sanitized on list entry', [
                'company_id' => $companyId,
                'stats' => $stats,
                'header_fixed' => $headerFixed,
            ]);
        }

        return $stats;
    }

    /**
     * Sanitiza una orden documento: ABS qty, borra ítems 0, borra si queda vacía, recalcula.
     *
     * @return array{touched: bool, qty_fixed: int, zero_items_deleted: int, order_deleted: int, recalculated: int}
     */
    public function sanitizeDocumentOrder(int $orderId, bool $skipBulkAbs = false): array
    {
        $result = [
            'touched' => false,
            'qty_fixed' => 0,
            'zero_items_deleted' => 0,
            'order_deleted' => 0,
            'recalculated' => 0,
        ];

        return DB::transaction(function () use ($orderId, $result, $skipBulkAbs) {
            /** @var Order|null $order */
            $order = Order::query()
                ->where('order_id', $orderId)
                ->lockForUpdate()
                ->first();

            if (! $order || strtoupper((string) $order->tipo_d) !== 'D') {
                return $result;
            }

            if (! $skipBulkAbs) {
                $qtyFixed = $this->absNegativeQtyForOrderIds([$orderId]);
                if ($qtyFixed > 0) {
                    $result['qty_fixed'] = $qtyFixed;
                    $result['touched'] = true;
                }
            }

            $zeroDetails = OrderDetail::query()
                ->where('order_id', $orderId)
                ->where(function ($q) {
                    $q->where('qty', '=', 0)
                        ->orWhereRaw('ABS(qty) < ?', [self::QTY_EPSILON]);
                })
                ->get();

            foreach ($zeroDetails as $detail) {
                $detail->forceDelete();
                $result['zero_items_deleted']++;
                $result['touched'] = true;
            }

            $remaining = OrderDetail::query()->where('order_id', $orderId)->count();
            if ($remaining === 0) {
                $order->delete();
                $result['order_deleted'] = 1;
                $result['touched'] = true;

                return $result;
            }

            $order = $order->fresh() ?? $order;
            $needsRecalc = $result['touched']
                || (float) ($order->amount ?? 0) < 0
                || (float) ($order->total_amount ?? 0) < 0
                || $this->headerLooksStale($order);

            if ($needsRecalc) {
                $this->recalculateOrderTotals($order);
                $result['recalculated'] = 1;
                $result['touched'] = true;
            }

            return $result;
        });
    }

    /**
     * Recalcula líneas + cabecera (mismo criterio que create de OrderController + delivered_*).
     */
    public function recalculateOrderTotals(Order $order): void
    {
        if (strtoupper((string) $order->tipo_d) !== 'D') {
            return;
        }

        $discountType = (int) ($order->discount_type ?? 0);
        $discount = (float) ($order->discount ?? 0);
        $lineDiscRate = ($discountType === 1 && $discount > 0) ? ($discount / 100.0) : 0.0;

        $details = OrderDetail::query()->where('order_id', $order->order_id)->get();
        $subTotal = 0.0;

        foreach ($details as $detail) {
            $qty = abs((float) ($detail->qty ?? 0));
            if ((float) ($detail->qty ?? 0) < 0) {
                $detail->qty = $qty;
            }
            $sale = (float) ($detail->sale_price ?? 0);
            $deliveredQty = (float) ($detail->delivered_quantity ?? 0);

            $amountSales = $qty * $sale;
            $discountAmount = $amountSales * $lineDiscRate;
            $totalAmount = $amountSales - $discountAmount;

            $amountDelivered = $deliveredQty * $sale;
            $discountDelivered = $amountDelivered * $lineDiscRate;
            $totalDelivered = $amountDelivered - $discountDelivered;

            $detail->amount_sales = $amountSales;
            $detail->discount_amount = $discountAmount;
            $detail->total_amount = $totalAmount;
            $detail->amount_delivered = $amountDelivered;
            $detail->discount_delivered = $discountDelivered;
            $detail->total_delivered = $totalDelivered;
            $detail->amount = abs($deliveredQty) > self::QTY_EPSILON ? $amountDelivered : $amountSales;
            $detail->discountt = abs($deliveredQty) > self::QTY_EPSILON ? $discountDelivered : $discountAmount;
            $detail->total = abs($deliveredQty) > self::QTY_EPSILON ? $totalDelivered : $totalAmount;
            $detail->saveQuietly();

            $subTotal += $amountSales;
        }

        $discountAmountHeader = 0.0;
        if ($discountType === 1) {
            $discountAmountHeader = $subTotal * ($discount / 100.0);
        } elseif ($discountType === 2) {
            $discountAmountHeader = $discount;
        }

        $order->amount = $subTotal;
        $order->discount_a = $discountAmountHeader;
        $order->total_amount = max(0.0, $subTotal - $discountAmountHeader);
        $order->amount_delivered = (float) $details->sum(fn (OrderDetail $d) => (float) ($d->amount_delivered ?? 0));
        $order->discount_delivered = (float) $details->sum(fn (OrderDetail $d) => (float) ($d->discount_delivered ?? 0));
        $order->total_delivered = (float) $details->sum(fn (OrderDetail $d) => (float) ($d->total_delivered ?? 0));
        // Preservar `updated` explícitamente: la columna tiene ON UPDATE current_timestamp(),
        // y si no va en el SET MySQL la pisaría a "ahora" (rompe el orden del listado).
        $preservedUpdated = $order->getRawOriginal('updated')
            ?? ($order->getAttributes()['updated'] ?? null);
        if ($preservedUpdated !== null) {
            $order->setAttribute('updated', $preservedUpdated);
        }
        $order->timestamps = false;
        $order->saveQuietly();
        $order->timestamps = true;
    }

    private function bulkAbsNegativeQtyForCompany(int $companyId): int
    {
        return $this->forceAbsNegativeQty($companyId);
    }

    /**
     * Corrige cabeceras ya persistidas en negativo (el listado lee amount/total_amount).
     */
    private function bulkAbsNegativeHeaderAmountsForCompany(int $companyId): int
    {
        // `updated = updated` evita que ON UPDATE current_timestamp() altere la fecha de modificación.
        return (int) DB::update(
            'UPDATE orders
             SET amount = ABS(amount),
                 total_amount = ABS(COALESCE(total_amount, 0)),
                 discount_a = ABS(COALESCE(discount_a, 0)),
                 updated = updated
             WHERE company_id = ?
               AND tipo_d = ?
               AND (amount < 0 OR total_amount < 0 OR discount_a < 0)',
            [$companyId, 'D']
        );
    }

    /**
     * @return list<int>
     */
    private function findOrderIdsNeedingSanitize(int $companyId): array
    {
        $badQty = DB::table('order_details as od')
            ->join('orders as o', 'o.order_id', '=', 'od.order_id')
            ->where('o.company_id', $companyId)
            ->where('o.tipo_d', 'D')
            ->where(function ($q) {
                $q->where('od.qty', '<', 0)
                    ->orWhere('od.qty', '=', 0)
                    ->orWhereRaw('ABS(od.qty) < ?', [self::QTY_EPSILON]);
            })
            ->distinct()
            ->pluck('o.order_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $negHeader = Order::query()
            ->where('company_id', $companyId)
            ->where('tipo_d', 'D')
            ->where(function ($q) {
                $q->where('amount', '<', 0)->orWhere('total_amount', '<', 0);
            })
            ->pluck('order_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $empty = Order::query()
            ->where('company_id', $companyId)
            ->where('tipo_d', 'D')
            ->whereDoesntHave('order_details')
            ->pluck('order_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_values(array_unique(array_merge($badQty, $negHeader, $empty)));
    }

    private function headerLooksStale(Order $order): bool
    {
        $linesSum = (float) OrderDetail::query()
            ->where('order_id', $order->order_id)
            ->selectRaw('COALESCE(SUM(ABS(qty) * sale_price), 0) as s')
            ->value('s');

        $header = (float) ($order->amount ?? 0);
        if ($linesSum > self::QTY_EPSILON && abs($header - $linesSum) > 0.02) {
            return true;
        }

        return $header < 0 || (float) ($order->total_amount ?? 0) < 0;
    }

    /**
     * @param  list<int>  $orderIds
     */
    private function absNegativeQtyForOrderIds(array $orderIds): int
    {
        if ($orderIds === []) {
            return 0;
        }

        $ids = OrderDetail::query()
            ->whereIn('order_id', $orderIds)
            ->where('qty', '<', 0)
            ->pluck('detail_id');

        if ($ids->isEmpty()) {
            return 0;
        }

        return (int) DB::table('order_details')
            ->whereIn('detail_id', $ids->all())
            ->update(['qty' => DB::raw('ABS(qty)')]);
    }

    private function resolveTipoD(OrderDetail $detail, int $orderId): string
    {
        if ($detail->relationLoaded('order') && $detail->order) {
            return strtoupper((string) ($detail->order->tipo_d ?? ''));
        }

        $tipo = Order::query()->where('order_id', $orderId)->value('tipo_d');

        return strtoupper((string) ($tipo ?? ''));
    }
}

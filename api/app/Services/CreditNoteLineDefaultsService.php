<?php

namespace App\Services;

use App\Enums\OrderStatusCodes;
use App\Models\CustomerOrderProductFifoAllocation;
use App\Models\Order;
use App\Models\OrderDetail;

/**
 * Notas de crédito (tipo_d = C):
 * - Si no hay cantidad asignada, queda igual a la cantidad.
 * - El bulto es un entero: cantidad / MOQ (minimum_stock), redondeado hacia arriba.
 * - La nota de la línea y el dato bajo el nombre indican de qué orden se devuelve, con cantidad y precio.
 * - Si la línea sale de varias órdenes, el precio del producto es el promedio ponderado y se recalcula la nota.
 * Se aplica al guardar la NC y otra vez al abrirla si falta.
 */
class CreditNoteLineDefaultsService
{
    private const QTY_EPS = 0.00005;

    /** @var array<int, list<array{order_number: string, price: float, remaining: float}>>|null */
    private ?array $salePools = null;

    public function ensure(Order $order, bool $allowFifoSync = true): Order
    {
        if (strtoupper((string) ($order->tipo_d ?? '')) !== 'C') {
            return $order;
        }

        $this->salePools = null;
        $order->loadMissing(['order_details.product']);
        $orderId = (int) ($order->order_id ?? 0);
        if ($orderId < 1) {
            return $order;
        }

        $grouped = $this->allocationsByDetail($orderId);
        if ($grouped === [] && $allowFifoSync && app(CustomerOrderProductFifoService::class)->tablesReady()) {
            app(CustomerOrderProductFifoService::class)->syncOrder($order, false);
            $order->load(['order_details.product']);
            $grouped = $this->allocationsByDetail($orderId);
        }

        $returnsByDetail = [];
        $recalculate = false;
        $priceDigits = $this->priceDigits($order);
        foreach ($order->order_details ?? [] as $detail) {
            if (! $detail instanceof OrderDetail) {
                continue;
            }
            $detailId = (int) ($detail->detail_id ?? 0);
            $returns = $grouped[$detailId] ?? [];
            if ($returns === []) {
                $returns = $this->takeFromSales($order, $detail);
            }
            $returnsByDetail[$detailId] = $returns;
            if ($this->applyAveragePrice($detail, $returns, $priceDigits)) {
                $recalculate = true;
            }
            $this->applyDetail($detail, $returns);
            $detail->setAttribute('credit_returns', $returns);
        }

        if ($recalculate) {
            $this->recalculateOrder($order);
            foreach ($order->order_details ?? [] as $detail) {
                if (! $detail instanceof OrderDetail) {
                    continue;
                }
                $detailId = (int) ($detail->detail_id ?? 0);
                if (isset($returnsByDetail[$detailId])) {
                    $detail->setAttribute('credit_returns', $returnsByDetail[$detailId]);
                }
            }
        }

        return $order;
    }

    /**
     * @return array<int, list<array{order_number: string, qty: float, price: float}>>
     */
    private function allocationsByDetail(int $orderId): array
    {
        if (! app(CustomerOrderProductFifoService::class)->tablesReady()) {
            return [];
        }

        $rows = CustomerOrderProductFifoAllocation::query()
            ->with('fifo')
            ->where('credit_order_id', $orderId)
            ->get();

        $grouped = [];
        foreach ($rows as $row) {
            $detailId = (int) ($row->credit_detail_id ?? 0);
            $number = trim((string) ($row->fifo->source_order_number ?? ''));
            if ($detailId < 1 || $number === '') {
                continue;
            }
            $price = round((float) ($row->fifo->sale_price ?? $row->credit_price ?? 0), 2);
            $qty = round((float) ($row->qty ?? 0), 4);
            $key = $number.'|'.$price;
            if (! isset($grouped[$detailId][$key])) {
                $grouped[$detailId][$key] = [
                    'order_number' => $number,
                    'qty' => 0.0,
                    'price' => $price,
                ];
            }
            $grouped[$detailId][$key]['qty'] = round($grouped[$detailId][$key]['qty'] + $qty, 4);
        }

        $out = [];
        foreach ($grouped as $detailId => $items) {
            $out[(int) $detailId] = array_values($items);
        }

        return $out;
    }

    /**
     * Si el FIFO no asignó la línea (la venta aún no está completada en almacén),
     * se recorre el historial de ventas del cliente, de la más antigua a la más nueva.
     *
     * @return list<array{order_number: string, qty: float, price: float}>
     */
    private function takeFromSales(Order $order, OrderDetail $detail): array
    {
        $needed = abs((float) ($detail->qty ?? 0));
        $productId = (int) ($detail->product_id ?? 0);
        if ($needed <= self::QTY_EPS || $productId < 1) {
            return [];
        }

        $this->buildSalePools($order);
        if (! isset($this->salePools[$productId])) {
            return [];
        }

        $creditPrice = round((float) ($detail->sale_price ?? 0), 4);
        $taken = [];
        foreach ($this->salePools[$productId] as &$row) {
            if ($needed <= self::QTY_EPS) {
                break;
            }
            if ($creditPrice > $row['price'] + self::QTY_EPS) {
                continue;
            }
            $chunk = min($needed, $row['remaining']);
            if ($chunk <= self::QTY_EPS) {
                continue;
            }
            $row['remaining'] = round($row['remaining'] - $chunk, 4);
            $needed = round($needed - $chunk, 4);
            $key = $row['order_number'].'|'.$row['price'];
            if (! isset($taken[$key])) {
                $taken[$key] = [
                    'order_number' => $row['order_number'],
                    'qty' => 0.0,
                    'price' => round($row['price'], 2),
                ];
            }
            $taken[$key]['qty'] = round($taken[$key]['qty'] + $chunk, 4);
        }
        unset($row);

        return array_values($taken);
    }

    private function buildSalePools(Order $order): void
    {
        if ($this->salePools !== null) {
            return;
        }

        $this->salePools = [];
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

        $companyId = (int) ($order->company_id ?? 0);
        $customerId = (int) ($order->customer_id ?? 0);
        if ($companyId < 1 || $customerId < 1) {
            return;
        }

        $skipStatus = [
            OrderStatusCodes::CANCELED->value,
            OrderStatusCodes::REJECTED->value,
            OrderStatusCodes::SHOPPING->value,
        ];

        $sales = $this->productLines($companyId, $customerId, array_values($productIds), 'D', (int) $order->order_id, $skipStatus);
        foreach ($sales as $sale) {
            $number = trim((string) ($sale->order_number ?? ''));
            $qty = abs((float) ($sale->qty ?? 0));
            if ($number === '' || $qty <= self::QTY_EPS) {
                continue;
            }
            $this->salePools[(int) $sale->product_id][] = [
                'order_number' => $number,
                'price' => round((float) ($sale->sale_price ?? 0), 4),
                'remaining' => $qty,
            ];
        }

        $others = $this->productLines($companyId, $customerId, array_values($productIds), 'C', (int) $order->order_id, $skipStatus);
        foreach ($others as $other) {
            $productId = (int) $other->product_id;
            if (! isset($this->salePools[$productId])) {
                continue;
            }
            $left = abs((float) ($other->qty ?? 0));
            $creditPrice = round((float) ($other->sale_price ?? 0), 4);
            foreach ($this->salePools[$productId] as &$row) {
                if ($left <= self::QTY_EPS) {
                    break;
                }
                if ($creditPrice > $row['price'] + self::QTY_EPS) {
                    continue;
                }
                $chunk = min($left, $row['remaining']);
                if ($chunk <= self::QTY_EPS) {
                    continue;
                }
                $row['remaining'] = round($row['remaining'] - $chunk, 4);
                $left = round($left - $chunk, 4);
            }
            unset($row);
        }
    }

    /**
     * @param  list<int>  $productIds
     * @param  list<int>  $skipStatus
     */
    private function productLines(int $companyId, int $customerId, array $productIds, string $tipo, int $exceptOrderId, array $skipStatus)
    {
        return OrderDetail::query()
            ->select([
                'order_details.detail_id',
                'order_details.product_id',
                'order_details.qty',
                'order_details.sale_price',
                'orders.order_number',
            ])
            ->join('orders', 'orders.order_id', '=', 'order_details.order_id')
            ->leftJoin('status_orders', 'status_orders.id_status', '=', 'orders.status_id')
            ->where('orders.company_id', $companyId)
            ->where('orders.customer_id', $customerId)
            ->where('orders.order_id', '!=', $exceptOrderId)
            ->whereRaw("UPPER(TRIM(orders.tipo_d)) = ?", [$tipo])
            ->whereIn('order_details.product_id', $productIds)
            ->where(function ($query) use ($skipStatus) {
                $query->whereNull('status_orders.cod_status')
                    ->orWhereNotIn('status_orders.cod_status', $skipStatus);
            })
            ->orderBy('orders.created')
            ->orderBy('orders.order_id')
            ->orderBy('order_details.detail_id')
            ->get();
    }

    /**
     * @param  list<array{order_number: string, qty: float, price: float}>  $returns
     */
    private function applyDetail(OrderDetail $detail, array $returns): void
    {
        unset($detail->credit_returns);

        $qty = abs((float) ($detail->qty ?? 0));
        if ($qty <= self::QTY_EPS) {
            return;
        }

        $dirty = false;
        $assigned = $detail->delivered_quantity;
        if ($assigned === null || abs((float) $assigned) <= self::QTY_EPS) {
            $detail->delivered_quantity = $qty;
            $dirty = true;
        }

        if (! $this->isWholePack($detail->delivered_pack)) {
            $packs = $this->packsFromMoq($detail, $qty);
            $detail->delivered_pack = $packs;
            $detail->pack = $packs;
            $dirty = true;
        } elseif (! $this->isWholePack($detail->pack)) {
            $detail->pack = (int) round((float) $detail->delivered_pack);
            $dirty = true;
        }

        $note = $this->noteFromReturns($returns);
        if ($note !== '' && ! $this->commentHasReturns($detail, $returns)) {
            $visible = $this->visibleComment($detail);
            $next = $visible === '' ? $note : ($visible.' | '.$note);
            $detail->comments = $next;
            $detail->comment = $next;
            $dirty = true;
        }

        if ($dirty) {
            $detail->save();
        }
    }

    /**
     * Precio promedio ponderado por cantidad cuando la línea sale de más de una orden.
     *
     * @param  list<array{order_number: string, qty: float, price: float}>  $returns
     */
    private function applyAveragePrice(OrderDetail $detail, array $returns, int $priceDigits): bool
    {
        $orders = [];
        $qtySum = 0.0;
        $amount = 0.0;
        foreach ($returns as $row) {
            $number = trim((string) ($row['order_number'] ?? ''));
            $qty = (float) ($row['qty'] ?? 0);
            if ($number === '' || $qty <= self::QTY_EPS) {
                continue;
            }
            $orders[$number] = true;
            $qtySum += $qty;
            $amount += $qty * (float) ($row['price'] ?? 0);
        }
        if (count($orders) < 2 || $qtySum <= self::QTY_EPS) {
            return false;
        }

        $average = round($amount / $qtySum, $priceDigits);
        if (abs((float) ($detail->sale_price ?? 0) - $average) > 0.0001) {
            $detail->sale_price = $average;
        }

        return true;
    }

    private function priceDigits(Order $order): int
    {
        $order->loadMissing('company.format_numeric');
        $digits = (int) ($order->company->format_numeric->digits ?? 2);
        if ($digits < 0) {
            return 2;
        }
        if ($digits > 6) {
            return 6;
        }

        return $digits;
    }

    private function recalculateOrder(Order $order): void
    {
        $discountType = (int) ($order->discount_type ?? 0);
        $discount = (float) ($order->discount ?? 0);
        $lineDiscRate = ($discountType === 1 && $discount > 0) ? ($discount / 100.0) : 0.0;

        $subTotal = 0.0;
        $amountDelivered = 0.0;
        $discountDeliveredSum = 0.0;
        $totalDeliveredSum = 0.0;

        foreach ($order->order_details ?? [] as $detail) {
            if (! $detail instanceof OrderDetail) {
                continue;
            }
            unset($detail->credit_returns);
            $qtySigned = (float) ($detail->qty ?? 0);
            $sale = (float) ($detail->sale_price ?? 0);
            $deliveredQty = (float) ($detail->delivered_quantity ?? 0);

            $amountSales = $qtySigned * $sale;
            $discountAmount = $amountSales * $lineDiscRate;
            $totalAmount = $amountSales - $discountAmount;
            $deliveredAmount = $deliveredQty * $sale;
            $deliveredDiscount = $deliveredAmount * $lineDiscRate;
            $deliveredTotal = $deliveredAmount - $deliveredDiscount;

            $detail->amount_sales = $amountSales;
            $detail->discount_amount = $discountAmount;
            $detail->total_amount = $totalAmount;
            $detail->amount_delivered = $deliveredAmount;
            $detail->discount_delivered = $deliveredDiscount;
            $detail->total_delivered = $deliveredTotal;
            $detail->amount = $deliveredQty > self::QTY_EPS ? $deliveredAmount : $amountSales;
            $detail->discountt = $deliveredQty > self::QTY_EPS ? $deliveredDiscount : $discountAmount;
            $detail->total = $deliveredQty > self::QTY_EPS ? $deliveredTotal : $totalAmount;
            if ($detail->isDirty()) {
                $detail->save();
            }

            $subTotal += $amountSales;
            $amountDelivered += $deliveredAmount;
            $discountDeliveredSum += $deliveredDiscount;
            $totalDeliveredSum += $deliveredTotal;
        }

        $headerDiscount = 0.0;
        if ($discountType === 1) {
            $headerDiscount = $subTotal * ($discount / 100.0);
        } elseif ($discountType === 2) {
            $headerDiscount = $discount;
        }

        $net = $subTotal - $headerDiscount;
        if ($subTotal >= 0) {
            $net = max(0.0, $net);
        }
        $order->amount = $subTotal;
        $order->discount_a = $headerDiscount;
        $order->total_amount = $net;
        $order->amount_delivered = $amountDelivered;
        $order->discount_delivered = $discountDeliveredSum;
        $order->total_delivered = $totalDeliveredSum;

        $preservedUpdated = $order->getRawOriginal('updated')
            ?? ($order->getAttributes()['updated'] ?? null);
        if ($preservedUpdated !== null) {
            $order->setAttribute('updated', $preservedUpdated);
        }
        if ($order->isDirty()) {
            $order->timestamps = false;
            $order->saveQuietly();
            $order->timestamps = true;
        }
    }

    /**
     * @param mixed $pack
     */
    private function isWholePack($pack): bool
    {
        if ($pack === null || $pack === '') {
            return false;
        }
        $value = (float) $pack;
        if ($value <= self::QTY_EPS) {
            return false;
        }

        return abs($value - round($value)) <= self::QTY_EPS;
    }

    private function packsFromMoq(OrderDetail $detail, float $qty): int
    {
        $moq = (float) ($detail->product->minimum_stock ?? $detail->product->minimum_quantity ?? 0);
        if ($moq <= self::QTY_EPS) {
            $moq = 1.0;
        }
        $raw = $qty / $moq;
        $nearest = round($raw);
        $packs = abs($raw - $nearest) <= 0.0001 ? (int) $nearest : (int) ceil($raw);

        return max(1, $packs);
    }

    /**
     * @param  list<array{order_number: string, qty: float, price: float}>  $returns
     */
    private function noteFromReturns(array $returns): string
    {
        $parts = [];
        foreach ($returns as $row) {
            $qty = (float) $row['qty'];
            $qtyText = abs($qty - round($qty)) <= self::QTY_EPS
                ? (string) (int) round($qty)
                : rtrim(rtrim(number_format($qty, 4, '.', ''), '0'), '.');
            $parts[] = $row['order_number'].' · '.$qtyText.' · '.number_format((float) $row['price'], 2, '.', '');
        }

        return implode(' | ', $parts);
    }

    /**
     * @param  list<array{order_number: string, qty: float, price: float}>  $returns
     */
    private function commentHasReturns(OrderDetail $detail, array $returns): bool
    {
        $text = $this->visibleComment($detail);
        if ($text === '') {
            return false;
        }
        foreach ($returns as $row) {
            if (! str_contains($text, (string) $row['order_number'])) {
                return false;
            }
        }

        return true;
    }

    private function visibleComment(OrderDetail $detail): string
    {
        $comments = trim((string) ($detail->comments ?? ''));
        $comment = trim((string) ($detail->comment ?? ''));
        if ($comments === '1') {
            $text = ($comment !== '' && $comment !== '-' && $comment !== '—' && $comment !== '1') ? $comment : '';
        } else {
            $text = $comments !== ''
                ? $comments
                : (($comment !== '' && $comment !== '-' && $comment !== '—') ? $comment : '');
        }

        $text = trim($text);
        if ($text === '-' || $text === '—') {
            return '';
        }

        return $text;
    }
}

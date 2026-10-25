<?php

namespace App\Services;

use App\Enums\OrderStatusCodes;
use App\Exceptions\FifoCreditException;
use App\Models\CustomerOrderProductFifo;
use App\Models\CustomerOrderProductFifoAllocation;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\StatusOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Control FIFO de vendido vs nota de crédito por cliente/producto/orden origen.
 * - Ventas (tipo_d=D): abren/actualizan capas solo cuando la orden está completada.
 * - NC (tipo_d=C): consumen capas ASC por opened_at; credit_price <= sale_price.
 */
class CustomerOrderProductFifoService
{
    private const QTY_EPS = 0.00005;

    private const PRICE_EPS = 0.00005;

    /** Estados de venta que habilitan capas FIFO (producto ya “vendido/completado”). */
    public const SALE_FIFO_STATUS_CODES = [
        OrderStatusCodes::WAREHOUSE_COMPLETED->value,
        OrderStatusCodes::COMPLETED->value,
    ];

    public function tablesReady(): bool
    {
        return Schema::hasTable('customer_order_product_fifo')
            && Schema::hasTable('customer_order_product_fifo_allocations');
    }

    /**
     * Sincroniza FIFO a partir de una orden (venta o NC).
     *
     * @param  bool  $strict  En NC: si false, no falla por qty insuficiente ni precio > venta (warehouse).
     */
    public function syncOrder(Order $order, bool $strict = true): void
    {
        if (! $this->tablesReady()) {
            return;
        }

        $order->loadMissing(['order_details.product']);
        $tipo = strtoupper((string) ($order->tipo_d ?? 'D'));

        if ($tipo === 'D') {
            if ($this->isSaleEligibleForFifo($order)) {
                $this->syncSaleOrder($order);
            } else {
                // Aún no completada (o cancelada): no debe figurar como disponible para NC.
                $this->withdrawUncreditedSaleLayers($order);
            }

            return;
        }

        if ($tipo === 'C') {
            $this->syncCreditOrder($order, $strict);
        }
    }

    /**
     * Una venta solo entra al FIFO cuando está completada (almacén o final).
     */
    public function isSaleEligibleForFifo(Order $order): bool
    {
        $order->loadMissing('statusOrder');
        $cod = $order->statusOrder?->cod_status;

        if ($cod === null && $order->status_id) {
            $cod = StatusOrder::query()
                ->where('id_status', (int) $order->status_id)
                ->value('cod_status');
        }

        return in_array((int) $cod, self::SALE_FIFO_STATUS_CODES, true);
    }

    /**
     * Capas FIFO detalladas (por orden) de toda la compañía.
     *
     * @return array{status: string, company_id?: int, items?: list<array<string, mixed>>, error?: string}
     */
    public function listForCompany(int $companyId, bool $onlyAvailable = true): array
    {
        if ($companyId < 1) {
            return ['status' => '0', 'error' => 'invalid_params'];
        }

        if (! $this->tablesReady()) {
            return ['status' => '0', 'error' => 'fifo_tables_missing'];
        }

        $query = CustomerOrderProductFifo::query()
            ->with([
                'product.unit',
                'sourceOrder:order_id,order_number,customer_id,created,updated',
            ])
            ->where('company_id', $companyId)
            ->where('status', '!=', CustomerOrderProductFifo::STATUS_CANCELLED)
            ->orderBy('customer_id')
            ->orderBy('opened_at')
            ->orderBy('id');

        if ($onlyAvailable) {
            $query->where('qty_remaining', '>', self::QTY_EPS);
        }

        $rows = $query->get();
        if ($rows->isEmpty()) {
            return ['status' => '0', 'error' => 'no_fifo_found'];
        }

        $items = $rows->map(static function (CustomerOrderProductFifo $row): array {
            $product = $row->product;
            $unit = $product?->unit;
            $order = $row->sourceOrder;
            $orderCreated = $order?->created ?? $row->opened_at;
            $orderUpdated = $order?->updated;

            return [
                'company_id' => (int) $row->company_id,
                'customer_id' => (int) $row->customer_id,
                'product_id' => (int) $row->product_id,
                'sku' => (string) ($row->sku ?: ($product?->sku ?? '')),
                'product_name' => (string) ($product?->name ?? ''),
                'unit_id' => (int) ($product?->unit_id ?? $unit?->unit_id ?? 0) ?: null,
                'unit' => (string) ($unit?->unit ?? ''),
                'unit_description' => (string) ($unit?->description ?? ''),
                'order_id' => (int) $row->source_order_id,
                'order_number' => (string) ($row->source_order_number ?: ($order?->order_number ?? '')),
                'order_date' => $orderCreated
                    ? (is_string($orderCreated) ? $orderCreated : $orderCreated->format('Y-m-d H:i:s'))
                    : '',
                'order_created' => $orderCreated
                    ? (is_string($orderCreated) ? $orderCreated : $orderCreated->format('Y-m-d H:i:s'))
                    : '',
                'order_updated' => $orderUpdated
                    ? (is_string($orderUpdated) ? $orderUpdated : $orderUpdated->format('Y-m-d H:i:s'))
                    : '',
                'sales_price' => round((float) $row->sale_price, 4),
                'product_sales_price' => round((float) ($product?->sale_price ?? 0), 4),
                'qty_sales' => round((float) $row->qty_sold, 4),
                'qty_creditnote' => round((float) $row->qty_credited, 4),
                'qty_total' => round((float) $row->qty_remaining, 4),
                'status' => (string) $row->status,
                'opened_at' => optional($row->opened_at)?->format('Y-m-d H:i:s') ?? '',
            ];
        })->values()->all();

        return [
            'status' => '1',
            'company_id' => $companyId,
            'items' => $items,
        ];
    }

    /**
     * Resumen agregado por customer_id + sku (sin órdenes ni fechas).
     *
     * @return array{status: string, company_id?: int, items?: list<array<string, mixed>>, error?: string}
     */
    public function summaryByCustomerSku(int $companyId, bool $onlyAvailable = true): array
    {
        if ($companyId < 1) {
            return ['status' => '0', 'error' => 'invalid_params'];
        }

        if (! $this->tablesReady()) {
            return ['status' => '0', 'error' => 'fifo_tables_missing'];
        }

        $query = CustomerOrderProductFifo::query()
            ->with(['product.unit'])
            ->where('company_id', $companyId)
            ->where('status', '!=', CustomerOrderProductFifo::STATUS_CANCELLED);

        if ($onlyAvailable) {
            $query->where('qty_remaining', '>', self::QTY_EPS);
        }

        $rows = $query
            ->orderBy('customer_id')
            ->orderBy('sku')
            ->get();

        if ($rows->isEmpty()) {
            return ['status' => '0', 'error' => 'no_fifo_found'];
        }

        /** @var array<string, array<string, mixed>> $grouped */
        $grouped = [];
        foreach ($rows as $row) {
            /** @var CustomerOrderProductFifo $row */
            $product = $row->product;
            $unit = $product?->unit;
            $sku = (string) ($row->sku ?: ($product?->sku ?? ''));
            $customerId = (int) $row->customer_id;
            $key = $customerId.'|'.$sku;

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'customer_id' => $customerId,
                    'sku' => $sku,
                    'product_name' => (string) ($product?->name ?? ''),
                    'unit' => (string) ($unit?->unit ?? ''),
                    'qty_sales' => 0.0,
                    'qty_credit_notes' => 0.0,
                    'qty_total' => 0.0,
                ];
            }

            $grouped[$key]['qty_sales'] += (float) $row->qty_sold;
            $grouped[$key]['qty_credit_notes'] += (float) $row->qty_credited;
            $grouped[$key]['qty_total'] += (float) $row->qty_remaining;
            if ($grouped[$key]['product_name'] === '' && $product?->name) {
                $grouped[$key]['product_name'] = (string) $product->name;
            }
            if ($grouped[$key]['unit'] === '' && $unit?->unit) {
                $grouped[$key]['unit'] = (string) $unit->unit;
            }
        }

        $items = array_values(array_map(static function (array $row): array {
            return [
                'customer_id' => (int) $row['customer_id'],
                'sku' => (string) $row['sku'],
                'product_name' => (string) $row['product_name'],
                'unit' => (string) $row['unit'],
                'qty_sales' => round((float) $row['qty_sales'], 4),
                'qty_credit_notes' => round((float) $row['qty_credit_notes'], 4),
                'qty_total' => round((float) $row['qty_total'], 4),
            ];
        }, $grouped));

        if ($onlyAvailable) {
            $items = array_values(array_filter(
                $items,
                static fn (array $row): bool => (float) $row['qty_total'] > self::QTY_EPS
            ));
        }

        if ($items === []) {
            return ['status' => '0', 'error' => 'no_fifo_found'];
        }

        return [
            'status' => '1',
            'company_id' => $companyId,
            'items' => $items,
        ];
    }

    /**
     * Reconstruye capas de venta (D) y luego reaplica NC (C) de la compañía/cliente.
     */
    public function rebuild(?int $companyId = null, ?int $customerId = null): array
    {
        if (! $this->tablesReady()) {
            return ['layers' => 0, 'credits' => 0, 'error' => 'fifo_tables_missing'];
        }

        return DB::transaction(function () use ($companyId, $customerId) {
            $allocQ = CustomerOrderProductFifoAllocation::query();
            $fifoQ = CustomerOrderProductFifo::query();
            if ($companyId) {
                $allocQ->where('company_id', $companyId);
                $fifoQ->where('company_id', $companyId);
            }
            if ($customerId) {
                $allocQ->where('customer_id', $customerId);
                $fifoQ->where('customer_id', $customerId);
            }
            $allocQ->delete();
            $fifoQ->delete();

            $salesQ = Order::query()
                ->where('tipo_d', 'D')
                ->whereNull('deleted_at')
                ->whereHas('statusOrder', function ($q) {
                    $q->whereIn('cod_status', self::SALE_FIFO_STATUS_CODES);
                })
                ->with(['order_details.product', 'statusOrder'])
                ->orderBy('created')
                ->orderBy('order_id');
            if ($companyId) {
                $salesQ->where('company_id', $companyId);
            }
            if ($customerId) {
                $salesQ->where('customer_id', $customerId);
            }

            $layers = 0;
            foreach ($salesQ->cursor() as $order) {
                $this->syncSaleOrder($order);
                $layers++;
            }

            $creditsQ = Order::query()
                ->where('tipo_d', 'C')
                ->whereNull('deleted_at')
                ->with(['order_details.product'])
                ->orderBy('created')
                ->orderBy('order_id');
            if ($companyId) {
                $creditsQ->where('company_id', $companyId);
            }
            if ($customerId) {
                $creditsQ->where('customer_id', $customerId);
            }

            $credits = 0;
            foreach ($creditsQ->cursor() as $order) {
                try {
                    $this->syncCreditOrder($order, false);
                    $credits++;
                } catch (FifoCreditException $e) {
                    // Históricos sin stock FIFO suficiente: se omiten sin abortar rebuild.
                    continue;
                }
            }

            return ['layers' => $layers, 'credits' => $credits];
        });
    }

    /**
     * Quita capas sin consumo de NC (orden aún no completada / cancelada).
     * Si ya hubo NC contra la capa, se deja histórico exhausted.
     */
    private function withdrawUncreditedSaleLayers(Order $order): void
    {
        $orderId = (int) ($order->order_id ?? 0);
        if ($orderId < 1) {
            return;
        }

        foreach (CustomerOrderProductFifo::query()->where('source_order_id', $orderId)->get() as $layer) {
            if ((float) $layer->qty_credited > self::QTY_EPS) {
                $layer->qty_sold = (float) $layer->qty_credited;
                $layer->qty_remaining = 0;
                $layer->status = CustomerOrderProductFifo::STATUS_EXHAUSTED;
                $layer->save();
            } else {
                $layer->delete();
            }
        }
    }

    private function syncSaleOrder(Order $order): void
    {
        $companyId = (int) ($order->company_id ?? 0);
        $customerId = (int) ($order->customer_id ?? 0);
        if ($companyId < 1 || $customerId < 1) {
            return;
        }

        $order->loadMissing(['order_details.product']);
        $details = $order->order_details ?? collect();
        $aliveDetailIds = [];

        foreach ($details as $detail) {
            /** @var OrderDetail $detail */
            $detailId = (int) $detail->detail_id;
            if ($detailId < 1) {
                continue;
            }
            $aliveDetailIds[] = $detailId;

            $qtySold = abs((float) $detail->qty);
            $salePrice = round((float) $detail->sale_price, 4);
            $productId = (int) $detail->product_id;
            $sku = (string) ($detail->product?->sku ?? Product::query()->where('pro_id', $productId)->value('sku') ?? '');
            $openedAt = $order->created ?? $detail->created ?? now();

            /** @var CustomerOrderProductFifo|null $layer */
            $layer = CustomerOrderProductFifo::query()
                ->where('source_detail_id', $detailId)
                ->first();

            if ($layer) {
                if ($qtySold + self::QTY_EPS < (float) $layer->qty_credited) {
                    throw new FifoCreditException(
                        'Cannot reduce sold qty below already credited qty.',
                        'fifo_qty_below_credited',
                        [
                            'source_detail_id' => $detailId,
                            'qty_sold' => $qtySold,
                            'qty_credited' => (float) $layer->qty_credited,
                        ]
                    );
                }
                $layer->fill([
                    'company_id' => $companyId,
                    'customer_id' => $customerId,
                    'product_id' => $productId,
                    'sku' => $sku,
                    'source_order_id' => (int) $order->order_id,
                    'source_order_number' => (string) ($order->order_number ?? ''),
                    'sale_price' => $salePrice,
                    'qty_sold' => $qtySold,
                    'qty_remaining' => max(0, $qtySold - (float) $layer->qty_credited),
                    'opened_at' => $openedAt,
                ]);
                $layer->refreshStatus();
                $layer->save();
            } else {
                $layer = new CustomerOrderProductFifo([
                    'company_id' => $companyId,
                    'customer_id' => $customerId,
                    'product_id' => $productId,
                    'sku' => $sku,
                    'source_order_id' => (int) $order->order_id,
                    'source_detail_id' => $detailId,
                    'source_order_number' => (string) ($order->order_number ?? ''),
                    'sale_price' => $salePrice,
                    'qty_sold' => $qtySold,
                    'qty_credited' => 0,
                    'qty_remaining' => $qtySold,
                    'opened_at' => $openedAt,
                    'status' => $qtySold > self::QTY_EPS
                        ? CustomerOrderProductFifo::STATUS_OPEN
                        : CustomerOrderProductFifo::STATUS_EXHAUSTED,
                ]);
                $layer->save();
            }
        }

        // Capas de detalles eliminados de esta orden.
        $orphanQ = CustomerOrderProductFifo::query()
            ->where('source_order_id', (int) $order->order_id);
        if ($aliveDetailIds !== []) {
            $orphanQ->whereNotIn('source_detail_id', $aliveDetailIds);
        }
        foreach ($orphanQ->get() as $orphan) {
            if ((float) $orphan->qty_credited > self::QTY_EPS) {
                // Conservar histórico: qty_sold = qty_credited, remaining 0.
                $orphan->qty_sold = (float) $orphan->qty_credited;
                $orphan->qty_remaining = 0;
                $orphan->status = CustomerOrderProductFifo::STATUS_EXHAUSTED;
                $orphan->save();
            } else {
                $orphan->delete();
            }
        }
    }

    /**
     * @param  bool  $strict  Si false, omite líneas sin stock (rebuild histórico).
     */
    private function syncCreditOrder(Order $order, bool $strict = true): void
    {
        $companyId = (int) ($order->company_id ?? 0);
        $customerId = (int) ($order->customer_id ?? 0);
        if ($companyId < 1 || $customerId < 1) {
            return;
        }

        $order->loadMissing(['order_details']);
        $this->releaseCreditOrder((int) $order->order_id);

        foreach ($order->order_details ?? [] as $detail) {
            /** @var OrderDetail $detail */
            $qtyNeeded = abs((float) $detail->qty);
            if ($qtyNeeded <= self::QTY_EPS) {
                continue;
            }

            $creditPrice = round((float) $detail->sale_price, 4);
            $productId = (int) $detail->product_id;
            $remainingToAllocate = $qtyNeeded;

            $layers = CustomerOrderProductFifo::query()
                ->where('company_id', $companyId)
                ->where('customer_id', $customerId)
                ->where('product_id', $productId)
                ->where('status', '!=', CustomerOrderProductFifo::STATUS_CANCELLED)
                ->where('qty_remaining', '>', self::QTY_EPS)
                ->orderBy('opened_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($layers as $layer) {
                if ($remainingToAllocate <= self::QTY_EPS) {
                    break;
                }

                if ($creditPrice > ((float) $layer->sale_price) + self::PRICE_EPS) {
                    if ($strict) {
                        throw new FifoCreditException(
                            'Credit note price cannot exceed sale price of source order layer.',
                            'fifo_price_exceeds_sale',
                            [
                                'product_id' => $productId,
                                'credit_price' => $creditPrice,
                                'sale_price' => (float) $layer->sale_price,
                                'source_order_id' => (int) $layer->source_order_id,
                                'source_order_number' => (string) $layer->source_order_number,
                            ]
                        );
                    }
                    // Modo permisivo (warehouse): saltar esta capa y seguir.
                    continue;
                }

                $take = min($remainingToAllocate, (float) $layer->qty_remaining);
                if ($take <= self::QTY_EPS) {
                    continue;
                }

                CustomerOrderProductFifoAllocation::query()->create([
                    'fifo_id' => (int) $layer->id,
                    'company_id' => $companyId,
                    'customer_id' => $customerId,
                    'product_id' => $productId,
                    'credit_order_id' => (int) $order->order_id,
                    'credit_detail_id' => (int) $detail->detail_id,
                    'credit_order_number' => (string) ($order->order_number ?? ''),
                    'qty' => round($take, 4),
                    'credit_price' => $creditPrice,
                ]);

                $layer->qty_credited = round((float) $layer->qty_credited + $take, 4);
                $layer->qty_remaining = max(0, round((float) $layer->qty_sold - (float) $layer->qty_credited, 4));
                $layer->refreshStatus();
                $layer->save();

                $remainingToAllocate = round($remainingToAllocate - $take, 4);
            }

            if ($remainingToAllocate > self::QTY_EPS) {
                if ($strict) {
                    throw new FifoCreditException(
                        'Insufficient sold quantity available for credit note (FIFO).',
                        'fifo_insufficient_qty',
                        [
                            'product_id' => $productId,
                            'qty_needed' => $qtyNeeded,
                            'qty_short' => $remainingToAllocate,
                        ]
                    );
                }
            }
        }
    }

    private function releaseCreditOrder(int $creditOrderId): void
    {
        if ($creditOrderId < 1) {
            return;
        }

        $allocations = CustomerOrderProductFifoAllocation::query()
            ->where('credit_order_id', $creditOrderId)
            ->lockForUpdate()
            ->get();

        foreach ($allocations as $alloc) {
            /** @var CustomerOrderProductFifo|null $layer */
            $layer = CustomerOrderProductFifo::query()
                ->where('id', (int) $alloc->fifo_id)
                ->lockForUpdate()
                ->first();
            if ($layer) {
                $layer->qty_credited = max(0, round((float) $layer->qty_credited - (float) $alloc->qty, 4));
                $layer->qty_remaining = max(0, round((float) $layer->qty_sold - (float) $layer->qty_credited, 4));
                if ($layer->status !== CustomerOrderProductFifo::STATUS_CANCELLED) {
                    $layer->refreshStatus();
                }
                $layer->save();
            }
            $alloc->delete();
        }
    }
}

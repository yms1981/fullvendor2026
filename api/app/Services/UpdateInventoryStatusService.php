<?php

namespace App\Services;

use App\Models\DeliveredOrder;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderStatusHistory;
use App\Models\StatusOrder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Equivalente moderno de updateInventoryStatus_post (API legacy):
 * - Actualiza orders.delivery_status y status (cod_status → status_id)
 * - Si delivery_status == 0, fuerza order_status = 0
 * - Por cada ítem de productlist: actualiza order_details (qty/pack/delivered_*)
 *   y reemplaza el registro en delivered_orders
 */
class UpdateInventoryStatusService
{
    /**
     * @param  array{
     *     order_id:int|string,
     *     order_status:int|string,
     *     delivery_status:int|string,
     *     productlist?:list<array{product_id?:int|string,delivered_quantity?:mixed,delivered_pack?:mixed}>
     * }  $input
     * @return array{
     *     order_id:int,
     *     order_status:int,
     *     delivery_status:int,
     *     status_id:int|null,
     *     items_updated:int
     * }
     */
    public function handle(array $input, int $companyId, ?int $userId = null): array
    {
        $orderId = (int) ($input['order_id'] ?? 0);
        $deliveryStatus = $input['delivery_status'] ?? null;
        $orderStatusRaw = $input['order_status'] ?? null;

        if ($orderId < 1 || $deliveryStatus === null || $deliveryStatus === '' || $orderStatusRaw === null || $orderStatusRaw === '') {
            throw new InvalidArgumentException('missing_fields');
        }

        $deliveryStatus = (int) $deliveryStatus;
        // Legado: si delivery_status == 0 → order_status = 0
        $codStatus = $deliveryStatus === 0 ? 0 : (int) $orderStatusRaw;

        $order = Order::query()
            ->where('order_id', $orderId)
            ->where('company_id', $companyId)
            ->first();

        if (! $order) {
            throw new RuntimeException('order_not_found');
        }

        $status = StatusOrder::query()
            ->where('cod_status', $codStatus)
            ->first();

        if (! $status) {
            throw new RuntimeException('status_not_found');
        }

        $productList = $input['productlist'] ?? $input['product_list'] ?? [];
        if (! is_array($productList)) {
            $productList = [];
        }

        $itemsUpdated = 0;

        DB::transaction(function () use (
            $order,
            $companyId,
            $userId,
            $codStatus,
            $deliveryStatus,
            $status,
            $productList,
            &$itemsUpdated
        ) {
            $order->delivery_status = $deliveryStatus;
            $order->status_id = (int) $status->id_status;
            $order->save();

            OrderStatusHistory::query()->create([
                'order_id' => $order->order_id,
                'company_id' => $companyId,
                'user_id' => $userId,
                'cod_status' => $codStatus,
            ]);

            foreach ($productList as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $productId = (int) ($row['product_id'] ?? $row['pro_id'] ?? 0);
                if ($productId < 1) {
                    continue;
                }

                $deliveredQty = $row['delivered_quantity'] ?? $row['delivered_qty'] ?? null;
                if ($deliveredQty === null || $deliveredQty === '') {
                    continue;
                }

                $detail = OrderDetail::query()
                    ->where('order_id', $order->order_id)
                    ->where('product_id', $productId)
                    ->first();

                if (! $detail) {
                    throw new RuntimeException('order_detail_not_found');
                }

                $qty = (float) $deliveredQty;
                $packRaw = $row['delivered_pack'] ?? $row['pack'] ?? null;
                $pack = ($packRaw === null || $packRaw === '') ? null : (float) $packRaw;

                $detail->qty = $qty;
                if ($pack !== null) {
                    $detail->pack = $pack;
                }
                $detail->delivered_quantity = $qty;
                $detail->delivered_pack = $pack;
                $detail->delivered_date = now();
                $detail->save();

                DeliveredOrder::query()
                    ->where('order_id', $order->order_id)
                    ->where('product_id', $productId)
                    ->forceDelete();

                DeliveredOrder::query()->create([
                    'order_id' => $order->order_id,
                    'product_id' => $productId,
                    'delivered_quantity' => $qty,
                    'delivered_pack' => $pack,
                    'delivered_date' => now(),
                ]);

                $itemsUpdated++;
            }
        });

        return [
            'order_id' => $orderId,
            'order_status' => $codStatus,
            'delivery_status' => $deliveryStatus,
            'status_id' => (int) $status->id_status,
            'items_updated' => $itemsUpdated,
        ];
    }
}

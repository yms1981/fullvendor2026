<?php

namespace App\Services;

use App\Models\DeliveredOrder;
use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Equivalente moderno de updateDeliveredItem_post (API legacy):
 * - Actualiza order_details.delivered_quantity / delivered_pack
 * - Reemplaza el registro en delivered_orders (antes order_dispached)
 */
class UpdateDeliveredItemService
{
    /**
     * @param  array{order_id:int,product_id:int,delivered_qty:float|int|string,delivered_pack?:float|int|string|null}  $input
     * @return array{
     *     order_id:int,
     *     product_id:int,
     *     delivered_quantity:float,
     *     delivered_pack:float|null,
     *     detail_id:int,
     *     delivered_id:int
     * }
     */
    public function handle(array $input, int $companyId): array
    {
        $orderId = (int) ($input['order_id'] ?? 0);
        $productId = (int) ($input['product_id'] ?? 0);
        $deliveredQty = $input['delivered_qty'] ?? $input['delivered_quantity'] ?? null;
        $deliveredPack = $input['delivered_pack'] ?? null;

        if ($orderId < 1 || $productId < 1 || $deliveredQty === null || $deliveredQty === '') {
            throw new InvalidArgumentException('missing_fields');
        }

        $order = Order::query()
            ->where('order_id', $orderId)
            ->where('company_id', $companyId)
            ->first();

        if (! $order) {
            throw new RuntimeException('order_not_found');
        }

        $detail = OrderDetail::query()
            ->where('order_id', $orderId)
            ->where('product_id', $productId)
            ->first();

        if (! $detail) {
            throw new RuntimeException('order_detail_not_found');
        }

        $qty = (float) $deliveredQty;
        $pack = $deliveredPack === null || $deliveredPack === ''
            ? null
            : (float) $deliveredPack;

        return DB::transaction(function () use ($detail, $orderId, $productId, $qty, $pack) {
            $detail->delivered_quantity = $qty;
            $detail->delivered_pack = $pack;
            $detail->delivered_date = now();
            $detail->save();

            // Soft-delete previos (tabla actual: delivered_orders; legacy: order_dispached).
            DeliveredOrder::query()
                ->where('order_id', $orderId)
                ->where('product_id', $productId)
                ->forceDelete();

            $delivered = DeliveredOrder::query()->create([
                'order_id' => $orderId,
                'product_id' => $productId,
                'delivered_quantity' => $qty,
                'delivered_pack' => $pack,
                'delivered_date' => now(),
            ]);

            return [
                'order_id' => $orderId,
                'product_id' => $productId,
                'delivered_quantity' => $qty,
                'delivered_pack' => $pack,
                'detail_id' => (int) $detail->detail_id,
                'delivered_id' => (int) $delivered->delivered_id,
            ];
        });
    }
}

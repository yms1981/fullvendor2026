<?php

namespace App\Services;

use App\Enums\OrderStatusCodes;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\StatusOrder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Equivalente moderno de updateInventoryStock_post (API legacy):
 * - Actualiza order_details.qty del producto en la orden
 * - Fija el estado de la orden a cod_status 16 (IN_PROCESS)
 * - warehouse_user_id se valida por contrato (compatibilidad) pero no altera stock de products
 */
class UpdateInventoryStockService
{
    /**
     * @param  array{
     *     order_id:int|string,
     *     product_id:int|string,
     *     warehouse_user_id:int|string,
     *     stock_quantity:float|int|string
     * }  $input
     * @return array{
     *     order_id:int,
     *     product_id:int,
     *     stock_quantity:float,
     *     stock:float|null,
     *     status_id:int,
     *     cod_status:int,
     *     detail_id:int
     * }
     */
    public function handle(array $input, int $companyId, ?int $userId = null): array
    {
        $orderId = (int) ($input['order_id'] ?? 0);
        $productId = (int) ($input['product_id'] ?? $input['pro_id'] ?? 0);
        $warehouseUserId = (int) ($input['warehouse_user_id'] ?? 0);
        $stockQuantity = $input['stock_quantity'] ?? null;

        if ($orderId < 1 || $productId < 1 || $warehouseUserId < 1 || $stockQuantity === null || $stockQuantity === '') {
            throw new InvalidArgumentException('missing_fields');
        }

        $order = Order::query()
            ->where('order_id', $orderId)
            ->where('company_id', $companyId)
            ->first();

        if (! $order) {
            throw new RuntimeException('order_not_found');
        }

        $product = Product::query()
            ->where('company_id', $companyId)
            ->where(function ($q) use ($productId) {
                $q->where('pro_id', $productId)
                    ->orWhere('product_id', $productId);
            })
            ->first();

        if (! $product) {
            throw new RuntimeException('product_not_found');
        }

        $resolvedProductId = (int) $product->pro_id;

        $detail = OrderDetail::query()
            ->where('order_id', $orderId)
            ->where('product_id', $resolvedProductId)
            ->first();

        // Compat: algunas líneas guardan product_id legacy distinto de pro_id.
        if (! $detail && $productId !== $resolvedProductId) {
            $detail = OrderDetail::query()
                ->where('order_id', $orderId)
                ->where('product_id', $productId)
                ->first();
        }

        if (! $detail) {
            throw new RuntimeException('order_detail_not_found');
        }

        $codStatus = OrderStatusCodes::IN_PROCESS->value;
        $status = StatusOrder::query()
            ->where('cod_status', $codStatus)
            ->first();

        if (! $status) {
            throw new RuntimeException('status_not_found');
        }

        $qty = (float) $stockQuantity;
        $statusId = (int) $status->id_status;

        DB::transaction(function () use ($detail, $order, $qty, $statusId, $codStatus, $companyId, $userId) {
            $detail->qty = $qty;
            $detail->save();

            $order->status_id = $statusId;
            $order->save();

            OrderStatusHistory::query()->create([
                'order_id' => $order->order_id,
                'company_id' => $companyId,
                'user_id' => $userId,
                'cod_status' => $codStatus,
            ]);
        });

        $product->refresh();

        return [
            'order_id' => $orderId,
            'product_id' => $resolvedProductId,
            'stock_quantity' => $qty,
            'stock' => isset($product->stock) ? (float) $product->stock : null,
            'status_id' => $statusId,
            'cod_status' => $codStatus,
            'detail_id' => (int) $detail->detail_id,
        ];
    }
}

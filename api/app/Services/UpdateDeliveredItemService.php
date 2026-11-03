<?php

namespace App\Services;

use App\Models\DeliveredOrder;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
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

        // Buscar solo por order_id (evitar falsos "order_not_found" por company_id).
        $order = Order::query()->where('order_id', $orderId)->first();

        if (! $order) {
            throw new RuntimeException('order_not_found');
        }

        $orderCompanyId = (int) ($order->company_id ?? 0);
        if ($companyId > 0 && $orderCompanyId > 0 && $orderCompanyId !== (int) $companyId) {
            throw new RuntimeException('order_company_mismatch');
        }

        // Incluir soft-deleted: si existía y se borró, restaurar en vez de insertar.
        $detail = OrderDetail::withTrashed()
            ->where('order_id', $orderId)
            ->where('product_id', $productId)
            ->first();

        $qty = (float) $deliveredQty;
        $pack = $deliveredPack === null || $deliveredPack === ''
            ? null
            : (float) $deliveredPack;

        return DB::transaction(function () use (
            $detail,
            $order,
            $orderId,
            $productId,
            $companyId,
            $qty,
            $pack
        ) {
            $created = false;
            // Warehouse app "Add Product": crear / restaurar línea si no existe.
            if (! $detail || $detail->trashed()) {
                $productCompanyId = (int) ($order->company_id ?: $companyId);
                $product = Product::query()
                    ->where('pro_id', $productId)
                    ->where('company_id', $productCompanyId)
                    ->where('status', 1)
                    ->first();
                // Fallback: mismo pro_id en la company del token.
                if (! $product && $productCompanyId !== $companyId) {
                    $product = Product::query()
                        ->where('pro_id', $productId)
                        ->where('company_id', $companyId)
                        ->where('status', 1)
                        ->first();
                }
                if (! $product) {
                    throw new RuntimeException('product_not_found_or_inactive');
                }
                $salePrice = (float) ($product->sale_price ?? 0);
                $orderedQty = $qty > 0 ? $qty : 1.0;
                $orderedPack = ($pack !== null && $pack > 0) ? $pack : 1.0;

                if ($detail && $detail->trashed()) {
                    $detail->restore();
                    $detail->qty = $orderedQty;
                    $detail->pack = $orderedPack;
                    $detail->sale_price = $salePrice;
                    $detail->sale_price_app = $salePrice;
                    $detail->amount = $salePrice * $orderedQty;
                    $detail->total = $salePrice * $orderedQty;
                    $detail->delivered_quantity = $qty;
                    $detail->delivered_pack = $pack;
                    $detail->delivered_date = now();
                    $detail->company_id = $productCompanyId;
                    $detail->discount = $detail->discount ?? 0;
                    $detail->discount_type = $detail->discount_type ?? 0;
                    $detail->id_kor = $detail->id_kor ?: 1;
                    $detail->comment = $detail->comment ?? '';
                    $detail->comments = $detail->comments ?? '';
                    $detail->save();
                    $created = true;
                } else {
                    $created = true;
                    // Campos NOT NULL sin default en order_details: comment, comments, id_kor, discount, delivered_quantity
                    $detail = OrderDetail::query()->create([
                        'order_id' => $orderId,
                        'product_id' => $productId,
                        'company_id' => $productCompanyId,
                        'qty' => $orderedQty,
                        'pack' => $orderedPack,
                        'sale_price' => $salePrice,
                        'sale_price_app' => $salePrice,
                        'fob_price' => 0,
                        'purchase_price' => 0,
                        'discount' => 0,
                        'discount_type' => 0,
                        'amount' => $salePrice * $orderedQty,
                        'total' => $salePrice * $orderedQty,
                        'amount_sales' => $salePrice * $orderedQty,
                        'total_amount' => $salePrice * $orderedQty,
                        'discount_amount' => 0,
                        'delivered_quantity' => $qty,
                        'delivered_pack' => $pack,
                        'delivered_date' => now(),
                        'created' => now(),
                        'comment' => '',
                        'comments' => '',
                        'id_kor' => 1,
                    ]);
                }
                // Recalcular totales solo con columnas reales de `orders`.
                $lineTotal = $salePrice * $orderedQty;
                $order->amount = (float) ($order->amount ?? 0) + $lineTotal;
                if (\Illuminate\Support\Facades\Schema::hasColumn('orders', 'total_amount')) {
                    $order->total_amount = (float) ($order->total_amount ?? 0) + $lineTotal;
                }
                if (\Illuminate\Support\Facades\Schema::hasColumn('orders', 'total')) {
                    $order->total = (float) ($order->total ?? 0) + $lineTotal;
                }
                if (\Illuminate\Support\Facades\Schema::hasColumn('orders', 'subtotal')) {
                    $order->subtotal = (float) ($order->subtotal ?? 0) + $lineTotal;
                }
                $order->save();
            } else {
                $detail->delivered_quantity = $qty;
                $detail->delivered_pack = $pack;
                $detail->delivered_date = now();
                $detail->save();
            }

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
                'created' => $created,
            ];
        });
    }
}

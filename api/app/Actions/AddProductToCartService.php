<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Validation\ValidationException;

class AddProductToCartService
{
    /**
     * @throws ValidationException
     */
    public function execute($validatedRequest): array
    {
        ['order_id' => $orderId, 'product_id' => $productId, 'quantity' => $quantity] = $validatedRequest;
        $comments = $validatedRequest['comments'] ?? ''; //permitir que sea null?

        $order = Order::findOrFail($orderId);

        $product = Product::findOrFail($productId);

        $this->validateMinimumQuantity($product, $quantity);

        $this->validateStock($product, $quantity);

        $orderDetail = $order->order_details()->withTrashed()->where('product_id', $productId)->first();

        if ($orderDetail) {
            if ($orderDetail->trashed()) {
                $orderDetail->restore();
                $orderDetail->qty = 0;
            }
            $orderDetail->qty += $quantity;
            $orderDetail->comments = $comments;
            $orderDetail->save();
        } else {
            $orderDetail = $order->order_details()->create([
                'product_id' => $productId,
                'qty' => $quantity,
                'company_id' => $order->company_id,
                'discount' => $product->discount ?? 0,
                'sale_price' => $product->getPriceForCustomer($order->customer),
                'comments' => $comments ?? '', //permitir que sea null?

                //campos que se necesitan completar si o si para crear un nuevo detalle de orden
                'delivered_quantity' => 0,
                'comment' => '',
                'id_kor' => 0,

            ]);
        }

        $order->load('order_details');
        $totalsArray = $order->getTotals(true); //order total details formated
        $totalsArray['product_qty'] = $orderDetail->qty;

        return $totalsArray;
    }

    /**
     * @throws ValidationException
     */
    protected function validateMinimumQuantity(Product $product, int $quantity): void
    {
        if (!$product->validMinimumQuantity($quantity)){
            throw ValidationException::withMessages([
                'quantity' => "La cantidad mínima de compra es {$product->minimum_stock}"
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    protected function validateStock(Product $product, int $quantity): void
    {
        if (!$product->hasStock($quantity)) {
            throw ValidationException::withMessages([
                'quantity' => "Stock insuficiente para este producto"
            ]);
        }
    }
}

<?php

namespace App\Services;

use App\Enums\OrderNoteTypes;
use App\Enums\OrderStatusCodes;
use App\Exceptions\FifoCreditException;
use App\Http\Controllers\Orders\CreatesOrders;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Language;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\StatusOrder;
use App\Models\User;
use App\Support\OrderAuthTokenProvenance;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/**
 * Crea una nota de crédito (tipo_d = C) desde warehouse.
 * warehouse_user_id del body → orders.warehouse_user_id.
 * El usuario del token queda en user_id_token / token_create / rol_token.
 */
class CreateWarehouseCreditNoteService
{
    use CreatesOrders;

    /**
     * @param  array<string, mixed>  $validated
     * @param  int|null  $tokenUserId  users.id del Bearer (procedencia), no el almacenero
     */
    public function handle(array $validated, int $companyId, ?int $tokenUserId = null): Order
    {
        $productsData = $validated['products'] ?? [];
        if (! is_array($productsData) || $productsData === []) {
            throw new InvalidArgumentException('missing_products');
        }

        $warehouseUserId = (int) ($validated['warehouse_user_id'] ?? 0);
        if ($warehouseUserId <= 0) {
            throw new RuntimeException('warehouse_user_required');
        }

        // Almacenero = users.id enviado en warehouse_user_id.
        $warehouseUser = User::query()->find($warehouseUserId);
        if (! $warehouseUser) {
            throw new RuntimeException('warehouse_user_required');
        }

        $internalComments = $validated['internal_comments'] ?? null;

        $company = Company::query()->find($companyId);
        $languageId = $company?->language_id ?? Language::query()->value('language_id');
        if ($languageId === null) {
            throw new RuntimeException('language_required');
        }

        $sellerId = $this->resolveSellerId($validated, $company, (int) $validated['customer_id']);

        $year2 = date('y');
        $prefix = 'OCN'.$year2;
        $digitLength = 6;

        try {
            DB::beginTransaction();

            // Sin eventos FIFO intermedios: el observer abortaba la NC por qty.
            // Se sincroniza FIFO una sola vez al final, en modo permisivo.
            $order = Order::withoutEvents(function () use (
                $validated,
                $companyId,
                $tokenUserId,
                $warehouseUser,
                $languageId,
                $sellerId,
                $digitLength,
                $prefix,
                $productsData,
                $internalComments
            ) {
                $orderModel = new Order();
                $uuid = $orderModel->generate_uuid();
                $orderNumber = $this->generateUniqueOrderNumber($companyId, $digitLength, $prefix);

                $provenance = OrderAuthTokenProvenance::fromRequest(request(), $companyId);

                $orderPayload = [
                    'company_id' => $companyId,
                    'UUID' => $uuid,
                    'tipo_d' => 'C',
                    'order_number' => $orderNumber,
                    'language_id' => $languageId,
                    'customer_id' => (int) $validated['customer_id'],
                    'seller_id' => $sellerId > 0 ? $sellerId : null,
                    'discount_type' => $validated['discount_type'],
                    'discount' => $validated['discount'] ?? 0,
                    'proforma' => $validated['proforma'] ?? false,
                    'status_id' => (int) (StatusOrder::getIdByCode(OrderStatusCodes::PLACED) ?: StatusOrder::getIdByCode(OrderStatusCodes::SHOPPING)),
                    'source' => $validated['source'] ?? 'warehouse',
                    'token_create' => $provenance['token_create'],
                    'user_id_token' => $provenance['user_id_token'] ?? (($tokenUserId && $tokenUserId > 0) ? $tokenUserId : null),
                    'rol_token' => $provenance['rol_token'] ?? 'warehouse',
                ];

                // Almacenero del body → warehouse_user_id (users.id).
                // warehouse_id también = user_id (mismo patrón que assign-warehouse).
                $orderPayload['warehouse_user_id'] = (int) $warehouseUser->id;
                if (Schema::hasColumn('orders', 'warehouse_id')) {
                    $orderPayload['warehouse_id'] = (int) $warehouseUser->id;
                }
                if (Schema::hasColumn('orders', 'warehouse_assign_date')) {
                    $orderPayload['warehouse_assign_date'] = now();
                }

                $order = Order::create($orderPayload);

                $noteUserId = ($tokenUserId && $tokenUserId > 0) ? $tokenUserId : (int) $warehouseUser->id;
                if (! empty($internalComments) && $noteUserId > 0) {
                    $order->notes()->create([
                        'type' => OrderNoteTypes::Internal->value,
                        'user_id' => $noteUserId,
                        'note' => $internalComments,
                    ]);
                }

                $details = [];
                foreach ($productsData as $item) {
                    $product = Product::query()->findOrFail($item['pro_id']);
                    $qty = (float) $item['qty'];
                    $salePrice = (float) $item['price'];
                    $lineTotal = $qty * $salePrice;

                    $details[] = new OrderDetail([
                        'product_id' => $item['pro_id'],
                        'qty' => $qty,
                        'sale_price' => $salePrice,
                        'fob_price' => (float) ($product->fob_price ?? 0),
                        'fixed_pricedate' => ! empty($item['fixed_price']) ? Carbon::now() : null,
                        'comment' => $item['comment'] ?? '-',
                        'amount_sales' => $lineTotal,
                        'amount' => $lineTotal,
                        'total' => $lineTotal,
                        'total_amount' => $lineTotal,
                        'delivered_quantity' => 0,
                        'delivered_pack' => 0,
                        'pack' => 0,
                        'discount' => 0,
                        'id_kor' => 1,
                        'comments' => 1,
                    ]);
                }

                $order->order_details()->saveMany($details);

                $subTotal = collect($details)->sum(static function (OrderDetail $d): float {
                    return (float) ($d->total_amount ?? ((float) $d->qty * (float) $d->sale_price));
                });
                $discountType = (int) ($order->discount_type ?? 0);
                $discount = (float) ($order->discount ?? 0);
                $discountAmount = 0.0;
                if ($discountType === 1) {
                    $discountAmount = $subTotal * ($discount / 100);
                } elseif ($discountType === 2) {
                    $discountAmount = $discount;
                }
                $order->amount = $subTotal;
                $order->discount_a = $discountAmount;
                $order->total_amount = max(0.0, $subTotal - $discountAmount);
                $order->save();

                return $order;
            });

            // FIFO permisivo: no bloquea la NC.
            try {
                app(CustomerOrderProductFifoService::class)->syncOrder(
                    $order->fresh(['order_details.product']),
                    false
                );
            } catch (FifoCreditException) {
                // Se crea igual aunque no haya capas FIFO.
            }

            DB::commit();

            $with = [
                'customer',
                'seller.user',
                'statusOrder',
                'order_details.product.unit',
                'order_details.product.images',
                'order_details.product.default_image',
                'notes',
            ];
            if (Schema::hasColumn('orders', 'warehouse_user_id')) {
                $with[] = 'warehouse_user.warehouse';
                $with[] = 'warehouse_user.user';
            }
            if (Schema::hasColumn('orders', 'warehouse_id')) {
                $with[] = 'warehouse';
            }

            return $order->fresh($with);
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * seller_id opcional: request → vendedores del cliente → default de compañía → null.
     *
     * @param  array<string, mixed>  $validated
     */
    private function resolveSellerId(array $validated, ?Company $company, int $customerId): int
    {
        $sellerId = (int) ($validated['seller_id'] ?? 0);
        if ($sellerId > 0) {
            return $sellerId;
        }

        $customer = Customer::query()->with('sellers')->find($customerId);
        $fromCustomer = (int) ($customer?->sellers?->first()?->id ?? 0);
        if ($fromCustomer > 0) {
            return $fromCustomer;
        }

        return (int) ($company?->default_seller_id ?? 0);
    }
}

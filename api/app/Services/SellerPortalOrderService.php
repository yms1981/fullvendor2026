<?php

namespace App\Services;

use App\Enums\OrderNoteTypes;
use App\Enums\OrderStatusCodes;
use App\Exceptions\FifoCreditException;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Orders\CreatesOrders;
use App\Http\Resources\OrderListResource;
use App\Models\Company;
use App\Models\Language;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\StatusOrder;
use App\Models\Vendor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class SellerPortalOrderService
{
    use CreatesOrders;

    public function __construct(
        private readonly SellerPortalCustomerService $customerService,
    ) {}

    /**
     * @return list<int>
     */
    public function assignedCustomerIds(Vendor $vendor): array
    {
        return $this->customerService->assignedCustomerIds($vendor);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listOrders(Vendor $vendor, int $sellerId, array $customerIds, bool $includeOrderDetails = false): array
    {
        $companyId = (int) $vendor->company_id;
        $assigned = $this->assignedCustomerIds($vendor);
        if ($assigned === []) {
            return [];
        }

        $assignedLookup = array_fill_keys($assigned, true);
        $filterCustomers = [];
        if ($customerIds !== []) {
            foreach ($customerIds as $cid) {
                $cid = (int) $cid;
                if ($cid > 0 && isset($assignedLookup[$cid])) {
                    $filterCustomers[] = $cid;
                }
            }
        } else {
            $filterCustomers = $assigned;
        }

        if ($filterCustomers === []) {
            return [];
        }

        $relations = [
            'customer',
            'seller.user',
            'statusOrder',
        ];
        if ($includeOrderDetails) {
            $relations[] = 'order_details.product.unit';
        }
        if (Schema::hasColumn('orders', 'warehouse_id')) {
            $relations[] = 'warehouse';
        } elseif (Schema::hasColumn('orders', 'warehouse_user_id')) {
            $relations[] = 'warehouse_user.warehouse';
            $relations[] = 'warehouse_user.user';
        }

        $orders = Order::query()
            ->where('company_id', $companyId)
            ->whereIn('customer_id', $filterCustomers)
            ->when($sellerId > 0, static function ($q) use ($sellerId) {
                $q->where('seller_id', $sellerId);
            })
            ->with($relations)
            ->orderByDesc('order_id')
            ->get()
            ->filter(fn (Order $order) => $this->orderAccessibleByVendor($vendor, $order))
            ->values();

        return OrderListResource::collection($orders)->resolve();
    }

    public function orderAccessibleByVendor(Vendor $vendor, Order $order): bool
    {
        if ((int) $order->company_id !== (int) $vendor->company_id) {
            return false;
        }

        $sellerId = (int) $vendor->id;
        $orderSellerId = (int) ($order->seller_id ?? 0);
        if ($orderSellerId > 0 && $orderSellerId === $sellerId) {
            return true;
        }

        $assigned = $this->assignedCustomerIds($vendor);
        if ($assigned === []) {
            return false;
        }

        $customerId = (int) ($order->customer_id ?? 0);
        if ($customerId <= 0 || ! in_array($customerId, $assigned, true)) {
            return false;
        }

        if ($orderSellerId > 0 && $orderSellerId !== $sellerId) {
            return false;
        }

        return true;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findOrderFull(Vendor $vendor, int $orderId): ?array
    {
        $order = Order::query()->find($orderId);
        if ($order === null || ! $this->orderAccessibleByVendor($vendor, $order)) {
            return null;
        }

        /** @var OrderController $controller */
        $controller = app(OrderController::class);

        return $controller->showFull($orderId);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function productForOrderImage(Vendor $vendor, int $proId): ?array
    {
        if ($proId <= 0) {
            return null;
        }

        $product = Product::query()
            ->where('pro_id', $proId)
            ->where('company_id', (int) $vendor->company_id)
            ->with(['default_image', 'images'])
            ->first();

        return $product?->toArray();
    }

    /**
     * @param array<string, mixed> $validated
     *
     * @return array{order: Order}|array{error: string, status: int}
     */
    public function createOrder(Vendor $vendor, array $validated, CompanyPlanLimitService $planLimits): array
    {
        $companyId = (int) $vendor->company_id;
        $customerId = (int) ($validated['customer_id'] ?? 0);
        $customer = \App\Models\Customer::query()
            ->where('customer_id', $customerId)
            ->where('company_id', $companyId)
            ->first();

        if ($customer === null || ! $this->customerService->customerAccessibleByVendor($vendor, $customer)) {
            return ['error' => 'customer_forbidden', 'status' => 403];
        }

        $denied = $planLimits->assertCanCreateOrder($companyId);
        if ($denied !== null) {
            $payload = json_decode($denied->getContent(), true);

            return ['error' => (string) ($payload['message'] ?? 'plan_limit'), 'status' => 422];
        }

        $productsData = $validated['products'] ?? [];
        $internalComments = $validated['internal_comments'] ?? null;
        unset($validated['products'], $validated['internal_comments']);

        $sellerId = (int) ($validated['seller_id'] ?? 0);
        if ($sellerId <= 0) {
            $sellerId = (int) $vendor->id;
        }
        $validated['seller_id'] = $sellerId;

        $company = Company::find($companyId);
        $languageId = $company?->language_id ?? Language::query()->value('language_id');
        if ($languageId === null) {
            return ['error' => 'language_id_required', 'status' => 422];
        }

        $orderModel = new Order();
        $uuid = $orderModel->generate_uuid();
        $tipoD = $validated['tipo_d'] ?? 'D';
        $year2 = date('y');
        $prefix = ($tipoD === 'C') ? 'OCN' . $year2 : 'ORD' . $year2;
        $digitLength = ($tipoD === 'C') ? 6 : 5;

        try {
            DB::beginTransaction();

            $orderNumber = $this->generateUniqueOrderNumber($companyId, $digitLength, $prefix);

            $order = Order::create([
                'company_id'     => $companyId,
                'UUID'           => $uuid,
                'tipo_d'         => $tipoD,
                'order_number'   => $orderNumber,
                'language_id'    => $languageId,
                'customer_id'    => $customerId,
                'seller_id'      => $sellerId,
                'discount_type'  => $validated['discount_type'],
                'discount'       => $validated['discount'] ?? 0,
                'proforma'       => $validated['proforma'] ?? false,
                'status_id'      => StatusOrder::getIdByCode(OrderStatusCodes::PLACED),
                'source'         => $validated['source'] ?? 'seller_portal',
            ]);

            if ($internalComments) {
                $order->notes()->create([
                    'type'    => OrderNoteTypes::Internal->value,
                    'user_id' => auth()->id(),
                    'note'    => $internalComments,
                ]);
            }

            $details = [];
            foreach ($productsData as $item) {
                $product = Product::findOrFail($item['pro_id']);
                $qty = (float) $item['qty'];
                $salePrice = (float) $item['price'];
                $lineTotal = $qty * $salePrice;

                $details[] = new OrderDetail([
                    'product_id'         => $item['pro_id'],
                    'qty'                => $qty,
                    'sale_price'         => $salePrice,
                    'fob_price'          => (float) ($product->fob_price ?? 0),
                    'fixed_pricedate'    => ! empty($item['fixed_price']) ? Carbon::now() : null,
                    'comment'            => $item['comment'] ?? '-',
                    'amount_sales'       => $lineTotal,
                    'amount'             => $lineTotal,
                    'total'              => $lineTotal,
                    'total_amount'       => $lineTotal,
                    'delivered_quantity' => 0,
                    'delivered_pack'     => 0,
                    'pack'               => 0,
                    'discount'           => 0,
                    'id_kor'             => 1,
                    'comments'           => 1,
                ]);
            }

            $order->order_details()->saveMany($details);

            $subTotal = collect($details)->sum(static fn (OrderDetail $d): float => (float) ($d->total_amount ?? ((float) $d->qty * (float) $d->sale_price)));
            $discountType = (int) ($order->discount_type ?? 0);
            $discount = (float) ($order->discount ?? 0);
            $discountAmount = 0.0;
            if ($discountType === 1) {
                $discountAmount = $subTotal * ($discount / 100);
            } elseif ($discountType === 2) {
                $discountAmount = $discount;
            }
            $totalAmount = max(0.0, $subTotal - $discountAmount);
            $order->amount = $subTotal;
            $order->discount_a = $discountAmount;
            $order->total_amount = $totalAmount;
            $order->save();

            app(CustomerOrderProductFifoService::class)->syncOrder(
                $order->fresh(['order_details.product'])
            );

            DB::commit();

            return ['order' => $order->fresh('order_details.product')];
        } catch (FifoCreditException $e) {
            DB::rollBack();

            return [
                'error' => $e->errorKey,
                'status' => 422,
                'message' => $e->getMessage(),
                'context' => $e->context,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('SellerPortalOrderService::createOrder', [
                'message' => $e->getMessage(),
            ]);

            return ['error' => 'order_create_failed', 'status' => 500];
        }
    }
}

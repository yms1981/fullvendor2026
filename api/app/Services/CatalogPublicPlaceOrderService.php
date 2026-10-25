<?php

namespace App\Services;

use App\Enums\OrderNoteTypes;
use App\Enums\OrderStatusCodes;
use App\Http\Controllers\Orders\CreatesOrders;
use App\Models\Address;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Language;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\StatusCart;
use App\Models\StatusOrder;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use stdClass;
use Throwable;

final class CatalogPublicPlaceOrderService
{
    use CreatesOrders;

    public function __construct(
        private readonly CatalogPublicSessionService $sessionService,
        private readonly CompanyPlanLimitService $planLimits,
        private readonly CatalogPublicOrderEmailService $emailService,
    ) {
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public function place(stdClass $cart, string $token, array $input): array
    {
        if ($this->sessionService->isExpired($cart)) {
            return $this->fail('expired', 'El tiempo del catálogo expiró.', 410);
        }

        $companyId = (int) ($cart->company_id ?? 0);
        $customerId = (int) ($cart->customer_id ?? 0);
        $catalogId = (int) ($cart->catalog_id ?? 0);
        $typeCart = (string) ($cart->type_cart ?? '');

        if ($companyId < 1 || $customerId < 1 || $token === '') {
            return $this->fail('invalid_cart', 'Carrito inválido.', 422);
        }

        if ($this->isSubmitBlocked($cart)) {
            return $this->fail('nosubmit', 'No se puede enviar la orden en el estado actual del carrito.', 422);
        }

        $company = Company::query()->findOrFail($companyId);
        $customer = Customer::query()->findOrFail($customerId);

        $denied = $this->planLimits->assertCanCreateOrder($companyId);
        if ($denied !== null) {
            $payload = json_decode($denied->getContent(), true);

            return $this->fail(
                'plan_limit',
                (string) ($payload['message'] ?? 'Limit reached'),
                422
            );
        }

        $lines = $this->loadCartLines($token);
        if ($lines === []) {
            return $this->fail('empty_cart', 'El carrito no tiene productos.', 422);
        }

        $validation = $this->validateLines($lines, $company);
        if ($validation !== null) {
            return $validation;
        }

        try {
            $sellerId = $this->resolveSellerId($cart, $companyId);
        } catch (ValidationException $e) {
            return $this->fail('seller_missing', $e->getMessage(), 422);
        }

        $languageId = $company->language_id ?? Language::query()->value('language_id');
        if ($languageId === null) {
            return $this->fail('language_missing', 'No hay idioma configurado para la empresa.', 422);
        }

        $customerDiscount = (float) ($customer->discount ?? 0);
        $proforma = ! empty($input['proforma']);
        $internalComments = trim((string) ($input['internal_comments'] ?? ''));
        $notes = is_array($input['notes'] ?? null) ? $input['notes'] : [];
        $newAddress = is_array($input['new_address'] ?? null) ? $input['new_address'] : null;

        $statusCode = $typeCart === 'to_customer'
            ? OrderStatusCodes::APPROVED
            : OrderStatusCodes::PLACED;

        try {
            $order = DB::transaction(function () use (
                $cart,
                $token,
                $companyId,
                $customerId,
                $catalogId,
                $typeCart,
                $sellerId,
                $languageId,
                $customerDiscount,
                $proforma,
                $internalComments,
                $notes,
                $newAddress,
                $statusCode,
                $lines,
                $input
            ): Order {
                $year2 = date('y');
                $orderNumber = $this->generateUniqueOrderNumber($companyId, 5, 'ORD' . $year2);

                $order = Order::create([
                    'company_id' => $companyId,
                    'UUID' => (new Order())->generate_uuid(),
                    'tipo_d' => 'D',
                    'order_number' => $orderNumber,
                    'language_id' => $languageId,
                    'customer_id' => $customerId,
                    'seller_id' => $sellerId,
                    'discount_type' => 1,
                    'discount' => $customerDiscount > 0 ? $customerDiscount : 0,
                    'proforma' => $proforma,
                    'status_id' => StatusOrder::getIdByCode($statusCode),
                    'source' => $this->resolveSource($typeCart, $input),
                    'catalog_id' => $catalogId > 0 ? $catalogId : null,
                    'portal_vendor' => $typeCart === 'to_portal_seller' ? 1 : 0,
                    'date_confirmed' => $typeCart === 'to_customer' ? Carbon::now() : null,
                ]);

                $noteUserId = (int) ($cart->user_id ?? 0);

                if ($internalComments !== '') {
                    $order->notes()->create([
                        'type' => OrderNoteTypes::Internal->value,
                        'user_id' => $noteUserId > 0 ? $noteUserId : null,
                        'note' => $internalComments,
                    ]);
                }

                if ($newAddress !== null && ! empty($newAddress['new_address'])) {
                    $address = $this->createAddress($newAddress);
                    $order->address_id = $address->id;
                    $order->save();
                }

                if (! empty($notes['shipping_notes'])) {
                    $order->notes()->create([
                        'user_id' => $noteUserId > 0 ? $noteUserId : null,
                        'type' => OrderNoteTypes::Shipping,
                        'note' => (string) $notes['shipping_notes'],
                    ]);
                }

                if (! empty($notes['comment_order'])) {
                    $order->notes()->create([
                        'user_id' => $noteUserId > 0 ? $noteUserId : null,
                        'type' => OrderNoteTypes::Order,
                        'note' => (string) $notes['comment_order'],
                    ]);
                }

                $details = [];
                foreach ($lines as $line) {
                    $product = $line['product'];
                    $qty = (float) $line['qty'];
                    $salePrice = (float) $line['sale_price'];
                    $lineTotal = $qty * $salePrice;

                    $details[] = new OrderDetail([
                        'product_id' => (int) $product->pro_id,
                        'qty' => $qty,
                        'sale_price' => $salePrice,
                        'fob_price' => (float) ($product->fob_price ?? 0),
                        'fixed_pricedate' => null,
                        'comment' => (string) ($line['comment'] ?? '-'),
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

                $subTotal = collect($details)->sum(static fn (OrderDetail $d): float => (float) ($d->total_amount ?? 0));
                $discountAmount = $customerDiscount > 0 ? $subTotal * ($customerDiscount / 100) : 0.0;
                $order->amount = $subTotal;
                $order->discount_a = $discountAmount;
                $order->total_amount = max(0.0, $subTotal - $discountAmount);
                $order->save();

                app(CustomerOrderProductFifoService::class)->syncOrder(
                    $order->fresh(['order_details.product'])
                );

                $this->finalizeCatalogCart($cart, $token, (int) $order->order_id, $typeCart);

                return $order;
            });

            $orderId = (int) $order->order_id;
            $this->emailService->sendAfterPlace($order, $typeCart);

            return [
                'success' => true,
                'order_id' => $orderId,
                'type_cart' => $typeCart,
                'emails_sent' => in_array($typeCart, ['to_portal_seller', 'to_customer'], true),
            ];
        } catch (Throwable $e) {
            report($e);

            return $this->fail('server_error', $e->getMessage(), 500);
        }
    }

    private function isSubmitBlocked(stdClass $cart): bool
    {
        $statusCartId = $cart->status_cart ?? null;
        if ($statusCartId === null || $statusCartId === '') {
            return false;
        }

        $row = StatusCart::query()->where('id_status', (int) $statusCartId)->first();

        return $row !== null && (int) ($row->nosubmit_order ?? 0) === 1;
    }

    /**
     * @return list<array{product: Product, qty: float, sale_price: float, comment: string}>
     */
    private function loadCartLines(string $token): array
    {
        if (! Schema::hasTable('detail_car')) {
            return [];
        }

        $rows = DB::table('detail_car as d')
            ->join('products as p', 'p.pro_id', '=', 'd.product_id')
            ->where('d.token_car', $token)
            ->orderBy('d.detail_id')
            ->select([
                'd.qty',
                'd.sale_price as detail_sale_price',
                'd.comment',
                'p.pro_id',
                'p.company_id',
                'p.name',
                'p.stock',
                'p.minimum_stock',
                'p.notify_minimum_stock',
                'p.fob_price',
            ])
            ->get();

        $lines = [];
        foreach ($rows as $row) {
            $product = Product::query()->find((int) $row->pro_id);
            if ($product === null) {
                continue;
            }
            $lines[] = [
                'product' => $product,
                'qty' => (float) ($row->qty ?? 0),
                'sale_price' => (float) ($row->detail_sale_price ?? 0),
                'comment' => (string) ($row->comment ?? ''),
            ];
        }

        return $lines;
    }

    /**
     * @param list<array{product: Product, qty: float, sale_price: float, comment: string}> $lines
     * @return array<string, mixed>|null
     */
    private function validateLines(array $lines, Company $company): ?array
    {
        foreach ($lines as $line) {
            $product = $line['product'];
            $qty = (int) $line['qty'];

            if ($qty <= 0) {
                return $this->fail('invalid_quantity', "Cantidad inválida para {$product->name}.", 422);
            }

            if ((int) ($product->notify_minimum_stock ?? 0) === 1 && (float) ($product->minimum_stock ?? 0) > 0) {
                $minimum = (int) $product->minimum_stock;
                if ($qty % $minimum !== 0) {
                    return $this->fail(
                        'quantity_not_multiple',
                        "La cantidad de {$product->name} debe ser múltiplo de {$minimum}.",
                        422,
                        ['minimum_stock' => $minimum, 'product_id' => (int) $product->pro_id]
                    );
                }
            }

            if (! $product->validMinimumQuantity($qty)) {
                return $this->fail(
                    'minimum_quantity',
                    "La cantidad mínima de {$product->name} es {$product->minimum_stock}.",
                    422,
                    ['product_id' => (int) $product->pro_id]
                );
            }

            if ($company->sell_with_inventory && ! $product->hasStock($qty)) {
                return $this->fail(
                    'insufficient_stock',
                    "Stock insuficiente para {$product->name}.",
                    422,
                    ['product_id' => (int) $product->pro_id]
                );
            }
        }

        return null;
    }

    /**
     * @throws ValidationException
     */
    private function resolveSellerId(stdClass $cart, int $companyId): int
    {
        $userId = (int) ($cart->user_id ?? 0);
        if ($userId > 0) {
            $seller = Vendor::query()->where('user_id', $userId)->first();
            if ($seller !== null) {
                return (int) $seller->id;
            }
        }

        $company = Company::query()->find($companyId);
        if ($company !== null && ! empty($company->default_seller_id)) {
            return (int) $company->default_seller_id;
        }

        throw ValidationException::withMessages([
            'seller_id' => 'No se pudo determinar el vendedor.',
        ]);
    }

    /**
     * @param array<string, mixed> $input
     */
    private function resolveSource(string $typeCart, array $input): string
    {
        if (! empty($input['source']) && is_string($input['source'])) {
            return $input['source'];
        }

        return match ($typeCart) {
            'to_company', 'to_portal_seller', 'to_customer' => 'web',
            default => 'web',
        };
    }

    private function finalizeCatalogCart(stdClass $cart, string $token, int $orderId, string $typeCart): void
    {
        $cartId = (int) ($cart->cart_id ?? 0);
        if ($cartId < 1) {
            return;
        }

        $updates = [
            'order_id' => $orderId,
            'status_cart' => 4,
            'disable_total' => 1,
            'deleted_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ];

        if ($typeCart === 'to_customer') {
            $updates['order_sent'] = 2;
        }

        if ($typeCart === 'to_company') {
            $updates['expired_date'] = Carbon::now()->subDay()->format('Y-m-d H:i:s');
        }

        DB::table('catalog_cart')->where('cart_id', $cartId)->update($updates);

        if (in_array($typeCart, ['to_portal_seller', 'to_customer'], true) && Schema::hasTable('detail_car')) {
            DB::table('detail_car')->where('token_car', $token)->delete();
        }
    }

    /**
     * @param array<string, mixed> $addressData
     */
    private function createAddress(array $addressData): Address
    {
        $countryRaw = $addressData['country'] ?? $addressData['country_id'] ?? null;
        $stateRaw = $addressData['state'] ?? $addressData['state_id'] ?? null;
        $cityRaw = $addressData['city'] ?? $addressData['city_id'] ?? null;

        $payload = [
            'street' => (string) ($addressData['delivery_address'] ?? ''),
            'zipcode' => (string) ($addressData['zip_code'] ?? ''),
            'zone' => (string) ($addressData['zone'] ?? ''),
            'country_id' => $this->nullableIntId($countryRaw),
            'state_id' => $this->nullableIntId($stateRaw),
            'city_id' => $this->nullableIntId($cityRaw),
        ];

        // Catálogo público: ciudad/estado suelen venir como texto libre, no IDs.
        if ($payload['state_id'] === null && is_string($stateRaw) && trim($stateRaw) !== '') {
            $payload['state'] = trim($stateRaw);
        }
        if ($payload['city_id'] === null && is_string($cityRaw) && trim($cityRaw) !== '') {
            $payload['city'] = trim($cityRaw);
        }

        return Address::create($payload);
    }

    /**
     * @param mixed $value
     */
    private function nullableIntId($value): ?int
    {
        if ($value === null || $value === '' || is_bool($value)) {
            return null;
        }
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }
        if (is_string($value) && ctype_digit(trim($value))) {
            $id = (int) trim($value);

            return $id > 0 ? $id : null;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $extra
     * @return array<string, mixed>
     */
    private function fail(string $code, string $message, int $httpStatus, array $extra = []): array
    {
        return array_merge([
            'success' => false,
            'error' => $code,
            'message' => $message,
            'http_status' => $httpStatus,
        ], $extra);
    }
}

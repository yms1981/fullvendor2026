<?php

namespace App\Services\Ecommerce;

use App\Enums\OrderNoteTypes;
use App\Enums\OrderStatusCodes;
use App\Exceptions\CreditReturnExceedsSalesException;
use App\Exceptions\FifoCreditException;
use App\Http\Controllers\Orders\CreatesOrders;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\StatusOrder;
use App\Models\Vendor;
use App\Services\CompanyPlanLimitService;
use App\Services\CreditReturnOverSalesService;
use App\Services\CustomerOrderProductFifoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/**
 * Crea un pedido de la tienda con el esquema actual (seller_id + order_details).
 * El cuerpo acepta el DTO que ya arma Home Value (user_id del vendedor + itemList).
 */
class EcommerceOrderService
{
    use CreatesOrders;

    /**
     * @param  array<string, mixed>  $data
     * @return array{order_id: int, order_number: string, uuid: string, duplicate: bool}
     */
    public function place(array $data): array
    {
        $companyId = (int) ($data['company_id'] ?? 0);
        $lockedCompany = (int) config('ecommerce.company_id', 0);
        if ($lockedCompany > 0 && $companyId !== $lockedCompany) {
            throw new InvalidArgumentException('company_id no corresponde a esta tienda.');
        }
        if ($companyId <= 0 || ! Company::query()->whereKey($companyId)->exists()) {
            throw new InvalidArgumentException('company_id no es válido.');
        }

        $denied = app(CompanyPlanLimitService::class)->assertCanCreateOrder($companyId);
        if ($denied !== null) {
            $payload = $denied->getData(true);
            $message = is_array($payload) ? (string) ($payload['message'] ?? '') : '';
            throw new InvalidArgumentException($message !== '' ? $message : 'Límite de pedidos de la compañía.');
        }

        $customerId = (int) ($data['customer_id'] ?? 0);
        $customer = Customer::query()
            ->where('customer_id', $customerId)
            ->where('company_id', $companyId)
            ->first();
        if ($customer === null) {
            throw new InvalidArgumentException('El cliente no existe en esta compañía.');
        }

        $seller = $this->resolveSeller($data, $companyId);
        $sellerUserId = (int) ($seller->user_id ?? 0);
        if ($sellerUserId <= 0) {
            throw new InvalidArgumentException('El vendedor no tiene usuario asociado.');
        }

        $items = $data['itemList'] ?? $data['items'] ?? $data['products'] ?? [];
        if (! is_array($items) || $items === []) {
            throw new InvalidArgumentException('El pedido no tiene líneas.');
        }

        $tipoD = strtoupper(trim((string) ($data['tipo_d'] ?? 'D')));
        if (! in_array($tipoD, ['D', 'C'], true)) {
            $tipoD = 'D';
        }

        $languageId = (int) ($data['language_id'] ?? 0);
        if ($languageId <= 0) {
            $languageId = (int) (Company::query()->whereKey($companyId)->value('language_id') ?? 1);
        }

        [$discount, $discountType] = $this->normalizeHeaderDiscount($data);

        $uuid = trim((string) ($data['uuid'] ?? $data['UUID'] ?? ''));
        if ($uuid !== '') {
            $existing = Order::query()
                ->where('company_id', $companyId)
                ->where('UUID', $uuid)
                ->first();
            if ($existing !== null) {
                return [
                    'order_id' => (int) $existing->order_id,
                    'order_number' => (string) $existing->order_number,
                    'uuid' => (string) $existing->UUID,
                    'duplicate' => true,
                ];
            }
        }

        $lines = $this->buildLines($items, $companyId, $tipoD, $languageId);
        $subTotal = 0.0;
        foreach ($lines as $line) {
            $subTotal += (float) $line['total_amount'];
        }
        $discountAmount = $discountType === 1
            ? $subTotal * ($discount / 100)
            : $discount;
        if ($discountAmount < 0) {
            $discountAmount = 0.0;
        }
        if ($discountAmount > $subTotal) {
            $discountAmount = $subTotal;
        }
        $totalAmount = max(0.0, $subTotal - $discountAmount);

        $comment = trim((string) ($data['order_comment'] ?? $data['comment'] ?? ''));
        if (strlen($comment) > 8000) {
            $comment = substr($comment, 0, 8000);
        }

        $year2 = date('y');
        $prefix = ($tipoD === 'C') ? 'OCN'.$year2 : 'ORD'.$year2;
        $digitLength = ($tipoD === 'C') ? 6 : 5;
        $statusId = StatusOrder::getIdByCode(OrderStatusCodes::PLACED);
        if ($statusId === false) {
            throw new InvalidArgumentException('No hay un estado inicial de pedido.');
        }

        try {
            DB::beginTransaction();

            $orderModel = new Order();
            if ($uuid === '') {
                $uuid = $orderModel->generate_uuid();
            }
            $orderNumber = $this->generateUniqueOrderNumber($companyId, $digitLength, $prefix);

            $orderPayload = [
                'company_id' => $companyId,
                'UUID' => $uuid,
                'tipo_d' => $tipoD,
                'order_number' => $orderNumber,
                'language_id' => $languageId,
                'customer_id' => $customerId,
                'seller_id' => (int) $seller->id,
                'discount_type' => $discountType,
                'discount' => $discount,
                'proforma' => 0,
                'status_id' => $statusId,
                'source' => 'ecommerce',
                'amount' => $subTotal,
                'discount_a' => $discountAmount,
                'total_amount' => $totalAmount,
            ];
            if (Schema::hasColumn('orders', 'token_create')) {
                $orderPayload['token_create'] = 'ecommerce';
            }
            if (Schema::hasColumn('orders', 'user_id_token')) {
                $orderPayload['user_id_token'] = $sellerUserId;
            }
            if (Schema::hasColumn('orders', 'rol_token')) {
                $orderPayload['rol_token'] = 'ecommerce';
            }

            $order = Order::create($orderPayload);

            $details = [];
            foreach ($lines as $line) {
                $details[] = new OrderDetail($line);
            }
            $order->order_details()->saveMany($details);

            if ($comment !== '') {
                $order->notes()->create([
                    'type' => OrderNoteTypes::Order->value,
                    'user_id' => $sellerUserId,
                    'note' => $comment,
                ]);
            }

            if ($tipoD === 'C') {
                app(CreditReturnOverSalesService::class)->assertOrderWithinSales($order);
            }

            app(CustomerOrderProductFifoService::class)->syncOrder($order->fresh(['order_details.product']));

            DB::commit();
        } catch (CreditReturnExceedsSalesException $e) {
            DB::rollBack();
            throw new InvalidArgumentException('La nota de crédito supera las ventas.');
        } catch (FifoCreditException $e) {
            DB::rollBack();
            throw new InvalidArgumentException($e->getMessage());
        } catch (InvalidArgumentException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return [
            'order_id' => (int) $order->order_id,
            'order_number' => (string) $order->order_number,
            'uuid' => $uuid,
            'duplicate' => false,
        ];
    }

    /**
     * La tienda envía 1 = importe y 2 = porcentaje.
     * En orders el 1 es porcentaje y el 2 es importe.
     *
     * @param  array<string, mixed>  $data
     * @return array{0: float, 1: int}
     */
    private function normalizeHeaderDiscount(array $data): array
    {
        $discount = (float) ($data['discount'] ?? 0);
        if (! is_finite($discount) || $discount < 0) {
            $discount = 0.0;
        }
        $incoming = (int) ($data['discount_type'] ?? 1);
        $storedType = $incoming === 2 ? 1 : 2;

        return [round($discount, 4), $storedType];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveSeller(array $data, int $companyId): Vendor
    {
        $sellerId = (int) ($data['seller_id'] ?? 0);
        if ($sellerId > 0) {
            $seller = Vendor::query()
                ->whereKey($sellerId)
                ->where('company_id', $companyId)
                ->first();
            if ($seller !== null) {
                return $seller;
            }
        }

        $userId = (int) ($data['user_id'] ?? 0);
        if ($userId > 0) {
            $seller = Vendor::query()
                ->where('user_id', $userId)
                ->where('company_id', $companyId)
                ->first();
            if ($seller !== null) {
                return $seller;
            }
        }

        throw new InvalidArgumentException('No se encontró el vendedor de la orden.');
    }

    /**
     * @param  array<int, mixed>  $items
     * @return list<array<string, mixed>>
     */
    private function buildLines(array $items, int $companyId, string $tipoD, int $languageId): array
    {
        $lines = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $incomingId = (int) ($item['product_id'] ?? $item['pro_id'] ?? 0);
            $qty = (float) ($item['qty'] ?? 0);
            $price = (float) ($item['sale_price'] ?? $item['salesp'] ?? $item['price'] ?? 0);
            if ($incomingId <= 0 || $qty <= 0) {
                continue;
            }
            if (! is_finite($price) || $price < 0) {
                $price = 0.0;
            }
            if (strtoupper($tipoD) === 'D' && $qty < 0) {
                $qty = abs($qty);
            }

            $product = null;
            if ($languageId > 0) {
                $product = Product::query()
                    ->where('company_id', $companyId)
                    ->where('product_id', $incomingId)
                    ->where('language_id', $languageId)
                    ->orderByDesc('pro_id')
                    ->first();
            }
            if ($product === null) {
                $product = Product::query()
                    ->where('company_id', $companyId)
                    ->where('product_id', $incomingId)
                    ->orderByDesc('pro_id')
                    ->first();
            }
            if ($product === null) {
                $product = Product::query()
                    ->where('company_id', $companyId)
                    ->where('pro_id', $incomingId)
                    ->first();
            }
            if ($product === null) {
                throw new InvalidArgumentException('Producto no encontrado: '.$incomingId);
            }

            $lineTotal = round($qty * $price, 2);
            $lineComment = trim((string) ($item['comment'] ?? $item['line_note'] ?? $item['comments'] ?? ''));
            if (strlen($lineComment) > 5000) {
                $lineComment = substr($lineComment, 0, 5000);
            }

            $lines[] = [
                'product_id' => (int) $product->pro_id,
                'qty' => $qty,
                'sale_price' => round($price, 4),
                'fob_price' => (float) ($product->fob_price ?? 0),
                'comment' => $lineComment,
                'comments' => $lineComment,
                'amount_sales' => $lineTotal,
                'amount' => $lineTotal,
                'total' => $lineTotal,
                'total_amount' => $lineTotal,
                'delivered_quantity' => 0,
                'delivered_pack' => 0,
                'pack' => 0,
                'discount' => 0,
                'discount_type' => 1,
                'id_kor' => 1,
            ];
        }

        if ($lines === []) {
            throw new InvalidArgumentException('El pedido no tiene líneas válidas.');
        }

        return $lines;
    }
}

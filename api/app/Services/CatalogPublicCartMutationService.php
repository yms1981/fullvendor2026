<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\FormatNumeric;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use stdClass;

final class CatalogPublicCartMutationService
{
    public function __construct(
        private readonly CatalogCartTotalsService $totalsService,
        private readonly CatalogPublicSessionService $sessionService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function setLineQty(stdClass $cart, string $token, int $productId, ?int $qty, ?string $comment = null): array
    {
        if ($qty === null) {
            return $this->error('quantity_required');
        }

        if ($qty === 0) {
            $this->deleteLine($token, $productId);
            $this->syncOpenCartStatus($cart, $token);

            return $this->successPayload($cart, $token, null, true);
        }

        $product = $this->findCartProduct($cart, $productId);
        $customer = Customer::query()->findOrFail((int) $cart->customer_id);

        if ((int) $product->notify_minimum_stock === 1 && (float) $product->minimum_stock > 0) {
            $minimum = (int) $product->minimum_stock;
            if ($qty % $minimum !== 0) {
                return $this->error('quantity_not_multiple', ['minimum_stock' => $minimum]);
            }
        }

        $salePrice = $this->salePriceForCart($product, $customer);
        $this->upsertLine($cart, $token, $productId, $qty, $salePrice, $comment);
        $this->syncOpenCartStatus($cart, $token);

        $line = $this->fetchLine($token, $productId);

        return $this->successPayload($cart, $token, $line, false);
    }

    /**
     * @return array<string, mixed>
     */
    public function incrementLine(stdClass $cart, string $token, int $productId): array
    {
        $product = $this->findCartProduct($cart, $productId);
        $customer = Customer::query()->findOrFail((int) $cart->customer_id);
        $salePrice = $this->salePriceForCart($product, $customer);

        $minimum = (float) $product->minimum_stock > 0 ? (int) $product->minimum_stock : 1;
        $existing = $this->fetchLine($token, $productId);
        $newQty = $existing !== null ? $minimum + (int) $existing['qty'] : $minimum;

        $this->upsertLine($cart, $token, $productId, $newQty, $salePrice, $existing['comment'] ?? null);
        $this->syncOpenCartStatus($cart, $token);

        $line = $this->fetchLine($token, $productId);

        return $this->successPayload($cart, $token, $line, false);
    }

    /**
     * @return array<string, mixed>
     */
    public function removeLine(stdClass $cart, string $token, int $productId): array
    {
        $this->deleteLine($token, $productId);
        $this->syncOpenCartStatus($cart, $token);

        return $this->successPayload($cart, $token, null, true);
    }

    /**
     * @return array<string, mixed>
     */
    public function clearCart(stdClass $cart, string $token): array
    {
        if (Schema::hasTable('detail_car')) {
            DB::table('detail_car')->where('token_car', $token)->delete();
        }
        $this->syncOpenCartStatus($cart, $token);

        return $this->successPayload($cart, $token, null, true);
    }

    private function findCartProduct(stdClass $cart, int $productId): Product
    {
        $companyId = (int) ($cart->company_id ?? 0);

        return Product::query()
            ->where('company_id', $companyId)
            ->where('pro_id', $productId)
            ->firstOrFail();
    }

    private function salePriceForCart(Product $product, Customer $customer): float
    {
        if ((float) ($product->price_reduced ?? 0) > 0) {
            return (float) $product->price_reduced;
        }

        return $product->getPriceForCustomer($customer);
    }

    private function upsertLine(stdClass $cart, string $token, int $productId, int $qty, float $salePrice, ?string $comment): void
    {
        $existing = DB::table('detail_car')
            ->where('token_car', $token)
            ->where('product_id', $productId)
            ->first();

        $payload = [
            'qty' => $qty,
            'sale_price' => $salePrice,
        ];
        if ($comment !== null) {
            $payload['comment'] = $comment;
        }

        if ($existing !== null) {
            DB::table('detail_car')
                ->where('token_car', $token)
                ->where('product_id', $productId)
                ->update($payload);

            return;
        }

        DB::table('detail_car')->insert(array_merge($payload, [
            'company_id' => (int) $cart->company_id,
            'cart_id' => (int) $cart->cart_id,
            'customer_id' => (int) $cart->customer_id,
            'token_car' => $token,
            'product_id' => $productId,
            'comment' => $comment ?? '',
            'created' => now()->format('Y-m-d H:i:s'),
        ]));
    }

    private function deleteLine(string $token, int $productId): void
    {
        DB::table('detail_car')
            ->where('token_car', $token)
            ->where('product_id', $productId)
            ->delete();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchLine(string $token, int $productId): ?array
    {
        $row = DB::table('detail_car')
            ->where('token_car', $token)
            ->where('product_id', $productId)
            ->first();

        if ($row === null) {
            return null;
        }

        $qty = (int) ($row->qty ?? 0);
        $salePrice = (float) ($row->sale_price ?? 0);

        return [
            'product_id' => $productId,
            'qty' => $qty,
            'comment' => (string) ($row->comment ?? ''),
            'importe_total' => $qty * $salePrice,
            'sale_price' => $salePrice,
        ];
    }

    private function syncOpenCartStatus(stdClass $cart, string $token): void
    {
        $cartId = (int) ($cart->cart_id ?? 0);
        $companyId = (int) ($cart->company_id ?? 0);
        if ($cartId < 1) {
            return;
        }

        $statusCart = $cart->status_cart ?? null;
        $newStatus = 2;

        if ($statusCart !== null && (int) $statusCart !== 4) {
            $lineCount = Schema::hasTable('detail_car')
                ? (int) DB::table('detail_car')->where('token_car', $token)->count()
                : 0;
            $newStatus = $lineCount > 0 ? 3 : 2;
        }

        DB::table('catalog_cart')
            ->where('cart_id', $cartId)
            ->where('company_id', $companyId)
            ->update(['status_cart' => $newStatus]);
    }

    /**
     * @param array<string, mixed>|null $line
     * @return array<string, mixed>
     */
    private function successPayload(stdClass $cart, string $token, ?array $line, bool $deleted): array
    {
        $totals = $this->totalsService->totalsForCart($cart);
        $formatNumeric = $this->resolveFormatNumeric((int) ($cart->company_id ?? 0));

        $payload = [
            'success' => true,
            'deleted' => $deleted,
            'totals' => $totals,
            'detalle' => $totals,
        ];

        if ($line !== null) {
            $payload['line'] = [
                'product_id' => (int) $line['product_id'],
                'qty' => (int) $line['qty'],
                'qty_formatted' => $this->formatQty((int) $line['qty'], $formatNumeric),
                'comment' => (string) ($line['comment'] ?? ''),
                'importe_total' => $this->formatAmount((float) $line['importe_total'], $formatNumeric),
            ];
            $payload['cantidad_actual'] = (int) $line['qty'];
            $payload['cantidad_actual_formatted'] = $payload['line']['qty_formatted'];
            $payload['importe_total'] = $payload['line']['importe_total'];
            $payload['comment'] = $payload['line']['comment'];
        }

        return $payload;
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private function error(string $code, array $params = []): array
    {
        return array_merge([
            'success' => false,
            'error' => $code,
        ], $params);
    }

    private function resolveFormatNumeric(int $companyId): ?FormatNumeric
    {
        if ($companyId < 1) {
            return FormatNumeric::query()->where('default', 1)->first();
        }

        $company = \App\Models\Company::query()->find($companyId);

        return $company && $company->format_id
            ? FormatNumeric::query()->find((int) $company->format_id)
            : FormatNumeric::query()->where('default', 1)->first();
    }

    private function formatAmount(float $amount, ?FormatNumeric $formatNumeric): string
    {
        if ($formatNumeric !== null
            && isset($formatNumeric->digits, $formatNumeric->radixPoint, $formatNumeric->groupSeparator)) {
            return number_format(
                $amount,
                (int) $formatNumeric->digits,
                (string) $formatNumeric->radixPoint,
                (string) $formatNumeric->groupSeparator
            );
        }

        return number_format($amount, 2, '.', ',');
    }

    private function formatQty(int $qty, ?FormatNumeric $formatNumeric): string
    {
        if ($formatNumeric !== null && isset($formatNumeric->radixPoint, $formatNumeric->groupSeparator)) {
            return number_format(
                $qty,
                0,
                (string) $formatNumeric->radixPoint,
                (string) $formatNumeric->groupSeparator
            );
        }

        return number_format($qty, 0, '.', ',');
    }
}

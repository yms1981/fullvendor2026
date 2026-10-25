<?php

namespace App\Services;

use App\Enums\OrderStatusCodes;
use App\Models\FormatNumeric;
use App\Models\Order;
use stdClass;

final class CatalogPublicOrdersService
{
    public function __construct(
        private readonly CatalogPublicSessionService $sessionService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function ordersList(stdClass $cart, int $page, int $perPage): array
    {
        if ($this->sessionService->isExpired($cart)) {
            return [
                'success' => false,
                'error' => 'expired',
                'message' => 'El tiempo del catálogo expiró.',
            ];
        }

        $companyId = (int) ($cart->company_id ?? 0);
        $customerId = (int) ($cart->customer_id ?? 0);
        if ($companyId < 1 || $customerId < 1) {
            return ['success' => false, 'error' => 'invalid_cart', 'message' => 'Carrito inválido.'];
        }

        $perPage = min(50, max(1, $perPage));
        $page = max(1, $page);

        $query = Order::query()
            ->where('company_id', $companyId)
            ->where('customer_id', $customerId)
            ->whereRelation('statusOrder', 'cod_status', '!=', OrderStatusCodes::SHOPPING->value)
            ->with([
                'statusOrder',
                'seller.user:id,first_name,last_name,name',
                'company.format_numeric',
            ])
            ->orderByDesc('created');

        $cartUserId = (int) ($cart->user_id ?? 0);
        if ($cartUserId > 0) {
            $query->whereHas('seller', fn ($q) => $q->where('user_id', $cartUserId));
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);
        $languageId = (int) ($paginator->first()?->language_id ?? 1);

        $data = [];
        foreach ($paginator->items() as $order) {
            /** @var Order $order */
            $data[] = $this->formatOrderListRow($order, $languageId);
        }

        return [
            'success' => true,
            'data' => $data,
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function orderDetail(stdClass $cart, int $orderId): array
    {
        if ($this->sessionService->isExpired($cart)) {
            return [
                'success' => false,
                'error' => 'expired',
                'message' => 'El tiempo del catálogo expiró.',
            ];
        }

        if ($orderId < 1) {
            return ['success' => false, 'error' => 'order_not_found', 'message' => 'Orden no encontrada.'];
        }

        $order = Order::query()
            ->with([
                'statusOrder',
                'seller.user:id,first_name,last_name,name',
                'company.format_numeric',
                'company.formatDate',
                'company.currencyModel',
                'customer',
                'address.city',
                'address.state',
                'address.country',
                'order_details.product.images',
                'order_details.product.default_image',
                'order_details.product.unit',
            ])
            ->find($orderId);

        if ($order === null || ! $this->orderBelongsToCart($order, $cart)) {
            return ['success' => false, 'error' => 'order_not_found', 'message' => 'Orden no encontrada.'];
        }

        $totals = $order->getTotals(false);
        $totalsFormatted = $order->getTotals(true);
        $formatNumeric = $order->company?->format_numeric ?? FormatNumeric::default();
        $languageId = (int) ($order->language_id ?? 1);

        return [
            'success' => true,
            'order' => $this->formatOrderDetail($order, $totals, $totalsFormatted, $formatNumeric, $languageId),
        ];
    }

    private function orderBelongsToCart(Order $order, stdClass $cart): bool
    {
        $companyId = (int) ($cart->company_id ?? 0);
        $customerId = (int) ($cart->customer_id ?? 0);
        if ((int) $order->company_id !== $companyId || (int) $order->customer_id !== $customerId) {
            return false;
        }

        $cartUserId = (int) ($cart->user_id ?? 0);
        if ($cartUserId < 1) {
            return true;
        }

        $sellerUserId = (int) ($order->seller?->user_id ?? 0);

        return $sellerUserId === $cartUserId;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatOrderListRow(Order $order, int $languageId): array
    {
        $totals = $order->getTotals(true);
        $status = $order->statusOrder;
        $sellerName = $this->sellerDisplayName($order);

        return [
            'order_id' => (int) $order->order_id,
            'order_number' => (string) ($order->order_number ?? ''),
            'source' => (string) ($order->source ?? ''),
            'created' => $order->created?->format('Y-m-d H:i:s'),
            'total' => (string) ($totals['total_orden'] ?? '0'),
            'total_amount' => (float) ($order->total_amount ?? $order->total ?? 0),
            'vendedor' => $sellerName,
            'status_order' => $status ? [
                'color' => (string) ($status->color ?? '#6c757d'),
                'name_status_english' => $this->statusLabel($status, $languageId),
                'name_status_spanish' => (string) ($status->name_status_spanish ?? ''),
                'cod_status' => (int) ($status->cod_status ?? 0),
            ] : null,
        ];
    }

    /**
     * @param array<string, mixed> $totals
     * @param array<string, mixed> $totalsFormatted
     * @return array<string, mixed>
     */
    private function formatOrderDetail(
        Order $order,
        array $totals,
        array $totalsFormatted,
        FormatNumeric $formatNumeric,
        int $languageId,
    ): array {
        $status = $order->statusOrder;
        $details = [];
        foreach ($order->order_details ?? [] as $detail) {
            $product = $detail->product;
            $images = [];
            if ($product !== null) {
                foreach ($product->images ?? [] as $image) {
                    $images[] = [
                        'uuid' => (string) ($image->uuid ?? $image->name ?? ''),
                        'name' => (string) ($image->name ?? ''),
                        'type' => (string) ($image->type ?? ''),
                    ];
                }
            }

            $details[] = [
                'detail_id' => (int) $detail->detail_id,
                'product_id' => (int) $detail->product_id,
                'qty' => (float) $detail->qty,
                'sale_price' => (float) $detail->sale_price,
                'total_amount' => (float) ($detail->qty * $detail->sale_price),
                'comments' => $detail->comments,
                'comment_producto' => $detail->comments,
                'delivered_quantity' => (float) ($detail->delivered_quantity ?? 0),
                'modify_date' => $detail->modify_date,
                'fixed_pricedate' => $detail->fixed_pricedate,
                'product' => $product ? [
                    'pro_id' => (int) $product->pro_id,
                    'sku' => (string) ($product->sku ?? ''),
                    'name' => (string) ($product->name ?? ''),
                    'stock' => $product->stock,
                    'status' => (int) ($product->status ?? 1),
                    'barcode' => $product->barcode,
                    'images' => $images,
                    'default_image' => $product->default_image ? [
                        'uuid' => (string) ($product->default_image->uuid ?? $product->default_image->name ?? ''),
                    ] : null,
                ] : null,
            ];
        }

        return [
            'order_id' => (int) $order->order_id,
            'order_number' => (string) ($order->order_number ?? ''),
            'source' => (string) ($order->source ?? ''),
            'created' => $order->created?->format('Y-m-d H:i:s'),
            'updated' => $order->updated?->format('Y-m-d H:i:s'),
            'company_id' => (int) $order->company_id,
            'customer_id' => (int) $order->customer_id,
            'user_id' => (int) ($order->seller?->user_id ?? 0),
            'amount' => (float) ($totals['sub_total'] ?? 0),
            'subtotal' => (float) ($totals['sub_total'] ?? 0),
            'discount' => (float) ($totals['discount_total'] ?? 0),
            'descuento' => (float) ($totals['discount_total'] ?? 0),
            'total' => (float) ($totals['total_orden'] ?? 0),
            'total_amount' => (float) ($totals['total_orden'] ?? 0),
            'total_formatted' => (string) ($totalsFormatted['total_orden'] ?? ''),
            'sub_total_formatted' => (string) ($totalsFormatted['sub_total'] ?? ''),
            'discount_total_formatted' => (string) ($totalsFormatted['discount_total'] ?? ''),
            'order_details' => $details,
            'status_order' => $status ? [
                'color' => (string) ($status->color ?? '#6c757d'),
                'name_status_english' => $this->statusLabel($status, $languageId),
                'name_status_spanish' => (string) ($status->name_status_spanish ?? ''),
                'cod_status' => (int) ($status->cod_status ?? 0),
            ] : null,
            'seller' => $order->seller ? [
                'user_id' => (int) ($order->seller->user_id ?? 0),
                'first_name' => (string) ($order->seller->user->first_name ?? ''),
                'last_name' => (string) ($order->seller->user->last_name ?? ''),
            ] : null,
            'address' => $order->address,
            'format_numeric' => [
                'digits' => (int) ($formatNumeric->digits ?? 2),
                'radixPoint' => (string) ($formatNumeric->radixPoint ?? '.'),
                'groupSeparator' => (string) ($formatNumeric->groupSeparator ?? ','),
            ],
        ];
    }

    private function sellerDisplayName(Order $order): string
    {
        $user = $order->seller?->user;
        if ($user === null) {
            return '';
        }
        $first = trim((string) ($user->first_name ?? ''));
        $last = trim((string) ($user->last_name ?? ''));
        $full = trim($first . ' ' . $last);
        if ($full !== '') {
            return $full;
        }

        return trim((string) ($user->name ?? ''));
    }

    private function statusLabel($status, int $languageId): string
    {
        if ($languageId === 2) {
            return trim((string) ($status->name_status_spanish ?: $status->name_status_english ?: ''));
        }

        return trim((string) ($status->name_status_english ?: $status->name_status_spanish ?: ''));
    }
}

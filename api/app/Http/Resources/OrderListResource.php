<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Incluye todas las propiedades de la orden, cliente, seller, warehouse (si existe),
     * nombre del estado y productos con descripciones, unidad de medida y sku.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $order = $this->resource;

        $rawOrderStatusCol = $order->getAttributes()['order_status'] ?? null;

        $data = $order->toArray();

        $data['customer'] = $order->relationLoaded('customer') && $order->customer
            ? $order->customer->toArray()
            : null;

        $data['seller'] = $order->relationLoaded('seller') && $order->seller
            ? $order->seller->toArray()
            : null;

        // Almacenero = users.id en warehouse_user_id (o warehouse_id legado).
        $warehouseUserDisplay = null;
        $warehouseUserPayload = null;
        $warehouseUserId = (int) ($order->warehouse_user_id ?? 0);
        if ($warehouseUserId <= 0) {
            $warehouseUserId = (int) ($order->warehouse_id ?? 0);
        }

        if (method_exists($order, 'warehouse_user') && $order->relationLoaded('warehouse_user') && $order->warehouse_user) {
            $wu = $order->warehouse_user;
            $wuUser = ($wu->relationLoaded('user') && $wu->user) ? $wu->user : null;
            if ($wuUser === null && (int) ($wu->user_id ?? 0) > 0) {
                $wuUser = \App\Models\User::query()->find((int) $wu->user_id);
            }
            $nm = $wuUser ? trim((string) ($wuUser->name ?? '')) : '';
            $em = $wuUser ? trim((string) ($wuUser->email ?? '')) : '';
            if ($nm !== '' || $em !== '') {
                $warehouseUserDisplay = $nm !== '' ? $nm : $em;
            }
            $warehouseUserPayload = [
                'user_id' => (int) ($wu->user_id ?? $warehouseUserId),
                'name' => $nm !== '' ? $nm : null,
                'email' => $em !== '' ? $em : null,
                'first_name' => $wu->first_name ?? null,
                'last_name' => $wu->last_name ?? null,
            ];
        }

        // Fuente de verdad pedida: tabla users por el user_id del token/asignación.
        if ($warehouseUserId > 0) {
            $u = \App\Models\User::query()->find($warehouseUserId);
            if ($u) {
                $nm = trim((string) ($u->name ?? ''));
                $em = trim((string) ($u->email ?? ''));
                $fromUsers = $nm !== '' ? $nm : ($em !== '' ? $em : null);
                if ($fromUsers !== null) {
                    $warehouseUserDisplay = $fromUsers;
                }
                $warehouseUserPayload = [
                    'user_id' => $warehouseUserId,
                    'name' => $nm !== '' ? $nm : null,
                    'email' => $em !== '' ? $em : null,
                    'first_name' => null,
                    'last_name' => null,
                ];
            }
        }

        $data['warehouse_user_id'] = $warehouseUserId > 0 ? $warehouseUserId : ($order->warehouse_user_id ?? null);
        $data['warehouse_user'] = $warehouseUserPayload;
        $data['warehouse_user_display'] = $warehouseUserDisplay;

        // Columna Warehouse del listado = nombre del almacenero (users), no el depósito físico.
        $data['warehouse'] = $warehouseUserDisplay !== null
            ? ['name' => $warehouseUserDisplay]
            : null;

        $data['status_name'] = $order->relationLoaded('statusOrder') && $order->statusOrder
            ? ($order->statusOrder->name_status_english ?? $order->statusOrder->name_status_spanish ?? null)
            : null;

        // cod_status numérico como order_status (compatibilidad MVC); etiqueta en order_status_label
        $so = $order->statusOrder;
        $data['order_status'] = $so ? (int) $so->cod_status : null;
        if (is_string($rawOrderStatusCol) && $rawOrderStatusCol !== '') {
            $data['order_status_label'] = $rawOrderStatusCol;
        } elseif ($so) {
            $data['order_status_label'] = Order::translatedStatusOrderLabel($so, (int) ($order->language_id ?? 1));
        } else {
            $data['order_status_label'] = null;
        }

        $data['order_details'] = [];
        if ($order->relationLoaded('order_details')) {
            foreach ($order->order_details as $detail) {
                $product = $detail->relationLoaded('product') ? $detail->product : null;
                $data['order_details'][] = [
                    ...$detail->toArray(),
                    'product' => $product ? [
                        'pro_id' => $product->pro_id,
                        'sku' => $product->sku,
                        'name' => $product->name,
                        'descriptions' => $product->descriptions ?? null,
                        'unit' => $product->relationLoaded('unit') && $product->unit
                            ? [
                                'unit_id' => $product->unit->unit_id,
                                'unit' => $product->unit->unit,
                                'description' => $product->unit->description ?? null,
                            ]
                            : null,
                    ] : null,
                ];
            }
        }

        return $data;
    }
}

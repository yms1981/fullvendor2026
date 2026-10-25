<?php

namespace App\Services;

use App\Enums\OrderStatusCodes;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\StatusOrder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Equivalente moderno de setstatus_post (API legacy):
 * - Acepta order_id + status (cod_status)
 * - Si status == 10 (PARTIALLY_COMPLETED), lo mapea a 9 (WAREHOUSE_ASSIGNED) como el legado
 * - Actualiza orders.status_id (y sincroniza texto order_status vía modelo)
 * - Registra historial en order_status_history
 */
class SetWarehouseOrderStatusService
{
    /**
     * @param  array{order_id:int|string,status:int|string}  $input
     * @return array{
     *     order_id:int,
     *     status_id:int,
     *     cod_status:int,
     *     requested_status:int,
     *     list:array{order_status:int},
     *     where:string,
     *     update:bool
     * }
     */
    public function handle(array $input, int $companyId, ?int $userId = null): array
    {
        $orderId = (int) ($input['order_id'] ?? 0);
        $requested = $input['status'] ?? $input['cod_status'] ?? $input['order_status'] ?? null;

        if ($orderId < 1 || $requested === null || $requested === '') {
            throw new InvalidArgumentException('missing_fields');
        }

        $requestedStatus = (int) $requested;
        // Legado: status "10" se fuerza a "9" (Warehouse Assigned).
        $codStatus = $requestedStatus === OrderStatusCodes::PARTIALLY_COMPLETED->value
            ? OrderStatusCodes::WAREHOUSE_ASSIGNED->value
            : $requestedStatus;

        $order = Order::query()
            ->where('order_id', $orderId)
            ->where('company_id', $companyId)
            ->first();

        if (! $order) {
            throw new RuntimeException('order_not_found');
        }

        $status = StatusOrder::query()
            ->where('cod_status', $codStatus)
            ->first();

        if (! $status) {
            throw new RuntimeException('status_not_found');
        }

        $statusId = (int) $status->id_status;

        DB::transaction(function () use ($order, $statusId, $codStatus, $companyId, $userId) {
            $order->status_id = $statusId;
            $order->save();

            OrderStatusHistory::query()->create([
                'order_id' => $order->order_id,
                'company_id' => $companyId,
                'user_id' => $userId,
                'cod_status' => $codStatus,
            ]);
        });

        return [
            'order_id' => $orderId,
            'status_id' => $statusId,
            'cod_status' => $codStatus,
            'requested_status' => $requestedStatus,
            'list' => [
                'order_status' => $codStatus,
            ],
            'where' => 'order_id='.$orderId,
            'update' => true,
        ];
    }
}

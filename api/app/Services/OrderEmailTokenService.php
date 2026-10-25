<?php

namespace App\Services;

use App\Support\DisplayDateFormat;
use App\Enums\OrderStatusCodes;
use App\Models\Order;
use App\Models\StatusOrder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Confirmar / rechazar orden desde enlace del correo (token_pass).
 *
 * Réplica la lógica de {@see \Products} (MVC) sin acceso directo a BD desde el front controller.
 */
final class OrderEmailTokenService
{
    public const RESULT_INVALID = 'invalid';
    public const RESULT_ALREADY_CONFIRMED = 'already_confirmed';
    public const RESULT_ALREADY_REJECTED = 'already_rejected';
    public const RESULT_EXPIRED = 'expired';
    public const RESULT_CONFIRMED = 'confirmed';
    public const RESULT_REJECTED = 'rejected';

    /**
     * @return array{
     *     result: string,
     *     company_id: int|null,
     *     language_id: int|null,
     *     order_meta: array<string, string>
     * }
     */
    public function confirm(string $token): array
    {
        return $this->process($token, OrderStatusCodes::APPROVED, 'date_confirmed');
    }

    /**
     * @return array{
     *     result: string,
     *     company_id: int|null,
     *     language_id: int|null,
     *     order_meta: array<string, string>
     * }
     */
    public function reject(string $token): array
    {
        return $this->process($token, OrderStatusCodes::REJECTED, 'date_rejected_email');
    }

    /**
     * @return array{
     *     result: string,
     *     company_id: int|null,
     *     language_id: int|null,
     *     order_meta: array<string, string>
     * }
     */
    private function process(string $token, OrderStatusCodes $targetStatus, string $dateColumn): array
    {
        $order = $this->findOrderByToken($token);
        if ($order === null) {
            return $this->buildResponse(self::RESULT_INVALID);
        }

        $cod = $this->codStatusForOrder($order);

        if ($targetStatus === OrderStatusCodes::APPROVED) {
            if ($cod === OrderStatusCodes::APPROVED->value) {
                return $this->buildResponse(self::RESULT_ALREADY_CONFIRMED, $order);
            }
            if ($cod === OrderStatusCodes::REJECTED->value) {
                return $this->buildResponse(self::RESULT_ALREADY_REJECTED, $order);
            }
        } else {
            if ($cod === OrderStatusCodes::REJECTED->value) {
                return $this->buildResponse(self::RESULT_ALREADY_REJECTED, $order);
            }
            if ($cod === OrderStatusCodes::APPROVED->value) {
                return $this->buildResponse(self::RESULT_ALREADY_CONFIRMED, $order);
            }
        }

        if ($this->isExpired($order)) {
            return $this->buildResponse(self::RESULT_EXPIRED, $order);
        }

        DB::transaction(function () use ($order, $targetStatus, $dateColumn): void {
            $this->applyStatusUpdate($order, $targetStatus, [
                $dateColumn => now()->format('Y-m-d H:i:s'),
            ]);
        });

        $order->refresh();

        $result = $targetStatus === OrderStatusCodes::APPROVED
            ? self::RESULT_CONFIRMED
            : self::RESULT_REJECTED;

        return $this->buildResponse($result, $order);
    }

    private function findOrderByToken(string $token): ?Order
    {
        $token = preg_replace('/[^a-zA-Z0-9_-]/', '', $token) ?? '';
        if ($token === '') {
            return null;
        }

        if (! Schema::hasColumn('orders', 'token_pass')) {
            return null;
        }

        return Order::query()
            ->with(['company', 'customer', 'statusOrder'])
            ->where('token_pass', $token)
            ->first();
    }

    public function codStatusForOrder(Order $order): int
    {
        if (Schema::hasColumn('orders', 'status_id')
            && $order->status_id !== null
            && $order->status_id !== '') {
            $status = $order->relationLoaded('statusOrder')
                ? $order->statusOrder
                : StatusOrder::query()->where('id_status', $order->status_id)->first();

            return $status ? (int) $status->cod_status : 0;
        }

        if (Schema::hasColumn('orders', 'order_status') && isset($order->order_status) && is_numeric($order->order_status)) {
            return (int) $order->order_status;
        }

        return 0;
    }

    private function isExpired(Order $order): bool
    {
        if (empty($order->date_expired_email)) {
            return false;
        }

        try {
            return Carbon::parse((string) $order->date_expired_email)->lt(now());
        } catch (\Throwable) {
            return strtotime((string) $order->date_expired_email) < time();
        }
    }

    /**
     * @param array<string, mixed> $extra
     */
    private function applyStatusUpdate(Order $order, OrderStatusCodes $codStatus, array $extra): void
    {
        $payload = $extra;

        if (Schema::hasColumn('orders', 'order_status')) {
            $payload['order_status'] = $codStatus->value;
        }

        if (Schema::hasColumn('orders', 'status_id')) {
            $statusId = StatusOrder::getIdByCode($codStatus);
            if ($statusId !== false) {
                $payload['status_id'] = (int) $statusId;
            }
        }

        if ($payload !== []) {
            $order->update($payload);
        }
    }

    /**
     * @return array{
     *     result: string,
     *     company_id: int|null,
     *     language_id: int|null,
     *     order_meta: array<string, string>
     * }
     */
    private function buildResponse(string $result, ?Order $order = null): array
    {
        if ($order === null) {
            return [
                'result' => $result,
                'company_id' => null,
                'language_id' => null,
                'order_meta' => [],
            ];
        }

        return [
            'result' => $result,
            'company_id' => (int) ($order->company_id ?? 0) ?: null,
            'language_id' => $this->resolveLanguageId($order),
            'order_meta' => $this->buildOrderMeta($order),
        ];
    }

    private function resolveLanguageId(Order $order): ?int
    {
        if ($order->language_id) {
            return (int) $order->language_id;
        }
        if ($order->company && $order->company->language_id) {
            return (int) $order->company->language_id;
        }

        return null;
    }

    /**
     * Metadatos para la vista pública (sin traducir el estado; el MVC usa cmp-orders).
     *
     * @return array<string, string>
     */
    private function buildOrderMeta(Order $order): array
    {
        $companyName = '';
        if ($order->company && ! empty($order->company->name)) {
            $companyName = (string) $order->company->name;
        }

        $customerName = '';
        if ($order->customer) {
            $name = trim((string) ($order->customer->name ?? ''));
            $biz = trim((string) ($order->customer->business_name ?? ''));
            $customerName = trim($name . ($biz !== '' ? ' (' . $biz . ')' : ''));
        }

        $cod = $this->codStatusForOrder($order);
        $statusKey = 'pending';
        if ($cod === OrderStatusCodes::APPROVED->value) {
            $statusKey = 'approved';
        } elseif ($cod === OrderStatusCodes::REJECTED->value) {
            $statusKey = 'rechazado';
        }

        $dateRaw = '';
        if (! empty($order->date_confirmed)) {
            $dateRaw = (string) $order->date_confirmed;
        } elseif (! empty($order->date_rejected_email)) {
            $dateRaw = (string) $order->date_rejected_email;
        } elseif (! empty($order->updated)) {
            $dateRaw = (string) $order->updated;
        } elseif (! empty($order->created)) {
            $dateRaw = (string) $order->created;
        }
        $dateText = $dateRaw !== '' ? DisplayDateFormat::formatDateTime($dateRaw) : '';

        return [
            'order_number' => isset($order->order_number) ? (string) $order->order_number : '',
            'customer' => $customerName,
            'company' => $companyName,
            'status_key' => $statusKey,
            'date' => $dateText,
        ];
    }
}

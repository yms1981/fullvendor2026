<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Customer;
use App\Models\MobileChangeLog;
use App\Models\MobileDevice;
use App\Models\MobileSyncLog;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Registro de dispositivos móviles y decisión sync total vs parcial.
 * Umbral: si nunca sincronizó o hay > TOTAL_SYNC_THRESHOLD cambios → total.
 */
final class MobileSyncService
{
    public const TOTAL_SYNC_THRESHOLD = 500;

    /**
     * Registra o actualiza el dispositivo y la última utilización.
     *
     * @param  array<string, mixed>  $payload
     * @return array{device: MobileDevice, created: bool}
     */
    public function registerOrTouch(User $user, array $payload): array
    {
        $uuid = trim((string) ($payload['device_uuid'] ?? $payload['device_id'] ?? ''));
        if ($uuid === '') {
            throw new \InvalidArgumentException('device_uuid_required');
        }

        $now = now();
        $attrs = [
            'user_id' => (int) $user->id,
            'company_id' => isset($payload['company_id']) && $payload['company_id'] !== ''
                ? (int) $payload['company_id']
                : null,
            'device_name' => $payload['device_name'] ?? null,
            'device_type' => $payload['device_type'] ?? null,
            'os_version' => $payload['os_version'] ?? null,
            'app_version' => $payload['app_version'] ?? null,
            'model' => $payload['model'] ?? null,
            'manufacturer' => $payload['manufacturer'] ?? null,
            'push_token' => $payload['push_token'] ?? null,
            'last_used_at' => $now,
        ];

        /** @var MobileDevice|null $device */
        $device = MobileDevice::withTrashed()->where('device_uuid', $uuid)->first();
        if ($device) {
            if ($device->trashed()) {
                $device->restore();
            }
            // Conservar company_id si no viene en el payload.
            if (! array_key_exists('company_id', $payload) || $payload['company_id'] === null || $payload['company_id'] === '') {
                unset($attrs['company_id']);
            }
            foreach (['device_name', 'device_type', 'os_version', 'app_version', 'model', 'manufacturer', 'push_token'] as $key) {
                if (! array_key_exists($key, $payload) || $payload[$key] === null || $payload[$key] === '') {
                    unset($attrs[$key]);
                }
            }
            $device->fill($attrs);
            $device->save();

            return ['device' => $device->fresh(), 'created' => false];
        }

        $attrs['device_uuid'] = $uuid;

        return ['device' => MobileDevice::create($attrs), 'created' => true];
    }

    /**
     * Evalúa si el dispositivo debe hacer sync total o parcial.
     *
     * @return array{
     *   sync_mode: string,
     *   is_total: bool,
     *   is_partial: bool,
     *   never_synced: bool,
     *   changes_count: int,
     *   threshold: int,
     *   since_at: string|null,
     *   groups: array<string, int>,
     *   sync_log_id: int,
     *   device: MobileDevice
     * }
     */
    public function evaluateSync(MobileDevice $device, ?int $companyId = null): array
    {
        $companyId = $companyId ?: (int) ($device->company_id ?? 0);
        if ($companyId <= 0) {
            throw new \InvalidArgumentException('company_id_required');
        }

        if ((int) $device->company_id !== $companyId) {
            $device->company_id = $companyId;
        }
        $device->last_used_at = now();
        $device->save();

        $since = $device->last_sync_at;
        $neverSynced = $since === null;
        $groups = [
            MobileChangeLog::GROUP_PRODUCTS => 0,
            MobileChangeLog::GROUP_CUSTOMERS => 0,
            MobileChangeLog::GROUP_CATEGORIES => 0,
            MobileChangeLog::GROUP_ORDERS => 0,
        ];

        $totalChanges = 0;
        if (! $neverSynced) {
            $rows = MobileChangeLog::query()
                ->select('entity_group', DB::raw('COUNT(*) as cnt'))
                ->where('company_id', $companyId)
                ->where('changed_at', '>', $since)
                ->groupBy('entity_group')
                ->pluck('cnt', 'entity_group');

            foreach ($groups as $group => $_) {
                $groups[$group] = (int) ($rows[$group] ?? 0);
            }
            $totalChanges = array_sum($groups);
        }

        $isTotal = $neverSynced || $totalChanges > self::TOTAL_SYNC_THRESHOLD;
        $mode = $isTotal ? 'total' : 'partial';

        $log = MobileSyncLog::create([
            'mobile_device_id' => $device->id,
            'company_id' => $companyId,
            'sync_mode' => $mode,
            'changes_count' => $neverSynced ? -1 : $totalChanges,
            'changes_products' => $groups[MobileChangeLog::GROUP_PRODUCTS],
            'changes_customers' => $groups[MobileChangeLog::GROUP_CUSTOMERS],
            'changes_categories' => $groups[MobileChangeLog::GROUP_CATEGORIES],
            'changes_orders' => $groups[MobileChangeLog::GROUP_ORDERS],
            'since_at' => $since,
            'status' => 'started',
            'synced_at' => now(),
        ]);

        return [
            'sync_mode' => $mode,
            'is_total' => $isTotal,
            'is_partial' => ! $isTotal,
            'never_synced' => $neverSynced,
            'changes_count' => $neverSynced ? null : $totalChanges,
            'threshold' => self::TOTAL_SYNC_THRESHOLD,
            'since_at' => $since?->toIso8601String(),
            'groups' => $groups,
            'sync_log_id' => (int) $log->id,
            'endpoints' => [
                'products' => '/api/v3/movilsync/products',
                'customers' => '/api/v3/movilsync/customers',
                'categories' => '/api/v3/movilsync/categories',
                'orders' => '/api/v3/movilsync/orders',
                'complete' => '/api/v3/movilsync/complete',
            ],
            'device' => $device->fresh(),
        ];
    }

    public function completeSync(MobileDevice $device, ?int $syncLogId = null): MobileDevice
    {
        $now = now();
        $device->last_sync_at = $now;
        $device->last_used_at = $now;
        $device->save();

        $query = MobileSyncLog::query()->where('mobile_device_id', $device->id);
        if ($syncLogId) {
            $query->where('id', $syncLogId);
        } else {
            $query->where('status', 'started')->orderByDesc('id');
        }
        $log = $query->first();
        if ($log) {
            $log->status = 'completed';
            $log->completed_at = $now;
            $log->save();
        }

        return $device->fresh();
    }

    /**
     * Registra un cambio de entidad para sync parcial.
     */
    public function recordChange(int $companyId, string $group, int $entityId, string $action): void
    {
        if ($companyId <= 0 || $entityId <= 0 || ! in_array($group, MobileChangeLog::GROUPS, true)) {
            return;
        }
        if (! in_array($action, [
            MobileChangeLog::ACTION_CREATED,
            MobileChangeLog::ACTION_UPDATED,
            MobileChangeLog::ACTION_DELETED,
        ], true)) {
            return;
        }

        MobileChangeLog::create([
            'company_id' => $companyId,
            'entity_group' => $group,
            'entity_id' => $entityId,
            'action' => $action,
            'changed_at' => now(),
        ]);
    }

    /**
     * Cambios de un grupo desde last_sync_at del dispositivo (+ payloads).
     *
     * @return array{since_at: string|null, items: list<array<string, mixed>>}
     */
    public function partialChanges(MobileDevice $device, string $group, ?int $companyId = null): array
    {
        if (! in_array($group, MobileChangeLog::GROUPS, true)) {
            throw new \InvalidArgumentException('invalid_entity_group');
        }

        $companyId = $companyId ?: (int) ($device->company_id ?? 0);
        if ($companyId <= 0) {
            throw new \InvalidArgumentException('company_id_required');
        }

        $since = $device->last_sync_at;
        if ($since === null) {
            throw new \InvalidArgumentException('requires_total_sync');
        }

        $device->last_used_at = now();
        $device->save();

        $changes = MobileChangeLog::query()
            ->where('company_id', $companyId)
            ->where('entity_group', $group)
            ->where('changed_at', '>', $since)
            ->orderBy('changed_at')
            ->orderBy('id')
            ->get();

        // Última acción por entity_id (si hubo create+update, queda updated; si deleted al final, deleted).
        $latestByEntity = [];
        foreach ($changes as $row) {
            $latestByEntity[(int) $row->entity_id] = $row;
        }

        $ids = array_keys($latestByEntity);
        $records = $this->loadEntities($group, $companyId, $ids);

        $items = [];
        foreach ($latestByEntity as $entityId => $change) {
            $action = (string) $change->action;
            $record = $records->get($entityId);
            if ($action === MobileChangeLog::ACTION_DELETED || $record === null) {
                $items[] = [
                    'entity_id' => $entityId,
                    'action' => MobileChangeLog::ACTION_DELETED,
                    'changed_at' => $change->changed_at?->toIso8601String(),
                    'data' => null,
                ];
                continue;
            }
            $items[] = [
                'entity_id' => $entityId,
                'action' => $action,
                'changed_at' => $change->changed_at?->toIso8601String(),
                'data' => $record,
            ];
        }

        return [
            'group' => $group,
            'since_at' => $since->toIso8601String(),
            'count' => count($items),
            'items' => $items,
        ];
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, array<string, mixed>>
     */
    protected function loadEntities(string $group, int $companyId, array $ids): Collection
    {
        if ($ids === []) {
            return collect();
        }

        return match ($group) {
            MobileChangeLog::GROUP_PRODUCTS => Product::query()
                ->where('company_id', $companyId)
                ->whereIn('pro_id', $ids)
                ->get()
                ->keyBy('pro_id')
                ->map(fn (Product $p) => $this->mapProduct($p)),
            MobileChangeLog::GROUP_CUSTOMERS => Customer::query()
                ->where('company_id', $companyId)
                ->whereIn('customer_id', $ids)
                ->get()
                ->keyBy('customer_id')
                ->map(fn (Customer $c) => $this->mapCustomer($c)),
            MobileChangeLog::GROUP_CATEGORIES => Category::query()
                ->where('company_id', $companyId)
                ->whereIn('cat_id', $ids)
                ->get()
                ->keyBy('cat_id')
                ->map(fn (Category $c) => $this->mapCategory($c)),
            MobileChangeLog::GROUP_ORDERS => Order::query()
                ->where('company_id', $companyId)
                ->whereIn('order_id', $ids)
                ->get()
                ->keyBy('order_id')
                ->map(fn (Order $o) => $this->mapOrder($o)),
            default => collect(),
        };
    }

    /** @return array<string, mixed> */
    protected function mapProduct(Product $p): array
    {
        return [
            'pro_id' => (int) $p->pro_id,
            'company_id' => (int) $p->company_id,
            'sku' => $p->sku,
            'name' => $p->name,
            'sale_price' => $p->sale_price,
            'status' => $p->status,
            'default_image_id' => $p->default_image_id,
            'updated_at' => optional($p->updated_at)->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    protected function mapCustomer(Customer $c): array
    {
        return [
            'customer_id' => (int) $c->customer_id,
            'company_id' => (int) $c->company_id,
            'name' => $c->name ?? null,
            'email' => $c->email ?? null,
            'phone' => $c->phone_number ?? $c->phone ?? null,
            'status' => $c->status ?? null,
            'updated_at' => optional($c->updated_at)->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    protected function mapCategory(Category $c): array
    {
        return [
            'cat_id' => (int) $c->cat_id,
            'company_id' => (int) $c->company_id,
            'category_name' => $c->category_name,
            'category_status' => $c->category_status,
            'category_default' => $c->category_default,
            'updated_at' => optional($c->updated_at)->toIso8601String(),
        ];
    }

    /** @return array<string, mixed> */
    protected function mapOrder(Order $o): array
    {
        return [
            'order_id' => (int) $o->order_id,
            'company_id' => (int) ($o->company_id ?? 0),
            'customer_id' => $o->customer_id ?? null,
            'order_status' => $o->order_status ?? $o->status ?? null,
            'total' => $o->total ?? null,
            'created' => optional($o->created)->toIso8601String(),
            'updated_at' => optional($o->updated_at)->toIso8601String(),
        ];
    }

    public function findDeviceForUser(User $user, string $deviceUuid): ?MobileDevice
    {
        return MobileDevice::query()
            ->where('device_uuid', $deviceUuid)
            ->where('user_id', $user->id)
            ->first();
    }
}

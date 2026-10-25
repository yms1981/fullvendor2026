<?php

namespace App\Observers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\MobileChangeLog;
use App\Models\Order;
use App\Models\Product;
use App\Services\MobileSyncService;
use Illuminate\Database\Eloquent\Model;

/**
 * Registra create/update/delete en mobile_change_log para sync parcial.
 */
class MobileChangeLogObserver
{
    public function __construct(private readonly MobileSyncService $sync)
    {
    }

    public function created(Model $model): void
    {
        $this->record($model, MobileChangeLog::ACTION_CREATED);
    }

    public function updated(Model $model): void
    {
        $this->record($model, MobileChangeLog::ACTION_UPDATED);
    }

    public function deleted(Model $model): void
    {
        $this->record($model, MobileChangeLog::ACTION_DELETED);
    }

    public function forceDeleted(Model $model): void
    {
        $this->record($model, MobileChangeLog::ACTION_DELETED);
    }

    protected function record(Model $model, string $action): void
    {
        [$group, $entityId, $companyId] = $this->resolve($model);
        if ($group === null || $entityId <= 0 || $companyId <= 0) {
            return;
        }
        $this->sync->recordChange($companyId, $group, $entityId, $action);
    }

    /**
     * @return array{0: string|null, 1: int, 2: int}
     */
    protected function resolve(Model $model): array
    {
        if ($model instanceof Product) {
            return [MobileChangeLog::GROUP_PRODUCTS, (int) $model->pro_id, (int) ($model->company_id ?? 0)];
        }
        if ($model instanceof Customer) {
            return [MobileChangeLog::GROUP_CUSTOMERS, (int) $model->customer_id, (int) ($model->company_id ?? 0)];
        }
        if ($model instanceof Category) {
            return [MobileChangeLog::GROUP_CATEGORIES, (int) $model->cat_id, (int) ($model->company_id ?? 0)];
        }
        if ($model instanceof Order) {
            return [MobileChangeLog::GROUP_ORDERS, (int) $model->order_id, (int) ($model->company_id ?? 0)];
        }

        return [null, 0, 0];
    }
}

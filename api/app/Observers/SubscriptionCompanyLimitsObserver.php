<?php

namespace App\Observers;

use App\Models\Subscription;
use App\Services\CompanyPlanSyncService;

/**
 * Cualquier alta/actualización/baja de subscriptions vía Eloquent recalcula los límites
 * en companies.max_number_of_* desde el plan de la suscripción activa.
 */
class SubscriptionCompanyLimitsObserver
{
    /** @var CompanyPlanSyncService */
    private $sync;

    public function __construct(CompanyPlanSyncService $sync)
    {
        $this->sync = $sync;
    }

    public function created(Subscription $subscription): void
    {
        $this->refresh($subscription);
    }

    public function updated(Subscription $subscription): void
    {
        $this->refresh($subscription);

        $originalCompany = (int) ($subscription->getOriginal('company_id') ?? 0);
        $currentCompany = (int) ($subscription->company_id ?? 0);
        if ($originalCompany > 0 && $originalCompany !== $currentCompany) {
            $this->sync->syncFromActiveSubscription($originalCompany);
        }
    }

    public function deleted(Subscription $subscription): void
    {
        $this->refresh($subscription);
    }

    private function refresh(Subscription $subscription): void
    {
        $companyId = (int) ($subscription->company_id ?? 0);
        if ($companyId < 1) {
            return;
        }
        $this->sync->syncFromActiveSubscription($companyId);
    }
}

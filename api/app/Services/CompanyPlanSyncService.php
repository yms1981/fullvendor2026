<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Mantiene companies.max_number_of_* alineado con el plan de la suscripción activa.
 *
 * Reglas:
 *   - Si la empresa tiene una suscripción con status=1 → se copian max_users, max_clients, max_orders.
 *   - Si no hay suscripción activa → los valores en companies no se tocan (se conservan los actuales).
 */
final class CompanyPlanSyncService
{
    /**
     * Sincroniza la empresa con su suscripción activa más reciente.
     *
     * @return bool true si actualizó companies; false si no había nada que sincronizar.
     */
    public function syncFromActiveSubscription(int $companyId): bool
    {
        if ($companyId < 1) {
            return false;
        }

        if (! Schema::hasTable('subscriptions') || ! Schema::hasTable('plan')) {
            return false;
        }

        $subscription = $this->loadActiveSubscription($companyId);
        if ($subscription === null) {
            return false;
        }

        $plan = Plan::query()->find((int) $subscription->plan_id);
        if ($plan === null) {
            return false;
        }

        return $this->applyPlanLimitsToCompany($companyId, $plan);
    }

    private function loadActiveSubscription(int $companyId): ?Subscription
    {
        return Subscription::query()
            ->where('company_id', $companyId)
            ->where('status', 1)
            ->orderByDesc('subscription_id')
            ->first();
    }

    private function applyPlanLimitsToCompany(int $companyId, Plan $plan): bool
    {
        $payload = [];
        if (Schema::hasColumn('companies', 'max_number_of_clients')) {
            $payload['max_number_of_clients'] = (int) ($plan->max_clients ?? 0);
        }
        if (Schema::hasColumn('companies', 'max_number_of_orders')) {
            $payload['max_number_of_orders'] = (int) ($plan->max_orders ?? 0);
        }
        if (Schema::hasColumn('companies', 'max_number_of_vendors')) {
            $payload['max_number_of_vendors'] = (int) ($plan->max_users ?? 0);
        }
        if ($payload === []) {
            return false;
        }

        try {
            $affected = Company::query()
                ->where('company_id', $companyId)
                ->update($payload);
        } catch (\Throwable $e) {
            Log::error('[CompanyPlanSyncService] update failed', [
                'company_id' => $companyId,
                'payload' => $payload,
                'message' => $e->getMessage(),
            ]);

            return false;
        }

        return $affected > 0;
    }
}

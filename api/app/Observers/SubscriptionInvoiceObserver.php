<?php

namespace App\Observers;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Al crear una subscription local, genera una invoice local enlazada a la company
 * (para tener registro local; los campos stripe_* permanecen vacíos hasta que la
 * factura se cree/sincronice en Stripe).
 */
class SubscriptionInvoiceObserver
{
    public function created(Subscription $subscription): void
    {
        if (! Schema::hasTable('invoices')) {
            return;
        }

        $companyId = (int) ($subscription->company_id ?? 0);
        $planId = (int) ($subscription->plan_id ?? 0);
        if ($companyId < 1 || $planId < 1) {
            return;
        }

        $subscriptionRowId = (int) ($subscription->getKey() ?? 0);
        if ($subscriptionRowId < 1) {
            return;
        }

        try {
            $company = Company::query()->find($companyId);
            $plan = Plan::query()->find($planId);
            if ($company === null || $plan === null) {
                return;
            }

            // Si ya existe alguna invoice para esta subscription, no duplicar.
            $exists = DB::table('invoices')
                ->where('subscription_id', $subscriptionRowId)
                ->exists();
            if ($exists) {
                return;
            }

            $payload = $this->buildInvoicePayload($subscriptionRowId, $company, $plan, $subscription);
            $payload = $this->filterPayloadForInvoices($payload);

            Invoice::query()->create($payload);
        } catch (\Throwable $e) {
            Log::error('[SubscriptionInvoiceObserver] Failed to create local invoice for subscription', [
                'subscription_id' => $subscriptionRowId,
                'company_id' => $companyId,
                'plan_id' => $planId,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array<string, int|string|float|null>
     */
    private function buildInvoicePayload(int $subscriptionRowId, Company $company, Plan $plan, Subscription $subscription): array
    {
        $now = date('Y-m-d H:i:s');
        $startDate = $this->resolveStartDate($subscription);
        $days = (int) ($plan->days ?? 0);
        $dateExpired = null;
        if ($days > 0) {
            $dateExpired = date('Y-m-d H:i:s', strtotime($startDate . ' +' . $days . ' days'));
        } elseif (! empty($subscription->end_period_date)) {
            $dateExpired = (string) $subscription->end_period_date;
        }

        $price = (float) ($plan->price ?? 0);
        $planTitle = trim((string) ($plan->title ?? ''));
        if ($planTitle === '') {
            $planTitle = 'Plan #' . (int) ($plan->plan_id ?? 0);
        }
        $companyName = trim((string) ($company->name ?? '')) ?: ('Company #' . (int) $company->company_id);

        $concept = sprintf('Subscription #%d · %s (%s)', $subscriptionRowId, $planTitle, $companyName);

        $payload = [
            'subscription_id' => $subscriptionRowId,
            'company_id' => (int) $company->company_id,
            'date' => $startDate,
            'date_expired' => $dateExpired,
            'paid' => 0,
            'days' => $days > 0 && $days <= 255 ? $days : null,
            'concept' => $concept,
            'invoice_paid' => 0,
            'debt' => $price,
            'stripe_status' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $entity = $this->loadIssuingEntity();
        if ($entity !== null) {
            $payload['issuing_entity'] = $entity['name'];
            $payload['address_entity'] = $entity['address'];
            $payload['country_entity'] = $entity['country'];
            $payload['state_entity'] = $entity['state'];
            $payload['city_entity'] = $entity['city'];
        }

        return $payload;
    }

    private function resolveStartDate(Subscription $subscription): string
    {
        $raw = trim((string) ($subscription->start_period_date ?? ''));
        if ($raw !== '') {
            return strpos($raw, ' ') === false ? ($raw . ' 00:00:00') : $raw;
        }

        return date('Y-m-d H:i:s');
    }

    /**
     * @return array{name: ?string, address: ?string, country: ?string, state: ?string, city: ?string}|null
     */
    private function loadIssuingEntity(): ?array
    {
        if (! Schema::hasTable('issuing_entity')) {
            return null;
        }
        $row = DB::table('issuing_entity')->orderBy('entity_id', 'asc')->first();
        if ($row === null) {
            return null;
        }

        return [
            'name' => isset($row->entity_name) ? (string) $row->entity_name : null,
            'address' => isset($row->entity_address) ? (string) $row->entity_address : null,
            'country' => isset($row->entity_country) ? (string) $row->entity_country : null,
            'state' => isset($row->entity_state) ? (string) $row->entity_state : null,
            'city' => isset($row->entity_city) ? (string) $row->entity_city : null,
        ];
    }

    /**
     * @param array<string, int|string|float|null> $payload
     *
     * @return array<string, int|string|float|null>
     */
    private function filterPayloadForInvoices(array $payload): array
    {
        $out = [];
        foreach ($payload as $col => $val) {
            if (Schema::hasColumn('invoices', $col)) {
                $out[$col] = $val;
            }
        }

        return $out;
    }
}

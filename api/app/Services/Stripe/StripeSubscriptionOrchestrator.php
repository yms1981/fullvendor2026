<?php

namespace App\Services\Stripe;

use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Stripe\Exception\ApiErrorException;

class StripeSubscriptionOrchestrator
{
    public function __construct(
        private readonly StripeSubscriptionService $subscriptionService,
        private readonly StripeCustomerService $customerService,
        private readonly StripePlanCatalogService $planCatalogService,
    ) {
    }

    /**
     * @return array{stripe_subscription_id: string}
     *
     * @throws ApiErrorException|\RuntimeException
     */
    public function createStripeSubscriptionForLocal(int $subscriptionRowId): array
    {
        if (! Schema::hasTable('subscriptions')) {
            throw new \RuntimeException('stripe_table_subscriptions_missing');
        }

        $subscription = Subscription::query()->with(['company', 'plan'])->findOrFail($subscriptionRowId);
        $company = $subscription->company;
        if ($company === null) {
            throw new \RuntimeException('stripe_company_not_found');
        }

        $plan = $subscription->plan;
        if (! $plan instanceof Plan) {
            throw new \RuntimeException('stripe_plan_not_found');
        }

        $priceId = $this->planCatalogService->resolvePriceIdForPlan($plan);
        if ($priceId === null || $priceId === '') {
            $synced = $this->planCatalogService->syncPlanToStripe((int) $plan->plan_id);
            $priceId = $synced['price_id'];
        }

        $customerId = $this->customerService->companyStripeId($company);
        if ($customerId === null || $customerId === '') {
            $customerId = $this->customerService->syncCompanyCustomer((int) $company->company_id);
        }

        $stripeSub = $this->subscriptionService->createSubscription($customerId, $priceId);
        $this->subscriptionService->linkLocalInvoiceFromStripeSubscription($subscription, $stripeSub);
        $details = $this->subscriptionService->retrieveSubscription($stripeSub->id);
        $this->subscriptionService->applyDetailsToSubscriptionModel($subscription, $details);
        $subscription->save();

        return ['stripe_subscription_id' => $stripeSub->id];
    }

    /**
     * @throws ApiErrorException
     */
    public function syncLocalSubscription(int $subscriptionRowId): array
    {
        $subscription = Subscription::query()->findOrFail($subscriptionRowId);
        $details = $this->subscriptionService->syncLocalSubscriptionFromStripe($subscription);

        return ['subscription' => $subscription->fresh(), 'stripe' => $details->toArray()];
    }

    /**
     * @return array{stripe_subscription_id: string, status: string}
     *
     * @throws ApiErrorException
     */
    public function cancelLocalSubscription(int $subscriptionRowId, bool $atPeriodEnd = true): array
    {
        $subscription = Subscription::query()->findOrFail($subscriptionRowId);
        $stripeId = trim((string) ($subscription->stripe_subscription_id ?? ''));
        if ($stripeId === '') {
            throw new \RuntimeException('stripe_subscription_id_missing');
        }

        $details = $this->subscriptionService->cancelSubscription($stripeId, $atPeriodEnd);
        $this->subscriptionService->applyDetailsToSubscriptionModel($subscription, $details);
        $subscription->save();

        return [
            'stripe_subscription_id' => $details->id,
            'status' => $details->status,
        ];
    }

    /**
     * @return array{processed: int, updated: int, errors: list<array{subscription_id: int, message: string}>}
     */
    public function syncAllWithStripe(): array
    {
        $processed = 0;
        $updated = 0;
        $errors = [];

        Subscription::query()
            ->whereNotNull('stripe_subscription_id')
            ->where('stripe_subscription_id', '!=', '')
            ->orderBy('subscription_id')
            ->chunkById(50, function (Collection $rows) use (&$processed, &$updated, &$errors) {
                foreach ($rows as $subscription) {
                    ++$processed;
                    try {
                        $this->subscriptionService->syncLocalSubscriptionFromStripe($subscription);
                        ++$updated;
                    } catch (\Throwable $e) {
                        $errors[] = [
                            'subscription_id' => (int) $subscription->getKey(),
                            'message' => $e->getMessage(),
                        ];
                    }
                }
            }, 'subscription_id');

        return compact('processed', 'updated', 'errors');
    }

    /**
     * Crea en Stripe las suscripciones locales sin stripe_subscription_id (company + plan requeridos).
     *
     * @return array{processed: int, updated: int, errors: list<array{subscription_id: int, message: string}>}
     */
    public function syncAllPendingSubscriptionsToStripe(): array
    {
        $processed = 0;
        $updated = 0;
        $errors = [];

        if (! Schema::hasTable('subscriptions') || ! Schema::hasColumn('subscriptions', 'stripe_subscription_id')) {
            return compact('processed', 'updated', 'errors');
        }

        Subscription::query()
            ->with(['company', 'plan'])
            ->where(function ($q) {
                $q->whereNull('stripe_subscription_id')->orWhere('stripe_subscription_id', '=', '');
            })
            ->when(Schema::hasColumn('subscriptions', 'status'), function ($q) {
                $q->where('status', '=', 1);
            })
            ->orderBy('subscription_id')
            ->chunkById(25, function (Collection $rows) use (&$processed, &$updated, &$errors) {
                foreach ($rows as $subscription) {
                    ++$processed;
                    try {
                        $this->createStripeSubscriptionForLocal((int) $subscription->getKey());
                        ++$updated;
                    } catch (\Throwable $e) {
                        $errors[] = [
                            'subscription_id' => (int) $subscription->getKey(),
                            'message' => $e->getMessage(),
                        ];
                    }
                }
            }, 'subscription_id');

        return compact('processed', 'updated', 'errors');
    }
}

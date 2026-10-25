<?php

namespace App\Services\Stripe;

use App\Models\Plan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Stripe\Exception\ApiErrorException;
use Stripe\Price;
use Stripe\Product;

/**
 * Sincroniza fila plan → Product + Price en Stripe (legado: price en plan.stripeld).
 */
class StripePlanCatalogService
{
    public function __construct(
        private readonly StripeApiKeyResolver $apiKeyResolver,
    ) {
    }

    /**
     * @return array{price_id: string, product_id: string, stripe_status: string}
     *
     * @throws ApiErrorException|\RuntimeException
     */
    public function syncPlanToStripe(int $planId): array
    {
        if (! Schema::hasTable('plan')) {
            throw new \RuntimeException('stripe_table_plan_missing');
        }

        $plan = Plan::query()->findOrFail($planId);
        $title = trim((string) ($plan->title ?? ''));
        if ($title === '') {
            $title = 'Plan #' . $planId;
        }

        $amountCents = (int) round(((float) ($plan->price ?? 0)) * 100);
        $currency = strtolower((string) config('stripe.currency', 'usd'));
        $interval = $this->billingInterval($plan);

        $client = $this->apiKeyResolver->stripeClient();
        $product = $this->findOrCreateProduct($client, $title);
        $price = $this->findOrCreateRecurringPrice($client, $product->id, $amountCents, $currency, $interval);

        if (Schema::hasColumn('plan', 'stripeld')) {
            $plan->stripeld = $price->id;
        }
        if (Schema::hasColumn('plan', 'stripeName')) {
            $plan->stripeName = $product->name;
        }
        if (Schema::hasColumn('plan', 'stripe_status')) {
            $plan->stripe_status = $price->active ? 'active' : 'inactive';
        }
        $plan->save();

        return [
            'price_id' => $price->id,
            'product_id' => $product->id,
            'stripe_status' => (string) ($plan->stripe_status ?? 'active'),
        ];
    }

    /**
     * Sincroniza todos los planes que aún no tienen stripeld (price id en Stripe).
     *
     * @return array{processed: int, updated: int, errors: list<array{plan_id: int, message: string}>}
     */
    public function syncAllPendingPlansToStripe(): array
    {
        $processed = 0;
        $updated = 0;
        $errors = [];

        if (! Schema::hasTable('plan') || ! Schema::hasColumn('plan', 'stripeld')) {
            return compact('processed', 'updated', 'errors');
        }

        Plan::query()
            ->where(function ($q) {
                $q->whereNull('stripeld')->orWhere('stripeld', '=', '');
            })
            ->when(Schema::hasColumn('plan', 'status'), function ($q) {
                $q->where('status', '=', 1);
            })
            ->orderBy('plan_id')
            ->chunkById(50, function (Collection $rows) use (&$processed, &$updated, &$errors) {
                foreach ($rows as $plan) {
                    ++$processed;
                    try {
                        $this->syncPlanToStripe((int) $plan->plan_id);
                        ++$updated;
                    } catch (\Throwable $e) {
                        $errors[] = [
                            'plan_id' => (int) $plan->plan_id,
                            'message' => $e->getMessage(),
                        ];
                    }
                }
            }, 'plan_id');

        return compact('processed', 'updated', 'errors');
    }

    public function resolvePriceIdForPlan(Plan $plan): ?string
    {
        if (Schema::hasColumn('plan', 'stripeld')) {
            $id = trim((string) ($plan->stripeld ?? ''));
            if ($id !== '') {
                return $id;
            }
        }

        return null;
    }

    private function billingInterval(Plan $plan): string
    {
        $bp = strtolower(trim((string) ($plan->billing_period ?? 'monthly')));

        return in_array($bp, ['annual', 'year', 'yearly'], true) ? 'year' : 'month';
    }

    private function findOrCreateProduct(\Stripe\StripeClient $client, string $productName): Product
    {
        $escaped = str_replace('"', '\\"', $productName);
        $search = $client->products->search([
            'query' => 'name:"' . $escaped . '"',
            'limit' => 100,
        ]);

        foreach ($search->data as $candidate) {
            if ($candidate->name === $productName) {
                return $candidate;
            }
        }

        return $client->products->create([
            'name' => $productName,
            'description' => $productName,
        ]);
    }

    private function findOrCreateRecurringPrice(
        \Stripe\StripeClient $client,
        string $productId,
        int $unitAmountCents,
        string $currency,
        string $interval,
    ): Price {
        $list = $client->prices->all([
            'product' => $productId,
            'active' => true,
            'limit' => 100,
        ]);

        foreach ($list->data as $price) {
            if ((int) ($price->unit_amount ?? 0) === $unitAmountCents
                && strtolower((string) $price->currency) === $currency
                && isset($price->recurring)
                && (string) ($price->recurring->interval ?? '') === $interval) {
                return $price;
            }
        }

        return $client->prices->create([
            'unit_amount' => max($unitAmountCents, 0),
            'currency' => $currency,
            'active' => true,
            'product' => $productId,
            'recurring' => ['interval' => $interval],
        ]);
    }
}

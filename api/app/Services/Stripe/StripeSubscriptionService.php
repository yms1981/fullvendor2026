<?php

namespace App\Services\Stripe;

use App\Models\Invoice;
use App\Models\Subscription;
use App\Services\Stripe\Dto\SubscriptionStripeDetailsData;
use Illuminate\Support\Facades\Schema;
use Stripe\Exception\ApiErrorException;
use Stripe\Subscription as StripeSubscription;

/**
 * Réplica de StripeSubscriptionService (legado C#).
 */
class StripeSubscriptionService
{
    public function __construct(
        private readonly StripeApiKeyResolver $apiKeyResolver,
        private readonly StripeInvoiceService $invoiceService,
    ) {
    }

    /**
     * @throws ApiErrorException
     */
    public function createSubscription(string $customerId, string $priceId): StripeSubscription
    {
        return $this->apiKeyResolver->stripeClient()->subscriptions->create([
            'customer' => $customerId,
            'items' => [['price' => $priceId]],
            'payment_behavior' => 'default_incomplete',
            'expand' => ['latest_invoice.payment_intent'],
        ]);
    }

    /**
     * @throws ApiErrorException
     */
    public function retrieveSubscription(string $subscriptionId): SubscriptionStripeDetailsData
    {
        $sub = $this->apiKeyResolver->stripeClient()->subscriptions->retrieve($subscriptionId, []);

        return $this->mapSubscription($sub);
    }

    /**
     * @throws ApiErrorException
     */
    public function cancelSubscription(string $subscriptionId, bool $atPeriodEnd = true): SubscriptionStripeDetailsData
    {
        $subscriptions = $this->apiKeyResolver->stripeClient()->subscriptions;
        if ($atPeriodEnd) {
            $sub = $subscriptions->update($subscriptionId, ['cancel_at_period_end' => true]);
        } else {
            $sub = $subscriptions->cancel($subscriptionId, []);
        }

        return $this->mapSubscription($sub);
    }

    public function applyDetailsToSubscriptionModel(Subscription $subscription, SubscriptionStripeDetailsData $details): void
    {
        $assignments = [
            'stripe_subscription_id' => $details->id,
            'stripe_status' => $details->status,
            'stripe_price_id' => $details->priceId,
            'stripe_currency' => $details->currency,
            'stripe_collection_method' => $details->collectionMethod,
            'stripe_cancel_at_period_end' => $details->cancelAtPeriodEnd,
            'stripe_current_period_start' => $details->currentPeriodStart,
            'stripe_current_period_end' => $details->currentPeriodEnd,
            'stripe_trial_start' => $details->trialStart,
            'stripe_trial_end' => $details->trialEnd,
            'stripe_cancel_at' => $details->cancelAt,
            'stripe_canceled_at' => $details->canceledAt,
            'stripe_ended_at' => $details->endedAt,
            'stripe_latest_invoice_id' => $details->latestInvoiceId,
            'stripe_default_payment_method_id' => $details->defaultPaymentMethodId,
            'stripe_created_at' => $details->createdAt,
        ];

        foreach ($assignments as $column => $value) {
            if (Schema::hasColumn('subscriptions', $column)) {
                $subscription->{$column} = $value;
            }
        }
    }

    public function syncLocalSubscriptionFromStripe(Subscription $subscription): SubscriptionStripeDetailsData
    {
        $stripeId = trim((string) ($subscription->stripe_subscription_id ?? ''));
        if ($stripeId === '') {
            throw new \RuntimeException('stripe_subscription_id_missing');
        }

        $details = $this->retrieveSubscription($stripeId);
        $this->applyDetailsToSubscriptionModel($subscription, $details);
        $subscription->save();
        $this->linkLocalInvoiceFromLatestStripeInvoiceId($subscription, $details->latestInvoiceId);

        return $details;
    }

    /**
     * Enlaza la factura local de la suscripción con latest_invoice de Stripe (sin crear factura puntual).
     *
     * @return int|null invoice_id local actualizado, o null si no aplica
     *
     * @throws ApiErrorException
     */
    public function linkLocalInvoiceFromStripeSubscription(Subscription $subscription, StripeSubscription $stripeSub): ?int
    {
        $latestInvoiceId = null;
        if (isset($stripeSub->latest_invoice)) {
            $latestInvoiceId = is_string($stripeSub->latest_invoice)
                ? $stripeSub->latest_invoice
                : ($stripeSub->latest_invoice->id ?? null);
        }

        return $this->linkLocalInvoiceFromLatestStripeInvoiceId($subscription, $latestInvoiceId);
    }

    /**
     * @throws ApiErrorException
     */
    public function linkLocalInvoiceFromLatestStripeInvoiceId(Subscription $subscription, ?string $stripeInvoiceId): ?int
    {
        $stripeInvoiceId = trim((string) $stripeInvoiceId);
        if ($stripeInvoiceId === '' || ! Schema::hasTable('invoices')) {
            return null;
        }

        $subscriptionId = (int) $subscription->getKey();
        if ($subscriptionId < 1) {
            return null;
        }

        $invoice = Invoice::query()
            ->where('subscription_id', $subscriptionId)
            ->orderBy('invoice_id')
            ->first();
        if ($invoice === null) {
            return null;
        }

        $details = $this->invoiceService->findStripeInvoiceById($stripeInvoiceId);
        $this->invoiceService->applyDetailsToInvoiceModel($invoice, $details);
        $invoice->save();

        return (int) $invoice->invoice_id;
    }

    private function mapSubscription(StripeSubscription $sub): SubscriptionStripeDetailsData
    {
        $priceId = null;
        if (isset($sub->items->data[0]->price->id)) {
            $priceId = (string) $sub->items->data[0]->price->id;
        }

        $customerId = null;
        if (isset($sub->customer)) {
            $customerId = is_string($sub->customer) ? $sub->customer : ($sub->customer->id ?? null);
        }

        $latestInvoiceId = null;
        if (isset($sub->latest_invoice)) {
            $latestInvoiceId = is_string($sub->latest_invoice) ? $sub->latest_invoice : ($sub->latest_invoice->id ?? null);
        }

        $defaultPaymentMethodId = null;
        if (isset($sub->default_payment_method)) {
            $defaultPaymentMethodId = is_string($sub->default_payment_method)
                ? $sub->default_payment_method
                : ($sub->default_payment_method->id ?? null);
        }

        return new SubscriptionStripeDetailsData(
            id: (string) $sub->id,
            customerId: $customerId,
            status: (string) $sub->status,
            priceId: $priceId,
            currency: isset($sub->currency) ? (string) $sub->currency : null,
            collectionMethod: isset($sub->collection_method) ? (string) $sub->collection_method : null,
            cancelAtPeriodEnd: (bool) ($sub->cancel_at_period_end ?? false),
            currentPeriodStart: $this->tsToIso($sub->current_period_start ?? null),
            currentPeriodEnd: $this->tsToIso($sub->current_period_end ?? null),
            trialStart: $this->tsToIso($sub->trial_start ?? null),
            trialEnd: $this->tsToIso($sub->trial_end ?? null),
            cancelAt: $this->tsToIso($sub->cancel_at ?? null),
            canceledAt: $this->tsToIso($sub->canceled_at ?? null),
            endedAt: $this->tsToIso($sub->ended_at ?? null),
            latestInvoiceId: $latestInvoiceId,
            defaultPaymentMethodId: $defaultPaymentMethodId,
            createdAt: $this->tsToIso($sub->created ?? null),
        );
    }

    /**
     * Convierte un timestamp Unix de Stripe a ISO 8601 (o null si no aplica).
     */
    private function tsToIso(mixed $ts): ?string
    {
        if ($ts === null || $ts === '' || $ts === false) {
            return null;
        }
        $int = (int) $ts;
        if ($int <= 0) {
            return null;
        }

        return date('c', $int);
    }
}

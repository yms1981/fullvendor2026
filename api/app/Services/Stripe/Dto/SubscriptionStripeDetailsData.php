<?php

namespace App\Services\Stripe\Dto;

/**
 * Snapshot del objeto \Stripe\Subscription persistible en la tabla `subscriptions`.
 *
 * Los campos de fecha se serializan como ISO 8601 (date('c', ts)) y se castean
 * a datetime en el modelo Subscription.
 */
final class SubscriptionStripeDetailsData
{
    public function __construct(
        public readonly string $id,
        public readonly ?string $customerId,
        public readonly string $status,
        public readonly ?string $priceId,
        public readonly ?string $currency,
        public readonly ?string $collectionMethod,
        public readonly bool $cancelAtPeriodEnd,
        public readonly ?string $currentPeriodStart,
        public readonly ?string $currentPeriodEnd,
        public readonly ?string $trialStart,
        public readonly ?string $trialEnd,
        public readonly ?string $cancelAt,
        public readonly ?string $canceledAt,
        public readonly ?string $endedAt,
        public readonly ?string $latestInvoiceId,
        public readonly ?string $defaultPaymentMethodId,
        public readonly ?string $createdAt,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customerId,
            'status' => $this->status,
            'price_id' => $this->priceId,
            'currency' => $this->currency,
            'collection_method' => $this->collectionMethod,
            'cancel_at_period_end' => $this->cancelAtPeriodEnd,
            'current_period_start' => $this->currentPeriodStart,
            'current_period_end' => $this->currentPeriodEnd,
            'trial_start' => $this->trialStart,
            'trial_end' => $this->trialEnd,
            'cancel_at' => $this->cancelAt,
            'canceled_at' => $this->canceledAt,
            'ended_at' => $this->endedAt,
            'latest_invoice_id' => $this->latestInvoiceId,
            'default_payment_method_id' => $this->defaultPaymentMethodId,
            'created_at' => $this->createdAt,
        ];
    }
}

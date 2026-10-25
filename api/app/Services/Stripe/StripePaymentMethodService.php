<?php

namespace App\Services\Stripe;

use Stripe\Exception\ApiErrorException;
use Stripe\PaymentMethod;

/**
 * Réplica de StripePaymentMethodService (legado C#).
 */
class StripePaymentMethodService
{
    public function __construct(
        private readonly StripeApiKeyResolver $apiKeyResolver,
        private readonly StripeCustomerService $customerService,
    ) {
    }

    /**
     * @return list<array{id: string, brand: string|null, last4: string|null, exp_month: int|null, exp_year: int|null}>
     *
     * @throws ApiErrorException
     */
    public function listForCompany(int $companyId): array
    {
        $customerId = $this->customerService->syncCompanyCustomer($companyId);
        $list = $this->apiKeyResolver->stripeClient()->paymentMethods->all([
            'customer' => $customerId,
            'type' => 'card',
        ]);

        $out = [];
        foreach ($list->data as $pm) {
            $out[] = $this->mapPaymentMethod($pm);
        }

        return $out;
    }

    /**
     * @throws ApiErrorException
     */
    public function attachToCompany(int $companyId, string $paymentMethodId): array
    {
        $customerId = $this->customerService->syncCompanyCustomer($companyId);
        $pm = $this->apiKeyResolver->stripeClient()->paymentMethods->attach($paymentMethodId, ['customer' => $customerId]);

        return $this->mapPaymentMethod($pm);
    }

    /**
     * @throws ApiErrorException
     */
    public function detach(string $paymentMethodId): bool
    {
        $pm = $this->apiKeyResolver->stripeClient()->paymentMethods->detach($paymentMethodId, []);

        return $pm->customer === null;
    }

    /**
     * @throws ApiErrorException
     */
    public function setDefaultForCompany(int $companyId, string $paymentMethodId): void
    {
        $customerId = $this->customerService->syncCompanyCustomer($companyId);
        $this->apiKeyResolver->stripeClient()->customers->update($customerId, [
            'invoice_settings' => ['default_payment_method' => $paymentMethodId],
        ]);
    }

    /**
     * @return array{id: string, brand: string|null, last4: string|null, exp_month: int|null, exp_year: int|null}
     */
    private function mapPaymentMethod(PaymentMethod $pm): array
    {
        $card = $pm->card;

        return [
            'id' => $pm->id,
            'brand' => $card->brand ?? null,
            'last4' => $card->last4 ?? null,
            'exp_month' => $card->exp_month ?? null,
            'exp_year' => $card->exp_year ?? null,
        ];
    }
}

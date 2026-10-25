<?php

namespace App\Observers;

use App\Models\Company;
use App\Services\Stripe\StripeCustomerService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Stripe\Exception\ApiErrorException;

/**
 * Al crear una company, crea el Customer en Stripe y persiste companies.stripe_id.
 * Equivalente al flujo legado C# al dar de alta una empresa.
 */
class CompanyStripeCustomerObserver
{
    public function __construct(
        private readonly StripeCustomerService $customerService,
    ) {
    }

    public function created(Company $company): void
    {
        if (! Schema::hasTable('companies') || ! Schema::hasColumn('companies', 'stripe_id')) {
            return;
        }

        $companyId = (int) ($company->company_id ?? 0);
        if ($companyId < 1) {
            return;
        }

        $existing = $this->customerService->companyStripeId($company);
        if ($existing !== null && $existing !== '') {
            return;
        }

        try {
            $this->customerService->syncCompanyCustomer($companyId);
        } catch (ApiErrorException $e) {
            Log::warning('[CompanyStripeCustomerObserver] Stripe customer not created on company create', [
                'company_id' => $companyId,
                'stripe_message' => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[CompanyStripeCustomerObserver] Unexpected error syncing Stripe customer', [
                'company_id' => $companyId,
                'message' => $e->getMessage(),
            ]);
        }
    }
}

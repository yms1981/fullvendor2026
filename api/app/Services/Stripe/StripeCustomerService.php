<?php

namespace App\Services\Stripe;

use App\Models\Company;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;

/** Equivalente a StripeService (clientes Stripe). */
class StripeCustomerService
{
    public function __construct(
        private readonly StripeApiKeyResolver $apiKeyResolver,
    ) {
    }

    /**
     * @throws ApiErrorException
     */
    public function createCustomer(string $email, string $name, ?string $phone = null): string
    {
        $this->apiKeyResolver->configureStripeClient();
        $options = [
            'email' => $email,
            'name' => $name,
        ];
        if ($phone !== null && $phone !== '') {
            $options['phone'] = $phone;
        }
        $customer = Customer::create($options);

        return $customer->id;
    }

    /**
     * Crea customer en Stripe y persiste companies.stripe_id si la columna existe.
     *
     * @throws ApiErrorException
     */
    public function syncCompanyCustomer(int $companyId): string
    {
        $company = Company::query()->findOrFail($companyId);
        $existing = $this->companyStripeId($company);
        if ($existing !== null && $existing !== '') {
            return $existing;
        }

        $email = trim((string) ($company->email ?? ''));
        $name = trim((string) ($company->name ?? $company->short_name ?? ''));
        if ($name === '') {
            $name = 'Company #' . $companyId;
        }
        $phone = trim((string) ($company->phone_number ?? $company->cell_number ?? ''));

        $customerId = $this->createCustomer($email, $name, $phone !== '' ? $phone : null);
        if (Schema::hasColumn('companies', 'stripe_id')) {
            \Illuminate\Support\Facades\DB::table('companies')
                ->where('company_id', $companyId)
                ->update(['stripe_id' => $customerId]);
        }

        return $customerId;
    }

    /**
     * Sincroniza todas las companies que aún no tienen stripe_id (customer en Stripe).
     *
     * @return array{processed: int, updated: int, errors: list<array{company_id: int, message: string}>}
     */
    public function syncAllPendingCompaniesToStripe(): array
    {
        $processed = 0;
        $updated = 0;
        $errors = [];

        if (! Schema::hasTable('companies') || ! Schema::hasColumn('companies', 'stripe_id')) {
            return compact('processed', 'updated', 'errors');
        }

        Company::query()
            ->where(function ($q) {
                $q->whereNull('stripe_id')->orWhere('stripe_id', '=', '');
            })
            ->orderBy('company_id')
            ->chunkById(50, function (Collection $rows) use (&$processed, &$updated, &$errors) {
                foreach ($rows as $company) {
                    ++$processed;
                    try {
                        $this->syncCompanyCustomer((int) $company->company_id);
                        ++$updated;
                    } catch (\Throwable $e) {
                        $errors[] = [
                            'company_id' => (int) $company->company_id,
                            'message' => $e->getMessage(),
                        ];
                    }
                }
            }, 'company_id');

        return compact('processed', 'updated', 'errors');
    }

    public function companyStripeId(Company $company): ?string
    {
        if (! Schema::hasColumn('companies', 'stripe_id')) {
            return null;
        }
        $id = $company->getAttribute('stripe_id');

        return is_string($id) && trim($id) !== '' ? trim($id) : null;
    }

    /**
     * @throws ApiErrorException
     */
    public function retrieveCustomer(string $customerId): Customer
    {
        return $this->apiKeyResolver->stripeClient()->customers->retrieve($customerId, []);
    }

    /**
     * @throws ApiErrorException
     */
    public function updateCustomer(string $customerId, string $email, string $name, ?string $phone = null): Customer
    {
        $options = [
            'email' => $email,
            'name' => $name,
        ];
        if ($phone !== null && $phone !== '') {
            $options['phone'] = $phone;
        }

        return $this->apiKeyResolver->stripeClient()->customers->update($customerId, $options);
    }

    /**
     * Sincroniza datos locales de la empresa hacia el customer existente en Stripe.
     *
     * @throws ApiErrorException
     */
    public function refreshCompanyCustomerOnStripe(int $companyId): string
    {
        $company = Company::query()->findOrFail($companyId);
        $customerId = $this->companyStripeId($company);
        if ($customerId === null || $customerId === '') {
            return $this->syncCompanyCustomer($companyId);
        }

        $email = trim((string) ($company->email ?? ''));
        $name = trim((string) ($company->name ?? $company->short_name ?? ''));
        if ($name === '') {
            $name = 'Company #' . $companyId;
        }
        $phone = trim((string) ($company->phone_number ?? $company->cell_number ?? ''));
        $this->updateCustomer($customerId, $email, $name, $phone !== '' ? $phone : null);

        return $customerId;
    }
}

<?php

namespace App\Services\AppSalesman;

use App\Actions\CatalogTokenService;
use App\Actions\SendCatalogService;
use App\Models\Catalog;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Vendor;
use Carbon\Carbon;
use Throwable;

/**
 * Genera enlace público de catálogo (mismo flujo que “enviar catálogo” / order/invitation),
 * sin enviar email/SMS/WhatsApp.
 */
class AppSalesmanCatalogLinkService
{
    public function __construct(
        private readonly CatalogTokenService $tokenService,
        private readonly SendCatalogService $sendCatalogService,
    ) {
    }

    /**
     * @return array{
     *   status: string,
     *   company_id?: int,
     *   customer_id?: int,
     *   catalog_id?: int,
     *   link?: string,
     *   token?: string,
     *   expiration_date?: string,
     *   created?: string,
     *   error?: string
     * }
     */
    public function createLink(int $companyId, int $customerId, ?int $catalogId = null): array
    {
        if ($companyId < 1) {
            return ['status' => '0', 'error' => 'validation_company_id'];
        }
        if ($customerId < 1) {
            return ['status' => '0', 'error' => 'validation_customer_id'];
        }

        $company = Company::query()->find($companyId);
        if (! $company) {
            return ['status' => '0', 'error' => 'company_not_found'];
        }

        $customer = Customer::query()
            ->with(['sellerAssignCatalog', 'sellers', 'company.defaultSeller'])
            ->where('customer_id', $customerId)
            ->first();

        if (! $customer) {
            return ['status' => '0', 'error' => 'customer_not_found'];
        }

        if ((int) ($customer->company_id ?? 0) !== $companyId) {
            return ['status' => '0', 'error' => 'customer_company_mismatch'];
        }

        $resolvedCatalogId = $this->resolveCatalogId($companyId, $catalogId);
        if ($resolvedCatalogId < 1) {
            return ['status' => '0', 'error' => 'catalog_not_found'];
        }

        $seller = $this->resolveSeller($customer, $company);
        $created = Carbon::now()->toIso8601String();

        try {
            $result = $this->tokenService->createToken(
                $customerId,
                $seller?->id,
                $resolvedCatalogId,
                ''
            );

            $token = (string) ($result['token'] ?? '');
            $expirationDate = (string) ($result['expirationDate'] ?? '');

            if ($token === '' || $expirationDate === '') {
                return ['status' => '0', 'error' => 'catalog_link_failed'];
            }

            $this->sendCatalogService->ensureCatalogCartRow(
                $company,
                $customer,
                $resolvedCatalogId,
                $token,
                $expirationDate,
                '',
                $seller
            );

            $link = rtrim((string) config('fullvendor.web_url'), '/')
                .'/catalogo/producto?token='.rawurlencode($token);

            return [
                'status' => '1',
                'company_id' => $companyId,
                'customer_id' => $customerId,
                'catalog_id' => $resolvedCatalogId,
                'link' => $link,
                'token' => $token,
                'expiration_date' => $expirationDate,
                'created' => $created,
            ];
        } catch (Throwable $e) {
            report($e);

            return ['status' => '0', 'error' => 'catalog_link_failed'];
        }
    }

    private function resolveCatalogId(int $companyId, ?int $catalogId): int
    {
        if ($catalogId !== null && $catalogId > 0) {
            $exists = Catalog::query()
                ->where('company_id', $companyId)
                ->where('catalog_id', $catalogId)
                ->exists();

            return $exists ? $catalogId : 0;
        }

        $default = Catalog::query()
            ->where('company_id', $companyId)
            ->where('default_catalog', 1)
            ->orderByDesc('catalog_id')
            ->value('catalog_id');

        if ($default) {
            return (int) $default;
        }

        $first = Catalog::query()
            ->where('company_id', $companyId)
            ->where('status', 1)
            ->orderByDesc('catalog_id')
            ->value('catalog_id');

        if ($first) {
            return (int) $first;
        }

        return (int) (Catalog::query()
            ->where('company_id', $companyId)
            ->orderByDesc('catalog_id')
            ->value('catalog_id') ?? 0);
    }

    private function resolveSeller(Customer $customer, Company $company): ?Vendor
    {
        $seller = $customer->sellerAssignCatalog
            ?? $customer->sellers->first()
            ?? $company->defaultSeller;

        if ($seller) {
            $seller->loadMissing('user');
        }

        return $seller instanceof Vendor ? $seller : null;
    }
}

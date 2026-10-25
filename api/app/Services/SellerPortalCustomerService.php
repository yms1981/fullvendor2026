<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Vendor;
use Illuminate\Support\Facades\Schema;

final class SellerPortalCustomerService
{
    public function __construct(
        private readonly SellerPortalCustomerGridMapper $gridMapper,
        private readonly SellerPortalContextService $contextService,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function portalGrid(Vendor $vendor): array
    {
        $sellerId = (int) $vendor->id;
        $companyId = (int) $vendor->company_id;
        $sellerNameById = [];
        foreach ($this->contextService->sellers($companyId) as $sr) {
            $sellerNameById[(int) $sr['id']] = (string) $sr['name'];
        }

        $rawRows = $this->fetchRawCustomerArrays($vendor);

        return $this->gridMapper->mapMany($rawRows, $sellerId, $sellerNameById);
    }

    /**
     * @return array<string, mixed>|null Fila cruda con sellers + customer_group
     */
    public function findRawCustomer(Vendor $vendor, int $customerId): ?array
    {
        if ($customerId <= 0) {
            return null;
        }

        $customer = Customer::query()
            ->where('customer_id', $customerId)
            ->where('company_id', (int) $vendor->company_id)
            ->with(['sellers', 'customer_group'])
            ->first();

        if ($customer === null || ! $this->customerAccessibleByVendor($vendor, $customer)) {
            return null;
        }

        return $this->customerModelToArray($customer);
    }

    public function customerAccessibleByVendor(Vendor $vendor, Customer $customer): bool
    {
        if ((int) $customer->company_id !== (int) $vendor->company_id) {
            return false;
        }

        if ((int) ($vendor->all_customers ?? 0) === 1) {
            return true;
        }

        if ($vendor->customers()->where('customers.customer_id', $customer->customer_id)->exists()) {
            return true;
        }

        return $this->legacyUserIdAssignmentMatches($vendor, $customer);
    }

    /**
     * @return list<int>
     */
    public function assignedCustomerIds(Vendor $vendor): array
    {
        $companyId = (int) $vendor->company_id;
        if ((int) ($vendor->all_customers ?? 0) === 1) {
            return Customer::query()
                ->where('company_id', $companyId)
                ->pluck('customer_id')
                ->map(static fn ($id) => (int) $id)
                ->all();
        }

        $ids = $vendor->customers()->pluck('customers.customer_id')->map(static fn ($id) => (int) $id)->all();
        $legacyIds = $this->legacyAssignedCustomerIds($vendor);

        return array_values(array_unique(array_merge($ids, $legacyIds)));
    }

    public function unassignSeller(Vendor $vendor, int $customerId): bool
    {
        $customer = Customer::query()
            ->where('customer_id', $customerId)
            ->where('company_id', (int) $vendor->company_id)
            ->first();

        if ($customer === null || ! $this->customerAccessibleByVendor($vendor, $customer)) {
            return false;
        }

        $customer->sellers()->detach([(int) $vendor->id]);

        return true;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchRawCustomerArrays(Vendor $vendor): array
    {
        $companyId = (int) $vendor->company_id;

        if ((int) ($vendor->all_customers ?? 0) === 1) {
            return Customer::query()
                ->where('company_id', $companyId)
                ->with(['sellers', 'customer_group'])
                ->orderBy('business_name')
                ->get()
                ->map(fn (Customer $c) => $this->customerModelToArray($c))
                ->all();
        }

        $fromPivot = $vendor->customers()
            ->with(['sellers', 'customer_group'])
            ->get()
            ->map(fn (Customer $c) => $this->customerModelToArray($c))
            ->all();

        $legacyIds = $this->legacyAssignedCustomerIds($vendor);
        if ($legacyIds === []) {
            return $fromPivot;
        }

        $existingIds = array_map(static fn (array $row) => (int) ($row['customer_id'] ?? 0), $fromPivot);
        $missingIds = array_values(array_diff($legacyIds, $existingIds));
        if ($missingIds === []) {
            return $fromPivot;
        }

        $extra = Customer::query()
            ->whereIn('customer_id', $missingIds)
            ->where('company_id', $companyId)
            ->with(['sellers', 'customer_group'])
            ->get()
            ->map(fn (Customer $c) => $this->customerModelToArray($c))
            ->all();

        return array_merge($fromPivot, $extra);
    }

    /**
     * Compatibilidad legacy: customers.user_id CSV con user_id del vendedor o seller id.
     *
     * @return list<int>
     */
    private function legacyAssignedCustomerIds(Vendor $vendor): array
    {
        if (! Schema::hasColumn('customers', 'user_id')) {
            return [];
        }

        $sellerUserId = (int) ($vendor->user_id ?? 0);
        $sellerId = (int) $vendor->id;
        $companyId = (int) $vendor->company_id;

        if ($sellerUserId <= 0 && $sellerId <= 0) {
            return [];
        }

        $q = Customer::query()->where('company_id', $companyId);
        $q->where(function ($query) use ($sellerUserId, $sellerId) {
            if ($sellerUserId > 0) {
                $query->whereRaw('FIND_IN_SET(?, COALESCE(user_id, "")) > 0', [$sellerUserId]);
            }
            if ($sellerId > 0) {
                $query->orWhereRaw('FIND_IN_SET(?, COALESCE(user_id, "")) > 0', [$sellerId]);
            }
        });

        return $q->pluck('customer_id')->map(static fn ($id) => (int) $id)->all();
    }

    private function legacyUserIdAssignmentMatches(Vendor $vendor, Customer $customer): bool
    {
        if (! Schema::hasColumn('customers', 'user_id')) {
            return false;
        }

        $csv = (string) ($customer->user_id ?? '');
        if ($csv === '') {
            return false;
        }

        $needles = array_filter([(int) ($vendor->user_id ?? 0), (int) $vendor->id], static fn ($v) => $v > 0);
        foreach ($needles as $needle) {
            if (preg_match('/(^|,)\s*' . preg_quote((string) $needle, '/') . '\s*(,|$)/', $csv)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private function customerModelToArray(Customer $customer): array
    {
        $data = $customer->toArray();
        if ($customer->relationLoaded('sellers')) {
            $data['sellers'] = $customer->sellers->map(static fn (Vendor $v) => ['id' => (int) $v->id])->all();
        }
        if ($customer->relationLoaded('customer_group') && $customer->customer_group !== null) {
            $data['customer_group'] = $customer->customer_group->toArray();
        }

        return $data;
    }
}

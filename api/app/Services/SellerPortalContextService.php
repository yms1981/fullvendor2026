<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Country;
use App\Models\CustomerGroup;
use App\Models\FormatNumeric;
use App\Models\TermsOfSale;
use App\Models\Vendor;
use Illuminate\Support\Facades\Schema;

final class SellerPortalContextService
{
    /**
     * @return array<string, mixed>
     */
    public function buildContext(Vendor $vendor): array
    {
        $company = $vendor->company ?? Company::query()->find((int) $vendor->company_id);
        $companyId = (int) ($vendor->company_id ?? 0);

        $defaultCountryId = 0;
        if ($company !== null) {
            $defaultCountryId = (int) ($company->country_id ?? 0);
            if ($defaultCountryId <= 0 && ! empty($company->country)) {
                $defaultCountryId = (int) (Country::query()
                    ->whereRaw('LOWER(name) = ?', [strtolower(trim((string) $company->country))])
                    ->value('id') ?? 0);
            }
        }

        $formatNumeric = $company?->format_numeric ?? FormatNumeric::default();
        $currencySymbol = '$';
        if ($company !== null) {
            $company->loadMissing('currencyModel');
            $sym = trim((string) ($company->currencyModel?->symbol ?? ''));
            if ($sym !== '') {
                $currencySymbol = $sym;
            }
        }

        return [
            'company_id' => $companyId,
            'seller_id' => (int) $vendor->id,
            'company_name' => (string) ($company?->name ?? ''),
            'all_customers' => (int) ($vendor->all_customers ?? 0),
            'default_term_id' => (int) ($company?->default_term_id ?? 0),
            'default_customer_group_id' => (int) ($company?->default_customer_group_id ?? 0),
            'default_country_id' => $defaultCountryId,
            'sell_with_inventory' => (int) ($company?->sell_with_inventory ?? 0),
            'show_inventory' => (int) ($company?->show_inventory ?? 0),
            'format_numeric' => $formatNumeric,
            'currency_symbol' => $currencySymbol,
        ];
    }

    /**
     * @return list<array{group_id: int, name: string}>
     */
    public function customerGroups(int $companyId): array
    {
        if ($companyId <= 0) {
            return [];
        }

        return CustomerGroup::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get(['group_id', 'name'])
            ->map(static fn ($row) => [
                'group_id' => (int) $row->group_id,
                'name' => trim((string) ($row->name ?? '')),
            ])
            ->all();
    }

    /**
     * @return list<array{term_id: int, name: string}>
     */
    public function terms(int $companyId): array
    {
        if ($companyId <= 0 || ! Schema::hasTable('terms_of_sales')) {
            return [];
        }

        $q = TermsOfSale::query()->where('company_id', $companyId);
        if (Schema::hasColumn('terms_of_sales', 'status')) {
            $q->where('status', 1);
        } elseif (Schema::hasColumn('terms_of_sales', 'term_status')) {
            $q->where('term_status', 1);
        }

        return $q->orderBy('name')
            ->get(['term_id', 'name'])
            ->map(static fn ($row) => [
                'term_id' => (int) $row->term_id,
                'name' => trim((string) ($row->name ?? '')),
            ])
            ->all();
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    public function sellers(int $companyId): array
    {
        if ($companyId <= 0) {
            return [];
        }

        return Vendor::query()
            ->where('company_id', $companyId)
            ->where('status', 1)
            ->with(['user:id,name,email'])
            ->orderBy('id')
            ->get()
            ->map(function (Vendor $v) {
                $label = trim((string) ($v->user?->name ?? ''));
                if ($label === '' && $v->user !== null) {
                    $label = trim((string) ($v->user->email ?? ''));
                }

                return [
                    'id' => (int) $v->id,
                    'name' => $label !== '' ? $label : ('#' . (string) $v->id),
                ];
            })
            ->all();
    }

    /**
     * @return list<array{id: int, name: string, sortname: string, phonecode: string, phonemask: string}>
     */
    public function countries(): array
    {
        return Country::query()
            ->orderBy('name')
            ->get(['id', 'name', 'sortname', 'phonecode', 'phonemask'])
            ->map(static fn ($row) => [
                'id' => (int) $row->id,
                'name' => trim((string) ($row->name ?? '')),
                'sortname' => trim((string) ($row->sortname ?? '')),
                'phonecode' => trim((string) ($row->phonecode ?? '')),
                'phonemask' => trim((string) ($row->phonemask ?? '')),
            ])
            ->all();
    }
}

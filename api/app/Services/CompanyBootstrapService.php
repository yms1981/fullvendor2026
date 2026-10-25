<?php

namespace App\Services;

use App\Enums\CompanyUserTypes;
use App\Models\Address;
use App\Models\Category;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Country;
use App\Models\CustomerGroup;
use App\Models\SellerProfiles;
use App\Models\TermsOfSale;
use App\Models\Unit;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

/**
 * Datos por defecto al crear una compañía (categoría, grupo, término, perfil, vendedor, unidad, almacén).
 *
 * Réplica la lógica de {@see \CompanyModel::init_databydefault} del MVC usando Eloquent y
 * comprobaciones de esquema ({@see Schema::hasColumn}).
 */
final class CompanyBootstrapService
{
    /**
     * @return array<string, bool> Indica qué entidades se crearon en esta ejecución.
     */
    public function bootstrap(int $companyId, ?string $plainPassword = null): array
    {
        $created = [
            'category' => false,
            'customer_group' => false,
            'term' => false,
            'profile' => false,
            'seller' => false,
            'unit' => false,
            'warehouse' => false,
        ];

        DB::transaction(function () use ($companyId, $plainPassword, &$created) {
            $company = Company::query()->findOrFail($companyId);
            $defaults = $this->resolveBootstrapDefaults($company);

            if (! $this->hasDefaultCategory($companyId)) {
                $this->createDefaultCategory($companyId, $defaults);
                $created['category'] = true;
            }

            if (! $this->hasDefaultCustomerGroup($companyId, $company)) {
                $this->createDefaultCustomerGroup($companyId, $defaults);
                $created['customer_group'] = true;
            }

            if ($this->shouldCreateDefaultPaymentTerm($companyId, $company)) {
                $this->createDefaultPaymentTerm($companyId, $defaults);
                $created['term'] = true;
            }

            if (! $this->hasDefaultProfile($companyId)) {
                $this->createDefaultProfile($companyId, $defaults);
                $created['profile'] = true;
            }

            if (! $this->hasDefaultSeller($companyId, $company)) {
                $this->createDefaultSeller($companyId, $defaults, $company, $plainPassword);
                $created['seller'] = true;
            }

            if ($this->shouldCreateDefaultUnit($companyId, $company, $defaults)) {
                $this->createDefaultUnit($companyId, $defaults);
                $created['unit'] = true;
            }

            if ($this->shouldCreateDefaultWarehouse($companyId)) {
                $this->createDefaultWarehouse($companyId, $defaults, $company);
                $created['warehouse'] = true;
            }

            $this->syncCompanyDefaultUnitAndPaymentTermIds($companyId, $defaults);
        });

        return $created;
    }

    /**
     * @return array<string, string>
     */
    private function unitDefaultFallbackByLanguage(int $langId): string
    {
        $map = [
            1 => 'Unit',
            2 => 'Unidad',
            3 => 'Unidade',
            4 => 'Unità',
            5 => 'Единица',
            6 => 'Einheit',
            7 => '单位',
        ];

        return $map[$langId] ?? 'Unit';
    }

    /**
     * @return array<string, string>
     */
    private function resolveBootstrapDefaults(Company $company): array
    {
        $langId = (int) ($company->language_id ?? 1);
        $es = $langId === 2;

        $defaults = [
            'category_default' => $this->langString('category_default', $es ? 'Sin Categoria' : 'Uncategorized', $langId),
            'group_default' => $this->langString('group_default', 'Group Fullvendor', $langId),
            'term_default' => $this->langString('term_default', $es ? 'Contado' : 'Cash', $langId),
            'profile_default' => $this->langString('profile_default', $es ? 'Administrador' : 'Administrator', $langId),
            'username_default' => $this->langString('username_default', $es ? 'administrador' : 'administrator', $langId),
            'unit_default' => $this->langString('unit_default', $this->unitDefaultFallbackByLanguage($langId), $langId),
            'warehouse_default' => $this->langString('warehouse_default', $es ? 'Almacén 1' : 'Warehouse 1', $langId),
        ];

        foreach (['category_default', 'group_default', 'term_default', 'profile_default', 'username_default', 'unit_default', 'warehouse_default'] as $key) {
            if (trim($defaults[$key]) === '') {
                $defaults[$key] = $es
                    ? [
                        'category_default' => 'Sin Categoria',
                        'group_default' => 'Fullvendor Group',
                        'term_default' => 'Contado',
                        'profile_default' => 'Administrador',
                        'username_default' => 'administrador',
                        'unit_default' => 'Unidad',
                        'warehouse_default' => 'Almacén 1',
                    ][$key]
                    : [
                        'category_default' => 'Without Category',
                        'group_default' => 'Fullvendor Group',
                        'term_default' => 'Cash',
                        'profile_default' => 'Administrator',
                        'username_default' => 'administrator',
                        'unit_default' => $this->unitDefaultFallbackByLanguage($langId),
                        'warehouse_default' => 'Warehouse 1',
                    ][$key];
            }
        }

        return $defaults;
    }

    private function langString(string $key, string $fallback, int $langId): string
    {
        $map = [
            1 => 'en-EN.json',
            2 => 'es-ES.json',
            3 => 'pt-PT.json',
            4 => 'it-IT.json',
            5 => 'ru-RU.json',
            6 => 'de-DE.json',
            7 => 'zh-CN.json',
        ];
        $file = $map[$langId] ?? 'en-EN.json';
        $path = lang_path($file);
        if (! is_readable($path)) {
            return $fallback;
        }
        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return $fallback;
        }
        $data = json_decode($raw, true);
        if (! is_array($data) || ! isset($data['cmp-data-default'][$key]) || ! is_string($data['cmp-data-default'][$key])) {
            return $fallback;
        }

        return trim($data['cmp-data-default'][$key]) !== '' ? $data['cmp-data-default'][$key] : $fallback;
    }

    private function hasDefaultCategory(int $companyId): bool
    {
        return Category::query()
            ->where('company_id', $companyId)
            ->where('category_default', 1)
            ->exists();
    }

    private function hasDefaultCustomerGroup(int $companyId, Company $company): bool
    {
        if (Schema::hasColumn('customer_groups', 'default')) {
            return CustomerGroup::query()
                ->where('company_id', $companyId)
                ->where('default', 1)
                ->exists();
        }

        if (Schema::hasColumn('companies', 'default_customer_group_id')) {
            $groupId = (int) ($company->default_customer_group_id ?? 0);
            if ($groupId > 0) {
                return CustomerGroup::query()
                    ->where('company_id', $companyId)
                    ->where('group_id', $groupId)
                    ->exists();
            }
        }

        return false;
    }

    private function shouldCreateDefaultPaymentTerm(int $companyId, Company $company): bool
    {
        if (Schema::hasColumn('companies', 'default_term_id')) {
            if ((int) ($company->default_term_id ?? 0) > 0) {
                return false;
            }
        }

        if (Schema::hasColumn('terms_of_sales', 'default')) {
            return ! TermsOfSale::query()
                ->where('company_id', $companyId)
                ->where('default', 1)
                ->exists();
        }

        return ! TermsOfSale::query()
            ->where('company_id', $companyId)
            ->exists();
    }

    private function hasDefaultProfile(int $companyId): bool
    {
        return SellerProfiles::query()
            ->where('company_id', $companyId)
            ->where('default', 1)
            ->exists();
    }

    private function hasDefaultSeller(int $companyId, Company $company): bool
    {
        if (Schema::hasColumn('sellers', 'default')) {
            return Vendor::query()
                ->where('company_id', $companyId)
                ->where('default', 1)
                ->exists();
        }

        if (Schema::hasColumn('companies', 'default_seller_id')) {
            $sellerId = (int) ($company->default_seller_id ?? 0);
            if ($sellerId > 0) {
                return Vendor::query()
                    ->where('company_id', $companyId)
                    ->where('id', $sellerId)
                    ->exists();
            }
        }

        return Vendor::query()
            ->where('company_id', $companyId)
            ->exists();
    }

  /**
     * @param array<string, string> $defaults
     */
    private function shouldCreateDefaultUnit(int $companyId, Company $company, array $defaults): bool
    {
        if (Schema::hasColumn('companies', 'default_unit_id')) {
            if ((int) ($company->default_unit_id ?? 0) > 0) {
                return false;
            }
        }

        $unitLabel = trim($defaults['unit_default']);
        if ($unitLabel !== '' && Unit::query()->where('company_id', $companyId)->where('unit', $unitLabel)->exists()) {
            return false;
        }

        if (Schema::hasColumn('units', 'default')) {
            return ! Unit::query()
                ->where('company_id', $companyId)
                ->where('default', 1)
                ->exists();
        }

        return Unit::query()->where('company_id', $companyId)->count() < 1;
    }

    private function shouldCreateDefaultWarehouse(int $companyId): bool
    {
        if (! Schema::hasTable('warehouses')) {
            return false;
        }

        return ! Warehouse::query()->where('company_id', $companyId)->exists();
    }

    /**
     * @param array<string, string> $defaults
     */
    private function createDefaultCategory(int $companyId, array $defaults): void
    {
        Category::query()->create([
            'company_id' => $companyId,
            'category_name' => $defaults['category_default'],
            'category_status' => 1,
            'category_default' => 1,
        ]);
    }

    /**
     * @param array<string, string> $defaults
     */
    private function createDefaultCustomerGroup(int $companyId, array $defaults): void
    {
        $payload = [
            'company_id' => $companyId,
            'name' => $defaults['group_default'],
            'percentage_on_price' => null,
            'percent_price_amount' => null,
            'group_status' => 1,
        ];
        if (Schema::hasColumn('customer_groups', 'default')) {
            $payload['default'] = 1;
        }

        $group = CustomerGroup::query()->create($payload);

        if (Schema::hasColumn('companies', 'default_customer_group_id')) {
            Company::query()
                ->where('company_id', $companyId)
                ->update(['default_customer_group_id' => $group->group_id]);
        }
    }

    /**
     * @param array<string, string> $defaults
     */
    private function createDefaultPaymentTerm(int $companyId, array $defaults): void
    {
        $payload = [
            'company_id' => $companyId,
            'name' => $defaults['term_default'],
        ];
        if (Schema::hasColumn('terms_of_sales', 'default')) {
            $payload['default'] = 1;
        }
        if (Schema::hasColumn('terms_of_sales', 'term_status')) {
            $payload['term_status'] = 1;
        }

        TermsOfSale::query()->create($this->filterExistingColumns('terms_of_sales', $payload));
    }

    /**
     * @param array<string, string> $defaults
     */
    private function createDefaultProfile(int $companyId, array $defaults): void
    {
        SellerProfiles::query()->create([
            'profile_name' => $defaults['profile_default'],
            'company_id' => $companyId,
            'order_discount' => 1,
            'order_disc_price' => 1,
            'order_net_discount' => 1,
            'order_net_disc_price' => 1,
            'can_change_price' => 1,
            'can_send_catalog' => 1,
            'can_create_customer' => 1,
            'status' => 1,
            'default' => 1,
        ]);
    }

    /**
     * @param array<string, string> $defaults
     */
    private function createDefaultSeller(int $companyId, array $defaults, Company $company, ?string $plainPassword): void
    {
        $profile = SellerProfiles::query()
            ->where('company_id', $companyId)
            ->where('default', 1)
            ->first();

        if (Schema::hasColumn('sellers', 'username')) {
            $legacy = [
                'company_id' => $companyId,
                'username' => $defaults['username_default'],
                'first_name' => $defaults['profile_default'],
                'last_name' => '',
                'email' => $company->email,
                'phone_number' => '--',
                'cell_number' => '--',
                'password' => $company->password,
                'profile_image' => null,
                'fax' => '',
                'status' => 1,
                'email_verification' => 1,
                'profile' => $profile?->profile_id,
                'all_customers' => 1,
            ];
            if (Schema::hasColumn('sellers', 'default')) {
                $legacy['default'] = 1;
            }
            $vendor = Vendor::query()->create($this->filterExistingColumns('sellers', $legacy));
            if (Schema::hasColumn('companies', 'default_seller_id')) {
                Company::query()
                    ->where('company_id', $companyId)
                    ->update(['default_seller_id' => $vendor->id]);
            }

            return;
        }

        $payload = [
            'company_id' => $companyId,
            'phone_number' => '--',
            'cell_number' => '--',
            'status' => 1,
            'all_customers' => 1,
            'proforma' => 0,
        ];
        if (Schema::hasColumn('sellers', 'email_on_salesman_order')) {
            $payload['email_on_salesman_order'] = 1;
        }

        $userId = null;
        if (Schema::hasColumn('sellers', 'user_id')) {
            $userId = $this->resolveOrCreateDefaultSellerUser($company, $defaults, $plainPassword);
            $payload['user_id'] = $userId;
        }

        $vendor = Vendor::query()->create($this->filterExistingColumns('sellers', $payload));

        if (Schema::hasColumn('companies', 'default_seller_id')) {
            Company::query()
                ->where('company_id', $companyId)
                ->update(['default_seller_id' => $vendor->id]);
        }

        if ($userId !== null && $profile !== null) {
            CompanyUser::query()->firstOrCreate(
                [
                    'company_id' => $companyId,
                    'user_id' => $userId,
                    'type' => CompanyUserTypes::Seller->value,
                ],
                [
                    'profileable_id' => $profile->profile_id,
                    'profileable_type' => SellerProfiles::class,
                ]
            );
        }
    }

    /**
     * @param array<string, string> $defaults
     */
    private function resolveOrCreateDefaultSellerUser(Company $company, array $defaults, ?string $plainPassword): int
    {
        $email = $this->defaultSellerUserEmail($company, $defaults);
        $existing = User::query()->where('email', $email)->first();
        if ($existing !== null) {
            return (int) $existing->id;
        }

        $user = new User([
            'name' => $defaults['profile_default'],
            'first_name' => $defaults['profile_default'],
            'last_name' => '',
            'email' => $email,
            'language_id' => (int) ($company->language_id ?? 1),
        ]);

        if ($plainPassword !== null && $plainPassword !== '') {
            app(UserPasswordService::class)->apply($user, $plainPassword);
        } elseif (is_string($company->password) && $company->password !== '') {
            // Ya es hash de companies; no hay plain disponible.
            $user->password = $company->password;
        } else {
            $generated = bin2hex(random_bytes(8));
            app(UserPasswordService::class)->apply($user, $generated);
        }

        $user->save();

        return (int) $user->id;
    }

    /**
     * @param array<string, string> $defaults
     */
    private function defaultSellerUserEmail(Company $company, array $defaults): string
    {
        $username = preg_replace('/[^a-z0-9._+-]/i', '', (string) ($defaults['username_default'] ?? 'seller'));
        if ($username === '') {
            $username = 'seller';
        }

        return strtolower($username) . '+c' . $company->company_id . '@seller.fullvendor.local';
    }

    /**
     * @param array<string, string> $defaults
     */
    private function createDefaultWarehouse(int $companyId, array $defaults, Company $company): void
    {
        $name = trim($defaults['warehouse_default']);
        if ($name === '') {
            $name = 'Warehouse 1';
        }

        $countryId = $this->resolveDefaultWarehouseCountryId($company);
        $address = Address::query()->create([
            'street' => '',
            'street_number' => '',
            'country_id' => $countryId,
            'state_id' => null,
            'city_id' => null,
            'zipcode' => '',
        ]);

        Warehouse::query()->create([
            'name' => $name,
            'company_id' => $companyId,
            'address_id' => $address->id,
        ]);
    }

    private function resolveDefaultWarehouseCountryId(Company $company): int
    {
        $countryId = (int) ($company->country ?? 0);
        if ($countryId > 0 && Country::query()->where('id', $countryId)->exists()) {
            return $countryId;
        }

        $fallback = Country::query()->orderBy('id')->value('id');

        return (int) ($fallback ?? 1);
    }

    /**
     * @param array<string, string> $defaults
     */
    private function createDefaultUnit(int $companyId, array $defaults): void
    {
        $unitLabel = trim($defaults['unit_default']);
        if ($unitLabel === '') {
            $unitLabel = 'Unit';
        }

        $payload = [
            'company_id' => $companyId,
            'unit' => $unitLabel,
            'formato' => null,
            'description' => $unitLabel,
            'status' => 1,
        ];
        if (Schema::hasColumn('units', 'default')) {
            $payload['default'] = 1;
        }

        Unit::query()->create($this->filterExistingColumns('units', $payload));
    }

    /**
     * @param array<string, string> $defaults
     */
    private function syncCompanyDefaultUnitAndPaymentTermIds(int $companyId, array $defaults = []): void
    {
        $company = Company::query()->find($companyId);
        if ($company === null) {
            return;
        }

        $patch = [];

        if (Schema::hasColumn('companies', 'default_unit_id')
            && (int) ($company->default_unit_id ?? 0) < 1) {
            $unit = null;
            if (Schema::hasColumn('units', 'default')) {
                $unit = Unit::query()
                    ->where('company_id', $companyId)
                    ->where('default', 1)
                    ->first();
            }
            $unitLabel = trim($defaults['unit_default'] ?? '');
            if ($unit === null && $unitLabel !== '') {
                $unit = Unit::query()
                    ->where('company_id', $companyId)
                    ->where('unit', $unitLabel)
                    ->first();
            }
            if ($unit === null) {
                $unit = Unit::query()
                    ->where('company_id', $companyId)
                    ->orderBy('unit_id')
                    ->first();
            }
            if ($unit !== null) {
                $patch['default_unit_id'] = (int) $unit->unit_id;
            }
        }

        if (Schema::hasColumn('companies', 'default_term_id')
            && (int) ($company->default_term_id ?? 0) < 1) {
            $term = TermsOfSale::query()
                ->where('company_id', $companyId)
                ->orderBy('term_id')
                ->first();
            if ($term !== null) {
                $patch['default_term_id'] = (int) $term->term_id;
            }
        }

        if ($patch !== []) {
            Company::query()
                ->where('company_id', $companyId)
                ->update($patch);
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function filterExistingColumns(string $table, array $payload): array
    {
        $filtered = [];
        foreach ($payload as $column => $value) {
            if (Schema::hasColumn($table, $column)) {
                $filtered[$column] = $value;
            }
        }

        return $filtered;
    }
}

<?php

namespace App\Services;

use App\Enums\CompanyUserTypes;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Customer;
use App\Models\CustomerUser;
use App\Models\Language;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Si el cliente tiene email y no existe en users, crea el usuario portal
 * con contraseña por defecto 12345678 (hash + password_plain) y lo vincula.
 * Si el usuario ya existe, solo asegura vínculos company_user / customer_user.
 */
class EnsureCustomerPortalUserService
{
    public const DEFAULT_PASSWORD = '12345678';

    public function __construct(
        private readonly UserPasswordService $passwords
    ) {}

    /**
     * Clientes con email válido sin user portal (o con password en blanco): asegurar.
     *
     * @return array{scanned:int, fixed:int, failed:int}
     */
    public function ensureMissing(?int $companyId = null, int $limit = 500): array
    {
        $stats = ['scanned' => 0, 'fixed' => 0, 'failed' => 0];

        $hasPivotDeletedAt = Schema::hasColumn('customer_user', 'deleted_at');
        $pivotActive = $hasPivotDeletedAt ? ' AND cu.deleted_at IS NULL' : '';

        $query = Customer::query()
            ->whereNotNull('email')
            ->whereRaw("TRIM(email) <> ''")
            ->where(function ($outer) use ($pivotActive) {
                // Sin user vinculado con el mismo email del cliente.
                $outer->whereRaw(
                    "NOT EXISTS (
                        SELECT 1
                        FROM users u
                        INNER JOIN customer_user cu
                            ON cu.user_id = u.id
                           AND cu.customer_id = customers.customer_id
                        WHERE LOWER(TRIM(u.email)) = LOWER(TRIM(customers.email))
                        {$pivotActive}
                    )"
                )->orWhereRaw(
                    // User vinculado pero password / password_plain vacío.
                    "EXISTS (
                        SELECT 1
                        FROM users u
                        INNER JOIN customer_user cu
                            ON cu.user_id = u.id
                           AND cu.customer_id = customers.customer_id
                        WHERE LOWER(TRIM(u.email)) = LOWER(TRIM(customers.email))
                        {$pivotActive}
                          AND (
                                u.password IS NULL OR TRIM(u.password) = ''
                             OR u.password_plain IS NULL OR TRIM(u.password_plain) = ''
                          )
                    )"
                );
            });

        if ($companyId !== null && $companyId > 0) {
            $query->where('company_id', $companyId);
        }

        $customers = $query
            ->orderBy('customer_id')
            ->limit(max(1, $limit))
            ->get();

        foreach ($customers as $customer) {
            $stats['scanned']++;
            $user = $this->ensure($customer);
            if ($user === null) {
                $stats['failed']++;
                continue;
            }
            $stats['fixed']++;
        }

        return $stats;
    }

    public function ensure(Customer $customer, ?string $plainPassword = null): ?User
    {
        $email = mb_strtolower(trim((string) ($customer->email ?? '')), 'UTF-8');
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $customerId = (int) ($customer->customer_id ?? 0);
        $companyId = (int) ($customer->company_id ?? 0);
        if ($customerId < 1 || $companyId < 1) {
            return null;
        }

        $password = trim((string) $plainPassword);
        if ($password === '') {
            $password = self::DEFAULT_PASSWORD;
        }

        try {
            return DB::transaction(function () use ($customer, $email, $customerId, $companyId, $password) {
                $user = User::query()
                    ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
                    ->orderBy('id')
                    ->first();

                $created = false;
                if ($user === null) {
                    $user = $this->createPortalUser($customer, $email, $companyId, $password);
                    $created = true;
                } elseif ($user->isDisabled()) {
                    $user->deleted_at = null;
                    $user->save();
                }

                $this->attachCompanyRole($user, $companyId);
                $this->attachCustomerLink($user, $customerId);

                // Sin password / password_plain vacío → default (o la del request).
                $this->ensurePasswordIfBlank($user, $password);

                if ($created) {
                    Log::info('EnsureCustomerPortalUserService: portal user created', [
                        'customer_id' => $customerId,
                        'user_id' => $user->id,
                        'email' => $email,
                    ]);
                }

                return $user->fresh() ?? $user;
            });
        } catch (\Throwable $e) {
            Log::error('EnsureCustomerPortalUserService failed: '.$e->getMessage(), [
                'customer_id' => $customerId,
                'email' => $email,
            ]);

            return null;
        }
    }

    private function createPortalUser(Customer $customer, string $email, int $companyId, string $plainPassword): User
    {
        $displayName = trim((string) ($customer->name ?? ''));
        if ($displayName === '') {
            $displayName = trim((string) ($customer->business_name ?? ''));
        }
        if ($displayName === '') {
            $displayName = $email;
        }

        $parts = preg_split('/\s+/u', $displayName, 2) ?: [];
        $first = trim((string) ($parts[0] ?? $displayName));
        $last = trim((string) ($parts[1] ?? ''));

        $user = User::make([
            'name' => $displayName,
            'first_name' => $first,
            'last_name' => $last,
            'email' => $email,
            'phone' => (string) ($customer->phone ?? ''),
            'cellphone' => (string) ($customer->cell_phone ?? ''),
            'fax' => '',
            'address' => (string) ($customer->commercial_address ?? $customer->address ?? ''),
            'city' => (string) ($customer->commercial_city ?? ''),
            'state' => (string) ($customer->commercial_state ?? ''),
            'zip_code' => (string) ($customer->commercial_zip_code ?? ''),
        ]);
        $user->language_id = $this->resolveLanguageId($customer, $companyId);

        $this->passwords->apply($user, $plainPassword !== '' ? $plainPassword : self::DEFAULT_PASSWORD);
        $user->save();

        return $user;
    }

    private function resolveLanguageId(Customer $customer, int $companyId): int
    {
        $fromCustomer = (int) ($customer->language_id ?? 0);
        if ($fromCustomer > 0) {
            return $fromCustomer;
        }

        $fromCompany = (int) (Company::query()->where('company_id', $companyId)->value('language_id') ?? 0);
        if ($fromCompany > 0) {
            return $fromCompany;
        }

        $default = Language::query()->where('default', true)->value('language_id')
            ?? Language::query()->value('language_id');

        return (int) ($default ?: 1);
    }

    /**
     * Si password o password_plain están vacío, aplica el fallback (por defecto 12345678).
     */
    private function ensurePasswordIfBlank(User $user, string $fallbackPassword): void
    {
        $fallback = trim($fallbackPassword) !== '' ? trim($fallbackPassword) : self::DEFAULT_PASSWORD;
        $plain = trim((string) ($user->password_plain ?? ''));
        $hash = trim((string) ($user->password ?? ''));

        if ($plain !== '' && $hash !== '') {
            return;
        }

        // Intentar recuperar plano conocido antes de resetear.
        $this->passwords->recoverPlainIfPossible($user);
        $user->refresh();
        $plain = trim((string) ($user->password_plain ?? ''));
        $hash = trim((string) ($user->password ?? ''));
        if ($plain !== '' && $hash !== '') {
            return;
        }

        $this->passwords->apply($user, $fallback);
        $user->save();
    }

    private function attachCompanyRole(User $user, int $companyId): void
    {
        CompanyUser::query()->firstOrCreate(
            [
                'company_id' => $companyId,
                'user_id' => $user->id,
                'type' => CompanyUserTypes::Customer->value,
            ],
            [
                'company_id' => $companyId,
                'user_id' => $user->id,
                'type' => CompanyUserTypes::Customer->value,
            ]
        );
    }

    private function attachCustomerLink(User $user, int $customerId): void
    {
        $query = CustomerUser::query()
            ->where('customer_id', $customerId)
            ->where('user_id', $user->id);

        $link = $query->first();
        if ($link === null) {
            CustomerUser::query()->create([
                'customer_id' => $customerId,
                'user_id' => $user->id,
            ]);

            return;
        }

        if (Schema::hasColumn('customer_user', 'deleted_at') && ! empty($link->deleted_at)) {
            $link->deleted_at = null;
            $link->save();
        }
    }
}

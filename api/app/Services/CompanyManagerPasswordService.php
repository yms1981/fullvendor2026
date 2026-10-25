<?php

namespace App\Services;

use App\Enums\CompanyUserTypes;
use App\Models\Company;
use App\Models\User;

/**
 * Mantiene users.password del manager alineado con companies.password
 * (el login /login usa Fortify contra users, no companies).
 */
class CompanyManagerPasswordService
{
    public static function syncFromCompany(Company $company): bool
    {
        $hash = trim((string) ($company->getRawOriginal('password') ?? $company->getAttributes()['password'] ?? ''));
        if ($hash === '') {
            return false;
        }

        $manager = self::resolveManagerUser($company);
        if ($manager === null) {
            return false;
        }

        $stored = trim((string) ($manager->getRawOriginal('password') ?? ''));
        if ($stored === $hash) {
            return true;
        }

        $manager->password = $hash;
        $manager->save();

        return true;
    }

    private static function resolveManagerUser(Company $company): ?User
    {
        $manager = $company->users()
            ->wherePivot('type', CompanyUserTypes::Manager->value)
            ->first();

        if ($manager instanceof User) {
            return $manager;
        }

        $email = strtolower(trim((string) ($company->email ?? '')));
        if ($email === '') {
            return null;
        }

        return User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('deleted_at')
            ->first();
    }
}

<?php

namespace App\Http\Responses;

use App\Models\User;
use App\Support\AvatarUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Misma carga útil que tras login Fortify (CustomAuthController) para MVC / apps.
 * Campos nuevos (avatar_url) se añaden sin alterar la estructura existente.
 */
class LoginSuccessJsonResponse
{
    private const COMPANY_SELECT = ['companies.company_id', 'name', 'image'];

    public static function forUser(User $user, Request $request): JsonResponse
    {
        $personalTokenName = $request->input('device_name') ?: 'mvc';
        $token = $user->createToken($personalTokenName)->plainTextToken;

        return response()->json(self::buildPayload($user, $token));
    }

    /**
     * Payload compartido (login Fortify, admin platform, mobile).
     *
     * @return array{user: User, app_access: array<string, mixed>, companies: Collection<int, array<string, mixed>>}
     */
    public static function buildPayload(User $user, ?string $plainTextToken = null, bool $warehouseKeyTypo = true): array
    {
        $warehouseKey = $warehouseKeyTypo ? 'wharehouse' : 'warehouse';

        $companyPanelCompanies = self::resolveCompanyPanelCompanies($user);

        $appAccess = [
            'admin' => ['access' => $user->isAdmin()],
            'company' => [
                'access' => $companyPanelCompanies->isNotEmpty(),
                'companies' => self::mapCompanies($user, $companyPanelCompanies),
            ],
            'marketplace' => [
                'access' => $user->isCustomer(),
                'companies' => self::mapCompanies(
                    $user,
                    $user->companiesForRole('customer')->select(self::COMPANY_SELECT)->get()
                ),
            ],
            $warehouseKey => [
                'access' => $user->isWarehouse(),
                'companies' => self::mapCompanies(
                    $user,
                    $user->companiesForRole('warehouse')->select(self::COMPANY_SELECT)->get()
                ),
            ],
            'salesman' => [
                'access' => $user->isSeller(),
                'companies' => self::mapCompanies(
                    $user,
                    $user->companiesForRole('seller')->select(self::COMPANY_SELECT)->get()
                ),
            ],
        ];

        $user->setAttribute('avatar_url', AvatarUrl::forUser($user->image ?? null));
        if ($plainTextToken !== null) {
            $user->token = $plainTextToken;
        }

        return [
            'user' => $user,
            'app_access' => $appAccess,
            'companies' => self::mapCompanies(
                $user,
                $user->companies()->select(self::COMPANY_SELECT)->get()
            ),
        ];
    }

    /**
     * Panel MVC /company: todos los managers y todos los sellers.
     * Si el mismo user tiene ambos roles en una compañía, prevalece manager.
     *
     * @return Collection<int, \App\Models\Company>
     */
    private static function resolveCompanyPanelCompanies(User $user): Collection
    {
        $companies = $user->companies()
            ->wherePivotIn('type', ['manager', 'seller'])
            ->select(self::COMPANY_SELECT)
            ->get();

        $byId = [];
        foreach ($companies as $company) {
            $id = (int) $company->company_id;
            if ($id < 1) {
                continue;
            }
            $type = strtolower(trim((string) ($company->pivot->type ?? '')));
            if (! isset($byId[$id]) || $type === 'manager') {
                $byId[$id] = $company;
            }
        }

        return collect(array_values($byId));
    }

    /**
     * @param  Collection<int, \App\Models\Company>  $companies
     * @return Collection<int, array<string, mixed>>
     */
    public static function mapCompanies(User $user, Collection $companies): Collection
    {
        return $companies->map(fn ($company) => [
            'company_id' => $company->company_id,
            'name' => $company->name,
            'avatar_url' => AvatarUrl::forCompany($company->image ?? null),
            'roles' => [
                'type' => $company->pivot->type,
                'abilities' => $user->abilities($company->pivot->type),
            ],
        ]);
    }
}

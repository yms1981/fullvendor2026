<?php

namespace App\Http\Controllers;

use App\Enums\CompanyUserTypes;
use App\Models\Company;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Login público para vendedores (portal B2B). Valida credenciales y fila `sellers` (Vendor).
 */
class SellerPortalLoginController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
                'company_id' => 'nullable|integer',
                'seller_id' => 'nullable|integer',
            ]);

            if (! Schema::hasTable('users') || ! Schema::hasTable('sellers')) {
                return response()->json([
                    'success' => false,
                    'code' => 'seller_login_server_error',
                ], 500);
            }

            $user = User::query()
                ->where('email', $request->input('email'))
                ->first();

            if ($user === null) {
                return response()->json([
                    'success' => false,
                    'code' => 'seller_login_email_not_found',
                ], 404);
            }

            if (! $this->userPasswordValid($user, (string) $request->input('password'))) {
                return response()->json([
                    'success' => false,
                    'code' => 'seller_login_invalid_password',
                ], 401);
            }

            $resolved = $this->resolvePortalSeller($user, $request);

            if (! empty($resolved['not_seller'])) {
                return response()->json([
                    'success' => false,
                    'code' => 'seller_login_not_seller',
                ], 403);
            }

            if (! empty($resolved['needs_selection'])) {
                return response()->json([
                    'success' => false,
                    'code' => 'seller_login_select_company',
                    'companies' => $resolved['companies'],
                    'sellers' => $resolved['sellers'],
                ], 422);
            }

            $vendorPayload = null;
            if ((int) $resolved['seller_id'] > 0) {
                // Evitar doble eager load: el modelo Vendor tiene $with = ['company'].
                $vendorPayload = Vendor::query()
                    ->withoutEagerLoad(['company'])
                    ->with(['company' => static function ($q) {
                        $q->select(
                            'company_id',
                            'name',
                            'status',
                            'language_id',
                            'default_term_id',
                            'default_customer_group_id',
                            'default_seller_id'
                        );
                    }])
                    ->where('id', (int) $resolved['seller_id'])
                    ->first();
            }

            $companyName = (string) ($vendorPayload?->company?->name ?? '');
            if ($companyName === '' && (int) $resolved['company_id'] > 0) {
                $companyName = (string) (Company::query()
                    ->where('company_id', (int) $resolved['company_id'])
                    ->value('name') ?? '');
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => (int) $user->id,
                    'seller_id' => $resolved['seller_id'],
                    'company_id' => $resolved['company_id'],
                    'language_id' => $resolved['language_id'],
                    'user_type' => 'seller',
                    'is_seller' => true,
                    'company_name' => $companyName,
                    'all_customers' => (int) ($vendorPayload?->all_customers ?? 0),
                    'default_term_id' => (int) ($vendorPayload?->company?->default_term_id ?? 0),
                    'default_customer_group_id' => (int) ($vendorPayload?->company?->default_customer_group_id ?? 0),
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('SellerPortalLoginController: '.$e->getMessage(), ['exception' => $e]);

            return response()->json([
                'success' => false,
                'code' => 'seller_login_server_error',
            ], 500);
        }
    }

    /**
     * @return array{
     *     company_id: int,
     *     seller_id: int,
     *     language_id: int,
     *     not_seller: bool,
     *     needs_selection: bool,
     *     companies: list<array{company_id: int, company_name: string}>|null,
     *     sellers: list<array{seller_id: int, company_id: int, label: string}>|null
     * }
     */
    private function resolvePortalSeller(User $user, Request $request): array
    {
        $languageId = $this->portalLanguageFromUser($user);

        $vendorRows = Vendor::query()
            ->where('user_id', (int) $user->id)
            ->where('status', 1)
            ->with(['company' => static function ($q) {
                $q->select('company_id', 'name', 'status', 'language_id');
            }])
            ->get()
            ->filter(static function (Vendor $v) {
                return $v->company !== null && (int) $v->company->status === 1;
            })
            ->values();

        if ($vendorRows->isEmpty()) {
            return $this->resolvedNotSeller($languageId);
        }

        $vendorRows = $vendorRows->filter(function (Vendor $v) use ($user) {
            return $this->userHasSellerRoleForCompany((int) $user->id, (int) $v->company_id);
        })->values();

        if ($vendorRows->isEmpty()) {
            return $this->resolvedNotSeller($languageId);
        }

        $reqCompanyId = (int) $request->input('company_id', 0);
        $reqSellerId = (int) $request->input('seller_id', 0);
        if ($reqCompanyId > 0 && $reqSellerId > 0) {
            $picked = $vendorRows->first(static function (Vendor $v) use ($reqCompanyId, $reqSellerId) {
                return (int) $v->company_id === $reqCompanyId && (int) $v->getKey() === $reqSellerId;
            });
            if ($picked !== null) {
                $languageId = $this->applyCompanyLanguage($picked->company, $languageId);

                return $this->resolvedSuccess((int) $picked->company_id, (int) $picked->getKey(), $languageId);
            }
        }

        if ($vendorRows->count() === 1) {
            $v = $vendorRows->first();
            $languageId = $this->applyCompanyLanguage($v->company, $languageId);

            return $this->resolvedSuccess((int) $v->company_id, (int) $v->getKey(), $languageId);
        }

        $companiesMap = [];
        foreach ($vendorRows as $v) {
            $cid = (int) $v->company_id;
            if (! isset($companiesMap[$cid]) && $v->company !== null) {
                $companiesMap[$cid] = [
                    'company_id' => $cid,
                    'company_name' => (string) ($v->company->name ?? ''),
                ];
            }
        }

        return [
            'company_id' => 0,
            'seller_id' => 0,
            'language_id' => $languageId,
            'not_seller' => false,
            'needs_selection' => true,
            'companies' => array_values($companiesMap),
            'sellers' => $vendorRows->map(static function (Vendor $v) {
                $name = $v->company !== null ? (string) ($v->company->name ?? '') : '';

                return [
                    'seller_id' => (int) $v->getKey(),
                    'company_id' => (int) $v->company_id,
                    'label' => $name !== '' ? $name.' (#'.(int) $v->getKey().')' : '#'.(int) $v->getKey(),
                ];
            })->values()->all(),
        ];
    }

    private function userHasSellerRoleForCompany(int $userId, int $companyId): bool
    {
        // Sin pivote de roles no se puede comprobar el tipo; no permitir acceso (solo rol seller).
        if (! Schema::hasTable('company_user')) {
            return false;
        }

        return DB::table('company_user')
            ->where('user_id', $userId)
            ->where('company_id', $companyId)
            ->where('type', CompanyUserTypes::Seller->value)
            ->exists();
    }

    /**
     * @return array{
     *     company_id: int,
     *     seller_id: int,
     *     language_id: int,
     *     not_seller: bool,
     *     needs_selection: bool,
     *     companies: null,
     *     sellers: null
     * }
     */
    private function resolvedSuccess(int $companyId, int $sellerId, int $languageId): array
    {
        return [
            'company_id' => $companyId,
            'seller_id' => $sellerId,
            'language_id' => $languageId,
            'not_seller' => false,
            'needs_selection' => false,
            'companies' => null,
            'sellers' => null,
        ];
    }

    /**
     * @return array{
     *     company_id: int,
     *     seller_id: int,
     *     language_id: int,
     *     not_seller: bool,
     *     needs_selection: bool,
     *     companies: null,
     *     sellers: null
     * }
     */
    private function resolvedNotSeller(int $languageId): array
    {
        return [
            'company_id' => 0,
            'seller_id' => 0,
            'language_id' => $languageId,
            'not_seller' => true,
            'needs_selection' => false,
            'companies' => null,
            'sellers' => null,
        ];
    }

    private function portalLanguageFromUser(User $user): int
    {
        $languageId = 1;
        if (Schema::hasColumn('users', 'language_id')) {
            $lid = (int) ($user->language_id ?? 0);
            if ($lid >= 1 && $lid <= 7) {
                $languageId = $lid;
            }
        }

        return $languageId;
    }

    private function applyCompanyLanguage(?Company $company, int $languageId): int
    {
        if ($company !== null && (int) $company->status === 1 && $company->language_id !== null) {
            $cl = (int) $company->language_id;
            if ($cl >= 1 && $cl <= 7) {
                return $cl;
            }
        }

        return $languageId;
    }

    private function userPasswordValid(User $user, string $plainPassword): bool
    {
        if ($plainPassword === '') {
            return false;
        }
        $hash = (string) ($user->password ?? '');
        if ($hash === '') {
            return false;
        }

        return Hash::check($plainPassword, $hash) || password_verify($plainPassword, $hash);
    }
}

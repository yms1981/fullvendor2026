<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Login público para clientes (portal B2B).
 * Valida credenciales contra `users` y permite acceso solo a usuarios
 * vinculados como cliente mediante la pivote `customer_user`.
 */
class CustomerPortalLoginController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required|string',
                'company_id' => 'nullable|integer',
                'customer_id' => 'nullable|integer',
            ]);

            if (! Schema::hasTable('users')) {
                return response()->json([
                    'success' => false,
                    'code' => 'customer_login_server_error',
                ], 500);
            }

            $user = User::query()
                ->where('email', $request->input('email'))
                ->first();

            if ($user === null) {
                return response()->json([
                    'success' => false,
                    'code' => 'customer_login_email_not_found',
                ], 404);
            }

            if (! $this->userPasswordValid($user, (string) $request->input('password'))) {
                return response()->json([
                    'success' => false,
                    'code' => 'customer_login_invalid_password',
                ], 401);
            }

            $resolved = $this->resolvePortalIdentifiers($user, $request);

            if (! empty($resolved['not_customer'])) {
                return response()->json([
                    'success' => false,
                    'code' => 'customer_login_not_customer',
                ], 403);
            }

            if (! empty($resolved['needs_selection'])) {
                return response()->json([
                    'success' => false,
                    'code' => 'customer_login_select_company',
                    'companies' => $resolved['companies'],
                    'customers' => $resolved['customers'],
                ], 422);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => (int) $user->id,
                    'customer_id' => $resolved['customer_id'],
                    'company_id' => $resolved['company_id'],
                    'language_id' => $resolved['language_id'],
                    'user_type' => 'customer',
                    'is_customer' => true,
                ],
            ]);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('CustomerPortalLoginController: '.$e->getMessage(), ['exception' => $e]);

            return response()->json([
                'success' => false,
                'code' => 'customer_login_server_error',
            ], 500);
        }
    }

    /**
     * @return array{
     *     company_id: int,
     *     customer_id: int,
     *     language_id: int,
     *     not_customer: bool,
     *     needs_selection: bool,
     *     companies: list<array{company_id: int, company_name: string}>|null,
     *     customers: list<array{customer_id: int, company_id: int, name: ?string, business_name: ?string}>|null
     * }
     */
    private function resolvePortalIdentifiers(User $user, Request $request): array
    {
        $languageId = $this->portalLanguageFromUser($user);
        if (! Schema::hasTable('customer_user')) {
            return $this->resolvedNotCustomer($languageId);
        }

        $pivotCustomerIds = DB::table('customer_user')
            ->where('user_id', (int) $user->id)
            ->pluck('customer_id')
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn (int $id) => $id > 0)
            ->unique()
            ->values();

        if ($pivotCustomerIds->isEmpty()) {
            return $this->resolvedNotCustomer($languageId);
        }

        $candidates = Customer::query()
            ->with(['company' => static function ($q) {
                $q->select('company_id', 'name', 'status', 'language_id');
            }])
            ->whereIn('customer_id', $pivotCustomerIds->all())
            ->where('customer_status', 1)
            ->get()
            ->filter(static function (Customer $c) {
                return $c->company !== null && (int) $c->company->status === 1;
            })
            ->values();

        if ($candidates->isEmpty()) {
            return $this->resolvedNotCustomer($languageId);
        }

        $reqCompanyId = (int) $request->input('company_id', 0);
        $reqCustomerId = (int) $request->input('customer_id', 0);
        if ($reqCompanyId > 0 && $reqCustomerId > 0) {
            $picked = $candidates->first(static function (Customer $c) use ($reqCompanyId, $reqCustomerId) {
                return (int) $c->company_id === $reqCompanyId && (int) $c->customer_id === $reqCustomerId;
            });
            if ($picked !== null) {
                $languageId = $this->applyCompanyLanguage($picked->company, $languageId);

                return $this->resolvedSuccess(
                    (int) $picked->company_id,
                    (int) $picked->customer_id,
                    $languageId
                );
            }
        }

        if ($candidates->count() === 1) {
            $c = $candidates->first();
            $languageId = $this->applyCompanyLanguage($c->company, $languageId);

            return $this->resolvedSuccess(
                (int) $c->company_id,
                (int) $c->customer_id,
                $languageId
            );
        }

        $companiesMap = [];
        foreach ($candidates as $c) {
            $cid = (int) $c->company_id;
            if (! isset($companiesMap[$cid])) {
                $companiesMap[$cid] = [
                    'company_id' => $cid,
                    'company_name' => (string) ($c->company->name ?? ''),
                ];
            }
        }

        return [
            'company_id' => 0,
            'customer_id' => 0,
            'language_id' => $languageId,
            'not_customer' => false,
            'needs_selection' => true,
            'companies' => array_values($companiesMap),
            'customers' => $candidates->map(static function (Customer $c) {
                return [
                    'customer_id' => (int) $c->customer_id,
                    'company_id' => (int) $c->company_id,
                    'name' => $c->name,
                    'business_name' => $c->business_name,
                ];
            })->values()->all(),
        ];
    }

    /**
     * @return array{
     *     company_id: int,
     *     customer_id: int,
     *     language_id: int,
     *     not_customer: bool,
     *     needs_selection: bool,
     *     companies: null,
     *     customers: null
     * }
     */
    private function resolvedSuccess(int $companyId, int $customerId, int $languageId): array
    {
        return [
            'company_id' => $companyId,
            'customer_id' => $customerId,
            'language_id' => $languageId,
            'not_customer' => false,
            'needs_selection' => false,
            'companies' => null,
            'customers' => null,
        ];
    }

    /**
     * @return array{
     *     company_id: int,
     *     customer_id: int,
     *     language_id: int,
     *     not_customer: bool,
     *     needs_selection: bool,
     *     companies: null,
     *     customers: null
     * }
     */
    private function resolvedNotCustomer(int $languageId): array
    {
        return [
            'company_id' => 0,
            'customer_id' => 0,
            'language_id' => $languageId,
            'not_customer' => true,
            'needs_selection' => false,
            'companies' => null,
            'customers' => null,
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

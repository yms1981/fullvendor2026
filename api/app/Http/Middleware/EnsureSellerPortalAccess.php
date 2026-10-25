<?php

namespace App\Http\Middleware;

use App\Enums\CompanyUserTypes;
use App\Models\User;
use App\Models\Vendor;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contexto del portal vendedor: valida token Sanctum + fila sellers activa + rol seller en company_user.
 * Resuelve seller_id/company_id desde cabeceras (sesión MVC), no desde el cuerpo del POST.
 */
class EnsureSellerPortalAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($user === null) {
            return response()->json(['message' => 'Unauthenticated.', 'error' => true], 401);
        }

        $sellerId = (int) ($request->header('X-Seller-Portal-Seller-Id')
            ?? $request->query('seller_portal_seller_id', 0));
        $companyId = (int) ($request->header('X-Seller-Portal-Company-Id')
            ?? $request->query('seller_portal_company_id', 0));

        $vendor = $this->resolveVendor($user, $sellerId, $companyId);
        if ($vendor === null) {
            return response()->json(['message' => 'forbidden', 'error' => true], 403);
        }

        if (! $this->userHasSellerRoleForCompany((int) $user->id, (int) $vendor->company_id)) {
            return response()->json(['message' => 'forbidden', 'error' => true], 403);
        }

        $request->attributes->set('seller_portal_vendor', $vendor);
        $request->attributes->set('seller_portal_seller_id', (int) $vendor->id);
        $request->attributes->set('seller_portal_company_id', (int) $vendor->company_id);

        return $next($request);
    }

    private function resolveVendor(User $user, int $requestedSellerId, int $requestedCompanyId): ?Vendor
    {
        $query = Vendor::query()
            ->where('user_id', (int) $user->id)
            ->where('status', 1);

        if ($requestedSellerId > 0) {
            $query->where('id', $requestedSellerId);
        }
        if ($requestedCompanyId > 0) {
            $query->where('company_id', $requestedCompanyId);
        }

        $rows = $query->with(['company' => static function ($q) {
            $q->select('company_id', 'name', 'status', 'language_id', 'default_term_id', 'default_customer_group_id', 'default_seller_id', 'country_id', 'country', 'sell_with_inventory', 'show_inventory');
        }])->get()->filter(static function (Vendor $v) {
            return $v->company !== null && (int) $v->company->status === 1;
        })->values();

        if ($rows->isEmpty()) {
            return null;
        }

        if ($rows->count() === 1) {
            return $rows->first();
        }

        if ($requestedSellerId > 0) {
            return $rows->first(static fn (Vendor $v) => (int) $v->id === $requestedSellerId);
        }

        return null;
    }

    private function userHasSellerRoleForCompany(int $userId, int $companyId): bool
    {
        if (! Schema::hasTable('company_user')) {
            return false;
        }

        return DB::table('company_user')
            ->where('user_id', $userId)
            ->where('company_id', $companyId)
            ->where('type', CompanyUserTypes::Seller->value)
            ->exists();
    }
}

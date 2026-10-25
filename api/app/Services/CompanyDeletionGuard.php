<?php

namespace App\Services;

use App\Enums\CompanyUserTypes;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Customer;
use App\Models\Vendor;
use App\Models\WarehouseUser;
use Illuminate\Support\Facades\DB;

/**
 * Reglas para permitir eliminar una compañía: sin pedidos ni clientes ni almacén;
 * solo vínculos manager + vendedor por defecto correctamente alineados.
 */
final class CompanyDeletionGuard
{
    /**
     * Clave en cmp-admin-company del JSON de idioma, o null si puede eliminarse.
     */
    public static function blockReasonTranslationKey(Company $company): ?string
    {
        $companyId = (int) $company->company_id;

        if (DB::table('orders')->where('company_id', $companyId)->exists()) {
            return 'company_delete_blocked_has_orders';
        }

        if (Customer::query()->where('company_id', $companyId)->exists()) {
            return 'company_delete_blocked_has_customers';
        }

        if (WarehouseUser::query()->where('company_id', $companyId)->exists()) {
            return 'company_delete_blocked_has_warehouse';
        }

        $pivots = CompanyUser::query()
            ->where('company_id', $companyId)
            ->get();

        $allowed = ['manager', 'seller'];
        foreach ($pivots as $row) {
            $typeNorm = strtolower((string) $row->type);
            if (! in_array($typeNorm, $allowed, true)) {
                if ($typeNorm === strtolower(CompanyUserTypes::Customer->value)) {
                    return 'company_delete_blocked_has_customers';
                }
                if ($typeNorm === strtolower(CompanyUserTypes::Warehouse->value)) {
                    return 'company_delete_blocked_has_warehouse';
                }

                return 'company_delete_blocked_other_user_role';
            }
        }

        $managers = $pivots->filter(fn ($r) => strtolower((string) $r->type) === 'manager');
        $sellers = $pivots->filter(fn ($r) => strtolower((string) $r->type) === 'seller');

        if ($managers->count() !== 1 || $sellers->count() !== 1 || $pivots->count() !== 2) {
            return 'company_delete_blocked_exactly_one_manager_one_seller';
        }

        $defaultSellerId = $company->default_seller_id;
        if (! $defaultSellerId) {
            return 'company_delete_blocked_default_seller_requirement';
        }

        $sellerPivot = $sellers->first();
        if ((int) ($sellerPivot->user_id ?? 0) < 1) {
            return 'company_delete_blocked_default_seller_requirement';
        }

        $vendor = Vendor::query()
            ->where('company_id', $companyId)
            ->whereKey((int) $defaultSellerId)
            ->first();

        if (! $vendor || (int) ($vendor->user_id ?? 0) < 1) {
            return 'company_delete_blocked_default_seller_requirement';
        }

        if ((int) $vendor->user_id !== (int) $sellerPivot->user_id) {
            return 'company_delete_blocked_default_seller_requirement';
        }

        return null;
    }
}

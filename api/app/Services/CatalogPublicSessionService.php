<?php

namespace App\Services;

use App\Models\Catalog;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\FormatNumeric;
use App\Models\StatusCart;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use stdClass;

final class CatalogPublicSessionService
{
    public function isExpired(stdClass $cart): bool
    {
        if (empty($cart->expired_date)) {
            return false;
        }

        try {
            return Carbon::now()->gt(Carbon::parse($cart->expired_date));
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function buildSessionPayload(stdClass $cart, bool $expired): array
    {
        $companyId = (int) ($cart->company_id ?? 0);
        $customerId = (int) ($cart->customer_id ?? 0);
        $catalogId = (int) ($cart->catalog_id ?? 0);
        $userId = (int) ($cart->user_id ?? 0);

        $company = $companyId > 0 ? Company::query()->find($companyId) : null;
        $customer = $customerId > 0
            ? Customer::query()->with('customer_group')->find($customerId)
            : null;

        $catalog = ($catalogId > 0 && $companyId > 0)
            ? Catalog::query()
                ->where('catalog_id', $catalogId)
                ->where('company_id', $companyId)
                ->first()
            : null;

        $studioDefinition = (new CatalogStudioDefinitionService())
            ->parseAttach($catalog ? (string) ($catalog->attach ?? '') : '');
        $logoUuid = $catalog ? trim((string) ($catalog->logo ?? '')) : '';
        $logoUrl = '';
        if ($logoUuid !== '') {
            $logoUrl = rtrim((string) config('fullvendor.web_url', config('app.url', 'http://localhost')), '/')
                . '/api/v2/images/'
                . rawurlencode($logoUuid);
        }

        $sellerPayload = null;
        if ($userId > 0 && $companyId > 0) {
            $vendor = Vendor::query()
                ->where('user_id', $userId)
                ->where('company_id', $companyId)
                ->with('user')
                ->first();
            if ($vendor?->user) {
                $sellerPayload = [
                    'user_id' => $userId,
                    'first_name' => (string) ($vendor->user->first_name ?? ''),
                    'last_name' => (string) ($vendor->user->last_name ?? ''),
                ];
            }
        }

        $customerGroup = $customer?->customer_group;
        if ($customer && $customerGroup === null && $company?->default_customer_group_id) {
            $customerGroup = CustomerGroup::query()->find((int) $company->default_customer_group_id);
        }

        $formatNumeric = null;
        if ($company !== null) {
            $formatNumeric = $company->format_id
                ? FormatNumeric::query()->find((int) $company->format_id)
                : FormatNumeric::query()->where('default', 1)->first();
        }

        $statusCartRow = null;
        $statusCartCod = $cart->status_cart ?? null;
        if ($statusCartCod !== null && $statusCartCod !== '') {
            $statusCartRow = StatusCart::query()->where('cod', (string) $statusCartCod)->first();
        }

        $nosubmit = (int) ($statusCartRow->nosubmit_order ?? 0) === 1;

        return [
            'valid' => ! $expired,
            'reason' => $expired ? 'expired' : null,
            'cart' => $this->cartPayload($cart),
            'company' => $company ? $this->companyPayload($company) : null,
            'catalog' => $catalog ? [
                'catalog_id' => (int) $catalog->catalog_id,
                'catalog_name' => (string) ($catalog->catalog_name ?? ''),
                'default_catalog' => (int) ($catalog->default_catalog ?? 0),
                'cabeza_pagina' => (string) ($catalog->cabeza_pagina ?? ''),
                'logo' => $logoUuid,
                'logo_url' => $logoUrl,
                'studio' => $studioDefinition,
            ] : null,
            'seller' => $sellerPayload,
            'customer' => $customer ? [
                'customer_id' => (int) $customer->customer_id,
                'business_name' => (string) ($customer->business_name ?? ''),
                'name' => (string) ($customer->name ?? ''),
                'discount' => (float) ($customer->discount ?? 0),
                'group_name' => (string) ($customerGroup->name ?? ''),
                'percentage_on_price' => (string) ($customerGroup->percentage_on_price ?? ''),
                'percent_price_amount' => (float) ($customerGroup->percent_price_amount ?? 0),
            ] : null,
            'format_numeric' => $formatNumeric ? [
                'digits' => (int) ($formatNumeric->digits ?? 2),
                'radixPoint' => (string) ($formatNumeric->radixPoint ?? '.'),
                'groupSeparator' => (string) ($formatNumeric->groupSeparator ?? ','),
            ] : null,
            'status_cart' => $statusCartRow ? [
                'cod' => $statusCartRow->cod,
                'nosubmit_order' => (int) ($statusCartRow->nosubmit_order ?? 0),
                'multiple_cart' => (int) ($statusCartRow->multiple_cart ?? 0),
            ] : null,
            'flags' => [
                'expired' => $expired,
                'can_submit_order' => ! $expired && ! $nosubmit,
            ],
        ];
    }

    public function touchSession(stdClass $cart): void
    {
        $cartId = (int) ($cart->cart_id ?? 0);
        $companyId = (int) ($cart->company_id ?? 0);
        if ($cartId < 1 || $companyId < 1) {
            return;
        }

        $updates = ['view' => 1];
        $statusCart = $cart->status_cart ?? null;
        if ($statusCart === null || (string) $statusCart === '1') {
            $updates['status_cart'] = 2;
        }

        DB::table('catalog_cart')
            ->where('cart_id', $cartId)
            ->where('company_id', $companyId)
            ->update($updates);
    }

    /**
     * @param array<string, mixed> $patch
     */
    public function patchSession(stdClass $cart, array $patch): void
    {
        $cartId = (int) ($cart->cart_id ?? 0);
        if ($cartId < 1) {
            return;
        }

        if (! empty($patch['expire'])) {
            DB::table('catalog_cart')
                ->where('cart_id', $cartId)
                ->update([
                    'expired_date' => Carbon::now()->subDay()->format('Y-m-d H:i:s'),
                ]);

            return;
        }

        $allowed = [];
        if (array_key_exists('view', $patch)) {
            $allowed['view'] = (int) $patch['view'];
        }
        if (array_key_exists('status_cart', $patch)) {
            $allowed['status_cart'] = (int) $patch['status_cart'];
        }

        if ($allowed !== []) {
            DB::table('catalog_cart')->where('cart_id', $cartId)->update($allowed);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function companyPayload(Company $company): array
    {
        return [
            'company_id' => (int) $company->company_id,
            'language_id' => (int) ($company->language_id ?? 0),
            'sell_with_inventory' => (int) ($company->sell_with_inventory ?? 0),
            'email_client_on_salesman_order' => (int) ($company->email_client_on_salesman_order ?? 0),
            'email_on_salesman_order' => (int) ($company->email_on_salesman_order ?? 0),
            'format_id' => (int) ($company->format_id ?? 0),
            'name' => (string) ($company->name ?? ''),
            'email' => (string) ($company->email ?? ''),
            'phone_number' => (string) ($company->phone_number ?? ''),
            'image' => $company->image,
            'currency' => (string) ($company->currency ?? ''),
            'show_inventory' => (int) ($company->show_inventory ?? 0),
            'show_semaforo_stock' => (int) ($company->show_semaforo_stock ?? 0),
            'show_plus_taxes_label' => (int) ($company->show_plus_taxes_label ?? 0),
            'sem_red_hasta' => $company->sem_red_hasta,
            'sem_amarillo_desde' => $company->sem_amarillo_desde,
            'sem_amarillo_hasta' => $company->sem_amarillo_hasta,
            'sem_verde_desde' => $company->sem_verde_desde,
            'sem_verde_hasta' => $company->sem_verde_hasta,
            'sem_red' => $company->sem_red,
            'sem_amarillo' => $company->sem_amarillo,
            'sem_verde' => $company->sem_verde,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function cartPayload(stdClass $cart): array
    {
        return [
            'cart_id' => (int) ($cart->cart_id ?? 0),
            'token_kor' => (string) ($cart->token_kor ?? ''),
            'company_id' => (int) ($cart->company_id ?? 0),
            'customer_id' => (int) ($cart->customer_id ?? 0),
            'catalog_id' => (int) ($cart->catalog_id ?? 0),
            'user_id' => (int) ($cart->user_id ?? 0),
            'type_cart' => (string) ($cart->type_cart ?? ''),
            'status_cart' => $cart->status_cart,
            'order_sent' => (int) ($cart->order_sent ?? 0),
            'expired_date' => $cart->expired_date ?? null,
            'view' => (int) ($cart->view ?? 0),
            'disable_total' => (int) ($cart->disable_total ?? 0),
            'order_id' => isset($cart->order_id) ? (int) $cart->order_id : null,
            'login_seller' => isset($cart->login_seller) ? (int) $cart->login_seller : 0,
            'show_logofullvendor' => isset($cart->show_logofullvendor) ? (int) $cart->show_logofullvendor : 0,
            'return_admin' => $cart->return_admin ?? null,
        ];
    }
}

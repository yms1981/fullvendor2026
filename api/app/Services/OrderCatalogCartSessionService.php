<?php

namespace App\Services;

use App\Models\Catalog;
use App\Models\Company;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Crea filas en catalog_cart para el flujo MVC "Agregar nueva orden → Desde catálogo".
 *
 * Usa el mismo cifrado legacy que Commonmodel::generar_token (AES + base64) para que
 * catalogo/producto?token=… siga resolviendo el carrito.
 */
final class OrderCatalogCartSessionService
{
    /**
     * Devuelve token_kor de un carrito to_customer vigente o crea uno nuevo.
     *
     * @return array{token_kor: string, catalog_id: int, customer_id: int}
     */
    public function ensure(int $companyId, int $customerId, ?int $sellerUserId = null): array
    {
        if (! Schema::hasTable('catalog_cart')) {
            throw new RuntimeException('catalog_cart_table_missing');
        }

        $now = Carbon::now()->format('Y-m-d H:i:s');
        $query = DB::table('catalog_cart')
            ->where('company_id', $companyId)
            ->where('customer_id', $customerId)
            ->where('type_cart', 'to_customer');

        if (Schema::hasColumn('catalog_cart', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
        if (Schema::hasColumn('catalog_cart', 'expired_date')) {
            $query->where('expired_date', '>', $now);
        }

        $existing = $query->orderByDesc('cart_id')->first();
        if ($existing !== null && ! empty($existing->token_kor)) {
            return [
                'token_kor'   => (string) $existing->token_kor,
                'catalog_id'  => (int) $existing->catalog_id,
                'customer_id' => (int) $existing->customer_id,
            ];
        }

        return $this->create($companyId, $customerId, $sellerUserId);
    }

    public function create(int $companyId, int $customerId, ?int $sellerUserId = null): array
    {
        if (! Schema::hasTable('catalog_cart')) {
            throw new RuntimeException('catalog_cart_table_missing');
        }

        $company = Company::findOrFail($companyId);
        $customer = Customer::where('customer_id', $customerId)
            ->where('company_id', $companyId)
            ->firstOrFail();

        if ($sellerUserId === null || $sellerUserId <= 0) {
            $sellerUserId = $this->resolveSellerUserId($customer);
        }

        $catalog = Catalog::where('company_id', $companyId)
            ->where('default_catalog', 1)
            ->first();

        if ($catalog === null) {
            $catalog = Catalog::where('company_id', $companyId)
                ->orderBy('catalog_id')
                ->first();
        }

        if ($catalog === null) {
            throw new RuntimeException('default_catalog_missing');
        }

        $expirationDays = (int) ($company->catalogsend_time ?? 0);
        if ($expirationDays < 1) {
            $expirationDays = 15;
        }

        $tokenKor = app(LegacyCatalogCartTokenService::class)->generateUniqueEncrypted();
        $now = Carbon::now();

        DB::table('catalog_cart')->insert([
            'customer_id'  => $customer->customer_id,
            'created'      => $now->format('Y-m-d H:i:s'),
            'user_id'      => $sellerUserId,
            'company_id'   => $companyId,
            'catalog_id'   => (int) $catalog->catalog_id,
            'expired_date' => $now->copy()->addDays($expirationDays)->format('Y-m-d H:i:s'),
            'token_kor'    => $tokenKor,
            'order_sent'   => 1,
            'status_cart'  => 1,
            'type_cart'    => 'to_customer',
            'cart_number'  => $this->generateCartNumber(),
        ]);

        return [
            'token_kor'   => $tokenKor,
            'catalog_id'  => (int) $catalog->catalog_id,
            'customer_id' => (int) $customer->customer_id,
        ];
    }

    private function resolveSellerUserId(Customer $customer): ?int
    {
        $seller = $customer->sellers()->first();
        if ($seller === null) {
            $seller = $customer->company?->defaultSeller;
        }

        $userId = $seller?->user_id;

        return ($userId !== null && (int) $userId > 0) ? (int) $userId : null;
    }

    private function generateCartNumber(int $length = 6): string
    {
        $characters = '123456789';
        $randomString = '';
        $max = strlen($characters) - 1;
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $max)];
        }

        return $randomString;
    }
}

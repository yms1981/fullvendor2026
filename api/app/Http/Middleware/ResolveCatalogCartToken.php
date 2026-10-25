<?php

namespace App\Http\Middleware;

use App\Actions\SendCatalogService;
use App\Models\CatalogRequest;
use App\Models\Customer;
use App\Models\Vendor;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resuelve catalog_cart por token_kor (query ?token= o header X-Catalog-Token).
 * Usado por el catálogo público MVC sin Bearer.
 */
final class ResolveCatalogCartToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = trim((string) ($request->query('token') ?? $request->header('X-Catalog-Token', '')));
        if ($token !== '' && str_contains($token, ' ')) {
            $token = str_replace(' ', '+', $token);
        }
        if ($token === '') {
            return response()->json([
                'valid' => false,
                'reason' => 'missing_token',
                'message' => 'Token de carrito requerido.',
            ], 422);
        }

        if (! Schema::hasTable('catalog_cart')) {
            return response()->json([
                'valid' => false,
                'reason' => 'catalog_cart_table_missing',
                'message' => 'Catálogo no disponible.',
            ], 503);
        }

        $cart = DB::table('catalog_cart')
            ->whereNull('deleted_at')
            ->where('token_kor', $token)
            ->first();

        if ($cart === null) {
            $cart = $this->recoverCartFromInvitationToken($token);
        }

        if ($cart === null) {
            return response()->json([
                'valid' => false,
                'reason' => 'not_found',
                'message' => 'Carrito no encontrado.',
            ], 404);
        }

        $request->attributes->set('catalog_cart_token', $token);
        $request->attributes->set('catalog_cart_row', $cart);

        return $next($request);
    }

    private function recoverCartFromInvitationToken(string $token): ?object
    {
        $catalogRequest = CatalogRequest::query()->where('token', $token)->first();
        if ($catalogRequest === null) {
            return null;
        }

        $customer = Customer::query()
            ->with(['company.defaultSeller', 'sellers', 'sellerAssignCatalog'])
            ->find((int) $catalogRequest->customer_id);
        if ($customer === null || $customer->company === null) {
            return null;
        }

        $seller = $customer->sellerAssignCatalog
            ?? $customer->sellers->first()
            ?? $customer->company->defaultSeller;

        $expirationDate = '';
        $catalogText = '';
        try {
            $invitation = json_decode(Crypt::decryptString($token), true, 512, JSON_THROW_ON_ERROR);
            $expirationDate = (string) ($invitation['expirationDate'] ?? '');
            $catalogText = (string) ($invitation['catalog_text'] ?? '');
        } catch (\Throwable) {
            // Si no se puede descifrar, igual intentamos crear el carrito con fecha por defecto.
        }

        if ($expirationDate === '') {
            $days = (int) ($customer->company->catalogsend_time ?: 15);
            $expirationDate = now()->addDays(max(1, $days))->toIso8601String();
        }

        app(SendCatalogService::class)->ensureCatalogCartRow(
            $customer->company,
            $customer,
            (int) $catalogRequest->catalog_id,
            $token,
            $expirationDate,
            $catalogText,
            $seller instanceof Vendor ? $seller : null
        );

        return DB::table('catalog_cart')
            ->whereNull('deleted_at')
            ->where('token_kor', $token)
            ->first();
    }
}

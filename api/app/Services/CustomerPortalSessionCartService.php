<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Token corto de sesión de carrito del portal cliente (tabla legacy session_cart).
 *
 * Réplica {@see \Customer_portal::getLastCartSession} / saveCartSession del MVC.
 * Si la tabla no existe, devuelve un token efímero (solo sesión MVC).
 */
final class CustomerPortalSessionCartService
{
    /**
     * @return array{session_token: string, persisted: bool}
     */
    public function ensure(int $companyId, int $customerId): array
    {
        if ($companyId < 1 || $customerId < 1) {
            return [
                'session_token' => $this->generateToken(),
                'persisted' => false,
            ];
        }

        if (! Schema::hasTable('session_cart')) {
            return [
                'session_token' => $this->generateToken(),
                'persisted' => false,
            ];
        }

        $existing = DB::table('session_cart')
            ->where('company_id', $companyId)
            ->where('customer_id', $customerId)
            ->orderByDesc('id')
            ->first();

        if ($existing !== null
            && empty($existing->is_closed)
            && ! empty($existing->session_token)) {
            return [
                'session_token' => (string) $existing->session_token,
                'persisted' => true,
            ];
        }

        $token = $this->generateToken();
        $payload = [
            'session_token' => $token,
            'company_id' => $companyId,
            'customer_id' => $customerId,
        ];
        if (Schema::hasColumn('session_cart', 'is_closed')) {
            $payload['is_closed'] = 0;
        }

        DB::table('session_cart')->insert($payload);

        return [
            'session_token' => $token,
            'persisted' => true,
        ];
    }

    public function catalogCartEnabled(): bool
    {
        return Schema::hasTable('catalog_cart');
    }

    private function generateToken(): string
    {
        return substr(bin2hex(random_bytes(4)), 0, 6);
    }
}

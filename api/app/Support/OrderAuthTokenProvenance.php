<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Extrae del request autenticado la procedencia para orders.token_create / user_id_token / rol_token.
 */
final class OrderAuthTokenProvenance
{
    /**
     * @return array{token_create: ?string, user_id_token: ?int, rol_token: ?string}
     */
    public static function fromRequest(?Request $request = null, ?int $companyId = null): array
    {
        $request ??= request();
        /** @var User|null $user */
        $user = $request->user();

        $tokenCreate = null;
        $bearer = $request->bearerToken();

        if ($user !== null && method_exists($user, 'currentAccessToken')) {
            $access = $user->currentAccessToken();
            if ($access !== null && isset($access->id)) {
                // No guardamos el Bearer en claro: id Sanctum + hash corto.
                $tokenCreate = 'sanctum:'.$access->id;
                if (is_string($bearer) && $bearer !== '') {
                    $tokenCreate .= ':'.substr(hash('sha256', $bearer), 0, 16);
                }
            }
        }

        if ($tokenCreate === null && is_string($bearer) && $bearer !== '') {
            $tokenCreate = 'bearer:'.hash('sha256', $bearer);
        }

        $userId = $user !== null ? (int) $user->id : null;
        if ($userId !== null && $userId <= 0) {
            $userId = null;
        }

        $rol = null;
        if ($user !== null && $companyId !== null && $companyId > 0) {
            $rol = self::resolveRole($user, $companyId);
        } elseif ($user !== null) {
            $roles = $user->roles()->pluck('type')->unique()->filter()->values()->all();
            $rol = self::pickPrimaryRole($roles);
        }

        return [
            'token_create' => $tokenCreate,
            'user_id_token' => $userId,
            'rol_token' => $rol,
        ];
    }

    private static function resolveRole(User $user, int $companyId): ?string
    {
        $roles = $user->rolesByCompany($companyId);
        if (! is_array($roles) || $roles === []) {
            return null;
        }

        return self::pickPrimaryRole($roles);
    }

    /**
     * @param  list<string>  $roles
     */
    private static function pickPrimaryRole(array $roles): ?string
    {
        $normalized = [];
        foreach ($roles as $r) {
            $r = strtolower(trim((string) $r));
            if ($r !== '') {
                $normalized[] = $r;
            }
        }
        if ($normalized === []) {
            return null;
        }

        $priority = ['warehouse', 'seller', 'manager', 'admin', 'customer'];
        foreach ($priority as $want) {
            if (in_array($want, $normalized, true)) {
                return $want;
            }
        }

        return $normalized[0];
    }
}

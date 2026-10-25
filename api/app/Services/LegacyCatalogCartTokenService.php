<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Generación de token_kor legacy (AES + base64), compatible con Commonmodel::generar_token del MVC.
 */
final class LegacyCatalogCartTokenService
{
    /**
     * @return array{password_generado: string, password_encrypt: string}
     */
    public function generateTokenPair(int $length = 10, int $maxAttempts = 50): array
    {
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $plain = $this->generatePassword($length);
            $encrypted = $this->legacyEncrypt($plain);
            if (! $this->tokenExists($encrypted)) {
                return [
                    'password_generado' => $plain,
                    'password_encrypt' => $encrypted,
                ];
            }
        }

        throw new RuntimeException('catalog_token_generation_failed');
    }

    public function generateUniqueEncrypted(int $length = 10, int $maxAttempts = 50): string
    {
        return $this->generateTokenPair($length, $maxAttempts)['password_encrypt'];
    }

    public function legacyEncrypt(string $plain): string
    {
        $key = hash('sha256', 'tu_clave_secreta');
        $iv = substr(hash('sha256', 'salt_secreto'), 0, 16);
        $encrypted = openssl_encrypt($plain, 'AES-256-CBC', $key, 0, $iv);

        return base64_encode($encrypted !== false ? $encrypted : '');
    }

    public function tokenExists(string $encryptedToken): bool
    {
        if (! Schema::hasTable('catalog_cart')) {
            return false;
        }

        return DB::table('catalog_cart')->where('token_kor', $encryptedToken)->exists();
    }

    private function generatePassword(int $length): string
    {
        $pattern = '1234567890abcdefghijklmnopqrstuvwxyz';
        $max = strlen($pattern) - 1;
        $key = '';
        for ($i = 0; $i < $length; $i++) {
            $key .= $pattern[random_int(0, $max)];
        }

        return $key;
    }
}

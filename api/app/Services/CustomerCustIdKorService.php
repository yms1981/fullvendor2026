<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\Schema;

/**
 * Genera y normaliza customers.cust_id_kor.
 * Alta nueva: C + 7 dígitos aleatorios únicos en la tabla.
 * Vacío en cliente existente: usa customer_id.
 */
class CustomerCustIdKorService
{
    private const PREFIX = 'C';
    private const RANDOM_DIGITS = 7;
    private const MAX_ATTEMPTS = 40;

    public function isBlank(?string $value): bool
    {
        $v = trim((string) $value);

        return $v === '' || $v === '0';
    }

    /**
     * Código único tipo C1234567.
     */
    public function generateUnique(): string
    {
        if (! Schema::hasColumn('customers', 'cust_id_kor')) {
            return self::PREFIX.random_int(10 ** (self::RANDOM_DIGITS - 1), (10 ** self::RANDOM_DIGITS) - 1);
        }

        for ($i = 0; $i < self::MAX_ATTEMPTS; $i++) {
            $code = self::PREFIX.$this->randomDigits(self::RANDOM_DIGITS);
            $exists = Customer::query()
                ->whereRaw('UPPER(TRIM(cust_id_kor)) = ?', [mb_strtoupper($code, 'UTF-8')])
                ->exists();
            if (! $exists) {
                return $code;
            }
        }

        // Fallback casi único: C + time + random
        return self::PREFIX.substr((string) time(), -4).$this->randomDigits(3);
    }

    /**
     * Tras crear: si está vacío → CXXXXXXX único.
     */
    public function fillAfterCreate(Customer $customer): void
    {
        if (! Schema::hasColumn('customers', 'cust_id_kor')) {
            return;
        }
        if (! $this->isBlank($customer->cust_id_kor ?? null)) {
            return;
        }

        $customer->cust_id_kor = $this->generateUnique();
        $customer->saveQuietly();
    }

    /**
     * En edición: si está vacío → customer_id.
     */
    public function fillIfBlankWithCustomerId(Customer $customer): void
    {
        if (! Schema::hasColumn('customers', 'cust_id_kor')) {
            return;
        }
        if (! $customer->exists || (int) ($customer->customer_id ?? 0) <= 0) {
            return;
        }
        if (! $this->isBlank($customer->cust_id_kor ?? null)) {
            return;
        }

        $customer->cust_id_kor = (string) $customer->customer_id;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function applyToPayload(array $data, ?int $customerId = null, bool $isCreate = false): array
    {
        if (! Schema::hasColumn('customers', 'cust_id_kor')) {
            return $data;
        }

        $hasKey = array_key_exists('cust_id_kor', $data);
        $blank = ! $hasKey || $this->isBlank(isset($data['cust_id_kor']) ? (string) $data['cust_id_kor'] : null);

        if (! $blank) {
            return $data;
        }

        if ($isCreate) {
            $data['cust_id_kor'] = $this->generateUnique();
        } elseif ($customerId !== null && $customerId > 0) {
            $data['cust_id_kor'] = (string) $customerId;
        }

        return $data;
    }

    private function randomDigits(int $length): string
    {
        $max = (10 ** $length) - 1;
        $n = random_int(0, $max);

        return str_pad((string) $n, $length, '0', STR_PAD_LEFT);
    }
}

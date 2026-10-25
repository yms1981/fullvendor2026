<?php

namespace App\Support;

use App\Support\SchemaMigration\LiveSchemaReader;
use Illuminate\Support\Facades\Schema;

/**
 * Esquema legacy vs nuevo de la tabla `currency` (PK `currency_id` o `id`).
 */
final class CurrencySchemaHelper
{
    private static ?string $primaryKeyColumn = null;

    private static ?string $companiesCurrencyReferencedColumn = null;

    public static function primaryKeyColumn(): string
    {
        if (self::$primaryKeyColumn !== null) {
            return self::$primaryKeyColumn;
        }

        if (Schema::hasColumn('currency', 'id')) {
            self::$primaryKeyColumn = 'id';
        } elseif (Schema::hasColumn('currency', 'currency_id')) {
            self::$primaryKeyColumn = 'currency_id';
        } else {
            self::$primaryKeyColumn = 'id';
        }

        return self::$primaryKeyColumn;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function applyPrimaryKeyToPayload(array $payload, int $legacyId): array
    {
        unset($payload['id'], $payload['currency_id']);

        $pk = self::primaryKeyColumn();
        $payload[$pk] = $legacyId;

        if ($pk === 'id' && Schema::hasColumn('currency', 'currency_id')) {
            $payload['currency_id'] = $legacyId;
        } elseif ($pk === 'currency_id' && Schema::hasColumn('currency', 'id')) {
            $payload['id'] = $legacyId;
        }

        return $payload;
    }

    /**
     * Columna de `currency` referenciada por el FK `companies.currency` (detectada en BD).
     */
    public static function companiesCurrencyOwnerKey(): string
    {
        if (self::$companiesCurrencyReferencedColumn !== null) {
            return self::$companiesCurrencyReferencedColumn;
        }

        $detected = self::detectCompaniesCurrencyForeignKeyColumn();
        if ($detected !== null && $detected !== '') {
            self::$companiesCurrencyReferencedColumn = $detected;

            return $detected;
        }

        if (Schema::hasColumn('currency', 'currency_id')) {
            self::$companiesCurrencyReferencedColumn = 'currency_id';

            return 'currency_id';
        }

        self::$companiesCurrencyReferencedColumn = 'id';

        return 'id';
    }

    public static function resetCachedSchema(): void
    {
        self::$primaryKeyColumn = null;
        self::$companiesCurrencyReferencedColumn = null;
    }

    private static function detectCompaniesCurrencyForeignKeyColumn(): ?string
    {
        if (! Schema::hasTable('companies') || ! Schema::hasColumn('companies', 'currency')) {
            return null;
        }

        try {
            $ref = (new LiveSchemaReader())->getForeignKeyReference('companies', 'currency');
            if ($ref !== null && ($ref['referenced_table'] ?? '') === 'currency') {
                return (string) ($ref['referenced_column'] ?? '');
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    /**
     * Asegura que la fila tenga el valor en la columna usada por el FK de companies.
     *
     * @param  array<string, mixed>  $update
     * @return array<string, mixed>
     */
    public static function ensureCompaniesForeignKeyInUpdate(array $update, object $existing, int $legacyId): array
    {
        $fk = self::companiesCurrencyOwnerKey();
        if (! Schema::hasColumn('currency', $fk)) {
            return $update;
        }

        $current = (int) ($existing->{$fk} ?? 0);
        if ($current !== $legacyId) {
            $update[$fk] = $legacyId;
        }

        return $update;
    }

    public static function rowCompanyCurrencyId(object $row): int
    {
        $fk = self::companiesCurrencyOwnerKey();
        $fkVal = (int) ($row->{$fk} ?? 0);
        if ($fkVal > 0) {
            return $fkVal;
        }

        return (int) ($row->{self::primaryKeyColumn()} ?? 0);
    }
}

<?php

namespace App\Support\SchemaMigration;

/**
 * Registro de tablas conocidas con esquema legacy tras migraciones Laravel parciales.
 *
 * Escenarios cubiertos (audit 2026-06):
 * - currency: currency_id → id (drop/recreate parcial)
 * - admin: userId → id
 * - plan: subscription_id → plan_id
 * - subscriptions (tabla plan antigua): RENAME → plan + PK
 * - subscriptions (PK nueva): subscription_row_id → subscription_id
 */
class LegacyTableMigrationRegistry
{
    /**
     * @return list<array{
     *     id: string,
     *     table: string,
     *     description: string,
     *     detect: callable(array<string, array<string, mixed>>, list<string>): bool,
     *     apply: callable(LegacyTableMigrationService, string, array<string, array<string, mixed>>): bool
     * }>
     */
    public function migrations(): array
    {
        return [
            [
                'id' => 'subscriptions_table_is_old_plan',
                'table' => 'subscriptions',
                'description' => 'Tabla subscriptions contiene datos de plan (language_id/billing_period) y debe renombrarse a plan',
                'detect' => static function (array $liveColumns, array $liveTables): bool {
                    return isset($liveTables['subscriptions'])
                        && ! isset($liveTables['plan'])
                        && isset($liveColumns['language_id'])
                        && (isset($liveColumns['billing_period']) || isset($liveColumns['lifecycle_status']));
                },
                'apply' => static fn (LegacyTableMigrationService $service, string $table, array $liveColumns): bool => $service->renameSubscriptionsPlanTableToPlan($liveColumns),
            ],
            [
                'id' => 'currency_pk_currency_id',
                'table' => 'currency',
                'description' => 'PK legacy currency_id → id con columnas nuevas',
                'detect' => static function (array $liveColumns, array $liveTables): bool {
                    return isset($liveColumns['currency_id']) && ! isset($liveColumns['id']);
                },
                'apply' => static fn (LegacyTableMigrationService $service, string $table, array $liveColumns): bool => $service->migrateCurrencyTable(),
            ],
            [
                'id' => 'companies_currency_fk_to_id',
                'table' => 'companies',
                'description' => 'FK companies.currency: currency.currency_id → currency.id',
                'detect' => static function (array $liveColumns, array $liveTables): bool {
                    if (! isset($liveTables['currency']) || ! isset($liveColumns['currency'])) {
                        return false;
                    }

                    $currencyCols = (new LiveSchemaReader())->listColumns('currency');
                    if (! isset($currencyCols['id'])) {
                        return false;
                    }

                    $fk = (new LiveSchemaReader())->getForeignKeyReference('companies', 'currency');

                    return $fk !== null
                        && ($fk['referenced_table'] ?? '') === 'currency'
                        && ($fk['referenced_column'] ?? '') === 'currency_id';
                },
                'apply' => static fn (LegacyTableMigrationService $service, string $table, array $liveColumns): bool => $service->migrateCompaniesCurrencyForeignKey(),
            ],
            [
                'id' => 'admin_pk_userId',
                'table' => 'admin',
                'description' => 'PK legacy userId → id',
                'detect' => static function (array $liveColumns, array $liveTables): bool {
                    return isset($liveColumns['userId']) && ! isset($liveColumns['id']);
                },
                'apply' => static fn (LegacyTableMigrationService $service, string $table, array $liveColumns): bool => $service->renamePrimaryKeyColumn('admin', 'userId', 'id'),
            ],
            [
                'id' => 'plan_pk_subscription_id',
                'table' => 'plan',
                'description' => 'PK legacy subscription_id → plan_id en tabla plan',
                'detect' => static function (array $liveColumns, array $liveTables): bool {
                    return isset($liveTables['plan'])
                        && isset($liveColumns['subscription_id'])
                        && ! isset($liveColumns['plan_id']);
                },
                'apply' => static fn (LegacyTableMigrationService $service, string $table, array $liveColumns): bool => $service->renamePrimaryKeyColumn('plan', 'subscription_id', 'plan_id'),
            ],
            [
                'id' => 'subscriptions_pk_subscription_row_id',
                'table' => 'subscriptions',
                'description' => 'PK subscription_row_id → subscription_id (suscripciones operativas)',
                'detect' => static function (array $liveColumns, array $liveTables): bool {
                    return isset($liveColumns['subscription_row_id'])
                        && ! isset($liveColumns['subscription_id'])
                        && ! isset($liveColumns['language_id']);
                },
                'apply' => static fn (LegacyTableMigrationService $service, string $table, array $liveColumns): bool => $service->renamePrimaryKeyColumn('subscriptions', 'subscription_row_id', 'subscription_id'),
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public function coveredTables(): array
    {
        $tables = [];
        foreach ($this->migrations() as $migration) {
            $tables[] = $migration['table'];
        }

        return array_values(array_unique($tables));
    }
}

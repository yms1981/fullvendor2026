<?php

namespace App\Support\SchemaMigration;

use App\Support\CurrencySchemaHelper;
use Illuminate\Support\Facades\DB;

class LegacyTableMigrationService
{
    public function __construct(
        private readonly LegacyTableMigrationRegistry $registry = new LegacyTableMigrationRegistry(),
        private readonly LiveSchemaReader $reader = new LiveSchemaReader(),
    ) {
    }

    /**
     * @return list<array{id: string, table: string, description: string, status: string, message: string|null}>
     */
    public function audit(): array
    {
        $liveTables = $this->reader->listTables();
        $results = [];

        foreach ($this->registry->migrations() as $migration) {
            $table = $migration['table'];
            if (! isset($liveTables[$table])) {
                continue;
            }

            $liveColumns = $this->reader->listColumns($table);
            $detect = $migration['detect'];

            if ($detect($liveColumns, $liveTables)) {
                $results[] = [
                    'id' => $migration['id'],
                    'table' => $table,
                    'description' => $migration['description'],
                    'status' => 'pending',
                    'message' => null,
                ];
            }
        }

        return $results;
    }

    /**
     * @return list<array{id: string, table: string, description: string, status: string, message: string|null}>
     */
    public function applyPending(bool $dryRun = false): array
    {
        $pending = $this->audit();
        $results = [];

        foreach ($pending as $item) {
            $migration = $this->findMigration($item['id']);
            if ($migration === null) {
                continue;
            }

            if ($dryRun) {
                $results[] = array_merge($item, [
                    'status' => 'would_apply',
                    'message' => 'Se aplicaría en --force sin --dry-run',
                ]);
                continue;
            }

            try {
                $liveColumns = $this->reader->listColumns($migration['table']);
                $apply = $migration['apply'];
                $applied = $apply($this, $migration['table'], $liveColumns);

                $results[] = array_merge($item, [
                    'status' => $applied ? 'applied' : 'skipped',
                    'message' => $applied ? 'Migración legacy aplicada.' : 'Sin cambios.',
                ]);
            } catch (\Throwable $exception) {
                $results[] = array_merge($item, [
                    'status' => 'failed',
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return $results;
    }

    public function renamePrimaryKeyColumn(string $table, string $from, string $to): bool
    {
        $liveColumns = $this->reader->listColumns($table);
        if (! isset($liveColumns[$from]) || isset($liveColumns[$to])) {
            return false;
        }

        $type = $liveColumns[$from]['column_type'];
        $nullable = $liveColumns[$from]['is_nullable'] === 'YES' ? 'NULL' : 'NOT NULL';
        $extra = str_contains(strtolower((string) $liveColumns[$from]['extra']), 'auto_increment')
            ? ' AUTO_INCREMENT'
            : '';

        DB::statement(
            "ALTER TABLE `{$table}` CHANGE `{$from}` `{$to}` {$type} {$nullable}{$extra}"
        );

        return true;
    }

    /**
     * @param  array<string, array<string, mixed>>  $liveColumns
     */
    public function renameSubscriptionsPlanTableToPlan(array $liveColumns): bool
    {
        if (isset($this->reader->listTables()['plan'])) {
            return false;
        }

        DB::statement('RENAME TABLE `subscriptions` TO `plan`');

        if (isset($liveColumns['subscription_id']) && ! isset($liveColumns['plan_id'])) {
            $this->renamePrimaryKeyColumn('plan', 'subscription_id', 'plan_id');
        }

        if (isset($liveColumns['lifecycle_status']) && ! isset($liveColumns['status'])) {
            DB::statement(
                "ALTER TABLE `plan` CHANGE `lifecycle_status` `status` VARCHAR(20) NOT NULL DEFAULT 'active'"
            );
        }

        return true;
    }

    public function migrateCurrencyTable(): bool
    {
        $liveColumns = $this->reader->listColumns('currency');
        if (! isset($liveColumns['currency_id']) || isset($liveColumns['id'])) {
            return false;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            DB::statement('DROP TABLE IF EXISTS `currency_new`');
            DB::unprepared(<<<'SQL'
CREATE TABLE `currency_new` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL DEFAULT '',
  `code` varchar(10) NOT NULL DEFAULT '',
  `symbol` varchar(10) NOT NULL DEFAULT '',
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `company_id` int(11) NOT NULL DEFAULT 0,
  `status` int(11) NOT NULL DEFAULT 1,
  `country` varchar(100) DEFAULT NULL,
  `currency` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL);

            $createdAtExpr = isset($liveColumns['created']) ? '`created`' : 'NULL';

            DB::statement(<<<SQL
INSERT INTO `currency_new` (
  `id`, `name`, `code`, `symbol`, `company_id`, `status`, `country`, `currency`, `created_at`
)
SELECT
  `currency_id`,
  COALESCE(NULLIF(`currency`, ''), NULLIF(`code`, ''), 'Legacy'),
  COALESCE(NULLIF(`code`, ''), ''),
  COALESCE(NULLIF(`symbol`, ''), ''),
  COALESCE(`company_id`, 0),
  COALESCE(`status`, 1),
  `country`,
  `currency`,
  {$createdAtExpr}
FROM `currency`
SQL);

            DB::statement('DROP TABLE `currency`');
            DB::statement('RENAME TABLE `currency_new` TO `currency`');
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        return true;
    }

    /**
     * Alinea companies.currency → currency.id cuando el FK legacy apunta a currency_id.
     */
    public function migrateCompaniesCurrencyForeignKey(): bool
    {
        $currencyColumns = $this->reader->listColumns('currency');
        if (! isset($currencyColumns['id'])) {
            return false;
        }

        $fk = $this->reader->getForeignKeyReference('companies', 'currency');
        if ($fk === null || ($fk['referenced_table'] ?? '') !== 'currency') {
            return false;
        }

        $referenced = (string) ($fk['referenced_column'] ?? '');
        if ($referenced === 'id') {
            return false;
        }

        $constraint = str_replace('`', '``', (string) ($fk['constraint'] ?? 'companies_currency_foreign'));

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            if ($referenced === 'currency_id' && isset($currencyColumns['currency_id'])) {
                DB::statement(<<<'SQL'
UPDATE `companies` AS co
INNER JOIN `currency` AS cu ON cu.`currency_id` = co.`currency`
SET co.`currency` = cu.`id`
WHERE co.`currency` IS NOT NULL
SQL);
            }

            DB::statement("ALTER TABLE `companies` DROP FOREIGN KEY `{$constraint}`");
            DB::statement(
                'ALTER TABLE `companies` ADD CONSTRAINT `companies_currency_foreign` '
                . 'FOREIGN KEY (`currency`) REFERENCES `currency` (`id`) '
                . 'ON UPDATE RESTRICT ON DELETE RESTRICT'
            );
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        CurrencySchemaHelper::resetCachedSchema();

        return true;
    }

    /**
     * @return array{id: string, table: string, description: string, detect: callable, apply: callable}|null
     */
    private function findMigration(string $id): ?array
    {
        foreach ($this->registry->migrations() as $migration) {
            if ($migration['id'] === $id) {
                return $migration;
            }
        }

        return null;
    }
}

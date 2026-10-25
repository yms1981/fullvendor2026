<?php

namespace App\Console\Commands;

use App\Support\SchemaMigration\LegacyTableMigrationRegistry;
use App\Support\SchemaMigration\LegacyTableMigrationService;
use Illuminate\Console\Command;

class SchemaLegacyAudit extends Command
{
    protected $signature = 'schema:legacy-audit
                            {--apply : Aplicar migraciones legacy detectadas}';

    protected $description = 'Detecta tablas con PK/esquema legacy (currency, admin, plan, subscriptions) y opcionalmente las migra';

    public function handle(LegacyTableMigrationService $legacyMigrator, LegacyTableMigrationRegistry $registry): int
    {
        $this->info('Auditando tablas legacy conocidas...');
        $this->line('Tablas cubiertas: ' . implode(', ', $registry->coveredTables()));
        $this->newLine();

        $pending = $legacyMigrator->audit();

        if ($pending === []) {
            $this->info('No se detectaron tablas legacy pendientes de migración.');

            return self::SUCCESS;
        }

        $this->warn('Migraciones legacy pendientes (' . count($pending) . '):');
        foreach ($pending as $row) {
            $this->line("  ! [{$row['id']}] {$row['table']}: {$row['description']}");
        }

        if (! (bool) $this->option('apply')) {
            $this->newLine();
            $this->line('Ejecute con --apply para migrar, o use schema:sync --force (aplica legacy automáticamente).');

            return self::FAILURE;
        }

        if (! $this->confirm('¿Aplicar ' . count($pending) . ' migración(es) legacy?', false)) {
            return self::SUCCESS;
        }

        $results = $legacyMigrator->applyPending(false);
        $this->newLine();
        foreach ($results as $row) {
            $status = strtoupper($row['status']);
            $this->line("  {$status} [{$row['id']}] {$row['table']}");
            if (! empty($row['message'])) {
                $this->line('    ' . $row['message']);
            }
        }

        $failed = array_filter($results, static fn (array $row): bool => $row['status'] === 'failed');

        return $failed === [] ? self::SUCCESS : self::FAILURE;
    }
}

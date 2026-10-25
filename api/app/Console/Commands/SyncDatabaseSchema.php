<?php

namespace App\Console\Commands;

use App\Support\SchemaMigration\SchemaSyncPlan;
use App\Support\SchemaMigration\SchemaSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncDatabaseSchema extends Command
{
    protected $signature = 'schema:sync
                            {--file= : Ruta al dump SQL de referencia}
                            {--views-dir= : Directorio con DDL de vistas adicionales}
                            {--dry-run : Analizar y mostrar cambios sin aplicarlos}
                            {--force : Aplicar cambios sin confirmación}
                            {--modify-columns : Actualizar columnas existentes si difieren del esquema}
                            {--no-views : Omitir sincronización de vistas}
                            {--no-indexes : Omitir sincronización de índices}
                            {--no-seed-data : Omitir carga de datos de referencia}
                            {--force-seed-data : Forzar recarga de datos de referencia (INSERT IGNORE)}
                            {--no-admin : Omitir verificación/creación del admin de plataforma (/admin)}
                            {--seed-dir= : Directorio con manifest.json y SQL de datos fijos}
                            {--show-sql : Mostrar el SQL de cada acción}
                            {--show-comparison : Mostrar comparación campo a campo por tabla}
                            {--no-migrate-legacy : No aplicar migraciones automáticas de tablas legacy (PK renombradas)}';

    protected $description = 'Analiza el esquema de referencia y crea tablas/columnas/índices/datos fijos/vistas faltantes en la base de datos';

    public function handle(SchemaSyncService $syncService): int
    {
        $schemaFile = $this->option('file')
            ?: base_path('database/sql/appfv_full_schema.sql');

        $viewsDirectory = $this->option('views-dir')
            ?: SchemaSyncService::resolveViewsDirectory();

        $dryRun = (bool) $this->option('dry-run');
        $modifyColumns = (bool) $this->option('modify-columns');
        $syncViews = ! (bool) $this->option('no-views');
        $syncIndexes = ! (bool) $this->option('no-indexes');
        $syncSeedData = ! (bool) $this->option('no-seed-data');
        $forceSeedData = (bool) $this->option('force-seed-data');
        $ensurePlatformAdmin = ! (bool) $this->option('no-admin');
        $seedDataDirectory = $this->option('seed-dir') ?: null;
        $showSql = (bool) $this->option('show-sql');
        $showComparison = (bool) $this->option('show-comparison');
        $migrateLegacy = ! (bool) $this->option('no-migrate-legacy');

        $this->info('Analizando esquema de referencia...');
        $this->line("Archivo: {$schemaFile}");

        if ($syncViews && $viewsDirectory !== null) {
            $this->line("Vistas adicionales: {$viewsDirectory}");
        } elseif ($syncViews) {
            $this->warn('No se encontró directorio de vistas adicionales (database/sql/views).');
        }

        if ($syncSeedData) {
            $seedDir = $seedDataDirectory ?: \App\Support\SchemaMigration\ReferenceDataService::defaultSeedDirectory();
            $this->line("Datos de referencia: {$seedDir}");
        }

        $legacyResults = [];
        $legacyPending = $syncService->auditLegacyTables();
        if ($legacyPending !== []) {
            $this->newLine();
            $this->warn('Tablas legacy detectadas (' . count($legacyPending) . '):');
            foreach ($legacyPending as $row) {
                $this->line("  ! {$row['table']}: {$row['description']}");
            }
        }

        if ($migrateLegacy && $legacyPending !== [] && ! $dryRun) {
            $shouldApplyLegacy = (bool) $this->option('force')
                || $this->confirm('¿Aplicar migraciones legacy antes del sync?', true);

            if ($shouldApplyLegacy) {
                try {
                    $legacyResults = $syncService->applyLegacyTables(dryRun: false);
                } catch (Throwable $exception) {
                    $this->error('Error en migración legacy: ' . $exception->getMessage());

                    return self::FAILURE;
                }
                $this->newLine();
                $this->info('Migraciones legacy aplicadas:');
                foreach ($legacyResults as $row) {
                    $status = strtoupper($row['status']);
                    $this->line("  {$status} {$row['table']} ({$row['id']})");
                    if (! empty($row['message'])) {
                        $this->line('    ' . $row['message']);
                    }
                }
            } else {
                $this->warn('Migraciones legacy omitidas.');
            }
        } elseif ($dryRun && $migrateLegacy && $legacyPending !== []) {
            $this->line('  → Con --force se aplicarían automáticamente antes del sync.');
        }

        try {
            $plan = $syncService->buildPlan(
                schemaFile: $schemaFile,
                viewsDirectory: $viewsDirectory,
                syncViews: $syncViews,
                syncIndexes: $syncIndexes,
                syncSeedData: $syncSeedData,
                forceSeedData: $forceSeedData,
                ensurePlatformAdmin: $ensurePlatformAdmin,
                seedDataDirectory: $seedDataDirectory,
                includeModifyColumns: $modifyColumns,
            );
        } catch (Throwable $exception) {
            $this->error('Error al analizar el esquema: ' . $exception->getMessage());

            return self::FAILURE;
        }

        $this->renderSummary($plan, $showSql, $showComparison, $legacyResults);

        if (! $plan->hasChanges()) {
            if ($plan->hasBlockingIssues()) {
                $this->warn('Hay tablas/columnas legacy que requieren migración manual antes de poder sincronizar el esquema completo.');
            } elseif ($plan->hasColumnDrift()) {
                $this->warn('Hay columnas con definición distinta entre BD y script. Revise la comparación o use --modify-columns.');
            } else {
                $this->info('La base de datos ya está alineada con el esquema de referencia.');
            }

            return ($plan->hasBlockingIssues() || $plan->hasColumnDrift()) ? self::FAILURE : self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('Modo dry-run: no se aplicaron cambios.');

            return self::SUCCESS;
        }

        if (! $dryRun && ! $this->option('force') && ! $this->confirm('¿Aplicar los cambios detectados?', true)) {
            $this->warn('Operación cancelada.');

            return self::SUCCESS;
        }

        try {
            $failures = $syncService->apply($plan, dryRun: false);
        } catch (Throwable $exception) {
            $this->error('Error al aplicar cambios: ' . $exception->getMessage());

            return self::FAILURE;
        }

        $viewFailures = $failures['views'] ?? [];
        $seedFailures = $failures['seeds'] ?? [];
        $columnFailures = $failures['columns'] ?? [];
        $indexFailures = $failures['indexes'] ?? [];

        if ($columnFailures !== []) {
            $this->newLine();
            $this->warn('Algunas columnas no pudieron sincronizarse (' . count($columnFailures) . '):');
            foreach ($columnFailures as $failure) {
                $this->line("  ! {$failure['table']}.{$failure['column']}: {$failure['error']}");
            }
        }

        if ($indexFailures !== []) {
            $this->newLine();
            $this->warn('Algunos índices no pudieron sincronizarse (' . count($indexFailures) . '):');
            foreach ($indexFailures as $failure) {
                $this->line("  ! {$failure['table']}.{$failure['index']}: {$failure['error']}");
            }
        }

        if ($seedFailures !== []) {
            $this->newLine();
            $this->warn('Algunos datos de referencia no pudieron cargarse (' . count($seedFailures) . '):');
            foreach ($seedFailures as $failure) {
                $this->line("  ! {$failure['table']}: {$failure['error']}");
            }
        }

        if ($viewFailures !== []) {
            $this->newLine();
            $this->warn('Algunas vistas no pudieron sincronizarse (' . count($viewFailures) . '):');
            foreach ($viewFailures as $failure) {
                $this->line("  ! {$failure['view']}: {$failure['error']}");
            }
        }

        if ($seedFailures !== [] || $viewFailures !== [] || $columnFailures !== [] || $indexFailures !== [] || $plan->hasBlockingIssues()) {
            $this->newLine();
            if ($plan->hasBlockingIssues()) {
                $this->warn('Hay tablas/columnas legacy que requieren migración manual.');
            }
            $this->info('Sincronización completada con advertencias.');

            return self::SUCCESS;
        }

        $this->info('Sincronización completada correctamente.');

        return self::SUCCESS;
    }

    private function renderSummary(SchemaSyncPlan $plan, bool $showSql = false, bool $showComparison = false, array $legacyResults = []): void
    {
        $this->newLine();
        if ($legacyResults !== []) {
            $this->line('Migraciones legacy aplicadas en esta ejecución: ' . count($legacyResults));
        }
        if ($plan->pendingLegacyMigrations !== []) {
            $this->warn('Migraciones legacy pendientes: ' . count($plan->pendingLegacyMigrations));
            foreach ($plan->pendingLegacyMigrations as $row) {
                $this->line("  ! {$row['table']}: {$row['description']}");
            }
            $this->newLine();
        }

        $this->line("Tablas en referencia: {$plan->expectedTableCount}");
        $this->line("Tablas en base de datos: {$plan->existingTableCount}");
        $this->line('Tablas comparadas (BD vs script): ' . count($plan->tableComparisons));
        $this->line('Tablas con columnas alineadas: ' . $plan->alignedTableCount());
        $this->line("Vistas en referencia: {$plan->expectedViewCount}");
        $this->line("Vistas en base de datos: {$plan->existingViewCount}");
        $this->newLine();

        $this->renderTableComparisons($plan, $showComparison);

        if ($plan->createTables !== []) {
            $this->info('Tablas a crear (' . count($plan->createTables) . '):');
            foreach ($plan->createTables as $action) {
                $this->line("  + {$action['table']}");
                if ($showSql) {
                    $this->line('    ' . str_replace("\n", "\n    ", $action['sql']));
                }
            }
            $this->newLine();
        }

        if ($plan->addColumns !== []) {
            $this->info('Columnas a añadir (' . count($plan->addColumns) . '):');
            foreach ($plan->addColumns as $action) {
                $this->line("  + {$action['table']}.{$action['column']}");
                if ($showSql) {
                    $this->line("    {$action['sql']}");
                }
            }
            $this->newLine();
        }

        if ($plan->divergentColumns !== []) {
            $this->info('Columnas con definición distinta (' . count($plan->divergentColumns) . '):');
            foreach ($plan->divergentColumns as $row) {
                $expected = $this->formatColumnDef($row['expected']);
                $actual = $this->formatColumnDef($row['actual']);
                $this->line("  ~ {$row['table']}.{$row['column']}: BD={$actual} | script={$expected}");
            }
            $this->newLine();
        }

        if ($plan->extraColumns !== []) {
            $this->line('Columnas extra en BD (no están en script): ' . count($plan->extraColumns));
            if ($showComparison) {
                foreach ($plan->extraColumns as $row) {
                    $this->line("  ? {$row['table']}.{$row['column']}");
                }
                $this->newLine();
            }
        }

        if ($plan->modifiedColumns !== []) {
            $this->info('Columnas a modificar (' . count($plan->modifiedColumns) . '):');
            foreach ($plan->modifiedColumns as $action) {
                $this->line("  ~ {$action['table']}.{$action['column']}");
                if ($showSql) {
                    $this->line("    {$action['sql']}");
                }
            }
            $this->newLine();
        }

        if ($plan->addIndexes !== []) {
            $this->info('Índices a añadir (' . count($plan->addIndexes) . '):');
            foreach ($plan->addIndexes as $action) {
                $this->line("  + {$action['table']}.{$action['index']}");
                if ($showSql) {
                    $this->line("    {$action['sql']}");
                }
            }
            $this->newLine();
        }

        if ($plan->addForeignKeys !== []) {
            $this->info('Claves foráneas a añadir (' . count($plan->addForeignKeys) . '):');
            foreach ($plan->addForeignKeys as $action) {
                $this->line("  + {$action['table']}.{$action['constraint']}");
                if ($showSql) {
                    $this->line("    {$action['sql']}");
                }
            }
            $this->newLine();
        }

        if ($plan->seedData !== []) {
            $this->info('Datos de referencia a cargar (' . count($plan->seedData) . '):');
            foreach ($plan->seedData as $action) {
                $this->line("  + {$action['table']} ({$action['live_rows']} → {$action['expected_rows']} filas)");
                if ($showSql) {
                    $this->line("    archivo: {$action['file']}");
                }
            }
            $this->newLine();
        }

        if ($plan->ensurePlatformAdmin) {
            $this->info('Admin de plataforma (/admin) a crear:');
            $this->line('  + admin@fullvendor.com (usuario: admin, contraseña por defecto del bootstrap)');
            $this->newLine();
        }

        if ($plan->createOrReplaceViews !== []) {
            $this->info('Vistas a crear/actualizar (' . count($plan->createOrReplaceViews) . '):');
            foreach ($plan->createOrReplaceViews as $action) {
                $this->line("  ~ {$action['view']}");
                if ($showSql) {
                    $this->line('    ' . str_replace("\n", "\n    ", $action['sql']));
                }
            }
            $this->newLine();
        }

        if ($plan->skippedTables !== []) {
            $this->warn('Tablas omitidas por estructura legacy (' . count($plan->skippedTables) . '):');
            foreach ($plan->skippedTables as $action) {
                $this->line("  ! {$action['table']}: {$action['reason']}");
                if ($showComparison && ! empty($action['missing_in_live'])) {
                    $this->line('    faltan en BD: ' . implode(', ', $action['missing_in_live']));
                }
                if ($showComparison && ! empty($action['extra_in_live'])) {
                    $this->line('    extra en BD: ' . implode(', ', $action['extra_in_live']));
                }
            }
            $this->newLine();
        }

        if ($plan->skippedColumns !== []) {
            $this->warn('Columnas omitidas por validación (' . count($plan->skippedColumns) . '):');
            foreach ($plan->skippedColumns as $action) {
                $this->line("  ! {$action['table']}.{$action['column']}: {$action['reason']}");
            }
            $this->newLine();
        }

        $this->line('Acciones totales: ' . $plan->totalActions());
    }

    private function renderTableComparisons(SchemaSyncPlan $plan, bool $verbose): void
    {
        $tablesWithDrift = array_filter(
            $plan->tableComparisons,
            static fn ($comparison) => $comparison->hasDrift() || $comparison->isLegacy(),
        );

        if ($tablesWithDrift === []) {
            return;
        }

        $this->info('Comparación BD vs script (' . count($tablesWithDrift) . ' tablas con diferencias):');

        foreach ($tablesWithDrift as $comparison) {
            if ($comparison->isLegacy()) {
                $this->line("  ! {$comparison->table}: LEGACY — {$comparison->legacyReason}");
                if ($verbose) {
                    if ($comparison->missingInLive !== []) {
                        $this->line('      faltan en BD: ' . implode(', ', $comparison->missingInLive));
                    }
                    if ($comparison->extraInLive !== []) {
                        $this->line('      extra en BD: ' . implode(', ', $comparison->extraInLive));
                    }
                }
                continue;
            }

            $parts = [];
            if ($comparison->missingInLive !== []) {
                $parts[] = 'faltan ' . count($comparison->missingInLive);
            }
            if ($comparison->divergent !== []) {
                $parts[] = 'difieren ' . count($comparison->divergent);
            }
            if ($comparison->extraInLive !== []) {
                $parts[] = 'extra ' . count($comparison->extraInLive);
            }

            $this->line('  ~ ' . $comparison->table . ': ' . implode(', ', $parts));

            if (! $verbose) {
                continue;
            }

            foreach ($comparison->missingInLive as $column) {
                $this->line("      + falta en BD: {$column}");
            }
            foreach ($comparison->divergent as $column => $diff) {
                $expected = $this->formatColumnDef($diff['expected']);
                $actual = $this->formatColumnDef($diff['actual']);
                $this->line("      ~ {$column}: BD={$actual} | script={$expected}");
            }
            foreach ($comparison->extraInLive as $column) {
                $this->line("      ? extra en BD: {$column}");
            }
        }

        $this->newLine();
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function formatColumnDef(array $definition): string
    {
        $parts = [(string) ($definition['column_type'] ?? '?')];
        $parts[] = ($definition['is_nullable'] ?? 'YES') === 'YES' ? 'NULL' : 'NOT NULL';
        if (! empty($definition['column_default'])) {
            $parts[] = 'DEFAULT ' . $definition['column_default'];
        }
        if (! empty($definition['extra'])) {
            $parts[] = strtoupper((string) $definition['extra']);
        }

        return implode(' ', $parts);
    }
}

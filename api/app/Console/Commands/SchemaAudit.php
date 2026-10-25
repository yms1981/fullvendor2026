<?php

namespace App\Console\Commands;

use App\Support\SchemaMigration\ReferenceDataService;
use App\Support\SchemaMigration\PlatformAdminService;
use App\Support\SchemaMigration\SchemaSyncService;
use Illuminate\Console\Command;
use Throwable;

class SchemaAudit extends Command
{
    protected $signature = 'schema:audit
                            {--file= : Ruta al dump SQL de referencia}
                            {--views-dir= : Directorio con DDL de vistas adicionales}
                            {--seed-dir= : Directorio con manifest.json de datos fijos}';

    protected $description = 'Audita tablas, columnas, índices, datos fijos y vistas contra el esquema de referencia';

    public function handle(SchemaSyncService $syncService, ReferenceDataService $referenceData, PlatformAdminService $platformAdmin): int
    {
        $schemaFile = $this->option('file')
            ?: base_path('database/sql/appfv_full_schema.sql');

        $viewsDirectory = $this->option('views-dir')
            ?: SchemaSyncService::resolveViewsDirectory();

        $seedDirectory = $this->option('seed-dir')
            ?: ReferenceDataService::defaultSeedDirectory();

        $this->info('Auditando esquema...');
        $this->line("Archivo: {$schemaFile}");
        $this->line("Datos fijos: {$seedDirectory}");

        try {
            $plan = $syncService->buildPlan(
                schemaFile: $schemaFile,
                viewsDirectory: $viewsDirectory,
                syncViews: true,
                syncIndexes: true,
                syncSeedData: true,
                forceSeedData: false,
                ensurePlatformAdmin: true,
                seedDataDirectory: $seedDirectory,
                includeModifyColumns: false,
            );
            $seedAudit = $referenceData->audit($seedDirectory);
        } catch (Throwable $exception) {
            $this->error('Error al auditar: ' . $exception->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->line("Tablas referencia/live: {$plan->expectedTableCount} / {$plan->existingTableCount}");
        $this->line("Vistas referencia/live: {$plan->expectedViewCount} / {$plan->existingViewCount}");
        $this->line('Tablas comparadas (BD vs script): ' . count($plan->tableComparisons));
        $this->line('Tablas con columnas alineadas: ' . $plan->alignedTableCount());
        $this->newLine();

        $this->line('Tablas a crear: ' . count($plan->createTables));
        $this->line('Columnas faltantes en BD: ' . count($plan->addColumns));
        $this->line('Columnas con definición distinta: ' . count($plan->divergentColumns));
        $this->line('Columnas extra en BD: ' . count($plan->extraColumns));
        $this->line('Columnas a modificar: ' . count($plan->modifiedColumns));
        $this->line('Índices a añadir: ' . count($plan->addIndexes));
        $this->line('Datos de referencia a cargar: ' . count($plan->seedData));
        $this->line('Admin /admin a crear: ' . ($plan->ensurePlatformAdmin ? '1' : '0'));
        $this->line('Vistas a crear/actualizar: ' . count($plan->createOrReplaceViews));
        $this->line('Tablas omitidas (legacy): ' . count($plan->skippedTables));
        $this->line('Migraciones legacy pendientes: ' . count($plan->pendingLegacyMigrations));
        $this->line('Columnas omitidas (validación): ' . count($plan->skippedColumns));

        if ($plan->pendingLegacyMigrations !== []) {
            $this->newLine();
            $this->warn('Tablas legacy (PK/esquema antiguo):');
            foreach ($plan->pendingLegacyMigrations as $row) {
                $this->line("  ! [{$row['id']}] {$row['table']}: {$row['description']}");
            }
            $this->line('  → php artisan schema:legacy-audit --apply');
        }

        if ($plan->divergentColumns !== []) {
            $this->newLine();
            $this->warn('Columnas con definición distinta (BD vs script):');
            foreach (array_slice($plan->divergentColumns, 0, 20) as $row) {
                $this->line("  ~ {$row['table']}.{$row['column']}");
            }
            if (count($plan->divergentColumns) > 20) {
                $this->line('  ... y ' . (count($plan->divergentColumns) - 20) . ' más');
            }
        }

        if ($plan->skippedTables !== []) {
            $this->newLine();
            $this->warn('Tablas con estructura legacy incompatible:');
            foreach ($plan->skippedTables as $row) {
                $this->line("  ! {$row['table']}: {$row['reason']}");
            }
        }

        if ($plan->skippedColumns !== []) {
            $this->newLine();
            $this->warn('Columnas omitidas por validación:');
            foreach ($plan->skippedColumns as $row) {
                $this->line("  ! {$row['table']}.{$row['column']}: {$row['reason']}");
            }
        }

        $this->newLine();
        $this->info('Datos fijos (referencia):');
        $manifestOutdated = false;
        foreach ($seedAudit as $row) {
            $status = match ($row['status']) {
                'ok' => 'OK',
                'empty' => 'VACÍA',
                'incomplete' => 'INCOMPLETA',
                'manifest_outdated' => 'MANIFEST DESACTUALIZADO',
                default => strtoupper($row['status']),
            };
            if ($row['status'] === 'manifest_outdated') {
                $manifestOutdated = true;
            }
            $this->line(sprintf(
                '  %-22s %6d / %-6d  %s',
                $row['table'] . ':',
                $row['live_rows'],
                $row['expected_rows'],
                $status
            ));
        }

        if ($manifestOutdated) {
            $this->newLine();
            $this->warn('La BD tiene más filas que manifest.json. Ejecute: php artisan schema:export');
        }

        $adminAudit = $platformAdmin->audit();
        $this->newLine();
        $this->info('Admin plataforma (/admin):');
        if ($adminAudit['exists']) {
            $this->line(sprintf(
                '  OK  id=%s  email=%s  username=%s  user_id=%s',
                $adminAudit['admin_id'],
                $adminAudit['email'],
                $adminAudit['username'],
                $adminAudit['user_id'],
            ));
        } else {
            $this->line('  FALTA (' . $adminAudit['status'] . ') → schema:sync --force o schema:ensure-admin');
        }

        $schemaOk = ! $plan->hasChanges()
            && ! $plan->hasBlockingIssues()
            && ! $plan->hasColumnDrift();
        $seedOk = count($plan->seedData) === 0 && ! $manifestOutdated;
        $adminOk = $adminAudit['exists'] && ! $plan->ensurePlatformAdmin;

        if ($schemaOk && $seedOk && $adminOk) {
            $this->newLine();
            $this->info('La base de datos está alineada con el esquema, los datos de referencia y el admin de plataforma.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->warn('Se detectaron diferencias. Ejecute schema:sync --dry-run para ver el detalle.');

        return self::FAILURE;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Throwable;

/**
 * Sustituye el migrate legacy de Laravel: sincroniza desde database/sql/.
 *
 * Las migraciones en database/migrations/ ya no crean la BD inicial;
 * la fuente de verdad es appfv_full_schema.sql + vistas + seed_data.
 */
class SyncSchemaInsteadOfMigrateCommand extends Command
{
    protected $signature = 'migrate
                            {--database= : Ignorado; usa la conexión por defecto de .env}
                            {--force : Aplicar sin confirmación}
                            {--path=* : Ignorado (legacy)}
                            {--realpath : Ignorado (legacy)}
                            {--schema-path= : Ignorado (legacy)}
                            {--pretend : Equivalente a schema:sync --dry-run}
                            {--seed : Ignorado; schema:sync ya carga datos fijos}
                            {--seeder= : Ignorado (legacy)}
                            {--step : Ignorado (legacy)}
                            {--graceful : No fallar si la BD aún no está disponible}';

    protected $description = 'Sincroniza tablas, columnas, índices, datos fijos y vistas desde database/sql/';

    public function handle(): int
    {
        $this->newLine();
        $this->components->warn('migrate → schema:sync (api/database/sql/appfv_full_schema.sql)');
        $this->line('Las migraciones Laravel en database/migrations/ no crean la BD inicial.');
        $this->newLine();

        $options = [
            '--dry-run' => (bool) $this->option('pretend'),
            '--force' => (bool) $this->option('force'),
        ];

        try {
            return $this->call('schema:sync', $options);
        } catch (Throwable $exception) {
            if ((bool) $this->option('graceful')) {
                $this->warn('schema:sync omitido: ' . $exception->getMessage());

                return self::SUCCESS;
            }

            throw $exception;
        }
    }
}

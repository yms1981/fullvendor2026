<?php

namespace App\Console\Commands;

use App\Support\SchemaMigration\SchemaExporter;
use Illuminate\Console\Command;
use Throwable;

class SchemaExport extends Command
{
    protected $signature = 'schema:export
                            {--skip-verify : No ejecutar schema:audit al final}';

    protected $description = 'Exporta esquema, vistas y datos fijos desde la BD conectada (.env) al repositorio';

    public function handle(SchemaExporter $exporter): int
    {
        $this->info('Exportando artefactos de migración desde la base de datos...');

        try {
            foreach ($exporter->exportAll() as $message) {
                $this->line('  ' . $message);
            }
        } catch (Throwable $exception) {
            $this->error('Exportación fallida: ' . $exception->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Exportación completada. Revise los cambios en api/database/sql/');

        if ($this->option('skip-verify')) {
            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('Verificando alineación BD ↔ repositorio...');

        return $this->call('schema:audit');
    }
}

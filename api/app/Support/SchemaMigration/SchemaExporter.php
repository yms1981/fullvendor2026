<?php

namespace App\Support\SchemaMigration;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Symfony\Component\Process\Process;

class SchemaExporter
{
    /**
     * @return list<string>
     */
    public function exportAll(?string $seedDirectory = null): array
    {
        $seedDirectory = $seedDirectory ?? ReferenceDataService::defaultSeedDirectory();
        $messages = [];

        $schemaFile = base_path('database/sql/appfv_full_schema.sql');
        $viewsDir = base_path('database/sql/views');

        if (! is_dir($viewsDir) && ! mkdir($viewsDir, 0755, true) && ! is_dir($viewsDir)) {
            throw new RuntimeException("No se pudo crear {$viewsDir}");
        }

        if (! is_dir($seedDirectory) && ! mkdir($seedDirectory, 0755, true) && ! is_dir($seedDirectory)) {
            throw new RuntimeException("No se pudo crear {$seedDirectory}");
        }

        $this->dumpSchema($schemaFile);
        $messages[] = "Esquema: {$schemaFile}";

        $viewCount = $this->exportViews($viewsDir);
        $messages[] = "Vistas: {$viewCount} en {$viewsDir}";

        $tableCounts = $this->exportReferenceData($seedDirectory);
        foreach ($tableCounts as $table => $count) {
            $messages[] = "Datos {$table}: {$count} filas";
        }

        return $messages;
    }

    public function dumpSchema(string $outputFile): void
    {
        $command = array_merge(
            $this->baseDumpCommand(),
            ['--force', '--no-data', $this->databaseName()],
        );

        $this->runDumpToFile($command, $outputFile);
    }

    public function exportViews(string $viewsDirectory): int
    {
        $views = DB::select(
            'SELECT TABLE_NAME AS name
             FROM information_schema.VIEWS
             WHERE TABLE_SCHEMA = ?
             ORDER BY TABLE_NAME',
            [$this->databaseName()]
        );

        $count = 0;
        foreach ($views as $view) {
            $viewName = (string) $view->name;
            $row = DB::selectOne('SHOW CREATE VIEW `' . str_replace('`', '``', $viewName) . '`');
            if (! $row) {
                continue;
            }

            $createSql = null;
            foreach ((array) $row as $key => $value) {
                if (is_string($key) && strcasecmp($key, 'Create View') === 0) {
                    $createSql = (string) $value;
                    break;
                }
            }

            if ($createSql === null || $createSql === '') {
                continue;
            }

            $createSql = preg_replace(
                '/CREATE (?:OR REPLACE )?ALGORITHM=[^ ]+ DEFINER=[^ ]+ SQL SECURITY [^ ]+ VIEW/i',
                'CREATE OR REPLACE VIEW',
                $createSql
            ) ?? $createSql;
            $createSql = preg_replace('/CREATE VIEW/i', 'CREATE OR REPLACE VIEW', $createSql, 1) ?? $createSql;

            file_put_contents(
                rtrim($viewsDirectory, '/\\') . '/' . $viewName . '.sql',
                $createSql . ';' . PHP_EOL
            );
            $count++;
        }

        return $count;
    }

    /**
     * @return array<string, int>
     */
    public function exportReferenceData(string $seedDirectory): array
    {
        $referenceData = new ReferenceDataService();
        $tables = $referenceData::defaultTables();
        $counts = [];

        foreach ($tables as $table) {
            $outputFile = rtrim($seedDirectory, '/\\') . '/' . $table . '.sql';
            $command = array_merge(
                $this->baseDumpCommand(),
                [
                    '--no-create-info',
                    '--complete-insert',
                    '--skip-add-locks',
                    '--insert-ignore',
                    $this->databaseName(),
                    $table,
                ]
            );

            $this->runDumpToFile($command, $outputFile);

            $row = DB::selectOne('SELECT COUNT(*) AS total FROM `' . str_replace('`', '``', $table) . '`');
            $counts[$table] = (int) ($row->total ?? 0);
        }

        $manifest = [
            'version' => 1,
            'apply_order' => $tables,
            'tables' => [],
        ];

        foreach ($tables as $table) {
            $manifest['tables'][$table] = [
                'file' => $table . '.sql',
                'expected_rows' => $counts[$table],
            ];
        }

        file_put_contents(
            rtrim($seedDirectory, '/\\') . '/manifest.json',
            json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL
        );

        return $counts;
    }

    /**
     * @return list<string>
     */
    private function baseDumpCommand(): array
    {
        $connection = config('database.connections.' . config('database.default'));
        $binary = $this->resolveDumpBinary();

        return [
            $binary,
            '-h' . (string) ($connection['host'] ?? '127.0.0.1'),
            '-P' . (string) ($connection['port'] ?? '3306'),
            '-u' . (string) ($connection['username'] ?? 'root'),
            '-p' . (string) ($connection['password'] ?? ''),
        ];
    }

    private function resolveDumpBinary(): string
    {
        foreach (['mariadb-dump', 'mysqldump'] as $binary) {
            $process = Process::fromShellCommandline('command -v ' . escapeshellarg($binary));
            $process->run();
            if ($process->isSuccessful()) {
                return trim($process->getOutput());
            }
        }

        throw new RuntimeException('No se encontró mariadb-dump ni mysqldump en PATH.');
    }

    /**
     * @param  list<string>  $command
     */
    private function runDumpToFile(array $command, string $outputFile): void
    {
        $process = new Process($command);
        $process->setTimeout(600);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(trim($process->getErrorOutput()) ?: 'mariadb-dump falló.');
        }

        if (file_put_contents($outputFile, $process->getOutput()) === false) {
            throw new RuntimeException("No se pudo escribir {$outputFile}");
        }
    }

    private function databaseName(): string
    {
        return (string) DB::connection()->getDatabaseName();
    }
}

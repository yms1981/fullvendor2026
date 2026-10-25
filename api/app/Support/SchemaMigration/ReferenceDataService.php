<?php

namespace App\Support\SchemaMigration;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReferenceDataService
{
    /**
     * @return list<string>
     */
    public static function defaultTables(): array
    {
        return [
            'countries',
            'languages',
            'currency_formats',
            'currencybycountries',
            'states',
            'cities',
            'format_date',
            'format_numeric',
            'status_cart',
        ];
    }

    public static function defaultSeedDirectory(): string
    {
        return base_path('database/sql/seed_data');
    }

    /**
     * @return array{
     *     version: int,
     *     apply_order: list<string>,
     *     tables: array<string, array{file: string, expected_rows: int}>
     * }
     */
    public function loadManifest(?string $seedDirectory = null): array
    {
        $directory = $seedDirectory ?? self::defaultSeedDirectory();
        $manifestFile = rtrim($directory, '/\\') . '/manifest.json';

        if (! is_file($manifestFile)) {
            throw new RuntimeException("No se encontró manifest.json en {$directory}");
        }

        $json = file_get_contents($manifestFile);
        if ($json === false) {
            throw new RuntimeException("No se pudo leer {$manifestFile}");
        }

        $manifest = json_decode($json, true);
        if (! is_array($manifest) || ! isset($manifest['tables'], $manifest['apply_order'])) {
            throw new RuntimeException('manifest.json inválido');
        }

        return $manifest;
    }

    /**
     * @return list<array{
     *     table: string,
     *     file: string,
     *     live_rows: int,
     *     expected_rows: int,
     *     sql: string
     * }>
     */
    public function buildSeedActions(?string $seedDirectory = null, bool $force = false): array
    {
        $directory = $seedDirectory ?? self::defaultSeedDirectory();
        $manifest = $this->loadManifest($directory);
        $actions = [];

        foreach ($manifest['apply_order'] as $table) {
            if (! isset($manifest['tables'][$table])) {
                continue;
            }

            $tableMeta = $manifest['tables'][$table];
            $expectedRows = (int) ($tableMeta['expected_rows'] ?? 0);
            $file = rtrim($directory, '/\\') . '/' . ($tableMeta['file'] ?? "{$table}.sql");

            if (! is_file($file)) {
                throw new RuntimeException("No se encontró seed para {$table}: {$file}");
            }

            if ($expectedRows === 0 && ! $force) {
                continue;
            }

            if (! $this->hasInsertStatements($file)) {
                continue;
            }

            $liveRows = $this->countLiveRows($table);

            if (! $force && $liveRows >= $expectedRows) {
                continue;
            }

            $actions[] = [
                'table' => $table,
                'file' => $file,
                'live_rows' => $liveRows,
                'expected_rows' => $expectedRows,
                'sql' => file_get_contents($file) ?: '',
            ];
        }

        return $actions;
    }

    /**
     * @param  list<array{table: string, file: string, live_rows: int, expected_rows: int, sql: string}>  $actions
     * @return list<array{table: string, error: string}>
     */
    public function apply(array $actions): array
    {
        if ($actions === []) {
            return [];
        }

        $failures = [];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($actions as $action) {
                try {
                    DB::unprepared($action['sql']);
                } catch (\Throwable $exception) {
                    $failures[] = [
                        'table' => $action['table'],
                        'error' => $exception->getMessage(),
                    ];
                }
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        return $failures;
    }

    /**
     * @return list<array{table: string, live_rows: int, expected_rows: int, status: string}>
     */
    public function audit(?string $seedDirectory = null): array
    {
        $directory = $seedDirectory ?? self::defaultSeedDirectory();
        $manifest = $this->loadManifest($directory);
        $results = [];

        foreach ($manifest['apply_order'] as $table) {
            if (! isset($manifest['tables'][$table])) {
                continue;
            }

            $expectedRows = (int) ($manifest['tables'][$table]['expected_rows'] ?? 0);
            $liveRows = $this->countLiveRows($table);

            $status = 'ok';
            if ($expectedRows > 0 && $liveRows === 0) {
                $status = 'empty';
            } elseif ($expectedRows > 0 && $liveRows < $expectedRows) {
                $status = 'incomplete';
            } elseif ($liveRows > $expectedRows) {
                $status = 'manifest_outdated';
            }

            $results[] = [
                'table' => $table,
                'live_rows' => $liveRows,
                'expected_rows' => $expectedRows,
                'status' => $status,
            ];
        }

        return $results;
    }

    private function countLiveRows(string $table): int
    {
        if (! $this->readerTableExists($table)) {
            return 0;
        }

        $row = DB::selectOne('SELECT COUNT(*) AS total FROM `' . str_replace('`', '``', $table) . '`');

        return (int) ($row->total ?? 0);
    }

    private function readerTableExists(string $table): bool
    {
        $database = (string) DB::connection()->getDatabaseName();
        $row = DB::selectOne(
            'SELECT 1 AS found
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?
               AND TABLE_TYPE = ?',
            [$database, $table, 'BASE TABLE']
        );

        return $row !== null;
    }

    private function hasInsertStatements(string $file): bool
    {
        $content = file_get_contents($file);
        if ($content === false) {
            return false;
        }

        return (bool) preg_match('/\bINSERT\s+(?:IGNORE\s+)?INTO\b/i', $content);
    }
}

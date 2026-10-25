<?php

namespace App\Support\SchemaMigration;

use App\Services\CurrencyCatalogService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SchemaSyncService
{
    /**
     * @return list<string>
     */
    public static function defaultViewsDirectories(): array
    {
        return [
            base_path('database/sql/views'),
            base_path('../docker/DDL/appfv/appfv'),
        ];
    }

    public static function resolveViewsDirectory(): ?string
    {
        foreach (self::defaultViewsDirectories() as $directory) {
            if (is_dir($directory)) {
                return $directory;
            }
        }

        return null;
    }

    public function __construct(
        private readonly SchemaSqlParser $parser = new SchemaSqlParser(),
        private readonly LiveSchemaReader $reader = new LiveSchemaReader(),
        private readonly ReferenceDataService $referenceData = new ReferenceDataService(),
        private readonly PlatformAdminService $platformAdmin = new PlatformAdminService(),
        private readonly SchemaTableDependencySorter $dependencySorter = new SchemaTableDependencySorter(),
        private readonly TableColumnComparator $columnComparator = new TableColumnComparator(),
        private readonly ColumnAddValidator $columnValidator = new ColumnAddValidator(),
        private readonly LegacyTableMigrationService $legacyMigrator = new LegacyTableMigrationService(),
    ) {
    }

    /**
     * @return list<array{id: string, table: string, description: string, status: string, message: string|null}>
     */
    public function auditLegacyTables(): array
    {
        return $this->legacyMigrator->audit();
    }

    /**
     * @return list<array{id: string, table: string, description: string, status: string, message: string|null}>
     */
    public function applyLegacyTables(bool $dryRun = false): array
    {
        return $this->legacyMigrator->applyPending($dryRun);
    }

    public function buildPlan(
        string $schemaFile,
        ?string $viewsDirectory = null,
        bool $syncViews = true,
        bool $syncIndexes = true,
        bool $syncSeedData = true,
        bool $forceSeedData = false,
        bool $ensurePlatformAdmin = true,
        ?string $seedDataDirectory = null,
        bool $includeModifyColumns = false,
    ): SchemaSyncPlan {
        if (! is_file($schemaFile)) {
            throw new RuntimeException("No se encontró el archivo de esquema: {$schemaFile}");
        }

        $sql = file_get_contents($schemaFile);
        if ($sql === false) {
            throw new RuntimeException("No se pudo leer el archivo de esquema: {$schemaFile}");
        }

        $expectedTables = $this->parser->parseTables($sql);
        $expectedViews = $this->parser->parseViews($sql);

        if ($viewsDirectory !== null && is_dir($viewsDirectory)) {
            $directoryViews = $this->parser->parseViewsFromDirectory($viewsDirectory);
            // El dump principal prevalece; el directorio solo aporta vistas ausentes en el dump.
            $expectedViews = array_merge($directoryViews, $expectedViews);
        }

        $liveTables = $this->reader->listTables();
        $liveViews = $this->reader->listViews();

        $plan = new SchemaSyncPlan(
            expectedTableCount: count($expectedTables),
            expectedViewCount: count($expectedViews),
            existingTableCount: count($liveTables),
            existingViewCount: count($liveViews),
            pendingLegacyMigrations: $this->legacyMigrator->audit(),
        );

        foreach ($expectedTables as $tableName => $tableDefinition) {
            if (! isset($liveTables[$tableName])) {
                $createSql = preg_replace(
                    '/^CREATE TABLE /i',
                    'CREATE TABLE IF NOT EXISTS ',
                    $tableDefinition['create_sql_without_foreign_keys'] ?? $tableDefinition['create_sql']
                ) ?? ($tableDefinition['create_sql_without_foreign_keys'] ?? $tableDefinition['create_sql']);

                $plan->createTables[] = [
                    'table' => $tableName,
                    'sql' => $createSql,
                ];

                foreach ($tableDefinition['foreign_keys'] ?? [] as $foreignKey) {
                    $plan->addForeignKeys[] = [
                        'table' => $tableName,
                        'constraint' => $foreignKey['name'],
                        'sql' => "ALTER TABLE `{$tableName}` ADD {$foreignKey['definition']}",
                    ];
                }

                continue;
            }

            $liveColumns = $this->reader->listColumns($tableName);
            $comparison = $this->columnComparator->compare(
                $tableName,
                $tableDefinition['columns'],
                $liveColumns,
            );

            $plan->tableComparisons[] = $comparison;

            if ($comparison->isLegacy()) {
                $plan->skippedTables[] = [
                    'table' => $tableName,
                    'reason' => $comparison->legacyReason,
                    'missing_in_live' => $comparison->missingInLive,
                    'extra_in_live' => $comparison->extraInLive,
                ];

                continue;
            }

            foreach ($comparison->extraInLive as $extraColumn) {
                $plan->extraColumns[] = [
                    'table' => $tableName,
                    'column' => $extraColumn,
                ];
            }

            foreach ($comparison->divergent as $columnName => $diff) {
                $plan->divergentColumns[] = [
                    'table' => $tableName,
                    'column' => $columnName,
                    'expected' => $diff['expected'],
                    'actual' => $diff['actual'],
                ];

                if ($includeModifyColumns) {
                    $columnDefinition = $tableDefinition['columns'][$columnName];
                    if ($this->shouldSkipColumnModify($tableName, $columnName, $columnDefinition['definition'], $liveColumns)) {
                        $plan->skippedColumns[] = [
                            'table' => $tableName,
                            'column' => $columnName,
                            'reason' => 'Modificación de columna no segura (AUTO_INCREMENT o NOT NULL con datos).',
                        ];
                    } else {
                        $plan->modifiedColumns[] = [
                            'table' => $tableName,
                            'column' => $columnName,
                            'expected' => $diff['expected'],
                            'actual' => $diff['actual'],
                            'sql' => "ALTER TABLE `{$tableName}` MODIFY COLUMN `{$columnName}` {$columnDefinition['definition']}",
                        ];
                    }
                }
            }

            $previousColumn = $this->resolvePreviousColumn($tableDefinition['columns'], $liveColumns);

            foreach ($comparison->missingInLive as $columnName) {
                $columnDefinition = $tableDefinition['columns'][$columnName];

                $validation = $this->columnValidator->validate(
                    $tableName,
                    $columnName,
                    $columnDefinition['definition'],
                    $liveColumns,
                );

                if (! $validation['allowed']) {
                    $plan->skippedColumns[] = [
                        'table' => $tableName,
                        'column' => $columnName,
                        'reason' => $validation['reason'],
                    ];
                    continue;
                }

                $afterClause = $previousColumn !== null ? " AFTER `{$previousColumn}`" : '';
                $plan->addColumns[] = [
                    'table' => $tableName,
                    'column' => $columnName,
                    'sql' => "ALTER TABLE `{$tableName}` ADD COLUMN `{$columnName}` {$validation['definition']}{$afterClause}",
                ];

                $liveColumns[$columnName] = [
                    'name' => $columnName,
                    'column_type' => '',
                    'is_nullable' => 'YES',
                    'column_default' => null,
                    'extra' => '',
                ];
                $previousColumn = $columnName;
            }

            if ($syncIndexes) {
                $liveIndexes = $this->reader->listIndexes($tableName);
                $pendingColumns = array_column(
                    array_values(array_filter(
                        $plan->addColumns,
                        static fn (array $action): bool => $action['table'] === $tableName,
                    )),
                    'column',
                );
                $availableColumns = array_merge(array_keys($liveColumns), $pendingColumns);

                foreach ($tableDefinition['indexes'] ?? [] as $indexName => $indexDefinition) {
                    if ($this->indexExists($liveIndexes, $indexName, $indexDefinition)) {
                        continue;
                    }

                    if (! $this->indexColumnsExist($indexDefinition, $availableColumns)) {
                        $plan->skippedColumns[] = [
                            'table' => $tableName,
                            'column' => $indexName,
                            'reason' => "Índice `{$indexName}` omitido: faltan columnas en BD.",
                        ];
                        continue;
                    }

                    $indexType = $indexDefinition['unique'] ? 'UNIQUE INDEX' : 'INDEX';
                    $plan->addIndexes[] = [
                        'table' => $tableName,
                        'index' => $indexName,
                        'sql' => "ALTER TABLE `{$tableName}` ADD {$indexType} `{$indexName}` ({$this->extractIndexColumnList($indexDefinition['definition'])})",
                    ];
                }
            }
        }

        $plan->createTables = $this->dependencySorter->sortCreateActions(
            $plan->createTables,
            $this->buildTableDependencies($expectedTables, $plan->createTables),
        );

        if ($syncViews) {
            foreach ($expectedViews as $viewName => $createViewSql) {
                if (! isset($liveViews[$viewName])) {
                    $plan->createOrReplaceViews[] = [
                        'view' => $viewName,
                        'sql' => $createViewSql,
                    ];
                    continue;
                }

                $liveDefinition = $this->reader->getViewDefinition($viewName);
                $expectedNormalized = $this->reader->normalizeViewSql($createViewSql);
                $liveNormalized = $this->reader->normalizeViewSql($liveDefinition);

                if ($expectedNormalized !== $liveNormalized) {
                    $plan->createOrReplaceViews[] = [
                        'view' => $viewName,
                        'sql' => $createViewSql,
                    ];
                }
            }
        }

        if ($syncSeedData) {
            $plan->seedData = $this->referenceData->buildSeedActions(
                seedDirectory: $seedDataDirectory,
                force: $forceSeedData,
            );
        }

        if ($ensurePlatformAdmin && $this->platformAdmin->needsBootstrap()) {
            $plan->ensurePlatformAdmin = true;
        }

        return $plan;
    }

    /**
     * @return array{views: list<array{view: string, error: string}>, seeds: list<array{table: string, error: string}>, columns: list<array{table: string, column: string, error: string}>, indexes: list<array{table: string, index: string, error: string}>}
     */
    public function apply(SchemaSyncPlan $plan, bool $dryRun = false): array
    {
        if ($dryRun || ! $plan->hasChanges()) {
            return ['views' => [], 'seeds' => [], 'columns' => [], 'indexes' => [], 'legacy' => []];
        }

        $viewFailures = [];
        $seedFailures = [];
        $columnFailures = [];
        $indexFailures = [];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($plan->createTables as $action) {
                try {
                    DB::unprepared($action['sql']);
                } catch (\Throwable $exception) {
                    $columnFailures[] = [
                        'table' => $action['table'],
                        'column' => '(create table)',
                        'error' => $exception->getMessage(),
                    ];
                }
            }

            foreach ($plan->addColumns as $action) {
                if ($this->isUnsafeAddColumnSql($action['sql'])) {
                    $columnFailures[] = [
                        'table' => $action['table'],
                        'column' => $action['column'],
                        'error' => 'ADD COLUMN con AUTO_INCREMENT bloqueado; requiere migración manual.',
                    ];
                    continue;
                }

                try {
                    DB::statement($action['sql']);
                } catch (\Throwable $exception) {
                    $columnFailures[] = [
                        'table' => $action['table'],
                        'column' => $action['column'],
                        'error' => $exception->getMessage(),
                    ];
                }
            }

            foreach ($plan->modifiedColumns as $action) {
                try {
                    DB::statement($action['sql']);
                } catch (\Throwable $exception) {
                    $columnFailures[] = [
                        'table' => $action['table'],
                        'column' => $action['column'],
                        'error' => $exception->getMessage(),
                    ];
                }
            }

            foreach ($plan->addIndexes as $action) {
                try {
                    DB::statement($action['sql']);
                } catch (\Throwable $exception) {
                    $indexFailures[] = [
                        'table' => $action['table'],
                        'index' => $action['index'],
                        'error' => $exception->getMessage(),
                    ];
                }
            }

            foreach ($plan->seedData as $action) {
                try {
                    DB::unprepared($action['sql']);
                } catch (\Throwable $exception) {
                    $seedFailures[] = [
                        'table' => $action['table'],
                        'error' => $exception->getMessage(),
                    ];
                }
            }

            try {
                $stats = app(CurrencyCatalogService::class)->syncFromLegacyTable(fillMissingOnly: true);
                if (($stats['inserted'] + $stats['updated']) > 0) {
                    // logged implicitly via seed apply summary
                }
            } catch (\Throwable $exception) {
                $seedFailures[] = [
                    'table' => 'currency_catalog_sync',
                    'error' => $exception->getMessage(),
                ];
            }

            foreach ($plan->addForeignKeys as $action) {
                try {
                    DB::statement($action['sql']);
                } catch (\Throwable $exception) {
                    $seedFailures[] = [
                        'table' => $action['table'] . '.' . $action['constraint'],
                        'error' => $exception->getMessage(),
                    ];
                }
            }

            if ($plan->ensurePlatformAdmin) {
                try {
                    $this->platformAdmin->ensure();
                } catch (\Throwable $exception) {
                    $seedFailures[] = [
                        'table' => 'platform_admin',
                        'error' => $exception->getMessage(),
                    ];
                }
            }

            foreach ($plan->createOrReplaceViews as $action) {
                try {
                    DB::unprepared($action['sql']);
                } catch (\Throwable $exception) {
                    $viewFailures[] = [
                        'view' => $action['view'],
                        'error' => $exception->getMessage(),
                    ];
                }
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        return [
            'views' => $viewFailures,
            'seeds' => $seedFailures,
            'columns' => $columnFailures,
            'indexes' => $indexFailures,
            'legacy' => [],
        ];
    }

    /**
     * @param  array<string, array{name: string, definition: string}>  $expectedColumns
     * @param  array<string, array{name: string}>  $liveColumns
     */
    private function resolvePreviousColumn(array $expectedColumns, array $liveColumns): ?string
    {
        $previous = null;
        foreach ($expectedColumns as $columnName => $_) {
            if (isset($liveColumns[$columnName])) {
                $previous = $columnName;
            }
        }

        return $previous;
    }

    /**
     * @param  array{name: string, columns: string}  $indexDefinition
     * @param  list<string>  $availableColumns
     */
    private function indexColumnsExist(array $indexDefinition, array $availableColumns): bool
    {
        $columns = explode(',', $indexDefinition['columns']);
        $available = array_flip(array_map('strtolower', $availableColumns));

        foreach ($columns as $column) {
            $column = strtolower(preg_replace('/\(\d+\)$/', '', trim($column)) ?? trim($column));
            if (! isset($available[$column])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, array{name: string, column_type: string, extra: string}>  $liveColumns
     */
    private function shouldSkipColumnModify(
        string $table,
        string $column,
        string $definition,
        array $liveColumns,
    ): bool {
        $liveAuto = $this->columnValidator->findAutoIncrementColumn($liveColumns);
        $wantsAutoIncrement = (bool) preg_match('/\bAUTO_INCREMENT\b/i', $definition);

        return $wantsAutoIncrement && $liveAuto !== null && $liveAuto !== $column;
    }

    /**
     * @param  array<string, array{name: string, unique: bool, columns: string}>  $liveIndexes
     * @param  array{name: string, unique: bool, columns: string, definition: string}  $expectedIndex
     */
    private function indexExists(array $liveIndexes, string $indexName, array $expectedIndex): bool
    {
        if (isset($liveIndexes[$indexName])) {
            return $liveIndexes[$indexName]['columns'] === $expectedIndex['columns']
                && $liveIndexes[$indexName]['unique'] === $expectedIndex['unique'];
        }

        foreach ($liveIndexes as $liveIndex) {
            if ($liveIndex['columns'] === $expectedIndex['columns']
                && $liveIndex['unique'] === $expectedIndex['unique']) {
                return true;
            }
        }

        return false;
    }

    private function extractIndexColumnList(string $definition): string
    {
        if (preg_match('/\((.+)\)$/s', $definition, $match)) {
            return trim($match[1]);
        }

        throw new RuntimeException("No se pudo extraer columnas del índice: {$definition}");
    }

    private function isUnsafeAddColumnSql(string $sql): bool
    {
        return (bool) preg_match('/\bADD\s+COLUMN\b/i', $sql)
            && (bool) preg_match('/\bAUTO_INCREMENT\b/i', $sql);
    }

    /**
     * @param  array<string, array{foreign_keys?: array<string, array{referenced_table: string|null}>}>  $expectedTables
     * @param  array<int, array{table: string, sql: string}>  $createActions
     * @return array<string, list<string>>
     */
    private function buildTableDependencies(array $expectedTables, array $createActions): array
    {
        $dependencies = [];

        foreach ($createActions as $action) {
            $tableName = $action['table'];
            $dependencies[$tableName] = [];

            foreach ($expectedTables[$tableName]['foreign_keys'] ?? [] as $foreignKey) {
                $referenced = $foreignKey['referenced_table'] ?? null;
                if ($referenced !== null && $referenced !== $tableName) {
                    $dependencies[$tableName][] = $referenced;
                }
            }
        }

        return $dependencies;
    }
}

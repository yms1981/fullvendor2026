<?php

namespace App\Support\SchemaMigration;

class SchemaSyncPlan
{
    /**
     * @param  array<int, array{table: string, sql: string}>  $createTables
     * @param  array<int, array{table: string, column: string, sql: string}>  $addColumns
     * @param  array<int, array{table: string, column: string, expected: array<string, mixed>, actual: array<string, mixed>, sql: string}>  $modifiedColumns
     * @param  array<int, array{table: string, file: string, live_rows: int, expected_rows: int, sql: string}>  $seedData
     * @param  array<int, array{table: string, index: string, sql: string}>  $addIndexes
     * @param  array<int, array{view: string, sql: string}>  $createOrReplaceViews
     * @param  array<int, string>  $skippedViews
     */
    public function __construct(
        public array $createTables = [],
        public array $addColumns = [],
        public array $modifiedColumns = [],
        public array $addIndexes = [],
        public array $addForeignKeys = [],
        public array $skippedTables = [],
        public array $skippedColumns = [],
        /** @var list<TableColumnComparison> */
        public array $tableComparisons = [],
        public array $divergentColumns = [],
        public array $extraColumns = [],
        /** @var list<array{id: string, table: string, description: string, status: string, message: string|null}> */
        public array $pendingLegacyMigrations = [],
        /** @var list<array{id: string, table: string, description: string, status: string, message: string|null}> */
        public array $legacyMigrationResults = [],
        public array $seedData = [],
        public bool $ensurePlatformAdmin = false,
        public array $createOrReplaceViews = [],
        public array $skippedViews = [],
        public int $expectedTableCount = 0,
        public int $expectedViewCount = 0,
        public int $existingTableCount = 0,
        public int $existingViewCount = 0,
    ) {
    }

    public function hasChanges(): bool
    {
        return $this->createTables !== []
            || $this->addColumns !== []
            || $this->modifiedColumns !== []
            || $this->addIndexes !== []
            || $this->addForeignKeys !== []
            || $this->seedData !== []
            || $this->ensurePlatformAdmin
            || $this->createOrReplaceViews !== [];
    }

    public function hasBlockingIssues(): bool
    {
        return $this->skippedTables !== [] || $this->skippedColumns !== [] || $this->pendingLegacyMigrations !== [];
    }

    public function hasPendingLegacyMigrations(): bool
    {
        return $this->pendingLegacyMigrations !== [];
    }

    public function hasColumnDrift(): bool
    {
        foreach ($this->tableComparisons as $comparison) {
            if ($comparison->isLegacy()) {
                continue;
            }

            if ($comparison->missingInLive !== [] || $comparison->divergent !== []) {
                return true;
            }
        }

        return false;
    }

    public function alignedTableCount(): int
    {
        $count = 0;
        foreach ($this->tableComparisons as $comparison) {
            if ($comparison->isFullyAligned()) {
                $count++;
            }
        }

        return $count;
    }

    public function totalActions(): int
    {
        return count($this->createTables)
            + count($this->addColumns)
            + count($this->modifiedColumns)
            + count($this->addIndexes)
            + count($this->addForeignKeys)
            + count($this->seedData)
            + ($this->ensurePlatformAdmin ? 1 : 0)
            + count($this->createOrReplaceViews);
    }
}

<?php

namespace App\Support\SchemaMigration;

use Illuminate\Support\Facades\DB;

class LiveSchemaReader
{
    private string $database;

    public function __construct(?string $connection = null)
    {
        $connectionName = $connection ?? (string) config('database.default');
        $this->database = (string) DB::connection($connectionName)->getDatabaseName();
    }

    /**
     * @return array<string, true>
     */
    public function listTables(): array
    {
        $rows = DB::select(
            'SELECT TABLE_NAME AS name
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ?
               AND TABLE_TYPE = ?',
            [$this->database, 'BASE TABLE']
        );

        $tables = [];
        foreach ($rows as $row) {
            $tables[(string) $row->name] = true;
        }

        return $tables;
    }

    /**
     * @return array<string, true>
     */
    public function listViews(): array
    {
        $rows = DB::select(
            'SELECT TABLE_NAME AS name
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = ?
               AND TABLE_TYPE = ?',
            [$this->database, 'VIEW']
        );

        $views = [];
        foreach ($rows as $row) {
            $views[(string) $row->name] = true;
        }

        return $views;
    }

    /**
     * @return array<string, array{
     *     name: string,
     *     column_type: string,
     *     is_nullable: string,
     *     column_default: mixed,
     *     extra: string,
     *     ordinal_position: int
     * }>
     */
    public function listColumns(string $table): array
    {
        $rows = DB::select(
            'SELECT COLUMN_NAME AS name,
                    COLUMN_TYPE AS column_type,
                    IS_NULLABLE AS is_nullable,
                    COLUMN_DEFAULT AS column_default,
                    EXTRA AS extra,
                    ORDINAL_POSITION AS ordinal_position
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?
             ORDER BY ORDINAL_POSITION',
            [$this->database, $table]
        );

        $columns = [];
        foreach ($rows as $row) {
            $columns[(string) $row->name] = [
                'name' => (string) $row->name,
                'column_type' => (string) $row->column_type,
                'is_nullable' => (string) $row->is_nullable,
                'column_default' => $row->column_default,
                'extra' => (string) $row->extra,
                'ordinal_position' => (int) $row->ordinal_position,
            ];
        }

        return $columns;
    }

    /**
     * @return array<string, array{name: string, unique: bool, columns: string}>
     */
    public function listIndexes(string $table): array
    {
        $rows = DB::select(
            'SELECT INDEX_NAME AS name,
                    NON_UNIQUE AS non_unique,
                    COLUMN_NAME AS column_name,
                    SUB_PART AS sub_part,
                    SEQ_IN_INDEX AS seq
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?
             ORDER BY INDEX_NAME, SEQ_IN_INDEX',
            [$this->database, $table]
        );

        $grouped = [];
        foreach ($rows as $row) {
            $indexName = (string) $row->name;
            if ($indexName === 'PRIMARY') {
                continue;
            }

            $column = strtolower((string) $row->column_name);
            if ($row->sub_part !== null) {
                $column .= '(' . (int) $row->sub_part . ')';
            }

            $grouped[$indexName]['name'] = $indexName;
            $grouped[$indexName]['unique'] = (int) $row->non_unique === 0;
            $grouped[$indexName]['columns'][] = $column;
        }

        $indexes = [];
        foreach ($grouped as $indexName => $data) {
            $indexes[$indexName] = [
                'name' => $indexName,
                'unique' => $data['unique'],
                'columns' => implode(',', $data['columns']),
            ];
        }

        return $indexes;
    }

    /**
     * @return array{constraint: string, referenced_table: string, referenced_column: string}|null
     */
    public function getForeignKeyReference(string $table, string $column): ?array
    {
        $rows = DB::select(
            'SELECT CONSTRAINT_NAME AS constraint_name,
                    REFERENCED_TABLE_NAME AS referenced_table,
                    REFERENCED_COLUMN_NAME AS referenced_column
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1',
            [$this->database, $table, $column]
        );

        if ($rows === []) {
            return null;
        }

        $row = $rows[0];

        return [
            'constraint' => (string) $row->constraint_name,
            'referenced_table' => (string) $row->referenced_table,
            'referenced_column' => (string) $row->referenced_column,
        ];
    }

    public function getViewDefinition(string $viewName): ?string
    {
        $row = DB::selectOne('SHOW CREATE VIEW `' . str_replace('`', '``', $viewName) . '`');

        if (! $row) {
            return null;
        }

        $createView = null;
        foreach ((array) $row as $key => $value) {
            if (is_string($key) && strcasecmp($key, 'Create View') === 0) {
                $createView = (string) $value;
                break;
            }
        }

        return $createView;
    }

    public function normalizeViewSql(?string $sql): string
    {
        if ($sql === null || trim($sql) === '') {
            return '';
        }

        $normalized = strtolower($sql);
        $normalized = preg_replace('/create\s+(?:or\s+replace\s+)?(?:algorithm\s*=\s*[^\s]+\s+)?(?:definer\s*=\s*[^\s]+\s+)?(?:sql\s+security\s+\w+\s+)?view\s+`[^`]+`\s+as\s+/i', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/`[^`]+`\./', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;
        $normalized = rtrim(trim($normalized), ';');

        return trim($normalized);
    }
}

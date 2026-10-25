<?php

namespace App\Support\SchemaMigration;

use Illuminate\Support\Facades\DB;

class ColumnAddValidator
{
    /** @var array<string, bool> */
    private array $tableRowCountCache = [];

    /**
     * @param  array<string, array{name: string, column_type: string, is_nullable: string, column_default: mixed, extra: string}>  $liveColumns
     * @return array{allowed: bool, reason: string|null, definition: string}
     */
    public function validate(string $table, string $column, string $definition, array $liveColumns): array
    {
        $definition = trim($definition);
        $liveAutoColumn = $this->findAutoIncrementColumn($liveColumns);
        $wantsAutoIncrement = (bool) preg_match('/\bAUTO_INCREMENT\b/i', $definition);

        if ($wantsAutoIncrement) {
            if ($liveAutoColumn !== null && $liveAutoColumn !== $column) {
                return [
                    'allowed' => false,
                    'reason' => "La tabla ya tiene AUTO_INCREMENT en `{$liveAutoColumn}`; no se puede añadir `{$column}` AUTO_INCREMENT.",
                    'definition' => $definition,
                ];
            }

            // MariaDB no permite ADD COLUMN ... AUTO_INCREMENT sin definirla como KEY en el mismo ALTER.
            return [
                'allowed' => false,
                'reason' => "No se puede añadir `{$column}` AUTO_INCREMENT con ALTER ADD COLUMN; migración manual de `{$table}`.",
                'definition' => $definition,
            ];
        }

        if (preg_match('/\bNOT NULL\b/i', $definition)
            && ! preg_match('/\bDEFAULT\b/i', $definition)
            && $this->tableHasRows($table)) {
            return [
                'allowed' => false,
                'reason' => "No se puede añadir `{$column}` NOT NULL sin DEFAULT en una tabla con datos.",
                'definition' => $definition,
            ];
        }

        return [
            'allowed' => true,
            'reason' => null,
            'definition' => trim($definition),
        ];
    }

    /**
     * @param  array<string, array{name: string, column_type: string, is_nullable: string, column_default: mixed, extra: string}>  $liveColumns
     */
    public function findAutoIncrementColumn(array $liveColumns): ?string
    {
        foreach ($liveColumns as $name => $column) {
            if (str_contains(strtolower((string) $column['extra']), 'auto_increment')) {
                return $name;
            }
        }

        return null;
    }

    private function tableHasRows(string $table): bool
    {
        if (isset($this->tableRowCountCache[$table])) {
            return $this->tableRowCountCache[$table];
        }

        $reader = new LiveSchemaReader();
        if (! isset($reader->listTables()[$table])) {
            $this->tableRowCountCache[$table] = false;

            return false;
        }

        $row = DB::selectOne(
            'SELECT COUNT(*) AS total FROM `' . str_replace('`', '``', $table) . '`'
        );

        $hasRows = ((int) ($row->total ?? 0)) > 0;
        $this->tableRowCountCache[$table] = $hasRows;

        return $hasRows;
    }
}

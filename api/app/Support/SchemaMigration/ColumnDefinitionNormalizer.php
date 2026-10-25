<?php

namespace App\Support\SchemaMigration;

class ColumnDefinitionNormalizer
{
    public function normalizeExpectedDefinition(string $definition): array
    {
        $definition = preg_replace('/\s+COMMENT\s+\'[^\']*\'/i', '', $definition) ?? $definition;
        $definition = preg_replace('/\s+COLLATE\s+[a-z0-9_]+/i', '', $definition) ?? $definition;
        $definition = preg_replace('/\s+CHARACTER SET\s+[a-z0-9_]+/i', '', $definition) ?? $definition;
        $definition = preg_replace('/\s+ON UPDATE current_timestamp\(\)/i', '', $definition) ?? $definition;
        $definition = trim($definition);

        $nullable = ! preg_match('/\bNOT NULL\b/i', $definition);
        $definition = preg_replace('/\bNOT NULL\b/i', '', $definition) ?? $definition;
        $definition = preg_replace('/\bNULL\b/i', '', $definition) ?? $definition;

        $autoIncrement = (bool) preg_match('/\bAUTO_INCREMENT\b/i', $definition);
        $definition = preg_replace('/\bAUTO_INCREMENT\b/i', '', $definition) ?? $definition;

        $default = null;
        if (preg_match('/\bDEFAULT\s+((?:\'[^\']*\'|"[^"]*"|current_timestamp\(\)|NULL|\d+(?:\.\d+)?))/i', $definition, $match)) {
            $default = $this->normalizeDefaultValue($match[1]);
            $definition = preg_replace('/\bDEFAULT\s+((?:\'[^\']*\'|"[^"]*"|current_timestamp\(\)|NULL|\d+(?:\.\d+)?))/i', '', $definition) ?? $definition;
        }

        $type = $this->normalizeColumnType(trim(preg_replace('/\s+/', ' ', $definition) ?? $definition));

        return [
            'column_type' => $type,
            'is_nullable' => $nullable ? 'YES' : 'NO',
            'column_default' => $default,
            'extra' => $autoIncrement ? 'auto_increment' : '',
        ];
    }

    public function normalizeLiveColumn(array $column): array
    {
        return [
            'column_type' => $this->normalizeColumnType((string) $column['column_type']),
            'is_nullable' => (string) $column['is_nullable'],
            'column_default' => $this->normalizeDefaultValue($column['column_default']),
            'extra' => strtolower(str_replace(' ', '_', (string) $column['extra'])),
        ];
    }

    public function normalizeColumnType(string $type): string
    {
        $type = strtolower(trim($type));
        $type = preg_replace('/\s+unsigned$/', ' unsigned', $type) ?? $type;
        $type = preg_replace('/\b(int|tinyint|smallint|mediumint|bigint)\(\d+\)/', '$1', $type) ?? $type;
        $type = preg_replace('/\b(decimal|numeric)\(\d+,\d+\)/', '$1', $type) ?? $type;
        $type = preg_replace('/\s+/', ' ', $type) ?? $type;

        return trim($type);
    }

    private function normalizeDefaultValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $stringValue = strtolower(trim((string) $value));
        $stringValue = trim($stringValue, "'\"");

        if ($stringValue === 'null') {
            return null;
        }

        if ($stringValue === 'current_timestamp()' || $stringValue === 'current_timestamp') {
            return 'current_timestamp()';
        }

        return $stringValue;
    }
}

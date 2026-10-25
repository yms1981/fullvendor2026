<?php

namespace App\Support\SchemaMigration;

use RuntimeException;

class SchemaSqlParser
{
    /**
     * @return array<string, array{name: string, create_sql: string, columns: array<string, array{name: string, definition: string, position: int}>}>
     */
    public function parseTables(string $sql): array
    {
        $tables = [];
        $lines = preg_split('/\R/', $sql) ?: [];
        $count = count($lines);

        for ($i = 0; $i < $count; $i++) {
            $line = $lines[$i];

            if (! preg_match('/^CREATE TABLE `([^`]+)` \($/', $line)) {
                continue;
            }

            $tableName = (string) preg_replace('/^CREATE TABLE `([^`]+)` \($/', '$1', $line);
            $bodyLines = [];
            $i++;

            while ($i < $count && ! preg_match('/^\) ENGINE=/', $lines[$i])) {
                $bodyLines[] = $lines[$i];
                $i++;
            }

            if ($i >= $count) {
                throw new RuntimeException("CREATE TABLE `{$tableName}` sin cierre ENGINE.");
            }

            $engineLine = $lines[$i];
            $body = implode("\n", $bodyLines);
            $createSql = "CREATE TABLE `{$tableName}` (\n{$body}\n{$engineLine}";
            $createSql = $this->normalizeCreateTableSql($createSql);

            $columns = $this->parseColumnsFromBody($body);
            $indexes = $this->parseIndexesFromBody($body);
            $foreignKeys = $this->parseForeignKeysFromBody($body);
            $createSqlWithoutForeignKeys = $this->stripForeignKeysFromCreateSql($createSql);

            $tables[$tableName] = [
                'name' => $tableName,
                'create_sql' => $createSql,
                'create_sql_without_foreign_keys' => $createSqlWithoutForeignKeys,
                'columns' => $columns,
                'indexes' => $indexes,
                'foreign_keys' => $foreignKeys,
            ];
        }

        return $tables;
    }

    /**
     * @return array<string, string> viewName => CREATE VIEW statement (sin DEFINER)
     */
    public function parseViews(string $sql): array
    {
        $views = [];

        if (preg_match_all(
            '/\/\*!50001 VIEW `([^`]+)` AS (.+?) \*\/;/s',
            $sql,
            $matches,
            PREG_SET_ORDER
        )) {
            foreach ($matches as $match) {
                $viewName = $match[1];
                $selectBody = trim($match[2]);

                if (preg_match('/^select\s+1\s+AS/i', $selectBody)) {
                    continue;
                }

                $views[$viewName] = $this->buildCreateViewStatement($viewName, $selectBody);
            }
        }

        return $views;
    }

    /**
     * @return array<string, string>
     */
    public function parseViewsFromDirectory(string $directory): array
    {
        if (! is_dir($directory)) {
            return [];
        }

        $views = [];
        $files = glob(rtrim($directory, '/\\') . '/*.sql') ?: [];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false || trim($content) === '') {
                continue;
            }

            if (! preg_match('/create\s+(?:or\s+replace\s+)?(?:algorithm\s*=\s*\w+\s+)?(?:definer\s*=\s*[^\s]+\s+)?view\s+`?([a-zA-Z0-9_]+)`?\s+as\s+(.+)$/is', $content, $match)) {
                continue;
            }

            $viewName = $match[1];
            $selectBody = trim($match[2]);
            $selectBody = preg_replace('/;\s*$/', '', $selectBody) ?? $selectBody;
            $selectBody = preg_replace('/\s*--.*$/m', '', $selectBody) ?? $selectBody;
            $selectBody = preg_replace('/`appfv`\./i', '', $selectBody) ?? $selectBody;
            $selectBody = preg_replace('/\bappfv\./i', '', $selectBody) ?? $selectBody;

            $views[$viewName] = $this->buildCreateViewStatement($viewName, trim($selectBody));
        }

        return $views;
    }

    /**
     * @return array<string, array{name: string, unique: bool, columns: string, definition: string}>
     */
    public function parseIndexesFromBody(string $body): array
    {
        $indexes = [];

        foreach ($this->splitSqlParts($body) as $part) {
            $part = trim($part);

            if (preg_match('/^PRIMARY KEY/i', $part) || preg_match('/^CONSTRAINT/i', $part)) {
                continue;
            }

            if (preg_match('/^UNIQUE KEY `([^`]+)` \((.+)\)$/is', $part, $match)) {
                $indexes[$match[1]] = [
                    'name' => $match[1],
                    'unique' => true,
                    'columns' => $this->normalizeIndexColumns($match[2]),
                    'definition' => $part,
                ];
                continue;
            }

            if (preg_match('/^(?:KEY|INDEX) `([^`]+)` \((.+)\)$/is', $part, $match)) {
                $indexes[$match[1]] = [
                    'name' => $match[1],
                    'unique' => false,
                    'columns' => $this->normalizeIndexColumns($match[2]),
                    'definition' => $part,
                ];
            }
        }

        return $indexes;
    }

    /**
     * @return array<string, array{name: string, definition: string, referenced_table: string|null}>
     */
    public function parseForeignKeysFromBody(string $body): array
    {
        $foreignKeys = [];

        foreach ($this->splitSqlParts($body) as $part) {
            $part = trim($part);

            if (! preg_match('/^CONSTRAINT `([^`]+)` FOREIGN KEY/i', $part, $match)) {
                continue;
            }

            $referencedTable = null;
            if (preg_match('/REFERENCES `([^`]+)`/i', $part, $referenceMatch)) {
                $referencedTable = $referenceMatch[1];
            }

            $foreignKeys[$match[1]] = [
                'name' => $match[1],
                'definition' => $part,
                'referenced_table' => $referencedTable,
            ];
        }

        return $foreignKeys;
    }

    public function stripForeignKeysFromCreateSql(string $createSql): string
    {
        if (! preg_match('/^(CREATE TABLE `([^`]+)` \()\n?(.*?)\n(\) ENGINE=.+)$/s', $createSql, $match)) {
            return $createSql;
        }

        $keptParts = [];
        foreach ($this->splitSqlParts($match[3]) as $part) {
            if (preg_match('/^CONSTRAINT `[^`]+` FOREIGN KEY/i', trim($part))) {
                continue;
            }

            $keptParts[] = $part;
        }

        return $match[1] . "\n" . implode(",\n", $keptParts) . "\n" . $match[4];
    }

    private function normalizeIndexColumns(string $columns): string
    {
        $columns = preg_replace('/\s+/', '', $columns) ?? $columns;
        $columns = str_replace('`', '', $columns);

        return strtolower($columns);
    }

    /**
     * @return array<string, array{name: string, definition: string, position: int}>
     */
    private function parseColumnsFromBody(string $body): array
    {
        $columns = [];
        $position = 0;

        foreach ($this->splitSqlParts($body) as $part) {
            if (! preg_match('/^`([^`]+)`\s+(.+)$/s', $part, $match)) {
                continue;
            }

            $name = $match[1];
            $definition = trim($match[2]);

            if ($this->isTableConstraint($definition)) {
                continue;
            }

            $columns[$name] = [
                'name' => $name,
                'definition' => $definition,
                'position' => $position,
            ];
            $position++;
        }

        return $columns;
    }

    private function isTableConstraint(string $definition): bool
    {
        return (bool) preg_match('/^(PRIMARY KEY|UNIQUE KEY|KEY|CONSTRAINT|FULLTEXT KEY|SPATIAL KEY)\b/i', $definition);
    }

    /**
     * @return list<string>
     */
    private function splitSqlParts(string $body): array
    {
        $parts = [];
        $current = '';
        $depth = 0;
        $inString = false;
        $stringChar = '';
        $length = strlen($body);

        for ($i = 0; $i < $length; $i++) {
            $char = $body[$i];

            if ($inString) {
                $current .= $char;
                if ($char === $stringChar && ($i === 0 || $body[$i - 1] !== '\\')) {
                    $inString = false;
                }
                continue;
            }

            if ($char === "'" || $char === '"') {
                $inString = true;
                $stringChar = $char;
                $current .= $char;
                continue;
            }

            if ($char === '(') {
                $depth++;
                $current .= $char;
                continue;
            }

            if ($char === ')') {
                $depth--;
                $current .= $char;
                continue;
            }

            if ($char === ',' && $depth === 0) {
                $parts[] = trim($current);
                $current = '';
                continue;
            }

            $current .= $char;
        }

        if (trim($current) !== '') {
            $parts[] = trim($current);
        }

        return $parts;
    }

    private function normalizeCreateTableSql(string $sql): string
    {
        $sql = preg_replace('/\sAUTO_INCREMENT=\d+/i', '', $sql) ?? $sql;

        return trim($sql);
    }

    private function buildCreateViewStatement(string $viewName, string $selectBody): string
    {
        $selectBody = preg_replace('/`appfv`\./i', '', $selectBody) ?? $selectBody;
        $selectBody = preg_replace('/\bappfv\./i', '', $selectBody) ?? $selectBody;

        return "CREATE OR REPLACE VIEW `{$viewName}` AS {$selectBody}";
    }
}

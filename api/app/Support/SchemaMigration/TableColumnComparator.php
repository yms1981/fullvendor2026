<?php

namespace App\Support\SchemaMigration;

class TableColumnComparator
{
    public function __construct(
        private readonly ColumnDefinitionNormalizer $normalizer = new ColumnDefinitionNormalizer(),
        private readonly ColumnAddValidator $columnValidator = new ColumnAddValidator(),
    ) {
    }

    /**
     * Compara columna a columna la BD live con el script de referencia.
     *
     * @param  array<string, array{name: string, definition: string}>  $expectedColumns
     * @param  array<string, array{name: string, column_type: string, is_nullable: string, column_default: mixed, extra: string}>  $liveColumns
     */
    public function compare(string $table, array $expectedColumns, array $liveColumns): TableColumnComparison
    {
        $legacyReason = $this->detectLegacyReason($expectedColumns, $liveColumns);

        if ($legacyReason !== null) {
            return new TableColumnComparison(
                table: $table,
                missingInLive: array_values(array_diff(array_keys($expectedColumns), array_keys($liveColumns))),
                extraInLive: array_values(array_diff(array_keys($liveColumns), array_keys($expectedColumns))),
                legacyReason: $legacyReason,
            );
        }

        $missingInLive = [];
        $extraInLive = array_values(array_diff(array_keys($liveColumns), array_keys($expectedColumns)));
        $aligned = [];
        $divergent = [];

        foreach ($expectedColumns as $columnName => $columnDefinition) {
            if (! isset($liveColumns[$columnName])) {
                $missingInLive[] = $columnName;
                continue;
            }

            $expectedNormalized = $this->normalizer->normalizeExpectedDefinition($columnDefinition['definition']);
            $actualNormalized = $this->normalizer->normalizeLiveColumn($liveColumns[$columnName]);

            if ($this->columnsDiffer($expectedNormalized, $actualNormalized)) {
                $divergent[$columnName] = [
                    'expected' => $expectedNormalized,
                    'actual' => $actualNormalized,
                ];
            } else {
                $aligned[] = $columnName;
            }
        }

        return new TableColumnComparison(
            table: $table,
            missingInLive: $missingInLive,
            extraInLive: $extraInLive,
            aligned: $aligned,
            divergent: $divergent,
        );
    }

    /**
     * @param  array<string, array{name: string, definition: string}>  $expectedColumns
     * @param  array<string, array{name: string, extra: string}>  $liveColumns
     */
    private function detectLegacyReason(array $expectedColumns, array $liveColumns): ?string
    {
        if ($liveColumns === []) {
            return null;
        }

        $liveAuto = $this->columnValidator->findAutoIncrementColumn($liveColumns);
        $expectedAuto = $this->findExpectedAutoIncrementColumn($expectedColumns);

        if ($expectedAuto !== null && ! isset($liveColumns[$expectedAuto])) {
            if ($liveAuto !== null) {
                return "PK/auto `{$liveAuto}` en BD vs `{$expectedAuto}` en script";
            }

            return "falta PK AUTO_INCREMENT `{$expectedAuto}` — migración manual de tabla";
        }

        if ($liveAuto !== null && $expectedAuto !== null && $liveAuto !== $expectedAuto) {
            return "PK/auto `{$liveAuto}` en BD vs `{$expectedAuto}` en script";
        }

        $overlap = count(array_intersect_key($expectedColumns, $liveColumns));
        $expectedCount = count($expectedColumns);
        $liveCount = count($liveColumns);

        if ($overlap === 0 && $expectedCount > 0 && $liveCount > 0) {
            return 'ninguna columna coincide entre BD y script';
        }

        if ($overlap > 0
            && ($expectedCount - $overlap) / max($expectedCount, 1) > 0.5
            && ($liveCount - $overlap) / max($liveCount, 1) > 0.5) {
            return 'más del 50% de columnas difieren de nombre';
        }

        return null;
    }

    /**
     * @param  array<string, array{name: string, definition: string}>  $expectedColumns
     */
    private function findExpectedAutoIncrementColumn(array $expectedColumns): ?string
    {
        foreach ($expectedColumns as $name => $column) {
            $normalized = $this->normalizer->normalizeExpectedDefinition($column['definition']);
            if ($normalized['extra'] === 'auto_increment') {
                return $name;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $expected
     * @param  array<string, mixed>  $actual
     */
    private function columnsDiffer(array $expected, array $actual): bool
    {
        return $expected['column_type'] !== $actual['column_type']
            || $expected['is_nullable'] !== $actual['is_nullable']
            || $expected['column_default'] !== $actual['column_default']
            || $expected['extra'] !== $actual['extra'];
    }
}

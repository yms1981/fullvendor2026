<?php

namespace App\Support\SchemaMigration;

class SchemaTableDependencySorter
{
    /**
     * @param  array<int, array{table: string, sql?: string}>  $actions
     * @param  array<string, list<string>>  $dependencies
     * @return array<int, array{table: string, sql?: string}>
     */
    public function sortCreateActions(array $actions, array $dependencies): array
    {
        if ($actions === []) {
            return [];
        }

        $pending = [];
        foreach ($actions as $action) {
            $pending[$action['table']] = $action;
        }

        $sorted = [];
        $visited = [];
        $visiting = [];

        foreach (array_keys($pending) as $table) {
            $this->visit($table, $dependencies, $pending, $visited, $visiting, $sorted);
        }

        return array_values($sorted);
    }

    /**
     * @param  array<string, list<string>>  $dependencies
     * @param  array<string, array{table: string, sql?: string}>  $pending
     * @param  array<string, true>  $visited
     * @param  array<string, true>  $visiting
     * @param  array<string, array{table: string, sql?: string}>  $sorted
     */
    private function visit(
        string $table,
        array $dependencies,
        array $pending,
        array &$visited,
        array &$visiting,
        array &$sorted,
    ): void {
        if (isset($visited[$table]) || ! isset($pending[$table])) {
            return;
        }

        if (isset($visiting[$table])) {
            return;
        }

        $visiting[$table] = true;

        foreach ($dependencies[$table] ?? [] as $dependency) {
            if (isset($pending[$dependency])) {
                $this->visit($dependency, $dependencies, $pending, $visited, $visiting, $sorted);
            }
        }

        unset($visiting[$table]);
        $visited[$table] = true;
        $sorted[$table] = $pending[$table];
    }
}

<?php

namespace App\Console\Commands;

use App\Services\LanguageSyncService;
use App\Support\Languages\SupportedLanguages;
use Illuminate\Console\Command;
use Throwable;

class SyncLanguages extends Command
{
    protected $signature = 'languages:sync
                            {--dry-run : Solo auditar idiomas faltantes o desactualizados}
                            {--no-update : Insertar faltantes sin actualizar registros existentes}';

    protected $description = 'Audita los idiomas del sistema (api/lang + catálogo canónico) y sincroniza la tabla languages';

    public function handle(LanguageSyncService $syncService): int
    {
        $this->info('Idiomas definidos por el sistema: ' . count(SupportedLanguages::definitions()));

        foreach (SupportedLanguages::definitions() as $definition) {
            $this->line(sprintf(
                '  #%d %s (%s) → %s',
                $definition['language_id'],
                $definition['name'],
                $definition['iso_639_1'],
                $definition['json_file']
            ));
        }

        try {
            $audit = $syncService->audit();
        } catch (Throwable $exception) {
            $this->error('Error al auditar languages: ' . $exception->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->line('Registros en tabla languages (soportados): ' . $audit['existing'] . ' / ' . $audit['supported']);

        $this->renderIdList('Faltantes en BD', $audit['missing']);
        $this->renderIdList('Desactualizados', $audit['outdated']);
        $this->renderIdList('Soft-deleted', $audit['soft_deleted']);
        $this->renderIdList('Extra en BD (no canónicos)', $audit['extra_in_database']);
        $this->renderStringList('JSON definidos pero ausentes', $audit['missing_json_files']);
        $this->renderStringList('JSON en disco sin catálogo', $audit['orphan_json_files']);

        $needsSync = $audit['missing'] !== []
            || $audit['outdated'] !== []
            || $audit['soft_deleted'] !== [];

        if (! $needsSync) {
            $this->newLine();
            $this->info('Tabla languages alineada con el catálogo del sistema.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->newLine();
            $this->warn('Modo dry-run: no se aplicaron cambios. Ejecute languages:sync sin --dry-run.');

            return self::FAILURE;
        }

        try {
            $result = $syncService->sync(! $this->option('no-update'));
        } catch (Throwable $exception) {
            $this->error('Error al sincronizar languages: ' . $exception->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->renderIdList('Creados', $result['created'], 'info');
        $this->renderIdList('Actualizados', $result['updated'], 'info');
        $this->renderIdList('Restaurados', $result['restored'], 'info');
        $this->renderIdList('Sin cambios', $result['unchanged']);

        $this->info('Sincronización de languages completada.');

        return self::SUCCESS;
    }

    /**
     * @param  list<int>  $ids
     */
    private function renderIdList(string $label, array $ids, string $style = 'comment'): void
    {
        if ($ids === []) {
            return;
        }

        $definitions = SupportedLanguages::definitions();
        $details = array_map(static function (int $id) use ($definitions): string {
            $definition = $definitions[$id] ?? null;
            if ($definition === null) {
                return (string) $id;
            }

            return sprintf('#%d %s (%s)', $id, $definition['name'], $definition['iso_639_1']);
        }, $ids);

        $this->{$style === 'info' ? 'info' : 'line'}("{$label}: " . implode(', ', $details));
    }

    /**
     * @param  list<string>  $items
     */
    private function renderStringList(string $label, array $items): void
    {
        if ($items === []) {
            return;
        }

        $this->warn("{$label}: " . implode(', ', $items));
    }
}

<?php

namespace App\Console\Commands;

use App\Services\CurrencyCatalogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SyncCurrencyFromLegacy extends Command
{
    protected $signature = 'currency:sync-legacy
                            {--force : Sobrescribir todos los campos desde currencybycountries}';

    protected $description = 'Sincroniza la tabla currency desde currencybycountries (inserta faltantes y completa campos vacíos)';

    public function handle(CurrencyCatalogService $catalog): int
    {
        if (! Schema::hasTable('currency') || ! Schema::hasTable('currencybycountries')) {
            $this->error('Faltan las tablas currency o currencybycountries.');

            return self::FAILURE;
        }

        $fillMissingOnly = ! (bool) $this->option('force');
        $mode = $fillMissingOnly ? 'completar campos vacíos' : 'sobrescribir desde legacy';

        $this->info("Sincronizando currency ← currencybycountries ({$mode})...");

        $stats = $catalog->syncFromLegacyTable($fillMissingOnly);

        $this->table(
            ['Acción', 'Cantidad'],
            [
                ['Insertadas', (string) $stats['inserted']],
                ['Actualizadas', (string) $stats['updated']],
                ['Omitidas', (string) $stats['skipped']],
            ]
        );

        $this->info('Sincronización finalizada.');

        return self::SUCCESS;
    }
}

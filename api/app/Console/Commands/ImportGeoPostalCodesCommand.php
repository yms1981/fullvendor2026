<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use ZipArchive;

/**
 * Importa códigos postales desde GeoNames (CC BY 4.0 — https://www.geonames.org/).
 *
 * Ejemplos:
 *   php artisan geo:import-postal --country=US
 *   php artisan geo:import-postal --all
 */
class ImportGeoPostalCodesCommand extends Command
{
    protected $signature = 'geo:import-postal
        {--country= : ISO2 del país (ej. US, MX, ES)}
        {--all : Importar allCountries.zip de GeoNames}
        {--truncate : Vaciar postal_codes antes de importar}
        {--link : Intentar enlazar country_id / state_id / city_id}';

    protected $description = 'Import GeoNames postal codes into postal_codes table';

    private const BASE_URL = 'https://download.geonames.org/export/zip/';

    public function handle(): int
    {
        $all = (bool) $this->option('all');
        $country = strtoupper(trim((string) $this->option('country')));

        if (!$all && $country === '') {
            $this->error('Indica --country=XX o --all');
            return self::FAILURE;
        }

        if ($this->option('truncate')) {
            DB::table('postal_codes')->truncate();
            $this->info('Tabla postal_codes vaciada.');
        }

        $dir = storage_path('app/world_geo');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $zipName = $all ? 'allCountries.zip' : $country . '.zip';
        $zipPath = $dir . '/' . $zipName;
        $txtName = $all ? 'allCountries.txt' : $country . '.txt';
        $txtPath = $dir . '/' . $txtName;

        if (!$this->download($zipName, $zipPath)) {
            return self::FAILURE;
        }

        if (!$this->unzip($zipPath, $dir, $txtName)) {
            return self::FAILURE;
        }

        $imported = $this->importFile($txtPath, (bool) $this->option('link'));
        $this->info("Importados {$imported} códigos postales.");

        return self::SUCCESS;
    }

    private function download(string $zipName, string $zipPath): bool
    {
        if (is_file($zipPath) && filesize($zipPath) > 1000) {
            $this->line("Usando ZIP cacheado: {$zipPath}");
            return true;
        }

        $url = self::BASE_URL . $zipName;
        $this->line("Descargando {$url} ...");

        try {
            $response = Http::timeout(300)->withOptions(['sink' => $zipPath])->get($url);
            if (!$response->successful() && !is_file($zipPath)) {
                $this->error('Descarga fallida HTTP ' . $response->status());
                return false;
            }
        } catch (\Throwable $e) {
            // sink puede dejar el archivo aunque Http lance; validamos tamaño
            if (!is_file($zipPath) || filesize($zipPath) < 1000) {
                // Fallback curl
                $cmd = 'curl -L --fail --retry 3 -o ' . escapeshellarg($zipPath) . ' ' . escapeshellarg($url);
                exec($cmd, $out, $code);
                if ($code !== 0 || !is_file($zipPath) || filesize($zipPath) < 1000) {
                    $this->error('No se pudo descargar: ' . $e->getMessage());
                    return false;
                }
            }
        }

        $this->info('Descarga OK (' . number_format(filesize($zipPath)) . ' bytes).');
        return true;
    }

    private function unzip(string $zipPath, string $dir, string $expectedTxt): bool
    {
        $txtPath = $dir . '/' . $expectedTxt;
        if (is_file($txtPath) && filesize($txtPath) > 1000) {
            return true;
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            $this->error('No se pudo abrir el ZIP');
            return false;
        }
        $zip->extractTo($dir);
        $zip->close();

        if (!is_file($txtPath)) {
            $this->error("No se encontró {$expectedTxt} tras descomprimir");
            return false;
        }

        return true;
    }

    private function importFile(string $txtPath, bool $link): int
    {
        $fh = fopen($txtPath, 'r');
        if ($fh === false) {
            $this->error('No se pudo leer ' . $txtPath);
            return 0;
        }

        $countryMap = [];
        if ($link) {
            foreach (DB::table('countries')->select('id', 'sortname')->get() as $c) {
                $countryMap[strtoupper((string) $c->sortname)] = (int) $c->id;
            }
        }

        $batch = [];
        $imported = 0;
        $now = now()->toDateTimeString();
        $bar = $this->output->createProgressBar();
        $bar->start();

        while (($line = fgets($fh)) !== false) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $cols = explode("\t", $line);
            if (count($cols) < 3) {
                continue;
            }

            $iso2 = strtoupper(substr((string) $cols[0], 0, 2));
            $postal = substr(trim((string) $cols[1]), 0, 20);
            $place = substr(trim((string) $cols[2]), 0, 180);
            if ($iso2 === '' || $postal === '' || $place === '') {
                continue;
            }

            $row = [
                'country_iso2' => $iso2,
                'postal_code' => $postal,
                'place_name' => $place,
                'admin_name1' => isset($cols[3]) ? substr(trim((string) $cols[3]), 0, 100) : null,
                'admin_code1' => isset($cols[4]) ? substr(trim((string) $cols[4]), 0, 20) : null,
                'admin_name2' => isset($cols[5]) ? substr(trim((string) $cols[5]), 0, 100) : null,
                'admin_code2' => isset($cols[6]) ? substr(trim((string) $cols[6]), 0, 20) : null,
                'admin_name3' => isset($cols[7]) ? substr(trim((string) $cols[7]), 0, 100) : null,
                'admin_code3' => isset($cols[8]) ? substr(trim((string) $cols[8]), 0, 20) : null,
                'latitude' => isset($cols[9]) && $cols[9] !== '' ? $cols[9] : null,
                'longitude' => isset($cols[10]) && $cols[10] !== '' ? $cols[10] : null,
                'accuracy' => isset($cols[11]) && $cols[11] !== '' ? (int) $cols[11] : null,
                'country_id' => $link ? ($countryMap[$iso2] ?? null) : null,
                'state_id' => null,
                'city_id' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $batch[] = $row;
            if (count($batch) >= 500) {
                DB::table('postal_codes')->insert($batch);
                $imported += count($batch);
                $bar->advance(count($batch));
                $batch = [];
            }
        }

        if ($batch !== []) {
            DB::table('postal_codes')->insert($batch);
            $imported += count($batch);
            $bar->advance(count($batch));
        }

        fclose($fh);
        $bar->finish();
        $this->newLine();

        if ($link) {
            $this->linkStatesAndCities();
        }

        return $imported;
    }

    private function linkStatesAndCities(): void
    {
        $this->line('Enlazando state_id / city_id por nombre...');

        // state_id: admin_name1 ≈ states.name + country_id
        DB::statement("
            UPDATE postal_codes pc
            INNER JOIN countries co ON co.id = pc.country_id
            INNER JOIN states s ON s.country_id = co.id
                AND UPPER(TRIM(s.name)) = UPPER(TRIM(pc.admin_name1))
            SET pc.state_id = s.id
            WHERE pc.admin_name1 IS NOT NULL AND pc.admin_name1 <> '' AND pc.state_id IS NULL
        ");

        // city_id: place_name ≈ cities.name + state_id
        DB::statement("
            UPDATE postal_codes pc
            INNER JOIN cities c ON c.state_id = pc.state_id
                AND UPPER(TRIM(c.name)) = UPPER(TRIM(pc.place_name))
            SET pc.city_id = c.id
            WHERE pc.state_id IS NOT NULL AND pc.city_id IS NULL
        ");

        $linked = DB::table('postal_codes')->whereNotNull('city_id')->count();
        $this->info("Filas con city_id enlazado: {$linked}");
    }
}

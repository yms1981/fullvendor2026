<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use OpenApi\Generator;
use Throwable;

/**
 * Actualiza Swagger UI (storage/api-docs/api-docs.json).
 * Intenta l5-swagger:generate; si falla, fusiona solo los módulos indicados.
 */
class OpenApiSyncCommand extends Command
{
    protected $signature = 'openapi:sync
                            {--scan=* : Carpetas bajo app/Http/Controllers a fusionar (ej. AppSalesman Erp)}
                            {--skip-full : No intentar l5-swagger:generate}';

    protected $description = 'Regenera/fusiona OpenAPI para que los endpoints nuevos aparezcan en /api/v2/documentation.';

    /**
     * Módulos con auth X-Api-Key (y similares) que siempre deben quedar en Swagger.
     *
     * @var list<string>
     */
    private array $defaultScans = [
        'Erp',
        'AppSalesman',
        'Mobile',
    ];

    public function handle(): int
    {
        $docsPath = storage_path('api-docs/api-docs.json');
        $scans = $this->resolveScanDirs();

        if (! $this->option('skip-full')) {
            $this->info('Intentando l5-swagger:generate…');
            try {
                $code = $this->call('l5-swagger:generate');
                if ($code === self::SUCCESS && is_readable($docsPath)) {
                    // Aun con generate OK, fusionar módulos clave por si el scan completo omitió algo.
                    $this->mergeScans($docsPath, $scans);
                    $this->info('Swagger actualizado (generate + merge). UI: /api/v2/documentation');

                    return self::SUCCESS;
                }
            } catch (Throwable $e) {
                $this->warn('l5-swagger:generate falló: '.$e->getMessage());
            }
            $this->warn('Usando merge parcial de módulos…');
        }

        if (! is_readable($docsPath)) {
            $this->error("No existe {$docsPath}. Cree un api-docs.json base o corrija l5-swagger:generate.");

            return self::FAILURE;
        }

        $this->mergeScans($docsPath, $scans);
        $this->info('Swagger actualizado (merge). UI: /api/v2/documentation');

        return self::SUCCESS;
    }

    /**
     * @return list<string> Absolute directories to scan
     */
    private function resolveScanDirs(): array
    {
        $requested = array_values(array_filter(array_map('strval', (array) $this->option('scan'))));
        $names = $requested !== [] ? $requested : $this->defaultScans;

        $dirs = [];
        foreach ($names as $name) {
            $name = trim($name, '/\\');
            if ($name === '') {
                continue;
            }
            $abs = app_path('Http/Controllers/'.$name);
            if (is_dir($abs) || is_file($abs)) {
                $dirs[] = $abs;
            } else {
                $this->warn("Scan omitido (no existe): {$abs}");
            }
        }

        // Siempre incluir Controller.php por SecuritySchemes globales.
        $dirs[] = app_path('Http/Controllers/Controller.php');

        return array_values(array_unique($dirs));
    }

    /**
     * @param  list<string>  $scanPaths
     */
    private function mergeScans(string $docsPath, array $scanPaths): void
    {
        $docs = json_decode((string) file_get_contents($docsPath), true);
        if (! is_array($docs)) {
            throw new \RuntimeException('api-docs.json inválido.');
        }

        $partial = Generator::scan($scanPaths);
        $add = json_decode($partial->toJson(), true) ?: [];

        foreach (($add['paths'] ?? []) as $routePath => $methods) {
            $docs['paths'][$routePath] = $methods;
            $this->line("  + {$routePath}");
        }

        $docs['components'] = $docs['components'] ?? [];
        $docs['components']['securitySchemes'] = $docs['components']['securitySchemes'] ?? [];
        foreach (($add['components']['securitySchemes'] ?? []) as $name => $scheme) {
            $docs['components']['securitySchemes'][$name] = $scheme;
        }

        $tags = $docs['tags'] ?? [];
        $names = array_column($tags, 'name');
        foreach (($add['tags'] ?? []) as $tag) {
            $tagName = $tag['name'] ?? '';
            if ($tagName === '') {
                continue;
            }
            $idx = array_search($tagName, $names, true);
            if ($idx === false) {
                $tags[] = $tag;
                $names[] = $tagName;
            } else {
                $tags[$idx] = array_merge($tags[$idx], $tag);
            }
        }
        $docs['tags'] = array_values($tags);

        file_put_contents(
            $docsPath,
            json_encode($docs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)."\n"
        );
    }
}

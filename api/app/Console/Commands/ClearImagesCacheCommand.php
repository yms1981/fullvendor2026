<?php

namespace App\Console\Commands;

use App\Models\Image;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Limpia / resetea la “caché” de metadatos de la tabla images
 * (productos, categorías, logos de compañía y avatares de usuario).
 */
class ClearImagesCacheCommand extends Command
{
    protected $signature = 'images:clear-cache
                            {--company= : Limitar a un company_id}
                            {--reset-disk : Actualiza images.disk al valor de IMAGES_DISK (por defecto sí)}
                            {--purge-orphans : Elimina filas images no referenciadas}
                            {--purge-all : Borra TODAS las imágenes y referencias (destructivo)}
                            {--delete-files : Al purgar, también borra archivos del storage activo}
                            {--dry-run : Solo muestra qué haría}';

    protected $description = 'Limpia metadatos de la tabla images (disk, huérfanos o wipe completo). Incluye logos y usuarios.';

    public function handle(): int
    {
        $companyId = $this->option('company') !== null && $this->option('company') !== ''
            ? (int) $this->option('company')
            : null;
        $dryRun = (bool) $this->option('dry-run');
        $purgeAll = (bool) $this->option('purge-all');
        $purgeOrphans = (bool) $this->option('purge-orphans');
        $deleteFiles = (bool) $this->option('delete-files');
        // Sin flags de purge: solo resetea disk. Con purge, también resetea si se pide --reset-disk.
        $resetDisk = ! $purgeAll && (! $purgeOrphans || (bool) $this->option('reset-disk'));
        if (! $purgeAll && ! $purgeOrphans) {
            $resetDisk = true;
        }
        if ((bool) $this->option('reset-disk')) {
            $resetDisk = true;
        }

        $targetDisk = Image::defaultDisk();
        $this->info('Arquitectura activa (IMAGES_DISK): '.$targetDisk);
        if ($companyId) {
            $this->line('Filtro company_id='.$companyId);
        }
        if ($dryRun) {
            $this->warn('Modo dry-run: no se escriben cambios.');
        }

        if ($purgeAll) {
            return $this->purgeAll($companyId, $deleteFiles, $dryRun);
        }

        $exit = self::SUCCESS;

        if ($resetDisk) {
            $exit = max($exit, $this->resetDiskColumn($companyId, $targetDisk, $dryRun));
        }

        if ($purgeOrphans) {
            $exit = max($exit, $this->purgeOrphans($companyId, $deleteFiles, $dryRun));
        }

        return $exit;
    }

    private function resetDiskColumn(?int $companyId, string $targetDisk, bool $dryRun): int
    {
        $query = DB::table('images');
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        $total = (clone $query)->count();
        $toUpdate = (clone $query)->where(function ($q) use ($targetDisk) {
            $q->whereNull('disk')->orWhere('disk', '!=', $targetDisk);
        })->count();

        $this->line("Reset disk → '{$targetDisk}': {$toUpdate} de {$total} filas");

        if ($dryRun || $toUpdate < 1) {
            return self::SUCCESS;
        }

        $updated = $query->where(function ($q) use ($targetDisk) {
            $q->whereNull('disk')->orWhere('disk', '!=', $targetDisk);
        })->update([
            'disk' => $targetDisk,
            'updated_at' => now(),
        ]);

        $this->info("Filas actualizadas: {$updated}");

        return self::SUCCESS;
    }

    private function purgeOrphans(?int $companyId, bool $deleteFiles, bool $dryRun): int
    {
        $referenced = $this->referencedUuids($companyId);
        $query = Image::query();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        if ($referenced !== []) {
            $query->whereNotIn('uuid', $referenced);
        }

        $orphans = $query->get();
        $this->line('Huérfanas a eliminar: '.$orphans->count());

        if ($dryRun || $orphans->isEmpty()) {
            return self::SUCCESS;
        }

        $deleted = 0;
        foreach ($orphans as $image) {
            try {
                if ($deleteFiles) {
                    $image->deleteFromStorage();
                }
                $image->delete();
                $deleted++;
            } catch (Throwable $e) {
                $this->error('No se pudo eliminar '.$image->uuid.': '.$e->getMessage());
            }
        }

        $this->info("Huérfanas eliminadas: {$deleted}");

        return self::SUCCESS;
    }

    private function purgeAll(?int $companyId, bool $deleteFiles, bool $dryRun): int
    {
        if (! $dryRun && ! $this->confirm('Esto borrará imágenes y referencias (productos, categorías, logos, usuarios). ¿Continuar?')) {
            $this->warn('Cancelado.');

            return self::FAILURE;
        }

        $imagesQuery = Image::query();
        if ($companyId) {
            $imagesQuery->where('company_id', $companyId);
        }
        $count = (clone $imagesQuery)->count();
        $this->warn("purge-all: {$count} filas en images");

        if ($dryRun) {
            return self::SUCCESS;
        }

        DB::transaction(function () use ($companyId, $deleteFiles, $imagesQuery) {
            // Product images
            if (Schema::hasTable('product_images')) {
                $pi = DB::table('product_images');
                if ($companyId && Schema::hasColumn('products', 'company_id')) {
                    $productIds = DB::table('products')->where('company_id', $companyId)->pluck('pro_id');
                    $pi->whereIn('product_id', $productIds);
                }
                $pi->delete();
            }

            if (Schema::hasColumn('products', 'default_image_id')) {
                $pq = DB::table('products')->whereNotNull('default_image_id');
                if ($companyId) {
                    $pq->where('company_id', $companyId);
                }
                $pq->update(['default_image_id' => null]);
            }

            if (Schema::hasColumn('categories', 'image')) {
                $cq = DB::table('categories')->whereNotNull('image');
                if ($companyId) {
                    $cq->where('company_id', $companyId);
                }
                $cq->update(['image' => null]);
            }

            if (Schema::hasColumn('companies', 'image')) {
                $coq = DB::table('companies')->whereNotNull('image');
                if ($companyId) {
                    $coq->where('id', $companyId);
                }
                $coq->update(['image' => null]);
            }

            if (Schema::hasColumn('users', 'image')) {
                $uq = DB::table('users')->whereNotNull('image');
                if ($companyId && Schema::hasColumn('users', 'company_id')) {
                    $uq->where('company_id', $companyId);
                }
                // Solo limpiar valores tipo uuid.ext (no paths legacy /uploads/...)
                $uq->where('image', 'REGEXP', '^[0-9a-fA-F-]{36}\\.')
                    ->update(['image' => null]);
            }

            $images = $imagesQuery->get();
            foreach ($images as $image) {
                if ($deleteFiles) {
                    try {
                        $image->deleteFromStorage();
                    } catch (Throwable $e) {
                        // continuar
                    }
                }
                $image->delete();
            }
        });

        $this->info('purge-all completado.');

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function referencedUuids(?int $companyId): array
    {
        $uuids = [];

        if (Schema::hasTable('product_images') && Schema::hasColumn('product_images', 'image_id')) {
            $q = DB::table('product_images')->whereNotNull('image_id');
            if ($companyId) {
                $productIds = DB::table('products')->where('company_id', $companyId)->pluck('pro_id');
                $q->whereIn('product_id', $productIds);
            }
            $uuids = array_merge($uuids, $q->pluck('image_id')->all());
        }

        if (Schema::hasColumn('products', 'default_image_id')) {
            $q = DB::table('products')->whereNotNull('default_image_id');
            if ($companyId) {
                $q->where('company_id', $companyId);
            }
            $uuids = array_merge($uuids, $q->pluck('default_image_id')->all());
        }

        if (Schema::hasColumn('categories', 'image')) {
            $q = DB::table('categories')->whereNotNull('image');
            if ($companyId) {
                $q->where('company_id', $companyId);
            }
            foreach ($q->pluck('image') as $val) {
                $uuids[] = $this->uuidFromImageRef((string) $val);
            }
        }

        if (Schema::hasColumn('companies', 'image')) {
            $q = DB::table('companies')->whereNotNull('image');
            if ($companyId) {
                $q->where('id', $companyId);
            }
            foreach ($q->pluck('image') as $val) {
                $uuids[] = $this->uuidFromImageRef((string) $val);
            }
        }

        if (Schema::hasColumn('users', 'image')) {
            $q = DB::table('users')->whereNotNull('image');
            if ($companyId && Schema::hasColumn('users', 'company_id')) {
                $q->where('company_id', $companyId);
            }
            foreach ($q->pluck('image') as $val) {
                $uuids[] = $this->uuidFromImageRef((string) $val);
            }
        }

        return array_values(array_unique(array_filter($uuids)));
    }

    private function uuidFromImageRef(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (preg_match('/^([0-9a-fA-F-]{36})(?:\\.[a-z0-9]+)?$/', $value, $m)) {
            return strtolower($m[1]);
        }

        return null;
    }
}

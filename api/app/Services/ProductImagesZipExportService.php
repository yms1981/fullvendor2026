<?php

namespace App\Services;

use App\Models\Image;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Empaqueta imágenes de productos en un ZIP descargable.
 * Nombres alineados con la importación: sku.ext, sku_pic2.ext, sku_pic3.ext…
 */
class ProductImagesZipExportService
{
    public const MAX_PRODUCTS = 5000;

    /**
     * @param  Collection<int, Product>  $products
     * @return array{path: string, filename: string, images: int, products: int, skipped: int}
     */
    public function buildZip(Collection $products): array
    {
        if ($products->count() > self::MAX_PRODUCTS) {
            throw new \InvalidArgumentException('product_images_export_too_many');
        }

        if (! class_exists(ZipArchive::class)) {
            throw new \RuntimeException('zip_extension_missing');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'fv_prod_img_');
        if ($tmp === false) {
            throw new \RuntimeException('temp_file_failed');
        }
        $zipPath = $tmp.'.zip';
        @unlink($tmp);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('zip_open_failed');
        }

        $usedNames = [];
        $imagesAdded = 0;
        $productsWithImages = 0;
        $skipped = 0;

        foreach ($products as $product) {
            $skuKey = $this->safeSku((string) ($product->sku ?? ''), (int) $product->pro_id);
            $rows = $product->relationLoaded('product_images')
                ? $product->product_images
                : collect();

            $ordered = $rows
                ->sortBy(static function ($row) {
                    $order = $row->img_order;
                    if ($order === null || $order === '') {
                        return 999999;
                    }

                    return (int) $order;
                })
                ->values();

            $indexInProduct = 0;
            $addedForProduct = 0;

            foreach ($ordered as $productImage) {
                /** @var Image|null $image */
                $image = $productImage->relationLoaded('image')
                    ? $productImage->image
                    : null;
                if (! $image instanceof Image) {
                    $skipped++;
                    continue;
                }

                $bytes = $image->getRawContent();
                if (! is_string($bytes) || $bytes === '') {
                    $skipped++;
                    continue;
                }

                $indexInProduct++;
                $ext = $this->resolveExtension($image, $bytes);
                $baseName = $indexInProduct === 1
                    ? $skuKey
                    : $skuKey.'_pic'.$indexInProduct;
                $entryName = $this->uniqueEntryName($baseName.'.'.$ext, $usedNames);

                if ($zip->addFromString($entryName, $bytes) === false) {
                    $skipped++;
                    continue;
                }

                $imagesAdded++;
                $addedForProduct++;
            }

            if ($addedForProduct > 0) {
                $productsWithImages++;
            }
        }

        $zip->close();

        if ($imagesAdded < 1) {
            @unlink($zipPath);
            throw new \RuntimeException('product_images_export_empty');
        }

        $filename = 'product_images_'.date('Ymd_His').'.zip';

        return [
            'path' => $zipPath,
            'filename' => $filename,
            'images' => $imagesAdded,
            'products' => $productsWithImages,
            'skipped' => $skipped,
        ];
    }

    private function safeSku(string $sku, int $proId): string
    {
        $sku = trim($sku);
        if ($sku === '') {
            $sku = 'product_'.$proId;
        }

        // Importación no admite "/" en el nombre de archivo.
        $sku = str_replace(['/', '\\'], '-', $sku);
        $sku = preg_replace('/[<>:"|?*\x00-\x1F]/', '_', $sku) ?? $sku;
        $sku = trim($sku, " .");
        if ($sku === '') {
            $sku = 'product_'.$proId;
        }

        return Str::limit($sku, 120, '');
    }

    private function resolveExtension(Image $image, string $bytes): string
    {
        $ext = strtolower(trim((string) ($image->extension ?? '')));
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }
        if ($ext !== '' && preg_match('/^[a-z0-9]{1,8}$/', $ext)) {
            return $ext;
        }

        $fromName = strtolower(pathinfo((string) ($image->name ?? ''), PATHINFO_EXTENSION));
        if ($fromName === 'jpeg') {
            $fromName = 'jpg';
        }
        if ($fromName !== '' && preg_match('/^[a-z0-9]{1,8}$/', $fromName)) {
            return $fromName;
        }

        if (class_exists(\finfo::class)) {
            try {
                $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
                $map = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    'image/gif' => 'gif',
                    'image/bmp' => 'bmp',
                    'image/avif' => 'avif',
                    'image/tiff' => 'tiff',
                    'image/svg+xml' => 'svg',
                ];
                if (is_string($mime) && isset($map[$mime])) {
                    return $map[$mime];
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return 'jpg';
    }

    /**
     * @param  array<string, true>  $usedNames
     */
    private function uniqueEntryName(string $name, array &$usedNames): string
    {
        if (! isset($usedNames[$name])) {
            $usedNames[$name] = true;

            return $name;
        }

        $pathInfo = pathinfo($name);
        $base = (string) ($pathInfo['filename'] ?? 'image');
        $ext = isset($pathInfo['extension']) ? '.'.$pathInfo['extension'] : '';
        $i = 2;
        do {
            $candidate = $base.'_'.$i.$ext;
            $i++;
        } while (isset($usedNames[$candidate]));

        $usedNames[$candidate] = true;

        return $candidate;
    }
}

<?php

namespace App\Services\Erp;

use App\Models\Image;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Subida ERP de fotos de producto respetando las políticas de FullVendor:
 *
 * - Disco: IMAGES_DISK (minio/s3 o images_local) vía Image::putContent().
 * - Normalización: JPEG compacto, max ~1200×1200 (config/images.php), type=product.
 * - Path físico:
 *   - MinIO/S3 → {company_id}/{name}
 *   - Local    → products/{company_id}/{name}
 * - Nombre (convención ProductImageController):
 *   - slot 1 → {SKU}.{ext}   (principal / default)
 *   - slot N → {SKU}_PIC{N}.{ext}
 * - Máx. 5 imágenes; img_order 1..5.
 * - default_image_id = UUID del slot 1.
 * - products.imgURL / simgURL → URL pública /api/v2/images/{uuid}
 */
class ErpProductImageUploadService
{
    public const MAX_IMAGES = 5;

    /**
     * @param  list<UploadedFile>  $files
     * @return array<string, mixed>
     */
    public function upload(Product $product, array $files, ?int $defaultIndex = null, bool $replace = false): array
    {
        $files = array_values(array_filter(
            $files,
            static fn ($file) => $file instanceof UploadedFile && $file->isValid()
        ));

        if ($files === []) {
            throw new \InvalidArgumentException('At least one valid image file is required (images[] / image).');
        }

        $companyId = (int) $product->company_id;
        $productId = (int) $product->pro_id;
        $sku = $this->normalizedSku($product);
        $requestedDefault = $defaultIndex;

        // La foto marcada como default debe guardarse como slot 1.
        $promoteDefaultToSlotOne = false;
        if ($defaultIndex !== null && isset($files[$defaultIndex])) {
            if ($defaultIndex !== 0) {
                $chosen = $files[$defaultIndex];
                unset($files[$defaultIndex]);
                $files = array_values(array_merge([$chosen], $files));
            }
            $promoteDefaultToSlotOne = true;
            $defaultIndex = 0;
        }

        return DB::transaction(function () use (
            $product,
            $files,
            $requestedDefault,
            $replace,
            $promoteDefaultToSlotOne,
            $companyId,
            $productId,
            $sku
        ) {
            if ($replace) {
                ProductImage::query()->where('product_id', $productId)->get()->each(
                    static function (ProductImage $row): void {
                        $row->delete();
                    }
                );
                $product->default_image_id = null;
                $product->imgURL = null;
                $product->simgURL = null;
                $product->save();
            }

            $hadDefault = trim((string) ($product->fresh()->default_image_id ?? '')) !== '';
            $currentCount = ProductImage::query()->where('product_id', $productId)->count();
            $slotsLeft = max(0, self::MAX_IMAGES - $currentCount);

            // Si vamos a ocupar/reemplazar el slot 1 con una nueva principal, no cuenta como slot extra.
            $needsSlotOneReplace = $promoteDefaultToSlotOne && $currentCount > 0 && ! $replace;
            if ($needsSlotOneReplace) {
                $slotsLeft = max(0, self::MAX_IMAGES - max(0, $currentCount - 1));
            }

            if ($slotsLeft === 0) {
                throw new \InvalidArgumentException(
                    'Product already has '.self::MAX_IMAGES.' images. Use replace=1 to replace the gallery.'
                );
            }

            $toUpload = array_slice($files, 0, $slotsLeft);
            $skipped = count($files) - count($toUpload);
            $maxOrder = (int) (ProductImage::query()->where('product_id', $productId)->max('img_order') ?? 0);

            $defaultRule = 'keep_existing';
            $setDefault = false;
            if ($replace || ! $hadDefault) {
                $setDefault = true;
                $defaultRule = $replace ? 'replace_slot_1' : 'first_when_empty_slot_1';
            }
            if ($promoteDefaultToSlotOne) {
                $setDefault = true;
                $defaultRule = 'default_index_as_slot_1';
            }

            $uploaded = [];
            $defaultUuid = null;
            $appendOrder = $maxOrder;

            foreach ($toUpload as $index => $file) {
                $isPrincipalUpload = $index === 0 && ($replace || ! $hadDefault || $promoteDefaultToSlotOne);

                if ($isPrincipalUpload) {
                    $slot = 1;
                    $image = $this->storeImageFile($file, $companyId, $this->expectedBaseName($sku, 1, $file));
                    $this->placeImageAtSlot($productId, $image, 1);
                    $defaultUuid = (string) $image->uuid;
                } else {
                    $appendOrder = max($appendOrder + 1, 2);
                    // Evitar chocar con slot 1 si acabamos de escribirlo.
                    while (
                        $appendOrder === 1
                        || ProductImage::query()
                            ->where('product_id', $productId)
                            ->where('img_order', $appendOrder)
                            ->exists()
                    ) {
                        $appendOrder++;
                    }
                    if ($appendOrder > self::MAX_IMAGES) {
                        $skipped++;

                        continue;
                    }
                    $slot = $appendOrder;
                    $image = $this->storeImageFile($file, $companyId, $this->expectedBaseName($sku, $slot, $file));
                    $this->placeImageAtSlot($productId, $image, $slot);
                }

                $uploaded[] = [
                    'uuid' => (string) $image->uuid,
                    'name' => (string) $image->name,
                    'path' => $image->path !== null ? (string) $image->path : null,
                    'disk' => $image->disk !== null ? (string) $image->disk : null,
                    'img_order' => $slot,
                    'is_default' => false,
                    'url' => $this->publicImageUrl((string) $image->uuid),
                ];
            }

            if ($uploaded === []) {
                throw new \InvalidArgumentException('No images could be stored.');
            }

            $this->recalculateImageNamesForProduct($productId, $sku);

            if ($setDefault) {
                if ($defaultUuid === null) {
                    $slotOne = ProductImage::query()
                        ->where('product_id', $productId)
                        ->orderBy('img_order')
                        ->orderBy('img_id')
                        ->first();
                    $defaultUuid = $slotOne?->image_id ? (string) $slotOne->image_id : $uploaded[0]['uuid'];
                }
                $product->default_image_id = $defaultUuid;
                $product->imgURL = $this->publicImageUrl($defaultUuid);
                $product->simgURL = $product->imgURL;
                $product->save();
            } elseif ($hadDefault && trim((string) ($product->imgURL ?? '')) === '') {
                $product->imgURL = $this->publicImageUrl((string) $product->default_image_id);
                $product->simgURL = $product->imgURL;
                $product->save();
            }

            $finalDefault = (string) ($product->fresh()->default_image_id ?? '');
            $meta = Image::query()
                ->whereIn('uuid', array_column($uploaded, 'uuid'))
                ->get(['uuid', 'name', 'path', 'disk'])
                ->keyBy('uuid');

            foreach ($uploaded as $i => $row) {
                $img = $meta->get($row['uuid']);
                if ($img) {
                    $uploaded[$i]['name'] = (string) $img->name;
                    $uploaded[$i]['path'] = $img->path !== null ? (string) $img->path : null;
                    $uploaded[$i]['disk'] = $img->disk !== null ? (string) $img->disk : null;
                }
                $uploaded[$i]['is_default'] = ((string) $row['uuid'] === $finalDefault);
            }

            $product->refresh();

            return [
                'product_id' => $productId,
                'sku' => (string) ($product->sku ?? ''),
                'default_image_id' => $finalDefault !== '' ? $finalDefault : null,
                'default_index' => $requestedDefault,
                'default_rule' => $defaultRule,
                'imgURL' => $product->imgURL,
                'simgURL' => $product->simgURL,
                'uploaded' => $uploaded,
                'skipped' => $skipped,
                'replaced' => $replace,
                'storage_policy' => [
                    'disk' => Image::defaultDisk(),
                    'type' => 'product',
                    'max_images' => self::MAX_IMAGES,
                    'naming' => '{SKU}.jpg / {SKU}_PIC{N}.jpg',
                    'slot_1_is_default' => true,
                    'normalized' => true,
                    'path_layout' => Image::usesDirectoryLayout()
                        ? 'products/{company_id}/{name}'
                        : '{company_id}/{name}',
                ],
            ];
        });
    }

    /**
     * Coloca (o reemplaza) la imagen en un slot exacto — misma idea que ProductImageController.
     */
    private function placeImageAtSlot(int $productId, Image $image, int $slot): void
    {
        $slot = max(1, min(self::MAX_IMAGES, $slot));

        // Evitar el mismo uuid en otro slot.
        ProductImage::query()
            ->where('product_id', $productId)
            ->where('image_id', $image->uuid)
            ->where('img_order', '!=', $slot)
            ->delete();

        $existing = ProductImage::query()
            ->where('product_id', $productId)
            ->where('img_order', $slot)
            ->orderBy('img_id')
            ->first();

        if ($existing) {
            $existing->image_id = $image->uuid;
            $existing->save();

            return;
        }

        $link = new ProductImage;
        $link->product_id = $productId;
        $link->image_id = $image->uuid;
        $link->img_order = $slot;
        $link->save();
    }

    private function storeImageFile(UploadedFile $file, int $companyId, string $baseName): Image
    {
        $originalName = (string) $file->getClientOriginalName();
        $origExt = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));
        if ($origExt === 'jpeg') {
            $origExt = 'jpg';
        }
        if ($origExt === '' || ! in_array($origExt, Image::ACCEPTED_EXTENSIONS, true)) {
            $origExt = Image::storageExtension();
        }

        $safeBase = Image::sanitizeStorageBaseName($baseName);
        $image = new Image;
        $image->company_id = $companyId;
        $image->name = $safeBase.'.'.$origExt;
        $image->disk = Image::defaultDisk();
        $image->extension = $origExt;
        $image->mime_type = (string) ($file->getMimeType() ?: Image::STORAGE_MIME);
        $image->type = $image->mime_type;
        $image->original_name = $originalName !== '' ? $originalName : ($safeBase.'.'.$origExt);

        try {
            // putContent: IMAGES_DISK + normalización JPEG + path products/{company}/{name} o {company}/{name}
            return $image->putContent($file->getContent(), $companyId, 'product');
        } catch (Throwable $e) {
            Log::warning('ErpProductImageUploadService: putContent failed', [
                'company_id' => $companyId,
                'name' => $image->name,
                'error' => $e->getMessage(),
            ]);
            throw new \InvalidArgumentException('Failed to store image: '.$e->getMessage(), 0, $e);
        }
    }

    private function normalizedSku(Product $product): string
    {
        $sku = strtoupper(trim((string) ($product->sku ?? '')));

        return str_replace('/', '', $sku);
    }

    private function expectedBaseName(string $sku, int $slot, UploadedFile $file): string
    {
        $slot = max(1, $slot);
        if ($sku !== '') {
            return $slot === 1 ? $sku : ($sku.'_PIC'.$slot);
        }

        $fromFile = pathinfo((string) $file->getClientOriginalName(), PATHINFO_FILENAME);
        if (is_string($fromFile) && $fromFile !== '' && $fromFile !== '.') {
            return $fromFile;
        }

        return (string) Str::uuid();
    }

    private function recalculateImageNamesForProduct(int $productId, string $sku): void
    {
        if ($sku === '') {
            return;
        }

        $rows = ProductImage::query()
            ->with('image')
            ->where('product_id', $productId)
            ->orderBy('img_order')
            ->orderBy('img_id')
            ->get();

        foreach ($rows as $pi) {
            $img = $pi->image;
            if (! $img) {
                continue;
            }
            $slot = max(1, (int) ($pi->img_order ?? 1));
            $ext = trim((string) ($img->extension ?? ''));
            if ($ext === '') {
                $ext = pathinfo((string) ($img->name ?? ''), PATHINFO_EXTENSION) ?: Image::storageExtension();
            }
            $ext = ltrim(strtolower($ext), '.');
            if ($ext === 'jpeg') {
                $ext = 'jpg';
            }
            $expected = ($slot === 1 ? $sku : ($sku.'_PIC'.$slot)).'.'.$ext;
            if ((string) $img->name !== $expected) {
                $img->name = $expected;
                $img->save();
            }
        }
    }

    private function publicImageUrl(string $uuid): string
    {
        return url('/api/v2/images/'.$uuid);
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use stdClass;

final class CatalogPublicCartLinesService
{
    private function publicWebBaseUrl(): string
    {
        return rtrim((string) config('fullvendor.web_url', config('app.url', 'http://localhost')), '/');
    }

    private function catalogPublicImageUrl(string $imageRef, string $token): string
    {
        return $this->publicWebBaseUrl()
            . '/api/v2/images/catalog/'
            . $imageRef
            . '?token='
            . rawurlencode($token);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function linesForCart(stdClass $cart, string $token): array
    {
        if (! Schema::hasTable('detail_car') || ! Schema::hasTable('products')) {
            return [];
        }

        $tokenCar = $token !== '' ? $token : (string) ($cart->token_kor ?? '');
        if ($tokenCar === '') {
            return [];
        }

        $rows = DB::table('detail_car as d')
            ->join('products as p', 'p.pro_id', '=', 'd.product_id')
            ->where('d.token_car', $tokenCar)
            ->orderBy('d.detail_id')
            ->select([
                'p.pro_id',
                'p.name',
                'p.stock',
                'p.product_id',
                'p.sale_price',
                'p.price_reduced',
                'p.sku',
                'p.minimum_stock',
                'p.notify_minimum_stock',
                'd.comment',
                'd.sale_price as detail_sale_price',
                'd.qty',
            ])
            ->selectRaw('(d.qty * d.sale_price) as importe')
            ->get();

        $lines = [];
        foreach ($rows as $row) {
            $qty = (float) ($row->qty ?? 0);
            $detailPrice = (float) ($row->detail_sale_price ?? 0);
            $importe = (float) ($row->importe ?? ($qty * $detailPrice));
            $imageRef = $this->firstImageRef((int) $row->pro_id);

            $lines[] = [
                'pro_id' => (int) $row->pro_id,
                'product_id' => (int) ($row->product_id ?? $row->pro_id),
                'name' => (string) ($row->name ?? ''),
                'stock' => $row->stock,
                'sku' => (string) ($row->sku ?? ''),
                'qty' => $qty,
                'sale_price' => (float) ($row->sale_price ?? 0),
                'detail_sale_price' => $detailPrice,
                'price_reduced' => (float) ($row->price_reduced ?? 0),
                'minimum_stock' => (float) ($row->minimum_stock ?? 0),
                'notify_minimum_stock' => (int) ($row->notify_minimum_stock ?? 0),
                'comment' => (string) ($row->comment ?? ''),
                'importe' => $importe,
                'images' => $imageRef,
                'imagen_js' => $imageRef,
                'image_url' => $imageRef !== null && $imageRef !== ''
                    ? $this->catalogPublicImageUrl($imageRef, $tokenCar)
                    : null,
            ];
        }

        return $lines;
    }

    private function firstImageRef(int $productId): ?string
    {
        if ($productId < 1 || ! Schema::hasTable('products')) {
            return null;
        }

        if (Schema::hasTable('images')) {
            $defaultUuid = DB::table('products')
                ->where('pro_id', $productId)
                ->value('default_image_id');
            if (is_string($defaultUuid) && $defaultUuid !== '') {
                $fromDefault = $this->imageRefFromUuid($defaultUuid);
                if ($fromDefault !== null) {
                    return $fromDefault;
                }
            }
        }

        if (! Schema::hasTable('product_images')) {
            return null;
        }

        $query = DB::table('product_images as pi')
            ->where('pi.product_id', $productId)
            ->orderByRaw('COALESCE(pi.img_order, 999999) ASC')
            ->orderBy('pi.img_id');

        if (Schema::hasTable('images')) {
            $row = (clone $query)
                ->leftJoin('images as i', DB::raw('CAST(pi.image_id AS CHAR)'), '=', DB::raw('CAST(i.uuid AS CHAR)'))
                ->select(['i.uuid', 'i.extension'])
                ->first();

            if ($row && ! empty($row->uuid)) {
                return $this->formatImageRef((string) $row->uuid, (string) ($row->extension ?? ''));
            }
        }

        $imageId = $query->value('pi.image_id');
        if (is_string($imageId) && trim($imageId) !== '') {
            return $this->imageRefFromUuid(trim($imageId));
        }

        return null;
    }

    private function imageRefFromUuid(string $uuid): ?string
    {
        if ($uuid === '' || ! Schema::hasTable('images')) {
            return null;
        }

        $row = DB::table('images')->where('uuid', $uuid)->first(['extension']);
        if (! $row) {
            return null;
        }

        return $this->formatImageRef($uuid, (string) ($row->extension ?? ''));
    }

    private function formatImageRef(string $uuid, string $extension): string
    {
        $ext = strtolower(ltrim($extension, '.'));
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }
        if (! in_array($ext, ['jpg', 'png', 'gif', 'webp'], true)) {
            $ext = 'png';
        }

        return $uuid . '.' . $ext;
    }
}

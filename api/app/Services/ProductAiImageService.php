<?php

namespace App\Services;

use App\Models\Image;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Busca (Openverse) o genera (DALL·E) una imagen según el nombre corto del producto
 * y la asigna como foto principal cuando el producto no tiene imagen.
 */
class ProductAiImageService
{
    public function __construct(
        private readonly OpenAiChatService $chat
    ) {
    }

    public function isEnabled(): bool
    {
        if (! filter_var(config('ai.product_ai_image.enabled', true), FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        return $this->chat->isConfigured();
    }

    public function maxPerRequest(): int
    {
        return max(1, (int) config('ai.product_ai_image.max_per_request', 3));
    }

    /**
     * @param  list<int>  $productIds
     * @return array{assigned: list<array{pro_id:int,uuid:string,source:string,short_name:string}>, skipped: list<array{pro_id:int,reason:string}>, failed: list<array{pro_id:int,reason:string}>}
     */
    public function assignForProducts(int $companyId, array $productIds): array
    {
        $assigned = [];
        $skipped = [];
        $failed = [];
        $limit = $this->maxPerRequest();
        $ids = array_values(array_unique(array_filter(array_map('intval', $productIds))));
        $ids = array_slice($ids, 0, $limit);

        if ($ids === []) {
            return compact('assigned', 'skipped', 'failed');
        }

        $products = Product::query()
            ->where('company_id', $companyId)
            ->whereIn('pro_id', $ids)
            ->where('status', 1)
            ->with(['product_images', 'default_image'])
            ->get();

        foreach ($products as $product) {
            $proId = (int) $product->pro_id;
            if ($this->productHasImage($product)) {
                $skipped[] = ['pro_id' => $proId, 'reason' => 'already_has_image'];
                continue;
            }

            try {
                $result = $this->assignToProduct($product);
                $assigned[] = [
                    'pro_id' => $proId,
                    'uuid' => $result['uuid'],
                    'source' => $result['source'],
                    'short_name' => $result['short_name'],
                ];
            } catch (\Throwable $e) {
                Log::warning('Product AI image assign failed', [
                    'pro_id' => $proId,
                    'error' => $e->getMessage(),
                ]);
                $failed[] = ['pro_id' => $proId, 'reason' => $e->getMessage()];
            }
        }

        foreach ($ids as $id) {
            if ($products->firstWhere('pro_id', $id)) {
                continue;
            }
            $skipped[] = ['pro_id' => (int) $id, 'reason' => 'not_found_or_disabled'];
        }

        return compact('assigned', 'skipped', 'failed');
    }

    /**
     * @return array{uuid: string, source: string, short_name: string}
     */
    public function assignToProduct(Product $product): array
    {
        if (! $this->isEnabled()) {
            throw new RuntimeException('ai_image_not_configured');
        }

        $meta = $this->resolveSearchMeta((string) ($product->name ?? ''), (string) ($product->sku ?? ''));
        $shortName = $meta['short_name'];
        $query = $meta['search_query'];

        $binary = null;
        $source = '';
        $extension = 'jpg';

        if (filter_var(config('ai.product_ai_image.openverse_enabled', true), FILTER_VALIDATE_BOOLEAN)) {
            $found = $this->searchOpenverseBinary($query);
            if ($found !== null) {
                $binary = $found['bytes'];
                $extension = $found['extension'];
                $source = 'openverse';
            }
        }

        if ($binary === null) {
            $generated = $this->generateOpenAiImageBinary($shortName !== '' ? $shortName : $query);
            $binary = $generated['bytes'];
            $extension = $generated['extension'];
            $source = 'openai';
        }

        $companyId = (int) $product->company_id;
        $baseName = 'ai_' . $product->pro_id . '_' . Str::slug(mb_substr($shortName !== '' ? $shortName : 'product', 0, 40));
        if ($baseName === '' || $baseName === 'ai_' . $product->pro_id . '_') {
            $baseName = 'ai_' . $product->pro_id;
        }

        $image = new Image();
        $image->company_id = $companyId;
        $image->name = $baseName . '.' . $extension;
        $image->disk = Image::defaultDisk();
        $image->extension = $extension;
        $image->mime_type = $extension === 'png' ? 'image/png' : 'image/jpeg';
        $image->type = $image->mime_type;
        $image->original_name = $baseName . '.' . $extension;
        $image = $image->putContent($binary, $companyId, 'product');

        $productImage = new ProductImage();
        $productImage->product_id = (int) $product->pro_id;
        $productImage->image_id = $image->uuid;
        $productImage->company_id = $companyId;
        $productImage->img_order = 1;
        $productImage->save();

        $product->default_image_id = $image->uuid;
        $product->image_updated = 1;
        $product->save();

        return [
            'uuid' => (string) $image->uuid,
            'source' => $source,
            'short_name' => $shortName,
        ];
    }

    public function productHasImage(Product $product): bool
    {
        if (! empty($product->default_image_id)) {
            return true;
        }
        if ($product->relationLoaded('default_image') && $product->default_image) {
            return true;
        }
        if ($product->relationLoaded('product_images') && $product->product_images->isNotEmpty()) {
            return true;
        }

        return ProductImage::query()->where('product_id', $product->pro_id)->exists();
    }

    /**
     * @return array{short_name: string, search_query: string}
     */
    private function resolveSearchMeta(string $name, string $sku): array
    {
        $fallbackName = trim($name) !== '' ? trim($name) : trim($sku);
        $fallbackQuery = $this->fallbackQuery($fallbackName);

        try {
            $raw = $this->chat->chat([
                [
                    'role' => 'user',
                    'content' => 'Product full name: ' . mb_substr($fallbackName, 0, 200)
                        . "\nSKU: " . mb_substr($sku, 0, 80)
                        . "\nReturn ONLY valid JSON with keys short_name and search_query."
                        . ' short_name = concise product name (max 8 words) for photo matching.'
                        . ' search_query = English keywords (2-6 words) to find an exact or very similar product photo.'
                        . ' No markdown.',
                ],
            ], ['lang' => 'en', 'screen' => 'products/ai-image']);

            $json = $this->extractJsonObject($raw);
            $short = trim((string) ($json['short_name'] ?? ''));
            $query = trim((string) ($json['search_query'] ?? ''));
            if ($short === '') {
                $short = $this->fallbackShortName($fallbackName);
            }
            if ($query === '') {
                $query = $this->fallbackQuery($short);
            }

            return [
                'short_name' => mb_substr($short, 0, 120),
                'search_query' => mb_substr($query, 0, 120),
            ];
        } catch (\Throwable $e) {
            Log::info('AI short name fallback', ['error' => $e->getMessage()]);

            return [
                'short_name' => $this->fallbackShortName($fallbackName),
                'search_query' => $fallbackQuery,
            ];
        }
    }

    private function fallbackShortName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];
        $parts = array_slice(array_values(array_filter($parts)), 0, 6);

        return implode(' ', $parts);
    }

    private function fallbackQuery(string $name): string
    {
        $cleaned = preg_replace('/[^a-zA-Z0-9\s]/', ' ', $name) ?? $name;
        $parts = preg_split('/\s+/', trim($cleaned)) ?: [];
        $parts = array_slice(array_values(array_filter($parts)), 0, 6);

        return implode(' ', $parts);
    }

    /**
     * @return array{bytes: string, extension: string}|null
     */
    private function searchOpenverseBinary(string $query): ?array
    {
        $query = trim($query);
        if ($query === '') {
            return null;
        }

        try {
            $response = Http::acceptJson()
                ->withHeaders([
                    'User-Agent' => 'FullVendor/1.0 (product-image-assign)',
                ])
                ->timeout(25)
                ->get('https://api.openverse.org/v1/images/', [
                    'q' => $query,
                    'page_size' => 5,
                    'license' => 'commercial',
                ]);

            if (! $response->successful()) {
                return null;
            }

            $results = data_get($response->json(), 'results', []);
            if (! is_array($results) || $results === []) {
                return null;
            }

            foreach ($results as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $url = (string) ($item['url'] ?? '');
                if ($url === '' || ! str_starts_with($url, 'http')) {
                    continue;
                }
                $downloaded = $this->downloadImageBytes($url);
                if ($downloaded !== null) {
                    return $downloaded;
                }
            }
        } catch (\Throwable $e) {
            Log::info('Openverse search failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * @return array{bytes: string, extension: string}
     */
    private function generateOpenAiImageBinary(string $productLabel): array
    {
        if (! filter_var(config('ai.openai.image_generation_enabled', true), FILTER_VALIDATE_BOOLEAN)) {
            throw new RuntimeException('openai_image_disabled');
        }

        $apiKey = trim((string) config('ai.openai.api_key', ''));
        if ($apiKey === '') {
            throw new RuntimeException('openai_not_configured');
        }

        $baseUrl = rtrim((string) config('ai.openai.base_url', 'https://api.openai.com/v1'), '/');
        $model = (string) config('ai.openai.image_model', 'dall-e-3');
        $timeout = max(30, (int) config('ai.openai.timeout', 60));
        $prompt = 'Professional product catalog photo of: ' . mb_substr($productLabel, 0, 180)
            . '. Clean white background, centered product, realistic e-commerce style, no text, no watermark.';

        $response = Http::withToken($apiKey)
            ->acceptJson()
            ->timeout($timeout)
            ->post($baseUrl . '/images/generations', [
                'model' => $model,
                'prompt' => $prompt,
                'n' => 1,
                'size' => '1024x1024',
                'response_format' => 'b64_json',
            ]);

        if (! $response->successful()) {
            $errorCode = (string) data_get($response->json(), 'error.code', '');
            $errorType = (string) data_get($response->json(), 'error.type', '');
            if ($response->status() === 429 || $errorCode === 'credit_balance_exhausted' || $errorType === 'insufficient_quota') {
                throw new RuntimeException('openai_quota_exceeded');
            }
            throw new RuntimeException('openai_image_failed');
        }

        $b64 = data_get($response->json(), 'data.0.b64_json');
        if (! is_string($b64) || $b64 === '') {
            $url = data_get($response->json(), 'data.0.url');
            if (is_string($url) && $url !== '') {
                $downloaded = $this->downloadImageBytes($url);
                if ($downloaded !== null) {
                    return $downloaded;
                }
            }
            throw new RuntimeException('openai_image_empty');
        }

        $bytes = base64_decode($b64, true);
        if ($bytes === false || $bytes === '') {
            throw new RuntimeException('openai_image_empty');
        }

        return ['bytes' => $bytes, 'extension' => 'png'];
    }

    /**
     * @return array{bytes: string, extension: string}|null
     */
    private function downloadImageBytes(string $url): ?array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'FullVendor/1.0 (product-image-assign)',
            ])
                ->timeout(30)
                ->get($url);

            if (! $response->successful()) {
                return null;
            }

            $bytes = $response->body();
            if ($bytes === '' || strlen($bytes) < 1024) {
                return null;
            }

            $mime = strtolower((string) ($response->header('Content-Type') ?? ''));
            $extension = 'jpg';
            if (str_contains($mime, 'png')) {
                $extension = 'png';
            } elseif (str_contains($mime, 'webp')) {
                $extension = 'webp';
            } elseif (str_contains($mime, 'gif')) {
                $extension = 'gif';
            }

            return ['bytes' => $bytes, 'extension' => $extension];
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function extractJsonObject(string $raw): array
    {
        $raw = trim($raw);
        if (preg_match('/\{.*\}/s', $raw, $m)) {
            $raw = $m[0];
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }
}

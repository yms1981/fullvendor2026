<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Erp\Concerns\ResolvesErpCompany;
use App\Models\Category;
use App\Models\Product;
use App\Services\Erp\ErpProductImageUploadService;
use App\Services\Erp\ErpProductSyncFromErpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use OpenApi\Attributes as OA;
use Throwable;

#[OA\Tag(name: 'Erp', description: 'Integración ERP. Auth: header X-Api-Key (sin Sanctum).')]
class ErpProductsController extends Controller
{
    use ResolvesErpCompany;

    #[OA\Get(
        path: 'api/v2/erp/products',
        summary: 'Listar productos de la empresa',
        security: [['erpApiKey' => []]],
        tags: ['Erp'],
        parameters: [
            new OA\Parameter(name: 'company_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Company-Id', in: 'header', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'OK'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'X-Api-Key inválida'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'company_id requerido'),
        ]
    )]
    public function list(Request $request): JsonResponse
    {
        $companyId = $this->requireCompanyId($request);
        $rows = Product::query()
            ->where('company_id', $companyId)
            ->orderBy('pro_id')
            ->get([
                'pro_id', 'sku', 'name', 'sale_price', 'fob_price', 'purchase_price',
                'stock', 'status', 'unit_id', 'category_id', 'cat_id', 'barcode',
                'currency_type', 'imgURL', 'simgURL',
            ]);

        return $this->erpOk($rows->map(fn (Product $p) => [
            'pro_id' => (int) $p->pro_id,
            'sku' => (string) ($p->sku ?? ''),
            'name' => (string) ($p->name ?? ''),
            'sale_price' => (float) ($p->sale_price ?? 0),
            'fob_price' => (float) ($p->fob_price ?? 0),
            'purchase_price' => (float) ($p->purchase_price ?? 0),
            'stock' => (float) ($p->stock ?? 0),
            'status' => (int) ($p->status ?? 0),
            'unit_id' => $p->unit_id,
            'category_id' => $p->category_id,
            'cat_id' => $p->cat_id,
            'barcode' => (string) ($p->barcode ?? ''),
            'currency_type' => (string) ($p->currency_type ?? '$'),
            'imgURL' => $p->imgURL,
            'simgURL' => $p->simgURL,
        ])->all());
    }

    #[OA\Get(
        path: 'api/v2/erp/products/without-images',
        summary: 'Listar productos sin fotos',
        description: 'Productos de la empresa sin default_image usable ni filas product_images con imagen en storage activo. '
            .'Sirve al ERP para detectar qué SKUs necesitan recibir imágenes.',
        security: [['erpApiKey' => []]],
        tags: ['Erp'],
        parameters: [
            new OA\Parameter(name: 'company_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 500)),
            new OA\Parameter(name: 'X-Company-Id', in: 'header', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'OK'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'X-Api-Key inválida'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'company_id requerido'),
        ]
    )]
    public function withoutImages(Request $request): JsonResponse
    {
        $companyId = $this->requireCompanyId($request);
        $limit = max(1, min(2000, (int) $request->input('limit', 500)));

        $rows = Product::query()
            ->where('company_id', $companyId)
            ->where(function ($q): void {
                $q->whereDoesntHave('default_image', fn ($iq) => $iq->onActiveStorage())
                    ->whereDoesntHave('product_images.image', fn ($iq) => $iq->onActiveStorage());
            })
            ->orderBy('pro_id')
            ->limit($limit)
            ->get(['pro_id', 'sku', 'name', 'status', 'imgURL', 'simgURL', 'default_image_id']);

        return $this->erpOk($rows->map(fn (Product $p) => [
            'pro_id' => (int) $p->pro_id,
            'sku' => (string) ($p->sku ?? ''),
            'name' => (string) ($p->name ?? ''),
            'status' => (int) ($p->status ?? 0),
            'imgURL' => $p->imgURL,
            'simgURL' => $p->simgURL,
            'default_image_id' => $p->default_image_id,
            'has_image' => false,
        ])->all(), 'Products without images');
    }

    #[OA\Post(
        path: 'api/v2/erp/products/upsert',
        summary: 'Crear o actualizar producto por SKU',
        security: [['erpApiKey' => []]],
        tags: ['Erp'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['company_id', 'sku'],
                properties: [
                    new OA\Property(property: 'company_id', type: 'integer', example: 1),
                    new OA\Property(property: 'sku', type: 'string', example: 'ABC-001'),
                    new OA\Property(property: 'name', type: 'string', nullable: true),
                    new OA\Property(property: 'sale_price', type: 'number', nullable: true),
                    new OA\Property(property: 'fob_price', type: 'number', nullable: true),
                    new OA\Property(property: 'purchase_price', type: 'number', nullable: true),
                    new OA\Property(property: 'stock', type: 'number', nullable: true),
                    new OA\Property(property: 'status', type: 'integer', nullable: true),
                    new OA\Property(property: 'unit_id', type: 'integer', nullable: true),
                    new OA\Property(property: 'category_id', type: 'integer', nullable: true),
                    new OA\Property(property: 'cat_id', type: 'integer', nullable: true),
                    new OA\Property(property: 'cats_id', type: 'string', nullable: true, description: 'CSV de cat_id remotos'),
                    new OA\Property(
                        property: 'category_names',
                        type: 'array',
                        nullable: true,
                        items: new OA\Items(type: 'string'),
                        description: 'Nombres de categoría (se crean/enlazan y rellenan cats_id)'
                    ),
                    new OA\Property(property: 'barcode', type: 'string', nullable: true),
                    new OA\Property(property: 'currency_type', type: 'string', nullable: true),
                    new OA\Property(property: 'imgURL', type: 'string', nullable: true),
                    new OA\Property(property: 'simgURL', type: 'string', nullable: true),
                    new OA\Property(property: 'language_id', type: 'integer', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Producto creado o actualizado'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'X-Api-Key inválida'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validación'),
        ]
    )]
    public function upsert(Request $request): JsonResponse
    {
        $companyId = $this->requireCompanyId($request);
        $sku = trim((string) $request->input('sku', ''));
        if ($sku === '') {
            return $this->erpFail('sku is required');
        }

        $product = Product::query()
            ->where('company_id', $companyId)
            ->whereRaw('UPPER(TRIM(sku)) = ?', [mb_strtoupper($sku, 'UTF-8')])
            ->first();

        $created = false;
        if (! $product) {
            $product = new Product;
            $product->company_id = $companyId;
            $product->sku = $sku;
            $product->language_id = (int) ($request->input('language_id') ?: 1);
            $product->created_on = now();
            $created = true;
        }

        foreach ([
            'name' => 'name',
            'sale_price' => 'sale_price',
            'fob_price' => 'fob_price',
            'purchase_price' => 'purchase_price',
            'stock' => 'stock',
            'status' => 'status',
            'unit_id' => 'unit_id',
            'category_id' => 'category_id',
            'cat_id' => 'cat_id',
            'cats_id' => 'cats_id',
            'barcode' => 'barcode',
            'currency_type' => 'currency_type',
            'imgURL' => 'imgURL',
            'simgURL' => 'simgURL',
        ] as $input => $column) {
            if ($request->exists($input)) {
                $product->{$column} = $request->input($input);
            }
        }

        // category_names[] / category_name: asegura cats remotas por nombre y rellena cats_id.
        $resolvedCats = $this->resolveCategoryIdsFromRequest($request, $companyId);
        if ($resolvedCats !== []) {
            $joined = implode(',', array_map(static fn (int $id): string => (string) $id, $resolvedCats));
            $product->cats_id = $joined;
            $product->cat_id = (string) $resolvedCats[0];
            $product->category_id = (string) $resolvedCats[0];
        } elseif ($request->exists('cats_id') || $request->exists('cat_id') || $request->exists('category_id')) {
            $fromIds = $this->parseCategoryIdList(
                $request->input('cats_id'),
                $request->input('cat_id'),
                $request->input('category_id'),
            );
            if ($fromIds !== []) {
                $joined = implode(',', array_map(static fn (int $id): string => (string) $id, $fromIds));
                $product->cats_id = $joined;
                if (! $request->exists('cat_id')) {
                    $product->cat_id = (string) $fromIds[0];
                }
                if (! $request->exists('category_id')) {
                    $product->category_id = (string) $fromIds[0];
                }
            }
        }

        if ($request->exists('stock') && ! $request->exists('status')) {
            $product->status = ((float) $product->stock) > 0 ? 1 : 0;
        }

        $product->modified_on = now();
        $product->save();

        return $this->erpOk([
            'pro_id' => (int) $product->pro_id,
            'sku' => (string) $product->sku,
            'created' => $created,
            'stock' => (float) $product->stock,
            'sale_price' => (float) $product->sale_price,
            'fob_price' => (float) $product->fob_price,
            'purchase_price' => (float) $product->purchase_price,
            'status' => (int) $product->status,
            'category_id' => $product->category_id,
            'cat_id' => $product->cat_id,
            'cats_id' => $product->cats_id,
        ], $created ? 'Product created' : 'Product updated');
    }

    #[OA\Post(
        path: 'api/v2/erp/products/stock-update',
        summary: 'Actualizar stock/precios por lote de SKUs',
        description: 'Acepta items[] o un objeto único con sku. Opcional: stock, fob_price, purchase_price, sale_price.',
        security: [['erpApiKey' => []]],
        tags: ['Erp'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['company_id'],
                properties: [
                    new OA\Property(property: 'company_id', type: 'integer', example: 1),
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'sku', type: 'string'),
                                new OA\Property(property: 'stock', type: 'number', nullable: true),
                                new OA\Property(property: 'fob_price', type: 'number', nullable: true),
                                new OA\Property(property: 'purchase_price', type: 'number', nullable: true),
                                new OA\Property(property: 'sale_price', type: 'number', nullable: true),
                                new OA\Property(property: 'company_id', type: 'integer', nullable: true),
                            ],
                            type: 'object'
                        )
                    ),
                    new OA\Property(property: 'sku', type: 'string', nullable: true, description: 'Alternativa a items[] para un solo SKU'),
                    new OA\Property(property: 'stock', type: 'number', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Resumen updated/failed'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'X-Api-Key inválida'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validación'),
        ]
    )]
    public function stockUpdate(Request $request): JsonResponse
    {
        $companyId = $this->requireCompanyId($request);
        $items = $request->input('items');
        if (! is_array($items) || $items === []) {
            // Allow single object payload.
            if ($request->filled('sku')) {
                $items = [$request->all()];
            } else {
                return $this->erpFail('items[] is required');
            }
        }

        $updated = 0;
        $failed = 0;
        $results = [];
        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                $failed++;
                $results[] = ['index' => $index, 'outcome' => 'failed', 'error' => 'invalid_item'];
                continue;
            }
            $sku = trim((string) ($item['sku'] ?? ''));
            $itemCompany = (int) ($item['company_id'] ?? $companyId);
            if ($sku === '' || $itemCompany <= 0) {
                $failed++;
                $results[] = ['index' => $index, 'sku' => $sku, 'outcome' => 'failed', 'error' => 'validation'];
                continue;
            }

            $product = Product::query()
                ->where('company_id', $itemCompany)
                ->whereRaw('UPPER(TRIM(sku)) = ?', [mb_strtoupper($sku, 'UTF-8')])
                ->first();
            if (! $product) {
                $failed++;
                $results[] = ['index' => $index, 'sku' => $sku, 'outcome' => 'failed', 'error' => 'sku_not_found'];
                continue;
            }

            $stock = max(0, (float) ($item['stock'] ?? $product->stock));
            if (array_key_exists('stock', $item)) {
                $product->stock = $stock;
            }
            if (array_key_exists('fob_price', $item)) {
                $product->fob_price = (float) $item['fob_price'];
            }
            if (array_key_exists('purchase_price', $item)) {
                $product->purchase_price = (float) $item['purchase_price'];
            }
            if (array_key_exists('sale_price', $item)) {
                $product->sale_price = (float) $item['sale_price'];
            }
            $product->status = $stock > 0 ? 1 : 0;
            $product->save();
            $updated++;
            $results[] = [
                'index' => $index,
                'sku' => $sku,
                'outcome' => 'updated',
                'pro_id' => (int) $product->pro_id,
                'stock' => (float) $product->stock,
                'status' => (int) $product->status,
            ];
        }

        return $this->erpOk([
            'summary' => ['total' => count($items), 'updated' => $updated, 'failed' => $failed],
            'results' => $results,
        ]);
    }

    #[OA\Post(
        path: 'api/v2/erp/products/sync',
        summary: 'Sync masivo de productos desde el ERP',
        description: 'Recibe productos con precios (fob_price / purchase_price), company_id y links de imágenes. '
            .'Si el producto no tiene fotos, el MVC descarga los links y las configura. '
            .'Si ya tiene fotos y el ERP trae file_mtime más reciente, descarga y reemplaza; si no, solo informa.',
        security: [['erpApiKey' => []]],
        tags: ['Erp'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['company_id', 'products'],
                properties: [
                    new OA\Property(property: 'company_id', type: 'integer', example: 1),
                    new OA\Property(
                        property: 'products',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'sku', type: 'string'),
                                new OA\Property(property: 'name', type: 'string', nullable: true),
                                new OA\Property(property: 'fob_price', type: 'number', nullable: true),
                                new OA\Property(property: 'purchase_price', type: 'number', nullable: true),
                                new OA\Property(property: 'sale_price', type: 'number', nullable: true),
                                new OA\Property(property: 'stock', type: 'number', nullable: true),
                                new OA\Property(property: 'company_id', type: 'integer', nullable: true),
                                new OA\Property(
                                    property: 'images',
                                    type: 'array',
                                    nullable: true,
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'url', type: 'string'),
                                            new OA\Property(property: 'is_default', type: 'boolean', nullable: true),
                                            new OA\Property(property: 'file_mtime', type: 'string', nullable: true),
                                            new OA\Property(property: 'sort_order', type: 'integer', nullable: true),
                                        ],
                                        type: 'object'
                                    )
                                ),
                            ],
                            type: 'object'
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Resumen del sync'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'X-Api-Key inválida'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validación'),
        ]
    )]
    public function syncFromErp(Request $request, ErpProductSyncFromErpService $sync): JsonResponse
    {
        $companyId = $this->requireCompanyId($request);
        $products = $request->input('products');
        if (! is_array($products) || $products === []) {
            return $this->erpFail('products[] is required');
        }

        try {
            $result = $sync->sync($companyId, $products);
        } catch (Throwable $e) {
            return $this->erpFail($e->getMessage());
        }

        return $this->erpOk($result, 'Products synced from ERP');
    }

    #[OA\Post(
        path: 'api/v2/erp/products/images/from-urls',
        summary: 'Actualizar imágenes de productos desde URLs del ERP',
        description: 'Recibe un listado de productos activos con URLs de imágenes. '
            .'El MVC descarga cada URL y las guarda (o reemplaza si el ERP trae file_mtime más reciente).',
        security: [['erpApiKey' => []]],
        tags: ['Erp'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['company_id', 'items'],
                properties: [
                    new OA\Property(property: 'company_id', type: 'integer', example: 1),
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        items: new OA\Items(
                            required: ['sku', 'images'],
                            properties: [
                                new OA\Property(property: 'sku', type: 'string'),
                                new OA\Property(property: 'company_id', type: 'integer', nullable: true),
                                new OA\Property(
                                    property: 'images',
                                    type: 'array',
                                    items: new OA\Items(
                                        properties: [
                                            new OA\Property(property: 'url', type: 'string'),
                                            new OA\Property(property: 'is_default', type: 'boolean', nullable: true),
                                            new OA\Property(property: 'file_mtime', type: 'string', nullable: true),
                                            new OA\Property(property: 'sort_order', type: 'integer', nullable: true),
                                        ],
                                        type: 'object'
                                    )
                                ),
                            ],
                            type: 'object'
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Resumen de descarga/comparación'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'X-Api-Key inválida'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validación'),
        ]
    )]
    public function imagesFromUrls(Request $request, ErpProductSyncFromErpService $sync): JsonResponse
    {
        $companyId = $this->requireCompanyId($request);
        $items = $request->input('items', $request->input('products'));
        if (! is_array($items) || $items === []) {
            return $this->erpFail('items[] is required (sku + images[].url)');
        }

        try {
            $result = $sync->syncImagesFromUrls($companyId, $items);
        } catch (Throwable $e) {
            return $this->erpFail($e->getMessage());
        }

        return $this->erpOk($result, 'Product images synced from URLs');
    }

    #[OA\Post(
        path: 'api/v2/erp/products/images/attach-url',
        summary: 'Adjuntar una imagen de producto desde URL del ERP',
        description: 'Recibe company_id, sku, url e is_default. El MVC descarga la URL y la guarda '
            .'con la misma política que el alta de fotos del backend (Image::putContent / slots SKU).',
        security: [['erpApiKey' => []]],
        tags: ['Erp'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['company_id', 'sku', 'url'],
                properties: [
                    new OA\Property(property: 'company_id', type: 'integer', example: 1),
                    new OA\Property(property: 'sku', type: 'string', example: 'ABC-001'),
                    new OA\Property(property: 'url', type: 'string', example: 'https://erp.example/storage/products/1/a.jpg'),
                    new OA\Property(property: 'image_url', type: 'string', nullable: true, description: 'Alias de url'),
                    new OA\Property(property: 'is_default', type: 'boolean', nullable: true, example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Imagen adjunta'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'X-Api-Key inválida'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validación'),
        ]
    )]
    public function attachImageFromUrl(Request $request, ErpProductImageUploadService $uploader): JsonResponse
    {
        $companyId = $this->requireCompanyId($request);
        $sku = trim((string) $request->input('sku', ''));
        $url = trim((string) ($request->input('url') ?? $request->input('image_url') ?? ''));
        if ($sku === '' || $url === '') {
            return $this->erpFail('sku and url are required');
        }

        try {
            $result = $uploader->attachFromUrlItems($companyId, [[
                'sku' => $sku,
                'url' => $url,
                'is_default' => filter_var($request->input('is_default', true), FILTER_VALIDATE_BOOLEAN),
                'company_id' => $companyId,
            ]]);
        } catch (Throwable $e) {
            return $this->erpFail($e->getMessage());
        }

        return $this->erpOk($result, 'Product image attached from URL');
    }

    #[OA\Post(
        path: 'api/v2/erp/products/images/attach-urls',
        summary: 'Adjuntar imágenes de productos desde URLs del ERP (lote)',
        description: 'Recibe company_id + items[{sku,url,is_default}]. Descarga cada URL y guarda '
            .'igual que el alta manual / multipart (slots {SKU} / {SKU}_PICN).',
        security: [['erpApiKey' => []]],
        tags: ['Erp'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['company_id', 'items'],
                properties: [
                    new OA\Property(property: 'company_id', type: 'integer', example: 1),
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        items: new OA\Items(
                            required: ['sku', 'url'],
                            properties: [
                                new OA\Property(property: 'sku', type: 'string'),
                                new OA\Property(property: 'url', type: 'string'),
                                new OA\Property(property: 'image_url', type: 'string', nullable: true),
                                new OA\Property(property: 'is_default', type: 'boolean', nullable: true),
                                new OA\Property(property: 'company_id', type: 'integer', nullable: true),
                            ],
                            type: 'object'
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Resumen de adjuntos'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'X-Api-Key inválida'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validación'),
        ]
    )]
    public function attachImagesFromUrls(Request $request, ErpProductImageUploadService $uploader): JsonResponse
    {
        $companyId = $this->requireCompanyId($request);
        $items = $request->input('items', $request->input('images'));
        if (! is_array($items) || $items === []) {
            return $this->erpFail('items[] is required (sku + url + is_default)');
        }

        try {
            $result = $uploader->attachFromUrlItems($companyId, $items);
        } catch (Throwable $e) {
            return $this->erpFail($e->getMessage());
        }

        return $this->erpOk($result, 'Product images attached from URLs');
    }

    #[OA\Post(
        path: 'api/v2/erp/products/images',
        summary: 'Subir imágenes de producto',
        description: 'Multipart: guarda archivos en el directorio de productos de la empresa y los asocia al producto. '
            .'Default: default_index (0-based) sobre las subidas de este request; si no se envía, la primera imagen '
            .'es default cuando replace=1 o el producto no tenía default. Máximo 5 imágenes por producto.',
        security: [['erpApiKey' => []]],
        tags: ['Erp'],
        parameters: [
            new OA\Parameter(name: 'company_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Company-Id', in: 'header', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'multipart/form-data',
                schema: new OA\Schema(
                    required: ['company_id'],
                    properties: [
                        new OA\Property(property: 'company_id', type: 'integer', example: 1),
                        new OA\Property(property: 'pro_id', type: 'integer', nullable: true, description: 'ID interno FullVendor'),
                        new OA\Property(property: 'sku', type: 'string', nullable: true, description: 'Alternativa a pro_id'),
                        new OA\Property(
                            property: 'images',
                            type: 'array',
                            items: new OA\Items(type: 'string', format: 'binary'),
                            description: 'Una o más imágenes (también acepta image / files / contents)'
                        ),
                        new OA\Property(
                            property: 'default_index',
                            type: 'integer',
                            nullable: true,
                            description: 'Índice 0-based de la imagen default entre las subidas de este request (default implícito: 0)'
                        ),
                        new OA\Property(
                            property: 'replace',
                            type: 'boolean',
                            nullable: true,
                            description: 'Si true, reemplaza la galería actual antes de subir'
                        ),
                    ],
                    type: 'object'
                )
            )
        ),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Imágenes guardadas y asignadas'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'X-Api-Key inválida'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Producto no encontrado'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validación'),
        ]
    )]
    public function uploadImages(Request $request, ErpProductImageUploadService $uploader): JsonResponse
    {
        $companyId = $this->requireCompanyId($request);

        $proId = (int) $request->input('pro_id', 0);
        $sku = trim((string) $request->input('sku', ''));
        if ($proId <= 0 && $sku === '') {
            return $this->erpFail('Product not found (pro_id or sku required).', 404);
        }

        $files = $this->collectUploadedImages($request);
        if ($files === []) {
            return $this->erpFail('At least one image file is required (images[] / image / files / contents).');
        }

        $product = $this->resolveProduct($request, $companyId);
        if (! $product) {
            return $this->erpFail('Product not found (pro_id or sku required).', 404);
        }

        $defaultIndex = $request->filled('default_index') ? (int) $request->input('default_index') : null;
        $replace = filter_var($request->input('replace', false), FILTER_VALIDATE_BOOLEAN);

        try {
            $result = $uploader->upload($product, $files, $defaultIndex, $replace);
        } catch (Throwable $e) {
            return $this->erpFail($e->getMessage());
        }

        return $this->erpOk($result, 'Product images uploaded');
    }

    private function resolveProduct(Request $request, int $companyId): ?Product
    {
        $proId = (int) $request->input('pro_id', 0);
        if ($proId > 0) {
            return Product::query()
                ->where('company_id', $companyId)
                ->where('pro_id', $proId)
                ->first();
        }

        $sku = trim((string) $request->input('sku', ''));
        if ($sku === '') {
            return null;
        }

        return Product::query()
            ->where('company_id', $companyId)
            ->whereRaw('UPPER(TRIM(sku)) = ?', [mb_strtoupper($sku, 'UTF-8')])
            ->first();
    }

    /**
     * @return list<UploadedFile>
     */
    private function collectUploadedImages(Request $request): array
    {
        $files = [];
        foreach (['images', 'image', 'files', 'contents'] as $key) {
            if (! $request->hasFile($key)) {
                continue;
            }
            $value = $request->file($key);
            if (is_array($value)) {
                foreach ($value as $file) {
                    if ($file instanceof UploadedFile) {
                        $files[] = $file;
                    }
                }
            } elseif ($value instanceof UploadedFile) {
                $files[] = $value;
            }
        }

        if ($files === []) {
            foreach ($request->allFiles() as $value) {
                if (is_array($value)) {
                    foreach ($value as $file) {
                        if ($file instanceof UploadedFile) {
                            $files[] = $file;
                        }
                    }
                } elseif ($value instanceof UploadedFile) {
                    $files[] = $value;
                }
            }
        }

        return array_values($files);
    }

    /**
     * Resuelve cat_id a partir de category_names / category_name (crea si faltan).
     *
     * @return list<int>
     */
    private function resolveCategoryIdsFromRequest(Request $request, int $companyId): array
    {
        $names = [];
        $raw = $request->input('category_names', $request->input('category_name'));
        if (is_array($raw)) {
            foreach ($raw as $item) {
                $name = trim((string) $item);
                if ($name !== '') {
                    $names[] = $name;
                }
            }
        } elseif (is_string($raw) && trim($raw) !== '') {
            foreach (preg_split('/[,;|]+/', $raw) ?: [] as $piece) {
                $name = trim((string) $piece);
                if ($name !== '') {
                    $names[] = $name;
                }
            }
        }

        if ($names === []) {
            return [];
        }

        $nameColumn = \App\Support\CategoryNameColumn::name();
        $ids = [];
        foreach (array_values(array_unique($names)) as $name) {
            $category = Category::query()
                ->where('company_id', $companyId)
                ->whereRaw('LOWER(TRIM('.$nameColumn.')) = ?', [mb_strtolower($name, 'UTF-8')])
                ->first();

            if (! $category) {
                $category = new Category;
                $category->company_id = $companyId;
                $category->{$nameColumn} = $name;
                $category->category_status = 1;
                $category->category_default = 0;
                $category->category_created_at = now();
                $category->save();
            }

            $catId = (int) $category->cat_id;
            if ($catId > 0) {
                $ids[] = $catId;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return list<int>
     */
    private function parseCategoryIdList(mixed ...$values): array
    {
        $ids = [];
        foreach ($values as $raw) {
            if ($raw === null || $raw === '') {
                continue;
            }
            $parts = is_array($raw)
                ? $raw
                : (preg_split('/\s*,\s*/', trim((string) $raw)) ?: []);
            foreach ($parts as $part) {
                $part = trim((string) $part);
                if ($part !== '' && is_numeric($part) && (int) $part > 0 && ! str_contains($part, '.')) {
                    $ids[] = (int) $part;
                }
            }
        }

        return array_values(array_unique($ids));
    }
}

<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Erp\Concerns\ResolvesErpCompany;
use App\Models\Product;
use App\Services\Erp\ErpProductImageUploadService;
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
            'barcode' => 'barcode',
            'currency_type' => 'currency_type',
            'imgURL' => 'imgURL',
            'simgURL' => 'simgURL',
        ] as $input => $column) {
            if ($request->exists($input)) {
                $product->{$column} = $request->input($input);
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
}

<?php

namespace App\Http\Controllers;

use App\Exports\ProductsExport;
use App\Http\Requests\BulkProductDeleteRequest;
use App\Http\Requests\SkuProductRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\CategoryGroup;
use App\Models\CategorySubgroup;
use App\Models\Company;
use App\Models\CustomerGroup;
use App\Support\DisplayDateFormat;
use App\Models\Image;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Services\ProductInventoryMvcService;
use App\Services\ProductRatingService;
use App\Support\ProductBulkSelection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Maatwebsite\Excel\Facades\Excel;
use OpenApi\Attributes as OA;

class ProductController extends Controller
{
    protected $resourceRoute = 'product';

    /** Orden por defecto: última modificación primero. */
    protected $defaultOrderProperty = 'modified_on';
    protected $defaultOrderDirection = 'DESC';
    protected $defaultOrderPropertySecondary = 'created_on';
    protected $defaultOrderDirectionSecondary = 'DESC';

    #[OA\Get(
        path: "api/v2/product",
        summary: "Display a listing of the Product.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Product"],

        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/Product')
                    ),
                    new OA\Property(
                property: 'meta',
                type: 'object',
                properties: [
                    new OA\Property(property: 'current_page', type: 'integer'),
                    new OA\Property(property: 'from', type: 'integer'),
                    new OA\Property(property: 'last_page', type: 'integer'),
                    new OA\Property(property: 'per_page', type: 'integer'),
                    new OA\Property(property: 'to', type: 'integer'),
                    new OA\Property(property: 'total', type: 'integer')
                ]
            ),
            new OA\Property(
                property: 'links',
                type: 'object',
                properties: [
                    new OA\Property(property: 'first', type: 'string'),
                    new OA\Property(property: 'last', type: 'string'),
                    new OA\Property(property: 'prev', type: 'string', nullable: true),
                    new OA\Property(property: 'next', type: 'string', nullable: true)
                ]
            )
                ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function index()
    {
        $this->ensureProductRatingsFromRequest();

        return parent::index();
    }

    /**
     * Asegura ratings de la empresa del request y carga la relación productRating.
     */
    private function ensureProductRatingsFromRequest(): void
    {
        $request = request();
        $companyId = (int) ($request->input('company') ?? $request->input('company_id') ?? 0);
        if ($companyId < 1) {
            $user = Auth::user();
            $companyId = (int) ($user?->companies?->first()?->company_id ?? 0);
        }
        if ($companyId > 0) {
            app(ProductRatingService::class)->ensureForCompany($companyId);
        }

        $relations = $request->input('relations', []);
        if (! is_array($relations)) {
            $relations = [];
        }
        if (! in_array('productRating', $relations, true)) {
            $relations[] = 'productRating';
        }
        $request->merge(['relations' => $relations]);
    }

    /**
     * Obtener todos los productos sin paginación de la compañía del usuario logueado
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Get(
        path: "api/v2/product/all",
        summary: "Obtener todos los productos sin paginación de la compañía del usuario logueado",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
        tags: ["Product"],
        parameters: [
            new OA\Parameter(
                name: 'props',
                in: 'query',
                required: false,
                description: 'Campos a retornar (array o string separado por comas)',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Lista de todos los productos de la compañía del usuario",
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Product')
                        )
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "No autenticado"
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: "No se pudo determinar la compañía del usuario"
            )
        ]
    )]
    public function all(Request $request)
    {
        // Obtener el usuario autenticado
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        
        // Obtener el company_id del request o de la primera compañía del usuario
        $companyId = $request->input('company_id') ?? $request->input('company');
        
        if (!$companyId) {
            // Si no se proporciona company_id, usar la primera compañía del usuario como manager
            $userCompany = $user->companiesForRole('manager')->first();
            if (!$userCompany) {
                // Si no es manager, intentar obtener de otras relaciones
                $userCompany = $user->companies()->first();
            }
            $companyId = $userCompany?->company_id;
        }
        
        if (!$companyId) {
            return response()->json([
                'message' => 'No se pudo determinar la compañía. Asegúrese de estar asociado a una compañía.'
            ], 422);
        }
        
        // Forzar all=true y company para obtener todas sin paginación filtradas por compañía
        $request->merge([
            'all' => true,
            'company' => $companyId,
            'company_id' => $companyId
        ]);
        
        // Optimizar las relaciones: cargar default_image, categories y unit sin company
        $originalRelations = $request->input('relations', []);
        $relations = is_array($originalRelations) ? $originalRelations : [];
        if (!in_array('default_image', $relations)) {
            $relations[] = 'default_image';
        }
        if (!in_array('categories', $relations)) {
            $relations[] = 'categories';
        }
        // Cargar unit sin company para evitar carga innecesaria
        if (!in_array('unit', $relations)) {
            $relations[] = 'unit';
        }
        if (!in_array('productRating', $relations, true)) {
            $relations[] = 'productRating';
        }
        $request->merge(['relations' => $relations]);
        app(ProductRatingService::class)->ensureForCompany((int) $companyId);
        
        // Construir query optimizada directamente usando subquery para unit
        // Esto evita cargar la relación completa y el $with automático de Unit que carga company
        $query = Product::without('unit') // Evitar el $with automático
            ->where('company_id', $companyId)
            ->addSelect([
                // Agregar el valor de unit directamente mediante subquery
                // Esto evita cargar la relación completa y el $with automático de Unit que carga company
                'unit_value' => DB::table('units')
                    ->select('unit')
                    ->whereColumn('units.unit_id', 'products.unit_id')
                    ->limit(1)
            ])
            ->with([
                'default_image' => function ($q) {
                    $q->select('uuid', 'name', 'path', 'company_id', 'disk', 'extension', 'mime_type');
                },
                'categories' => function($q) {
                    $q->select('categories.cat_id', 'categories.category_name');
                },
                'productRating',
            ]);
        
        // Aplicar filtros adicionales del request si existen
        $query = $query->filter($request->all());
        
        // Aplicar ordenamiento (por defecto modified_on DESC)
        $sortColumn = $request->input('orderProperty', $this->defaultOrderProperty);
        $sortDirection = $request->input('orderDirection', $this->defaultOrderDirection);
        if (! empty($sortColumn)) {
            $query->orderBy($sortColumn, $sortDirection);
        }
        
        // Obtener productos sin paginación
        set_time_limit(0);
        $products = $query->get();

        // product_images + images: whereIn sobre pro_id y sobre products.product_id (columna distinta del PK)
        $proIds = $products->pluck('pro_id')->unique()->filter()->map(static fn ($v) => (int) $v)->values()->all();
        $legacyProductIds = $products->pluck('product_id')
            ->filter(static fn ($v) => $v !== null && $v !== '')
            ->map(static fn ($v) => (int) $v)
            ->unique()
            ->values()
            ->all();
        $allMatchIds = array_values(array_unique(array_merge($proIds, $legacyProductIds)));

        // LEFT JOIN: si image_id es NULL o no existe en `images`, igual devolvemos la fila de product_images
        // (el INNER JOIN anterior dejaba product_images_table vacío en muchas bases legacy / sin backfill).
        $allJoinRows = collect();
        if ($allMatchIds !== []) {
            $allJoinRows = ProductImage::query()
                ->leftJoin('images', 'product_images.image_id', '=', 'images.uuid')
                ->whereIn('product_images.product_id', $allMatchIds)
                ->whereNotNull('product_images.product_id')
                ->orderByRaw('CASE WHEN images.uuid IS NOT NULL THEN 0 ELSE 1 END')
                ->orderByRaw('COALESCE(product_images.img_order, 999999) ASC')
                ->orderBy('product_images.img_id')
                ->select([
                    'product_images.img_id',
                    'product_images.product_id',
                    'product_images.image_id as product_images_image_id',
                    'images.uuid as resolved_image_uuid',
                    'images.name as resolved_image_name',
                    'images.path as resolved_image_path',
                    'images.disk as resolved_image_disk',
                    'images.extension as resolved_image_extension',
                    'images.mime_type as resolved_image_mime_type',
                    'images.type as resolved_image_type',
                    'images.size as resolved_image_size',
                    'images.company_id as resolved_image_company_id',
                    'images.original_name as resolved_image_original_name',
                ])
                ->get();
        }

        $groupedByPiProductId = $allJoinRows->groupBy(static fn ($row) => (int) $row->product_id);
        $imageFirstRowByPiProductId = $allJoinRows
            ->unique(static fn ($row) => (int) $row->product_id)
            ->keyBy(static fn ($row) => (int) $row->product_id);

        $resolveFirstProductImageJoinRow = static function ($product) use ($imageFirstRowByPiProductId) {
            $proId = (int) $product->pro_id;
            $legacy = $product->getAttribute('product_id');
            $legacy = ($legacy !== null && $legacy !== '') ? (int) $legacy : null;
            $row = $imageFirstRowByPiProductId->get($proId);
            if ($row) {
                return $row;
            }
            if ($legacy !== null && $legacy !== $proId) {
                return $imageFirstRowByPiProductId->get($legacy);
            }

            return null;
        };

        $productImageRowsForProduct = static function ($product) use ($groupedByPiProductId) {
            $proId = (int) $product->pro_id;
            $legacy = $product->getAttribute('product_id');
            $legacy = ($legacy !== null && $legacy !== '') ? (int) $legacy : null;
            $keys = [$proId];
            if ($legacy !== null && $legacy !== $proId) {
                $keys[] = $legacy;
            }
            $merged = collect();
            foreach ($keys as $k) {
                if ($groupedByPiProductId->has($k)) {
                    $merged = $merged->concat($groupedByPiProductId->get($k));
                }
            }

            return $merged->unique('img_id')->sortBy('img_id')->values();
        };

        $allDefaultImageIds = $products
            ->pluck('default_image_id')
            ->filter(static fn ($v) => $v !== null && $v !== '')
            ->map(static fn ($v) => (string) $v)
            ->unique()
            ->values()
            ->all();
        $imageRowsByUuid = collect();
        if ($allDefaultImageIds !== []) {
            $imageRowsByUuid = Image::query()
                ->whereIn('uuid', $allDefaultImageIds)
                ->get()
                ->keyBy('uuid');
        }

        // Procesar productos de forma optimizada
        $request = request();
        // Usar el host de la petición actual, con fallback a url() si falla
        try {
            $baseUrl = $request->getSchemeAndHttpHost();
            // Si el host contiene "laravel.test", usar localhost en su lugar
            if (strpos($baseUrl, 'laravel.test') !== false) {
                $baseUrl = str_replace('laravel.test', 'localhost', $baseUrl);
            }
        } catch (\Exception $e) {
            $baseUrl = url('/');
            $baseUrl = rtrim($baseUrl, '/');
        }
        $productsWithImageUrl = $products->map(function ($product) use ($resolveFirstProductImageJoinRow, $productImageRowsForProduct, $imageRowsByUuid) {
            // Convertir a array
            $productArray = $product->toArray();

            // Campos explícitos de la tabla `products` (la columna `product_id` es distinta del PK `pro_id`)
            $dbProductId = $product->getAttribute('product_id');
            $productArray['products_table'] = [
                'pro_id' => (int) $product->pro_id,
                'product_id' => $dbProductId !== null ? (int) $dbProductId : null,
                'company_id' => $product->company_id !== null ? (int) $product->company_id : null,
                'language_id' => $product->language_id !== null ? (int) $product->language_id : null,
                'sku' => $product->sku,
                'name' => $product->name,
                'sale_price' => (float) $product->sale_price,
                'stock' => (float) $product->stock,
                'minimum_stock' => (float) $product->minimum_stock,
                'notify_minimum_stock' => (int) ($product->notify_minimum_stock ?? 0),
                'status' => (int) ($product->status ?? 0),
                'default_image_id' => $product->default_image_id,
                'unit_id' => $product->unit_id !== null ? (int) $product->unit_id : null,
                'currency_type' => $product->currency_type,
                'price_reduced' => (float) ($product->price_reduced ?? 0),
            ];

            $piRows = $productImageRowsForProduct($product);
            $productArray['product_images_table'] = [
                'rows' => $piRows->map(static function ($r) {
                    $uuid = $r->resolved_image_uuid ?? null;

                    return [
                        'img_id' => (int) $r->img_id,
                        'product_id' => (int) $r->product_id,
                        'image_id' => $r->product_images_image_id ?? null,
                        'image_uuid' => $uuid !== null && $uuid !== '' ? (string) $uuid : null,
                        'resolved_in_images_table' => $uuid !== null && $uuid !== '',
                    ];
                })->values()->all(),
            ];
            
            // 1. Raíz: `product_id` = `pro_id` por compatibilidad con clientes que usan product_id como PK interno
            if (isset($productArray['pro_id'])) {
                $productArray['product_id'] = $productArray['pro_id'];
            }
            
            // 2. Obtener categorías relacionadas
            $categories = $product->categories ?? collect();
            
            // 3. category_id = nombres de categorías separadas por comas
            $categoryNames = $categories->pluck('category_name')->filter()->implode(',');
            $productArray['category_id'] = $categoryNames ?: null;
            
            // 4. cat_id = IDs de categorías separadas por comas
            $categoryIds = $categories->pluck('cat_id')->filter()->implode(',');
            $productArray['cat_id'] = $categoryIds ?: null;
            
            // 5. Img: 1) default_image 2) product_images→images (primera fila) 3) default_image_id solo si existe en images
            $imageUuid = $product->default_image?->uuid;
            $imgExt = 'webp';
            if ($product->default_image) {
                $e = strtolower((string) ($product->default_image->extension ?? 'webp'));
                $imgExt = in_array($e, ['webp', 'png', 'jpg', 'jpeg', 'gif'], true) ? $e : 'webp';
            }
            $joinRow = $resolveFirstProductImageJoinRow($product);
            if (! $imageUuid) {
                $resolved = $joinRow?->resolved_image_uuid ?? null;
                if ($resolved) {
                    $imageUuid = (string) $resolved;
                    $fe = strtolower((string) ($joinRow->resolved_image_extension ?? 'webp'));
                    $imgExt = in_array($fe, ['webp', 'png', 'jpg', 'jpeg', 'gif'], true) ? $fe : 'webp';
                }
            }
            if (! $imageUuid && $product->default_image_id !== null && $product->default_image_id !== '') {
                $did = (string) $product->default_image_id;
                if ($imageRowsByUuid->has($did)) {
                    $imageUuid = $did;
                    $rowIm = $imageRowsByUuid->get($did);
                    $fe = strtolower((string) ($rowIm->extension ?? 'webp'));
                    $imgExt = in_array($fe, ['webp', 'png', 'jpg', 'jpeg', 'gif'], true) ? $fe : 'webp';
                }
            }
            if ($imageUuid) {
                $resolvedImageModel = null;
                if ($product->default_image?->uuid === $imageUuid) {
                    $resolvedImageModel = $product->default_image;
                } elseif ($imageRowsByUuid->has($imageUuid)) {
                    $resolvedImageModel = $imageRowsByUuid->get($imageUuid);
                } elseif ($product->relationLoaded('product_images')) {
                    foreach ($product->product_images as $pi) {
                        if ($pi->relationLoaded('image') && $pi->image?->uuid === $imageUuid) {
                            $resolvedImageModel = $pi->image;
                            break;
                        }
                    }
                }
                if (! Image::isOnActiveStorage($resolvedImageModel)) {
                    $imageUuid = null;
                }
            }
            if ($imageUuid) {
                $productArray['Img'] = '/images/' . $imageUuid . '.' . $imgExt;
                $productArray['has_photo'] = true;
                $productArray['has_image'] = true;
            } else {
                // Importante: no rellenar con /images/no-image — rompe el filtro con/sin foto.
                // El placeholder es solo para UI vía image_placeholder / onerror.
                $productArray['Img'] = null;
                $productArray['has_photo'] = false;
                $productArray['has_image'] = false;
            }
            $productArray['image_placeholder'] = '/images/no-image';

            // Metadatos tabla `images` (p. ej. depuración portal seller / MinIO)
            $catalogImagesTable = null;
            if ($product->default_image) {
                $im = $product->default_image;
                $catalogImagesTable = [
                    'source' => 'default_image',
                    'uuid' => $im->uuid,
                    'name' => $im->name,
                    'path' => $im->path,
                    'disk' => $im->disk,
                    'extension' => $im->extension,
                    'mime_type' => $im->mime_type,
                    'type' => $im->type,
                    'size' => $im->size,
                    'company_id' => $im->company_id,
                    'original_name' => $im->original_name,
                    'language' => $im->language ?? null,
                ];
            } elseif ($joinRow && ($joinRow->resolved_image_uuid ?? null) !== null && ($joinRow->resolved_image_uuid ?? '') !== '') {
                $fb = $joinRow;
                $catalogImagesTable = [
                    'source' => 'product_images_join_images',
                    'product_image_img_id' => $fb->img_id ?? null,
                    'product_images_image_id' => $fb->product_images_image_id ?? null,
                    'uuid' => $fb->resolved_image_uuid,
                    'name' => $fb->resolved_image_name ?? null,
                    'path' => $fb->resolved_image_path ?? null,
                    'disk' => $fb->resolved_image_disk ?? null,
                    'extension' => $fb->resolved_image_extension ?? null,
                    'mime_type' => $fb->resolved_image_mime_type ?? null,
                    'type' => $fb->resolved_image_type ?? null,
                    'size' => $fb->resolved_image_size ?? null,
                    'company_id' => $fb->resolved_image_company_id ?? null,
                    'original_name' => $fb->resolved_image_original_name ?? null,
                ];
            }
            if ($catalogImagesTable === null && $imageUuid !== null && $imageUuid !== '' && $imageRowsByUuid->has($imageUuid)) {
                $im = $imageRowsByUuid->get($imageUuid);
                $catalogImagesTable = [
                    'source' => 'products.default_image_id',
                    'uuid' => $im->uuid,
                    'name' => $im->name,
                    'path' => $im->path,
                    'disk' => $im->disk,
                    'extension' => $im->extension,
                    'mime_type' => $im->mime_type,
                    'type' => $im->type,
                    'size' => $im->size,
                    'company_id' => $im->company_id,
                    'original_name' => $im->original_name,
                    'language' => $im->language ?? null,
                ];
            }
            if ($catalogImagesTable === null && $imageUuid !== null && $imageUuid !== '') {
                $catalogImagesTable = [
                    'source' => 'uuid_unresolved_in_images_table',
                    'uuid' => $imageUuid,
                ];
            }
            if ($catalogImagesTable === null) {
                $catalogImagesTable = [
                    'source' => 'none',
                    'uuid' => null,
                ];
            }
            $productArray['catalog_images_table'] = $catalogImagesTable;
            
            // Mantener imageurl para compatibilidad (pero usar Img como campo principal)
            $productArray['imageurl'] = $productArray['Img'];
            
            // Usar el valor de unit_value de la subquery en lugar de la relación
            // Eliminar unit_value y asignarlo a unit
            if (isset($productArray['unit_value'])) {
                $productArray['unit'] = $productArray['unit_value'];
                unset($productArray['unit_value']);
            } else {
                $productArray['unit'] = null;
            }
            
            return $productArray;
        });
        
        return response()->json([
            'data' => $productsWithImageUrl->values()->all()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: "api/v2/product",
        summary: "Store a newly created resource in storage.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
                requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/ProductRequest'
            )
        ),
        tags: ["Product"],

        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                new OA\Property(
                    property: 'data',
                    ref: '#/components/schemas/Product'
                ),
                new OA\Property(
                    property: 'message',
                    type: 'string',
                    example: 'Resource created successfully'
                )
            ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function store(StoreProductRequest $request)
    {
        $arrRequest = $request->only((new \App\Models\Product)->getFillable());
        /** @var User $currentUser */
        $currentUser = Auth::user();
        $companyId = (int) ($request->get('company_id') ?? $currentUser->companies->first()?->company_id ?? 0);
        if ($companyId <= 0) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'company_id required');
        }

        $arrRequest['company_id'] = $companyId;
        $arrRequest['language_id'] = Company::where('company_id', $companyId)->sole()->language_id;
        $arrRequest['minimum_stock'] = $request->get('minimum_stock') ?? 0;
        $arrRequest['price_reduced'] = $request->get('price_reduced') ?? 0;

        $categoryIds = $this->normalizeCategoryIds($request->input('category_id'));
        $categoryIdsCsv = $categoryIds !== [] ? implode(',', $categoryIds) : null;
        // Columnas denormalizadas: IDs de categorías (nunca nombres).
        $arrRequest['category_id'] = $categoryIdsCsv;
        $arrRequest['cat_id'] = $categoryIdsCsv;
        $arrRequest['cats_id'] = $categoryIdsCsv;

        /** @var Product $newProduct */
        $newProduct = $this->createFromAttributes($arrRequest, false);

        // product_id debe coincidir con pro_id (PK interno / legado).
        if ((int) ($newProduct->product_id ?? 0) !== (int) $newProduct->pro_id) {
            $newProduct->product_id = (int) $newProduct->pro_id;
            $newProduct->save();
        }

        if (isset($request->images) && is_array($request->images)) {
            $newProduct->images()->createMany($request->images);
        }
        if ($categoryIds !== []) {
            foreach ($categoryIds as $categoryId) {
                $newProduct->categories()->attach($categoryId);
            }
        }

        $this->syncProductCategoryGroups($newProduct, $request, $companyId);

        app(ProductRatingService::class)->ensureForProduct($companyId, (int) $newProduct->pro_id);

        return $newProduct->refresh();
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: "api/v2/product/{product}",
        summary: "Display the specified Product.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Product"],
        parameters: [
            new OA\Parameter(
                name: "product",
                description: "The Product identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        ref: '#/components/schemas/Product'
                    )
                ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function show($product)
    {
        $instance = $this->getMainModelInstance($product);
        $companyId = (int) ($instance->company_id ?? 0);
        $productId = (int) ($instance->pro_id ?? 0);
        if ($companyId > 0) {
            app(ProductRatingService::class)->ensureForCompany($companyId);
        }
        if ($productId > 0 && ! $instance->relationLoaded('productRating')) {
            $instance->load('productRating');
        }

        return $instance;
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: "api/v2/product/{product}",
        summary: "Updates the specified Product.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
                requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/ProductRequest'
            )
        ),
        tags: ["Product"],
        parameters: [
            new OA\Parameter(
                name: "product",
                description: "The Product identifier to update.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                new OA\Property(
                    property: 'data',
                    ref: '#/components/schemas/Product'
                ),
                new OA\Property(
                    property: 'message',
                    type: 'string',
                    example: 'Resource updated successfully'
                )
            ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function update(UpdateProductRequest $request, $productId)
    {
        $companyId = $request->input('company_id');
        /** @var Product $product */
        $product = Product::where('pro_id', $productId)
            ->where('company_id', $companyId)
            ->with(['categories'])
            ->sole();
        $categoryIds = $this->normalizeCategoryIds($request->input('category_id'));
        $categories = Category::where('company_id', $companyId)
            ->whereIn('cat_id', $categoryIds)
            ->get();
        // Solo sincronizar categorías si el cliente envió el campo (actualización parcial segura).
        if ($request->exists('category_id')) {
            $product->categories()->sync($categories);
        }
        //$product->product_images()->createMany($request->input('images'));
        $arrRequest = $request->only((new Product)->getFillable());

        $categoryIdsCsv = $categoryIds !== [] ? implode(',', $categoryIds) : null;
        if ($request->exists('category_id')) {
            $arrRequest['category_id'] = $categoryIdsCsv;
            $arrRequest['cat_id'] = $categoryIdsCsv;
            $arrRequest['cats_id'] = $categoryIdsCsv;
        }

        // No permitir borrar company_id en update; mantener el de la fila.
        unset($arrRequest['company_id']);

        if (empty($product->product_id)) {
            $arrRequest['product_id'] = (int) $product->pro_id;
        }

        $updated = $this->updateModel($product, $arrRequest);
        if (
            $request->exists('category_group_ids')
            || $request->exists('category_group_id')
            || $request->exists('category_subgroup_ids')
            || $request->exists('category_subgroup_id')
        ) {
            $this->syncProductCategoryGroups($product->fresh(), $request, (int) $companyId);
        }

        return $updated;
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: "api/v2/product/{product}",
        summary: "Remove the specified Product.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Product"],
        parameters: [
            new OA\Parameter(
                name: "product",
                description: "The Product identifier to remove.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                new OA\Property(
                    property: 'message',
                    type: 'string',
                    example: 'Resource deleted successfully'
                )
            ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function destroy(Product $product): ?Response
    {
        return $this->destroyResourceEmptyResponse($product);
    }

    #[OA\Get(
        path: "api/v2/product/sku-{sku}",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Product"],
        parameters: [
            new OA\Parameter(
                name: "sku",
                description: "The Product identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        ref: '#/components/schemas/Product'
                    )
                ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    /**
     * Lista ligera de SKUs de la compañía (validación de nombres en importación de imágenes).
     */
    #[OA\Get(
        path: 'api/v2/product/skus',
        summary: 'Listar SKUs de productos de la compañía',
        security: [['bearerAuth' => []]],
        tags: ['Product'],
        parameters: [
            new OA\Parameter(
                name: 'company_id',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Lista de SKUs',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(type: 'string')
                        ),
                    ]
                )
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'No autenticado'),
        ]
    )]
    public function skus(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $companyId = (int) ($request->input('company_id') ?? $request->input('company') ?? 0);
        if ($companyId < 1) {
            $userCompany = $user->companiesForRole('manager')->first()
                ?? $user->companies()->first();
            $companyId = (int) ($userCompany?->company_id ?? 0);
        }

        if ($companyId < 1) {
            return response()->json(['data' => []]);
        }

        $skus = Product::query()
            ->where('company_id', $companyId)
            ->whereNotNull('sku')
            ->where('sku', '!=', '')
            ->orderBy('sku')
            ->pluck('sku')
            ->map(static fn ($sku) => trim((string) $sku))
            ->filter(static fn ($sku) => $sku !== '')
            ->unique()
            ->values()
            ->all();

        return response()->json(['data' => $skus]);
    }

    public function getBySku(SkuProductRequest $request, $sku)
    {
        $companyId = $request->get('company_id');
        // Convertir sku a mayúsculas para la búsqueda
        $sku = mb_strtoupper($sku, 'UTF-8');
        try {
            $with = request()->input('relations',[]) + ['default_image'];
            return Product::where("company_id", $companyId)
                ->where("sku", $sku)
                ->with($with)
                ->sole();
        }
        catch (ModelNotFoundException $e) {
            return null;
        }
    }

    public function getByCompany($companyId, $productId)
    {
        return Product::where("company_id", $companyId)->where("product_id", $productId)->sole();
    }

    #[OA\Get(
        path: "api/v2/product/{productId}/categories",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Product"],
        parameters: [
            new OA\Parameter(
                name: "productId",
                description: "The Category identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        ref: '#/components/schemas/Category'
                    )
                ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function categories(Product $product)
    {
        return $product->categories;
    }

    /**
     * @throws \Exception
     */
    public function formatted($identifier, Request $request)
    {
        $column = $request->input('column', 'pro_id');

        $product = Product::where($column, $identifier)->firstOrFail();

        $company = $product->company;

        if (!$company) {
            throw new \Exception('The company associated with the product was not found');
        }

        $compId = $company->company_id;

        $unitFormat = $company->units
            ->where('unit', $product->unit_type)
            ->first()
            ->formato ?? null;

        return Product::with([
                                 'images' => function ($query) use ($compId) {
                                     $query->where('company_id', $compId)
                                         ->limit(1);
                                 }
                             ])
            ->select('products.*')
            ->addSelect([
                            'formato' => DB::raw("'{$unitFormat}' as formato"),
                            'imagen_principal' => ProductImage::select('images')
                                ->whereColumn('product_images.product_id', 'products.pro_id')
                                ->where('company_id', $compId)
                                ->where('img_default', 1)
                                ->limit(1),
                            'imagen_por_defecto' => ProductImage::select('images')
                                ->whereColumn('product_images.product_id', 'products.pro_id')
                                ->limit(1),
                        ])
            ->where('products.pro_id', $product->pro_id)
            ->first();
    }

    #[OA\Get(
        path: "api/v2/company/{companyId}/products/locations",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Product"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The Location identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        type: "array",
                        items: new OA\Items(type: "string", example: "Lima"),
                    )
                ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function getLocations($companyId)
    {
        // Query directa: evita scopes/appends del modelo y valores null/vacíos del distinct.
        $raw = \Illuminate\Support\Facades\DB::table('products')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->whereNotNull('locations')
            ->where('locations', '!=', '')
            ->distinct()
            ->orderBy('locations')
            ->pluck('locations');

        $unique = [];
        foreach ($raw as $value) {
            $parts = preg_split('/[,;|\/]+/u', (string) $value) ?: [];
            foreach ($parts as $part) {
                $part = trim($part);
                if ($part === '') {
                    continue;
                }
                // Clave normalizada para evitar duplicados por mayúsculas; valor original trim.
                $key = mb_strtolower($part, 'UTF-8');
                if (! isset($unique[$key])) {
                    $unique[$key] = $part;
                }
            }
        }

        $list = array_values($unique);
        natcasesort($list);

        return array_values($list);
    }


    #[OA\Put(
        path: "api/v2/product/{product}/enable",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
        tags: ["Product"],
        parameters: [
            new OA\Parameter(
                name: "product",
                description: "The Enable identifier to update.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    ref: '#/components/schemas/Product'
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function enable(Product $product)
    {
        $product->update(['status' => true]);
        return $product;
    }



    #[OA\Put(
        path: "api/v2/product/{product}/disable",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
        tags: ["Product"],
        parameters: [
            new OA\Parameter(
                name: "product",
                description: "The Disable identifier to update.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    ref: '#/components/schemas/Product'
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function disable(Product $product)
    {
        if (!$product->inOpenedOrders()) {
            $product->update(['status' => false]);
        } else {
            $company = Company::find($product->company_id);
            $locale = ($company && $company->language_id == 2) ? 'es' : 'en';
            $message = Lang::get('cmp-products.product_cannot_disable_has_open_orders', [], $locale);
            return response()->json(['message' => $message], 400);
        }
        return $product;
    }

    #[OA\Put(
        path: "api/v2/product/status",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
        tags: ["Product"],

        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation, generates an excel file",
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function bulkStatusChange(Request $request) {
        $newStatus = $request->input('status');
        $productIds = ProductBulkSelection::resolveIds($request);
        if ($productIds->isEmpty()) {
            return 0;
        }
        return Product::whereIn('pro_id', $productIds)->update(['status' => $newStatus]);
    }

    /**
     * Elimina varios productos (selección de página o todos los filtrados).
     */
    public function bulkDelete(BulkProductDeleteRequest $request)
    {
        $companyId = (int) $request->input('company_id');
        $productIds = ProductBulkSelection::resolveIds($request);
        if ($productIds->isEmpty()) {
            return response()->json([
                'deleted' => 0,
                'failed' => 0,
                'message' => 'No products selected',
            ]);
        }

        $products = Product::query()
            ->where('company_id', $companyId)
            ->whereIn('pro_id', $productIds)
            ->get();

        $deleted = 0;
        $failed = [];

        foreach ($products as $product) {
            try {
                $this->authorize('delete', $product);
                $product->delete();
                $deleted++;
            } catch (\Throwable $e) {
                $failed[] = [
                    'pro_id' => $product->pro_id,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'deleted' => $deleted,
            'failed' => count($failed),
            'errors' => $failed,
        ]);
    }




    #[OA\Get(
        path: "api/v2/product/export",
        summary: "Export products to excel|csv|pdf.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Product"],

        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation, generates an excel|csv|pdf file",

            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function export(Request $request)
    {
        /** @var Builder $query */
        $query = $this->getFilteredModelQuery($request);
        if ($request->has('products') && $request->input('products') !== 'todos') {
            $products = $request->input('products');
            if (is_string($products)) {
                $products = json_decode($products, true);
            }
            if (!empty($products) && is_array($products)) {
                $query->whereIn('products.pro_id', $products);
            }
        }

        $typeFile = $request->input('type_file', 'excel');

        $writerType = \Maatwebsite\Excel\Excel::XLSX;
        $extension = 'xlsx';
        switch ($typeFile) {
            case 'csv':
                $writerType = \Maatwebsite\Excel\Excel::CSV;
                \Config::set('excel.exports.csv.delimiter', $request->input('delimiter', ','));
                $extension = 'csv';
                break;
            case 'pdf':
                $writerType = \Maatwebsite\Excel\Excel::MPDF;
                $extension = 'pdf';
                break;
        }
        $columns = [
            'products.name',
            'products.sku',
            'products.barcode',
            'products.sale_price',
            'products.fob_price',
            'products.purchase_price',
            'products.stock',
            'products.minimum_stock',
            'products.notify_minimum_stock',
            'products.tags',
            'products.locations',
            'products.descriptions',
            'products.status',
        ];

        $products = $query->select($columns)
            ->selectSub(
                "SELECT GROUP_CONCAT(categories.category_name SEPARATOR ',')
                 FROM category_product
                 LEFT JOIN categories ON category_product.category_id = categories.cat_id
                 WHERE category_product.product_id = products.pro_id",
                'categories'
            )
            ->selectSub(
                'SELECT units.unit FROM units WHERE units.unit_id = products.unit_id LIMIT 1',
                'unit_type'
            )
            ->get();

        $fileName = 'product_list' . date('Ymd');

        if ($typeFile === 'pdf') {
            return $this->generateProductsPdf($products, $fileName);
        }

        return Excel::download(
            new ProductsExport($products),
            "{$fileName}.{$extension}",
            $writerType
        );
    }

    private function generateProductsPdf(Collection $products, string $fileName)
    {
        $export = new ProductsExport($products);
        $rows = $products->map(static fn ($product) => $export->map($product));

        $companyId = (int) ($products->first()->company_id ?? 0);
        $mpdf = new \Mpdf\Mpdf([
            'orientation' => 'L',
            'tempDir' => \App\Support\UploadsPath::mpdfTempDir($companyId),
        ]);
        $html = view('pdf.products', [
            'headings' => $export->headings(),
            'rows' => $rows,
        ])->render();
        $mpdf->WriteHTML($html);

        $pdfContent = $mpdf->Output('', 'S');

        return response($pdfContent, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '.pdf"')
            ->header('Content-Length', strlen($pdfContent));
    }

    #[OA\Get(
        path: "api/v2/product/price-list",
        summary: "Gets the price list of the product, from the customer groups and sale_price",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Product"],
        parameters: [
            new OA\Parameter(
                name: "product_id",
                description: "The product id",
                in: "query",
                required: false
            ),new OA\Parameter(
                name: "price",
                description: "The base price to increase or decrease by group",
                in: "query",
                required: false
            ),new OA\Parameter(
                name: "company_id",
                description: "The company id",
                in: "query",
                required: true
            ),

        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation, generates an excel|csv|pdf file",

            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                              properties: [
                                              new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                                          ]
                          )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                              properties: [
                                              new OA\Property(property: "message", type: "string", example: "Resource not found")
                                          ]
                          )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                              properties: [
                                              new OA\Property(property: "message", type: "string", example: "Internal server error")
                                          ]
                          )
            )
        ]
    )]
    public function getPriceList(Request $request)
    {
        $companyId = $request->input('company_id');
        $productId = $request->input('product_id');
        $basePrice = $request->input('price', 0);
        /** @var Product $product */
        $product = Product::where('pro_id', $productId)->first();
        if(!isset($product)) {
            $product = new Product();
            $product->sale_price = $basePrice;
        }

        $groups = CustomerGroup::where('company_id', $companyId)
            ->where('group_status', 1)
            ->get();
        foreach($groups as $group) {
            /** @var  CustomerGroup $group */

            $group->sale_price_recalculado = $product->getPriceForGroup($group);

            $percentageType = $group->percentage_on_price ?? null;
            if ($percentageType === null || $percentageType === '' || $percentageType === 'nothing') {
                $group->labelTipoPorcentaje = '';
            } else {
                $increaseDecreaseLabel = TranslationController::t(
                    $group->isDecrease() ? 'cmp-products.decrease' : 'cmp-products.increase'
                );
                $group->labelTipoPorcentaje = "({$increaseDecreaseLabel}) {$group->percent_price_amount} %";
            }
        }
        return response()->json($groups->values());
    }

    protected function insertImage(Product $product, $image, $position=null) {

    }

    public function addImage(Product $product, Request $request) {
        if($request->has('position'))
        {
            $this->insertImage($product, $request->file('image'), $request->input('position'));
        }
    }

    public function companyInventoryList(Request $request, int $companyId, ProductInventoryMvcService $service)
    {
        $page = max(1, (int) $request->input('page', 1));
        $perPage = max(10, min(100, (int) $request->input('per_page', 25)));

        $paginator = $service->paginatedList(
            $companyId,
            [
                'search' => $request->input('search', $request->input('q', '')),
                'stock_filter' => $request->input('stock_filter', 'all'),
                'category_ids' => $request->input('category_ids', $request->input('categories', [])),
                'sort_by' => $request->input('sort_by', 'sku'),
                'sort_dir' => $request->input('sort_dir', 'asc'),
            ],
            $page,
            $perPage
        );

        return response()->json($paginator);
    }

    public function companyInventoryExport(Request $request, int $companyId, ProductInventoryMvcService $service)
    {
        // Listados grandes (miles de filas): PDF/Excel pueden superar el max_execution_time por defecto.
        set_time_limit(0);
        if (function_exists('ini_set')) {
            @ini_set('memory_limit', '512M');
        }

        $typeFile = (string) $request->input('type_file', 'excel');
        $rows = $service->exportRows($companyId, [
            'search' => $request->input('search', $request->input('q', '')),
            'stock_filter' => $request->input('stock_filter', 'all'),
            'category_ids' => $request->input('category_ids', $request->input('categories', [])),
        ]);

        $headings = $request->input('headings', [
            'SKU',
            'Name',
            'Sale price',
            'Inventory',
            'Categories',
        ]);
        if (! is_array($headings) || $headings === []) {
            $headings = ['SKU', 'Name', 'Sale price', 'Inventory', 'Categories'];
        }

        $includeRealInventory = $typeFile === 'pdf';
        $fileName = 'product_inventory_'.date('Ymd');
        $export = new \App\Exports\ProductInventoryExport($rows, array_values($headings), $includeRealInventory);

        if ($typeFile === 'pdf') {
            $mapped = $rows->map(static fn ($row) => $export->map($row));
            $company = \App\Models\Company::query()->find($companyId);
            $pdfLabels = $request->input('pdf_labels', []);
            if (! is_array($pdfLabels)) {
                $pdfLabels = [];
            }

            $realInventoryHeading = '';
            if ($includeRealInventory && count($headings) >= 5) {
                $realInventoryHeading = (string) ($headings[4] ?? '');
            }

            $pdfContent = $this->buildInventoryPdfContent(
                array_values($headings),
                $mapped,
                $pdfLabels,
                $company,
                $realInventoryHeading
            );

            return response($pdfContent, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="'.$fileName.'.pdf"')
                ->header('Content-Length', (string) strlen($pdfContent));
        }

        $writerType = \Maatwebsite\Excel\Excel::XLSX;
        $extension = 'xlsx';
        if ($typeFile === 'csv') {
            $writerType = \Maatwebsite\Excel\Excel::CSV;
            \Config::set('excel.exports.csv.delimiter', $request->input('delimiter', ','));
            $extension = 'csv';
        }

        return \Maatwebsite\Excel\Facades\Excel::download($export, "{$fileName}.{$extension}", $writerType);
    }

    /**
     * Genera el PDF de inventario escribiendo el HTML en fragmentos (evita límite pcre.backtrack_limit).
     *
     * @param list<string> $headings
     * @param \Illuminate\Support\Collection<int, array<int, string|float>> $rows
     * @param array<string, mixed> $pdfLabels
     */
    private function buildInventoryPdfContent(
        array $headings,
        \Illuminate\Support\Collection $rows,
        array $pdfLabels,
        ?\App\Models\Company $company,
        string $realInventoryHeading
    ): string {
        $companyId = (int) ($company?->company_id ?? 0);
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'L',
            'tempDir' => \App\Support\UploadsPath::mpdfTempDir($companyId),
            // Tablas grandes: mode simple acelera mucho el render.
            'simpleTables' => true,
            'packTableData' => true,
        ]);
        $mpdf->shrink_tables_to_fit = 1;

        $viewData = [
            'headings' => $headings,
            'labels' => $pdfLabels,
            'company' => [
                'name' => (string) ($company->name ?? ''),
                'email' => (string) ($company->email ?? ''),
                'phone' => (string) ($company->phone_number ?? $company->cell_number ?? ''),
                'address' => trim((string) ($company->full_address ?? $company->address ?? '')),
            ],
            'logoDataUrl' => $this->resolveInventoryPdfLogo($company),
            'printDate' => DisplayDateFormat::formatDateTime(now()),
        ];

        $mpdf->WriteHTML(view('pdf.inventory_head', $viewData)->render());

        foreach ($rows->chunk(250) as $chunk) {
            $mpdf->WriteHTML(view('pdf.inventory_rows', [
                'rows' => $chunk,
                'headings' => $headings,
                'realInventoryHeading' => $realInventoryHeading,
            ])->render());
        }

        $mpdf->WriteHTML('</tbody></table></body></html>');

        return $mpdf->Output('', 'S');
    }

    /**
     * Normaliza category_id del request a lista de IDs enteros.
     * Acepta array, CSV "1,2,3" o un entero suelto. Ignora nombres.
     *
     * @param  mixed  $raw
     * @return list<int>
     */
    private function normalizeCategoryIds($raw): array
    {
        if ($raw === null || $raw === '') {
            return [];
        }

        if (is_int($raw) || (is_string($raw) && ctype_digit(trim($raw)))) {
            $id = (int) $raw;

            return $id > 0 ? [$id] : [];
        }

        if (is_string($raw)) {
            $raw = preg_split('/\s*,\s*/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        }

        if (! is_array($raw)) {
            return [];
        }

        $ids = [];
        foreach ($raw as $item) {
            if (is_int($item) || (is_string($item) && ctype_digit(trim($item)))) {
                $id = (int) $item;
                if ($id > 0) {
                    $ids[] = $id;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Sincroniza grupos/subgrupos de categoría del producto (M2M + columnas denormalizadas).
     */
    private function syncProductCategoryGroups(Product $product, $request, int $companyId): void
    {
        $groupIds = $this->normalizeCategoryIds($request->input('category_group_ids', $request->input('category_group_id')));
        $subgroupIds = $this->normalizeCategoryIds($request->input('category_subgroup_ids', $request->input('category_subgroup_id')));

        $validGroupIds = CategoryGroup::query()
            ->where('company_id', $companyId)
            ->whereIn('group_id', $groupIds ?: [0])
            ->pluck('group_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $validSubgroupIds = CategorySubgroup::query()
            ->where('company_id', $companyId)
            ->whereIn('subgroup_id', $subgroupIds ?: [0])
            ->pluck('subgroup_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        // Si hay subgrupos, incluir automáticamente sus grupos padres.
        if ($validSubgroupIds !== []) {
            $parentGroupIds = CategorySubgroup::query()
                ->where('company_id', $companyId)
                ->whereIn('subgroup_id', $validSubgroupIds)
                ->pluck('group_id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $validGroupIds = array_values(array_unique(array_merge($validGroupIds, $parentGroupIds)));
        }

        $product->categoryGroups()->sync($validGroupIds);
        $product->categorySubgroups()->sync($validSubgroupIds);

        $product->category_group_id = $validGroupIds[0] ?? null;
        $product->category_subgroup_id = $validSubgroupIds[0] ?? null;
        $product->save();
    }

    /**
     * Logo de empresa en base64 para PDF de inventario.
     */
    private function resolveInventoryPdfLogo(?\App\Models\Company $company): ?string
    {
        if ($company && ! empty($company->image)) {
            $img = \App\Models\Image::find($company->image);
            if ($img) {
                try {
                    $storage = $img->getStorage();
                    $path = $img->path ?? ($img->company_id.'/'.$img->name);
                    if ($storage->exists($path)) {
                        $content = $storage->get($path);
                        $mime = $img->mime_type ?? 'image/png';

                        return 'data:'.$mime.';base64,'.base64_encode($content);
                    }
                } catch (\Throwable $e) {
                    // fallback to default
                }
            }
        }
        try {
            $content = \Illuminate\Support\Facades\Storage::disk()->get('images/logos/logo.png');

            return 'data:image/png;base64,'.base64_encode($content);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function companyInventoryMovements(int $companyId, int $proId, ProductInventoryMvcService $service)
    {
        $result = $service->productMovements($companyId, $proId);

        if ($result['product'] === null) {
            return response()->json(['message' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json($result);
    }

    /**
     * Obtiene las especificaciones (pares nombre/valor) de un producto.
     */
    public function getSpecifications(int $productId, Request $request): JsonResponse
    {
        $companyId = (int) $request->input('company_id', 0);
        $query = Product::query()->where('pro_id', $productId);
        if ($companyId > 0) {
            $query->where('company_id', $companyId);
        }
        $product = $query->firstOrFail();

        return response()->json([
            'success' => true,
            'pro_id' => (int) $product->pro_id,
            'name' => (string) ($product->name ?? ''),
            'sku' => (string) ($product->sku ?? ''),
            'specifications' => $this->normalizeSpecifications($product->specifications),
        ]);
    }

    /**
     * Guarda las especificaciones de un producto.
     */
    public function updateSpecifications(int $productId, Request $request): JsonResponse
    {
        $companyId = (int) $request->input('company_id', 0);
        if ($companyId < 1) {
            return response()->json(['success' => false, 'message' => 'company_id required'], 422);
        }

        $product = Product::query()
            ->where('pro_id', $productId)
            ->where('company_id', $companyId)
            ->firstOrFail();

        $specs = $this->normalizeSpecifications($request->input('specifications', []));
        $product->specifications = $specs;
        $product->save();

        return response()->json([
            'success' => true,
            'pro_id' => (int) $product->pro_id,
            'specifications' => $specs,
        ]);
    }

    /**
     * Asigna imagen IA a productos enabled sin foto (máx. N por petición).
     */
    public function assignAiImages(Request $request, \App\Services\ProductAiImageService $service): JsonResponse
    {
        $companyId = (int) $request->input('company_id', 0);
        if ($companyId < 1) {
            return response()->json(['success' => false, 'message' => 'company_id required'], 422);
        }

        if (! $service->isEnabled()) {
            return response()->json([
                'success' => false,
                'code' => 'ai_image_not_configured',
                'message' => 'AI image assignment is not configured',
            ], 503);
        }

        $ids = $request->input('product_ids', []);
        if (! is_array($ids)) {
            $ids = [];
        }

        $result = $service->assignForProducts($companyId, $ids);

        return response()->json([
            'success' => true,
            'assigned' => $result['assigned'],
            'skipped' => $result['skipped'],
            'failed' => $result['failed'],
        ]);
    }

    /**
     * @param  mixed  $raw
     * @return list<array{name: string, value: string}>
     */
    private function normalizeSpecifications($raw): array
    {
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? $row['label'] ?? $row['key'] ?? ''));
            $value = trim((string) ($row['value'] ?? ''));
            if ($name === '' && $value === '') {
                continue;
            }
            $out[] = [
                'name' => mb_substr($name, 0, 120),
                'value' => mb_substr($value, 0, 500),
            ];
            if (count($out) >= 40) {
                break;
            }
        }

        return $out;
    }


}

<?php

namespace App\Http\Controllers;

use App\Exports\CategoriesExport;
use App\Http\Requests\BulkCategoryDeleteRequest;
use App\Http\Requests\ExportCategory;
use App\Http\Requests\ImportCategory;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryImageRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use App\Models\CategoryProduct;
use App\Models\Image;
use App\Models\Product;
use App\Services\FullVendorCatalogService;
use App\Support\ProductBulkSelection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel as ExcelAlias;
use Maatwebsite\Excel\Facades\Excel;
use OpenApi\Attributes as OA;

class CategoryController extends Controller
{
    protected $resourceRoute = 'category';
    protected $defaultOrderProperty = 'category_default';
    protected $defaultOrderDirection = 'DESC';

    /**
     * La FK `image` es necesaria para eager-load de image / imageRecord con props parciales.
     *
     * @param string|array<int, string> $properties
     * @return array<int, string>
     */
    protected function getColumns($properties): array
    {
        $columns = parent::getColumns($properties);
        if ($columns === ['*']) {
            return $columns;
        }

        $relations = request()->input('relations', []);
        $needsImageFk = is_array($relations) && (
            in_array('image', $relations, true)
            || in_array('imageRecord', $relations, true)
        );
        if ($needsImageFk && ! in_array('image', $columns, true)) {
            $columns[] = 'image';
        }

        return $columns;
    }
    
    /**
     * Sobrescribir getFilteredModelQuery para agregar filtro de company_id automáticamente
     */
    protected function getFilteredModelQuery(Request $request = null)
    {
        if (!$request) {
            $request = request();
        }
        
        // Obtener el usuario autenticado
        $user = $request->user();
        
        if ($user) {
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
            
            // Si tenemos company_id, agregarlo al request para el filtro
            if ($companyId) {
                $request->merge(['company' => $companyId]);
            }
        }
        
        return parent::getFilteredModelQuery($request);
    }

    #[OA\Get(
        path: "api/v2/category",
        summary: "Display a listing of the Category.",
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
                        items: new OA\Items(ref: '#/components/schemas/Category')
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
        return parent::index();
    }

    /**
     * Obtener todas las categorías sin paginación de la compañía del usuario logueado
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Get(
        path: "api/v2/category/all",
        summary: "Obtener todas las categorías sin paginación de la compañía del usuario logueado",
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
                description: "Lista de todas las categorías de la compañía del usuario",
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Category')
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
        
        // imageRecord no pisa la FK `image` (UUID) al serializar.
        $request->merge(['relations' => ['imageRecord']]);
        
        // Usar getModelList que ya maneja el parámetro 'all' y el filtro de company
        // Cuando all=true, devuelve una colección directa (sin paginación)
        // Cuando hay paginación, devuelve un objeto con data, meta, links
        $response = $this->getModelList($request);
        
        $addImageUrl = fn ($category) => $this->categoryArrayWithImageUrl($category);

        // Si la respuesta es una colección directa (all=true, sin paginación)
        if ($response instanceof \Illuminate\Support\Collection || is_array($response)) {
            $categories = $response instanceof \Illuminate\Support\Collection ? $response : collect($response);
            
            $categoriesWithImageUrl = $categories->map($addImageUrl);
            
            // Mantener la misma estructura que /api/v2/category: envolver en {'data': [...]}
            return response()->json([
                'data' => $categoriesWithImageUrl->values()->all()
            ]);
        }
        // Si la respuesta tiene estructura paginada (con data, meta, links)
        elseif (is_object($response) && isset($response->data)) {
            $data = $response->data;
            $dataWithImageUrl = collect($data)->map($addImageUrl);
            
            // Mantener la estructura original con meta y links
            $response->data = $dataWithImageUrl->values()->all();
            return $response;
        }
        // Si la respuesta es un array con estructura paginada
        elseif (is_array($response) && isset($response['data'])) {
            $data = $response['data'];
            $dataWithImageUrl = collect($data)->map($addImageUrl);
            
            // Mantener la estructura original con meta y links
            $response['data'] = $dataWithImageUrl->values()->all();
            return response()->json($response);
        }
        // Fallback: tratar como colección
        else {
            $categories = collect($response);
            $categoriesWithImageUrl = $categories->map($addImageUrl);
            
            return response()->json([
                'data' => $categoriesWithImageUrl->values()->all()
            ]);
        }
    }

    /**
     * Igual que category/all, pero ordenadas como el catálogo FullVendor (default_catalog=1).
     */
    #[OA\Get(
        path: "api/v2/category/catalog",
        operationId: "categoryCatalogOrdered",
        summary: "Categorías de la compañía en el orden del catálogo FullVendor (por defecto)",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
        tags: ["Product"],
        parameters: [
            new OA\Parameter(
                name: 'company_id',
                in: 'query',
                required: false,
                description: 'ID de compañía (si no se envía, se usa la del usuario autenticado)',
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'company',
                in: 'query',
                required: false,
                description: 'Alias de company_id',
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Lista de categorías ordenadas según order_categories del catálogo FullVendor",
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Category')
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
    public function catalog(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $companyId = $request->input('company_id') ?? $request->input('company');
        if (! $companyId) {
            $userCompany = $user->companiesForRole('manager')->first();
            if (! $userCompany) {
                $userCompany = $user->companies()->first();
            }
            $companyId = $userCompany?->company_id;
        }

        $companyId = (int) $companyId;
        if ($companyId <= 0) {
            return response()->json([
                'message' => 'No se pudo determinar la compañía. Asegúrese de estar asociado a una compañía.',
            ], 422);
        }

        $categories = app(FullVendorCatalogService::class)
            ->categoriesInCatalogOrder($companyId);

        $enriched = $categories
            ->map(fn ($category) => $this->categoryArrayWithImageUrl($category))
            ->values()
            ->all();

        return response()->json(['data' => $enriched]);
    }

    /**
     * @param  \App\Models\Category|array<string, mixed>|object  $category
     * @return array<string, mixed>
     */
    private function categoryArrayWithImageUrl($category): array
    {
        $categoryArray = is_array($category) ? $category : $category->toArray();

        $imageUrl = null;

        if (isset($categoryArray['imageRecord']) && is_array($categoryArray['imageRecord']) && isset($categoryArray['imageRecord']['uuid'])) {
            $imageUuid = $categoryArray['imageRecord']['uuid'];
            $ext = strtolower((string) ($categoryArray['imageRecord']['extension'] ?? Image::storageExtension()));
            if ($ext === 'jpeg') {
                $ext = 'jpg';
            }
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                $ext = 'webp';
            }
            $imageUrl = '/images/' . $imageUuid . '.' . $ext;
        } elseif (isset($categoryArray['image']) && is_array($categoryArray['image']) && isset($categoryArray['image']['uuid'])) {
            $imageUuid = $categoryArray['image']['uuid'];
            $ext = strtolower((string) ($categoryArray['image']['extension'] ?? Image::storageExtension()));
            if ($ext === 'jpeg') {
                $ext = 'jpg';
            }
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                $ext = 'webp';
            }
            $imageUrl = '/images/' . $imageUuid . '.' . $ext;
        } elseif (! empty($categoryArray['image_uuid']) && is_string($categoryArray['image_uuid'])) {
            $imageUrl = '/images/' . $categoryArray['image_uuid'];
        } elseif (! empty($categoryArray['image']) && is_string($categoryArray['image'])) {
            $imageUrl = '/images/' . $categoryArray['image'];
        }

        $categoryArray['imageurl'] = $imageUrl;
        $categoryArray['has_photo'] = $imageUrl !== null && $imageUrl !== '';
        $categoryArray['has_image'] = $categoryArray['has_photo'];
        $categoryArray['image_placeholder'] = '/images/no-image';

        return $categoryArray;
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: "api/v2/category",
        summary: "Store a newly created resource in storage.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
                requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/CategoryRequest'
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
                    ref: '#/components/schemas/Category'
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
    public function store(StoreCategoryRequest $request)
    {
        try {
            $imageUuid = $this->handleImageUpload($request, $request->company_id);

            $categoryAttributes = [
                'company_id' => $request->company_id,
                'category_name' => $request->category_name,
                'image' => $imageUuid,
            ];

            $resource = $this->createFromAttributes($categoryAttributes);

            $this->syncFullVendorCatalog((int) $request->company_id);

            return $resource;
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Sincroniza el catálogo FullVendor de la empresa con sus categorías activas.
     *
     * Centraliza en la API la lógica que antes hacía el MVC con consultas
     * directas a BD (Helper::creat_fullvendorcatalog). Cualquier fallo se
     * registra sin interrumpir la operación principal sobre la categoría.
     */
    private function syncFullVendorCatalog(?int $companyId): void
    {
        if (!$companyId) {
            return;
        }

        try {
            app(FullVendorCatalogService::class)->sync($companyId);
        } catch (\Throwable $e) {
            \Log::error('CategoryController::syncFullVendorCatalog - Error sincronizando catálogo FullVendor', [
                'company_id' => $companyId,
                'error'      => $e->getMessage(),
            ]);
        }
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: "api/v2/category/{category}",
        summary: "Display the specified Category.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Product"],
        parameters: [
            new OA\Parameter(
                name: "category",
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
    public function show($category)
    {
        return $this->getMainModelInstance($category);
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: "api/v2/category/{category}",
        summary: "Updates the specified Category.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
                requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/CategoryRequest'
            )
        ),
        tags: ["Product"],
        parameters: [
            new OA\Parameter(
                name: "category",
                description: "The Category identifier to update.",
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
    public function update(UpdateCategoryRequest $request, $category)
    {
        $validated = $request->validated();
        $category = Category::findOrFail($category);

        if ((int) $category->category_default === 1) {
            return response()->json(['message' => 'Default category cannot be edited'], 400);
        }

        $check = $this->canChangeStatus($category, $validated['category_status'] ?? $category->category_status);
        if ($check !== true) {
            return response()->json(['message' => $check], 400);
        }

        $resource = $this->updateModel($category, $validated);

        $this->syncFullVendorCatalog((int) $category->company_id);

        return $resource;
    }

    public function updateImage(UpdateCategoryImageRequest $request, $category)
    {
        \Log::info('==========================================');
        \Log::info('CategoryController::updateImage - MÉTODO LLAMADO');
        \Log::info('CategoryController::updateImage - Category ID recibido: ' . $category);
        \Log::info('CategoryController::updateImage - Request hasFile image: ' . ($request->hasFile('image') ? 'yes' : 'no'));
        \Log::info('CategoryController::updateImage - Request all: ' . json_encode($request->all()));
        \Log::info('CategoryController::updateImage - Request files: ' . json_encode($request->allFiles()));
        \Log::info('CategoryController::updateImage - Request input company_id: ' . ($request->input('company_id') ?? 'null'));
        
        $category = Category::findOrFail($category);

        if ((int) $category->category_default === 1) {
            return response()->json(['message' => 'Default category cannot be edited'], 400);
        }

        \Log::info('CategoryController::updateImage - Category encontrada: ' . $category->cat_id);
        \Log::info('CategoryController::updateImage - Imagen actual de la categoría: ' . ($category->image ?? 'null'));

        // PASO 1: SIEMPRE eliminar la imagen anterior y su registro (si existe)
        // Esto se hace PRIMERO, antes de cualquier otra operación
        $oldImageUuid = $category->image;
        $imageDeleted = false;
        
        if ($oldImageUuid) {
            \Log::info('CategoryController::updateImage - ELIMINANDO imagen anterior y su registro: ' . $oldImageUuid);
            try {
                $this->deleteCategoryImage($oldImageUuid);
                $imageDeleted = true;
                \Log::info('CategoryController::updateImage - Imagen anterior eliminada exitosamente de MinIO y base de datos');
            } catch (\Exception $e) {
                \Log::error('CategoryController::updateImage - Error al eliminar imagen anterior: ' . $e->getMessage());
                // Continuar con el proceso aunque falle la eliminación
            }
        } else {
            \Log::info('CategoryController::updateImage - No hay imagen anterior para eliminar');
        }

        // PASO 2: Si hay nueva imagen, insertarla y obtener UUID
        $imageUuid = null;
        if ($request->hasFile('image')) {
            \Log::info('CategoryController::updateImage - Nueva imagen detectada, insertando...');
            try {
                $imageUuid = $this->handleImageUpload($request, $category->company_id, null); // null porque ya eliminamos la anterior
                \Log::info('CategoryController::updateImage - Nueva imagen UUID obtenida: ' . $imageUuid);
            } catch (\Exception $e) {
                \Log::error('CategoryController::updateImage - Error al subir nueva imagen: ' . $e->getMessage());
                // Si falla la subida, imageUuid queda en null (imagen eliminada pero no reemplazada)
            }
        } else {
            \Log::info('CategoryController::updateImage - No se subió nueva imagen, solo se eliminó la anterior');
        }

        // PASO 3: Actualizar la tabla categories en el campo "image" con el nuevo UUID (o null si no hay nueva)
        $category->update(['image' => $imageUuid]);
        \Log::info('CategoryController::updateImage - Campo image actualizado en la tabla categories con UUID: ' . ($imageUuid ?? 'null'));

        // NO usar load('image'): choca con la FK `categories.image` y al serializar
        // puede entrar en bucle (timeout 30s / cURL error 28 desde el MVC).
        $category->refresh();
        $category->load('imageRecord:uuid,name,path,extension,mime_type,disk,company_id');

        \Log::info('CategoryController::updateImage - Categoría actualizada exitosamente. Imagen eliminada: ' . ($imageDeleted ? 'sí' : 'no'));

        return response()->json([
            'success' => true,
            'message' => $imageUuid ? 'Category image updated successfully' : ($imageDeleted ? 'Category image removed successfully' : 'Category updated successfully'),
            'data' => [
                'cat_id' => (int) $category->cat_id,
                'category_name' => $category->category_name,
                'image' => $category->image,
                'image_uuid' => $category->image,
                'imageRecord' => $category->imageRecord,
            ],
        ]);
    }

    /**
     * Elimina una imagen de categoría (de MinIO y de la base de datos)
     * Este método SIEMPRE elimina la imagen y su registro de la tabla images
     */
    private function deleteCategoryImage($imageUuid)
    {
        if (!$imageUuid) {
            \Log::warning('CategoryController::deleteCategoryImage - UUID vacío, no hay nada que eliminar');
            return false;
        }

        \Log::info('CategoryController::deleteCategoryImage ==========================================');
        \Log::info('CategoryController::deleteCategoryImage - INICIANDO eliminación de imagen: ' . $imageUuid);
        
        $oldImage = Image::where('uuid', $imageUuid)->first();
        
        if (!$oldImage) {
            \Log::warning('CategoryController::deleteCategoryImage - ✗ Imagen NO encontrada en la base de datos con UUID: ' . $imageUuid);
            \Log::warning('CategoryController::deleteCategoryImage - El UUID existe en categories.image pero no en images.uuid');
            return false;
        }
        
        \Log::info('CategoryController::deleteCategoryImage - ✓ Imagen encontrada en BD');
        \Log::info('CategoryController::deleteCategoryImage - Detalles de la imagen:');
        \Log::info('CategoryController::deleteCategoryImage -   - UUID: ' . $oldImage->uuid);
        \Log::info('CategoryController::deleteCategoryImage -   - Name: ' . $oldImage->name);
        \Log::info('CategoryController::deleteCategoryImage -   - Path: ' . ($oldImage->path ?? 'null'));
        \Log::info('CategoryController::deleteCategoryImage -   - Company ID: ' . ($oldImage->company_id ?? 'null'));
        \Log::info('CategoryController::deleteCategoryImage -   - Disk: ' . ($oldImage->disk ?? 'null'));
        
        try {
            // PASO 1: Eliminar de MinIO (almacenamiento)
            \Log::info('CategoryController::deleteCategoryImage - PASO 1: Eliminando de MinIO...');
            $oldImage->deleteFromStorage();
            \Log::info('CategoryController::deleteCategoryImage - ✓ PASO 1 COMPLETADO: Imagen eliminada de MinIO');
            
            // PASO 2: Eliminar de la base de datos (registro en tabla images)
            \Log::info('CategoryController::deleteCategoryImage - PASO 2: Eliminando registro de la tabla images...');
            $deleted = $oldImage->delete();
            \Log::info('CategoryController::deleteCategoryImage - Resultado de delete(): ' . ($deleted ? 'true' : 'false'));
            
            if ($deleted) {
                \Log::info('CategoryController::deleteCategoryImage - ✓ PASO 2 COMPLETADO: Registro eliminado de la tabla images');
                \Log::info('CategoryController::deleteCategoryImage - ✓✓✓ ELIMINACIÓN COMPLETA: Imagen y registro eliminados exitosamente');
                \Log::info('CategoryController::deleteCategoryImage ==========================================');
                return true;
            } else {
                \Log::error('CategoryController::deleteCategoryImage - ✗ ERROR: No se pudo eliminar el registro de la BD');
                \Log::info('CategoryController::deleteCategoryImage ==========================================');
                return false;
            }
        } catch (\Exception $e) {
            \Log::error('CategoryController::deleteCategoryImage - ✗✗✗ EXCEPCIÓN al eliminar imagen: ' . $e->getMessage());
            \Log::error('CategoryController::deleteCategoryImage - Stack trace: ' . $e->getTraceAsString());
            \Log::info('CategoryController::deleteCategoryImage ==========================================');
            throw $e; // Lanzar excepción para que se maneje en el método llamador
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: "api/v2/category/{category}",
        summary: "Remove the specified Category.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Product"],
        parameters: [
            new OA\Parameter(
                name: "category",
                description: "The Category identifier to remove.",
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
    public function destroy($category): \Illuminate\Http\JsonResponse | Response
    {
        $category = Category::findOrFail($category);

        if ((int) $category->category_default === 1){
            return response()->json(['message' => 'Default category cannot be deleted'], 400);
        }

        if ($category->products()->exists()){
            return response()->json(['message' => 'Category has products and cannot be deleted'], 400);
        }

        $companyId = (int) $category->company_id;

        $response = $this->destroyResourceEmptyResponse($category);

        $this->syncFullVendorCatalog($companyId);

        return $response;
    }

    /**
     * Elimina varias categorías (omite default y las que tienen productos).
     */
    public function bulkDelete(BulkCategoryDeleteRequest $request)
    {
        $companyId = (int) $request->input('company_id');
        $categories = $this->getBulkBuilder($request)->get();

        $deleted = 0;
        $skipped = [];
        $failed = [];

        foreach ($categories as $category) {
            if ((int) $category->category_default === 1) {
                $skipped[] = [
                    'cat_id' => $category->cat_id,
                    'reason' => 'default',
                ];
                continue;
            }
            if ($category->products()->exists()) {
                $skipped[] = [
                    'cat_id' => $category->cat_id,
                    'reason' => 'has_products',
                ];
                continue;
            }

            try {
                $this->authorize('delete', $category);
                $category->delete();
                $deleted++;
            } catch (\Throwable $e) {
                $failed[] = [
                    'cat_id' => $category->cat_id,
                    'message' => $e->getMessage(),
                ];
            }
        }

        if ($deleted > 0) {
            $this->syncFullVendorCatalog($companyId);
        }

        return response()->json([
            'deleted' => $deleted,
            'skipped' => count($skipped),
            'failed' => count($failed),
            'skipped_items' => $skipped,
            'errors' => $failed,
        ]);
    }


    #[OA\Get(
        path: "api/v2/category/{categoryId}/hasProducts",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Product"],
        parameters: [
            new OA\Parameter(
                name: "categoryId",
                description: "The HasProduct identifier to retrieve.",
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
                        type: 'boolean',
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
    public function hasProducts($categoryId)
    {
        /** @var Category $category */
        $category = Category::where('cat_id', $categoryId)
            ->sole();

        return $category->products()->count() > 0;
    }

    #[OA\Get(
        path: "api/v2/category/{categoryId}/products",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Product"],
        parameters: [
            new OA\Parameter(
                name: "categoryId",
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
    public function products($categoryId) //with offset & limit?
    {
        $perPage = (int) request()->input('per_page', request()->input('perPage', $this->perPage));
        if (request()->input('all') == 1 || request()->boolean('all')) {
            $perPage = max($perPage, 80);
        }
        $perPage = max(1, min(200, $perPage > 0 ? $perPage : (int) $this->perPage));

        $query = $this->getMainModelInstance($categoryId)
                ->products()
                ->select(
                    'products.pro_id',
                    'products.category_id',
                    'products.name',
                    'products.sku',
                    'products.sale_price',
                    'products.stock',
                    'products.status',
                    'products.currency_type',
                    'products.category_group_id',
                    'products.category_subgroup_id',
                    // Necesario para default_image / misma resolución de foto que el listado de productos
                    'products.default_image_id'
                )
                ->whereIn('products.status', [1, '1'])
                ->with([
                    'default_image',
                    // Misma fuente que ProductController / listado MVC: product_images ordenado + images
                    'product_images.image',
                    'categoryGroups:group_id,group_name,category_id,sort_order',
                    'categorySubgroups:subgroup_id,subgroup_name,group_id,sort_order',
                    'productRating',
                ]);

        $companyId = (int) (\App\Models\Category::query()
            ->where('cat_id', $categoryId)
            ->value('company_id') ?? 0);
        if ($companyId > 0) {
            app(\App\Services\ProductRatingService::class)->ensureForCompany($companyId);
        }

        // Preview de catálogo: primero productos con grupo/subgrupo para poder armar la jerarquía.
        if (request()->input('catalog_preview') == 1 || request()->boolean('catalog_preview')) {
            $query->orderByRaw(
                '(CASE WHEN products.category_subgroup_id IS NULL OR products.category_subgroup_id = 0 OR products.category_subgroup_id = \'\' THEN 1 ELSE 0 END)'
            )->orderByRaw(
                '(CASE WHEN products.category_group_id IS NULL OR products.category_group_id = 0 OR products.category_group_id = \'\' THEN 1 ELSE 0 END)'
            )->orderBy('products.pro_id', 'DESC');
        } else {
            $query->orderBy('products.pro_id', 'DESC');
        }

        return $query->paginate($perPage);
    }

    #[OA\Put(
        path: "api/v2/category/{categoryId}/products",
        summary: "TODO: Add summary",
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
                name: "categoryId",
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
    public function assignProducts($categoryId)
    {
        $productIds = ProductBulkSelection::resolveIds(request());
        /** @var Category $category */
        $category = Category::where('cat_id', $categoryId)
            ->sole();
        $category->products()->syncWithoutDetaching($productIds->all());
    }

    #[OA\Delete(
        path: "api/v2/category/{categoryId}/products",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Product"],
        parameters: [
            new OA\Parameter(
                name: "categoryId",
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
    public function removeProducts($categoryId)
    {
        $productIds = ProductBulkSelection::resolveIds(request());
        if ($productIds->isEmpty()) {
            return 0;
        }
        $deleted = CategoryProduct::query()
            ->where('category_id', $categoryId)
            ->whereIn('product_id', $productIds)
            ->forceDelete();
        return $deleted;
    }

    public function enable($categoryId)
    {
        $category = Category::findOrFail($categoryId);

        if ($category->category_status) {
            return $category;
        }

        $category->update(['category_status' => true]);

        $this->syncFullVendorCatalog((int) $category->company_id);

        return $category;
    }

    public function disable($categoryId)
    {
        $category = Category::findOrFail($categoryId);

        $check = $this->canChangeStatus($category, false);

        if ($check !== true) {
            return response()->json([
                'message' => $check,
                'category_name' => $category->category_name,
            ], 400);
        }

        $category->update(['category_status' => false]);

        $this->syncFullVendorCatalog((int) $category->company_id);

        return $category;
    }

    private function canChangeStatus(Category $category, $newStatus)
    {
        $newStatus = (bool) $newStatus;

        // Solo la categoría marcada como default (1) está protegida; null/0 no bloquean.
        if (!$newStatus && (int) $category->category_default === 1) {
            return 'Cannot change the status of the default category';
        }

        if ($category->category_status && !$newStatus && $category->products()->exists()) {
            return 'Cannot deactivate a category with associated products';
        }

        return true;
    }

    public function export(ExportCategory $request)
    {
        $builder = $this->getBulkBuilder($request);
        $categories = $builder->get();

        $export = new CategoriesExport($categories);
        $fileType = $request->file_type;
        $fileName = 'categories_list' . date('Ymd');

        return match ($fileType) {
            'excel' => Excel::download($export, $fileName . '.xlsx'),
            'csv'   => Excel::download($export, $fileName . '.csv', ExcelAlias::CSV),
            'pdf'   => $this->generatePdf($categories, $fileName),
            default => response()->json(['message' => 'Invalid file type'], 422),
        };
    }

    /**
     * @param Request $request
     * @return Builder|\Illuminate\Database\Query\Builder
     */
    private function getBulkBuilder(Request $request): \Illuminate\Database\Query\Builder|Builder
    {
        $builder = $this->getBuilder()->where('company_id', $request->input("company_id"));

        if ($request['all_checked'] === 'pagina') {
            $builder = $builder->whereIn('cat_id', $request['category_ids'] ?? []);
        } else {
            $filters = $request['filters'] ?? [];

            $builder = $builder->filter([
                'search' => isset($filters['search']) && $filters['search'] !== '' ? $filters['search'] : null,
                'status' => (isset($filters['status']) && trim($filters['status']) !== '') ? $filters['status'] : null,
            ]);
        }
        return $builder;
    }

    private function generatePdf($categories, string $fileName)
    {
        $companyId = 0;
        if ($categories instanceof \Illuminate\Support\Collection && $categories->isNotEmpty()) {
            $companyId = (int) ($categories->first()->company_id ?? 0);
        }

        $mpdf = new \Mpdf\Mpdf([
            'tempDir' => \App\Support\UploadsPath::mpdfTempDir($companyId),
        ]);
        $html = view('pdf.categories', compact('categories'))->render();
        $mpdf->WriteHTML($html);

        $pdfContent = $mpdf->Output('', 'S');

        return response($pdfContent, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="'.$fileName.'.pdf"')
            ->header('Content-Length', strlen($pdfContent));
    }

    public function import(ImportCategory $request)
    {
        $validated = $request->validated();

        $companyId = $validated['company_id'];
        $categories = $validated['categories'];

        $errors = [];
        foreach ($categories as $categoryName) {
            $exists = Category::where('category_name', $categoryName)->where('company_id', $companyId)->exists();

            if ($exists) {
                $errors[] = "Category '{$categoryName}' already exists for company.";
                continue;
            }

            try {
                Category::create([
                    'category_name' => $categoryName,
                    'company_id' => $companyId,
                ]);
            } catch (\Exception $e) {
                $errors[] = "The category could not be created '{$categoryName}'.";
            }
        }

        $this->syncFullVendorCatalog((int) $companyId);

        return empty($errors)
            ? response()->json(['message' => 'Categories imported successfully'])
            : response()->json(['errors' => $errors], 422);
    }

    private function handleImageUpload($request, $companyId, $oldImageUuid = null)
    {
        \Log::info('CategoryController::handleImageUpload - Verificando archivo');
        \Log::info('CategoryController::handleImageUpload - hasFile: ' . ($request->hasFile('image') ? 'yes' : 'no'));
        
        if (!$request->hasFile('image')) {
            \Log::warning('CategoryController::handleImageUpload - No se recibió archivo');
            return null;
        }

        $imageFile = $request->file('image');
        \Log::info('CategoryController::handleImageUpload - Archivo recibido: ' . $imageFile->getClientOriginalName());

        // Crear y guardar la nueva imagen (putContent normaliza tamaño/formato)
        $image = new Image();
        $image->company_id = $companyId;
        $image->name = Str::uuid() . '.' . Image::STORAGE_EXTENSION;
        $image->disk = Image::defaultDisk();
        $image->type = Image::STORAGE_MIME;
        $image->mime_type = Image::STORAGE_MIME;
        $image->extension = Image::STORAGE_EXTENSION;
        $image->original_name = $imageFile->getClientOriginalName();

        // putContent guarda según IMAGES_DISK (MinIO o directorio local)
        \Log::info('CategoryController::handleImageUpload - Guardando imagen', [
            'disk' => $image->disk,
            'company_id' => $companyId,
        ]);
        $image->putContent($imageFile->getContent(), $companyId, 'category');

        // Obtener el UUID de la nueva imagen
        $newImageUuid = $image->uuid;
        \Log::info('CategoryController::handleImageUpload - Nueva imagen creada con UUID: ' . $newImageUuid);

        return $newImageUuid;
    }

    public function addImageToCategory(UpdateCategoryImageRequest $request)
    {
        try {
            $imageFile = $request->file('image');

            $originalName = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);

            $category = Category::where('category_name', $originalName)->first();

            if (!$category) {
                return response()->json(['message' => "Category with name '$originalName' not found."], 404);
            }

            $imageUuid = $this->handleImageUpload($request, $category->company_id, $category->image);

            $category->update(['image' => $imageUuid]);

            return $category;

        } catch (\Throwable $e) {
            return response()->json(['message' => 'Error al procesar la imagen: ' . $e->getMessage()], 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductImageRequest;
use App\Http\Requests\UpdateProductImageRequest;
use App\Models\Image;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

class ProductImageController extends Controller
{
    protected $resourceRoute = 'productimage';

    #[OA\Get(
        path: "api/v2/productimage",
        summary: "Display a listing of the ProductImage.",
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
                        items: new OA\Items(ref: '#/components/schemas/Productimage')
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
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: "api/v2/productimage",
        summary: "Store a newly created resource in storage.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
                requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/ProductimageRequest'
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
                    ref: '#/components/schemas/Productimage'
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
    public function store(StoreProductImageRequest $request): \Illuminate\Http\Resources\Json\JsonResource
     {

        return $this->createFromAttributes($request->toArray());
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: "api/v2/productimage/{productimage}",
        summary: "Display the specified ProductImage.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Product"],
        parameters: [
            new OA\Parameter(
                name: "productimage",
                description: "The Productimage identifier to retrieve.",
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
                        ref: '#/components/schemas/Productimage'
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
    public function show(ProductImage $productimage)
    {
        return $productimage;
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: "api/v2/productimage/{productimage}",
        summary: "Updates the specified ProductImage.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
                requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/ProductimageRequest'
            )
        ),
        tags: ["Product"],
        parameters: [
            new OA\Parameter(
                name: "productimage",
                description: "The Productimage identifier to update.",
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
                    ref: '#/components/schemas/Productimage'
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
    public function update(UpdateProductImageRequest $request, ProductImage $productimage)
    {
        $attributes = $request->toArray();
        $result = $this->updateModel($productimage, $attributes);

        // Fix the order of images to ensure sequential img_order values
        if ($productimage->product_id) {
            $this->fixImagesOrder($productimage->product_id);
        }

        return $result;
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: "api/v2/productimage/{productimage}",
        summary: "Remove the specified ProductImage.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Product"],
        parameters: [
            new OA\Parameter(
                name: "productimage",
                description: "The Productimage identifier to remove.",
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
    public function destroy(ProductImage $productimage): ?Response
    {
        // Store the product ID before deleting the image
        $productId = $productimage->product_id;

        // Delete the database record
        $response = $this->destroyResourceEmptyResponse($productimage);

        // Fix the order of images to ensure sequential img_order values
        if ($productId) {
            $this->fixImagesOrder($productId);
        }

        return $response;
    }

    #[OA\Get(
        path: "api/v2/product/{productId}/images",
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
                description: "The Image identifier to retrieve.",
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
                        ref: '#/components/schemas/Productimage'
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
    public function getByProduct($productId)
    {
        return ProductImage::with('image')
            ->where('product_id', $productId)
            ->orderBy('img_order')
            ->orderBy('img_id')
            ->get();
    }

    #[OA\Get(
        path: "api/v2/productimage/product/{productId}/first",
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
                description: "The First identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                        ref: '#/components/schemas/Productimage'
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
    public function first($productId)
    {
        return $this->getBuilder()
            ->select('images')
            ->where('product_id', $productId)
            ->first();
    }


    /**
     * Fix the order of images for a product to ensure sequential img_order values
     *
     * This method checks if there are gaps in the img_order sequence and
     * reassigns img_order values to be sequential (1, 2, 3, ...) if needed.
     *
     * @param int $productId The ID of the product to fix image order for
     * @return bool True if changes were made, false if no changes were needed
     */
    protected function fixImagesOrder($productId)
    {
        // Get all images for the product ordered by img_order
        $images = ProductImage::where('product_id', $productId)
            ->orderBy('img_order')
            ->get();

        if ($images->isEmpty()) {
            return false; // No images to fix
        }

        $changesNeeded = false;
        $expectedOrder = 1;

        // Check if there are gaps in the sequence
        foreach ($images as $image) {
            if ($image->img_order != $expectedOrder) {
                $changesNeeded = true;
                break;
            }
            $expectedOrder++;
        }

        // If gaps exist, reassign img_order values to be sequential
        if ($changesNeeded) {
            $newOrder = 1;
            foreach ($images as $image) {
                $image->img_order = $newOrder++;
                $image->save();
            }
            // Mantener images.name alineado con el slot visual.
            $this->recalculateImageNamesForProduct($productId);
            return true; // Changes were made
        }

        // Aunque img_order no haya cambiado, asegurar que images.name está alineado
        // (cubre casos donde el SKU del producto cambió o nombres quedaron desfasados).
        $this->recalculateImageNamesForProduct($productId);

        return false; // No changes needed
    }

    /**
     * Recalcula `images.name` para todas las imágenes del producto a partir
     * del `img_order` actual en `product_images`. La convención es:
     *   slot 1 → {SKU}.{ext}
     *   slot 2 → {SKU}_PIC2.{ext}
     *   slot 3 → {SKU}_PIC3.{ext}
     *   slot N → {SKU}_PIC{N}.{ext}
     *
     * No mueve el archivo físico en MinIO (`images.path` y el objeto en storage
     * conservan el nombre original con que se subió); solo actualiza el campo
     * `images.name` que es el que se muestra en la UI. La imagen se sirve por
     * `uuid` + `images.path`, por lo que no hay impacto en la visualización.
     *
     * @param int $productId
     * @return int Cantidad de imágenes cuyo `name` fue actualizado.
     */
    protected function recalculateImageNamesForProduct($productId): int
    {
        $product = Product::find($productId);
        if (! $product) {
            return 0;
        }

        $sku = strtoupper(trim((string) ($product->sku ?? '')));
        // El renombrado al subir hoy también elimina el '/' del SKU.
        $sku = str_replace('/', '', $sku);
        if ($sku === '') {
            // Sin SKU no hay base estable para renombrar.
            return 0;
        }

        $productImages = ProductImage::with('image')
            ->where('product_id', $productId)
            ->orderBy('img_order')
            ->get();

        if ($productImages->isEmpty()) {
            return 0;
        }

        $changed = 0;
        foreach ($productImages as $pi) {
            /** @var Image|null $img */
            $img = $pi->image;
            if (! $img) {
                continue;
            }

            $slot = max(1, (int) ($pi->img_order ?? 1));
            $extension = $this->resolveImageExtension($img);
            $expectedName = $this->buildExpectedImageName($sku, $slot, $extension);

            if ((string) $img->name !== $expectedName) {
                $img->name = $expectedName;
                $img->save();
                $changed++;
            }
        }

        if ($changed > 0) {
            Log::info('Image names recalculated to match slot order', [
                'product_id'      => $productId,
                'sku'             => $sku,
                'images_changed'  => $changed,
                'total_images'    => $productImages->count(),
            ]);
        }

        return $changed;
    }

    /**
     * Devuelve la extensión (sin punto) usando la mejor fuente disponible.
     */
    private function resolveImageExtension(Image $img): string
    {
        $ext = trim((string) ($img->extension ?? ''));
        if ($ext !== '') {
            return ltrim($ext, '.');
        }
        $fromName = pathinfo((string) ($img->name ?? ''), PATHINFO_EXTENSION);
        if ($fromName !== '') {
            return $fromName;
        }
        $fromOriginal = pathinfo((string) ($img->original_name ?? ''), PATHINFO_EXTENSION);
        return $fromOriginal !== '' ? $fromOriginal : 'jpg';
    }

    /**
     * Construye el nombre esperado según el slot (1-indexado).
     */
    private function buildExpectedImageName(string $sku, int $slot, string $extension): string
    {
        $extension = ltrim($extension, '.');
        $base = $slot === 1 ? $sku : ($sku . '_PIC' . $slot);
        return $extension !== '' ? ($base . '.' . $extension) : $base;
    }

    public function sortImagesOfProduct($productId, Request $request)
    {
        try {
            $sortedImagesUuids = $request->input('images');

            // Log de entrada
            Log::info('sortImagesOfProduct called', [
                'productId'    => $productId,
                'images_param' => $sortedImagesUuids,
            ]);

            // Normalizar lista de UUIDs solicitados
            $sortedImagesUuids = array_values(array_filter(array_map(
                static function ($uuid) {
                    return is_scalar($uuid) ? trim((string) $uuid) : '';
                },
                is_array($sortedImagesUuids) ? $sortedImagesUuids : []
            ), static fn ($uuid) => $uuid !== ''));

            if ($sortedImagesUuids === []) {
                Log::warning('No images provided for sorting', [
                    'sortedImagesUuids' => $request->input('images'),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'No images provided for sorting',
                ], 400);
            }

            // Obtener todas las relaciones actuales del producto
            $images = ProductImage::where('product_id', $productId)
                ->orderBy('img_order')
                ->get();

            Log::info('Existing ProductImages BEFORE reordering', [
                'count'  => $images->count(),
                'images' => $images->map(function ($img) {
                    return [
                        'img_id'    => $img->img_id,
                        'image_id'  => $img->image_id,
                        'img_order' => $img->img_order,
                    ];
                })->toArray(),
            ]);

            $normalizeUuid = static function ($value): string {
                $s = strtolower(trim((string) $value));

                return str_replace(['{', '}', ' '], '', $s);
            };

            // Índice por UUID normalizado (image_id)
            $byUuid = [];
            foreach ($images as $image) {
                $key = $normalizeUuid($image->image_id);
                if ($key !== '') {
                    $byUuid[$key] = $image;
                }
            }

            // Construir arreglo de ProductImage en el orden recibido
            $sortedImages = [];
            foreach ($sortedImagesUuids as $imageUuid) {
                $key = $normalizeUuid($imageUuid);
                if ($key !== '' && isset($byUuid[$key])) {
                    $sortedImages[] = $byUuid[$key];
                }
            }

            // Sin coincidencias: no fallar con 422 si no hay vínculo activo (p. ej. modal desfasado).
            // Devolver éxito no-op para que la UI no bloquee al usuario.
            if (count($sortedImages) === 0) {
                Log::warning('sortImagesOfProduct: no matching product images; treating as no-op', [
                    'product_id'      => $productId,
                    'requested_uuids' => $sortedImagesUuids,
                    'existing_ids'    => array_keys($byUuid),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'No image order changes were required',
                    'data'    => [
                        'amountResorted' => 0,
                        'noop'           => true,
                    ],
                ]);
            }

            if (count($sortedImages) !== count($sortedImagesUuids)) {
                Log::warning('Some images were not found when reordering - proceeding only with existing ones', [
                    'expected'        => count($sortedImagesUuids),
                    'found'           => count($sortedImages),
                    'requested_uuids' => $sortedImagesUuids,
                    'found_image_ids' => array_map(function ($img) {
                        return $img->image_id;
                    }, $sortedImages),
                ]);
            }

            DB::beginTransaction();

            try {
                $newOrder       = 1;
                $amountResorted = 0;
                $updates        = [];
                $sqlStatements  = [];

                foreach ($sortedImages as $image) {
                    $oldOrder = $image->img_order;

                    if ($image->img_order != $newOrder) {
                        $amountResorted++;

                        $imgId        = $image->img_id;
                        $sqlStatement = "UPDATE product_images SET img_order = {$newOrder} WHERE img_id = {$imgId}";
                        $sqlStatements[] = $sqlStatement;

                        Log::info('SQL Statement to execute - Update image order', [
                            'sql'       => $sqlStatement,
                            'img_id'    => $imgId,
                            'old_order' => $oldOrder,
                            'new_order' => $newOrder,
                        ]);

                        ProductImage::where('img_id', $image->img_id)
                            ->update(['img_order' => $newOrder]);

                        // Actualizar objeto en memoria
                        $image->img_order = $newOrder;

                        $updates[] = [
                            'img_id'    => $image->img_id,
                            'image_id'  => $image->image_id,
                            'old_order' => $oldOrder,
                            'new_order' => $newOrder,
                        ];
                    }

                    $newOrder++;
                }

                Log::info('Images order updated (NO DELETE)', [
                    'product_id'     => $productId,
                    'amountResorted' => $amountResorted,
                    'updates'        => $updates,
                    'sql_statements' => $sqlStatements,
                ]);

                // Tras reordenar, alinear `images.name` con el slot visual.
                // No se mueve el archivo físico en MinIO: solo cambia el nombre
                // mostrado en la UI (la imagen se sigue sirviendo por UUID).
                $renamedCount = $this->recalculateImageNamesForProduct($productId);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Images sorted successfully (no deletions performed)',
                    'data'    => [
                        'amountResorted' => $amountResorted,
                        'renamedCount'   => $renamedCount,
                    ],
                ]);
            } catch (\Exception $e) {
                DB::rollBack();

                Log::error('Error updating image order (NO DELETE)', [
                    'product_id' => $productId,
                    'error'      => $e->getMessage(),
                    'trace'      => $e->getTraceAsString(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Error updating image order: ' . $e->getMessage(),
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error in sortImagesOfProduct (NO DELETE mode)', [
                'product_id' => $productId,
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error in sortImagesOfProduct: ' . $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Upload an image for a product.
     *
     * Body/query opcional:
     * - slot (int 1–5): coloca la imagen en ese slot exacto.
     *   slot 1 = imagen principal (reemplaza si ya existe).
     *   slot N = SKU_PICN (reemplaza si ese img_order ya existe).
     * Sin slot: comportamiento anterior (append + resecuencia densa).
     *
     * @param int $productId
     * @return \Illuminate\Http\JsonResponse
     */
    public function addImageToProduct($productId, $imageUuid)
    {
        $request = request();
        /** @var Product $product */
        $product = \App\Models\Product::findOrFail($productId);
        $image = \App\Models\Image::whereUuid($imageUuid)->firstOrFail();

        if ($image->company_id !== $product->company_id) {
            return response()->json(['error' => "Image company differs from Product company "], 400);
        }

        $slotRaw = $request->input('slot', $request->query('slot'));
        $slot = is_numeric($slotRaw) ? (int) $slotRaw : 0;

        if ($slot >= 1 && $slot <= 5) {
            $result = $this->placeImageAtSlot($product, $image, $slot);
            $product->refresh();

            return response()->json([
                'success' => true,
                'message' => $result['replaced']
                    ? 'Image replaced in slot successfully'
                    : 'Image placed in slot successfully',
                'data' => [
                    'product' => $product,
                    'image' => $image,
                    'default_image_id' => $product->default_image_id,
                    'slot' => $slot,
                    'replaced' => $result['replaced'],
                ],
            ]);
        }

        $productImage = new ProductImage();
        $productImage->product_id = $productId;
        $productImage->image_id = $image->uuid;
        $productImage->save();

        // Fix the order of images to ensure sequential img_order values
        $this->fixImagesOrder($productId);

        // Siempre asegurar imagen por defecto visible en edición de producto.
        $product->refresh();
        $first = ProductImage::where('product_id', $productId)->orderBy('img_order')->orderBy('img_id')->first();
        $defaultUuid = $first?->image_id ? (string) $first->image_id : (string) $image->uuid;
        if ((string) ($product->default_image_id ?? '') !== $defaultUuid) {
            $product->default_image_id = $defaultUuid;
            $product->save();
        }

        $product->refresh();
        return response()->json([
            'success' => true,
            'message' => 'Image uploaded successfully',
            'data' => [
                'product' => $product,
                'image' => $image,
                'default_image_id' => $product->default_image_id,
                'slot' => null,
                'replaced' => false,
            ],
        ]);
    }

    /**
     * Coloca (o reemplaza) una imagen en un slot exacto (img_order = $slot).
     * No colapsa huecos: _pic3 puede ir al slot 3 aunque falte el 2.
     *
     * @return array{replaced: bool}
     */
    protected function placeImageAtSlot(Product $product, Image $image, int $slot): array
    {
        $productId = (int) $product->pro_id;
        $replaced = false;

        DB::transaction(function () use ($product, $image, $slot, $productId, &$replaced) {
            $existing = ProductImage::where('product_id', $productId)
                ->where('img_order', $slot)
                ->orderBy('img_id')
                ->first();

            if ($existing) {
                $existing->image_id = $image->uuid;
                $existing->save();
                $replaced = true;
            } else {
                // Evitar duplicar el mismo uuid en otro slot del mismo producto.
                ProductImage::where('product_id', $productId)
                    ->where('image_id', $image->uuid)
                    ->delete();

                $productImage = new ProductImage();
                $productImage->product_id = $productId;
                $productImage->image_id = $image->uuid;
                $productImage->img_order = $slot;
                $productImage->save();
            }

            if ($slot === 1) {
                $product->default_image_id = (string) $image->uuid;
                $product->save();
            } else {
                $product->refresh();
                $hasDefault = ProductImage::where('product_id', $productId)
                    ->where('image_id', (string) ($product->default_image_id ?? ''))
                    ->exists();
                if (!$hasDefault) {
                    $first = ProductImage::where('product_id', $productId)
                        ->orderBy('img_order')
                        ->orderBy('img_id')
                        ->first();
                    if ($first) {
                        $product->default_image_id = (string) $first->image_id;
                        $product->save();
                    }
                }
            }

            // Renombrar display names según img_order real (sin colapsar slots).
            $this->recalculateImageNamesForProduct($productId);
        });

        return ['replaced' => $replaced];
    }

    /**
     * Remove an image from a product by its identifier and image UUID, and adjusts the image order.
     *
     * @param int|string $productId The identifier of the product from which the image will be removed.
     * @param string $imageUuid The unique identifier (UUID) of the image to be removed.
     *
     * @return \Illuminate\Http\JsonResponse A JSON response indicating the result of the operation.
     */
    public function removeImageFromProduct($productId, $imageUuid)
    {
        $productImage = ProductImage::where('product_id', $productId)->where('image_id', $imageUuid)->firstOrFail();
        $response = $this->destroyResourceEmptyResponse($productImage);

        // Fix the order of images to ensure sequential img_order values
        $this->fixImagesOrder($productId);

        return $response;
    }
}

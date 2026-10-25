<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDetailCarRequest;
use App\Http\Requests\UpdateDetailCarRequest;
use App\Models\DetailCar;
use App\Services\GetImagesProductsQuery;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class DetailCarController extends Controller
{
    protected $resourceRoute = 'detailcar';

    #[OA\Get(
        path: "api/v2/detailcar",
        summary: "Display a listing of the DetailCar.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Catalogcart"],

        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/Detailcar')
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
        path: "api/v2/detailcar",
        summary: "Store a newly created resource in storage.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
                requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/DetailcarRequest'
            )
        ),
        tags: ["Catalogcart"],

        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                new OA\Property(
                    property: 'data',
                    ref: '#/components/schemas/Detailcar'
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
    public function store(StoreDetailCarRequest $request): \Illuminate\Http\Resources\Json\JsonResource
     {
        return $this->createFromAttributes($request->toArray());
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: "api/v2/detailcar/{detailcar}",
        summary: "Display the specified DetailCar.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Catalogcart"],
        parameters: [
            new OA\Parameter(
                name: "detailcar",
                description: "The Detailcar identifier to retrieve.",
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
                        ref: '#/components/schemas/Detailcar'
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
    public function show($detailCar)
    {
        return $this->getMainModelInstance($detailCar);
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: "api/v2/detailcar/{detailcar}",
        summary: "Updates the specified DetailCar.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
                requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/DetailcarRequest'
            )
        ),
        tags: ["Catalogcart"],
        parameters: [
            new OA\Parameter(
                name: "detailcar",
                description: "The Detailcar identifier to update.",
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
                    ref: '#/components/schemas/Detailcar'
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
    public function update(UpdateDetailCarRequest $request, DetailCar $detailcar)
    {
        return $this->updateModel($this->getMainModelInstance($detailcar->detail_id), $request->toArray());
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: "api/v2/detailcar/{detailcar}",
        summary: "Remove the specified DetailCar.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Catalogcart"],
        parameters: [
            new OA\Parameter(
                name: "detailcar",
                description: "The Detailcar identifier to remove.",
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
    public function destroy(DetailCar $detailcar): ?Response
     {
        return $this->destroyResourceEmptyResponse($detailcar);
    }

    #[OA\Delete(
        path: "api/v2/detailcar/{token}/product/{productId}",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Catalogcart"],
        parameters: [
            new OA\Parameter(
                name: "token",
                description: "The Product identifier to remove.",
                in: "path",
                required: true
            ),
            new OA\Parameter(
                name: "productId",
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
    public function removeProduct($token,$productId): ?Response
    {
        $detailCar = DetailCar::where('product_id', $productId)->where('token_car', $token)->sole();
        return $this->destroyResourceEmptyResponse($detailCar);
    }

    #[OA\Get(
        path: "api/v2/detailcar/{token}/product/{productId}",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Catalogcart"],
        parameters: [
            new OA\Parameter(
                name: "token",
                description: "The Product identifier to retrieve.",
                in: "path",
                required: true
            ),
            new OA\Parameter(
                name: "productId",
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
    public function product($token,$productId)
    {
        return DetailCar::where('product_id', $productId)
            ->where('token_car', $token)
            ->where('product_id', $productId)
            ->sole();
    }

    #[OA\Delete(
        path: "api/v2/detailcar/session/{sessionId}",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Catalogcart"],
        parameters: [
            new OA\Parameter(
                name: "sessionId",
                description: "The Session identifier to remove.",
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
        ],
        deprecated: true

    )]
    public function destroyBySession($sessionId)
    {
        $detailCars = DetailCar::where('session_id', $sessionId)->get();

        if (!$detailCars->isEmpty()){
            foreach ($detailCars as $detailCar){
                $detailCar->delete();
            }
            return response()->noContent();
        }

        abort(\Symfony\Component\HttpFoundation\Response::HTTP_NOT_FOUND);
    }

    public  function orderedProducts($sessionId)
    {
        $imagesProductsQuery = GetImagesProductsQuery::getQuery();
        $noImgUrl="no-image";
        return $this->getBuilder()
            ->select([
                'detail_car.product_id AS id',
                'detail_car.product_id',
                'products.name',
                'products.sku AS sku',
                'detail_car.qty',
                'detail_car.sale_price',
                DB::raw('detail_car.sale_price * detail_car.qty AS total_amount'),
                'detail_car.comment AS comments',
                DB::raw("'$noImgUrl' AS images"), //why?
                'images.group_images',
            ])
            ->join('products', 'products.pro_id', '=', 'detail_car.product_id')
            ->leftJoinSub($imagesProductsQuery, 'images', 'products.pro_id', '=', 'images.product_id')
            ->where('detail_car.session_id', '=', $sessionId)
            ->paginate();
    }
}

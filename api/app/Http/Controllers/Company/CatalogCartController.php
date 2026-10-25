<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Actions\AddProductToCartService;
use App\Actions\PlaceOrderService;
use App\Enums\OrderStatusCodes;
use App\Http\Controllers\Orders\CreatesOrders;
use App\Http\Controllers\Orders\ManagesInvitationsToken;
use App\Http\Requests\AddProductToCartRequest;
use App\Http\Requests\InvitationTokenRequest;
use App\Http\Requests\PlaceOrderRequest;
use App\Http\Requests\QuickAddProductToCartRequest;
use App\Http\Requests\StoreCatalogCartRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateCatalogCartRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\CatalogCart;
use App\Models\CatalogRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\StatusOrder;
use App\Services\AbandonedCartsListService;
use App\Services\CompanyDashboardCatalogStatsService;
use App\Services\CompanyPlanLimitService;
use App\Services\CustomerCatalogCartService;
use App\Services\LegacyCatalogCartTokenService;
use App\Services\MontoCartQuery;
use App\Services\OpenCartCompanyService;
use App\Services\OpenCartOrderSyncService;
use App\Services\ProductOpenCartLinesService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;
use Throwable;

class CatalogCartController extends Controller
{
    use CreatesOrders;
    use ManagesInvitationsToken;

    protected $resourceRoute = 'catalogcart';

    #[OA\Get(
        path: "api/v2/catalogcart",
        summary: "Display a listing of the CatalogCart.",
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
                        items: new OA\Items(ref: '#/components/schemas/Catalogcart')
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
        path: "api/v2/catalogcart",
        summary: "Store a newly created resource in storage.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
                requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/CatalogcartRequest'
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
                    ref: '#/components/schemas/Catalogcart'
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
    public function store(StoreCatalogCartRequest $request, CompanyPlanLimitService $planLimits)
    {
        $request->validated();
        $cartData = $request->get('order');

        // Aceptar body plano (sin clave "order"): normalizado en StoreCatalogCartRequest::prepareForValidation
        if (! is_array($cartData)) {
            $cartData = [
                'company_id' => $request->input('company_id'),
                'catalog_id' => $request->input('catalog_id'),
                'tipo_d' => $request->input('tipo_d', 'D'),
                'seller_id' => $request->input('user_id') ?? $request->input('seller_id'),
            ];
        }

        $cartData['seller_id'] = $cartData['seller_id'] ?? $request->get('seller_id') ?? $request->get('user_id');
        if (! isset($cartData['tipo_d'])) {
            $cartData['tipo_d'] = 'D';
        }

        $customer = Customer::query()->find($request->get('customer_id'));
        if ($customer && $customer->company_id) {
            $denied = $planLimits->assertCanCreateOrder((int) $customer->company_id);
            if ($denied !== null) {
                return $denied;
            }
        }

        /** @var Order $order */
        $newOrder = $this->createOrder($cartData, $customer, [], true);

        return $this->makeResource($newOrder)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: "api/v2/catalogcart/{catalogcart}",
        summary: "Display the specified CatalogCart.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Catalogcart"],
        parameters: [
            new OA\Parameter(
                name: "catalogcart",
                description: "The Catalogcart identifier to retrieve.",
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
                        ref: '#/components/schemas/Catalogcart'
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
    public function show($catalogcart)
    {
        return $this->getMainModelInstance($catalogcart);
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: "api/v2/catalogcart/{catalogcart}",
        summary: "Updates the specified CatalogCart.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
                requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/CatalogcartRequest'
            )
        ),
        tags: ["Catalogcart"],
        parameters: [
            new OA\Parameter(
                name: "catalogcart",
                description: "The Catalogcart identifier to update.",
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
                    ref: '#/components/schemas/Catalogcart'
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
    public function update(UpdateCatalogCartRequest $request, CatalogCart $catalogcart)
    {
        return $this->updateModel($catalogcart, $request->toArray());
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: "api/v2/catalogcart/{catalogcart}",
        summary: "Remove the specified CatalogCart.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Catalogcart"],
        parameters: [
            new OA\Parameter(
                name: "catalogcart",
                description: "The Catalogcart identifier to remove.",
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
    public function destroy(CatalogCart $catalogcart): ?Response
    {
        return $this->destroyResourceEmptyResponse($catalogcart);
    }

    #[OA\Delete(
        path: "api/v2/catalogcart/{catalogCartId}/details",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Catalogcart"],
        parameters: [
            new OA\Parameter(
                name: "catalogCartId",
                description: "The Detail identifier to remove.",
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
    public function destroyDetails($catalogCartId): ?Response
    {
        $cart = $this->getBuilder()->where('order_id', $catalogCartId)->sole();
        $cart->order_details()->delete();
        return response()->noContent();
    }

    #[OA\Get(
        path: "api/v2/catalogcart/company/{companyId}/customer/{customerId}/hasPendings",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Customer","Catalogcart"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The HasPending identifier to retrieve.",
                in: "path",
                required: true
            ),
            new OA\Parameter(
                name: "customerId",
                description: "The HasPending identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
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
    public function hasPendings($companyId, $customerId, CustomerCatalogCartService $service): \Illuminate\Http\JsonResponse
    {
        return response()->json(
            $service->hasPendingCarts((int) $companyId, (int) $customerId)
        );
    }

    public function expiredCatalogCartsCount($companyId, $customerId, CustomerCatalogCartService $service): \Illuminate\Http\JsonResponse
    {
        return response()->json(
            $service->expiredCartsCount((int) $companyId, (int) $customerId)
        );
    }

    public function sendCatalogCount($companyId, Request $request, CompanyDashboardCatalogStatsService $service): \Illuminate\Http\JsonResponse
    {
        return response()->json(
            $service->sendCatalogCount((int) $companyId, $request)
        );
    }

    public function generateLegacyToken(LegacyCatalogCartTokenService $service): \Illuminate\Http\JsonResponse
    {
        return response()->json($service->generateTokenPair());
    }

    public function productOpenCartLines($companyId, $productId, ProductOpenCartLinesService $service): \Illuminate\Http\JsonResponse
    {
        return response()->json(
            $service->lines((int) $companyId, (int) $productId)
        );
    }

    public function getCartWithStatus($companyId, $catalogCartId)
    {
        return $this->getBuilder()
            ->with(['status:id_status,cod,allow_toremove,multiple_cart,nosubmit_order'])
            ->where('company_id', $companyId)
            ->where('cart_id', $catalogCartId)
            ->select(['cart_id', 'status_cart'])
            ->first();
    }

    #[OA\Get(
        path: "api/v2/catalogcart/company/{companyId}/cartIds",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Catalogcart"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The CartId identifier to retrieve.",
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
                        type: 'array',
                        items: new OA\Items(
                            type: 'integer'
                        )
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
    public function cartIds($companyId)
    {
        $montoCartQuery = MontoCartQuery::getQuery();

        return $this->getBuilder()
            ->join('customers as cus', 'catalog_cart.customer_id', '=', 'cus.customer_id')
            ->leftJoinSub($montoCartQuery, 'm', 'catalog_cart.cart_id', '=', 'm.cart_id')
            ->where('catalog_cart.company_id', $companyId)
            ->whereIn('catalog_cart.order_sent', [1, 2])
            ->pluck('catalog_cart.cart_id');
    }

    #[OA\Get(
        path: "api/v2/catalogcart/company/{companyId}/customer/{customerId}",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Customer"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The Customer identifier to retrieve.",
                in: "path",
                required: true
            ),
            new OA\Parameter(
                name: "customerId",
                description: "The Customer identifier to retrieve.",
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
                        ref: '#/components/schemas/Customer'
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
    public function openCart($companyId, $customerId)
    {
        return $this->getBuilder()
            ->where('company_id', $companyId)
            ->where('customer_id', $customerId)
            ->where('status_id', StatusOrder::getIdByCode(OrderStatusCodes::SHOPPING))
            ->firstOrFail();
    }

    #[OA\Get(
        path: "api/v2/catalogcart/company/{companyId}/openCarts",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Catalogcart"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The OpenCart identifier to retrieve.",
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
                        type: 'array',
                        items: new OA\Items(
                            ref: '#/components/schemas/Catalogcart'
                                  )
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
    public function abandonedCarts($companyId, Request $request, AbandonedCartsListService $service): \Illuminate\Http\JsonResponse
    {
        return response()->json($service->list((int) $companyId, $request));
    }

    public function openCartDetail($companyId, $cartId, OpenCartCompanyService $service): \Illuminate\Http\JsonResponse
    {
        $detail = $service->detail((int) $companyId, (int) $cartId);
        if ($detail === null) {
            return response()->json(['message' => 'Resource not found'], Response::HTTP_NOT_FOUND);
        }

        return response()->json($detail);
    }

    public function openCartSoftDelete($companyId, $cartId, OpenCartCompanyService $service): \Illuminate\Http\JsonResponse
    {
        return response()->json($service->softDelete((int) $companyId, (int) $cartId));
    }

    public function openCartSubmit($companyId, $cartId, OpenCartCompanyService $service): \Illuminate\Http\JsonResponse
    {
        return response()->json($service->submitOrder((int) $companyId, (int) $cartId));
    }

    public function openCartRenew($companyId, $cartId, Request $request, OpenCartCompanyService $service): \Illuminate\Http\JsonResponse
    {
        $days = (int) $request->input('days', 15);

        return response()->json($service->renewExpiration((int) $companyId, (int) $cartId, $days));
    }

    /**
     * Sincroniza líneas de una orden en curso (grilla backend) hacia Open Cart.
     */
    public function syncOpenCart(int $companyId, Request $request, OpenCartOrderSyncService $service): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->canAccessCompany($companyId)) {
            return response()->json(['message' => 'forbidden', 'error' => true], 403);
        }

        $customerId = (int) $request->input('customer_id', 0);
        if ($customerId < 1) {
            return response()->json(['success' => false, 'message' => 'customer_id_required'], 422);
        }

        $products = $request->input('products', []);
        if (! is_array($products)) {
            $products = [];
        }

        $cartId = (int) $request->input('cart_id', 0);
        $sellerUserId = (int) $request->input('seller_user_id', $request->input('user_id', 0));

        $result = $service->syncCompanyDraft(
            $companyId,
            $customerId,
            $products,
            $cartId > 0 ? $cartId : null,
            $sellerUserId > 0 ? $sellerUserId : null
        );

        return response()->json(['success' => true, 'data' => $result]);
    }

    public function openCarts($companyId)
    {
        $montoCartQuery = MontoCartQuery::getQuery();

        return $this->getBuilder()
            ->select([
                'catalog_cart.cart_id',
                'catalog_cart.cart_number',
                'catalog_cart.catalog_id',
                'catalog_cart.order_sent',
                'catalog_cart.token_kor',
                'catalog_cart.status_cart',
                'catalog_cart.company_id',
                //DB::raw("CONCAT(UPPER(customer.business_name), ' <strong>', UPPER(customer.name), '</strong>') AS customer"),
                'catalog_cart.created',
                'm.total',
                'catalog_cart.customer_id',
                'catalog_cart.order_id',
            ])
            ->with(['customer:customer_id,business_name,name', 'order:order_id,order_number'])
            ->leftJoinSub($montoCartQuery, 'm', 'catalog_cart.cart_id', '=', 'm.cart_id')
            ->where('catalog_cart.company_id', $companyId)
            ->where('catalog_cart.type_cart', 'to_customer')
            ->paginate();
    }

    #[OA\Get(
        path: "api/v2/catalogcart/token-{token}",
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
                description: "The Catalogcart identifier to retrieve.",
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
                        ref: '#/components/schemas/Catalogcart'
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
    public function getByToken($token)
    {
        return $this->getBuilder()
            ->where('token_kor', $token)
            ->first();
    }

    #[OA\Get(
        path: "api/v2/catalogcart/{orderId}/info",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Catalogcart"],
        parameters: [
            new OA\Parameter(
                name: "orderId",
                description: "The Info identifier to retrieve.",
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
                                                  property: 'created',
                                                  type:     'datetime',
                                              ),
                                              new OA\Property(
                                                  property: 'cart_id',
                                                  type:     'integer',
                                              ),
                                              new OA\Property(
                                                  property: 'company_id',
                                                  type:     'integer',
                                              ),
                                              new OA\Property(
                                                  property: 'email_customer',
                                                  type:     'string',
                                                  nullable: true,
                                              ),
                                              new OA\Property(
                                                  property: 'customer',
                                                  description: 'The Customer name',
                                                  type:     'string',
                                                  nullable: true,
                                              ),
                                              new OA\Property(
                                                  property: 'seller',
                                                  description: 'The Seller name',
                                                  type:     'string',
                                                  nullable: true,
                                              ),
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
    public function info($orderId)
    {
        $order = CatalogCart::with([
            'customer:customer_id,email,business_name,name',
            'seller.user:id,name'
        ])
            ->select('created', 'order_id', 'company_id', 'customer_id', 'seller_id')
            ->where('order_id', $orderId)
            ->firstOrFail();

        return [
            'created' => $order->created,
            'cart_id' => $order->order_id,
            'company_id' => $order->company_id,
            'email_customer' => $order->customer->email ?? null,
            'customer' => $order->customer ? "{$order->customer->business_name} {$order->customer->name}" : null,
            'seller' => $order->seller->user->name ?? null,
        ];
    }

    function createCartFromToken($encryptedToken)
    {
        $responseCode = null;
        try {
            $dataInvitation = $this->getInvitationFromToken($encryptedToken);
            $validator = Validator::make($dataInvitation, (new InvitationTokenRequest())->rules());

            if ($validator->fails()) {
                $dataInvitation = ['errors' => $validator->errors()];
                $responseCode = Response::HTTP_UNPROCESSABLE_ENTITY;

            } else {
                $expirationDate = Carbon::parse($dataInvitation['expirationDate']);
                $now = Carbon::now();

                if ($expirationDate->greaterThan($now)) {

                    $order = $this->getOpenedShoppingCart($dataInvitation['customer_id'], $dataInvitation['catalog_id'], $dataInvitation['seller_id']);

                    if ($order) {
                        $responseCode = Response::HTTP_OK;
                    } else {

                        $customer = Customer::where('customer_id', $dataInvitation['customer_id'])->first();
                        if ($customer && $customer->company_id) {
                            $denied = app(CompanyPlanLimitService::class)
                                ->assertCanCreateOrder((int) $customer->company_id);
                            if ($denied !== null) {
                                return $denied;
                            }
                        }
                        $dataInvitation['source'] = 'catalog';
                        $order = $this->createOrder($dataInvitation, $customer, null, true);
                        $responseCode = Response::HTTP_CREATED;
                    }
                    $order['catalog_text'] = $dataInvitation['catalog_text'];
                    $catalogRequest = CatalogRequest::where('request_id', $dataInvitation['catalog_request_id'])->first();
                    $catalogRequest->access_count = 1;
                    $catalogRequest->save();
                    $dataInvitation = $order;

                } else {
                    $dataInvitation = ['errors' => 'The expiration date is greater than the current date'];
                    $responseCode = Response::HTTP_UNPROCESSABLE_ENTITY;
                }
            }

        } catch (\Exception $e) {
            $dataInvitation = ['errors' => 'Token inválido o corrupto'];
            $responseCode = Response::HTTP_BAD_REQUEST;
        }
        return response()->json($dataInvitation, $responseCode);
    }

    #[OA\Delete(
        path: "api/v2/catalogcart/{orderId}/delete-product/{productId}",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Catalogcart"],
        parameters: [
            new OA\Parameter(
                name: "orderId",
                description: "The Delete product identifier to remove.",
                in: "path",
                required: true
            ),
            new OA\Parameter(
                name: "productId",
                description: "The Delete product identifier to remove.",
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
    public function deleteProduct($orderId, $productId)
    {
        $order = Order::where('order_id', $orderId)->sole();
        return $order->order_details()->where('product_id', $productId)->delete();
    }

    #[OA\Put(
        path: "api/v2/catalogcart/{order}/place-order",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
                requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/OrderRequest'
            )
        ),
        tags: ["Catalogcart"],
        parameters: [
            new OA\Parameter(
                name: "order",
                description: "The Place order identifier to update.",
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
                    ref: '#/components/schemas/Order'
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
    public function placeOrder(PlaceOrderRequest $request, Order $order, PlaceOrderService $service): \Illuminate\Http\JsonResponse
    {
        return $service->execute($request, $order);
    }

    #[OA\Post(
        path: "api/v2/catalogcart/addProduct",
        summary: "Adds a product to a cart",
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
        tags: ["Catalogcart"],
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
        ],
    )]
    public function addProduct(AddProductToCartRequest $request, AddProductToCartService $service)
    {
        $orderTotalDetail = $service->execute($request->validated());

        return response()->json($orderTotalDetail);
    }

    #[OA\Post(
        path: "api/v2/catalogcart/quickAddProduct",
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
        tags: ["Catalogcart"],

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
        ],
        deprecated: true
    )]
    public function quickAddProduct(QuickAddProductToCartRequest $request, AddProductToCartService $service)
    {
        $data = $request->validated();
        $product = Product::where('pro_id', $data['product_id'])->sole();
        $data['quantity'] = max($product->minimum_stock, 1); //minimum_stock is the value?

        $orderTotalDetail = $service->execute($data);

        return response()->json($orderTotalDetail);
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\OrderNoteTypes;
use App\Enums\OrderStatusCodes;
use App\Http\Controllers\Orders\CreatesOrders;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderListResource;
use App\Models\Company;
use App\Models\Language;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\StatusOrder;
use App\Models\User;
use App\Models\Vendor;
use App\Enums\CompanyUserTypes;
use App\Exceptions\FifoCreditException;
use App\Services\CompanyPlanLimitService;
use App\Services\CustomerOrderProductFifoService;
use App\Services\InventoryOrderMvcService;
use App\Services\OpenCartOrderSyncService;
use App\Services\OrderSendEmailService;
use App\Services\Orders\EnsurePositiveQtyForDocumentOrdersService;
use App\Services\UpdateDeliveredItemService;
use App\Services\WarehouseOrderMvcService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use RuntimeException;

class OrderController extends Controller
{
    use CreatesOrders;

    protected $resourceRoute = 'order';

    /** Orden por defecto: updated DESC, luego created DESC, luego order_id DESC. */
    protected $defaultOrderProperty = 'updated';
    protected $defaultOrderDirection = 'DESC';
    protected $defaultOrderPropertySecondary = 'created';
    protected $defaultOrderDirectionSecondary = 'DESC';

    /**
     * Orden estable del listado company/orders.
     * Cualifica columnas (evita ambigüedad) y añade order_id como desempate.
     */
    protected function getFilteredSortedModelQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $query = $this->getFilteredModelQuery($request);

        $sortColumn = (string) $request->input('orderProperty', $this->defaultOrderProperty);
        $sortDirection = strtoupper((string) $request->input('orderDirection', $this->defaultOrderDirection));
        if (! in_array($sortDirection, ['ASC', 'DESC'], true)) {
            $sortDirection = 'DESC';
        }

        // Alias de columnas de fecha del listado MVC.
        $aliases = [
            'orden_date' => 'created',
            'order_date' => 'created',
            'fecha' => 'created',
            'list_date_updated' => 'updated',
            'list_date_created' => 'created',
        ];
        if (isset($aliases[$sortColumn])) {
            $sortColumn = $aliases[$sortColumn];
        }

        $allowed = ['updated', 'created', 'order_id', 'order_number', 'total_amount', 'amount', 'status_id', 'customer_id', 'seller_id'];
        if ($sortColumn === '' || ! in_array($sortColumn, $allowed, true)) {
            $sortColumn = 'updated';
        }

        $query->orderBy('orders.'.$sortColumn, $sortDirection);

        if ($sortColumn !== 'created') {
            $query->orderBy('orders.created', $sortDirection === 'ASC' ? 'ASC' : 'DESC');
        }
        if ($sortColumn !== 'order_id') {
            $query->orderBy('orders.order_id', 'DESC');
        }

        return $query;
    }

    #[OA\Get(
        path: "api/v2/order",
        summary: "Display a listing of the Order.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Order"],

        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: 'data',
                                                     type:     'array',
                                                     items:    new OA\Items(ref: '#/components/schemas/Order')
                                                 ),
                                                 new OA\Property(
                                                     property:   'meta',
                                                     type:       'object',
                                                     properties: [
                                                                     new OA\Property(
                                                                         property: 'current_page',
                                                                         type:     'integer'
                                                                     ),
                                                                     new OA\Property(property: 'from', type: 'integer'),
                                                                     new OA\Property(
                                                                         property: 'last_page',
                                                                         type:     'integer'
                                                                     ),
                                                                     new OA\Property(
                                                                         property: 'per_page',
                                                                         type:     'integer'
                                                                     ),
                                                                     new OA\Property(property: 'to', type: 'integer'),
                                                                     new OA\Property(property: 'total', type: 'integer')
                                                                 ]
                                                 ),
                                                 new OA\Property(
                                                     property:   'links',
                                                     type:       'object',
                                                     properties: [
                                                                     new OA\Property(property: 'first', type: 'string'),
                                                                     new OA\Property(property: 'last', type: 'string'),
                                                                     new OA\Property(
                                                                         property: 'prev',
                                                                         type:     'string',
                                                                         nullable: true
                                                                     ),
                                                                     new OA\Property(
                                                                         property: 'next',
                                                                         type:     'string',
                                                                         nullable: true
                                                                     )
                                                                 ]
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Unauthenticated"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_NOT_FOUND,
                description: "Not found",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Resource not found"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Internal server error"
                                                 )
                                             ]
                             )
            )
        ]
    )]
    /**
     * Listado de órdenes restringido a la compañía activa del usuario.
     *
     * Migración MVC (2026): el flujo canónico es /company/orders (IsCRUD + API).
     * El legacy /company/order ya no debe usarse, pero coexistía con el nuevo.
     * Company_Controller inyecta company_id de sesión (compId) en cada GET; antes
     * este método lo ignoraba y usaba companies()->first(), mostrando órdenes de
     * otra compañía cuando el usuario tenía varias filas en company_user.
     *
     * @see resolveAuthenticatedCompanyId()
     */
    public function index()
    {
        $companyId = $this->resolveAuthenticatedCompanyId(request());
        if ($companyId === null) {
            return $this->companyIdForbiddenResponse(request());
        }
        request()->merge(['company_id' => $companyId]);
        $this->sanitizeDocumentOrdersOnListEntry($companyId);

        $result = parent::index();

        return $this->enrichWarehouseUserDisplayOnList(
            $this->normalizeDocumentOrderListPayload($result)
        );
    }

    /**
     * POST /api/v2/order/sanitize-document
     * Sanea órdenes documento (tipo_d=D) de la compañía: qty positivas, sin ítems 0, sin vacías, recálculo.
     */
    public function sanitizeDocumentOrders(Request $request): JsonResponse
    {
        $companyId = $this->resolveAuthenticatedCompanyId($request);
        if ($companyId === null) {
            $companyId = (int) $request->input('company_id', 0);
            if ($companyId < 1 || ! $this->mvcCompanyOrderAllowed($request, $companyId)) {
                return $this->companyIdForbiddenResponse($request);
            }
        }

        $stats = app(EnsurePositiveQtyForDocumentOrdersService::class)
            ->sanitizeCompanyDocumentOrders($companyId);

        return response()->json([
            'status' => '1',
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Lista órdenes de la compañía del token con cliente, seller, warehouse, estado y productos (descripción, unidad, sku).
     * GET /api/v2/order/list
     */
    public function list(): \Illuminate\Http\JsonResponse
    {
        $companyId = $this->resolveAuthenticatedCompanyId(request());
        if ($companyId === null) {
            return $this->companyIdForbiddenResponse(request());
        }
        $this->sanitizeDocumentOrdersOnListEntry($companyId);
        $sellerId = (int) request()->query('seller_id', 0);
        $includeOrderDetails = request()->boolean('include_order_details', true);

        $customerIds = [];
        $rawCustomerIds = request()->query('customer_ids');
        if (is_array($rawCustomerIds)) {
            foreach ($rawCustomerIds as $id) {
                $cid = (int) $id;
                if ($cid > 0) {
                    $customerIds[] = $cid;
                }
            }
        } elseif ($rawCustomerIds !== null && $rawCustomerIds !== '') {
            $cid = (int) $rawCustomerIds;
            if ($cid > 0) {
                $customerIds = [$cid];
            }
        }
        $customerIds = array_values(array_unique($customerIds));
        $maxCustomerIdsFilter = 400;
        $applyCustomerFilter = $customerIds !== [] && count($customerIds) <= $maxCustomerIdsFilter;

        $relations = [
            'customer',
            'seller.user',
            'statusOrder',
        ];
        if ($includeOrderDetails) {
            $relations[] = 'order_details.product.unit';
        }
        if (\Schema::hasColumn('orders', 'warehouse_id')) {
            $relations[] = 'warehouse';
        }
        if (\Schema::hasColumn('orders', 'warehouse_user_id')) {
            $relations[] = 'warehouse_user.warehouse';
            $relations[] = 'warehouse_user.user';
        }

        $orders = Order::query()
            ->where('company_id', $companyId)
            ->when($sellerId > 0, static function ($q) use ($sellerId) {
                $q->where('seller_id', $sellerId);
            })
            ->when($applyCustomerFilter, static function ($q) use ($customerIds) {
                $q->whereIn('customer_id', $customerIds);
            })
            ->with($relations)
            ->orderBy('order_id', 'desc')
            ->get();

        return response()->json([
            'data' => OrderListResource::collection($orders),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: "api/v2/order",
        summary: "Store a newly created resource in storage.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content:  new OA\JsonContent(
                          ref: '#/components/schemas/OrderRequest'
                      )
        ),
        tags: ["Order"],

        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: 'data',
                                                     ref:      '#/components/schemas/Order'
                                                 ),
                                                 new OA\Property(
                                                     property: 'message',
                                                     type:     'string',
                                                     example:  'Resource created successfully'
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Unauthenticated"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_NOT_FOUND,
                description: "Not found",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Resource not found"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Internal server error"
                                                 )
                                             ]
                             )
            )
        ]
    )]
    public function store(StoreOrderRequest $request, CompanyPlanLimitService $planLimits): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validated();
        $productsData = $validated['products'];
        $internalComments = $validated['internal_comments'] ?? null;
        $openCartId = isset($validated['cart_id']) ? (int) $validated['cart_id'] : null;
        $openCartToken = isset($validated['token_kor']) ? (string) $validated['token_kor'] : null;
        unset($validated['products'], $validated['internal_comments'], $validated['cart_id'], $validated['token_kor']);

        $companyId = $this->resolveAuthenticatedCompanyId($request);
        if ($companyId === null) {
            return $this->companyIdForbiddenResponse($request);
        }

        $denied = $planLimits->assertCanCreateOrder((int) $companyId);
        if ($denied !== null) {
            return $denied;
        }

        $company = Company::find($companyId);
        $languageId = $company?->language_id ?? Language::first()?->language_id;
        if ($languageId === null) {
            return response()->json([
                'message' => 'No language configured for the company. Set company language or add a language in the system.',
                'error' => 'language_id is required.',
            ], 422);
        }

        // Si seller_id viene en 0, resolver por user_id del token en tabla sellers
        if ((int) ($validated['seller_id'] ?? 0) === 0) {
            $userId = auth()->id();
            $seller = Vendor::where('user_id', $userId)->first();
            if (!$seller) {
                return response()->json([
                    'message' => 'Unable to resolve seller. User from token has no associated seller.',
                    'error' => 'seller_id could not be determined from token.',
                ], 422);
            }
            $validated['seller_id'] = $seller->id;
        }

        $orderModel = new Order();
        $uuid = $orderModel->generate_uuid();
        $tipoD = $validated['tipo_d'] ?? 'D';
        $year2 = date('y');
        $prefix = ($tipoD === 'C') ? 'OCN' . $year2 : 'ORD' . $year2;
        $digitLength = ($tipoD === 'C') ? 6 : 5;

        try {
            DB::beginTransaction();

            $orderNumber = $this->generateUniqueOrderNumber($companyId, $digitLength, $prefix);

            $provenance = \App\Support\OrderAuthTokenProvenance::fromRequest(request(), $companyId);

            $order = Order::create([
                'company_id'       => $companyId,
                'UUID'            => $uuid,
                'tipo_d'          => $tipoD,
                'order_number'    => $orderNumber,
                'language_id'     => $languageId,
                'customer_id'     => $validated['customer_id'],
                'seller_id'       => $validated['seller_id'],
                'discount_type'   => $validated['discount_type'],
                'discount'        => $validated['discount'] ?? 0,
                'proforma'        => $validated['proforma'] ?? false,
                'status_id'       => StatusOrder::getIdByCode(OrderStatusCodes::PLACED),
                'source'          => $validated['source'] ?? 'manual',
                'token_create'    => $provenance['token_create'],
                'user_id_token'   => $provenance['user_id_token'],
                'rol_token'       => $provenance['rol_token'],
            ]);

            if ($internalComments) {
                $order->notes()->create([
                    'type' => OrderNoteTypes::Internal->value,
                    'user_id' => auth()->user()->id,
                    'note' => $internalComments
                ]);
            }

            $details = [];

            foreach ($productsData as $item) {
                $product = Product::findOrFail($item['pro_id']);
                $qty = (float) $item['qty'];
                // Órdenes documento (D): cantidad siempre positiva.
                if (strtoupper((string) $tipoD) === 'D' && $qty < 0) {
                    $qty = abs($qty);
                }
                $salePrice = (float) $item['price'];
                $lineTotal = $qty * $salePrice;

                $details[] = new OrderDetail([
                    'product_id'        => $item['pro_id'],
                    'qty'               => $qty,
                    'sale_price'        => $salePrice,
                    'fob_price'         => (float) ($product->fob_price ?? 0),
                    'fixed_pricedate'   => $item['fixed_price'] ? Carbon::now() : null,
                    'comment'           => $item['comment'] ?? '-',
                    'amount_sales'      => $lineTotal,
                    'amount'            => $lineTotal,
                    'total'             => $lineTotal,
                    'total_amount'      => $lineTotal,
                    'delivered_quantity' => 0,
                    'delivered_pack'    => 0,
                    'pack'              => 0,
                    'discount'          => 0,
                    'id_kor'            => 1,
                    'comments'          => 1,
                ]);
            }

            $order->order_details()->saveMany($details);

            // Sincronizar montos en cabecera de orden para compatibilidad con listados legacy.
            $subTotal = collect($details)->sum(static function (OrderDetail $d): float {
                return (float) ($d->total_amount ?? ((float) $d->qty * (float) $d->sale_price));
            });
            $discountType = (int) ($order->discount_type ?? 0);
            $discount = (float) ($order->discount ?? 0);
            $discountAmount = 0.0;
            if ($discountType === 1) {
                $discountAmount = $subTotal * ($discount / 100);
            } elseif ($discountType === 2) {
                $discountAmount = $discount;
            }
            $totalAmount = max(0.0, $subTotal - $discountAmount);
            $order->amount = $subTotal;
            $order->discount_a = $discountAmount;
            $order->total_amount = $totalAmount;
            $order->save();

            // Control FIFO: ventas abren capas; NC consumen capas (precio NC <= venta).
            app(CustomerOrderProductFifoService::class)->syncOrder($order->fresh(['order_details.product']));

            DB::commit();

            try {
                app(OpenCartOrderSyncService::class)->closeCompleted(
                    (int) $companyId,
                    ($openCartId !== null && $openCartId > 0) ? $openCartId : null,
                    ($openCartToken !== null && $openCartToken !== '') ? $openCartToken : null,
                    (int) $order->order_id,
                    (int) $order->customer_id
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('OrderController::store open cart close failed', [
                    'order_id' => $order->order_id,
                    'error' => $e->getMessage(),
                ]);
            }

            return $this->makeResource($order)->response()->setStatusCode(Response::HTTP_CREATED);

        } catch (FifoCreditException $e) {
            DB::rollBack();

            return response()->json([
                'message' => $e->getMessage(),
                'error' => $e->errorKey,
                'context' => $e->context,
            ], 422);
        } catch (\Throwable $e) {
            DB::rollBack();
            \Illuminate\Support\Facades\Log::error('OrderController::store failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Error creating order',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: "api/v2/order/{order}",
        summary: "Display the specified Order.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Order"],
        parameters: [
            new OA\Parameter(
                name:        "order",
                description: "The Order identifier to retrieve.",
                in:          "path",
                required:    true
            ),
        ],
        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: 'data',
                                                     ref:      '#/components/schemas/Order'
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Unauthenticated"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_NOT_FOUND,
                description: "Not found",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Resource not found"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Internal server error"
                                                 )
                                             ]
                             )
            )
        ]
    )]
    public function show($order)
    {
        $with = request()->input('relations', ['order_details']);
        if (is_string($with)) {
            $with = array_filter(array_map('trim', explode(',', $with)));
        } elseif (is_array($with)) {
            $normalized = [];
            foreach ($with as $item) {
                if (!is_string($item) || $item === '') {
                    continue;
                }
                if (strpos($item, ',') !== false) {
                    foreach (array_filter(array_map('trim', explode(',', $item))) as $part) {
                        $normalized[] = $part;
                    }
                } else {
                    $normalized[] = $item;
                }
            }
            $with = $normalized;
        }
        $with = is_array($with) ? $with : [];
        if (!in_array('order_details', $with)) {
            $with[] = 'order_details';
        }
        $order = $this->getModelInstanceBuilder($order)->with($with)->sole();
        return $this->makeResource($order)->toArray(request());
    }

    /**
     * Devuelve toda la información de la orden para la vista de detalle (p. ej. /company/orders/view).
     * Carga todas las relaciones necesarias: cliente, empresa, vendedor, dirección, líneas, estado, historial, etc.
     */
    public function showFull($order)
    {
        $with = [
            'address',
            'address.city',
            'address.state',
            'address.country',
            'company',
            'company.formatDate',
            'company.format_numeric',
            'company.currencyModel',
            'customer',
            'customer.commercialAddresses',
            'customer.commercialAddresses.city',
            'customer.commercialAddresses.state',
            'customer.commercialAddresses.country',
            'seller',
            'seller.user',
            'warehouse_user',
            'warehouse',
            'order_details',
            'order_details.product',
            'order_details.product.images',
            'order_details.product.default_image',
            'notes',
            'statusOrder',
            'order_status_histories',
            'order_status_histories.status',
            'order_status_histories.user',
        ];
        $order = $this->getModelInstanceBuilder($order)->with($with)->sole();
        return $this->makeResource($order)->toArray(request());
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: "api/v2/order/{order}",
        summary: "Updates the specified Order.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content:  new OA\JsonContent(
                          ref: '#/components/schemas/OrderRequest'
                      )
        ),
        tags: ["Order"],
        parameters: [
            new OA\Parameter(
                name:        "order",
                description: "The Order identifier to update.",
                in:          "path",
                required:    true
            ),
        ],
        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: 'data',
                                                     ref:      '#/components/schemas/Order'
                                                 ),
                                                 new OA\Property(
                                                     property: 'message',
                                                     type:     'string',
                                                     example:  'Resource updated successfully'
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Unauthenticated"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_NOT_FOUND,
                description: "Not found",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Resource not found"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Internal server error"
                                                 )
                                             ]
                             )
            )
        ]
    )]
    public function update(UpdateOrderRequest $request, Order $order)
    {
        $attributes = $request->toArray();

        // Sincronizar detalles: eliminar, actualizar y crear (detalle / detalle_eliminar desde el frontend)
        $detalleEliminar = $attributes['detalle_eliminar'] ?? [];
        $detalle = $attributes['detalle'] ?? [];
        unset($attributes['detalle_eliminar'], $attributes['detalle']);

        try {
            DB::transaction(function () use ($order, $detalleEliminar, $detalle) {
                if (!empty($detalleEliminar)) {
                    $order->order_details()->whereIn('detail_id', $detalleEliminar)->forceDelete();
                }
                foreach ($detalle as $item) {
                    $detailId = isset($item['id']) && $item['id'] !== '' && $item['id'] !== null
                        ? (int) $item['id'] : null;
                    $productId = (int) ($item['id_producto'] ?? 0);
                    $qty = (float) ($item['qty'] ?? 0);
                    // Órdenes documento (D): cantidad siempre positiva.
                    if (strtoupper((string) ($order->tipo_d ?? 'D')) === 'D' && $qty < 0) {
                        $qty = abs($qty);
                    }
                    $salePrice = (float) ($item['precio'] ?? 0);
                    $comment = $item['comentarios'] ?? '';
                    $fixedPrice = !empty($item['precio_fijo']);
                    $lineTotal = $qty * $salePrice;

                    if ($detailId) {
                        $detail = $order->order_details()->where('detail_id', $detailId)->first();
                        if ($detail) {
                            $detail->update([
                                'qty' => $qty,
                                'sale_price' => $salePrice,
                                'comments' => $comment,
                                'fixed_pricedate' => $fixedPrice ? Carbon::now() : null,
                                'amount_sales' => $lineTotal,
                                'total_amount' => $lineTotal,
                                'amount' => $lineTotal,
                                'total' => $lineTotal,
                            ]);
                        }
                    } else {
                        if ($productId <= 0) {
                            continue;
                        }
                        $product = Product::find($productId);
                        $order->order_details()->create([
                            'product_id' => $productId,
                            'qty' => $qty,
                            'sale_price' => $salePrice,
                            'fob_price' => $product ? (float) ($product->fob_price ?? 0) : 0,
                            'comment' => $comment,
                            'comments' => $comment,
                            'fixed_pricedate' => $fixedPrice ? Carbon::now() : null,
                            'amount_sales' => $lineTotal,
                            'amount' => $lineTotal,
                            'total' => $lineTotal,
                            'total_amount' => $lineTotal,
                            'delivered_quantity' => 0,
                            'delivered_pack' => 0,
                            'pack' => 0,
                            'discount' => 0,
                            'id_kor' => 1,
                        ]);
                    }
                }

                app(CustomerOrderProductFifoService::class)->syncOrder(
                    $order->fresh(['order_details.product'])
                );
            });
        } catch (FifoCreditException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => $e->errorKey,
                'context' => $e->context,
            ], 422);
        }

        return $this->updateModel($order, $attributes);
    }

    /**
     * Update order status. PATCH /api/v2/order/{order}/status
     * Acepta status_id (id_status) o cod_status; si viene cod_status se resuelve a id_status.
     */
    public function updateStatus(Request $request, Order $order): \Illuminate\Http\JsonResponse
    {
        $statusId = $request->input('status_id');
        $codStatus = $request->input('cod_status');
        if ($codStatus !== null && $codStatus !== '') {
            $status = \App\Models\StatusOrder::query()
                ->where('cod_status', (int) $codStatus)
                ->first();
            if ($status) {
                $statusId = $status->id_status;
            }
        }
        if (!$statusId) {
            return response()->json(['success' => false, 'message' => 'status_id or cod_status is required'], 422);
        }
        $order->status_id = (int) $statusId;
        $order->save();
        return response()->json(['success' => true, 'message' => 'Order status updated']);
    }

    /**
     * Actualiza status_id de la orden buscando el estado en status_orders por cod_status.
     * POST /api/v2/order/status-by-order-number
     * Body: company_id, order_number, cod_status
     */
    public function updateStatusByOrderNumber(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['required', 'integer'],
            'order_number' => ['required', 'string', 'max:120'],
            'cod_status' => ['required', 'integer'],
        ]);

        $companyId = (int) $validated['company_id'];
        $orderNumber = trim((string) $validated['order_number']);
        $codStatus = (int) $validated['cod_status'];

        $order = Order::query()
            ->where('company_id', $companyId)
            ->where('order_number', $orderNumber)
            ->first();

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => $this->cmpOrdersApiMessage('order_status_by_number_order_not_found'),
            ], 404);
        }

        /** @var User|null $user */
        $user = $request->user();
        if (! $user instanceof User || ! $this->userMayUpdateOrderStatusByNumber($user, $companyId, $order)) {
            return response()->json([
                'success' => false,
                'message' => $this->cmpOrdersApiMessage('order_status_by_number_forbidden'),
            ], 403);
        }

        $status = StatusOrder::query()
            ->where('cod_status', $codStatus)
            ->first();

        if (! $status) {
            return response()->json([
                'success' => false,
                'message' => $this->cmpOrdersApiMessage('order_status_by_number_status_not_found'),
            ], 404);
        }

        $order->status_id = (int) $status->id_status;
        $order->save();

        return response()->json([
            'success' => true,
            'message' => $this->cmpOrdersApiMessage('order_status_by_number_success'),
            'order_id' => $order->order_id,
            'status_id' => $order->status_id,
            'cod_status' => $codStatus,
        ]);
    }

    private function userMayUpdateOrderStatusByNumber(User $user, int $companyId, Order $order): bool
    {
        if ((int) $order->company_id !== $companyId) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }

        return $user->isManager($companyId)
            || $user->isSeller($companyId)
            || $user->isWarehouse($companyId);
    }

    /**
     * Generate PDF for an order. POST /api/v2/order/{order}/generatePdf
     * Acepta include_locations, delivery_notes en el body.
     */
    public function generatePdf(Request $request, Order $order): \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
    {
        $request->validate([
            'include_locations' => 'sometimes|boolean',
            'delivery_notes' => 'nullable|string',
        ]);

        $order->loadMissing([
            'order_details',
            'order_details.product',
            'order_details.product.default_image',
            'order_details.product.images',
            'customer',
            'seller.user',
            'company',
            'company.currencyModel',
            'company.format_numeric',
            'company.addresses',
            'company.addresses.city',
            'company.addresses.state',
            'company.addresses.country',
            'address',
            'address.city',
            'address.state',
            'address.country',
            'notes',
        ]);

        // Usar locale del request, o el de la compañía de la orden (tabla de traducciones por idioma)
        $locale = $request->input('locale');
        if (empty($locale) && $order->relationLoaded('company') && $order->company) {
            $languageId = (int) ($order->company->language_id ?? 1);
            $locale = $languageId === 2 ? 'es' : 'en';
        }
        $locale = $locale ?: app()->getLocale() ?: 'en';
        $locale = in_array($locale, ['es', 'en'], true) ? $locale : 'en';

        $langPath = base_path('lang/' . $locale . '/cmp-orders.php');
        if (!is_file($langPath)) {
            $langPath = base_path('lang/en/cmp-orders.php');
        }
        $lang = is_file($langPath) ? (array) require $langPath : [];

        $logoDataUrl = $this->resolveOrderPdfLogo($order);
        $notesText = $this->resolveOrderPdfNotes($order);
        $productImageDataUrls = $this->resolveOrderPdfProductImages($order);

        try {
            @ini_set('pcre.backtrack_limit', (string) PHP_INT_MAX);
            @ini_set('pcre.recursion_limit', (string) PHP_INT_MAX);

            $companyId = (int) ($order->company_id ?? 0);
            $pdfContent = $this->buildOrderPdfContent($order, $lang, $logoDataUrl, $notesText, $productImageDataUrls);
            $safeNumber = preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) ($order->order_number ?? $order->order_id)) ?: (string) $order->order_id;
            $fileName = 'order-'.$safeNumber.'.pdf';

            // Copia en uploads/documents/{company_id}/orders/
            if ($companyId > 0 && is_string($pdfContent) && $pdfContent !== '') {
                $dest = \App\Support\UploadsPath::companyOrderPdfFile($companyId, $fileName);
                @file_put_contents($dest, $pdfContent);
            }

            return response($pdfContent, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $fileName . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('OrderController::generatePdf failed', [
                'order_id' => $order->order_id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'message' => 'Error generating PDF: ' . $e->getMessage(),
                'error' => 'PDF Generation Failed',
            ], 500);
        }
    }

    /**
     * Genera el PDF escribiendo HTML en fragmentos (evita pcre.backtrack_limit con imágenes base64).
     *
     * @param  array<string, mixed>  $lang
     * @param  array<int|string, string>  $productImageDataUrls
     */
    private function buildOrderPdfContent(
        Order $order,
        array $lang,
        ?string $logoDataUrl,
        string $notesText,
        array $productImageDataUrls
    ): string {
        $companyId = (int) ($order->company_id ?? 0);
        $viewData = $this->prepareOrderPdfViewData($order, $lang, $logoDataUrl, $notesText, $productImageDataUrls);

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'tempDir' => \App\Support\UploadsPath::mpdfTempDir($companyId),
            'simpleTables' => true,
            'packTableData' => true,
        ]);

        $mpdf->WriteHTML(view('pdf.order_head', $viewData)->render());

        $details = collect($order->order_details ?? []);
        // Con fotos base64, chunks pequeños evitan el límite PCRE de mPDF.
        foreach ($details->chunk(12) as $chunk) {
            $mpdf->WriteHTML(view('pdf.order_rows', array_merge($viewData, [
                'details' => $chunk,
            ]))->render());
        }

        $mpdf->WriteHTML(view('pdf.order_foot', $viewData)->render());

        return $mpdf->Output('', 'S');
    }

    /**
     * @param  array<string, mixed>  $lang
     * @param  array<int|string, string>  $productImageDataUrls
     * @return array<string, mixed>
     */
    private function prepareOrderPdfViewData(
        Order $order,
        array $lang,
        ?string $logoDataUrl,
        string $notesText,
        array $productImageDataUrls
    ): array {
        $t = $lang;
        $formatNumeric = null;
        if ($order->relationLoaded('company') && $order->company && $order->company->relationLoaded('format_numeric')) {
            $formatNumeric = $order->company->format_numeric;
        }
        if (! $formatNumeric) {
            $formatNumeric = \App\Models\FormatNumeric::default();
        }
        $digits = (int) ($formatNumeric->digits ?? 2);
        $fmtMoney = static function ($value) use ($formatNumeric, $digits) {
            if ($formatNumeric) {
                return $formatNumeric->formatNumber((float) $value);
            }

            return number_format((float) $value, $digits, '.', ',');
        };
        $fmtQty = static function ($value) use ($digits) {
            $n = (float) $value;
            if (floor($n) == $n) {
                return number_format($n, 0, '.', ',');
            }

            return number_format($n, $digits, '.', ',');
        };

        $moneda = '$';
        $companyName = '';
        $companyAddress = '';
        $companyPhone = '';
        $companyEmail = '';
        if ($order->relationLoaded('company') && $order->company) {
            $c = $order->company;
            $moneda = (isset($c->currencyModel) && $c->currencyModel ? $c->currencyModel->symbol : null)
                ?? (isset($c->currencies[0]) ? $c->currencies[0]->symbol : null)
                ?? '$';
            $companyName = trim((string) ($c->company_name ?? $c->name ?? ''));
            $companyEmail = trim((string) ($c->email ?? $c->company_email ?? ''));
            $companyAddress = trim((string) ($c->address ?? ''));
            if ($companyAddress === '' && $c->relationLoaded('addresses') && $c->addresses->isNotEmpty()) {
                $addr = $c->addresses->first();
                $parts = array_filter([
                    trim(($addr->street ?? '').' '.($addr->street_number ?? '')),
                    $addr->zone ?? null,
                    ($addr->relationLoaded('city') && $addr->city) ? $addr->city->name : null,
                    ($addr->relationLoaded('state') && $addr->state) ? $addr->state->name : null,
                    $addr->zipcode ?? null,
                    ($addr->relationLoaded('country') && $addr->country) ? $addr->country->name : null,
                ], static fn ($v) => trim((string) $v) !== '');
                $companyAddress = implode(', ', $parts);
            }
            $companyPhone = trim((string) ($c->phone_number ?? $c->phone ?? ''));
        }

        $customerName = '';
        $customerContact = '';
        $customerAddress = '';
        $customerPhone = '';
        $customerCell = '';
        $customerEmail = '';
        if ($order->relationLoaded('customer') && $order->customer) {
            $cu = $order->customer;
            $business = trim((string) ($cu->business_name ?? ''));
            $contact = trim((string) ($cu->name ?? ''));
            $customerName = $business !== '' ? $business : $contact;
            $customerContact = ($business !== '' && $contact !== '' && $contact !== $business) ? $contact : '';
            $customerEmail = trim((string) ($cu->email ?? ''));
            $customerPhone = trim((string) ($cu->phone ?? $cu->phone_number ?? ''));
            $customerCell = trim((string) ($cu->cell_phone ?? ''));
            if ($order->relationLoaded('address') && $order->address) {
                $addr = $order->address;
                $parts = array_filter([
                    trim(($addr->street ?? '').' '.($addr->street_number ?? '')),
                    $addr->zone ?? null,
                    ($addr->relationLoaded('city') && $addr->city) ? $addr->city->name : null,
                    ($addr->relationLoaded('state') && $addr->state) ? $addr->state->name : null,
                    $addr->zipcode ?? null,
                    ($addr->relationLoaded('country') && $addr->country) ? $addr->country->name : null,
                ], static fn ($v) => trim((string) $v) !== '');
                $customerAddress = implode(', ', $parts);
            }
            if ($customerAddress === '' && ! empty($cu->commercial_address)) {
                $customerAddress = trim((string) $cu->commercial_address);
            }
            if ($customerAddress === '' && ! empty($cu->dispatch_address)) {
                $customerAddress = trim((string) $cu->dispatch_address);
            }
        }

        $sellerName = '';
        if ($order->relationLoaded('seller') && $order->seller) {
            $sellerName = trim((string) ($order->seller->user->name ?? $order->seller->name ?? ''));
        }

        $subtotal = (float) ($order->subtotal ?? 0);
        $discount = (float) ($order->descuento ?? $order->discount ?? 0);

        return [
            'order' => $order,
            'lang' => $lang,
            'logoDataUrl' => $logoDataUrl,
            'notesText' => $notesText,
            'productImageDataUrls' => $productImageDataUrls,
            'title' => (isset($order->tipo_d) && strtoupper((string) $order->tipo_d) === 'C')
                ? ($t['pdf_title_credit_note'] ?? $t['credit_note'] ?? 'CREDIT NOTE')
                : ($t['pdf_title_order'] ?? 'ORDER'),
            'orderNumberLabel' => $t['pdf_order_number'] ?? 'Order #',
            'dateLabel' => $t['pdf_date'] ?? 'Date',
            'vendorLabel' => $t['pdf_vendor'] ?? 'VENDOR',
            'shipToLabel' => $t['pdf_ship_to'] ?? 'SHIP TO',
            'productsLabel' => $t['pdf_products_services'] ?? 'PRODUCTS OR SERVICES',
            'descLabel' => $t['pdf_description'] ?? 'Description',
            'unitPriceLabel' => $t['pdf_unit_price'] ?? 'Unit Price',
            'qtyLabel' => $t['pdf_quantity'] ?? 'Quantity',
            'totalLabel' => $t['pdf_total'] ?? 'Total',
            'subtotalLabel' => $t['pdf_subtotal'] ?? 'Subtotal',
            'discountLabel' => $t['pdf_discount'] ?? 'Discount',
            'notesLabel' => $t['pdf_notes'] ?? 'Notes',
            'imageLabel' => $t['pdf_image'] ?? 'Image',
            'labelPhone' => $t['phone'] ?? 'Phone',
            'labelEmail' => $t['email'] ?? 'Email',
            'labelAddress' => $t['address'] ?? 'Address',
            'labelSeller' => $t['seller'] ?? 'Seller',
            'labelMobile' => $t['cell_phone'] ?? 'Mobile',
            'fmtMoney' => $fmtMoney,
            'fmtQty' => $fmtQty,
            'moneda' => $moneda,
            'companyName' => $companyName,
            'companyAddress' => $companyAddress,
            'companyPhone' => $companyPhone,
            'companyEmail' => $companyEmail,
            'customerName' => $customerName,
            'customerContact' => $customerContact,
            'customerAddress' => $customerAddress,
            'customerPhone' => $customerPhone,
            'customerCell' => $customerCell,
            'customerEmail' => $customerEmail,
            'sellerName' => $sellerName,
            'orderDate' => $order->created ? \App\Support\DisplayDateFormat::format($order->created) : '',
            'subtotal' => $subtotal,
            'discount' => $discount,
            'grandTotal' => (float) ($order->total ?? max(0, $subtotal - $discount)),
            'itemCount' => $order->order_details ? $order->order_details->count() : 0,
        ];
    }

    /**
     * Resolve logo for PDF: company image (by UUID) or default logo from storage.
     */
    private function resolveOrderPdfLogo(Order $order): ?string
    {
        $company = $order->relationLoaded('company') ? $order->company : null;
        if ($company && !empty($company->image)) {
            $img = \App\Models\Image::find($company->image);
            if ($img) {
                try {
                    $storage = $img->getStorage();
                    $path = $img->path ?? ($img->company_id . '/' . $img->name);
                    if ($storage->exists($path)) {
                        $content = $storage->get($path);
                        $mime = $img->mime_type ?? 'image/png';
                        return 'data:' . $mime . ';base64,' . base64_encode($content);
                    }
                } catch (\Throwable $e) {
                    // fallback to default
                }
            }
        }
        try {
            $content = \Illuminate\Support\Facades\Storage::disk()->get('images/logos/logo.png');
            return 'data:image/png;base64,' . base64_encode($content);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Build notes text for PDF from order notes (order_notes table).
     */
    private function resolveOrderPdfNotes(Order $order): string
    {
        $notes = $order->relationLoaded('notes') ? $order->notes : $order->notes()->get();
        if ($notes->isEmpty()) {
            return '';
        }
        $lines = [];
        foreach ($notes as $note) {
            $text = trim((string) ($note->note ?? ''));
            if ($text !== '') {
                $lines[] = $text;
            }
        }
        return implode("\n", $lines);
    }

    /**
     * Build map product_id => dataUrl for product images in PDF.
     * Usa product.default_image (Image / MinIO) o la primera product.images.
     */
    private function resolveOrderPdfProductImages(Order $order): array
    {
        $out = [];
        foreach ($order->order_details ?? [] as $detail) {
            $product = $detail->product ?? null;
            if (!$product) {
                continue;
            }
            $img = null;
            if ($product->relationLoaded('default_image') && $product->default_image) {
                $img = $product->default_image;
            }
            if (!$img && $product->relationLoaded('images') && $product->images->isNotEmpty()) {
                $img = $product->images->first();
            }
            if (!$img) {
                continue;
            }
            $key = $product->pro_id ?? $product->product_id ?? $detail->order_detail_id;
            if (isset($out[$key])) {
                continue;
            }
            try {
                $content = null;
                $storage = $img->getStorage();
                $path = $img->path ?? ($img->company_id && $img->name ? $img->company_id . '/' . $img->name : null);
                if ($path && $storage->exists($path)) {
                    $content = $storage->get($path);
                }
                if ($content === null && $img->company_id && $img->name) {
                    $uploadsPath = \App\Support\UploadsPath::root();
                    $localPath = $uploadsPath . '/products/' . $img->company_id . '/' . $img->name;
                    if (file_exists($localPath) && is_readable($localPath)) {
                        $content = file_get_contents($localPath);
                    }
                }
                if ($content !== null) {
                    $mime = $img->mime_type ?? 'image/png';
                    $out[$key] = 'data:' . $mime . ';base64,' . base64_encode($content);
                }
            } catch (\Throwable $e) {
                // skip this product image
            }
        }
        return $out;
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: "api/v2/order/{order}",
        summary: "Remove the specified Order.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Order"],
        parameters: [
            new OA\Parameter(
                name:        "order",
                description: "The Order identifier to remove.",
                in:          "path",
                required:    true
            ),
        ],
        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: 'message',
                                                     type:     'string',
                                                     example:  'Resource deleted successfully'
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Unauthenticated"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_NOT_FOUND,
                description: "Not found",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Resource not found"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Internal server error"
                                                 )
                                             ]
                             )
            )
        ]
    )]
    public function destroy(Order $order): ?Response
    {
        return $this->destroyResourceEmptyResponse($order);
    }


    #[OA\Get(
        path: "api/v2/order/customer/{customerId}/latest",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Order"],
        parameters: [
            new OA\Parameter(
                name:        "customerId",
                description: "The Latest identifier to retrieve.",
                in:          "path",
                required:    true
            ),
        ],
        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation",
                content:     new OA\JsonContent(
                                 type:  'array',
                                 items: new OA\Items(ref: '#/components/schemas/Order')
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Unauthenticated"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_NOT_FOUND,
                description: "Not found",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Resource not found"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Internal server error"
                                                 )
                                             ]
                             )
            )
        ]
    )]
    public function listLatest(Request $request, $customerId): \Illuminate\Database\Eloquent\Collection
    {
        $limit = $request->get('limit') ?: 5;
        return $this->getBuilder()
            ->where('customer_id', $customerId)
            ->orderBy('order_id', 'desc')
            ->limit($limit)
            ->get();
    }

    #[OA\Get(
        path: "api/v2/order/customer/{customerId}/payed",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Order"],
        parameters: [
            new OA\Parameter(
                name:        "customerId",
                description: "The Payed identifier to retrieve.",
                in:          "path",
                required:    true
            ),
        ],
        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation",
                content:     new OA\JsonContent(
                                 type:  'array',
                                 items: new OA\Items(ref: '#/components/schemas/Order')
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Unauthenticated"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_NOT_FOUND,
                description: "Not found",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Resource not found"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Internal server error"
                                                 )
                                             ]
                             )
            )
        ]
    )]
    public function payedOfCustomer($customerId)
    {
        return $this->getBuilder()
            ->with('order_details')
            ->where('customer_id', $customerId)
            ->where('payment_status', '1')
            ->get();
    }

    #[OA\Get(
        path: "api/v2/order/payed/count",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Order"],

        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation",
                content:     new OA\JsonContent(
                                 type: 'integer'
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Unauthenticated"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_NOT_FOUND,
                description: "Not found",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Resource not found"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Internal server error"
                                                 )
                                             ]
                             )
            )
        ]
    )]
    public function allPayedCount()
    {
        return $this->getBuilder()
            ->with('order_details')
            ->where('payment_status', '1')
            ->count();
    }

    #[OA\Get(
        path: "api/v2/order/{orderId}/product/{productId}",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Order"],
        parameters: [
            new OA\Parameter(
                name:        "orderId",
                description: "The Product identifier to retrieve.",
                in:          "path",
                required:    true
            ),
            new OA\Parameter(
                name:        "productId",
                description: "The Product identifier to retrieve.",
                in:          "path",
                required:    true
            ),
        ],
        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: 'data',
                                                     ref:      '#/components/schemas/Product'
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Unauthenticated"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_NOT_FOUND,
                description: "Not found",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Resource not found"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Internal server error"
                                                 )
                                             ]
                             )
            )
        ]
    )]
    public function getProductDetail($orderId, $productId)
    {
        $order = Order::where('order_id', $orderId)->first();
        return $order->order_details->firstWhere('product_id', $productId);
    }

    #[OA\Get(
        path: "api/v2/order/company/{companyId}/warehouse",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Order"],
        parameters: [
            new OA\Parameter(
                name:        "companyId",
                description: "The Warehouse identifier to retrieve.",
                in:          "path",
                required:    true
            ),
        ],
        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: 'data',
                                                     ref:      '#/components/schemas/Warehouseorder'
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Unauthenticated"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_NOT_FOUND,
                description: "Not found",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Resource not found"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Internal server error"
                                                 )
                                             ]
                             )
            )
        ]
    )]
    public function warehouse($companyId, Request $request)
    {
        $companyId = (int) $companyId;
        $this->sanitizeDocumentOrdersOnListEntry($companyId);

        $perPage = max(1, min(100, (int) $request->input('per_page', $request->input('perPage', 25))));
        $page = max(1, (int) $request->input('page', 1));

        $sellerNameSql = $this->hasWarehouseSellerUserJoin()
            ? "COALESCE(NULLIF(TRIM(seller_user.name), ''), NULLIF(TRIM(seller_user.email), ''), '')"
            : "''";

        $warehouseStaffUserIdSql = $this->warehouseStaffUserIdSql();

        $warehouseOrders = $this->getBuilder()
            ->join('customers', 'customers.customer_id', '=', 'orders.customer_id')
            ->leftJoin('users as warehouse_staff', function ($join) use ($warehouseStaffUserIdSql) {
                $join->whereRaw("warehouse_staff.id = {$warehouseStaffUserIdSql}");
            })
            ->where('orders.company_id', $companyId);

        if (Schema::hasColumn('warehouse_users', 'first_name')) {
            $warehouseOrders->leftJoin('warehouse_users as wu_pivot', function ($join) use ($warehouseStaffUserIdSql) {
                $join->whereRaw("wu_pivot.user_id = {$warehouseStaffUserIdSql}")
                    ->on('wu_pivot.company_id', '=', 'orders.company_id');
            });
            $warehouseUserSql = "COALESCE(NULLIF(TRIM(CONCAT(COALESCE(wu_pivot.first_name,''),' ',COALESCE(wu_pivot.last_name,''))), ''), NULLIF(TRIM(warehouse_staff.name), ''), NULLIF(TRIM(warehouse_staff.email), ''), '')";
        } else {
            $warehouseUserSql = "COALESCE(NULLIF(TRIM(warehouse_staff.name), ''), NULLIF(TRIM(warehouse_staff.email), ''), '')";
        }

        $select = [
            'orders.*',
            DB::raw("{$sellerNameSql} as seller_info"),
            DB::raw("{$warehouseUserSql} as warehouse_user_name"),
            'customers.business_name',
            'customers.name as customer_contact_name',
            'customers.email as customer_email',
            'customers.phone as customer_phone',
            'customers.cell_phone as customer_cell_phone',
            DB::raw("TRIM(CONCAT_WS(', ', NULLIF(customers.dispatch_delivery_address,''), NULLIF(customers.dispatch_address,''), NULLIF(customers.dispatch_city,''), NULLIF(customers.dispatch_state,''))) as customer_address"),
        ];

        if (Schema::hasColumn('orders', 'status_id') && ! Schema::hasColumn('orders', 'order_status')) {
            $select[] = DB::raw('so_wh.cod_status as order_status');
        }

        $warehouseOrders->select($select);

        $this->applyWarehouseSellerUserJoins($warehouseOrders);
        $this->applyWarehouseAssignedStatusScope($warehouseOrders);

        $customerId = $request->input('customer_id', $request->input('customer'));
        if ($customerId !== null && $customerId !== '') {
            $warehouseOrders->where('orders.customer_id', (int) $customerId);
        }

        if ($request->filled('status')) {
            $codStatuses = is_array($request->input('status')) ? $request->input('status') : explode(',', (string) $request->input('status'));
            $codStatuses = array_map('intval', $codStatuses);
            if (Schema::hasColumn('orders', 'status_id')) {
                $idStatuses = StatusOrder::whereIn('cod_status', $codStatuses)->pluck('id_status')->toArray();
                if (! empty($idStatuses)) {
                    $warehouseOrders->whereIn('orders.status_id', $idStatuses);
                }
            } elseif (Schema::hasColumn('orders', 'order_status')) {
                $warehouseOrders->whereIn('orders.order_status', $codStatuses);
            }
        }

        $warehouseUserId = $request->input('warehouse_user_id', $request->input('warehouseUser'));
        if ($warehouseUserId !== null && $warehouseUserId !== '') {
            $warehouseOrders->whereRaw("{$warehouseStaffUserIdSql} = ?", [(int) $warehouseUserId]);
        }

        $warehouseOrders->orderBy('orders.updated', 'DESC');

        $paginator = $warehouseOrders->paginate($perPage, ['*'], 'page', $page);

        $transformed = $paginator->getCollection()->map(function ($order) {
            $row = (object) (is_array($order) ? $order : $order->toArray());
            $row->nombre_cliente = $row->business_name ?? null;
            $row->nombre_contacto = $row->customer_contact_name ?? null;
            $created = $row->created ?? null;
            $updated = $row->updated ?? null;
            if (is_object($created) && method_exists($created, 'format')) {
                $row->created = $created->format('Y-m-d H:i:s');
            }
            if (is_object($updated) && method_exists($updated, 'format')) {
                $row->updated = $updated->format('Y-m-d H:i:s');
            }
            $row->list_date_created = $row->created;
            $row->list_date_updated = $row->updated;
            if (Schema::hasColumn('orders', 'status_id') && isset($row->status_id) && ! isset($row->order_status)) {
                $cod = StatusOrder::query()->where('id_status', $row->status_id)->value('cod_status');
                $row->order_status = $cod !== null ? (int) $cod : 0;
            }

            return $row;
        });

        return $paginator->setCollection($transformed);
    }

    private function hasWarehouseSellerUserJoin(): bool
    {
        return Schema::hasTable('sellers') || Schema::hasColumn('orders', 'user_id');
    }

    /**
     * user_id del operario de almacén: COALESCE(warehouse_user_id, warehouse_id).
     * assign-warehouse escribe en warehouse_id; datos legacy pueden usar warehouse_user_id.
     */
    private function warehouseStaffUserIdSql(): string
    {
        $hasUserCol = Schema::hasColumn('orders', 'warehouse_user_id');
        $hasIdCol = Schema::hasColumn('orders', 'warehouse_id');

        if ($hasUserCol && $hasIdCol) {
            return 'COALESCE(NULLIF(orders.warehouse_user_id, 0), NULLIF(orders.warehouse_id, 0))';
        }
        if ($hasUserCol) {
            return 'orders.warehouse_user_id';
        }
        if ($hasIdCol) {
            return 'orders.warehouse_id';
        }

        return 'NULL';
    }

    /**
     * Vendedor: nombre en users; si existe tabla sellers, COALESCE(orders.user_id, sellers.user_id).
     */
    private function applyWarehouseSellerUserJoins($query): void
    {
        if (Schema::hasTable('sellers')) {
            $query->leftJoin('sellers', 'sellers.id', '=', 'orders.seller_id');
            if (Schema::hasColumn('orders', 'user_id')) {
                $query->leftJoin('users as seller_user', function ($join) {
                    $join->whereRaw('seller_user.id = COALESCE(orders.user_id, sellers.user_id)');
                });
            } else {
                $query->leftJoin('users as seller_user', 'seller_user.id', '=', 'sellers.user_id');
            }
        } elseif (Schema::hasColumn('orders', 'user_id')) {
            $query->leftJoin('users as seller_user', 'seller_user.id', '=', 'orders.user_id');
        }
    }

    /**
     * Solo órdenes en estado “asignado a almacén” (cod. 9): status_id + status_orders u order_status legado.
     */
    private function applyWarehouseAssignedStatusScope($query): void
    {
        $cod = OrderStatusCodes::WAREHOUSE_ASSIGNED->value;
        if (Schema::hasColumn('orders', 'status_id')) {
            $query->join('status_orders as so_wh', 'so_wh.id_status', '=', 'orders.status_id')
                ->where('so_wh.cod_status', $cod);
        } elseif (Schema::hasColumn('orders', 'order_status')) {
            $query->where('orders.order_status', $cod);
        } else {
            $query->whereRaw('1 = 0');
        }
    }

    /**
     * Órdenes con detalle: compañía del token, filtro orders.warehouse_id = user_id (cualquier estado).
     * POST cuerpo JSON únicamente: {"user_id":28}
     */
    public function listOrdersByWarehouseUser(Request $request)
    {
        return $this->queryOrdersByWarehouseUserId($request, false);
    }

    /**
     * Igual pero solo estado warehouse assigned (cod_status 9).
     * POST cuerpo JSON únicamente: {"user_id":28}
     */
    public function listOrdersByWarehouseUserWarehouseAssigned(Request $request)
    {
        return $this->queryOrdersByWarehouseUserId($request, true);
    }

    private function queryOrdersByWarehouseUserId(Request $request, bool $onlyWarehouseAssigned)
    {
        $raw = $request->getContent();
        if ($raw === '' || $raw === null) {
            return response()->json([
                'message' => $this->cmpOrdersApiMessage('list_by_warehouse_user_id_required'),
                'error'   => true,
            ], 422);
        }
        $data = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($data)) {
            return response()->json([
                'message' => $this->cmpOrdersApiMessage('list_by_warehouse_json_invalid'),
                'error'   => true,
            ], 422);
        }
        if (! array_key_exists('user_id', $data)) {
            return response()->json([
                'message' => $this->cmpOrdersApiMessage('list_by_warehouse_user_id_required'),
                'error'   => true,
            ], 422);
        }
        $userId = $data['user_id'];
        if ($userId === null || $userId === '') {
            return response()->json([
                'message' => $this->cmpOrdersApiMessage('list_by_warehouse_user_id_required'),
                'error'   => true,
            ], 422);
        }
        $wid = (int) $userId;
        if ($wid <= 0) {
            return response()->json([
                'message' => $this->cmpOrdersApiMessage('list_by_warehouse_user_id_invalid'),
                'error'   => true,
            ], 422);
        }

        $companyId = $this->resolveAuthenticatedCompanyId($request);
        if ($companyId === null) {
            return $this->companyIdForbiddenResponse($request);
        }
        if (! Schema::hasColumn('orders', 'warehouse_id')) {
            return response()->json([
                'message' => $this->cmpOrdersApiMessage('warehouse_id_column_missing'),
                'error'   => true,
            ], 422);
        }

        $relations = [
            'customer',
            'seller',
            'statusOrder',
            'order_details.product.unit',
            'warehouse',
        ];

        $query = Order::query()
            ->where('company_id', (int) $companyId)
            ->where('warehouse_id', $wid)
            ->with($relations)
            ->orderByDesc('order_id');

        if ($onlyWarehouseAssigned) {
            $query->whereHas('statusOrder', function ($q) {
                $q->where('cod_status', OrderStatusCodes::WAREHOUSE_ASSIGNED->value);
            });
        }

        return OrderListResource::collection($query->get());
    }

    #[OA\Get(
        path: "api/v2/order/company/{companyId}/inventory",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Order"],
        parameters: [
            new OA\Parameter(
                name:        "companyId",
                description: "The Inventory identifier to retrieve.",
                in:          "path",
                required:    true
            ),
        ],
        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: 'data',
                                                     ref:      '#/components/schemas/Inventory'
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Unauthenticated"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_NOT_FOUND,
                description: "Not found",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Resource not found"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Internal server error"
                                                 )
                                             ]
                             )
            )
        ]
    )]
    public function companyInventoryList($companyId)
    {
        return $this->getBuilder()
            ->with(['warehouseUser:user_id,first_name,last_name', 'statusOrder:id_status,cod_status'])
            ->where('tipo_d', 'I')
            ->where('company_id', $companyId)
            ->get(
                ['order_id', 'order_number', 'tipo_d', 'warehouse_user_id', 'created', 'order_comments', 'status_id']
            );
    }

    /**
     * Listado de órdenes de inventario (tipo_d = I): company_id y filtros en el cuerpo JSON (POST).
     * POST /api/v2/order/inventory/orders-list
     */
    public function companyInventoryOrdersListPost(Request $request): JsonResponse
    {
        $payload = $request->json()->all();
        if (! is_array($payload) || $payload === []) {
            $payload = $request->all();
        }
        $companyId = isset($payload['company_id']) ? (int) $payload['company_id'] : 0;
        if ($companyId <= 0) {
            return response()->json([
                'message' => $this->cmpOrdersApiMessage('inventory_orders_list_company_id_required'),
                'error' => true,
            ], 422);
        }

        $user = $request->user();
        if (! $user || ! $user->canAccessCompany($companyId)) {
            return response()->json([
                'message' => $this->cmpOrdersApiMessage('list_by_warehouse_forbidden'),
            ], 403);
        }

        $desde = $payload['desde_fecha'] ?? null;
        $hasta = $payload['hasta_fecha'] ?? null;
        $estado = $payload['estado'] ?? null;
        $warehouseUser = $payload['warehouse_user'] ?? null;
        $sort = $payload['sort'] ?? null;
        $orderTypeRaw = $payload['order_type'] ?? '';
        $orderType = strtoupper((string) $orderTypeRaw) === 'DESC' ? 'DESC' : 'ASC';
        $perPage = min(200, max(1, (int) ($payload['per_page'] ?? 25)));
        $page = max(1, (int) ($payload['page'] ?? 1));

        $hasLegacyWuNames = Schema::hasColumn('warehouse_users', 'first_name');

        $q = Order::query()
            ->where('company_id', $companyId)
            ->where('tipo_d', 'I');

        if ($hasLegacyWuNames) {
            $q->with([
                'warehouse_user' => static function ($rel) {
                    $rel->select('user_id', 'first_name', 'last_name');
                },
                'statusOrder' => static function ($rel) {
                    $rel->select('id_status', 'cod_status');
                },
            ]);
        } else {
            $q->with([
                'warehouse_user' => static function ($rel) {
                    $rel->select('user_id', 'company_id');
                },
                'warehouse_user.user' => static function ($rel) {
                    $rel->select('id', 'name', 'email');
                },
                'statusOrder' => static function ($rel) {
                    $rel->select('id_status', 'cod_status');
                },
            ]);
        }

        $appliedDesde = false;
        if (! empty($desde) && $desde !== 'Invalid date') {
            try {
                $d = Carbon::parse((string) $desde)->subDay()->format('Y-m-d');
                $q->where('created', '>', $d)->where('updated', '>', $d);
                $appliedDesde = true;
            } catch (\Throwable) {
                $appliedDesde = false;
            }
        }
        if (! $appliedDesde) {
            $fecha = Carbon::now()->startOfMonth()->subDay()->format('Y-m-d');
            $q->where('created', '>', $fecha)->where('updated', '>', $fecha);
        }
        if (! empty($hasta) && $hasta !== 'Invalid date') {
            try {
                $h = Carbon::parse((string) $hasta)->addDay()->format('Y-m-d');
                $q->where('created', '<', $h);
            } catch (\Throwable) {
                // fecha hasta inválida: se ignora
            }
        }

        $codes = [];
        if (is_array($estado)) {
            $codes = array_values(array_filter(array_map('intval', $estado)));
        } elseif ($estado !== null && $estado !== '') {
            $codes = [(int) $estado];
        }
        if ($codes !== []) {
            $q->whereHas('statusOrder', static function ($sq) use ($codes) {
                $sq->whereIn('cod_status', $codes);
            });
        }

        $wid = null;
        if ($warehouseUser !== null && $warehouseUser !== '') {
            if (is_array($warehouseUser)) {
                $arr = array_values(array_filter($warehouseUser, static fn ($v) => $v !== null && $v !== ''));
                $wid = $arr !== [] ? (int) $arr[0] : null;
            } else {
                $wid = (int) $warehouseUser;
            }
        }
        if ($wid !== null && $wid > 0) {
            $q->where('warehouse_user_id', $wid);
        }

        switch ($sort) {
            case 'orden_number':
                $q->orderBy('order_id', $orderType);
                break;
            case 'orden_warehouseuser':
                $dir = $orderType === 'DESC' ? 'DESC' : 'ASC';
                if ($hasLegacyWuNames) {
                    $q->orderByRaw(
                        '(SELECT first_name FROM warehouse_users WHERE warehouse_users.user_id = orders.warehouse_user_id LIMIT 1) '.$dir
                    );
                } elseif (Schema::hasTable('users') && Schema::hasColumn('users', 'name')) {
                    $q->orderByRaw(
                        '(SELECT name FROM users WHERE users.id = orders.warehouse_user_id LIMIT 1) '.$dir
                    );
                } else {
                    $q->orderBy('warehouse_user_id', $dir);
                }
                break;
            case 'orden_date':
                $q->orderBy('created', $orderType);
                break;
            case 'orden_comments':
                $q->orderBy('order_comments', $orderType);
                break;
            default:
                $q->orderBy('order_id', 'DESC');
        }

        $totalFiltered = (clone $q)->count();
        $totalCompanyInventory = Order::query()
            ->where('company_id', $companyId)
            ->where('tipo_d', 'I')
            ->count();

        $rows = $q->skip(($page - 1) * $perPage)->take($perPage)->get();

        // El listado MVC usa usuarios tipo depósito (users.id); la relación warehouse_user
        // apunta a warehouse_users.user_id. Si no hay fila en warehouse_users, el nombre queda vacío:
        // resolvemos nombre/email desde users para esos IDs.
        $userIdsForNames = $rows->pluck('warehouse_user_id')->filter(static fn ($id) => (int) $id > 0)->unique()->values()->all();
        $usersById = $userIdsForNames === []
            ? collect()
            : User::query()->whereIn('id', $userIdsForNames)->get(['id', 'name', 'email'])->keyBy(static fn (User $u) => (int) $u->getKey());

        $data = $rows->map(function (Order $o) use ($hasLegacyWuNames, $usersById) {
            $wu = $o->warehouse_user;
            $name = '';
            if ($wu !== null) {
                if ($hasLegacyWuNames) {
                    $name = trim((string) ($wu->first_name ?? '').' '.(string) ($wu->last_name ?? ''));
                } elseif ($wu->relationLoaded('user') && $wu->user !== null) {
                    $name = trim((string) ($wu->user->name ?? ''));
                    if ($name === '' && isset($wu->user->email)) {
                        $name = (string) $wu->user->email;
                    }
                }
            }
            if ($name === '' && $o->warehouse_user_id) {
                $u = $usersById->get((int) $o->warehouse_user_id);
                if ($u !== null) {
                    $name = trim((string) ($u->name ?? ''));
                    if ($name === '' && isset($u->email)) {
                        $name = (string) $u->email;
                    }
                }
            }

            return [
                'order_id' => $o->order_id,
                'order_number' => $o->order_number,
                'tipo_d' => (string) ($o->tipo_d ?? 'I'),
                'warehouse_user_id' => $o->warehouse_user_id,
                'warehouse_user' => $name,
                'created' => $o->created ? $o->created->format('Y-m-d H:i:s') : null,
                'order_comments' => $o->order_comments,
                'order_status' => $o->statusOrder !== null ? (int) $o->statusOrder->cod_status : null,
            ];
        })->values()->all();

        return response()->json([
            'data' => $data,
            'total_filtered' => $totalFiltered,
            'total_company_inventory' => $totalCompanyInventory,
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    /**
     * Categorías con al menos un producto activo (tabla category_product; respaldo products.category_id).
     * POST /api/v2/order/inventory/categories-for-select — cuerpo JSON: { "company_id": int }
     */
    public function companyInventoryCategoriesForSelectPost(Request $request, InventoryOrderMvcService $service): JsonResponse
    {
        $payload = $request->json()->all();
        if (! is_array($payload) || $payload === []) {
            $payload = $request->all();
        }
        $companyId = isset($payload['company_id']) ? (int) $payload['company_id'] : 0;
        if ($companyId <= 0) {
            return response()->json([
                'message' => $this->cmpOrdersApiMessage('inventory_orders_list_company_id_required'),
                'error' => true,
            ], 422);
        }

        $user = $request->user();
        if (! $user || ! $user->canAccessCompany($companyId)) {
            return response()->json([
                'message' => $this->cmpOrdersApiMessage('list_by_warehouse_forbidden'),
            ], 403);
        }

        return response()->json(['data' => $service->categoriesForAddSelect($companyId)]);
    }

    #[OA\Get(
        path: "api/v2/order/company/{companyId}/getOrderNumber/orderType/{type}",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Order"],
        parameters: [
            new OA\Parameter(
                name:        "companyId",
                description: "The OrderType identifier to retrieve.",
                in:          "path",
                required:    true
            ),
            new OA\Parameter(
                name:        "type",
                description: "The OrderType identifier to retrieve.",
                in:          "path",
                required:    true
            ),
        ],
        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: 'ordernumber',
                                                     type:     'string',
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Unauthenticated"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_NOT_FOUND,
                description: "Not found",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Resource not found"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Internal server error"
                                                 )
                                             ]
                             )
            )
        ]
    )]
    public function generateOrderNumber(Request $request, $companyId, $orderType)
    {
        $length = $request->get('length') ?? 6;
        $prefix = 'ORD';
        if ($orderType == 'C') {
            $prefix = 'OCN';
        }

        $randomString = $this->getNextPossibleOrderNumber($companyId, $length, $prefix);
        return response()->json(['order_number' => "{$prefix}{$randomString}"]);
    }


    #[OA\Get(
        path: "api/v2/order/company/{companyId}/customer/{customerId}/previouslyOrderedProducts",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Order"],
        parameters: [
            new OA\Parameter(
                name:        "companyId",
                description: "The PreviouslyOrderedProduct identifier to retrieve.",
                in:          "path",
                required:    true
            ),
            new OA\Parameter(
                name:        "customerId",
                description: "The PreviouslyOrderedProduct identifier to retrieve.",
                in:          "path",
                required:    true
            ),
        ],
        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: 'data',
                                                     ref:      '#/components/schemas/Order'
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Unauthenticated"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_NOT_FOUND,
                description: "Not found",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Resource not found"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Internal server error"
                                                 )
                                             ]
                             )
            )
        ]
    )]
    public function previouslyOrderedProducts(Request $request, $companyId, $customerId)
    {
        $statusList = $request->input('status_list');
        $productId = $request->input('product_id');

        // La estrella filtra orders.status_id con los códigos de OrderStatusCodes
        // (3, 4, 8, 12). En status_orders esos números no son el mismo campo:
        // id_status 3 = Pending (cod_status 2), mientras cod_status 3 = Dispatched
        // (id_status 5). El modal tiene que incluir ambos para mostrar la orden
        // que encendió la estrella y las ventas en el estado de negocio real.
        $query = DB::table('orders as o')
            ->join('order_details as od', 'o.order_id', '=', 'od.order_id')
            ->join('products as p', 'od.product_id', '=', 'p.pro_id')
            ->join('status_orders as so', 'so.id_status', '=', 'o.status_id')
            ->select(
                'o.order_id',
                DB::raw('MIN(so.cod_status) as order_status'),
                DB::raw('MIN(o.created) as created'),
                DB::raw('MIN(o.order_number) as order_number'),
                'od.product_id',
                DB::raw('MIN(p.name) as name'),
                DB::raw('MIN(od.sale_price) as sale_price')
            )
            ->where('o.company_id', $companyId)
            ->where('o.customer_id', $customerId);

        if (!empty($statusList)) {
            $codes = array_values(array_unique(array_map('intval', (array) $statusList)));
            $query->where(function ($q) use ($codes) {
                $q->whereIn('o.status_id', $codes)
                    ->orWhereIn('so.cod_status', $codes);
            });
        } else {
            $query->where(function ($q) {
                $q->where('o.status_id', 12)->orWhere('so.cod_status', 12);
            });
        }

        if ($productId) {
            $query->where('od.product_id', $productId);
        }

        $query->groupBy('o.order_id', 'od.product_id')
            ->orderByDesc('o.order_id');

        $result = $query->get();

        return response()->json($result);
    }

    #[OA\Get(
        path: "api/v2/order/{orderId}/details",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Order"],
        parameters: [
            new OA\Parameter(
                name:        "orderId",
                description: "The Detail identifier to retrieve.",
                in:          "path",
                required:    true
            ),
        ],
        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: 'data',
                                                     ref:      '#/components/schemas/Orderdetail'
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Unauthenticated"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_NOT_FOUND,
                description: "Not found",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Resource not found"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Internal server error"
                                                 )
                                             ]
                             )
            )
        ]
    )]
    public function details($orderId)
    {
        $productId = request()->get('productId', null);

        $order = $this->getBuilder()
            ->where('order_id', $orderId)
            ->sole();

        $detailQuery = $order->order_details()
            ->when($productId, function ($query, $productId) {
                return $query->where('product_id', $productId);
            });

        return $detailQuery->get();
    }

    #[OA\Get(
        path: "api/v2/order/{orderId}/total-details",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Order"],
        parameters: [
            new OA\Parameter(
                name:        "orderId",
                description: "The Total detail identifier to retrieve.",
                in:          "path",
                required:    true
            ),
        ],
        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: 'total_orden',
                                                     type:     'number'
                                                 ),
                                                 new OA\Property(
                                                     property: 'discount_total',
                                                     type:     'number'
                                                 ),
                                                 new OA\Property(
                                                     property: 'sub_total',
                                                     type:     'number'
                                                 ),
                                                 new OA\Property(
                                                     property: 'cantidad_productos',
                                                     type:     'number'
                                                 ),
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Unauthenticated"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_NOT_FOUND,
                description: "Not found",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Resource not found"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Internal server error"
                                                 )
                                             ]
                             )
            )
        ]
    )]
    public function totalDetails($orderId): array
    {
        $order = $this->getMainModelInstance($orderId);

        return $order->getTotals(request()->has('formated'));
    }

    /**
     * Actualiza cantidad entregada de un ítem de orden (legado: updateDeliveredItem_post).
     * POST /api/v2/order/update-delivered-item
     * Body JSON: order_id, product_id, delivered_qty|delivered_quantity, delivered_pack
     *
     * Preferir: POST /api/v2/warehouse/update-delivered-item
     */
    #[OA\Post(
        path: 'api/v2/order/update-delivered-item',
        summary: 'Actualizar ítem entregado (alias; preferir /warehouse/update-delivered-item)',
        description: 'Alias de POST /api/v2/warehouse/update-delivered-item. Mantiene compatibilidad con clientes que usan el path bajo order.',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseOrders'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['order_id', 'product_id'],
                properties: [
                    new OA\Property(property: 'company_id', type: 'integer', example: 1),
                    new OA\Property(property: 'order_id', type: 'integer', example: 123),
                    new OA\Property(property: 'product_id', type: 'integer', example: 456),
                    new OA\Property(property: 'delivered_qty', type: 'number', example: 10),
                    new OA\Property(property: 'delivered_quantity', type: 'number', example: 10),
                    new OA\Property(property: 'delivered_pack', type: 'number', nullable: true, example: 2),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Ítem actualizado'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Sin permiso'),
            new OA\Response(response: 404, description: 'Orden o detalle no encontrado'),
            new OA\Response(response: 422, description: 'Campos faltantes'),
        ]
    )]
    public function updateDeliveredItem(Request $request, UpdateDeliveredItemService $service): JsonResponse
    {
        $authCompanyId = $this->resolveAuthenticatedCompanyId($request);
        if ($authCompanyId === null) {
            return response()->json([
                'status' => '0',
                'success' => false,
                'message' => $this->cmpOrdersApiMessage('update_delivered_item_forbidden'),
                'error' => true,
            ], 403);
        }

        /** @var User|null $user */
        $user = $request->user();
        if (! $user instanceof User) {
            return response()->json([
                'status' => '0',
                'success' => false,
                'message' => $this->cmpOrdersApiMessage('update_delivered_item_forbidden'),
                'error' => true,
            ], 403);
        }

        $orderId = (int) ($request->input('order_id') ?? 0);
        $order = Order::query()->where('order_id', $orderId)->first();
        if ($order && ! $this->userMayUpdateOrderStatusByNumber($user, $authCompanyId, $order)) {
            return response()->json([
                'status' => '0',
                'success' => false,
                'message' => $this->cmpOrdersApiMessage('update_delivered_item_forbidden'),
                'error' => true,
            ], 403);
        }

        try {
            $result = $service->handle([
                'order_id' => $request->input('order_id'),
                'product_id' => $request->input('product_id'),
                'delivered_qty' => $request->input('delivered_qty', $request->input('delivered_quantity')),
                'delivered_pack' => $request->input('delivered_pack'),
            ], $authCompanyId);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'status' => '0',
                'success' => false,
                'message' => $this->cmpOrdersApiMessage('update_delivered_item_missing_fields'),
                'error' => true,
            ], 422);
        } catch (RuntimeException $e) {
            $key = $e->getMessage() === 'order_detail_not_found'
                ? 'update_delivered_item_detail_not_found'
                : 'update_delivered_item_order_not_found';

            return response()->json([
                'status' => '0',
                'success' => false,
                'message' => $this->cmpOrdersApiMessage($key),
                'error' => true,
            ], 404);
        }

        $list = [
            'delivered_quantity' => $result['delivered_quantity'],
            'delivered_pack' => $result['delivered_pack'],
        ];

        return response()->json([
            'status' => '1',
            'success' => true,
            'message' => $this->cmpOrdersApiMessage('update_delivered_item_success'),
            'order_id' => $result['order_id'],
            'product_id' => $result['product_id'],
            'detail_id' => $result['detail_id'],
            'delivered_id' => $result['delivered_id'],
            'list' => $list,
            'update' => true,
        ]);
    }

    /**
     * POST JSON: { "order_id": 147, "warehouse_id": 28 }
     * warehouse_id = user_id del usuario con rol warehouse; se guarda tal cual en orders.warehouse_id
     */
    public function assignWarehouseSimple(Request $request): JsonResponse
    {
        $orderId = $request->input('order_id');
        $warehouseId = $request->input('warehouse_id');
        if ($orderId === null || $orderId === '' || $warehouseId === null || $warehouseId === '') {
            return response()->json([
                'message' => $this->cmpOrdersApiMessage('assign_warehouse_missing_ids'),
                'error'   => true,
            ], 422);
        }

        $authCompanyId = $this->resolveAuthenticatedCompanyId($request);
        if ($authCompanyId === null) {
            return response()->json([
                'message' => $this->cmpOrdersApiMessage('warehouse_assign_forbidden'),
                'error'   => true,
            ], 403);
        }

        $order = Order::query()->where('order_id', (int) $orderId)->first();
        if (! $order) {
            return response()->json([
                'message' => $this->cmpOrdersApiMessage('assign_warehouse_order_not_found'),
                'error'   => true,
            ], 404);
        }

        if ((int) $order->company_id !== (int) $authCompanyId) {
            return response()->json([
                'message' => $this->cmpOrdersApiMessage('warehouse_assign_forbidden'),
                'error'   => true,
            ], 403);
        }

        if (! Schema::hasColumn('orders', 'warehouse_id')) {
            return response()->json([
                'message' => $this->cmpOrdersApiMessage('warehouse_id_column_missing'),
                'error'   => true,
            ], 500);
        }

        $userId = (int) $warehouseId;
        $isWarehouseUser = DB::table('company_user')
            ->where('company_id', $order->company_id)
            ->where('user_id', $userId)
            ->where('type', CompanyUserTypes::Warehouse->value)
            ->exists();

        if (! $isWarehouseUser) {
            return response()->json([
                'message' => $this->cmpOrdersApiMessage('assign_warehouse_warehouse_invalid'),
                'error'   => true,
            ], 422);
        }

        $statusId = StatusOrder::getIdByCode(OrderStatusCodes::WAREHOUSE_ASSIGNED);
        if ($statusId === false) {
            $statusId = $order->status_id;
        }

        $order->warehouse_id = $userId;
        if (Schema::hasColumn('orders', 'warehouse_user_id')) {
            $order->warehouse_user_id = $userId;
        }
        if (Schema::hasColumn('orders', 'warehouse_assign_date')) {
            $order->warehouse_assign_date = now();
        }
        $order->status_id = (int) $statusId;
        $order->save();

        return response()->json([
            'data'    => $order->fresh(),
            'message' => $this->cmpOrdersApiMessage('warehouse_assign_api_success'),
        ]);
    }

    /**
     * Envía la orden por correo al cliente (confirmar/rechazar vía token).
     * POST /api/v2/order/sendOrder
     */
    public function sendOrder(Request $request, OrderSendEmailService $service): JsonResponse
    {
        $authCompanyId = $this->resolveAuthenticatedCompanyId($request);
        if ($authCompanyId === null) {
            return response()->json([
                'message' => $this->cmpOrdersApiMessage('warehouse_assign_forbidden'),
                'error'   => true,
            ], 403);
        }

        /** @var User|null $user */
        $user = $request->user();
        if (! $user instanceof User) {
            return response()->json([
                'message' => $this->cmpOrdersApiMessage('warehouse_assign_forbidden'),
                'error'   => true,
            ], 403);
        }

        $result = $service->send($request, $authCompanyId, $user);

        return response()->json([
            'message' => $result['message'],
            'success' => $result['success'],
        ], $result['status']);
    }

    /**
     * POST /api/v2/order/inventory/products-for-add — productos activos para alta de inventario.
     */
    public function companyInventoryProductsForAddPost(Request $request, InventoryOrderMvcService $service): JsonResponse
    {
        $companyId = (int) ($request->input('company_id') ?? 0);
        if ($companyId <= 0 || ! $this->inventoryOrderCompanyAllowed($request, $companyId)) {
            return response()->json(['message' => $this->cmpOrdersApiMessage('list_by_warehouse_forbidden'), 'error' => true], 403);
        }

        return response()->json(['data' => $service->productsForAdd($companyId)]);
    }

    /**
     * POST /api/v2/order/inventory/create
     */
    public function companyInventoryCreatePost(Request $request, InventoryOrderMvcService $service): JsonResponse
    {
        $companyId = (int) ($request->input('company_id') ?? 0);
        if ($companyId <= 0 || ! $this->inventoryOrderCompanyAllowed($request, $companyId)) {
            return response()->json(['message' => $this->cmpOrdersApiMessage('list_by_warehouse_forbidden'), 'error' => true], 403);
        }

        $warehouseUserId = (int) ($request->input('warehouse_user') ?? 0);
        $productList = $request->input('product_list', []);
        if (! is_array($productList)) {
            $productList = [];
        }

        try {
            $orders = $service->createOrders($companyId, $warehouseUserId, $productList);
        } catch (\InvalidArgumentException $e) {
            $code = $e->getMessage();

            return response()->json([
                'success' => false,
                'msg' => $code,
            ], 422);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('inventory order create failed', [
                'company_id' => $companyId,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'msg' => 'inventory_create_failed',
            ], 500);
        }

        $orderIds = [];
        foreach ($orders as $order) {
            $orderIds[] = (int) $order->order_id;
        }

        return response()->json([
            'success' => true,
            'order_id' => $orderIds[0] ?? null,
            'order_ids' => $orderIds,
            'orders_count' => count($orderIds),
            'urlredirect' => null,
        ]);
    }

    /**
     * POST /api/v2/order/inventory/add-one-product
     */
    public function companyInventoryAddOneProductPost(Request $request, InventoryOrderMvcService $service): JsonResponse
    {
        $companyId = (int) ($request->input('company_id') ?? 0);
        if ($companyId <= 0 || ! $this->inventoryOrderCompanyAllowed($request, $companyId)) {
            return response()->json(['success' => false, 'msg' => 'forbidden'], 403);
        }

        $proId = (int) ($request->input('aditional_product') ?? 0);
        $items = $service->productsForAdd($companyId);
        $found = null;
        foreach ($items as $item) {
            if ((int) ($item['pro_id'] ?? 0) === $proId) {
                $found = $item;
                break;
            }
        }

        if ($found === null) {
            return response()->json(['success' => false, 'msg' => 'product_noexist']);
        }

        $product = (object) $found;
        $product->category_name = $product->categories_name ?? '';
        $product->aditional = 1;

        return response()->json(['success' => true, 'products' => [$product]]);
    }

    /**
     * GET /api/v2/order/inventory/order/{orderId}
     */
    public function companyInventoryShow(Request $request, int $orderId): JsonResponse
    {
        $companyId = (int) ($request->query('company_id') ?? 0);
        if ($companyId <= 0 || ! $this->inventoryOrderCompanyAllowed($request, $companyId)) {
            return response()->json(['message' => 'forbidden', 'error' => true], 403);
        }

        $order = Order::query()
            ->where('company_id', $companyId)
            ->where('order_id', $orderId)
            ->where('tipo_d', 'I')
            ->with('statusOrder:id_status,cod_status')
            ->first();

        if ($order === null) {
            return response()->json(['message' => 'not_found', 'error' => true], 404);
        }

        return response()->json([
            'data' => [
                'order_id' => (int) $order->order_id,
                'order_number' => (string) $order->order_number,
                'warehouse_user_id' => $order->warehouse_user_id,
                'created' => $order->created ? $order->created->format('Y-m-d H:i:s') : null,
                'order_status' => $order->statusOrder !== null ? (int) $order->statusOrder->cod_status : null,
                'status_id' => (int) ($order->status_id ?? 0),
                'tipo_d' => (string) ($order->tipo_d ?? 'I'),
            ],
        ]);
    }

    /**
     * GET /api/v2/order/inventory/order/{orderId}/details
     */
    public function companyInventoryDetails(Request $request, int $orderId, InventoryOrderMvcService $service): JsonResponse
    {
        $companyId = (int) ($request->query('company_id') ?? 0);
        if ($companyId <= 0 || ! $this->inventoryOrderCompanyAllowed($request, $companyId)) {
            return response()->json(['message' => 'forbidden', 'error' => true], 403);
        }

        $rows = $service->orderDetails($companyId, $orderId);
        if ($rows === [] && ! Order::query()->where('company_id', $companyId)->where('order_id', $orderId)->where('tipo_d', 'I')->exists()) {
            return response()->json(['message' => 'not_found', 'error' => true], 404);
        }

        return response()->json(['data' => $rows]);
    }

    /**
     * DELETE /api/v2/order/inventory/detail/{detailId}
     */
    public function companyInventoryDeleteDetail(Request $request, int $detailId): JsonResponse
    {
        $companyId = (int) ($request->input('company_id') ?? $request->query('company_id') ?? 0);
        if ($companyId <= 0 || ! $this->inventoryOrderCompanyAllowed($request, $companyId)) {
            return response()->json(['success' => false, 'msg' => 'forbidden'], 403);
        }

        $detail = OrderDetail::query()->where('detail_id', $detailId)->first();
        if ($detail === null) {
            return response()->json(['success' => false, 'msg' => 'not_found'], 404);
        }

        $order = Order::query()
            ->where('order_id', $detail->order_id)
            ->where('company_id', $companyId)
            ->with('statusOrder:id_status,cod_status')
            ->first();

        if ($order === null || (string) ($order->tipo_d ?? '') !== 'I') {
            return response()->json(['success' => false, 'msg' => 'order_typeI'], 422);
        }

        $codStatus = $order->statusOrder !== null ? (int) $order->statusOrder->cod_status : 0;
        if ($codStatus !== OrderStatusCodes::PENDING->value) {
            return response()->json(['success' => false, 'msg' => 'order_type_pending'], 422);
        }

        $detail->delete();

        return response()->json(['success' => true]);
    }

    /**
     * POST /api/v2/order/inventory/export-rows
     */
    public function companyInventoryExportRowsPost(Request $request, InventoryOrderMvcService $service): JsonResponse
    {
        $companyId = (int) ($request->input('company_id') ?? 0);
        if ($companyId <= 0 || ! $this->inventoryOrderCompanyAllowed($request, $companyId)) {
            return response()->json(['message' => 'forbidden', 'error' => true], 403);
        }

        $orderIds = $request->input('order_ids', []);
        if (! is_array($orderIds)) {
            $orderIds = [];
        }

        return response()->json(['data' => $service->exportRows($companyId, $orderIds)]);
    }

    /**
     * GET /api/v2/order/warehouse/status-list?lang_id=
     */
    public function companyWarehouseMvcStatusList(Request $request, WarehouseOrderMvcService $service): JsonResponse
    {
        $langId = max(1, (int) $request->query('lang_id', 1));

        return response()->json(['data' => $service->warehouseStatusList($langId)]);
    }

    /**
     * GET /api/v2/order/warehouse/order/{orderId}?company_id=
     */
    public function companyWarehouseMvcShow(Request $request, int $orderId, WarehouseOrderMvcService $service): JsonResponse
    {
        $companyId = (int) ($request->query('company_id') ?? 0);
        if ($companyId <= 0 || ! $this->mvcCompanyOrderAllowed($request, $companyId)) {
            return response()->json(['message' => 'forbidden', 'error' => true], 403);
        }

        $payload = $service->orderShowPayload($companyId, $orderId);
        if ($payload === null) {
            return response()->json(['message' => 'not_found', 'error' => true], 404);
        }

        $payload['currency_symbol'] = $service->companyCurrencySymbol($companyId);

        return response()->json(['data' => $payload]);
    }

    /**
     * GET /api/v2/order/warehouse/order/{orderId}/details?company_id=
     */
    public function companyWarehouseMvcDetails(Request $request, int $orderId, WarehouseOrderMvcService $service): JsonResponse
    {
        $companyId = (int) ($request->query('company_id') ?? 0);
        if ($companyId <= 0 || ! $this->mvcCompanyOrderAllowed($request, $companyId)) {
            return response()->json(['message' => 'forbidden', 'error' => true], 403);
        }

        if ($service->findCompanyOrder($companyId, $orderId) === null) {
            return response()->json(['message' => 'not_found', 'error' => true], 404);
        }

        return response()->json(['data' => $service->orderDetailRows($companyId, $orderId)]);
    }

    /**
     * GET /api/v2/order/warehouse/previously-ordered?company_id=&customer_id=
     */
    public function companyWarehouseMvcPreviouslyOrdered(Request $request, WarehouseOrderMvcService $service): JsonResponse
    {
        $companyId = (int) ($request->query('company_id') ?? 0);
        $customerId = (int) ($request->query('customer_id') ?? 0);
        if ($companyId <= 0 || ! $this->mvcCompanyOrderAllowed($request, $companyId)) {
            return response()->json(['message' => 'forbidden', 'error' => true], 403);
        }

        return response()->json([
            'data' => $service->previouslyOrderedProducts($companyId, $customerId),
        ]);
    }

    /**
     * GET /api/v2/order/warehouse/company/{companyId}/products-active
     */
    public function companyWarehouseMvcProductsActive(Request $request, int $companyId, WarehouseOrderMvcService $service): JsonResponse
    {
        if ($companyId <= 0 || ! $this->mvcCompanyOrderAllowed($request, $companyId)) {
            return response()->json(['message' => 'forbidden', 'error' => true], 403);
        }

        return response()->json(['data' => $service->activeProductsList($companyId)]);
    }

    private function inventoryOrderCompanyAllowed(Request $request, int $companyId): bool
    {
        return $this->mvcCompanyOrderAllowed($request, $companyId);
    }

    private function mvcCompanyOrderAllowed(Request $request, int $companyId): bool
    {
        $user = $request->user();

        return $user && $user->canAccessCompany($companyId);
    }

    /**
     * Al entrar a listados de órdenes: corrige qty negativas en tipo_d=D, elimina ítems en 0
     * y órdenes vacías, y recalcula montos. También asigna almacenero por defecto a NC sin él.
     */
    private function sanitizeDocumentOrdersOnListEntry(int $companyId): void
    {
        if ($companyId < 1) {
            return;
        }

        try {
            app(EnsurePositiveQtyForDocumentOrdersService::class)
                ->sanitizeCompanyDocumentOrders($companyId);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Order list document sanitize failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);
        }

        $this->assignDefaultWarehouseUserToCreditNotesOnListEntry($companyId);
    }

    /**
     * Notas de crédito (tipo_d=C) sin warehouse_user_id → primer almacenero de la compañía.
     */
    private function assignDefaultWarehouseUserToCreditNotesOnListEntry(int $companyId): void
    {
        if ($companyId < 1 || ! Schema::hasColumn('orders', 'warehouse_user_id')) {
            return;
        }

        try {
            $creditNotesMissingWarehouse = static function ($q) use ($companyId) {
                $q->where('company_id', $companyId)
                    ->whereRaw("UPPER(TRIM(COALESCE(tipo_d, ''))) = ?", ['C'])
                    ->where(static function ($inner) {
                        $inner->whereNull('warehouse_user_id')
                            ->orWhere('warehouse_user_id', 0);
                    });
            };

            if (! Order::query()->where($creditNotesMissingWarehouse)->exists()) {
                return;
            }

            $defaultUserId = $this->resolveFirstCompanyWarehouseUserId($companyId);
            if ($defaultUserId === null || $defaultUserId < 1) {
                return;
            }

            $payload = ['warehouse_user_id' => $defaultUserId];
            if (Schema::hasColumn('orders', 'warehouse_id')) {
                $payload['warehouse_id'] = $defaultUserId;
            }
            if (Schema::hasColumn('orders', 'warehouse_assign_date')) {
                $payload['warehouse_assign_date'] = now();
            }

            Order::query()->where($creditNotesMissingWarehouse)->update($payload);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Credit note default warehouse_user assign failed', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Primer almacenero de la compañía: warehouse_users.user_id, si no company_user tipo warehouse.
     */
    private function resolveFirstCompanyWarehouseUserId(int $companyId): ?int
    {
        if (Schema::hasTable('warehouse_users')) {
            $fromWu = DB::table('warehouse_users')
                ->where('company_id', $companyId)
                ->when(
                    Schema::hasColumn('warehouse_users', 'deleted_at'),
                    static fn ($q) => $q->whereNull('deleted_at')
                )
                ->orderBy('user_id')
                ->value('user_id');
            if ($fromWu) {
                return (int) $fromWu;
            }
        }

        $fromCompanyUser = DB::table('company_user')
            ->where('company_id', $companyId)
            ->where('type', CompanyUserTypes::Warehouse->value)
            ->orderBy('user_id')
            ->value('user_id');

        return $fromCompanyUser ? (int) $fromCompanyUser : null;
    }

    /**
     * Garantiza que el JSON del listado no lleve amount/total negativos en órdenes D
     * (por si la sanitización no pudo persistir o el cliente lee total_amount).
     */
    private function normalizeDocumentOrderListPayload(mixed $result): mixed
    {
        $fixModel = static function ($order) {
            if (! $order instanceof Order) {
                return $order;
            }
            if (strtoupper((string) ($order->tipo_d ?? 'D')) !== 'D') {
                return $order;
            }
            foreach (['amount', 'total_amount', 'discount_a'] as $attr) {
                $val = $order->getAttribute($attr);
                if (is_numeric($val) && (float) $val < 0) {
                    $order->setAttribute($attr, abs((float) $val));
                }
            }
            if ($order->relationLoaded('order_details')) {
                foreach ($order->order_details as $detail) {
                    $qty = (float) ($detail->qty ?? 0);
                    if ($qty < 0) {
                        $detail->setAttribute('qty', abs($qty));
                    }
                }
            }

            return $order;
        };

        if ($result instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
            $result->setCollection(
                $result->getCollection()->map($fixModel)
            );

            return $result;
        }

        if ($result instanceof \Illuminate\Support\Collection) {
            return $result->map($fixModel);
        }

        return $result;
    }

    /**
     * Columna Warehouse del listado MVC = nombre del almacenero (users),
     * desde warehouse_user_id o warehouse_id legado (users.id).
     */
    private function enrichWarehouseUserDisplayOnList(mixed $result): mixed
    {
        $mapOrders = function ($orders) {
            if (! $orders instanceof \Illuminate\Support\Collection) {
                return $orders;
            }

            $userIds = $orders->map(static function ($order) {
                if (! $order instanceof Order) {
                    return 0;
                }
                $uid = (int) ($order->warehouse_user_id ?? 0);
                if ($uid <= 0) {
                    $uid = (int) ($order->warehouse_id ?? 0);
                }

                return $uid;
            })->filter(static fn ($id) => $id > 0)->unique()->values()->all();

            $usersById = $userIds === []
                ? collect()
                : User::query()->whereIn('id', $userIds)->get(['id', 'name', 'email'])->keyBy('id');

            return $orders->map(function ($order) use ($usersById) {
                if (! $order instanceof Order) {
                    return $order;
                }

                $whLine = '';
                if ($order->relationLoaded('warehouse_user') && $order->warehouse_user?->user) {
                    $whLine = trim((string) ($order->warehouse_user->user->name ?? ''));
                    if ($whLine === '') {
                        $whLine = trim((string) ($order->warehouse_user->user->email ?? ''));
                    }
                }

                $uid = (int) ($order->warehouse_user_id ?? 0);
                if ($uid <= 0) {
                    $uid = (int) ($order->warehouse_id ?? 0);
                }
                if ($whLine === '' && $uid > 0) {
                    $u = $usersById->get($uid);
                    if ($u) {
                        $whLine = trim((string) ($u->name ?? ''));
                        if ($whLine === '') {
                            $whLine = trim((string) ($u->email ?? ''));
                        }
                    }
                }

                if ($whLine !== '') {
                    $order->setAttribute('warehouse_user_name', $whLine);
                    $order->setAttribute('warehouse_user_display', $whLine);
                    // El listado MVC lee warehouse.name; forzar nombre del almacenero.
                    $order->setRelation('warehouse', (new \App\Models\Warehouse())->forceFill(['name' => $whLine]));
                }
                if ($uid > 0 && empty($order->warehouse_user_id)) {
                    $order->setAttribute('warehouse_user_id', $uid);
                }

                return $order;
            });
        };

        if ($result instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) {
            $result->setCollection($mapOrders($result->getCollection()));

            return $result;
        }

        if ($result instanceof \Illuminate\Support\Collection) {
            return $mapOrders($result);
        }

        return $result;
    }

    /**
     * Compañía activa para órdenes: prioriza company_id/company del request (sesión MVC)
     * y valida pertenencia en company_user; si no viene, usa la primera compañía del token
     * (p. ej. seller portal sin compId en query).
     */
    private function resolveAuthenticatedCompanyId(Request $request): ?int
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }

        $requested = (int) ($request->input('company_id') ?? $request->input('company') ?? 0);
        if ($requested > 0) {
            return $this->mvcCompanyOrderAllowed($request, $requested) ? $requested : null;
        }

        $fallback = (int) ($user->companies()->first()?->company_id ?? 0);

        return $fallback > 0 ? $fallback : null;
    }

    private function companyIdForbiddenResponse(Request $request): JsonResponse
    {
        $requested = (int) ($request->input('company_id') ?? $request->input('company') ?? 0);
        if ($requested > 0 && $request->user()) {
            return response()->json([
                'message' => 'You are not allowed to access this company.',
                'error'   => 'company_id forbidden.',
            ], 403);
        }

        return response()->json([
            'message' => 'User has no company associated.',
            'error'   => 'company_id is required from token.',
        ], 403);
    }

    private function cmpOrdersApiMessage(string $key): string
    {
        $locale = strtolower((string) request()->header('Accept-Language', request()->header('X-App-Locale', 'en')));
        $file = str_contains($locale, 'es') ? 'es-ES.json' : 'en-EN.json';
        $path = base_path('lang/'.$file);
        if (! is_readable($path)) {
            return $key;
        }
        $data = json_decode(file_get_contents($path), true);

        return $data['cmp-orders'][$key] ?? $key;
    }
}

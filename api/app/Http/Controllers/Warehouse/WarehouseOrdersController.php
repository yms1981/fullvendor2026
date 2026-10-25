<?php

namespace App\Http\Controllers\Warehouse;

use App\Enums\OrderStatusCodes;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWarehouseCreditNoteRequest;
use App\Http\Resources\OrderListResource;
use App\Models\Order;
use App\Models\User;
use App\Services\CreateWarehouseCreditNoteService;
use App\Services\SetWarehouseOrderStatusService;
use App\Services\UpdateDeliveredItemService;
use App\Services\UpdateInventoryStatusService;
use App\Services\UpdateInventoryStockService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use RuntimeException;
use Throwable;

/**
 * APIs de almacén (warehouse):
 * - Órdenes pendientes / terminadas / inventario (con ítems)
 * - Inventario con productos completos + fotos
 * - Crear nota de crédito (sin restricción de roles)
 * - Leer orden completa / full / items
 * - Actualizar ítem entregado (legado updateDeliveredItem_post)
 * - Despacho inventario (legado updateInventoryStatus_post)
 * - Stock de línea inventario (legado updateInventoryStock_post)
 * - Cambiar estado de orden (legado setstatus_post)
 */
#[OA\Tag(
    name: 'WarehouseOrders',
    description: 'APIs de almacén: órdenes, inventario, credit notes, ítems entregados, despacho y cambio de estado'
)]
class WarehouseOrdersController extends Controller
{
    /**
     * GET|POST /api/v2/warehouse/credit-notes/list
     * Listado de notas de crédito (tipo_d=C) con el usuario warehouse que las creó.
     */
    #[OA\Get(
        path: 'api/v2/warehouse/credit-notes/list',
        summary: 'Listar notas de crédito (warehouse)',
        description: 'Órdenes con tipo_d=C. Incluye warehouse_user_id / warehouse_user / warehouse_user_display del usuario que creó la NC (token).',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseOrders'],
        parameters: [
            new OA\Parameter(name: 'company_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'warehouse_user_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'customer_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 25)),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Listado paginado de notas de crédito'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'No autenticado'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Sin acceso a la compañía'),
        ]
    )]
    #[OA\Post(
        path: 'api/v2/warehouse/credit-notes/list',
        summary: 'Listar notas de crédito (JSON body)',
        description: 'Igual que GET; filtros en cuerpo JSON.',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseOrders'],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'company_id', type: 'integer'),
                    new OA\Property(property: 'warehouse_user_id', type: 'integer'),
                    new OA\Property(property: 'customer_id', type: 'integer'),
                    new OA\Property(property: 'page', type: 'integer'),
                    new OA\Property(property: 'per_page', type: 'integer'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Listado paginado de notas de crédito'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'No autenticado'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Sin acceso a la compañía'),
        ]
    )]
    public function listCreditNotes(Request $request): JsonResponse
    {
        $auth = $this->authorizeWarehouseCompany($request);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }
        [, $companyId] = $auth;

        $payload = $this->requestPayload($request);
        $perPage = min(200, max(1, (int) ($payload['per_page'] ?? $payload['perPage'] ?? 25)));
        $page = max(1, (int) ($payload['page'] ?? 1));
        $includeDetails = ! array_key_exists('include_order_details', $payload)
            || filter_var($payload['include_order_details'], FILTER_VALIDATE_BOOLEAN);

        $q = $this->findCompanyOrderQuery($companyId)
            ->where('tipo_d', 'C')
            ->with($this->orderListRelations($includeDetails));

        $customerId = $payload['customer_id'] ?? $payload['customer'] ?? null;
        if ($customerId !== null && $customerId !== '') {
            $q->where('customer_id', (int) $customerId);
        }

        $this->applyWarehouseUserFilter($q, $payload);
        $this->applyDateFilters($q, $payload);

        $q->orderByDesc('updated')->orderByDesc('order_id');

        $paginator = $q->paginate($perPage, ['*'], 'page', $page);
        $rows = OrderListResource::collection($paginator->getCollection())->resolve();

        return response()->json([
            'success' => true,
            'message' => $this->msg('warehouse_credit_notes_list_ok'),
            'data' => $rows,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * POST /api/v2/warehouse/credit-notes
     * Crea nota de crédito (tipo_d=C). Requiere warehouse_user_id → orders.warehouse_user_id.
     * Sin restricción de roles (solo autenticación + acceso a la compañía).
     */
    #[OA\Post(
        path: 'api/v2/warehouse/credit-notes',
        summary: 'Crear nota de crédito (warehouse)',
        description: 'Siempre tipo_d=C (prefijo OCN). Obliga warehouse_user_id (users.id del almacenero) y lo guarda en orders.warehouse_user_id. seller_id opcional. El Bearer alimenta token_create / user_id_token / rol_token.',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseOrders'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['customer_id', 'warehouse_user_id', 'discount_type', 'products'],
                properties: [
                    new OA\Property(property: 'company_id', type: 'integer', example: 1),
                    new OA\Property(property: 'customer_id', type: 'integer', example: 10),
                    new OA\Property(property: 'warehouse_user_id', type: 'integer', example: 677, description: 'users.id del almacenero → orders.warehouse_user_id'),
                    new OA\Property(property: 'seller_id', type: 'integer', nullable: true, description: 'Opcional. Si se omite: vendedor del cliente o default de compañía'),
                    new OA\Property(property: 'discount_type', type: 'integer', enum: [1, 2], example: 1),
                    new OA\Property(property: 'discount', type: 'number', example: 0),
                    new OA\Property(property: 'proforma', type: 'boolean', example: false),
                    new OA\Property(property: 'internal_comments', type: 'string', nullable: true),
                    new OA\Property(property: 'source', type: 'string', example: 'warehouse'),
                    new OA\Property(
                        property: 'products',
                        type: 'array',
                        items: new OA\Items(
                            type: 'object',
                            required: ['pro_id', 'qty', 'price', 'fixed_price'],
                            properties: [
                                new OA\Property(property: 'pro_id', type: 'integer'),
                                new OA\Property(property: 'qty', type: 'number'),
                                new OA\Property(property: 'price', type: 'number'),
                                new OA\Property(property: 'fixed_price', type: 'boolean'),
                                new OA\Property(property: 'comment', type: 'string', nullable: true),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: Response::HTTP_CREATED, description: 'Nota de crédito creada (incluye warehouse_user)'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'No autenticado'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Sin acceso a la compañía'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Validación'),
            new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: 'Error al crear'),
        ]
    )]
    public function createCreditNote(
        StoreWarehouseCreditNoteRequest $request,
        CreateWarehouseCreditNoteService $service
    ): JsonResponse {
        $auth = $this->authorizeWarehouseCompany($request);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }
        [$user, $companyId] = $auth;
        // warehouse_user_id del body → orders.warehouse_user_id
        // user del token → user_id_token / token_create / rol_token

        try {
            $order = $service->handle($request->validated(), $companyId, (int) $user->id);
        } catch (InvalidArgumentException) {
            return $this->fail($this->msg('warehouse_credit_note_missing_products'), 422);
        } catch (RuntimeException $e) {
            $key = match ($e->getMessage()) {
                'language_required' => 'warehouse_credit_note_language_required',
                'warehouse_user_required' => 'warehouse_credit_note_user_required',
                'seller_unresolved' => 'warehouse_credit_note_seller_unresolved',
                default => 'warehouse_credit_note_error',
            };

            return $this->fail($this->msg($key), 422);
        } catch (Throwable $e) {
            return response()->json([
                'status' => '0',
                'success' => false,
                'message' => $this->msg('warehouse_credit_note_error'),
                'error' => $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'status' => '1',
            'success' => true,
            'message' => $this->msg('warehouse_credit_note_created'),
            'data' => (new OrderListResource($order))->toArray($request),
        ], Response::HTTP_CREATED);
    }

    /**
     * POST /api/v2/warehouse/set-status
     * Equivalente a setstatus_post (legado).
     */
    #[OA\Post(
        path: 'api/v2/warehouse/set-status',
        summary: 'Cambiar estado de una orden (almacén)',
        description: 'Equivalente a setstatus_post. Body: order_id + status (cod_status). Si status=10 se mapea a 9 (Warehouse Assigned), igual que el legado.',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseOrders'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['order_id', 'status'],
                properties: [
                    new OA\Property(property: 'company_id', type: 'integer', example: 1),
                    new OA\Property(property: 'order_id', type: 'integer', example: 123),
                    new OA\Property(property: 'status', type: 'integer', example: 9, description: 'cod_status. Alias: cod_status, order_status. Si es 10 → se guarda como 9'),
                    new OA\Property(property: 'cod_status', type: 'integer', example: 9, description: 'Alias de status'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Estado actualizado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: '1'),
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'order_id', type: 'integer'),
                        new OA\Property(property: 'status_id', type: 'integer'),
                        new OA\Property(property: 'cod_status', type: 'integer'),
                        new OA\Property(
                            property: 'list',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'order_status', type: 'integer', example: 9),
                            ]
                        ),
                        new OA\Property(property: 'where', type: 'string', example: 'order_id=123'),
                        new OA\Property(property: 'update', type: 'boolean', example: true),
                    ]
                )
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'No autenticado'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Sin permiso'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Orden o estado no encontrado'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Campos faltantes'),
        ]
    )]
    public function setStatus(Request $request, SetWarehouseOrderStatusService $service): JsonResponse
    {
        $auth = $this->authorizeWarehouseCompany($request);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }
        [$user, $companyId] = $auth;

        $payload = $this->requestPayload($request);
        $orderId = (int) ($payload['order_id'] ?? $request->input('order_id') ?? 0);
        $order = Order::query()->where('order_id', $orderId)->first();
        if ($order && ! $this->userMayManageWarehouseOrders($user, $companyId, $order)) {
            return $this->fail($this->msg('warehouse_set_status_forbidden'), 403);
        }

        try {
            $result = $service->handle([
                'order_id' => $payload['order_id'] ?? $request->input('order_id'),
                'status' => $payload['status'] ?? $request->input('status'),
                'cod_status' => $payload['cod_status'] ?? $request->input('cod_status'),
                'order_status' => $payload['order_status'] ?? $request->input('order_status'),
            ], $companyId, (int) $user->id);
        } catch (InvalidArgumentException) {
            return $this->fail($this->msg('warehouse_set_status_missing_fields'), 422);
        } catch (RuntimeException $e) {
            $key = match ($e->getMessage()) {
                'status_not_found' => 'warehouse_set_status_status_not_found',
                default => 'warehouse_set_status_order_not_found',
            };

            return $this->fail($this->msg($key), 404);
        }

        return response()->json([
            'status' => '1',
            'success' => true,
            'message' => $this->msg('warehouse_set_status_success'),
            'order_id' => $result['order_id'],
            'status_id' => $result['status_id'],
            'cod_status' => $result['cod_status'],
            'list' => $result['list'],
            'where' => $result['where'],
            'update' => $result['update'],
        ]);
    }

    /**
     * POST /api/v2/warehouse/update-delivered-item
     */
    #[OA\Post(
        path: 'api/v2/warehouse/update-delivered-item',
        summary: 'Actualizar cantidad entregada de un ítem de orden',
        description: 'Equivalente a updateDeliveredItem_post (legado). Actualiza order_details y reemplaza el registro en delivered_orders.',
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
                    new OA\Property(property: 'delivered_qty', type: 'number', example: 10, description: 'Alias: delivered_quantity'),
                    new OA\Property(property: 'delivered_quantity', type: 'number', example: 10),
                    new OA\Property(property: 'delivered_pack', type: 'number', nullable: true, example: 2),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Ítem actualizado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: '1'),
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'order_id', type: 'integer'),
                        new OA\Property(property: 'product_id', type: 'integer'),
                        new OA\Property(property: 'detail_id', type: 'integer'),
                        new OA\Property(property: 'delivered_id', type: 'integer'),
                        new OA\Property(
                            property: 'list',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'delivered_quantity', type: 'number'),
                                new OA\Property(property: 'delivered_pack', type: 'number', nullable: true),
                            ]
                        ),
                        new OA\Property(property: 'update', type: 'boolean', example: true),
                    ]
                )
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'No autenticado'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Sin permiso'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Orden o detalle no encontrado'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Campos faltantes'),
        ]
    )]
    public function updateDeliveredItem(Request $request, UpdateDeliveredItemService $service): JsonResponse
    {
        $auth = $this->authorizeWarehouseCompany($request);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }
        [$user, $companyId] = $auth;

        $orderId = (int) ($request->input('order_id') ?? 0);
        $order = Order::query()->where('order_id', $orderId)->first();
        if ($order && ! $this->userMayManageWarehouseOrders($user, $companyId, $order)) {
            return $this->fail($this->msg('update_delivered_item_forbidden'), 403);
        }

        try {
            $result = $service->handle([
                'order_id' => $request->input('order_id'),
                'product_id' => $request->input('product_id'),
                'delivered_qty' => $request->input('delivered_qty', $request->input('delivered_quantity')),
                'delivered_pack' => $request->input('delivered_pack'),
            ], $companyId);
        } catch (InvalidArgumentException) {
            return $this->fail($this->msg('update_delivered_item_missing_fields'), 422);
        } catch (RuntimeException $e) {
            $key = $e->getMessage() === 'order_detail_not_found'
                ? 'update_delivered_item_detail_not_found'
                : 'update_delivered_item_order_not_found';

            return $this->fail($this->msg($key), 404);
        }

        return response()->json([
            'status' => '1',
            'success' => true,
            'message' => $this->msg('update_delivered_item_success'),
            'order_id' => $result['order_id'],
            'product_id' => $result['product_id'],
            'detail_id' => $result['detail_id'],
            'delivered_id' => $result['delivered_id'],
            'list' => [
                'delivered_quantity' => $result['delivered_quantity'],
                'delivered_pack' => $result['delivered_pack'],
            ],
            'update' => true,
        ]);
    }

    /**
     * POST /api/v2/warehouse/update-inventory-status
     * Equivalente a updateInventoryStatus_post (legado).
     */
    #[OA\Post(
        path: 'api/v2/warehouse/update-inventory-status',
        summary: 'Despachar / actualizar estado de inventario de una orden',
        description: 'Equivalente a updateInventoryStatus_post. Actualiza delivery_status + order_status y, opcionalmente, productlist (delivered_orders + order_details).',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseOrders'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['order_id', 'order_status', 'delivery_status'],
                properties: [
                    new OA\Property(property: 'company_id', type: 'integer', example: 1),
                    new OA\Property(property: 'order_id', type: 'integer', example: 123),
                    new OA\Property(property: 'order_status', type: 'integer', example: 3, description: 'cod_status. Si delivery_status=0 se fuerza a 0'),
                    new OA\Property(property: 'delivery_status', type: 'integer', example: 1),
                    new OA\Property(
                        property: 'productlist',
                        type: 'array',
                        nullable: true,
                        items: new OA\Items(
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'product_id', type: 'integer', example: 456),
                                new OA\Property(property: 'delivered_quantity', type: 'number', example: 10),
                                new OA\Property(property: 'delivered_pack', type: 'number', nullable: true, example: 2),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Orden despachada / actualizada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: '1'),
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'order_id', type: 'integer'),
                        new OA\Property(property: 'order_status', type: 'integer'),
                        new OA\Property(property: 'delivery_status', type: 'integer'),
                        new OA\Property(property: 'status_id', type: 'integer'),
                        new OA\Property(property: 'items_updated', type: 'integer'),
                    ]
                )
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'No autenticado'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Sin permiso'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Orden, estado o detalle no encontrado'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Campos faltantes'),
        ]
    )]
    public function updateInventoryStatus(Request $request, UpdateInventoryStatusService $service): JsonResponse
    {
        $auth = $this->authorizeWarehouseCompany($request);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }
        [$user, $companyId] = $auth;

        $payload = $this->requestPayload($request);
        $orderId = (int) ($payload['order_id'] ?? $request->input('order_id') ?? 0);
        $order = Order::query()->where('order_id', $orderId)->first();
        if ($order && ! $this->userMayManageWarehouseOrders($user, $companyId, $order)) {
            return $this->fail($this->msg('warehouse_inventory_status_forbidden'), 403);
        }

        try {
            $result = $service->handle([
                'order_id' => $payload['order_id'] ?? $request->input('order_id'),
                'order_status' => $payload['order_status'] ?? $request->input('order_status'),
                'delivery_status' => $payload['delivery_status'] ?? $request->input('delivery_status'),
                'productlist' => $payload['productlist'] ?? $payload['product_list'] ?? $request->input('productlist'),
            ], $companyId, (int) $user->id);
        } catch (InvalidArgumentException) {
            return $this->fail($this->msg('warehouse_inventory_status_missing_fields'), 422);
        } catch (RuntimeException $e) {
            $key = match ($e->getMessage()) {
                'status_not_found' => 'warehouse_inventory_status_status_not_found',
                'order_detail_not_found' => 'warehouse_inventory_status_detail_not_found',
                default => 'warehouse_inventory_status_order_not_found',
            };

            return $this->fail($this->msg($key), 404);
        }

        return response()->json([
            'status' => '1',
            'success' => true,
            'message' => $this->msg('warehouse_inventory_status_success'),
            'order_id' => $result['order_id'],
            'order_status' => $result['order_status'],
            'delivery_status' => $result['delivery_status'],
            'status_id' => $result['status_id'],
            'items_updated' => $result['items_updated'],
        ]);
    }

    /**
     * POST /api/v2/warehouse/update-inventory-stock
     * Equivalente a updateInventoryStock_post (legado).
     */
    #[OA\Post(
        path: 'api/v2/warehouse/update-inventory-stock',
        summary: 'Actualizar cantidad (qty) de un ítem de inventario',
        description: 'Equivalente a updateInventoryStock_post. Actualiza order_details.qty y pone la orden en estado 16 (IN_PROCESS).',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseOrders'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['order_id', 'product_id', 'warehouse_user_id', 'stock_quantity'],
                properties: [
                    new OA\Property(property: 'company_id', type: 'integer', example: 1),
                    new OA\Property(property: 'order_id', type: 'integer', example: 123),
                    new OA\Property(property: 'product_id', type: 'integer', example: 456),
                    new OA\Property(property: 'warehouse_user_id', type: 'integer', example: 7),
                    new OA\Property(property: 'stock_quantity', type: 'number', example: 12),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Stock de línea actualizado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: '1'),
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'order_id', type: 'integer'),
                        new OA\Property(property: 'product_id', type: 'integer'),
                        new OA\Property(property: 'stock_quantity', type: 'number'),
                        new OA\Property(property: 'stock', type: 'number', nullable: true),
                        new OA\Property(property: 'cod_status', type: 'integer', example: 16),
                        new OA\Property(property: 'status_id', type: 'integer'),
                        new OA\Property(property: 'detail_id', type: 'integer'),
                    ]
                )
            ),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'No autenticado'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Sin permiso'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Orden, producto o detalle no encontrado'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'Campos faltantes'),
        ]
    )]
    public function updateInventoryStock(Request $request, UpdateInventoryStockService $service): JsonResponse
    {
        $auth = $this->authorizeWarehouseCompany($request);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }
        [$user, $companyId] = $auth;

        $payload = $this->requestPayload($request);
        $orderId = (int) ($payload['order_id'] ?? $request->input('order_id') ?? 0);
        $order = Order::query()->where('order_id', $orderId)->first();
        if ($order && ! $this->userMayManageWarehouseOrders($user, $companyId, $order)) {
            return $this->fail($this->msg('warehouse_inventory_stock_forbidden'), 403);
        }

        try {
            $result = $service->handle([
                'order_id' => $payload['order_id'] ?? $request->input('order_id'),
                'product_id' => $payload['product_id'] ?? $request->input('product_id'),
                'warehouse_user_id' => $payload['warehouse_user_id'] ?? $request->input('warehouse_user_id'),
                'stock_quantity' => $payload['stock_quantity'] ?? $request->input('stock_quantity'),
            ], $companyId, (int) $user->id);
        } catch (InvalidArgumentException) {
            return $this->fail($this->msg('warehouse_inventory_stock_missing_fields'), 422);
        } catch (RuntimeException $e) {
            $key = match ($e->getMessage()) {
                'product_not_found' => 'warehouse_inventory_stock_product_not_found',
                'order_detail_not_found' => 'warehouse_inventory_stock_detail_not_found',
                'status_not_found' => 'warehouse_inventory_stock_status_not_found',
                default => 'warehouse_inventory_stock_order_not_found',
            };

            return $this->fail($this->msg($key), 404);
        }

        return response()->json([
            'status' => '1',
            'success' => true,
            'message' => $this->msg('warehouse_inventory_stock_success'),
            'order_id' => $result['order_id'],
            'product_id' => $result['product_id'],
            'stock_quantity' => $result['stock_quantity'],
            'stock' => $result['stock'],
            'cod_status' => $result['cod_status'],
            'status_id' => $result['status_id'],
            'detail_id' => $result['detail_id'],
        ]);
    }

    /**
     * Órdenes pendientes de almacén (cod_status = 9 Warehouse Assigned).
     * GET|POST /api/v2/warehouse/orders/pending
     */
    #[OA\Get(
        path: 'api/v2/warehouse/orders/pending',
        summary: 'Listar órdenes pendientes de almacén',
        description: 'Órdenes con cod_status = 9 (Warehouse Assigned). Excluye inventario (tipo_d = I).',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseOrders'],
        parameters: [
            new OA\Parameter(name: 'company_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'warehouse_user_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'customer_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 25)),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Listado paginado', content: new OA\JsonContent(ref: '#/components/schemas/WarehouseOrdersListResponse')),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'No autenticado'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Sin acceso a la compañía'),
        ]
    )]
    #[OA\Post(
        path: 'api/v2/warehouse/orders/pending',
        summary: 'Listar órdenes pendientes de almacén (JSON body)',
        description: 'Igual que GET; filtros en cuerpo JSON.',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseOrders'],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(ref: '#/components/schemas/WarehouseOrdersListRequest')
        ),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Listado paginado', content: new OA\JsonContent(ref: '#/components/schemas/WarehouseOrdersListResponse')),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'No autenticado'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Sin acceso a la compañía'),
        ]
    )]
    public function pendingOrders(Request $request): JsonResponse
    {
        return $this->listWarehouseOrders($request, [
            OrderStatusCodes::WAREHOUSE_ASSIGNED->value,
        ], excludeInventory: true);
    }

    /**
     * Órdenes terminadas de almacén (cod_status 11 Warehouse Completed + 12 Completed).
     * GET|POST /api/v2/warehouse/orders/completed
     */
    #[OA\Get(
        path: 'api/v2/warehouse/orders/completed',
        summary: 'Listar órdenes terminadas de almacén',
        description: 'Órdenes con cod_status 11 (Warehouse Completed) o 12 (Completed). Excluye inventario (tipo_d = I).',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseOrders'],
        parameters: [
            new OA\Parameter(name: 'company_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'warehouse_user_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'customer_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 25)),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Listado paginado', content: new OA\JsonContent(ref: '#/components/schemas/WarehouseOrdersListResponse')),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'No autenticado'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Sin acceso a la compañía'),
        ]
    )]
    #[OA\Post(
        path: 'api/v2/warehouse/orders/completed',
        summary: 'Listar órdenes terminadas de almacén (JSON body)',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseOrders'],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(ref: '#/components/schemas/WarehouseOrdersListRequest')
        ),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Listado paginado', content: new OA\JsonContent(ref: '#/components/schemas/WarehouseOrdersListResponse')),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'No autenticado'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Sin acceso a la compañía'),
        ]
    )]
    public function completedOrders(Request $request): JsonResponse
    {
        return $this->listWarehouseOrders($request, [
            OrderStatusCodes::WAREHOUSE_COMPLETED->value,
            OrderStatusCodes::COMPLETED->value,
        ], excludeInventory: true);
    }

    /**
     * Órdenes de inventario (tipo_d = I).
     * GET|POST /api/v2/warehouse/orders/inventory
     */
    #[OA\Get(
        path: 'api/v2/warehouse/orders/inventory',
        summary: 'Listar órdenes de inventario',
        description: 'Órdenes con tipo_d = I. Filtros opcionales de fecha, estado y almacenero.',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseOrders'],
        parameters: [
            new OA\Parameter(name: 'company_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'warehouse_user_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'estado', in: 'query', required: false, schema: new OA\Schema(type: 'integer'), description: 'cod_status'),
            new OA\Parameter(name: 'desde_fecha', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'hasta_fecha', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 25)),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Listado paginado', content: new OA\JsonContent(ref: '#/components/schemas/WarehouseOrdersListResponse')),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'No autenticado'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Sin acceso a la compañía'),
        ]
    )]
    #[OA\Post(
        path: 'api/v2/warehouse/orders/inventory',
        summary: 'Listar órdenes de inventario (JSON body)',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseOrders'],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(ref: '#/components/schemas/WarehouseOrdersListRequest')
        ),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Listado paginado', content: new OA\JsonContent(ref: '#/components/schemas/WarehouseOrdersListResponse')),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'No autenticado'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Sin acceso a la compañía'),
        ]
    )]
    public function inventoryOrders(Request $request): JsonResponse
    {
        return $this->listWarehouseOrders($request, [], excludeInventory: false, inventoryOnly: true);
    }

    /**
     * Listado de órdenes de inventario con ítems y producto completo (fotos incluidas).
     * GET|POST /api/v2/warehouse/orders/inventory/with-products
     */
    #[OA\Get(
        path: 'api/v2/warehouse/orders/inventory/with-products',
        summary: 'Inventario con ítems y productos completos + fotos',
        description: 'Órdenes tipo_d=I con order_details y producto completo (atributos, unit, categories, images, default_image).',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseOrders'],
        parameters: [
            new OA\Parameter(name: 'company_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'warehouse_user_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'estado', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'desde_fecha', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'hasta_fecha', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 25)),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Listado paginado con productos completos'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'No autenticado'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Sin acceso a la compañía'),
        ]
    )]
    #[OA\Post(
        path: 'api/v2/warehouse/orders/inventory/with-products',
        summary: 'Inventario con productos completos (JSON body)',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseOrders'],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(ref: '#/components/schemas/WarehouseOrdersListRequest')
        ),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Listado paginado con productos completos'),
        ]
    )]
    public function inventoryOrdersWithProducts(Request $request): JsonResponse
    {
        $auth = $this->authorizeWarehouseCompany($request);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }
        [, $companyId] = $auth;

        $payload = $this->requestPayload($request);
        $perPage = min(200, max(1, (int) ($payload['per_page'] ?? $payload['perPage'] ?? 25)));
        $page = max(1, (int) ($payload['page'] ?? 1));

        $relations = [
            'customer',
            'seller.user',
            'statusOrder',
            'order_details.product.unit',
            'order_details.product.images',
            'order_details.product.default_image',
            'order_details.product.categories',
            'order_details.product.categoryGroup',
            'order_details.product.categorySubgroup',
        ];
        if (Schema::hasColumn('orders', 'warehouse_id')) {
            $relations[] = 'warehouse';
        }
        if (Schema::hasColumn('orders', 'warehouse_user_id')) {
            $relations[] = 'warehouse_user.warehouse';
            $relations[] = 'warehouse_user.user';
        }

        $q = $this->findCompanyOrderQuery($companyId)
            ->where('tipo_d', 'I')
            ->with($relations);

        $this->applyDateFilters($q, $payload);
        $this->applyStatusCodesFilter($q, $payload['estado'] ?? $payload['status'] ?? null);
        $this->applyWarehouseUserFilter($q, $payload);

        $customerId = $payload['customer_id'] ?? $payload['customer'] ?? null;
        if ($customerId !== null && $customerId !== '') {
            $q->where('customer_id', (int) $customerId);
        }

        $q->orderByDesc('updated')->orderByDesc('order_id');

        $paginator = $q->paginate($perPage, ['*'], 'page', $page);

        $rows = $paginator->getCollection()->map(function (Order $order) use ($request) {
            $data = (new OrderListResource($order))->toArray($request);
            $data['order_details'] = [];
            foreach ($order->order_details ?? [] as $detail) {
                $row = $detail->toArray();
                $row['product'] = $this->mapProductFull($detail->product);
                $data['order_details'][] = $row;
            }

            return $data;
        })->values();

        return response()->json([
            'success' => true,
            'message' => $this->msg('warehouse_orders_inventory_products_ok'),
            'data' => $rows,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    /**
     * Órdenes Warehouse Assigned (9) + Completed (11/12) con info completa e ítems.
     * Incluye delivered_quantity / delivered_pack (y alias delivered_qty).
     * GET|POST /api/v2/warehouse/orders/assigned-and-completed
     * No modifica pending/completed existentes.
     */
    #[OA\Get(
        path: 'api/v2/warehouse/orders/assigned-and-completed',
        summary: 'Warehouse Assigned + Completed con info completa',
        description: 'Devuelve órdenes cod_status 9 (warehouse_assigned) y 11/12 (completed) con customer, seller, warehouse, order_details, producto completo/fotos y delivered_quantity/delivered_pack (alias delivered_qty). Excluye inventario (tipo_d=I).',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseOrders'],
        parameters: [
            new OA\Parameter(name: 'company_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'warehouse_user_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'customer_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'desde_fecha', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'hasta_fecha', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 25), description: 'Por grupo (assigned y completed)'),
            new OA\Parameter(name: 'group', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['all', 'assigned', 'completed'], example: 'all')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Listados con info completa'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'No autenticado'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Sin acceso a la compañía'),
        ]
    )]
    #[OA\Post(
        path: 'api/v2/warehouse/orders/assigned-and-completed',
        summary: 'Warehouse Assigned + Completed con info completa (JSON body)',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseOrders'],
        requestBody: new OA\RequestBody(
            required: false,
            content: new OA\JsonContent(ref: '#/components/schemas/WarehouseOrdersListRequest')
        ),
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Listados con info completa'),
        ]
    )]
    public function assignedAndCompletedOrders(Request $request): JsonResponse
    {
        $auth = $this->authorizeWarehouseCompany($request);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }
        [, $companyId] = $auth;

        $payload = $this->requestPayload($request);
        $perPage = min(200, max(1, (int) ($payload['per_page'] ?? $payload['perPage'] ?? 25)));
        $page = max(1, (int) ($payload['page'] ?? 1));
        $group = strtolower((string) ($payload['group'] ?? 'all'));
        if (! in_array($group, ['all', 'assigned', 'completed'], true)) {
            $group = 'all';
        }

        $assignedCodes = [OrderStatusCodes::WAREHOUSE_ASSIGNED->value];
        $completedCodes = [
            OrderStatusCodes::WAREHOUSE_COMPLETED->value,
            OrderStatusCodes::COMPLETED->value,
        ];

        $data = [];
        $meta = [];

        if ($group === 'all' || $group === 'assigned') {
            $assigned = $this->paginateWarehouseOrdersFull(
                $companyId,
                $payload,
                $assignedCodes,
                $perPage,
                $page,
                $request
            );
            $data['warehouse_assigned'] = $assigned['rows'];
            $meta['warehouse_assigned'] = $assigned['meta'];
        }

        if ($group === 'all' || $group === 'completed') {
            $completed = $this->paginateWarehouseOrdersFull(
                $companyId,
                $payload,
                $completedCodes,
                $perPage,
                $page,
                $request
            );
            $data['completed'] = $completed['rows'];
            $meta['completed'] = $completed['meta'];
        }

        return response()->json([
            'success' => true,
            'message' => $this->msg('warehouse_orders_assigned_completed_ok'),
            'data' => $data,
            'meta' => $meta,
        ]);
    }

    /**
     * APIs sencillas: solo warehouse_id → órdenes + ítems (como GET /api/v2/order/list).
     * Siempre incluyen delivered_quantity / delivered_pack / delivered_qty.
     */
    #[OA\Get(
        path: 'api/v2/warehouse/by-warehouse-id/assigned',
        summary: 'Órdenes Warehouse Assigned por warehouse_id',
        description: 'Listado simple (formato order/list) filtrado por warehouse_id. Solo cod_status=9. Ítems con delivered_*.',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseOrders'],
        parameters: [
            new OA\Parameter(name: 'warehouse_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer', example: 28)),
            new OA\Parameter(name: 'company_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Listado'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'warehouse_id requerido'),
        ]
    )]
    #[OA\Post(
        path: 'api/v2/warehouse/by-warehouse-id/assigned',
        summary: 'Órdenes Warehouse Assigned por warehouse_id (JSON)',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseOrders'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['warehouse_id'],
                properties: [
                    new OA\Property(property: 'warehouse_id', type: 'integer', example: 28),
                    new OA\Property(property: 'company_id', type: 'integer', example: 1),
                ]
            )
        ),
        responses: [new OA\Response(response: Response::HTTP_OK, description: 'Listado')]
    )]
    public function listAssignedByWarehouseId(Request $request): JsonResponse
    {
        return $this->listSimpleByWarehouseId($request, 'assigned');
    }

    #[OA\Get(
        path: 'api/v2/warehouse/by-warehouse-id/completed',
        summary: 'Órdenes Completed por warehouse_id',
        description: 'Listado simple (formato order/list) filtrado por warehouse_id. cod_status 11 y 12. Ítems con delivered_*.',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseOrders'],
        parameters: [
            new OA\Parameter(name: 'warehouse_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer', example: 28)),
            new OA\Parameter(name: 'company_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Listado'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'warehouse_id requerido'),
        ]
    )]
    #[OA\Post(
        path: 'api/v2/warehouse/by-warehouse-id/completed',
        summary: 'Órdenes Completed por warehouse_id (JSON)',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseOrders'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['warehouse_id'],
                properties: [
                    new OA\Property(property: 'warehouse_id', type: 'integer', example: 28),
                    new OA\Property(property: 'company_id', type: 'integer', example: 1),
                ]
            )
        ),
        responses: [new OA\Response(response: Response::HTTP_OK, description: 'Listado')]
    )]
    public function listCompletedByWarehouseId(Request $request): JsonResponse
    {
        return $this->listSimpleByWarehouseId($request, 'completed');
    }

    #[OA\Get(
        path: 'api/v2/warehouse/by-warehouse-id/inventory',
        summary: 'Órdenes de inventario por warehouse_id',
        description: 'Listado simple (formato order/list) filtrado por warehouse_id. Solo tipo_d=I. Ítems con delivered_*.',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseOrders'],
        parameters: [
            new OA\Parameter(name: 'warehouse_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer', example: 28)),
            new OA\Parameter(name: 'company_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Listado'),
            new OA\Response(response: Response::HTTP_UNPROCESSABLE_ENTITY, description: 'warehouse_id requerido'),
        ]
    )]
    #[OA\Post(
        path: 'api/v2/warehouse/by-warehouse-id/inventory',
        summary: 'Órdenes de inventario por warehouse_id (JSON)',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseOrders'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['warehouse_id'],
                properties: [
                    new OA\Property(property: 'warehouse_id', type: 'integer', example: 28),
                    new OA\Property(property: 'company_id', type: 'integer', example: 1),
                ]
            )
        ),
        responses: [new OA\Response(response: Response::HTTP_OK, description: 'Listado')]
    )]
    public function listInventoryByWarehouseId(Request $request): JsonResponse
    {
        return $this->listSimpleByWarehouseId($request, 'inventory');
    }

    /**
     * @param  'assigned'|'completed'|'inventory'  $kind
     */
    private function listSimpleByWarehouseId(Request $request, string $kind): JsonResponse
    {
        $auth = $this->authorizeWarehouseCompany($request);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }
        [, $companyId] = $auth;

        $payload = $this->requestPayload($request);
        $warehouseId = $payload['warehouse_id']
            ?? $payload['warehouseId']
            ?? $request->query('warehouse_id')
            ?? null;

        if ($warehouseId === null || $warehouseId === '') {
            return $this->fail($this->msg('warehouse_by_id_required'), 422);
        }
        $wid = (int) $warehouseId;
        if ($wid <= 0) {
            return $this->fail($this->msg('warehouse_by_id_invalid'), 422);
        }
        if (! Schema::hasColumn('orders', 'warehouse_id')) {
            return $this->fail($this->msg('warehouse_id_column_missing'), 422);
        }

        // Mismo shape que GET /api/v2/order/list (+ delivered explícito en ítems).
        $relations = [
            'customer',
            'seller.user',
            'statusOrder',
            'order_details.product.unit',
            'warehouse',
        ];
        if (Schema::hasColumn('orders', 'warehouse_user_id')) {
            $relations[] = 'warehouse_user.warehouse';
            $relations[] = 'warehouse_user.user';
        }

        $q = Order::query()
            ->where('company_id', $companyId)
            ->where(function ($w) use ($wid) {
                // assign-warehouse guarda el user_id en warehouse_id; legacy puede usar warehouse_user_id.
                $w->where('warehouse_id', $wid);
                if (Schema::hasColumn('orders', 'warehouse_user_id')) {
                    $w->orWhere('warehouse_user_id', $wid);
                }
            })
            ->with($relations)
            ->orderByDesc('order_id');

        if ($kind === 'inventory') {
            $q->where('tipo_d', 'I');
            $messageKey = 'warehouse_by_id_inventory_ok';
        } elseif ($kind === 'assigned') {
            $q->where(function ($w) {
                $w->whereNull('tipo_d')->orWhere('tipo_d', '<>', 'I');
            });
            $this->applyStatusCodesFilter($q, [OrderStatusCodes::WAREHOUSE_ASSIGNED->value]);
            $messageKey = 'warehouse_by_id_assigned_ok';
        } else {
            $q->where(function ($w) {
                $w->whereNull('tipo_d')->orWhere('tipo_d', '<>', 'I');
            });
            $this->applyStatusCodesFilter($q, [
                OrderStatusCodes::WAREHOUSE_COMPLETED->value,
                OrderStatusCodes::COMPLETED->value,
            ]);
            $messageKey = 'warehouse_by_id_completed_ok';
        }

        $orders = $q->get();
        $rows = $orders->map(function (Order $order) use ($request) {
            $data = (new OrderListResource($order))->toArray($request);
            // Asegurar delivered en cada ítem (alias delivered_qty).
            $details = [];
            foreach ($data['order_details'] ?? [] as $detail) {
                $qty = (float) ($detail['delivered_quantity'] ?? 0);
                $detail['delivered_quantity'] = $qty;
                $detail['delivered_qty'] = $qty;
                $detail['delivered_pack'] = array_key_exists('delivered_pack', $detail)
                    ? ($detail['delivered_pack'] !== null ? (float) $detail['delivered_pack'] : null)
                    : null;
                $details[] = $detail;
            }
            $data['order_details'] = $details;

            return $data;
        })->values();

        return response()->json([
            'success' => true,
            'message' => $this->msg($messageKey),
            'warehouse_id' => $wid,
            'data' => $rows,
        ]);
    }

    /**
     * @param  list<int>  $codStatuses
     * @param  array<string, mixed>  $payload
     * @return array{rows: list<array<string, mixed>>, meta: array<string, int>}
     */
    private function paginateWarehouseOrdersFull(
        int $companyId,
        array $payload,
        array $codStatuses,
        int $perPage,
        int $page,
        Request $request
    ): array {
        $q = $this->findCompanyOrderQuery($companyId)
            ->where(function ($w) {
                $w->whereNull('tipo_d')->orWhere('tipo_d', '<>', 'I');
            })
            ->with($this->warehouseFullOrderRelations());

        $this->applyStatusCodesFilter($q, $codStatuses);
        $this->applyDateFilters($q, $payload);
        $this->applyWarehouseUserFilter($q, $payload);

        $customerId = $payload['customer_id'] ?? $payload['customer'] ?? null;
        if ($customerId !== null && $customerId !== '') {
            $q->where('customer_id', (int) $customerId);
        }

        $q->orderByDesc('updated')->orderByDesc('order_id');

        $paginator = $q->paginate($perPage, ['*'], 'page', $page);
        $rows = $paginator->getCollection()
            ->map(fn (Order $order) => $this->mapOrderWithDeliveredDetails($order, $request))
            ->values()
            ->all();

        return [
            'rows' => $rows,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'status_codes' => $codStatuses,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private function warehouseFullOrderRelations(): array
    {
        $relations = [
            'customer',
            'seller.user',
            'statusOrder',
            'notes',
            'address',
            'address.city',
            'address.state',
            'address.country',
            'order_details.product.unit',
            'order_details.product.images',
            'order_details.product.default_image',
            'order_details.product.categories',
            'order_details.product.categoryGroup',
            'order_details.product.categorySubgroup',
        ];
        if (Schema::hasColumn('orders', 'warehouse_id')) {
            $relations[] = 'warehouse';
        }
        if (Schema::hasColumn('orders', 'warehouse_user_id')) {
            $relations[] = 'warehouse_user.warehouse';
            $relations[] = 'warehouse_user.user';
        }

        return $relations;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapOrderWithDeliveredDetails(Order $order, Request $request): array
    {
        $data = (new OrderListResource($order))->toArray($request);
        if ($order->relationLoaded('address')) {
            $data['address'] = $order->address ? $order->address->toArray() : null;
        }
        if ($order->relationLoaded('notes')) {
            $data['notes'] = $order->notes->toArray();
        }

        $data['order_details'] = [];
        foreach ($order->order_details ?? [] as $detail) {
            $row = $detail->toArray();
            $deliveredQty = (float) ($detail->delivered_quantity ?? 0);
            $deliveredPack = $detail->delivered_pack !== null ? (float) $detail->delivered_pack : null;
            $row['delivered_quantity'] = $deliveredQty;
            $row['delivered_qty'] = $deliveredQty;
            $row['delivered_pack'] = $deliveredPack;
            $row['product'] = $this->mapProductFull($detail->product);
            $data['order_details'][] = $row;
        }

        return $data;
    }

    /**
     * @param  \App\Models\Product|null  $product
     * @return array<string, mixed>|null
     */
    private function mapProductFull($product): ?array
    {
        if (! $product) {
            return null;
        }

        $data = $product->toArray();
        $data['unit'] = $product->relationLoaded('unit') && $product->unit
            ? $product->unit->toArray()
            : null;
        $data['default_image'] = $product->relationLoaded('default_image')
            ? $product->default_image
            : null;
        $data['images'] = $product->relationLoaded('images')
            ? $product->images
            : [];
        $data['categories'] = $product->relationLoaded('categories')
            ? $product->categories
            : [];
        $data['category_group'] = $product->relationLoaded('categoryGroup') && $product->categoryGroup
            ? $product->categoryGroup->toArray()
            : null;
        $data['category_subgroup'] = $product->relationLoaded('categorySubgroup') && $product->categorySubgroup
            ? $product->categorySubgroup->toArray()
            : null;

        return $data;
    }

    /**
     * Orden completa con ítems (mismo formato que GET /api/v2/order/list).
     * GET /api/v2/warehouse/orders/{orderId}
     */
    #[OA\Get(
        path: 'api/v2/warehouse/orders/{orderId}',
        summary: 'Leer una orden completa con sus ítems',
        description: 'Misma estructura que el listado original de órdenes (OrderListResource): customer, seller, warehouse, status e order_details con producto/sku/unidad.',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseOrders'],
        parameters: [
            new OA\Parameter(name: 'orderId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'company_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Orden completa'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'No autenticado'),
            new OA\Response(response: Response::HTTP_FORBIDDEN, description: 'Sin acceso'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Orden no encontrada'),
        ]
    )]
    public function showOrder(Request $request, int $orderId): JsonResponse
    {
        $auth = $this->authorizeWarehouseCompany($request);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }
        [, $companyId] = $auth;

        $order = $this->findCompanyOrderQuery($companyId)
            ->where('order_id', $orderId)
            ->with($this->orderListRelations(true))
            ->first();

        if (! $order) {
            return $this->fail($this->msg('warehouse_order_not_found'), 404);
        }

        return response()->json([
            'success' => true,
            'message' => $this->msg('warehouse_order_show_ok'),
            'data' => (new OrderListResource($order))->toArray($request),
        ]);
    }

    /**
     * Orden con todas las relaciones de detalle (como GET /api/v2/order/{order}/full).
     * GET /api/v2/warehouse/orders/{orderId}/full
     */
    #[OA\Get(
        path: 'api/v2/warehouse/orders/{orderId}/full',
        summary: 'Leer orden con detalle ampliado',
        description: 'Incluye dirección, empresa, cliente, vendedor, líneas con imágenes de producto, notas e historial de estados.',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseOrders'],
        parameters: [
            new OA\Parameter(name: 'orderId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'company_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Orden ampliada'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Orden no encontrada'),
        ]
    )]
    public function showOrderFull(Request $request, int $orderId): JsonResponse
    {
        $auth = $this->authorizeWarehouseCompany($request);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }
        [, $companyId] = $auth;

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
            'warehouse_user.user',
            'warehouse',
            'order_details',
            'order_details.product',
            'order_details.product.unit',
            'order_details.product.images',
            'order_details.product.default_image',
            'notes',
            'statusOrder',
            'order_status_histories',
            'order_status_histories.status',
            'order_status_histories.user',
        ];

        $order = $this->findCompanyOrderQuery($companyId)
            ->where('order_id', $orderId)
            ->with($with)
            ->first();

        if (! $order) {
            return $this->fail($this->msg('warehouse_order_not_found'), 404);
        }

        $data = (new OrderListResource($order))->toArray($request);
        // Ampliar con relaciones que OrderListResource no aplana.
        $data['address'] = $order->relationLoaded('address') && $order->address
            ? $order->address->toArray()
            : null;
        $data['company'] = $order->relationLoaded('company') && $order->company
            ? $order->company->toArray()
            : null;
        $data['notes'] = $order->relationLoaded('notes')
            ? $order->notes->toArray()
            : [];
        $data['order_status_histories'] = $order->relationLoaded('order_status_histories')
            ? $order->order_status_histories->toArray()
            : [];

        // order_details con imágenes de producto
        $data['order_details'] = [];
        foreach ($order->order_details ?? [] as $detail) {
            $product = $detail->product;
            $row = $detail->toArray();
            $row['product'] = $product ? [
                'pro_id' => $product->pro_id,
                'sku' => $product->sku,
                'name' => $product->name,
                'descriptions' => $product->descriptions ?? null,
                'unit' => $product->relationLoaded('unit') && $product->unit
                    ? [
                        'unit_id' => $product->unit->unit_id,
                        'unit' => $product->unit->unit,
                        'description' => $product->unit->description ?? null,
                    ]
                    : null,
                'default_image' => $product->relationLoaded('default_image') ? $product->default_image : null,
                'images' => $product->relationLoaded('images') ? $product->images : [],
            ] : null;
            $data['order_details'][] = $row;
        }

        return response()->json([
            'success' => true,
            'message' => $this->msg('warehouse_order_show_ok'),
            'data' => $data,
        ]);
    }

    /**
     * Solo ítems / líneas de la orden.
     * GET /api/v2/warehouse/orders/{orderId}/items
     */
    #[OA\Get(
        path: 'api/v2/warehouse/orders/{orderId}/items',
        summary: 'Leer ítems de una orden',
        description: 'Devuelve order_details con producto (sku, name, unit) y cantidades entregadas.',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseOrders'],
        parameters: [
            new OA\Parameter(name: 'orderId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'company_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'product_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'Ítems de la orden'),
            new OA\Response(response: Response::HTTP_NOT_FOUND, description: 'Orden no encontrada'),
        ]
    )]
    public function showOrderItems(Request $request, int $orderId): JsonResponse
    {
        $auth = $this->authorizeWarehouseCompany($request);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }
        [, $companyId] = $auth;

        $order = $this->findCompanyOrderQuery($companyId)
            ->where('order_id', $orderId)
            ->first();

        if (! $order) {
            return $this->fail($this->msg('warehouse_order_not_found'), 404);
        }

        $productId = $request->input('product_id', $request->query('product_id'));
        $details = $order->order_details()
            ->with(['product.unit', 'product.default_image'])
            ->when($productId !== null && $productId !== '', static function ($q) use ($productId) {
                $q->where('product_id', (int) $productId);
            })
            ->get();

        $items = $details->map(static function ($detail) {
            $product = $detail->product;

            return [
                ...$detail->toArray(),
                'product' => $product ? [
                    'pro_id' => $product->pro_id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'descriptions' => $product->descriptions ?? null,
                    'unit' => $product->relationLoaded('unit') && $product->unit
                        ? [
                            'unit_id' => $product->unit->unit_id,
                            'unit' => $product->unit->unit,
                            'description' => $product->unit->description ?? null,
                        ]
                        : null,
                    'default_image' => $product->relationLoaded('default_image') ? $product->default_image : null,
                ] : null,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'message' => $this->msg('warehouse_order_items_ok'),
            'order_id' => $orderId,
            'data' => $items,
        ]);
    }

    /**
     * @param  list<int>  $codStatuses
     */
    private function listWarehouseOrders(
        Request $request,
        array $codStatuses,
        bool $excludeInventory,
        bool $inventoryOnly = false
    ): JsonResponse {
        $auth = $this->authorizeWarehouseCompany($request);
        if ($auth instanceof JsonResponse) {
            return $auth;
        }
        [, $companyId] = $auth;

        $payload = $this->requestPayload($request);
        $perPage = min(200, max(1, (int) ($payload['per_page'] ?? $payload['perPage'] ?? 25)));
        $page = max(1, (int) ($payload['page'] ?? 1));
        $includeDetails = ! array_key_exists('include_order_details', $payload)
            || filter_var($payload['include_order_details'], FILTER_VALIDATE_BOOLEAN);

        $q = $this->findCompanyOrderQuery($companyId)
            ->with($this->orderListRelations($includeDetails));

        if ($inventoryOnly) {
            $q->where('tipo_d', 'I');
            $this->applyDateFilters($q, $payload);
            $this->applyStatusCodesFilter($q, $payload['estado'] ?? $payload['status'] ?? null);
        } else {
            if ($excludeInventory) {
                $q->where(function ($w) {
                    $w->whereNull('tipo_d')->orWhere('tipo_d', '<>', 'I');
                });
            }
            if ($codStatuses !== []) {
                $this->applyStatusCodesFilter($q, $codStatuses);
            }
        }

        $customerId = $payload['customer_id'] ?? $payload['customer'] ?? null;
        if ($customerId !== null && $customerId !== '') {
            $q->where('customer_id', (int) $customerId);
        }

        $this->applyWarehouseUserFilter($q, $payload);

        $q->orderByDesc('updated')->orderByDesc('order_id');

        $paginator = $q->paginate($perPage, ['*'], 'page', $page);
        $rows = OrderListResource::collection($paginator->getCollection())->resolve();

        if ($inventoryOnly) {
            $messageKey = 'warehouse_orders_inventory_ok';
        } elseif ($codStatuses === [OrderStatusCodes::WAREHOUSE_ASSIGNED->value]) {
            $messageKey = 'warehouse_orders_pending_ok';
        } else {
            $messageKey = 'warehouse_orders_completed_ok';
        }

        $meta = [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
        ];
        if (! $inventoryOnly) {
            $meta['status_codes'] = $codStatuses;
        }

        return response()->json([
            'success' => true,
            'message' => $this->msg($messageKey),
            'data' => $rows,
            'meta' => $meta,
        ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\Order>
     */
    private function findCompanyOrderQuery(int $companyId)
    {
        return Order::query()->where('company_id', $companyId);
    }

    /**
     * Relaciones iguales a GET /api/v2/order/list.
     *
     * @return list<string>
     */
    private function orderListRelations(bool $includeOrderDetails): array
    {
        $relations = [
            'customer',
            'seller.user',
            'statusOrder',
        ];
        if ($includeOrderDetails) {
            $relations[] = 'order_details.product.unit';
        }
        if (Schema::hasColumn('orders', 'warehouse_id')) {
            $relations[] = 'warehouse';
        }
        if (Schema::hasColumn('orders', 'warehouse_user_id')) {
            $relations[] = 'warehouse_user.warehouse';
            $relations[] = 'warehouse_user.user';
        }

        return $relations;
    }

    /**
     * @return array{0:User,1:int}|JsonResponse
     */
    private function authorizeWarehouseCompany(Request $request): array|JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        if (! $user instanceof User) {
            return $this->fail($this->msg('update_delivered_item_forbidden'), 403);
        }

        $payload = $this->requestPayload($request);
        $companyId = (int) ($payload['company_id'] ?? $payload['company'] ?? $request->query('company_id') ?? 0);
        if ($companyId <= 0) {
            $companyId = (int) ($user->companies()->first()?->company_id ?? 0);
        }
        if ($companyId <= 0) {
            return $this->fail($this->msg('list_by_warehouse_no_company'), 403);
        }
        if (! $user->canAccessCompany($companyId)) {
            return $this->fail($this->msg('list_by_warehouse_forbidden'), 403);
        }

        return [$user, $companyId];
    }

    private function userMayManageWarehouseOrders(User $user, int $companyId, Order $order): bool
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
     * @return array<string, mixed>
     */
    private function requestPayload(Request $request): array
    {
        $json = $request->json()->all();
        if (is_array($json) && $json !== []) {
            return array_merge($request->query(), $json);
        }

        return array_merge($request->query(), $request->all());
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Order>  $q
     * @param  array<string, mixed>  $payload
     */
    private function applyDateFilters($q, array $payload): void
    {
        $desde = $payload['desde_fecha'] ?? $payload['from'] ?? null;
        $hasta = $payload['hasta_fecha'] ?? $payload['to'] ?? null;
        if (! empty($desde) && $desde !== 'Invalid date') {
            try {
                $d = Carbon::parse((string) $desde)->startOfDay()->format('Y-m-d H:i:s');
                $q->where('created', '>=', $d);
            } catch (\Throwable) {
                // ignore
            }
        }
        if (! empty($hasta) && $hasta !== 'Invalid date') {
            try {
                $h = Carbon::parse((string) $hasta)->endOfDay()->format('Y-m-d H:i:s');
                $q->where('created', '<=', $h);
            } catch (\Throwable) {
                // ignore
            }
        }
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Order>  $q
     * @param  mixed  $estado
     */
    private function applyStatusCodesFilter($q, mixed $estado): void
    {
        $codes = [];
        if (is_array($estado)) {
            $codes = array_values(array_filter(array_map('intval', $estado)));
        } elseif ($estado !== null && $estado !== '') {
            $codes = [(int) $estado];
        }
        if ($codes === []) {
            return;
        }
        $q->whereHas('statusOrder', static function ($sq) use ($codes) {
            $sq->whereIn('cod_status', $codes);
        });
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\Order>  $q
     * @param  array<string, mixed>  $payload
     */
    private function applyWarehouseUserFilter($q, array $payload): void
    {
        $warehouseUser = $payload['warehouse_user']
            ?? $payload['warehouse_user_id']
            ?? $payload['user_id']
            ?? null;
        if ($warehouseUser === null || $warehouseUser === '') {
            return;
        }
        $wid = is_array($warehouseUser)
            ? (int) (array_values(array_filter($warehouseUser))[0] ?? 0)
            : (int) $warehouseUser;
        if ($wid <= 0) {
            return;
        }

        $hasUserCol = Schema::hasColumn('orders', 'warehouse_user_id');
        $hasIdCol = Schema::hasColumn('orders', 'warehouse_id');
        if ($hasUserCol && $hasIdCol) {
            $q->where(function ($w) use ($wid) {
                $w->where('warehouse_user_id', $wid)->orWhere('warehouse_id', $wid);
            });
        } elseif ($hasUserCol) {
            $q->where('warehouse_user_id', $wid);
        } elseif ($hasIdCol) {
            $q->where('warehouse_id', $wid);
        }
    }

    private function fail(string $message, int $status): JsonResponse
    {
        return response()->json([
            'status' => '0',
            'success' => false,
            'message' => $message,
            'error' => true,
        ], $status);
    }

    private function msg(string $key): string
    {
        $locale = strtolower((string) request()->header('Accept-Language', request()->header('X-App-Locale', 'en')));
        $file = str_contains($locale, 'es') ? 'es-ES.json' : 'en-EN.json';
        $path = base_path('lang/'.$file);
        if (! is_readable($path)) {
            return $key;
        }
        $data = json_decode((string) file_get_contents($path), true);

        return $data['cmp-orders'][$key] ?? $key;
    }
}

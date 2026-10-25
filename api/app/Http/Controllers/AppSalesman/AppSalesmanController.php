<?php

namespace App\Http\Controllers\AppSalesman;

use App\Http\Controllers\Controller;
use App\Services\AppSalesman\AppSalesmanCatalogLinkService;
use App\Services\AppSalesman\AppSalesmanOrderListService;
use App\Services\AppSalesman\AppSalesmanProductFifoService;
use App\Services\AppSalesman\AppSalesmanRequestedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

/**
 * Sección AppSalesman: endpoints públicos (sin token / sin Sanctum).
 */
#[OA\Tag(
    name: 'AppSalesman',
    description: 'App vendedor. APIs públicas (sin autenticación).'
)]
class AppSalesmanController extends Controller
{
    public function __construct(
        private readonly AppSalesmanOrderListService $orderListService,
        private readonly AppSalesmanRequestedService $requestedService,
        private readonly AppSalesmanCatalogLinkService $catalogLinkService,
        private readonly AppSalesmanProductFifoService $productFifoService,
    ) {
    }

    /**
     * GET /api/v2/app-salesman/ping
     * Comprueba que la sección AppSalesman responde.
     */
    #[OA\Get(
        path: 'api/v2/app-salesman/ping',
        operationId: 'appSalesmanPing',
        summary: 'Ping AppSalesman (público)',
        description: 'Comprueba que la API AppSalesman está operativa. Sin autenticación.',
        security: [],
        tags: ['AppSalesman'],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'OK',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: '1'),
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
        ]
    )]
    public function ping(): JsonResponse
    {
        return response()->json([
            'status' => '1',
            'success' => true,
            'message' => $this->msg('ping_ok'),
        ]);
    }

    /**
     * POST /api/v2/app-salesman/orderList
     * Réplica del legado orderList_post (mismo JSON de respuesta).
     */
    #[OA\Post(
        path: 'api/v2/app-salesman/orderList',
        operationId: 'appSalesmanOrderList',
        summary: 'Listar órdenes del cliente (legado orderList)',
        description: 'Misma respuesta que el REST CI orderList_post: órdenes tipo_d=D del customer_id con product_list. Público (sin token).',
        security: [],
        tags: ['AppSalesman'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['language_id', 'user_id', 'customer_id'],
                properties: [
                    new OA\Property(property: 'language_id', type: 'integer', example: 2),
                    new OA\Property(property: 'user_id', type: 'integer', example: 15, description: 'ID de usuario del vendedor (requerido por compatibilidad)'),
                    new OA\Property(property: 'customer_id', type: 'integer', example: 608),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Éxito (status=1) o error de negocio/validación (status=0). Siempre HTTP 200 como el legado.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: '1'),
                        new OA\Property(property: 'language_id', type: 'integer', example: 2),
                        new OA\Property(property: 'user_id', type: 'integer', example: 1),
                        new OA\Property(
                            property: 'order_list',
                            type: 'array',
                            items: new OA\Items(type: 'object')
                        ),
                        new OA\Property(property: 'error', type: 'string', example: 'No Order found.'),
                    ]
                )
            ),
        ]
    )]
    public function orderList(Request $request): JsonResponse
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'language_id' => 'required',
            'user_id' => 'required',
            'customer_id' => 'required',
        ]);

        if ($validator->fails()) {
            foreach (['language_id', 'user_id', 'customer_id'] as $field) {
                if ($validator->errors()->has($field)) {
                    return response()->json([
                        'status' => '0',
                        'error' => $this->msg('validation_'.$field),
                    ], Response::HTTP_OK);
                }
            }
        }

        $result = $this->orderListService->listForCustomer((int) $data['customer_id']);

        if (($result['status'] ?? '0') === '0') {
            return response()->json([
                'status' => '0',
                'error' => $this->msg('no_orders_found'),
            ], Response::HTTP_OK);
        }

        return response()->json($result, Response::HTTP_OK);
    }

    /**
     * POST /api/v2/app-salesman/orderListCompany
     * Réplica del legado orderListCompany_post (mismo JSON de respuesta).
     */
    #[OA\Post(
        path: 'api/v2/app-salesman/orderListCompany',
        operationId: 'appSalesmanOrderListCompany',
        summary: 'Listar órdenes de la compañía (legado orderListCompany)',
        description: 'Órdenes tipo_d=D del company_id del mes en curso (por created), con product_list. Público (sin token).',
        security: [],
        tags: ['AppSalesman'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id', 'company_id'],
                properties: [
                    new OA\Property(property: 'language_id', type: 'integer', example: 2, description: 'Opcional; compatibilidad con app legado'),
                    new OA\Property(property: 'user_id', type: 'integer', example: 15, description: 'ID de usuario del vendedor (requerido por compatibilidad)'),
                    new OA\Property(property: 'company_id', type: 'integer', example: 1),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Éxito (status=1) o error de negocio/validación (status=0). Siempre HTTP 200 como el legado.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: '1'),
                        new OA\Property(property: 'language_id', type: 'integer', example: 2),
                        new OA\Property(property: 'user_id', type: 'integer', example: 1),
                        new OA\Property(
                            property: 'order_list',
                            type: 'array',
                            items: new OA\Items(type: 'object')
                        ),
                        new OA\Property(property: 'error', type: 'string', example: 'No Order found.'),
                    ]
                )
            ),
        ]
    )]
    public function orderListCompany(Request $request): JsonResponse
    {
        $data = $request->all();
        // Compatibilidad: el legado a veces enviaba customer_id como company_id en form-urlencoded.
        if (! isset($data['company_id']) && isset($data['customer_id'])) {
            $data['company_id'] = $data['customer_id'];
        }

        $validator = Validator::make($data, [
            'user_id' => 'required',
            'company_id' => 'required',
        ]);

        if ($validator->fails()) {
            foreach (['user_id', 'company_id'] as $field) {
                if ($validator->errors()->has($field)) {
                    return response()->json([
                        'status' => '0',
                        'error' => $this->msg('validation_'.$field),
                    ], Response::HTTP_OK);
                }
            }
        }

        $result = $this->orderListService->listForCompany((int) $data['company_id']);

        if (($result['status'] ?? '0') === '0') {
            return response()->json([
                'status' => '0',
                'error' => $this->msg('no_orders_found'),
            ], Response::HTTP_OK);
        }

        return response()->json($result, Response::HTTP_OK);
    }

    /**
     * POST /api/v2/app-salesman/orderListSeller
     * Órdenes del vendedor (seller_id) logueado, solo mes en curso.
     */
    #[OA\Post(
        path: 'api/v2/app-salesman/orderListSeller',
        operationId: 'appSalesmanOrderListSeller',
        summary: 'Listar órdenes del vendedor logueado (mes en curso)',
        description: 'Filtra orders.seller_id del usuario (sellers.user_id) + company_id, solo mes actual (tipo_d=D). Público (sin token).',
        security: [],
        tags: ['AppSalesman'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id', 'company_id'],
                properties: [
                    new OA\Property(property: 'language_id', type: 'integer', example: 2),
                    new OA\Property(property: 'user_id', type: 'integer', example: 15, description: 'users.id del vendedor logueado'),
                    new OA\Property(property: 'company_id', type: 'integer', example: 1),
                    new OA\Property(property: 'seller_id', type: 'integer', example: 42, description: 'Opcional: sellers.id directo'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Éxito (status=1) o error de negocio/validación (status=0). Siempre HTTP 200.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: '1'),
                        new OA\Property(property: 'language_id', type: 'integer', example: 2),
                        new OA\Property(property: 'user_id', type: 'integer', example: 15),
                        new OA\Property(property: 'seller_id', type: 'integer', example: 42),
                        new OA\Property(
                            property: 'order_list',
                            type: 'array',
                            items: new OA\Items(type: 'object')
                        ),
                        new OA\Property(property: 'error', type: 'string', example: 'No Order found.'),
                    ]
                )
            ),
        ]
    )]
    public function orderListSeller(Request $request): JsonResponse
    {
        $data = $request->all();
        if (! isset($data['company_id']) && isset($data['customer_id'])) {
            $data['company_id'] = $data['customer_id'];
        }

        $validator = Validator::make($data, [
            'user_id' => 'required',
            'company_id' => 'required',
            'seller_id' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            foreach (['user_id', 'company_id'] as $field) {
                if ($validator->errors()->has($field)) {
                    return response()->json([
                        'status' => '0',
                        'error' => $this->msg('validation_'.$field),
                    ], Response::HTTP_OK);
                }
            }
        }

        $sellerId = isset($data['seller_id']) && $data['seller_id'] !== '' && $data['seller_id'] !== null
            ? (int) $data['seller_id']
            : null;

        $result = $this->orderListService->listForSeller(
            (int) $data['company_id'],
            (int) $data['user_id'],
            $sellerId
        );

        if (($result['status'] ?? '0') === '0') {
            $errKey = (string) ($result['error'] ?? 'no_orders_found');
            if ($errKey === 'seller_not_found') {
                return response()->json([
                    'status' => '0',
                    'error' => $this->msg('seller_not_found'),
                ], Response::HTTP_OK);
            }

            return response()->json([
                'status' => '0',
                'error' => $this->msg('no_orders_found'),
            ], Response::HTTP_OK);
        }

        return response()->json($result, Response::HTTP_OK);
    }

    /**
     * POST /api/v2/app-salesman/requested
     * Cantidades solicitadas por producto/cliente (SUM qty) para una compañía.
     */
    #[OA\Post(
        path: 'api/v2/app-salesman/requested',
        operationId: 'appSalesmanRequested',
        summary: 'Cantidades solicitadas por producto y cliente',
        description: 'Agrega qty de order_details en órdenes tipo_d=D filtradas por company_id. Agrupa por company_id, customer_id, product_id y sku. Público (sin token).',
        security: [],
        tags: ['AppSalesman'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['company_id'],
                properties: [
                    new OA\Property(property: 'company_id', type: 'integer', example: 1),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Éxito (status=1) o error de negocio/validación (status=0).',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: '1'),
                        new OA\Property(property: 'company_id', type: 'integer', example: 1),
                        new OA\Property(
                            property: 'requested',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'company_id', type: 'integer', example: 1),
                                    new OA\Property(property: 'customer_id', type: 'integer', example: 608),
                                    new OA\Property(property: 'product_id', type: 'integer', example: 5098),
                                    new OA\Property(property: 'sku', type: 'string', example: 'SKU-1'),
                                    new OA\Property(property: 'qty', type: 'number', example: 12.5),
                                ],
                                type: 'object'
                            )
                        ),
                        new OA\Property(property: 'error', type: 'string', example: 'No requested found.'),
                    ]
                )
            ),
        ]
    )]
    public function requested(Request $request): JsonResponse
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'company_id' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => '0',
                'error' => $this->msg('validation_company_id'),
            ], Response::HTTP_OK);
        }

        $result = $this->requestedService->listForCompany((int) $data['company_id']);

        if (($result['status'] ?? '0') === '0') {
            $errKey = ($result['error'] ?? '') === 'invalid_company_id'
                ? 'validation_company_id'
                : 'no_requested_found';

            return response()->json([
                'status' => '0',
                'error' => $this->msg($errKey),
            ], Response::HTTP_OK);
        }

        return response()->json($result, Response::HTTP_OK);
    }

    /**
     * POST /api/v2/app-salesman/productFifo
     * Capas FIFO detalladas de toda la compañía (para notas de crédito).
     */
    #[OA\Post(
        path: 'api/v2/app-salesman/productFifo',
        operationId: 'appSalesmanProductFifo',
        summary: 'FIFO detalle por orden (toda la compañía)',
        description: 'Listado por capas FIFO de la compañía (solo company_id). Incluye customer_id, sku, product_name, unit, fechas, sales_price, product_sales_price, qty y order_id/order_number. Público (sin token).',
        security: [],
        tags: ['AppSalesman'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['company_id'],
                properties: [
                    new OA\Property(property: 'company_id', type: 'integer', example: 1),
                    new OA\Property(
                        property: 'only_available',
                        type: 'boolean',
                        example: true,
                        description: 'Si true (default), solo capas con qty_total > 0'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Éxito (status=1) o error de negocio/validación (status=0).',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: '1'),
                        new OA\Property(property: 'company_id', type: 'integer', example: 1),
                        new OA\Property(
                            property: 'items',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'customer_id', type: 'integer', example: 608),
                                    new OA\Property(property: 'sku', type: 'string', example: 'SKU-1'),
                                    new OA\Property(property: 'product_id', type: 'integer', example: 5098),
                                    new OA\Property(property: 'product_name', type: 'string', example: 'PRODUCTO EJEMPLO'),
                                    new OA\Property(property: 'unit_id', type: 'integer', example: 1, nullable: true),
                                    new OA\Property(property: 'unit', type: 'string', example: 'UND'),
                                    new OA\Property(property: 'unit_description', type: 'string', example: 'Unidad'),
                                    new OA\Property(property: 'order_id', type: 'integer', example: 1201),
                                    new OA\Property(property: 'order_number', type: 'string', example: 'ORD25000123'),
                                    new OA\Property(property: 'order_date', type: 'string', example: '2026-10-01 12:00:00'),
                                    new OA\Property(property: 'order_created', type: 'string', example: '2026-10-01 12:00:00'),
                                    new OA\Property(property: 'order_updated', type: 'string', example: '2026-10-05 18:30:00'),
                                    new OA\Property(property: 'sales_price', type: 'number', example: 10.5, description: 'Precio de la capa (orden origen); tope NC'),
                                    new OA\Property(property: 'product_sales_price', type: 'number', example: 11.0, description: 'Precio de venta actual del producto'),
                                    new OA\Property(property: 'qty_sales', type: 'number', example: 20),
                                    new OA\Property(property: 'qty_creditnote', type: 'number', example: 5),
                                    new OA\Property(property: 'qty_total', type: 'number', example: 15, description: 'Cantidad aún disponible para NC'),
                                ],
                                type: 'object'
                            )
                        ),
                        new OA\Property(property: 'error', type: 'string', example: 'No FIFO layers found.'),
                    ]
                )
            ),
        ]
    )]
    public function productFifo(Request $request): JsonResponse
    {
        return $this->respondProductFifo($request, 'detail');
    }

    /**
     * POST /api/v2/app-salesman/productFifoSummary
     * Resumen FIFO agregado por customer_id + sku.
     */
    #[OA\Post(
        path: 'api/v2/app-salesman/productFifoSummary',
        operationId: 'appSalesmanProductFifoSummary',
        summary: 'FIFO resumen por cliente y SKU',
        description: 'Agrega capas FIFO por customer_id + sku: customer_id, sku, product_name, unit, qty_sales, qty_credit_notes, qty_total. Sin order_id ni fechas. Solo company_id. Público (sin token).',
        security: [],
        tags: ['AppSalesman'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['company_id'],
                properties: [
                    new OA\Property(property: 'company_id', type: 'integer', example: 1),
                    new OA\Property(
                        property: 'only_available',
                        type: 'boolean',
                        example: true,
                        description: 'Si true (default), solo filas con qty_total > 0'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Éxito (status=1) o error de negocio/validación (status=0).',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: '1'),
                        new OA\Property(property: 'company_id', type: 'integer', example: 1),
                        new OA\Property(
                            property: 'items',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'customer_id', type: 'integer', example: 608),
                                    new OA\Property(property: 'sku', type: 'string', example: 'SKU-1'),
                                    new OA\Property(property: 'product_name', type: 'string', example: 'PRODUCTO EJEMPLO'),
                                    new OA\Property(property: 'unit', type: 'string', example: 'UND'),
                                    new OA\Property(property: 'qty_sales', type: 'number', example: 20),
                                    new OA\Property(property: 'qty_credit_notes', type: 'number', example: 5),
                                    new OA\Property(property: 'qty_total', type: 'number', example: 15),
                                ],
                                type: 'object'
                            )
                        ),
                        new OA\Property(property: 'error', type: 'string'),
                    ]
                )
            ),
        ]
    )]
    public function productFifoSummary(Request $request): JsonResponse
    {
        return $this->respondProductFifo($request, 'summary');
    }

    /**
     * @param  'detail'|'summary'  $mode
     */
    private function respondProductFifo(Request $request, string $mode): JsonResponse
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'company_id' => 'required|integer|min:1',
            'only_available' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => '0',
                'error' => $this->msg('validation_company_id'),
            ], Response::HTTP_OK);
        }

        $onlyAvailable = true;
        if (array_key_exists('only_available', $data)) {
            $onlyAvailable = filter_var($data['only_available'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($onlyAvailable === null) {
                $onlyAvailable = true;
            }
        }

        $companyId = (int) $data['company_id'];
        $result = $mode === 'summary'
            ? $this->productFifoService->summaryByCustomerSku($companyId, $onlyAvailable)
            : $this->productFifoService->listForCompany($companyId, $onlyAvailable);

        if (($result['status'] ?? '0') === '0') {
            $err = (string) ($result['error'] ?? 'no_fifo_found');
            $errKey = match ($err) {
                'invalid_params' => 'validation_company_id',
                'fifo_tables_missing' => 'fifo_tables_missing',
                default => 'no_fifo_found',
            };

            return response()->json([
                'status' => '0',
                'error' => $this->msg($errKey),
            ], Response::HTTP_OK);
        }

        return response()->json($result, Response::HTTP_OK);
    }

    /**
     * POST /api/v2/app-salesman/catalogLink
     * Genera link de catálogo para un cliente (mismo mecanismo que enviar catálogo).
     */
    #[OA\Post(
        path: 'api/v2/app-salesman/catalogLink',
        operationId: 'appSalesmanCatalogLink',
        summary: 'Generar link de catálogo para cliente',
        description: 'Crea token de catálogo (catalog_request + catalog_cart) como el envío de catálogo, sin enviar email. Requiere company_id y customer_id. Opcional catalog_id (si no, usa el catálogo por defecto de la compañía). Público (sin token).',
        security: [],
        tags: ['AppSalesman'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['company_id', 'customer_id'],
                properties: [
                    new OA\Property(property: 'company_id', type: 'integer', example: 1),
                    new OA\Property(property: 'customer_id', type: 'integer', example: 608),
                    new OA\Property(property: 'catalog_id', type: 'integer', example: 12, description: 'Opcional; si se omite se usa el catálogo default/activo de la compañía'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: 'Éxito (status=1) o error de negocio/validación (status=0).',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'string', example: '1'),
                        new OA\Property(property: 'company_id', type: 'integer', example: 1),
                        new OA\Property(property: 'customer_id', type: 'integer', example: 608),
                        new OA\Property(property: 'catalog_id', type: 'integer', example: 12),
                        new OA\Property(property: 'link', type: 'string', example: 'http://localhost/catalogo/producto?token=eyJ...'),
                        new OA\Property(property: 'token', type: 'string'),
                        new OA\Property(property: 'expiration_date', type: 'string', example: '2026-04-10T12:00:00+00:00'),
                        new OA\Property(property: 'created', type: 'string', example: '2026-03-26T12:00:00+00:00'),
                        new OA\Property(property: 'error', type: 'string'),
                    ]
                )
            ),
        ]
    )]
    public function catalogLink(Request $request): JsonResponse
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'company_id' => 'required|integer|min:1',
            'customer_id' => 'required|integer|min:1',
            'catalog_id' => 'nullable|integer|min:1',
        ]);

        if ($validator->fails()) {
            foreach (['company_id', 'customer_id'] as $field) {
                if ($validator->errors()->has($field)) {
                    return response()->json([
                        'status' => '0',
                        'error' => $this->msg('validation_'.$field),
                    ], Response::HTTP_OK);
                }
            }
        }

        $catalogId = isset($data['catalog_id']) && $data['catalog_id'] !== '' && $data['catalog_id'] !== null
            ? (int) $data['catalog_id']
            : null;

        $result = $this->catalogLinkService->createLink(
            (int) $data['company_id'],
            (int) $data['customer_id'],
            $catalogId
        );

        if (($result['status'] ?? '0') === '0') {
            $errKey = (string) ($result['error'] ?? 'catalog_link_failed');

            return response()->json([
                'status' => '0',
                'error' => $this->msg($errKey),
            ], Response::HTTP_OK);
        }

        return response()->json($result, Response::HTTP_OK);
    }

    protected function msg(string $key): string
    {
        $locale = strtolower((string) request()->header('Accept-Language', request()->header('X-App-Locale', 'en')));
        $file = str_contains($locale, 'es') ? 'es-ES.json' : 'en-EN.json';
        $path = base_path('lang/'.$file);
        if (! is_readable($path)) {
            return $key;
        }
        $data = json_decode((string) file_get_contents($path), true);

        return $data['cmp-app-salesman'][$key] ?? $key;
    }

    protected function fail(string $message, int $status = 422): JsonResponse
    {
        return response()->json([
            'status' => '0',
            'success' => false,
            'error' => true,
            'message' => $message,
        ], $status);
    }
}

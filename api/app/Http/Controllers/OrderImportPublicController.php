<?php

namespace App\Http\Controllers;

use App\Enums\OrderNoteTypes;
use App\Enums\OrderStatusCodes;
use App\Http\Controllers\Orders\CreatesOrders;
use App\Http\Requests\ImportOrderPublicRequest;
use App\Http\Requests\ImportOrdersBulkPublicRequest;
use App\Models\Category;
use App\Models\CategoryGroup;
use App\Models\CategorySubgroup;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Language;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\StatusOrder;
use App\Models\Unit;
use App\Models\Vendor;
use App\Models\WarehouseUser;
use Carbon\Carbon;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

/**
 * API pública para subir/importar órdenes desde un ERP (sin Bearer token).
 *
 * POST /api/v2/order/import
 * POST /api/v2/order/import-bulk
 */
class OrderImportPublicController extends Controller
{
    use CreatesOrders;

    /** @var array<string, mixed> Caché por request (import / import-bulk). */
    protected array $importRuntimeCache = [];

    #[OA\Post(
        path: 'api/v2/order/import',
        summary: 'Importar orden desde ERP (público, sin token)',
        description: 'Si el cliente (iderp) no existe, lo crea. Si la orden (norden+año) existe, la actualiza (ítems y status_id del cabezal). Sin ítems válidos → omite. Ítems cantidad/precio 0 se omiten.',
        tags: ['Order'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['company_id', 'cabezal', 'items'],
                properties: [
                    new OA\Property(property: 'company_id', type: 'integer', example: 1),
                    new OA\Property(
                        property: 'cabezal',
                        type: 'object',
                        required: ['iderp', 'fecha', 'norden'],
                        properties: [
                            new OA\Property(property: 'iderp', type: 'string', example: '18568'),
                            new OA\Property(property: 'fecha', type: 'string', example: '2026-09-05'),
                            new OA\Property(property: 'norden', type: 'string', example: 'ERP-1001'),
                            new OA\Property(property: 'notas', type: 'string', example: 'Pedido ERP'),
                            new OA\Property(property: 'descuento', type: 'number', example: 0),
                            new OA\Property(property: 'subtotal', type: 'number', example: 100),
                            new OA\Property(property: 'total', type: 'number', example: 100),
                            new OA\Property(property: 'codigo_estado', type: 'integer', example: 0),
                            new OA\Property(property: 'nombre_estado', type: 'string', example: 'Nuevo'),
                            new OA\Property(property: 'numero_estado', type: 'integer', example: 3),
                            new OA\Property(property: 'seller_id', type: 'integer', example: 1),
                            new OA\Property(property: 'warehouse_user_id', type: 'integer', example: 28),
                            new OA\Property(property: 'seller_name', type: 'string', example: 'Juan Pérez'),
                            new OA\Property(property: 'seller_email', type: 'string', example: 'juan@empresa.com'),
                            new OA\Property(property: 'warehouse_name', type: 'string', example: 'María López'),
                            new OA\Property(property: 'warehouse_email', type: 'string', example: 'almacen@empresa.com'),
                            new OA\Property(property: 'customer_business_name', type: 'string', example: 'ACME INC'),
                            new OA\Property(property: 'customer_name', type: 'string', example: 'John Doe'),
                            new OA\Property(property: 'customer_email', type: 'string', example: 'john@acme.com'),
                            new OA\Property(property: 'customer_phone', type: 'string', example: '555-1234'),
                            new OA\Property(property: 'customer_cell_phone', type: 'string', example: 'string'),
                            new OA\Property(property: 'customer_tax_id', type: 'string', example: 'string'),
                            new OA\Property(property: 'customer_notes', type: 'string', example: 'string'),
                            new OA\Property(property: 'customer_group_id', type: 'integer', example: 3),
                            new OA\Property(property: 'customer_term_id', type: 'integer', example: 1),
                            new OA\Property(property: 'commercial_address', type: 'string', example: 'string'),
                            new OA\Property(property: 'commercial_country', type: 'string', example: 'string'),
                            new OA\Property(property: 'commercial_state', type: 'string', example: 'string'),
                            new OA\Property(property: 'commercial_city', type: 'string', example: 'string'),
                            new OA\Property(property: 'commercial_zip_code', type: 'string', example: 'string'),
                            new OA\Property(property: 'dispatch_address', type: 'string', example: 'string'),
                            new OA\Property(property: 'dispatch_country', type: 'string', example: 'string'),
                            new OA\Property(property: 'dispatch_state', type: 'string', example: 'string'),
                            new OA\Property(property: 'dispatch_city', type: 'string', example: 'string'),
                        ]
                    ),
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        items: new OA\Items(
                            required: ['sku', 'cantidad', 'precio'],
                            properties: [
                                new OA\Property(property: 'sku', type: 'string', example: 'ABC-001'),
                                new OA\Property(property: 'cantidad', type: 'number', example: 2),
                                new OA\Property(property: 'precio', type: 'number', example: 10.5),
                                new OA\Property(property: 'name', type: 'string', example: 'Producto ABC', description: 'Requerido si el SKU no existe (alta de producto)'),
                                new OA\Property(property: 'sale_price', type: 'number', example: 10.5),
                                new OA\Property(property: 'categories', type: 'array', items: new OA\Items(type: 'string', example: 'Electrónica'), description: 'Nombres de categorías (no IDs); se crean si no existen'),
                                new OA\Property(property: 'category_groups', type: 'array', items: new OA\Items(type: 'string', example: 'Audio'), description: 'Nombres de grupos (no IDs)'),
                                new OA\Property(property: 'category_subgroups', type: 'array', items: new OA\Items(type: 'string', example: 'Audio/Auriculares'), description: 'Nombres de subgrupos; opcional "Grupo/Subgrupo"'),
                                new OA\Property(property: 'barcode', type: 'string'),
                                new OA\Property(property: 'barcode_images', type: 'string'),
                                new OA\Property(property: 'tags', type: 'string', example: 'nuevo,promo'),
                                new OA\Property(property: 'descriptions', type: 'string'),
                                new OA\Property(property: 'stock', type: 'number', example: 100),
                                new OA\Property(property: 'minimum_stock', type: 'number', example: 5),
                                new OA\Property(property: 'notify_minimum_stock', type: 'integer', example: 1),
                                new OA\Property(property: 'currency_type', type: 'string', example: 'USD'),
                                new OA\Property(property: 'fob_price', type: 'number', example: 8),
                                new OA\Property(property: 'purchase_price', type: 'number', example: 7),
                                new OA\Property(property: 'locations', type: 'string', example: 'A-01'),
                                new OA\Property(property: 'status', type: 'integer', example: 1),
                                new OA\Property(property: 'sell_with_inventory', type: 'integer', example: 1),
                                new OA\Property(property: 'price_reduced', type: 'number', example: 0),
                                new OA\Property(property: 'sem_red_hasta', type: 'integer'),
                                new OA\Property(property: 'sem_amarillo_desde', type: 'integer'),
                                new OA\Property(property: 'sem_amarillo_hasta', type: 'integer'),
                                new OA\Property(property: 'sem_verde_desde', type: 'integer'),
                                new OA\Property(property: 'sem_verde_hasta', type: 'integer'),
                                new OA\Property(property: 'unit', type: 'string', example: 'UND'),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Orden creada'),
            new OA\Response(response: 200, description: 'Orden ya existía en ese año; importación omitida'),
            new OA\Response(response: 422, description: 'Validación / datos no encontrados'),
        ]
    )]
    public function import(ImportOrderPublicRequest $request)
    {
        $request->validated();
        $companyId = (int) $request->input('company_id');

        $company = Company::query()->where('company_id', $companyId)->first();
        if (! $company) {
            return response()->json(['message' => 'Compañía no encontrada.', 'error' => 'company_not_found'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Usar input normalizado (no solo validated) para conservar todos los campos de producto.
        $result = $this->importOneResult(
            $company,
            is_array($request->input('cabezal')) ? $request->input('cabezal') : [],
            is_array($request->input('items')) ? $request->input('items') : []
        );

        return response()->json($result['body'], $result['status']);
    }

    #[OA\Post(
        path: 'api/v2/order/import-bulk',
        summary: 'Importar varias órdenes desde ERP (público, sin token)',
        description: 'Body = arreglo JSON; cada elemento = order/import. No detiene el lote: created/updated/skipped/failed en results[]. Estado del cabezal se aplica al crear y al actualizar.',
        tags: ['Order'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'array',
                items: new OA\Items(
                    type: 'object',
                    required: ['company_id', 'cabezal', 'items'],
                    properties: [
                        new OA\Property(property: 'company_id', type: 'integer', example: 1),
                        new OA\Property(
                            property: 'cabezal',
                            type: 'object',
                            required: ['iderp', 'fecha', 'norden'],
                            properties: [
                                new OA\Property(property: 'iderp', type: 'string', example: '18568'),
                                new OA\Property(property: 'fecha', type: 'string', example: '2026-09-05'),
                                new OA\Property(property: 'norden', type: 'string', example: 'ERP-1001'),
                                new OA\Property(property: 'notas', type: 'string', example: 'Pedido ERP'),
                                new OA\Property(property: 'descuento', type: 'number', example: 0),
                                new OA\Property(property: 'subtotal', type: 'number', example: 100),
                                new OA\Property(property: 'total', type: 'number', example: 100),
                                new OA\Property(property: 'codigo_estado', type: 'integer', example: 0),
                                new OA\Property(property: 'nombre_estado', type: 'string', example: 'Nuevo'),
                                new OA\Property(property: 'numero_estado', type: 'integer', example: 3),
                                new OA\Property(property: 'seller_id', type: 'integer', example: 1),
                                new OA\Property(property: 'warehouse_user_id', type: 'integer', example: 28),
                                new OA\Property(property: 'seller_name', type: 'string', example: 'Juan Pérez'),
                                new OA\Property(property: 'seller_email', type: 'string', example: 'juan@empresa.com'),
                                new OA\Property(property: 'warehouse_name', type: 'string', example: 'María López'),
                                new OA\Property(property: 'warehouse_email', type: 'string', example: 'almacen@empresa.com'),
                                new OA\Property(property: 'customer_business_name', type: 'string', example: 'ACME INC'),
                                new OA\Property(property: 'customer_name', type: 'string', example: 'John Doe'),
                                new OA\Property(property: 'customer_email', type: 'string', example: 'john@acme.com'),
                                new OA\Property(property: 'customer_phone', type: 'string', example: '555-1234'),
                                new OA\Property(property: 'customer_cell_phone', type: 'string', example: 'string'),
                                new OA\Property(property: 'customer_tax_id', type: 'string', example: 'string'),
                                new OA\Property(property: 'customer_notes', type: 'string', example: 'string'),
                                new OA\Property(property: 'customer_group_id', type: 'integer', example: 3),
                                new OA\Property(property: 'customer_term_id', type: 'integer', example: 1),
                                new OA\Property(property: 'commercial_address', type: 'string', example: 'string'),
                                new OA\Property(property: 'commercial_country', type: 'string', example: 'string'),
                                new OA\Property(property: 'commercial_state', type: 'string', example: 'string'),
                                new OA\Property(property: 'commercial_city', type: 'string', example: 'string'),
                                new OA\Property(property: 'commercial_zip_code', type: 'string', example: 'string'),
                                new OA\Property(property: 'dispatch_address', type: 'string', example: 'string'),
                                new OA\Property(property: 'dispatch_country', type: 'string', example: 'string'),
                                new OA\Property(property: 'dispatch_state', type: 'string', example: 'string'),
                                new OA\Property(property: 'dispatch_city', type: 'string', example: 'string'),
                            ]
                        ),
                        new OA\Property(
                            property: 'items',
                            type: 'array',
                            items: new OA\Items(
                                required: ['sku', 'cantidad', 'precio'],
                                properties: [
                                    new OA\Property(property: 'sku', type: 'string', example: 'ABC-001'),
                                    new OA\Property(property: 'cantidad', type: 'number', example: 2),
                                    new OA\Property(property: 'precio', type: 'number', example: 10.5),
                                    new OA\Property(property: 'name', type: 'string', example: 'Producto ABC', description: 'Requerido si el SKU no existe (alta de producto)'),
                                    new OA\Property(property: 'sale_price', type: 'number', example: 10.5),
                                    new OA\Property(property: 'categories', type: 'array', items: new OA\Items(type: 'string', example: 'Electrónica'), description: 'Nombres de categorías (no IDs); se crean si no existen'),
                                    new OA\Property(property: 'category_groups', type: 'array', items: new OA\Items(type: 'string', example: 'Audio'), description: 'Nombres de grupos (no IDs)'),
                                    new OA\Property(property: 'category_subgroups', type: 'array', items: new OA\Items(type: 'string', example: 'Audio/Auriculares'), description: 'Nombres de subgrupos; opcional "Grupo/Subgrupo"'),
                                    new OA\Property(property: 'barcode', type: 'string'),
                                    new OA\Property(property: 'barcode_images', type: 'string'),
                                    new OA\Property(property: 'tags', type: 'string', example: 'nuevo,promo'),
                                    new OA\Property(property: 'descriptions', type: 'string'),
                                    new OA\Property(property: 'stock', type: 'number', example: 100),
                                    new OA\Property(property: 'minimum_stock', type: 'number', example: 5),
                                    new OA\Property(property: 'notify_minimum_stock', type: 'integer', example: 1),
                                    new OA\Property(property: 'currency_type', type: 'string', example: 'USD'),
                                    new OA\Property(property: 'fob_price', type: 'number', example: 8),
                                    new OA\Property(property: 'purchase_price', type: 'number', example: 7),
                                    new OA\Property(property: 'locations', type: 'string', example: 'A-01'),
                                    new OA\Property(property: 'status', type: 'integer', example: 1),
                                    new OA\Property(property: 'sell_with_inventory', type: 'integer', example: 1),
                                    new OA\Property(property: 'price_reduced', type: 'number', example: 0),
                                    new OA\Property(property: 'sem_red_hasta', type: 'integer'),
                                    new OA\Property(property: 'sem_amarillo_desde', type: 'integer'),
                                    new OA\Property(property: 'sem_amarillo_hasta', type: 'integer'),
                                    new OA\Property(property: 'sem_verde_desde', type: 'integer'),
                                    new OA\Property(property: 'sem_verde_hasta', type: 'integer'),
                                    new OA\Property(property: 'unit', type: 'string', example: 'UND'),
                                ]
                            )
                        ),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Lote procesado (ver summary y results)'),
            new OA\Response(response: 422, description: 'Validación del lote'),
        ]
    )]
    public function importBulk(ImportOrdersBulkPublicRequest $request)
    {
        // Evitar 504 / HTML de timeout en lotes grandes.
        set_time_limit(0);
        if (function_exists('ini_set')) {
            @ini_set('memory_limit', '512M');
            @ini_set('max_input_time', '0');
        }
        ignore_user_abort(true);

        $orders = $request->input('orders', []);

        $results = [];
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $failed = 0;
        $companies = [];

        foreach ($orders as $index => $orderPayload) {
            if (! is_array($orderPayload) || ! empty($orderPayload['_invalid'])) {
                $failed++;
                $results[] = [
                    'index' => $index,
                    'norden' => null,
                    'outcome' => 'failed',
                    'http_status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'result' => [
                        'message' => 'Elemento del lote inválido (se esperaba un objeto orden).',
                        'error' => 'invalid_order_payload',
                    ],
                ];
                continue;
            }

            $cabezal = is_array($orderPayload['cabezal'] ?? null) ? $orderPayload['cabezal'] : [];
            $norden = trim((string) ($cabezal['norden'] ?? ''));

            try {
                $validatedOne = ImportOrderPublicRequest::validateNormalizedOrder($orderPayload);
                if (! $validatedOne['ok']) {
                    $failed++;
                    $results[] = [
                        'index' => $index,
                        'norden' => $norden !== '' ? $norden : null,
                        'outcome' => 'failed',
                        'http_status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                        'result' => [
                            'message' => 'Error de validación en la orden; se omitió y se continuó con el lote.',
                            'error' => 'validation_error',
                            'errors' => $validatedOne['errors'],
                        ],
                    ];
                    continue;
                }

                $orderPayload = $validatedOne['data'];
                $companyId = (int) ($orderPayload['company_id'] ?? 0);
                $cabezal = $orderPayload['cabezal'] ?? [];
                $items = $orderPayload['items'] ?? [];
                $norden = trim((string) ($cabezal['norden'] ?? ''));

                if (! isset($companies[$companyId])) {
                    $companies[$companyId] = Company::query()->where('company_id', $companyId)->first();
                }
                $company = $companies[$companyId];
                if (! $company) {
                    $failed++;
                    $results[] = [
                        'index' => $index,
                        'norden' => $norden,
                        'outcome' => 'failed',
                        'http_status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                        'result' => [
                            'message' => 'Compañía no encontrada.',
                            'error' => 'company_not_found',
                            'company_id' => $companyId,
                        ],
                    ];
                    continue;
                }

                $result = $this->importOneResult($company, $cabezal, is_array($items) ? $items : []);
                $status = (int) $result['status'];
                $body = $result['body'];

                if ($status === Response::HTTP_CREATED) {
                    $created++;
                    $outcome = 'created';
                } elseif ($status === Response::HTTP_OK && ! empty($body['updated'])) {
                    $updated++;
                    $outcome = 'updated';
                } elseif ($status === Response::HTTP_OK && ! empty($body['skipped'])) {
                    $skipped++;
                    $outcome = 'skipped';
                } else {
                    $failed++;
                    $outcome = 'failed';
                }

                $results[] = [
                    'index' => $index,
                    'norden' => $norden,
                    'outcome' => $outcome,
                    'http_status' => $status,
                    'result' => $body,
                ];
            } catch (\Throwable $e) {
                $failed++;
                Log::error('OrderImportPublicController::importBulk item failed', [
                    'index' => $index,
                    'norden' => $norden,
                    'message' => $e->getMessage(),
                ]);
                $results[] = [
                    'index' => $index,
                    'norden' => $norden !== '' ? $norden : null,
                    'outcome' => 'failed',
                    'http_status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'result' => [
                        'message' => 'No se pudo importar la orden; se continuó con el resto del lote.',
                        'error' => $e->getMessage(),
                    ],
                ];
            }
        }

        return response()->json([
            'message' => 'Lote de órdenes procesado. Las órdenes válidas se importaron; las inválidas se informaron sin detener el proceso.',
            'summary' => [
                'total' => count($orders),
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'failed' => $failed,
            ],
            'results' => $results,
        ], Response::HTTP_OK);
    }

    /**
     * Procesa una orden con la misma lógica que POST /order/import.
     *
     * @param  array<string, mixed>  $cabezal
     * @param  array<int, array<string, mixed>>  $items
     * @return array{status:int,body:array<string,mixed>}
     */
    protected function importOneResult(Company $company, array $cabezal, array $items): array
    {
        $companyId = (int) $company->company_id;
        $requestedOrderNumber = trim((string) ($cabezal['norden'] ?? ''));
        $iderp = trim((string) ($cabezal['iderp'] ?? ''));

        // Ítems con cantidad=0 o precio=0 se omiten.
        $items = array_values(array_filter($items, static function ($item) {
            if (! is_array($item)) {
                return false;
            }
            $qty = (float) ($item['cantidad'] ?? 0);
            $price = (float) ($item['precio'] ?? 0);

            return $qty != 0.0 && $price != 0.0;
        }));

        // Sin ítems válidos: se omite la orden (no se crea).
        if ($items === []) {
            return [
                'status' => Response::HTTP_OK,
                'body' => [
                    'message' => 'La orden no tiene ítems válidos; se omitió la importación.',
                    'skipped' => true,
                    'error' => 'empty_items',
                    'data' => [
                        'requested_order_number' => $requestedOrderNumber !== '' ? $requestedOrderNumber : null,
                        'company_id' => $companyId,
                        'iderp' => $iderp !== '' ? $iderp : null,
                        'items_count' => 0,
                    ],
                ],
            ];
        }

        if ($iderp === '') {
            return [
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'body' => [
                    'message' => 'El iderp del cliente es obligatorio.',
                    'error' => 'iderp_required',
                ],
            ];
        }

        $customerResolved = $this->resolveOrCreateCustomer($company, $iderp, $cabezal);
        if (! empty($customerResolved['error'])) {
            return [
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'body' => [
                    'message' => $customerResolved['message'],
                    'error' => $customerResolved['error'],
                    'iderp' => $iderp,
                    'company_id' => $companyId,
                ],
            ];
        }
        /** @var Customer $customer */
        $customer = $customerResolved['customer'];
        $customerCreated = ! empty($customerResolved['created']);

        try {
            $fecha = Carbon::parse($cabezal['fecha']);
        } catch (\Throwable $e) {
            return [
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'body' => [
                    'message' => 'La fecha de la orden no es válida.',
                    'error' => 'invalid_fecha',
                ],
            ];
        }
        $year = (int) $fecha->year;
        $notes = trim((string) ($cabezal['notas'] ?? ''));
        $discount = (float) ($cabezal['descuento'] ?? 0);
        $subtotalIn = isset($cabezal['subtotal']) ? (float) $cabezal['subtotal'] : null;
        $totalIn = isset($cabezal['total']) ? (float) $cabezal['total'] : null;

        $resolvedProducts = [];
        $productsCreated = 0;
        foreach ($items as $index => $item) {
            $resolved = $this->resolveOrCreateProduct($company, $item);
            if (! empty($resolved['error'])) {
                return [
                    'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'body' => [
                        'message' => $resolved['message'],
                        'error' => $resolved['error'],
                        'sku' => $resolved['sku'] ?? ($item['sku'] ?? null),
                    ],
                ];
            }

            /** @var Product $product */
            $product = $resolved['product'];
            if (! empty($resolved['created'])) {
                $productsCreated++;
            }

            $qty = (float) $item['cantidad'];
            $price = (float) $item['precio'];
            $lineTotal = $qty * $price;
            $resolvedProducts[] = [
                'index' => $index,
                'product' => $product,
                'qty' => $qty,
                'price' => $price,
                'line_total' => $lineTotal,
                'product_created' => ! empty($resolved['created']),
            ];
        }

        if ($resolvedProducts === []) {
            return [
                'status' => Response::HTTP_OK,
                'body' => [
                    'message' => 'La orden no tiene ítems válidos; se omitió la importación.',
                    'skipped' => true,
                    'error' => 'empty_items',
                    'data' => [
                        'requested_order_number' => $requestedOrderNumber !== '' ? $requestedOrderNumber : null,
                        'company_id' => $companyId,
                        'iderp' => $iderp,
                        'items_count' => 0,
                    ],
                ],
            ];
        }

        $statusResolved = $this->resolveStatusId(
            $cabezal['codigo_estado'] ?? null,
            $cabezal['numero_estado'] ?? null,
            $cabezal['nombre_estado'] ?? null
        );
        if (! empty($statusResolved['error'])) {
            return [
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'body' => [
                    'message' => $statusResolved['message'],
                    'error' => $statusResolved['error'],
                ],
            ];
        }
        $statusId = (int) $statusResolved['id'];

        $sellerResolved = $this->resolveSellerId(
            $customer,
            $company,
            $cabezal['seller_id'] ?? null,
            $cabezal['seller_name'] ?? null,
            $cabezal['seller_email'] ?? null
        );
        if (! empty($sellerResolved['error'])) {
            return [
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'body' => [
                    'message' => $sellerResolved['message'],
                    'error' => $sellerResolved['error'],
                ],
            ];
        }
        $sellerId = (int) $sellerResolved['id'];

        if ($customerCreated || ! $customer->sellers()->where('sellers.id', $sellerId)->exists()) {
            $customer->sellers()->syncWithoutDetaching([$sellerId]);
        }
        if (empty($customer->seller_assign_catalog)) {
            $customer->seller_assign_catalog = $sellerId;
            $customer->save();
        }

        $warehouseResolved = $this->resolveWarehouseUserId(
            $companyId,
            $cabezal['warehouse_user_id'] ?? null,
            $cabezal['warehouse_name'] ?? null,
            $cabezal['warehouse_email'] ?? null
        );
        if (! empty($warehouseResolved['error'])) {
            return [
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'body' => [
                    'message' => $warehouseResolved['message'],
                    'error' => $warehouseResolved['error'],
                ],
            ];
        }
        $warehouseUserId = isset($warehouseResolved['id']) ? (int) $warehouseResolved['id'] : null;
        if ($warehouseUserId !== null && $warehouseUserId <= 0) {
            $warehouseUserId = null;
        }

        $languageId = $company->language_id ?? Language::query()->value('language_id');
        if ($languageId === null) {
            return [
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'body' => [
                    'message' => 'La compañía no tiene idioma configurado.',
                    'error' => 'language_required',
                ],
            ];
        }

        $noteUserId = $this->resolveNoteUserId($companyId, $sellerId);

        $existingOrder = Order::query()
            ->where('company_id', $companyId)
            ->where('order_number', $requestedOrderNumber)
            ->whereYear('created', $year)
            ->first();

        $calcSubtotal = array_sum(array_column($resolvedProducts, 'line_total'));
        $subtotal = $subtotalIn !== null ? $subtotalIn : $calcSubtotal;
        $discountAmount = max(0.0, $discount);
        $totalAmount = $totalIn !== null ? $totalIn : max(0.0, $subtotal - $discountAmount);
        $now = Carbon::now();

        $buildDetails = function () use ($resolvedProducts, $companyId): array {
            $details = [];
            foreach ($resolvedProducts as $row) {
                /** @var Product $product */
                $product = $row['product'];
                $qty = $row['qty'];
                $salePrice = $row['price'];
                $lineTotal = $row['line_total'];

                $details[] = new OrderDetail([
                    'company_id' => $companyId,
                    'product_id' => $product->pro_id,
                    'qty' => $qty,
                    'sale_price' => $salePrice,
                    'fob_price' => (float) ($product->fob_price ?? 0),
                    'fixed_pricedate' => null,
                    'comment' => '-',
                    'amount_sales' => $lineTotal,
                    'amount' => $lineTotal,
                    'total' => $lineTotal,
                    'total_amount' => $lineTotal,
                    'delivered_quantity' => 0,
                    'delivered_pack' => 0,
                    'pack' => 0,
                    'discount' => 0,
                    'id_kor' => 1,
                    'comments' => 1,
                ]);
            }

            return $details;
        };

        try {
            DB::beginTransaction();

            if ($existingOrder) {
                $updatePayload = [
                    'language_id' => $languageId,
                    'customer_id' => $customer->customer_id,
                    'seller_id' => $sellerId,
                    'discount_type' => 2,
                    'discount' => $discountAmount,
                    'discount_a' => $discountAmount,
                    'amount' => $subtotal,
                    'total_amount' => $totalAmount,
                    'status_id' => $statusId, // estado del cabezal (codigo/numero/nombre)
                    'source' => 'erp_import',
                    'created' => $fecha->toDateTimeString(),
                    'updated' => $now->toDateTimeString(),
                ];

                if ($warehouseUserId) {
                    $updatePayload = array_merge($updatePayload, $this->warehouseAssignmentAttributes($warehouseUserId, $now));
                }
                // Si no se resolvió almacenero, no borrar una asignación previa.

                $existingOrder->fill($updatePayload);
                $existingOrder->save();

                if ($warehouseUserId) {
                    $existingOrder->forceFill($this->warehouseAssignmentAttributes($warehouseUserId, $now))->save();
                }

                // Reemplazar ítems por los de la orden nueva.
                $existingOrder->order_details()->forceDelete();
                $details = $buildDetails();
                $existingOrder->order_details()->saveMany($details);

                if ($notes !== '') {
                    $existingOrder->notes()->create([
                        'type' => OrderNoteTypes::Order->value,
                        'user_id' => $noteUserId,
                        'note' => $notes,
                    ]);
                }

                DB::commit();
                $existingOrder->refresh();

                return [
                    'status' => Response::HTTP_OK,
                    'body' => [
                        'message' => 'Orden existente actualizada correctamente.',
                        'skipped' => false,
                        'updated' => true,
                        'data' => [
                            'order_id' => (int) $existingOrder->order_id,
                            'order_number' => $existingOrder->order_number,
                            'requested_order_number' => $requestedOrderNumber,
                            'company_id' => $companyId,
                            'customer_id' => (int) $customer->customer_id,
                            'customer_created' => $customerCreated,
                            'iderp' => $iderp,
                            'seller_id' => (int) $existingOrder->seller_id,
                            'warehouse_user_id' => $warehouseUserId,
                            'status_id' => (int) $existingOrder->status_id,
                            'subtotal' => (float) $existingOrder->amount,
                            'descuento' => (float) $existingOrder->discount_a,
                            'total' => (float) $existingOrder->total_amount,
                            'created' => $existingOrder->created,
                            'updated' => $existingOrder->updated,
                            'items_count' => count($details),
                            'products_created' => $productsCreated,
                        ],
                    ],
                ];
            }

            $orderModel = new Order();
            $uuid = $orderModel->generate_uuid();

            $orderPayload = [
                'company_id' => $companyId,
                'UUID' => $uuid,
                'tipo_d' => 'D',
                'order_number' => $requestedOrderNumber,
                'language_id' => $languageId,
                'customer_id' => $customer->customer_id,
                'seller_id' => $sellerId,
                'discount_type' => 2,
                'discount' => $discountAmount,
                'discount_a' => $discountAmount,
                'amount' => $subtotal,
                'total_amount' => $totalAmount,
                'proforma' => false,
                'status_id' => $statusId,
                'source' => 'erp_import',
                'created' => $fecha->toDateTimeString(),
                'updated' => $now->toDateTimeString(),
            ];

            if ($warehouseUserId) {
                $orderPayload = array_merge($orderPayload, $this->warehouseAssignmentAttributes($warehouseUserId, $now));
            }

            $order = Order::create($orderPayload);

            // Asegurar asignación de almacén (mismo criterio que assign-warehouse).
            if ($warehouseUserId) {
                $order->forceFill($this->warehouseAssignmentAttributes($warehouseUserId, $now))->save();
            }
            $details = $buildDetails();
            $order->order_details()->saveMany($details);

            if ($notes !== '') {
                $order->notes()->create([
                    'type' => OrderNoteTypes::Order->value,
                    'user_id' => $noteUserId,
                    'note' => $notes,
                ]);
            }

            DB::commit();

            return [
                'status' => Response::HTTP_CREATED,
                'body' => [
                    'message' => 'Orden importada correctamente.',
                    'skipped' => false,
                    'updated' => false,
                    'data' => [
                        'order_id' => (int) $order->order_id,
                        'order_number' => $order->order_number,
                        'requested_order_number' => $requestedOrderNumber,
                        'company_id' => $companyId,
                        'customer_id' => (int) $customer->customer_id,
                        'customer_created' => $customerCreated,
                        'iderp' => $iderp,
                        'seller_id' => (int) $order->seller_id,
                        'warehouse_user_id' => $warehouseUserId,
                        'status_id' => (int) $order->status_id,
                        'subtotal' => (float) $order->amount,
                        'descuento' => (float) $order->discount_a,
                        'total' => (float) $order->total_amount,
                        'created' => $order->created,
                        'updated' => $order->updated,
                        'items_count' => count($details),
                        'products_created' => $productsCreated,
                    ],
                ],
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('OrderImportPublicController::importOneResult failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return [
                'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'body' => [
                    'message' => 'No se pudo importar la orden.',
                    'error' => $e->getMessage(),
                ],
            ];
        }
    }

    /**
     * Busca producto por SKU; si no existe, lo crea con datos del ítem (categorías por nombre).
     *
     * @param  array<string, mixed>  $item
     * @return array{product?:Product,created?:bool,error?:string,message?:string,sku?:string}
     */
    protected function resolveOrCreateProduct(Company $company, array $item): array
    {
        $companyId = (int) $company->company_id;
        $sku = mb_strtoupper(trim((string) ($item['sku'] ?? '')), 'UTF-8');
        if ($sku === '') {
            return [
                'error' => 'sku_required',
                'message' => 'Cada ítem debe incluir sku.',
                'sku' => null,
            ];
        }

        $cacheKey = 'product:'.$companyId.':'.$sku;
        if (isset($this->importRuntimeCache[$cacheKey])) {
            return $this->importRuntimeCache[$cacheKey];
        }

        $existing = Product::query()
            ->where('company_id', $companyId)
            ->whereRaw('UPPER(TRIM(sku)) = ?', [$sku])
            ->first();

        if ($existing) {
            return $this->importRuntimeCache[$cacheKey] = ['product' => $existing, 'created' => false];
        }

        $name = trim((string) ($item['name'] ?? ''));
        if ($name === '') {
            return [
                'error' => 'product_data_required',
                'message' => 'El SKU "'.$sku.'" no existe. Envíe name (y opcionalmente categories, category_groups, stock, etc.) en el ítem para crearlo.',
                'sku' => $sku,
            ];
        }

        try {
            $product = $this->createProductFromItem($company, $item, $sku, $name);
        } catch (\Throwable $e) {
            Log::error('OrderImportPublicController::createProductFromItem failed', [
                'sku' => $sku,
                'company_id' => $companyId,
                'message' => $e->getMessage(),
            ]);

            return [
                'error' => 'product_create_failed',
                'message' => 'No se pudo crear el producto "'.$sku.'": '.$e->getMessage(),
                'sku' => $sku,
            ];
        }

        return $this->importRuntimeCache[$cacheKey] = ['product' => $product, 'created' => true, 'sku' => $sku];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function createProductFromItem(Company $company, array $item, string $sku, string $name): Product
    {
        $companyId = (int) $company->company_id;
        $salePrice = isset($item['sale_price']) && is_numeric($item['sale_price'])
            ? (float) $item['sale_price']
            : (float) ($item['precio'] ?? 0);
        $fob = isset($item['fob_price']) && is_numeric($item['fob_price'])
            ? (float) $item['fob_price']
            : $salePrice;
        $purchase = isset($item['purchase_price']) && is_numeric($item['purchase_price'])
            ? (float) $item['purchase_price']
            : $salePrice;
        $stock = isset($item['stock']) && is_numeric($item['stock']) ? (float) $item['stock'] : 0.0;
        if ($stock < 0) {
            $stock = 0.0;
        }

        if (array_key_exists('status', $item) && $item['status'] !== null && $item['status'] !== '') {
            $status = (int) $item['status'] ? 1 : 0;
        } else {
            $status = $stock > 0 ? 1 : 0;
        }

        $categoryIds = $this->resolveCategoryIdsByNames(
            $companyId,
            is_array($item['categories'] ?? null) ? $item['categories'] : []
        );
        if ($categoryIds === []) {
            $categoryIds = [$this->ensureDefaultCategoryId($companyId)];
        }
        $categoryIdsCsv = implode(',', $categoryIds);

        $languageId = (int) ($company->language_id ?? Language::query()->value('language_id') ?? 1);
        $unitId = $this->resolveUnitIdForImport($company, is_string($item['unit'] ?? null) ? (string) $item['unit'] : null);

        $minimumStock = isset($item['minimum_stock']) && is_numeric($item['minimum_stock'])
            ? (float) $item['minimum_stock']
            : 0.0;
        $notifyMoq = isset($item['notify_minimum_stock']) && $item['notify_minimum_stock'] !== null && $item['notify_minimum_stock'] !== ''
            ? ((int) $item['notify_minimum_stock'] ? 1 : 0)
            : 0;
        $priceReduced = isset($item['price_reduced']) && is_numeric($item['price_reduced'])
            ? (float) $item['price_reduced']
            : 0.0;
        $sellWithInventory = isset($item['sell_with_inventory']) && $item['sell_with_inventory'] !== null && $item['sell_with_inventory'] !== ''
            ? ((int) $item['sell_with_inventory'] ? 1 : 0)
            : null;

        $payload = [
            'company_id' => $companyId,
            'language_id' => $languageId,
            'sku' => $sku,
            'name' => mb_strtoupper(trim($name), 'UTF-8'),
            'sale_price' => $salePrice,
            'stock' => $stock,
            'minimum_stock' => $minimumStock,
            'notify_minimum_stock' => $notifyMoq,
            'fob_price' => $fob,
            'purchase_price' => $purchase,
            'price_reduced' => $priceReduced,
            'status' => $status,
            'category_id' => $categoryIdsCsv,
            'cat_id' => $categoryIdsCsv,
            'cats_id' => $categoryIdsCsv,
            'barcode' => $this->nullableString($item['barcode'] ?? null),
            'barcode_images' => $this->nullableString($item['barcode_images'] ?? null),
            'tags' => $this->nullableString($item['tags'] ?? null),
            'descriptions' => $this->nullableString($item['descriptions'] ?? null),
            'locations' => $this->nullableString($item['locations'] ?? null),
            'currency_type' => $this->nullableString($item['currency_type'] ?? null),
            'unit_id' => $unitId,
            'created_on' => Carbon::now(),
            'modified_on' => Carbon::now(),
        ];

        if ($sellWithInventory !== null) {
            $payload['sell_with_inventory'] = $sellWithInventory;
        }
        foreach (['sem_red_hasta', 'sem_amarillo_desde', 'sem_amarillo_hasta', 'sem_verde_desde', 'sem_verde_hasta'] as $semField) {
            if (isset($item[$semField]) && is_numeric($item[$semField])) {
                $payload[$semField] = (int) $item[$semField];
            }
        }

        $product = Product::create($payload);
        if ((int) ($product->product_id ?? 0) !== (int) $product->pro_id) {
            $product->product_id = (int) $product->pro_id;
            $product->save();
        }

        foreach ($categoryIds as $categoryId) {
            $product->categories()->syncWithoutDetaching([(int) $categoryId]);
        }

        $this->syncProductCategoryGroupsByNames(
            $product,
            $companyId,
            (int) $categoryIds[0],
            is_array($item['category_groups'] ?? null) ? $item['category_groups'] : [],
            is_array($item['category_subgroups'] ?? null) ? $item['category_subgroups'] : []
        );

        return $product->fresh();
    }

    /**
     * @param  list<string>  $groupNames
     * @param  list<string>  $subgroupNames
     */
    protected function syncProductCategoryGroupsByNames(
        Product $product,
        int $companyId,
        int $primaryCategoryId,
        array $groupNames,
        array $subgroupNames
    ): void {
        $groupIds = [];
        foreach ($groupNames as $groupName) {
            $groupName = trim((string) $groupName);
            if ($groupName === '') {
                continue;
            }
            $groupIds[] = $this->findOrCreateCategoryGroupId($companyId, $primaryCategoryId, $groupName);
        }

        $subgroupIds = [];
        foreach ($subgroupNames as $raw) {
            $raw = trim((string) $raw);
            if ($raw === '') {
                continue;
            }
            $groupName = null;
            $subName = $raw;
            if (str_contains($raw, '/')) {
                [$groupName, $subName] = array_map('trim', explode('/', $raw, 2));
            } elseif (str_contains($raw, ' - ')) {
                [$groupName, $subName] = array_map('trim', explode(' - ', $raw, 2));
            }
            $groupId = $groupName !== null && $groupName !== ''
                ? $this->findOrCreateCategoryGroupId($companyId, $primaryCategoryId, $groupName)
                : ($groupIds[0] ?? $this->findOrCreateCategoryGroupId($companyId, $primaryCategoryId, 'General'));
            $groupIds[] = $groupId;
            $subgroupIds[] = $this->findOrCreateCategorySubgroupId($companyId, $groupId, $subName);
        }

        $groupIds = array_values(array_unique(array_filter($groupIds)));
        $subgroupIds = array_values(array_unique(array_filter($subgroupIds)));

        if ($groupIds === [] && $subgroupIds === []) {
            return;
        }

        if ($subgroupIds !== []) {
            $parentIds = CategorySubgroup::query()
                ->where('company_id', $companyId)
                ->whereIn('subgroup_id', $subgroupIds)
                ->pluck('group_id')
                ->map(fn ($id) => (int) $id)
                ->all();
            $groupIds = array_values(array_unique(array_merge($groupIds, $parentIds)));
        }

        $product->categoryGroups()->sync($groupIds);
        $product->categorySubgroups()->sync($subgroupIds);
        $product->category_group_id = $groupIds[0] ?? null;
        $product->category_subgroup_id = $subgroupIds[0] ?? null;
        $product->save();
    }

    protected function findOrCreateCategoryGroupId(int $companyId, int $categoryId, string $name): int
    {
        $upper = mb_strtoupper(trim($name), 'UTF-8');
        $existing = CategoryGroup::query()
            ->where('company_id', $companyId)
            ->whereRaw('UPPER(TRIM(group_name)) = ?', [$upper])
            ->first();
        if ($existing) {
            return (int) $existing->group_id;
        }

        $created = CategoryGroup::create([
            'company_id' => $companyId,
            'category_id' => $categoryId,
            'group_name' => trim($name),
            'group_status' => 1,
            'sort_order' => 0,
        ]);

        return (int) $created->group_id;
    }

    protected function findOrCreateCategorySubgroupId(int $companyId, int $groupId, string $name): int
    {
        $upper = mb_strtoupper(trim($name), 'UTF-8');
        $existing = CategorySubgroup::query()
            ->where('company_id', $companyId)
            ->where('group_id', $groupId)
            ->whereRaw('UPPER(TRIM(subgroup_name)) = ?', [$upper])
            ->first();
        if ($existing) {
            return (int) $existing->subgroup_id;
        }

        $created = CategorySubgroup::create([
            'company_id' => $companyId,
            'group_id' => $groupId,
            'subgroup_name' => trim($name),
            'subgroup_status' => 1,
            'sort_order' => 0,
        ]);

        return (int) $created->subgroup_id;
    }

    /**
     * @param  list<string>  $names
     * @return list<int>
     */
    protected function resolveCategoryIdsByNames(int $companyId, array $names): array
    {
        $ids = [];
        foreach ($names as $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }
            $ids[] = $this->findOrCreateCategoryId($companyId, $name);
        }

        return array_values(array_unique($ids));
    }

    protected function findOrCreateCategoryId(int $companyId, string $name): int
    {
        $upper = mb_strtoupper(trim($name), 'UTF-8');
        $cacheKey = 'category:'.$companyId.':'.$upper;
        if (isset($this->importRuntimeCache[$cacheKey])) {
            return (int) $this->importRuntimeCache[$cacheKey];
        }

        $existing = Category::query()
            ->where('company_id', $companyId)
            ->whereRaw('UPPER(TRIM(category_name)) = ?', [$upper])
            ->first();

        if ($existing) {
            return $this->importRuntimeCache[$cacheKey] = (int) $existing->cat_id;
        }

        $created = Category::create([
            'company_id' => $companyId,
            'category_name' => trim($name),
            'category_status' => 1,
            'category_default' => 0,
        ]);

        return $this->importRuntimeCache[$cacheKey] = (int) $created->cat_id;
    }

    protected function ensureDefaultCategoryId(int $companyId): int
    {
        $default = Category::query()
            ->where('company_id', $companyId)
            ->where('category_default', 1)
            ->orderBy('cat_id')
            ->first();

        if ($default) {
            return (int) $default->cat_id;
        }

        $any = Category::query()
            ->where('company_id', $companyId)
            ->orderBy('cat_id')
            ->first();

        if ($any) {
            return (int) $any->cat_id;
        }

        return $this->findOrCreateCategoryId($companyId, 'General');
    }

    protected function resolveUnitIdForImport(Company $company, ?string $unitName): ?int
    {
        $companyId = (int) $company->company_id;
        $name = $unitName !== null ? trim($unitName) : '';

        if ($name !== '') {
            $upper = mb_strtoupper($name, 'UTF-8');
            $byName = Unit::query()
                ->where('company_id', $companyId)
                ->where(function ($q) use ($upper) {
                    $q->whereRaw('UPPER(TRIM(unit)) = ?', [$upper])
                        ->orWhereRaw('UPPER(TRIM(description)) = ?', [$upper]);
                })
                ->orderBy('unit_id')
                ->value('unit_id');
            if ($byName) {
                return (int) $byName;
            }
        }

        if (! empty($company->default_unit_id)) {
            return (int) $company->default_unit_id;
        }

        $defaultFlag = Unit::query()
            ->where('company_id', $companyId)
            ->where('default', 1)
            ->orderBy('unit_id')
            ->value('unit_id');
        if ($defaultFlag) {
            return (int) $defaultFlag;
        }

        $first = Unit::query()->where('company_id', $companyId)->orderBy('unit_id')->value('unit_id');

        return $first ? (int) $first : null;
    }

    /**
     * Busca cliente por iderp; si no existe, lo crea con los datos del cabezal.
     *
     * @param  array<string, mixed>  $cabezal
     * @return array{customer?:Customer,created?:bool,error?:string,message?:string}
     */
    protected function resolveOrCreateCustomer(Company $company, string $iderp, array $cabezal): array
    {
        $companyId = (int) $company->company_id;
        $iderpKey = mb_strtoupper(trim($iderp), 'UTF-8');
        $cacheKey = 'customer:'.$companyId.':'.$iderpKey;
        if (isset($this->importRuntimeCache[$cacheKey])) {
            return $this->importRuntimeCache[$cacheKey];
        }

        $existing = Customer::query()
            ->where('company_id', $companyId)
            ->whereRaw('UPPER(TRIM(cust_id_kor)) = ?', [$iderpKey])
            ->first();

        if ($existing) {
            return $this->importRuntimeCache[$cacheKey] = ['customer' => $existing, 'created' => false];
        }

        $businessName = trim((string) ($cabezal['customer_business_name'] ?? ''));
        $contactName = trim((string) ($cabezal['customer_name'] ?? ''));
        if ($businessName === '' && $contactName === '') {
            return $this->importRuntimeCache[$cacheKey] = [
                'error' => 'customer_data_required',
                'message' => 'El cliente con iderp "'.$iderp.'" no existe. Envíe customer_business_name o customer_name en el cabezal para crearlo.',
            ];
        }

        try {
            $customer = $this->createCustomerFromCabezal($company, $iderp, $cabezal, $businessName, $contactName);
        } catch (\Throwable $e) {
            Log::error('OrderImportPublicController::createCustomerFromCabezal failed', [
                'message' => $e->getMessage(),
                'iderp' => $iderp,
                'company_id' => $companyId,
            ]);

            // No cachear fallos: un reintento en el mismo lote podría funcionar tras crear defaults.
            return [
                'error' => 'customer_create_failed',
                'message' => 'No se pudo crear el cliente: '.$e->getMessage(),
            ];
        }

        return $this->importRuntimeCache[$cacheKey] = ['customer' => $customer, 'created' => true];
    }

    /**
     * @param  array<string, mixed>  $cabezal
     */
    protected function createCustomerFromCabezal(
        Company $company,
        string $iderp,
        array $cabezal,
        string $businessName,
        string $contactName
    ): Customer {
        $companyId = (int) $company->company_id;

        // Siempre defaults válidos de la compañía (ignorar customer_group_id / customer_term_id del cabezal).
        $groupId = $this->resolveValidCustomerGroupId($company);
        $termId = $this->resolveValidCustomerTermId($company);

        if ($businessName === '') {
            $businessName = $contactName;
        }
        if ($contactName === '') {
            $contactName = $businessName;
        }

        $sellerAssign = (int) ($company->default_seller_id ?? 0);
        if ($sellerAssign <= 0) {
            $sellerAssign = (int) (Vendor::query()->where('company_id', $companyId)->orderBy('id')->value('id') ?? 0);
        }

        $payload = [
            'company_id' => $companyId,
            'cust_id_kor' => $iderp,
            'name' => $contactName,
            'business_name' => $businessName,
            'email' => $this->nullableString($cabezal['customer_email'] ?? null),
            'phone' => $this->nullableString($cabezal['customer_phone'] ?? null),
            'cell_phone' => $this->nullableString($cabezal['customer_cell_phone'] ?? null),
            'tax_id' => $this->nullableString($cabezal['customer_tax_id'] ?? null),
            'notes' => $this->nullableString($cabezal['customer_notes'] ?? null) ?? '',
            'discount' => 0,
            'group_id' => $groupId,
            'term_id' => $termId,
            'customer_status' => 1,
            'catalog_emails' => 1,
            'seller_assign_catalog' => $sellerAssign > 0 ? $sellerAssign : null,
            'commercial_address' => $this->nullableString($cabezal['commercial_address'] ?? null),
            'commercial_delivery_address' => $this->nullableString($cabezal['commercial_delivery_address'] ?? null),
            'commercial_country' => $this->nullableString($cabezal['commercial_country'] ?? null),
            'commercial_state' => $this->nullableString($cabezal['commercial_state'] ?? null),
            'commercial_city' => $this->nullableString($cabezal['commercial_city'] ?? null),
            'commercial_zone' => $this->nullableString($cabezal['commercial_zone'] ?? null),
            'commercial_zip_code' => $this->nullableString($cabezal['commercial_zip_code'] ?? null),
            'dispatch_address' => $this->nullableString($cabezal['dispatch_address'] ?? null),
            'dispatch_delivery_address' => $this->nullableString($cabezal['dispatch_delivery_address'] ?? null),
            'dispatch_country' => $this->nullableString($cabezal['dispatch_country'] ?? null),
            'dispatch_state' => $this->nullableString($cabezal['dispatch_state'] ?? null),
            'dispatch_city' => $this->nullableString($cabezal['dispatch_city'] ?? null),
            'dispatch_zone' => $this->nullableString($cabezal['dispatch_zone'] ?? null),
            'dispatch_zip_code' => $this->nullableString($cabezal['dispatch_zip_code'] ?? null),
            'dispatch_shipping_notes' => $this->nullableString($cabezal['dispatch_shipping_notes'] ?? null),
        ];

        $customer = Customer::create($payload);
        $customer->id_kor = (int) $customer->customer_id;
        $customer->save();

        return $customer->fresh();
    }

    /**
     * Grupo de clientes válido (FK). Si el default de compañía no existe, usa el primero o crea "General".
     */
    protected function resolveValidCustomerGroupId(Company $company): int
    {
        $companyId = (int) $company->company_id;
        $cacheKey = 'cust_group:'.$companyId;
        if (isset($this->importRuntimeCache[$cacheKey])) {
            return (int) $this->importRuntimeCache[$cacheKey];
        }

        $candidates = [(int) ($company->default_customer_group_id ?? 0)];
        foreach ($candidates as $candidateId) {
            if ($candidateId <= 0) {
                continue;
            }
            $exists = DB::table('customer_groups')
                ->where('company_id', $companyId)
                ->where('group_id', $candidateId)
                ->whereNull('deleted_at')
                ->exists();
            if ($exists) {
                return $this->importRuntimeCache[$cacheKey] = $candidateId;
            }
        }

        $first = (int) (DB::table('customer_groups')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->orderBy('group_id')
            ->value('group_id') ?? 0);
        if ($first > 0) {
            return $this->importRuntimeCache[$cacheKey] = $first;
        }

        $created = \App\Models\CustomerGroup::create([
            'company_id' => $companyId,
            'name' => 'General',
            'group_status' => 1,
            'percent_price_amount' => 0,
        ]);

        return $this->importRuntimeCache[$cacheKey] = (int) $created->group_id;
    }

    /**
     * Término de venta válido (FK). Si el default no existe, usa el primero o crea "Cash".
     */
    protected function resolveValidCustomerTermId(Company $company): int
    {
        $companyId = (int) $company->company_id;
        $cacheKey = 'cust_term:'.$companyId;
        if (isset($this->importRuntimeCache[$cacheKey])) {
            return (int) $this->importRuntimeCache[$cacheKey];
        }

        $candidates = [(int) ($company->default_term_id ?? 0)];
        foreach ($candidates as $candidateId) {
            if ($candidateId <= 0) {
                continue;
            }
            $exists = DB::table('terms_of_sales')
                ->where('company_id', $companyId)
                ->where('term_id', $candidateId)
                ->whereNull('deleted_at')
                ->exists();
            if ($exists) {
                return $this->importRuntimeCache[$cacheKey] = $candidateId;
            }
        }

        $first = (int) (DB::table('terms_of_sales')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->orderBy('term_id')
            ->value('term_id') ?? 0);
        if ($first > 0) {
            return $this->importRuntimeCache[$cacheKey] = $first;
        }

        $created = \App\Models\TermsOfSale::create([
            'company_id' => $companyId,
            'name' => 'Cash',
            'term_status' => 1,
        ]);

        return $this->importRuntimeCache[$cacheKey] = (int) $created->term_id;
    }

    protected function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * Resuelve estado por lo que trae el cabezal (prioridad ERP):
     * codigo_estado (cod_status) → numero_estado (id_status) → nombre_estado.
     * Si no envían nada → estado Nuevo/PLACED (cod_status=0).
     *
     * @return array{id?:int,error?:string,message?:string}
     */
    protected function resolveStatusId(mixed $codigoEstado, mixed $numeroEstado, mixed $nombreEstado): array
    {
        $code = null;
        if ($codigoEstado !== null && $codigoEstado !== '' && is_numeric($codigoEstado)) {
            $code = (int) $codigoEstado;
        }

        if ($code !== null) {
            $byCode = StatusOrder::query()->where('cod_status', $code)->first();
            if ($byCode) {
                return ['id' => (int) $byCode->id_status];
            }

            // Compatibilidad: si no hay cod_status, intentar como id_status.
            $byId = StatusOrder::query()->where('id_status', $code)->first();
            if ($byId) {
                return ['id' => (int) $byId->id_status];
            }

            return [
                'error' => 'status_not_found',
                'message' => 'No se encontró estado con codigo_estado (cod_status) = '.$code.'.',
            ];
        }

        $numero = null;
        if ($numeroEstado !== null && $numeroEstado !== '' && is_numeric($numeroEstado)) {
            $numero = (int) $numeroEstado;
        }

        if ($numero !== null) {
            $byId = StatusOrder::query()->where('id_status', $numero)->first();
            if ($byId) {
                return ['id' => (int) $byId->id_status];
            }

            return [
                'error' => 'status_not_found',
                'message' => 'No se encontró estado con numero_estado (id_status) = '.$numero.'.',
            ];
        }

        if (is_string($nombreEstado) && trim($nombreEstado) !== '') {
            $name = trim($nombreEstado);
            $upper = mb_strtoupper($name, 'UTF-8');
            $byName = StatusOrder::query()
                ->where(function ($q) use ($upper) {
                    $q->whereRaw('UPPER(TRIM(name_status_spanish)) = ?', [$upper])
                        ->orWhereRaw('UPPER(TRIM(name_status_english)) = ?', [$upper]);
                })
                ->first();
            if ($byName) {
                return ['id' => (int) $byName->id_status];
            }

            return [
                'error' => 'status_not_found',
                'message' => 'No se encontró estado con nombre "'.$name.'".',
            ];
        }

        return ['id' => $this->ensurePlacedStatusId()];
    }

    protected function ensurePlacedStatusId(): int
    {
        $placed = StatusOrder::query()
            ->where('cod_status', OrderStatusCodes::PLACED->value)
            ->first();

        if ($placed) {
            return (int) $placed->id_status;
        }

        $created = StatusOrder::create([
            'cod_status' => OrderStatusCodes::PLACED->value,
            'name_status_spanish' => 'Nuevo',
            'name_status_english' => 'New',
        ]);

        return (int) $created->id_status;
    }

    /**
     * Prioridad: email → nombre exacto → nombre similar → default.
     * No se busca por seller_id (se ignora el id).
     *
     * @return array{id?:int,error?:string,message?:string}
     */
    protected function resolveSellerId(
        Customer $customer,
        Company $company,
        mixed $sellerId,
        mixed $sellerName,
        mixed $sellerEmail
    ): array {
        $companyId = (int) $company->company_id;
        $name = is_string($sellerName) ? trim($sellerName) : '';
        $email = is_string($sellerEmail) ? trim($sellerEmail) : '';
        $cacheKey = 'seller:'.$companyId.':'.mb_strtolower($email, 'UTF-8').'|'.mb_strtoupper($name, 'UTF-8');
        if (isset($this->importRuntimeCache[$cacheKey])) {
            return $this->importRuntimeCache[$cacheKey];
        }

        if ($email !== '') {
            $byEmail = Vendor::query()
                ->where('company_id', $companyId)
                ->whereHas('user', function ($q) use ($email) {
                    $q->whereRaw('LOWER(TRIM(email)) = ?', [mb_strtolower($email, 'UTF-8')]);
                })
                ->first();
            if ($byEmail) {
                return $this->importRuntimeCache[$cacheKey] = ['id' => (int) $byEmail->id];
            }
        }

        if ($name !== '') {
            $byExact = $this->findSellerByNameExact($companyId, $name);
            if ($byExact) {
                return $this->importRuntimeCache[$cacheKey] = ['id' => (int) $byExact->id];
            }

            $bySimilar = $this->findSellerByNameSimilar($companyId, $name);
            if ($bySimilar) {
                return $this->importRuntimeCache[$cacheKey] = ['id' => (int) $bySimilar->id];
            }
        }

        $result = $this->defaultSellerId($customer, $company);

        // Solo cachear resolución estable (por nombre/email). Defaults por cliente no se cachean con esa key.
        if ($email !== '' || $name !== '') {
            $this->importRuntimeCache[$cacheKey] = $result;
        }

        return $result;
    }

    /**
     * @return array{id?:int,error?:string,message?:string}
     */
    protected function defaultSellerId(Customer $customer, Company $company): array
    {
        $companyId = (int) $company->company_id;

        if (! empty($company->default_seller_id)) {
            $defaultId = (int) $company->default_seller_id;
            $exists = Vendor::query()
                ->where('id', $defaultId)
                ->where('company_id', $companyId)
                ->exists();
            if ($exists) {
                return ['id' => $defaultId];
            }
        }

        $linked = $customer->sellers()->first();
        if ($linked) {
            return ['id' => (int) $linked->id];
        }

        $first = Vendor::query()
            ->where('company_id', $companyId)
            ->orderBy('id')
            ->first();

        if ($first) {
            return ['id' => (int) $first->id];
        }

        return [
            'error' => 'seller_required',
            'message' => 'No hay vendedor por defecto ni sellers en la compañía.',
        ];
    }

    protected function findSellerByNameExact(int $companyId, string $name): ?Vendor
    {
        $upper = mb_strtoupper(trim($name), 'UTF-8');

        return Vendor::query()
            ->where('company_id', $companyId)
            ->whereHas('user', function ($q) use ($upper) {
                $q->where(function ($inner) use ($upper) {
                    $inner->whereRaw('UPPER(TRIM(name)) = ?', [$upper])
                        ->orWhereRaw('UPPER(TRIM(CONCAT(IFNULL(first_name, ""), " ", IFNULL(last_name, "")))) = ?', [$upper])
                        ->orWhereRaw('UPPER(TRIM(first_name)) = ?', [$upper])
                        ->orWhereRaw('UPPER(TRIM(last_name)) = ?', [$upper]);
                });
            })
            ->orderBy('id')
            ->first();
    }

    protected function findSellerByNameSimilar(int $companyId, string $name): ?Vendor
    {
        $needle = mb_strtoupper(trim($name), 'UTF-8');
        if ($needle === '') {
            return null;
        }

        $listKey = 'sellers_list:'.$companyId;
        if (! isset($this->importRuntimeCache[$listKey])) {
            $this->importRuntimeCache[$listKey] = Vendor::query()
                ->where('company_id', $companyId)
                ->with('user')
                ->orderBy('id')
                ->get();
        }
        /** @var \Illuminate\Support\Collection<int, Vendor> $sellers */
        $sellers = $this->importRuntimeCache[$listKey];

        $best = null;
        $bestScore = 0.0;
        foreach ($sellers as $seller) {
            $user = $seller->user;
            if (! $user) {
                continue;
            }
            foreach ($this->userNameVariants($user) as $variant) {
                $score = $this->nameSimilarityScore($needle, $variant);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $best = $seller;
                }
            }
        }

        return $bestScore >= 55.0 ? $best : null;
    }

    /**
     * Atributos de asignación de almacén (convención app: warehouse_id = user_id del operario).
     *
     * @return array{warehouse_user_id:int,warehouse_id:int,warehouse_assign_date:string}
     */
    protected function warehouseAssignmentAttributes(int $warehouseUserId, Carbon $now): array
    {
        return [
            'warehouse_user_id' => $warehouseUserId,
            'warehouse_id' => $warehouseUserId,
            'warehouse_assign_date' => $now->toDateTimeString(),
        ];
    }

    /**
     * Prioridad: email → nombre exacto → nombre similar → primer almacenero de la compañía.
     * Busca en warehouse_users y en company_user tipo warehouse.
     * No se busca por warehouse_user_id del cabezal.
     *
     * @return array{id:?int,error?:string,message?:string}
     */
    protected function resolveWarehouseUserId(
        int $companyId,
        mixed $warehouseUserId,
        mixed $warehouseName,
        mixed $warehouseEmail
    ): array {
        $name = is_string($warehouseName) ? trim($warehouseName) : '';
        $email = is_string($warehouseEmail) ? trim($warehouseEmail) : '';
        $cacheKey = 'wh:'.$companyId.':'.mb_strtolower($email, 'UTF-8').'|'.mb_strtoupper($name, 'UTF-8');
        if (isset($this->importRuntimeCache[$cacheKey])) {
            return $this->importRuntimeCache[$cacheKey];
        }

        if ($email !== '') {
            $byEmail = $this->findWarehouseStaffUserIdByEmail($companyId, $email);
            if ($byEmail) {
                return $this->importRuntimeCache[$cacheKey] = ['id' => $byEmail];
            }
        }

        if ($name !== '') {
            $byExact = $this->findWarehouseUserByNameExact($companyId, $name);
            if ($byExact) {
                return $this->importRuntimeCache[$cacheKey] = ['id' => $byExact];
            }

            $bySimilar = $this->findWarehouseUserByNameSimilar($companyId, $name);
            if ($bySimilar) {
                return $this->importRuntimeCache[$cacheKey] = ['id' => $bySimilar];
            }
        }

        $result = ['id' => $this->defaultWarehouseUserId($companyId)];
        $this->importRuntimeCache[$cacheKey] = $result;

        return $result;
    }

    protected function defaultWarehouseUserId(int $companyId): ?int
    {
        $cacheKey = 'wh_default:'.$companyId;
        if (array_key_exists($cacheKey, $this->importRuntimeCache)) {
            return $this->importRuntimeCache[$cacheKey];
        }

        $fromWu = WarehouseUser::query()
            ->where('company_id', $companyId)
            ->orderBy('user_id')
            ->value('user_id');
        if ($fromWu) {
            return $this->importRuntimeCache[$cacheKey] = (int) $fromWu;
        }

        $fromCompanyUser = DB::table('company_user')
            ->where('company_id', $companyId)
            ->where('type', 'warehouse')
            ->orderBy('user_id')
            ->value('user_id');

        return $this->importRuntimeCache[$cacheKey] = ($fromCompanyUser ? (int) $fromCompanyUser : null);
    }

    protected function findWarehouseStaffUserIdByEmail(int $companyId, string $email): ?int
    {
        $emailLower = mb_strtolower(trim($email), 'UTF-8');

        $fromWu = WarehouseUser::query()
            ->where('company_id', $companyId)
            ->whereHas('user', function ($q) use ($emailLower) {
                $q->whereRaw('LOWER(TRIM(email)) = ?', [$emailLower]);
            })
            ->orderBy('user_id')
            ->value('user_id');
        if ($fromWu) {
            return (int) $fromWu;
        }

        $fromCompany = DB::table('users')
            ->join('company_user', 'company_user.user_id', '=', 'users.id')
            ->where('company_user.company_id', $companyId)
            ->where('company_user.type', 'warehouse')
            ->whereRaw('LOWER(TRIM(users.email)) = ?', [$emailLower])
            ->orderBy('users.id')
            ->value('users.id');

        return $fromCompany ? (int) $fromCompany : null;
    }

    protected function findWarehouseUserByNameExact(int $companyId, string $name): ?int
    {
        $upper = mb_strtoupper(trim($name), 'UTF-8');

        $fromWu = WarehouseUser::query()
            ->where('company_id', $companyId)
            ->whereHas('user', function ($q) use ($upper) {
                $q->where(function ($inner) use ($upper) {
                    $inner->whereRaw('UPPER(TRIM(name)) = ?', [$upper])
                        ->orWhereRaw('UPPER(TRIM(CONCAT(IFNULL(first_name, ""), " ", IFNULL(last_name, "")))) = ?', [$upper])
                        ->orWhereRaw('UPPER(TRIM(first_name)) = ?', [$upper])
                        ->orWhereRaw('UPPER(TRIM(last_name)) = ?', [$upper]);
                });
            })
            ->orderBy('user_id')
            ->value('user_id');

        if ($fromWu) {
            return (int) $fromWu;
        }

        $fromCompany = DB::table('users')
            ->join('company_user', 'company_user.user_id', '=', 'users.id')
            ->where('company_user.company_id', $companyId)
            ->where('company_user.type', 'warehouse')
            ->where(function ($q) use ($upper) {
                $q->whereRaw('UPPER(TRIM(users.name)) = ?', [$upper])
                    ->orWhereRaw('UPPER(TRIM(CONCAT(IFNULL(users.first_name, ""), " ", IFNULL(users.last_name, "")))) = ?', [$upper]);
            })
            ->orderBy('users.id')
            ->value('users.id');

        return $fromCompany ? (int) $fromCompany : null;
    }

    protected function findWarehouseUserByNameSimilar(int $companyId, string $name): ?int
    {
        $needle = mb_strtoupper(trim($name), 'UTF-8');
        if ($needle === '') {
            return null;
        }

        $listKey = 'wh_candidates:'.$companyId;
        if (! isset($this->importRuntimeCache[$listKey])) {
            $candidates = [];

            $warehouseUsers = WarehouseUser::query()
                ->where('company_id', $companyId)
                ->with('user')
                ->orderBy('user_id')
                ->get();

            foreach ($warehouseUsers as $wu) {
                if ($wu->user) {
                    $candidates[(int) $wu->user_id] = $wu->user;
                }
            }

            $rows = DB::table('users')
                ->join('company_user', 'company_user.user_id', '=', 'users.id')
                ->where('company_user.company_id', $companyId)
                ->where('company_user.type', 'warehouse')
                ->select('users.id', 'users.name', 'users.first_name', 'users.last_name')
                ->orderBy('users.id')
                ->get();
            foreach ($rows as $row) {
                $candidates[(int) $row->id] = $row;
            }

            $this->importRuntimeCache[$listKey] = $candidates;
        }

        /** @var array<int, object> $candidates */
        $candidates = $this->importRuntimeCache[$listKey];

        $bestId = null;
        $bestScore = 0.0;
        foreach ($candidates as $userId => $user) {
            foreach ($this->userNameVariants($user) as $variant) {
                $score = $this->nameSimilarityScore($needle, $variant);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestId = (int) $userId;
                }
            }
        }

        return $bestScore >= 55.0 ? $bestId : null;
    }

    /**
     * @param  object  $user  User model o stdClass con name/first_name/last_name
     * @return list<string>
     */
    protected function userNameVariants(object $user): array
    {
        $name = trim((string) ($user->name ?? ''));
        $first = trim((string) ($user->first_name ?? ''));
        $last = trim((string) ($user->last_name ?? ''));
        $full = trim($first.' '.$last);

        $variants = [];
        foreach ([$name, $first, $last, $full] as $v) {
            $v = mb_strtoupper($v, 'UTF-8');
            if ($v !== '') {
                $variants[] = $v;
            }
        }

        return array_values(array_unique($variants));
    }

    protected function nameSimilarityScore(string $a, string $b): float
    {
        $a = mb_strtoupper(trim($a), 'UTF-8');
        $b = mb_strtoupper(trim($b), 'UTF-8');
        if ($a === '' || $b === '') {
            return 0.0;
        }
        if ($a === $b) {
            return 100.0;
        }

        similar_text($a, $b, $pct);

        if (str_contains($b, $a) || str_contains($a, $b)) {
            $pct = max($pct, 72.0);
        }

        $tokensA = preg_split('/\s+/u', $a) ?: [];
        $tokensB = preg_split('/\s+/u', $b) ?: [];
        $tokensA = array_values(array_filter($tokensA));
        $tokensB = array_values(array_filter($tokensB));
        if ($tokensA !== [] && $tokensB !== []) {
            $overlap = 0;
            foreach ($tokensA as $ta) {
                foreach ($tokensB as $tb) {
                    if ($ta === $tb || str_contains($tb, $ta) || str_contains($ta, $tb)) {
                        $overlap++;
                        break;
                    }
                }
            }
            $tokenScore = ($overlap / max(count($tokensA), count($tokensB))) * 100.0;
            $pct = max($pct, $tokenScore);
        }

        $maxLen = max(mb_strlen($a, 'UTF-8'), mb_strlen($b, 'UTF-8'));
        if ($maxLen > 0 && function_exists('levenshtein')) {
            // levenshtein es byte-oriented; útil como refuerzo en ASCII/latin.
            $lev = @levenshtein(substr($a, 0, 255), substr($b, 0, 255));
            if ($lev !== false && $lev >= 0) {
                $levScore = (1 - ($lev / max(strlen($a), strlen($b), 1))) * 100.0;
                $pct = max($pct, $levScore);
            }
        }

        return (float) $pct;
    }

    protected function resolveNoteUserId(int $companyId, ?int $sellerId): int
    {
        if ($sellerId) {
            $seller = Vendor::query()->find($sellerId);
            if ($seller && (int) ($seller->user_id ?? 0) > 0) {
                return (int) $seller->user_id;
            }
        }

        $companyUser = DB::table('company_user')
            ->where('company_id', $companyId)
            ->orderBy('user_id')
            ->value('user_id');

        if ($companyUser) {
            return (int) $companyUser;
        }

        $anyUser = DB::table('users')->orderBy('id')->value('id');

        return $anyUser ? (int) $anyUser : 1;
    }
}

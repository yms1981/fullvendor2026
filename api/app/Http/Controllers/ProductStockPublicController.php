<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProductStockPublicRequest;
use App\Models\Product;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

/**
 * API pública para actualizar stock / precios FOB y compra de productos (sin Bearer token).
 *
 * POST /api/v2/product/stock-update
 */
class ProductStockPublicController extends Controller
{
    #[OA\Post(
        path: 'api/v2/product/stock-update',
        summary: 'Actualizar stock y precios de productos (público, sin token)',
        description: 'Acepta un objeto o un arreglo. stock negativo se convierte en 0. stock=0 deshabilita el producto; stock>0 lo habilita. Actualiza fob_price y purchase_price.',
        tags: ['Product'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                oneOf: [
                    new OA\Schema(
                        required: ['company_id', 'sku', 'stock', 'fob_price', 'purchase_price'],
                        properties: [
                            new OA\Property(property: 'company_id', type: 'integer', example: 1),
                            new OA\Property(property: 'sku', type: 'string', example: 'ABC-001'),
                            new OA\Property(property: 'stock', type: 'number', example: 10),
                            new OA\Property(property: 'fob_price', type: 'number', example: 5.5),
                            new OA\Property(property: 'purchase_price', type: 'number', example: 4.2),
                        ]
                    ),
                    new OA\Schema(
                        type: 'array',
                        items: new OA\Items(
                            required: ['company_id', 'sku', 'stock', 'fob_price', 'purchase_price'],
                            properties: [
                                new OA\Property(property: 'company_id', type: 'integer', example: 1),
                                new OA\Property(property: 'sku', type: 'string', example: 'ABC-001'),
                                new OA\Property(property: 'stock', type: 'number', example: 0),
                                new OA\Property(property: 'fob_price', type: 'number', example: 5.5),
                                new OA\Property(property: 'purchase_price', type: 'number', example: 4.2),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Lote procesado (summary + results)'),
            new OA\Response(response: 422, description: 'Validación del lote'),
        ]
    )]
    public function update(UpdateProductStockPublicRequest $request)
    {
        $items = $request->input('items', []);
        $results = [];
        $updated = 0;
        $failed = 0;

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                $failed++;
                $results[] = [
                    'index' => $index,
                    'sku' => null,
                    'outcome' => 'failed',
                    'result' => [
                        'message' => 'Elemento inválido.',
                        'error' => 'invalid_item',
                    ],
                ];
                continue;
            }

            $sku = trim((string) ($item['sku'] ?? ''));

            try {
                $validated = UpdateProductStockPublicRequest::validateItem($item);
                if (! $validated['ok']) {
                    $failed++;
                    $results[] = [
                        'index' => $index,
                        'sku' => $sku !== '' ? $sku : null,
                        'outcome' => 'failed',
                        'result' => [
                            'message' => 'Error de validación; se continuó con el resto.',
                            'error' => 'validation_error',
                            'errors' => $validated['errors'],
                        ],
                    ];
                    continue;
                }

                $data = $validated['data'];
                $companyId = (int) $data['company_id'];
                $sku = trim((string) $data['sku']);
                $stock = (float) $data['stock'];
                if ($stock < 0) {
                    $stock = 0.0;
                }
                $fob = (float) $data['fob_price'];
                $purchase = (float) $data['purchase_price'];
                $status = $stock > 0 ? 1 : 0;

                $product = Product::query()
                    ->where('company_id', $companyId)
                    ->whereRaw('UPPER(TRIM(sku)) = ?', [mb_strtoupper($sku, 'UTF-8')])
                    ->first();

                if (! $product) {
                    $failed++;
                    $results[] = [
                        'index' => $index,
                        'sku' => $sku,
                        'outcome' => 'failed',
                        'result' => [
                            'message' => 'SKU no encontrado en la compañía.',
                            'error' => 'sku_not_found',
                            'company_id' => $companyId,
                        ],
                    ];
                    continue;
                }

                $product->stock = $stock;
                $product->fob_price = $fob;
                $product->purchase_price = $purchase;
                $product->status = $status;
                $product->save();

                $updated++;
                $results[] = [
                    'index' => $index,
                    'sku' => $sku,
                    'outcome' => 'updated',
                    'result' => [
                        'message' => $status === 1
                            ? 'Producto actualizado y habilitado.'
                            : 'Producto actualizado y deshabilitado (stock 0).',
                        'data' => [
                            'pro_id' => (int) $product->pro_id,
                            'company_id' => $companyId,
                            'sku' => $product->sku,
                            'stock' => (float) $product->stock,
                            'fob_price' => (float) $product->fob_price,
                            'purchase_price' => (float) $product->purchase_price,
                            'status' => (int) $product->status,
                            'enabled' => $status === 1,
                        ],
                    ],
                ];
            } catch (\Throwable $e) {
                $failed++;
                Log::error('ProductStockPublicController::update failed', [
                    'index' => $index,
                    'sku' => $sku,
                    'message' => $e->getMessage(),
                ]);
                $results[] = [
                    'index' => $index,
                    'sku' => $sku !== '' ? $sku : null,
                    'outcome' => 'failed',
                    'result' => [
                        'message' => 'No se pudo actualizar el producto; se continuó con el resto.',
                        'error' => $e->getMessage(),
                    ],
                ];
            }
        }

        $body = [
            'message' => 'Actualización de stock procesada.',
            'summary' => [
                'total' => count($items),
                'updated' => $updated,
                'failed' => $failed,
            ],
            'results' => $results,
        ];

        // Si solo enviaron un ítem, simplificar respuesta (misma info en results[0]).
        if (count($items) === 1 && isset($results[0])) {
            $body['data'] = $results[0]['result']['data'] ?? null;
            $body['outcome'] = $results[0]['outcome'];
            if ($results[0]['outcome'] === 'failed') {
                return response()->json([
                    'message' => $results[0]['result']['message'] ?? 'Error al actualizar.',
                    'error' => $results[0]['result']['error'] ?? 'update_failed',
                    'errors' => $results[0]['result']['errors'] ?? null,
                    'summary' => $body['summary'],
                    'results' => $results,
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        return response()->json($body, Response::HTTP_OK);
    }
}

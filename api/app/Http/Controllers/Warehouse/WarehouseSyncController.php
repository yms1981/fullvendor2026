<?php

namespace App\Http\Controllers\Warehouse;

use App\Http\Controllers\Mobile\MovilSyncController;
use App\Models\MobileChangeLog;
use App\Services\AppSalesman\AppSalesmanProductFifoService;
use App\Services\MobileSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

/**
 * Sync y FIFO propios de la app Warehouse.
 * No modifica AppSalesman ni MovilSyncController; reutiliza servicios internos.
 */
#[OA\Tag(
    name: 'WarehouseSync',
    description: 'Registro de dispositivo, sync total/parcial y FIFO para FullVendor Warehouse (Sanctum).'
)]
class WarehouseSyncController extends MovilSyncController
{
    public function __construct(
        MobileSyncService $sync,
        private readonly AppSalesmanProductFifoService $productFifoService,
    ) {
        parent::__construct($sync);
    }

    #[OA\Post(
        path: 'api/v3/warehouse/movilregister',
        summary: 'Registrar dispositivo warehouse',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseSync']
    )]
    public function movilregister(Request $request): JsonResponse
    {
        return parent::movilregister($request);
    }

    #[OA\Post(
        path: 'api/v3/warehouse/movilsync',
        summary: 'Decisión sync total/parcial (warehouse)',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseSync']
    )]
    public function movilsync(Request $request): JsonResponse
    {
        return parent::movilsync($request);
    }

    #[OA\Post(
        path: 'api/v3/warehouse/movilsync/complete',
        summary: 'Marcar sync warehouse completada',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseSync']
    )]
    public function complete(Request $request): JsonResponse
    {
        return parent::complete($request);
    }

    #[OA\Get(
        path: 'api/v3/warehouse/movilsync/products',
        summary: 'Deltas productos (warehouse)',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseSync']
    )]
    public function products(Request $request): JsonResponse
    {
        return $this->partialGroup($request, MobileChangeLog::GROUP_PRODUCTS);
    }

    #[OA\Get(
        path: 'api/v3/warehouse/movilsync/customers',
        summary: 'Deltas clientes (warehouse)',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseSync']
    )]
    public function customers(Request $request): JsonResponse
    {
        return $this->partialGroup($request, MobileChangeLog::GROUP_CUSTOMERS);
    }

    #[OA\Get(
        path: 'api/v3/warehouse/movilsync/categories',
        summary: 'Deltas categorías (warehouse)',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseSync']
    )]
    public function categories(Request $request): JsonResponse
    {
        return $this->partialGroup($request, MobileChangeLog::GROUP_CATEGORIES);
    }

    #[OA\Get(
        path: 'api/v3/warehouse/movilsync/orders',
        summary: 'Deltas órdenes (warehouse)',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseSync']
    )]
    public function orders(Request $request): JsonResponse
    {
        return $this->partialGroup($request, MobileChangeLog::GROUP_ORDERS);
    }

    /**
     * POST /api/v2/warehouse/product-fifo
     * FIFO detalle por orden (misma lógica que AppSalesman, ruta warehouse).
     */
    #[OA\Post(
        path: 'api/v2/warehouse/product-fifo',
        summary: 'FIFO detalle (warehouse)',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseSync']
    )]
    public function productFifo(Request $request): JsonResponse
    {
        return $this->respondProductFifo($request, 'detail');
    }

    /**
     * POST /api/v2/warehouse/product-fifo-summary
     */
    #[OA\Post(
        path: 'api/v2/warehouse/product-fifo-summary',
        summary: 'FIFO resumen (warehouse)',
        security: [['bearerAuth' => []]],
        tags: ['WarehouseSync']
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
                'error' => 'validation_company_id',
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
            return response()->json([
                'status' => '0',
                'error' => (string) ($result['error'] ?? 'no_fifo_found'),
            ], Response::HTTP_OK);
        }

        return response()->json($result, Response::HTTP_OK);
    }
}

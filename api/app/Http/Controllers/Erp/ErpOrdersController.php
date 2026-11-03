<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Erp\Concerns\ResolvesErpCompany;
use App\Http\Controllers\OrderImportPublicController;
use App\Http\Requests\ImportOrderPublicRequest;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\StatusOrder;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Erp', description: 'Integración ERP. Auth: header X-Api-Key (sin Sanctum).')]
class ErpOrdersController extends Controller
{
    use ResolvesErpCompany;

    #[OA\Get(
        path: 'api/v2/erp/orders',
        summary: 'Listar órdenes (shape softorderList)',
        security: [['erpApiKey' => []]],
        tags: ['Erp'],
        parameters: [
            new OA\Parameter(name: 'company_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'tipo_d', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'OK'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'X-Api-Key inválida'),
        ]
    )]
    public function list(Request $request): JsonResponse
    {
        $companyId = $this->requireCompanyId($request);
        $limit = max(1, min(500, (int) $request->input('limit', 500)));

        $query = Order::query()
            ->where('company_id', $companyId)
            ->orderByDesc('created')
            ->limit($limit);

        $tipoD = trim((string) $request->input('tipo_d', ''));
        if ($tipoD !== '') {
            $query->where('tipo_d', $tipoD);
        }
        if ($request->filled('from')) {
            $query->where('created', '>=', Carbon::parse((string) $request->input('from'))->startOfDay());
        }
        if ($request->filled('to')) {
            $query->where('created', '<=', Carbon::parse((string) $request->input('to'))->endOfDay());
        }

        $orders = $query->get();
        if ($orders->isEmpty()) {
            return $this->erpFail('No Order found.', 404, ['order_list' => []]);
        }

        return $this->erpOk([
            'order_list' => $this->hydrate($orders),
        ]);
    }

    #[OA\Get(
        path: 'api/v2/erp/orders/{orderNumber}',
        summary: 'Detalle de orden por número',
        security: [['erpApiKey' => []]],
        tags: ['Erp'],
        parameters: [
            new OA\Parameter(name: 'orderNumber', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'company_id', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'OK'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'X-Api-Key inválida'),
        ]
    )]
    public function showOrder(Request $request, string $orderNumber): JsonResponse
    {
        $companyId = $this->requireCompanyId($request);
        $number = trim(urldecode($orderNumber));
        if ($number === '') {
            return $this->erpFail('order_number is required');
        }

        $order = Order::query()
            ->where('company_id', $companyId)
            ->whereRaw('UPPER(order_number) = ?', [mb_strtoupper($number)])
            ->first();

        if (! $order) {
            return $this->erpFail('Order not found.', 404);
        }

        $list = $this->hydrate(collect([$order]));

        return $this->erpOk($list[0] ?? []);
    }

    /**
     * Importa/actualiza una orden (misma lógica que POST /order/import público, con X-Api-Key).
     */
    #[OA\Post(
        path: 'api/v2/erp/orders/import',
        summary: 'Importar orden desde ERP (autenticado)',
        description: 'Misma lógica que el import público /order/import, autenticado con X-Api-Key.',
        security: [['erpApiKey' => []]],
        tags: ['Erp'],
        parameters: [
            new OA\Parameter(name: 'company_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'X-Company-Id', in: 'header', required: false, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: Response::HTTP_OK, description: 'OK'),
            new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: 'X-Api-Key inválida'),
        ]
    )]
    public function import(ImportOrderPublicRequest $request): Response|JsonResponse
    {
        return app(OrderImportPublicController::class)->import($request);
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @return list<array<string, mixed>>
     */
    private function hydrate(Collection $orders): array
    {
        $orderIds = $orders->pluck('order_id')->map(fn ($id) => (int) $id)->all();
        $customerIds = $orders->pluck('customer_id')->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
        $sellerIds = $orders->pluck('seller_id')->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();

        $details = OrderDetail::query()
            ->whereIn('order_id', $orderIds)
            ->orderBy('detail_id')
            ->get()
            ->groupBy(fn ($row) => (int) $row->order_id);

        $productIds = $details->flatten(1)->pluck('product_id')->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
        $customers = Customer::query()->whereIn('customer_id', $customerIds)->get()->keyBy('customer_id');
        $products = Product::query()->whereIn('pro_id', $productIds)->get()->keyBy('pro_id');
        $sellers = Vendor::query()->whereIn('id', $sellerIds)->with('user')->get()->keyBy('id');
        $statuses = StatusOrder::query()->get()->keyBy('id_status');

        $out = [];
        foreach ($orders as $order) {
            $orderId = (int) $order->order_id;
            $customer = $customers->get((int) $order->customer_id);
            $seller = $sellers->get((int) ($order->seller_id ?? 0));
            $status = $statuses->get((int) ($order->status_id ?? 0));
            $cod = (int) ($status->cod_status ?? $order->status_id ?? 0);

            $sellerUser = $seller?->user;
            $sellerName = trim((string) ($sellerUser?->name ?? ''));
            if ($sellerName === '' && $sellerUser) {
                $sellerName = trim(($sellerUser->first_name ?? '').' '.($sellerUser->last_name ?? ''));
            }

            $productList = [];
            $subTotal = 0.0;
            foreach ($details->get($orderId, collect()) as $detail) {
                $product = $products->get((int) $detail->product_id);
                $qty = (float) ($detail->qty ?? 0);
                $sale = (float) ($detail->sale_price ?? 0);
                $subTotal += $qty * $sale;
                $productList[] = [
                    'order_id' => $orderId,
                    'product_id' => (int) ($detail->product_id ?? 0),
                    'name' => (string) ($product?->name ?? ''),
                    'sku' => (string) ($product?->sku ?? ''),
                    'qty' => round($qty, 2),
                    'sale_price' => round($sale, 2),
                    'fob_price' => round((float) ($detail->fob_price ?? $product?->fob_price ?? 0), 2),
                    'purchase_price' => round((float) ($detail->purchase_price ?? $product?->purchase_price ?? 0), 2),
                    'barcode' => (string) ($product?->barcode ?? ''),
                    'discount' => round((float) ($detail->discount ?? 0), 2),
                    'discount_type' => (int) ($detail->discount_type ?? 1),
                    'delivered_qty' => round((float) ($detail->delivered_quantity ?? 0), 2),
                    'currency_type' => (string) ($product?->currency_type ?? '$'),
                ];
            }

            $out[] = [
                'tipo_d' => (string) ($order->tipo_d ?? ''),
                'order_id' => $orderId,
                'order_number' => (string) ($order->order_number ?? ''),
                'order_comments' => (string) ($order->order_comments ?? ''),
                'internal_notes' => (string) ($order->internal_notes ?? ''),
                'amount' => round((float) ($order->amount ?? $subTotal), 2),
                'total_amount' => round((float) ($order->total_amount ?? $subTotal), 2),
                'discount' => round((float) ($order->discount ?? 0), 2),
                'discount_type' => (int) ($order->discount_type ?? 1),
                'business_name' => (string) ($customer?->business_name ?? ''),
                'customer_id' => (int) ($order->customer_id ?? 0),
                'name' => (string) ($customer?->name ?? ''),
                'email' => (string) ($customer?->email ?? ''),
                'phone' => (string) ($customer?->phone ?? ''),
                'created' => optional($order->created)?->format('Y-m-d H:i:s') ?? (string) ($order->created ?? ''),
                'updated' => optional($order->updated)?->format('Y-m-d H:i:s') ?? (string) ($order->updated ?? ''),
                'order_status' => $cod,
                'status_id' => (int) ($order->status_id ?? 0),
                'name_status_spanish' => (string) ($status?->name_status_spanish ?? ''),
                'name_status_english' => (string) ($status?->name_status_english ?? ''),
                'scolor' => (string) ($status?->color ?? ''),
                'user_id' => (int) ($order->seller_id ?? 0) ?: '',
                'seller_id' => (int) ($order->seller_id ?? 0) ?: '',
                'seller_name' => $sellerName,
                'seller_email' => (string) ($sellerUser?->email ?? ''),
                'warehouse_user_id' => (int) ($order->warehouse_user_id ?? 0) ?: '',
                'source' => (string) ($order->source ?? ''),
                'product_list' => $productList,
            ];
        }

        return $out;
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Controllers\CustomerController;
use App\Http\Requests\SellerPortal\SellerPortalStoreCustomerRequest;
use App\Http\Requests\SellerPortal\SellerPortalStoreOrderRequest;
use App\Http\Requests\SellerPortal\SellerPortalUpdateCustomerRequest;
use App\Models\Customer;
use App\Models\Vendor;
use App\Services\CompanyPlanLimitService;
use App\Services\SellerPortalCatalogService;
use App\Services\SellerPortalContextService;
use App\Services\SellerPortalCustomerService;
use App\Services\SellerPortalOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * API dedicada al portal vendedor MVC (/sellers/*). No expone rutas del panel company al MVC.
 */
class SellerPortalController extends Controller
{
    public function context(SellerPortalContextService $contextService, Request $request): JsonResponse
    {
        $vendor = $this->vendor($request);

        return response()->json(['data' => $contextService->buildContext($vendor)]);
    }

    public function customerGroups(SellerPortalContextService $contextService, Request $request): JsonResponse
    {
        $vendor = $this->vendor($request);

        return response()->json(['data' => $contextService->customerGroups((int) $vendor->company_id)]);
    }

    public function terms(SellerPortalContextService $contextService, Request $request): JsonResponse
    {
        $vendor = $this->vendor($request);

        return response()->json(['data' => $contextService->terms((int) $vendor->company_id)]);
    }

    public function sellers(SellerPortalContextService $contextService, Request $request): JsonResponse
    {
        $vendor = $this->vendor($request);

        return response()->json(['data' => $contextService->sellers((int) $vendor->company_id)]);
    }

    public function countries(SellerPortalContextService $contextService): JsonResponse
    {
        return response()->json(['data' => $contextService->countries()]);
    }

    public function customers(SellerPortalCustomerService $customerService, Request $request): JsonResponse
    {
        $vendor = $this->vendor($request);

        return response()->json(['data' => $customerService->portalGrid($vendor)]);
    }

    public function showCustomer(int $customerId, SellerPortalCustomerService $customerService, Request $request): JsonResponse
    {
        $vendor = $this->vendor($request);
        $row = $customerService->findRawCustomer($vendor, $customerId);
        if ($row === null) {
            return response()->json(['message' => 'not_found', 'error' => true], 404);
        }

        return response()->json(['data' => $row]);
    }

    public function storeCustomer(
        SellerPortalStoreCustomerRequest $request,
        CustomerController $customers,
        CompanyPlanLimitService $planLimits,
    ): JsonResponse {
        $vendor = $this->vendor($request);
        $request->merge(['company_id' => (int) $vendor->company_id]);

        return $customers->store($request, $planLimits);
    }

    public function updateCustomer(
        SellerPortalUpdateCustomerRequest $request,
        int $customerId,
        SellerPortalCustomerService $customerService,
        CustomerController $customers,
    ): JsonResponse {
        $vendor = $this->vendor($request);
        $customer = Customer::query()->find($customerId);
        if ($customer === null || ! $customerService->customerAccessibleByVendor($vendor, $customer)) {
            return response()->json(['message' => 'not_found', 'error' => true], 404);
        }

        $request->merge(['company_id' => (int) $vendor->company_id]);

        return $customers->update($request, $customer);
    }

    public function unassignCustomer(
        int $customerId,
        SellerPortalCustomerService $customerService,
        Request $request,
    ): JsonResponse {
        $vendor = $this->vendor($request);
        if (! $customerService->unassignSeller($vendor, $customerId)) {
            return response()->json(['message' => 'not_found', 'error' => true], 404);
        }

        return response()->json(['message' => 'ok']);
    }

    public function orders(SellerPortalOrderService $orderService, Request $request): JsonResponse
    {
        $vendor = $this->vendor($request);
        $sellerId = (int) $request->query('seller_id', (int) $vendor->id);
        $includeDetails = $request->boolean('include_order_details', false);

        $customerIds = [];
        $raw = $request->query('customer_ids');
        if (is_array($raw)) {
            foreach ($raw as $id) {
                $cid = (int) $id;
                if ($cid > 0) {
                    $customerIds[] = $cid;
                }
            }
        } elseif ($raw !== null && $raw !== '') {
            $cid = (int) $raw;
            if ($cid > 0) {
                $customerIds = [$cid];
            }
        }

        if ($customerIds === []) {
            $customerIds = $orderService->assignedCustomerIds($vendor);
        }

        return response()->json([
            'data' => $orderService->listOrders($vendor, $sellerId, $customerIds, $includeDetails),
        ]);
    }

    public function showOrder(int $orderId, SellerPortalOrderService $orderService, Request $request): JsonResponse
    {
        $vendor = $this->vendor($request);
        $row = $orderService->findOrderFull($vendor, $orderId);
        if ($row === null) {
            return response()->json(['message' => 'not_found', 'error' => true], 404);
        }

        return response()->json(['data' => $row]);
    }

    public function showProduct(int $proId, SellerPortalOrderService $orderService, Request $request): JsonResponse
    {
        $vendor = $this->vendor($request);
        $row = $orderService->productForOrderImage($vendor, $proId);
        if ($row === null) {
            return response()->json(['message' => 'not_found', 'error' => true], 404);
        }

        return response()->json(['data' => $row]);
    }

    public function catalogProducts(
        int $customerId,
        SellerPortalCatalogService $catalogService,
        Request $request,
    ): JsonResponse {
        $vendor = $this->vendor($request);

        return response()->json([
            'data' => $catalogService->productsForCustomer($vendor, $customerId),
        ]);
    }

    public function catalogBrowseSession(
        int $customerId,
        SellerPortalCatalogService $catalogService,
        Request $request,
    ): JsonResponse {
        $vendor = $this->vendor($request);

        try {
            $session = $catalogService->createBrowseSession($vendor, $customerId);

            return response()->json(['data' => $session]);
        } catch (\RuntimeException $e) {
            $code = match ($e->getMessage()) {
                'customer_forbidden' => 403,
                'default_catalog_missing', 'catalog_cart_table_missing' => 422,
                default => 500,
            };

            return response()->json(['message' => $e->getMessage(), 'error' => true], $code);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'error', 'error' => true], 500);
        }
    }

    public function storeCatalogOrder(
        SellerPortalStoreOrderRequest $request,
        SellerPortalOrderService $orderService,
        CompanyPlanLimitService $planLimits,
    ): JsonResponse {
        $vendor = $this->vendor($request);
        $validated = $request->validated();
        if ((int) ($validated['seller_id'] ?? 0) <= 0) {
            $validated['seller_id'] = (int) $vendor->id;
        }
        $validated['source'] = $validated['source'] ?? 'seller_portal';

        $result = $orderService->createOrder($vendor, $validated, $planLimits);
        if (isset($result['error'])) {
            return response()->json(['message' => $result['error'], 'error' => true], (int) ($result['status'] ?? 500));
        }

        $order = $result['order'];

        return response()->json([
            'data' => [
                'order_id' => (int) $order->order_id,
                'id' => (int) $order->order_id,
            ],
        ], Response::HTTP_CREATED);
    }

    private function vendor(Request $request): Vendor
    {
        /** @var Vendor $vendor */
        $vendor = $request->attributes->get('seller_portal_vendor');

        return $vendor;
    }
}

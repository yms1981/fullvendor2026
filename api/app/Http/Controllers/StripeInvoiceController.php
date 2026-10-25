<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\Stripe\StripeCustomerService;
use App\Services\Stripe\StripeInvoiceLang;
use App\Services\Stripe\StripeInvoiceOrchestrator;
use App\Services\Stripe\StripeInvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\HttpFoundation\Response;

/**
 * API equivalente a StripeFactura + StripeTools (facturas).
 */
class StripeInvoiceController extends Controller
{
    public function __construct(
        private readonly StripeInvoiceOrchestrator $orchestrator,
        private readonly StripeInvoiceService $invoiceService,
        private readonly StripeCustomerService $customerService,
    ) {
    }

    /**
     * POST /stripe/invoices/{invoice}/create — CrearFacturaAsync
     */
    public function createOnStripe(int $invoice): JsonResponse
    {
        if (! Schema::hasTable('invoices')) {
            return $this->errorResponse('stripe_table_invoices_missing', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        try {
            $result = $this->orchestrator->createStripeInvoiceForLocal($invoice);

            return response()->json([
                'data' => $result,
                'message' => StripeInvoiceLang::get('msg_stripe_invoice_created'),
            ]);
        } catch (ApiErrorException $e) {
            return $this->stripeError($e);
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * POST /stripe/invoices/{invoice}/sync — actualizar una factura desde Stripe
     */
    public function syncFromStripe(int $invoice): JsonResponse
    {
        try {
            $result = $this->orchestrator->syncLocalInvoice($invoice);

            return response()->json([
                'data' => $result,
                'message' => StripeInvoiceLang::get('msg_stripe_invoice_synced'),
            ]);
        } catch (ApiErrorException $e) {
            return $this->stripeError($e);
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * POST /stripe/invoices/sync-all — UpdateInvoiceStripe
     */
    public function syncAll(): JsonResponse
    {
        try {
            $result = $this->orchestrator->syncAllLocalInvoicesWithStripe();

            return response()->json([
                'data' => $result,
                'message' => StripeInvoiceLang::get('msg_stripe_bulk_sync_done'),
            ]);
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }

    /**
     * POST /stripe/invoices/create-missing — VerificarFacturaAsync
     */
    public function createMissing(): JsonResponse
    {
        try {
            $result = $this->orchestrator->createMissingStripeInvoices();

            return response()->json([
                'data' => $result,
                'message' => StripeInvoiceLang::get('msg_stripe_missing_created'),
            ]);
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }

    /**
     * GET /stripe/invoices/stripe/{stripeInvoiceId} — BuscarFacturaPorId
     */
    public function showStripeInvoice(string $stripeInvoiceId): JsonResponse
    {
        try {
            $details = $this->invoiceService->findStripeInvoiceById($stripeInvoiceId);

            return response()->json(['data' => $details->toArray()]);
        } catch (ApiErrorException $e) {
            return $this->stripeError($e);
        }
    }

    /**
     * POST /stripe/invoices/stripe/{stripeInvoiceId}/void — CancelarFactura
     */
    public function voidStripeInvoice(string $stripeInvoiceId): JsonResponse
    {
        try {
            $voided = $this->invoiceService->voidStripeInvoice($stripeInvoiceId);
            if (Schema::hasTable('invoices')) {
                Invoice::query()->where('stripe_id', $stripeInvoiceId)->update(['stripe_status' => 'void']);
            }

            return response()->json([
                'data' => ['voided' => $voided],
                'message' => StripeInvoiceLang::get('msg_stripe_invoice_voided'),
            ]);
        } catch (ApiErrorException $e) {
            return $this->stripeError($e);
        }
    }

    /**
     * GET /stripe/companies/{company}/invoices — ListarFacturasPorClienteAsync
     */
    public function listByCompany(int $company): JsonResponse
    {
        try {
            $customerId = $this->customerService->syncCompanyCustomer($company);
            $limit = (int) request()->query('limit', 100);
            $list = $this->invoiceService->listStripeInvoicesByCustomer($customerId, $limit);

            return response()->json([
                'data' => array_map(static fn ($d) => $d->toArray(), $list),
            ]);
        } catch (ApiErrorException $e) {
            return $this->stripeError($e);
        }
    }

    private function errorResponse(string $messageKey, int $status): JsonResponse
    {
        return response()->json([
            'message' => StripeInvoiceLang::get($messageKey),
            'message_key' => $messageKey,
        ], $status);
    }

    private function stripeError(ApiErrorException $e): JsonResponse
    {
        return response()->json([
            'message' => StripeInvoiceLang::get('msg_stripe_api_error'),
            'message_key' => 'msg_stripe_api_error',
            'stripe_error' => $e->getMessage(),
        ], Response::HTTP_BAD_GATEWAY);
    }
}

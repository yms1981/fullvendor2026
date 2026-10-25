<?php

namespace App\Services\Stripe;

use App\Models\Invoice;
use Illuminate\Support\Collection;

/**
 * Réplica de StripeTools: orquestación facturas ↔ Stripe.
 */
class StripeInvoiceOrchestrator
{
    public function __construct(
        private readonly StripeInvoiceService $invoiceService,
        private readonly StripeCustomerService $customerService,
    ) {
    }

    /**
     * CrearFacturaAsync: crea en Stripe si la empresa tiene (o obtiene) stripe_id.
     *
     * @return array{stripe_invoice_id: string}
     */
    public function createStripeInvoiceForLocal(int $invoiceId): array
    {
        $invoice = Invoice::query()->with('company')->findOrFail($invoiceId);
        $company = $invoice->company;
        if ($company === null) {
            throw new \RuntimeException('stripe_company_not_found');
        }

        $customerId = $this->customerService->companyStripeId($company);
        if ($customerId === null || $customerId === '') {
            $customerId = $this->customerService->syncCompanyCustomer((int) $company->company_id);
        }

        $items = $this->invoiceService->buildItemsFromLocalInvoice($invoice);
        $description = trim((string) ($invoice->concept ?? ''));
        $docNumber = trim((string) ($invoice->doc_number ?? ''));
        if ($docNumber === '') {
            $docNumber = null;
        }

        $stripeInvoiceId = $this->invoiceService->createStripeInvoice(
            $invoiceId,
            $docNumber,
            $customerId,
            $items,
            $description,
        );

        return ['stripe_invoice_id' => $stripeInvoiceId];
    }

    /**
     * UpdateInvoiceStripe: sincroniza una factura local que ya tiene stripe_id.
     */
    public function syncLocalInvoice(int $invoiceId): array
    {
        $invoice = Invoice::query()->findOrFail($invoiceId);
        $details = $this->invoiceService->syncLocalInvoiceFromStripe($invoice);

        return ['invoice' => $invoice->fresh(), 'stripe' => $details->toArray()];
    }

    /**
     * UpdateInvoiceStripe masivo.
     *
     * @return array{processed: int, updated: int, errors: list<array{invoice_id: int, message: string}>}
     */
    public function syncAllLocalInvoicesWithStripe(): array
    {
        $processed = 0;
        $updated = 0;
        $errors = [];

        Invoice::query()
            ->whereNotNull('stripe_id')
            ->where('stripe_id', '!=', '')
            ->orderBy('invoice_id')
            ->chunkById(50, function (Collection $rows) use (&$processed, &$updated, &$errors) {
                foreach ($rows as $invoice) {
                    ++$processed;
                    try {
                        $this->invoiceService->syncLocalInvoiceFromStripe($invoice);
                        ++$updated;
                    } catch (\Throwable $e) {
                        $errors[] = [
                            'invoice_id' => (int) $invoice->invoice_id,
                            'message' => $e->getMessage(),
                        ];
                    }
                }
            }, 'invoice_id');

        return [
            'processed' => $processed,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }

    /**
     * VerificarFacturaAsync: crea en Stripe las facturas locales sin stripe_id.
     *
     * @return array{attempted: int, created: int, errors: list<array{invoice_id: int, message: string}>}
     */
    public function createMissingStripeInvoices(): array
    {
        $attempted = 0;
        $created = 0;
        $errors = [];

        Invoice::query()
            ->where(function ($q) {
                $q->whereNull('stripe_id')->orWhere('stripe_id', '=', '');
            })
            ->orderBy('invoice_id')
            ->chunkById(25, function (Collection $rows) use (&$attempted, &$created, &$errors) {
                foreach ($rows as $invoice) {
                    ++$attempted;
                    try {
                        $this->createStripeInvoiceForLocal((int) $invoice->invoice_id);
                        ++$created;
                    } catch (\Throwable $e) {
                        $errors[] = [
                            'invoice_id' => (int) $invoice->invoice_id,
                            'message' => $e->getMessage(),
                        ];
                    }
                }
            }, 'invoice_id');

        return [
            'attempted' => $attempted,
            'created' => $created,
            'errors' => $errors,
        ];
    }
}

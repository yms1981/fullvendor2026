<?php

namespace App\Services\Stripe;

use App\Models\Invoice;
use App\Services\Stripe\Dto\InvoiceItemData;
use App\Services\Stripe\Dto\InvoiceStripeDetailsData;
use Stripe\Exception\ApiErrorException;
use Stripe\Invoice as StripeInvoice;
use Stripe\Price;
use Stripe\Product;
use Stripe\StripeClient;

/**
 * Réplica del flujo StripeFactura (legado C#): factura puntual en Stripe + sync a invoices.
 */
class StripeInvoiceService
{
    public function __construct(
        private readonly StripeApiKeyResolver $apiKeyResolver,
    ) {
    }

    /**
     * Crea factura en Stripe y actualiza la fila local.
     *
     * @param  list<InvoiceItemData>  $items
     *
     * @throws ApiErrorException|\RuntimeException
     */
    public function createStripeInvoice(
        int $invoiceId,
        ?string $docNumber,
        string $customerId,
        array $items,
        string $description = '',
        ?string $currency = null,
    ): string {
        if ($items === []) {
            throw new \InvalidArgumentException('stripe_invoice_items_required');
        }

        $client = $this->apiKeyResolver->stripeClient();
        $currency = strtolower($currency ?: (string) config('stripe.currency', 'usd'));
        $productName = $description !== '' ? $description : $items[0]->description;
        $unitAmountCents = (int) round($items[0]->amount * 100);
        $autoAdvance = $items[0]->amount == 0.0;

        $product = $this->findOrCreateProduct($client, $productName);
        $this->findOrCreatePrice($client, $product->id, $unitAmountCents, $currency);

        $createOpts = [
            'customer' => $customerId,
            'auto_advance' => $autoAdvance,
        ];
        if ($docNumber !== null && $docNumber !== '') {
            $createOpts['number'] = $docNumber;
        }
        $invoice = $client->invoices->create($createOpts);

        $client->invoiceItems->create([
            'customer' => $customerId,
            'invoice' => $invoice->id,
            'description' => $description !== '' ? $description : $items[0]->description,
            'quantity' => $items[0]->quantity,
            'price_data' => [
                'currency' => $currency,
                'product' => $product->id,
                'unit_amount' => $unitAmountCents,
            ],
        ]);

        $finalized = $client->invoices->finalizeInvoice($invoice->id, []);

        $this->applyStripeInvoiceToLocal($invoiceId, $finalized);

        return $finalized->id;
    }

    /**
     * @throws ApiErrorException
     */
    public function findStripeInvoiceById(string $stripeInvoiceId): InvoiceStripeDetailsData
    {
        $invoice = $this->apiKeyResolver->stripeClient()->invoices->retrieve($stripeInvoiceId, ['expand' => ['lines']]);

        return $this->mapStripeInvoiceToDetails($invoice);
    }

    /**
     * @throws ApiErrorException
     */
    public function voidStripeInvoice(string $stripeInvoiceId): bool
    {
        $invoice = $this->apiKeyResolver->stripeClient()->invoices->voidInvoice($stripeInvoiceId, []);

        return $invoice->status === 'void';
    }

    /**
     * @return list<InvoiceStripeDetailsData>
     *
     * @throws ApiErrorException
     */
    public function listStripeInvoicesByCustomer(string $customerId, int $limit = 100): array
    {
        $list = $this->apiKeyResolver->stripeClient()->invoices->all([
            'customer' => $customerId,
            'limit' => min(max($limit, 1), 100),
            'expand' => ['data.lines'],
        ]);

        $out = [];
        foreach ($list->data as $invoice) {
            $out[] = $this->mapStripeInvoiceToDetails($invoice);
        }

        return $out;
    }

    /**
     * Sincroniza campos Stripe de una factura local desde la API.
     *
     * @throws ApiErrorException
     */
    public function syncLocalInvoiceFromStripe(Invoice $invoice): InvoiceStripeDetailsData
    {
        $stripeId = trim((string) ($invoice->stripe_id ?? ''));
        if ($stripeId === '') {
            throw new \RuntimeException('stripe_invoice_id_missing');
        }

        $details = $this->findStripeInvoiceById($stripeId);
        $this->applyDetailsToInvoiceModel($invoice, $details);
        $invoice->save();

        return $details;
    }

    public function applyDetailsToInvoiceModel(Invoice $invoice, InvoiceStripeDetailsData $details): void
    {
        $invoice->stripe_id = $details->id;
        $invoice->stripe_url = $details->hostedInvoiceUrl;
        $invoice->stripe_doc = $details->invoiceNumber;
        $invoice->stripe_pdf = $details->invoicePdfUrl;
        $invoice->stripe_status = $details->status;
        if ($details->status === 'paid') {
            $invoice->paid = 1;
        }
    }

    /**
     * @param  list<InvoiceItemData>  $items
     */
    public function buildItemsFromLocalInvoice(Invoice $invoice): array
    {
        $amount = $this->resolveInvoiceAmount($invoice);
        $desc = trim((string) ($invoice->concept ?? ''));

        return [
            new InvoiceItemData(
                description: $desc !== '' ? $desc : 'Invoice #' . $invoice->invoice_id,
                unitPrice: $amount,
                amount: $amount,
                quantity: 1,
            ),
        ];
    }

    public function resolveInvoiceAmount(Invoice $invoice): float
    {
        if ($invoice->invoice_paid !== null && (float) $invoice->invoice_paid > 0) {
            return (float) $invoice->invoice_paid;
        }
        if ($invoice->debt !== null && (float) $invoice->debt > 0) {
            return (float) $invoice->debt;
        }

        return 0.0;
    }

    private function applyStripeInvoiceToLocal(int $invoiceId, StripeInvoice $stripeInvoice): void
    {
        $invoice = Invoice::query()->findOrFail($invoiceId);
        $details = $this->mapStripeInvoiceToDetails($stripeInvoice);
        $this->applyDetailsToInvoiceModel($invoice, $details);
        $invoice->save();
    }

    private function mapStripeInvoiceToDetails(StripeInvoice $invoice): InvoiceStripeDetailsData
    {
        $items = [];
        if ($invoice->lines !== null && is_iterable($invoice->lines->data)) {
            foreach ($invoice->lines->data as $line) {
                $items[] = new InvoiceItemData(
                    description: (string) ($line->description ?? ''),
                    unitPrice: ((float) ($line->amount ?? 0)) / 100,
                    amount: ((float) ($line->amount ?? 0)) / 100,
                    quantity: (int) ($line->quantity ?? 1),
                );
            }
        }

        $paidAt = null;
        if ($invoice->status === 'paid' && isset($invoice->status_transitions->paid_at)) {
            $paidAt = \DateTimeImmutable::createFromFormat('U', (string) $invoice->status_transitions->paid_at) ?: null;
        }

        $createdAt = null;
        if (isset($invoice->created)) {
            $createdAt = \DateTimeImmutable::createFromFormat('U', (string) $invoice->created) ?: null;
        }

        $dueDate = null;
        if (isset($invoice->due_date)) {
            $dueDate = \DateTimeImmutable::createFromFormat('U', (string) $invoice->due_date) ?: null;
        }

        return new InvoiceStripeDetailsData(
            id: $invoice->id,
            invoiceNumber: $invoice->number,
            customerId: is_string($invoice->customer) ? $invoice->customer : ($invoice->customer->id ?? null),
            status: (string) $invoice->status,
            amountTotal: ((float) ($invoice->amount_due ?? 0)) / 100,
            amountPaid: ((float) ($invoice->amount_paid ?? 0)) / 100,
            amountRemaining: ((float) ($invoice->amount_remaining ?? 0)) / 100,
            createdAt: $createdAt,
            dueDate: $dueDate,
            paidAt: $paidAt,
            description: $invoice->description,
            items: $items,
            hostedInvoiceUrl: $invoice->hosted_invoice_url,
            invoicePdfUrl: $invoice->invoice_pdf,
            currency: (string) ($invoice->currency ?? 'usd'),
            paymentAttemptCount: (int) ($invoice->attempt_count ?? 0),
        );
    }

    private function findOrCreateProduct(StripeClient $client, string $productName): Product
    {
        $escaped = str_replace('"', '\\"', $productName);
        $search = $client->products->search([
            'query' => 'name:"' . $escaped . '"',
            'limit' => 100,
        ]);

        foreach ($search->data as $candidate) {
            if ($candidate->name === $productName) {
                return $candidate;
            }
        }

        return $client->products->create([
            'name' => $productName,
            'description' => $productName,
        ]);
    }

    private function findOrCreatePrice(StripeClient $client, string $productId, int $unitAmountCents, string $currency): Price
    {
        $list = $client->prices->all([
            'product' => $productId,
            'active' => true,
            'limit' => 100,
        ]);

        foreach ($list->data as $price) {
            if ((int) ($price->unit_amount ?? 0) === $unitAmountCents && strtolower((string) $price->currency) === $currency) {
                return $price;
            }
        }

        if ($list->data !== []) {
            return $list->data[0];
        }

        return $client->prices->create([
            'unit_amount' => $unitAmountCents,
            'currency' => $currency,
            'active' => true,
            'product' => $productId,
        ]);
    }
}

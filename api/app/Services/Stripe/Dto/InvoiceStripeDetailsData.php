<?php

namespace App\Services\Stripe\Dto;

/** Equivalente a DetallesFacturaStripe (legado C#). */
final class InvoiceStripeDetailsData
{
    /**
     * @param  list<InvoiceItemData>  $items
     */
    public function __construct(
        public string $id,
        public ?string $invoiceNumber,
        public ?string $customerId,
        public string $status,
        public float $amountTotal,
        public float $amountPaid,
        public float $amountRemaining,
        public ?\DateTimeInterface $createdAt,
        public ?\DateTimeInterface $dueDate,
        public ?\DateTimeInterface $paidAt,
        public ?string $description,
        public array $items,
        public ?string $hostedInvoiceUrl,
        public ?string $invoicePdfUrl,
        public string $currency,
        public int $paymentAttemptCount,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoiceNumber,
            'customer_id' => $this->customerId,
            'status' => $this->status,
            'amount_total' => $this->amountTotal,
            'amount_paid' => $this->amountPaid,
            'amount_remaining' => $this->amountRemaining,
            'created_at' => $this->createdAt?->format('c'),
            'due_date' => $this->dueDate?->format('c'),
            'paid_at' => $this->paidAt?->format('c'),
            'description' => $this->description,
            'items' => array_map(static fn (InvoiceItemData $i) => [
                'description' => $i->description,
                'unit_price' => $i->unitPrice,
                'amount' => $i->amount,
                'quantity' => $i->quantity,
            ], $this->items),
            'hosted_invoice_url' => $this->hostedInvoiceUrl,
            'invoice_pdf_url' => $this->invoicePdfUrl,
            'currency' => $this->currency,
            'payment_attempt_count' => $this->paymentAttemptCount,
        ];
    }
}

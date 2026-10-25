<?php

namespace App\Services\Stripe\Dto;

/** Equivalente a ItemFactura (legado C#). */
final class InvoiceItemData
{
    public function __construct(
        public string $description,
        public float $unitPrice,
        public float $amount,
        public int $quantity = 1,
    ) {
    }
}

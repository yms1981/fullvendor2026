<?php

namespace App\Services\Stripe;

/**
 * @deprecated Use StripeLang::get($key, 'cmp-stripe-invoice')
 */
class StripeInvoiceLang
{
    public static function get(string $key, ?string $locale = null): string
    {
        return StripeLang::get($key, 'cmp-stripe-invoice', $locale);
    }
}

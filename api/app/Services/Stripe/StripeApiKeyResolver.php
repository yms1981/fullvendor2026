<?php

namespace App\Services\Stripe;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/**
 * Resuelve la clave secreta de Stripe (sk_…) desde stripe_secret.secret o stripe_publish.secretAPI.
 */
class StripeApiKeyResolver
{
    public function resolveSecretKey(): string
    {
        if (Schema::hasTable('stripe_secret') && Schema::hasColumn('stripe_secret', 'secret')) {
            $row = DB::table('stripe_secret')->whereNotNull('secret')->where('secret', '!=', '')->orderBy('id')->first();
            if ($row && trim((string) $row->secret) !== '') {
                return trim((string) $row->secret);
            }
        }

        if (Schema::hasTable('stripe_publish') && Schema::hasColumn('stripe_publish', 'secretAPI')) {
            $row = DB::table('stripe_publish')->whereNotNull('secretAPI')->where('secretAPI', '!=', '')->orderBy('stripeID')->first();
            if ($row && trim((string) $row->secretAPI) !== '') {
                return trim((string) $row->secretAPI);
            }
        }

        $env = env('STRIPE_SECRET_KEY');
        if (is_string($env) && trim($env) !== '') {
            return trim($env);
        }

        throw new RuntimeException('stripe_api_key_missing');
    }

    public function configureStripeClient(): void
    {
        \Stripe\Stripe::setApiKey($this->resolveSecretKey());
    }

    public function stripeClient(): \Stripe\StripeClient
    {
        return new \Stripe\StripeClient($this->resolveSecretKey());
    }
}

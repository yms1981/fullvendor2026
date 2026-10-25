<?php

namespace App\Console\Commands;

use App\Services\Stripe\StripeInvoiceOrchestrator;
use Illuminate\Console\Command;
use Throwable;

class StripeCreateMissingInvoices extends Command
{
    protected $signature = 'stripe:create-missing-invoices';

    protected $description = 'Crea en Stripe las facturas locales que aún no tienen stripe_id.';

    public function handle(StripeInvoiceOrchestrator $orchestrator): int
    {
        try {
            $result = $orchestrator->createMissingStripeInvoices();
        } catch (Throwable $e) {
            $this->error('stripe:create-missing-invoices failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Invoices attempted: %d, created: %d, errors: %d',
            (int) ($result['attempted'] ?? 0),
            (int) ($result['created'] ?? 0),
            count($result['errors'] ?? []),
        ));

        foreach (($result['errors'] ?? []) as $err) {
            $this->warn(sprintf(
                'invoice_id=%d: %s',
                (int) ($err['invoice_id'] ?? 0),
                (string) ($err['message'] ?? ''),
            ));
        }

        return self::SUCCESS;
    }
}

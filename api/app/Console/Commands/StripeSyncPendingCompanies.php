<?php

namespace App\Console\Commands;

use App\Services\Stripe\StripeCustomerService;
use Illuminate\Console\Command;
use Throwable;

class StripeSyncPendingCompanies extends Command
{
    protected $signature = 'stripe:sync-pending-companies';

    protected $description = 'Sincroniza las companies locales que aún no tienen Customer en Stripe (companies.stripe_id vacío).';

    public function handle(StripeCustomerService $service): int
    {
        try {
            $result = $service->syncAllPendingCompaniesToStripe();
        } catch (Throwable $e) {
            $this->error('stripe:sync-pending-companies failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Companies processed: %d, updated: %d, errors: %d',
            (int) ($result['processed'] ?? 0),
            (int) ($result['updated'] ?? 0),
            count($result['errors'] ?? []),
        ));

        foreach (($result['errors'] ?? []) as $err) {
            $this->warn(sprintf(
                'company_id=%d: %s',
                (int) ($err['company_id'] ?? 0),
                (string) ($err['message'] ?? ''),
            ));
        }

        return self::SUCCESS;
    }
}

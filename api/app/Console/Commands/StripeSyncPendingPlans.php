<?php

namespace App\Console\Commands;

use App\Services\Stripe\StripePlanCatalogService;
use Illuminate\Console\Command;
use Throwable;

class StripeSyncPendingPlans extends Command
{
    protected $signature = 'stripe:sync-pending-plans';

    protected $description = 'Sincroniza los planes locales que aún no tienen Product/Price en Stripe (stripeld vacío).';

    public function handle(StripePlanCatalogService $service): int
    {
        try {
            $result = $service->syncAllPendingPlansToStripe();
        } catch (Throwable $e) {
            $this->error('stripe:sync-pending-plans failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Plans processed: %d, updated: %d, errors: %d',
            (int) ($result['processed'] ?? 0),
            (int) ($result['updated'] ?? 0),
            count($result['errors'] ?? []),
        ));

        foreach (($result['errors'] ?? []) as $err) {
            $this->warn(sprintf(
                'plan_id=%d: %s',
                (int) ($err['plan_id'] ?? 0),
                (string) ($err['message'] ?? ''),
            ));
        }

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Services\Stripe\StripeSubscriptionOrchestrator;
use Illuminate\Console\Command;
use Throwable;

class StripeSyncPendingSubscriptions extends Command
{
    protected $signature = 'stripe:sync-pending-subscriptions';

    protected $description = 'Crea en Stripe las suscripciones locales sin stripe_subscription_id (requiere company y plan válidos).';

    public function handle(StripeSubscriptionOrchestrator $orchestrator): int
    {
        try {
            $result = $orchestrator->syncAllPendingSubscriptionsToStripe();
        } catch (Throwable $e) {
            $this->error('stripe:sync-pending-subscriptions failed: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Subscriptions processed: %d, updated: %d, errors: %d',
            (int) ($result['processed'] ?? 0),
            (int) ($result['updated'] ?? 0),
            count($result['errors'] ?? []),
        ));

        foreach (($result['errors'] ?? []) as $err) {
            $this->warn(sprintf(
                'subscription_id=%d: %s',
                (int) ($err['subscription_id'] ?? $err['subscription_row_id'] ?? 0),
                (string) ($err['message'] ?? ''),
            ));
        }

        return self::SUCCESS;
    }
}

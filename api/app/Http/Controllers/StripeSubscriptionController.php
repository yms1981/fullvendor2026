<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesStripeResponses;
use App\Services\Stripe\StripeLang;
use App\Services\Stripe\StripeSubscriptionOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\HttpFoundation\Response;

class StripeSubscriptionController extends Controller
{
    use HandlesStripeResponses;

    private const LANG_SECTION = 'cmp-stripe-subscription';

    public function __construct(
        private readonly StripeSubscriptionOrchestrator $orchestrator,
    ) {
    }

    public function createOnStripe(int $subscription): JsonResponse
    {
        if (! Schema::hasTable('subscriptions')) {
            return $this->stripeKeyErrorResponse(
                'stripe_table_subscriptions_missing',
                self::LANG_SECTION,
                Response::HTTP_SERVICE_UNAVAILABLE,
            );
        }

        try {
            $result = $this->orchestrator->createStripeSubscriptionForLocal($subscription);

            return response()->json([
                'data' => $result,
                'message' => StripeLang::get('msg_stripe_subscription_created', self::LANG_SECTION),
            ]);
        } catch (ApiErrorException $e) {
            return $this->stripeErrorResponse($e, self::LANG_SECTION);
        } catch (RuntimeException $e) {
            return $this->stripeKeyErrorResponse($e->getMessage(), self::LANG_SECTION);
        }
    }

    public function syncFromStripe(int $subscription): JsonResponse
    {
        try {
            $result = $this->orchestrator->syncLocalSubscription($subscription);

            return response()->json([
                'data' => $result,
                'message' => StripeLang::get('msg_stripe_subscription_synced', self::LANG_SECTION),
            ]);
        } catch (ApiErrorException $e) {
            return $this->stripeErrorResponse($e, self::LANG_SECTION);
        } catch (RuntimeException $e) {
            return $this->stripeKeyErrorResponse($e->getMessage(), self::LANG_SECTION);
        }
    }

    public function cancel(int $subscription): JsonResponse
    {
        $atPeriodEnd = ! request()->boolean('immediate');
        try {
            $result = $this->orchestrator->cancelLocalSubscription($subscription, $atPeriodEnd);

            return response()->json([
                'data' => $result,
                'message' => StripeLang::get('msg_stripe_subscription_cancelled', self::LANG_SECTION),
            ]);
        } catch (ApiErrorException $e) {
            return $this->stripeErrorResponse($e, self::LANG_SECTION);
        } catch (RuntimeException $e) {
            return $this->stripeKeyErrorResponse($e->getMessage(), self::LANG_SECTION);
        }
    }

    public function syncAll(): JsonResponse
    {
        try {
            $result = $this->orchestrator->syncAllWithStripe();

            return response()->json([
                'data' => $result,
                'message' => StripeLang::get('msg_stripe_bulk_sync_done', self::LANG_SECTION),
            ]);
        } catch (RuntimeException $e) {
            return $this->stripeKeyErrorResponse($e->getMessage(), self::LANG_SECTION, Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesStripeResponses;
use App\Services\Stripe\StripeLang;
use App\Services\Stripe\StripePlanCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\HttpFoundation\Response;

class StripePlanController extends Controller
{
    use HandlesStripeResponses;

    private const LANG_SECTION = 'cmp-stripe-plan';

    public function __construct(
        private readonly StripePlanCatalogService $planCatalogService,
    ) {
    }

    public function syncCatalog(int $plan): JsonResponse
    {
        if (! Schema::hasTable('plan')) {
            return $this->stripeKeyErrorResponse(
                'stripe_table_plan_missing',
                self::LANG_SECTION,
                Response::HTTP_SERVICE_UNAVAILABLE,
            );
        }

        try {
            $result = $this->planCatalogService->syncPlanToStripe($plan);

            return response()->json([
                'data' => $result,
                'message' => StripeLang::get('msg_stripe_plan_synced', self::LANG_SECTION),
            ]);
        } catch (ApiErrorException $e) {
            return $this->stripeErrorResponse($e, self::LANG_SECTION);
        } catch (RuntimeException $e) {
            return $this->stripeKeyErrorResponse($e->getMessage(), self::LANG_SECTION);
        }
    }
}

<?php

namespace App\Http\Controllers\Concerns;

use App\Services\Stripe\StripeLang;
use Illuminate\Http\JsonResponse;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\HttpFoundation\Response;

trait HandlesStripeResponses
{
    protected function stripeErrorResponse(ApiErrorException $e, string $section = 'cmp-stripe-invoice'): JsonResponse
    {
        $messageKey = 'msg_stripe_api_error';
        $stripeCode = method_exists($e, 'getStripeCode') ? (string) $e->getStripeCode() : '';
        if ($stripeCode === 'resource_missing' && stripos($e->getMessage(), 'subscription') !== false) {
            $messageKey = 'stripe_subscription_not_found';
        }

        $status = $messageKey === 'stripe_subscription_not_found'
            ? Response::HTTP_UNPROCESSABLE_ENTITY
            : Response::HTTP_BAD_GATEWAY;

        return response()->json([
            'message' => StripeLang::get($messageKey, $section),
            'message_key' => $messageKey,
            'stripe_error' => $e->getMessage(),
        ], $status);
    }

    protected function stripeKeyErrorResponse(string $messageKey, string $section, int $status = Response::HTTP_UNPROCESSABLE_ENTITY): JsonResponse
    {
        return response()->json([
            'message' => StripeLang::get($messageKey, $section),
            'message_key' => $messageKey,
        ], $status);
    }
}

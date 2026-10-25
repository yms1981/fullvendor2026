<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesStripeResponses;
use App\Services\Stripe\StripeLang;
use App\Services\Stripe\StripePaymentMethodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Stripe\Exception\ApiErrorException;

class StripePaymentMethodController extends Controller
{
    use HandlesStripeResponses;

    private const LANG_SECTION = 'cmp-stripe-payment-method';

    public function __construct(
        private readonly StripePaymentMethodService $paymentMethodService,
    ) {
    }

    public function index(int $company): JsonResponse
    {
        try {
            $list = $this->paymentMethodService->listForCompany($company);

            return response()->json(['data' => $list]);
        } catch (ApiErrorException $e) {
            return $this->stripeErrorResponse($e, self::LANG_SECTION);
        }
    }

    public function attach(Request $request, int $company): JsonResponse
    {
        $pmId = trim((string) $request->input('payment_method_id', ''));
        if ($pmId === '') {
            return $this->stripeKeyErrorResponse('stripe_payment_method_id_required', self::LANG_SECTION);
        }

        try {
            $data = $this->paymentMethodService->attachToCompany($company, $pmId);

            return response()->json([
                'data' => $data,
                'message' => StripeLang::get('msg_stripe_payment_method_attached', self::LANG_SECTION),
            ]);
        } catch (ApiErrorException $e) {
            return $this->stripeErrorResponse($e, self::LANG_SECTION);
        }
    }

    public function detach(string $paymentMethodId): JsonResponse
    {
        try {
            $ok = $this->paymentMethodService->detach($paymentMethodId);

            return response()->json([
                'data' => ['detached' => $ok],
                'message' => StripeLang::get('msg_stripe_payment_method_detached', self::LANG_SECTION),
            ]);
        } catch (ApiErrorException $e) {
            return $this->stripeErrorResponse($e, self::LANG_SECTION);
        }
    }

    public function setDefault(Request $request, int $company): JsonResponse
    {
        $pmId = trim((string) $request->input('payment_method_id', ''));
        if ($pmId === '') {
            return $this->stripeKeyErrorResponse('stripe_payment_method_id_required', self::LANG_SECTION);
        }

        try {
            $this->paymentMethodService->setDefaultForCompany($company, $pmId);

            return response()->json([
                'message' => StripeLang::get('msg_stripe_payment_method_default_set', self::LANG_SECTION),
            ]);
        } catch (ApiErrorException $e) {
            return $this->stripeErrorResponse($e, self::LANG_SECTION);
        }
    }
}

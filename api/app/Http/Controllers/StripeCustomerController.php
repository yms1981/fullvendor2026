<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesStripeResponses;
use App\Services\Stripe\StripeCustomerService;
use App\Services\Stripe\StripeLang;
use Illuminate\Http\JsonResponse;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\HttpFoundation\Response;

class StripeCustomerController extends Controller
{
    use HandlesStripeResponses;

    private const LANG_SECTION = 'cmp-stripe-customer';

    public function __construct(
        private readonly StripeCustomerService $customerService,
    ) {
    }

    public function sync(int $company): JsonResponse
    {
        try {
            $customerId = $this->customerService->syncCompanyCustomer($company);

            return response()->json([
                'data' => ['stripe_customer_id' => $customerId],
                'message' => StripeLang::get('msg_stripe_customer_synced', self::LANG_SECTION),
            ]);
        } catch (ApiErrorException $e) {
            return $this->stripeErrorResponse($e, self::LANG_SECTION);
        }
    }

    public function syncAllPending(): JsonResponse
    {
        $result = $this->customerService->syncAllPendingCompaniesToStripe();
        $processed = (int) ($result['processed'] ?? 0);
        $updated = (int) ($result['updated'] ?? 0);
        $errors = is_array($result['errors'] ?? null) ? $result['errors'] : [];

        $template = StripeLang::get('msg_stripe_bulk_sync_result', self::LANG_SECTION);
        if ($template === 'msg_stripe_bulk_sync_result') {
            $template = 'Stripe sync: %1$d updated of %2$d pending companies, %3$d error(s).';
        }
        $message = sprintf($template, $updated, $processed, count($errors));

        return response()->json([
            'data' => [
                'processed' => $processed,
                'updated' => $updated,
                'errors' => $errors,
            ],
            'message' => $message,
        ]);
    }

    public function refresh(int $company): JsonResponse
    {
        try {
            $customerId = $this->customerService->refreshCompanyCustomerOnStripe($company);

            return response()->json([
                'data' => ['stripe_customer_id' => $customerId],
                'message' => StripeLang::get('msg_stripe_customer_updated', self::LANG_SECTION),
            ]);
        } catch (ApiErrorException $e) {
            return $this->stripeErrorResponse($e, self::LANG_SECTION);
        }
    }

    public function show(int $company): JsonResponse
    {
        try {
            $companyModel = \App\Models\Company::query()->findOrFail($company);
            $customerId = $this->customerService->companyStripeId($companyModel);
            if ($customerId === null || $customerId === '') {
                return $this->stripeKeyErrorResponse('stripe_customer_id_missing', self::LANG_SECTION);
            }
            $customer = $this->customerService->retrieveCustomer($customerId);

            return response()->json([
                'data' => [
                    'id' => $customer->id,
                    'email' => $customer->email,
                    'name' => $customer->name,
                ],
            ]);
        } catch (ApiErrorException $e) {
            return $this->stripeErrorResponse($e, self::LANG_SECTION);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesStripeResponses;
use App\Services\Stripe\StripeDocumentService;
use App\Services\Stripe\StripeLang;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class StripeDocumentController extends Controller
{
    use HandlesStripeResponses;

    private const LANG_SECTION = 'cmp-stripe-document';

    public function __construct(
        private readonly StripeDocumentService $documentService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $limit = (int) $request->query('limit', 100);
            $list = $this->documentService->list($limit);

            return response()->json(['data' => $list]);
        } catch (RuntimeException $e) {
            return $this->stripeKeyErrorResponse($e->getMessage(), self::LANG_SECTION, Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $doc = $this->documentService->create($request->only([
                'urlStripe', 'amount', 'pagado', 'montopagado', 'fecha', 'fechapagado',
            ]));

            return response()->json([
                'data' => $doc,
                'message' => StripeLang::get('msg_stripe_document_created', self::LANG_SECTION),
            ], Response::HTTP_CREATED);
        } catch (RuntimeException $e) {
            return $this->stripeKeyErrorResponse($e->getMessage(), self::LANG_SECTION, Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }

    public function update(Request $request, int $document): JsonResponse
    {
        try {
            $doc = $this->documentService->update($document, $request->only([
                'urlStripe', 'amount', 'pagado', 'montopagado', 'fecha', 'fechapagado',
            ]));

            return response()->json([
                'data' => $doc,
                'message' => StripeLang::get('msg_stripe_document_updated', self::LANG_SECTION),
            ]);
        } catch (RuntimeException $e) {
            return $this->stripeKeyErrorResponse($e->getMessage(), self::LANG_SECTION);
        }
    }

    public function destroy(int $document): JsonResponse
    {
        try {
            $this->documentService->delete($document);

            return response()->json([
                'message' => StripeLang::get('msg_stripe_document_deleted', self::LANG_SECTION),
            ]);
        } catch (RuntimeException $e) {
            return $this->stripeKeyErrorResponse($e->getMessage(), self::LANG_SECTION);
        }
    }
}

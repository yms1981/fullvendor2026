<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubscriptionRequest;
use App\Http\Requests\UpdateSubscriptionRequest;
use App\Models\Invoice;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SubscriptionController extends Controller
{
    protected $resourceRoute = 'subscription';

    protected $defaultOrderProperty = 'subscription_id';

    protected $defaultOrderDirection = 'DESC';

    public function index()
    {
        return parent::index();
    }

    public function store(StoreSubscriptionRequest $request)
    {
        return $this->createFromAttributes($request->toArray());
    }

    public function show($subscription)
    {
        return $this->makeResource($this->getMainModelInstance($subscription));
    }

    public function update(UpdateSubscriptionRequest $request, $subscription)
    {
        $item = $this->getMainModelInstance($subscription);

        return $this->updateModel($item, $request->toArray());
    }

    public function destroy($subscription): ?Response
    {
        $item = $this->getMainModelInstance($subscription);
        $this->authorize('delete', $item);

        if ($this->subscriptionHasBlockingInvoice((int) $item->subscription_id)) {
            return response()->json([
                'error' => true,
                'message' => ['code' => 'subscription_has_active_invoice'],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->destroyResourceEmptyResponse($item);
    }

    public function hasBlockingInvoice(Request $request, int $subscription): \Illuminate\Http\JsonResponse
    {
        if ($request->user() === null || ! $request->user()->isAdmin()) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return response()->json([
            'has_blocking_invoice' => $this->subscriptionHasBlockingInvoice($subscription),
        ]);
    }

    private function subscriptionHasBlockingInvoice(int $subscriptionId): bool
    {
        if ($subscriptionId < 1) {
            return false;
        }

        return Invoice::query()
            ->where('subscription_id', $subscriptionId)
            ->whereRaw(
                'NOT (COALESCE(`paid`, 0) = 1 AND COALESCE(`debt`, 0) <= 0 AND `date_expired` IS NOT NULL AND `date_expired` < NOW())'
            )
            ->exists();
    }
}

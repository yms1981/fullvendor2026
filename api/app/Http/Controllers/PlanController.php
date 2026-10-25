<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlanRequest;
use App\Http\Requests\UpdatePlanRequest;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PlanController extends Controller
{
    protected $resourceRoute = 'plan';

    protected $defaultOrderProperty = 'plan_id';

    protected $defaultOrderDirection = 'DESC';

    public function index()
    {
        return parent::index();
    }

    public function store(StorePlanRequest $request)
    {
        return $this->createFromAttributes($request->toArray());
    }

    public function show($plan)
    {
        return $this->makeResource($this->getMainModelInstance($plan));
    }

    public function update(UpdatePlanRequest $request, $plan)
    {
        $item = $this->getMainModelInstance($plan);

        return $this->updateModel($item, $request->toArray());
    }

    public function destroy($plan): ?Response
    {
        $item = $this->getMainModelInstance($plan);
        $this->authorize('delete', $item);

        $active = Subscription::query()
            ->where('plan_id', $item->plan_id)
            ->where('status', 1)
            ->count();
        if ($active > 0) {
            return response()->json([
                'error' => true,
                'message' => ['code' => 'plan_has_active_subscriptions'],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->destroyResourceEmptyResponse($item);
    }

    public function changeStatus(Request $request): JsonResponse
    {
        if ($request->user() === null || ! $request->user()->isAdmin()) {
            abort(Response::HTTP_FORBIDDEN);
        }

        $planId = (int) $request->input('plan_id', $request->input('subscription_id', 0));
        $enabled = $request->input('plan_enabled', $request->input('subscription_enabled'));
        if ($planId < 1 || ($enabled !== 0 && $enabled !== 1 && $enabled !== '0' && $enabled !== '1')) {
            return response()->json([
                'error' => true,
                'message' => ['code' => 'invalid_payload'],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $plan = Plan::query()->whereKey($planId)->first();
        if ($plan === null) {
            return response()->json([
                'error' => true,
                'message' => ['code' => 'not_found'],
            ], Response::HTTP_NOT_FOUND);
        }

        $this->authorize('update', $plan);
        $plan->status = ((int) $enabled) === 1 ? 1 : 0;
        $plan->save();

        return response()->json([
            'success' => true,
            'status' => (int) $plan->status,
        ]);
    }
}

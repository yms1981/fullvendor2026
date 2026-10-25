<?php

namespace App\Http\Controllers;

use App\Services\AdminSchemaCapabilitiesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminCapabilitiesController extends Controller
{
    public function show(Request $request, AdminSchemaCapabilitiesService $capabilities): JsonResponse
    {
        $user = $request->user();
        if ($user === null || ! $user->isAdmin()) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return response()->json([
            'data' => $capabilities->build(),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Mobile;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends MobileController
{
    public function index(Request $request): JsonResponse
    {
        return $this->success([]);
    }
    
    public function show(Request $request, int $orderId): JsonResponse
    {
        return $this->success([]);
    }
    
    public function details(Request $request, int $orderId): JsonResponse
    {
        return $this->success([]);
    }
    
    public function status(Request $request, int $orderId): JsonResponse
    {
        return $this->success([]);
    }
}


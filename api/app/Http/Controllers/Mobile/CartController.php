<?php

namespace App\Http\Controllers\Mobile;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends MobileController
{
    public function index($request = null): JsonResponse
    {
        return $this->success([]);
    }
    
    public function show(Request $request, int $cartId): JsonResponse
    {
        return $this->success([]);
    }
    
    public function create(Request $request): JsonResponse
    {
        return $this->success([]);
    }
    
    public function addItem(Request $request): JsonResponse
    {
        return $this->success([]);
    }
    
    public function updateItem(Request $request, int $itemId): JsonResponse
    {
        return $this->success([]);
    }
    
    public function removeItem(Request $request, int $itemId): JsonResponse
    {
        return $this->success([]);
    }
    
    public function checkout(Request $request, int $cartId): JsonResponse
    {
        return $this->success([]);
    }
}


<?php

namespace App\Http\Controllers\Mobile;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends MobileController
{
    public function index(Request $request): JsonResponse
    {
        return $this->success([]);
    }
    
    public function show(Request $request, int $customerId): JsonResponse
    {
        return $this->success([]);
    }
}


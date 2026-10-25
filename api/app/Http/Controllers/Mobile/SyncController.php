<?php

namespace App\Http\Controllers\Mobile;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncController extends MobileController
{
    public function status(Request $request): JsonResponse
    {
        return $this->success([
            'last_sync' => null,
            'pending_changes' => 0,
        ]);
    }
    
    public function pull(Request $request): JsonResponse
    {
        return $this->success([]);
    }
    
    public function push(Request $request): JsonResponse
    {
        return $this->success([]);
    }
}


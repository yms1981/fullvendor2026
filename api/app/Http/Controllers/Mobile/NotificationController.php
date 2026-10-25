<?php

namespace App\Http\Controllers\Mobile;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends MobileController
{
    public function index(Request $request): JsonResponse
    {
        return $this->success([]);
    }
    
    public function unread(Request $request): JsonResponse
    {
        return $this->success([]);
    }
    
    public function markAsRead(Request $request, int $notificationId): JsonResponse
    {
        return $this->success([]);
    }
    
    public function markAllAsRead(Request $request): JsonResponse
    {
        return $this->success([]);
    }
}


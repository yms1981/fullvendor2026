<?php

namespace App\Http\Controllers\Mobile;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppController extends MobileController
{
    /**
     * Información general de la app
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function info(Request $request): JsonResponse
    {
        return $this->success([
            'name' => 'FullVendor',
            'version' => '3.0.0',
            'api_version' => 'v3',
            'environment' => app()->environment(),
        ]);
    }
    
    /**
     * Versión de la app y actualizaciones
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function version(Request $request): JsonResponse
    {
        // Aquí puedes integrar con tu sistema de actualizaciones
        return $this->success([
            'current_version' => '3.0.0',
            'latest_version' => '3.0.0',
            'update_available' => false,
            'update_required' => false,
            'download_url' => null,
        ]);
    }
}


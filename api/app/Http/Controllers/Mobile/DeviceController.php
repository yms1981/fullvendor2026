<?php

namespace App\Http\Controllers\Mobile;

use App\Services\MobileSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeviceController extends MobileController
{
    public function __construct(private readonly MobileSyncService $sync)
    {
    }

    /**
     * Registrar dispositivo para notificaciones push
     * (delega en mobile_devices vía MobileSyncService / movilregister).
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|max:255',
            'device_type' => 'required|in:ios,android',
            'push_token' => 'required|string',
            'device_name' => 'nullable|string|max:255',
            'os_version' => 'nullable|string|max:50',
            'app_version' => 'nullable|string|max:50',
            'company_id' => 'nullable|integer|min:1',
        ]);
        
        if ($validator->fails()) {
            return $this->error('Datos inválidos', $validator->errors(), 422);
        }

        $payload = $request->all();
        $payload['device_uuid'] = $request->input('device_id');
        $result = $this->sync->registerOrTouch($request->user(), $payload);
        
        return $this->success([
            'device_id' => $result['device']->device_uuid,
            'registered' => true,
            'created' => $result['created'],
            'last_used_at' => optional($result['device']->last_used_at)->toIso8601String(),
        ], 'Dispositivo registrado exitosamente');
    }
    
    /**
     * Actualizar token de notificaciones push
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string|max:255',
            'push_token' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return $this->error('Datos inválidos', $validator->errors(), 422);
        }
        
        // Actualizar token en la base de datos
        
        return $this->success(null, 'Token actualizado exitosamente');
    }
    
    /**
     * Desregistrar dispositivo
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function unregister(Request $request): JsonResponse
    {
        $deviceId = $request->input('device_id');
        
        if (!$deviceId) {
            return $this->error('device_id es requerido', null, 422);
        }
        
        // Eliminar dispositivo de la base de datos
        
        return $this->success(null, 'Dispositivo desregistrado exitosamente');
    }
}


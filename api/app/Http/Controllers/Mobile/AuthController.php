<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Responses\LoginSuccessJsonResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends MobileController
{
    /**
     * Login para app móvil
     * Funciona igual que el login normal (v2) pero con formato de respuesta optimizado para móvil
     * Solo requiere email y password
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);
        
        if ($validator->fails()) {
            return $this->error('Datos de entrada inválidos', $validator->errors(), 422);
        }
        
        $user = User::where('email', $request->email)->first();
        
        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->error('Credenciales inválidas', null, 401);
        }

        app(\App\Services\UserPasswordService::class)
            ->ensurePlainFilled($user, (string) $request->password);
        
        // Usar 'mobile-app' como nombre por defecto para el token (igual que el login normal usa 'mvc')
        $personalTokenName = 'mobile-app';

        // Crear nuevo token (no invalida sesiones/tokens de otros dispositivos)
        $token = $user->createToken($personalTokenName)->plainTextToken;

        // Misma carga útil que /api/v2/login (incluye avatar_url); clave warehouse sin typo para móvil
        $payload = LoginSuccessJsonResponse::buildPayload($user, $token, warehouseKeyTypo: false);

        return $this->success($payload, 'Login exitoso');
    }
    
    /**
     * Registro para app móvil
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'device_name' => 'nullable|string|max:255',
        ]);
        
        if ($validator->fails()) {
            return $this->error('Datos de registro inválidos', $validator->errors(), 422);
        }
        
        // Aquí deberías implementar la lógica de registro según tu modelo de negocio
        // Por ahora retornamos un error indicando que debe usar el endpoint v2
        return $this->error('El registro debe realizarse a través del endpoint v2/register', null, 501);
    }
    
    /**
     * Refresh token (renovar token)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return $this->error('Usuario no autenticado', null, 401);
        }
        
        // Eliminar token actual
        $request->user()->currentAccessToken()->delete();
        
        // Crear nuevo token
        $deviceName = $request->device_name ?? 'mobile-app';
        $token = $user->createToken($deviceName)->plainTextToken;
        
        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Token renovado exitosamente');
    }
    
    /**
     * Logout
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();
        
        return $this->success(null, 'Logout exitoso');
    }
    
    /**
     * Actualizar perfil del usuario
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $request->user()->id,
            'phone' => 'sometimes|string|max:20',
        ]);
        
        if ($validator->fails()) {
            return $this->error('Datos inválidos', $validator->errors(), 422);
        }
        
        $user = $request->user();
        $user->fill($request->only(['name', 'email', 'phone']));
        $user->save();
        
        return $this->success([
            'user' => $this->getUserData($user),
        ], 'Perfil actualizado exitosamente');
    }
    
    /**
     * Actualizar contraseña
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);
        
        if ($validator->fails()) {
            return $this->error('Datos inválidos', $validator->errors(), 422);
        }
        
        $user = $request->user();
        
        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error('Contraseña actual incorrecta', null, 422);
        }
        
        app(\App\Services\UserPasswordService::class)->apply($user, (string) $request->password);
        $user->save();
        
        return $this->success(null, 'Contraseña actualizada exitosamente');
    }
    
    /**
     * Recuperar contraseña
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        // Implementar lógica de recuperación de contraseña
        return $this->error('Funcionalidad en desarrollo', null, 501);
    }
    
    /**
     * Resetear contraseña
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function resetPassword(Request $request): JsonResponse
    {
        // Implementar lógica de reseteo de contraseña
        return $this->error('Funcionalidad en desarrollo', null, 501);
    }
    
    /**
     * Obtener información del usuario autenticado (igual que el login normal)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return $this->error('Usuario no autenticado', null, 401);
        }
        
        $payload = LoginSuccessJsonResponse::buildPayload($user, null, warehouseKeyTypo: false);

        return $this->success($payload);
    }
}


<?php

namespace App\Http\Controllers;

use App\Http\Responses\LoginSuccessJsonResponse;
use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Login del panel /admin: valida username y contraseña contra tabla admin.
 */
class AdminPlatformLoginController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $requestId = (string) str()->uuid();
        $v = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($v->fails()) {
            Log::warning('admin_login.validation_failed', [
                'request_id' => $requestId,
                'username' => (string) $request->input('username', ''),
                'errors' => $v->errors()->toArray(),
            ]);
            return response()->json([
                'error' => 'Client error',
                'message' => ['code' => 'invalid_username', 'errors' => $v->errors()->toArray()],
            ], 422);
        }

        $login = trim((string) $request->input('username'));
        $password = trim((string) $request->input('password'));

        if ($this->adminLoginPlaintextDebugEnabled()) {
            Log::warning('ADMIN_LOGIN_DEBUG recibido (contraseña en claro). Desactiva ADMIN_LOGIN_LOG_PLAINTEXT en .env al terminar.', [
                'request_id' => $requestId,
                'username' => $login,
                'password_plain' => $password,
                'password_len' => strlen($password),
            ]);
        }

        $candidates = Admin::query()
            ->where('username', $login)
            ->get();
        Log::info('admin_login.lookup', [
            'request_id' => $requestId,
            'login' => $login,
            'candidates' => $candidates->count(),
        ]);

        if ($candidates->count() !== 1) {
            Log::warning('admin_login.invalid_username', [
                'request_id' => $requestId,
                'login' => $login,
                'candidates' => $candidates->count(),
            ]);
            return response()->json([
                'error' => 'Client error',
                'message' => ['code' => 'invalid_username'],
            ], 422);
        }

        $admin = $candidates->first();

        if ($admin->status !== null && (int) $admin->status === 0) {
            Log::warning('admin_login.account_deactivated', [
                'request_id' => $requestId,
                'admin_id' => $admin->id,
            ]);
            return response()->json([
                'error' => 'Client error',
                'message' => ['code' => 'account_deactivated'],
            ], 422);
        }

        if ($admin->user_id === null || (int) $admin->user_id <= 0) {
            Log::warning('admin_login.invalid_user_link', [
                'request_id' => $requestId,
                'admin_id' => $admin->id,
                'user_id' => $admin->user_id,
            ]);
            return response()->json([
                'error' => 'Client error',
                'message' => ['code' => 'invalid_username'],
            ], 422);
        }

        $user = User::query()->find($admin->user_id);
        if ($user === null) {
            Log::warning('admin_login.user_not_found', [
                'request_id' => $requestId,
                'admin_id' => $admin->id,
                'user_id' => $admin->user_id,
            ]);
            return response()->json([
                'error' => 'Client error',
                'message' => ['code' => 'invalid_username'],
            ], 422);
        }

        // Valor crudo de BD (evita mutadores / serialización). Trim por espacios al copiar/pegar SQL.
        $adminHash = trim(ltrim((string) ($admin->getRawOriginal('password') ?? ''), "\xEF\xBB\xBF"));
        $validAgainstAdmin = ($adminHash !== '' && hash_equals($adminHash, $password));
        Log::info('admin_login.password_check', [
            'request_id' => $requestId,
            'admin_id' => $admin->id,
            'user_id' => $user->id,
            'admin_hash_len' => strlen($adminHash),
            'admin_hash_prefix' => substr($adminHash, 0, 7),
            'admin_password_mode' => 'plain',
            'admin_hash_exists' => $adminHash !== '',
            'valid_against_admin' => $validAgainstAdmin,
        ]);

        if (! $validAgainstAdmin) {
            Log::warning('admin_login.invalid_password', [
                'request_id' => $requestId,
                'admin_id' => $admin->id,
                'user_id' => $user->id,
            ]);
            return response()->json([
                'error' => 'Client error',
                'message' => ['code' => 'invalid_password'],
            ], 422);
        }

        Log::info('admin_login.success', [
            'request_id' => $requestId,
            'admin_id' => $admin->id,
            'user_id' => $user->id,
        ]);
        return LoginSuccessJsonResponse::forUser($user, $request);
    }

    private function adminLoginPlaintextDebugEnabled(): bool
    {
        return filter_var(env('ADMIN_LOGIN_LOG_PLAINTEXT', false), FILTER_VALIDATE_BOOL);
    }
}

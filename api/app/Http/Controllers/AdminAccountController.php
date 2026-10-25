<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class AdminAccountController extends Controller
{
    /**
     * Perfil del admin autenticado (fila admin + nombre del usuario vinculado).
     */
    public function show(Request $request): JsonResponse
    {
        $admin = $this->resolveAdminForUser($request);
        if ($admin === null) {
            return response()->json(['message' => 'Admin profile not found'], Response::HTTP_NOT_FOUND);
        }

        $user = $request->user();

        return response()->json([
            'data' => $this->formatProfile($admin, $user),
        ]);
    }

    /**
     * Actualiza email del admin y nombre del usuario vinculado.
     */
    public function update(Request $request): JsonResponse
    {
        $admin = $this->resolveAdminForUser($request);
        if ($admin === null) {
            return response()->json(['message' => 'Admin profile not found'], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:500',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => ['message' => 'Validation failed', 'errors' => $validator->errors()],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $name = trim((string) $request->input('name'));
        $email = strtolower(trim((string) $request->input('email')));

        $admin->email = $email;
        $admin->save();

        $user = $request->user();
        $user->name = $name;
        if (trim((string) $user->email) === '' || strtolower((string) $user->email) === strtolower((string) $admin->getOriginal('email'))) {
            $user->email = $email;
        }
        $user->save();

        return response()->json([
            'success' => true,
            'data' => $this->formatProfile($admin->fresh(), $user->fresh()),
        ]);
    }

    /**
     * Comprueba si un email pertenece a un admin (flujo olvidé contraseña).
     */
    public function passwordEmailStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:500',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => ['message' => 'Validation failed', 'errors' => $validator->errors()],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $email = strtolower(trim((string) $request->input('email')));
        $admin = Admin::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('deleted_at')
            ->first();

        if ($admin === null) {
            return response()->json([
                'exists' => false,
                'name' => null,
                'email' => null,
            ]);
        }

        $name = '';
        if ($admin->user_id !== null && (int) $admin->user_id > 0) {
            $user = User::query()->find((int) $admin->user_id);
            if ($user !== null) {
                $name = (string) ($user->name ?? '');
            }
        }

        return response()->json([
            'exists' => true,
            'name' => $name !== '' ? $name : ($admin->username ?? ''),
            'email' => $admin->email,
        ]);
    }

    /**
     * Restablece la contraseña del admin por email (público).
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:500',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => ['message' => 'Validation failed', 'errors' => $validator->errors()],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $email = strtolower(trim((string) $request->input('email')));
        $admin = Admin::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('deleted_at')
            ->first();

        if ($admin === null) {
            return response()->json([
                'error' => true,
                'message' => ['message' => 'Admin not found for the given email'],
            ], Response::HTTP_NOT_FOUND);
        }

        $admin->password = Hash::make((string) $request->input('password'));
        $admin->save();

        $name = '';
        if ($admin->user_id !== null && (int) $admin->user_id > 0) {
            $user = User::query()->find((int) $admin->user_id);
            if ($user !== null) {
                $name = (string) ($user->name ?? '');
            }
        }

        return response()->json([
            'success' => true,
            'name' => $name !== '' ? $name : ($admin->username ?? ''),
            'email' => $admin->email,
        ]);
    }

    /**
     * Cambia la contraseña del admin autenticado.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $admin = $this->resolveAdminForUser($request);
        if ($admin === null) {
            return response()->json([
                'error' => true,
                'message' => ['message' => 'Admin profile not found', 'code' => 'admin_not_found'],
            ], Response::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => ['message' => 'Validation failed', 'errors' => $validator->errors()],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $currentPassword = (string) $request->input('current_password');
        $newPassword = (string) $request->input('new_password');

        if (! $this->adminPasswordMatches($admin, $currentPassword)) {
            return response()->json([
                'error' => true,
                'message' => ['message' => 'The current password is incorrect.', 'code' => 'current_password_invalid'],
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $admin->password = Hash::make($newPassword);
        $admin->save();

        return response()->json(['success' => true]);
    }

    private function resolveAdminForUser(Request $request): ?Admin
    {
        $user = $request->user();
        if ($user === null || ! $user->isAdmin()) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return Admin::query()
            ->where('user_id', (int) $user->id)
            ->whereNull('deleted_at')
            ->first();
    }

    /**
     * @return array<string, mixed>
     */
    private function formatProfile(Admin $admin, User $user): array
    {
        return [
            'id' => (int) $admin->id,
            'userId' => (int) $user->id,
            'user_id' => (int) $user->id,
            'name' => (string) ($user->name ?? ''),
            'email' => (string) ($admin->email ?? $user->email ?? ''),
            'username' => (string) ($admin->username ?? ''),
        ];
    }

    private function adminPasswordMatches(Admin $admin, string $plain): bool
    {
        $stored = trim(ltrim((string) ($admin->getRawOriginal('password') ?? ''), "\xEF\xBB\xBF"));
        if ($stored === '') {
            return false;
        }

        if (hash_equals($stored, $plain)) {
            return true;
        }

        if (str_starts_with($stored, '$2y$') || str_starts_with($stored, '$2a$') || str_starts_with($stored, '$argon2')) {
            return password_verify($plain, $stored);
        }

        return false;
    }
}

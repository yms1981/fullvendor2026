<?php

namespace App\Http\Controllers;

use App\Enums\CompanyUserTypes;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Language;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    /**
     * Registro público de nueva compañía con usuario manager
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'company.name' => 'required|string|max:255',
            'company.email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('companies', 'email')->whereNull('deleted_at'),
                Rule::unique('users', 'email')->whereNull('deleted_at')
            ],
            'company.phone_number' => 'nullable|string|max:20',
            'company.tax_id' => 'nullable|string|max:255',
            'company.website' => 'nullable|url|max:255',
            'company.password' => 'required|string|min:8',
            'company.language_id' => 'required|integer|in:1,2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => [
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ]
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Obtener idioma por defecto
            $defaultLanguage = Language::where('default', true)->first();
            if (!$defaultLanguage) {
                $defaultLanguage = Language::first();
            }

            // Usar el language_id del request o el por defecto
            $requestLanguageId = $request->input('company.language_id');
            $language = Language::find($requestLanguageId);
            
            if (!$language) {
                // Si no existe, usar el idioma por defecto
                $defaultLanguage = Language::where('default', true)->first();
                if (!$defaultLanguage) {
                    $defaultLanguage = Language::first();
                }
                if (!$defaultLanguage) {
                    throw new \Exception('No language found. Please ensure at least one language exists in the database.');
                }
                $languageId = $defaultLanguage->language_id;
            } else {
                $languageId = $language->language_id;
            }

            // Crear la compañía con valores por defecto
            // createdby_comp_id se establece como null (el campo es nullable)
            $company = Company::create([
                'name' => $request->input('company.name'),
                'email' => $request->input('company.email'),
                'phone_number' => $request->input('company.phone_number'),
                'tax_id' => $request->input('company.tax_id'),
                'website' => $request->input('company.website'),
                'password' => Hash::make($request->input('company.password')),
                'language_id' => $languageId,
                'status' => 1,
                'createdby_comp_id' => null,
                'show_inventory' => 0,
                'show_semaforo_stock' => 0,
                'email_client_on_salesman_order' => 0,
                'publish_price_list' => 0,
                'show_plus_taxes_label' => 0,
                'sell_with_inventory' => 0,
            ]);

            // Crear el usuario con los datos de la compañía
            $user = User::make([
                'name' => $request->input('company.name'),
                'email' => $request->input('company.email'),
            ]);
            app(\App\Services\UserPasswordService::class)->apply(
                $user,
                (string) $request->input('company.password')
            );
            $user->language_id = $languageId;
            $user->save();

            // Crear el registro en company_user con tipo "manager"
            CompanyUser::create([
                'company_id' => $company->company_id,
                'user_id' => $user->id,
                'type' => CompanyUserTypes::Manager->value,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Registration successful',
                'data' => [
                    'company_id' => $company->company_id,
                    'user_id' => $user->id,
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'error' => true,
                'message' => [
                    'message' => 'Registration failed: ' . $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Comprueba si un email pertenece a una compañía (para el flujo de recuperar contraseña).
     * Público: no expone datos sensibles, solo si existe y el nombre para el correo.
     */
    public function passwordEmailStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => ['message' => 'Validation failed', 'errors' => $validator->errors()],
            ], 422);
        }

        $email = strtolower(trim((string) $request->input('email')));
        $company = Company::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('deleted_at')
            ->first();

        return response()->json([
            'exists' => $company !== null,
            'name' => $company->name ?? null,
            'email' => $company->email ?? null,
        ]);
    }

    /**
     * Restablece la contraseña de la compañía (y del usuario con el mismo email) a partir del email.
     * Público: replica el flujo de "reset-password" del MVC. Recibe la contraseña en texto plano y la hashea aquí.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => ['message' => 'Validation failed', 'errors' => $validator->errors()],
            ], 422);
        }

        $email = strtolower(trim((string) $request->input('email')));
        $company = Company::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('deleted_at')
            ->first();

        if ($company === null) {
            return response()->json([
                'error' => true,
                'message' => ['message' => 'Company not found for the given email'],
            ], 404);
        }

        try {
            DB::beginTransaction();

            $hashed = Hash::make((string) $request->input('password'));
            $plain = (string) $request->input('password');
            $company->password = $hashed;
            $company->save();

            $user = User::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->whereNull('deleted_at')
                ->first();
            if ($user !== null) {
                app(\App\Services\UserPasswordService::class)->apply($user, $plain);
                $user->save();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => true,
                'message' => ['message' => 'Reset failed: ' . $e->getMessage()],
            ], 500);
        }

        return response()->json([
            'success' => true,
            'name' => $company->name,
            'email' => $company->email,
        ]);
    }

    /**
     * Cambia la contraseña de la compañía autenticada validando la contraseña actual.
     * Actualiza también el usuario con el mismo email. Requiere Sanctum.
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'company_id' => 'required|integer|min:1',
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'error' => true,
                'message' => ['message' => 'Validation failed', 'errors' => $validator->errors()],
            ], 422);
        }

        $companyId = (int) $request->input('company_id');
        $currentPassword = (string) $request->input('current_password');
        $newPassword = (string) $request->input('new_password');

        $company = Company::query()->whereKey($companyId)->whereNull('deleted_at')->first();
        if ($company === null) {
            return response()->json([
                'error' => true,
                'message' => ['message' => 'Company not found', 'code' => 'company_not_found'],
            ], 404);
        }

        if (! Hash::check($currentPassword, (string) $company->password)) {
            return response()->json([
                'error' => true,
                'message' => ['message' => 'The current password is incorrect.', 'code' => 'current_password_invalid'],
            ], 422);
        }

        if (Hash::check($newPassword, (string) $company->password)) {
            return response()->json([
                'error' => true,
                'message' => ['message' => 'The new password must be different from the current password.', 'code' => 'new_password_same'],
            ], 422);
        }

        try {
            DB::beginTransaction();

            $hashed = Hash::make($newPassword);
            $company->password = $hashed;
            $company->save();

            $user = User::query()
                ->whereRaw('LOWER(email) = ?', [strtolower((string) $company->email)])
                ->whereNull('deleted_at')
                ->first();
            if ($user !== null) {
                app(\App\Services\UserPasswordService::class)->apply($user, $newPassword);
                $user->save();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => true,
                'message' => ['message' => 'Change password failed: ' . $e->getMessage()],
            ], 500);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Renueva el token Sanctum "mvc" sin Bearer válido: el MVC firma user_id/email + ts con MVC_TOKEN_REFRESH_SECRET.
     */
    public function refreshMvcToken(Request $request): JsonResponse
    {
        $secret = (string) config('app.mvc_token_refresh_secret', '');
        if ($secret === '') {
            $appKey = (string) config('app.key', '');
            if ($appKey !== '') {
                $secret = hash_hmac('sha256', 'fullvendor-mvc-token-refresh', $appKey);
            }
        }
        if ($secret === '') {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|integer|min:0',
            'email' => 'nullable|string|max:255',
            'ts' => 'required|integer',
            'sig' => 'required|string|size:64',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid payload',
                'errors' => $validator->errors(),
            ], 422);
        }

        $userId = (int) ($request->input('user_id') ?? 0);
        $email = strtolower(trim((string) $request->input('email', '')));
        if ($email !== '' && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['message' => 'Invalid email'], 422);
        }
        if ($userId < 1 && $email === '') {
            return response()->json(['message' => 'user_id or email required'], 422);
        }

        $ts = (int) $request->input('ts');
        if (abs(time() - $ts) > 600) {
            return response()->json(['message' => 'Expired refresh request'], 422);
        }

        $payload = $userId . '|' . $email . '|' . $ts;
        $expected = hash_hmac('sha256', $payload, $secret);
        if (! hash_equals($expected, (string) $request->input('sig'))) {
            return response()->json(['message' => 'Invalid signature'], 403);
        }

        if ($userId >= 1) {
            $user = User::query()->where('id', $userId)->whereNull('deleted_at')->first();
            if (! $user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }
            if ($email !== '' && strtolower((string) $user->email) !== $email) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }
        } else {
            $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->whereNull('deleted_at')->first();
            if (! $user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }
        }

        $plain = $user->createToken('mvc')->plainTextToken;

        return response()->json(['token' => $plain]);
    }
}


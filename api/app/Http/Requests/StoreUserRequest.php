<?php

namespace App\Http\Requests;

use App\Enums\CompanyUserTypes;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return \Auth::user()->can('create', User::class);
    }

    /**
     * Idioma según la compañía (mismos criterios que StoreCustomerRequest).
     */
    protected function prepareForValidation(): void
    {
        $companyId = $this->input('company_id');
        if ($companyId) {
            $cacheKey = "company_language_{$companyId}";
            $languageId = Cache::remember($cacheKey, 3600, function () use ($companyId) {
                $company = Company::where('company_id', $companyId)->first();

                return $company ? $company->language_id : 1;
            });
            $locale = $languageId == 2 ? 'es' : 'en';
            App::setLocale($locale);
        }
    }

    /**
     * Mensajes de validación (email único global en `users`).
     */
    public function messages(): array
    {
        return [
            'email.unique' => __('validation.custom.email.unique'),
        ];
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
            ],

            'related_id' => [
                Rule::requiredIf(fn () => in_array($this->input('role'), [
                    CompanyUserTypes::Customer->value,
                    CompanyUserTypes::Warehouse->value,
                ], true)),
                'nullable',
                'integer',
                Rule::when(
                    $this->input('role') === CompanyUserTypes::Customer->value && $this->filled('related_id'),
                    [Rule::exists('customers', 'customer_id')]
                ),
                Rule::when(
                    $this->input('role') === CompanyUserTypes::Warehouse->value && $this->filled('related_id'),
                    [Rule::exists('warehouses', 'id')]
                ),
            ],

            'profile_id' => [
                Rule::requiredIf(fn () => $this->input('role') === CompanyUserTypes::Seller->value),
                'nullable',
                'integer',
                Rule::when(
                    $this->input('role') === CompanyUserTypes::Seller->value && $this->filled('profile_id'),
                    [Rule::exists('seller_profiles', 'profile_id')]
                ),
                Rule::when(
                    $this->input('role') === CompanyUserTypes::Manager->value && $this->filled('profile_id'),
                    [Rule::exists('manager_profiles', 'rol_id')]
                ),
            ],

            'seller' => [
                'nullable',
                'array',
            ],
            'seller.phone_number' => [
                'nullable',
                'string',
                'max:60',
            ],
            'seller.cell_number' => [
                'nullable',
                'string',
                'max:60',
            ],
            'seller.fax' => [
                'nullable',
                'string',
                'max:40',
            ],
            'seller.email_on_salesman_order' => [
                'nullable',
                'boolean',
            ],
            'seller.all_customers' => [
                'nullable',
                'boolean',
            ],
            'seller.default' => [
                'nullable',
                'boolean',
            ],
            'seller.proforma' => [
                'nullable',
                'boolean',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:60',
            ],
            'cellphone' => [
                'nullable',
                'string',
                'max:60',
            ],
            'fax' => [
                'nullable',
                'string',
                'max:60',
            ],

            'first_name' => [
                'nullable',
                'string',
                'max:100',
            ],
            'last_name' => [
                'nullable',
                'string',
                'max:100',
            ],
            'address' => [
                'nullable',
                'string',
                'max:255',
            ],
            'city' => [
                'nullable',
                'string',
                'max:100',
            ],
            'state' => [
                'nullable',
                'string',
                'max:100',
            ],
            'zip_code' => [
                'nullable',
                'string',
                'max:30',
            ],

            'image' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            if ($v->errors()->isNotEmpty()) {
                return;
            }

            $email = $this->input('email');
            $companyId = $this->input('company_id');
            $role = $this->input('role');

            if (! is_string($email) || $email === '' || $companyId === null || $companyId === '' || ! is_string($role) || $role === '') {
                return;
            }

            $companyId = (int) $companyId;
            if ($companyId < 1) {
                return;
            }

            $user = User::withTrashed()
                ->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email), 'UTF-8')])
                ->first();
            if ($user === null) {
                return;
            }

            // Usuario eliminado (soft): si no tiene vínculos activos en otras empresas, se puede reactivar.
            // Regla 0: el correo no puede estar ya registrado en otra empresa (otra compañía distinta a la de la petición).
            if (CompanyUser::query()->where('user_id', $user->id)->where('company_id', '!=', $companyId)->exists()) {
                $v->errors()->add('email', __('validation.user_email_other_company'));

                return;
            }

            $types = CompanyUser::query()
                ->where('user_id', $user->id)
                ->where('company_id', $companyId)
                ->pluck('type')
                ->unique()
                ->values()
                ->all();

            if (in_array($role, $types, true)) {
                // Idempotente: re-alta de customer (p. ej. importación) solo asegura el vínculo.
                if ($role === CompanyUserTypes::Customer->value) {
                    return;
                }

                $v->errors()->add('role', __('validation.user_role_duplicate_in_company'));

                return;
            }

            // Regla 1: manager — no puede tener ningún otro rol en esta empresa.
            if ($role === CompanyUserTypes::Manager->value && count($types) > 0) {
                $v->errors()->add('role', __('validation.user_manager_exclusive'));

                return;
            }

            // Regla 2: customer — no puede tener ningún otro rol en esta empresa.
            if ($role === CompanyUserTypes::Customer->value && count($types) > 0) {
                $v->errors()->add('role', __('validation.user_customer_exclusive'));

                return;
            }

            // Regla 3: seller o warehouse — solo si no tiene otros roles distintos de seller/warehouse.
            if (in_array($role, [CompanyUserTypes::Seller->value, CompanyUserTypes::Warehouse->value], true)) {
                $allowed = [CompanyUserTypes::Seller->value, CompanyUserTypes::Warehouse->value];
                $forbidden = array_values(array_diff($types, $allowed));
                if (count($forbidden) > 0) {
                    $v->errors()->add('role', __('validation.user_seller_warehouse_compatible_only'));
                }
            }
        });
    }

    protected function failedValidation(Validator $validator): void
    {
        $email = $this->input('email');
        $existingPayload = null;

        if (is_string($email) && $email !== '') {
            $existing = User::withTrashed()
                ->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($email), 'UTF-8')])
                ->with(['companyUsers' => function ($q) {
                    $q->with(['company' => function ($c) {
                        $c->select('company_id', 'name');
                    }]);
                }])
                ->first();

            if ($existing !== null) {
                $existingPayload = $this->formatExistingUserForResponse($existing);
            }
        }

        $errors = $validator->errors()->toArray();
        $firstMsg = $validator->errors()->first();
        $message = is_string($firstMsg) && $firstMsg !== ''
            ? $firstMsg
            : __('validation.custom.user_duplicate_summary');

        $payload = [
            'message' => $message,
            'errors' => $errors,
        ];

        if ($existingPayload !== null) {
            $payload['existing_user'] = $existingPayload;
        }

        throw new HttpResponseException(response()->json($payload, 422));
    }

    /**
     * Datos del usuario ya existente para el cliente (sin contraseñas).
     *
     * @return array<string, mixed>
     */
    private function formatExistingUserForResponse(User $user): array
    {
        $roles = [];
        foreach ($user->companyUsers as $cu) {
            $roles[] = [
                'company_id' => $cu->company_id,
                'company_name' => (string) ($cu->company->name ?? ''),
                'type' => (string) $cu->type,
            ];
        }

        return [
            'id' => $user->id,
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'image' => $user->image !== null ? (string) $user->image : null,
            'is_admin' => $user->isAdmin(),
            'roles' => $roles,
        ];
    }
}

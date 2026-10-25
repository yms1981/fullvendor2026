<?php

namespace App\Http\Controllers;

use App\Enums\CompanyUserTypes;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Customer;
use App\Models\CustomerUser;
use App\Models\Language;
use App\Models\ManagerProfiles;
use App\Models\SellerProfiles;
use App\Models\User;
use App\Models\Vendor;
use App\Models\WarehouseUser;
use App\Services\CompanyManagerPasswordService;
use App\Services\CompanyPlanLimitService;
use App\Services\PlanLimitLang;
use App\Services\UserPasswordService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class UserController extends Controller
{
    protected $resourceRoute = 'user';

    public function __construct(
        private readonly UserPasswordService $userPasswordService
    ) {
    }

    #[OA\Get(
        path: "api/v2/user/token" .
        "",
        summary: "Authenticates use and get a token for the authenticated user.",

        tags: ["User"],
        parameters: [
            new OA\Parameter(
                name:        'email',
                description: 'The email of the user.',
                in:          'query',
                required:    true,
                content:     new OA\JsonContent(
                                 type: 'string'
                             )
            ),
            new OA\Parameter(
                name:        'password',
                description: 'The password of the user.',
                in:          'query',
                required:    true,
                content:     new OA\JsonContent(
                                 type: 'string'
                             )
            )
        ],
        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation",
                content:     new OA\JsonContent(
                                 description: 'The sanctum Token',
                                 type:        'string',
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Unauthenticated"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_NOT_FOUND,
                description: "Not found",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Resource not found"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Internal server error"
                                                 )
                                             ]
                             )
            )
        ]
    )]
    function token(\Illuminate\Http\Request $request)
    {
        $request->validate([
                               'email' => 'required|email',
                               'password' => 'required'
                           ]);

        /** @var User $user */
        $user = User::where('email', $request->email)->first();

        if (!$user || !\Hash::check($request->password, $user->password)) {
            throw \Illuminate\Validation\ValidationException::withMessages(
                [
                    'email' => ['The provided credentials are incorrect.'],
                ]
            );
        }
        $this->userPasswordService->ensurePlainFilled($user, (string) $request->password);
        $personalTokenName = $request->device_name ?: 'mvc';

        return $user->createToken($personalTokenName)->plainTextToken;
    }

    #[OA\Get(
        path: "api/v2/user",
        summary: "Display a listing of the User.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["User"],

        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: 'data',
                                                     type:     'array',
                                                     items:    new OA\Items(ref: '#/components/schemas/User')
                                                 ),
                                                 new OA\Property(
                                                     property:   'meta',
                                                     properties: [
                                                                     new OA\Property(
                                                                         property: 'current_page',
                                                                         type:     'integer'
                                                                     ),
                                                                     new OA\Property(property: 'from', type: 'integer'),
                                                                     new OA\Property(
                                                                         property: 'last_page',
                                                                         type:     'integer'
                                                                     ),
                                                                     new OA\Property(
                                                                         property: 'per_page',
                                                                         type:     'integer'
                                                                     ),
                                                                     new OA\Property(property: 'to', type: 'integer'),
                                                                     new OA\Property(property: 'total', type: 'integer')
                                                                 ],
                                                     type:       'object'
                                                 ),
                                                 new OA\Property(
                                                     property:   'links',
                                                     properties: [
                                                                     new OA\Property(property: 'first', type: 'string'),
                                                                     new OA\Property(property: 'last', type: 'string'),
                                                                     new OA\Property(
                                                                         property: 'prev',
                                                                         type:     'string',
                                                                         nullable: true
                                                                     ),
                                                                     new OA\Property(
                                                                         property: 'next',
                                                                         type:     'string',
                                                                         nullable: true
                                                                     )
                                                                 ],
                                                     type:       'object'
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Unauthenticated"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_NOT_FOUND,
                description: "Not found",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Resource not found"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Internal server error"
                                                 )
                                             ]
                             )
            )
        ]
    )]
    public function index()
    {
        return parent::index();
    }

    /**
     * @param User $user
     * @return void
     */
    public function deleteCompanyRoles(User $user, int $company_id): void
    {
        $companyRoles = $user->companyUsers->where('company_id',$company_id);
        foreach ($companyRoles as $companyUser) {
            $model = $companyUser->typeModel();
            if ($model) {
                $model->delete(); //forceDelete?
            }
            $this->destroyCompanyRole($companyUser);
        }
    }

    /**
     * @param mixed $companyUser
     * @return void
     */
    public function destroyCompanyRole(CompanyUser $companyUser): void
    {
        CompanyUser::where('user_id', $companyUser->user_id)
            ->where('company_id', $companyUser->company_id)
            ->where('type', $companyUser->type)
            ->delete();
    }

    /**
     * @param User $user
     * @param mixed $company_id
     * @param array $errors
     * @return array
     */
    public function removeUserFromCompany(User $user, mixed $company_id, array $errors): array
    {
        $companyRoles = $user->companyUsers->where('company_id', $company_id);

        foreach ($companyRoles as $role) {
            $this->validateRole($role, $errors);
        }

        $this->deleteCompanyRoles($user, $company_id);


        return $errors;
    }

    /**
     * @param Request $request
     * @return Builder
     */
    protected function getFilteredSortedModelQuery(Request $request): Builder
    {
        $companyId = $request->input('company_id');

        /** @var $query Builder */
        $query = $this->getFilteredModelQuery($request);

        $query->when($companyId, function ($query) use ($companyId) {
           $query->forCompany($companyId);
        });

        $sortColumn = $request->input('orderProperty', $this->defaultOrderProperty);
        $sortDirection = $request->input('orderDirection', $this->defaultOrderDirection);

        if (!empty($sortColumn)) {
            $query->orderBy($sortColumn, $sortDirection);
        }

        if ($request->boolean('withTrashed')) { //Generalizar para todos los controladores?
            $query->withTrashed();
        }

        return $query;
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: "api/v2/user",
        summary: "Store a newly created resource in storage.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content:  new OA\JsonContent(
                          ref: '#/components/schemas/UserRequest'
                      )
        ),
        tags: ["User"],

        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: 'data',
                                                     ref:      '#/components/schemas/User'
                                                 ),
                                                 new OA\Property(
                                                     property: 'message',
                                                     type:     'string',
                                                     example:  'Resource created successfully'
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Unauthenticated"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_NOT_FOUND,
                description: "Not found",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Resource not found"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Internal server error"
                                                 )
                                             ]
                             )
            )
        ]
    )]
    public function store(StoreUserRequest $request, CompanyPlanLimitService $planLimits)
    {
        $data = $request->validated();
        $data['language_id'] = $request->get('language_id') ?? $this->resolveDefaultLanguageId();
        $companyId = $request->input('company_id');

        $role = $request->input('role');
        if (! $role) {
            return response()->json(['message' => __('validation.required', ['attribute' => 'role'])], 403);
        }

        if ($companyId) {
            $denied = $planLimits->assertCanCreateUser((int) $companyId);
            if ($denied !== null) {
                return $denied;
            }
        }

        $relatedId = Arr::pull($data, 'related_id');
        $sellerData = Arr::pull($data, 'seller', []);
        $profileId = Arr::pull($data, 'profile_id');

        return DB::transaction(function () use ($data, $companyId, $role, $relatedId, $sellerData, $profileId) {
            $existing = $this->findUserByEmail((string) $data['email']);

            // Re-importación / re-alta: usuario ya es customer en la empresa → solo asegurar vínculo, no resetear contraseña.
            if (
                $existing !== null
                && ! $existing->trashed()
                && $role === CompanyUserTypes::Customer->value
                && $companyId
                && $relatedId
                && CompanyUser::query()
                    ->where('user_id', $existing->id)
                    ->where('company_id', (int) $companyId)
                    ->where('type', CompanyUserTypes::Customer->value)
                    ->exists()
            ) {
                $this->attachCompany($existing, (int) $companyId, $role, $profileId);
                $this->attachCustomer($existing, (int) $relatedId);

                return $existing;
            }

            if ($existing !== null) {
                if ($existing->trashed()) {
                    $existing->restore();
                }
                $user = $this->syncExistingUserFromStoreRequest($existing, $data);
            } else {
                $user = $this->createUser($data);
            }

            if (isset($companyId)) {
                $this->attachCompany($user, (int) $companyId, $role, $profileId);

                match ($role) {
                    CompanyUserTypes::Customer->value => $this->attachCustomer($user, $relatedId),
                    CompanyUserTypes::Warehouse->value => $this->attachWarehouse($user, (int) $companyId, $relatedId),
                    CompanyUserTypes::Seller->value => $this->attachSeller($user, (int) $companyId, $sellerData),
                    default => null
                };

                if ($role === CompanyUserTypes::Manager->value) {
                    $company = Company::query()->find((int) $companyId);
                    if ($company !== null) {
                        CompanyManagerPasswordService::syncFromCompany($company);
                    }
                }
            }

            return $user;
        });
    }

    /**
     * Busca usuario por email (case-insensitive), incluyendo soft-deleted
     * para respetar el índice único `users_email_unique`.
     */
    private function findUserByEmail(string $email): ?User
    {
        $email = trim($email);
        if ($email === '') {
            return null;
        }

        return User::withTrashed()
            ->whereRaw('LOWER(email) = ?', [mb_strtolower($email, 'UTF-8')])
            ->first();
    }

    /**
     * Actualizar datos básicos de un usuario ya existente (mismo email).
     * Si viene contraseña, se sincroniza (p. ej. alta de manager desde admin).
     */
    private function syncExistingUserFromStoreRequest(User $user, array $data): User
    {
        $user->name = $data['name'];
        $syncable = [
            'first_name',
            'last_name',
            'phone',
            'cellphone',
            'fax',
            'address',
            'city',
            'state',
            'zip_code',
            'image',
        ];
        foreach ($syncable as $field) {
            if (array_key_exists($field, $data)) {
                $user->{$field} = $data[$field];
            }
        }
        if (isset($data['language_id'])) {
            $user->language_id = (int) $data['language_id'];
        }
        $plainPassword = isset($data['password']) ? trim((string) $data['password']) : '';
        if ($plainPassword !== '') {
            $this->userPasswordService->apply($user, $plainPassword);
        }
        $user->save();

        return $user;
    }

    /**
     * Crear usuario
     */
    private function createUser(array $data): User
    {
        $plainPassword = (string) ($data['password'] ?? '');
        unset($data['password'], $data['password_plain']);
        /** @var User $user */
        $user = User::make($data);
        $this->userPasswordService->apply($user, $plainPassword);

        if(!isset($data['language_id'])) {
            /** @var Company $company */
            try {
                $companyId = request()->get('company_id') ?? request()->get('company') ;
                $company = $companyId ? Company::find($companyId) : null;
            } catch (NotFoundExceptionInterface|ContainerExceptionInterface $e) {
                $company = Company::query()->first();
            }
            $user->language_id = $company ? $company->language_id : $this->resolveDefaultLanguageId();
        }
        else
            $user->language_id = $data['language_id'];

        try {
            $user->save();
        } catch (QueryException $e) {
            // Carrera / soft-delete: reutilizar el usuario existente del índice único.
            if (! $this->isUniqueEmailConstraintViolation($e)) {
                throw $e;
            }
            $existing = $this->findUserByEmail((string) ($data['email'] ?? ''));
            if ($existing === null) {
                throw $e;
            }
            if ($existing->trashed()) {
                $existing->restore();
            }
            $data['password'] = $plainPassword;

            return $this->syncExistingUserFromStoreRequest($existing, $data);
        }

        return $user;
    }

    private function isUniqueEmailConstraintViolation(QueryException $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'users_email_unique')
            || (str_contains($message, '1062') && str_contains(mb_strtolower($message), 'email'));
    }

    /**
     * Idioma por defecto de la aplicación cuando no hay company ni language_id en la petición.
     */
    private function resolveDefaultLanguageId(): int
    {
        $defaultLanguage = Language::where('default', true)->first()
            ?? Language::first();

        if (!$defaultLanguage) {
            throw new \RuntimeException('No language found. Please ensure at least one language exists in the database.');
        }

        return (int) $defaultLanguage->language_id;
    }

    /**
     * Asociar usuario a la compañía
     */
    private function attachCompany(User $user, int $companyId, string $role, ?int $profileId = null): void
    {
        $data = [
            'company_id' => $companyId,
            'user_id' => $user->id,
            'type' => $role,
        ];

        if (in_array($role, [CompanyUserTypes::Seller->value, CompanyUserTypes::Manager->value]) && $profileId) {
            $data['profileable_id'] = $profileId;
            $data['profileable_type'] = $role === CompanyUserTypes::Seller->value
                ? SellerProfiles::class
                : ManagerProfiles::class;
        }

        CompanyUser::query()->firstOrCreate(
            [
                'company_id' => $companyId,
                'user_id' => $user->id,
                'type' => $role,
            ],
            $data
        );
    }

    /**
     * Asociar usuario a customer
     */
    private function attachCustomer(User $user, int $customerId): void
    {
        CustomerUser::query()->firstOrCreate([
            'customer_id' => $customerId,
            'user_id' => $user->id,
        ]);
    }

    /**
     * Asociar usuario a warehouse
     */
    private function attachWarehouse(User $user, int $companyId, int $warehouseId): void
    {
        WarehouseUser::create([
            'user_id' => $user->id,
            'company_id' => $companyId,
            'warehouse_id' => $warehouseId
        ]);
    }

    /**
     * Asociar usuario a seller
     */
    private function attachSeller(User $user, int $companyId, array $sellerData): void
    {
        $seller = Vendor::create(array_merge($sellerData, [
            'user_id' => $user->id,
            'company_id' => $companyId
        ]));

        if (! empty($sellerData['default'])) {
            Company::where('company_id', $companyId)->update(['default_seller_id' => $seller->id]);
        }
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: "api/v2/user/{user}",
        summary: "Display the specified User.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["User"],
        parameters: [
            new OA\Parameter(
                name:        "user",
                description: "The User identifier to retrieve.",
                in:          "path",
                required:    true
            ),
        ],
        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: 'data',
                                                     ref:      '#/components/schemas/User'
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Unauthenticated"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_NOT_FOUND,
                description: "Not found",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Resource not found"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Internal server error"
                                                 )
                                             ]
                             )
            )
        ]
    )]
    public function show($id)
    {
        $user = $this->getMainModelInstance($id);
        $companyId = request()->input('company_id');

        if ($companyId) {
            $user->setRelation('seller', $user->sellerByCompany($companyId));
            $user->setRelation('customer', $user->customerByCompany($companyId));
            $user->setRelation('warehouse', $user->warehouseByCompany($companyId));
        }
        return $user;
    }

    protected function getMainModelInstance($modelId)
    {
        $columns = request()->input('props',['*']);
        $with = request()->input('relations',[]);

        $companyId = request()->input('company_id');
        $withTrashed = request()->input('withTrashed');

        return $this->getModelInstanceBuilder($modelId)
            ->select($columns)
            ->with($with)
            ->when($companyId, function ($query) use ($companyId) {
                $query->forCompany($companyId);
            })
            ->when($withTrashed, function ($query) {
                $query->withTrashed();
            })
            ->sole();
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: "api/v2/user/{user}",
        summary: "Updates the specified User.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content:  new OA\JsonContent(
                          ref: '#/components/schemas/UserRequest'
                      )
        ),
        tags: ["User"],
        parameters: [
            new OA\Parameter(
                name:        "user",
                description: "The User identifier to update.",
                in:          "path",
                required:    true
            ),
        ],
        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: 'data',
                                                     ref:      '#/components/schemas/User'
                                                 ),
                                                 new OA\Property(
                                                     property: 'message',
                                                     type:     'string',
                                                     example:  'Resource updated successfully'
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Unauthenticated"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_NOT_FOUND,
                description: "Not found",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Resource not found"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Internal server error"
                                                 )
                                             ]
                             )
            )
        ]
    )]
    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();
        $sellerData = Arr::pull($data, 'seller');

        $plainPassword = isset($data['password']) ? trim((string) $data['password']) : '';
        if ($plainPassword !== '') {
            $data = array_merge($data, $this->userPasswordService->attributes($plainPassword));
        } else {
            unset($data['password'], $data['password_plain']);
        }

        if (is_array($sellerData)) {
            $sellerError = $this->syncSellerFromUserUpdate($user, $sellerData);
            if ($sellerError !== null) {
                return $sellerError;
            }
        }

        return $this->updateModel($this->getMainModelInstance($user->id), $data);
    }

    /**
     * Actualiza flags del vendedor (all_customers, proforma, default) al editar el usuario.
     *
     * @param  array<string, mixed>  $sellerData
     */
    private function syncSellerFromUserUpdate(User $user, array $sellerData): ?\Illuminate\Http\JsonResponse
    {
        $companyId = (int) request()->input('company_id');
        if ($companyId < 1) {
            return null;
        }

        $vendor = $user->sellerByCompany($companyId);
        if ($vendor === null) {
            return null;
        }

        $langId = (int) (auth()->user()->language_id ?? 2);
        $wantsDefault = ! empty($sellerData['default']);

        if ($vendor->isDefault && ! $wantsDefault) {
            return response()->json([
                'message' => PlanLimitLang::get($langId, 'cmp-vendedor', 'no_puede_desactivar'),
            ], 422);
        }

        if (! $vendor->isDefault && $wantsDefault) {
            if (! $vendor->status) {
                return response()->json([
                    'message' => PlanLimitLang::get($langId, 'cmp-vendedor', 'cannot_set_default_when_inactive'),
                ], 422);
            }
            $vendor->company->update(['default_seller_id' => $vendor->id]);
        }

        $vendor->update([
            'phone_number' => (string) ($sellerData['phone_number'] ?? $vendor->phone_number ?? ''),
            'cell_number' => (string) ($sellerData['cell_number'] ?? $vendor->cell_number ?? ''),
            'fax' => array_key_exists('fax', $sellerData) ? ($sellerData['fax'] ?: null) : $vendor->fax,
            'email_on_salesman_order' => ! empty($sellerData['email_on_salesman_order']) ? 1 : 0,
            'all_customers' => ! empty($sellerData['all_customers']) ? 1 : 0,
            'proforma' => ! empty($sellerData['proforma']) ? 1 : 0,
        ]);

        $profileId = (int) ($sellerData['profile_id'] ?? 0);
        if ($profileId > 0) {
            CompanyUser::query()
                ->where('company_id', $companyId)
                ->where('user_id', $user->id)
                ->where('type', CompanyUserTypes::Seller->value)
                ->update([
                    'profileable_id' => $profileId,
                    'profileable_type' => SellerProfiles::class,
                ]);
        }

        return null;
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: "api/v2/user/{user}",
        summary: "Remove the specified User.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["User"],
        parameters: [
            new OA\Parameter(
                name:        "user",
                description: "The User identifier to remove.",
                in:          "path",
                required:    true
            ),
        ],
        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: 'message',
                                                     type:     'string',
                                                     example:  'Resource deleted successfully'
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Unauthenticated"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_NOT_FOUND,
                description: "Not found",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Resource not found"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Internal server error"
                                                 )
                                             ]
                             )
            )
        ]
    )]
    public function destroy(User $user)
    {
        $this->authorize('forceDelete', $user);

        if (auth()->id() === $user->id ){
            return response()->json(['message' => 'No podés eliminar tu propio usuario'], 403);
        }
        /** @var User $authUser */
        $authUser = auth()->user();
        if($authUser->isAdmin())
            $companyIds = $user->companies->map(fn($company) => $company->id)->toArray();
        else {

            $company_id = \request()->input('company_id');

            if (!$company_id || !Company::where('company_id', $company_id)->exists()){
                return response()->json(['message' => 'Error al obtener la compania'], 403);
            }
            if($authUser->isManager($company_id))
                $companyIds[] = $company_id;
            else
                return response()->json(['message' => 'No puede administrar la compania'], 403);

        }


        $errors = [];
        foreach ($companyIds as $companyId) {
            $errors = $this->removeUserFromCompany($user, $companyId, $errors);

            if (!empty($errors)) {
                return response()->json([
                    'message' => 'No se puede eliminar el usuario por las siguientes razones:',
                    'errors' => $errors,
                ], 422);
            }
        }
        if (!$user->companyUsers()->exists()) {
            $user->forceDelete();
        }

        return response()->noContent();
    }

    #[OA\Get(
        path: "api/v2/user/{userId}/customer",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["User", "Customer"],
        parameters: [
            new OA\Parameter(
                name:        "userId",
                description: "The Customer identifier to retrieve.",
                in:          "path",
                required:    true
            ),
        ],
        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: 'data',
                                                     ref:      '#/components/schemas/Customer'
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Unauthenticated"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_NOT_FOUND,
                description: "Not found",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Resource not found"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Internal server error"
                                                 )
                                             ]
                             )
            )
        ]
    )]
    public function customer(int $userId, int $companyId)
    {
        return $this->getMainModelInstance($userId)
            ->customerByCompany($companyId);
    }

    #[OA\Get(
        path: "api/v2/user/{userId}/seller",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["User", "Seller"],
        parameters: [
            new OA\Parameter(
                name:        "userId",
                description: "The Seller identifier to retrieve.",
                in:          "path",
                required:    true
            ),
        ],
        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: 'data',
                                                     ref:      '#/components/schemas/Vendor'
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Unauthenticated"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_NOT_FOUND,
                description: "Not found",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Resource not found"
                                                 )
                                             ]
                             )
            ),
            new OA\Response(
                response:    Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: "message",
                                                     type:     "string",
                                                     example:  "Internal server error"
                                                 )
                                             ]
                             )
            )
        ]
    )]
    public function seller(int $userId, int $companyId)
    {
        return $this->getMainModelInstance($userId)
            ->sellerByCompany($companyId);
    }

    #[OA\Put(
        path: "/api/v2/user/{userId}/enable",
        summary: "Restaurar un usuario eliminado (soft delete)",
        security: [['bearerAuth' => []]],
        tags: ["Users"],
        parameters: [
            new OA\Parameter(
                name: "userId",
                in: "path",
                required: true,
                description: "ID del usuario a restaurar",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Usuario restaurado correctamente",
                content: new OA\JsonContent(ref: "#/components/schemas/User")
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Usuario no encontrado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "No autenticado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Error interno del servidor",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function enable($userId)
    {
        $user = $this->getBuilder()
            ->withTrashed()
            ->findOrFail($userId);

        $this->authorize('restore', $user);

        if (!$user || !$user->trashed()) {
            return $user;
        }

        $user->restore();

        return $user;
    }

    #[OA\Put(
        path: "/api/v2/user/{userId}/disable",
        summary: "Deshabilitar un usuario (soft delete)",
        security: [['bearerAuth' => []]],
        tags: ["Users"],
        parameters: [
            new OA\Parameter(
                name: "userId",
                in: "path",
                required: true,
                description: "ID del usuario a deshabilitar",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Usuario deshabilitado correctamente",
                content: new OA\JsonContent(ref: "#/components/schemas/User")
            ),
            new OA\Response(
                response: Response::HTTP_FORBIDDEN,
                description: "No permitido (intentando deshabilitarse a sí mismo o ya está deshabilitado)",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Forbidden")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Usuario no encontrado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "No autenticado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Error interno del servidor",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function disable($userId)
    {
        $user = $this->getMainModelInstance($userId);

        if (!$user || $user->trashed() || auth()->id() === $user->id) {
            return $user;
        }

        $user->delete();

        return $user;
    }

    protected function validateRole(CompanyUser $role, array &$errors): void
    {
        $type = $role->type;
        $model = $role->typeModel();

        match ($type) {
            CompanyUserTypes::Customer->value => $this->validateCustomer($model, $errors),
            CompanyUserTypes::Seller->value   => $this->validateSeller($model, $errors),
            //warehouse: si tiene despachos || si tiene ordenes de inventario = mensaje no se puede eliminar
            CompanyUserTypes::Warehouse->value => null, //TODO when inventory orders are implemented
            CompanyUserTypes::Manager->value  => null,
            default => $errors[] = "Tipo de usuario desconocido: $type.",
        };
    }

    protected function validateCustomer(Customer|null $customer, array &$errors): void
    {
        if (!$customer) {
            $errors[] = "Error al encontrar el cliente asociado.";
            return;
        }

        $hasOrders = $customer->orders()->exists();
        $hasRequests = $customer->catalog_requests()
            ->where('status', 1)
            ->exists();
//        $hasReturns = //TODO when create the new entity "returns?"

        if ($hasOrders || $hasRequests ) {//|| $hasReturns
            $errors[] = "El cliente (ID: {$customer->customer_id}) tiene órdenes, solicitudes o devoluciones activas.";
        }
    }

    protected function validateSeller(Vendor|null $seller, array &$errors): void
    {
        if (!$seller) {
            $errors[] = "Error al encontrar el vendedor asociado.";
            return;
        }

        $hasOrders = $seller->orders()->exists();
        $hasCustomers = $seller->customers()->exists();

        if ($hasOrders || $hasCustomers) {
            $errors[] = "El vendedor (ID: {$seller->id}) tiene órdenes o clientes asignados.";
        }
    }
}

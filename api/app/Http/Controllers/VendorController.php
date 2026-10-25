<?php

namespace App\Http\Controllers;

use App\Enums\CompanyUserTypes;
use App\Http\Requests\StoreVendorRequest;
use App\Http\Requests\UpdateVendorRequest;
use App\Models\CompanyUser;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class VendorController extends Controller
{
    protected $resourceRoute = 'vendor';

    #[OA\Get(
        path: "api/v2/vendor",
        summary: "Display a listing of the Vendor.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Seller"],

        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/Vendor')
                    ),
                    new OA\Property(
                property: 'meta',
                type: 'object',
                properties: [
                    new OA\Property(property: 'current_page', type: 'integer'),
                    new OA\Property(property: 'from', type: 'integer'),
                    new OA\Property(property: 'last_page', type: 'integer'),
                    new OA\Property(property: 'per_page', type: 'integer'),
                    new OA\Property(property: 'to', type: 'integer'),
                    new OA\Property(property: 'total', type: 'integer')
                ]
            ),
            new OA\Property(
                property: 'links',
                type: 'object',
                properties: [
                    new OA\Property(property: 'first', type: 'string'),
                    new OA\Property(property: 'last', type: 'string'),
                    new OA\Property(property: 'prev', type: 'string', nullable: true),
                    new OA\Property(property: 'next', type: 'string', nullable: true)
                ]
            )
                ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
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
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: "api/v2/vendor",
        summary: "Store a newly created resource in storage.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
                requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/VendorRequest'
            )
        ),
        tags: ["Seller"],

        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                new OA\Property(
                    property: 'data',
                    ref: '#/components/schemas/Vendor'
                ),
                new OA\Property(
                    property: 'message',
                    type: 'string',
                    example: 'Resource created successfully'
                )
            ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function store(StoreVendorRequest $request): \Illuminate\Http\Resources\Json\JsonResource
    {
        $validated = $request->validated();

        $profile_id = $validated['profile_id'];
        unset($validated['profile_id']);

        $newSeller = Vendor::create($validated);

        if ($validated['default']) {
            $newSeller->company->update(['default_seller_id' => $newSeller->id]);
        }

        $this->syncCompanyUser($newSeller, $profile_id);

        return $this->makeResource($newSeller);
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: "api/v2/vendor/{vendor}",
        summary: "Display the specified Vendor.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Seller"],
        parameters: [
            new OA\Parameter(
                name: "vendor",
                description: "The Vendor identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        ref: '#/components/schemas/Vendor'
                    )
                ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function show($vendor)
    {
        return $this->getMainModelInstance($vendor);
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: "api/v2/vendor/{vendor}",
        summary: "Updates the specified Vendor.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
                requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/VendorRequest'
            )
        ),
        tags: ["Seller"],
        parameters: [
            new OA\Parameter(
                name: "vendor",
                description: "The Vendor identifier to update.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                new OA\Property(
                    property: 'data',
                    ref: '#/components/schemas/Vendor'
                ),
                new OA\Property(
                    property: 'message',
                    type: 'string',
                    example: 'Resource updated successfully'
                )
            ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function update(UpdateVendorRequest $request, Vendor $vendor)
    {
        $validated = $request->validated();

        $profile_id = $validated['profile_id'];
        unset($validated['profile_id']);

        if ($vendor->isDefault && !$validated['default']) {
            return response()->json(['message' => 'No se puede desactivar el vendedor por defecto de la empresa.',], 422);
        }

        if (!$vendor->isDefault && $validated['default']) {
            if (!$vendor->status)
                return response()->json([ 'message' => 'No se puede utilizar como vendedor por defecto dado que está desactivado.', ], 422);

            $vendor->company->update(['default_seller_id' => $vendor->id]);
        }

        $oldUserId = $vendor->user_id;
        $vendor->update($validated);

        $this->syncCompanyUser($vendor, $profile_id, $oldUserId);

        return $this->makeResource($vendor);
    }

    private function syncCompanyUser(Vendor $vendor, int $profile_id, ?int $oldUserId = null)
    {
        $data = [
            'company_id' => $vendor->company_id,
            'type'       => CompanyUserTypes::Seller->value,
        ];

        if ($oldUserId) {
            $existing = CompanyUser::where($data)->where('user_id', $oldUserId)->first();

            if ($existing) {
                if ($vendor->user_id === $oldUserId) {
                    CompanyUser::where([
                        'company_id' => $vendor->company_id,
                        'user_id' => $oldUserId,
                        'type' => CompanyUserTypes::Seller->value,
                    ])->update([
                        'profileable_id'   => $profile_id,
                        'profileable_type' => \App\Models\SellerProfiles::class,
                    ]);
                } else {
                    CompanyUser::where([
                        'company_id' => $vendor->company_id,
                        'user_id' => $oldUserId,
                        'type' => CompanyUserTypes::Seller->value,
                    ])->delete();

                    $this->createCompanyUser($data, $vendor, $profile_id);
                }
                return;
            }
        }

        $this->createCompanyUser($data, $vendor, $profile_id);
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: "api/v2/vendor/{vendor}",
        summary: "Remove the specified Vendor.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Seller"],
        parameters: [
            new OA\Parameter(
                name: "vendor",
                description: "The Vendor identifier to remove.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                new OA\Property(
                    property: 'message',
                    type: 'string',
                    example: 'Resource deleted successfully'
                )
            ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function destroy(Vendor $vendor): \Illuminate\Http\JsonResponse|Response
    {
         if ($vendor->isDefault)
         {
             return response()->json([
                 'message' => 'You cannot delete the default vendor',
             ], 422);
         }
        return $this->destroyResourceEmptyResponse($vendor);
    }

    #[OA\Get(
        path: "api/v2/vendor/company/{companyId}/name-{vendorName}/exists",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Seller"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The Exist identifier to retrieve.",
                in: "path",
                required: true
            ),
            new OA\Parameter(
                name: "vendorName",
                description: "The Exist identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    type: "boolean",
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function nameExists($companyId, $vendorName): bool
    {
        return $this->getBuilder()
            ->where('company_id', $companyId)
            ->whereRaw("CONCAT(first_name, ' ', last_name) = ?", [$vendorName])
            ->exists();
    }

    #[OA\Get(
        path: "api/v2/vendor/company/{companyId}/username-{userName}/exists",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Seller"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The Exist identifier to retrieve.",
                in: "path",
                required: true
            ),
            new OA\Parameter(
                name: "userName",
                description: "The Exist identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    type: 'boolean',
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function userNameExist($companyId, $userName): bool
    {
        return $this->getBuilder()
            ->where('company_id', $companyId)
            ->where('username', $userName)
            ->exists();
    }

    #[OA\Get(
        path: "api/v2/vendor/company/{companyId}/email/{email}/exists",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Seller"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The Exist identifier to retrieve.",
                in: "path",
                required: true
            ),
            new OA\Parameter(
                name: "email",
                description: "The Exist identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    type: 'boolean',
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function emailExists($companyId, $email): bool
    {
        return $this->getBuilder()
            ->where('company_id', $companyId)
            ->where('email', $email)
            ->exists();
    }

    #[OA\Get(
        path: "api/v2/vendor/{vendor}/customers",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Seller"],
        parameters: [
            new OA\Parameter(
                name: "vendor",
                description: "The Customer identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        ref: '#/components/schemas/Customer'
                    )
                ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function customers(Vendor $vendor){
        /** @var User|null $user */
        $user = auth()->user();
        if ($user && ! $user->isAdmin() && ! $user->isManager()) {
            if (! $user->isSeller((int) $vendor->company_id)) {
                abort(403);
            }
            $own = $user->sellerByCompany((int) $vendor->company_id);
            if (! $own || (int) $own->id !== (int) $vendor->id) {
                abort(403);
            }
        }

        $page = request()->input('page', 1);
        $perPage = request()->input('per_page', 20);
        $props = request()->input('props', ['*']);
        $onlyActive = filter_var(request()->query('only_active', '1'), FILTER_VALIDATE_BOOLEAN);

        return $vendor
            ->customers()
            ->when($onlyActive, static fn ($q) => $q->where('customer_status', true))
            ->with(['sellers', 'customer_group'])
            ->select($props)
            ->paginate($perPage, ['*'], 'page', $page);
    }

    #[OA\Get(
        path: "api/v2/vendor/email/{email}/exists",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Seller"],
        parameters: [
            new OA\Parameter(
                name: "email",
                description: "The Exist identifier to retrieve.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    type: 'boolean',
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server Error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function existEmailInOtherCompany(Request $request, $email): bool
    {
        $companyId = $request->input('companyId');
        return $this->getBuilder()
            ->where('company_id','!=', $companyId)
            ->where('email', $email)
            ->exists();
    }

    public function enable(Vendor $seller): Vendor
    {
        if ($seller->status) {
            return $seller;
        }

        $seller->update(['status' => true]);

        return $seller;
    }

    public function disable(Vendor $seller): Vendor
    {
        if (!$seller->status || $seller->isDefault) {
            return $seller;
        }

        $seller->update(['status' => false]);

        return $seller;
    }

    /**
     * @param array $data
     * @param Vendor $vendor
     * @param int $profile_id
     * @return void
     */
    private function createCompanyUser(array $data, Vendor $vendor, int $profile_id): void
    {
        CompanyUser::create(array_merge($data, [
            'user_id' => $vendor->user_id,
            'profileable_id' => $profile_id,
            'profileable_type' => \App\Models\SellerProfiles::class,
        ]));
    }

    /**
     * Listar sellers de la compañía del usuario autenticado
     */
    #[OA\Get(
        path: "api/v2/sellers",
        summary: "Listar sellers de la compañía del usuario autenticado",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
        tags: ["Seller"],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Vendor')
                        )
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: "No se pudo determinar la compañía del usuario",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "No se pudo determinar la compañía")
                    ]
                )
            )
        ]
    )]
    public function listSellersByCompany(Request $request)
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        
        // Obtener el company_id del request o de la primera compañía del usuario
        $companyId = $request->input('company_id') ?? $request->input('company');
        
        if (!$companyId) {
            // Intentar obtener de cualquier compañía del usuario (manager, seller, etc.)
            $userCompany = $user->companies()->first();
            $companyId = $userCompany?->company_id;
        }
        
        if (!$companyId) {
            return response()->json([
                'message' => 'No se pudo determinar la compañía. Asegúrese de estar asociado a una compañía.'
            ], 422);
        }
        
        // Obtener todos los sellers de la compañía con información del usuario
        $sellers = Vendor::where('company_id', $companyId)
            ->with('user:id,name,email,phone,cellphone')
            ->get();
        
        // Usar el VendorResource para mantener consistencia con el resto de la API
        return response()->json([
            'data' => $sellers->map(function ($seller) {
                return $this->makeResource($seller);
            })
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerGroupRequest;
use App\Http\Requests\UpdateCustomerGroupRequest;
use App\Models\Company;
use App\Models\CustomerGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class CustomerGroupController extends Controller
{
    protected $resourceRoute = 'customergroup';

    #[OA\Get(
        path: "api/v2/customergroup",
        summary: "Display a listing of the CustomerGroup.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Customer"],

        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/Customergroup')
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
        path: "api/v2/customergroup",
        summary: "Store a newly created resource in storage.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
                requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/CustomergroupRequest'
            )
        ),
        tags: ["Customer"],

        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                new OA\Property(
                    property: 'data',
                    ref: '#/components/schemas/Customergroup'
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
    public function store(StoreCustomerGroupRequest $request): \Illuminate\Http\Resources\Json\JsonResource
     {
         $validated = $request->validated();
         $isDefault = $validated['default'] ?? false;

         $validated = $this->normalizeCustomerGroupPercentage($validated);

         $newGroup = CustomerGroup::create($validated);

         if ($isDefault) {
             $newGroup->company->update(['default_customer_group_id' => $newGroup->group_id]);
         }

        return $this->makeResource($newGroup);
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: "api/v2/customergroup/{customergroup}",
        summary: "Display the specified CustomerGroup.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Customer"],
        parameters: [
            new OA\Parameter(
                name: "customergroup",
                description: "The Customergroup identifier to retrieve.",
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
                        ref: '#/components/schemas/Customergroup'
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
    public function show($customerGroup)
    {

        return $this->getMainModelInstance($customerGroup);
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: "api/v2/customergroup/{customergroup}",
        summary: "Updates the specified CustomerGroup.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
                requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/CustomergroupRequest'
            )
        ),
        tags: ["Customer"],
        parameters: [
            new OA\Parameter(
                name: "customergroup",
                description: "The Customergroup identifier to update.",
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
                    ref: '#/components/schemas/Customergroup'
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
    public function update(UpdateCustomerGroupRequest $request, CustomerGroup $customergroup)
    {
        $validated = $request->validated();
        $isDefault = $validated['default'] ?? false;

        $validated = $this->normalizeCustomerGroupPercentage($validated);

        if ($customergroup->isDefault && !$isDefault) {
            return response()->json(['message' => "Default customer group cannot be deactivated."], 422);
        }

        if (!$customergroup->isDefault && $isDefault) {
            if (!$customergroup->group_status) {
                return response()->json([
                    'message' => 'Cannot be used as default customer group because it is not active.',
                ], 422);
            }

            $customergroup->company->update(['default_customer_group_id' => $customergroup->group_id]);
        }

        return $this->updateModel($customergroup, $validated);
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: "api/v2/customergroup/{customergroup}",
        summary: "Remove the specified CustomerGroup.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Customer"],
        parameters: [
            new OA\Parameter(
                name: "customergroup",
                description: "The Customergroup identifier to remove.",
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
    public function destroy(CustomerGroup $customergroup): \Illuminate\Http\JsonResponse|Response
    {
         $customersCount = (int) $customergroup->customers()->count();
         if ($customersCount > 0){
             $blocker = $this->buildCustomerGroupBlockedPayload(
                 $customergroup,
                 $customersCount,
                 'group_has_customers_cannot_delete',
                 'Customer group has %d assigned customer(s) (%s) and cannot be deleted.'
             );

             return response()->json($blocker, 400);
         }

        if ($customergroup->isDefault) {
            return response()->json(['message' => 'Default customer group cannot be deleted.'],400);
        }

        // force=true para borrado físico en DB (el modelo usa SoftDeletes)
        return $this->destroyResourceEmptyResponse($customergroup, true);
     }

    protected function getPaginatedModelList(Builder $filteredSortedModelQuery, array $columns, Request $request)
    {
        return $this->getBuilder()
            ->filter($request->all())
            ->orderBy('group_id','DESC')
            ->paginate();
    }

    #[OA\Get(
        path: "api/v2/customer-group/name-{name}/company/{companyId}/exists",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Customer"],
        parameters: [
            new OA\Parameter(
                name: "name",
                description: "The Exist identifier to retrieve.",
                in: "path",
                required: true
            ),
            new OA\Parameter(
                name: "companyId",
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
    public function exists($customerGroupName,$companyId)
    {
        return response()->json(
            $this->getBuilder()
            ->where('company_id', $companyId)
            ->where('name', $customerGroupName)
            ->count() > 0
        );
    }


    #[OA\Get(
        path: "api/v2/customer-group/name-{name}/company/{companyId}",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Customer"],
        parameters: [
            new OA\Parameter(
                name: "name",
                description: "The Company identifier to retrieve.",
                in: "path",
                required: true
            ),
            new OA\Parameter(
                name: "companyId",
                description: "The Company identifier to retrieve.",
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
                        ref: '#/components/schemas/Company'
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
    public function showByName($name,$companyId): \Illuminate\Database\Eloquent\Collection
    {
        return $this->getBuilder()
            ->where('company_id', $companyId)
            ->where('name', $name)
            ->get();
    }


    #[OA\Get(
        path: "api/v2/customer-group/{customerGroupId}/customers",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Customer"],
        parameters: [
            new OA\Parameter(
                name: "customerGroupId",
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
    public function customers($customerGroupId)
    {
        $customerGroup = $this->getBuilder()->find($customerGroupId);
        if ($customerGroup) {
            return $customerGroup->customers;
        }
        return response()->json(['message' => 'Customer group not found'], 404);
    }

    public function enable($groupId)
    {
        $group = $this->getMainModelInstance($groupId);

        if (!$group || $group->group_status) {
            return $group;
        }

        $group->update(['group_status' => true]);

        return $group;
    }

    public function disable($groupId)
    {
        $group = $this->getMainModelInstance($groupId);

        if (!$group) {
            return $group;
        }

        if (!$group->group_status) {
            return $group;
        }

        if ($group->isDefault) {
            return response()->json(['message' => 'Default customer group cannot be disabled.'], 400);
        }

        $customersCount = (int) $group->customers()->count();
        if ($customersCount > 0) {
            $blocker = $this->buildCustomerGroupBlockedPayload(
                $group,
                $customersCount,
                'group_has_customers_cannot_disable',
                'Customer group has %d assigned customer(s) (%s) and cannot be disabled.'
            );

            return response()->json($blocker, 400);
        }

        $group->update(['group_status' => false]);

        return $group;
    }

    public function setDefault($groupId)
    {
        $group = $this->getMainModelInstance($groupId);

        if (!$group) {
            return response()->json(['message' => 'Customer group not found'], 404);
        }

        if (!$group->group_status) {
            return response()->json([
                'message' => 'Cannot be used as default customer group because it is not active.',
            ], 422);
        }

        $companyId = (int) (request()->input('company_id') ?: $group->company_id);
        Company::where('company_id', $companyId)->update(['default_customer_group_id' => $group->group_id]);

        return $group->fresh(['company']);
    }

    /**
     * Obtener todos los grupos de clientes de la compañía del usuario autenticado
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Get(
        path: "api/v2/customergroup/all",
        summary: "Obtener todos los grupos de clientes de la compañía del usuario autenticado",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
        tags: ["Customer"],
        parameters: [
            new OA\Parameter(
                name: 'props',
                in: 'query',
                required: false,
                description: 'Campos a retornar (array o string separado por comas)',
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Lista de todos los grupos de clientes de la compañía",
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Customergroup')
                        )
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
                response: Response::HTTP_UNPROCESSABLE_ENTITY,
                description: "No se pudo determinar la compañía",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "No se pudo determinar la compañía. Asegúrese de estar asociado a una compañía.")
                    ]
                )
            )
        ]
    )]
    public function all(Request $request)
    {
        // Obtener el usuario autenticado
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        
        // Obtener el company_id del request o de la primera compañía del usuario
        $companyId = $request->input('company_id') ?? $request->input('company');
        
        if (!$companyId) {
            // Si no se proporciona company_id, usar la primera compañía del usuario como manager
            $userCompany = $user->companiesForRole('manager')->first();
            if (!$userCompany) {
                // Si no es manager, intentar obtener de otras relaciones
                $userCompany = $user->companies()->first();
            }
            $companyId = $userCompany?->company_id;
        }
        
        if (!$companyId) {
            return response()->json([
                'message' => 'No se pudo determinar la compañía. Asegúrese de estar asociado a una compañía.'
            ], 422);
        }
        
        // Forzar all=true y company para obtener todas sin paginación filtradas por compañía
        $request->merge([
            'all' => true,
            'company' => $companyId,
            'company_id' => $companyId
        ]);
        
        // Usar getModelList que ya maneja el parámetro 'all' y el filtro de company
        $groups = $this->getModelList($request);
        
        // Si la respuesta es una colección directa (all=true, sin paginación)
        if ($groups instanceof \Illuminate\Support\Collection || is_array($groups)) {
            $groupsCollection = $groups instanceof \Illuminate\Support\Collection ? $groups : collect($groups);
            
            // Mantener la misma estructura que /api/v2/customergroup: envolver en {'data': [...]}
            return response()->json([
                'data' => $groupsCollection->values()->all()
            ]);
        }
        // Si la respuesta tiene estructura paginada (con data, meta, links)
        elseif (is_object($groups) && isset($groups->data)) {
            return $groups;
        }
        // Si la respuesta es un array con estructura paginada
        elseif (is_array($groups) && isset($groups['data'])) {
            return response()->json($groups);
        }
        // Fallback: tratar como colección
        else {
            $groupsCollection = collect($groups);
            return response()->json([
                'data' => $groupsCollection->values()->all()
            ]);
        }
    }

    /**
     * Construye el payload de error cuando un grupo no se puede eliminar/deshabilitar
     * porque tiene clientes asignados. Incluye una muestra de nombres reales.
     */
    private function buildCustomerGroupBlockedPayload(
        CustomerGroup $group,
        int $customersCount,
        string $messageKey,
        string $fallbackTemplate,
        int $sampleSize = 10
    ): array {
        $sample = $group->customers()
            ->select(['customer_id', 'name', 'business_name'])
            ->orderBy('name')
            ->limit($sampleSize)
            ->get()
            ->map(function ($customer) {
                $name = trim((string) ($customer->name ?? ''));
                if ($name === '') {
                    $name = trim((string) ($customer->business_name ?? ''));
                }
                if ($name === '') {
                    $name = '#' . (int) ($customer->customer_id ?? 0);
                }
                return $name;
            })
            ->values()
            ->all();

        $overflow = max(0, $customersCount - count($sample));
        $namesPreview = implode(', ', $sample);
        if ($overflow > 0) {
            $namesPreview .= sprintf(' (+%d more)', $overflow);
        }

        return [
            'message' => sprintf($fallbackTemplate, $customersCount, $namesPreview),
            'message_key' => $messageKey,
            'customers_count' => $customersCount,
            'customers_names' => $sample,
            'customers_overflow' => $overflow,
        ];
    }

    /**
     * @param array<string, mixed> $validated
     * @return array<string, mixed>
     */
    private function normalizeCustomerGroupPercentage(array $validated): array
    {
        $percentage = $validated['percentage_on_price'] ?? null;
        $normalized = is_scalar($percentage) ? strtolower(trim((string) $percentage)) : '';

        if ($percentage === null || $normalized === '' || $normalized === 'nothing') {
            $validated['percentage_on_price'] = null;
            $validated['percent_price_amount'] = 0;
        }

        return $validated;
    }

}

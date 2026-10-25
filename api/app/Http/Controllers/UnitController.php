<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use App\Models\Company;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class UnitController extends Controller
{
    protected $resourceRoute = 'unit';

    #[OA\Get(
        path: "api/v2/unit",
        summary: "Display a listing of the Unit.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Non Functional"],

        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/Unit')
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
        path: "api/v2/unit",
        summary: "Store a newly created resource in storage.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
                requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/UnitRequest'
            )
        ),
        tags: ["Non Functional"],

        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                new OA\Property(
                    property: 'data',
                    ref: '#/components/schemas/Unit'
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
    public function store(StoreUnitRequest $request): \Illuminate\Http\JsonResponse
     {
         $validated = $request->validated();

         $newUnit = $this->createFromAttributes(collect($validated)->except('default')->all());

         if ($validated['default']) {
             $this->updateDefaultUnit($validated['company_id'],$newUnit->unit_id);
         }

         return $newUnit->response()->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: "api/v2/unit/{unit}",
        summary: "Display the specified Unit.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Non Functional"],
        parameters: [
            new OA\Parameter(
                name: "unit",
                description: "The Unit identifier to retrieve.",
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
                        ref: '#/components/schemas/Unit'
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
    public function show($unit)
    {
        return $this->getMainModelInstance($unit);
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: "api/v2/unit/{unit}",
        summary: "Updates the specified Unit.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
                requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/UnitRequest'
            )
        ),
        tags: ["Non Functional"],
        parameters: [
            new OA\Parameter(
                name: "unit",
                description: "The Unit identifier to update.",
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
                    ref: '#/components/schemas/Unit'
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
    public function update(UpdateUnitRequest $request, $unitId)
    {
        $validated = $request->validated();
        $unit = $this->getMainModelInstance($unitId);

        if (!$unit) {
            return response()->json(['message' => 'Unidad no encontrada'], 404);
        }

        if ($unit->isDefault && !$validated['default']) {
            return response()->json(['message' => 'No se puede desactivar la unidad default de la empresa.',], 422);
        }

        if (!$unit->isDefault && $validated['default']) {
            if (!$unit->status) {
                return response()->json([
                    'message' => 'No se puede utilizar como unidad por defecto dado que está desactivado.',
                ], 422);
            }

            $this->updateDefaultUnit($validated['company_id'],$unit->unit_id);
        }

        return $this->updateModel($unit, $validated);
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: "api/v2/unit/{unit}",
        summary: "Remove the specified Unit.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Non Functional"],
        parameters: [
            new OA\Parameter(
                name: "unit",
                description: "The Unit identifier to remove.",
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
    public function destroy($unitId)
     {
        $unit = $this->getMainModelInstance($unitId);

        if (!$unit) {
            return response()->json(['message' => 'Unidad no encontrada'], 404);
        }

        if ($unit->company_id != request()->input('company')) {
            return response()->json(['message' => 'No tiene permisos para modificar esta unidad'], 403);
        }

        if ($unit->isDefault) {
            return response()->json(['message' => 'No se puede eliminar la unidad por defecto'], 400);
        }

        if ($unit->products()->exists()) {
            return response()->json(['message' => 'No se puede eliminar la unidad porque tiene productos asociados.'], 400);
        }

        return $this->destroyResourceEmptyResponse($unit);
    }

    protected function getPaginatedModelList(Builder $filteredSortedModelQuery, array $columns, Request $request)
    {
        return $this->getBuilder()
            ->filter($request->all())
            ->orderBy('unit_id','DESC')
            ->paginate();
    }

    #[OA\Get(
        path: "api/v2/unit/company/{companyId}",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Non Functional"],
        parameters: [
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
    public function default($companyId)
    {
        return Company::find($companyId)->defaultUnit;
    }

    public function updateDefault(UpdateUnitRequest $request)
    {
        $companyId = $request->get('company_id');
        return $this->updateModel(
            $this->default($companyId),
            $request->toArray()
        );
    }

    #[OA\Get(
        path: "api/v2/unit/{companyId}/{unitName}/exists",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Non Functional"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The Exist identifier to retrieve.",
                in: "path",
                required: true
            ),
            new OA\Parameter(
                name: "unitName",
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
    public function exists($companyId, $unitName): \Illuminate\Http\JsonResponse
    {
        return response()->json(
            $this->getBuilder()
                ->where('company_id', $companyId)
                ->where('unit', $unitName)
                ->exists()
        );
    }

    #[OA\Put(
        path: "api/v2/unit/{unitId}/enable",
        summary: "Enable a Unit",
        security: [
            ['bearerAuth' => []]
        ],
        tags: ["Non Functional"],
        parameters: [
            new OA\Parameter(
                name: "unitId",
                in: "path",
                required: true,
                description: "ID of the Unit to enable",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Unit successfully enabled",
                content: new OA\JsonContent(ref: "#/components/schemas/Unit")
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Unit not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
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
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function enable($unitId)
    {
        $unit = $this->getMainModelInstance($unitId);

        if (!$unit || $unit->status) {
            return $unit;
        }

        $unit->update(['status' => true]);

        return $unit;
    }

    #[OA\Put(
        path: "api/v2/unit/{unitId}/disable",
        summary: "Disable a Unit",
        security: [
            ['bearerAuth' => []]
        ],
        tags: ["Non Functional"],
        parameters: [
            new OA\Parameter(
                name: "unitId",
                in: "path",
                required: true,
                description: "ID of the Unit to disable",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Unit successfully disabled",
                content: new OA\JsonContent(ref: "#/components/schemas/Unit")
            ),
            new OA\Response(
                response: Response::HTTP_NOT_FOUND,
                description: "Unit not found",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Resource not found")
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
                response: Response::HTTP_INTERNAL_SERVER_ERROR,
                description: "Server error",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Internal server error")
                    ]
                )
            )
        ]
    )]
    public function disable($unitId)
    {
        $unit = $this->getMainModelInstance($unitId);

        if (!$unit) {
            return $unit;
        }

        if (!$unit->status) {
            return $unit;
        }

        if ($unit->isDefault) {
            return response()->json([
                'message' => 'The default Unit of measure cannot be disabled',
                'message_key' => 'defecto_nopuede_desactivar',
            ], 400);
        }

        $unit->update(['status' => false]);

        return $unit;
    }

    public function setDefault($unitId)
    {
        $unit = $this->getMainModelInstance($unitId);

        if (!$unit) {
            return response()->json(['message' => 'Unidad no encontrada'], 404);
        }

        if (!$unit->status) {
            return response()->json([
                'message' => 'No se puede utilizar como unidad por defecto dado que está desactivado.',
            ], 422);
        }

        $companyId = (int) (request()->input('company_id') ?: $unit->company_id);
        $this->updateDefaultUnit($companyId, $unit->unit_id);

        return $unit->fresh(['company']);
    }

    /**
     * @param $company_id
     * @param $unit_id
     * @return void
     */
    protected function updateDefaultUnit($company_id, $unit_id): void
    {
        Company::where('company_id', $company_id)->update(['default_unit_id' => $unit_id]);
    }
}

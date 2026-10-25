<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRolsRequest;
use App\Http\Requests\UpdateRolsRequest;
use App\Models\Rols;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class RolsController extends Controller
{
    protected $resourceRoute = 'rols';

    #[OA\Get(
        path: "api/v2/rols",
        summary: "Display a listing of the Rols.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
        tags: ["User"],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/Rols')
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
            )
        ]
    )]
    public function index()
    {
       return parent::index();
    }

    #[OA\Post(
        path: "api/v2/rols",
        summary: "Store a newly created resource in storage.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
                requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/RolsRequest'
            )
        ),
        tags: ["User"],
        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                new OA\Property(
                    property: 'data',
                    ref: '#/components/schemas/Rols'
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
            )
        ]
    )]
    public function store(StoreRolsRequest $request): \Illuminate\Http\Resources\Json\JsonResource
     {
        return $this->createFromAttributes($request->toArray());
    }

    #[OA\Get(
        path: "api/v2/rols/{rol}",
        summary: "Display the specified Rols.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
        tags: ["User"],
        parameters: [
            new OA\Parameter(
                name: "rol",
                description: "The Rols identifier to retrieve.",
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
                    ref: '#/components/schemas/Rols'
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
            )
        ]
    )]
    public function show($id)
    {
        return $this->getMainModelInstance($id);
    }

    #[OA\Put(
        path: "api/v2/rols/{rol}",
        summary: "Updates the specified Rols.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
                requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/RolsRequest'
            )
        ),
        tags: ["User"],
        parameters: [
            new OA\Parameter(
                name: "rol",
                description: "The Rols identifier to update.",
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
                    ref: '#/components/schemas/Rols'
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
            )
        ]
    )]
    public function update(UpdateRolsRequest $request, Rols $rol): \Illuminate\Http\Resources\Json\JsonResource
    {
        // Usar validated() para obtener solo los campos validados
        // El modelo Rols tiene $fillable que filtra automáticamente los campos permitidos
        return $this->updateModel($rol, $request->validated());
    }

    #[OA\Delete(
        path: "api/v2/rols/{rol}",
        summary: "Remove the specified Rols from storage.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
        tags: ["User"],
        parameters: [
            new OA\Parameter(
                name: "rol",
                description: "The Rols identifier to delete.",
                in: "path",
                required: true
            ),
        ],
        responses: [
            new OA\Response(
                response: Response::HTTP_NO_CONTENT,
                description: "Successful operation"
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "Unauthorized",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Unauthenticated")
                    ]
                )
            )
        ]
    )]
    public function destroy(Rols $rol)
     {
        return $this->destroyResourceEmptyResponse($rol);
    }

    protected function getPaginatedModelList(Builder $filteredSortedModelQuery, array $columns, Request $request)
    {
        $perPage = $request->input('per_page', $request->input('perPage', $this->perPage));
        $page = $request->input('page', 1);
        
        return $filteredSortedModelQuery
            ->orderBy('rol_id','desc')
            ->paginate($perPage, $columns, 'page', $page);
    }
}


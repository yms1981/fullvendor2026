<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStateRequest;
use App\Http\Requests\UpdateStateRequest;
use App\Models\State;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class StateController extends Controller
{
    protected $resourceRoute = 'state';

    /**
     * Estados/departamentos: filtrar por country_id cuando venga en la petición.
     * Sin country_id no se filtran por país (comportamiento legacy).
     */
    protected function getFilteredModelQuery(\Illuminate\Http\Request $request = null)
    {
        if (!$request) {
            $request = request();
        }

        $filterData = $request->all();
        unset($filterData['company']);
        unset($filterData['company_id']);

        $query = $this->getBuilder()->filter($filterData);

        // Forzar filtro por país: solo departamentos/estados del país seleccionado
        $countryId = $request->input('country_id');
        if ($countryId !== null && $countryId !== '' && $countryId !== 'todo' && $countryId != -1) {
            $query->where('country_id', (int) $countryId);
        }

        return $query;
    }

    #[OA\Get(
        path: "api/v2/state",
        summary: "Display a listing of the State.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Places"],

        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                    new OA\Property(
                        property: 'data',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/State')
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
     * Obtener todos los estados sin paginación
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Get(
        path: "api/v2/state/all",
        summary: "Obtener todos los estados sin paginación",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
        tags: ["Places"],
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
                description: "Lista de todos los estados",
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/State')
                        )
                    ]
                )
            ),
            new OA\Response(
                response: Response::HTTP_UNAUTHORIZED,
                description: "No autenticado"
            )
        ]
    )]
    public function all(\Illuminate\Http\Request $request)
    {
        // Obtener el usuario autenticado
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        
        // Forzar all=true para obtener todos sin paginación
        // Los estados son datos maestros, no se filtran por compañía
        $request->merge(['all' => true]);
        
        // Usar getModelList que ya maneja el parámetro 'all'
        $response = $this->getModelList($request);
        
        // Si la respuesta es una colección directa (all=true, sin paginación)
        if ($response instanceof \Illuminate\Support\Collection || is_array($response)) {
            $states = $response instanceof \Illuminate\Support\Collection ? $response : collect($response);
            
            // Mantener la misma estructura que /api/v2/state: envolver en {'data': [...]}
            return response()->json([
                'data' => $states->values()->all()
            ]);
        }
        // Si la respuesta tiene estructura paginada (con data, meta, links)
        elseif (is_object($response) && isset($response->data)) {
            return $response;
        }
        // Si la respuesta es un array con estructura paginada
        elseif (is_array($response) && isset($response['data'])) {
            return response()->json($response);
        }
        // Fallback: tratar como colección
        else {
            $states = collect($response);
            return response()->json([
                'data' => $states->values()->all()
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: "api/v2/state",
        summary: "Store a newly created resource in storage.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
                requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/StateRequest'
            )
        ),
        tags: ["Places"],

        responses: [
            new OA\Response(
                response: Response::HTTP_OK,
                description: "Successful operation",
                content: new OA\JsonContent(
                    properties: [
                new OA\Property(
                    property: 'data',
                    ref: '#/components/schemas/State'
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
    public function store(StoreStateRequest $request): \Illuminate\Http\Resources\Json\JsonResource
     {
        return $this->createFromAttributes($request->toArray());
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: "api/v2/state/{state}",
        summary: "Display the specified State.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Places"],
        parameters: [
            new OA\Parameter(
                name: "state",
                description: "The State identifier to retrieve.",
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
                        ref: '#/components/schemas/State'
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
    public function show($state)
    {
        return $this->getMainModelInstance($state);
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: "api/v2/state/{state}",
        summary: "Updates the specified State.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
                requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/StateRequest'
            )
        ),
        tags: ["Places"],
        parameters: [
            new OA\Parameter(
                name: "state",
                description: "The State identifier to update.",
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
                    ref: '#/components/schemas/State'
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
    public function update(UpdateStateRequest $request, State $state)
    {
        return $this->updateModel($this->getMainModelInstance($state->id),$request->toArray());
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: "api/v2/state/{state}",
        summary: "Remove the specified State.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Places"],
        parameters: [
            new OA\Parameter(
                name: "state",
                description: "The State identifier to remove.",
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
    public function destroy(State $state): \Illuminate\Http\JsonResponse
    {
         if ($state->cities()->exists()) {
             return response()->json(['message' => 'No se puede eliminar el estado porque tiene ciudades asociadas.'], 400);
         }

         $state->delete();

         return response()->json(['success' => true],204);
    }
}

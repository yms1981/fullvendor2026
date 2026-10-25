<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCityRequest;
use App\Http\Requests\UpdateCityRequest;
use App\Models\City;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class CityController extends Controller
{
    protected $resourceRoute = 'city';

    /**
     * Ciudades/provincias: filtrar por state_id cuando venga en la petición.
     * Solo devolver provincias del departamento/estado seleccionado.
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

        $stateId = $request->input('state_id');
        $countryId = $request->input('country_id');

        if ($stateId !== null && $stateId !== '' && $stateId !== 'todo' && $stateId != -1) {
            $query->where('state_id', (int) $stateId);
        } elseif ($countryId !== null && $countryId !== '' && $countryId !== 'todo' && $countryId != -1) {
            $query->whereHas('state', static function ($q) use ($countryId) {
                $q->where('country_id', (int) $countryId);
            });
        }

        return $query;
    }

    #[OA\Get(
        path: "api/v2/city",
        summary: "Display a listing of the City.",
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
                        items: new OA\Items(ref: '#/components/schemas/City')
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
     * Obtener todas las ciudades sin paginación
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Get(
        path: "api/v2/city/all",
        summary: "Obtener todas las ciudades sin paginación",
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
                description: "Lista de todas las ciudades",
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/City')
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
        // Las ciudades son datos maestros, no se filtran por compañía
        $request->merge(['all' => true]);
        
        // Usar getModelList que ya maneja el parámetro 'all'
        $response = $this->getModelList($request);
        
        // Si la respuesta es una colección directa (all=true, sin paginación)
        if ($response instanceof \Illuminate\Support\Collection || is_array($response)) {
            $cities = $response instanceof \Illuminate\Support\Collection ? $response : collect($response);
            
            // Mantener la misma estructura que /api/v2/city: envolver en {'data': [...]}
            return response()->json([
                'data' => $cities->values()->all()
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
            $cities = collect($response);
            return response()->json([
                'data' => $cities->values()->all()
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: "api/v2/city",
        summary: "Store a newly created resource in storage.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
                requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/CityRequest'
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
                    ref: '#/components/schemas/City'
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
    public function store(StoreCityRequest $request)
    {
        return $this->createFromAttributes($request->toArray());
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: "api/v2/city/{city}",
        summary: "Display the specified City.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Places"],
        parameters: [
            new OA\Parameter(
                name: "city",
                description: "The City identifier to retrieve.",
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
                        ref: '#/components/schemas/City'
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
    public function show(City $city)
    {
        return $this->getMainModelInstance($city->id);
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: "api/v2/city/{city}",
        summary: "Updates the specified City.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
                requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/CityRequest'
            )
        ),
        tags: ["Places"],
        parameters: [
            new OA\Parameter(
                name: "city",
                description: "The City identifier to update.",
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
                    ref: '#/components/schemas/City'
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
    public function update(UpdateCityRequest $request, City $city)
    {
        return $this->updateModel($this->getMainModelInstance($city->id),$request->toArray());
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: "api/v2/city/{city}",
        summary: "Remove the specified City.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Places"],
        parameters: [
            new OA\Parameter(
                name: "city",
                description: "The City identifier to remove.",
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
    public function destroy(City $city)
    {
        return $this->destroyResourceEmptyResponse($city);
    }
}

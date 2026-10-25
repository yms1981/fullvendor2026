<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCountryRequest;
use App\Http\Requests\UpdateCountryRequest;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class CountryController extends Controller
{
    protected $resourceRoute = 'country';

    /**
     * Sobrescribir getFilteredModelQuery para ignorar filtros de company_id
     * Los países son datos maestros y deben devolverse todos sin importar la compañía
     */
    protected function getFilteredModelQuery(\Illuminate\Http\Request $request = null)
    {
        if (!$request) {
            $request = request();
        }
        
        // Eliminar parámetros de listado y compañía; solo pasar al filtro campos que CountryFilter entiende
        $filterData = $request->all();
        $ignoreKeys = [
            'company', 'company_id', 'page', 'per_page', 'perPage',
            'props', 'relations', 'orderProperty', 'orderDirection', 'all'
        ];
        foreach ($ignoreKeys as $key) {
            unset($filterData[$key]);
        }

        return $this->getBuilder()->filter($filterData);
    }

    #[OA\Get(
        path: "api/v2/country",
        summary: "Display a listing of the Country.",
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
                        items: new OA\Items(ref: '#/components/schemas/Country')
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
     * Obtener todos los países sin paginación
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Get(
        path: "api/v2/country/all",
        summary: "Obtener todos los países sin paginación",
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
                description: "Lista de todos los países",
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Country')
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
    public function all(Request $request)
    {
        // Obtener el usuario autenticado
        $user = $request->user();
        
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
        
        // Forzar all=true para obtener todos sin paginación
        // Los países son datos maestros, no se filtran por compañía
        $request->merge(['all' => true]);
        
        // Usar getModelList que ya maneja el parámetro 'all'
        $response = $this->getModelList($request);
        
        // Si la respuesta es una colección directa (all=true, sin paginación)
        if ($response instanceof \Illuminate\Support\Collection || is_array($response)) {
            $countries = $response instanceof \Illuminate\Support\Collection ? $response : collect($response);
            
            // Mantener la misma estructura que /api/v2/country: envolver en {'data': [...]}
            return response()->json([
                'data' => $countries->values()->all()
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
            $countries = collect($response);
            return response()->json([
                'data' => $countries->values()->all()
            ]);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: "api/v2/country",
        summary: "Store a newly created resource in storage.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
                requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/CountryRequest'
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
                    ref: '#/components/schemas/Country'
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
    public function store(StoreCountryRequest $request): \Illuminate\Http\Resources\Json\JsonResource
     {
        return $this->createFromAttributes($request->toArray());
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: "api/v2/country/{country}",
        summary: "Display the specified Country.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Places"],
        parameters: [
            new OA\Parameter(
                name: "country",
                description: "The Country identifier to retrieve.",
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
                        ref: '#/components/schemas/Country'
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
    public function show($country)
    {
        return $this->getMainModelInstance($country);
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: "api/v2/country/{country}",
        summary: "Updates the specified Country.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
                requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/CountryRequest'
            )
        ),
        tags: ["Places"],
        parameters: [
            new OA\Parameter(
                name: "country",
                description: "The Country identifier to update.",
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
                    ref: '#/components/schemas/Country'
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
    public function update(UpdateCountryRequest $request, Country $country)
    {
        return $this->updateModel($this->getMainModelInstance($country->id),$request->toArray());
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: "api/v2/country/{country}",
        summary: "Remove the specified Country.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Places"],
        parameters: [
            new OA\Parameter(
                name: "country",
                description: "The Country identifier to remove.",
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
    public function destroy(Country $country): \Illuminate\Http\JsonResponse
    {
         if ($country->states()->exists()) {
             return response()->json(['message' => 'No se puede eliminar el pais porque tiene estados asociados.'], 400);
         }

        $country->delete();

        return response()->json(['success' => true],204);
    }
}

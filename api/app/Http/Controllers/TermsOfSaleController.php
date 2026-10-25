<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTermsOfSaleRequest;
use App\Http\Requests\UpdateTermsOfSaleRequest;
use App\Models\Company;
use App\Models\TermsOfSale;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use OpenApi\Attributes as OA;

class TermsOfSaleController extends Controller
{
    protected $resourceRoute = 'termsofsale';

    #[OA\Get(
        path: "api/v2/termsofsale",
        summary: "Display a listing of the TermsOfSale.",
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
                        items: new OA\Items(ref: '#/components/schemas/Termsofsale')
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
        path: "api/v2/termsofsale",
        summary: "Store a newly created resource in storage.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
                requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/TermsofsaleRequest'
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
                    ref: '#/components/schemas/Termsofsale'
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
    public function store(StoreTermsOfSaleRequest $request)
     {
         $validated = $request->validated();

         $existing = TermsOfSale::withTrashed()
             ->where('company_id', $validated['company_id'])
             ->where('name', $validated['name'])
             ->first();

         if ($existing && $existing->trashed()) {
             $existing->restore();
             if ((int) $existing->term_status !== 1) {
                 $existing->update(['term_status' => 1]);
             }
             if (!empty($validated['default'])) {
                 $this->updateDefaultTerm($validated['company_id'], $existing->term_id);
             }

             return $this->makeResource($existing->fresh(['company']));
         }

         $newTerm = $this->createFromAttributes($validated);

         if ($validated['default']) {
             $this->updateDefaultTerm($validated['company_id'],$newTerm->term_id);
         }

        return $newTerm;
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: "api/v2/termsofsale/{termsofsale}",
        summary: "Display the specified TermsOfSale.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Customer"],
        parameters: [
            new OA\Parameter(
                name: "termsofsale",
                description: "The Termsofsale identifier to retrieve.",
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
                        ref: '#/components/schemas/Termsofsale'
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
    public function show($termsOfSale)
    {
        return $this->getMainModelInstance($termsOfSale);
    }

    /**
     * Update the specified resource in storage.
     */
     #[OA\Put(
             path: "/api/v2/termsofsale/{id}",
             summary: "Updates the specified TermsOfSale.",
             security: [
                 [
                     'bearerAuth' => []
                 ]
             ],
             tags: ["Customer"],
             responses: [
                 new OA\Response(response: Response::HTTP_OK, description: "TermsOfSale updated successfully"),
                 new OA\Response(response: Response::HTTP_UNAUTHORIZED, description: "Unauthorized"),
                 new OA\Response(response: Response::HTTP_NOT_FOUND, description: "not found"),
                 new OA\Response(response: Response::HTTP_INTERNAL_SERVER_ERROR, description: "Server Error")
             ]
         )]
    public function update(UpdateTermsOfSaleRequest $request, $termsId)
    {
        $validated = $request->validated();
        $term = $this->getMainModelInstance($termsId);

        if (!$term) {
            return response()->json(['message' => 'Término no encontrado'], 404);
        }

        if ($term->isDefault && !$validated['default']) {
            return response()->json(['message' => 'No se puede desactivar el término default de la empresa.',], 422);
        }

        if (!$term->isDefault && $validated['default']) {
            if (!$term->term_status) {
                return response()->json([
                    'message' => 'No se puede utilizar como término por defecto dado que está desactivado.',
                ], 422);
            }

            $this->updateDefaultTerm($validated['company_id'],$term->term_id);
        }

        return $this->updateModel($term, $validated);
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: "api/v2/termsofsale/{termsId}",
        summary: "Remove the specified TermsOfSale.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Customer"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The Company identifier to remove.",
                in: "path",
                required: true
            ),
            new OA\Parameter(
                name: "termsId",
                description: "The Company identifier to remove.",
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
    public function destroy($termId): \Illuminate\Http\JsonResponse
    {
         $term = $this->getMainModelInstance($termId);

         if (!$term) {
             return response()->json(['message' => 'Término no encontrado'], 404);
         }

         if ($term->company_id != request()->input('company')) {
             return response()->json(['message' => 'No tiene permisos para modificar este término'], 403);
         }

         if ($term->isDefault) {
             return response()->json(['message' => 'No se puede eliminar el término por defecto'], 400);
         }

         if ($term->customers()->exists()) {
             return response()->json(['message' => 'No se puede eliminar el término porque tiene clientes asociados.'], 400);
         }

         $term->delete();

         return response()->json(['success' => true],204);
    }

    /**
     * @param $company_id
     * @return void
     */
    protected function updateDefaultTerm($company_id, $term_id): void
    {
        Company::where('company_id', $company_id)->update(['default_term_id' => $term_id]);
    }

    /**
     * Resuelve un término de venta para acciones de estado (enable/disable/setDefault).
     */
    protected function findTermForCompanyAction(int|string $termId): ?TermsOfSale
    {
        $termId = (int) $termId;
        if ($termId <= 0) {
            return null;
        }

        $term = TermsOfSale::query()->where('term_id', $termId)->first();
        if (!$term) {
            return null;
        }

        $companyId = (int) (request()->input('company_id') ?: request()->input('company') ?: 0);
        if ($companyId > 0 && (int) $term->company_id !== $companyId) {
            return null;
        }

        return $term;
    }

    protected function termNotFoundResponse()
    {
        return response()->json(['message' => 'Término no encontrado'], 404);
    }

    protected function getMainModelInstance($modelId)
    {
        $term = $this->findTermForCompanyAction($modelId);
        if (!$term) {
            abort(404, 'Término no encontrado');
        }

        $columns = request()->input('props', ['*']);
        $with = request()->input('relations', []);

        if ($columns === ['*'] && empty($with)) {
            return $term;
        }

        $query = TermsOfSale::query()->where('term_id', $term->term_id);
        if ($columns !== ['*']) {
            $query->select($columns);
        }
        if (!empty($with)) {
            $query->with($with);
        }

        return $query->first() ?? $term;
    }

    protected function getPaginatedModelList(Builder $filteredSortedModelQuery, array $columns, Request $request)
    {
        return $this->getBuilder()
            ->filter($request->all())
            ->orderBy('term_id','DESC')
            ->paginate();

    }

    #[OA\Get(
        path: "api/v2/termsofsale/company/{companyId}/{termsName}/exists",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Customer"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The Exist identifier to retrieve.",
                in: "path",
                required: true
            ),
            new OA\Parameter(
                name: "termsName",
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
    public function exists($companyId, $termsName): \Illuminate\Http\JsonResponse
    {
        return response()->json(
            $this->getBuilder()
                ->where('company_id', $companyId)
                ->where('name', $termsName)
                ->get()
                ->count() > 0
        );
    }

    #[OA\Get(
        path: "api/v2/termsofsale/company/{companyId}",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Customer"],
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
        return Company::find($companyId)->defaultTermOfSale;
    }

    #[OA\Put(
        path: "api/v2/termsofsale/company/{companyId}",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
                requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/CompanyRequest'
            )
        ),
        tags: ["Customer"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The Company identifier to update.",
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
    public function updateDefault(UpdateTermsOfSaleRequest $request, $companyId)
    {
        return $this->updateModel(
            $this->default($companyId),
            $request->toArray()
        );
    }

    #[OA\Get(
        path: "api/v2/termsofsale/company/{companyId}/name-{name}",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Customer"],
        parameters: [
            new OA\Parameter(
                name: "companyId",
                description: "The Company identifier to retrieve.",
                in: "path",
                required: true
            ),
            new OA\Parameter(
                name: "name",
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
    public function showByName($companyId, $name)
    {
        return $this->getBuilder()
            ->where('company_id', $companyId)
            ->where('name', $name)
            ->get();
    }

    #[OA\Put(
        path: "api/v2/termsofsale/{termId}/enable",
        summary: "Enable a TermsOfSale.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
        tags: ["Customer"],
        parameters: [
            new OA\Parameter(
                name: "termId",
                description: "The TermsOfSale identifier to enable.",
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
                        ref: '#/components/schemas/Termsofsale'
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
    public function enable($termId)
    {
        $term = $this->findTermForCompanyAction($termId);

        if (!$term) {
            return $this->termNotFoundResponse();
        }

        if ($term->term_status == 1) {
            return $term;
        }

        $term->update(['term_status' => 1]);

        return $term;
    }

    #[OA\Put(
        path: "api/v2/termsofsale/{termId}/disable",
        summary: "Disable a TermsOfSale.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
        tags: ["Customer"],
        parameters: [
            new OA\Parameter(
                name: "termId",
                description: "The TermsOfSale identifier to disable.",
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
                        ref: '#/components/schemas/Termsofsale'
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
    public function disable($termId)
    {
        $term = $this->findTermForCompanyAction($termId);

        if (!$term) {
            return $this->termNotFoundResponse();
        }

        if ($term->term_status == 0) {
            return $term;
        }

        if ($term->isDefault) {
            return response()->json(['message' => 'No se puede desactivar el término default de la empresa.'], 422);
        }

        $term->update(['term_status' => 0]);

        return $term;
    }

    public function setDefault($termId)
    {
        $term = $this->findTermForCompanyAction($termId);

        if (!$term) {
            return $this->termNotFoundResponse();
        }

        if (!$term->term_status) {
            return response()->json([
                'message' => 'No se puede utilizar como término por defecto dado que está desactivado.',
            ], 422);
        }

        $companyId = (int) (request()->input('company_id') ?: $term->company_id);
        $this->updateDefaultTerm($companyId, $term->term_id);

        return $term->fresh(['company']);
    }

    /**
     * Obtener todos los términos de venta de la compañía del usuario autenticado
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    #[OA\Get(
        path: "api/v2/termsofsale/all",
        summary: "Obtener todos los términos de venta de la compañía del usuario autenticado",
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
                description: "Lista de todos los términos de venta de la compañía",
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Termsofsale')
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
        $terms = $this->getModelList($request);
        
        // Si la respuesta es una colección directa (all=true, sin paginación)
        if ($terms instanceof \Illuminate\Support\Collection || is_array($terms)) {
            $termsCollection = $terms instanceof \Illuminate\Support\Collection ? $terms : collect($terms);
            
            // Mantener la misma estructura que /api/v2/termsofsale: envolver en {'data': [...]}
            return response()->json([
                'data' => $termsCollection->values()->all()
            ]);
        }
        // Si la respuesta tiene estructura paginada (con data, meta, links)
        elseif (is_object($terms) && isset($terms->data)) {
            return $terms;
        }
        // Si la respuesta es un array con estructura paginada
        elseif (is_array($terms) && isset($terms['data'])) {
            return response()->json($terms);
        }
        // Fallback: tratar como colección
        else {
            $termsCollection = collect($terms);
            return response()->json([
                'data' => $termsCollection->values()->all()
            ]);
        }
    }
}

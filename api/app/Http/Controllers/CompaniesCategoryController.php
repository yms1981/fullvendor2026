<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompaniesCategoryRequest;
use App\Http\Requests\UpdateCompaniesCategoryRequest;
use App\Models\CompaniesCategory;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class CompaniesCategoryController extends Controller
{
    protected $resourceRoute = 'companiescategory';

    #[OA\Get(
        path: "api/v2/companiescategory",
        summary: "Display a listing of the CompaniesCategory.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Company"],

        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: 'data',
                                                     type:     'array',
                                                     items:    new OA\Items(
                                                                   ref: '#/components/schemas/Companiescategory'
                                                               )
                                                 ),
                                                 new OA\Property(
                                                     property:   'meta',
                                                     type:       'object',
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
                                                                 ]
                                                 ),
                                                 new OA\Property(
                                                     property:   'links',
                                                     type:       'object',
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
                                                                 ]
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
     * Store a newly created resource in storage.
     */
    #[OA\Post(
        path: "api/v2/companiescategory",
        summary: "Store a newly created resource in storage.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content:  new OA\JsonContent(
                          ref: '#/components/schemas/CompaniescategoryRequest'
                      )
        ),
        tags: ["Company"],

        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: 'data',
                                                     ref:      '#/components/schemas/Companiescategory'
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
    public function store(StoreCompaniesCategoryRequest $request): \Illuminate\Http\Resources\Json\JsonResource
    {
        return $this->createFromAttributes($request->toArray());
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: "api/v2/companiescategory/{companiescategory}",
        summary: "Display the specified CompaniesCategory.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Company"],
        parameters: [
            new OA\Parameter(
                name:        "companiescategory",
                description: "The Companiescategory identifier to retrieve.",
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
                                                     ref:      '#/components/schemas/Companiescategory'
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
    public function show($companiescategory)
    {
        return $this->getMainModelInstance($companiescategory);
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: "api/v2/companiescategory/{companiescategory}",
        summary: "Updates the specified CompaniesCategory.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content:  new OA\JsonContent(
                          ref: '#/components/schemas/CompaniescategoryRequest'
                      )
        ),
        tags: ["Company"],
        parameters: [
            new OA\Parameter(
                name:        "companiescategory",
                description: "The Companiescategory identifier to update.",
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
                                                     ref:      '#/components/schemas/Companiescategory'
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
    public function update(UpdateCompaniesCategoryRequest $request, CompaniesCategory $companiescategory)
    {
        return $this->updateModel($companiescategory, $request->toArray());
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: "api/v2/companiescategory/{companiescategory}",
        summary: "Remove the specified CompaniesCategory.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Company"],
        parameters: [
            new OA\Parameter(
                name:        "companiescategory",
                description: "The Companiescategory identifier to remove.",
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
    public function destroy($companiesategory): ?Response
    {
        return $this->destroyResourceEmptyResponse($this->getMainModelInstance($companiesategory));
    }

    #[OA\Get(
        path: "api/v2/companiescategory/actives",
        summary: "TODO: Add summary",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Company"],

        responses: [
            new OA\Response(
                response:    Response::HTTP_OK,
                description: "Successful operation",
                content:     new OA\JsonContent(
                                 properties: [
                                                 new OA\Property(
                                                     property: 'data',
                                                     type:     'array',
                                                     items:    new OA\Items(type: 'boolean', example: 'true or false')
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
    public function actives()
    {
        return CompaniesCategory::where('category_status', 1)->get(); //1 = active
    }
}

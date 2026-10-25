<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConsoleconfigRequest;
use App\Http\Requests\UpdateConsoleconfigRequest;
use App\Models\Consoleconfig;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class ConsoleconfigController extends Controller
{
    protected $resourceRoute = 'consoleconfig';

    #[OA\Get(
        path: "api/v2/consoleconfig",
        summary: "Display a listing of the Consoleconfig.",
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
                        items: new OA\Items(ref: '#/components/schemas/Consoleconfig')
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
        path: "api/v2/consoleconfig",
        summary: "Store a newly created resource in storage.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
                requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/ConsoleconfigRequest'
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
                    ref: '#/components/schemas/Consoleconfig'
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
    public function store(StoreConsoleconfigRequest $request)
    {
        return $this->createFromAttributes($request->toArray());
    }

    /**
     * Display the specified resource.
     */
    #[OA\Get(
        path: "api/v2/consoleconfig/{consoleconfig}",
        summary: "Display the specified Consoleconfig.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Non Functional"],
        parameters: [
            new OA\Parameter(
                name: "consoleconfig",
                description: "The Consoleconfig identifier to retrieve.",
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
                        ref: '#/components/schemas/Consoleconfig'
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
    public function show(Consoleconfig $consoleconfig)
    {
        return $this->getMainModelInstance($consoleconfig->id);
    }

    /**
     * Update the specified resource in storage.
     */
    #[OA\Put(
        path: "api/v2/consoleconfig/{consoleconfig}",
        summary: "Updates the specified Consoleconfig.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],
                requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                ref: '#/components/schemas/ConsoleconfigRequest'
            )
        ),
        tags: ["Non Functional"],
        parameters: [
            new OA\Parameter(
                name: "consoleconfig",
                description: "The Consoleconfig identifier to update.",
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
                    ref: '#/components/schemas/Consoleconfig'
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
    public function update(UpdateConsoleconfigRequest $request, Consoleconfig $consoleconfig)
    {
        return $this->updateModel($this->getMainModelInstance($consoleconfig->id),$request->toArray());
    }

    /**
     * Remove the specified resource from storage.
     */
    #[OA\Delete(
        path: "api/v2/consoleconfig/{consoleconfig}",
        summary: "Remove the specified Consoleconfig.",
        security: [
            [
                'bearerAuth' => []
            ]
        ],

        tags: ["Non Functional"],
        parameters: [
            new OA\Parameter(
                name: "consoleconfig",
                description: "The Consoleconfig identifier to remove.",
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
    public function destroy(Consoleconfig $consoleconfig)
    {
        return $this->destroyResourceEmptyResponse($consoleconfig);
    }
}

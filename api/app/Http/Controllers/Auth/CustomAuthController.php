<?php

namespace App\Http\Controllers\Auth;

use App\Http\Responses\LoginSuccessJsonResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Auth', description: 'Autenticación Fortify / Sanctum (login MVC y apps)')]
class CustomAuthController implements LoginResponse
{
    #[OA\Post(
        path: '/api/v2/login',
        operationId: 'authLogin',
        summary: 'Login (email + password)',
        description: 'Autenticación Fortify. Devuelve user (con avatar_url), app_access y companies (cada una con avatar_url). Campos previos se mantienen.',
        security: [],
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'user@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret'),
                    new OA\Property(property: 'device_name', type: 'string', nullable: true, example: 'mvc', description: 'Nombre del token Sanctum (default: mvc)'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login OK',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'user',
                            type: 'object',
                            description: 'Usuario autenticado; incluye token Sanctum y avatar_url',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string'),
                                new OA\Property(property: 'email', type: 'string'),
                                new OA\Property(property: 'image', type: 'string', nullable: true, description: 'UUID o path legacy del avatar'),
                                new OA\Property(property: 'avatar_url', type: 'string', nullable: true, example: 'https://example.com/api/v2/images/uuid.webp'),
                                new OA\Property(property: 'token', type: 'string', description: 'Bearer Sanctum'),
                            ]
                        ),
                        new OA\Property(property: 'app_access', type: 'object'),
                        new OA\Property(
                            property: 'companies',
                            type: 'array',
                            items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'company_id', type: 'integer'),
                                    new OA\Property(property: 'name', type: 'string'),
                                    new OA\Property(property: 'avatar_url', type: 'string', nullable: true, example: 'https://example.com/api/v2/images/uuid.jpg'),
                                    new OA\Property(property: 'roles', type: 'object'),
                                ],
                                type: 'object'
                            )
                        ),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Credenciales inválidas / validación'),
        ]
    )]
    public function toResponse($request): JsonResponse
    {
        return LoginSuccessJsonResponse::forUser(Auth::user(), $request);
    }
}

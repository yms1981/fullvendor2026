<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CompanyGpsSeller',
    type: 'object',
    description: 'Seller de la compañía con info de último punto GPS',
    properties: [
        new OA\Property(property: 'user_id', type: 'integer', example: 1323),
        new OA\Property(property: 'name', type: 'string', example: 'Seller Demo'),
        new OA\Property(property: 'email', type: 'string', example: 'seller@fullvendor.com'),
        new OA\Property(property: 'image', type: 'string', nullable: true),
        new OA\Property(property: 'avatar_url', type: 'string', nullable: true, example: '/uploads/users/avatar.jpg'),
        new OA\Property(
            property: 'last_point_at',
            type: 'string',
            format: 'date-time',
            nullable: true,
            description: 'Fecha/hora del último punto GPS registrado (ISO 8601)'
        ),
    ]
)]
#[OA\Schema(
    schema: 'CompanyGpsPoint',
    type: 'object',
    description: 'Punto GPS individual de la tabla gpstracker',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'latitude', type: 'number', format: 'float', example: 4.60971),
        new OA\Property(property: 'longitude', type: 'number', format: 'float', example: -74.08175),
        new OA\Property(property: 'created', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'address', type: 'string', nullable: true, example: 'Calle 100 #10-20, Bogotá'),
        new OA\Property(property: 'deviceinfo', type: 'string', nullable: true, example: 'Android 14 / Pixel 7'),
        new OA\Property(property: 'login', type: 'integer', nullable: true, description: 'Identificador de sesión/login asociado (si aplica)'),
    ]
)]
#[OA\Schema(
    schema: 'CompanyGpsTrack',
    type: 'object',
    description: 'Ruta GPS de un seller en una fecha',
    properties: [
        new OA\Property(property: 'user_id', type: 'integer', example: 1323),
        new OA\Property(property: 'date', type: 'string', format: 'date', example: '2026-07-21'),
        new OA\Property(property: 'seller', ref: '#/components/schemas/CompanyGpsSeller', nullable: true),
        new OA\Property(property: 'points_count', type: 'integer', example: 42),
        new OA\Property(
            property: 'points',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/CompanyGpsPoint')
        ),
    ]
)]
#[OA\Schema(
    schema: 'CompanyGpsPointInput',
    type: 'object',
    required: ['latitude', 'longitude'],
    description: 'Punto GPS a registrar',
    properties: [
        new OA\Property(property: 'latitude', type: 'number', format: 'float', example: 4.60971, minimum: -90, maximum: 90),
        new OA\Property(property: 'longitude', type: 'number', format: 'float', example: -74.08175, minimum: -180, maximum: 180),
        new OA\Property(property: 'address', type: 'string', nullable: true, maxLength: 600),
        new OA\Property(property: 'deviceinfo', type: 'string', nullable: true),
        new OA\Property(property: 'login', type: 'integer', nullable: true),
        new OA\Property(
            property: 'created',
            type: 'string',
            format: 'date-time',
            nullable: true,
            description: 'Si se omite, se usa la hora del servidor'
        ),
    ]
)]
#[OA\Schema(
    schema: 'CompanyGpsStorePointsRequest',
    type: 'object',
    description: 'Body para registrar puntos. Acepta un único punto en la raíz o un arreglo `points` (máx. 200).',
    properties: [
        new OA\Property(property: 'company_id', type: 'integer', example: 39, description: 'Obligatorio si no se envía header X-Company-Id'),
        new OA\Property(property: 'latitude', type: 'number', format: 'float', example: 4.60971),
        new OA\Property(property: 'longitude', type: 'number', format: 'float', example: -74.08175),
        new OA\Property(property: 'address', type: 'string', nullable: true),
        new OA\Property(property: 'deviceinfo', type: 'string', nullable: true),
        new OA\Property(property: 'login', type: 'integer', nullable: true),
        new OA\Property(property: 'created', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(
            property: 'points',
            type: 'array',
            nullable: true,
            maxItems: 200,
            description: 'Lote de puntos. Si se envía, se ignora el punto suelto de la raíz.',
            items: new OA\Items(ref: '#/components/schemas/CompanyGpsPointInput')
        ),
    ]
)]
#[OA\Schema(
    schema: 'CompanyGpsCreatedPoint',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 101),
        new OA\Property(property: 'latitude', type: 'number', format: 'float'),
        new OA\Property(property: 'longitude', type: 'number', format: 'float'),
        new OA\Property(property: 'created', type: 'string', format: 'date-time', nullable: true),
    ]
)]
class CompanyGpsSchemas
{
}

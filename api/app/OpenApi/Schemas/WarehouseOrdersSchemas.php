<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'WarehouseOrdersListRequest',
    type: 'object',
    description: 'Filtros para listados de órdenes de almacén / inventario',
    properties: [
        new OA\Property(property: 'company_id', type: 'integer', example: 1),
        new OA\Property(property: 'warehouse_user_id', type: 'integer', nullable: true, example: 28),
        new OA\Property(property: 'warehouse_user', type: 'integer', nullable: true, description: 'Alias de warehouse_user_id'),
        new OA\Property(property: 'user_id', type: 'integer', nullable: true, description: 'Alias de warehouse_user_id'),
        new OA\Property(property: 'customer_id', type: 'integer', nullable: true),
        new OA\Property(property: 'estado', type: 'integer', nullable: true, description: 'cod_status (solo inventario)'),
        new OA\Property(property: 'status', type: 'integer', nullable: true, description: 'Alias de estado'),
        new OA\Property(property: 'desde_fecha', type: 'string', format: 'date', nullable: true, example: '2026-01-01'),
        new OA\Property(property: 'hasta_fecha', type: 'string', format: 'date', nullable: true, example: '2026-09-17'),
        new OA\Property(property: 'page', type: 'integer', example: 1),
        new OA\Property(property: 'per_page', type: 'integer', example: 25),
    ]
)]
#[OA\Schema(
    schema: 'WarehouseOrdersListResponse',
    type: 'object',
    description: 'Respuesta paginada de órdenes de almacén',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'message', type: 'string'),
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(type: 'object')
        ),
        new OA\Property(
            property: 'meta',
            type: 'object',
            properties: [
                new OA\Property(property: 'current_page', type: 'integer'),
                new OA\Property(property: 'per_page', type: 'integer'),
                new OA\Property(property: 'total', type: 'integer'),
                new OA\Property(property: 'last_page', type: 'integer'),
                new OA\Property(
                    property: 'status_codes',
                    type: 'array',
                    items: new OA\Items(type: 'integer'),
                    nullable: true
                ),
            ]
        ),
    ]
)]
class WarehouseOrdersSchemas
{
}

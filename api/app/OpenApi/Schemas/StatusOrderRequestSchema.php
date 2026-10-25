<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StatusorderRequest',
    properties: [
        new OA\Property(
            property: 'id_status',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'name_status_spanish',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'name_status_english',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'cod_status',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'color',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'disabled',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'sombrilla',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'color_icono',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'tipo_d',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'cannot_edit',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'can_show_data_asigned',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'send_email',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'deleted_at',
            type: 'string',
            format: 'date-time',
            nullable: true
        ),
    ]
)]
class StatusOrderRequestSchema {}


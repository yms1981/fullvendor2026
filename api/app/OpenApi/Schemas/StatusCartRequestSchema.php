<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'StatuscartRequest',
    properties: [
        new OA\Property(
            property: 'id_status',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'name_spa',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'name_eng',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'color',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'cod',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'class',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'selected_default',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'allow_toremove',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'multiple_cart',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'nosubmit_order',
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
class StatusCartRequestSchema {}


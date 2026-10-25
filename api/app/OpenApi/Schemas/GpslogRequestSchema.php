<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GpslogRequest',
    properties: [
        new OA\Property(
            property: 'company_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'user_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'login',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'infotext',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'created',
            type: 'string',
            format: 'date-time',
            nullable: true
        ),
    ]
)]
class GpslogRequestSchema {}


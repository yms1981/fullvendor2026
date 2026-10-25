<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GpstrackerRequest',
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
            property: 'longitude',
            type: 'number',
            format: 'float',
            nullable: true
        ),
        new OA\Property(
            property: 'latitude',
            type: 'number',
            format: 'float',
            nullable: true
        ),
        new OA\Property(
            property: 'created',
            type: 'string',
            format: 'date-time',
            nullable: true
        ),
        new OA\Property(
            property: 'login',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'address',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'deviceinfo',
            type: 'string',
            nullable: true
        ),
    ]
)]
class GpstrackerRequestSchema {}


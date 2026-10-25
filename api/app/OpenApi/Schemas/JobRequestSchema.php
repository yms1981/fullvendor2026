<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'JobRequest',
    properties: [
        new OA\Property(
            property: 'queue',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'payload',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'attempts',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'reserved_at',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'available_at',
            type: 'string',
            nullable: true
        ),
    ]
)]
class JobRequestSchema {}


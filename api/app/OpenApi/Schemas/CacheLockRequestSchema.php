<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CachelockRequest',
    properties: [
        new OA\Property(
            property: 'key',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'owner',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'expiration',
            type: 'string',
            nullable: true
        ),
    ]
)]
class CacheLockRequestSchema {}


<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Cache',
    properties: [
        new OA\Property(
            property: 'key',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'value',
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
class CacheSchema {}


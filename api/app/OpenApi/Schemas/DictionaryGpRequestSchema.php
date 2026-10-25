<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'DictionarygpRequest',
    properties: [
        new OA\Property(
            property: 'eng',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'esp',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'arg',
            type: 'string',
            nullable: true
        ),
    ]
)]
class DictionaryGpRequestSchema {}


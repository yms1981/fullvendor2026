<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ProrepetidoRequest',
    properties: [
        new OA\Property(
            property: 'company_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'sku',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'cuenta',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
    ]
)]
class ProRepetidoRequestSchema {}


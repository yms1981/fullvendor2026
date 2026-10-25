<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'InitialordernumberRequest',
    properties: [
        new OA\Property(
            property: 'company_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'initial_number',
            type: 'string',
            nullable: true
        ),
    ]
)]
class InitialOrderNumberRequestSchema {}


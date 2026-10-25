<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'DevicescompanytempRequest',
    properties: [
        new OA\Property(
            property: 'company_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'deviceinfo',
            type: 'string',
            nullable: true
        ),
    ]
)]
class DevicesCompanytempRequestSchema {}


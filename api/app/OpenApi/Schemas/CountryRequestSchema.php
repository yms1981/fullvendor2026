<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CountryRequest',
    properties: [
        new OA\Property(
            property: 'sortname',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'name',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'phonecode',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'time_zone',
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
class CountryRequestSchema {}


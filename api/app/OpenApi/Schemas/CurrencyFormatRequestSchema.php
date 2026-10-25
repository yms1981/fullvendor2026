<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CurrencyformatRequest',
    properties: [
        new OA\Property(
            property: 'CurrencyName',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'CurrencyCode',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'CurrencyFormat',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'currOutput',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'CodeRegional',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'format_id',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'order',
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
class CurrencyFormatRequestSchema {}


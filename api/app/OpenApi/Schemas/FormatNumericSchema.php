<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Formatnumeric',
    properties: [
        new OA\Property(
            property: 'format_id',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'numeric_format',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'radixPoint',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'groupSeparator',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'digits',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'default',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'code_regional',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'created_at',
            type: 'string',
            format: 'date-time',
            nullable: true
        ),
        new OA\Property(
            property: 'updated_at',
            type: 'string',
            format: 'date-time',
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
class FormatNumericSchema {}


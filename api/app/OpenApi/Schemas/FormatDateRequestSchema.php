<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'FormatdateRequest',
    properties: [
        new OA\Property(
            property: 'format_php',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'format_js',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'language_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'date_firstmonth',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'default',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'month_year',
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
class FormatDateRequestSchema {}


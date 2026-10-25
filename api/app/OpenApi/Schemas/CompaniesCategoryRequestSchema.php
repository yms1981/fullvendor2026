<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CompaniescategoryRequest',
    properties: [
        new OA\Property(
            property: 'category_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'category_name',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'category_status',
            type: 'string',
            nullable: true
        ),
        new OA\Property(
            property: 'category_created_at',
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
class CompaniesCategoryRequestSchema {}


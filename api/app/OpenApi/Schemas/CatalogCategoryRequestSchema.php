<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CatalogcategoryRequest',
    properties: [
        new OA\Property(
            property: 'catalog_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'category_id',
            type: 'integer',
            format: 'int64',
            nullable: true
        ),
        new OA\Property(
            property: 'order',
            type: 'integer',
            format: 'int32',
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
class CatalogCategoryRequestSchema {}

